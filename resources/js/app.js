//
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

const appName = import.meta.env.VITE_APP_NAME || 'AMBARA-SISTEM DIGITAL';

createInertiaApp({
    title: (title) => (title ? title + ' - ' + appName : appName),
    resolve: (name) => resolvePageComponent('./Pages/' + name + '.vue', import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Toast, { position: 'top-center', timeout: 4000, closeOnClick: true, pauseOnHover: true, draggable: true, showCloseButton: 'onError', transition: 'Vue-Toastification__bounce' })
            .mount(el);
    },
    progress: {
        color: '#6F9435',
        showSpinner: true,
        delay: 16,
    },
});

// Enable instant navigation - visit pages immediately, then update props
router.on('navigate', (event) => {
    // Allow immediate page switch without waiting
});

// Service Worker registration for PWA offline support.
//
// sw.js handles asset caching, the pmtiles:// protocol for offline map tiles,
// and serves /offline.html when the network is unreachable. Without this
// registration none of that code ever runs.
//
// Production only: in dev the SW would cache Vite's dev-server bundles and
// break HMR. Use `sw.js` being absent (or unregister) when testing locally.
if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // updateViaCache: 'none' membuat browser memeriksa sw.js di jaringan
        // setiap kali halaman dimuat. Tanpa itu, salinan sw.js yang tersimpan
        // di HTTP cache bisa dipakai lagi sehingga service worker baru tidak
        // pernah terpasang sampai cache itu kedaluwarsa.
        navigator.serviceWorker
            .register('/sw.js', { scope: '/', updateViaCache: 'none' })
            .then((registration) => {
                // Pick up a newly deployed sw.js without forcing a reload;
                // the update activates on its own via skipWaiting/claim.
                registration.addEventListener('updatefound', () => {
                    const installing = registration.installing;
                    if (!installing) return;

                    installing.addEventListener('statechange', () => {
                        if (installing.state === 'activated' && navigator.serviceWorker.controller) {
                            console.info('[SW] Versi baru aktif. Muat ulang untuk memakai aset terbaru.');
                        }
                    });
                });
            })
            .catch((error) => {
                console.error('[SW] Gagal mendaftarkan service worker:', error);
            });
    });
}

 
// Cookie overflow protection for PWA
// Monitors cookie size and clears old non-essential cookies to prevent 500 errors
(function() {
    function getCookieSize() {
        if (!document.cookie) return 0;
        return decodeURIComponent(document.cookie).length;
    }
    
    function clearStaleCookies() {
        const threshold = 3500; // browsers limit ~4096 bytes
        if (getCookieSize() > threshold) {
            // Clear Inertia history state from cookie if present
            const inertiaCookie = document.cookie.match(/inertia_preview_data=([^;]+)/);
            if (inertiaCookie) {
                document.cookie = 'inertia_preview_data=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            }
            // Clear any large flash data cookies
            const cookies = document.cookie.split(';');
            for (let i = 0; i < cookies.length; i++) {
                const cookie = cookies[i].trim();
                const eq = cookie.indexOf('=');
                const name = eq > -1 ? cookie.substr(0, eq) : cookie;
                // Don't delete session cookie
                if (name && !name.includes('session') && !name.includes('remember') && cookie.length > 500) {
                    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
                }
            }
            console.warn('[CookieGuard] Cleared stale cookies to prevent overflow');
        }
    }
    
    // Check on initial load
    window.addEventListener('load', clearStaleCookies);
    // Check on navigation
    router.on('navigate', clearStaleCookies);
    // Check periodically for long sessions
    setInterval(clearStaleCookies, 300000);
})();

// Catch cookie-related errors globally
window.addEventListener('error', function(event) {
    const err = event.error;
    if (err && (err.message && err.message.includes('cookie') || err.message && err.message.includes('431'))) {
        // 431 Request Header Fields Too Large - clear only non-essential cookies
        document.cookie.split(';').forEach(function(c) {
            const cookie = c.trim();
            const eq = cookie.indexOf('=');
            const name = eq > -1 ? cookie.substr(0, eq) : cookie;
            // Don't delete session or remember cookies
            if (name && !name.includes('session') && !name.includes('remember')) {
                document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            }
        });
    }
});
