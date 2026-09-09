<script setup>
/**
 * Grouped column chart — theo ApexCharts Basic Column demo:
 * https://apexcharts.com/javascript-chart-demos/column-charts/basic-column/
 * Cuộn ngang khi nhiều category (ChartHScroll).
 */
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import ChartHScroll from './ChartHScroll.vue'
import { seriesColors, useApexChart } from '@/composables/useApexChart'

defineOptions({ name: 'ChartColumnBasic' })

const props = defineProps({
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 350 },
  width: { type: [Number, String], default: '100%' },
  colors: { type: Array, default: () => [] },
  /** Formatter trục Y (giá trị) */
  yFormatter: { type: Function, default: null },
  tooltipFormatter: { type: Function, default: null },
  columnWidth: { type: String, default: '55%' },
  /** Độ rộng tối thiểu mỗi nhóm category (px) — vượt khung thì cuộn ngang */
  minCategoryWidth: { type: Number, default: 112 },
  showDataLabels: { type: Boolean, default: true },
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
        type: 'bar',
        toolbar: { show: false },
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: props.columnWidth,
          borderRadius: 5,
          borderRadiusApplication: 'end',
          dataLabels: {
            position: 'top',
          },
        },
      },
      dataLabels: {
        enabled: props.showDataLabels,
        formatter: formatValue,
        offsetY: -4,
        style: {
          fontSize: '11px',
          fontWeight: 600,
          colors: ['var(--el-text-color-primary, #303133)'],
        },
        background: { enabled: false },
        dropShadow: { enabled: false },
      },
      stroke: {
        show: true,
        width: 2,
        colors: ['transparent'],
      },
      fill: { opacity: 1 },
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
        padding: { left: 6, right: 8, top: 22, bottom: 0 },
      },
    },
    seriesColors(props.colors),
    props.options,
  )
})
</script>

<template>
  <div class="chart-column-basic">
    <ChartHScroll :categories="categories" :min-category-width="minCategoryWidth">
      <VueApexCharts
        :key="renderKey"
        type="bar"
        :height="height"
        :width="width"
        :options="chartOptions"
        :series="normalizedSeries"
      />
    </ChartHScroll>
  </div>
</template>

<style scoped lang="scss">
.chart-column-basic {
  width: 100%;
  min-width: 0;
}
</style>
