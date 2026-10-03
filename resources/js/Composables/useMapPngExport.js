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

/**
 * Isi bahan ajar yang ikut dicetak pada PNG.
 *
 * Dipisah dari kode gambar supaya teksnya mudah dibaca dan proofread tanpa
 * harus menelusuri perintah fillText. Halaman cetak (printMap) tidak memakai
 * daftar ini: hasil cetaknya hanya peta, tanpa bahan ajar.
 *
 * Setiap butir membawa `draw`: gambar sketsa kecil komponen itu. Daftar teks
 * saja memaksa pembaca membandingkan penjelasan dengan peta yang sedang
 * dilihat di layar atau di atas kertas, padahal keduanya tidak pernah berada
 * di tempat yang sama. Sketsa yang diletakkan di kiri butir membuat tiap
 * komponen punya bentuk yang bisa diingat, bukan sekadar nama.
 *
 * Penjelasan sengaja dibuat satu sampai dua baris. Bahannya untuk dipakai
 * mengulang di kelas, bukan untuk dibaca sendiri; uraian panjang hanya membuat
 * PNG setinggi banyak halaman tanpa menambah apa yang perlu dihafal.
 */
export const CONTOUR_COMPONENTS = [
  {
    title: 'Garis Kontur',
    text: 'Garis imajiner yang menghubungkan titik-titik dengan ketinggian sama dari permukaan laut.',
    draw: drawGarisKontur,
  },
  {
    title: 'Nilai Kontur',
    text: 'Angka pada garis kontur yang menunjukkan besarnya elevasi, biasanya dalam satuan meter.',
    draw: drawNilaiKontur,
  },
  {
    title: 'Interval Kontur',
    text: 'Jarak vertikal yang konstan antara dua garis kontur yang berurutan.',
    draw: drawIntervalKontur,
  },
  {
    title: 'Garis Kontur Indeks',
    text: 'Garis kontur yang digambar lebih tebal setiap kelipatan interval tertentu agar mudah dibaca.',
    draw: drawKonturIndeks,
  },
  {
    title: 'Indikator Relief & Kenampakan Medan',
    text: 'Kerapatan garis menggambarkan bentuk lahan: lereng landai renggang, lereng curam rapat, lembah membentuk V ke hulu, bukit melingkar.',
    draw: drawRelief,
  },
];

export const MAP_COMPLETENESS = [
  {
    title: 'Judul Peta',
    text: 'Menunjukkan identitas atau nama wilayah yang dipetakan.',
    draw: drawJudulPeta,
  },
  {
    title: 'Skala Peta',
    text: 'Perbandingan jarak pada peta dengan jarak sebenarnya di lapangan.',
    draw: drawSkalaPeta,
  },
  {
    title: 'Arah Utara / Orientasi',
    text: 'Tanda panah yang menunjukkan arah utara geografis.',
    draw: drawArahUtara,
  },
  {
    title: 'Legenda / Keterangan',
    text: 'Penjelasan mengenai simbol-simbol lain yang ada di dalam peta.',
    draw: drawLegenda,
  },
  {
    title: 'Grid Koordinat',
    text: 'Garis bujur dan lintang atau UTM untuk menentukan posisi titik pada peta.',
    draw: drawGrid,
  },
];

const FONT = "'Segoe UI', 'Helvetica Neue', Arial, sans-serif";
const MARGIN = 56;
/**
 * Tinggi kotak peta di lembar cetak.
 *
 * Tinggi tidak dipatok satu angka karena snapshot peta punya rasio aspek
 * sendiri, yaitu rasio jendela yang sedang dipakai pengguna. Kalau tinggi
 * dipatok, snapshot harus dipotong di tengah supaya memenuhi kotak, dan
 * potongan itulah yang membuat peta hasil unduhan tampak ter-zoom: bagian atas
 * dan bawah wilayah yang sedang dilihat hilang, lalu sisanya diperbesar.
 *
 * Tinggi dihitung dari rasio snapshot supaya seluruh isi snapshot ikut tercetak
 * apa adanya. Batas MIN dan MAX hanya menjaga supaya lembar tidak jadi
 * terlalu pendek atau terlalu tinggi; di luar batas itu snapshot placed dengan
 * cara "contain" (lihat fitContain) sehingga tidak ada yang terpotong.
 */
const MAP_MIN_HEIGHT = 420;
const MAP_MAX_HEIGHT = 820;

/** Rasio yang dipakai ketika tidak ada snapshot peta, supaya sheet tetap utuh. */
const MAP_DEFAULT_RATIO = 1.8;

/**
 * Tinggi kotak peta untuk lebar konten tertentu.
 *
 * Dipakai measureHeight dan buildMapPng supaya tinggi kanvas dan tinggi kotak
 * yang benar-benar digambar selalu sama.
 */
function mapAreaHeight(mapCanvas, contentWidth) {
  const usable = mapCanvas && mapCanvas.width > 0 && mapCanvas.height > 0;
  const ratio = usable ? mapCanvas.width / mapCanvas.height : MAP_DEFAULT_RATIO;
  const ideal = contentWidth / ratio;

  return Math.round(Math.min(Math.max(ideal, MAP_MIN_HEIGHT), MAP_MAX_HEIGHT));
}

/**
 * Tempatkan snapshot di dalam kotak peta tanpa memotong bagian mana pun.
 *
 * Selalu memakai rasio kedua sumbu yang sama, sehingga gambar tidak pernah
 * teregang. Kalau rasionya tidak sama dengan kotak, sisanya dibiarkan sebagai
 * latar abu-abu danSnapshot dipusatkan.
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

/**
 * Ukuran kotak sketsa tiap butir bahan ajar.
 *
 * Sketsa selalu setinggi ITEM_VISUAL_HEIGHT; teks di sebelahnya boleh lebih
 * pendek atau lebih panjang. Tinggi baris memakai yang paling besar di antara
 * keduanya supaya gambar dan teks tidak saling menimpa.
 */
const ITEM_VISUAL_WIDTH = 118;
const ITEM_VISUAL_HEIGHT = 82;
const ITEM_VISUAL_GAP = 16;

/** Baris judul, subjudul, dan jaraknya, dipakai bersama oleh pengukur tinggi dan penggambar. */
const TITLE_LINE_HEIGHT = 36;
const TITLE_GAP = 10;
const SUBTITLE_LINE_HEIGHT = 18;
const SUBTITLE_GAP = 20;

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

function drawParagraph(ctx, text, x, y, maxWidth, lineHeight) {
  const lines = wrapText(ctx, text, maxWidth);

  lines.forEach((line, index) => {
    ctx.fillText(line, x, y + index * lineHeight);
  });

  return y + lines.length * lineHeight;
}

/**
 * Jumlah baris judul peta.
 *
 * Nama wilayah bisa lebih panjang dari lebar kanvas, dan fillText tidak
 * membungkus teks. Tanpa dipecah, teks keluar kanvas dan hilang begitu file
 * disimpan, sementara tinggi kanvas tetap dihitung untuk satu baris. Karena itu
 * jumlah barisnya dipakai lagi oleh measureHeight.
 */
function titleLineCount(ctx, title, maxWidth) {
  setFont(ctx, 30, '700');
  return wrapText(ctx, title, maxWidth).length;
}

/**
 * Jumlah baris subjudul.
 *
 * Subjudul memuat koordinat, zoom, dan skala sekaligus, jadi panjangnya
 * bergantung pada nilai yang sedang dilihat dan tidak bisa dipatok satu baris.
 * Teks yang dihitung harus persis sama dengan yang digambar, kalau tidak
 * keduanya menghitung jumlah baris yang berbeda.
 */
function subtitleLineCount(ctx, text, maxWidth) {
  setFont(ctx, 13, '400');
  return wrapText(ctx, text, maxWidth).length;
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
 * Bingkai kotak sketsa bahan ajar.
 *
 * Latar diisi dan garis tepi dibuat di sini supaya sepuluh sketsa berikut
 * tidak mengulang hal yang sama dan kelihatan seragam di halaman.
 */
function beginThumb(ctx, x, y, width, height) {
  ctx.save();
  ctx.beginPath();
  ctx.rect(x, y, width, height);
  ctx.fillStyle = '#faf7f2';
  ctx.fill();
  ctx.lineWidth = 1;
  ctx.strokeStyle = INK.rule;
  ctx.stroke();
  ctx.clip();
  return { x, y, width, height };
}

/** Titik tengah kotak sketsa, dipakai sketsa yang menggambar dari tengah. */
function thumbCenter(box) {
  return { cx: box.x + box.width / 2, cy: box.y + box.height / 2 };
}

/**
 * Menggambar label kecil di dalam kotak sketsa.
 *
 * Label menggambar sendiri posisinya karena tiap sketsa punya tinggi yang
 * berbeda; yang sama di antaranya hanyalah ukuran, warna, dan perataan.
 */
function thumbLabel(ctx, text, cx, y) {
  setFont(ctx, 10, '600');
  ctx.fillStyle = INK.muted;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(text, cx, y);
}

/**
 * Garis kontur: beberapa garis tertutup yang mengelilingi satu bukit.
 *
 * Hanya garis, tanpa arsiran, karena yang dimaksud komponen ini adalah garis
 * imajiner itu sendiri, bukan bentuk lahan yang dilingkarinya.
 */
function drawGarisKontur(ctx, x, y, width, height) {
  const box = beginThumb(ctx, x, y, width, height);
  const { cx, cy } = thumbCenter(box);

  ctx.strokeStyle = INK.accent;
  ctx.lineWidth = 1.8;
  ctx.lineJoin = 'round';

  for (const scale of [0.82, 0.58, 0.34]) {
    const rx = (box.width * 0.38) * scale;
    const ry = (box.height * 0.34) * scale;
    ctx.beginPath();
    ctx.ellipse(cx, cy, rx, ry, 0, 0, Math.PI * 2);
    ctx.stroke();
  }

  thumbLabel(ctx, 'ketinggian sama', cx, box.y + box.height - 11);
  ctx.restore();
}

/**
 * Nilai kontur: satu garis kontur dengan celah kecil dan angka di dalamnya.
 *
 * Celahnya disengaja supaya angka terbaca sebagai label yang berdiri sendiri,
 * bukan sekadar tulisan yang menumpuk di atas garis.
 */
function drawNilaiKontur(ctx, x, y, width, height) {
  const box = beginThumb(ctx, x, y, width, height);
  const { cx, cy } = thumbCenter(box);
  const rx = box.width * 0.4;
  const ry = box.height * 0.36;

  ctx.strokeStyle = INK.accent;
  ctx.lineWidth = 1.8;
  ctx.beginPath();
  // Elips dipisah jadi dua busur supaya ada celah di kiri untuk angka.
  ctx.ellipse(cx, cy, rx, ry, 0, -Math.PI * 0.62, Math.PI * 0.62);
  ctx.stroke();
  ctx.beginPath();
  ctx.ellipse(cx, cy, rx, ry, 0, Math.PI * 0.86, Math.PI * 1.86);
  ctx.stroke();

  // Kotak putih di belakang angka supaya garis tidak melintas di bawahnya.
  ctx.fillStyle = '#faf7f2';
  ctx.fillRect(cx - 21, cy - 8, 42, 16);
  setFont(ctx, 13, '700');
  ctx.fillStyle = INK.title;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText('120', cx, cy);

  thumbLabel(ctx, 'elevasi (m)', cx, box.y + box.height - 11);
  ctx.restore();
}

/**
 * Interval kontur: dua garis berurutan dengan panah ganda di antaranya.
 *
 * Angka di panah menyatakan selisih ketinggian yang diukur tegak lurus kedua
 * garis, bukan jarak mendatar di layar.
 */
function drawIntervalKontur(ctx, x, y, width, height) {
  const box = beginThumb(ctx, x, y, width, height);
  const { cx, cy } = thumbCenter(box);
  const top = cy - 18;
  const bottom = cy + 14;
  const lineLeft = box.x + 16;
  const lineRight = box.x + box.width - 16;

  ctx.strokeStyle = INK.accent;
  ctx.lineWidth = 2;
  for (const lineY of [top, bottom]) {
    ctx.beginPath();
    ctx.moveTo(lineLeft, lineY);
    ctx.lineTo(lineRight, lineY);
    ctx.stroke();
  }

  // Garis bantu vertikal: batas diukur dari satu garis ke garis berikutnya.
  ctx.strokeStyle = INK.frame;
  ctx.lineWidth = 1;
  ctx.setLineDash([3, 3]);
  ctx.beginPath();
  ctx.moveTo(cx, top);
  ctx.lineTo(cx, bottom);
  ctx.stroke();
  ctx.setLineDash([]);

  // Kepala panah di kedua ujung garis bantu.
  ctx.strokeStyle = INK.title;
  ctx.lineWidth = 1.2;
  for (const [tipY, dir] of [[top, 1], [bottom, -1]]) {
    ctx.beginPath();
    ctx.moveTo(cx, tipY);
    ctx.lineTo(cx, tipY + 8 * dir);
    ctx.moveTo(cx - 4, tipY + 4 * dir);
    ctx.lineTo(cx, tipY + 8 * dir);
    ctx.lineTo(cx + 4, tipY + 4 * dir);
    ctx.stroke();
  }

  ctx.fillStyle = '#faf7f2';
  ctx.fillRect(cx + 6, cy - 9, 30, 18);
  setFont(ctx, 11, '700');
  ctx.fillStyle = INK.title;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'middle';
  ctx.fillText('10 m', cx + 6, cy - 1);

  ctx.restore();
}

/**
 * Garis kontur indeks: garis sama tebal, tapi setiap kelipatan yang satu
 * digambar lebih tebal.
 */
function drawKonturIndeks(ctx, x, y, width, height) {
  const box = beginThumb(ctx, x, y, width, height);
  const { cx } = thumbCenter(box);
  const startY = box.y + 14;
  const endY = box.y + box.height - 22;
  const left = box.x + 14;
  const right = box.x + box.width - 14;

  for (let i = 0; i < 5; i += 1) {
    const lineY = startY + ((endY - startY) * i) / 4;
    const isIndex = i % 2 === 0;
    ctx.strokeStyle = isIndex ? INK.title : INK.accent;
    ctx.lineWidth = isIndex ? 2.4 : 1.3;
    ctx.beginPath();
    ctx.moveTo(left, lineY);
    ctx.lineTo(right, lineY);
    ctx.stroke();
  }

  thumbLabel(ctx, 'tebal = indeks', cx, box.y + box.height - 11);
  ctx.restore();
}

/**
 * Relief dan kenampakan medan: dua kenampakan yang paling sering dipelajari.
 *
 * Kiri lembah (garis membentuk V dengan ujung tumpul menunjuk ke hulu), kanan
 * bukit (garis melingkar makin ke tengah). Menggambar keduanya berdampingan
 * menunjukkan bahwa pola garis berbeda untuk bentuk lahan yang berbeda.
 */
function drawRelief(ctx, x, y, width, height) {
  const box = beginThumb(ctx, x, y, width, height);
  const midX = box.x + box.width / 2;

  // Garis pemisah kedua contoh.
  ctx.strokeStyle = INK.rule;
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.moveTo(midX, box.y + 10);
  ctx.lineTo(midX, box.y + box.height - 20);
  ctx.stroke();

  // Lembah: garis kontur membentuk V, ujung tumpul menunjuk ke atas (hulu).
  ctx.strokeStyle = INK.accent;
  ctx.lineWidth = 1.5;
  ctx.lineJoin = 'round';
  for (let i = 0; i < 3; i += 1) {
    const spread = 12 + i * 9;
    const apexY = box.y + box.height / 2 + 8 - i * 2;
    ctx.beginPath();
    ctx.moveTo(midX - box.width / 4 - 6 + i * 2, apexY + spread * 0.55);
    ctx.quadraticCurveTo(midX - box.width / 4, apexY - 4, midX - box.width / 4 + 6 - i * 2, apexY + spread * 0.55);
    ctx.stroke();
  }

  // Bukit: garis melingkar, makin rapat ke puncak.
  for (const scale of [0.85, 0.58, 0.32]) {
    ctx.beginPath();
    ctx.ellipse(midX + box.width / 4, box.y + box.height / 2, box.width * 0.19 * scale, box.height * 0.26 * scale, 0, 0, Math.PI * 2);
    ctx.stroke();
  }

  thumbLabel(ctx, 'lembah', box.x + box.width * 0.27, box.y + box.height - 10);
  thumbLabel(ctx, 'bukit', box.x + box.width * 0.75, box.y + box.height - 10);
  ctx.restore();
}

/**
 * Judul peta: kotak berisi baris tebal di atas dan baris tipis di bawahnya.
 *
 * Baris tebal mewakili nama wilayah, baris tipis mewakili keterangan tambahan
 * seperti wilayah administrative dan nilai interval kontur.
 */
function drawJudulPeta(ctx, x, y, width, height) {
  const box = beginThumb(ctx, x, y, width, height);
  const left = box.x + 12;
  const right = box.x + box.width - 12;
  const top = box.y + 16;

  ctx.fillStyle = INK.title;
  ctx.fillRect(left, top, (right - left) * 0.78, 6);

  ctx.fillStyle = INK.muted;
  ctx.fillRect(left, top + 13, (right - left) * 0.5, 3);
  ctx.fillRect(left, top + 20, (right - left) * 0.62, 3);

  ctx.fillStyle = INK.rule;
  ctx.fillRect(left, box.y + box.height - 20, right - left, 1);

  thumbLabel(ctx, 'nama wilayah', box.x + box.width / 2, box.y + box.height - 9);
  ctx.restore();
}

/**
 * Skala peta: bilah bersegmen dengan angka di kedua ujungnya.
 *
 * Bentuk bersegmen disengaja: skala yang membagi jarak jadi beberapa bagian
 * enak dibaca, sedangkan satu garis panjang tidak.
 */
function drawSkalaPeta(ctx, x, y, width, height) {
  const box = beginThumb(ctx, x, y, width, height);
  const left = box.x + 14;
  const right = box.x + box.width - 14;
  const barY = box.y + box.height / 2 - 10;
  const barWidth = right - left;
  const segments = 4;
  const segmentWidth = barWidth / segments;

  for (let i = 0; i < segments; i += 1) {
    ctx.fillStyle = i % 2 === 0 ? INK.title : INK.paper;
    ctx.fillRect(left + i * segmentWidth, barY, segmentWidth, 8);
  }

  ctx.strokeStyle = INK.title;
  ctx.lineWidth = 1;
  ctx.strokeRect(left, barY, barWidth, 8);

  setFont(ctx, 9, '600');
  ctx.fillStyle = INK.muted;
  ctx.textBaseline = 'top';
  ctx.textAlign = 'center';
  for (let i = 0; i <= segments; i += 1) {
    ctx.fillText(String(i * 5), left + i * segmentWidth, barY + 11);
  }

  thumbLabel(ctx, 'km', box.x + box.width / 2, box.y + box.height - 9);
  ctx.restore();
}

/**
 * Arah utara: mawar kompas sederhana dengan jarum utara menyala.
 *
 * Bentuknya lebih kecil dari mawar kompas peta utama supaya tidak bersaing,
 * tapi tetap memakai kode warna yang sama: utara merah, arah lain netral.
 */
function drawArahUtara(ctx, x, y, width, height) {
  const box = beginThumb(ctx, x, y, width, height);
  const { cx, cy } = thumbCenter(box);
  const radius = Math.min(box.width, box.height) * 0.34;

  ctx.strokeStyle = INK.frame;
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.arc(cx, cy, radius, 0, Math.PI * 2);
  ctx.stroke();

  // Jarum utara: segitiga panjang ke atas dan pendek ke bawah.
  ctx.fillStyle = INK.north;
  ctx.beginPath();
  ctx.moveTo(cx, cy - radius * 0.78);
  ctx.lineTo(cx - radius * 0.2, cy + radius * 0.2);
  ctx.lineTo(cx + radius * 0.2, cy + radius * 0.2);
  ctx.closePath();
  ctx.fill();

  ctx.fillStyle = INK.south;
  ctx.beginPath();
  ctx.moveTo(cx, cy + radius * 0.5);
  ctx.lineTo(cx - radius * 0.18, cy - radius * 0.05);
  ctx.lineTo(cx + radius * 0.18, cy - radius * 0.05);
  ctx.closePath();
  ctx.fill();

  setFont(ctx, 9, '700');
  ctx.fillStyle = INK.north;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText('U', cx, cy - radius - 7);

  ctx.restore();
}

/**
 * Legenda: kotak dengan dua baris, tiap baris pasangan simbol dan keterangan.
 *
 * Baris pertama memakai garis (simbol garis kontur), baris kedua memakai
 * kotak kecil terisi (simbol bangunan atau kawasan), supaya jelas bahwa setiap
 * baris adalah pasangan simbol dan keterangan.
 */
function drawLegenda(ctx, x, y, width, height) {
  const box = beginThumb(ctx, x, y, width, height);
  const left = box.x + 12;
  const sampleX = left;
  const textX = left + 26;
  const right = box.x + box.width - 10;

  for (let i = 0; i < 2; i += 1) {
    const rowY = box.y + 22 + i * 20;

    if (i === 0) {
      ctx.strokeStyle = INK.accent;
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(sampleX, rowY);
      ctx.lineTo(sampleX + 18, rowY);
      ctx.stroke();
    } else {
      ctx.fillStyle = INK.accent;
      ctx.fillRect(sampleX, rowY - 4, 9, 8);
    }

    ctx.fillStyle = INK.body;
    ctx.fillRect(textX, rowY - 2, Math.min((right - textX) * (i === 0 ? 0.72 : 0.54), 1e9), 3);
  }

  thumbLabel(ctx, 'simbol + keterangan', box.x + box.width / 2, box.y + box.height - 12);
  ctx.restore();
}

/**
 * Grid koordinat: kotak terisi garis bujur dan lintang berpersilangan, dengan
 * satu titik penanda di perpotongan tertentu.
 *
 * Label bujur dan lintang sengaja kecil: di bahan ajar yang diperkecil, yang
 * penting pola persilangan dan titik penanda, bukan angka yang presisi.
 */
function drawGrid(ctx, x, y, width, height) {
  const box = beginThumb(ctx, x, y, width, height);
  const left = box.x + 14;
  const right = box.x + box.width - 14;
  const top = box.y + 14;
  const bottom = box.y + box.height - 22;
  const cells = 4;
  const cellWidth = (right - left) / cells;
  const cellHeight = (bottom - top) / cells;

  ctx.strokeStyle = INK.frame;
  ctx.lineWidth = 1;
  for (let i = 0; i <= cells; i += 1) {
    ctx.beginPath();
    ctx.moveTo(left + i * cellWidth, top);
    ctx.lineTo(left + i * cellWidth, bottom);
    ctx.stroke();

    ctx.beginPath();
    ctx.moveTo(left, top + i * cellHeight);
    ctx.lineTo(right, top + i * cellHeight);
    ctx.stroke();
  }

  // Perpotongan yang ditandai, dengan cincin supaya tetap terlihat di atas garis.
  const markX = left + cellWidth * 2;
  const markY = top + cellHeight * 2;
  ctx.fillStyle = INK.paper;
  ctx.beginPath();
  ctx.arc(markX, markY, 5.5, 0, Math.PI * 2);
  ctx.fill();
  ctx.fillStyle = INK.accent;
  ctx.beginPath();
  ctx.arc(markX, markY, 3.5, 0, Math.PI * 2);
  ctx.fill();

  thumbLabel(ctx, 'bujur & lintang', box.x + box.width / 2, box.y + box.height - 10);
  ctx.restore();
}

/**
 * Hitung tinggi kanvas yang dibutuhkan seluruh isi.
 *
 * Judul bisa memerlukan lebih dari satu baris kalau namanya panjang, jadi
 * tinggi kanvas ikut bergantung pada isi judul. Versi lama menambah 46 piksel
 * tanpa mengukur, sehingga judul panjang menimpa subjudul dan isi di
 * bawahnya, sementara kanvas tetap sebesar judul satu baris.
 *
 * Snapshot peta ikut diteruskan karena tinggi kotak peta dihitung dari rasio
 * aspect-nya. Tanpa itu, kanvas bisa lebih pendek daripada isi dan bagian
 * bawah terpotong.
 */
function measureHeight(ctx, width, title, subtitle, mapCanvas) {
  const contentWidth = width - MARGIN * 2;
  const textWidth = contentWidth - ITEM_VISUAL_WIDTH - ITEM_VISUAL_GAP;

  let height = MARGIN;
  height += titleLineCount(ctx, title, contentWidth) * TITLE_LINE_HEIGHT + TITLE_GAP;
  height += subtitleLineCount(ctx, subtitle, contentWidth) * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP;
  height += mapAreaHeight(mapCanvas, contentWidth);
  height += 34; // keterangan di bawah peta
  height += 28; // jarak

  setFont(ctx, 15, '400');

  for (const section of teachingSections()) {
    // drawSection memakai 24 piksel untuk garis bawah judul bagian dan 12 lagi
    // ke butir pertama. Keduanya dihitung di sini dengan angka yang sama.
    height += 24 + 12;

    for (const item of section.items) {
      height += itemRowHeight(ctx, item, textWidth);
    }

    height += 12; // jarak ke bagian berikutnya
  }

  height += 30; // footer
  height += MARGIN;

  return Math.ceil(height);
}

/**
 * Dua bagian bahan ajar, dalam urutan yang dicetak.
 *
 * Disatukan supaya measureHeight dan buildMapPng memakai daftar bagian yang
 * sama. Kalau salah satu ditambah dan yang lain tidak, tinggi kanvas tidak
 * lagi cocok dengan isinya.
 */
function teachingSections() {
  return [
    { heading: 'Komponen Utama Peta Kontur', items: CONTOUR_COMPONENTS },
    { heading: 'Kelengkapan Umum Peta (Pendukung)', items: MAP_COMPLETENESS },
  ];
}

/**
 * Tinggi satu baris bahan ajar.
 *
 * Dipakai measureHeight dan drawSection supaya keduanya menghitung tinggi yang
 * sama. Kalau dipisah, gambar dan tinggi kanvas bisa berbeda dan isi pada
 * bagian bawah terpotong.
 */
function itemRowHeight(ctx, item, textWidth) {
  const textHeight = 20 + wrapText(ctx, item.text, textWidth).length * 19;
  return Math.max(ITEM_VISUAL_HEIGHT, textHeight) + 14;
}

function drawSection(ctx, heading, items, x, y, contentWidth) {
  const textWidth = contentWidth - ITEM_VISUAL_WIDTH - ITEM_VISUAL_GAP;
  const textX = x + ITEM_VISUAL_WIDTH + ITEM_VISUAL_GAP;

  setFont(ctx, 17, '700');
  ctx.fillStyle = INK.accent;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  ctx.fillText(heading, x, y);

  const underlineY = y + 24;
  ctx.strokeStyle = INK.accent;
  ctx.lineWidth = 2;
  ctx.beginPath();
  ctx.moveTo(x, underlineY);
  ctx.lineTo(x + 84, underlineY);
  ctx.stroke();

  let cursorY = underlineY + 12;
  setFont(ctx, 15, '400');

  items.forEach((item) => {
    // Sketsa diletakkan di kiri dengan lebar tetap, judul dan penjelasannya
    // di sebelah kanannya. Lebar kolom teks dihitung sekali di luar loop
    // supaya semua butir sejajar dan tidak bergeser sendiri.
    item.draw(ctx, x, cursorY, ITEM_VISUAL_WIDTH, ITEM_VISUAL_HEIGHT);

    // Judul dan deskripsi digambar sebagai dua baris terpisah, bukan satu
    // paragraf dengan potongan tebal di tengah. Bentuk kanvas tidak punya
    // alur teks multi-gaya seperti DOM, jadi menyisipkan tebal di tengah paragraf
    // berarti mengukur setiap potongan secara manual; tata letak dua baris
    // menghasilkan hal yang sama tanpa pengukuran per-potongan.
    setFont(ctx, 15, '700');
    ctx.fillStyle = INK.title;
    ctx.textAlign = 'left';
    ctx.textBaseline = 'top';
    ctx.fillText(item.title, textX, cursorY);

    setFont(ctx, 15, '400');
    ctx.fillStyle = INK.body;
    const bodyLines = wrapText(ctx, item.text, textWidth);
    bodyLines.forEach((line, index) => {
      ctx.fillText(line, textX, cursorY + 20 + index * 19);
    });

    cursorY += itemRowHeight(ctx, item, textWidth);
  });

  return cursorY;
}

/**
 * Susun PNG cetak peta lengkap dengan bahan ajarnya.
 *
 * @param {object} options
 * @param {HTMLCanvasElement|null} options.mapCanvas  Snapshot peta. Null berarti
 *  area peta tidak bisa dibaca (lihat captureMapCanvas di bawah) dan hanya bahan ajar
 *   yang dicetak.
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
} = {}) {
  const width = 1240;
  const scale = 2;
  const picked = { ...DEFAULT_COMPONENTS, ...components };
const mapWidth = width - MARGIN * 2;
const mapHeight = mapAreaHeight(mapCanvas, mapWidth);
  const mapWidthMeters = metersPerPixel(latitude, zoom) * mapWidth;

  // Teks yang benar-benar akan dicetak harus sudah diketahui sebelum kanvas
  // berukuran apa pun, karena tinggi kanvas bergantung pada jumlah baris judul
  // dan subjudul.
  const subtitleText = subtitle || `Lebar area ${distanceLabel(mapWidthMeters)}`;

  const probe = document.createElement('canvas').getContext('2d');
  setFont(probe, 15, '400');
  const height = measureHeight(probe, width, title, subtitleText, mapCanvas);

  const canvas = document.createElement('canvas');
  canvas.width = width * scale;
  canvas.height = height * scale;

  const ctx = canvas.getContext('2d');
  ctx.scale(scale, scale);

  ctx.fillStyle = INK.paper;
  ctx.fillRect(0, 0, width, height);

  // Judul, dipecah bila panjangnya melebihi lebar konten.
  let cursorY = MARGIN;
  setFont(ctx, 30, '700');
  ctx.fillStyle = INK.title;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  const titleLines = wrapText(ctx, title, mapWidth);
  titleLines.forEach((line, index) => {
    ctx.fillText(line, MARGIN, cursorY + index * TITLE_LINE_HEIGHT);
  });
  cursorY += titleLines.length * TITLE_LINE_HEIGHT + TITLE_GAP;

  setFont(ctx, 13, '400');
  ctx.fillStyle = INK.muted;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  // Subjudul juga dipecah: ia memuat koordinat, zoom, dan skala sekaligus,
  // sehingga panjangnya bergantung pada nilai yang sedang dilihat.
  const subtitleLines = wrapText(ctx, subtitleText, mapWidth);
  subtitleLines.forEach((line, index) => {
    ctx.fillText(line, MARGIN, cursorY + index * SUBTITLE_LINE_HEIGHT);
  });

  // Kursor harus ikut bertambah sesuai jumlah baris. Kalau hanya menambah tinggi
  // satu baris, subjudul dua baris menimpa kotak peta.
  cursorY += subtitleLines.length * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP;

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

  // Keterangan sumber dan legenda
  cursorY = mapY + mapHeight + 10;
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
  ctx.fillText('Sumber: OpenStreetMap, PMTiles Kontur Sulsel', width - MARGIN, cursorY);

  cursorY += 34;

  const contentWidth = width - MARGIN * 2;

  for (const section of teachingSections()) {
    cursorY = drawSection(ctx, section.heading, section.items, MARGIN, cursorY, contentWidth);
  }

  // Footer
  setFont(ctx, 11, '400');
  ctx.fillStyle = INK.muted;
  ctx.textAlign = 'left';
  ctx.fillText(
    `Dicetak pada ${new Date().toLocaleString('id-ID')} dari AMBARA - Sistem Digital Ambalan UPT SMAN 2 Maros`,
    MARGIN,
    cursorY + 14,
  );

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
 */
function regionPadding(map) {
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
 * @returns {Promise<() => void>} Fungsi pemulih kamera.
 */
async function frameRegion(map, region, maxZoom) {
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
        [region.south, region.west],
        [region.north, region.east],
      ],
      {
        padding: regionPadding(map),
        bearing: 0,
        pitch: 0,
        // Tanpa animasi: yang dikejar adalah snapshot yang benar, dan animasi
        // hanya menambah waktu tunggu tanpa mengubah hasilnya.
        animate: false,
        duration: 0,
        ...(Number.isFinite(maxZoom) ? { maxZoom } : {}),
      },
    );
  } catch {
    // Peta tanpa fitBounds yang bisa dipakai. Kamera dibiarkan apa adanya dan
    // snapshot tetap diambil, supaya pengguna tetap mendapat berkas.
    return () => {};
  }

  await waitForMapIdle(map);

  return restore;
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
   * @returns {Promise<{ ok: boolean, blocked?: boolean, error?: string|null, mapMissing?: boolean }>}
   */
  async function exportMapPng(map, overrides = {}) {
    if (isExporting.value) {
      return { ok: false, error: 'Ada proses cetak PNG lain yang sedang berjalan.' };
    }

    isExporting.value = true;
    exportError.value = null;

    // `filename`, `region`, dan `regionMaxZoom` bukan opsi gambar, jadi
    // dipisah sebelum diteruskan ke buildMapPng.
    const { filename, region = null, regionMaxZoom = null, ...pngOptions } = overrides;

    // Kamera diarahkan ke wilayah lebih dulu, baru dibaca. Semua angka yang
    // dipakai berikutnya (pusat, zoom, batas, skala) diambil dari kamera yang
    // sudah sesuai wilayah, sehingga judul, bilah skala, dan grid koordinat
    // cocok dengan isi gambarnya.
    let restoreCamera = null;

    if (region && map?.fitBounds) {
      restoreCamera = await frameRegion(map, region, regionMaxZoom);
    }

    try {
      const center = map?.getCenter?.() ?? { lat: 0, lng: 0 };
      const zoom = map?.getZoom?.() ?? 10;
      const { canvas, blocked, reason } = await captureMapCanvas(map);

      // Batas yang benar-benar tertangkap diambil dari peta, bukan dihitung ulang
      // dari pusat dan zoom: snapshot dipotong di tengah supaya memenuhi kotak
      // cetak, jadi hanya peta yang tahu wilayah yang benar-benar terlihat.
      //
      // Batas dibaca lewat getWest/getSouth/getEast/getNorth, bukan toArray().
      // LngLatBounds.toArray() mengembalikan [[west, south], [east, north]],
      // yaitu dua pasang angka, bukan empat angka berurutan. Memecahnya seperti
      // angka berurutan membuat west berisi pasangan dan east berisi undefined,
      // sehingga selisihnya NaN dan grid koordinat diam-diam tidak pernah
      // digambar padahal penggunanya mencentangnya.
      let bounds = null;

      try {
        const raw = map?.getBounds?.();
        if (raw) {
          bounds = {
            west: raw.getWest(),
            south: raw.getSouth(),
            east: raw.getEast(),
            north: raw.getNorth(),
          };
        }
      } catch {
        bounds = null;
      }

      // Subjudul digabung, bukan ditimpa. Versi lama menaruh seluruh subjudul
      // di dalam spread ...pngOptions, sehingga subtitle yang dikirim halaman
      // menimpa bagian ini dan Angka "Skala 1:..." yang sempat dihitung tidak
      // pernah muncul di PNG mana pun.
      const detail = `Koordinat tengah ${center.lng.toFixed(4)}, ${center.lat.toFixed(4)} | Zoom ${zoom.toFixed(1)}`
        + ` | Skala 1:${scaleDenominator(center.lat, zoom).toLocaleString('id-ID')}`;
      const { subtitle: subtitleOverride, ...restOptions } = pngOptions;
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
        ...restOptions,
      });

      const stamp = new Date().toISOString().slice(0, 16).replace(/[:T]/g, '-');
      triggerPngDownload(blob, filename ?? `peta-kontur-${stamp}.png`);

      // Alasan kegagalan peta dikembalikan ke pemanggil supaya halaman bisa
      // memberi tahu pengguna. Tanpa ini, kotak peta kosong disertai status
      // "PNG tersimpan" dan pengguna mengira peta ikut tercetak.
      return { ok: true, blocked, error: null, mapMissing: !canvas, mapProblem: reason };
    } catch (error) {
      // Pesan asli harus ikut dikembalikan. Kalau hanya disimpan di
      // exportError, pemanggil yang tidak punya akses ke ref itu hanya melihat
      // "gagal" tanpa sebab, sehingga galatnya tidak bisa ditelusuri.
      exportError.value = error?.message ?? 'Gagal membuat PNG peta.';
      return { ok: false, blocked: false, error: exportError.value };
    } finally {
      // Kamera dikembalikan walau cetak gagal. Kalau tidak, satu unduhan yang
      // bermasalah cukup untuk membuat peta utama tersesat ke wilayah lain dan
      // pengguna mengira tampilan itu memang pilihan mereka.
      restoreCamera?.();
      isExporting.value = false;
    }
  }

  return { isExporting, exportError, exportMapPng };
}
