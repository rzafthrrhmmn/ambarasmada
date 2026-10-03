/**
 * Definisi basemap untuk kedua halaman peta.
 *
 * Modul ini sengaja terpisah dari `maplibre.js`. Halaman peta mengimpor MapLibre
 * secara dinamis supaya pustaka 1 MB itu tidak masuk bundel awal, sementara
 * definisi basemap dan teks atribusinya dibutuhkan sinkron oleh template di
 * <script setup>. Berkas modul ringan inilah yang diimpor statis.
 *
 * Atribusi diletakkan di satu tempat karena teks lisensi tidak boleh berbeda
 * antar halaman. OpenTopoMap memakai CC-BY-SA, yang mewajibkan atribusi yang
 * menyebutkan sumber data sekaligus lisensinya. Versi sebelumnya hanya menulis
 * "© OpenTopoMap" di kedua halaman, jadi syarat lisensi tidak pernah dipenuhi
 * meskipun tile-nya tampil.
 */

/**
 * Atribusi OpenStreetMap.
 *
 * Tile OSM merupakan karya kontributornya, bukan milik proyek OSM, jadi
 * "contributors" adalah bagian yang wajib muncul. Tile policy juga melarang
 * unduhan massal, dan paket offline di aplikasi ini memang hanya mengambil
 * tile kontur milik sendiri dari PMTiles, bukan tile OSM.
 */
export const OSM_ATTRIBUTION = '© OpenStreetMap contributors';

/**
 * Atribusi OpenTopoMap, ditulis persis seperti diminta di halaman "Verwendung"
 * miliknya.
 *
 * Sumbernya bukan cuma OpenTopoMap: tile-nya dirakit dari data OSM dan SRTM,
 * sedangkan kartu gambarnya tetap milik OpenTopoMap. Menyebutkan satu nama saja
 * tidak memenuhi syarat CC-BY-SA.
 */
export const OPENTOPOMAP_ATTRIBUTION =
  'Kartendaten: © OpenStreetMap-Mitwirkende, SRTM | Kartendarstellung: © OpenTopoMap (CC-BY-SA)';

export const ESRI_ATTRIBUTION = '© Esri';

export const STADIA_ATTRIBUTION = '© Stadia Maps';

/**
 * Keempat basemap yang bisa dipilih.
 *
 * Host tile OpenTopoMap yang dipakai adalah bentuk tunggal yang sah. Bentuk
 * jamak `tiles.opentopomap.org` menyajikan sertifikat TLS yang tidak cocok dengan
 * nama hostnya sehingga browser menolak koneksinya, dan bentuk itu tidak boleh
 * dipakai. Bentuk `{a|b|c}.tile.opentopomap.org` membagi beban ke tiga host,
 * tetapi setiap subdomainnya harus diizinkan satu per satu di CSP
 * `connect-src`, jadi tidak dipakai di sini.
 */
export const BASEMAPS = {
  osm: {
    type: 'raster',
    tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
    tileSize: 256,
    attribution: OSM_ATTRIBUTION,
  },
  satellite: {
    type: 'raster',
    tiles: ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'],
    tileSize: 256,
    attribution: ESRI_ATTRIBUTION,
  },
  terrain: {
    type: 'raster',
    tiles: ['https://tile.opentopomap.org/{z}/{x}/{y}.png'],
    tileSize: 256,
    attribution: OPENTOPOMAP_ATTRIBUTION,
  },
  dark: {
    type: 'raster',
    tiles: ['https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png'],
    tileSize: 256,
    attribution: STADIA_ATTRIBUTION,
  },
};

/**
 * Basemap untuk halaman cetak dan halaman peta offline.
 *
 * Keduanya selalu memakai OSM dan tidak mengikuti pilihan basemap pengguna, jadi
 * mereka memakai satu definisi yang sama. Ini bukan pilihan gratuit: keduanya
 * dibangun ulang sebagai dokumen HTML mandiri, bukan sebagai tampilan peta yang
 * sedang berjalan.
 */
export const PRINT_BASEMAP = BASEMAPS.osm;

/** Label basemap yang tampil di pilihan dropdown. */
export const BASEMAP_LABELS = {
  osm: 'OpenStreetMap',
  satellite: 'Satelit (Esri)',
  terrain: 'Terrain (OpenTopoMap)',
  dark: 'Dark (Stadia Maps)',
};

/**
 * Atribusi untuk satu basemap.
 *
 * Fungsi ini memakai nilai pengganti kalau kuncinya tidak dikenal, supaya
 * halaman yang mengirim basemap tak terduga tetap menampilkan kredit, bukan
 * string kosong yang tidak terlihat.
 */
export function basemapAttribution(key) {
  return BASEMAPS[key]?.attribution ?? OSM_ATTRIBUTION;
}
