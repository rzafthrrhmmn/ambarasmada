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
 * harus menelusuri perintah fillText. Dipakai juga oleh halaman cetak
 * (printMap) supaya kedua keluaran tidak berbeda isi.
 */
export const CONTOUR_COMPONENTS = [
  {
    title: 'Garis Kontur',
    text: 'Garis imajiner pada peta yang menghubungkan titik-titik dengan ketinggian atau elevasi yang sama dari permukaan laut.',
  },
  {
    title: 'Nilai Kontur',
    text: 'Angka atau label numerik yang menunjukkan besarnya elevasi (biasanya dalam satuan meter) pada suatu garis kontur.',
  },
  {
    title: 'Interval Kontur',
    text: 'Jarak vertikal yang konstan antara dua garis kontur yang berurutan.',
  },
  {
    title: 'Garis Kontur Indeks',
    text: 'Garis kontur yang digambar lebih tebal setiap kelipatan interval tertentu dan biasanya dilengkapi dengan angka nilai ketinggian untuk memudahkan pembacaan.',
  },
  {
    title: 'Indikator Relief & Kenampakan Medan',
    text: 'Pola kerapatan garis yang menggambarkan bentuk-bentuk lahan, seperti lereng landai (garis renggang), lereng curam (garis rapat), lembah (membentuk huruf V menunjuk ke hulu), atau bukit/gunung (melingkar).',
  },
];

export const MAP_COMPLETENESS = [
  {
    title: 'Judul Peta',
    text: 'Menunjukkan identitas atau nama wilayah yang dipetakan.',
  },
  {
    title: 'Skala Peta',
    text: 'Perbandingan jarak pada peta dengan jarak sebenarnya di lapangan, yang juga menentukan besar kecilnya interval kontur.',
  },
  {
    title: 'Arah Utara / Orientasi',
    text: 'Tanda panah yang menunjukkan arah utara geografis.',
  },
  {
    title: 'Legenda / Keterangan',
    text: 'Penjelasan mengenai simbol-simbol lain yang ada di dalam peta.',
  },
  {
    title: 'Grid Koordinat',
    text: 'Sistem koordinat garis bujur dan lintang atau UTM untuk penentuan posisi.',
  },
];

const FONT = "'Segoe UI', 'Helvetica Neue', Arial, sans-serif";
const MARGIN = 56;
const MAP_HEIGHT = 560;

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
 * Hitung tinggi kanvas yang dibutuhkan seluruh isi.
 *
 * Judul bisa memerlukan lebih dari satu baris kalau namanya panjang, jadi
 * tinggi kanvas ikut bergantung pada isi judul. Versi lama menambah 46 piksel
 * tanpa mengukur, sehingga judul panjang menimpa subjudul dan isi di
 * bawahnya, sementara kanvas tetap sebesar judul satu baris.
 */
function measureHeight(ctx, width, title, subtitle) {
  const contentWidth = width - MARGIN * 2;

  let height = MARGIN;
  height += titleLineCount(ctx, title, contentWidth) * TITLE_LINE_HEIGHT + TITLE_GAP;
  height += subtitleLineCount(ctx, subtitle, contentWidth) * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP;
  height += MAP_HEIGHT;
  height += 34; // keterangan di bawah peta
  height += 28; // jarak

  setFont(ctx, 15, '400');

  for (const section of [
    { heading: 'Komponen Utama Peta Kontur', items: CONTOUR_COMPONENTS },
    { heading: 'Kelengkapan Umum Peta (Pendukung)', items: MAP_COMPLETENESS },
  ]) {
    height += 26; // judul bagian

    for (const item of section.items) {
      // Harus sama dengan yang digambar drawSection: satu baris untuk judul,
      // lalu baris-baris deskripsi yang dipecah menurut lebar konten.
      height += 20 + wrapText(ctx, item.text, contentWidth - 22).length * 19 + 10;
    }

    height += 12;
  }

  height += 30; // footer
  height += MARGIN;

  return Math.ceil(height);
}

function drawSection(ctx, heading, items, x, y, contentWidth) {
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
    // Judul dan deskripsi digambar sebagai dua baris terpisah, bukan satu
    // paragraf dengan potongan tebal di tengah. Bentuk kanvas tidak punya
    // alur teks multi-gaya seperti DOM, jadi menyisipkan tebal di tengah paragraf
    // berarti mengukur setiap potongan secara manual; tata letak dua baris
    // menghasilkan hal yang sama tanpa pengukuran per-potongan.
    ctx.beginPath();
    ctx.arc(x + 7, cursorY + 9, 4, 0, Math.PI * 2);
    ctx.fillStyle = INK.accent;
    ctx.fill();

    setFont(ctx, 15, '700');
    ctx.fillStyle = INK.title;
    ctx.textAlign = 'left';
    ctx.textBaseline = 'top';
    ctx.fillText(item.title, x + 22, cursorY);
    cursorY += 20;

    setFont(ctx, 15, '400');
    ctx.fillStyle = INK.body;
    const bodyLines = wrapText(ctx, item.text, contentWidth - 22);
    bodyLines.forEach((line, index) => {
      ctx.fillText(line, x + 22, cursorY + index * 19);
    });

    cursorY += bodyLines.length * 19 + 10;
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
  components = {},
  elevation = null,
  bounds = null,
} = {}) {
  const width = 1240;
  const scale = 2;
  const picked = { ...DEFAULT_COMPONENTS, ...components };
  const mapWidth = width - MARGIN * 2;
  const mapHeight = MAP_HEIGHT;
  const mapWidthMeters = metersPerPixel(latitude, zoom) * mapWidth;

  // Teks yang benar-benar akan dicetak harus sudah diketahui sebelum kanvas
  // berukuran apa pun, karena tinggi kanvas bergantung pada jumlah baris judul
  // dan subjudul.
  const subtitleText = subtitle || `Lebar area ${distanceLabel(mapWidthMeters)}`;

  const probe = document.createElement('canvas').getContext('2d');
  setFont(probe, 15, '400');
  const height = measureHeight(probe, width, title, subtitleText);

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

  // Batas geografis yang benar-benar tertangkap di dalam kotak cetak. Awalnya
  // sama dengan batas viewport, lalu disempitkan mengikuti pemotongan snapshot.
  let visibleBounds = bounds;

  ctx.save();
  ctx.beginPath();
  ctx.rect(mapX, mapY, mapWidth, mapHeight);
  ctx.clip();

  ctx.fillStyle = '#e5e7eb';
  ctx.fillRect(mapX, mapY, mapWidth, mapHeight);

  if (mapCanvas && mapCanvas.width > 0 && mapCanvas.height > 0) {
    // Skala kanvas peta agar menutupi kotak tanpa mengubah rasio aspek.
    // Kanvas 0x0 sengaja dilewati: rasionya 0/0 = NaN, dan drawImage dengan
    // ukuran NaN melempar TypeError yang menggagalkan seluruh ekspor.
    const sourceRatio = mapCanvas.width / mapCanvas.height;
    const targetRatio = mapWidth / mapHeight;
    let drawWidth;
    let drawHeight;
    let offsetX;
    let offsetY;

    if (sourceRatio > targetRatio) {
      drawHeight = mapHeight;
      drawWidth = mapHeight * sourceRatio;
      offsetX = mapX - (drawWidth - mapWidth) / 2;
      offsetY = mapY;
    } else {
      drawWidth = mapWidth;
      drawHeight = drawWidth / sourceRatio;
      offsetX = mapX;
      offsetY = mapY - (drawHeight - mapHeight) / 2;
    }

    ctx.drawImage(mapCanvas, offsetX, offsetY, drawWidth, drawHeight);

    // Batas yang benar-benar terlihat di dalam kotak cetak. Snapshot dipotong
    // di tengah supaya memenuhi kotak, jadi sebagian viewport terbuang keluar,
    // dan grid koordinat harus memakai potongan yang sama. Kalau tidak, label
    // 119,65°BT menunjuk ke tempat yang salah di atas peta.
    if (bounds) {
      const visibleLon = ((bounds.east - bounds.west) * mapWidth) / drawWidth;
      const visibleLat = ((bounds.north - bounds.south) * mapHeight) / drawHeight;
      const centerLon = (bounds.east + bounds.west) / 2;
      const centerLat = (bounds.north + bounds.south) / 2;

      visibleBounds = {
        west: centerLon - visibleLon / 2,
        east: centerLon + visibleLon / 2,
        south: centerLat - visibleLat / 2,
        north: centerLat + visibleLat / 2,
      };
    }
  } else {
    setFont(ctx, 15, '400');
    ctx.fillStyle = INK.muted;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(
      areaBlocked
        ? 'Area peta tidak dapat disertakan: browser memblokir pembacaan tile karena izin CORS.'
        : 'Area peta tidak dapat disertakan.',
      mapX + mapWidth / 2,
      mapY + mapHeight / 2,
    );
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
  // sementara legenda, histogram, kompas, dan skala menumpuk di atasnya.
  if (picked.grid && visibleBounds) {
    drawGraticule(ctx, { x: mapX, y: mapY, w: mapWidth, h: mapHeight }, visibleBounds, reserved);
  }

  if (picked.legend) {
    drawLegend(ctx, mapX + PANEL.inset, mapY + PANEL.inset);
  }

  if (picked.histogram) {
    drawHistogram(
      ctx,
      mapX + mapWidth - PANEL.inset - PANEL.histogramWidth,
      mapY + PANEL.inset,
      PANEL.histogramWidth,
      PANEL.histogramHeight,
      elevation,
    );
  }

  // Kompas di pojok kanan bawah area peta, jauh dari histogram di kanan atas.
  if (picked.northArrow) {
    const compassY = mapY + mapHeight - PANEL.inset - PANEL.compassRadius;
    drawCompass(
      ctx,
      mapX + mapWidth - PANEL.inset - PANEL.compassRadius,
      compassY,
      PANEL.compassRadius,
      bearing,
    );
  }

  // Skala di pojok kiri bawah area peta
  if (picked.scaleBar) {
    drawScaleBar(
      ctx,
      mapX + PANEL.inset + 8,
      mapY + mapHeight - PANEL.inset - 26,
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
  cursorY = drawSection(ctx, 'Komponen Utama Peta Kontur', CONTOUR_COMPONENTS, MARGIN, cursorY, contentWidth);
  cursorY = drawSection(
    ctx,
    'Kelengkapan Umum Peta (Pendukung)',
    MAP_COMPLETENESS,
    MARGIN,
    cursorY + 4,
    contentWidth,
  );

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

export function useMapPngExport() {
  const isExporting = ref(false);
  const exportError = ref(null);

  /**
   * Ambil snapshot peta yang sedang tampil.
   *
   * MapLibre baru selesai menggambar saat pemanggilan berikutnya selesai, jadi
   * satu frame dipaksa lebih dulu sebelum kanvas dibaca.
   */
  async function captureMapCanvas(map) {
    if (!map) {
      return { canvas: null, blocked: false };
    }

    try {
      map.triggerRepaint();
    } catch {
      // Map yang sudah dihancurkan tidak perlu dipaksa menggambar.
    }

    await new Promise((resolve) => {
      let settled = false;
      const finish = () => {
        if (settled) return;
        settled = true;
        resolve();
      };

      try {
        map.once('idle', finish);
      } catch {
        finish();
        return;
      }

      // Jangan menggantung bila peta sedang idle dan tidak memancarkan event.
      setTimeout(finish, 1500);
      requestAnimationFrame(() => requestAnimationFrame(finish));
    });

    try {
      const canvas = map.getCanvas();

      // Kanvas yang belum pernah digambar (wadah 0x0, misalnya peta di balik
      // modal yang belum sempat diresize) tidak punya satu pun piksel untuk
      // disalin. Rasio 0/0 jadi NaN, dan drawImage dengan lebar NaN melempar
      // TypeError yang menggagalkan seluruh ekspor.
      if (!canvas || !canvas.width || !canvas.height) {
        return { canvas: null, blocked: false };
      }

      // Uji baca dulu: kanvas peta yang di-taint CORS tidak boleh masuk PNG.
      //
      // Uji ini sengaja memakai kanvas 2D, bukan gl.readPixels. readPixels
      // dengan format 0 (bukan gl.RGBA) tidak melempar galat, hanya menulis
      // "WebGL: INVALID_ENUM: readPixels: invalid format" ke console dan
      // mengembalikan diam-diam, sehingga hasil uji selalu lulus padahal tidak
      // ada yang dicek. drawImage dari kanvas WebGL tidak melempar; yang
      // melempar SecurityError adalah getImageData pada kanvas 2D hasil
      // drawImage itu, karena kanvas ikut menjadi tainted.
      const probe = document.createElement('canvas');
      probe.width = 1;
      probe.height = 1;

      const probeCtx = probe.getContext('2d');
      probeCtx.drawImage(canvas, 0, 0, 1, 1);
      probeCtx.getImageData(0, 0, 1, 1);

      return { canvas, blocked: false };
    } catch {
      return { canvas: null, blocked: true };
    }
  }

  /**
   * Cetak peta + bahan ajar kontur menjadi PNG lalu unduh otomatis.
   *
   * @returns {Promise<{ ok: boolean, blocked?: boolean }>}
   */
  async function exportMapPng(map, overrides = {}) {
    if (isExporting.value) {
      return { ok: false, error: 'Ada proses cetak PNG lain yang sedang berjalan.' };
    }

    isExporting.value = true;
    exportError.value = null;

    // `filename` bukan opsi gambar, jadi dipisah sebelum diteruskan.
    const { filename, ...pngOptions } = overrides;

    try {
      const center = map?.getCenter?.() ?? { lat: 0, lng: 0 };
      const zoom = map?.getZoom?.() ?? 10;
      const { canvas, blocked } = await captureMapCanvas(map);

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
        bounds,
        ...restOptions,
      });

      const stamp = new Date().toISOString().slice(0, 16).replace(/[:T]/g, '-');
      triggerPngDownload(blob, filename ?? `peta-kontur-${stamp}.png`);

      return { ok: true, blocked, error: null };
    } catch (error) {
      // Pesan asli harus ikut dikembalikan. Kalau hanya disimpan di
      // exportError, pemanggil yang tidak punya akses ke ref itu hanya melihat
      // "gagal" tanpa sebab, sehingga galatnya tidak bisa ditelusuri.
      exportError.value = error?.message ?? 'Gagal membuat PNG peta.';
      return { ok: false, blocked: false, error: exportError.value };
    } finally {
      isExporting.value = false;
    }
  }

  return { isExporting, exportError, exportMapPng };
}