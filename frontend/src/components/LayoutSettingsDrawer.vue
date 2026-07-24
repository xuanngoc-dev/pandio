<template>
  <el-drawer
    v-model="visible"
    title="Cài đặt giao diện"
    direction="rtl"
    size="360px"
    class="layout-settings-drawer"
  >
    <div class="settings-body">
      <section class="settings-section">
        <h4 class="settings-section__title">Menu bên</h4>

        <div class="settings-row">
          <div class="settings-row__meta">
            <span class="settings-row__label">Thu gọn theo nhóm</span>
            <span class="settings-row__desc">
              Cho phép mở rộng / thu gọn từng nhóm menu
            </span>
          </div>
          <el-switch v-model="layoutStore.menuGroupCollapsible" />
        </div>

        <div class="settings-row" :class="{ 'is-disabled': !layoutStore.menuGroupCollapsible }">
          <div class="settings-row__meta">
            <span class="settings-row__label">Chỉ mở một nhóm</span>
            <span class="settings-row__desc">
              Đóng các nhóm khác khi mở một nhóm mới
            </span>
          </div>
          <el-switch
            v-model="layoutStore.menuUniqueOpened"
            :disabled="!layoutStore.menuGroupCollapsible"
          />
        </div>
      </section>

      <section class="settings-section">
        <h4 class="settings-section__title">Thanh điều hướng</h4>

        <div class="settings-row">
          <div class="settings-row__meta">
            <span class="settings-row__label">Vị trí navbar</span>
            <span class="settings-row__desc">
              Cố định luôn hiện trên cùng, hoặc cuộn theo nội dung
            </span>
          </div>
          <el-segmented
            v-model="navbarMode"
            :options="navbarOptions"
            size="small"
          />
        </div>

        <div class="settings-row">
          <div class="settings-row__meta">
            <span class="settings-row__label">Sidebar cố định</span>
            <span class="settings-row__desc">
              Giữ menu bên trái cố định khi cuộn trang
            </span>
          </div>
          <el-switch v-model="layoutStore.sidebarFixed" />
        </div>
      </section>

      <section class="settings-section">
        <h4 class="settings-section__title">Khác</h4>

        <div class="settings-row">
          <div class="settings-row__meta">
            <span class="settings-row__label">Chế độ tối</span>
            <span class="settings-row__desc">Bật / tắt giao diện tối</span>
          </div>
          <el-switch v-model="isDark" @change="onDarkChange" />
        </div>
      </section>
    </div>

    <template #footer>
      <div class="settings-footer">
        <el-button @click="layoutStore.reset()">Khôi phục mặc định</el-button>
        <el-button type="primary" @click="visible = false">Đóng</el-button>
      </div>
    </template>
  </el-drawer>
</template>

<script setup>
import { computed } from 'vue'
import { useLayoutStore } from '@/stores/layout'

const visible = defineModel({ type: Boolean, default: false })

const props = defineProps({
  dark: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:dark'])

const layoutStore = useLayoutStore()

const isDark = computed({
  get: () => props.dark,
  set: (val) => emit('update:dark', val),
})

const navbarOptions = [
  { label: 'Cố định', value: 'fixed' },
  { label: 'Cuộn theo', value: 'scroll' },
]

const navbarMode = computed({
  get: () => (layoutStore.navbarFixed ? 'fixed' : 'scroll'),
  set: (val) => {
    layoutStore.navbarFixed = val === 'fixed'
  },
})

function onDarkChange(val) {
  document.documentElement.classList.toggle('dark', val)
  localStorage.setItem('darkMode', val ? '1' : '0')
}
</script>

<style scoped lang="scss">
.settings-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.settings-section {
  padding: 4px 0 12px;
  border-bottom: 1px solid var(--el-border-color-lighter);

  &:last-child {
    border-bottom: none;
  }

  &__title {
    margin: 0 0 12px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--el-text-color-secondary);
  }
}

.settings-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 10px 0;

  &.is-disabled {
    opacity: 0.5;
  }

  &__meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
  }

  &__label {
    font-size: 14px;
    font-weight: 500;
    color: var(--el-text-color-primary);
  }

  &__desc {
    font-size: 12px;
    line-height: 1.4;
    color: var(--el-text-color-secondary);
  }
}

.settings-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
