<template>
  <CustomCard shadow="hover" class="filter-card">
    <div class="date-filter">
      <div class="date-filter-picker">
        <CustomDatePicker
          v-model="model"
          type="daterange"
          range-separator="–"
          start-placeholder="Từ ngày"
          end-placeholder="Đến ngày"
          format="DD/MM/YYYY"
          value-format="YYYY-MM-DD"
          :clearable="false"
          style="width: 100%"
        />
      </div>
      <div class="date-filter-presets">
        <CustomButton
          v-for="preset in DATE_PRESETS"
          :key="preset.key"
          :type="activePreset === preset.key ? 'primary' : 'default'"
          plain
          @click="applyPreset(preset.key)"
        >
          {{ preset.label }}
        </CustomButton>
      </div>
    </div>
  </CustomCard>
</template>

<script setup>
import { computed } from 'vue'
import { DATE_PRESETS, activePresetKey, getPresetRange } from './lichKhachHangDate'

const model = defineModel({ type: Array, default: () => getPresetRange('this_month') })

const activePreset = computed(() => activePresetKey(model.value))

function applyPreset(key) {
  model.value = getPresetRange(key)
}
</script>

<style scoped lang="scss">
.date-filter {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
}

.date-filter-picker {
  width: 340px;
  max-width: 100%;

  :deep(.el-date-editor) {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
  }
}

.date-filter-presets {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

@media (max-width: 767px) {
  .date-filter-picker {
    width: 100%;
  }
}
</style>
