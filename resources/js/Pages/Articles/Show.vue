<template>
  <AppLayout>
    <article class="mx-auto max-w-4xl">
      <nav class="mb-5 flex items-center gap-1.5 text-xs text-[#8fa06a]" aria-label="Remah roti">
        <Link href="/articles" class="inline-flex items-center gap-1.5 font-semibold transition hover:text-[#EDD330]">
          <AppIcon name="arrowLeft" class="h-3.5 w-3.5" />
          Artikel &amp; Laporan
        </Link>
        <AppIcon name="chevronRight" class="h-3 w-3 shrink-0 text-[#8fa06a]/60" />
        <span class="truncate text-[#d4dc9a]">{{ article.kategori }}</span>
      </nav>

      <header class="card-accent">
        <span class="card-glow" aria-hidden="true" />

        <div class="relative flex flex-wrap items-center gap-2">
          <span class="badge-neutral">{{ article.kategori }}</span>
          <span class="badge" :class="article.is_published ? 'badge-approved' : 'badge-pending'">
            <AppIcon :name="article.is_published ? 'checkCircle' : 'note'" class="h-3.5 w-3.5" />
            {{ article.is_published ? 'Terbit' : 'Draf' }}
          </span>
          <span
            v-if="article.ambalan"
            class="badge border border-[#6F9435]/40 bg-[#263D26] text-[#d4dc9a]"
          >
            <AppIcon name="pin" class="h-3.5 w-3.5" />
            {{ article.ambalan.nama }}
          </span>
        </div>

        <h1 class="relative mt-3 text-2xl font-extrabold leading-tight text-[#f0ead8] sm:text-3xl">
          {{ article.judul }}
        </h1>

        <div class="relative mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-[#8fa06a]">
          <span class="inline-flex items-center gap-1.5">
            <AppIcon name="profile" class="h-3.5 w-3.5 shrink-0 text-[#A7B92B]" />
            {{ article.author?.name ?? 'Tanpa penulis' }}
          </span>
          <span class="inline-flex items-center gap-1.5">
            <AppIcon name="calendar" class="h-3.5 w-3.5 shrink-0 text-[#A7B92B]" />
            <time :datetime="article.created_at">{{ formatDate(article.created_at) }}</time>
          </span>
          <span class="inline-flex items-center gap-1.5">
            <AppIcon name="clock" class="h-3.5 w-3.5 shrink-0 text-[#A7B92B]" />
            {{ readingTime }} menit baca
          </span>
        </div>

        <div v-if="canManage" class="relative mt-5 flex flex-wrap gap-2 border-t border-[#6F9435]/30 pt-4">
          <Link href="/articles" class="btn-ghost btn-sm">
            <AppIcon name="articles" class="h-4 w-4" />
            Semua artikel
          </Link>
          <button v-if="canDelete" type="button" class="btn-danger btn-sm" @click="destroy">
            <AppIcon name="trash" class="h-4 w-4" />
            Hapus
          </button>
        </div>
      </header>

      <figure
        v-if="article.image_url"
        class="mt-5 overflow-hidden rounded-2xl border-2 border-[#6F9435]/40 bg-[#263D26]"
      >
        <img
          :src="article.image_url"
          :alt="article.judul"
          class="max-h-96 w-full object-cover"
          loading="lazy"
        />
      </figure>

      <div
        class="prose-artikel mt-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5 shadow-lg sm:p-7"
      >
        <p v-for="(paragraph, index) in paragraphs" :key="index">{{ paragraph }}</p>
      </div>

      <footer
        v-if="article.updated_at && article.updated_at !== article.created_at"
        class="mt-4 rounded-xl border border-dashed border-[#6F9435]/35 bg-[#263D26]/60 px-4 py-3 text-[11px] text-[#8fa06a]"
      >
        <span class="inline-flex items-center gap-1.5">
          <AppIcon name="note" class="h-3.5 w-3.5 shrink-0 text-[#A7B92B]" />
          Terakhir diperbarui {{ formatDate(article.updated_at) }}
        </span>
      </footer>

      <div class="mt-6 flex justify-center">
        <Link href="/articles" class="btn-ghost">
          <AppIcon name="arrowLeft" class="h-4 w-4" />
          Kembali ke daftar artikel
        </Link>
      </div>
    </article>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  article: { type: Object, required: true },
  canManage: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
});

/*
 * Konten artikel disimpan sebagai satu kolom teks, jadi pemecahannya per baris
 * kosong membuat halaman tetap enak dibaca tanpa harus memasang penyusun
 * markdown yang tidak ada di proyek ini.
 */
const paragraphs = computed(() =>
  String(props.article.konten ?? '')
    .split(/\n{2,}/)
    .map((bagian) => bagian.trim())
    .filter(Boolean),
);

const readingTime = computed(() => {
  const words = String(props.article.konten ?? '').trim().split(/\s+/).filter(Boolean).length;

  return Math.max(1, Math.round(words / 200));
});

function formatDate(value) {
  if (!value) {
    return '-';
  }

  return new Date(value).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

function destroy() {
  if (!confirm('Hapus artikel ini?')) {
    return;
  }

  router.delete(`/articles/${props.article.id}`);
}
</script>