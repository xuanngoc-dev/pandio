<template>
  <span class="table-column-config">
    <CustomTooltip content="Cấu hình cột hiển thị" placement="top">
      <CustomButton @click="settings.openConfig()">
        <CustomIcon><Setting /></CustomIcon>
        Cấu hình
      </CustomButton>
    </CustomTooltip>

    <CustomDialog
      v-model="settings.dialogVisible"
      title="Cấu hình cột hiển thị"
      :width="1080"
    >
      <p class="column-config-hint">Chọn các cột muốn hiển thị trên bảng. Cấu hình được lưu trên trình duyệt này.</p>
      <div class="column-config-list">
        <label
          v-for="col in settings.columns"
          :key="col.key"
          class="column-config-item"
        >
          <el-checkbox v-model="settings.draft[col.key]">
            {{ col.label }}
          </el-checkbox>
        </label>
      </div>
      <template #footer>
        <div class="column-config-footer">
          <CustomButton @click="settings.selectAllDraft()">Chọn tất cả</CustomButton>
          <div class="column-config-footer-right">
            <CustomButton @click="settings.dialogVisible = false">Hủy</CustomButton>
            <CustomButton type="primary" @click="settings.saveConfig()">Lưu</CustomButton>
          </div>
        </div>
      </template>
    </CustomDialog>
  </span>
</template>

<script setup>
import { Setting } from '@element-plus/icons-vue'
import { CustomButton, CustomDialog, CustomIcon, CustomTooltip } from '@/components/element'

defineProps({
  /** Giá trị trả về từ useTableColumns(...) */
  settings: {
    type: Object,
    required: true,
  },
})
</script>

<style scoped lang="scss">
.table-column-config {
  display: inline-flex;
  align-items: center;
}

.column-config-hint {
  margin: 0 0 12px;
  font-size: 13px;
  color: var(--el-text-color-secondary);
  line-height: 1.45;
}

.column-config-list {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 8px;
  max-height: min(55vh, 420px);
  overflow: auto;
  padding-right: 4px;

  @media (max-width: 1100px) {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  @media (max-width: 768px) {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  @media (max-width: 480px) {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.column-config-item {
  display: flex;
  align-items: flex-start;
  min-width: 0;
  padding: 8px 10px;
  border-radius: 6px;
  cursor: pointer;

  &:hover {
    background: var(--el-fill-color-light);
  }

  :deep(.el-checkbox) {
    width: 100%;
    height: auto;
    align-items: flex-start;
    white-space: normal;
  }

  :deep(.el-checkbox__label) {
    white-space: normal;
    word-break: break-word;
    line-height: 1.35;
  }
}

.column-config-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
}

.column-config-footer-right {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
</style>
