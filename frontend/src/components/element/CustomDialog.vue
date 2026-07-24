<script setup>
/**
 * CustomDialog — wrapper el-dialog.
 * Mặc định: destroy-on-close, append-to-body, width responsive.
 */
import { computed, useAttrs, useSlots } from 'vue'

defineOptions({ name: 'CustomDialog', inheritAttrs: false })

const visible = defineModel({ type: Boolean, default: false })

const props = defineProps({
  title: { type: String, default: '' },
  /** Chiều rộng dialog. VD: 780 | '780px' | '70%' */
  width: { type: [String, Number], default: 520 },
  /** Căn giữa title. */
  destroyOnClose: { type: Boolean, default: true },
  appendToBody: { type: Boolean, default: true },
  alignCenter: { type: Boolean, default: true },
})

const attrs = useAttrs()
const slots = useSlots()

const resolvedWidth = computed(() =>
  typeof props.width === 'number' ? `${props.width}px` : props.width
)

const fallthroughAttrs = computed(() => {
  const { class: _class, style: _style, ...rest } = attrs
  return rest
})

const dialogClass = computed(() => {
  const fromAttrs = attrs.class
  const list = ['custom-dialog']
  if (typeof fromAttrs === 'string' && fromAttrs) list.push(fromAttrs)
  else if (Array.isArray(fromAttrs)) list.push(...fromAttrs.filter(Boolean))
  return list
})
</script>

<template>
  <el-dialog
    v-model="visible"
    :title="title"
    :width="resolvedWidth"
    :align-center="alignCenter"
    :destroy-on-close="destroyOnClose"
    :append-to-body="appendToBody"
    :class="dialogClass"
    :style="{ '--custom-dialog-width': resolvedWidth }"
    v-bind="fallthroughAttrs"
  >
    <template v-for="(_, name) in slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}" />
    </template>
  </el-dialog>
</template>

<style>
.custom-dialog.el-dialog {
  width: min(var(--custom-dialog-width, 520px), calc(100vw - 32px)) !important;
  max-width: var(--custom-dialog-width, 520px);
}
</style>
