<template>
  <div class="bulk-action-bar">
    <CustomTooltip
      v-for="action in actions"
      :key="action.key"
      :content="action.tooltip || action.label"
      placement="top"
    >
      <span class="btn-wrap">
        <el-badge
          :value="action.badge || 0"
          :hidden="!(action.badge > 0)"
          :max="99"
          :type="action.badgeType || action.type || 'info'"
          class="action-badge"
        >
          <CustomButton
            :type="action.type || 'default'"
            :disabled="action.disabled ?? !(action.badge > 0)"
            :loading="!!action.loading"
            @click="$emit('action', action.key)"
          >
            {{ action.label }}
          </CustomButton>
        </el-badge>
      </span>
    </CustomTooltip>
    <slot />
  </div>
</template>

<script setup>
/**
 * Thanh nút hành động hàng loạt (badge góc trên phải từng nút).
 * Không dùng plain — nút solid theo type.
 */
import { CustomButton, CustomTooltip } from '@/components/element'

defineProps({
  /**
   * @type {Array<{
   *   key: string,
   *   label: string,
   *   type?: string,
   *   badge?: number,
   *   badgeType?: string,
   *   tooltip?: string,
   *   disabled?: boolean,
   *   loading?: boolean,
   * }>}
   */
  actions: {
    type: Array,
    default: () => [],
  },
})

defineEmits(['action'])
</script>

<style scoped lang="scss">
.bulk-action-bar {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 12px;
}

.btn-wrap {
  display: inline-flex;
}

.action-badge {
  :deep(.el-badge__content) {
    transform: translateY(-50%) translateX(50%);
  }
}
</style>
