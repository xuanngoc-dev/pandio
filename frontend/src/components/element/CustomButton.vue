<script setup>
/**
 * CustomButton — wrapper el-button.
 */
import { useSlots } from 'vue'

defineOptions({ name: 'CustomButton', inheritAttrs: false })

const slots = useSlots()
</script>

<template>
  <el-button v-bind="$attrs">
    <template v-for="(_, name) in slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}" />
    </template>
  </el-button>
</template>
