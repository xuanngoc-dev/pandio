<script setup>
import { computed } from 'vue'

defineOptions({ name: 'ChartHScroll' })

const props = defineProps({
  categories: { type: Array, default: () => [] },
  /** Độ rộng tối thiểu cho mỗi cột / điểm trên trục X (px) */
  minCategoryWidth: { type: Number, default: 72 },
})

const innerStyle = computed(() => ({
  minWidth: `${Math.max(props.categories.length, 1) * props.minCategoryWidth}px`,
}))
</script>

<template>
  <div class="chart-h-scroll">
    <div class="chart-h-scroll__inner" :style="innerStyle">
      <slot />
    </div>
  </div>
</template>

<style scoped lang="scss">
.chart-h-scroll {
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-width: thin;
  scrollbar-color: var(--el-border-color-darker) transparent;
  line-height: 0;
  padding-bottom: 2px;

  &::-webkit-scrollbar {
    height: 6px;
  }

  &::-webkit-scrollbar-thumb {
    border-radius: 99px;
    background: var(--el-border-color-darker);
  }

  &::-webkit-scrollbar-track {
    background: transparent;
  }
}

.chart-h-scroll__inner {
  width: 100%;
  line-height: 0;

  :deep(.vue-apexcharts),
  :deep(.apexcharts-canvas),
  :deep(svg) {
    overflow: visible;
  }
}
</style>
