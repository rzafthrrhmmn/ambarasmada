import maplibreWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import { config } from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

/**
 * Satu-satunya titik masuk untuk MapLibre GL.
 *
 * MapLibre v6 memuat worker-nya lewat `new URL('./maplibre-gl-worker.mjs',
 * import.meta.url)`. Setelah Vite mem-bundle `maplibre-gl.mjs`, `import.meta.url`
 * menunjuk ke file hasil build, sehingga worker dicari di `/build/assets/` dan
 * selalu 404 karena worker tidak ikut ter-bundle. Akibatnya peta tidak pernah
 * selesai render.
 *
 * `?worker&url` membuat Vite membangun worker sebagai chunk tersendiri (beserta
 * dependensinya) dan memberi URL-nya, jadi worker selalu ada di folder build.
 * URL tersebut same-origin, sehingga MapLibre instantiate worker secara langsung
 * tanpa blob URL.
 *
 * Stylesheet MapLibre ikut diimpor di sini karena tanpa itu kontainer peta dan
 * seluruh kontrolnya (zoom, kompas, legenda, atribusi) tidak bergaya dan peta
 * tampak sebagai kotak kosong. Halaman Peta mengimpor modul ini secara dinamis,
 * sehingga mengimpor CSS di sini menutup kedua halaman peta sekaligus.
 */
config.WORKER_URL = maplibreWorkerUrl;

/**
 * Server glyph untuk layer symbol (label kontur dan label kabupaten).
 *
 * Style yang punya layer `symbol` dengan `text-field` wajib mendeklarasikan
 * `glyphs`. Tanpa itu MapLibre tidak bisa menyusun shader teks: error-nya
 *uncaught, render loop berhenti, dan kanvas tetap abu-abu meski peta,
 * kontur, dan batas kabupaten sudah termuat.
 *
 * Host ini melayani tepat nama fontstack yang dipakai kedua halaman peta,
 * yaitu "Open Sans Regular" dan "Open Sans Bold". Host demo MapLibre sendiri
 * (demotiles.maplibre.org) tidak punya kedua fontstack itu dan menjawab 404.
 */
export const GLYPHS_URL = 'https://fonts.openmaptiles.org/{fontstack}/{range}.pbf';

export * from 'maplibre-gl';
