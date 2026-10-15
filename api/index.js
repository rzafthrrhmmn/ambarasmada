import { createRequire } from 'module';
import { spawn } from 'child_process';
import http from 'http';
import path from 'path';

const require = createRequire(import.meta.url);

function findPhpBin() {
    try {
        const pkgJsonPath = require.resolve('@libphp/almalinux-9-v85/package.json');
        const pkgDir = path.dirname(pkgJsonPath);
        const phpBin = path.join(pkgDir, 'native/php/php');
        const phpIni = path.join(pkgDir, 'native/php/php.ini');
        const phpModules = path.join(pkgDir, 'native/php/modules');
        const phpLibs = path.join(pkgDir, 'native/lib');
        return { phpBin, phpIni, phpModules, phpLibs, pkgDir };
    } catch (e) {
        const phpBin = process.env.PHP_BIN || 'php';
        const phpIni = process.env.PHP_INI || '';
        const phpModules = '';
        const phpLibs = '';
        return { phpBin, phpIni, phpModules, phpLibs, pkgDir: '' };
    }
}

const LAMBDA_TASK_ROOT = process.env.LAMBDA_TASK_ROOT || '/var/task';
process.env.LAMBDA_TASK_ROOT = LAMBDA_TASK_ROOT;

const { phpBin, phpIni, phpModules, phpLibs } = findPhpBin();

const PHP_PORT = 3000;
const docroot = LAMBDA_TASK_ROOT;
const router = path.join(LAMBDA_TASK_ROOT, 'server.php');

let serverProcess = null;
let readyResolvers = [];
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
            path: '/',
            timeout: 200,
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

function startPhpServer() {
    return new Promise((resolve, reject) => {
        if (isStarting) {
            readyResolvers.push({ resolve, reject });
            return;
        }

        if (serverProcess && !serverProcess.killed) {
            resolve();
            return;
        }

        isStarting = true;

        const args = [
            '-c', phpIni,
            '-d', `extension_dir=${phpModules}`,
            '-d', 'display_errors=stderr',
            '-S', `127.0.0.1:${PHP_PORT}`,
            '-t', docroot,
            router
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
            readyResolvers.forEach(({ reject }) => reject(err));
            readyResolvers = [];
            reject(err);
        });

        serverProcess.on('exit', (code, signal) => {
            console.error(`[PHP] Server exited (code: ${code}, signal: ${signal})`);
            serverProcess = null;
            isStarting = false;
            readyResolvers = [];
            serverProcess = null;
        });

        setTimeout(async () => {
            if (isStarting) {
                const ready = await waitForServer(50);
                if (ready) {
                    isStarting = false;
                    const resolvers = [...readyResolvers];
                    readyResolvers = [];
                    resolvers.forEach(({ resolve }) => resolve());
                    if (serverProcess && !serverProcess.killed) {
                        resolve();
                    } else {
                        reject(new Error('PHP server process died during startup'));
                    }
                } else {
                    if (serverProcess && !serverProcess.killed) {
                        isStarting = false;
                        const resolvers = [...readyResolvers];
                        readyResolvers = [];
                        resolvers.forEach(({ resolve }) => resolve());
                        resolve();
                    }
                }
            }
        }, 2000);
    });
}

let serverStarted = false;

export default async function handler(req, res) {
    try {
        if (!serverStarted || !serverProcess || serverProcess.killed) {
            await startPhpServer();
            serverStarted = true;
        }

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
