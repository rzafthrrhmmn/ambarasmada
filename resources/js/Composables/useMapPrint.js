/**
 * Logika cetak peta.
 *
 * Diekstrak dari `resources/js/Pages/Peta/Index.vue`. Fungsi-fungsi di sini
 * menangani ekspor PNG dan jendela cetak HTML mandiri.
 */
import { ref } from 'vue';
import { scaleLabel } from '@/Composables/useMapPngMeasure.js';
import { PRINT_BASEMAP, basemapAttribution } from '@/basemaps.js';

export function useMapPrint() {
  /**
   * Escape teks sebelum masuk ke HTML cetakan.
   * @param {string} text
   * @returns {string}
   */
  function escapeHtml(text) {
    return String(text ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
    })[c]);
  }

  /**
   * Buat nama berkas aman dari teks.
   * @param {string} text
   * @returns {string}
   */
  function slugify(text) {
    return String(text ?? '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'peta';
  }

  /**
   * Cetak peta yang sedang tampil sebagai PNG.
   *
   * @param {Object} map
   * @param {Function} exportMapPng
   * @param {Ref|string} sourceCredit
   * @param {string} printOrientation
   * @param {string} logoUrl
   * @param {Object} printComponents
   * @param {Object|null} selectedWilayah
   * @param {Array|null} selectedWilayahBBox
   * @param {boolean} matchViewport
   * @param {Object|null} region
   * @param {Object} components
   * @returns {Promise<Object>}
   */
  async function printMapPng(map, exportMapPng, sourceCredit, printOrientation, logoUrl, printComponents, selectedWilayah, selectedWilayahBBox, matchViewport, region, components) {
    if (!map || !exportMapPng) {
      return { ok: false, error: 'Peta atau fungsi ekspor tidak tersedia.' };
    }

    const result = await exportMapPng(map, {
      title: 'Peta Kontur Sulawesi',
      sources: sourceCredit.value ?? sourceCredit,
      region: selectedWilayahBBox ? { bbox: selectedWilayahBBox } : region,
      matchViewport,
      orientation: printOrientation,
      logo: logoUrl,
    });

    return result;
  }

  /**
   * Buka jendela cetak dengan HTML print.
   *
   * @param {Object} map
   * @param {Function} escapeHtml
   * @param {string} scaleLabel
   * @param {string} geojsonUrl
   * @param {string} pmtilesSourceUrl
   * @param {string} basemapAttributionText
   * @param {Object} PRINT_BASEMAP
   * @param {boolean} hasKecamatanGeojson
   * @param {string} kecamatanGeojsonUrl
   * @param {Object|null} selectedWilayah
   */
  function printMap(map, escapeHtmlFn, scaleLabelText, geojsonUrl, pmtilesSourceUrl, basemapAttributionText, PRINT_BASEMAP, hasKecamatanGeojson, kecamatanGeojsonUrl, selectedWilayah) {
    if (!map) return;
    const printWindow = window.open('', '_blank');
    const center = map.getCenter();
    const zoom = map.getZoom();
    const bearing = map.getBearing();
    const skalaLabel = scaleLabelText || scaleLabel(center.lat, zoom);
    const date = new Date().toLocaleString('id-ID');
    const url = geojsonUrl;
    const kecUrl = hasKecamatanGeojson ? kecamatanGeojsonUrl : '';
    const sumber = escapeHtmlFn(`Sumber: ${basemapAttributionText}, PMTiles Kontur Sulsel`);
    const wilayahLabel = selectedWilayah ? escapeHtmlFn(selectedWilayah.label || selectedWilayah.name || '') : '';
    const wilayahKode = selectedWilayah ? escapeHtmlFn(selectedWilayah.kode || '') : '';

    const html = `<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Peta Kontur Sulawesi - Cetak</title>
<style>
  @page { margin: 1cm; size: A4 landscape; }
  body { margin: 0; font-family: Arial, sans-serif; }
  .header { text-align: center; margin-bottom: 10px; }
  .header h1 { margin: 0; font-size: 24px; color: #1f2937; }
  .header p { margin: 5px 0; font-size: 12px; color: #6b7280; }
  .map-container { position: relative; width: 100%; height: 70vh; border: 2px solid #6F9435; }
  #print-map { width: 100%; height: 100%; }
  .footer { display: flex; justify-content: space-between; margin-top: 10px; font-size: 11px; color: #6b7280; }
  .legend { display: flex; gap: 20px; flex-wrap: wrap; }
  .legend-item { display: flex; align-items: center; gap: 5px; }
  .legend-color { width: 20px; height: 3px; border-radius: 2px; }
</style></head><body>
<div class="header">
  <h1>Peta Kontur Sulawesi</h1>
  <p>Dicetak pada: ${date} | Koordinat tengah: ${center.lng.toFixed(6)}, ${center.lat.toFixed(6)} | Zoom: ${zoom.toFixed(1)} | Skala ${skalaLabel}
  ${wilayahLabel ? ` | Wilayah: ${wilayahLabel}` : ''}
  ${wilayahKode ? ` | Kode: ${wilayahKode}` : ''}
  </p>
</div>
<div class="map-container" id="print-map"></div>
<div class="footer">
  <div>${sumber}</div>
  <div class="legend">
    <div class="legend-item"><span class="legend-color" style="background:#8c510a"></span> Kontur</div>
    <div class="legend-item"><span class="legend-color" style="background:#2563eb; border:1px dashed #2563eb"></span> Batas Kabupaten</div>
  </div>
</div>
<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"><\/script>
<link href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" rel="stylesheet" />
<script src="https://unpkg.com/pmtiles/dist/pmtiles.js"><\/script>
<script>
  const protocol = new pmtiles.Protocol();
  maplibregl.addProtocol('pmtiles', protocol.tile);
  const map = new maplibregl.Map({
    container: 'print-map',
    style: {
      version: 8,
      sources: {
        'kontur': { type: 'vector', url: '${pmtilesSourceUrl}' },
        'batas': { type: 'geojson', data: '${url}' },
        ${hasKecamatanGeojson ? `'kecamatan': { type: 'geojson', data: '${kecUrl}' },` : ''}
        'osm': ${JSON.stringify(PRINT_BASEMAP)}
      },
      layers: [
        { id: 'osm', type: 'raster', source: 'osm' },
        { id: 'kontur', type: 'line', source: 'kontur', 'source-layer': 'kontur',
          layout: { 'line-join': 'round', 'line-cap': 'round' },
          paint: { 'line-color': '#8c510a', 'line-width': ['case', ['==', ['%', ['get', 'ELEV'], 50], 0], 1.8, 0.8] }
        },
        { id: 'batas', type: 'line', source: 'batas', paint: { 'line-color': '#2563eb', 'line-width': 1.5, 'line-dasharray': [2, 2] } }
        ${hasKecamatanGeojson ? `,
        { id: 'kecamatan', type: 'line', source: 'kecamatan', paint: { 'line-color': '#6F9435', 'line-width': 1, 'line-dasharray': [1, 1] } }` : ''}
      ]
    },
    center: [${center.lng}, ${center.lat}],
    zoom: ${zoom},
    bearing: ${bearing},
    pitch: ${map.getPitch?.() || 0},
  });
  map.once('load', () => { window.print(); });
<\/script>
</body></html>`;
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
  }

  /**
   * Generate HTML halaman cetak (kamera + wilayah).
   *
   * @param {Object} camera
   * @param {Object|null} wilayah
   * @param {Object} options
   * @returns {string}
   */
  function generatePrintHTML(camera, wilayah, options = {}) {
    const {
      date = new Date().toLocaleString('id-ID'),
      geojsonUrl = '',
      pmtilesSourceUrl = 'pmtiles://',
      basemapAttributionText = '',
      PRINT_BASEMAP = {},
      hasKecamatanGeojson = false,
      kecamatanGeojsonUrl = '',
    } = options;

    const center = camera?.center ?? { lng: 0, lat: 0 };
    const zoom = camera?.zoom ?? 10;
    const bearing = camera?.bearing ?? 0;
    const pitch = camera?.pitch ?? 0;
    const skalaLabel = scaleLabel(center.lat, zoom);
    const sumber = escapeHtml(`Sumber: ${basemapAttributionText}, PMTiles Kontur Sulsel`);
    const wilayahLabel = wilayah ? escapeHtml(wilayah.label || wilayah.name || '') : '';
    const wilayahKode = wilayah ? escapeHtml(wilayah.kode || '') : '';

    return `<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Peta Kontur Sulawesi - Cetak</title>
<style>
  @page { margin: 1cm; size: A4 landscape; }
  body { margin: 0; font-family: Arial, sans-serif; }
  .header { text-align: center; margin-bottom: 10px; }
  .header h1 { margin: 0; font-size: 24px; color: #1f2937; }
  .header p { margin: 5px 0; font-size: 12px; color: #6b7280; }
  .map-container { position: relative; width: 100%; height: 70vh; border: 2px solid #6F9435; }
  #print-map { width: 100%; height: 100%; }
  .footer { display: flex; justify-content: space-between; margin-top: 10px; font-size: 11px; color: #6b7280; }
  .legend { display: flex; gap: 20px; flex-wrap: wrap; }
  .legend-item { display: flex; align-items: center; gap: 5px; }
  .legend-color { width: 20px; height: 3px; border-radius: 2px; }
</style></head><body>
<div class="header">
  <h1>Peta Kontur Sulawesi</h1>
  <p>Dicetak pada: ${date} | Koordinat tengah: ${center.lng.toFixed(6)}, ${center.lat.toFixed(6)} | Zoom: ${zoom.toFixed(1)} | Skala ${skalaLabel}
  ${wilayahLabel ? ` | Wilayah: ${wilayahLabel}` : ''}
  ${wilayahKode ? ` | Kode: ${wilayahKode}` : ''}
  </p>
</div>
<div class="map-container" id="print-map"></div>
<div class="footer">
  <div>${sumber}</div>
  <div class="legend">
    <div class="legend-item"><span class="legend-color" style="background:#8c510a"></span> Kontur</div>
    <div class="legend-item"><span class="legend-color" style="background:#2563eb; border:1px dashed #2563eb"></span> Batas Kabupaten</div>
  </div>
</div>
<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"><\/script>
<link href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" rel="stylesheet" />
<script src="https://unpkg.com/pmtiles/dist/pmtiles.js"><\/script>
<script>
  const protocol = new pmtiles.Protocol();
  maplibregl.addProtocol('pmtiles', protocol.tile);
  const map = new maplibregl.Map({
    container: 'print-map',
    style: {
      version: 8,
      sources: {
        'kontur': { type: 'vector', url: '${pmtilesSourceUrl}' },
        'batas': { type: 'geojson', data: '${geojsonUrl}' },
        ${hasKecamatanGeojson ? `'kecamatan': { type: 'geojson', data: '${kecamatanGeojsonUrl}' },` : ''}
        'osm': ${JSON.stringify(PRINT_BASEMAP)}
      },
      layers: [
        { id: 'osm', type: 'raster', source: 'osm' },
        { id: 'kontur', type: 'line', source: 'kontur', 'source-layer': 'kontur',
          layout: { 'line-join': 'round', 'line-cap': 'round' },
          paint: { 'line-color': '#8c510a', 'line-width': ['case', ['==', ['%', ['get', 'ELEV'], 50], 0], 1.8, 0.8] }
        },
        { id: 'batas', type: 'line', source: 'batas', paint: { 'line-color': '#2563eb', 'line-width': 1.5, 'line-dasharray': [2, 2] } }
        ${hasKecamatanGeojson ? `,
        { id: 'kecamatan', type: 'line', source: 'kecamatan', paint: { 'line-color': '#6F9435', 'line-width': 1, 'line-dasharray': [1, 1] } }` : ''}
      ]
    },
    center: [${center.lng}, ${center.lat}],
    zoom: ${zoom},
    bearing: ${bearing},
    pitch: ${pitch},
  });
  map.once('load', () => { window.print(); });
<\/script>
</body></html>`;
  }

  return {
    printMapPng,
    printMap,
    escapeHtml,
    generatePrintHTML,
    slugify,
  };
}
