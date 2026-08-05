import { reactive } from 'vue'
import { ElMessage } from 'element-plus'
import { SEARCH_SOURCES } from '@/utils/quickSearchEntities'

const STORAGE_KEY = 'pandio.quickSearch.sources'

/** Các mục tìm kiếm có thể bật/tắt (gồm chức năng + entity). */
export const QUICK_SEARCH_OPTIONS = [
  { key: 'functions', label: 'Chức năng', icon: 'Menu' },
  ...SEARCH_SOURCES.map((source) => ({
    key: source.key,
    label: source.label,
    icon: source.icon,
  })),
]

function defaultVisibility() {
  return Object.fromEntries(QUICK_SEARCH_OPTIONS.map((opt) => [opt.key, true]))
}

function loadVisibility() {
  const defaults = defaultVisibility()
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return defaults
    const saved = JSON.parse(raw)
    if (!Array.isArray(saved)) return defaults
    const next = Object.fromEntries(
      QUICK_SEARCH_OPTIONS.map((opt) => [opt.key, saved.includes(opt.key)])
    )
    if (!Object.values(next).some(Boolean)) return defaults
    return next
  } catch {
    return defaults
  }
}

/**
 * Bật/tắt nguồn tìm kiếm nhanh + lưu localStorage.
 */
export function useQuickSearchSources() {
  const state = reactive({
    options: QUICK_SEARCH_OPTIONS,
    visibility: loadVisibility(),
    dialogVisible: false,
    draft: defaultVisibility(),

    isEnabled(key) {
      return state.visibility[key] !== false
    },

    enabledEntityKeys() {
      return SEARCH_SOURCES.map((s) => s.key).filter((key) => state.isEnabled(key))
    },

    enabledSources() {
      return SEARCH_SOURCES.filter((source) => state.isEnabled(source.key))
    },

    openConfig() {
      for (const opt of QUICK_SEARCH_OPTIONS) {
        state.draft[opt.key] = state.visibility[opt.key] !== false
      }
      state.dialogVisible = true
    },

    selectAllDraft() {
      for (const opt of QUICK_SEARCH_OPTIONS) {
        state.draft[opt.key] = true
      }
    },

    clearDraft() {
      for (const opt of QUICK_SEARCH_OPTIONS) {
        state.draft[opt.key] = false
      }
    },

    saveConfig() {
      const selected = QUICK_SEARCH_OPTIONS.filter((opt) => state.draft[opt.key]).map(
        (opt) => opt.key
      )
      if (!selected.length) {
        ElMessage.warning('Vui lòng chọn ít nhất một mục tìm kiếm')
        return false
      }
      for (const opt of QUICK_SEARCH_OPTIONS) {
        state.visibility[opt.key] = !!state.draft[opt.key]
      }
      localStorage.setItem(STORAGE_KEY, JSON.stringify(selected))
      state.dialogVisible = false
      ElMessage.success('Đã lưu cấu hình tìm kiếm')
      return true
    },
  })

  return state
}
