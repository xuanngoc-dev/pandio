<template>
  <div
    v-if="total > 0"
    class="app-pagination"
    :class="{ 'is-mobile': isMobile }"
  >
    <div class="app-pagination__info">
      <template v-if="isMobile">
        <strong>{{ from }}–{{ to }}</strong> / {{ total }}
      </template>
      <template v-else>
        Hiển thị
        <strong>{{ from }}–{{ to }}</strong>
        / {{ total }} bản ghi
      </template>
    </div>
    <el-pagination
      :current-page="modelValue"
      :page-size="pageSize"
      :page-sizes="pageSizes"
      :total="total"
      :background="effectiveBackground"
      :size="paginationSize"
      :pager-count="pagerCount"
      :layout="effectiveLayout"
      :disabled="disabled"
      @update:current-page="onPageChange"
      @update:page-size="onSizeChange"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'

/** Khớp breakpoint mobile của MainLayout */
const MOBILE_BREAKPOINT = 992

const props = defineProps({
  /** Trang hiện tại (v-model) */
  modelValue: {
    type: Number,
    default: 1,
  },
  /** Số bản ghi mỗi trang (v-model:page-size) */
  pageSize: {
    type: Number,
    default: 10,
  },
  total: {
    type: Number,
    default: 0,
  },
  pageSizes: {
    type: Array,
    default: () => [10, 20, 50, 100],
  },
  layout: {
    type: String,
    default: 'sizes, prev, pager, next, jumper',
  },
  /** Layout riêng cho mobile — mặc định bỏ jumper cho gọn */
  mobileLayout: {
    type: String,
    default: 'sizes, prev, pager, next',
  },
  background: {
    type: Boolean,
    default: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'update:pageSize', 'change'])

const isMobile = ref(false)
let mediaQuery = null

const paginationSize = computed(() => (isMobile.value ? 'small' : 'default'))
const pagerCount = computed(() => (isMobile.value ? 5 : 7))
const effectiveLayout = computed(() =>
  isMobile.value ? props.mobileLayout : props.layout
)
const effectiveBackground = computed(() =>
  isMobile.value ? false : props.background
)

const from = computed(() => {
  if (props.total <= 0) return 0
  return (props.modelValue - 1) * props.pageSize + 1
})

const to = computed(() => {
  return Math.min(props.modelValue * props.pageSize, props.total)
})

function syncMobile(e) {
  isMobile.value = e.matches
}

function onPageChange(page) {
  emit('update:modelValue', page)
  emit('change', { page, pageSize: props.pageSize })
}

function onSizeChange(size) {
  emit('update:pageSize', size)
  // Đổi page size → về trang 1
  emit('update:modelValue', 1)
  emit('change', { page: 1, pageSize: size })
}

onMounted(() => {
  mediaQuery = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`)
  isMobile.value = mediaQuery.matches
  mediaQuery.addEventListener('change', syncMobile)
})

onUnmounted(() => {
  mediaQuery?.removeEventListener('change', syncMobile)
})
</script>

<style scoped>
.app-pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 16px;
}

.app-pagination__info {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.app-pagination__info strong {
  color: var(--el-text-color-primary);
  font-weight: 600;
}

.app-pagination.is-mobile {
  gap: 8px;
  margin-top: 8px;
}

.app-pagination.is-mobile .app-pagination__info {
  font-size: 12px;
}

.app-pagination.is-mobile :deep(.el-pagination) {
  flex-wrap: wrap;
  row-gap: 6px;
  justify-content: flex-end;
}
</style>
