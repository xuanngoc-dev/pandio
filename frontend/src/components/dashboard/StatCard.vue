<script setup>
import { computed } from 'vue'
import { CaretBottom, CaretTop } from '@element-plus/icons-vue'

defineOptions({ name: 'StatCard' })

const props = defineProps({
  title: { type: String, required: true },
  value: { type: [String, Number], required: true },
  hint: { type: String, default: '' },
  change: { type: Number, default: null },
  changeLabel: { type: String, default: 'so với kỳ trước' },
  tone: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'success', 'warning', 'danger', 'info'].includes(value),
  },
})

const changeKind = computed(() => {
  if (props.change == null) return ''
  if (props.change > 0) return 'up'
  if (props.change < 0) return 'down'
  return 'flat'
})

const changeText = computed(() => {
  if (props.change == null) return ''
  const abs = Math.abs(props.change)
  const sign = props.change > 0 ? '+' : props.change < 0 ? '-' : ''
  return `${sign}${abs}%`
})
</script>

<template>
  <CustomCard shadow="hover" class="stat-card" :class="`is-${tone}`">
    <div class="stat-card__top">
      <div class="stat-card__icon">
        <slot name="icon" />
      </div>
      <div class="stat-card__meta">
        <p class="stat-card__title">{{ title }}</p>
        <p class="stat-card__value">{{ value }}</p>
      </div>
      <CustomTooltip v-if="change != null" :content="hint || changeLabel" placement="top">
        <span class="stat-card__change" :class="`is-${changeKind}`">
          <CustomIcon v-if="changeKind === 'up'"><CaretTop /></CustomIcon>
          <CustomIcon v-else-if="changeKind === 'down'"><CaretBottom /></CustomIcon>
          {{ changeText }}
        </span>
      </CustomTooltip>
    </div>

    <div v-if="$slots.chart" class="stat-card__chart">
      <slot name="chart" />
    </div>
  </CustomCard>
</template>

<style scoped lang="scss">
.stat-card {
  --stat-tone: var(--el-color-primary);
  --stat-tone-bg: var(--el-color-primary-light-9);
  width: 100%;

  &.is-success {
    --stat-tone: var(--el-color-success);
    --stat-tone-bg: var(--el-color-success-light-9);
  }
  &.is-warning {
    --stat-tone: var(--el-color-warning);
    --stat-tone-bg: var(--el-color-warning-light-9);
  }
  &.is-danger {
    --stat-tone: var(--el-color-danger);
    --stat-tone-bg: var(--el-color-danger-light-9);
  }
  &.is-info {
    --stat-tone: var(--el-color-info);
    --stat-tone-bg: var(--el-color-info-light-9);
  }

  :deep(.el-card__body) {
    padding: 10px 12px 6px;
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
}

.stat-card__top {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.stat-card__icon {
  flex-shrink: 0;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--stat-tone-bg);
  color: var(--stat-tone);
  font-size: 15px;
}

.stat-card__meta {
  min-width: 0;
  flex: 1;
}

.stat-card__title {
  margin: 0;
  font-size: 12px;
  line-height: 1.25;
  color: var(--el-text-color-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.stat-card__value {
  margin: 2px 0 0;
  font-size: 16px;
  font-weight: 600;
  line-height: 1.25;
  letter-spacing: -0.02em;
  color: var(--el-text-color-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.stat-card__change {
  display: inline-flex;
  align-items: center;
  gap: 1px;
  flex-shrink: 0;
  margin-top: 1px;
  font-size: 11px;
  font-weight: 600;
  cursor: default;

  &.is-up {
    color: var(--el-color-success);
  }
  &.is-down {
    color: var(--el-color-danger);
  }
  &.is-flat {
    color: var(--el-text-color-secondary);
  }
}

.stat-card__chart {
  margin: 0 -6px -2px;
}
</style>
