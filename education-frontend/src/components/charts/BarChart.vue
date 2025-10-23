<template>
  <div class="bar-chart" :class="{ 'horizontal-scroll': scroll }">
    <div class="bars-wrapper" :style="{ gap: gap + 'px' }">
      <div
        v-for="(item, idx) in internalData"
        :key="idx"
        class="bar-item"
        :style="{ minWidth: barWidth + 'px', width: barWidth + 'px' }"
        :title="item.label + ': ' + item.value"
      >
        <div class="bar" :style="barStyle(item)">
          <span class="bar-value" v-if="showValues">{{ item.value }}</span>
        </div>
        <div class="bar-label" :title="item.label">{{ compactLabel(item.label) }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: { type: Array, default: () => [] }, // [{label, value}]
  maxValue: { type: Number, default: null },
  height: { type: Number, default: 120 },
  barColor: { type: String, default: '#4f46e5' },
  showValues: { type: Boolean, default: true },
  scroll: { type: Boolean, default: true },
  minBarHeight: { type: Number, default: 4 },
  barWidth: { type: Number, default: 16 },
  gap: { type: Number, default: 4 }
});

const internalData = computed(() => props.data || []);

const resolvedMax = computed(() => {
  if (props.maxValue !== null) return props.maxValue;
  const max = Math.max(...internalData.value.map(d => d.value), 0);
  return max === 0 ? 1 : max;
});

function barStyle(item) {
  const ratio = item.value / resolvedMax.value;
  const h = Math.max(props.height * ratio, item.value > 0 ? props.minBarHeight : 0);
  return {
    height: h + 'px',
    background: `linear-gradient(180deg, ${props.barColor}, ${shadeColor(props.barColor, -20)})`
  };
}

function compactLabel(label) {
  if (!label) return '';
  // For YYYY-MM keep MM
  if (/^\d{4}-\d{2}$/.test(label)) return label.slice(5);
  return label.toString();
}

function shadeColor(color, percent) {
  // only handles hex #rrggbb
  if (!color.startsWith('#') || (color.length !== 7)) return color;
  const num = parseInt(color.slice(1), 16);
  let r = (num >> 16) + percent;
  let g = ((num >> 8) & 0x00FF) + percent;
  let b = (num & 0x0000FF) + percent;
  r = Math.min(255, Math.max(0, r));
  g = Math.min(255, Math.max(0, g));
  b = Math.min(255, Math.max(0, b));
  return '#' + (r << 16 | g << 8 | b).toString(16).padStart(6, '0');
}
</script>

<style scoped>
.bar-chart { width: 100%; }
.bars-wrapper { display: flex; align-items: flex-end; gap: 8px; }
.horizontal-scroll .bars-wrapper { overflow-x: auto; }
.bar-item { flex: 0 0 auto; display:flex; flex-direction:column; align-items:center; }
.bar { width: 100%; border-radius:4px 4px 0 0; position:relative; display:flex; justify-content:center; align-items:flex-end; transition:height .3s; }
.bar-value { position:absolute; top:-14px; font-size: .55rem; font-weight:600; color:#1e293b; background:#f1f5f9; padding:1px 3px; border-radius:3px; line-height:1; box-shadow:0 1px 2px rgba(0,0,0,.1); }
.bar-label { margin-top:2px; font-size:.5rem; font-weight:600; color:#475569; text-align:center; white-space:nowrap; letter-spacing:.5px; }
</style>
