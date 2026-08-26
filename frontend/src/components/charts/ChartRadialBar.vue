<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { seriesColors, useApexChart } from '@/composables/useApexChart'

defineOptions({ name: 'ChartRadialBar' })

const props = defineProps({
  series: { type: Array, required: true },
  labels: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 320 },
  width: { type: [Number, String], default: '100%' },
  colors: { type: Array, default: () => [] },
  options: { type: Object, default: () => ({}) },
})

const { isDark, chartKey, buildOptions } = useApexChart()
const renderKey = computed(() => `${chartKey.value}:${props.labels.join('|')}`)

const chartOptions = computed(() =>
  buildOptions(
    {
      chart: { type: 'radialBar' },
      labels: props.labels,
      stroke: { lineCap: 'round' },
      legend: {
        show: true,
        formatter: (seriesName, opts) => {
          const value = opts?.w?.globals?.series?.[opts.seriesIndex]
          return value == null ? seriesName : `${seriesName}: ${value}%`
        },
      },
      plotOptions: {
        radialBar: {
          hollow: { size: props.series.length > 1 ? '28%' : '54%' },
          track: {
            background: isDark.value ? '#3a3c40' : '#ebeef5',
            strokeWidth: '100%',
          },
          dataLabels: {
            name: { fontSize: '13px' },
            value: {
              fontSize: '20px',
              fontWeight: 600,
              formatter: (val) => `${val}%`,
            },
            total: {
              show: true,
              label: 'TB',
              formatter: (w) => {
                const totals = w.globals.seriesTotals || []
                if (!totals.length) return '0%'
                const avg = Math.round(
                  totals.reduce((sum, n) => sum + n, 0) / totals.length,
                )
                return `${avg}%`
              },
            },
          },
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
    type="radialBar"
    :height="height"
    :width="width"
    :options="chartOptions"
    :series="series"
  />
</template>
