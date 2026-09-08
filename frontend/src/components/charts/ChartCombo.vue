<script setup>
import { computed, ref, watch } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import ChartHScroll from './ChartHScroll.vue'
import { axisFormatters, seriesColors, useApexChart } from '@/composables/useApexChart'

defineOptions({ name: 'ChartCombo' })

const DEFAULT_COLORS = ['#409eff', '#67c23a', '#e6a23c', '#f56c6c', '#9b59b6', '#36cfc9']

const props = defineProps({
  /** Series Apex: mỗi phần tử có thể khai `type: 'column' | 'line'` */
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 320 },
  width: { type: [Number, String], default: '100%' },
  colors: { type: Array, default: () => [] },
  /** Formatter trục trái — mặc định gắn series `line` (số lượng) */
  yFormatter: { type: Function, default: null },
  /** Formatter trục phải — mặc định gắn series `column` (doanh thu) */
  yFormatterRight: { type: Function, default: null },
  tooltipFormatter: { type: Function, default: null },
  leftAxisTitle: { type: String, default: '' },
  rightAxisTitle: { type: String, default: '' },
  /**
   * Multiple Y-Axis (ApexCharts):
   * - `line` → trục trái (yFormatter)
   * - `column` → trục phải (yFormatterRight)
   * Nhiều series cùng type dùng chung scale qua `seriesName`.
   */
  dualAxis: { type: Boolean, default: true },
  options: { type: Object, default: () => ({}) },
  minCategoryWidth: { type: Number, default: 88 },
  columnWidth: { type: String, default: '58%' },
})

const chartRef = ref(null)
/** Tên series đang ẩn (click legend để toggle) */
const hiddenNames = ref([])

const { chartKey, buildOptions } = useApexChart()
const renderKey = computed(() => `${chartKey.value}:${props.categories.join('|')}`)

const resolvedColors = computed(() =>
  props.colors?.length ? props.colors : DEFAULT_COLORS,
)

const legendItems = computed(() =>
  props.series.map((s, i) => {
    const name = s.name || `Series ${i + 1}`
    return {
      name,
      color: resolvedColors.value[i % resolvedColors.value.length],
      hidden: hiddenNames.value.includes(name),
    }
  }),
)

watch(
  () => props.series.map((s, i) => s.name || `Series ${i + 1}`).join('\0'),
  () => {
    hiddenNames.value = []
  },
)

watch(renderKey, () => {
  hiddenNames.value = []
})

function toggleLegend(name) {
  const chart = chartRef.value
  if (!chart || !name) return

  if (typeof chart.toggleSeries === 'function') {
    chart.toggleSeries(name)
  } else if (typeof chart.hideSeries === 'function' && typeof chart.showSeries === 'function') {
    if (hiddenNames.value.includes(name)) chart.showSeries(name)
    else chart.hideSeries(name)
  }

  if (hiddenNames.value.includes(name)) {
    hiddenNames.value = hiddenNames.value.filter((n) => n !== name)
  } else {
    hiddenNames.value = [...hiddenNames.value, name]
  }
}

function seriesType(s, index) {
  return s.type || (index === 0 ? 'column' : 'line')
}

function seriesNameOf(s, index) {
  return s.name || `Series ${index + 1}`
}

/**
 * Multiple Y-Axis theo chuẩn ApexCharts:
 * mỗi series 1 entry yaxis; series cùng type share scale qua seriesName.
 * - line → trái (số HĐ)
 * - column → phải (doanh thu)
 */
function buildMultipleYaxis(seriesList, leftFmt, rightFmt) {
  const primaryLineIdx = seriesList.findIndex((s, i) => seriesType(s, i) === 'line')
  const primaryColumnIdx = seriesList.findIndex((s, i) => seriesType(s, i) === 'column')
  const primaryLineName =
    primaryLineIdx >= 0 ? seriesNameOf(seriesList[primaryLineIdx], primaryLineIdx) : null
  const primaryColumnName =
    primaryColumnIdx >= 0
      ? seriesNameOf(seriesList[primaryColumnIdx], primaryColumnIdx)
      : null

  const colors = resolvedColors.value
  const leftColor =
    primaryLineIdx >= 0 ? colors[primaryLineIdx % colors.length] : '#67c23a'
  const rightColor =
    primaryColumnIdx >= 0 ? colors[primaryColumnIdx % colors.length] : '#409eff'

  return seriesList.map((s, i) => {
    const type = seriesType(s, i)
    const name = seriesNameOf(s, i)
    const isLine = type === 'line'
    const primaryName = isLine ? primaryLineName : primaryColumnName
    const isPrimary = name === primaryName
    const fmt = isLine ? leftFmt : rightFmt
    const color = isLine ? leftColor : rightColor
    const titleText = isLine ? props.leftAxisTitle : props.rightAxisTitle

    return {
      seriesName: primaryName || name,
      show: isPrimary,
      showAlways: false,
      opposite: !isLine,
      min: 0,
      forceNiceScale: true,
      tickAmount: 5,
      axisTicks: {
        show: isPrimary,
        color,
      },
      axisBorder: {
        show: isPrimary,
        color,
      },
      labels: {
        show: isPrimary,
        style: { colors: [color], fontSize: '12px' },
        formatter: (val) => {
          const n = Number(val)
          if (!Number.isFinite(n)) return ''
          if (fmt) return fmt(isLine ? Math.round(n) : n)
          return isLine ? String(Math.round(n)) : String(n)
        },
      },
      title: {
        text: isPrimary && titleText ? titleText : undefined,
        style: { color, fontSize: '12px', fontWeight: 500 },
      },
      tooltip: { enabled: false },
    }
  })
}

const chartOptions = computed(() => {
  const leftFmt = props.yFormatter
  const rightFmt = props.yFormatterRight || props.yFormatter
  const tipFmt = props.tooltipFormatter

  const yaxis = props.dualAxis
    ? buildMultipleYaxis(props.series, leftFmt, rightFmt)
    : {
        min: 0,
        labels: {
          formatter: (val) => (leftFmt ? leftFmt(val) : String(val)),
        },
      }

  return buildOptions(
    {
      chart: { type: 'line', stacked: false },
      xaxis: {
        categories: props.categories,
        tickPlacement: 'on',
        labels: {
          rotate: 0,
          hideOverlappingLabels: false,
          trim: false,
        },
      },
      yaxis,
      stroke: {
        width: props.series.map((s, i) => (seriesType(s, i) === 'line' ? 3 : 0)),
        curve: 'smooth',
      },
      plotOptions: {
        bar: {
          borderRadius: 5,
          columnWidth: props.columnWidth,
        },
      },
      markers: {
        size: props.series.map((s, i) => (seriesType(s, i) === 'line' ? 4 : 0)),
        strokeWidth: 2,
        hover: { size: 6 },
      },
      dataLabels: { enabled: false },
      legend: { show: false },
      grid: {
        padding: {
          top: 8,
          left: 8,
          right: 8,
          bottom: 0,
        },
      },
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: (val, opts) => {
            if (tipFmt) return tipFmt(val, opts)
            const seriesIndex = opts?.seriesIndex ?? 0
            const series = props.series[seriesIndex]
            const type = seriesType(series || {}, seriesIndex)
            const fmt = type === 'line' ? leftFmt : rightFmt
            const n = Number(val)
            if (!Number.isFinite(n)) return ''
            if (fmt) return fmt(type === 'line' ? Math.round(n) : n)
            return String(n)
          },
        },
      },
    },
    seriesColors(props.colors),
    props.dualAxis ? {} : axisFormatters(props.yFormatter, props.tooltipFormatter),
    props.options,
  )
})

const normalizedSeries = computed(() =>
  props.series.map((s, i) => ({
    ...s,
    type: seriesType(s, i),
  })),
)
</script>

<template>
  <div class="chart-combo">
    <ChartHScroll :categories="categories" :min-category-width="minCategoryWidth">
      <VueApexCharts
        ref="chartRef"
        :key="renderKey"
        type="line"
        :height="height"
        :width="width"
        :options="chartOptions"
        :series="normalizedSeries"
      />
    </ChartHScroll>

    <div v-if="legendItems.length" class="chart-combo__legend" role="list">
      <button
        v-for="item in legendItems"
        :key="item.name"
        type="button"
        class="chart-combo__legend-item"
        :class="{ 'is-hidden': item.hidden }"
        :aria-pressed="!item.hidden"
        :title="item.hidden ? `Hiện ${item.name}` : `Ẩn ${item.name}`"
        @click="toggleLegend(item.name)"
      >
        <span class="chart-combo__legend-dot" :style="{ background: item.color }" />
        <span class="chart-combo__legend-text">{{ item.name }}</span>
      </button>
    </div>
  </div>
</template>

<style scoped lang="scss">
.chart-combo {
  width: 100%;
  min-width: 0;
}

.chart-combo__legend {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  justify-content: center;
  gap: 16px;
  width: 100%;
  padding: 8px 4px 0;
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-width: thin;
  white-space: nowrap;
}

.chart-combo__legend-item {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  gap: 6px;
  margin: 0;
  padding: 2px 0;
  border: 0;
  background: transparent;
  cursor: pointer;
  white-space: nowrap;
  user-select: none;
  opacity: 1;
  transition: opacity 0.15s ease;

  &:hover {
    opacity: 0.85;
  }

  &.is-hidden {
    opacity: 0.38;

    .chart-combo__legend-text {
      text-decoration: line-through;
    }
  }
}

.chart-combo__legend-dot {
  flex-shrink: 0;
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.chart-combo__legend-text {
  font-size: 12px;
  font-weight: 500;
  line-height: 1.2;
  color: var(--el-text-color-regular);
  white-space: nowrap;
}
</style>
