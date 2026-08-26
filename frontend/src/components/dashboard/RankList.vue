<script setup>
import { computed } from 'vue'

defineOptions({ name: 'RankList' })

const props = defineProps({
  items: { type: Array, default: () => [] },
  /** Field hiển thị số liệu, mặc định `value` */
  valueKey: { type: String, default: 'value' },
})

const maxValue = computed(() => {
  const nums = props.items.map((item) => Number(item[props.valueKey]) || 0)
  return Math.max(1, ...nums)
})

function percentOf(item) {
  return Math.round(((Number(item[props.valueKey]) || 0) / maxValue.value) * 100)
}

function initialsOf(item) {
  if (item.initials) return item.initials
  const parts = String(item.name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
  if (!parts.length) return '?'
  if (parts.length === 1) return parts[0].slice(0, 1).toUpperCase()
  return `${parts[0].charAt(0)}${parts[parts.length - 1].charAt(0)}`.toUpperCase()
}
</script>

<template>
  <ol class="rank-list">
    <li v-for="(item, index) in items" :key="item.id || item.name" class="rank-item">
      <span class="rank-item__pos" :class="`is-${index + 1}`">{{ index + 1 }}</span>
      <el-avatar
        :size="28"
        :src="item.avatar || undefined"
        :style="{ background: item.color || 'var(--el-color-primary-light-7)' }"
      >
        {{ initialsOf(item) }}
      </el-avatar>
      <div class="rank-item__body">
        <div class="rank-item__row">
          <span class="rank-item__name" :title="item.name">{{ item.name }}</span>
          <span class="rank-item__value">{{ item.valueLabel || item[valueKey] }}</span>
        </div>
        <div class="rank-item__bar" aria-hidden="true">
          <span class="rank-item__bar-fill" :style="{ width: `${percentOf(item)}%`, background: item.color }" />
        </div>
        <p v-if="item.meta" class="rank-item__meta">{{ item.meta }}</p>
      </div>
    </li>
  </ol>
</template>

<style scoped lang="scss">
.rank-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.rank-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.rank-item__pos {
  flex-shrink: 0;
  width: 18px;
  margin-top: 5px;
  font-size: 12px;
  font-weight: 600;
  text-align: center;
  color: var(--el-text-color-placeholder);

  &.is-1 {
    color: #e6a23c;
  }
  &.is-2 {
    color: #909399;
  }
  &.is-3 {
    color: #c47a3a;
  }
}

.rank-item__body {
  min-width: 0;
  flex: 1;
}

.rank-item__row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
}

.rank-item__name {
  min-width: 0;
  font-size: 13px;
  font-weight: 500;
  line-height: 1.3;
  color: var(--el-text-color-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rank-item__value {
  flex-shrink: 0;
  font-size: 12px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.rank-item__bar {
  margin-top: 4px;
  height: 4px;
  border-radius: 99px;
  background: var(--el-fill-color);
  overflow: hidden;
}

.rank-item__bar-fill {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: var(--el-color-primary);
}

.rank-item__meta {
  margin: 3px 0 0;
  font-size: 11px;
  line-height: 1.3;
  color: var(--el-text-color-placeholder);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
