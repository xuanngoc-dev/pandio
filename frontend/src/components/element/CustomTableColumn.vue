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
    <template v-for="(_, name) in slots" #[name]="scope">
      <slot :name="name" v-bind="scope || {}" />
    </template>
  </el-table-column>
</template>
