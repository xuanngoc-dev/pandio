<script setup>
/**
 * Khung cuộn ngang cho ApexCharts khi nhiều category.
 * Legend (.apexcharts-legend) luôn neo theo viewport — không bị kéo theo khi scroll.
 */
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

defineOptions({ name: 'ChartHScroll' })

const props = defineProps({
  categories: { type: Array, default: () => [] },
  /** Độ rộng tối thiểu cho mỗi cột / điểm trên trục X (px) */
  minCategoryWidth: { type: Number, default: 72 },
})

const rootRef = ref(null)

const innerStyle = computed(() => ({
  minWidth: `${Math.max(props.categories.length, 1) * props.minCategoryWidth}px`,
}))

let resizeObserver
let mutationObserver
let syncTimer
let syncing = false

function syncStickyLegend() {
  const root = rootRef.value
  if (!root || syncing) return

  const legend = root.querySelector('.apexcharts-legend')
  if (!legend) return

  const viewportW = root.clientWidth
  if (viewportW <= 0) return

  const scrollLeft = root.scrollLeft
  syncing = true

  legend.style.setProperty('position', 'absolute', 'important')
  legend.style.setProperty('left', `${scrollLeft}px`, 'important')
  legend.style.setProperty('right', 'auto', 'important')
  legend.style.setProperty('width', `${viewportW}px`, 'important')
  legend.style.setProperty('max-width', `${viewportW}px`, 'important')
  legend.style.setProperty('margin-left', '0', 'important')
  legend.style.setProperty('margin-right', '0', 'important')
  legend.style.setProperty('transform', 'none', 'important')
  legend.style.setProperty('display', 'flex', 'important')
  legend.style.setProperty('flex-wrap', 'wrap', 'important')
  legend.style.setProperty('justify-content', 'center', 'important')
  legend.style.setProperty('align-items', 'center', 'important')
  legend.style.setProperty('box-sizing', 'border-box', 'important')
  legend.style.setProperty('pointer-events', 'auto', 'important')

  requestAnimationFrame(() => {
    syncing = false
  })
}

function scheduleSync() {
  if (syncing) return
  clearTimeout(syncTimer)
  syncTimer = setTimeout(() => {
    syncStickyLegend()
  }, 16)
}

onMounted(() => {
  const root = rootRef.value
  if (!root) return

  root.addEventListener('scroll', syncStickyLegend, { passive: true })

  if (typeof ResizeObserver !== 'undefined') {
    resizeObserver = new ResizeObserver(() => scheduleSync())
    resizeObserver.observe(root)
  }

  if (typeof MutationObserver !== 'undefined') {
    mutationObserver = new MutationObserver((mutations) => {
      if (syncing) return
      const relevant = mutations.some((m) => {
        if (m.type === 'childList') return true
        // Bỏ qua style do chính syncStickyLegend ghi
        if (m.type === 'attributes' && m.target?.classList?.contains('apexcharts-legend')) {
          return false
        }
        return m.type === 'attributes'
      })
      if (relevant) scheduleSync()
    })
    mutationObserver.observe(root, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class'],
    })
  }

  nextTick(() => scheduleSync())
})

onBeforeUnmount(() => {
  clearTimeout(syncTimer)
  rootRef.value?.removeEventListener('scroll', syncStickyLegend)
  resizeObserver?.disconnect()
  mutationObserver?.disconnect()
  resizeObserver = null
  mutationObserver = null
})

watch(
  () => [props.categories.length, props.minCategoryWidth],
  () => nextTick(() => scheduleSync()),
)
</script>

<template>
  <div ref="rootRef" class="chart-h-scroll">
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

  /* Fallback trước khi JS sync — legend canh giữa viewport */
  :deep(.apexcharts-legend) {
    display: flex !important;
    flex-wrap: wrap;
    justify-content: center !important;
    align-items: center;
    box-sizing: border-box;
  }

  :deep(.apexcharts-legend-series) {
    display: inline-flex !important;
    align-items: center;
  }

  :deep(.apexcharts-legend-text) {
    white-space: nowrap;
  }
}

.chart-h-scroll__inner {
  width: 100%;
  line-height: 0;
  position: relative;

  :deep(.vue-apexcharts),
  :deep(.apexcharts-canvas),
  :deep(svg) {
    overflow: visible;
  }

  /* Canvas cần relative để legend absolute neo theo scrollLeft */
  :deep(.apexcharts-canvas) {
    position: relative !important;
  }
}
</style>
