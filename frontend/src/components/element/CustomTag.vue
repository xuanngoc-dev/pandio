<script setup>
/**
 * CustomTag — wrapper el-tag.
 */
import { useSlots } from 'vue'

defineOptions({ name: 'CustomTag', inheritAttrs: false })

const slots = useSlots()
</script>

<template>
  <el-tag v-bind="$attrs">
    <template v-for="(_, name) in slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}" />
    </template>
  </el-tag>
</template>
