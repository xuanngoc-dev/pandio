<script setup>
/**
 * Horizontal bar chart — theo ApexCharts Basic Bar demo:
 * https://apexcharts.com/javascript-chart-demos/bar-charts/basic-bar/
 */
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { seriesColors, useApexChart } from '@/composables/useApexChart'

defineOptions({ name: 'ChartBarBasic' })

const props = defineProps({
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 350 },
  width: { type: [Number, String], default: '100%' },
  colors: { type: Array, default: () => [] },
  /** Mỗi category một màu (phù hợp trạng thái) */
  distributed: { type: Boolean, default: false },
  /** Formatter trục giá trị (xaxis khi horizontal) */
  xFormatter: { type: Function, default: null },
  tooltipFormatter: { type: Function, default: null },
  /** Hiện dataLabels trên thanh, kể cả giá trị 0 */
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
    if (typeof props.xFormatter === 'function') return props.xFormatter(n)
    return String(n)
  }

  const tooltipFn =
    typeof props.tooltipFormatter === 'function' ? props.tooltipFormatter : props.xFormatter

  return buildOptions(
    {
      chart: {
        type: 'bar',
        toolbar: { show: false },
      },
      plotOptions: {
        bar: {
          horizontal: true,
          borderRadius: 4,
          borderRadiusApplication: 'end',
          barHeight: '64%',
          distributed: props.distributed,
        },
      },
      dataLabels: {
        enabled: props.showDataLabels,
        formatter: formatValue,
        style: {
          fontSize: '11px',
          fontWeight: 600,
          colors: props.distributed ? undefined : ['#fff'],
        },
        dropShadow: { enabled: false },
      },
      stroke: { show: false, width: 0 },
      fill: { type: 'solid', opacity: 1 },
      legend: { show: !props.distributed },
      // Horizontal bar: categories nằm trên trục Y (Apex vẫn khai báo qua xaxis.categories)
      xaxis: {
        categories: props.categories,
        min: 0,
        forceNiceScale: true,
        ...(valueMax.value <= 0 ? { max: 5 } : {}),
        labels: {
          formatter: formatValue,
        },
      },
      yaxis: {
        labels: {
          maxWidth: 120,
        },
      },
      tooltip: {
        y: {
          formatter: (val) =>
            typeof tooltipFn === 'function' ? tooltipFn(val) : formatValue(val),
        },
      },
      grid: {
        padding: { left: 8, right: 16, top: 8, bottom: 0 },
      },
    },
    seriesColors(props.colors),
    props.options,
  )
})
</script>

<template>
  <div class="chart-bar-basic">
    <VueApexCharts
      :key="renderKey"
      type="bar"
      :height="height"
      :width="width"
      :options="chartOptions"
      :series="normalizedSeries"
    />
  </div>
</template>

<style scoped lang="scss">
.chart-bar-basic {
  width: 100%;
  min-width: 0;
}
</style>
