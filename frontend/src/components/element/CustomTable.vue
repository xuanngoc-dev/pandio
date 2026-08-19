<script setup>
/**
 * CustomTable — wrapper el-table.
 * Default slot phải là con trực tiếp của el-table để cột được đăng ký đúng.
 */
import { provide, ref, toRef } from 'vue'
import { TABLE_COLUMN_SETTINGS_KEY } from '@/composables/useTableColumns'

defineOptions({ name: 'CustomTable', inheritAttrs: false })

const props = defineProps({
  /** Giá trị trả về từ useTableColumns(...) — dùng để ghim cột trái/phải. */
  columnSettings: {
    type: Object,
    default: null,
  },
})

provide(TABLE_COLUMN_SETTINGS_KEY, toRef(props, 'columnSettings'))

const tableRef = ref(null)

defineExpose({
  clearSelection: (...args) => tableRef.value?.clearSelection?.(...args),
  getSelectionRows: (...args) => tableRef.value?.getSelectionRows?.(...args),
  toggleRowSelection: (...args) => tableRef.value?.toggleRowSelection?.(...args),
  setCurrentRow: (...args) => tableRef.value?.setCurrentRow?.(...args),
  clearSort: (...args) => tableRef.value?.clearSort?.(...args),
  clearFilter: (...args) => tableRef.value?.clearFilter?.(...args),
  doLayout: (...args) => tableRef.value?.doLayout?.(...args),
  sort: (...args) => tableRef.value?.sort?.(...args),
})
</script>

<template>
  <el-table
    ref="tableRef"
    border
    :key="columnSettings?.fixedSignature || undefined"
    v-bind="$attrs"
  >
    <slot />
    <template v-if="$slots.append" #append>
      <slot name="append" />
    </template>
    <template v-if="$slots.empty" #empty>
      <slot name="empty" />
    </template>
  </el-table>
</template>
