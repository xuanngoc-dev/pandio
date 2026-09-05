<template>
  <div class="bulk-action-bar" :class="{ 'is-mobile': isMobile }">
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
            :size="buttonSize"
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
 * Mobile (≤767px): size="small", gap/padding gọn hơn.
 */
import { computed, onBeforeUnmount, onMounted, provide, ref } from 'vue'
import { CustomButton, CustomTooltip } from '@/components/element'
import { BULK_ACTION_BTN_SIZE_KEY } from '@/components/element/buttonContext'

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

const MOBILE_MQ = '(max-width: 767px)'
const isMobile = ref(false)
let mediaQuery = null

const buttonSize = computed(() => (isMobile.value ? 'small' : undefined))
provide(BULK_ACTION_BTN_SIZE_KEY, buttonSize)

function syncMobile() {
  isMobile.value = !!mediaQuery?.matches
}

onMounted(() => {
  mediaQuery = window.matchMedia(MOBILE_MQ)
  syncMobile()
  mediaQuery.addEventListener('change', syncMobile)
})

onBeforeUnmount(() => {
  mediaQuery?.removeEventListener('change', syncMobile)
  mediaQuery = null
})
</script>

<style scoped lang="scss">
.bulk-action-bar {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 12px;

  > * {
    margin: 0;
  }

  &.is-mobile {
    gap: 6px;

    :deep(.el-button) {
      margin: 0;
    }

    :deep(.el-button + .el-button) {
      margin-left: 0;
    }

    :deep(.el-badge__content) {
      font-size: 10px;
      height: 14px;
      line-height: 14px;
      padding: 0 4px;
    }
  }
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
