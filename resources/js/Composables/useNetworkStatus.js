import { ref, readonly } from 'vue';
import { pendingCount, subscribe, flush } from '@/OfflineQueue.js';

/**
 * Status koneksi global.
 *
 * Hanya ada di dua halaman peta, sehingga anggota yang sedang mengisi
 * presensi atau membuka halaman lain tidak mendapat indikasi koneksi terputus.
 * State di modul ini bersifat singleton: seluruh komponen memakai salinan
 * yang sama, jadi listener window cukup dipasang sekali.
 */

const isOnline = ref(typeof navigator === 'undefined' ? true : navigator.onLine);
const queued = ref(0);
const flushing = ref(false);
let initialised = false;

export function useNetworkStatus() {
    initialise();

    return {
        isOnline: readonly(isOnline),
        queued: readonly(queued),
        flushing: readonly(flushing),
        flushQueue,
    };
}

export function initialise() {
    if (initialised) return;
    initialised = true;

    window.addEventListener('online', () => {
        isOnline.value = true;
        flushQueue();
    });

    window.addEventListener('offline', () => {
        isOnline.value = false;
    });

    subscribe((items) => {
        queued.value = items.length;
    });

    if (navigator.onLine) {
        flushQueue();
    }
}

export async function flushQueue() {
    if (flushing.value || !isOnline.value || queued.value === 0) return;

    flushing.value = true;
    try {
        await flush();
    } finally {
        flushing.value = false;
        queued.value = pendingCount();
    }
}
