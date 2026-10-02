import { computed, ref, watch } from 'vue';
import { useAccess } from '@/Composables/useAccess.js';
import { bottomNavModel, navigationModel, resolveSidebarFooter } from '@/Navigation/model.js';

/**
 * Resolver model navigasi.
 *
 * Navigation/model.js hanya berisi data; berkas inilah yang mengubahnya
 * menjadi tampilan sesuai kondisi nyata: kapabilitas yang dimiliki pengguna,
 * angka badge dari props Inertia, rute yang sedang aktif, kata kunci pencarian,
 * dan apakah rail sidebar sedang dalam keadaan ringkas.
 *
 * Semua nilai turunan dihitung ulang otomatis saat props Inertia berpindah, jadi
 * sidebar desktop, drawer mobile, dan bottom nav selalu membaca sumber yang
 * sama tanpa menyalin daftar menu.
 */

const COLLAPSE_STORAGE_KEY = 'ambara.sidebar.collapsed';

/** Ambil nilai props dengan jalur bertitik, mis. `auth.user.pending_count`. */
function readProp(source, path) {
    if (!source || !path) {
        return undefined;
    }

    return String(path)
        .split('.')
        .reduce((value, key) => (value === null || value === undefined ? value : value[key]), source);
}

function normalizePath(url) {
    return (
        String(url || '')
            .split('?')[0]
            .split('#')[0]
            .replace(/\/+$/, '') || '/'
    );
}

function normalizeText(value) {
    return String(value || '')
        .toLowerCase()
        .trim();
}

/**
 * Ubah segmen rute menjadi bacaan manusia, mis. `field-guides` menjadi
 * `Field guides`. Segmen angka (id) dilewati agar judul halaman tidak berubah
 * menjadi angka saja.
 */
function humanizeSegment(segment) {
    const cleaned = String(segment || '')
        .replace(/[-_]+/g, ' ')
        .trim();

    if (!cleaned) {
        return null;
    }

    return cleaned.charAt(0).toUpperCase() + cleaned.slice(1);
}

function readCollapsedPreference() {
    try {
        return window.localStorage.getItem(COLLAPSE_STORAGE_KEY) === '1';
    } catch {
        // Penyimpanan bisa dinonaktifkan, misalnya pada mode privat.
        return false;
    }
}

function writeCollapsedPreference(collapsed) {
    try {
        window.localStorage.setItem(COLLAPSE_STORAGE_KEY, collapsed ? '1' : '0');
    } catch {
        // Tanpa penyimpanan, preferensi hanya berlaku selama sesi ini.
    }
}

export function useNavigation(page) {
    const { user, canAny } = useAccess(page);
    const currentPath = computed(() => normalizePath(page.url));
    const query = ref('');
    const collapsed = ref(typeof window === 'undefined' ? false : readCollapsedPreference());

    /**
     * Entri terlihat bila pengguna punya salah satu kapabilitas yang diminta.
     * Aturan ini sama dengan yang dipakai halaman, jadi menu dan isi halaman
     * tidak pernah berbeda isi.
     */
    const canSee = (item) => Boolean(user.value?.role) && canAny(item.capabilities ?? []);

    /** Entri dianggap aktif pada path persis atau pada turunannya. */
    const matchesPath = (item) => {
        if (item.href === currentPath.value) {
            return true;
        }

        if (item.exact || currentPath.value === '/') {
            return false;
        }

        return currentPath.value.startsWith(`${item.href}/`);
    };

    const badgeFor = (item) => {
        const value = Number(readProp(page.props, item.badge?.source) ?? 0);

        return Number.isFinite(value) && value > 0 ? value : 0;
    };

    /**
     * Gabungkan model mentah dengan konteks pengguna: bagian asal, jumlah
     * badge, dan status aktif. Status aktif belum final karena harus memilih
     * satu-satunya entri yang paling spesifik.
     */
    const buildEntry = (item, section) => ({
        ...item,
        sectionKey: section.key,
        sectionLabel: section.label,
        badge: badgeFor(item),
        matches: matchesPath(item),
    });

    const bottomSection = { key: 'bottom', label: 'Navigasi bawah' };

    const sidebarEntries = computed(() =>
        navigationModel.flatMap((section) => section.items.filter(canSee).map((item) => buildEntry(item, section))),
    );

    const bottomEntries = computed(() => bottomNavModel.filter(canSee).map((item) => buildEntry(item, bottomSection)));

    /**
     * `/members/pending` cocok juga dengan `/members`. Tanpa memilih href
     * terpanjang, dua menu akan menyala bersamaan. Sebaliknya `/kehadiran`
     * dipakai sidebar maupun bottom nav, jadi yang dibandingkan adalah href,
     * bukan kunci entri.
     */
    const activeHref = computed(() => {
        const candidates = [...sidebarEntries.value, ...bottomEntries.value].filter((entry) => entry.matches);

        if (candidates.length === 0) {
            return null;
        }

        return candidates.reduce((best, entry) => (entry.href.length > best.href.length ? entry : best)).href;
    });

    const withActive = (entry) => ({ ...entry, active: entry.href === activeHref.value });

    const sections = computed(() =>
        navigationModel
            .map((section) => ({
                key: section.key,
                label: section.label,
                items: section.items.filter(canSee).map((item) => buildEntry(item, section)).map(withActive),
            }))
            .filter((section) => section.items.length > 0),
    );

    const bottomItems = computed(() => bottomEntries.value.map(withActive));

    const tokens = computed(() => normalizeText(query.value).split(/\s+/).filter(Boolean));

    const matchesQuery = (entry) => {
        const haystack = normalizeText(
            [entry.label, entry.hint, entry.sectionLabel, entry.href, ...(entry.keywords || [])].join(' '),
        );

        return tokens.value.every((token) => haystack.includes(token));
    };

    /**
     * Mode pencarian menggabungkan semua hasil ke dalam satu bagian supaya
     * daftar tidak berlonjong-lonjong setiap kali kata kunci diketik.
     */
    const visibleSections = computed(() => {
        if (tokens.value.length === 0) {
            return sections.value;
        }

        const items = sidebarEntries.value.filter(matchesQuery).map(withActive);

        if (items.length === 0) {
            return [];
        }

        return [{ key: '__pencarian', label: 'Hasil pencarian', items }];
    });

    const noResults = computed(() => tokens.value.length > 0 && visibleSections.value.length === 0);
    const resultCount = computed(() => visibleSections.value.reduce((total, section) => total + section.items.length, 0));

    const activeEntry = computed(() => sidebarEntries.value.find((entry) => entry.href === activeHref.value) ?? null);

    /**
     * Konteks halaman untuk header: bagian dan menu yang sedang aktif. Kalau
     * rute tidak ada di model (halaman turunan yang tak terdaftar, atau rute
     * luar sidebar), judul diambil dari props lalu dari segmen path terakhir.
     */
    const context = computed(() => {
        if (activeEntry.value) {
            return {
                parent: activeEntry.value.sectionLabel,
                current: activeEntry.value.label,
                icon: activeEntry.value.icon,
                source: 'navigation',
            };
        }

        const fromProps = readProp(page.props, 'pageTitle') ?? readProp(page.props, 'title');

        if (fromProps) {
            return { parent: null, current: String(fromProps), icon: null, source: 'props' };
        }

        const segments = normalizePath(page.url)
            .split('/')
            .filter(Boolean);

        for (let index = segments.length - 1; index >= 0; index -= 1) {
            if (/^\d+$/.test(segments[index])) {
                continue;
            }

            const label = humanizeSegment(segments[index]);

            if (label) {
                return { parent: null, current: label, icon: null, source: 'path' };
            }
        }

        return { parent: null, current: 'Beranda', icon: null, source: 'path' };
    });

    const footer = computed(() =>
        resolveSidebarFooter({
            counts: {
                pendingCount: readProp(page.props, 'pendingCount') ?? 0,
                unreadNotificationCount: readProp(page.props, 'unreadNotificationCount') ?? 0,
            },
        }),
    );

    function toggleCollapsed() {
        collapsed.value = !collapsed.value;
    }

    function clearQuery() {
        query.value = '';
    }

    watch(collapsed, (value) => writeCollapsedPreference(value));

    // Pencarian tidak boleh menggantung setelah pengguna berpindah halaman.
    watch(() => page.url, clearQuery);

    // Rail ringkas tidak menampilkan kotak pencarian, jadi kata kunci yang
    // tersisa harus dibuang agar isi rail tidak terfilter tanpa jejak.
    watch(collapsed, (value) => {
        if (value) {
            clearQuery();
        }
    });

    return {
        user,
        query,
        collapsed,
        sections: visibleSections,
        bottomItems,
        footer,
        context,
        activeEntry,
        noResults,
        resultCount,
        matchesPath,
        toggleCollapsed,
        clearQuery,
    };
}