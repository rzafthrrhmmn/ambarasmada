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

