<script setup>
/**
 * CustomSwitch — wrapper el-switch.
 * Mặc định giống el-switch + inline-prompt + active/inactive text.
 * Mobile (≤767px): size="small" trừ khi truyền size tường minh.
 *
 * active-text / inactive-text: truyền từ ngoài để ghi đè mặc định.
 */
import { useSlots } from 'vue'
import { useResponsiveComponentSize } from '@/composables/useResponsiveSize'

defineOptions({ name: 'CustomSwitch', inheritAttrs: false })

defineProps({
  activeText: { type: String, default: 'Bật' },
  inactiveText: { type: String, default: 'Tắt' },
})

const slots = useSlots()
const { resolvedSize } = useResponsiveComponentSize()
</script>

<template>
  <el-switch
    v-bind="$attrs"
    :size="resolvedSize"
    inline-prompt
    :active-text="activeText"
    :inactive-text="inactiveText"
  >
    <template v-for="(_, name) in slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}" />
    </template>
  </el-switch>
</template>
