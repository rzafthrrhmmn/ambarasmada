/**
 * Antrean mutasi offline.
 *
 * Presensi anggota sering dilakukan di lapangan (balai, lapangan, area tanpa
 * sinyal). Payloadnya kecil dan flat, jadi localStorage sudah cukup. Payload
 * berukuran besar seperti unggahan bukti SKU TIDAK cocok untuk localStorage
 * (batas ~5 MB) dan tidak diantrekan di sini.
 *
 * Keamanan: antrean memakai endpoint yang sama dengan form biasa, sehingga
 * tetap melewati middleware auth + throttle Laravel. Endpoint checkIn
 * mengulang baris yang sama (updateOrCreate), jadi pengiriman ulang aman dan
 * tidak menghasilkan presensi ganda.
 */

const STORAGE_KEY = 'ambara_offline_queue_v1';
const MAX_ITEMS = 50;

let listeners = new Set();
let flushing = false;
let onlineHandlerBound = false;

function read() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function write(items) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    } catch (error) {
        console.error('[OfflineQueue] Gagal menyimpan antrean:', error);
    }
    notify();
}

function notify() {
    const snapshot = pending();
    listeners.forEach((listener) => {
        try {
            listener(snapshot);
        } catch (error) {
            console.error('[OfflineQueue] Listener bermasalah:', error);
        }
    });
}

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

export function pending() {
    return read().map((item) => ({ ...item.payload, queued_at: item.queued_at }));
}

export function pendingCount() {
    return read().length;
}

export function subscribe(listener) {
    listeners.add(listener);
    listener(pending());
    bindOnlineListener();
    return () => listeners.delete(listener);
}

function bindOnlineListener() {
    if (onlineHandlerBound) return;
    onlineHandlerBound = true;
    window.addEventListener('online', () => {
        flush().catch(() => {});
    });
}

/**
 * Masukkan satu payload ke antrean.
 */
export function enqueue(payload, meta = {}) {
    const items = read();

    if (items.length >= MAX_ITEMS) {
        items.shift();
    }

    const payload_id =
        meta.payload_id ||
        `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;

    items.push({
        payload_id,
        payload: { ...payload, payload_id },
        url: meta.url,
        queued_at: new Date().toISOString(),
        attempts: 0,
    });

    write(items);
    return payload_id;
}

/**
 * Kirim semua antrean yang tertunda.
 *
 * Item yang ditolak server (4xx selain 419) dibuang karena pengiriman ulang
 * tidak akan pernah berhasil. Item dengan 419/5xx atau kegagalan jaringan
 * dipertahankan untuk dicoba lagi.
 */
export async function flush() {
    if (flushing) return { sent: 0, failed: 0 };
    if (!navigator.onLine) return { sent: 0, failed: pendingCount() };

    const items = read();
    if (items.length === 0) return { sent: 0, failed: 0 };

    flushing = true;
    let sent = 0;
    let failed = 0;
    const keep = [];

    try {
        for (const item of items) {
            try {
                const response = await fetch(item.url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(item.payload),
                });

                if (response.ok) {
                    sent += 1;
                    continue;
                }

                const status = response.status;

                if (status === 419 || status >= 500) {
                    failed += 1;
                    keep.push({ ...item, attempts: (item.attempts || 0) + 1 });
                    continue;
                }

                failed += 1;
                const message = await safeMessage(response);
                console.warn('[OfflineQueue] Item ditolak server:', status, message);
            } catch (error) {
                failed += 1;
                keep.push({ ...item, attempts: (item.attempts || 0) + 1 });
                console.warn('[OfflineQueue] Item gagal dikirim, akan dicoba lagi:', error);
            }
        }
    } finally {
        write(keep);
        flushing = false;
    }

    return { sent, failed };
}

async function safeMessage(response) {
    try {
        const data = await response.json();
        return data.message || data.errors || '(tanpa pesan)';
    } catch {
        return '(respons bukan JSON)';
    }
}

export function clear() {
    write([]);
}
