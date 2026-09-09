<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import ChartHScroll from './ChartHScroll.vue'
import { axisFormatters, dataLabelLayer, seriesColors, useApexChart } from '@/composables/useApexChart'

defineOptions({ name: 'ChartColumn' })

const props = defineProps({
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 320 },
  width: { type: [Number, String], default: '100%' },
  colors: { type: Array, default: () => [] },
  stacked: { type: Boolean, default: false },
  distributed: { type: Boolean, default: false },
  yFormatter: { type: Function, default: null },
  tooltipFormatter: { type: Function, default: null },
  options: { type: Object, default: () => ({}) },
  /** Độ rộng tối thiểu mỗi cột (px) — vượt khung thì cuộn ngang */
  minCategoryWidth: { type: Number, default: 88 },
  columnWidth: { type: String, default: '68%' },
})

const rootRef = ref(null)
const chartRef = ref(null)
let resizeObserver
let resizeTimer

const { chartKey, buildOptions } = useApexChart()

/**
 * Chỉ remount khi theme / categories đổi.
 * Không gắn series vào key — remount liên tục + resize gây "Element not found".
 */
const renderKey = computed(() => `${chartKey.value}:${props.categories.join('|')}`)

/** Ép number — ApexCharts đôi khi bỏ cột nếu data là string. */
const normalizedSeries = computed(() =>
  props.series.map((s) => ({
    ...s,
    data: (s.data || []).map((v) => Number(v) || 0),
  })),
)

const yAxisScale = computed(() => {
  const values = normalizedSeries.value.flatMap((s) => s.data || [])
  const max = values.length ? Math.max(...values) : 0
  return {
    min: 0,
    forceNiceScale: true,
    ...(max <= 0 ? { max: 5 } : {}),
  }
})

const chartOptions = computed(() =>
  buildOptions(
    {
      chart: {
        type: 'bar',
        stacked: props.stacked,
        redrawOnParentResize: true,
        redrawOnWindowResize: true,
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
      yaxis: yAxisScale.value,
      stroke: props.distributed
        ? { show: false, width: 0 }
        : { show: true, width: 2, colors: ['transparent'] },
      plotOptions: {
        bar: {
          horizontal: false,
          borderRadius: 5,
          columnWidth: props.columnWidth,
          distributed: props.distributed,
          dataLabels: {
            position: props.stacked ? 'center' : 'top',
            hideOverflowingLabels: false,
            total: props.stacked
              ? {
                  enabled: true,
                  offsetY: -6,
                  style: { fontSize: '11px', fontWeight: 600 },
                  formatter: (val) =>
                    props.yFormatter ? props.yFormatter(val) : String(val),
                }
              : undefined,
          },
        },
      },
      legend: { show: !props.distributed },
      fill: { type: 'solid', opacity: 1 },
    },
    seriesColors(props.colors),
    axisFormatters(props.yFormatter, props.tooltipFormatter),
    dataLabelLayer(
      props.yFormatter,
      props.stacked
        ? {
            dataLabels: {
              offsetY: 0,
              style: { colors: ['#fff'] },
            },
            grid: { padding: { top: 12 } },
          }
        : {
            dataLabels: {
              offsetY: -4,
              formatter: (val) => {
                const n = Number(val)
                if (Number.isNaN(n)) return ''
                return props.yFormatter ? props.yFormatter(n) : String(n)
              },
            },
            grid: { padding: { top: 18 } },
          },
    ),
    props.options,
  ),
)

const frameStyle = computed(() => ({
  minHeight: typeof props.height === 'number' ? `${props.height}px` : String(props.height),
}))

function resizeChart() {
  clearTimeout(resizeTimer)
  resizeTimer = setTimeout(() => {
    const root = rootRef.value
    const chart = chartRef.value
    if (!root || !root.isConnected || !chart) return
    if (root.clientWidth <= 0) return
    try {
      if (typeof chart.resize === 'function') chart.resize()
    } catch {
      // ApexCharts throw "Element not found" nếu DOM đã unmount giữa chừng
    }
  }, 50)
}

onMounted(() => {
  if (typeof ResizeObserver === 'undefined' || !rootRef.value) return
  resizeObserver = new ResizeObserver(() => resizeChart())
  resizeObserver.observe(rootRef.value)
  resizeChart()
})

onBeforeUnmount(() => {
  clearTimeout(resizeTimer)
  resizeObserver?.disconnect()
  resizeObserver = null
})
</script>

<template>
  <div ref="rootRef" class="chart-column" :style="frameStyle">
    <ChartHScroll :categories="categories" :min-category-width="minCategoryWidth">
      <VueApexCharts
        :key="renderKey"
        ref="chartRef"
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
.chart-column {
  width: 100%;
  min-width: 0;
}
</style>
