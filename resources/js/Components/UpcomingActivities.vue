<template>
  <div class="rounded-2xl border-2 border-daun-400/40 bg-hutan-600 p-5 shadow-lg">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <h2 class="font-extrabold text-emas-400">Aktivitas mendatang</h2>
        <p class="text-xs font-medium text-krem-300">Latihan rutin &amp; kegiatan dalam 7 hari ke depan</p>
      </div>
      <Link
        href="/attendance"
        class="text-xs font-bold text-daun-400 hover:text-emas-400"
      >Lihat semua</Link>
    </div>

    <ReminderPermission v-if="!permissionResolved" @granted="requestPermission" />

    <div v-if="loading" class="space-y-3">
      <p v-for="n in 3" :key="n" class="h-14 animate-pulse rounded-xl bg-hutan-800"></p>
    </div>

    <div
      v-else-if="upcoming.length"
      class="space-y-3"
    >
      <div
        v-for="session in upcoming"
        :key="session.id"
        class="flex items-center justify-between rounded-xl border-2 border-daun-500/40 bg-hutan-800 p-3"
      >
        <div class="min-w-0">
          <p class="truncate text-sm font-bold text-krem-100">{{ session.nama }}</p>
          <p class="truncate text-xs text-krem-300">
            {{ formatDate(session.tanggal) }} • {{ session.lokasi || '-' }}
          </p>
        </div>

        <div class="flex items-center gap-2">
          <span
            :class="attendedClass(session.id)"
            class="rounded-full px-2 py-0.5 text-[10px] font-bold"
          >{{ attendanceLabel(session.id) }}</span>

          <button
            v-if="session.tanggal >= todayISO"
            @click="toggleReminder(session)"
            :title="isReminded(session.id) ? 'Batalkan pengingat' : 'Ingatkan 10 menit sebelumnya'"
            class="rounded-lg border-2 border-daun-500 px-2.5 py-1 text-[10px] font-bold transition"
            :class="
              isReminded(session.id)
                ? 'bg-daun-500/40 text-emas-400'
                : 'text-[#d4dc9a] hover:bg-daun-500/30 hover:text-emas-400'
            "
          >
            <BellIcon :filled="isReminded(session.id)" />
          </button>
        </div>
      </div>
    </div>

    <p v-else class="py-4 text-center text-sm font-medium text-krem-300">
      Tidak ada kegiatan latihan dalam 7 hari ke depan.
    </p>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  sessions: { type: Array, default: () => [] },
  attendedSessionIds: { type: Array, default: () => [] },
});

const today = new Date();
today.setHours(0, 0, 0, 0);
const todayISO = today.toISOString().slice(0, 10);

const loading = ref(false);
const permissionResolved = ref(typeof window !== 'undefined' && 'Notification' in window && Notification.permission !== 'default');

const sevenDaysAhead = computed(() => {
  const d = new Date(today);
  d.setDate(d.getDate() + 7);
  return d;
});

const upcoming = computed(() => {
  return props.sessions
    .filter((session) => {
      const d = new Date(session.tanggal);
      d.setHours(0, 0, 0, 0);
      return d >= today && d <= sevenDaysAhead.value;
    })
    .sort((a, b) => new Date(a.tanggal) - new Date(b.tanggal));
});

const REMINDER_KEY = 'attendance_reminders';
const reminders = ref([]);

function isReminded(id) {
  return reminders.value.includes(id);
}

function toggleReminder(session) {
  const idx = reminders.value.indexOf(session.id);
  if (idx >= 0) {
    reminders.value.splice(idx, 1);
  } else {
    reminders.value.push(session.id);
  }
  localStorage.setItem(REMINDER_KEY, JSON.stringify(reminders.value));
}

function attendedClass(id) {
  return props.attendedSessionIds?.includes(id)
    ? 'bg-[#166534] text-[#4ade80]'
    : 'bg-[#422006]/30 text-[#f59e0b]';
}

function attendanceLabel(id) {
  return props.attendedSessionIds?.includes(id) ? 'Hadir' : 'Belum';
}

function formatDate(value) {
  const d = new Date(value);
  const options = { weekday: 'short', day: 'numeric', month: 'short' };
  return d.toLocaleDateString('id-ID', options);
}

const BellIcon = {
  props: { filled: Boolean },
  template: `<svg v-if="filled" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3"><path fill-rule="evenodd" d="M8.707 2.293a1 1 0 0 1 1.414 0 l2.5 2.5a1 1 0 0 1-1.414 1.414L9 4.914V13a1 1 0 1 1-2 0V4.914L6.586 6.207a1 1 0 0 1-1.414-1.414l2.5-2.5z" clip-rule="evenodd"/></svg><svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20" stroke="currentColor" stroke-width="1.5" class="h-3 w-3"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h1.5a1.5 1.5 0 1 1 0 3h-14A1.5 1.5 0 0 1 4.5 17m7-1V9a3 3 0 1 1 6 0v1m-6 4a3 3 0 1 1 6 0v1m-6 0h6"/></svg>`,
};

const ReminderPermission = {
  emits: ['granted'],
  template:
    '<p class="mb-3 text-[11px] text-krem-300">Aktifkan notifikasi untuk pengingat kegiatan. <button @click="req" class="font-bold text-daun-400 hover:text-emas-400">Izinkan</button></p>',
  methods: {
    async req() {
      const result = await Notification.requestPermission();
      this.$emit('granted', result);
    },
  },
};

function requestPermission(result) {
  permissionResolved.value = result === 'granted';
}

let tick = null;

function checkDueReminders() {
  if (Notification.permission !== 'granted') {
    return;
  }
  const now = new Date();
  for (const session of props.sessions) {
    if (!reminders.value.includes(session.id)) {
      continue;
    }
    const sessionTime = new Date(session.tanggal);
    sessionTime.setHours(0, 0, 0, 0);
    const diffMs = sessionTime.getTime() - now.getTime();
    const diffMin = diffMs / (1000 * 60);
    if (diffMin > 0 && diffMin <= 10) {
      new Notification('Pengingat Latihan Rutin', {
        body: `${session.nama} (${formatDate(session.tanggal)}) akan dimulai dalam 10 menit.`,
        icon: '/images/Logo_Ambalan.png',
      });
      reminders.value = reminders.value.filter((id) => id !== session.id);
      localStorage.setItem(REMINDER_KEY, JSON.stringify(reminders.value));
    }
  }
}

onMounted(() => {
  const stored = localStorage.getItem(REMINDER_KEY);
  if (stored) {
    reminders.value = JSON.parse(stored);
  }
  loading.value = false;
  if (permissionResolved.value) {
    tick = setInterval(checkDueReminders, 60_000);
    checkDueReminders();
  }
});

onBeforeUnmount(() => {
  if (tick) {
    clearInterval(tick);
  }
});
</script>
