<script setup>
/**
 * CustomInput — wrapper el-input.
 * Chỉnh style / hành vi mặc định tại đây để áp dụng toàn app.
 */
import { ref, useSlots } from 'vue'

defineOptions({ name: 'CustomInput', inheritAttrs: false })

const model = defineModel({ default: undefined })

const inputRef = ref(null)
const slots = useSlots()

defineExpose({
  focus: (...args) => inputRef.value?.focus?.(...args),
  blur: (...args) => inputRef.value?.blur?.(...args),
  select: (...args) => inputRef.value?.select?.(...args),
  clear: (...args) => inputRef.value?.clear?.(...args),
})
</script>

<template>
  <el-input ref="inputRef" v-model="model" v-bind="$attrs">
    <template v-for="(_, name) in slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}" />
    </template>
  </el-input>
</template>
