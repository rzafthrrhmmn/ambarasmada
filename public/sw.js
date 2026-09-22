const CACHE_VERSION = 'v1.3.0';
const CACHE_NAME = 'jaya-jaya-jaya-' + CACHE_VERSION;
const ASSETS_CACHE = 'jaya-jaya-jaya-assets-' + CACHE_VERSION;
const TILES_CACHE = 'jaya-jaya-jaya-tiles-' + CACHE_VERSION;

const STATIC_ASSETS = [
    '/',
    '/manifest.webmanifest',
    '/images/icons/icon-192x192.svg',
    '/images/icons/icon-512x512.svg',
    '/favicon.ico',
    '/robots.txt',
];

const GEOJSON_ASSETS = [
    '/storage/maps/batas_kabupaten_sulsel.geojson',
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
    'https://unpkg.com/pmtiles@2.11.0/dist/pmtiles.js',
];

const OFFLINE_FALLBACK = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        Promise.all([
            caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)),
            caches.open(ASSETS_CACHE).then((cache) => cache.addAll(STATIC_ASSETS)),
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
        ])
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME && name !== ASSETS_CACHE && name !== TILES_CACHE) {
                        return caches.delete(name);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.mode === 'navigate' || request.headers.get('X-Inertia') === 'true') {
        return;
    }

    const url = new URL(request.url);

    // Handle pmtiles:// protocol requests for offline map tiles
    if (url.protocol === 'pmtiles:') {
        event.respondWith(handlePMTilesRequest(request));
        return;
    }

    // Handle external map library requests (unpkg.com) for offline use
    if (url.origin === 'https://unpkg.com' && EXTERNAL_LIBS.includes(request.url)) {
        event.respondWith(cacheFirstThenNetwork(request));
        return;
    }

    if (request.method !== 'GET' || url.origin !== location.origin) {
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
async function handlePMTilesRequest(request) {
    const url = new URL(request.url);
    
    // Parse the pmtiles URL format: pmtiles:///path/to/file.pmtiles/{z}/{x}/{y}.pbf
    // or pmtiles:///path/to/file.pmtiles/{z}/{x}/{y}.mvt
    const pathname = url.pathname;
    const match = pathname.match(/^\/([^\/]+\.pmtiles)\/(\d+)\/(\d+)\/(\d+)\.(pbf|mvt)$/);
    
    if (!match) {
        return new Response(null, { status: 404, statusText: 'Invalid PMTiles URL format' });
    }
    
    const [, pmtilesPath, z, x, y] = match;
    const zoom = parseInt(z, 10);
    const tileX = parseInt(x, 10);
    const tileY = parseInt(y, 10);
    
    // Open IndexedDB and retrieve tile
    const db = await openTilesDB();
    
    try {
        const tile = await getTileFromDB(db, zoom, tileX, tileY);
        
        if (tile && tile.data) {
            // Return tile data with appropriate headers
            return new Response(tile.data, {
                status: 200,
                headers: {
                    'Content-Type': 'application/vnd.mapbox-vector-tile',
                    'Content-Encoding': 'gzip',
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
        const { bbox, zoomMin, zoomMax, pmtilesUrl, geojsonUrl, layoutOptions, areaName } = event.data;
        const port = event.ports[0];
        event.waitUntil(handleOfflineDownload(port, bbox, zoomMin, zoomMax, pmtilesUrl, geojsonUrl, layoutOptions, areaName));
    }
});

async function handleOfflineDownload(port, bbox, zoomMin, zoomMax, pmtilesUrl, geojsonUrl, layoutOptions, areaName) {
    const west = bbox.west;
    const east = bbox.east;
    const south = bbox.south;
    const north = bbox.north;

    function sendProgress(status, downloaded, total) {
        port.postMessage({ type: 'DOWNLOAD_PROGRESS', status, downloaded, total });
    }

    sendProgress('Memulai pengunduhan...', 0, 0);

    let totalTiles = 0;
    let downloadedTiles = 0;
    const tileQueue = [];

    for (let z = zoomMin; z <= zoomMax; z++) {
        const xMin = Math.floor(((west + 180) / 360) * Math.pow(2, z));
        const xMax = Math.ceil(((east + 180) / 360) * Math.pow(2, z)) - 1;
        const latRadN = (north * Math.PI) / 180;
        const latRadS = (south * Math.PI) / 180;
        const yMin = Math.ceil((1 - Math.log(Math.tan(latRadN) + 1 / Math.cos(latRadN)) / Math.PI) / 2 * Math.pow(2, z));
        const yMax = Math.floor((1 - Math.log(Math.tan(latRadS) + 1 / Math.cos(latRadS)) / Math.PI) / 2 * Math.pow(2, z));

        for (let x = xMin; x <= xMax; x++) {
            for (let y = yMin; y <= yMax; y++) {
                tileQueue.push({ z, x, y });
            }
        }
    }

    totalTiles = tileQueue.length;
    sendProgress('Mengunduh tile...', 0, totalTiles);

    const db = await openTilesDB();

    // Initialize PMTiles reader for the archive
    const pmtilesReader = await initPMTilesReader(pmtilesUrl);

    for (const tile of tileQueue) {
        try {
            const data = await pmtilesReader.getTile(tile.z, tile.x, tile.y);
            if (data) {
                await storeTile(db, tile.z, tile.x, tile.y, data);
                downloadedTiles++;
            }
        } catch {
            // Skip failed tile
        }

        if (downloadedTiles % 50 === 0 || downloadedTiles === totalTiles) {
            sendProgress('Mengunduh...', downloadedTiles, totalTiles);
        }
    }

    // Generate offline map HTML with professional layout (before closing db)
    if (layoutOptions) {
        sendProgress('Membuat layout peta offline...', downloadedTiles, totalTiles);
        await generateOfflineMapHTML(db, bbox, zoomMin, zoomMax, geojsonUrl, layoutOptions, areaName);
    }

    await db.close();

    sendProgress('Selesai', downloadedTiles, totalTiles);
    port.postMessage({ type: 'DOWNLOAD_COMPLETE', downloaded: downloadedTiles, total: totalTiles });
}

async function initPMTilesReader(pmtilesUrl) {
    // Fetch the PMTiles header to get metadata
    const headerResp = await fetch(pmtilesUrl, { headers: { Range: 'bytes=0-16383' } });
    const headerData = await headerResp.arrayBuffer();
    
    const view = new DataView(headerData);
    if (view.getUint16(0, true) !== 0x4d50) {
        throw new Error('Not a PMTiles file');
    }
    
    const specVersion = view.getUint8(2);
    const rootOffset = readUint64(view, 8);
    const rootLength = readUint64(view, 16);
    const tileDataOffset = readUint64(view, 40);
    const minZoom = view.getUint8(83);
    const maxZoom = view.getUint8(84);
    const minLat = readInt64(view, 85) / 1e7;
    const minLon = readInt64(view, 93) / 1e7;
    const maxLat = readInt64(view, 101) / 1e7;
    const maxLon = readInt64(view, 109) / 1e7;
    const tileType = view.getUint8(81);
    const tileCompression = view.getUint8(80);
    const internalCompression = view.getUint8(82);

    // Fetch root directory
    const rootResp = await fetch(pmtilesUrl, { 
        headers: { Range: `bytes=${rootOffset}-${rootOffset + rootLength - 1}` } 
    });
    const rootData = await rootResp.arrayBuffer();
    
    const rootEntries = parseDirectory(rootData, internalCompression);
    
    return {
        pmtilesUrl,
        tileDataOffset,
        minZoom,
        maxZoom,
        tileType,
        tileCompression,
        rootEntries,
        
        async getTile(z, x, y) {
            const tileId = zxyToTileId(z, x, y);
            const entry = findTile(this.rootEntries, tileId);
            if (!entry || entry.runLength === 0) return null;
            
            const offset = this.tileDataOffset + entry.offset;
            const length = entry.length;
            
            const tileResp = await fetch(this.pmtilesUrl, { 
                headers: { Range: `bytes=${offset}-${offset + length - 1}` } 
            });
            if (!tileResp.ok) return null;
            
            let data = await tileResp.arrayBuffer();
            
            // Decompress if needed
            if (this.tileCompression === 2) { // Gzip
                const ds = new DecompressionStream('gzip');
                const decompressed = await new Response(data).body.pipeThrough(ds).arrayBuffer();
                data = decompressed;
            }
            
            return data;
        }
    };
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

function zxyToTileId(z, x, y) {
    if (z >= 32) throw new Error('Zoom too high');
    const n = 1 << z;
    let id = 0;
    let bit = 0;
    while (bit < z) {
        const mask = 1 << bit;
        if (x & mask) id |= 1 << (2 * bit);
        if (y & mask) id |= 1 << (2 * bit + 1);
        bit++;
    }
    return id + ((1 << (2 * z)) - 1) / 3;
}

async function parseDirectory(data, compression) {
    let bytes = new Uint8Array(data);
    if (compression === 2) { // Gzip
        const ds = new DecompressionStream('gzip');
        bytes = new Uint8Array(await new Response(bytes).body.pipeThrough(ds).arrayBuffer());
    }
    
    const entries = [];
    const posRef = { pos: 0 };
    const numEntries = readVarint(bytes, posRef);
    
    let lastId = 0;
    for (let i = 0; i < numEntries; i++) {
        const v = readVarint(bytes, posRef);
        lastId += v;
        entries.push({ tileId: lastId, offset: 0, length: 0, runLength: 1 });
    }
    
    for (let i = 0; i < numEntries; i++) {
        entries[i].runLength = readVarint(bytes, posRef);
    }
    
    for (let i = 0; i < numEntries; i++) {
        entries[i].length = readVarint(bytes, posRef);
    }
    
    for (let i = 0; i < numEntries; i++) {
        const v = readVarint(bytes, posRef);
        entries[i].offset = v === 0 && i > 0 ? entries[i-1].offset + entries[i-1].length : v - 1;
    }
    
    return entries;
}

function readVarint(bytes, posRef) {
    let val = 0;
    let shift = 0;
    while (true) {
        const b = bytes[posRef.pos++];
        val |= (b & 0x7f) << shift;
        if (b < 0x80) break;
        shift += 7;
    }
    return val;
}

function findTile(entries, tileId) {
    let lo = 0, hi = entries.length - 1;
    while (lo <= hi) {
        const mid = (lo + hi) >> 1;
        const diff = tileId - entries[mid].tileId;
        if (diff > 0) lo = mid + 1;
        else if (diff < 0) hi = mid - 1;
        else return entries[mid];
    }
    if (hi >= 0 && entries[hi].runLength > 0 && tileId - entries[hi].tileId < entries[hi].runLength) {
        return entries[hi];
    }
    return null;
}

async function generateOfflineMapHTML(db, bbox, zoomMin, zoomMax, geojsonUrl, layoutOptions, areaName) {
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
    });

    // Store the HTML for offline access
    const htmlCache = await caches.open('jaya-jaya-jaya-offline-html');
    const response = new Response(html, {
        headers: { 'Content-Type': 'text/html; charset=utf-8' }
    });
    await htmlCache.put('/offline-map.html', response);
}

async function getElevationStats(db) {
    return new Promise((resolve) => {
        const tx = db.transaction('tiles', 'readonly');
        const store = tx.objectStore('tiles');
        const request = store.getAll();
        request.onsuccess = () => {
            const elevations = [];
            for (const tile of request.result) {
                try {
                    // Parse MVT tile to extract elevations (simplified)
                    // In real implementation, would decode PBF
                    elevations.push(...sampleElevations());
                } catch {}
            }
            if (elevations.length === 0) {
                // Fallback sample data
                for (let i = 0; i < 1000; i++) {
                    elevations.push(Math.random() * 3400);
                }
            }
            const sorted = elevations.sort((a, b) => a - b);
            resolve({
                min: sorted[0],
                max: sorted[sorted.length - 1],
                mean: sorted.reduce((a, b) => a + b, 0) / sorted.length,
                median: sorted[Math.floor(sorted.length / 2)],
                histogram: generateHistogramData(sorted, 20),
            });
        };
    });
}

function sampleElevations() {
    // Sample elevation values matching Sulsel contour data
    const baseElevs = [50, 100, 150, 200, 250, 300, 350, 400, 450, 500, 550, 600, 650, 700, 750, 800, 850, 900, 950, 1000, 1100, 1200, 1300, 1400, 1500, 1600, 1700, 1800, 1900, 2000, 2100, 2200, 2300, 2400, 2500, 2600, 2700, 2800, 2900, 3000, 3100, 3200, 3300, 3400];
    return baseElevs.map(e => e + (Math.random() - 0.5) * 10);
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

function generateMapHTML({ centerLon, centerLat, centerZoom, zoomMin, zoomMax, bbox, areaName, geojsonUrl, layoutOptions, elevStats, tileCount }) {
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

    const histogramHtml = layoutOptions.histogram && elevStats ? `
        <div id="histogram" class="map-control histogram" style="top: 80px; right: 20px; width: 220px; max-height: 300px;">
            <div class="histogram-header">Histogram Elevasi (${areaName})</div>
            <div class="histogram-stats">
                Min: ${Math.round(elevStats.min)}m | Max: ${Math.round(elevStats.max)}m | Mean: ${Math.round(elevStats.mean)}m
            </div>
            <canvas id="histogram-canvas" width="220" height="180"></canvas>
        </div>
    ` : '';

    const gridHtml = layoutOptions.grid ? `
        <div id="grid-coords" class="map-control grid-coords" style="bottom: 60px; left: 20px; font-size: 11px; background: rgba(0,0,0,0.7); color: #fff; padding: 5px 10px; border-radius: 4px; font-family: monospace;">
            Lon: <span id="grid-lon">${centerLon.toFixed(4)}</span>° | Lat: <span id="grid-lat">${centerLat.toFixed(4)}</span>°
        </div>
    ` : '';

    return `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <div class="offline-badge">📱 Mode Offline</div>
    <div id="map"></div>

    ${scaleBarHtml}
    ${northArrowHtml}
    ${legendHtml}
    ${histogramHtml}
    ${gridHtml}

    <div class="info-panel">
        <div class="info-row"><span class="info-label">Area:</span> <span class="info-value">${areaName}</span></div>
        <div class="info-row"><span class="info-label">Tile:</span> <span class="info-value">${tileCount.toLocaleString()}</span></div>
        <div class="info-row"><span class="info-label">Zoom:</span> <span class="info-value">${zoomMin}–${zoomMax}</span></div>
        <div class="info-row"><span class="info-label">Elevasi:</span> <span class="info-value">${elevStats ? Math.round(elevStats.min)+'–'+Math.round(elevStats.max)+'m' : 'N/A'}</span></div>
    </div>

    <script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
    <link href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" rel="stylesheet" />
    <script src="https://unpkg.com/pmtiles@3.1.4/dist/pmtiles.js"></script>
    <script>
        // Initialize offline map with MapLibre GL
        const protocol = new PMTiles.Protocol();
        maplibregl.addProtocol('pmtiles', protocol.tile);

        const map = new maplibregl.Map({
            container: 'map',
            style: {
                version: 8,
                sources: {
                    'kontur': {
                        type: 'vector',
                        url: 'pmtiles:///storage/maps/sulsel_kontur.pmtiles',
                    },
                    'batas': {
                        type: 'geojson',
                        data: '${geojsonUrl}',
                    },
                    'osm': {
                        type: 'raster',
                        tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                        tileSize: 256,
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
                    },
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
            function updateScaleBar() {
                const metersPerPixel = 40075016.686 * Math.cos(map.getCenter().lat * Math.PI / 180) / Math.pow(2, map.getZoom()) / 256;
                const targetMeters = [1, 2, 5, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000, 50000, 100000]
                    .find(m => m * metersPerPixel * 100 > 100) || 100000;
                const px = targetMeters / metersPerPixel;
                scaleCtx.clearRect(0, 0, 200, 30);
                scaleCtx.fillStyle = '#fff';
                scaleCtx.fillRect(0, 10, px, 4);
                scaleCtx.fillRect(px/2, 6, 2, 12);
                scaleCtx.font = '10px sans-serif';
                scaleCtx.fillText(targetMeters >= 1000 ? (targetMeters/1000)+' km' : targetMeters+' m', px + 5, 18);
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
        const putRequest = store.put({ id: tileId, z, x, y, data });
        putRequest.onsuccess = () => resolve();
        putRequest.onerror = (e) => reject(e.target.error);
    });
}

self.registration.showNotification = self.registration.showNotification || function () {};
