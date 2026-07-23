<script setup>
/**
 * CustomSelect — wrapper el-select.
 */
import { ref, useSlots } from 'vue'

defineOptions({ name: 'CustomSelect', inheritAttrs: false })

const model = defineModel({ default: undefined })
const selectRef = ref(null)
const slots = useSlots()

defineExpose({
  focus: (...args) => selectRef.value?.focus?.(...args),
  blur: (...args) => selectRef.value?.blur?.(...args),
})
</script>

<template>
  <el-select ref="selectRef" v-model="model" v-bind="$attrs">
    <template v-for="(_, name) in slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}" />
    </template>
  </el-select>
</template>
