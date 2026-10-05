<template>
  <AppLayout>
    <div class="page-head">
      <div>
        <p class="page-eyebrow">Blog Ambalan</p>
        <h1 class="page-title">Artikel &amp; Laporan</h1>
        <p class="page-subtitle">Berbagi pengalaman, laporan kegiatan, dan artikel.</p>
      </div>
      <button v-if="canManage" type="button" class="btn-primary" @click="openCreate">
        <AppIcon name="plus" :stroke="2.2" class="h-4 w-4" />
        Artikel Baru
      </button>
    </div>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
      <label class="relative block flex-1">
        <span class="sr-only">Cari artikel</span>
        <AppIcon
          name="search"
          class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#8fa06a]"
        />
        <input
          v-model="search"
          type="search"
          placeholder="Cari judul atau isi artikel..."
          class="field pl-9"
        />
      </label>
      <select v-model="filterKategori" class="field sm:w-56">
        <option value="">Semua Kategori</option>
        <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
      </select>
    </div>

    <div v-if="!visibleArticles.length" class="empty-state">
      <AppIcon name="articles" class="empty-state-icon h-6 w-6" />
      <span class="empty-state-text">
        {{ search ? 'Tidak ada artikel yang cocok dengan pencarian.' : 'Belum ada artikel.' }}
      </span>
      <button v-if="search" type="button" class="btn-ghost btn-sm mt-1" @click="search = ''">
        <AppIcon name="close" class="h-3.5 w-3.5" />
        Bersihkan pencarian
      </button>
    </div>

    <div v-else class="grid gap-4 md:grid-cols-2">
      <article
        v-for="article in visibleArticles"
        :key="article.id"
        class="group card flex flex-col hover:-translate-y-1 hover:shadow-xl hover:shadow-[#EDD330]/15"
      >
        <span class="card-glow" aria-hidden="true" />

        <Link
          v-if="article.image_url"
          :href="`/articles/${article.id}`"
          class="relative -mx-5 -mt-5 mb-4 block overflow-hidden"
        >
          <img
            :src="article.image_url"
            :alt="article.judul"
            class="h-40 w-full object-cover transition duration-300 group-hover:scale-[1.03]"
            loading="lazy"
          />
        </Link>

        <div class="relative flex flex-1 flex-col">
          <div class="flex flex-wrap items-center gap-2">
            <span class="badge-neutral">{{ article.kategori }}</span>
            <span v-if="article.is_published" class="badge-approved">
              <AppIcon name="check" :stroke="2.6" class="h-3 w-3" />
              Terbit
            </span>
            <span v-else class="badge-pending">
              <AppIcon name="note" class="h-3 w-3" />
              Draf
            </span>
          </div>

          <h3 class="mt-2 text-base font-bold leading-snug text-[#f0ead8]">
            <Link
              :href="`/articles/${article.id}`"
              class="transition-colors after:absolute after:inset-0 hover:text-[#EDD330]"
            >
              {{ article.judul }}
            </Link>
          </h3>

          <p class="mt-2 line-clamp-3 flex-1 text-xs leading-5 text-[#8fa06a]">{{ article.konten }}</p>

          <div class="mt-4 flex items-end justify-between gap-3 border-t border-[#6F9435]/25 pt-3">
            <span class="inline-flex min-w-0 items-center gap-1.5 text-[10px] text-[#8fa06a]">
              <AppIcon name="profile" class="h-3.5 w-3.5 shrink-0 text-[#A7B92B]" />
              <span class="truncate">{{ article.author?.name ?? 'Tanpa penulis' }}</span>
            </span>
            <span class="inline-flex shrink-0 items-center gap-1.5 text-[10px] text-[#8fa06a]">
              <AppIcon name="calendar" class="h-3.5 w-3.5 shrink-0 text-[#A7B92B]" />
              {{ formatDate(article.created_at) }}
            </span>
          </div>
        </div>

        <div
          v-if="canManage"
          class="relative z-10 mt-3 flex flex-wrap gap-2"
        >
          <Link :href="`/articles/${article.id}`" class="btn-ghost btn-sm">
            <AppIcon name="externalLink" class="h-3.5 w-3.5" />
            Detail
          </Link>
          <button
            v-if="article.can_edit"
            type="button"
            class="btn-ghost btn-sm"
            @click="openEdit(article)"
          >
            <AppIcon name="note" class="h-3.5 w-3.5" />
            Edit
          </button>
          <button
            v-if="article.can_delete"
            type="button"
            class="btn-danger btn-sm"
            @click="destroy(article)"
          >
            <AppIcon name="trash" class="h-3.5 w-3.5" />
            Hapus
          </button>
        </div>
      </article>
    </div>

    <Pagination
      v-if="visibleArticles.length"
      :links="articles.links"
      class="mt-5 border-t border-[#6F9435] p-3"
    />

    <Modal v-if="showForm" :title="editingArticle ? 'Edit Artikel' : 'Tambah Artikel'" @close="reset">
      <form class="grid gap-3" @submit.prevent="submit">
        <label class="block">
          <span class="field-label">Judul</span>
          <input v-model="form.judul" required class="field mt-1" />
          <span v-if="form.errors.judul" class="mt-1 block text-xs text-[#ef4419]">{{ form.errors.judul }}</span>
        </label>
        <label class="block">
          <span class="field-label">Kategori</span>
          <select v-model="form.kategori" required class="field mt-1">
            <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
          </select>
          <span v-if="form.errors.kategori" class="mt-1 block text-xs text-[#ef4419]">{{ form.errors.kategori }}</span>
        </label>
        <label class="block">
          <span class="field-label">Konten</span>
          <textarea v-model="form.konten" required rows="8" class="field mt-1 resize-y"></textarea>
          <span v-if="form.errors.konten" class="mt-1 block text-xs text-[#ef4419]">{{ form.errors.konten }}</span>
        </label>
        <label class="block">
          <span class="field-label">Gambar sampul</span>
          <input
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="field mt-1 file:mr-3 file:rounded-lg file:border-0 file:bg-[#6F9435] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white"
            @change="form.image = $event.target.files[0]"
          />
          <span v-if="form.errors.image" class="mt-1 block text-xs text-[#ef4419]">{{ form.errors.image }}</span>
        </label>
        <label class="flex items-center gap-2">
          <input
            v-model="form.is_published"
            type="checkbox"
            class="h-4 w-4 rounded border-[#6F9435] bg-[#263D26] text-[#A7B92B]"
          />
          <span class="text-xs font-medium text-[#f0ead8]">Terbitkan</span>
        </label>
        <button type="submit" :disabled="form.processing" class="btn-primary">
          {{ editingArticle ? 'Simpan Perubahan' : 'Tambah Artikel' }}
        </button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { useAccess } from '@/Composables/useAccess.js';

const FALLBACK_CATEGORIES = ['Laporan Kegiatan', 'Artikel', 'Berita', 'Tips & Trik', 'Lainnya'];

const props = defineProps({
  articles: { type: Object, required: true },
  categories: { type: Array, default: () => ['Laporan Kegiatan', 'Artikel', 'Berita', 'Tips & Trik', 'Lainnya'] },
  filters: { type: Object, default: () => ({}) },
});

const { can } = useAccess();

// Dulu selalu true sehingga tombol terbit muncul untuk semua orang, lalu
// ditolak 403 saat dikirim.
const canManage = computed(() => can('content.articles.create'));

const search = ref('');
const filterKategori = ref(props.filters.kategori ?? '');
const showForm = ref(false);
const editingArticle = ref(null);

const form = useForm({
  judul: '',
  konten: '',
  kategori: FALLBACK_CATEGORIES[0],
  image: null,
  is_published: false,
});

const visibleArticles = computed(() => {
  const keyword = search.value.trim().toLowerCase();

  if (!keyword) {
    return props.articles.data;
  }

  return props.articles.data.filter(
    (article) =>
      article.judul?.toLowerCase().includes(keyword) || article.konten?.toLowerCase().includes(keyword),
  );
});

// Filter kategori ikut ditulis ke URL supaya tautan ke halaman yang sama bisa
// dibagikan dan tombol "kembali" memuat filter yang sama.
watch(filterKategori, (value) => {
  router.get(
    '/articles',
    value ? { kategori: value } : {},
    { preserveState: true, preserveScroll: true, replace: true },
  );
});

function openCreate() {
  editingArticle.value = null;
  form.reset();
  form.kategori = props.categories[0] ?? FALLBACK_CATEGORIES[0];
  showForm.value = true;
}

function openEdit(article) {
  editingArticle.value = article;
  form.judul = article.judul;
  form.konten = article.konten;
  form.kategori = article.kategori;
  form.is_published = article.is_published;
  form.image = null;
  showForm.value = true;
}

function reset() {
  showForm.value = false;
  editingArticle.value = null;
  form.reset();
}

function submit() {
  // Berkas kosong berarti "tidak mengunggah apa pun". Mengirim `null` akan
  // menghapus gambar lama, karena ArticleController selalu menyimpan ulang
  // field `image` setiap kali artikel disunting.
  if (!form.image) {
    delete form.image;
  }

  const id = editingArticle.value?.id;
  const options = { onSuccess: reset };

  if (id) {
    form.patch(`/articles/${id}`, options);
  } else {
    form.post('/articles', options);
  }
}

function destroy(article) {
  if (!confirm(`Hapus artikel "${article.judul}"?`)) {
    return;
  }

  router.delete(`/articles/${article.id}`);
}

function formatDate(value) {
  if (!value) {
    return '-';
  }

  return new Date(value).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}
</script>