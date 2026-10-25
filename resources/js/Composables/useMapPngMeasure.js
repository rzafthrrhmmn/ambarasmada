/**
 * Pengukuran ukuran lembar dan area peta untuk cetak PNG.
 *
 * Fungsi di sini dipakai oleh pengukur tinggi dan penggambar agar keduanya
 * tidak pernah menghasilkan jawaban berbeda soal ukuran area peta.
 */
import {
  SHEET_ORIENTATIONS,
  DEFAULT_ORIENTATION,
  MARGIN,
  MAP_MIN_HEIGHT,
  MAP_MAX_RATIO,
  MAP_MIN_WIDTH,
  FOOTER_GAP,
  FOOTER_HEIGHT,
  TITLE_LINE_HEIGHT,
  TITLE_GAP,
  SUBTITLE_LINE_HEIGHT,
  SUBTITLE_GAP,
  LOGO_GAP,
  LOGO_MAX_WIDTH,
  LOGO_MAX_HEIGHT,
} from './useMapPngHeader.js';
import { setFont, wrapText, metersPerPixel, scaleDenominator } from './useMapPngCanvas.js';

/**
 * Hitung ukuran lembar dan area petanya untuk isi tertentu.
 *
 * Tinggi area peta bergantung pada jumlah baris judul dan subjudul, dan itu
 * hanya bisa diketahui setelah teksnya diukur. Fungsi ini dipakai dua tempat:
 * `buildMapPng` untuk benar-benar menggambar, dan jalur ekspor untuk mengetahui
 * rasio sisi kotak peta lebih dulu.
 *
 * @param {{ title?: string, subtitle?: string, orientation?: string, logo?: {width:number,height:number}|null }} [options]
 * @returns {{width:number, height:number, mapWidth:number, mapHeight:number, textLeft:number, textWidth:number, subtitleTop:number, orientation:string}}
 */
export function measureSheet({ title = '', subtitle = '', orientation = DEFAULT_ORIENTATION, logo = null } = {}) {
  const preset = SHEET_ORIENTATIONS[orientation] ?? SHEET_ORIENTATIONS[DEFAULT_ORIENTATION];

  const width = preset.width;
  const height = preset.height;
  const mapWidth = width - MARGIN * 2;
  const probe = document.createElement('canvas').getContext('2d');

  const reserved = logo ? logo.width + LOGO_GAP : 0;
  const textLeft = MARGIN + reserved;
  const textWidth = Math.max(MAP_MIN_WIDTH, mapWidth - reserved);

  const fontSizeTitle = 32;
  const fontSizeSubtitle = 14;

  setFont(probe, fontSizeTitle, '700');
  const titleLines = wrapText(probe, title, textWidth).length;
  setFont(probe, fontSizeSubtitle, '400');
  const subtitleLines = wrapText(probe, subtitle, mapWidth).length;

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
 * Skala lembar yang aman untuk ukuran lembar sekarang.
 */
export function sheetScale(sheet) {
  const MAX_SHEET_PIXELS = 16_000_000;
  const SHEET_SCALE = 3;

  const area = sheet.width * sheet.height;
  const capped = Math.sqrt(MAX_SHEET_PIXELS / area);
  return Math.max(1, Math.min(SHEET_SCALE, capped));
}

/**
 * Skala peta dalam bentuk 1:N, siap ditulis.
 */
export function scaleLabel(latitude, zoom) {
  const denominator = Math.round(scaleDenominator(latitude, zoom));

  if (!Number.isFinite(denominator) || denominator <= 0) {
    return '1:-';
  }

  return `1:${denominator.toLocaleString('id-ID')}`;
}

/**
 * Ringkasan format lembar untuk ditampilkan sebagai pratinjau di dialog.
 */
export function describeSheet(orientation = 'landscape') {
  const preset = {
    landscape: { label: 'Landscape 16:9', width: 1600, height: 900 },
    portrait: { label: 'Potrait 3:4', width: 1000, height: 1333 },
  }[orientation] ?? {
    label: 'Landscape 16:9',
    width: 1600,
    height: 900,
  };

  const logoSize = { width: LOGO_MAX_WIDTH, height: LOGO_MAX_HEIGHT };
  const sheet = measureSheet({
    title: 'Peta Kontur - Contoh Wilayah',
    subtitle: 'Koordinat tengah 119,4321, -5,1234 | Zoom 11.0 | Skala 1:250.000',
    orientation,
    logo: { width: LOGO_MAX_WIDTH, height: LOGO_MAX_HEIGHT },
  });
  const scale = sheetScale(sheet);

  return {
    orientation: orientation in { landscape: true, portrait: true } ? orientation : 'landscape',
    label: preset.label,
    width: sheet.width,
    height: sheet.height,
    mapAspect: sheet.mapWidth / sheet.mapHeight,
    outputWidth: sheet.width * scale,
    outputHeight: sheet.height * scale,
  };
}
