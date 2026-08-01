import { reactive } from 'vue'
import { ElMessage } from 'element-plus'

const STORAGE_PREFIX = 'pandio.tableColumns.'

/**
 * Ẩn/hiện cột bảng + lưu localStorage theo từng trang.
 *
 * @param {string} storageKey — khóa duy nhất (vd: 'van-hanh-cuoi.concept-list')
 * @param {Array<{ key: string, label: string }>} columns — cột cấu hình được (không gồm STT / selection / Thao tác)
 */
export function useTableColumns(storageKey, columns) {
  function defaultVisibility() {
    return Object.fromEntries(columns.map((col) => [col.key, true]))
  }

  function loadVisibility() {
    const defaults = defaultVisibility()
    try {
      const raw = localStorage.getItem(STORAGE_PREFIX + storageKey)
      if (!raw) return defaults
      const saved = JSON.parse(raw)
      if (!Array.isArray(saved)) return defaults
      const next = Object.fromEntries(columns.map((col) => [col.key, saved.includes(col.key)]))
      if (!Object.values(next).some(Boolean)) return defaults
      return next
    } catch {
      return defaults
    }
  }

  const state = reactive({
    columns,
    visibility: loadVisibility(),
    dialogVisible: false,
    draft: defaultVisibility(),

    isColumnVisible(key) {
      return state.visibility[key] !== false
    },

    openConfig() {
      for (const col of columns) {
        state.draft[col.key] = state.visibility[col.key] !== false
      }
      state.dialogVisible = true
    },

    selectAllDraft() {
      for (const col of columns) {
        state.draft[col.key] = true
      }
    },

    saveConfig() {
      const selected = columns.filter((col) => state.draft[col.key]).map((col) => col.key)
      if (!selected.length) {
        ElMessage.warning('Vui lòng chọn ít nhất một cột')
        return
      }
      for (const col of columns) {
        state.visibility[col.key] = !!state.draft[col.key]
      }
      localStorage.setItem(STORAGE_PREFIX + storageKey, JSON.stringify(selected))
      state.dialogVisible = false
      ElMessage.success('Đã lưu cấu hình cột')
    },
  })

  return state
}
