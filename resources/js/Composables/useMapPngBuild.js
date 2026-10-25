/**
 * Pembangun lembar cetak PNG dan fungsi unduhan.
 *
 * `buildMapPng` menggambar seluruh lembar (header, peta, panel, kaki halaman)
 * ke kanvas 2D dan mengembalikan Blob PNG. `triggerPngDownload` memicu
 * unduhan di browser lewat anchor + objectURL.
 */
import {
  INK,
  FONT,
  SHEET_WIDTH,
  SHEET_HEIGHT,
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
  PANEL,
  LEGEND_ITEMS,
  LEGEND_LABEL_X,
} from './useMapPngHeader.js';
import {
  fitContain,
  drawGraticule,
  drawLegend,
  drawHistogram,
  drawCompass,
  drawScaleBar,
  formatDegrees,
  setFont,
  wrapText,
} from './useMapPngCanvas.js';
import { loadLogo } from './useMapPngCache.js';

/**
 * Susun PNG cetak peta pada lembar landscape.
 *
 * @param {object} options
 * @param {HTMLCanvasElement|null} options.mapCanvas
 * @param {number} options.latitude
 * @param {number} options.longitude
 * @param {number} options.zoom
 * @param {number} options.bearing
 * @param {string} options.title
 * @param {string} options.subtitle
 * @param {boolean} options.areaBlocked
 * @param {string|null} options.mapProblem
 * @param {object} options.components
 * @param {object|null} options.elevation
 * @param {{west:number,east:number,south:number,north:number}|null} options.bounds
 * @param {string} options.sources
 * @param {string|null} options.logo
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
  orientation = 'landscape',
  logo = '/images/Logo_Urutan_Organiasasi_Kepramukaan.png',
  sources = 'Sumber: © OpenStreetMap contributors, PMTiles Kontur Sulsel',
} = {}) {
  const picked = { scaleBar: true, northArrow: true, legend: true, histogram: false, grid: false, ...components };

  const preset = {
    landscape: { label: 'Landscape 16:9', width: SHEET_WIDTH, height: SHEET_HEIGHT },
    portrait: { label: 'Potrait 3:4', width: 1000, height: 1333 },
  }[orientation] ?? {
    label: 'Landscape 16:9',
    width: SHEET_WIDTH,
    height: SHEET_HEIGHT,
  };

  const width = preset.width;
  const height = preset.height;
  const mapWidth = width - MARGIN * 2;

  const mapWidthMeters = ((156543.03392 * Math.cos((latitude * Math.PI) / 180)) / 2 ** zoom) * mapWidth;

  const subtitleText = subtitle || `Lebar area ${distanceLabel(mapWidthMeters)}`;

  const canvas = document.createElement('canvas');
  const SHEET_SCALE = 3;
  canvas.width = width * SHEET_SCALE;
  canvas.height = height * SHEET_SCALE;

  const ctx = canvas.getContext('2d');
  ctx.scale(SHEET_SCALE, SHEET_SCALE);

  ctx.fillStyle = INK.paper;
  ctx.fillRect(0, 0, width, height);

  // Logo
  let logoSize = null;
  if (logo) {
    try {
      const logoImage = await loadLogo(logo);
      if (logoImage && logoImage.width > 0 && logoImage.height > 0) {
        const ratio = Math.min(224 / logoImage.width, 56 / logoImage.height);
        logoSize = {
          width: Math.max(1, Math.round(logoImage.width * ratio)),
          height: Math.max(1, Math.round(logoImage.height * ratio)),
        };
        ctx.drawImage(logoImage, MARGIN, MARGIN, logoSize.width, logoSize.height);
      }
    } catch {
      // Logo gagal dimuat, lembar tetap dicetak tanpa logo.
    }
  }

  const reserved = logoSize ? logoSize.width + 20 : 0;
  const textLeft = MARGIN + reserved;
  const textWidth = Math.max(MAP_MIN_WIDTH, mapWidth - reserved);

  const probe = document.createElement('canvas').getContext('2d');
  setFont(probe, 32, '700');
  const titleLines = wrapText(probe, title, textWidth).length;
  setFont(probe, 14, '400');
  const subtitleLines = wrapText(probe, subtitleText, mapWidth).length;

  const titleHeight = titleLines * TITLE_LINE_HEIGHT + TITLE_GAP;
  const stackTop = Math.max(titleHeight, logoSize ? logoSize.height + 20 : 0);
  const subtitleTop = MARGIN + stackTop;
  const headerHeight = stackTop + subtitleLines * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP;

  const { textLeft: measuredTextLeft, textWidth: measuredTextWidth, subtitleTop: measuredSubtitleTop } = {
    textLeft,
    textWidth,
    subtitleTop,
  };

  let cursorY = MARGIN;
  setFont(ctx, 32, '700');
  ctx.fillStyle = INK.title;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  const wrappedTitle = wrapText(ctx, title, measuredTextWidth);
  wrappedTitle.forEach((line, index) => {
    ctx.fillText(line, measuredTextLeft, cursorY + index * TITLE_LINE_HEIGHT);
  });

  setFont(ctx, 14, '400');
  ctx.fillStyle = INK.muted;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';
  const wrappedSubtitle = wrapText(ctx, subtitleText, mapWidth);
  wrappedSubtitle.forEach((line, index) => {
    ctx.fillText(line, MARGIN, measuredSubtitleTop + index * SUBTITLE_LINE_HEIGHT);
  });

  const headerRuleY = measuredSubtitleTop + wrappedSubtitle.length * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP / 2;

  ctx.strokeStyle = INK.rule;
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.moveTo(MARGIN, headerRuleY);
  ctx.lineTo(width - MARGIN, headerRuleY);
  ctx.stroke();

  ctx.fillStyle = INK.accent;
  ctx.fillRect(MARGIN, headerRuleY - 1.5, 72, 3);

  cursorY = measuredSubtitleTop + wrappedSubtitle.length * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP;

  const mapX = MARGIN;
  const mapY = cursorY;

  const visibleBounds = bounds;
  let areaRect = { x: mapX, y: mapY, w: mapWidth, h: mapHeight };

  ctx.save();
  ctx.beginPath();
  ctx.rect(mapX, mapY, mapWidth, mapHeight);
  ctx.clip();

  ctx.fillStyle = '#e5e7eb';
  ctx.fillRect(mapX, mapY, mapWidth, mapHeight);

  if (mapCanvas && mapCanvas.width > 0 && mapCanvas.height > 0) {
    const placement = fitContain(mapCanvas.width, mapCanvas.height, mapX, mapY, mapWidth, mapHeight);
    ctx.drawImage(mapCanvas, placement.x, placement.y, placement.w, placement.h);
    areaRect = placement;
  } else {
    setFont(ctx, 15, '700');
    ctx.fillStyle = INK.title;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('Area peta tidak ikut tercetak', mapX + mapWidth / 2, mapY + mapHeight / 2 - 22);

    setFont(ctx, 13, '400');
    ctx.fillStyle = INK.muted;

    const detail = areaBlocked
      ? 'Browser memblokir pembacaan tile karena izin CORS host peta.'
      : mapProblem ?? 'Kanvas peta tidak memberi isinya saat disalin.';

    const lines = wrapText(ctx, detail, mapWidth - 80);
    lines.forEach((line, index) => {
      ctx.fillText(line, mapX + mapWidth / 2, mapY + mapHeight / 2 + 6 + index * 20);
    });
  }

  const reservedPanel = {
    topLeft: picked.legend,
    topRight: picked.histogram,
    bottomLeft: picked.scaleBar,
    bottomRight: picked.northArrow,
  };

  if (picked.grid && visibleBounds) {
    drawGraticule(ctx, areaRect, visibleBounds, reservedPanel);
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

  if (picked.northArrow) {
    drawCompass(
      ctx,
      areaRect.x + areaRect.w - PANEL.inset - PANEL.compassRadius,
      areaRect.y + areaRect.h - PANEL.inset - PANEL.compassRadius,
      PANEL.compassRadius,
      bearing,
    );
  }

  if (picked.scaleBar) {
    drawScaleBar(
      ctx,
      areaRect.x + PANEL.inset + 8,
      areaRect.y + areaRect.h - PANEL.inset - 26,
      ((156543.03392 * Math.cos((latitude * Math.PI) / 180)) / 2 ** zoom),
      PANEL.scaleBarMaxWidth,
    );
  }

  ctx.restore();

  ctx.strokeStyle = INK.frame;
  ctx.lineWidth = 1.5;
  ctx.strokeRect(mapX, mapY, mapWidth, mapHeight);

  cursorY = mapY + mapHeight + FOOTER_GAP;
  setFont(ctx, 12, '400');
  ctx.fillStyle = INK.muted;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'top';

  ctx.fillStyle = INK.accent;
  ctx.fillRect(MARGIN, cursorY + 4, 22, 4);
  ctx.fillStyle = INK.muted;
  ctx.fillText('Garis kontur (garis tebal = kontur indeks)', MARGIN + 30, cursorY);

  ctx.textAlign = 'right';
  ctx.fillText(sources, width - MARGIN, cursorY);

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

  ctx.textAlign = 'right';
  ctx.fillText('AMBARA - Sistem Digital Ambalan UPT SMAN 2 Maros', width - MARGIN, footerBottomY);

  return new Promise((resolve, reject) => {
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
 * Panjang dunia nyata yang terwakili oleh sejumlah piksel.
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
 * Memicu unduhan PNG di browser.
 */
export function triggerPngDownload(blob, filename) {
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();

  setTimeout(() => URL.revokeObjectURL(url), 10_000);
}
