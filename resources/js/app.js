import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import '../css/app.css';

const appName = import.meta.env.VITE_APP_NAME || 'Ekosistem Digital Kepramukaan';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true, import: 'default' });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        return createApp({
            render: () => h(App, props),
        })
            .use(plugin)
            .mount(el);
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
});

router.on('error', (event) => {
    const response = event.detail?.response;
    if (response && (response.status === 401 || response.status === 403)) {
        window.location.href = '/login';
        return;
    }
    if (event.detail instanceof Error && event.detail.message.includes('Network error')) {
        window.location.href = '/login';
    }
});

window.addEventListener('load', () => {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch((error) => console.error('Service worker registration failed', error));
    }
});

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.deferredPrompt = event;
    window.pwaInstallAvailable = true;
    window.dispatchEvent(new CustomEvent('pwa-install-ready'));
});

window.addEventListener('appinstalled', () => {
    window.deferredPrompt = null;
    window.pwaInstallAvailable = false;
    window.dispatchEvent(new CustomEvent('pwa-install-unavailable'));
});

window.installPwa = async () => {
    const promptEvent = window.deferredPrompt;
    if (!promptEvent) {
        return null;
    }

    try {
        promptEvent.prompt();
        if (promptEvent.userChoice) {
            await promptEvent.userChoice;
        }
    } catch {
        return null;
    } finally {
        window.deferredPrompt = null;
        window.pwaInstallAvailable = false;
        window.dispatchEvent(new CustomEvent('pwa-install-unavailable'));
    }
};
