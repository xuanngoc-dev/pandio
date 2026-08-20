<template>
  <span class="table-column-config">
    <CustomTooltip content="Cấu hình cột hiển thị" placement="top">
      <CustomButton @click="settings.openConfig()">
        Cấu hình
      </CustomButton>
    </CustomTooltip>

    <CustomDialog
      v-model="settings.dialogVisible"
      title="Cấu hình cột hiển thị"
      :width="1280"
    >
      <p class="column-config-hint">
        Chọn các cột muốn hiển thị trên bảng. Cấu hình được lưu trên trình duyệt này.
      </p>

      <div v-loading="settings.configLoading" class="column-config-body">
        <section
          v-for="group in settings.columnGroups"
          :key="group.key"
          class="column-config-section"
          :class="{ 'is-ungrouped': !group.label || !hasNamedGroups }"
        >
          <div v-if="group.label && hasNamedGroups" class="column-config-section-header">
            <span class="column-config-section-title">{{ group.label }}</span>
            <button
              type="button"
              class="column-config-section-action"
              @click="settings.selectGroupDraft(group.key, !isGroupFullySelected(group))"
            >
              {{ isGroupFullySelected(group) ? 'Bỏ chọn nhóm' : 'Chọn nhóm' }}
            </button>
          </div>
          <div v-if="group.columns.length" class="column-config-list">
            <label
              v-for="col in group.columns"
              :key="col.key"
              class="column-config-item"
            >
              <el-checkbox v-model="settings.draft[col.key]">
                {{ col.label }}
              </el-checkbox>
            </label>
          </div>
          <p v-else class="column-config-empty">Không có cột trong nhóm này.</p>
        </section>

        <section class="column-config-section column-config-fixed">
          <div class="column-config-section-header">
            <span class="column-config-section-title">Cột cố định</span>
          </div>
          <p class="column-config-fixed-hint">
            Ghim cột khi cuộn ngang. Bên trái mặc định gồm checkbox và số thứ tự; bên phải mặc định
            gồm thao tác và trạng thái (nếu có). Mỗi cột chỉ cố định một bên.
          </p>
          <div class="column-config-fixed-grid">
            <div class="column-config-fixed-pane">
              <div class="column-config-fixed-pane-title">Bên trái</div>
              <div v-if="leftPinColumns.length" class="column-config-list column-config-list--pin">
                <label
                  v-for="col in leftPinColumns"
                  :key="`left-${col.key}`"
                  class="column-config-item"
                >
                  <el-checkbox
                    :model-value="settings.isFixedDraftChecked(col.key, 'left')"
                    @update:model-value="(val) => settings.toggleFixedDraft(col.key, 'left', val)"
                  >
                    {{ col.label }}
                  </el-checkbox>
                </label>
              </div>
              <p v-else class="column-config-empty">Không có cột.</p>
            </div>
            <div class="column-config-fixed-pane">
              <div class="column-config-fixed-pane-title">Bên phải</div>
              <div v-if="rightPinColumns.length" class="column-config-list column-config-list--pin">
                <label
                  v-for="col in rightPinColumns"
                  :key="`right-${col.key}`"
                  class="column-config-item"
                >
                  <el-checkbox
                    :model-value="settings.isFixedDraftChecked(col.key, 'right')"
                    @update:model-value="(val) => settings.toggleFixedDraft(col.key, 'right', val)"
                  >
                    {{ col.label }}
                  </el-checkbox>
                </label>
              </div>
              <p v-else class="column-config-empty">Không có cột.</p>
            </div>
          </div>
        </section>
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
import { computed } from 'vue'
import { Setting } from '@element-plus/icons-vue'
import { CustomButton, CustomDialog, CustomIcon, CustomTooltip } from '@/components/element'

const props = defineProps({
  /** Giá trị trả về từ useTableColumns(...) */
  settings: {
    type: Object,
    required: true,
  },
})

const hasNamedGroups = computed(() =>
  (props.settings.columnGroups || []).some((group) => !!group.label),
)

const leftPinColumns = computed(() => props.settings.pinGroups?.left || [])
const rightPinColumns = computed(() => props.settings.pinGroups?.right || [])

function isGroupFullySelected(group) {
  return group.columns.every((col) => !!props.settings.draft[col.key])
}
</script>

<style scoped lang="scss">
.table-column-config {
  display: inline-flex;
  align-items: center;
}

.column-config-hint {
  margin: 0 0 12px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  line-height: 1.45;
}

.column-config-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-height: min(70vh, 640px);
  overflow: auto;
  padding-right: 4px;
}

.column-config-section {
  padding: 12px 14px 10px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  background: var(--el-fill-color-blank);

  &.is-ungrouped {
    padding: 0;
    border: none;
    border-radius: 0;
    background: transparent;
  }
}

.column-config-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--el-border-color-extra-light);
}

.column-config-section-title {
  font-size: 13px;
  font-weight: 500;
  color: var(--el-text-color-regular);
  line-height: 1.3;
}

.column-config-section-action {
  border: none;
  background: transparent;
  padding: 0;
  font-size: 11px;
  font-weight: 400;
  color: var(--el-color-primary);
  cursor: pointer;
  line-height: 1.3;

  &:hover {
    text-decoration: underline;
  }
}

.column-config-list {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 8px;

  @media (max-width: 1100px) {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  @media (max-width: 768px) {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  @media (max-width: 480px) {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  &--pin {
    grid-template-columns: repeat(3, minmax(0, 1fr));

    @media (max-width: 1100px) {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    @media (max-width: 768px) {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
}

.column-config-empty {
  margin: 0;
  padding: 4px 2px 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
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
    font-size: 13px;
    font-weight: 400;
    color: var(--el-text-color-regular);
    white-space: normal;
    word-break: break-word;
    line-height: 1.35;
  }
}

.column-config-fixed-hint {
  margin: -2px 0 12px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  line-height: 1.45;
}

.column-config-fixed-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;

  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
}

.column-config-fixed-pane {
  min-width: 0;
  padding: 10px 12px 8px;
  border: 1px solid var(--el-border-color-extra-light);
  border-radius: 8px;
  background: var(--el-fill-color-lighter);
}

.column-config-fixed-pane-title {
  margin-bottom: 8px;
  font-size: 12px;
  font-weight: 500;
  color: var(--el-text-color-regular);
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
