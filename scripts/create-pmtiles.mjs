import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { DatabaseSync } from 'node:sqlite';
import geojsonVt from 'geojson-vt';
import { fromGeojsonVt } from 'vt-pbf';

const INPUT_GPKG = path.resolve('file dokumen/sulsel_kontur.gpkg');
const LAYER_NAME = 'sulsel_kontur__contours';
const GEOJSON_PATH = path.resolve('storage/app/public/maps/sulsel_kontur.geojson');
const MBTILES_PATH = path.resolve('storage/app/public/maps/sulsel_kontur.mbtiles');
const PMTILES_PATH = path.resolve('storage/app/public/maps/sulsel_kontur.pmtiles');
const PMTILES_BIN = path.resolve('public/bin/pmtiles.exe');
const MAX_ZOOM = 15;

const sqlite = new DatabaseSync(MBTILES_PATH);

sqlite.exec(`
    CREATE TABLE IF NOT EXISTS metadata (name TEXT PRIMARY KEY, value TEXT);
    CREATE TABLE IF NOT EXISTS tiles (z INTEGER, x INTEGER, y INTEGER, tile_data BLOB, PRIMARY KEY (z, x, y));
    CREATE INDEX IF NOT EXISTS idx_tiles_xyz ON tiles(z, x, y);
`);

const insertTile = sqlite.prepare('INSERT OR REPLACE INTO tiles (z, x, y, tile_data) VALUES (?, ?, ?, ?)');
const setMetadata = sqlite.prepare('INSERT OR REPLACE INTO metadata (name, value) VALUES (?, ?)');

console.log('Step 1: Export GPKG to GeoJSON via ogr2ogr...');
const OGR2OGR = 'C:\\Program Files\\QGIS 4.2.2\\bin\\ogr2ogr.exe';
const ogrCmd = `"${OGR2OGR}" -f GeoJSON -lco RFC7946=YES "${GEOJSON_PATH}" "${INPUT_GPKG}" ${LAYER_NAME}`;
execSync(ogrCmd, { stdio: 'inherit', timeout: 600000, maxBuffer: 100 * 1024 * 1024, shell: true });
console.log('GeoJSON exported.');

const geojson = JSON.parse(fs.readFileSync(GEOJSON_PATH, 'utf8'));
console.log(`Features: ${geojson.features.length}`);

console.log('Step 2: Build tile index with geojson-vt...');
const tileIndex = geojsonVt(geojson, {
    maxZoom: MAX_ZOOM,
    indexMaxZoom: 8,
    tolerance: 3,
    extent: 4096,
    buffer: 64,
    lineMetrics: false,
    debug: 1,
    maxPoints: 0,
});
console.log(`Tiles pre-generated (z 0-${tileIndex.options.indexMaxZoom}): ${tileIndex.tileCoords.length}`);

console.log('Step 3: Collect all tiles with data via drilling...');
const stack = [{ z: 0, x: 0, y: 0 }];
let tileCount = 0;

while (stack.length) {
    const { z, x, y } = stack.pop();
    const tile = tileIndex.getTile(z, x, y);
    if (!tile || tile.features.length === 0) continue;

    const pbf = fromGeojsonVt({ kontur: tile }, { version: 1, extent: 4096 });
    insertTile.run(z, x, y, Buffer.from(pbf));
    tileCount++;

    if (z < MAX_ZOOM) {
        for (let dx = 0; dx < 2; dx++) {
            for (let dy = 0; dy < 2; dy++) {
                stack.push({ z: z + 1, x: x * 2 + dx, y: y * 2 + dy });
            }
        }
    }

    if (tileCount % 500 === 0) {
        console.log(`  Tiles: ${tileCount}`);
    }
}

setMetadata.run('name', 'Sulawesi Kontur');
setMetadata.run('description', 'Vector tile data for Sulawesi contour lines');
setMetadata.run('type', 'overlay');
setMetadata.run('format', 'pbf');
setMetadata.run('minzoom', '0');
setMetadata.run('maxzoom', MAX_ZOOM.toString());
setMetadata.run('version', '1.0');

sqlite.close();
console.log(`MBTiles created with ${tileCount} tiles.`);

console.log('Step 4: Convert MBTiles to PMTiles...');
execSync(`"${PMTILES_BIN}" convert "${MBTILES_PATH}" "${PMTILES_PATH}"`, { stdio: 'inherit', timeout: 600000 });
console.log('PMTiles created!');

fs.rmSync(GEOJSON_PATH, { force: true });
fs.rmSync(MBTILES_PATH, { force: true });
console.log('Cleaned up intermediate files.');
console.log('Done!');
