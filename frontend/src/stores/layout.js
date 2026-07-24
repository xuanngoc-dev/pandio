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

  function persist() {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({
        menuGroupCollapsible: menuGroupCollapsible.value,
        menuUniqueOpened: menuUniqueOpened.value,
        navbarFixed: navbarFixed.value,
        sidebarFixed: sidebarFixed.value,
      })
    )
  }

  watch(
    [menuGroupCollapsible, menuUniqueOpened, navbarFixed, sidebarFixed],
    persist
  )

  function reset() {
    menuGroupCollapsible.value = defaults.menuGroupCollapsible
    menuUniqueOpened.value = defaults.menuUniqueOpened
    navbarFixed.value = defaults.navbarFixed
    sidebarFixed.value = defaults.sidebarFixed
  }

  return {
    menuGroupCollapsible,
    menuUniqueOpened,
    navbarFixed,
    sidebarFixed,
    reset,
  }
})
