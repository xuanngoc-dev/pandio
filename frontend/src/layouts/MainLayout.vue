<template>
  <el-container
    class="main-layout"
    :class="{
      'is-navbar-fixed': navbarFixed,
      'is-sidebar-fixed': sidebarFixed,
      'is-sidebar-overlay': isSidebarOverlay,
      'is-mobile': isMobile,
      'is-mobile-menu-open': isMobile && mobileMenuOpen,
    }"
  >
    <!-- Sidebar: slot giữ chỗ trong flow; panel có thể phủ như drawer -->
    <div
      :key="asideMountKey"
      class="aside-slot"
      :style="{ width: asideSlotWidth }"
    >
      <el-aside
        :width="asidePanelWidth"
        class="aside"
        :class="{
          'is-fixed': sidebarFixed || isSidebarOverlayExpanded,
          'is-collapsed': collapsed,
          'is-overlay-expanded': isSidebarOverlayExpanded,
          'is-hover-expanded': hoverExpanded,
          'is-mobile-drawer': isMobile,
          'is-mobile-open': isMobile && mobileMenuOpen,
        }"
        @mouseenter="onAsideEnter"
        @mouseleave="onAsideLeave"
      >
        <div class="brand">
          <el-button
            v-if="isMobile"
            text
            class="brand-toggle"
            aria-label="Ẩn menu"
            @click="closeMobileMenu"
          >
            <el-icon :size="20"><Fold /></el-icon>
          </el-button>
          <el-avatar
            v-if="brandLogoUrl"
            :size="28"
            shape="square"
            :src="brandLogoUrl"
            class="brand-logo"
          />
          <el-icon v-else :size="22"><Monitor /></el-icon>
          <span class="brand-text" :class="{ 'is-hidden': collapsed && !isMobile }">{{ brandName }}</span>
        </div>

        <div class="aside-menu">
          <SideMenu :collapsed="collapsed" />
        </div>
      </el-aside>
    </div>

    <Transition name="aside-mask">
      <div
        v-if="showAsideMask"
        class="aside-mask"
        aria-hidden="true"
        @click="onAsideMaskClick"
      />
    </Transition>

    <el-container class="content-shell">
      <!-- Header -->
      <el-header
        class="header"
        :class="{ 'is-fixed': navbarFixed }"
      >
        <div class="header-left">
          <el-button
            v-if="isMobile"
            text
            class="mobile-menu-btn"
            :aria-label="mobileMenuOpen ? 'Ẩn menu' : 'Hiện menu'"
            @click="toggleMobileMenu"
          >
            <el-icon :size="20">
              <Fold v-if="mobileMenuOpen" />
              <Expand v-else />
            </el-icon>
          </el-button>
          <div v-if="isMobile" class="header-brand">
            <el-avatar
              v-if="brandLogoUrl"
              :size="28"
              shape="square"
              :src="brandLogoUrl"
              class="brand-logo"
            />
            <el-icon v-else :size="20"><Monitor /></el-icon>
            <span class="header-brand__name">{{ brandName }}</span>
          </div>
          <el-button v-else text @click="togglePinnedCollapse">
            <el-icon :size="20">
              <Fold v-if="!pinnedCollapsed" />
              <Expand v-else />
            </el-icon>
          </el-button>
          <!-- <span class="page-title">{{ pageTitle }}</span> -->
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
                <span class="user-avatar-wrap">
                  <el-avatar :size="32">{{ avatarLetter }}</el-avatar>
                  <el-tooltip :content="networkTooltip" placement="bottom">
                    <span
                      class="network-status"
                      :class="networkToneClass"
                      role="status"
                      :aria-label="networkTooltip"
                      @click.stop
                    />
                  </el-tooltip>
                </span>
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
import { computed, ref, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useLayoutStore } from '@/stores/layout'
import { useNetworkStatus } from '@/composables/useNetworkStatus'
import { getCauHinhJson, updateCauHinhJson } from '@/api/cauHinhJson'
import { fetchThongTinStudio } from '@/api/thongTinStudio'
import { mediaUrl } from '@/utils/media'
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
const { navbarFixed, sidebarFixed, sidebarPushContent } = storeToRefs(layoutStore)
const {
  tooltip: networkTooltip,
  toneClass: networkToneClass,
} = useNetworkStatus()

/** Thu gọn do người dùng / viewport (trạng thái “ghim”) — desktop */
const pinnedCollapsed = ref(false)
/** Xổ tạm khi hover lúc đang thu gọn */
const hoverExpanded = ref(false)
/** Viewport mobile: ẩn sidebar mặc định, mở dạng drawer */
const isMobile = ref(false)
const mobileMenuOpen = ref(false)
const settingsOpen = ref(false)
const notificationsOpen = ref(false)
const searchOpen = ref(false)
const unreadNotifications = ref(0)
const isDark = ref(document.documentElement.classList.contains('dark'))
const now = ref(new Date())
/** Tăng khi đổi mode đẩy/overlay để remount sidebar sạch */
const asideMountKey = ref(0)
const studioInfo = ref(null)

const brandName = computed(() => studioInfo.value?.ten_studio?.trim() || 'Pandio')
const brandLogoUrl = computed(() => mediaUrl(studioInfo.value?.logo) || '')

const isMac = /Mac|iPhone|iPad|iPod/.test(navigator.platform)
const searchShortcutLabel = isMac ? '⌘K' : 'Ctrl+K'

/** Đang thu gọn về mặt hiển thị (ghim thu gọn và không đang hover) */
const collapsed = computed(() => {
  if (isMobile.value) return false
  return pinnedCollapsed.value && !hoverExpanded.value
})

/** Overlay: mode drawer, hoặc xổ tạm bằng hover, hoặc menu mobile */
const isSidebarOverlay = computed(() => {
  if (isMobile.value) return true
  return !sidebarPushContent.value
})
const isSidebarOverlayExpanded = computed(() => {
  if (isMobile.value) return mobileMenuOpen.value
  if (hoverExpanded.value) return true
  return isSidebarOverlay.value && !pinnedCollapsed.value
})
/** Mask chỉ khi mở hẳn kiểu drawer (không dùng khi hover) */
const showAsideMask = computed(() => {
  if (isMobile.value) return mobileMenuOpen.value
  return isSidebarOverlay.value && !pinnedCollapsed.value
})

const asideSlotWidth = computed(() => {
  // Mobile: không chiếm chỗ — nội dung full width
  if (isMobile.value) return '0px'
  // Hover xổ luôn overlay — slot giữ 64px khi đang ghim thu gọn
  if (pinnedCollapsed.value) return '64px'
  if (isSidebarOverlay.value) return '64px'
  return '220px'
})
const asidePanelWidth = computed(() => {
  if (isMobile.value) return '220px'
  return collapsed.value ? '64px' : '220px'
})

let hoverLeaveTimer = null

function clearHoverLeaveTimer() {
  if (hoverLeaveTimer != null) {
    window.clearTimeout(hoverLeaveTimer)
    hoverLeaveTimer = null
  }
}

function onAsideEnter() {
  if (isMobile.value || !pinnedCollapsed.value) return
  clearHoverLeaveTimer()
  hoverExpanded.value = true
}

function onAsideLeave() {
  if (isMobile.value || !pinnedCollapsed.value) return
  clearHoverLeaveTimer()
  hoverLeaveTimer = window.setTimeout(() => {
    hoverExpanded.value = false
    hoverLeaveTimer = null
  }, 180)
}

function togglePinnedCollapse() {
  clearHoverLeaveTimer()
  hoverExpanded.value = false
  pinnedCollapsed.value = !pinnedCollapsed.value
}

function pinCollapse() {
  clearHoverLeaveTimer()
  hoverExpanded.value = false
  pinnedCollapsed.value = true
}

function toggleMobileMenu() {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

function closeMobileMenu() {
  mobileMenuOpen.value = false
}

function onAsideMaskClick() {
  if (isMobile.value) {
    closeMobileMenu()
    return
  }
  pinCollapse()
}

watch(sidebarPushContent, async () => {
  // Remount aside + SideMenu để el-aside/CSS mode áp dụng đúng ngay, không cần F5
  clearHoverLeaveTimer()
  hoverExpanded.value = false
  mobileMenuOpen.value = false
  asideMountKey.value += 1
  await nextTick()
})

/** Đóng drawer mobile khi đổi trang */
watch(
  () => route.fullPath,
  () => {
    if (isMobile.value) closeMobileMenu()
  },
)

const pageTitle = computed(() => route.meta.title || brandName.value)
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
  clearHoverLeaveTimer()
  hoverExpanded.value = false
  isMobile.value = e.matches
  mobileMenuOpen.value = false
  // Desktop: mở rộng; mobile: ẩn menu (không chiếm chỗ)
  pinnedCollapsed.value = e.matches
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

async function loadStudioBrand() {
  try {
    const { data } = await getCauHinhJson({ skipLoading: true })
    const fromJson = data?.thong_tin_cau_hinh?.thong_tin_studio || null
    if (fromJson?.ten_studio) {
      studioInfo.value = fromJson
      return
    }

    // Migrate 1 lần từ bảng studio cũ nếu JSON chưa có
    const { data: listData } = await fetchThongTinStudio(
      { mac_dinh: 'co', per_page: 1 },
      { skipLoading: true },
    )
    let item = (listData.data || [])[0] || null
    if (!item) {
      const fallback = await fetchThongTinStudio({ per_page: 1 }, { skipLoading: true })
      item = (fallback.data.data || [])[0] || null
    }
    if (!item?.ten_studio) {
      studioInfo.value = null
      return
    }

    const payload = {
      ten_studio: item.ten_studio,
      khau_hieu: item.khau_hieu || null,
      logo: item.logo ? mediaUrl(item.logo) || item.logo : null,
      dia_chi: item.dia_chi || null,
      email: item.email || null,
      so_dien_thoai: item.so_dien_thoai || null,
      ma_so_thue: item.ma_so_thue || null,
      mac_dinh: item.mac_dinh === 'khong' ? 'khong' : 'co',
    }
    await updateCauHinhJson(
      { thong_tin_cau_hinh: { thong_tin_studio: payload } },
      { skipLoading: true },
    )
    studioInfo.value = payload
  } catch {
    studioInfo.value = null
  }
}

onMounted(() => {
  const saved = localStorage.getItem('darkMode')
  if (saved === '1') {
    isDark.value = true
    document.documentElement.classList.add('dark')
  }

  mediaQuery = window.matchMedia(`(max-width: ${COLLAPSE_BREAKPOINT - 1}px)`)
  isMobile.value = mediaQuery.matches
  mobileMenuOpen.value = false
  pinnedCollapsed.value = mediaQuery.matches
  mediaQuery.addEventListener('change', syncCollapseByViewport)
  window.addEventListener('keydown', onGlobalKeydown)

  clockTimer = window.setInterval(() => {
    now.value = new Date()
  }, 1000)

  loadStudioBrand()
  window.addEventListener('studio-brand-updated', loadStudioBrand)
})

onUnmounted(() => {
  mediaQuery?.removeEventListener('change', syncCollapseByViewport)
  window.removeEventListener('keydown', onGlobalKeydown)
  window.removeEventListener('studio-brand-updated', loadStudioBrand)
  clearHoverLeaveTimer()
  if (clockTimer != null) window.clearInterval(clockTimer)
})
</script>

<style scoped lang="scss">
.main-layout {
  min-height: 100vh;
  position: relative;

  &.is-navbar-fixed,
  &.is-sidebar-fixed {
    height: 100vh;
    overflow: hidden;
  }
}

.aside-slot {
  flex-shrink: 0;
  position: relative;
  transition: width 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.aside-mask {
  position: fixed;
  inset: 0;
  z-index: 90;
  background: var(--el-overlay-color-lighter);
}

.aside-mask-enter-active,
.aside-mask-leave-active {
  transition: opacity 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.aside-mask-enter-from,
.aside-mask-leave-to {
  opacity: 0;
}

.aside {
  border-right: 1px solid var(--el-border-color);
  background: var(--el-bg-color);
  transition:
    width 0.28s cubic-bezier(0.4, 0, 0.2, 1),
    box-shadow 0.28s cubic-bezier(0.4, 0, 0.2, 1),
    transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;

  &.is-fixed {
    height: 100vh;
    position: sticky;
    top: 0;
    overflow: hidden;
  }

  &.is-overlay-expanded {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    z-index: 100;
    overflow: hidden;
    box-shadow: var(--el-box-shadow-dark);
  }

  &.is-hover-expanded {
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
  }

  /* Mobile drawer: trượt từ trái, mặc định ẩn */
  &.is-mobile-drawer {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    z-index: 100;
    overflow: hidden;
    width: 220px !important;
    transform: translateX(-100%);
    pointer-events: none;
    box-shadow: none;
  }

  &.is-mobile-drawer.is-mobile-open {
    transform: translateX(0);
    pointer-events: auto;
    box-shadow: var(--el-box-shadow-dark);
  }
}

.aside-menu {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: thin;
  scrollbar-color: var(--el-border-color) transparent;

  &::-webkit-scrollbar {
    width: 2px;
  }

  &::-webkit-scrollbar-track {
    background: transparent;
  }

  &::-webkit-scrollbar-thumb {
    background-color: var(--el-border-color);
    border-radius: 2px;

    &:hover {
      background-color: var(--el-text-color-secondary);
    }
  }
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
  transition: padding 0.28s cubic-bezier(0.4, 0, 0.2, 1), justify-content 0.28s ease;

  .aside.is-collapsed & {
    justify-content: center;
    padding: 0;
  }

  .aside.is-mobile-drawer & {
    padding: 0 12px 0 8px;
  }
}

.brand-toggle {
  flex-shrink: 0;
  margin-right: -4px;
}

.brand-logo {
  flex-shrink: 0;
  border-radius: 6px;
  background: var(--el-fill-color-light);
}

.brand-text {
  display: inline-block;
  max-width: 140px;
  overflow: hidden;
  text-overflow: ellipsis;
  opacity: 1;
  transform: translateX(0);
  transition:
    opacity 0.2s ease 0.06s,
    transform 0.28s cubic-bezier(0.4, 0, 0.2, 1),
    max-width 0.28s cubic-bezier(0.4, 0, 0.2, 1);

  &.is-hidden {
    max-width: 0;
    opacity: 0;
    transform: translateX(-6px);
    transition:
      opacity 0.15s ease,
      transform 0.2s ease,
      max-width 0.28s cubic-bezier(0.4, 0, 0.2, 1);
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

.mobile-menu-btn {
  flex-shrink: 0;
}

.header-brand {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  color: var(--el-color-primary);
  font-weight: 700;
  font-size: 16px;
}

.header-brand__name {
  display: none;
}

@media (max-width: 991px) {
  .aside-slot {
    width: 0 !important;
    overflow: visible;
    flex-shrink: 0;
  }

  .header {
    gap: 10px;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
    scrollbar-width: thin;
    scrollbar-color: var(--el-border-color) transparent;

    &::-webkit-scrollbar {
      height: 4px;
    }

    &::-webkit-scrollbar-track {
      background: transparent;
    }

    &::-webkit-scrollbar-thumb {
      background-color: var(--el-border-color);
      border-radius: 2px;
    }
  }

  .header-left,
  .header-right {
    gap: 8px;
    flex-wrap: nowrap;
    flex-shrink: 0;
  }

  .header-search {
    flex: 0 0 40px;
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

.user-avatar-wrap {
  position: relative;
  display: inline-flex;
  flex-shrink: 0;
}

.network-status {
  position: absolute;
  top: -1px;
  right: -1px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 2px solid var(--el-bg-color);
  box-sizing: content-box;
  cursor: default;
  box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.06);

  &.is-online {
    background: #22c55e;
  }

  &.is-unstable {
    background: #eab308;
  }

  &.is-offline {
    background: #ef4444;
  }
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
.el-drawer__header{
  margin-bottom: 12px;
}
</style>
