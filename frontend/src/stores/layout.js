import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

const STORAGE_KEY = 'pandio-layout-settings'

const defaults = {
  /** Cho phép mở rộng / thu gọn menu theo nhóm */
  menuGroupCollapsible: false,
  /** Chỉ mở một nhóm menu tại một thời điểm */
  menuUniqueOpened: true,
  /** Navbar cố định (true) hoặc cuộn theo trang (false) */
  navbarFixed: true,
  /** Sidebar cố định khi cuộn */
  sidebarFixed: true,
  /** Mở rộng menu có đẩy content-shell (true) hoặc phủ như drawer (false) */
  sidebarPushContent: true,
}

function loadSettings() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return { ...defaults }
    return { ...defaults, ...JSON.parse(raw) }
  } catch {
    return { ...defaults }
  }
}

/**
 * Pinia Layout Store — cấu hình giao diện (menu, navbar, …).
 */
export const useLayoutStore = defineStore('layout', () => {
  const saved = loadSettings()

  const menuGroupCollapsible = ref(saved.menuGroupCollapsible)
  const menuUniqueOpened = ref(saved.menuUniqueOpened)
  const navbarFixed = ref(saved.navbarFixed)
  const sidebarFixed = ref(saved.sidebarFixed)
  const sidebarPushContent = ref(saved.sidebarPushContent)

  function persist() {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({
        menuGroupCollapsible: menuGroupCollapsible.value,
        menuUniqueOpened: menuUniqueOpened.value,
        navbarFixed: navbarFixed.value,
        sidebarFixed: sidebarFixed.value,
        sidebarPushContent: sidebarPushContent.value,
      })
    )
  }

  watch(
    [
      menuGroupCollapsible,
      menuUniqueOpened,
      navbarFixed,
      sidebarFixed,
      sidebarPushContent,
    ],
    persist
  )

  function reset() {
    menuGroupCollapsible.value = defaults.menuGroupCollapsible
    menuUniqueOpened.value = defaults.menuUniqueOpened
    navbarFixed.value = defaults.navbarFixed
    sidebarFixed.value = defaults.sidebarFixed
    sidebarPushContent.value = defaults.sidebarPushContent
  }

  return {
    menuGroupCollapsible,
    menuUniqueOpened,
    navbarFixed,
    sidebarFixed,
    sidebarPushContent,
    reset,
  }
})
