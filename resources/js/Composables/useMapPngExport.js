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

  // Cincin luar
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

  setFont(ctx, 11, '600');
  ctx.fillStyle = INK.muted;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText('UTARA', cx, cy + radius + 16);
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
 * Pilih jarak "bulat" yang paling muat dalam maxWidth piksel.
 */
function niceDistance(maxMeters, maxWidthPx, metersPerPx) {
  const candidates = [
    1, 2, 5, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000, 50000,
    100000, 200000, 500000, 1000000, 2000000,
  ];

  for (const meters of candidates) {
    if (meters / metersPerPx <= maxWidthPx) {
      return meters;
    }
  }

  return maxMeters;
}

function formatDistance(meters) {
  return distanceLabel(meters);
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

/** Hitung tinggi kanvas yang dibutuhkan seluruh isi. */
function measureHeight(ctx, width) {
  const contentWidth = width - MARGIN * 2;

  let height = MARGIN;
  height += 46; // judul
  height += 22; // subjudul
  height += 20;
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

  height += 40; // footer
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
 *  _area peta tidak bisa dibaca (lihat_BLOCKED_ di bawah) dan hanya bahan ajar
 *   yang dicetak.
 * @param {number} options.latitude
 * @param {number} options.zoom
 * @param {number} options.bearing
 * @param {string} options.title
 * @param {string} options.subtitle
 * @param {boolean} options.areaBlocked  True bila snapshot peta terhalang
 *   kebijakan CORS browser.
 * @returns {Promise<Blob>}
 */
export async function buildMapPng({
  mapCanvas = null,
  latitude = 0,
  zoom = 10,
  bearing = 0,
  title = 'Peta Kontur Sulawesi',
  subtitle = '',
  areaBlocked = false,
} = {}) {
  const width = 1240;
  const scale = 2;

  const probe = document.createElement('canvas').getContext('2d');
  setFont(probe, 15, '400');
  const height = measureHeight(probe, width);

  const canvas = document.createElement('canvas');
  canvas.width = width * scale;
  canvas.height = height * scale;

  const ctx = canvas.getContext('2d');
  ctx.scale(scale, scale);

  const mapWidth = width - MARGIN * 2;
  const mapHeight = MAP_HEIGHT;
  const mapWidthMeters = metersPerPixel(latitude, zoom) * mapWidth;

  ctx.fillStyle = INK.paper;
  ctx.fillRect(0, 0, width, height);

  // Judul
  let cursorY = MARGIN;
  setFont(ctx, 30, '700');
  ctx.fillStyle = INK.title;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  ctx.fillText(title, MARGIN, cursorY);
  cursorY += 46;

  setFont(ctx, 13, '400');
  ctx.fillStyle = INK.muted;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  ctx.fillText(subtitle || `Lebar area ${distanceLabel(mapWidthMeters)}`, MARGIN, cursorY);
  cursorY += 22 + 20;

  // Area peta
  const mapX = MARGIN;
  const mapY = cursorY;

  ctx.save();
  ctx.beginPath();
  ctx.rect(mapX, mapY, mapWidth, mapHeight);
  ctx.clip();

  ctx.fillStyle = '#e5e7eb';
  ctx.fillRect(mapX, mapY, mapWidth, mapHeight);

  if (mapCanvas) {
    // Skala kanvas peta agar menutupi kotak tanpa mengubah rasio aspek.
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

  // Kompas di pojok kanan atas area peta
  drawCompass(ctx, width - MARGIN - 56, mapY + 56, 34, bearing);

  // Skala di pojok kiri bawah area peta
  drawScaleBar(ctx, mapX + 24, mapY + mapHeight - 44, metersPerPixel(latitude, zoom), 220);

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
      return { ok: false };
    }

    isExporting.value = true;
    exportError.value = null;

    try {
      const center = map?.getCenter?.() ?? { lat: 0, lng: 0 };
      const zoom = map?.getZoom?.() ?? 10;
      const { canvas, blocked } = await captureMapCanvas(map);

      const blob = await buildMapPng({
        mapCanvas: canvas,
        latitude: center.lat,
        zoom,
        bearing: map?.getBearing?.() ?? 0,
        subtitle:
          `Koordinat tengah ${center.lng.toFixed(4)}, ${center.lat.toFixed(4)} | Zoom ${zoom.toFixed(1)}`
          + ` | Skala 1:${scaleDenominator(center.lat, zoom).toLocaleString('id-ID')}`,
        areaBlocked: blocked,
        ...overrides,
      });

      const stamp = new Date().toISOString().slice(0, 16).replace(/[:T]/g, '-');
      triggerPngDownload(blob, `peta-kontur-${stamp}.png`);

      return { ok: true, blocked };
    } catch (error) {
      exportError.value = error?.message ?? 'Gagal membuat PNG peta.';
      return { ok: false, error: exportError.value };
    } finally {
      isExporting.value = false;
    }
  }

  return { isExporting, exportError, exportMapPng };
}