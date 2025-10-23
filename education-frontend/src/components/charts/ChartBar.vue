<template>
  <div class="chart-wrapper">
    <Bar
      v-if="chartData && chartData.datasets.length"
      :data="chartData"
      :options="computedOptions"
      :height="height"
    />
    <div v-else class="empty-chart">No data</div>
  </div>
</template>

<script setup>
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
  Title
} from 'chart.js'
import { Bar } from 'vue-chartjs'
import { computed } from 'vue'

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend, Title)

const props = defineProps({
  labels: { type: Array, default: () => [] },
  values: { type: Array, default: () => [] },
  datasetLabel: { type: String, default: 'Dataset' },
  height: { type: Number, default: 220 },
  color: { type: String, default: '#3b82f6' },
  backgroundOpacity: { type: Number, default: 0.25 },
  borderWidth: { type: Number, default: 1 },
  yMax: { type: Number, default: null },
  showLegend: { type: Boolean, default: true },
  showTitle: { type: Boolean, default: false },
  titleText: { type: String, default: '' }
})

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [
    {
      label: props.datasetLabel,
      data: props.values,
      backgroundColor: hexToRgba(props.color, props.backgroundOpacity),
      borderColor: props.color,
      borderWidth: props.borderWidth,
      borderRadius: 4,
      maxBarThickness: 42
    }
  ]
}))

const computedOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: props.showLegend, position: 'top', labels: { font: { size: 11 } } },
    title: { display: props.showTitle, text: props.titleText },
    tooltip: { enabled: true, callbacks: { label: ctx => `${ctx.parsed.y}` } }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { maxRotation: 0, minRotation: 0, font: { size: 10 } }
    },
    y: {
      beginAtZero: true,
      suggestedMax: props.yMax || undefined,
      ticks: { stepSize: 1, font: { size: 10 } }
    }
  }
}))

function hexToRgba(hex, alpha = 0.3) {
  if (!hex || !hex.startsWith('#')) return hex
  let h = hex.replace('#', '')
  if (h.length === 3) h = h.split('').map(c => c + c).join('')
  const bigint = parseInt(h, 16)
  const r = (bigint >> 16) & 255
  const g = (bigint >> 8) & 255
  const b = bigint & 255
  return `rgba(${r}, ${g}, ${b}, ${alpha})`
}
</script>

<style scoped>
.chart-wrapper { position: relative; width: 100%; }
.empty-chart { text-align:center; font-size:.75rem; padding:1rem; color:#64748b; }
</style>
