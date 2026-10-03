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
 * Dipatok landscape dan tidak lagi mengikuti isi. Versi lama mengukur tinggi
 * dari jumlah teks bahan ajar, jadi lembarnya jadi tinggi seperti halaman web:
 * peta tinggal jadi pita tipis di bagian atas, dan wilayah yang dipilih terlihat
 * kecil sampai sulit dibaca.
 *
 * Sekarang isinya cuma peta, jadi ukuran halaman adalah pilihan format, bukan
 * hasil pengukuran. 16:10 cukup lebar untuk satu wilayah kabupaten, dan tidak
 * memaksa pemakai memutar kertas saat dicetak.
 */
const SHEET_WIDTH = 1600;
const SHEET_HEIGHT = 1000;

const MARGIN = 48;

/**
 * Batas tinggi area peta.
 *
 * Batas atas menjaga judul dan keterangan sumber tetap muat di lembar
 * landscape. Batas bawah cuma jaring pengaman: kalau judulnya luar biasa
 * panjang, area peta tidak boleh habis lalu judul menimpa peta.
 */
const MAP_MIN_HEIGHT = 260;
const MAP_MAX_HEIGHT = 820;

/** Keterangan sumber di bawah peta, termasuk jaraknya dari area peta. */
const FOOTER_GAP = 12;
const FOOTER_HEIGHT = 18;

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
const SUBTITLE_GAP = 18;

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
  const width = SHEET_WIDTH;
  const height = SHEET_HEIGHT;
  const scale = 2;
  const picked = { ...DEFAULT_COMPONENTS, ...components };
  const mapWidth = width - MARGIN * 2;
  const mapWidthMeters = metersPerPixel(latitude, zoom) * mapWidth;

  // Teks yang benar-benar akan dicetak harus sudah diketahui sebelum kanvas
  // digambar, karena tinggi area peta bergantung pada jumlah baris judul dan
  // subjudul.
  const subtitleText = subtitle || `Lebar area ${distanceLabel(mapWidthMeters)}`;

  const probe = document.createElement('canvas').getContext('2d');

  // Tinggi area peta = sisa lembar setelah judul, subjudul, dan kaki halaman.
  // Lembarnya sendiri tetap landscape: kalau judulnya butuh ruang lebih, yang
  // mengecil adalah peta, bukan halaman. Itu membuat format keluar konsisten
  // untuk semua wilayah, yang justru tidak bisa dijamin kalau tinggi lembar
  // ikut berubah-ubah.
  const headerHeight = (() => {
    setFont(probe, 32, '700');
    const titleLines = wrapText(probe, title, mapWidth).length;
    setFont(probe, 14, '400');
    const subtitleLines = wrapText(probe, subtitleText, mapWidth).length;

    return titleLines * TITLE_LINE_HEIGHT + TITLE_GAP
      + subtitleLines * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP;
  })();

  const available = height - MARGIN * 2 - headerHeight - FOOTER_GAP - FOOTER_HEIGHT;
  const mapHeight = Math.round(Math.min(Math.max(available, MAP_MIN_HEIGHT), MAP_MAX_HEIGHT));

  const canvas = document.createElement('canvas');
  canvas.width = width * scale;
  canvas.height = height * scale;

  const ctx = canvas.getContext('2d');
  ctx.scale(scale, scale);

  ctx.fillStyle = INK.paper;
  ctx.fillRect(0, 0, width, height);

  // Judul, dipecah bila panjangnya melebihi lebar konten.
  let cursorY = MARGIN;
  setFont(ctx, 32, '700');
  ctx.fillStyle = INK.title;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  const titleLines = wrapText(ctx, title, mapWidth);
  titleLines.forEach((line, index) => {
    ctx.fillText(line, MARGIN, cursorY + index * TITLE_LINE_HEIGHT);
  });
  cursorY += titleLines.length * TITLE_LINE_HEIGHT + TITLE_GAP;

  setFont(ctx, 14, '400');
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

  // Keterangan sumber dan kaki halaman.
  //
  // Tidak ada lagi daftar uraian komponen peta kontur di bawah peta. Uraian itu
  // cocok untuk lembar handout, tapi di sini ia hanya memperpanjang halaman dan
  // membuat peta mengecil sampai wilayah yang dipilih sulit dibaca.
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
  ctx.fillText('Sumber: OpenStreetMap, PMTiles Kontur Sulsel', width - MARGIN, cursorY);

  // Kaki halaman menempel ke tepi bawah lembar, tidak mengikuti cursor, supaya
  // posisinya sama untuk semua wilayah.
  setFont(ctx, 11, '400');
  ctx.fillStyle = INK.muted;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'alphabetic';
  ctx.fillText(
    `Dicetak pada ${new Date().toLocaleString('id-ID')} dari AMBARA - Sistem Digital Ambalan UPT SMAN 2 Maros`,
    MARGIN,
    height - MARGIN * 0.5,
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
