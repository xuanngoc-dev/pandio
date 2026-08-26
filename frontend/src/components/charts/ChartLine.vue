<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import ChartHScroll from './ChartHScroll.vue'
import { axisFormatters, dataLabelLayer, seriesColors, useApexChart } from '@/composables/useApexChart'

defineOptions({ name: 'ChartLine' })

const props = defineProps({
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 320 },
  width: { type: [Number, String], default: '100%' },
  colors: { type: Array, default: () => [] },
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
      chart: { type: 'line' },
      xaxis: {
        categories: props.categories,
        labels: {
          rotate: 0,
          hideOverlappingLabels: false,
          trim: false,
        },
      },
      stroke: { curve: 'smooth', width: 3 },
      markers: { size: 4, strokeWidth: 2, hover: { size: 6 } },
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
      type="line"
      :height="height"
      :width="width"
      :options="chartOptions"
      :series="series"
    />
  </ChartHScroll>
</template>
