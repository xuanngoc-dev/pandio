<script setup>
/**
 * CustomOption — wrapper el-option.
 */
import { useSlots } from 'vue'

defineOptions({ name: 'CustomOption', inheritAttrs: false })

const slots = useSlots()
</script>

<template>
  <el-option v-bind="$attrs">
    <template v-for="(_, name) in slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}" />
    </template>
  </el-option>
</template>
