<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { seriesColors, useApexChart } from '@/composables/useApexChart'

defineOptions({ name: 'ChartDonut' })

const props = defineProps({
  series: { type: Array, required: true },
  labels: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 320 },
  width: { type: [Number, String], default: '100%' },
  colors: { type: Array, default: () => [] },
  totalLabel: { type: String, default: 'Tổng' },
  totalFormatter: { type: Function, default: null },
  tooltipFormatter: { type: Function, default: null },
  options: { type: Object, default: () => ({}) },
})

const { chartKey, buildOptions } = useApexChart()
const renderKey = computed(() => `${chartKey.value}:${props.labels.join('|')}`)

const chartOptions = computed(() =>
  buildOptions(
    {
      chart: { type: 'donut' },
      labels: props.labels,
      stroke: { width: 0 },
      legend: { position: 'bottom' },
      dataLabels: {
        enabled: true,
        formatter: (val, opts) => {
          const value = opts?.w?.config?.series?.[opts.seriesIndex]
          const rounded = Math.round(Number(val) || 0)
          if (value == null) return `${rounded}%`
          return `${value} (${rounded}%)`
        },
        style: { fontSize: '11px', fontWeight: 600 },
        dropShadow: { enabled: false },
      },
      plotOptions: {
        pie: {
          dataLabels: {
            minAngleToShowLabel: 8,
          },
          donut: {
            size: '64%',
            labels: {
              show: true,
              name: { fontSize: '13px' },
              value: {
                fontSize: '22px',
                fontWeight: 600,
                formatter: (val) =>
                  props.totalFormatter ? props.totalFormatter(Number(val)) : String(val),
              },
              total: {
                show: true,
                label: props.totalLabel,
                formatter: (w) => {
                  const total = w.globals.seriesTotals.reduce((sum, n) => sum + n, 0)
                  return props.totalFormatter ? props.totalFormatter(total) : String(total)
                },
              },
            },
          },
        },
      },
      tooltip: {
        y: {
          formatter: (val) =>
            props.tooltipFormatter ? props.tooltipFormatter(val) : String(val),
        },
      },
    },
    seriesColors(props.colors),
    props.options,
  ),
)
</script>

<template>
  <VueApexCharts
    :key="renderKey"
    type="donut"
    :height="height"
    :width="width"
    :options="chartOptions"
    :series="series"
  />
</template>
