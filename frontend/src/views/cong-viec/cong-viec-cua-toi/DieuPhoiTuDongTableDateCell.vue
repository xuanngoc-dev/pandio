<template>
  <div class="dieu-phoi-table__editable-cell">
    <span class="dieu-phoi-table__date-cell">
      {{ field.display || '—' }}
      <CustomTooltip
        v-if="field.status"
        :content="field.status.tooltip"
        placement="top"
      >
        <CustomIcon
          class="dieu-phoi-table__deadline-icon"
          :class="field.status.late ? 'is-late' : 'is-ok'"
        >
          <WarningFilled v-if="field.status.late" />
          <CircleCheckFilled v-else />
        </CustomIcon>
      </CustomTooltip>
    </span>
    <CustomTooltip
      v-if="canEdit"
      :content="field.iso ? `Sửa ${field.label.toLowerCase()}` : `Thêm ${field.label.toLowerCase()}`"
      placement="top"
    >
      <CustomButton
        :type="field.iso ? 'warning' : 'primary'"
        circle
        size="small"
        :icon="field.iso ? Edit : Plus"
        :loading="loading"
        @click.stop="$emit('edit', field)"
      />
    </CustomTooltip>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CircleCheckFilled, Edit, Plus, WarningFilled } from '@element-plus/icons-vue'
import { CustomButton, CustomIcon, CustomTooltip } from '@/components/element'
import { buildSharedDateField } from '@/utils/dieuPhoiTuDongDisplay'

const props = defineProps({
  row: {
    type: Object,
    required: true,
  },
  fieldKey: {
    type: String,
    required: true,
  },
  status: {
    type: Object,
    default: null,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['edit'])

const field = computed(() => buildSharedDateField(props.row, props.fieldKey, props.status))
</script>

<style scoped lang="scss">
.dieu-phoi-table__editable-cell {
  display: inline-flex;
  align-items: center;
  justify-content: flex-start;
  gap: 6px;
  min-width: 0;
}

.dieu-phoi-table__date-cell {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  min-width: 0;
}

.dieu-phoi-table__deadline-icon {
  font-size: 14px;
  flex-shrink: 0;

  &.is-ok {
    color: var(--el-color-success);
  }

  &.is-late {
    color: var(--el-color-danger);
  }
}
</style>
