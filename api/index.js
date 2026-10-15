import { createRequire } from 'module';
import { spawn } from 'child_process';
import http from 'http';
import path from 'path';
import fs from 'fs';

// Import @libphp to force @vercel/nft to bundle the native/ directory
import libphp from '@libphp/almalinux-9-v85';
void libphp;

// Import laravel-vendor package to force @vercel/nft to include vendor/ and server.php
// ESM import is statically analyzed by @vercel/nft, unlike require() through createRequire
import 'laravel-vendor';

const require = createRequire(import.meta.url);
const tar = require('tar');

const pkgJsonPath = require.resolve('@libphp/almalinux-9-v85/package.json');
const pkgDir = path.dirname(pkgJsonPath);
const phpBin = path.join(pkgDir, 'native', 'php', 'php');
const phpIni = path.join(pkgDir, 'native', 'php', 'php.ini');
const phpModules = path.join(pkgDir, 'native', 'php', 'modules');
const phpLibs = path.join(pkgDir, 'native', 'lib');

const APP_EXTRACT_DIR = '/tmp';
const VENDOR_EXTRACT_DIR = '/tmp/vendor';

const vendorTarballPath = require.resolve('laravel-vendor/vendor.tar.gz');
const appTarballPath = require.resolve('laravel-vendor/app-files.tar.gz');

function extractTarballs() {
    if (!fs.existsSync(path.join(VENDOR_EXTRACT_DIR, 'autoload.php'))) {
        fs.mkdirSync(VENDOR_EXTRACT_DIR, { recursive: true });
        tar.x({ file: vendorTarballPath, cwd: VENDOR_EXTRACT_DIR, sync: true });
        console.log('[VENDOR] Extracted vendor/ to', VENDOR_EXTRACT_DIR);
    }

    if (!fs.existsSync(path.join(APP_EXTRACT_DIR, 'server.php'))) {
        tar.x({ file: appTarballPath, cwd: APP_EXTRACT_DIR, sync: true });
        console.log('[APP] Extracted application files to', APP_EXTRACT_DIR);
    }

    process.env.LAMBDA_TASK_ROOT = APP_EXTRACT_DIR;
    process.env.VENDOR_PATH = VENDOR_EXTRACT_DIR;
}

const PHP_PORT = 3000;
let docroot = APP_EXTRACT_DIR;
let router = path.join(APP_EXTRACT_DIR, 'server.php');

let serverProcess = null;
let isStarting = false;

function getPhpEnv() {
    const env = { ...process.env };
    if (phpLibs) {
        env.LD_LIBRARY_PATH = `${phpLibs}:/usr/lib64:/lib64:${process.env.LD_LIBRARY_PATH || ''}`;
    }
    if (phpModules) {
        env.PHP_INI_EXTENSION_DIR = phpModules;
    }
    return env;
}

function checkServerReady() {
    return new Promise((resolve) => {
        const req = http.request({
            hostname: '127.0.0.1',
            port: PHP_PORT,
            method: 'GET',
            path: '/api/status',
            timeout: 500,
        }, (res) => {
            res.resume();
            resolve(true);
        });
        req.on('error', () => resolve(false));
        req.on('timeout', () => {
            req.destroy();
            resolve(false);
        });
        req.end();
    });
}

async function waitForServer(maxAttempts = 50) {
    for (let i = 0; i < maxAttempts; i++) {
        if (await checkServerReady()) {
            return true;
        }
        await new Promise(r => setTimeout(r, 100));
    }
    return false;
}

async function startPhpServer() {
    if (serverProcess && !serverProcess.killed) {
        return;
    }

    if (isStarting) {
        return new Promise((resolve) => {
            const check = () => {
                if (!isStarting) resolve();
                else setTimeout(check, 100);
            };
            check();
        });
    }

    isStarting = true;

    const finalDocroot = APP_EXTRACT_DIR + '/public';
    const finalRouter = path.join(APP_EXTRACT_DIR, 'server.php');

    const args = [
        '-c', phpIni,
        '-d', `extension_dir=${phpModules}`,
        '-d', 'display_errors=stderr',
        '-S', `127.0.0.1:${PHP_PORT}`,
        '-t', finalDocroot,
        finalRouter
    ];

    serverProcess = spawn(phpBin, args, {
        env: getPhpEnv(),
        stdio: ['ignore', 'pipe', 'pipe'],
    });

    serverProcess.stdout.on('data', (data) => {
        const output = data.toString();
        process.stdout.write(`[PHP] ${output}`);
    });

    serverProcess.stderr.on('data', (data) => {
        const output = data.toString();
        process.stderr.write(`[PHP] ${output}`);
    });

    serverProcess.on('error', (err) => {
        console.error('[PHP] Server error:', err);
        serverProcess = null;
        isStarting = false;
    });

    serverProcess.on('exit', (code, signal) => {
        console.error(`[PHP] Server exited (code: ${code}, signal: ${signal})`);
        serverProcess = null;
        isStarting = false;
    });

    // Wait for server to be ready
    const ready = await waitForServer(100);
    isStarting = false;
    if (!ready && serverProcess && !serverProcess.killed) {
        console.warn('[PHP] Server not responding but process still running');
    }
}

// Module-level initialization: extract tarballs and start PHP server during cold start
let initPromise = null;

async function initialize() {
    if (initPromise) return initPromise;
    
    initPromise = (async () => {
        console.log('[INIT] Starting cold start initialization');
        console.time('[INIT] extractTarballs');
        extractTarballs();
        console.timeEnd('[INIT] extractTarballs');
        
        console.time('[INIT] startPhpServer');
        await startPhpServer();
        console.timeEnd('[INIT] startPhpServer');
        
        console.log('[INIT] Cold start initialization complete');
    })();
    
    return initPromise;
}

// Kick off initialization immediately
initialize().catch(err => {
    console.error('[INIT] Failed:', err);
});

let serverReady = false;

function ensureServerReady() {
    if (serverReady) return Promise.resolve();
    return initPromise;
}

export default async function handler(req, res) {
    try {
        await ensureServerReady();

        const url = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`);
        const headers = { ...req.headers };
        delete headers['host'];
        delete headers['content-length'];
        delete headers['transfer-encoding'];
        delete headers['connection'];

        const proxyReq = http.request({
            hostname: '127.0.0.1',
            port: PHP_PORT,
            path: url.pathname + url.search,
            method: req.method || 'GET',
            headers,
        }, (proxyRes) => {
            res.statusCode = proxyRes.statusCode || 200;

            for (const [key, value] of Object.entries(proxyRes.headers)) {
                if (key.toLowerCase() !== 'transfer-encoding') {
                    res.setHeader(key, value);
                }
            }

            proxyRes.pipe(res);
        });

        proxyReq.on('error', (error) => {
            console.error('[PROXY ERROR]', error);
            if (!res.headersSent) {
                res.statusCode = 500;
                res.setHeader('Content-Type', 'text/plain');
                res.end('Internal Server Error');
            }
        });

        if (req.method !== 'GET' && req.method !== 'HEAD' && req.method !== 'OPTIONS') {
            req.pipe(proxyReq);
        } else {
            proxyReq.end();
        }
    } catch (error) {
        console.error('[HANDLER ERROR]', error);
        if (!res.headersSent) {
            res.statusCode = 500;
            res.setHeader('Content-Type', 'text/plain');
            res.end('Internal Server Error');
        }
    }
}
