<script setup>
import { ref, watch } from 'vue'
import { formatInteger, parseIntegerInput } from '@/utils/number'
import CustomInput from './CustomInput.vue'

defineOptions({ name: 'MoneyInput', inheritAttrs: false })

const model = defineModel({ type: [Number, null], default: null })

const displayValue = ref('')

watch(
  () => model.value,
  (val) => {
    const formatted = formatInteger(val)
    if (formatted !== displayValue.value) {
      displayValue.value = formatted
    }
  },
  { immediate: true },
)

function onInput(val) {
  const parsed = parseIntegerInput(val)
  model.value = parsed
  displayValue.value = parsed == null ? '' : formatInteger(parsed)
}

function onBlur() {
  displayValue.value = formatInteger(model.value)
}
</script>

<template>
  <CustomInput
    :model-value="displayValue"
    inputmode="numeric"
    placeholder="0"
    v-bind="$attrs"
    @update:model-value="onInput"
    @blur="onBlur"
  />
</template>
