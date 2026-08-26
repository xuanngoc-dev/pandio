<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { seriesColors, useApexChart } from '@/composables/useApexChart'

defineOptions({ name: 'ChartSparkline' })

const props = defineProps({
  /** Mảng số `[1,2,3]` hoặc series Apex `[{ name, data }]` */
  series: { type: Array, required: true },
  name: { type: String, default: '' },
  height: { type: [Number, String], default: 48 },
  width: { type: [Number, String], default: '100%' },
  colors: { type: Array, default: () => [] },
  type: { type: String, default: 'area' },
  options: { type: Object, default: () => ({}) },
})

const { chartKey, buildOptions } = useApexChart()

const normalizedSeries = computed(() => {
  if (!props.series.length) return [{ name: props.name, data: [] }]
  if (typeof props.series[0] === 'number') {
    return [{ name: props.name, data: props.series }]
  }
  return props.series
})

const chartOptions = computed(() =>
  buildOptions(
    {
      chart: {
        type: props.type,
        sparkline: { enabled: true },
        animations: { enabled: false },
      },
      stroke: { curve: 'smooth', width: 2 },
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.35,
          opacityTo: 0.02,
          stops: [0, 100],
        },
      },
      tooltip: { enabled: false },
      grid: { padding: { top: 4, bottom: 0, left: 0, right: 0 } },
    },
    seriesColors(props.colors),
    props.options,
  ),
)
</script>

<template>
  <VueApexCharts
    :key="chartKey"
    :type="type"
    :height="height"
    :width="width"
    :options="chartOptions"
    :series="normalizedSeries"
  />
</template>
