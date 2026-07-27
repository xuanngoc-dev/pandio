<template>
  <el-container
    class="main-layout"
    :class="{
      'is-navbar-fixed': layoutStore.navbarFixed,
      'is-sidebar-fixed': layoutStore.sidebarFixed,
    }"
  >
    <!-- Sidebar -->
    <el-aside
      :width="collapsed ? '64px' : '220px'"
      class="aside"
      :class="{
        'is-fixed': layoutStore.sidebarFixed,
        'is-collapsed': collapsed,
      }"
    >
      <div class="brand">
        <el-icon :size="22"><Monitor /></el-icon>
        <span v-show="!collapsed" class="brand-text">Pandio</span>
      </div>

      <div class="aside-menu">
        <SideMenu :collapsed="collapsed" />
      </div>
    </el-aside>

    <el-container class="content-shell">
      <!-- Header -->
      <el-header
        class="header"
        :class="{ 'is-fixed': layoutStore.navbarFixed }"
      >
        <div class="header-left">
          <el-button text @click="collapsed = !collapsed">
            <el-icon :size="20">
              <Fold v-if="!collapsed" />
              <Expand v-else />
            </el-icon>
          </el-button>
          <span class="page-title">{{ pageTitle }}</span>
        </div>

        <div class="header-right">
          <div class="header-datetime" :title="nowFullLabel">
            <span class="header-datetime__weekday">{{ nowWeekday }}</span>
            <span class="header-datetime__time">{{ nowTime }}</span>
            <span class="header-datetime__date">{{ nowDate }}</span>
          </div>

          <button
            type="button"
            class="header-search"
            aria-label="Tìm kiếm nhanh"
            @click="searchOpen = true"
          >
            <el-icon :size="16"><Search /></el-icon>
            <span class="header-search__placeholder">Tìm kiếm nhanh...</span>
            <kbd class="header-search__kbd">{{ searchShortcutLabel }}</kbd>
          </button>

          <el-switch
            v-model="isDark"
            inline-prompt
            active-text="🌙"
            inactive-text="☀️"
            @change="toggleDark"
          />

          <el-tooltip content="Thông báo" placement="bottom">
            <el-badge
              :value="unreadNotifications"
              :hidden="!unreadNotifications"
              class="header-badge"
            >
              <el-button text class="icon-btn" @click="notificationsOpen = true">
                <el-icon :size="20"><Bell /></el-icon>
              </el-button>
            </el-badge>
          </el-tooltip>

          <el-tooltip content="Cài đặt giao diện" placement="bottom">
            <el-button text class="icon-btn" @click="settingsOpen = true">
              <el-icon :size="20"><Setting /></el-icon>
            </el-button>
          </el-tooltip>

          <template v-if="authStore.isAuthenticated">
            <el-dropdown trigger="click" @command="onCommand">
              <span class="user-trigger">
                <el-avatar :size="32">{{ avatarLetter }}</el-avatar>
                <span class="user-name" :title="userFullName">
                  <span class="user-name__full">{{ userFullName }}</span>
                  <span class="user-name__short">{{ userShortName }}</span>
                </span>
                <el-icon><ArrowDown /></el-icon>
              </span>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item disabled>
                    {{ authStore.user?.email }}
                  </el-dropdown-item>
                  <el-dropdown-item divided command="logout">
                    Đăng xuất
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
          <template v-else>
            <el-button type="primary" @click="$router.push({ name: 'login' })">
              Đăng nhập
            </el-button>
            <el-button @click="$router.push({ name: 'register' })">Đăng ký</el-button>
          </template>
        </div>
      </el-header>

      <!-- Nội dung trang -->
      <el-main class="main">
        <router-view />
      </el-main>
    </el-container>

    <NotificationDrawer
      v-model="notificationsOpen"
      v-model:unread-count="unreadNotifications"
    />
    <LayoutSettingsDrawer v-model="settingsOpen" v-model:dark="isDark" />
    <QuickSearchModal v-model="searchOpen" />
  </el-container>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useLayoutStore } from '@/stores/layout'
import { Monitor, Fold, Expand, ArrowDown, Setting, Bell, Search } from '@element-plus/icons-vue'
import SideMenu from '@/components/SideMenu.vue'
import NotificationDrawer from '@/components/NotificationDrawer.vue'
import LayoutSettingsDrawer from '@/components/LayoutSettingsDrawer.vue'
import QuickSearchModal from '@/components/QuickSearchModal.vue'

/** Dưới breakpoint này sidebar tự chuyển sang collapsed */
const COLLAPSE_BREAKPOINT = 992

const WEEKDAYS = [
  'Chủ Nhật',
  'Thứ Hai',
  'Thứ Ba',
  'Thứ Tư',
  'Thứ Năm',
  'Thứ Sáu',
  'Thứ Bảy',
]

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const layoutStore = useLayoutStore()

const collapsed = ref(false)
const settingsOpen = ref(false)
const notificationsOpen = ref(false)
const searchOpen = ref(false)
const unreadNotifications = ref(0)
const isDark = ref(document.documentElement.classList.contains('dark'))
const now = ref(new Date())

const isMac = /Mac|iPhone|iPad|iPod/.test(navigator.platform)
const searchShortcutLabel = isMac ? '⌘K' : 'Ctrl+K'

const pageTitle = computed(() => route.meta.title || 'Pandio')
const userFullName = computed(() => String(authStore.user?.name || '').trim())
const userShortName = computed(() => {
  const parts = userFullName.value.split(/\s+/).filter(Boolean)
  return parts.length ? parts[parts.length - 1] : 'User'
})
const avatarLetter = computed(() =>
  (userShortName.value || 'U').charAt(0).toUpperCase()
)

const nowWeekday = computed(() => WEEKDAYS[now.value.getDay()])
const nowTime = computed(() =>
  now.value.toLocaleTimeString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  })
)
const nowDate = computed(() =>
  now.value.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
)
const nowFullLabel = computed(
  () => `${nowWeekday.value}, ${nowTime.value} — ${nowDate.value}`
)

let mediaQuery = null
let clockTimer = null

function syncCollapseByViewport(e) {
  collapsed.value = e.matches
}

function onGlobalKeydown(event) {
  const key = event.key?.toLowerCase()
  const withModifier = event.metaKey || event.ctrlKey

  if (withModifier && key === 'k') {
    event.preventDefault()
    searchOpen.value = true
  }
}

function toggleDark(val) {
  document.documentElement.classList.toggle('dark', val)
  localStorage.setItem('darkMode', val ? '1' : '0')
}

async function onCommand(cmd) {
  if (cmd === 'logout') {
    await authStore.logout()
    router.push({ name: 'login' })
  }
}

onMounted(() => {
  const saved = localStorage.getItem('darkMode')
  if (saved === '1') {
    isDark.value = true
    document.documentElement.classList.add('dark')
  }

  mediaQuery = window.matchMedia(`(max-width: ${COLLAPSE_BREAKPOINT - 1}px)`)
  collapsed.value = mediaQuery.matches
  mediaQuery.addEventListener('change', syncCollapseByViewport)
  window.addEventListener('keydown', onGlobalKeydown)

  clockTimer = window.setInterval(() => {
    now.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  mediaQuery?.removeEventListener('change', syncCollapseByViewport)
  window.removeEventListener('keydown', onGlobalKeydown)
  if (clockTimer != null) window.clearInterval(clockTimer)
})
</script>

<style scoped lang="scss">
.main-layout {
  min-height: 100vh;

  &.is-navbar-fixed,
  &.is-sidebar-fixed {
    height: 100vh;
    overflow: hidden;
  }
}

.aside {
  border-right: 1px solid var(--el-border-color);
  background: var(--el-bg-color);
  transition: width 0.2s ease;
  display: flex;
  flex-direction: column;

  &.is-fixed {
    height: 100vh;
    position: sticky;
    top: 0;
    overflow: hidden;
  }
}

.aside-menu {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
}

.brand {
  height: 60px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 18px;
  font-weight: 700;
  font-size: 18px;
  color: var(--el-color-primary);
  border-bottom: 1px solid var(--el-border-color);
  overflow: hidden;
  white-space: nowrap;

  .aside.is-collapsed & {
    justify-content: center;
    padding: 0;
  }
}

.content-shell {
  min-width: 0;
  min-height: 0;

  .is-navbar-fixed &,
  .is-sidebar-fixed & {
    height: 100vh;
  }

  .is-navbar-fixed & {
    overflow: hidden;
  }

  .is-sidebar-fixed:not(.is-navbar-fixed) & {
    overflow-y: auto;
  }
}

.header {
  display: flex;
  align-items: center;
  gap: 16px;
  border-bottom: 1px solid var(--el-border-color);
  background: var(--el-bg-color);
  flex-shrink: 0;

  &.is-fixed {
    position: sticky;
    top: 0;
    z-index: 20;
  }
}

.header-search {
  flex: 0 1 280px;
  max-width: 280px;
  min-width: 0;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  border: 1px solid var(--el-border-color);
  border-radius: 8px;
  background: var(--el-fill-color-light);
  color: var(--el-text-color-secondary);
  cursor: pointer;
  transition: border-color 0.15s ease, background 0.15s ease;

  &:hover {
    border-color: var(--el-color-primary-light-5);
    background: var(--el-fill-color);
  }
}

.header-search__placeholder {
  flex: 1;
  min-width: 0;
  text-align: left;
  font-size: 12px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.header-search__kbd {
  flex-shrink: 0;
  padding: 1px 5px;
  border-radius: 4px;
  border: 1px solid var(--el-border-color);
  background: var(--el-bg-color);
  font-size: 10px;
  line-height: 1.4;
  color: var(--el-text-color-placeholder);
  font-family: inherit;
}

.header-datetime {
  display: flex;
  align-items: baseline;
  gap: 12px;
  flex-shrink: 0;
  padding: 4px 2px;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
  color: var(--el-text-color-regular);
}

.header-datetime__weekday {
  font-size: 12px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.header-datetime__time {
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.02em;
  color: var(--el-color-primary);
}

.header-datetime__date {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.header-left,
.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.header-right {
  margin-left: auto;
  gap: 10px;
}

@media (max-width: 991px) {
  .header-search {
    flex-basis: 40px;
    max-width: 40px;
    justify-content: center;
    padding: 6px;
  }

  .header-search__placeholder,
  .header-search__kbd {
    display: none;
  }

  .header-datetime__weekday {
    display: none;
  }
}

@media (max-width: 640px) {
  .header-datetime {
    display: none;
  }
}

.icon-btn {
  padding: 8px;
}

.header-badge {
  :deep(.el-badge__content) {
    transform: translateY(-2px) translateX(2px);
  }
}

.page-title {
  font-weight: 600;
}

.user-trigger {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  max-width: 220px;
}

.user-name {
  flex: 1;
  min-width: 0;
  max-width: 160px;
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-name__full {
  display: inline;
}

.user-name__short {
  display: none;
}

@media (max-width: 640px) {
  .user-trigger {
    max-width: 120px;
  }

  .user-name {
    max-width: 72px;
  }

  .user-name__full {
    display: none;
  }

  .user-name__short {
    display: inline;
  }
}

.main {
  background: var(--el-bg-color-page);
  min-height: 0;

  .is-navbar-fixed & {
    overflow-y: auto;
  }
}
</style>

<style lang="scss">
/* Tăng tương phản chữ el-select disabled — giao diện sáng */
html:not(.dark) {
  --el-select-disabled-color: #141414 !important;

  .el-select .el-select__wrapper.is-disabled .el-select__selected-item {
    color: var(--el-select-disabled-color) !important;
    -webkit-text-fill-color: var(--el-select-disabled-color) !important;
  }
}
</style>
