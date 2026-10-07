import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import type { GuestHomeData, GuestStats, GalleryItem, Announcement } from '@/Types/guest';

export const useGuestStore = defineStore('guest', () => {
  const data = ref<GuestHomeData | null>(null);
  const stats = ref<GuestStats>({ members: 0, alumni: 0 });
  const announcements = ref<Announcement[]>([]);
  const gallery = ref<GalleryItem[]>([]);
  const sliderSlides = ref<GuestHomeData['sliderSlides']>([]);
  const lastFetched = ref<number | null>(null);
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  const TTL = 5 * 60 * 1000;

  const isStale = computed(() => {
    if (!lastFetched.value) return true;
    return Date.now() - lastFetched.value > TTL;
  });

  async function fetchGuestData(force = false) {
    if (!force && !isStale.value && data.value) {
      return data.value;
    }

    if (!force && typeof navigator !== 'undefined' && navigator.onLine === false && data.value) {
      return data.value;
    }

    isLoading.value = true;
    error.value = null;

    try {
      const response = await fetch('/v1/guest/home', {
        headers: {
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const json: GuestHomeData = await response.json();
      data.value = json;
      stats.value = json.stats;
      announcements.value = Array.isArray(json.announcements) ? json.announcements : [];
      gallery.value = Array.isArray(json.gallery) ? json.gallery : [];
      sliderSlides.value = Array.isArray(json.sliderSlides) ? json.sliderSlides : [];
      lastFetched.value = Date.now();

      return json;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch guest data';
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  function setFromProps(payload: {
    announcements?: Announcement[];
    gallery?: GalleryItem[];
    sliderSlides?: GuestHomeData['sliderSlides'];
    stats?: GuestStats;
  }) {
    if (payload.announcements) {
      announcements.value = payload.announcements;
    }
    if (payload.gallery) {
      gallery.value = payload.gallery;
    }
    if (payload.sliderSlides) {
      sliderSlides.value = payload.sliderSlides;
    }
    if (payload.stats) {
      stats.value = payload.stats;
    }
  }

  function updateStats(newStats: Partial<GuestStats>) {
    stats.value = { ...stats.value, ...newStats };
  }

  function clear() {
    data.value = null;
    stats.value = { members: 0, alumni: 0 };
    announcements.value = [];
    gallery.value = [];
    sliderSlides.value = [];
    lastFetched.value = null;
    error.value = null;
  }

  return {
    data,
    stats,
    announcements,
    gallery,
    sliderSlides,
    lastFetched,
    isLoading,
    error,
    isStale,
    fetchGuestData,
    updateStats,
    clear,
    setFromProps,
  };
});
