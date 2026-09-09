<script setup>
/**
 * Basic line chart — theo ApexCharts Basic Line demo:
 * https://apexcharts.com/javascript-chart-demos/line-charts/basic-line/
 * Cuộn ngang khi nhiều category (ChartHScroll).
 */
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import ChartHScroll from './ChartHScroll.vue'
import { seriesColors, useApexChart } from '@/composables/useApexChart'

defineOptions({ name: 'ChartLineBasic' })

const props = defineProps({
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 350 },
  width: { type: [Number, String], default: '100%' },
  colors: { type: Array, default: () => [] },
  /** Formatter trục Y (giá trị) */
  yFormatter: { type: Function, default: null },
  tooltipFormatter: { type: Function, default: null },
  /** Độ rộng tối thiểu mỗi điểm category (px) — vượt khung thì cuộn ngang */
  minCategoryWidth: { type: Number, default: 56 },
  showDataLabels: { type: Boolean, default: false },
  curve: { type: String, default: 'straight' },
  options: { type: Object, default: () => ({}) },
})

const { chartKey, buildOptions } = useApexChart()
const renderKey = computed(() => `${chartKey.value}:${props.categories.join('|')}`)

const normalizedSeries = computed(() =>
  props.series.map((s) => ({
    ...s,
    data: (s.data || []).map((v) => Number(v) || 0),
  })),
)

const valueMax = computed(() => {
  const values = normalizedSeries.value.flatMap((s) => s.data || [])
  return values.length ? Math.max(...values) : 0
})

const chartOptions = computed(() => {
  const formatValue = (val) => {
    const n = Number(val)
    if (Number.isNaN(n)) return ''
    if (typeof props.yFormatter === 'function') return props.yFormatter(n)
    return String(n)
  }

  const tooltipFn =
    typeof props.tooltipFormatter === 'function' ? props.tooltipFormatter : props.yFormatter

  return buildOptions(
    {
      chart: {
        type: 'line',
        toolbar: { show: false },
        zoom: { enabled: false },
      },
      stroke: {
        curve: props.curve,
        width: 2,
      },
      markers: {
        size: 3,
        strokeWidth: 2,
        hover: { size: 5 },
      },
      dataLabels: {
        enabled: props.showDataLabels,
        formatter: formatValue,
        style: {
          fontSize: '11px',
          fontWeight: 600,
          colors: ['var(--el-text-color-primary, #303133)'],
        },
        background: { enabled: false },
        dropShadow: { enabled: false },
      },
      legend: {
        show: true,
        position: 'bottom',
      },
      xaxis: {
        categories: props.categories,
        tickPlacement: 'on',
        labels: {
          rotate: 0,
          hideOverlappingLabels: false,
          trim: false,
        },
      },
      yaxis: {
        min: 0,
        forceNiceScale: true,
        ...(valueMax.value <= 0 ? { max: 5 } : {}),
        labels: {
          formatter: formatValue,
        },
      },
      tooltip: {
        y: {
          formatter: (val) =>
            typeof tooltipFn === 'function' ? tooltipFn(val) : formatValue(val),
        },
      },
      grid: {
        padding: { left: 6, right: 8, top: 12, bottom: 0 },
      },
    },
    seriesColors(props.colors),
    props.options,
  )
})
</script>

<template>
  <div class="chart-line-basic">
    <ChartHScroll :categories="categories" :min-category-width="minCategoryWidth">
      <VueApexCharts
        :key="renderKey"
        type="line"
        :height="height"
        :width="width"
        :options="chartOptions"
        :series="normalizedSeries"
      />
    </ChartHScroll>
  </div>
</template>

<style scoped lang="scss">
.chart-line-basic {
  width: 100%;
  min-width: 0;
}
</style>
