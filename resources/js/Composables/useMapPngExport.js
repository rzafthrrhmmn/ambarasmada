import { ref } from 'vue';

/**
 * Cetak peta sebagai PNG.
 *
 * Peta MapLibre dirender di dalam WebGL canvas. Canvas itu tidak bisa dibaca
 * sebagai gambar kecuali constructor Map diberi `preserveDrawingBuffer: true`,
 * karena tanpa itu browser boleh membuang buffer-nya sesaat setelah frame
 * selesai digambar dan `toDataURL()` mengembalikan kanvas kosong.
 *
 * Berkas ini tidak memakai pustaka gambar apa pun. Gambar peta diambil dari
 * kanvas 2D yang dibuat di sini, lalu digabung dengan snapshot peta memakai
 * drawImage. Alasannya: proyek tidak punya html2canvas maupun jspdf, dan
 * menambahkan dependensi untuk satu fitur cetak terasa berlebihan.
 */

/** Palet cetak: terang, supaya hasil unduhan masih terbaca di printer. */
const INK = {
  paper: '#ffffff',
  title: '#1f2937',
  body: '#374151',
  muted: '#6b7280',
  rule: '#d1d5db',
  accent: '#8c510a',
  accentSoft: '#f3e8d8',
  frame: '#9ca3af',
  north: '#b91c1c',
  south: '#374151',
};

const FONT = "'Segoe UI', 'Helvetica Neue', Arial, sans-serif";

/**
 * Ukuran lembar cetak.
 *
 * Dipatok dan tidak lagi mengikuti isi. Versi lama mengukur tinggi dari jumlah
 * teks bahan ajar, jadi lembarnya jadi tinggi seperti halaman web: peta tinggal
 * jadi pita tipis di bagian atas, dan wilayah yang dipilih terlihat kecil
 * sampai sulit dibaca.
 *
 * Sekarang isinya cuma peta, jadi ukuran halaman adalah pilihan format, bukan
 * hasil pengukuran, dan pemakai yang memilihnya lewat orientation.
 *
 * Landscape adalah bawaan karena peta lebih terbaca mendatar dan daftar
 * komponen cetak juga butuh ruang mendatar. Tingginya tetap dipatok supaya
 * semua wilayah keluar dengan format sama.
 */
const SHEET_WIDTH = 1600;
const SHEET_HEIGHT = 900;

/**
 * Format lembar yang bisa dipilih pengguna.
 *
 * Dua pilihan yang diminta: landscape 16:9 dan potrait 3:4. Rasio memakai
 * pembulatan yang membuat lebarnya habis dibagi tiganya, jadi 1000 x 1333
 * sudah 3:4 betulan dan bukan 3:4 setengah.
 *
 * Tinggi potrait lebih besar dari landscape supaya area petanya masih tersisa
 * setelah dikurangi margin, header, dan kaki halaman.
 */
const SHEET_ORIENTATIONS = {
  landscape: { label: 'Landscape 16:9', width: SHEET_WIDTH, height: SHEET_HEIGHT },
  portrait: { label: 'Potrait 3:4', width: 1000, height: 1333 },
};

/** Format yang dipakai kalau pemanggil tidak menyebut format. */
const DEFAULT_ORIENTATION = 'landscape';

/** Daftar format untuk dipilih di dialog unduhan. */
export const SHEET_ORIENTATION_LIST = Object.entries(SHEET_ORIENTATIONS).map(
  ([value, preset]) => ({ value, label: preset.label }),
);

/**
 * Lambang yang dicetak di header lembar PNG.
 *
 * Yang dipakai adalah lambang resmi urutan organisasi Kepramukaan, bukan logo
 * ambalan yang bisa diunggah lewat pengaturan. Alasannya isi lembar ini adalah
 * bahan ajar: identitas lembar harus sama di semua wilayah yang diunduh, dan
 * logo yang bisa diganti admin membuat dua unduhan dari wilayah yang sama
 * terlihat seperti berasal dari dua sumber berbeda. Berkas asli 2789 x 659 ada
 * di media/; yang dilayani aplikasi turunannya yang sudah diperkecil supaya
 * header tidak mengunduh hampir satu megabyte setiap kali peta dicetak.
 *
 * Logonya mendatar, jadi kotak logo di header dibuat lebar dan pendek. Pakai
 * kotak persegi seperti sebelumnya akan mengecilkannya jadi seperti garis.
 */
export const SHEET_LOGO_URL = '/images/Logo_Urutan_Organiasasi_Kepramukaan.png';

/**
 * Ringkasan format lembar untuk ditampilkan sebagai pratinjau di dialog.
 *
 * Angkanya dihitung dengan fungsi yang sama dengan yang menggambar lembar, jadi
 * pratinjau ini tidak bisa berbeda dari berkas yang nanti diunduh. Kalau
 * perhitungannya ditulis ulang di halaman, cepat atau lambat keduanya akan
 * menyimpang dan pratinjau ini jadi tidak berguna justru karena itu.
 *
 * Yang dikembalikan adalah ukuran CSS lembar, ukuran area peta, dan ukuran
 * berkas PNG setelah dikalikan skala render.
 *
 * @param {string} [orientation]
 * @returns {{orientation:string,label:string,width:number,height:number,mapAspect:number,outputWidth:number,outputHeight:number}}
 */
export function describeSheet(orientation = DEFAULT_ORIENTATION) {
  const preset = SHEET_ORIENTATIONS[orientation] ?? SHEET_ORIENTATIONS[DEFAULT_ORIENTATION];
  // Logo tidak dimuat di sini, tapi ruangnya tetap harus dipesan supaya
  // pratinjau ini tidak menjanjikan area peta yang lebih besar dari yang
  // benar-benar ada. Kotak penuh dipakai karena gambar logonya tidak diketahui
  // di sini, dan overestimate hanya membuat pratinjau sedikit lebih kecil.
  const sheet = measureSheet({
    title: 'Peta Kontur - Contoh Wilayah',
    subtitle: 'Koordinat tengah 119,4321, -5,1234 | Zoom 11.0 | Skala 1:250.000',
    orientation,
    logo: { width: LOGO_MAX_WIDTH, height: LOGO_MAX_HEIGHT },
  });
  const scale = sheetScale(sheet);

  return {
    orientation: orientation in SHEET_ORIENTATIONS ? orientation : DEFAULT_ORIENTATION,
    label: preset.label,
    width: sheet.width,
    height: sheet.height,
    mapAspect: sheet.mapWidth / sheet.mapHeight,
    outputWidth: sheet.width * scale,
    outputHeight: sheet.height * scale,
  };
}

const MARGIN = 48;

/**
 * Batas tinggi area peta.
 *
 * Batas bawah cuma jaring pengaman: kalau judulnya luar biasa panjang, area
 * peta tidak boleh habis lalu judul menimpa peta.
 *
 * Batas atas memakai bagian dari tinggi lembar, bukan angka tetap. Angka tetap
 * hanya benar untuk satu format; dipakai di potrait, petanya akan mentok di
 * separuh halaman dan separuh bawahnya jadi ruang kosong yang tidak berguna.
 */
const MAP_MIN_HEIGHT = 260;
const MAP_MAX_RATIO = 0.82;

/**
 * Lebar minimum teks header setelah kolom logo dipotong.
 *
 * Kolom logo selebar LOGO_MAX_WIDTH, jadi pada format terlebar pun masih tersisa
 * lebih dari separuh lembar. Angka ini jaring pengaman saja: kalau someday
 * logonya jauh lebih lebar, judul dan subjudul tidak boleh tersempit sampai
 * satu huruf per baris.
 */
const MAP_MIN_WIDTH = 320;

/** Jarak area peta ke baris keterangan sumber di bawahnya. */
const FOOTER_GAP = 16;

/** Tinggi baris keterangan sumber: keterangan garis kontur dan kredit sumber. */
const FOOTER_ROW = 18;

/** Jarak baris keterangan sumber ke garis pemisah kaki halaman. */
const FOOTER_RULE_GAP = 12;

/** Tinggi baris paling bawah, yaitu waktu cetak dan nama sistem. */
const FOOTER_ROW_BOTTOM = 16;

/**
 * Total tinggi kaki halaman.
 *
 * Dijumlahkan dari tinggi tiap barisnya supaya menambah baris baru tidak
 * membuat isinya menimpa tepi kertas. Tinggi ini juga yang dipotong dari sisa
 * lembar saat menghitung tinggi area peta, sehingga kaki halaman yang bertambah
 * membuat peta mengecil, bukan justru menindih.
 */
const FOOTER_HEIGHT = FOOTER_ROW + FOOTER_RULE_GAP + FOOTER_ROW_BOTTOM;

/**
 * Skala render lembar cetak.
 *
 * Isi lembar digambar ulang di kanvas 2D, bukan difoto dari layar, jadi
 * menaikkan skala membuat setiap huruf, garis, dan simbol lebih tajam. Angkanya
 * bukan sembarang: lembar landscape 1600 x 900 dikali 3 jadi 4800 x 2700, yaitu
 * 13 juta piksel, masih di bawah batas luas kanvas yang biasa dipakai peramban
 * seluler (16,7 juta piksel). Dikali 4 sudah 19,4 juta piksel dan sebagian
 * peramban menolak kanvasnya sehingga PNG tersimpan kosong. Versi lama memakai
 * 2, jadi seluruh isi lembar sekarang digambar tiga kali lebih rapat.
 */
const SHEET_SCALE = 3;

/**
 * Batas luas kanvas yang aman untuk satu lembar.
 *
 * Jaring pengaman: kalau ukuran lembar someday diubah, skala otomatis
 * diturunkan alih-alih menghasilkan berkas PNG yang gagal dibuat.
 */
const MAX_SHEET_PIXELS = 16_000_000;

/**
 * Sisi terpanjang kanvas WebGL yang boleh diminta saat ekspor.
 *
 * Kanvas peta adalah render target WebGL, bukan kanvas 2D, jadi batasnya lebih
 * rendah: 4096 piksel adalah ukuran yang hampir semua GPU seluler anggap aman.
 * MapLibre menurunkan rasio piksel secara diam-diam kalau kanvasnya melewati
 * `maxCanvasSize`, jadi batasnya lebih baik dihitung di sini daripada diharapkan.
 */
const EXPORT_CANVAS_MAX_SIDE = 4096;

/** Batas atas rasio piksel ekspor, supaya tidak berlebihan saat zoom tinggi. */
const EXPORT_MAX_PIXEL_RATIO = 4;

/**
 * Batas luas kanvas WebGL saat ekspor.
 *
 * Batas sisi terpanjang saja tidak cukup untuk format potrait, karena
 * kanvasnya menjadi tinggi dan sempit. Batas luas ini yang menjaga
 * framebuffer tetap muat di GPU seluler.
 */
const EXPORT_CANVAS_MAX_PIXELS = 12_000_000;

/**
 * Lebar kanvas peta CSS saat ekspor.
 *
 * Dipakai sebagai permukaan temporer supaya kanvas peta dapat dibuat landscape
 * dan selebar ini walaupun layar pemakainya sempit. Lebarnya sengaja tidak
 * mengikuti lebar layar: yang sedang diolah adalah berkas PNG, bukan tampilan
 * di layar.
 */
const EXPORT_SURFACE_WIDTH = 1400;

/**
 * Skala lembar yang aman untuk ukuran lembar sekarang.
 *
 * @returns {number}
 */
function sheetScale(sheet) {
  const area = sheet.width * sheet.height;
  const capped = Math.sqrt(MAX_SHEET_PIXELS / area);
  return Math.max(1, Math.min(SHEET_SCALE, capped));
}

/**
 * Batas ukuran logo di header, dalam satuan CSS lembar.
 *
 * Logo tidak boleh memakai seluruh tinggi header: judul dan subjudul tetap
 * harus punya ruangnya masing-masing di sebelah kanan dan di bawahnya.
 *
 * Kotaknya mendatar karena lambang urutan organisasi Kepramukaan berbentuk
 * memanjang (sekitar 4:1). Versi sebelumnya memakai kotak persegi 64 x 64, dan
 * gambar setinggi 64 dengan rasio 4:1 hanya jadi setinggi 15: logo yang sudah
 * kecil makin tidak terbaca karena diperkecil, bukan karena ruangnya kurang.
 */
const LOGO_MAX_WIDTH = 224;
const LOGO_MAX_HEIGHT = 56;

/** Jarak logo ke teks di sebelahnya dan ke baris pertama yang ada di bawahnya. */
const LOGO_GAP = 20;

/**
 * Ukuran logo di dalam kotak header, dengan rasio gambar tetap terjaga.
 *
 * Dipakai oleh pengukur tinggi dan penggambar, jadi keduanya tidak bisa
 * berbeda jawaban soal berapa ruang yang dimakan logo.
 *
 * @param {HTMLImageElement|null|undefined} image
 * @returns {{width:number, height:number}|null}
 */
function logoBox(image) {
  if (!image || !(image.width > 0) || !(image.height > 0)) {
    return null;
  }

  const ratio = Math.min(LOGO_MAX_WIDTH / image.width, LOGO_MAX_HEIGHT / image.height);

  return {
    width: Math.max(1, Math.round(image.width * ratio)),
    height: Math.max(1, Math.round(image.height * ratio)),
  };
}

/** Logo yang sudah dimuat, dikunci per URL supaya tidak diunduh dua kali. */
const logoCache = new Map();

/**
 * Muat logo untuk digambar di header lembar.
 *
 * Gambar dari domain lain hanya boleh digambar ke kanvas kalau server-nya
 * mengirim header CORS. Kalau tidak, satu piksel pun gambarnya akan membuat
 * seluruh kanvas tercemar dan toBlob gagal dengan SecurityError, sehingga
 * seluruh PNG gagal dibuat gara-gara satu logo. Karena itu logo yang gagal
 * dimuat dilewati saja, dan hasilnya tetap PNG tanpa logo.
 *
 * Hasilnya disimpan per URL: pengukuran lembar dan penggambar keduanya butuh
 * logo, dan tanpa cache keduanya akan memuat berkas yang sama dua kali.
 *
 * @param {string|null|undefined} url
 * @returns {Promise<HTMLImageElement|null>}
 */
function loadLogo(url) {
  if (!url) {
    return Promise.resolve(null);
  }

  if (!logoCache.has(url)) {
    logoCache.set(url, new Promise((resolve) => {
      const image = new Image();

      image.crossOrigin = 'anonymous';

      image.onload = () => resolve(image);
      // onerror termasuk logo yang tidak ditemukan dan penolakan CORS. Keduanya
      // berakhir sama saja: tanpa logo.
      image.onerror = () => resolve(null);

      image.src = url;
    }));
  }

  return logoCache.get(url);
}

/**
 * Hitung ukuran lembar dan area petanya untuk isi tertentu.
 *
 * Tinggi area peta bergantung pada jumlah baris judul dan subjudul, dan itu
 * hanya bisa diketahui setelah teksnya diukur. Fungsi ini dipakai dua tempat:
 * `buildMapPng` untuk benar-benar menggambar, dan jalur ekspor untuk mengetahui
 * rasio sisi kotak peta lebih dulu. Dipisah supaya keduanya tidak bisa berbeda
 * jawaban, yang akan membuat kanvas peta dibetulkan ke ukuran yang salah lalu
 * muncul pita abu-abu di tepi PNG.
 *
 * @param {{ title?: string, subtitle?: string, orientation?: string }} [options]
 * @returns {{width:number, height:number, mapWidth:number, mapHeight:number, orientation:string}}
 */
function measureSheet({ title = '', subtitle = '', orientation = DEFAULT_ORIENTATION, logo = null } = {}) {
  const preset = SHEET_ORIENTATIONS[orientation] ?? SHEET_ORIENTATIONS[DEFAULT_ORIENTATION];

  const width = preset.width;
  const height = preset.height;
  const mapWidth = width - MARGIN * 2;
  const probe = document.createElement('canvas').getContext('2d');

  // Kolom logo memakan lebar judul, jadi pemecahan baris judul harus memakai
  // lebar yang sama dengan yang dipakai menggambar.
  const reserved = logo ? logo.width + LOGO_GAP : 0;
  const textLeft = MARGIN + reserved;
  const textWidth = Math.max(MAP_MIN_WIDTH, mapWidth - reserved);

  setFont(probe, 32, '700');
  const titleLines = wrapText(probe, title, textWidth).length;
  setFont(probe, 14, '400');
  // Subjudul tetap selebar lembar: tidak ada yang mengapitinya di kanan.
  const subtitleLines = wrapText(probe, subtitle, mapWidth).length;

  // Logo, judul, dan subjudul berbagi satu header. Yang paling dalam menentukan
  // tempat baris pertama di bawah header. `stackTop` diukur dari MARGIN
  // supaya bisa langsung dipakai menghitung tinggi header, sedangkan
  // `subtitleTop` adalah koordinat gambarnya dan harus sudah mutlak.
  const titleHeight = titleLines * TITLE_LINE_HEIGHT + TITLE_GAP;
  const stackTop = Math.max(titleHeight, logo ? logo.height + LOGO_GAP : 0);
  const subtitleTop = MARGIN + stackTop;
  const headerHeight = stackTop + subtitleLines * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP;

  const available = height - MARGIN * 2 - headerHeight - FOOTER_GAP - FOOTER_HEIGHT;
  const maxMapHeight = Math.round(height * MAP_MAX_RATIO);
  const mapHeight = Math.round(Math.min(Math.max(available, MAP_MIN_HEIGHT), maxMapHeight));

  return {
    width,
    height,
    mapWidth,
    mapHeight,
    textLeft,
    textWidth,
    subtitleTop,
    orientation: preset.label ? orientation : DEFAULT_ORIENTATION,
  };
}

/**
 * Tempatkan snapshot di dalam kotak peta tanpa memotong bagian mana pun.
 *
 * Selalu memakai rasio kedua sumbu yang sama, sehingga gambar tidak pernah
 * teregang dan tidak ada bagian peta yang hilang. Kalau rasionya tidak sama
 * dengan kotak, sisanya dibiarkan sebagai latar abu-abu dan snapshot
 * dipusatkan.
 *
 * Karena tidak ada yang dipotong, batas geografis yang benar-benar terlihat
 * tetap sama dengan batas viewport, jadi grid koordinat tidak perlu dihitung
 * ulang.
 */
function fitContain(sourceWidth, sourceHeight, boxX, boxY, boxWidth, boxHeight) {
  if (!(sourceWidth > 0 && sourceHeight > 0)) {
    return { x: boxX, y: boxY, w: boxWidth, h: boxHeight };
  }

  const scale = Math.min(boxWidth / sourceWidth, boxHeight / sourceHeight);
  const w = sourceWidth * scale;
  const h = sourceHeight * scale;

  return {
    x: boxX + (boxWidth - w) / 2,
    y: boxY + (boxHeight - h) / 2,
    w,
    h,
  };
}

/** Baris judul, subjudul, dan jaraknya, dipakai bersama oleh pengukur tinggi dan penggambar. */
const TITLE_LINE_HEIGHT = 38;
const TITLE_GAP = 8;
const SUBTITLE_LINE_HEIGHT = 20;

/**
 * Jarak subjudul ke area peta.
 *
 * Di dalam jarak ini digambar garis pemisah header, jadi angkanya bukan
 * sekadar jarak kosong: kalau diperkecil, garis dan peta saling menempel dan
 * header terlihat seperti belum selesai.
 */
const SUBTITLE_GAP = 26;

/**
 * Ukuran elemen yang digantung di atas area peta.
 *
 * Nilai-nilai ini dipakai bersama oleh penggambar dan pengukur tinggi, dan
 * juga oleh penempatan grid koordinat. Panel-panel ini tidak boleh saling
 * tumpang tindih: legenda di kiri atas, histogram di kanan atas, kompas di
 * kanan bawah, dan skala di kiri bawah.
 *
 * `legendWidth` sengaja tidak ada: lebar legenda diukur dari label terpanjang
 * memakai measureText, jadi tidak bisa dipatok di sini.
 */
const PANEL = {
  legendRowHeight: 17,
  legendHeader: 28,
  histogramWidth: 220,
  histogramHeight: 150,
  compassRadius: 34,
  scaleBarMaxWidth: 220,
  inset: 16,
};

function setFont(ctx, size, weight = '400') {
  ctx.font = `${weight} ${size}px ${FONT}`;
}

/**
 * Pecah teks menjadi baris-baris yang muat dalam maxWidth.
 *
 * Canvas tidak punya pembungkus teks bawaan seperti CSS, jadi pemenggalan
 * dilakukan manual. Kata yang lebih panjang dari satu baris dipaksa terpotong
 * di tengah agar tidak pernah menggambar keluar kanvas.
 */
function wrapText(ctx, text, maxWidth) {
  const words = String(text).split(/\s+/).filter(Boolean);
  const lines = [];
  let current = '';

  for (const word of words) {
    const candidate = current ? `${current} ${word}` : word;

    if (ctx.measureText(candidate).width <= maxWidth) {
      current = candidate;
      continue;
    }

    if (current) {
      lines.push(current);
      current = '';
    }

    if (ctx.measureText(word).width <= maxWidth) {
      current = word;
      continue;
    }

    let chunk = '';
    for (const char of word) {
      if (ctx.measureText(chunk + char).width > maxWidth && chunk) {
        lines.push(chunk);
        chunk = char;
      } else {
        chunk += char;
      }
    }
    current = chunk;
  }

  if (current) {
    lines.push(current);
  }

  return lines.length ? lines : [''];
}

/**
 * Gambar mawar kompas yang berputar mengikuti orientasi peta.
 *
 * Mata angin wajib ada di peta hasil cetak: tanpa penanda utara, pembaca
 * tidak tahu sisi atas peta ke arah mana. Jarum utara diputar sebesar bearing
 * peta supaya tetap menunjuk utara geografis meski peta ikut berputar.
 */
function drawCompass(ctx, cx, cy, radius, bearing = 0) {
  ctx.save();
  ctx.translate(cx, cy);
  ctx.rotate((-bearing * Math.PI) / 180);

  // Cincin luar: latar putih lalu garis tepi, dari satu path yang sama.
  // Latarnya penting karena penanda utara harus tetap terbaca di atas
  // basemap gelap dan di atas garis kontur.
  ctx.beginPath();
  ctx.arc(0, 0, radius, 0, Math.PI * 2);
  ctx.fillStyle = 'rgba(255, 255, 255, 0.88)';
  ctx.fill();
  ctx.lineWidth = 2;
  ctx.strokeStyle = INK.frame;
  ctx.stroke();

  // Empat arah
  const points = [
    { label: 'U', angle: -Math.PI / 2 },
    { label: 'T', angle: 0 },
    { label: 'S', angle: Math.PI / 2 },
    { label: 'B', angle: Math.PI },
  ];

  for (const point of points) {
    const ax = Math.cos(point.angle);
    const ay = Math.sin(point.angle);
    const tipX = ax * radius * 0.66;
    const tipY = ay * radius * 0.66;

    ctx.beginPath();
    ctx.moveTo(tipX, tipY);
    ctx.lineTo(-ay * radius * 0.13, ax * radius * 0.13);
    ctx.lineTo(ay * radius * 0.13, -ax * radius * 0.13);
    ctx.closePath();
    ctx.fillStyle = point.label === 'U' ? INK.north : 'rgba(55, 65, 81, 0.55)';
    ctx.fill();

    setFont(ctx, 13, '700');
    ctx.fillStyle = point.label === 'U' ? INK.north : INK.body;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(point.label, ax * radius * 0.84, ay * radius * 0.84);
  }

  ctx.restore();
}

/**
 * Meter per piksel pada zoom dan lintang tertentu.
 *
 * Rumus Web Mercator yang sama dipakai Peta/Index.vue untuk menghitung skala
 * pada dialog cetak, supaya angka di PNG dan angka di halaman cetak tidak
 * berbeda.
 */
export function metersPerPixel(latitude, zoom) {
  return (156543.03392 * Math.cos((latitude * Math.PI) / 180)) / 2 ** zoom;
}

/**
 * Skala peta dalam bentuk 1:N.
 *
 * Meter per piksel WebGL memakai definisi 96 DPI yang sama dengan dokumen
 * HTML/CSS: satu inci layar dianggap 96 piksel. Skala 1:N berarti satu sentimeter
 * di peta cetak mewakili N sentimeter di dunia nyata, jadi N = meterPerPixel /
 * (0.0254 / 96).
 *
 * Angka ini hanya bermakna kalau ukuran cetaknya diketahui, dan berkas PNG
 * tidak punya ukuran fisika. Karena itu angkanya ditulis sebagai keterangan
 * pada subjudul, sementara bilah skala di dalam peta tetap jadi acuan yang
 * jujur: bilah itu diukur dari piksel dandpi yang sama.
 *
 * @param {number} latitude
 * @param {number} zoom
 * @returns {number}
 */
export function scaleDenominator(latitude, zoom) {
  const METERS_PER_INCH = 0.0254;
  const CSS_PIXELS_PER_INCH = 96;

  return metersPerPixel(latitude, zoom) / (METERS_PER_INCH / CSS_PIXELS_PER_INCH);
}

/**
 * Skala peta dalam bentuk 1:N, siap ditulis.
 *
 * Pembulatan dan format Locale-nya dikumpulkan di sini karena angkanya muncul
 * di tiga tempat: readout pada halaman peta, subjudul PNG, dan footer halaman
 * cetak. Kalau tiap tempat memformat sendiri, cepat atau lambat ada yang
 * menulis meter per piksel seolah-olah pembilang rasio.
 *
 * @param {number} latitude
 * @param {number} zoom
 * @returns {string}
 */
export function scaleLabel(latitude, zoom) {
  const denominator = Math.round(scaleDenominator(latitude, zoom));

  // Di dekat kutub cos(latitude) mendekati nol sehingga pembilangnya jatuh ke
  // angka yang tidak masuk akal. Menulis "1:0" lebih membingungkan daripada
  // tidak menulis apa pun.
  if (!Number.isFinite(denominator) || denominator <= 0) {
    return '1:-';
  }

  return `1:${denominator.toLocaleString('id-ID')}`;
}

/**
 * Panjang dunia nyata yang terwakili oleh sejumlah piksel.
 *
 * Dipakai untuk menyatakan berapa luas area yang benar-benar tertangkap. Untuk
 * skala 1:24.000 tidak ada angka yang jujur untuk ditulis di sini: berkas PNG
 * tidak punya ukuran fisika, sehingga "1:24.000" hanya bermakna kalau ukuran
 * cetaknya ditetapkan. Karena itu PNG memakai bilah skala yang mengukur
 * dirinya sendiri, ditambah lebar area dalam kilometer.
 */
export function distanceLabel(meters) {
  if (!Number.isFinite(meters) || meters <= 0) {
    return '-';
  }

  if (meters >= 1000) {
    const km = meters / 1000;
    return `${km >= 100 ? km.toFixed(0) : km.toFixed(1)} km`;
  }

  return `${Math.round(meters)} m`;
}

/**
 * Pilih jarak "bulat" terbesar yang masih muat dalam maxWidthPx.
 *
 * Kandidat dibaca dari yang terbesar ke terkecil. Versi lama memakai find()
 * pada daftar menaik, jadi selalu mengembalikan kandidat PERTAMA yang muat,
 * yaitu 1 meter: bilah skornya setebal 0,03 piksel dan ujungnya menulis
 * "1 m" padahal peta yang dicetak mencakup ratusan kilometer.
 */
function niceDistance(maxMeters, maxWidthPx, metersPerPx) {
  const candidates = [
    1, 2, 5, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000, 50000,
    100000, 200000, 500000, 1000000, 2000000,
  ];

  for (let i = candidates.length - 1; i >= 0; i--) {
    const meters = candidates[i];
    if (meters / metersPerPx <= maxWidthPx) {
      return meters;
    }
  }

  return maxMeters;
}

function formatDistance(meters) {
  return distanceLabel(meters);
}

/**
 * Komponen cetak yang bisa dipilih pengguna.
 *
 * Defaults-nya dipakai oleh Peta/Index yang tidak punya dialog komponen.
 * Peta/MapDenganPencarian meneruskan pilihan dari daftar "Komponen Peta Offline"
 * supaya berkas PNG sama persis dengan paket unduhan offline.
 */
export const DEFAULT_COMPONENTS = {
  scaleBar: true,
  northArrow: true,
  legend: true,
  histogram: false,
  grid: false,
};

/** Langkah grid "bulat" dalam derajat, dari yang paling rapat ke paling jarang. */
const GRID_STEPS = [0.001, 0.002, 0.005, 0.01, 0.02, 0.05, 0.1, 0.2, 0.5, 1, 2];

/**
 * Pilih langkah grid yang jarak antar garisnya sekitar `targetPx` piksel.
 *
 * @param {number} degreesPerPx  besar derajat yang dipetakan satu piksel
 * @param {number} targetPx
 */
function niceGridStep(degreesPerPx, targetPx) {
  const wanted = degreesPerPx * targetPx;

  for (const step of GRID_STEPS) {
    if (step >= wanted) return step;
  }

  return GRID_STEPS[GRID_STEPS.length - 1];
}

/**
 * Garis koordinat (grid) di atas area peta, lengkap dengan labelnya.
 *
 * Posisi garis diturunkan dari batas geografis yang benar-benar terlihat, yang
 * diambil dari map.getBounds(). Cara itu penting karena snapshot peta dipotong
 * di tengah supaya memenuhi kotak cetak: kotak cetak tidak pernah sama dengan
 * seluruh viewport, dan menghitung dari pusat/zoom sendiri akan menghasilkan
 * label yang tidak cocok dengan garisnya.
 *
 * Label lintang diletakkan di tepi yang tidak dipakai panel mana pun, dan
 * label bujur di tepi bawah yang tidak dipakai bilah skala. Tanpa itu, label
 * 119,65°BT bisa jatuh tepat di atas legenda atau histogram dan tidak terbaca.
 *
 * @param {{x:number,y:number,w:number,h:number}} box
 * @param {{west:number,east:number,south:number,north:number}} bounds
 * @param {{topLeft:boolean,topRight:boolean,bottomLeft:boolean,bottomRight:boolean}} reserved
 *   Panel yang menutupi tiap sudut area peta.
 */
function drawGraticule(ctx, box, bounds, reserved) {
  const spanLon = bounds.east - bounds.west;
  const spanLat = bounds.north - bounds.south;

  if (!(spanLon > 0) || !(spanLat > 0)) return;

  const stepLon = niceGridStep(spanLon / box.w, 110);
  const stepLat = niceGridStep(spanLat / box.h, 110);

  // Sisi label lintang dipilih dari sudut yang bebas. Kalau semua sudut tertutup
  // panel, label tetap ditulis di tepi kiri supaya kotaknya tidak pernah kosong.
  const latSide = !reserved.topLeft
    ? 'left'
    : !reserved.bottomLeft
      ? 'bottom'
      : !reserved.topRight
        ? 'right'
        : 'left';

  // Bilah skala menutupi sudut kiri bawah dan kompas sudut kanan bawah, jadi
  // label bujur pindah ke tepi atas kalau salah satunya aktif.
  const lonSide = reserved.bottomLeft || reserved.bottomRight ? 'top' : 'bottom';

  ctx.save();
  ctx.strokeStyle = 'rgba(31, 41, 55, 0.35)';
  ctx.lineWidth = 1;
  ctx.setLineDash([4, 3]);
  ctx.font = `11px ${FONT}`;
  ctx.fillStyle = 'rgba(31, 41, 55, 0.85)';

  for (let lon = Math.ceil(bounds.west / stepLon) * stepLon; lon <= bounds.east; lon += stepLon) {
    const x = box.x + ((lon - bounds.west) / spanLon) * box.w;

    ctx.beginPath();
    ctx.moveTo(x, box.y);
    ctx.lineTo(x, box.y + box.h);
    ctx.stroke();

    ctx.textAlign = 'center';

    if (lonSide === 'bottom') {
      ctx.textBaseline = 'bottom';
      ctx.fillText(formatDegrees(lon, 'lon'), x, box.y + box.h - 4);
    } else {
      ctx.textBaseline = 'top';
      ctx.fillText(formatDegrees(lon, 'lon'), x, box.y + 4);
    }
  }

  for (let lat = Math.ceil(bounds.south / stepLat) * stepLat; lat <= bounds.north; lat += stepLat) {
    const y = box.y + ((bounds.north - lat) / spanLat) * box.h;

    ctx.beginPath();
    ctx.moveTo(box.x, y);
    ctx.lineTo(box.x + box.w, y);
    ctx.stroke();

    if (latSide === 'left') {
      ctx.textAlign = 'left';
      ctx.textBaseline = 'top';
      ctx.fillText(formatDegrees(lat, 'lat'), box.x + 6, y + 4);
    } else if (latSide === 'right') {
      ctx.textAlign = 'right';
      ctx.textBaseline = 'top';
      ctx.fillText(formatDegrees(lat, 'lat'), box.x + box.w - 6, y + 4);
    } else {
      ctx.textAlign = 'left';
      ctx.textBaseline = 'bottom';
      ctx.fillText(formatDegrees(lat, 'lat'), box.x + 6, y - 4);
    }
  }

  ctx.setLineDash([]);
  ctx.restore();
}

function formatDegrees(value, axis) {
  const text = Math.abs(value) < 1e-9 ? '0' : value.toFixed(3).replace(/\.?0+$/, '');
  return axis === 'lon' ? `${text}°BT` : `${text}°LS`;
}

const LEGEND_ITEMS = [
  { color: '#8c510a', dash: [], width: 2.4, label: 'Kontur indeks (tebal)' },
  { color: '#8c510a', dash: [], width: 1, label: 'Garis kontur' },
  { color: '#2563eb', dash: [5, 3], width: 1.5, label: 'Batas kabupaten' },
];

/** Lebar tetap panel legenda, termasuk ruang untuk contoh simbol. */
const LEGEND_LABEL_X = 48;

/**
 * Tinggi panel legenda.
 *
 * Judul memakai baris yang sama dengan isi, jadi tinggi bisa dihitung dari
 * jumlah item saja. Dihitung di sini supaya penggambar dan penempatan grid
 * memakai angka yang sama.
 */
function legendHeight() {
  return PANEL.legendHeader + LEGEND_ITEMS.length * PANEL.legendRowHeight + 8;
}

/**
 * Legenda peta di pojok kiri atas area peta.
 *
 * Isinya mengulang simbol yang benar-benar ada di style: garis kontur tebal
 * (kontur indeks), garis kontur biasa, dan batas kabupaten. Legenda yang tidak
 * cocok dengan yang digambar membuat pembaca salah menafsirkan peta.
 */
function drawLegend(ctx, x, y) {
  ctx.save();
  setFont(ctx, 12, '700');

  // Lebar ikut label terpanjang, bukan angka tetap: label "Kontur indeks
  // (tebal)" pernah keluar kotak dan menutupi tepi peta.
  setFont(ctx, 11, '400');
  const width = Math.ceil(
    Math.max(
      ...LEGEND_ITEMS.map((item) => ctx.measureText(item.label).width),
      ctx.measureText('Legenda').width,
    ) + LEGEND_LABEL_X + 16,
  );
  const height = legendHeight();
  const radius = 8;

  ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
  ctx.strokeStyle = INK.rule;
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.roundRect(x, y, width, height, radius);
  ctx.fill();
  ctx.stroke();

  ctx.fillStyle = INK.title;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  ctx.fillText('Legenda', x + 12, y + 8);

  setFont(ctx, 11, '400');
  LEGEND_ITEMS.forEach((item, index) => {
    const rowY = y + 28 + index * PANEL.legendRowHeight;

    ctx.strokeStyle = item.color;
    ctx.lineWidth = item.width;
    ctx.setLineDash(item.dash);
    ctx.beginPath();
    ctx.moveTo(x + 12, rowY + 5);
    ctx.lineTo(x + 40, rowY + 5);
    ctx.stroke();
    ctx.setLineDash([]);

    ctx.fillStyle = INK.body;
    ctx.fillText(item.label, x + LEGEND_LABEL_X, rowY);
  });

  ctx.restore();
}

/**
 * Histogram elevasi.
 *
 * Data elevasi harus datang dari luar. Dulu modul ini membuat angka acak
 * supaya kotak histogram selalu terisi, dan angka acak itu ikut tercetak ke
 * bahan ajar seolah-olah data elevasi sungguhan. Sekarang tanpa data asli,
 * kotaknya diisi keterangan apa adanya, bukan grafik palsu.
 *
 * @param {{min:number,max:number,counts:number[],binSize:number}|null} stats
 */
function drawHistogram(ctx, x, y, w, h, stats) {
  ctx.save();
  ctx.fillStyle = 'rgba(255, 255, 255, 0.92)';
  ctx.strokeStyle = INK.rule;
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.roundRect(x, y, w, h, 8);
  ctx.fill();
  ctx.stroke();

  ctx.fillStyle = INK.title;
  setFont(ctx, 12, '700');
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  ctx.fillText('Histogram Elevasi', x + 12, y + 8);

  if (!stats || !stats.counts?.length) {
    setFont(ctx, 11, '400');
    ctx.fillStyle = INK.muted;
    ctx.fillText('Data elevasi tidak tersedia pada paket ini.', x + 12, y + 30);
    ctx.restore();
    return;
  }

  const max = Math.max(...stats.counts);
  const plotH = h - 46;
  const barW = (w - 24) / stats.counts.length;

  ctx.fillStyle = INK.accent;
  stats.counts.forEach((count, index) => {
    const barH = max > 0 ? (count / max) * plotH : 0;
    ctx.fillRect(x + 12 + index * barW, y + 26 + plotH - barH, barW - 1, barH);
  });

  setFont(ctx, 10, '400');
  ctx.fillStyle = INK.muted;
  ctx.fillText(`${Math.round(stats.min)} m`, x + 12, y + h - 16);
  ctx.textAlign = 'right';
  ctx.fillText(`${Math.round(stats.max)} m`, x + w - 12, y + h - 16);

  ctx.restore();
}

function drawScaleBar(ctx, x, y, metersPerPx, maxWidthPx) {
  const meters = niceDistance(Infinity, maxWidthPx, metersPerPx);
  const widthPx = meters / metersPerPx;

  ctx.save();

  // Latar putih supaya baris skala tetap terbaca di atas basemap gelap.
  const pad = 8;
  const barHeight = 10;
  const labelHeight = 16;

  ctx.fillStyle = 'rgba(255, 255, 255, 0.88)';
  ctx.fillRect(x - pad, y - pad, widthPx + pad * 2, barHeight + labelHeight + pad);

  // Bilah berselang-seling, konvensi kartografi umum.
  const segments = 4;
  const segmentWidth = widthPx / segments;

  for (let i = 0; i < segments; i += 1) {
    ctx.fillStyle = i % 2 === 0 ? INK.title : INK.paper;
    ctx.fillRect(x + i * segmentWidth, y, segmentWidth, barHeight);
  }

  ctx.strokeStyle = INK.title;
  ctx.lineWidth = 1.5;
  ctx.strokeRect(x, y, widthPx, barHeight);

  setFont(ctx, 12, '600');
  ctx.fillStyle = INK.title;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  ctx.fillText('0', x, y + barHeight + 3);
  ctx.textAlign = 'right';
  ctx.fillText(formatDistance(meters), x + widthPx, y + barHeight + 3);

  ctx.restore();
}

/**
* Susun PNG cetak peta pada lembar landscape.
 *
 * Isi lembar sekarang hanya peta: judul, peta, keterangan, dan kaki halaman.
 * Daftar uraian komponen peta kontur yang dulu dicetak di bawah peta sudah
 * dihapus karena ia membuat lembar tinggi dan peta mengecil.
 *
 * @param {object} options
 * @param {HTMLCanvasElement|null} options.mapCanvas  Snapshot peta. Null berarti
 *   area peta tidak bisa dibaca (lihat captureMapCanvas di bawah) dan lembar
 *   tetap dicetak dengan keterangan bahwa petanya tidak terbaca.
 * @param {number} options.latitude
 * @param {number} options.longitude
 * @param {number} options.zoom
 * @param {number} options.bearing
 * @param {string} options.title
 * @param {string} options.subtitle
 * @param {boolean} options.areaBlocked  True bila snapshot peta terhalang
 *   kebijakan CORS browser.
 * @param {string|null} options.mapProblem  Alasan kenapa snapshot peta kosong,
 *   misalnya kanvas berukuran nol atau kanvas terbaca tanpa isi.Null berarti
 *   snapshot ada dan isinya terbaca.
 * @param {object} options.components  Pilihan komponen dari dialog "Komponen
 *   Peta Offline". Key yang tidak disebut memakai DEFAULT_COMPONENTS.
 * @param {object|null} options.elevation  Statistik elevasi nyata untuk
 *   histogram: { min, max, counts }. Null berarti kotak histogram hanya
 *   menampilkan keterangan bahwa data tidak tersedia.
 * @param {{west:number,east:number,south:number,north:number}|null} options.bounds
 *   Batas geografis yang persis tertangkap di kotak peta. Tanpa ini grid
 *   koordinat dilewati karena posisinya tidak bisa ditentukan secara jujur.
 * @param {string} options.sources  Kredit sumber data yang dicetak di kaki
 *   lembar. Halaman yang memanggil wajib mengirim basemap yang sedang terlihat:
 *   MapLibre menulis atribusi ke elemen DOM di atas kanvas WebGL, jadi
 *   elemen itu tidak ikut masuk ke snapshot dan PNG tanpa kredit eksplisit
 *   menampilkan citra yang tidak dikreditkan. Nilai standarnya hanya berlaku
 *   untuk Peta/Index, yang cetakannya selalu memakai OSM.
 * @param {string|null} options.logo  Alamat lambang yang dicetak di header.
 *   Bawaannya adalah lambang urutan organisasi Kepramukaan (SHEET_LOGO_URL)
 *   supaya semua lembar, dari halaman mana pun, memakai identitas yang sama.
 *   Null berarti lembar dicetak tanpa lambang.
 * @returns {Promise<Blob>}
 */
export async function buildMapPng({
  mapCanvas = null,
  latitude = 0,
  longitude = 0,
  zoom = 10,
  bearing = 0,
  title = 'Peta Kontur Sulawesi',
  subtitle = '',
  areaBlocked = false,
  mapProblem = null,
  components = {},
  elevation = null,
  bounds = null,
  orientation = DEFAULT_ORIENTATION,
  logo = SHEET_LOGO_URL,
  sources = 'Sumber: © OpenStreetMap contributors, PMTiles Kontur Sulsel',
} = {}) {
  const picked = { ...DEFAULT_COMPONENTS, ...components };
  const preset = SHEET_ORIENTATIONS[orientation] ?? SHEET_ORIENTATIONS[DEFAULT_ORIENTATION];

  // Lebar area peta diperlukan untuk teks subjudul bawaan, jadi harus diketahui
  // sebelum kanvas digambar.
  const mapWidthMeters = metersPerPixel(latitude, zoom) * (preset.width - MARGIN * 2);

  // Teks yang benar-benar akan dicetak harus sudah diketahui sebelum kanvas
  // digambar, karena tinggi area peta bergantung pada jumlah baris judul dan
  // subjudul.
  const subtitleText = subtitle || `Lebar area ${distanceLabel(mapWidthMeters)}`;

  // Logo dimuat sebelum lembar diukur, karena ruang yang dipesan logo ikut
  // menentukan tinggi header. Kalau diukur tanpa logo, tinggi header yang
  // dipakai untuk menggambar jadi lebih besar dari yang dihitung dan isinya
  // terdorong menimpa area peta. Kegagalan memuatnya dilewati saja, lalu
  // lembar tetap dicetak tanpa logo.
  const logoImage = await loadLogo(logo);
  const logoSize = logoBox(logoImage);

  // Ukuran lembar dan area petanya diukur sekali di sini lalu dipakai untuk
  // menggambar. Pemanggil yang mengirim ukuran sendiri tidak boleh menimpanya:
  // tinggi peta harus mengikuti format yang dipatok, bukan format hasil
  // pengukuran pemanggil.
  const sheet = measureSheet({ title, subtitle: subtitleText, orientation, logo: logoSize });
  const { width, height, mapWidth, mapHeight } = sheet;
  const scale = sheetScale(sheet);

  const canvas = document.createElement('canvas');
  canvas.width = width * scale;
  canvas.height = height * scale;

  const ctx = canvas.getContext('2d');
  ctx.scale(scale, scale);

  ctx.fillStyle = INK.paper;
  ctx.fillRect(0, 0, width, height);

  // Logo duduk di kiri header dan judulnya digeser ke kanan, bukan ditumpuk
  // di atasnya. Logo menutupi judul akan membuat keduanya saling tumpang tindih
  // kalau judulnya panjang, dan panjangnya tergantung nama wilayah.
  if (logoSize) {
    ctx.drawImage(logoImage, MARGIN, MARGIN, logoSize.width, logoSize.height);
  }

  // Lebar dan posisi teks berasal dari pengukuran, bukan dihitung ulang di
  // sini: kalau pemecahan baris memakai lebar yang berbeda dari yang dipakai
  // menghitung tinggi header, hasilnya satu baris lebih banyak dan menimpa peta.
  const { textLeft, textWidth, subtitleTop } = sheet;

  // Judul, dipecah bila panjangnya melebihi lebar konten.
  let cursorY = MARGIN;
  setFont(ctx, 32, '700');
  ctx.fillStyle = INK.title;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  const titleLines = wrapText(ctx, title, textWidth);
  titleLines.forEach((line, index) => {
    ctx.fillText(line, textLeft, cursorY + index * TITLE_LINE_HEIGHT);
  });

  setFont(ctx, 14, '400');
  ctx.fillStyle = INK.muted;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  // Subjudul mulai di bawah logo, bukan di sampingnya. Logo bisa setinggi
  // LOGO_MAX_HEIGHT, sedangkan judul cuma satu baris, jadi kalau subjudul
  // langsung mengikuti judul, baris pertamanya akan berada di dalam kotak logo
  // dan teksnya tertutup gambarnya.
  const subtitleLines = wrapText(ctx, subtitleText, mapWidth);
  subtitleLines.forEach((line, index) => {
    ctx.fillText(line, MARGIN, subtitleTop + index * SUBTITLE_LINE_HEIGHT);
  });

  // Garis pemisah antara header dan peta. Tanpa itu, subjudul terakhir dan
  // bingkai peta jadi satu blok abu-abu yang tidak jelas di mana judul berhenti
  // dan peta dimulai.
  //
  // Garis digambar di dalam jarak SUBTITLE_GAP, bukan di luar, supaya ruangnya
  // sudah dihitung measureSheet dan tidak menambah tinggi lembar diam-diam.
  const headerRuleY = subtitleTop + subtitleLines.length * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP / 2;

  ctx.strokeStyle = INK.rule;
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.moveTo(MARGIN, headerRuleY);
  ctx.lineTo(width - MARGIN, headerRuleY);
  ctx.stroke();

  // Segmen tebal di ujung kiri garis. Garis tipis penuh saja membuat header
  // terbaca sebagai garis sembarang, jadi ujung kirinya ditebalkan agar
  // urutannya jelas: logo dan judul dulu, baru peta.
  ctx.fillStyle = INK.accent;
  ctx.fillRect(MARGIN, headerRuleY - 1.5, 72, 3);

  // Kursor harus ikut bertambah sesuai jumlah baris. Kalau hanya menambah tinggi
  // satu baris, subjudul dua baris menimpa kotak peta.
  cursorY = subtitleTop + subtitleLines.length * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP;

  // Area peta
  const mapX = MARGIN;
  const mapY = cursorY;

  // Batas geografis yang benar-benar tertangkap di dalam kotak cetak.
  // Snapshot tidak lagi dipotong, jadi batas yang digambar sama persis dengan
  // batas viewport: tidak ada wilayah yang hilang, dan grid koordinat tidak
  // perlu dihitung ulang dari potongan.
  const visibleBounds = bounds;
  let areaRect = { x: mapX, y: mapY, w: mapWidth, h: mapHeight };

  ctx.save();
  ctx.beginPath();
  ctx.rect(mapX, mapY, mapWidth, mapHeight);
  ctx.clip();

  ctx.fillStyle = '#e5e7eb';
  ctx.fillRect(mapX, mapY, mapWidth, mapHeight);

  if (mapCanvas && mapCanvas.width > 0 && mapCanvas.height > 0) {
    // Seluruh snapshot diletakkan di dalam kotak tanpa dipotong. Versi lama
    // memakai "cover": snapshot diperbesar sampai menutupi kotak, lalu
    // dipotong di tengah. Itulah yang membuat peta hasil unduhan tampak
    // ter-zoom dan sebagian wilayah tidak ikut tercetak.
    const placement = fitContain(mapCanvas.width, mapCanvas.height, mapX, mapY, mapWidth, mapHeight);
    ctx.drawImage(mapCanvas, placement.x, placement.y, placement.w, placement.h);

    // Panel dan grid digambar di atas gambar peta, bukan di atas seluruh kotak.
    // Kalau snapshot tertahan oleh batas MIN/MAX, sisanya jadi pita abu-abu dan
    // panel tidak boleh berdiri di sana karena tidak ada peta di bawahnya.
    areaRect = placement;
  } else {
    // Kotak peta kosong harus menjelaskan dirinya. Isi abu-abu polos tanpa
    // keterangan pernah membuat pengguna mengira peta ikut tercetak padahal
    // yang tersimpan hanya bahan ajarnya.
    setFont(ctx, 15, '700');
    ctx.fillStyle = INK.title;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('Area peta tidak ikut tercetak', mapX + mapWidth / 2, mapY + mapHeight / 2 - 22);

    setFont(ctx, 13, '400');
    ctx.fillStyle = INK.muted;

    const detail = areaBlocked
      ? 'Browser memblokir pembacaan tile karena izin CORS host peta.'
      : mapProblem
        ?? 'Kanvas peta tidak memberi isinya saat disalin.';

    const lines = wrapText(ctx, detail, mapWidth - 80);
    lines.forEach((line, index) => {
      ctx.fillText(line, mapX + mapWidth / 2, mapY + mapHeight / 2 + 6 + index * 20);
    });
  }

  // Panel digantung di empat sudut, masing-masing satu pemilik supaya tidak
  // saling menimpa. Versi lama menaruh kompas di pojok kanan atas, tempat
  // histogram juga diletakkan, sehingga keduanya bertumpuk dan jam norturya
  // menutupi sebagian histogram.
  const reserved = {
    topLeft: picked.legend,
    topRight: picked.histogram,
    bottomLeft: picked.scaleBar,
    bottomRight: picked.northArrow,
  };

  // Grid koordinat digambar paling awal di atas peta: garisnya yang jadi latar,
  // sementara legenda, histogram, kompas, dan skala menumpuk di atasnya. Semua
  // memakai areaRect, yaitu gambar peta yang benar-benar ada, bukan kotak cetaknya.
  if (picked.grid && visibleBounds) {
    drawGraticule(ctx, areaRect, visibleBounds, reserved);
  }

  if (picked.legend) {
    drawLegend(ctx, areaRect.x + PANEL.inset, areaRect.y + PANEL.inset);
  }

  if (picked.histogram) {
    drawHistogram(
      ctx,
      areaRect.x + areaRect.w - PANEL.inset - PANEL.histogramWidth,
      areaRect.y + PANEL.inset,
      PANEL.histogramWidth,
      PANEL.histogramHeight,
      elevation,
    );
  }

  // Kompas di pojok kanan bawah area peta, jauh dari histogram di kanan atas.
  if (picked.northArrow) {
    drawCompass(
      ctx,
      areaRect.x + areaRect.w - PANEL.inset - PANEL.compassRadius,
      areaRect.y + areaRect.h - PANEL.inset - PANEL.compassRadius,
      PANEL.compassRadius,
      bearing,
    );
  }

  // Skala di pojok kiri bawah area peta
  if (picked.scaleBar) {
    drawScaleBar(
      ctx,
      areaRect.x + PANEL.inset + 8,
      areaRect.y + areaRect.h - PANEL.inset - 26,
      metersPerPixel(latitude, zoom),
      PANEL.scaleBarMaxWidth,
    );
  }

  ctx.restore();

  // Bingkai area peta
  ctx.strokeStyle = INK.frame;
  ctx.lineWidth = 1.5;
  ctx.strokeRect(mapX, mapY, mapWidth, mapHeight);

  // Keterangan sumber dan kaki halaman.
  //
  // Tidak ada lagi daftar uraian komponen peta kontur di bawah peta. Uraian itu
  // cocok untuk lembar handout, tapi di sini ia hanya memperpanjang halaman dan
  // membuat peta mengecil sampai wilayah yang dipilih sulit dibaca.
  //
  // Kaki halaman disusun dua baris yang dipisah garis tipis: baris atas
  // membawa keterangan isi peta dan kredit datanya, baris bawah membawa waktu
  // cetak di kiri dan nama sistem di kanan. Semuanya ditumpangkan ke tepi peta
  // dengan tinggi yang sudah dipesan FOOTER_HEIGHT, bukan menempel ke tepi
  // kertas, sehingga posisinya tidak bergeser antarwilayah.
  cursorY = mapY + mapHeight + FOOTER_GAP;
  setFont(ctx, 12, '400');
  ctx.fillStyle = INK.muted;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';

  // Swatch garis kontur
  ctx.fillStyle = INK.accent;
  ctx.fillRect(MARGIN, cursorY + 4, 22, 4);
  ctx.fillStyle = INK.muted;
  ctx.fillText('Garis kontur (garis tebal = kontur indeks)', MARGIN + 30, cursorY);

  ctx.textAlign = 'right';
  ctx.fillText(sources, width - MARGIN, cursorY);

  // Garis pemisah di antara keterangan sumber dan baris paling bawah. Tanpa
  // garis, kedua baris terbaca sebagai satu paragraf panjang yang tidak
  // penting.
  const footerRuleY = cursorY + FOOTER_ROW + FOOTER_RULE_GAP / 2;

  ctx.strokeStyle = INK.rule;
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.moveTo(MARGIN, footerRuleY);
  ctx.lineTo(width - MARGIN, footerRuleY);
  ctx.stroke();

  const footerBottomY = cursorY + FOOTER_ROW + FOOTER_RULE_GAP;

  setFont(ctx, 11, '400');
  ctx.fillStyle = INK.muted;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  ctx.fillText(`Dicetak pada ${new Date().toLocaleString('id-ID')}`, MARGIN, footerBottomY);

  // Nama sistem di kanan, bukan menempel di belakang waktu cetak. Satu baris
  // yang memuat keduanya jadi panjang dan tidak imbang, dan tepi kanvas yang
  // sudah dipatok tidak boleh jadi tempat teks yang terpotong.
  ctx.textAlign = 'right';
  ctx.fillText('AMBARA - Sistem Digital Ambalan UPT SMAN 2 Maros', width - MARGIN, footerBottomY);

  return new Promise((resolve, reject) => {
    // Kanvas 2D yang sudah di-taint (gambar peta lintas origin) membuat toBlob
    // melempar SecurityError. Tanpa try di sini pesan errornya hilang dan
    // pemanggil hanya melihat Promise ditolak tanpa pesan.
    try {
      canvas.toBlob((blob) => {
        if (blob) {
          resolve(blob);
          return;
        }

        reject(new Error('Browser gagal mengubah kanvas menjadi PNG.'));
      }, 'image/png');
    } catch {
      reject(new Error('Area peta tidak bisa dibaca karena dimuat dari domain lain tanpa izin CORS.'));
    }
  });
}

/**
 * Meng_trigger unduhan PNG di browser.
 *
 * Cara ini memakai anchor + objectURL, bukan window.open, supaya nama file
 * benar-benar terpakai dan tidak membuka tab baru.
 */
export function triggerPngDownload(blob, filename) {
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();

  // ObjectURL tidak dikumpulkan browser, jadi harus dilepas sendiri. Timeout
  // singkat memastikan unduhan sudah mulai diambil sebelum URL dicabut.
  setTimeout(() => URL.revokeObjectURL(url), 10_000);
}

/**
 * Berapa banyak isi nyata yang terbaca dari kanvas peta.
 *
 * Kanvas peta bisa ada dan berukuran benar, tetapi kosong: itu yang
 * terjadi ketika salinannya diambil di luar frame tempat WebGL selesai
 * menggambar. Kotak peta lalu tergambar abu-abu polos dan berkas PNG tetap
 * tersimpan, sehingga pengguna mengira peta ikut tercetak padahal tidak ada.
 * Fungsi ini menghitung piksel yang bukan abu-abu latar supaya keadaan itu
 * terdeteksi, bukan diteruskan.
 *
 * @param {HTMLCanvasElement} canvas
 * @returns {number} 0 sampai 100
 */
function mapContentRatio(canvas) {
  const probe = document.createElement('canvas');
  const size = 64;
  probe.width = size;
  probe.height = size;
  const ctx = probe.getContext('2d', { willReadFrequently: true });

  ctx.drawImage(canvas, 0, 0, size, size);
  const { data } = ctx.getImageData(0, 0, size, size);

  let ink = 0;
  let first = null;

  for (let i = 0; i < data.length; i += 4) {
    const key = `${data[i]},${data[i + 1]},${data[i + 2]},${data[i + 3]}`;

    if (first === null) {
      first = key;
    } else if (key === first) {
      continue;
    }

    ink++;
  }

  return (ink / (data.length / 4)) * 100;
}

/**
 * Tunggu peta selesai memuat tile dan selesai menggambar.
 *
 * Dipakai setelah kamera dipindahkan: tanpa jeda ini snapshot bisa diambil
 * ketika tile wilayah baru belum selesai dimuat, sehingga peta hasil unduhan
 * ada lubuk putih di tempat yang seharusnya daratan.
 *
 * Batas waktunya penting supaya peta yang macet tidak menggantung unduhan.
 */
function waitForMapIdle(map, timeoutMs = 6000) {
  return new Promise((resolve) => {
    let settled = false;

    const finish = () => {
      if (settled) return;
      settled = true;
      map?.off?.('idle', finish);
      resolve();
    };

    map?.once?.('idle', finish);
    setTimeout(finish, timeoutMs);
  });
}

/**
 * Margin yang disisakan di sekeliling wilayah agar peta tidak menempel ke tepi.
 *
 * Marginnya sebagian dari ukuran kanvas, bukan angka tetap, karena panel
 * legenda, histogram, kompas, dan skala digambar di atas sudut peta. Tanpa
 * margin, sudut wilayah yang justru paling penting justru tertutup panel.
 * Padding dibatasi 26% supaya wilayah kecil tidak menyusut jadi titik.
 *
 * Padding bisa dimatikan untuk ekspor yang harus meniru apa yang sedang terlihat
 * di layar. Marginnya berguna untuk wilayah yang dipilih, karena panel legenda,
 * histogram, kompas, dan skala menumpuk di sudut peta sehingga sudut wilayah
 * tertutup panel. UntukPotret layar yang sedang tampil, margin justru membuat
 * berkasnya berbeda dari layar, padahal itu justru yang diminta pemakainya.
 *
 * @param {object} map
 * @param {boolean} [enabled=true]
 */
function regionPadding(map, enabled = true) {
  if (!enabled) {
    return { top: 0, bottom: 0, left: 0, right: 0 };
  }

  const canvas = map?.getCanvas?.();
  const width = canvas?.width ?? 0;
  const height = canvas?.height ?? 0;

  if (!(width > 0) || !(height > 0)) {
    return { top: 24, bottom: 24, left: 24, right: 24 };
  }

  const edge = Math.round(Math.min(width, height) * 0.06);
  const limit = (value) => Math.min(value, Math.round((width * 0.26)));

  return {
    top: edge,
    bottom: edge,
    left: limit(Math.round(width * 0.14)),
    right: limit(Math.round(width * 0.1)),
  };
}

/**
 * Batas geografis yang sedang terlihat di kanvas peta.
 *
 * Dipakai untuk dua hal: menyamakan isi PNG dengan isi layar, dan menuliskan
 * grid koordinat. Harus dibaca sebelum kanvas diubah ukurannya, karena luas
 * yang terlihat ikut berubah begitu ukuran kanvas berubah.
 *
 * @param {object} map
 * @returns {{west:number,south:number,east:number,north:number}|null}
 */
function visibleRegion(map) {
  try {
    const raw = map?.getBounds?.();

    if (!raw) {
      return null;
    }

    return {
      west: raw.getWest(),
      south: raw.getSouth(),
      east: raw.getEast(),
      north: raw.getNorth(),
    };
  } catch {
    // Peta yang belum selesai diukur tidak punya batas yang bisa dipercaya.
    return null;
  }
}

/**
 * Arahkan peta ke satu wilayah, lalu kembalikan cara mengembalikan kamera lama.
 *
 * Ini sumber utama bug "preview benar, hasil unduhan salah": snapshot PNG diambil
 * dari kanvas peta yang sedang tampil, jadi wilayah yang benar-benar ikut
 * tercetak adalah viewport pengguna, bukan wilayah yang dipilih di dialog.
 * Memusatkan kamera saja tidak cukup karena zoomnya juga harus muat seluruh
 * wilayah; karena itu dipakai fitBounds dan bukan jumpTo dengan zoom tetap.
 *
 * Kamera lama dikembalikan setelah snapshot diambil supaya pandangan pengguna
 * tidak berubah gara-gara ia mengunduh peta.
 *
 * @param {object} map
 * @param {{west:number,south:number,east:number,north:number}} region
 * @param {number} [maxZoom]  Batas zoom paket offline, dipakai sebagai pengaman
 *   supaya wilayah kecil tidak diperbesar melebihi isi arsip.
 * @param {{ pad?: boolean }} [options]  `pad: false` memakai area peta tanpa
 *   margin, supaya luas yang tercetak persis sama dengan yang terlihat di layar.
 * @returns {Promise<() => void>} Fungsi pemulih kamera.
 */
async function frameRegion(map, region, maxZoom, options = {}) {
  const previous = {
    center: map.getCenter(),
    zoom: map.getZoom(),
    bearing: map.getBearing(),
    pitch: map.getPitch(),
  };

  const restore = () => {
    try {
      map.jumpTo(previous);
    } catch {
      // Peta yang sudah dibuang tidak bisa dipulihkan; tidak ada yang perlu
      // dikembalikan ke pengguna karena halaman sudah tidak aktif.
    }
  };

  try {
    map.fitBounds(
      [
        // Urutannya bujur lalu lintang. MapLibre membaca setiap titik sebagai
        // [lng, lat]; versi lama menuliskannya [south, west] sehingga angka bujur
        // 119 terbaca sebagai lintang 119 dan fitBounds melempar "Invalid LngLat
        // latitude value". Kegagalan itu tertelan catch di bawah, jadi peta
        // tidak pernah bergerak dan PNG berisi viewport.
        [region.west, region.south],
        [region.east, region.north],
      ],
      {
        padding: regionPadding(map, options.pad !== false),
        bearing: 0,
        pitch: 0,
        // Tanpa animasi: yang dikejar adalah snapshot yang benar, dan animasi
        // hanya menambah waktu tunggu tanpa mengubah hasilnya.
        animate: false,
        duration: 0,
        ...(Number.isFinite(maxZoom) ? { maxZoom } : {}),
      },
    );
  } catch (error) {
    // Kegagalan ini wajib diteruskan, bukan ditelan. Kalau dibiarkan diam,
    // pengguna menerima berkas berisi viewport yang bukan wilayah pilihannya
    // tanpa ada petunjuk apa pun salahnya.
    return {
      restore: () => {},
      framed: false,
      reason: error?.message ?? 'Peta tidak bisa diarahkan ke wilayah yang dipilih.',
    };
  }

  await waitForMapIdle(map);

  return { restore, framed: true, reason: null };
}

/**
 * Rasio sisi yang dipakai kanvas peta saat ekspor.
 *
 * Dua hal yang membuat hasil unduhan dari ponsel berbeda dari hasil desktop,
 * dan keduanya berakar pada satu sebab: kanvas peta mengikuti ukuran layarnya.
 * Di desktop kanvasnya sudah landscape dan cukup lebar. Di ponsel kanvasnya
 * portrait dan sempit, sehingga snapshot-nya ikut portrait di dalam kotak peta
 * yang landscape. `fitContain` tidak pernah memotong, jadi yang terjadi bukan
 * terpotong melainkan pita abu-abu besar di kiri dan kanan. Resolusi petanya
 * sendiri ikut turun karena kanvas kecil lalu dilebarkan ke kotak cetak.
 *
 * Fungsi ini mengembalikan ukuran yang perlu dipakai agar keduanya beres:
 * landscape seperti desktop, dan cukup besar supaya kualitasnya tidak bergantung
 * pada besar kecilnya layar tempat pengguna menekan tombol cetak.
 *
 * @param {object} map
 * @param {number} targetAspect  Rasio sisi kotak peta di lembar cetak.
 * @returns {{ width:number, height:number, ratio:number }}
 */
function exportSurfaceFor(map, targetAspect) {


  // Kanvas selalu dibetulkan mengikuti kotak peta, bukan hanya ketika rasionya
  // meleset.
  //
  // Versi lama mempertahankan ukuran kanvas yang sedang terlihat supaya peta tidak
  // berkedip. Dua alasan membatalkan itu:
  //
  // 1. Kanvas yang dibiarkan apa adanya sering terlalu kecil. Di ponsel kanvas
  //    berpotret cuma beberapa ratus piksel, jadi bitmap-nya keluar sekitar
  //    1440 x 2000 padahal kotak cetaknya butuh 2712 x 3279. Peta jadi buram.
  // 2. Rasionya tetap meleset sedikit, jadi fitContain selalu menyisakan pita
  //    abu-abu dan sebagian lebar berkas terbuang.
  //
  // Kanvasnya dipulihkan setelah snapshot diambil, jadi pengguna hanya melihat
  // betulan sesaat, bukan kanvas yang tertinggal di ukuran ekspor.

  const width = EXPORT_SURFACE_WIDTH;
  const height = Math.round(EXPORT_SURFACE_WIDTH / targetAspect);

  // Rasio piksel tertinggi yang masih muat di bawah batas kanvas WebGL.
  const ceiling = Math.min(
    EXPORT_MAX_PIXEL_RATIO,
    EXPORT_CANVAS_MAX_SIDE / Math.max(width, 1),
    EXPORT_CANVAS_MAX_SIDE / Math.max(height, 1),
    // Batas luasnya yang membuat format potrait tetap aman. Kanvas potrait
    // menjulang, jadi batas sisi terpanjang tidak lagi cukup: 1400 x 1690 dengan
    // rasio 2,4 sudah 6,9 juta piksel dan sebagian GPU seluler kehabisan
    // memori di sana.
    Math.sqrt(EXPORT_CANVAS_MAX_PIXELS / Math.max(width * height, 1)),
  );

  // Yang dipakai adalah plafonnya, bukan rasio piksel yang sedang dipakai
  // perangkat.
  //
  // Dua-duanya dulu salah di sini dan tidak boleh kembali:
  //
  // - min(max(...)) selalu memilih nilai terkecil, sehingga di komputer dengan
  //   rasio piksel 1 hasilnya tetap 1 dan ketajaman tidak pernah bertambah.
  // - max(ratioSaatIni, plafon) membiarkan perangkat beresolusi tinggi melewati
  //   plafon. Di ponsel ber-dpr 3 dan format potrait, kanvasnya jadi 4200 x
  //   5079 yaitu 21 juta piksel, melewati batas 12 juta, dan sebagian GPU
  //   kehabisan memori atau kehilangan konteks WebGL.
  //
  // Plafon di atas sudah memperhitungkan rasio piksel perangkat lewat batas
  // sisi dan batas luasnya, jadi tidak ada yang hilang dengan memakainya.
  const ratio = Math.max(1, ceiling);

  return { width, height, ratio };
}

/**
 * Jalankan suatu pekerjaan dengan kanvas peta yang disiapkan khusus ekspor.
 *
 * MapLibre hanya bisa mengubah ukuran kanvas lewat mengubah ukuran wadahnya
 * lalu memanggil `resize()`. Peramban tidak punya cara lain, dan setiap
 * perubahan dikembalikan utuh setelah pekerjaan selesai supaya tampilan pengguna
 * tidak tersisa berubah gara-gara ia mengunduh peta.
 *
 * @param {object} map
 * @param {{ width:number, height:number, ratio:number }} surface
 * @param {() => Promise<unknown>} run
 * @returns {Promise<unknown>}
 */
async function withExportSurface(map, surface, run) {
  const container = map?.getContainer?.();

  // Tanpa wadah yang bisa diubah, tidak ada yang bisa disiapkan. Pemanggil
  // tetap jalan dengan kanvas apa adanya daripada gagal menulis berkas.
  if (!container) {
    return run();
  }

  const previousWidth = container.style.width;
  const previousHeight = container.style.height;
  const previousRatio = map.getPixelRatio?.();

  try {
    container.style.width = `${Math.round(surface.width)}px`;
    container.style.height = `${Math.round(surface.height)}px`;

    // setPixelRatio harus dipanggil sebelum resize: resize-lah yang memakai
    // rasio itu untuk menghitung ukuran buffer kanvas.
    if (Number.isFinite(surface.ratio) && surface.ratio > 0) {
      map.setPixelRatio?.(surface.ratio);
    }

    map.resize();
    await waitForMapIdle(map);

    return await run();
  } finally {
    container.style.width = previousWidth;
    container.style.height = previousHeight;

    if (Number.isFinite(previousRatio) && previousRatio > 0) {
      try {
        map.setPixelRatio?.(previousRatio);
      } catch {
        // Peta yang sudah dibuang tidak bisa dikembalikan; halaman sudah tidak
        // aktif sehingga tidak ada yang perlu diberitahukan ke pengguna.
      }
    }

    try {
      map.resize();
    } catch {
      // Sama seperti di atas: tidak ada yang bisa dilakukan kalau peta sudah mati.
    }
  }
}

export function useMapPngExport() {
  const isExporting = ref(false);
  const exportError = ref(null);

  /**
   * Ambil snapshot peta yang sedang tampil.
   *
   * MapLibre menggambar di requestAnimationFrame. Kalau kanvas disalin dari
   * luar frame itu, buffer WebGL bisa sudah dikosongkan dan hasilnya hijau muda
   * transparan yang naik jadi kotak kosong. Karena itu salinan diambil di
   * dalam event 'render', tepat setelah frame digambar, dan hasilnya diperiksa
   * supaya lapse diulang.
   *
   * @returns {Promise<{canvas: HTMLCanvasElement|null, blocked: boolean, reason: string|null}>}
   */
  async function captureMapCanvas(map) {
    if (!map) {
      return { canvas: null, blocked: false, reason: 'Peta belum siap, belum ada kanvas untuk disalin.' };
    }

    const source = (() => {
      try {
        return map.getCanvas?.() ?? null;
      } catch {
        return null;
      }
    })();

    if (!source || !source.width || !source.height) {
      return {
        canvas: null,
        blocked: false,
        reason: 'Kanvas peta berukuran nol. Peta mungkin sedang tersembunyi di balik dialog.',
      };
    }

    // Kanvas 2D tempat salinan diletakkan. Salinan dilakukan lewat kanvas 2D
    // supaya bisa langsung dipakai drawImage tanpa menyembunyikan masalah
    // kanvas WebGL yang sudah dipaksa menggambar.
    const copy = document.createElement('canvas');
    copy.width = source.width;
    copy.height = source.height;

    let blankReason = null;

    for (let attempt = 0; attempt < 3; attempt += 1) {
      try {
        // Paksa peta menggambar sekali lagi supaya frame dijamin terjadi.
        map.triggerRepaint?.();
      } catch {
        // Peta yang sudah dihancurkan tidak bisa dipaksa, tapi frame terakhir
        // masih bisa disalin.
      }

      // Tunggu frame selesai digambar, lalu salin di dalam frame yang sama.
      await new Promise((resolve) => {
        let settled = false;
        const done = () => {
          if (settled) return;
          settled = true;
          resolve();
        };

        try {
          map.once('render', () => {
            try {
              copy.getContext('2d').drawImage(source, 0, 0);
            } catch {
              blankReason = 'Kanvas peta tidak dapat disalin.';
            }
            done();
          });
        } catch {
          // Peta tanpa API event: salin langsung.
          try {
            copy.getContext('2d').drawImage(source, 0, 0);
          } catch {
            blankReason = 'Kanvas peta tidak dapat disalin.';
          }
          done();
          return;
        }

        // Jangan menggantung: peta yang idle dan tidak memancarkan 'render'
        // harus tetap menghasilkan sesuatu.
        setTimeout(() => {
          try {
            copy.getContext('2d').drawImage(source, 0, 0);
          } catch {
            blankReason = 'Kanvas peta tidak dapat disalin.';
          }
          done();
        }, 1200);
      });

      let ratio = 0;
      try {
        ratio = mapContentRatio(copy);
      } catch (error) {
        return { canvas: null, blocked: true, reason: error?.name ?? 'Area peta tidak bisa dibaca.' };
      }

      if (ratio > 1.5) {
        return { canvas: copy, blocked: false, reason: null };
      }

      blankReason = `Kanvas peta terbaca kosong (isi ${ratio.toFixed(1)}%).`;
    }

    return { canvas: null, blocked: false, reason: blankReason ?? 'Kanvas peta terbaca kosong.' };
  }

  /**
   * Cetak peta + bahan ajar kontur menjadi PNG lalu unduh otomatis.
   *
   * `region` membuat snapshot mengikuti wilayah yang dipilih pengguna di dialog
   * unduhan, bukan viewport yang sedang terlihat. Tanpa itu, peta mini di dialog
   * benar-benar menampilkan seluruh wilayah sementara berkas PNG-nya berisi
   * potongan yang kebetulan sedang dilihat.
   *
   * `matchViewport: true` menulis isi kanvas persis seperti yang tampil, dengan
   * luas yang sama dan tanpa margin. Dipakai tombol Cetak. Batas yang sedang
   * terlihat dibaca lebih dulu, sebelum ukuran kanvas diubah untuk resolusi
   * ekspor, karena luas yang tampil ikut berubah begitu ukuran kanvas berubah.
   * Di layar yang sudah landscape luasnya sama persis; di layar berpotret
   * pertahankan hasil landscape, jadi isinya sedikit lebih luas ke arah
   * kiri dan kanan, tidak pernah lebih sempit.
   *
   * @returns {Promise<{ ok: boolean, blocked?: boolean, error?: string|null, mapMissing?: boolean }>}
   */
  async function exportMapPng(map, overrides = {}) {
    if (isExporting.value) {
      return { ok: false, error: 'Ada proses cetak PNG lain yang sedang berjalan.' };
    }

    isExporting.value = true;
    exportError.value = null;

    // `filename`, `region`, `regionMaxZoom`, dan `matchViewport` bukan opsi gambar,
    // jadi dipisah sebelum diteruskan ke buildMapPng.
    const {
      filename,
      region = null,
      regionMaxZoom = null,
      matchViewport = false,
      orientation = DEFAULT_ORIENTATION,
      logo = SHEET_LOGO_URL,
      ...pngOptions
    } = overrides;
    const { subtitle: subtitleOverride, ...restOptions } = pngOptions;

    // Luas yang sedang terlihat harus dibaca SEBELUM kanvas diubah ukurannya.
    // Ukuran kanvas ikut menentukan berapa luas yang tampil, jadi batas yang
    // dibaca sesudahnya sudah menunjuk ke wilayah yang berbeda.
    //
    // matchViewport dipakai tombol Cetak: isinya harus sama dengan yang terlihat
    // di layar, bukan wilayah yang dipilih di dropdown dan bukan seluruh
    // Sulawesi. Dipakai bersama pad: false supaya area petanya persis sebesar
    // yang di layar, tanpa margin yang biasanya dipakai supaya panel cetakan
    // tidak menutupi sudut wilayah.
    const viewportRegion = matchViewport ? visibleRegion(map) : null;
    const targetRegion = region ?? viewportRegion;

    // Rasio sisi kotak peta di lembar harus diketahui lebih dulu, karena kanvas
    // peta disiapkan supaya ikut rasio itu. Subjudul yang dipakai untuk
    // menghitungnya belum lengkap karena detail koordinat baru ada setelah
    // kamera diarahkan, tetapi selisihnya hanya satu baris teks dan tidak
    // mengubah tinggi area peta secara berarti.
    // Logo ikut diukur di sini, bukan hanya saat menggambar. Kalau tidak,
    // rasio sisi yang dipakai menyiapkan kanvas peta berbeda dari rasio yang
    // benar-benar digambar, dan tepi PNG akan berisi pita abu-abu.
    const logoSize = logoBox(await loadLogo(restOptions.logo));
    const sheet = measureSheet({
      title: restOptions.title,
      subtitle: subtitleOverride,
      orientation,
      logo: logoSize,
    });
    const surface = exportSurfaceFor(map, sheet.mapWidth / sheet.mapHeight);

    try {
      // Kamera diarahkan ke wilayah lebih dulu, baru dibaca. Semua angka yang
      // dipakai berikutnya (pusat, zoom, batas, skala) diambil dari kamera yang
      // sudah sesuai wilayah, sehingga judul, bilah skala, dan grid koordinat
      // cocok dengan isi gambarnya.
      //
      // Semuanya berjalan di dalam permukaan ekspor, jadi snapshot diambil dari
      // kanvas yang sudah landscape dan resolusi tinggi. Peta pengguna, kanvas,
      // dan rasio pikselnya dikembalikan seperti semula setelah selesai.
      const printed = await withExportSurface(map, surface, async () => {
        let restoreCamera = null;
        let framingProblem = null;

        if (targetRegion && map?.fitBounds) {
          // Batas zoom paket offline hanya berlaku untuk wilayah yang dipilih.
          // Untuk tampilan yang sedang terlihat, zoom yang sedang dipakai
          // justru harus dipertahankan.
          const framing = await frameRegion(
            map,
            targetRegion,
            region ? regionMaxZoom : null,
            { pad: !matchViewport },
          );
          restoreCamera = framing.restore;
          framingProblem = framing.framed ? null : framing.reason;
        }

        try {
          const center = map?.getCenter?.() ?? { lat: 0, lng: 0 };
          const zoom = map?.getZoom?.() ?? 10;
          const { canvas, blocked, reason } = await captureMapCanvas(map);

          // Batas yang benar-benar tertangkap diambil dari peta, bukan dihitung
          // ulang dari pusat dan zoom: hanya peta yang tahu wilayah yang benar-
          // benar terlihat pada kanvas yang sedang direkam.
          //
          // Pembacaannya memakai helper yang sama dengan pembacaan batas untuk
          // matchViewport, jadi tidak ada dua cara berbeda untuk hal yang
          // mestinya sama. Helper itu juga yang menjaga agar batas dibaca lewat
          // getWest/getSouth/getEast/getNorth, bukan toArray(): LngLatBounds
          // .toArray() mengembalikan [[west, south], [east, north]], yaitu dua
          // pasang angka, bukan empat angka berurutan. Memecahnya seperti angka
          // berurutan membuat west berisi pasangan dan east berisi undefined,
          // sehingga selisihnya NaN dan grid koordinat diam-diam tidak pernah
          // digambar padahal penggunanya mencentangnya.
          const bounds = visibleRegion(map);

          // Subjudul digabung, bukan ditimpa. Versi lama menaruh seluruh
          // subjudul di dalam spread ...pngOptions, sehingga subtitle yang
          // dikirim halaman menimpa bagian ini dan Angka "Skala 1:..." yang
          // sempat dihitung tidak pernah muncul di PNG mana pun.
          const detail = `Koordinat tengah ${center.lng.toFixed(4)}, ${center.lat.toFixed(4)} | Zoom ${zoom.toFixed(1)}`
            + ` | Skala ${scaleLabel(center.lat, zoom)}`;
          const subtitle = subtitleOverride ? `${subtitleOverride} — ${detail}` : detail;

          const blob = await buildMapPng({
            mapCanvas: canvas,
            latitude: center.lat,
            longitude: center.lng,
            zoom,
            bearing: map?.getBearing?.() ?? 0,
            subtitle,
            areaBlocked: blocked,
            mapProblem: reason,
            bounds,
            orientation,
            logo,
            ...restOptions,
          });

          return { blob, canvas, blocked, reason, framingProblem };
        } finally {
          // Kamera dikembalikan walau cetak gagal. Kalau tidak, satu unduhan
          // yang bermasalah cukup untuk membuat peta utama tersesat ke wilayah
          // lain dan pengguna mengira tampilan itu memang pilihan mereka.
          restoreCamera?.();
        }
      });

      const { blob, canvas, blocked, reason, framingProblem } = printed;

      const stamp = new Date().toISOString().slice(0, 16).replace(/[:T]/g, '-');
      triggerPngDownload(blob, filename ?? `peta-kontur-${stamp}.png`);

      // Alasan kegagalan peta dikembalikan ke pemanggil supaya halaman bisa
      // memberi tahu pengguna. Tanpa ini, kotak peta kosong disertai status
      // "PNG tersimpan" dan pengguna mengira peta ikut tercetak.
      //
      // framingProblem sengaja dipisah dari mapProblem: artinya berbeda.
      // Berkas tetap tersimpan, tetapi wilayah yang dipilih bukan isi
      // gambarnya, dan itu harus disampaikan ke pengguna.
      return {
        ok: true,
        blocked,
        error: null,
        mapMissing: !canvas,
        mapProblem: reason,
        framingProblem,
        regionMatched: !framingProblem,
      };
    } catch (error) {
      // Pesan asli harus ikut dikembalikan. Kalau hanya disimpan di
      // exportError, pemanggil yang tidak punya akses ke ref itu hanya melihat
      // "gagal" tanpa sebab, sehingga galatnya tidak bisa ditelusuri.
      exportError.value = error?.message ?? 'Gagal membuat PNG peta.';
      return { ok: false, blocked: false, error: exportError.value };
    } finally {
      // Kamera dan ukuran kanvas sudah dikembalikan di dalam closure di atas,
      // jadi yang tersisa hanya melepas kunci cetak. Kuncinya harus dilepas
      // walau cetaknya gagal, kalau tidak tombol cetak tidak akan pernah bisa
      // dipakai lagi setelah satu kegagalan.
      isExporting.value = false;
    }
  }

  return { isExporting, exportError, exportMapPng };
}
