<script setup>
/**
 * CustomTableColumn — wrapper el-table-column.
 */
import { useSlots } from 'vue'

defineOptions({ name: 'CustomTableColumn', inheritAttrs: false })

const slots = useSlots()
</script>

<template>
  <el-table-column v-bind="$attrs">
    <template v-for="(_, name) in slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}" />
    </template>
  </el-table-column>
</template>
