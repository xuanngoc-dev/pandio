<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import ChartHScroll from './ChartHScroll.vue'
import { axisFormatters, dataLabelLayer, seriesColors, useApexChart } from '@/composables/useApexChart'

defineOptions({ name: 'ChartArea' })

const props = defineProps({
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 320 },
  width: { type: [Number, String], default: '100%' },
  colors: { type: Array, default: () => [] },
  stacked: { type: Boolean, default: false },
  yFormatter: { type: Function, default: null },
  tooltipFormatter: { type: Function, default: null },
  options: { type: Object, default: () => ({}) },
  minCategoryWidth: { type: Number, default: 72 },
})

const { chartKey, buildOptions } = useApexChart()
const renderKey = computed(() => `${chartKey.value}:${props.categories.join('|')}`)

const chartOptions = computed(() =>
  buildOptions(
    {
      chart: { type: 'area', stacked: props.stacked },
      xaxis: {
        categories: props.categories,
        labels: {
          rotate: 0,
          hideOverlappingLabels: false,
          trim: false,
        },
      },
      stroke: { curve: 'smooth', width: 2.5 },
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.42,
          opacityTo: 0.04,
          stops: [0, 90, 100],
        },
      },
      markers: { size: 0, hover: { size: 5 } },
    },
    seriesColors(props.colors),
    axisFormatters(props.yFormatter, props.tooltipFormatter),
    dataLabelLayer(props.yFormatter, {
      dataLabels: {
        offsetY: -6,
        background: {
          enabled: true,
          borderRadius: 4,
          padding: 3,
          opacity: 0.88,
          borderWidth: 0,
          foreColor: '#fff',
        },
      },
      grid: { padding: { top: 16 } },
    }),
    props.options,
  ),
)
</script>

<template>
  <ChartHScroll :categories="categories" :min-category-width="minCategoryWidth">
    <VueApexCharts
      :key="renderKey"
      type="area"
      :height="height"
      :width="width"
      :options="chartOptions"
      :series="series"
    />
  </ChartHScroll>
</template>
