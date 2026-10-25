/**
 * Cetak peta sebagai PNG — composable wrapper.
 *
 * Semua konstanta dan fungsi rendering sekarang ada di sub-modul:
 *  - useMapPngHeader.js: konstanta & tata letak
 *  - useMapPngCanvas.js: fungsi rendering kanvas
 *  - useMapPngCache.js: cache & pemuatan logo
 *  - useMapPngMeasure.js: pengukuran lembar & skala
 *  - useMapPngBuild.js: pembangun lembar PNG & unduhan
 *
 * Berkas ini hanya menyatukan semua itu ke dalam satu composable
 * `useMapPngExport()` untuk kompatibilitas backward dengan konsumen lama.
 */

import { ref } from 'vue';

import {
  DEFAULT_ORIENTATION,
  SHEET_LOGO_URL,
  SHEET_ORIENTATION_LIST,
} from './useMapPngHeader.js';
import {
  mapContentRatio,
  visibleRegion,
  frameRegion,
  withExportSurface,
  exportSurfaceFor,
} from './useMapPngCanvas.js';
import {
  buildMapPng,
  triggerPngDownload,
} from './useMapPngBuild.js';
import {
  loadLogo,
  logoBox,
} from './useMapPngCache.js';
import {
  measureSheet,
  scaleLabel,
  describeSheet,
} from './useMapPngMeasure.js';

// Re-export semua simbol dari sub-modul untuk kompatibilitas backward
// dengan konsumen dan tes yang mengimpor dari berkas ini secara langsung.
export {
  buildMapPng,
  triggerPngDownload,
  distanceLabel,
} from './useMapPngBuild.js';
export {
  setFont,
  wrapText,
  fitContain,
  drawCompass,
  legendHeight,
  drawLegend,
  drawHistogram,
  drawScaleBar,
  niceGridStep,
  drawGraticule,
  formatDegrees,
  mapContentRatio,
  exportSurfaceFor,
  waitForMapIdle,
  regionPadding,
  visibleRegion,
  frameRegion,
  withExportSurface,
  metersPerPixel,
  scaleDenominator,
} from './useMapPngCanvas.js';
export { logoCache, loadLogo, logoBox } from './useMapPngCache.js';
export {
  measureSheet,
  sheetScale,
  scaleLabel,
  describeSheet,
} from './useMapPngMeasure.js';
export {
  INK,
  FONT,
  SHEET_WIDTH,
  SHEET_HEIGHT,
  SHEET_ORIENTATIONS,
  DEFAULT_ORIENTATION,
  SHEET_ORIENTATION_LIST,
  SHEET_LOGO_URL,
  MARGIN,
  MAP_MIN_HEIGHT,
  MAP_MAX_RATIO,
  MAP_MIN_WIDTH,
  FOOTER_GAP,
  FOOTER_ROW,
  FOOTER_RULE_GAP,
  FOOTER_ROW_BOTTOM,
  FOOTER_HEIGHT,
  SHEET_SCALE,
  MAX_SHEET_PIXELS,
  EXPORT_CANVAS_MAX_SIDE,
  EXPORT_MAX_PIXEL_RATIO,
  EXPORT_CANVAS_MAX_PIXELS,
  EXPORT_SURFACE_WIDTH,
  LOGO_MAX_WIDTH,
  LOGO_MAX_HEIGHT,
  LOGO_GAP,
  TITLE_LINE_HEIGHT,
  TITLE_GAP,
  SUBTITLE_LINE_HEIGHT,
  SUBTITLE_GAP,
  PANEL,
  LEGEND_ITEMS,
  LEGEND_LABEL_X,
  DEFAULT_COMPONENTS,
  GRID_STEPS,
} from './useMapPngHeader.js';

/**
 * Composable utama untuk ekspor PNG peta.
 */
export function useMapPngExport() {
  const isExporting = ref(false);
  const exportError = ref(null);

  /**
   * Ambil snapshot peta yang sedang tampil.
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

    const copy = document.createElement('canvas');
    copy.width = source.width;
    copy.height = source.height;

    let blankReason = null;

    for (let attempt = 0; attempt < 3; attempt += 1) {
      try {
        map.triggerRepaint?.();
      } catch {
        // Peta yang sudah dihancurkan tidak bisa dipaksa.
      }

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
          try {
            copy.getContext('2d').drawImage(source, 0, 0);
          } catch {
            blankReason = 'Kanvas peta tidak dapat disalin.';
          }
          done();
          return;
        }

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
   */
  async function exportMapPng(map, overrides = {}) {
    if (isExporting.value) {
      return { ok: false, error: 'Ada proses cetak PNG lain yang sedang berjalan.' };
    }

    isExporting.value = true;
    exportError.value = null;

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

    const viewportRegion = matchViewport ? visibleRegion(map) : null;
    const targetRegion = region ?? viewportRegion;

    const logoSize = logoBox(await loadLogo(restOptions.logo));
    const sheet = measureSheet({
      title: restOptions.title,
      subtitle: subtitleOverride,
      orientation,
      logo: logoSize,
    });
    const { textLeft, textWidth, subtitleTop } = sheet;
    const surface = exportSurfaceFor(map, sheet.mapWidth / sheet.mapHeight);

    try {
      const printed = await withExportSurface(map, surface, async () => {
        let restoreCamera = null;
        let framingProblem = null;

        if (targetRegion && map?.fitBounds) {
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
          const bounds = visibleRegion(map);

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
          restoreCamera?.();
        }
      });

      const { blob, canvas, blocked, reason, framingProblem } = printed;

      const stamp = new Date().toISOString().slice(0, 16).replace(/[:T]/g, '-');
      triggerPngDownload(blob, filename ?? `peta-kontur-${stamp}.png`);

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
      exportError.value = error?.message ?? 'Gagal membuat PNG peta.';
      return { ok: false, blocked: false, error: exportError.value };
    } finally {
      isExporting.value = false;
    }
  }

  return { isExporting, exportError, exportMapPng };
}
