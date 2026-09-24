import { execSync } from 'child_process';
import { existsSync, readdirSync, readFileSync } from 'fs';
import { join } from 'path';

const PUBLIC_BUILD = 'public/build';
const MANIFEST = join(PUBLIC_BUILD, 'manifest.json');
const ASSETS_DIR = join(PUBLIC_BUILD, 'assets');

console.log('=== Vercel Deployment Test ===\n');

let passed = 0;
let failed = 0;

function check(name, condition) {
    if (condition) {
        console.log(`  PASS: ${name}`);
        passed++;
    } else {
        console.log(`  FAIL: ${name}`);
        failed++;
    }
}

console.log('Step 1: Installing dependencies...');
try {
    execSync('npm install', { stdio: 'inherit' });
} catch {
    console.log('npm install failed.\n');
    process.exit(1);
}

console.log('Step 2: Running npm run build...');
try {
    execSync('npm run build', { stdio: 'inherit' });
    console.log('Build completed.\n');
} catch {
    console.log('Build FAILED.\n');
    process.exit(1);
}

console.log('Step 2: Verifying build output...\n');

check('public/build/ directory exists', existsSync(PUBLIC_BUILD));
check('public/build/manifest.json exists', existsSync(MANIFEST));
check('public/build/assets/ directory exists', existsSync(ASSETS_DIR));

if (existsSync(MANIFEST)) {
    try {
        const manifest = JSON.parse(readFileSync(MANIFEST, 'utf-8'));
        check('manifest.json is valid JSON', true);
        const jsAssets = Object.entries(manifest).filter(([k]) => k.endsWith('.js'));
        const cssAssets = Object.entries(manifest).filter(([k]) => k.endsWith('.css'));
        check('manifest has JS entries', jsAssets.length > 0);
        check('manifest has CSS entries', cssAssets.length > 0);
        console.log(`  Info: ${jsAssets.length} JS, ${cssAssets.length} CSS entries in manifest`);
    } catch {
        check('manifest.json is valid JSON', false);
    }
}

if (existsSync(ASSETS_DIR)) {
    const assets = readdirSync(ASSETS_DIR);
    check('assets directory is not empty', assets.length > 0);
    console.log(`  Info: ${assets.length} files in assets/`);
    assets.forEach(f => console.log(`    - ${f}`));
}

check('api/status.php exists', existsSync('api/status.php'));
check('api/index.php exists', existsSync('api/index.php'));
check('vercel.json exists', existsSync('vercel.json'));
check('vite.config.js exists', existsSync('vite.config.js'));

console.log(`\n=== Results: ${passed} passed, ${failed} failed ===`);
if (failed > 0) {
    process.exit(1);
}
