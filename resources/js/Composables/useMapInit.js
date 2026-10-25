/**
 * Inisialisasi dan kontrol peta MapLibre.
 *
 * Diekstrak dari `resources/js/Pages/Peta/Index.vue`. Semua fungsi menerima
 * instansi peta dan ref/state dari komponen pemanggil agar composable ini
 * tetap bebas dari state milik komponen tertentu.
 */
import { ref } from 'vue';
import { scaleLabel } from '@/Composables/useMapPngMeasure.js';
import { BASEMAPS } from '@/basemaps.js';

export function useMapInit() {
  const map = ref(null);
  const mouseCoords = ref(null);

  /**
   * Inisialisasi MapLibre dengan PMTiles protocol, basemap layers,
   * kontur layers, batas kabupaten, batas kecamatan (bersyarat),
   * ScaleControl, NavigationControl, dan event listeners.
   *
   * @param {Ref} mapContainer
   * @param {Object} mapConfig
   * @param {Ref} basemap
   * @param {Ref} showContour
   * @param {Ref} showHillshade
   * @param {Ref} hasGeolocation
   * @param {Ref} isOffline
   * @param {Ref} mapError
   * @param {Ref} mapStatus
   * @param {Ref} loading
   */
  async function initMap(mapContainer, mapConfig, basemap, showContour, showHillshade, hasGeolocation, isOffline, mapError, mapStatus, loading) {
    if (!mapContainer.value || !mapConfig?.hasPmtiles) return;

    mapError.value = null;
    loading.value = true;

    try {
      const { Map, addProtocol, ScaleControl, NavigationControl, GLYPHS_URL } = await import('../../maplibre');
      const { Protocol } = await import('pmtiles');

      const protocol = new Protocol();
      addProtocol('pmtiles', protocol.tile);

      const geojsonUrl = mapConfig?.geojsonUrl ?? '/storage/maps/batas_kabupaten_sulsel.geojson';
      const relativeGeojson = geojsonUrl.startsWith('http')
        ? geojsonUrl.replace(/^https?:\/\/[^\/]+/, '')
        : geojsonUrl;
      const pmtilesSourceUrl = `pmtiles://${mapConfig?.pmtilesUrl ?? ''}`;

      map.value = new Map({
        container: mapContainer.value,
        preserveDrawingBuffer: true,
        style: {
          version: 8,
          glyphs: GLYPHS_URL,
          sources: {
            'kontur-sulawesi': {
              type: 'vector',
              url: pmtilesSourceUrl,
            },
            'batas-kabupaten': {
              type: 'geojson',
              data: relativeGeojson,
            },
            'osm-tiles': BASEMAPS.osm,
            'satellite-tiles': BASEMAPS.satellite,
            'terrain-tiles': BASEMAPS.terrain,
            'dark-tiles': BASEMAPS.dark,
            'hillshade-tiles': {
              type: 'raster-dem',
              tiles: ['https://s3.amazonaws.com/elevation-tiles-prod/terrarium/{z}/{x}/{y}.png'],
              encoding: 'terrarium',
              tileSize: 256,
              maxzoom: 14,
            },
          },
          layers: [
            {
              id: 'osm-base',
              type: 'raster',
              source: 'osm-tiles',
              layout: { visibility: 'visible' },
            },
            {
              id: 'satellite-layer',
              type: 'raster',
              source: 'satellite-tiles',
              layout: { visibility: 'none' },
            },
            {
              id: 'terrain-layer',
              type: 'raster',
              source: 'terrain-tiles',
              layout: { visibility: 'none' },
            },
            {
              id: 'dark-layer',
              type: 'raster',
              source: 'dark-tiles',
              layout: { visibility: 'none' },
            },
            {
              id: 'hillshade-layer',
              type: 'hillshade',
              source: 'hillshade-tiles',
              layout: { visibility: showHillshade.value ? 'visible' : 'none' },
              paint: {
                'hillshade-illumination-direction': 315,
                'hillshade-illumination-anchor': 'map',
                'hillshade-exaggeration': 0.5,
                'hillshade-shadow-color': 'rgba(0, 0, 0, 0.5)',
                'hillshade-highlight-color': 'rgba(255, 255, 255, 0.5)',
                'hillshade-accent-color': 'rgba(140, 81, 10, 0.3)',
              },
            },
            {
              id: 'garis-kontur',
              type: 'line',
              source: 'kontur-sulawesi',
              'source-layer': 'kontur',
              layout: {
                'line-join': 'round',
                'line-cap': 'round',
                visibility: showContour.value ? 'visible' : 'none',
              },
              paint: {
                'line-color': '#8c510a',
                'line-width': ['case', ['==', ['%', ['get', 'ELEV'], 50], 0], 1.8, 0.8],
                'line-opacity': 0.8,
              },
            },
            {
              id: 'kabupaten-border',
              type: 'line',
              source: 'batas-kabupaten',
              paint: {
                'line-color': '#2563eb',
                'line-width': 1.5,
                'line-dasharray': [2, 2],
              },
            },
          ],
        },
        center: mapConfig.center ?? [119.863, -0.900],
        zoom: mapConfig.zoom ?? 10,
        maxZoom: 15,
      });

      const scale = new ScaleControl({ maxWidth: 200, unit: 'metric' });
      map.value.addControl(scale, 'bottom-left');

      const nav = new NavigationControl({ showCompass: true, showZoom: false });
      map.value.addControl(nav, 'top-right');

      if ('geolocation' in navigator) {
        hasGeolocation.value = true;
      }

      const clearLoading = () => {
        loading.value = false;
      };

      map.value.on('idle', clearLoading);

      setTimeout(() => {
        if (loading.value) {
          clearLoading();
          mapStatus.value = 'Peta dimuat sebagian: sebagian layer belum selesai masuk dan masih dimuat di latar belakang.';
        }
      }, 12000);

      map.value.on('load', () => {
        clearLoading();
        mapError.value = null;
        mapStatus.value = 'Peta kontur Sulawesi dimuat. Garis kontur setiap 10 meter elevasi.';
      });

      map.value.on('error', (e) => {
        clearLoading();
        const errorMsg = e.error?.message || 'Kesalahan peta';
        mapStatus.value = `Peringatan: ${errorMsg}`;
        console.error('Map error:', e.error || errorMsg);
        if (
          errorMsg.includes('source') ||
          errorMsg.includes('tile') ||
          errorMsg.includes('network') ||
          errorMsg.includes('404') ||
          errorMsg.includes('500')
        ) {
          mapError.value = `Gagal memuat data peta: ${errorMsg}. Periksa koneksi dan coba lagi.`;
        }
      });

      // Mouse move - coordinate display
      map.value.on('mousemove', (e) => {
        mouseCoords.value = { lng: e.lngLat.lng, lat: e.lngLat.lat };
      });

      map.value.on('mouseleave', () => {
        mouseCoords.value = null;
      });

      map.value.on('click', () => {
        // Tempat kait untuk komponen pemanggil jika diperlukan.
      });

      // Offline/online detection
      const onlineHandler = () => { isOffline.value = false; };
      const offlineHandler = () => { isOffline.value = true; };
      window.addEventListener('online', onlineHandler);
      window.addEventListener('offline', offlineHandler);

      // Expose handlers for cleanup on unmount
      initMap.__onlineHandler = onlineHandler;
      initMap.__offlineHandler = offlineHandler;
    } catch (error) {
      loading.value = false;
      mapError.value = `Gagal memuat peta: ${error.message}`;
      console.error('Map initialization error:', error);
    }
  }

  /**
   * Ganti visibilitas layer basemap.
   * @param {Object} mapInstance
   * @param {Ref} basemap
   */
  function changeBasemap(mapInstance, basemap) {
    if (!mapInstance) return;
    const layers = ['osm-base', 'satellite-layer', 'terrain-layer', 'dark-layer'];
    layers.forEach((layer) => {
      if (mapInstance.getLayer(layer)) {
        const visible =
          layer === `${basemap.value}-layer` ||
          (layer === 'osm-base' && basemap.value === 'osm');
        mapInstance.setLayoutProperty(layer, 'visibility', visible ? 'visible' : 'none');
      }
    });
  }

  /**
   * Geolokasi pengguna.
   * @param {Object} mapInstance
   * @param {Ref} hasGeolocation
   * @param {Ref} locating
   */
  function locateUser(mapInstance, hasGeolocation, locating) {
    if (!mapInstance || !hasGeolocation.value) return;
    locating.value = true;
    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        const { longitude, latitude } = pos.coords;
        mapInstance.flyTo({ center: [longitude, latitude], zoom: 14, duration: 2000 });
        const { Popup } = await import('../../maplibre');
        new Popup()
          .setLngLat([longitude, latitude])
          .setHTML('<div class="p-2 text-[#1f2937]">Lokasi Anda</div>')
          .addTo(mapInstance);
        locating.value = false;
      },
      (err) => {
        locating.value = false;
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }

  /**
   * Toggle visibility layer.
   * @param {Object} mapInstance
   * @param {string} layerId
   * @param {boolean} visible
   */
  function toggleLayer(mapInstance, layerId, visible) {
    if (!mapInstance) return;
    if (mapInstance.getLayer(layerId)) {
      mapInstance.setLayoutProperty(layerId, 'visibility', visible ? 'visible' : 'none');
    }
  }

  /**
   * Toggle hillshade.
   * @param {Object} mapInstance
   * @param {boolean} visible
   */
  function toggleHillshade(mapInstance, visible) {
    if (!mapInstance) return;
    const layerId = 'hillshade-layer';
    if (mapInstance.getLayer(layerId)) {
      mapInstance.setLayoutProperty(layerId, 'visibility', visible ? 'visible' : 'none');
    }
  }

  /**
   * Hitung skala 1:N dari center + zoom.
   * @param {Object} mapInstance
   * @param {Function} scaleLabel
   * @returns {string|null}
   */
  function updateMapScale(mapInstance, scaleLabelFn = scaleLabel) {
    if (!mapInstance) return null;
    const center = mapInstance.getCenter();
    const zoom = mapInstance.getZoom();
    return scaleLabelFn(center.lat, zoom);
  }

  return {
    map,
    mouseCoords,
    initMap,
    changeBasemap,
    locateUser,
    toggleLayer,
    toggleHillshade,
    updateMapScale,
  };
}
