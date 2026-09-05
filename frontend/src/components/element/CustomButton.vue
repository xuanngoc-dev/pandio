<script setup>
/**
 * CustomButton — wrapper el-button.
 * Kế thừa size từ BulkActionBar khi đang mobile (nếu chưa truyền size).
 */
import { computed, inject, unref, useAttrs, useSlots } from 'vue'
import { BULK_ACTION_BTN_SIZE_KEY } from '@/components/element/buttonContext'

defineOptions({ name: 'CustomButton', inheritAttrs: false })

const slots = useSlots()
const attrs = useAttrs()
const inheritedSize = inject(BULK_ACTION_BTN_SIZE_KEY, null)

const resolvedSize = computed(() => {
  if (attrs.size != null && attrs.size !== '') return attrs.size
  return unref(inheritedSize) || undefined
})
</script>

<template>
  <el-button v-bind="$attrs" :size="resolvedSize">
    <template v-for="(_, name) in slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}" />
    </template>
  </el-button>
</template>
