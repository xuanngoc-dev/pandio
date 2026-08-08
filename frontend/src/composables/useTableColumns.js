import { reactive } from 'vue'
import { ElMessage } from 'element-plus'

const STORAGE_PREFIX = 'pandio.tableColumns.'

/**
 * Nhóm cột theo `group` (giữ thứ tự xuất hiện lần đầu).
 * Cột không có group → nhóm mặc định (không hiện tiêu đề nếu chỉ có 1 nhóm).
 *
 * @param {Array<{ key: string, label: string, group?: string }>} columns
 * @returns {Array<{ key: string, label: string | null, columns: typeof columns }>}
 */
function buildColumnGroups(columns) {
  const groups = []
  const indexByKey = new Map()

  for (const col of columns) {
    const label = col.group || null
    const key = label || '__default__'
    let group = indexByKey.get(key)
    if (!group) {
      group = { key, label, columns: [] }
      indexByKey.set(key, group)
      groups.push(group)
    }
    group.columns.push(col)
  }

  return groups
}

/**
 * Ẩn/hiện cột bảng + lưu localStorage theo từng trang.
 *
 * @param {string} storageKey — khóa duy nhất (vd: 'van-hanh-cuoi.concept-list')
 * @param {Array<{ key: string, label: string, defaultVisible?: boolean, group?: string }>} columns
 *   — cột cấu hình được (không gồm STT / selection / Thao tác).
 *   `defaultVisible: false` ẩn cột khi chưa có cấu hình lưu.
 *   `group` — nhãn khu vực trong dialog cấu hình cột (tuỳ chọn).
 */
export function useTableColumns(storageKey, columns) {
  function isDefaultVisible(col) {
    return col.defaultVisible !== false
  }

  function defaultVisibility() {
    return Object.fromEntries(columns.map((col) => [col.key, isDefaultVisible(col)]))
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

  const columnGroups = buildColumnGroups(columns)

  const state = reactive({
    columns,
    columnGroups,
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

    selectGroupDraft(groupKey, checked = true) {
      const group = columnGroups.find((item) => item.key === groupKey)
      if (!group) return
      for (const col of group.columns) {
        state.draft[col.key] = checked
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
