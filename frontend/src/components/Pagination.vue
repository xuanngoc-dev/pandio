<template>
  <div class="app-pagination" v-if="total > 0">
    <div class="app-pagination__info">
      Hiển thị
      <strong>{{ from }}–{{ to }}</strong>
      / {{ total }} bản ghi
    </div>
    <el-pagination
      :current-page="modelValue"
      :page-size="pageSize"
      :page-sizes="pageSizes"
      :total="total"
      :background="background"
      :layout="layout"
      :disabled="disabled"
      @update:current-page="onPageChange"
      @update:page-size="onSizeChange"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

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

const from = computed(() => {
  if (props.total <= 0) return 0
  return (props.modelValue - 1) * props.pageSize + 1
})

const to = computed(() => {
  return Math.min(props.modelValue * props.pageSize, props.total)
})

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
</style>
