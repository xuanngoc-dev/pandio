import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { getMenuPageTabs, tabPermissionKey } from '@/data/cauHinhQuanTri'

/**
 * Lọc tabs theo vai_tro.cau_hinh.
 * Danh sách tabs lấy từ menu.json (field `tabs`) — thêm tab ở menu.json là VaiTro + màn tự nhận.
 *
 * @param {string} menuIndex path menu (VD: /nhan-su/cham-cong-nghi-phep)
 * @param {{ useQuery?: boolean, defaultTab?: string, tabs?: Array<{ name: string, label: string }> }} [options]
 */
export function usePageTabs(menuIndex, options = {}) {
  const authStore = useAuthStore()
  const route = useRoute()
  const tabs = options.tabs?.length ? options.tabs : getMenuPageTabs(menuIndex)
  const { useQuery = false, defaultTab = tabs[0]?.name } = options

  const visibleTabs = computed(() => {
    const allowed = authStore.allowedCauHinhPaths
    if (allowed === null) return tabs
    const set = new Set(allowed)
    return tabs.filter((tab) => set.has(tabPermissionKey(menuIndex, tab.name)))
  })

  function pickInitial() {
    const list = visibleTabs.value
    if (!list.length) return ''
    if (useQuery) {
      const q = String(route.query.tab || '')
      if (q && list.some((t) => t.name === q)) return q
    }
    if (defaultTab && list.some((t) => t.name === defaultTab)) return defaultTab
    return list[0].name
  }

  const activeTab = ref(pickInitial())

  watch(
    visibleTabs,
    (list) => {
      if (!list.length) {
        activeTab.value = ''
        return
      }
      if (!list.some((t) => t.name === activeTab.value)) {
        activeTab.value = list[0].name
      }
    },
    { immediate: true },
  )

  function hasTab(name) {
    return visibleTabs.value.some((t) => t.name === name)
  }

  return {
    visibleTabs,
    activeTab,
    hasTab,
  }
}
