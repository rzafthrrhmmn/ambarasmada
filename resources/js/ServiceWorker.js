/**
 * Resolusi service worker yang aman untuk fitur peta offline.
 *
 * `navigator.serviceWorker.ready` TIDAK aman dipanggil di sini: promise itu
 * tidak pernah resolve bila tidak ada service worker yang terdaftar, sehingga
 * alur unduhan tile akan menggantung selamanya dengan spinner berputar tanpa
 * error maupun timeout. GetRegistration() sendiri selalu resolve (dengan
 * undefined bila tidak ada), jadi kegagalan bisa dilaporkan ke pengguna.
 */

export const SW_UNAVAILABLE = 'Service Worker belum aktif. Muat ulang halaman setelah deploy terbaru.';

export async function getActiveServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        throw new Error('Browser Anda tidak mendukung Service Worker.');
    }

    const registration = await navigator.serviceWorker.getRegistration('/');

    if (!registration) {
        throw new Error(SW_UNAVAILABLE);
    }

    const worker = registration.active || navigator.serviceWorker.controller;

    if (!worker) {
        throw new Error('Service Worker belum siap. Tunggu sebentar lalu coba lagi.');
    }

    return worker;
}

/**
 * Cache yang boleh dihapus saat logout.
 *
 * Respons Inertia memuat props milik pengguna yang sedang login (nama, role,
 * data anggota). Kalau cache ini bertahan di perangkat bersama, akun berikutnya
 * bisa sempat melihat data pengguna sebelumnya. Karena itu logout wajib
 * membersihkannya.
 */
export async function purgeUserScopedCaches() {
    if (typeof caches === 'undefined') return;

    try {
        const names = await caches.keys();
        const scoped = names.filter(
            (name) => name.includes('inertia') || name.includes('pages') || name.includes('offline-html')
        );

        await Promise.all(scoped.map((name) => caches.delete(name)));

        return scoped.length;
    } catch (error) {
        console.warn('[SW] Gagal membersihkan cache setelah logout:', error);
        return 0;
    }
}
