<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import ChartHScroll from './ChartHScroll.vue'
import { axisFormatters, seriesColors, useApexChart } from '@/composables/useApexChart'

defineOptions({ name: 'ChartCombo' })

const props = defineProps({
  /** Series Apex: mỗi phần tử có thể khai `type: 'column' | 'line'` */
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 320 },
  width: { type: [Number, String], default: '100%' },
  colors: { type: Array, default: () => [] },
  yFormatter: { type: Function, default: null },
  yFormatterRight: { type: Function, default: null },
  tooltipFormatter: { type: Function, default: null },
  /** Bật trục Y phải cho series type line (index >= 1) */
  dualAxis: { type: Boolean, default: true },
  options: { type: Object, default: () => ({}) },
  minCategoryWidth: { type: Number, default: 88 },
  columnWidth: { type: String, default: '58%' },
})

const { chartKey, buildOptions } = useApexChart()
const renderKey = computed(() => `${chartKey.value}:${props.categories.join('|')}`)

const chartOptions = computed(() => {
  const leftFmt = props.yFormatter
  const rightFmt = props.yFormatterRight || props.yFormatter
  const tipFmt = props.tooltipFormatter

  const yaxis = props.dualAxis
    ? [
        {
          title: { text: undefined },
          labels: {
            formatter: (val) => (leftFmt ? leftFmt(val) : String(val)),
          },
        },
        {
          opposite: true,
          title: { text: undefined },
          labels: {
            formatter: (val) => (rightFmt ? rightFmt(val) : String(val)),
          },
        },
      ]
    : {
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
        width: props.series.map((s, i) => {
          const type = s.type || (i === 0 ? 'column' : 'line')
          return type === 'line' ? 3 : 0
        }),
        curve: 'smooth',
      },
      plotOptions: {
        bar: {
          borderRadius: 5,
          columnWidth: props.columnWidth,
        },
      },
      markers: { size: 4, strokeWidth: 2, hover: { size: 6 } },
      dataLabels: { enabled: false },
      legend: { show: true },
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: (val, opts) => {
            if (tipFmt) return tipFmt(val, opts)
            const seriesIndex = opts?.seriesIndex ?? 0
            const series = props.series[seriesIndex]
            const type = series?.type || (seriesIndex === 0 ? 'column' : 'line')
            const fmt = type === 'line' ? rightFmt : leftFmt
            return fmt ? fmt(val) : String(val)
          },
        },
      },
    },
    seriesColors(props.colors),
    props.dualAxis ? {} : axisFormatters(props.yFormatter, props.tooltipFormatter),
    props.options,
  )
})

const normalizedSeries = computed(() => {
  if (!props.dualAxis) {
    return props.series.map((s, i) => ({
      ...s,
      type: s.type || (i === 0 ? 'column' : 'line'),
    }))
  }
  return props.series.map((s, i) => {
    const type = s.type || (i === 0 ? 'column' : 'line')
    return {
      ...s,
      type,
      yAxisIndex: s.yAxisIndex ?? (type === 'line' ? 1 : 0),
    }
  })
})
</script>

<template>
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
</template>
