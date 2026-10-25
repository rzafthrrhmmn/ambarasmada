/**
 * Fungsi-fungsi rendering kanvas untuk lembar cetak PNG.
 *
 * Semua fungsi yang langsung mengoperasikan `<canvas 2d>` — teks, garis,
 * kompas, legenda, histogram, bilah skala, grid koordinat — dikumpulkan di
 * sini agar mudah diuji dan dipelihara secara terpisah dari logika bisnis
 * ekspor.
 */
import {
  INK,
  FONT,
  PANEL,
  LEGEND_ITEMS,
  LEGEND_LABEL_X,
  GRID_STEPS,
  MARGIN,
  MAP_MIN_WIDTH,
  FOOTER_GAP,
  FOOTER_ROW,
  FOOTER_RULE_GAP,
  FOOTER_ROW_BOTTOM,
  TITLE_LINE_HEIGHT,
  TITLE_GAP,
  SUBTITLE_LINE_HEIGHT,
  SUBTITLE_GAP,
  EXPORT_SURFACE_WIDTH,
  EXPORT_MAX_PIXEL_RATIO,
  EXPORT_CANVAS_MAX_SIDE,
  EXPORT_CANVAS_MAX_PIXELS,
} from './useMapPngHeader.js';

/**
 * Set font kanvas dengan ukuran dan bobot yang ditentukan.
 */
export function setFont(ctx, size, weight = '400') {
  ctx.font = `${weight} ${size}px ${FONT}`;
}

/**
 * Pecah teks menjadi baris-baris yang muat dalam maxWidth.
 *
 * Canvas tidak punya pembungkus teks bawaan seperti CSS, jadi pemenggalan
 * dilakukan manual. Kata yang lebih panjang dari satu baris dipaksa terpotong
 * di tengah agar tidak pernah menggambar keluar kanvas.
 */
export function wrapText(ctx, text, maxWidth) {
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
 * Tempatkan snapshot di dalam kotak peta tanpa memotong bagian mana pun.
 *
 * Selalu memakai rasio kedua sumbu yang sama, sehingga gambar tidak pernah
 * teregang dan tidak ada bagian peta yang hilang. Kalau rasionya tidak sama
 * dengan kotak, sisanya dibiarkan sebagai latar abu-abu dan snapshot
 * dipusatkan.
 */
export function fitContain(sourceWidth, sourceHeight, boxX, boxY, boxWidth, boxHeight) {
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
 * Gambar mawar kompas yang berputar mengikuti orientasi peta.
 *
 * Mata angin wajib ada di peta hasil cetak: tanpa penanda utara, pembaca
 * tidak tahu sisi atas peta ke arah mana. Jarum utara diputar sebesar bearing
 * peta supaya tetap menunjuk utara geografis meski peta ikut berputar.
 */
export function drawCompass(ctx, cx, cy, radius, bearing = 0) {
  ctx.save();
  ctx.translate(cx, cy);
  ctx.rotate((-bearing * Math.PI) / 180);

  ctx.beginPath();
  ctx.arc(0, 0, radius, 0, Math.PI * 2);
  ctx.fillStyle = 'rgba(255, 255, 255, 0.88)';
  ctx.fill();
  ctx.lineWidth = 2;
  ctx.strokeStyle = INK.frame;
  ctx.stroke();

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

/** Tinggi panel legenda. */
export function legendHeight() {
  return PANEL.legendHeader + LEGEND_ITEMS.length * PANEL.legendRowHeight + 8;
}

/**
 * Legenda pmap di pojok kiri atas area peta.
 *
 * Isinya mengulang simbol yang benar-benar ada di style: garis kontur tebal
 * (kontur indeks), garis kontur biasa, dan batas kabupaten.
 */
export function drawLegend(ctx, x, y) {
  ctx.save();
  setFont(ctx, 12, '700');

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
 * Data elevasi harus datang dari luar. Tanpa data asli, kotaknya diisi
 * keterangan apa adanya, bukan grafik palsu.
 */
export function drawHistogram(ctx, x, y, w, h, stats) {
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

/**
 * Bilah skala di pojok kiri bawah area peta.
 */
export function drawScaleBar(ctx, x, y, metersPerPx, maxWidthPx) {
  const candidates = [
    1, 2, 5, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000, 50000,
    100000, 200000, 500000, 1000000, 2000000,
  ];

  let meters = maxWidthPx > 0 ? candidates[candidates.length - 1] : 1;
  for (let i = candidates.length - 1; i >= 0; i--) {
    const m = candidates[i];
    if (m / metersPerPx <= maxWidthPx) {
      meters = m;
      break;
    }
  }

  const widthPx = meters / metersPerPx;

  ctx.save();

  const pad = 8;
  const barHeight = 10;
  const labelHeight = 16;

  ctx.fillStyle = 'rgba(255, 255, 255, 0.88)';
  ctx.fillRect(x - pad, y - pad, widthPx + pad * 2, barHeight + labelHeight + pad);

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
  ctx.fillText(`${meters >= 1000 ? (meters / 1000).toFixed(meters >= 100 ? 0 : 1) : Math.round(meters)}${meters >= 1000 ? ' km' : ' m'}`, x + widthPx, y + barHeight + 3);

  ctx.restore();
}

/** Langkah grid "bulat" dalam derajat. */
export function niceGridStep(degreesPerPx, targetPx) {
  const wanted = degreesPerPx * targetPx;

  for (const step of GRID_STEPS) {
    if (step >= wanted) return step;
  }

  return GRID_STEPS[GRID_STEPS.length - 1];
}

/**
 * Grid koordinat di atas area peta, lengkap dengan labelnya.
 *
 * @param {{topLeft:boolean,topRight:boolean,bottomLeft:boolean,bottomRight:boolean}} reserved
 *   Panel yang menutupi tiap sudut area peta.
 */
export function drawGraticule(ctx, box, bounds, reserved) {
  const spanLon = bounds.east - bounds.west;
  const spanLat = bounds.north - bounds.south;

  if (!(spanLon > 0) || !(spanLat > 0)) return;

  const stepLon = niceGridStep(spanLon / box.w, 110);
  const stepLat = niceGridStep(spanLat / box.h, 110);

  const latSide = !reserved.topLeft
    ? 'left'
    : !reserved.bottomLeft
      ? 'bottom'
      : !reserved.topRight
        ? 'right'
        : 'left';

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

export function formatDegrees(value, axis) {
  const text = Math.abs(value) < 1e-9 ? '0' : value.toFixed(3).replace(/\.?0+$/, '');
  return axis === 'lon' ? `${text}°BT` : `${text}°LS`;
}

/**
 * Hitung rasio konten non-abu-abu pada kanvas peta.
 *
 * Digunakan untuk mendeteksi kanvas yang kosong (harus abu-abu latar)
 * sehingga pemanggil tahu apakah snapshot peta benar-benar terbaca.
 *
 * @returns {number} 0 sampai 100
 */
export function mapContentRatio(canvas) {
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
 * Rasio sisi yang dipakai kanvas peta saat ekspor.
 *
 * Mengembalikan ukuran yang diperlukan agar kualitas PNG tidak bergantung
 * pada ukuran layar pengguna.
 */
export function exportSurfaceFor(map, targetAspect) {
  const width = EXPORT_SURFACE_WIDTH;
  const height = Math.round(EXPORT_SURFACE_WIDTH / targetAspect);

  const ceiling = Math.min(
    EXPORT_MAX_PIXEL_RATIO,
    EXPORT_CANVAS_MAX_SIDE / Math.max(width, 1),
    EXPORT_CANVAS_MAX_SIDE / Math.max(height, 1),
    Math.sqrt(EXPORT_CANVAS_MAX_PIXELS / Math.max(width * height, 1)),
  );

  const ratio = Math.max(1, ceiling);

  return { width, height, ratio };
}

/**
 * Tunggu peta selesai memuat tile dan selesai menggambar.
 */
export function waitForMapIdle(map, timeoutMs = 6000) {
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
 */
export function regionPadding(map, enabled = true) {
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
 * @returns {{west:number,south:number,east:number,north:number}|null}
 */
export function visibleRegion(map) {
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
    return null;
  }
}

/**
 * Arahkan peta ke satu wilayah, lalu kembalikan cara mengembalikan kamera lama.
 *
 * @returns {Promise<{ restore: () => void, framed: boolean, reason: string|null }>}
 */
export async function frameRegion(map, region, maxZoom, options = {}) {
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
      // Peta yang sudah dibuang tidak bisa dipulihkan.
    }
  };

  try {
    map.fitBounds(
      [
        [region.west, region.south],
        [region.east, region.north],
      ],
      {
        padding: regionPadding(map, options.pad !== false),
        bearing: 0,
        pitch: 0,
        animate: false,
        duration: 0,
        ...(Number.isFinite(maxZoom) ? { maxZoom } : {}),
      },
    );
  } catch (error) {
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
 * Jalankan suatu pekerjaan dengan kanvas peta yang disiapkan khusus ekspor.
 *
 * MapLibre hanya bisa mengubah ukuran kanvas lewat mengubah ukuran wadahnya
 * lalu memanggil `resize()`.
 *
 * @returns {Promise<unknown>}
 */
export async function withExportSurface(map, surface, run) {
  const container = map?.getContainer?.();

  if (!container) {
    return run();
  }

  const previousWidth = container.style.width;
  const previousHeight = container.style.height;
  const previousRatio = map.getPixelRatio?.();

  try {
    container.style.width = `${Math.round(surface.width)}px`;
    container.style.height = `${Math.round(surface.height)}px`;

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
        // Peta yang sudah dibuang tidak bisa dikembalikan.
      }
    }

    try {
      map.resize();
    } catch {
      // Sama seperti di atas: tidak ada yang bisa dilakukan kalau peta sudah mati.
    }
  }
}

/**
 * Meter per piksel pada zoom dan lintang tertentu.
 */
export function metersPerPixel(latitude, zoom) {
  return (156543.03392 * Math.cos((latitude * Math.PI) / 180)) / 2 ** zoom;
}

/**
 * Skala peta dalam bentuk 1:N.
 */
export function scaleDenominator(latitude, zoom) {
  const METERS_PER_INCH = 0.0254;
  const CSS_PIXELS_PER_INCH = 96;

  return metersPerPixel(latitude, zoom) / (METERS_PER_INCH / CSS_PIXELS_PER_INCH);
}
