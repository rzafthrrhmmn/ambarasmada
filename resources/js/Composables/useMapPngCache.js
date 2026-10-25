/**
 * Cache dan pemuatan gambar logo untuk header lembar cetak PNG.
 *
 * Logo yang sudah dimuat dikunci per URL supaya tidak diunduh dua kali:
 * pengukuran lembar dan penggambar keduanya butuh logo, dan tanpa cache
 * keduanya akan memuat berkas yang sama dua kali.
 */
import { LOGO_MAX_WIDTH, LOGO_MAX_HEIGHT } from './useMapPngHeader.js';

/** Logo yang sudah dimuat, dikunci per URL. */
export const logoCache = new Map();

/**
 * Muat logo untuk digambar di header lembar.
 *
 * Gambar dari domain lain hanya boleh digambar ke kanvas kalau server-nya
 * mengirim header CORS. Kalau tidak, satu piksel pun gambarnya akan membuat
 * seluruh kanvas tercemar dan toBlob gagal dengan SecurityError, sehingga
 * seluruh PNG gagal dibuat gara-gara satu logo. Karena itu logo yang gagal
 * dimuat dilewati saja, dan hasilnya tetap PNG tanpa logo.
 *
 * @param {string|null|undefined} url
 * @returns {Promise<HTMLImageElement|null>}
 */
export function loadLogo(url) {
  if (!url) {
    return Promise.resolve(null);
  }

  if (!logoCache.has(url)) {
    logoCache.set(url, new Promise((resolve) => {
      const image = new Image();

      image.crossOrigin = 'anonymous';

      image.onload = () => resolve(image);
      // onerror termasuk logo yang tidak ditemukan dan penolakan CORS. Keduanya
      // berakhir sama saja: tanpa logo.
      image.onerror = () => resolve(null);

      image.src = url;
    }));
  }

  return logoCache.get(url);
}

/**
 * Ukuran logo di dalam kotak header, dengan rasio gambar tetap terjaga.
 *
 * Dipakai oleh pengukur tinggi dan penggambar, jadi keduanya tidak bisa
 * berbeda jawaban soal berapa ruang yang dimakan logo.
 *
 * @param {HTMLImageElement|null|undefined} image
 * @returns {{width:number, height:number}|null}
 */
export function logoBox(image) {
  if (!image || !(image.width > 0) || !(image.height > 0)) {
    return null;
  }

  const ratio = Math.min(LOGO_MAX_WIDTH / image.width, LOGO_MAX_HEIGHT / image.height);

  return {
    width: Math.max(1, Math.round(image.width * ratio)),
    height: Math.max(1, Math.round(image.height * ratio)),
  };
}
