/**
 * Logika unduhan paket tile offline.
 *
 * Diekstrak dari `resources/js/Pages/Peta/Index.vue`. Menangani unduhan tile
 * PMTiles untuk offline melalui service worker, disertai snapshot PNG peta.
 */
import { ref } from 'vue';
import { getActiveServiceWorker, SW_PROTOCOL } from '@/ServiceWorker.js';

export function useMapOffline() {
  /**
   * Unduh paket tile offline + PNG.
   *
   * @param {Object} map
   * @param {Function} exportMapPng
   * @param {Function} printMapPng
   * @param {Function} getActiveServiceWorkerFn
   * @param {number} SW_PROTOCOL
   * @param {Object} currentBBox
   * @param {number} offlineZoomMin
   * @param {number} offlineZoomMax
   * @param {string} pmtilesUrl
   * @param {string} geojsonUrl
   * @param {string} kecamatanGeojsonUrl
   * @param {boolean} hasKecamatanGeojson
   * @param {Object|null} selectedOfflineWilayah
   * @param {Object} layoutOptions
   * @param {string} areaName
   * @param {Ref} downloading
   * @param {Ref} downloadStatus
   * @returns {Promise<void>}
   */
  async function downloadOffline(map, exportMapPng, printMapPng, getActiveServiceWorkerFn, SW_PROTOCOL, currentBBox, offlineZoomMin, offlineZoomMax, pmtilesUrl, geojsonUrl, kecamatanGeojsonUrl, hasKecamatanGeojson, selectedOfflineWilayah, layoutOptions, areaName, downloading, downloadStatus) {
    if (!pmtilesUrl) {
      downloadStatus.value = 'PMTiles URL tidak tersedia.';
      return;
    }

    downloading.value = true;
    downloadStatus.value = 'Menyiapkan PNG peta...';

    // PNG dicetak lebih dulu, sebelum paket tile diambil. Urutan ini menentukan
    // isi berkasnya: snapshot PNG diambil dari kanvas peta yang sedang tampil.
    let png = { ok: false };
    if (printMapPng) {
      png = await printMapPng({ silent: true });
    }

    if (!('serviceWorker' in navigator)) {
      downloadStatus.value = 'Service Worker tidak didukung browser ini, jadi paket tile offline tidak bisa dibuat.';
      downloading.value = false;
      return;
    }

    try {
      const worker = await (getActiveServiceWorkerFn || getActiveServiceWorker)();

      const bbox = currentBBox ?? {
        west: 118.5,
        east: 125.5,
        south: -6.0,
        north: 2.0,
      };
      const wilayah = selectedOfflineWilayah;
      if (wilayah && wilayah.bbox) {
        bbox.west = wilayah.bbox[0];
        bbox.south = wilayah.bbox[1];
        bbox.east = wilayah.bbox[2];
        bbox.north = wilayah.bbox[3];
      }

      const minZoom = offlineZoomMin ?? 8;
      const maxZoom = offlineZoomMax ?? 12;

      // PMTiles di-host di Supabase Storage, jadi service worker harus memakai
      // URL absolut agar HTTP Range langsung dibaca dari host arsip. GeoJSON
      // masih dilayani dari origin sendiri sehingga boleh dibuat relatif.
      const absPmtilesUrl = pmtilesUrl;
      const geojsonUrlRelative = geojsonUrl.replace(/^https?:\/\/[^\/]+/, '');

      downloadStatus.value = png.ok
        ? 'PNG peta tersimpan. Mengunduh paket tile untuk offline...'
        : 'PNG gagal dibuat. Mengunduh paket tile untuk offline...';

      const channel = new MessageChannel();
      channel.port1.onmessage = (event) => {
        const data = event.data;

        if (data.protocol !== SW_PROTOCOL) {
          downloadStatus.value =
            'Service Worker di perangkat ini masih versi lama. Muat ulang halaman (Ctrl+Shift+R), lalu ulangi unduhan.';
          downloading.value = false;
          return;
        }

        if (data.type === 'DOWNLOAD_PROGRESS') {
          downloadStatus.value = `${data.status} (${data.downloaded}/${data.total} tile)`;
        }
        if (data.type === 'DOWNLOAD_COMPLETE') {
          const skipped = data.skipped ? `, ${data.skipped} tile tidak tersedia di arsip` : '';
          downloadStatus.value =
            `Selesai! ${data.downloaded} tile berhasil diunduh untuk offline (zoom ${data.zoomMin}-${data.zoomMax}${skipped}).`
            + (png.ok ? ' PNG peta tersimpan.' : ' PNG peta gagal dibuat.');
          downloading.value = false;
        }
        if (data.type === 'DOWNLOAD_ERROR') {
          downloadStatus.value = data.error;
          downloading.value = false;
        }
      };

      worker.postMessage(
        {
          type: 'DOWNLOAD_OFFLINE_TILES',
          bbox,
          zoomMin: minZoom,
          zoomMax: maxZoom,
          pmtilesUrl: absPmtilesUrl,
          geojsonUrl: geojsonUrlRelative,
          protocol: SW_PROTOCOL,
        },
        [channel.port2]
      );
    } catch (error) {
      downloadStatus.value = `Gagal mengunduh: ${error.message}`;
      downloading.value = false;
    }
  }

  return {
    downloadOffline,
  };
}
