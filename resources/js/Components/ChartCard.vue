<template>
  <canvas ref="canvasRef" :width="props.width" :height="props.height"></canvas>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import {
  Chart as ChartJS,
  BarController,
  LineController,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Title as ChartTitle,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';

ChartJS.register(BarController, LineController, CategoryScale, LinearScale, BarElement, LineElement, PointElement, ChartTitle, Tooltip, Legend, Filler);

const props = defineProps({
  type: { type: String, required: true },
  data: { type: Object, required: true },
  options: { type: Object },
  width: { type: Number, default: 300 },
  height: { type: Number, default: 200 },
});

const canvasRef = ref(null);
const chartInstance = ref(null);

onMounted(() => {
  draw();
});

watch(
  [() => props.data, () => props.options, () => props.type],
  () => {
    if (chartInstance.value) {
      chartInstance.value.destroy();
    }
    draw();
  },
  { deep: true }
);

function draw() {
  if (!canvasRef.value) {
    return;
  }

  const ctx = canvasRef.value.getContext('2d');
  chartInstance.value = new ChartJS(ctx, {
    type: props.type,
    data: props.data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: { color: '#d4dc9a', font: { size: 11 } },
        },
      },
      scales: {
        x: { ticks: { color: '#8fa06a', font: { size: 10 } }, grid: { color: 'rgba(111, 148, 53, 0.2)' } },
        y: { ticks: { color: '#8fa06a', font: { size: 10 } }, grid: { color: 'rgba(111, 148, 53, 0.2)' } },
      },
      ...props.options,
    },
  });
}
</script>
