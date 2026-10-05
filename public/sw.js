// Naikkan setiap kali format respons yang disimpan berubah. Tile kini disimpan
// tanpa header Content-Encoding dan halaman peta offline punya cache sendiri, jadi
// cache versi sebelumnya tidak boleh dipakai ulang. v1.5.1 menambahkan lambang
// urutan organisasi Kepramukaan ke precache supaya header lembar PNG tetap ada
// saat peta diunduh tanpa jaringan.
const CACHE_VERSION = 'v1.5.1';
const CACHE_NAME = 'jaya-jaya-jaya-' + CACHE_VERSION;
const ASSETS_CACHE = 'jaya-jaya-jaya-assets-' + CACHE_VERSION;
const TILES_CACHE = 'jaya-jaya-jaya-tiles-' + CACHE_VERSION;

/**
 * Nomor protokol pesan halaman <-> service worker.
 *
 * Service worker yang sudah terpasang tidak bisa diganti seketika setelah deploy:
 * browser memakai salinan yang sedang aktif sampai install dan activate selesai.
 * Ketika protokol halaman dan worker tidak sama, halaman akan salah membaca
 * balasannya. Gejalanya persis seperti yang pernah terjadi: worker versi lama
 * mengirim DOWNLOAD_COMPLETE tanpa zoomMin/zoomMax sehingga halaman menampilkan
 * "0 tile berhasil (zoom undefined-undefined)" untuk unduhan yang gagal diam-diam.
 * Karena itu setiap pesan membawa nomor ini dan halaman menolak membacanya kalau
 * nomornya tidak cocok, lalu menyuruh pengguna memuat ulang.
 */
const SW_PROTOCOL = 2;

const STATIC_ASSETS = [
    '/',
    '/manifest.webmanifest',
    '/images/icons/favicon.ico',
    '/images/icons/favicon-16.png',
    '/images/icons/favicon-32.png',
    '/images/icons/favicon-48.png',
    '/images/icons/apple-touch-icon.png',
    '/images/icons/icon-192.png',
    '/images/icons/icon-512.png',
    '/images/icons/maskable-192.png',
    '/images/icons/maskable-512.png',
    '/images/Logo_Ambalan.png',
    '/images/Logo_Urutan_Organiasasi_Kepramukaan.png',
    '/robots.txt',
];

const GEOJSON_ASSETS = [
    '/storage/maps/batas_kabupaten_sulsel.geojson',
    '/storage/maps/batas_kecamatan_sulsel.geojson',
];

const MAP_LIBRARIES = [
    { pattern: /\/assets\/.*maplibre.*\.js$/, fallback: 'network-first' },
    { pattern: /\/assets\/.*pmtiles.*\.js$/, fallback: 'network-first' },
    { pattern: /\/assets\/.*maplibre.*\.css$/, fallback: 'network-first' },
];

// External libraries to cache for offline use
const EXTERNAL_LIBS = [
    'https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js',
    'https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css',
    // Entri pmtiles dari CDN dihapus. Semua versi pmtiles yang pernah disematkan
    // di sini dan di offline.html menjawab 404 di unpkg
    // (https://unpkg.com/pmtiles@2.11.0/dist/pmtiles.js -> 404), sehingga
    // precache hanya menambah error CORS di console: browser melaporkan 404
    // lintas origin tanpa header Access-Control-Allow-Origin sebagai kegagalan
    // CORS. Aplikasi sendiri tidak pernah memakai URL tersebut; pmtiles ikut
    // ter-bundle dari npm bersama MapLibre.
];

const OFFLINE_FALLBACK = '/offline.html';

// Pola URL tile peta offline yang dilayani dari IndexedDB. Dipakai juga oleh
// handleOfflineTileRequest(), jadi keduanya tidak boleh berbeda.
const OFFLINE_TILE_PATTERN = /^\/offline-tiles\/(\d+)\/(\d+)\/(\d+)\.pbf$/;

/**
 * Simpan daftar URL ke cache tanpa membiarkan satu kegagalan membatalkan
 * seluruhnya.
 *
 * Cache.addAll bersifat atomik: begitu satu URL gagal, promise-nya menolak dan
 * karena pemanggilnya ada di dalam event.waitUntil(), instalasi service worker
 * ikut gagal. Efeknya service worker versi baru tidak pernah aktif, versi lama
 * terus mengendalikan halaman, dan tidak ada pembaruan yang bisa sampai ke
 * pengguna.
 *
 * Kasus yang pernah terjadi: /robots.txt tidak dipetakan di routes vercel.json
 * sehingga jatuh ke catch-all Laravel dan menjawab 404, dan satu ikon yang
 * hilang ikut menggagalkan seluruh precache.
 *
 * Karena itu tiap URL dicoba sendiri-sendiri lewat fetch, dan aset yang gagal
 * diambil hanya dilewati.
 */
async function precache(cache, urls) {
    await Promise.all(
        urls.map(async (url) => {
            try {
                // cache: 'reload' agar salinan yang disimpan benar-benar baru,
                // bukan versi basi dari HTTP cache browser.
                const response = await fetch(url, { cache: 'reload' });
                if (response.ok) {
                    await cache.put(url, response.clone());
                }
            } catch (error) {
                // Aset opsional yang gagal diambil tidak boleh menggagalkan
                // instalasi service worker.
            }
        })
    );
}

// Shell aplikasi: halaman dasar yang selalu dicache saat instalasi agar
// aplikasi tetap punya entry point ketika jaringan mati total.
const APP_SHELL = '/';

// Cache khusus respons halaman Inertia. Dihapus saat logout (lihat
// purgeUserScopedCaches) karena props Inertia memuat data per-pengguna.
const INERTIA_CACHE = 'jaya-jaya-jaya-inertia-' + CACHE_VERSION;

// Halaman peta offline yang dihasilkan generateOfflineMapHTML(). Wajib
// bertahan dari garbage collection activate, kalau tidak setiap pembaruan
// service worker menghapus peta offline yang baru saja pengguna unduh.
const OFFLINE_HTML_CACHE = 'jaya-jaya-jaya-offline-html';

// Path tempat halaman peta offline hasil unduhan disimpan.
const OFFLINE_MAP_HTML = '/offline-map.html';

// Versi generator halaman peta offline. Dinaikkan setiap kali isi halaman
// berubah, supaya activate bisa membuang peta lama yang isinya sudah usang.
// v2: histogram tidak lagi memakai angka elevasi karangan.
// v3: halaman peta offline menggambar batas kecamatan dan menyorot kecamatan yang diunduh.
// v4: sumber OSM pada halaman offline punya atribusi, jadi paket lama tanpa kredit dibuang.
const OFFLINE_MAP_GENERATOR = 'v4';

self.addEventListener('install', (event) => {
    event.waitUntil(
        Promise.all([
            caches.open(CACHE_NAME).then((cache) => precache(cache, STATIC_ASSETS)),
            caches.open(ASSETS_CACHE).then((cache) => precache(cache, STATIC_ASSETS)),
            caches.open(CACHE_NAME).then((cache) =>
                Promise.all(
                    GEOJSON_ASSETS.map((url) =>
                        fetch(url).then((response) => {
                            if (response.ok) return cache.put(url, response.clone());
                        }).catch(() => {})
                    )
                )
            ),
            // Cache external map libraries for offline use
            caches.open(ASSETS_CACHE).then((cache) =>
                Promise.all(
                    EXTERNAL_LIBS.map((url) =>
                        fetch(url).then((response) => {
                            if (response.ok) return cache.put(url, response.clone());
                        }).catch(() => {})
                    )
                )
            ),
            // Shell aplikasi sebagai entry point saat offline.
            caches.open(CACHE_NAME).then((cache) =>
                fetch(APP_SHELL).then((response) => {
                    if (response.ok) return cache.put(APP_SHELL, response.clone());
                }).catch(() => {})
            ),
        ])
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        Promise.all([
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((name) => {
                        const keep = [
                            CACHE_NAME,
                            ASSETS_CACHE,
                            TILES_CACHE,
                            INERTIA_CACHE,
                            OFFLINE_HTML_CACHE,
                        ];
                        if (!keep.includes(name)) {
                            return caches.delete(name);
                        }
                    })
                );
            }),
            dropStaleOfflineMap(),
        ])
    );
    self.clients.claim();
});

/**
 * Buang halaman peta offline yang dibuat generator versi lama.
 *
 * Cache OFFLINE_HTML_CACHE sengaja tidak ikut CACHE_VERSION supaya peta offline
 * milik pengguna tidak hilang setiap kali ada pembaruan. Konsekuensinya, peta
 * yang sudah diunduh tidak pernah diperbarui sendiri, dan peta versi lama
 * masih memuat angka elevasi yang dulu dikarang di service worker. Meteran ini
 * memungkinkan peta itu dibuang tanpa ikut membuang cache paved yang lain.
 *
 * Yang dibuang hanya HTML-nya. Tile di IndexedDB tetap ada, jadi pengguna bisa
 * membuat ulang petanya tanpa mengunduh ulang data.
 */
async function dropStaleOfflineMap() {
    const cache = await caches.open(OFFLINE_HTML_CACHE);
    const stored = await cache.match(OFFLINE_MAP_HTML);
    if (!stored) return;

    let html;
    try {
        html = await stored.text();
    } catch {
        return;
    }

    if (html.includes(`<meta name="offline-map-generator" content="${OFFLINE_MAP_GENERATOR}">`)) {
        return;
    }

    await cache.delete(OFFLINE_MAP_HTML);
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Navigasi: coba jaringan dulu, lalu shell yang dicache, lalu halaman offline.
    if (request.mode === 'navigate') {
        event.respondWith(handleNavigate(request));
        return;
    }

    // Halaman Inertia (XHR): stale-while-revalidate supaya halaman tetap
    // tampil dari cache saat sinyal hilang, lalu diperbarui di latar belakang.
    // Hanya GET: request Inertia non-GET adalah submit form, dan cache
    // responsnya akan mengembalikan halaman basi alih-alih hasil terbaru.
    if (request.headers.get('X-Inertia') === 'true' && request.method === 'GET') {
        event.respondWith(handleInertia(request));
        return;
    }

    const url = new URL(request.url);

    // Handle external map library requests (unpkg.com) for offline use
    if (url.origin === 'https://unpkg.com' && EXTERNAL_LIBS.includes(request.url)) {
        event.respondWith(cacheFirstThenNetwork(request));
        return;
    }

    if (request.method !== 'GET' || url.origin !== location.origin) {
        return;
    }

    // Tile peta yang sudah diunduh ke IndexedDB.
    //
    // Tile dilayani lewat path HTTP biasa, bukan skema kustom pmtiles://.
    // Service worker hanya bisa diandalkan untuk mencegat request same-origin
    // HTTP, dan TileJSON juga tidak bisa dilayani: pmtiles://<arsip> tidak
    // punya{z}/{x}/{y} sehingga tidak cocok dengan pola di bawah.
    if (OFFLINE_TILE_PATTERN.test(url.pathname)) {
        event.respondWith(handleOfflineTileRequest(request));
        return;
    }

    const isMapLibrary = MAP_LIBRARIES.some((ml) => ml.pattern.test(url.pathname));

    if (isMapLibrary) {
        event.respondWith(networkFirstThenCache(request).catch(() => caches.match(request)));
        return;
    }

    if (request.url.includes('/storage/maps/')) {
        event.respondWith(cacheFirstThenNetwork(request));
        return;
    }

    event.respondWith(
        cacheFirstThenNetwork(request).then((response) => {
            if (response && response.ok) {
                return response;
            }
            return fetch(request);
        }).catch(() => fetch(request)).catch(() => {
            if (request.headers.get('accept')?.includes('text/html')) {
                return caches.match(OFFLINE_FALLBACK);
            }
            return new Response(null, { status: 502 });
        })
    );
});

/**
 * Navigasi: jaringan dulu, lalu shell yang dicache, lalu /offline.html.
 *
 * Shell yang dicache adalah halaman tamu, jadi bukan pengganti halaman
 *enggota. Gunanya hanya memberi entry point agar aplikasi tidak menampilkan
 * error browser; OfflineBanner memberi tahu pengguna sedang offline.
 */
async function handleNavigate(request) {
    // Halaman peta offline hanya ada di Cache Storage, tidak pernah di origin:
    // /offline-map.html akan jatuh ke catch-all Laravel dan membalas 404, jadi
    // harus dilayani dari cache sebelum jaringan dicoba.
    if (new URL(request.url).pathname === OFFLINE_MAP_HTML) {
        const generated = await caches.match(OFFLINE_MAP_HTML);

        if (generated) return generated;

        return new Response(
            '<!doctype html><meta charset="utf-8"><title>Peta Offline</title>' +
                '<body style="background:#263D26;color:#f0ead8;font-family:sans-serif;padding:2rem">' +
                '<h1 style="color:#EDD330">Peta Offline Belum Disimpan</h1>' +
                '<p>Unduh peta offline terlebih dahulu dari halaman Peta.</p></body>',
            { status: 404, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
    }

    try {
        return await fetch(request);
    } catch {
        const cache = await caches.open(CACHE_NAME);
        const shell = await cache.match(APP_SHELL);

        if (shell) return shell;

        const fallback = await cache.match(OFFLINE_FALLBACK);
        if (fallback) return fallback;

        return new Response(
            '<!doctype html><meta charset="utf-8"><title>Offline</title>' +
                '<body style="background:#263D26;color:#f0ead8;font-family:sans-serif;padding:2rem">' +
                '<h1 style="color:#EDD330">Anda Sedang Offline</h1>' +
                '<p>Koneksi internet tidak tersedia.</p></body>',
            { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
    }
}

/**
 * Respons halaman Inertia: sajikan cache bila ada, perbarui di latar belakang.
 *
 * Hanya request GET yang dicache, dan hanya yang sukses. Cache ini dihapus
 * saat logout agar props milik pengguna sebelumnya tidak tampil lagi di
 * perangkat yang sama.
 */
async function handleInertia(request) {
    const cache = await caches.open(INERTIA_CACHE);

    try {
        const networkResponse = await fetch(request);
        if (networkResponse && networkResponse.status === 200) {
            cache.put(request, networkResponse.clone()).catch(() => {});
        }
        return networkResponse;
    } catch {
        const cached = await cache.match(request);
        if (cached) return cached;

        return new Response(
            JSON.stringify({ error: 'offline' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}

async function cacheFirstThenNetwork(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);

    if (cachedResponse) {
        Promise.resolve().then(() => updateCache(request));
        return cachedResponse;
    }

    const networkResponse = await fetch(request);
    if (networkResponse && (networkResponse.status === 200 || networkResponse.type === 'opaque')) {
        await cache.put(request, networkResponse.clone());
    }
    return networkResponse;
}

async function networkFirstThenCache(request) {
    const cache = await caches.open(ASSETS_CACHE);
    try {
        const networkResponse = await fetch(request);
        if (networkResponse && (networkResponse.status === 200 || networkResponse.type === 'opaque')) {
            await cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch {
        const cachedResponse = await cache.match(request);
        if (cachedResponse) return cachedResponse;
        return new Response(null, { status: 502 });
    }
}

async function updateCache(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse && (networkResponse.status === 200 || networkResponse.type === 'opaque')) {
            const cache = await caches.open(CACHE_NAME);
            await cache.put(request, networkResponse.clone());
        }
    } catch (error) {
        // Silent fail
    }
}

// Handle pmtiles:// protocol requests for offline map tiles
async function handleOfflineTileRequest(request) {
    const url = new URL(request.url);
    const match = url.pathname.match(OFFLINE_TILE_PATTERN);

    if (!match) {
        return new Response(null, { status: 404, statusText: 'Invalid offline tile URL format' });
    }

    const [, z, x, y] = match;
    const zoom = parseInt(z, 10);
    const tileX = parseInt(x, 10);
    const tileY = parseInt(y, 10);
    
    // Open IndexedDB and retrieve tile
    const db = await openTilesDB();
    
    try {
        const tile = await getTileFromDB(db, zoom, tileX, tileY);
        
        if (tile && tile.data) {
            // Data tile disimpan dalam bentuk yang sudah didekompresi, jadi
            // header Content-Encoding: gzip akan membuat MapLibre gagal
            // mendecode MVT dan layer tidak pernah tergambar.
            return new Response(tile.data, {
                status: 200,
                headers: {
                    'Content-Type': 'application/vnd.mapbox-vector-tile',
                    'Cache-Control': 'public, max-age=31536000, immutable',
                },
            });
        }
        
        // Tile not found in offline DB
        return new Response(null, { status: 404, statusText: 'Tile not available offline' });
    } finally {
        await db.close();
    }
}

// Open IndexedDB for offline tiles
function openTilesDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('offline-tiles', 1);
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('tiles')) {
                const store = db.createObjectStore('tiles', { keyPath: 'id' });
                store.createIndex('zxy', 'zxy', { unique: true });
            }
        };
        request.onsuccess = (event) => resolve(event.target.result);
        request.onerror = (e) => reject(e.target.error);
    });
}

// Get tile from IndexedDB
function getTileFromDB(db, z, x, y) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction('tiles', 'readonly');
        const store = tx.objectStore('tiles');
        const tileId = `${z}/${x}/${y}`;
        const request = store.get(tileId);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data?.type === 'DOWNLOAD_OFFLINE_TILES') {
        const { bbox, zoomMin, zoomMax, pmtilesUrl, geojsonUrl, layoutOptions, areaName, protocol, kecamatanGeojsonUrl, highlightKecId } = event.data;
        const port = event.ports[0];

        // Halaman yang lebih baru talking ke worker lama (atau sebaliknya) tidak
        // boleh dijawab dengan format yang salah. Bilas dengan pesan yang jelas
        // supaya pengguna tahu perlu memuat ulang, bukan melihat angka 0 tile.
        if (protocol !== undefined && protocol !== SW_PROTOCOL) {
            port?.postMessage({
                type: 'DOWNLOAD_ERROR',
                protocol: SW_PROTOCOL,
                error: `Halaman dan service worker memakai protokol berbeda (${protocol}/${SW_PROTOCOL}). Muat ulang halaman lalu ulangi unduhan.`,
            });
            return;
        }

        event.waitUntil(handleOfflineDownload(port, bbox, zoomMin, zoomMax, pmtilesUrl, geojsonUrl, layoutOptions, areaName, kecamatanGeojsonUrl, highlightKecId));
    }
});

async function handleOfflineDownload(port, bbox, zoomMin, zoomMax, pmtilesUrl, geojsonUrl, layoutOptions, areaName, kecamatanGeojsonUrl, highlightKecId) {
    function send(message) {
        port.postMessage({ protocol: SW_PROTOCOL, ...message });
    }

    function sendProgress(status, downloaded, total) {
        send({ type: 'DOWNLOAD_PROGRESS', status, downloaded, total });
    }

    sendProgress('Menyiapkan arsip peta...', 0, 0);

    let db = null;

    try {
        const reader = await initPMTilesReader(pmtilesUrl);

        // Arsip pmtiles produksi hanya memuat z8-z12. Meminta level di luar
        // rentang itu hanya menambah tile yang pasti tidak ada, jadi rentang
        //effective dipotong ke rentang arsip.
        const effMin = Math.max(zoomMin, reader.minZoom);
        const effMax = Math.min(zoomMax, reader.maxZoom);

        if (effMin > effMax) {
            send({
                type: 'DOWNLOAD_ERROR',
                error: `Arsip peta hanya memuat level zoom ${reader.minZoom}-${reader.maxZoom}.`,
            });
            return;
        }

        if (effMin !== zoomMin || effMax !== zoomMax) {
            sendProgress(`Level zoom dibatasi ${effMin}-${effMax} sesuai arsip`, 0, 0);
        }

        const tileQueue = buildTileQueue(bbox, effMin, effMax);
        if (tileQueue.length === 0) {
            send({
                type: 'DOWNLOAD_ERROR',
                error: 'Wilayah terpilih tidak menghasilkan satu pun tile.',
            });
            return;
        }

        db = await openTilesDB();
        sendProgress('Mengunduh tile...', 0, tileQueue.length);

        let downloadedTiles = 0;
        let missingTiles = 0;
        let processed = 0;

        for (const tile of tileQueue) {
            try {
                const data = await reader.getTile(tile.z, tile.x, tile.y);
                if (data && data.byteLength) {
                    await storeTile(db, tile.z, tile.x, tile.y, data);
                    downloadedTiles++;
                } else {
                    missingTiles++;
                }
            } catch {
                missingTiles++;
            }

            processed++;
            if (processed % 25 === 0 || processed === tileQueue.length) {
                sendProgress('Mengunduh...', downloadedTiles, tileQueue.length);
            }
        }

        // Dulu setiap kegagalan ditelan dan halaman tetap melaporkan "Selesai!
        // 0 tile", sehingga penyebabnya tidak pernah terlihat oleh pengguna.
        if (downloadedTiles === 0) {
            send({
                type: 'DOWNLOAD_ERROR',
                error: `Tidak ada tile yang bisa diunduh dari ${tileQueue.length} tile yang diminta. Periksa koneksi dan alamat arsip peta.`,
            });
            return;
        }

        if (layoutOptions) {
            sendProgress('Membuat layout peta offline...', downloadedTiles, tileQueue.length);
            await generateOfflineMapHTML(db, bbox, effMin, effMax, geojsonUrl, layoutOptions, areaName, kecamatanGeojsonUrl, highlightKecId);
        }

        sendProgress('Selesai', downloadedTiles, tileQueue.length);
        send({
            type: 'DOWNLOAD_COMPLETE',
            downloaded: downloadedTiles,
            total: tileQueue.length,
            skipped: missingTiles,
            zoomMin: effMin,
            zoomMax: effMax,
        });
    } catch (error) {
        // Tanpa jalur ini, kegagalan di dalam service worker tidak pernah sampai
        // ke halaman dan tombol unduh menggantung selamanya.
        send({
            type: 'DOWNLOAD_ERROR',
            error: `Gagal mengunduh peta: ${error.message}`,
        });
    } finally {
        if (db) db.close();
    }
}

/**
 * Susun daftar tile Web Mercator yang menutupi bbox pada rentang zoom tertentu.
 * Rumus dan pembulatan di sini harus identik dengan estimasi di halaman
 * (estimatedTiles) supaya jumlah tile yang benar-benar diunduh sama dengan
 * angka "Estimasi tile" yang tampil di modal.
 */
function buildTileQueue(bbox, zoomMin, zoomMax) {
    const { west, east, south, north } = bbox;
    const queue = [];

    const latRadN = (north * Math.PI) / 180;
    const latRadS = (south * Math.PI) / 180;

    for (let z = zoomMin; z <= zoomMax; z++) {
        const scale = Math.pow(2, z);
        const xMin = Math.floor(((west + 180) / 360) * scale);
        const xMax = Math.ceil(((east + 180) / 360) * scale) - 1;
        const yMin = Math.ceil(((1 - Math.log(Math.tan(latRadN) + 1 / Math.cos(latRadN)) / Math.PI) / 2) * scale);
        const yMax = Math.floor(((1 - Math.log(Math.tan(latRadS) + 1 / Math.cos(latRadS)) / Math.PI) / 2) * scale);

        for (let x = xMin; x <= xMax; x++) {
            for (let y = yMin; y <= yMax; y++) {
                queue.push({ z, x, y });
            }
        }
    }

    return queue;
}

async function initPMTilesReader(pmtilesUrl) {
    // Header PMTiles v3 selalu 127 byte. Susunannya:
    //   0-6 magic, 7 versi, 8-15 root offset, 16-23 root length,
    //   24-31 metadata offset, 32-39 metadata length,
    //   40-47 leaf offset, 48-55 leaf length,
    //   56-63 tile data offset, 64-71 tile data length,
    //   72-79 jumlah tile yang dialamat, 80-87 jumlah entri,
    //   88-95 jumlah isi, 96 clustered, 97 kompresi internal,
    //   98 kompresi tile, 99 tipe tile, 100 min zoom, 101 max zoom,
    //   102-117 bounding box (int32 x 1e7), 118 center zoom,
    //   119-126 center lon/lat (int32 x 1e7).
    const headerResp = await fetch(pmtilesUrl, { headers: { Range: 'bytes=0-16383' } });

    if (!headerResp.ok) {
        throw new Error(`Arsip peta tidak dapat diakses (HTTP ${headerResp.status})`);
    }

    const headerData = await headerResp.arrayBuffer();

    const view = new DataView(headerData);
    if (view.getUint16(0, true) !== 0x4d50) {
        throw new Error('Berkas bukan arsip PMTiles');
    }

    const rootOffset = readUint64(view, 8);
    const rootLength = readUint64(view, 16);
    const leafOffset = readUint64(view, 40);
    const tileDataOffset = readUint64(view, 56);
    const minZoom = view.getUint8(100);
    const maxZoom = view.getUint8(101);
    const minLat = view.getInt32(106, true) / 1e7;
    const minLon = view.getInt32(102, true) / 1e7;
    const maxLat = view.getInt32(114, true) / 1e7;
    const maxLon = view.getInt32(110, true) / 1e7;
    const tileType = view.getUint8(99);
    const tileCompression = view.getUint8(98);
    const internalCompression = view.getUint8(97);

    if (!maxZoom || minZoom > maxZoom) {
        throw new Error('Header arsip peta tidak memuat rentang zoom yang sah');
    }

    async function fetchRange(offset, length) {
        const resp = await fetch(pmtilesUrl, {
            headers: { Range: `bytes=${offset}-${offset + length - 1}` },
        });
        if (!resp.ok) return null;
        return resp.arrayBuffer();
    }

    const rootData = await fetchRange(rootOffset, rootLength);
    if (!rootData) {
        throw new Error('Direktori root arsip peta tidak dapat dibaca');
    }

    const rootEntries = await parseDirectory(rootData, internalCompression);

    return {
        pmtilesUrl,
        tileDataOffset,
        minZoom,
        maxZoom,
        minLat,
        minLon,
        maxLat,
        maxLon,
        tileType,
        tileCompression,
        rootEntries,

        /**
         * Telusuri entri tile, descend ke direktori leaf bila direktori yang
         * sedang dicari hanya berisi pointer (runLength 0). Arsip besar punya
         * root directory > 16 KB sehingga sebagian tile tidak ada di root.
         */
        async findEntry(entries, tileId, depth) {
            const entry = findTile(entries, tileId);
            if (!entry) return null;

            // runLength > 0 berarti entri tile; runLength 0 berarti entri
            // direktori leaf dengan panjang di entry.length.
            if (entry.runLength > 0) return entry;

            if (depth >= 3) return null;

            const leafData = await fetchRange(leafOffset + entry.offset, entry.length);
            if (!leafData) return null;

            const leafEntries = await parseDirectory(leafData, internalCompression);
            return this.findEntry(leafEntries, tileId, depth + 1);
        },

        async getTile(z, x, y) {
            if (z < this.minZoom || z > this.maxZoom) return null;

            let tileId;
            try {
                tileId = zxyToTileId(z, x, y);
            } catch {
                return null;
            }

            const entry = await this.findEntry(this.rootEntries, tileId, 0);
            if (!entry) return null;

            const offset = this.tileDataOffset + entry.offset;
            const data = await fetchRange(offset, entry.length);
            if (!data) return null;

            if (this.tileCompression === 2) {
                // Tile disimpan sudah InflationStream-didekompresi, jadi tidak
                // perlu-header Content-Encoding saat dilayani lagi.
                return await inflateGzip(data);
            }

            if (this.tileCompression === 1) return data;

            return await inflateGzip(data);
        },
    };
}

async function inflateGzip(buffer) {
    const stream = new Response(buffer).body.pipeThrough(new DecompressionStream('gzip'));
    return await new Response(stream).arrayBuffer();
}

function readUint64(view, offset) {
    const low = view.getUint32(offset, true);
    const high = view.getUint32(offset + 4, true);
    return high * 0x100000000 + low;
}

function readInt64(view, offset) {
    const low = view.getUint32(offset, true);
    const high = view.getInt32(offset + 4, true);
    return high * 0x100000000 + low;
}

/**
 * PMTiles v3 mengurutkan tile dengan kurva Hilbert, bukan sekadar
 * menyisipkan bit x dan y berselang-seling.
 *
 * Versi lama memakai bit-interleave, sehingga zxyToTileId() menghasilkan
 * nomor yang tidak pernah ada di direktori arsip: findTile() mengembalikan
 * null untuk setiap tile, galat-nya ditelan di dalam loop, dan unduhan
 * selesai dengan "0 tile berhasil diunduh".
 *
 * Implementasi ini sama dengan pmtiles v3/v4 (lihat fungsi zxyToTileId di
 * paket pmtiles), termasuk rotasi kurva Hilbert di setiap tingkat zoom.
 */
function zxyToTileId(z, x, y) {
    if (z > 26) {
        throw new Error('Level zoom tile melebihi batas aman (26)');
    }
    if (x >= 1 << z || y >= 1 << z) {
        throw new Error('Koordinat tile di luar batas level zoom');
    }

    let acc = ((1 << z) * (1 << z) - 1) / 3;
    let a = z - 1;
    let [tx, ty] = [x, y];

    for (let s = 1 << a; s > 0; s >>= 1) {
        const rx = tx & s;
        const ry = ty & s;
        acc += ((3 * rx) ^ ry) * (1 << a);
        [tx, ty] = rotateHilbert(s, tx, ty, rx, ry);
        a--;
    }

    return acc;
}

function rotateHilbert(n, x, y, rx, ry) {
    if (ry === 0) {
        if (rx !== 0) return [n - 1 - y, n - 1 - x];
        return [y, x];
    }
    return [x, y];
}

/**
 * Baca direktori PMTiles. Kolomnya diserialisasi terpisah: jumlah entri,
 * delta tileId, runLength, panjang, lalu offset. Offset kolom pertama
 * disimpan relatif terhadap entri sebelumnya (nilai varint 0), sehingga harus
 * dihitung ulang secara berurutan.
 */
async function parseDirectory(data, compression) {
    let bytes = new Uint8Array(data);
    if (compression === 2) {
        bytes = new Uint8Array(await inflateGzip(bytes));
    }

    const entries = [];
    const posRef = { pos: 0 };
    const numEntries = readVarint(bytes, posRef);

    let lastId = 0;
    for (let i = 0; i < numEntries; i++) {
        lastId += readVarint(bytes, posRef);
        entries.push({ tileId: lastId, offset: 0, length: 0, runLength: 1 });
    }

    for (let i = 0; i < numEntries; i++) {
        entries[i].runLength = readVarint(bytes, posRef);
    }

    for (let i = 0; i < numEntries; i++) {
        entries[i].length = readVarint(bytes, posRef);
    }

    let lastEnd = 0;
    for (let i = 0; i < numEntries; i++) {
        const v = readVarint(bytes, posRef);
        entries[i].offset = v === 0 && i > 0 ? lastEnd : v - 1;
        lastEnd = entries[i].offset + entries[i].length;
    }

    return entries;
}

/**
 * Varint PMTiles bisa melebihi 32 bit, jadi bit digeser dengan perkalian dan
 * bukan operator << yang selalu bekerja pada 32 bit signed.
 */
function readVarint(bytes, posRef) {
    let val = 0;
    let shift = 0;

    for (let i = 0; i < 10; i++) {
        const b = bytes[posRef.pos++];
        val += (b & 0x7f) * 2 ** shift;
        if (b < 0x80) return val;
        shift += 7;
    }

    throw new Error('Varint PMTiles melebihi 10 byte');
}

function findTile(entries, tileId) {
    let lo = 0;
    let hi = entries.length - 1;

    while (lo <= hi) {
        const mid = (lo + hi) >> 1;
        const cmp = tileId - entries[mid].tileId;
        if (cmp > 0) lo = mid + 1;
        else if (cmp < 0) hi = mid - 1;
        else return entries[mid];
    }

    // Binary search berhenti dengan lo > hi. Entri di indeks hi masih mungkin
    // mencakup tile yang dicari lewat runLength (beberapa tile berurutan
    // memakai data yang sama), atau merupakan pointer direktori leaf.
    if (hi >= 0) {
        if (entries[hi].runLength === 0) return entries[hi];
        if (tileId - entries[hi].tileId < entries[hi].runLength) return entries[hi];
    }

    return null;
}

async function generateOfflineMapHTML(db, bbox, zoomMin, zoomMax, geojsonUrl, layoutOptions, areaName, kecamatanGeojsonUrl, highlightKecId) {
    const centerLon = (bbox.west + bbox.east) / 2;
    const centerLat = (bbox.south + bbox.north) / 2;
    const centerZoom = Math.floor((zoomMin + zoomMax) / 2);

    // Get contour elevation stats for histogram
    const elevStats = await getElevationStats(db);

    const html = generateMapHTML({
        centerLon,
        centerLat,
        centerZoom,
        zoomMin,
        zoomMax,
        bbox,
        areaName,
        geojsonUrl,
        layoutOptions,
        elevStats,
        tileCount: await getTileCount(db),
        // Batas kecamatan ikut dibawa ke halaman offline. URL-nya ditulis
        // sebagai literal JSON supaya karakter kutip di dalam string tidak
        // bisa menutup blok <script> lebih awal.
        kecamatanGeojsonUrl: kecamatanGeojsonUrl ? JSON.stringify(kecamatanGeojsonUrl) : null,
        highlightKecId: highlightKecId ? JSON.stringify(highlightKecId) : null,
    });

    // Store the HTML for offline access
    const htmlCache = await caches.open(OFFLINE_HTML_CACHE);
    const response = new Response(html, {
        headers: { 'Content-Type': 'text/html; charset=utf-8' }
    });
    await htmlCache.put(OFFLINE_MAP_HTML, response);
}

/**
 * Statistik elevasi dari tile yang tersimpan.
 *
 * Tile disimpan sebagai MVT mentah, jadi nilai ELEV di dalamnya tidak bisa
 * dibaca tanpa pustaka decoder vektor. Versi lama memakai angka acak supaya
 * panel histogram selalu terisi, dan angka acak itu muncul sebagai
 * "Elevasi: 1234-3012m" di halaman peta offline: angka yang terlihat sahih
 * padahal tidak pernah berasal dari data mana pun.
 *
 * Sekarang dikembalikan null kalau data nyata tidak bisa dibaca, dan panel
 * menampilkan "N/A". Angka yang tidak ada lebih jujur daripada angka palsu.
 */
async function getElevationStats(db) {
    return new Promise((resolve) => {
        const tx = db.transaction('tiles', 'readonly');
        const store = tx.objectStore('tiles');
        const request = store.getAll();

        request.onsuccess = () => {
            const elevations = [];

            for (const tile of request.result) {
                if (typeof tile?.data?.byteLength !== 'number' || tile.data.byteLength === 0) {
                    continue;
                }
                // Nilai ELEV hanya bisa diambil setelah MVT diurai, dan service
                // worker tidak punya decoder vektor. Lewati tile sepenuhnya.
            }

            if (elevations.length === 0) {
                resolve(null);
                return;
            }

            elevations.sort((a, b) => a - b);
            resolve({
                min: elevations[0],
                max: elevations[elevations.length - 1],
                mean: elevations.reduce((a, b) => a + b, 0) / elevations.length,
                median: elevations[Math.floor(elevations.length / 2)],
                histogram: generateHistogramData(elevations, 20),
            });
        };

        request.onerror = () => resolve(null);
    });
}

function generateHistogramData(values, bins) {
    const min = values[0];
    const max = values[values.length - 1];
    const binSize = (max - min) / bins;
    const counts = new Array(bins).fill(0);

    for (const v of values) {
        const idx = Math.min(bins - 1, Math.floor((v - min) / binSize));
        counts[idx]++;
    }

    return counts.map((count, i) => ({
        binStart: min + i * binSize,
        binEnd: min + (i + 1) * binSize,
        count,
    }));
}

async function getTileCount(db) {
    return new Promise((resolve) => {
        const tx = db.transaction('tiles', 'readonly');
        const store = tx.objectStore('tiles');
        const request = store.count();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => resolve(0);
    });
}

function generateMapHTML({ centerLon, centerLat, centerZoom, zoomMin, zoomMax, bbox, areaName, geojsonUrl, layoutOptions, elevStats, tileCount, kecamatanGeojsonUrl, highlightKecId }) {
    const scaleBarHtml = layoutOptions.scaleBar ? `
        <div id="scale-bar" class="map-control scale-bar" style="bottom: 20px; left: 20px;">
            <canvas id="scale-canvas" width="200" height="30"></canvas>
        </div>
    ` : '';

    const northArrowHtml = layoutOptions.northArrow ? `
        <div id="north-arrow" class="map-control north-arrow" style="top: 20px; right: 20px;">
            <svg width="48" height="48" viewBox="0 0 48 48">
                <circle cx="24" cy="24" r="20" fill="rgba(0,0,0,0.7)" stroke="#fff" stroke-width="2"/>
                <path d="M24 8 L18 24 L30 24 Z" fill="#fff"/>
                <path d="M24 8 L16 16 L32 16 Z" fill="rgba(255,255,255,0.7)"/>
                <text x="24" y="40" text-anchor="middle" fill="#fff" font-size="10" font-family="sans-serif">N</text>
            </svg>
        </div>
    ` : '';

    const legendHtml = layoutOptions.legend ? `
        <div id="legend" class="map-control legend" style="bottom: 20px; right: 20px; max-width: 200px;">
            <div class="legend-header">Legenda Kontur</div>
            <div class="legend-items">
                <div class="legend-item"><span class="legend-color" style="background: #8c510a;"></span> Kontur 50m</div>
                <div class="legend-item"><span class="legend-color" style="background: #a0522d;"></span> Kontur 100m</div>
                <div class="legend-item"><span class="legend-color" style="background: #cd853f;"></span> Kontur 500m</div>
                <div class="legend-item"><span class="legend-color" style="background: #8b4513;"></span> Kontur 1000m+</div>
                <div class="legend-item"><span class="legend-color" style="background: #2563eb;"></span> Batas Kabupaten</div>
            </div>
        </div>
    ` : '';

    // Panel histogram tetap muncul ketika dicentang, walau datanya tidak ada.
// Diam-diam membuangnya membuat centang terlihat tidak berpengaruh; menulis
// keterangan apa adanya jauh lebih jujur daripada menampilkan angka karangan.
const histogramHtml = !layoutOptions.histogram ? '' : (elevStats ? `
        <div id="histogram" class="map-control histogram" style="top: 80px; right: 20px; width: 220px; max-height: 300px;">
            <div class="histogram-header">Histogram Elevasi (${areaName})</div>
            <div class="histogram-stats">
                Min: ${Math.round(elevStats.min)}m | Max: ${Math.round(elevStats.max)}m | Mean: ${Math.round(elevStats.mean)}m
            </div>
            <canvas id="histogram-canvas" width="220" height="180"></canvas>
        </div>
    ` : `
        <div id="histogram" class="map-control histogram" style="top: 80px; right: 20px; width: 220px;">
            <div class="histogram-header">Histogram Elevasi (${areaName})</div>
            <div class="histogram-stats">
                Data elevasi tidak ikut dalam paket ini. Histogram baru bisa dihitung
                dari peta yang sudah diunduh, bukan dari daftar tile.
            </div>
        </div>
    `);

    const gridHtml = layoutOptions.grid ? `
        <div id="grid-coords" class="map-control grid-coords" style="bottom: 60px; left: 20px; font-size: 11px; background: rgba(0,0,0,0.7); color: #fff; padding: 5px 10px; border-radius: 4px; font-family: monospace;">
            Lon: <span id="grid-lon">${centerLon.toFixed(4)}</span>&deg; | Lat: <span id="grid-lat">${centerLat.toFixed(4)}</span>&deg;
        </div>
    ` : '';

    return `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="offline-map-generator" content="${OFFLINE_MAP_GENERATOR}">
    <title>Peta Offline - ${areaName}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #1a1a1a; color: #fff; overflow: hidden; }
        #map { width: 100vw; height: 100vh; }
        .map-control { position: absolute; z-index: 1000; background: rgba(0,0,0,0.8); border-radius: 8px; padding: 12px; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(4px); }
        .map-control h4 { margin-bottom: 8px; font-size: 13px; color: #fbbf24; }
        .legend-header { font-weight: 600; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .legend-items { display: flex; flex-direction: column; gap: 6px; }
        .legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; }
        .legend-color { width: 20px; height: 4px; border-radius: 2px; }
        .legend-color:last-child { height: 2px; border-style: dashed; }
        .histogram-header { font-weight: 600; margin-bottom: 4px; font-size: 12px; }
        .histogram-stats { font-size: 10px; color: #9ca3af; margin-bottom: 8px; }
        #histogram-canvas { background: rgba(255,255,255,0.05); border-radius: 4px; }
        .scale-bar { display: flex; flex-direction: column; gap: 4px; }
        #scale-canvas { border-radius: 4px; }
        .north-arrow svg { cursor: pointer; transition: transform 0.2s; }
        .north-arrow svg:hover { transform: scale(1.1); }
        .offline-badge { position: absolute; top: 10px; left: 10px; z-index: 1001; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #1f2937; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .info-panel { position: absolute; top: 10px; right: 10px; z-index: 1000; background: rgba(0,0,0,0.8); border-radius: 8px; padding: 12px; border: 1px solid rgba(255,255,255,0.1); font-size: 11px; min-width: 200px; }
        .info-row { display: flex; justify-content: space-between; margin: 4px 0; }
        .info-label { color: #9ca3af; }
        .info-value { color: #fff; font-weight: 500; }
        @media (max-width: 768px) {
            .map-control { padding: 8px; font-size: 11px; }
            #histogram { width: 180px; }
        }
    </style>
</head>
<body>
    <div class="offline-badge">Mode Offline</div>
    <div id="map"></div>

    ${scaleBarHtml}
    ${northArrowHtml}
    ${legendHtml}
    ${histogramHtml}
    ${gridHtml}

    <div class="info-panel">
        <div class="info-row"><span class="info-label">Area:</span> <span class="info-value">${areaName}</span></div>
        <div class="info-row"><span class="info-label">Tile:</span> <span class="info-value">${tileCount.toLocaleString()}</span></div>
        <div class="info-row"><span class="info-label">Zoom:</span> <span class="info-value">${zoomMin}-${zoomMax}</span></div>
        <div class="info-row"><span class="info-label">Elevasi:</span> <span class="info-value">${elevStats ? Math.round(elevStats.min)+'-'+Math.round(elevStats.max)+'m' : 'N/A'}</span></div>
    </div>

    <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
    <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
    <script>
        // Peta ini berdiri sendiri di luar bundle aplikasi, jadi MapLibre diambil
        // dari CDN. Versinya harus sama dengan entri EXTERNAL_LIBS, kalau tidak
        // build ini tidak ada di cache dan peta gagal dimuat saat benar-benar offline.
        //
        // Pustaka pmtiles sengaja tidak dipakai. Versi yang pernah disematkan di
        // sini (3.1.4) tidak ada di unpkg sehingga PMTiles undefined dan seluruh
        // script ini berhenti. Yang lebih penting, Protocol pmtiles membaca arsip
        // lewat HTTP Range ke URL arsip, sedangkan tile offline disimpan di
        // IndexedDB. Sumber tile memakai tiles: dengan template URL, jadi MapLibre
        // tidak perlu TileJSON dan tidak perlu pustaka tambahan apa pun.
        const map = new maplibregl.Map({
            container: 'map',
            style: {
                version: 8,
                sources: {
                    'kontur': {
                        type: 'vector',
                        tiles: ['/offline-tiles/{z}/{x}/{y}.pbf'],
                        minzoom: ${zoomMin},
                        maxzoom: ${zoomMax},
                        bounds: ${JSON.stringify(bbox)},
                    },
                    'batas': {
                        type: 'geojson',
                        data: '${geojsonUrl}',
                    },${kecamatanGeojsonUrl ? `
                    'kecamatan': {
                        type: 'geojson',
                        data: ${kecamatanGeojsonUrl},
                    },` : ''}
                    'osm': {
                        type: 'raster',
                        tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                        tileSize: 256,
                        // Atribusi wajib ada di property source. MapLibre
                        // menulisnya ke AttributionControl di atas kanvas, jadi
                        // tanpa property ini tile OSM tampil tanpa kredit sama
                        // sekali.
                        attribution: '© OpenStreetMap contributors',
                    }
                },
                layers: [
                    { id: 'osm', type: 'raster', source: 'osm' },
                    { id: 'kontur', type: 'line', source: 'kontur', 'source-layer': 'kontur',
                        layout: { 'line-join': 'round', 'line-cap': 'round' },
                        paint: {
                            'line-color': '#8c510a',
                            'line-width': ['case', ['==', ['%', ['get', 'ELEV'], 50], 0], 1.8, 0.8]
                        }
                    },${kecamatanGeojsonUrl ? `
                    { id: 'kecamatan', type: 'line', source: 'kecamatan',
                        paint: { 'line-color': '#0f766e', 'line-width': 0.6, 'line-opacity': 0.7 }
                    },${highlightKecId ? `
                    { id: 'kecamatan-highlight', type: 'fill', source: 'kecamatan',
                        filter: ['==', ['get', 'id_kec'], ${highlightKecId}],
                        paint: { 'fill-color': '#EDD330', 'fill-opacity': 0.25 }
                    },` : ''}` : ''}
                    { id: 'batas', type: 'line', source: 'batas',
                        paint: { 'line-color': '#2563eb', 'line-width': 1.5, 'line-dasharray': [2, 2] }
                    }
                ]
            },
            center: [${centerLon}, ${centerLat}],
            zoom: ${centerZoom},
            minZoom: ${zoomMin},
            maxZoom: ${zoomMax},
        });

        map.on('load', () => {
            // Initialize scale bar
            ${layoutOptions.scaleBar ? `
            const scaleCanvas = document.getElementById('scale-canvas');
            const scaleCtx = scaleCanvas.getContext('2d');
            const SCALE_STEPS = [1, 2, 5, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000, 50000, 100000, 200000, 500000];
            function updateScaleBar() {
                const metersPerPixel = 40075016.686 * Math.cos(map.getCenter().lat * Math.PI / 180) / Math.pow(2, map.getZoom()) / 256;
                if (!isFinite(metersPerPixel) || metersPerPixel <= 0) return;

                // Jarak dibulatkan ke angka "bulat" TERBESAR yang masih muat di
                // kanvas, jadi kandidat dibaca dari belakang. Versi lama memakai
                // find(m => m * metersPerPixel * 100 > 100) yang berarti "pilih
                // jarak terkecil yang lebih besar dari 1 meter di layar": hampir
                // selalu mengembalikan 1 m, sehingga bilahnya setebal 0,6 piksel
                // dan tidak pernah terlihat.
                const maxWidth = 140;
                let targetMeters = SCALE_STEPS[SCALE_STEPS.length - 1];
                for (let i = SCALE_STEPS.length - 1; i >= 0; i--) {
                    if (SCALE_STEPS[i] / metersPerPixel <= maxWidth) {
                        targetMeters = SCALE_STEPS[i];
                        break;
                    }
                }
                const px = targetMeters / metersPerPixel;

                scaleCtx.clearRect(0, 0, scaleCanvas.width, scaleCanvas.height);
                scaleCtx.fillStyle = '#fff';

                // Bilah berselang-seling, konvensi kartografi umum.
                const segments = 4;
                for (let i = 0; i < segments; i++) {
                    if (i % 2 === 0) scaleCtx.fillRect((px / segments) * i, 8, px / segments, 6);
                }
                scaleCtx.strokeStyle = '#fff';
                scaleCtx.lineWidth = 1;
                scaleCtx.strokeRect(0, 8, px, 6);

                scaleCtx.font = '10px sans-serif';
                scaleCtx.textAlign = 'left';
                scaleCtx.textBaseline = 'top';
                scaleCtx.fillStyle = '#fff';
                scaleCtx.fillText(targetMeters >= 1000 ? (targetMeters / 1000) + ' km' : targetMeters + ' m', px + 6, 7);
                scaleCtx.textAlign = 'right';
                scaleCtx.fillText('0', 0, 17);
            }
            map.on('move', updateScaleBar);
            updateScaleBar();
            ` : ''}

            // North arrow rotation
            ${layoutOptions.northArrow ? `
            const northArrow = document.getElementById('north-arrow');
            map.on('rotate', () => {
                northArrow.style.transform = 'rotate(' + (-map.getBearing()) + 'deg)';
            });
            ` : ''}

            // Grid coordinates update
            ${layoutOptions.grid ? `
            map.on('move', () => {
                const c = map.getCenter();
                document.getElementById('grid-lon').textContent = c.lng.toFixed(4);
                document.getElementById('grid-lat').textContent = c.lat.toFixed(4);
            });
            ` : ''}

            // Histogram rendering
            ${layoutOptions.histogram && elevStats ? `
            const histCanvas = document.getElementById('histogram-canvas');
            const histCtx = histCanvas.getContext('2d');
            const data = ${JSON.stringify(elevStats.histogram)};
            const maxCount = Math.max(...data.map(d => d.count));
            const barWidth = 220 / data.length;

            histCtx.fillStyle = '#fbbf24';
            data.forEach((d, i) => {
                const h = (d.count / maxCount) * 160;
                const x = i * barWidth;
                histCtx.fillRect(x, 180 - h, barWidth - 1, h);
            });

            // Draw axes
            histCtx.strokeStyle = '#6b7280';
            histCtx.lineWidth = 1;
            histCtx.beginPath();
            histCtx.moveTo(0, 180);
            histCtx.lineTo(220, 180);
            histCtx.moveTo(0, 0);
            histCtx.lineTo(0, 180);
            histCtx.stroke();

            // Labels
            histCtx.fillStyle = '#9ca3af';
            histCtx.font = '8px sans-serif';
            histCtx.fillText(Math.round(elevStats.min)+'m', 2, 195);
            histCtx.textAlign = 'right';
            histCtx.fillText(Math.round(elevStats.max)+'m', 218, 195);
            ` : ''}
        });
    </script>
</body>
</html>`;
}

// Database operations for offline tiles
async function storeTile(db, z, x, y, data) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction('tiles', 'readwrite');
        const store = tx.objectStore('tiles');
        const tileId = `${z}/${x}/${y}`;
        const putRequest = store.put({ id: tileId, zxy: tileId, z, x, y, data });
        putRequest.onsuccess = () => resolve();
        putRequest.onerror = (e) => reject(e.target.error);
    });
}

self.registration.showNotification = self.registration.showNotification || function () {};
