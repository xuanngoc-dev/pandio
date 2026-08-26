<script setup>
import { computed } from 'vue'
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

const { chartKey, buildOptions } = useApexChart()
const renderKey = computed(() => `${chartKey.value}:${props.categories.join('|')}`)

const chartOptions = computed(() =>
  buildOptions(
    {
      chart: { type: 'bar', stacked: props.stacked },
      xaxis: {
        categories: props.categories,
        tickPlacement: 'on',
        labels: {
          rotate: 0,
          hideOverlappingLabels: false,
          trim: false,
        },
      },
      stroke: { show: true, width: 2, colors: ['transparent'] },
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
            dataLabels: { offsetY: -4 },
            grid: { padding: { top: 18 } },
          },
    ),
    props.options,
  ),
)
</script>

<template>
  <ChartHScroll :categories="categories" :min-category-width="minCategoryWidth">
    <VueApexCharts
      :key="renderKey"
      type="bar"
      :height="height"
      :width="width"
      :options="chartOptions"
      :series="series"
    />
  </ChartHScroll>
</template>
