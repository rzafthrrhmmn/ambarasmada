import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ROLE_GROUPS, ROLES, resolveCapabilities } from '@/Access/capabilities.js';

/**
 * Pemeriksaan hak akses di sisi klien.
 *
 * Pola yang dipakai halaman:
 *
 *   const { can, isAnggota } = useAccess();
 *
 *   <button v-if="can('attendance.session.create')">+ Sesi latihan</button>
 *   <p v-if="isAnggota">Kehadiran saya</p>
 *
 * `can()` membaca daftar kapabilitas di Access/capabilities.js, bukan daftar
 * peran yang ditulis ulang di setiap halaman. Nilai kembalinya reaktif:
 * mengikuti props Inertia, jadi perpindahan peran atau pembaruan props langsung
 * mengubah tampilan tanpa memuat ulang halaman.
 */
export function useAccess(page = null) {
    const activePage = page ?? usePage();

    const user = computed(() => activePage.props.auth?.user ?? null);
    const role = computed(() => user.value?.role ?? null);
    const isJuruUang = computed(() => Boolean(user.value?.is_juru_uang));
    const capabilities = computed(() => resolveCapabilities(user.value));

    /** Apakah pengguna punya kapabilitas tertentu. */
    const can = (capability) => capabilities.value.has(capability);

    /** Cukup salah satu dari kapabilitas yang diminta. */
    const canAny = (...list) => list.flat().some((capability) => can(capability));

    /** Harus punya semuanya. */
    const canAll = (...list) => list.flat().every((capability) => can(capability));

    /** Apakah peran pengguna termasuk salah satu yang diminta. */
    const is = (...list) => list.flat().includes(role.value);

    /** Apakah peran pengguna termasuk satu kelompok peran. */
    const inGroup = (group) => {
        const members = ROLE_GROUPS[group];

        if (!members) {
            if (import.meta.env?.DEV) {
                console.warn(`[rbac] Kelompok peran "${group}" tidak dikenal.`);
            }

            return false;
        }

        return is(members);
    };

    return {
        user,
        role,
        isJuruUang,
        capabilities,
        can,
        canAny,
        canAll,
        is,
        inGroup,
        isAdmin: computed(() => is(ROLES.ADMIN)),
        isPembina: computed(() => is(ROLES.PEMBINA)),
        isPengurus: computed(() => is(ROLES.PENGURUS)),
        isAnggota: computed(() => is(ROLES.ANGGOTA)),
        isAlumni: computed(() => is(ROLES.ALUMNI)),
        /** Admin, Pembina, atau Pengurus. */
        isManagement: computed(() => inGroup('management')),
        /** Admin atau Pembina. */
        isApprover: computed(() => inGroup('approver')),
    };
}