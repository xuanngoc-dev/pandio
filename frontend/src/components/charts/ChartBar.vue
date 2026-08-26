<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { axisFormatters, dataLabelLayer, seriesColors, useApexChart } from '@/composables/useApexChart'

defineOptions({ name: 'ChartBar' })

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
})

const { chartKey, buildOptions } = useApexChart()
const renderKey = computed(() => `${chartKey.value}:${props.categories.join('|')}`)

const chartOptions = computed(() =>
  buildOptions(
    {
      chart: { type: 'bar', stacked: props.stacked },
      xaxis: { categories: props.categories },
      stroke: { show: false },
      plotOptions: {
        bar: {
          horizontal: true,
          borderRadius: 5,
          barHeight: props.stacked ? '68%' : '58%',
          distributed: props.distributed,
          dataLabels: { position: 'center' },
        },
      },
      legend: { show: !props.distributed },
      grid: { padding: { left: 4 } },
    },
    seriesColors(props.colors),
    axisFormatters(props.yFormatter, props.tooltipFormatter),
    dataLabelLayer(props.yFormatter, {
      dataLabels: {
        style: { colors: ['#fff'] },
      },
    }),
    props.options,
  ),
)
</script>

<template>
  <VueApexCharts
    :key="renderKey"
    type="bar"
    :height="height"
    :width="width"
    :options="chartOptions"
    :series="series"
  />
</template>
