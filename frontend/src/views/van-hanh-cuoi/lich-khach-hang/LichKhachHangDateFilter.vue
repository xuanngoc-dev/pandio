<template>
  <CustomCard shadow="hover" class="filter-card">
    <CustomRow :gutter="12" class="toolbar date-filter">
      <CustomCol :xs="12" :sm="12" :md="6" :lg="6">
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
      </CustomCol>
      <CustomCol :xs="24" :sm="24" :md="18" :lg="18">
        <div class="toolbar-actions date-filter-presets">
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
      </CustomCol>
    </CustomRow>
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
.date-filter-picker {
  width: 100%;
  max-width: 100%;

  :deep(.el-date-editor) {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
  }
}
</style>
