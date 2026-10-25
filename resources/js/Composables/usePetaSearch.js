/**
 * Logika pencarian untuk halaman Peta dengan Pencarian.
 *
 * Digunakan oleh SearchPanel.vue dan MapDenganPencarian.vue.
 * Semua state pencarian (query, hasil, fokus, urutan) dikumpulkan di sini
 * agar tidak tersebar di dua komponen yang berbeda.
 */
import { ref } from 'vue';

export function usePetaSearch() {
  const searchQuery = ref('');
  const searchFocused = ref(false);
  const searchResults = ref([]);

  // State pencarian kabupaten / kecamatan dari data props (bukan Nominatim).
  const kabupatens = ref([]);
  const selectedKabupaten = ref('');
  const selectedKecamatan = ref('');

  // Urutan penanda pencarian, dipakai searchPlace() supaya jawaban yang sudah
  // basi tidak menimpa jawaban pencarian yang lebih baru.
  let searchSequence = 0;

  /**
   * Cari tempat atau koordinat (lat, lng) lewat Nominatim OpenStreetMap.
   */
  async function searchPlace() {
    if (!searchQuery.value.trim()) return;
    searchFocused.value = false;

    // Periksa apakah query adalah koordinat
    const coordMatch = searchQuery.value.match(/^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/);
    if (coordMatch) {
      const lat = parseFloat(coordMatch[1]);
      const lng = parseFloat(coordMatch[2]);
      return { type: 'coordinates', lat, lng };
    }

    // Cari via Nominatim
    const nomorPencarian = ++searchSequence;

    try {
      searchResults.value = [];
      const response = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=5&countrycodes=id&accept-language=id`,
      );
      const data = await response.json();

      if (nomorPencarian !== searchSequence) {
        return { type: 'stale' };
      }

      searchResults.value = data.map((item) => ({
        place_id: item.place_id,
        display_name: item.display_name,
        lat: parseFloat(item.lat),
        lon: parseFloat(item.lon),
      }));

      if (searchResults.value.length === 0) {
        return { type: 'notFound' };
      }

      return { type: 'results', results: searchResults.value };
    } catch (error) {
      if (nomorPencarian !== searchSequence) {
        return { type: 'stale' };
      }

      console.error('Search error:', error);
      return { type: 'error', error };
    }
  }

  /**
   * Pilih hasil pencarian dan kembalikan koordinat.
   */
  function selectSearchResult(result) {
    return {
      lat: result.lat,
      lon: result.lon,
      label: result.display_name || result.label || '',
    };
  }

  /**
   * Bersihkan pencarian.
   */
  function clearSearch() {
    searchQuery.value = '';
    searchResults.value = [];
    searchFocused.value = false;
  }

  /* ------------------------------------------------------------------ */
  /* Pencarian kabupaten / kecamatan dari data props                    */
  /* ------------------------------------------------------------------ */

  /**
   * Set daftar kabupaten yang diterima dari props.
   * @param {Array} kabupatens
   */
  function setKabupatens(kabupatensList) {
    kabupatens.value = Array.isArray(kabupatensList) ? kabupatensList : [];
    selectedKabupaten.value = '';
    selectedKecamatan.value = '';
  }

  /**
   * Set kabupaten yang dipilih dan reset kecamatan.
   * @param {string|number} id
   */
  function setSelectedKabupaten(id) {
    selectedKabupaten.value = id == null ? '' : String(id);
    selectedKecamatan.value = '';
  }

  /**
   * Set kecamatan yang dipilih.
   * @param {string|number} id
   */
  function setSelectedKecamatan(id) {
    selectedKecamatan.value = id == null ? '' : String(id);
  }

  /**
   * Kembalikan daftar kecamatan milik kabupaten.
   * @param {string|number} kabupatenId
   * @returns {Array}
   */
  function getKecamatanOptions(kabupatenId) {
    const kab = kabupatens.value.find((k) => String(k.id) === String(kabupatenId));
    if (!kab) return [];
    return Array.isArray(kab.kecamatans) ? kab.kecamatans : [];
  }

  /**
   * Kembalikan data kabupaten berdasarkan id.
   * @param {string|number} kabupatenId
   * @returns {Object|null}
   */
  function getSelectedKabData(kabupatenId) {
    return kabupatens.value.find((k) => String(k.id) === String(kabupatenId)) ?? null;
  }

  /**
   * Kembalikan data kecamatan berdasarkan id di seluruh kabupaten.
   * @param {string|number} kecamatanId
   * @returns {Object|null}
   */
  function getSelectedKecData(kecamatanId) {
    for (const kab of kabupatens.value) {
      const kec = (kab.kecamatans ?? []).find((k) => String(k.id) === String(kecamatanId));
      if (kec) return kec;
    }
    return null;
  }

  /**
   * Kembalikan informasi wilayah dari kabupaten dan (opsional) kecamatan.
   * Kecamatan tanpa bbox fallback ke bbox kabupaten.
   * @param {Object|null} kabupaten
   * @param {Object|null} kecamatan
   * @returns {{name:string,label:string,kode:string,bbox:Array|null,namaKab:string,namaKec:string}}
   */
  function getWilayahInfo(kabupaten, kecamatan) {
    const namaKab = kabupaten?.name ?? '';
    const namaKec = kecamatan?.name ?? '';
    const kode = kecamatan?.kode ?? kabupaten?.kode ?? '';
    const bbox = kecamatan?.bbox ?? kabupaten?.bbox ?? null;
    const name = kecamatan ? `${namaKec}, ${namaKab}` : namaKab;
    const label = kecamatan ? `${namaKec} (${namaKab})` : namaKab;
    return { name, label, kode, bbox, namaKab, namaKec };
  }

  /**
   * Kembalikan wilayah yang sedang dipilih.
   * @returns {Object|null}
   */
  function getSelectedWilayah() {
    const kab = kabupatens.value.find((k) => String(k.id) === String(selectedKabupaten.value));
    if (!kab) return null;
    const kec = selectedKecamatan.value
      ? (kab.kecamatans ?? []).find((k) => String(k.id) === String(selectedKecamatan.value))
      : null;
    return getWilayahInfo(kab, kec);
  }

  /**
   * Kembalikan bbox wilayah yang sedang dipilih.
   * @returns {Array|null}
   */
  function getSelectedWilayahBBox() {
    const wilayah = getSelectedWilayah();
    return wilayah?.bbox ?? null;
  }

  /**
   * Reset semua pilihan kabupaten / kecamatan.
   */
  function clearSelection() {
    selectedKabupaten.value = '';
    selectedKecamatan.value = '';
  }

  return {
    searchQuery,
    searchFocused,
    searchResults,
    searchPlace,
    selectSearchResult,
    clearSearch,
    kabupatens,
    selectedKabupaten,
    selectedKecamatan,
    setKabupatens,
    setSelectedKabupaten,
    setSelectedKecamatan,
    getKecamatanOptions,
    getSelectedKabData,
    getSelectedKecData,
    getWilayahInfo,
    getSelectedWilayah,
    getSelectedWilayahBBox,
    clearSelection,
  };
}
