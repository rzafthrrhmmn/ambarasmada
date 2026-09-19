<template>
  <div class="rounded-2xl border-2 border-[#A7B92A]/40 bg-[#335233] p-4 shadow-lg">
    <div class="mb-3 flex items-center justify-between">
      <button
        @click="prevMonth"
        class="rounded-lg border-2 border-[#6F9435] p-1 text-xs text-[#d4dc9a] hover:bg-[#6F9435]/30 hover:text-[#EDD330]"
        aria-label="Bulan sebelumnya"
      >‹</button>
      <p class="text-center text-sm font-bold text-[#f0ead8]">{{ monthYear }}</p>
      <button
        @click="nextMonth"
        class="rounded-lg border-2 border-[#6F9435] p-1 text-xs text-[#d4dc9a] hover:bg-[#6F9435]/30 hover:text-[#EDD330]"
        aria-label="Bulan depan"
      >›</button>
    </div>

    <div class="grid grid-cols-7 gap-[2px] text-[10px] font-bold text-[#8fa06a]">
      <p class="text-center">Min</p>
      <p class="text-center">Sen</p>
      <p class="text-center">Sel</p>
      <p class="text-center">Rab</p>
      <p class="text-center">Kam</p>
      <p class="text-center">Jum</p>
      <p class="text-center">Sab</p>
    </div>

    <div class="grid grid-cols-7 gap-[2px]">
      <template v-for="n in firstDayOffset" :key="'offset-' + n">
        <div></div>
      </template>

      <button
        v-for="day in calendarDays"
        :key="day.toISOString()"
        @click="$emit('select-day', formatISO(day))"
        :class="dayClass(day)"
        class="aspect-square w-full rounded-lg border-2 text-center leading-tight text-sm font-medium transition"
      >
        {{ day.getDate() }}
        <span
          v-if="hasActivity(day)"
          class="mx-auto mt-0.5 block h-1 w-1 rounded-full bg-[#EDD330]"
        ></span>
      </button>
    </div>

    <p v-if="!hasAnyActivity" class="mt-3 text-center text-[11px] text-[#8fa06a]">
      Tidak ada kegiatan latihan pada bulan ini.
    </p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  sessions: { type: Array, default: () => [] },
});
const emit = defineEmits(['select-day']);

const current = ref(new Date());

const monthYear = computed(() =>
  current.value.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }),
);

const firstDayOffset = computed(() => {
  return new Date(current.value.getFullYear(), current.value.getMonth(), 1).getDay();
});

const daysInMonth = computed(() => {
  return new Date(current.value.getFullYear(), current.value.getMonth() + 1, 0).getDate();
});

const allDayStrings = computed(() =>
  props.sessions.map((s) => new Date(s.tanggal).toISOString().slice(0, 10)),
);

function hasActivity(day) {
  return allDayStrings.value.includes(formatISO(day));
}

const hasAnyActivity = computed(() => props.sessions.length > 0);

function prevMonth() {
  current.value = new Date(current.value.getFullYear(), current.value.getMonth() - 1, 1);
}

function nextMonth() {
  current.value = new Date(current.value.getFullYear(), current.value.getMonth() + 1, 1);
}

const calendarDays = computed(() => {
  const days = [];
  for (let d = 1; d <= daysInMonth.value; d++) {
    days.push(new Date(current.value.getFullYear(), current.value.getMonth(), d));
  }
  return days;
});

function formatISO(day) {
  const yy = day.getFullYear();
  const mm = String(day.getMonth() + 1).padStart(2, '0');
  const dd = String(day.getDate()).padStart(2, '0');
  return `${yy}-${mm}-${dd}`;
}

function dayClass(day) {
  const isToday =
    day.getDate() === new Date().getDate() &&
    day.getMonth() === new Date().getMonth() &&
    day.getFullYear() === new Date().getFullYear();
  const has = hasActivity(day);
  let cls = 'border-transparent text-[#d4dc9a] hover:bg-[#6F9435]/30';
  if (isToday) {
    cls = 'border-[#EDD330] bg-[#6F9435]/40 text-[#EDD330]';
  } else if (has) {
    cls = 'border-[#A7B92A]/60 bg-[#263D26] text-[#EDD330]';
  }
  return cls;
}
</script>
