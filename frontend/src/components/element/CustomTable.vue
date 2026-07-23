<script setup>
/**
 * CustomTable — wrapper el-table.
 */
import { ref, useSlots } from 'vue'

defineOptions({ name: 'CustomTable', inheritAttrs: false })

const tableRef = ref(null)
const slots = useSlots()

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
  <el-table ref="tableRef" v-bind="$attrs">
    <template v-for="(_, name) in slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}" />
    </template>
  </el-table>
</template>
