<script setup>
/**
 * CustomTableColumn — wrapper el-table-column.
 * Tự gán `fixed` theo cấu hình cột (nếu bảng truyền `column-settings`).
 */
import { computed, inject, unref, useAttrs, useSlots } from 'vue'
import { FIXED_COL, TABLE_COLUMN_SETTINGS_KEY } from '@/composables/useTableColumns'

defineOptions({ name: 'CustomTableColumn', inheritAttrs: false })

const props = defineProps({
  /** Khóa cột trong useTableColumns, khi không suy ra được từ type/prop/label. */
  configKey: {
    type: String,
    default: '',
  },
})

const slots = useSlots()
const attrs = useAttrs()
const settingsRef = inject(TABLE_COLUMN_SETTINGS_KEY, null)

function resolveConfigKey() {
  if (props.configKey) return props.configKey
  if (attrs.type === 'selection') return FIXED_COL.selection
  if (attrs.label === 'STT' || attrs.label === 'Số thứ tự') return FIXED_COL.stt
  if (attrs.label === 'Thao tác') return FIXED_COL.actions
  return attrs.prop || attrs.label || null
}

const resolvedFixed = computed(() => {
  const settings = unref(settingsRef)
  const key = resolveConfigKey()
  if (settings?.columnFixed && key && settings.isPinColumn?.(key)) {
    return settings.columnFixed(key) || false
  }
  return attrs.fixed
})
</script>

<template>
  <el-table-column v-bind="$attrs" :fixed="resolvedFixed">
    <template v-for="(_, name) in slots" #[name]="scope">
      <slot :name="name" v-bind="scope || {}" />
    </template>
  </el-table-column>
</template>
