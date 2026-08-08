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

function readSelectedKeys(storageKey) {
  try {
    const raw = localStorage.getItem(STORAGE_PREFIX + storageKey)
    if (!raw) return null
    const saved = JSON.parse(raw)
    return Array.isArray(saved) ? saved : null
  } catch {
    return null
  }
}

/**
 * @param {Array<{ key: string, defaultVisible?: boolean }>} columns
 * @param {string} storageKey
 * @param {{ allowAllHidden?: boolean }} [opts]
 */
function visibilityForColumns(columns, storageKey, opts = {}) {
  const { allowAllHidden = false } = opts
  const defaults = Object.fromEntries(
    columns.map((col) => [col.key, col.defaultVisible !== false]),
  )
  const saved = readSelectedKeys(storageKey)
  if (!saved) return defaults
  const next = Object.fromEntries(columns.map((col) => [col.key, saved.includes(col.key)]))
  if (!allowAllHidden && !Object.values(next).some(Boolean)) return defaults
  return next
}

/**
 * Ẩn/hiện cột bảng + lưu localStorage theo từng trang.
 *
 * @param {string} storageKey — khóa duy nhất (vd: 'van-hanh-cuoi.concept-list')
 * @param {Array<{ key: string, label: string, defaultVisible?: boolean, group?: string }>} baseColumns
 *   — cột cố định (không gồm STT / selection / Thao tác).
 * @param {{ onBeforeOpen?: () => void | Promise<void> }} [options]
 */
export function useTableColumns(storageKey, baseColumns, options = {}) {
  const { onBeforeOpen } = options

  let extraColumns = []
  /** @type {string | null} */
  let extraStorageKey = null

  function allColumns() {
    return [...baseColumns, ...extraColumns]
  }

  function buildVisibility() {
    const baseVis = visibilityForColumns(baseColumns, storageKey)
    if (!extraColumns.length) return { ...baseVis }

    const extraVis = extraStorageKey
      ? visibilityForColumns(extraColumns, extraStorageKey, { allowAllHidden: true })
      : Object.fromEntries(extraColumns.map((col) => [col.key, col.defaultVisible !== false]))

    return { ...baseVis, ...extraVis }
  }

  function syncDraft() {
    for (const col of allColumns()) {
      state.draft[col.key] = state.visibility[col.key] !== false
    }
  }

  function applyColumnsState() {
    const cols = allColumns()
    state.columns = cols
    state.columnGroups = buildColumnGroups(cols)
    state.visibility = buildVisibility()
  }

  const state = reactive({
    columns: allColumns(),
    columnGroups: buildColumnGroups(allColumns()),
    visibility: {},
    dialogVisible: false,
    configLoading: false,
    draft: {},

    isColumnVisible(key) {
      return state.visibility[key] !== false
    },

    /**
     * Gắn/gỡ cột động (vd: chi tiết theo loại HĐ).
     * @param {Array<{ key: string, label: string, defaultVisible?: boolean, group?: string }>} columns
     * @param {{ storageKey?: string | null }} [opts]
     */
    setExtraColumns(columns = [], opts = {}) {
      extraColumns = Array.isArray(columns) ? [...columns] : []
      extraStorageKey = opts.storageKey || null
      applyColumnsState()
      if (state.dialogVisible) syncDraft()
    },

    async openConfig() {
      state.dialogVisible = true
      state.configLoading = true
      try {
        if (typeof onBeforeOpen === 'function') {
          await onBeforeOpen()
        }
        syncDraft()
      } finally {
        state.configLoading = false
      }
    },

    selectAllDraft() {
      for (const col of allColumns()) {
        state.draft[col.key] = true
      }
    },

    selectGroupDraft(groupKey, checked = true) {
      const group = state.columnGroups.find((item) => item.key === groupKey)
      if (!group) return
      for (const col of group.columns) {
        state.draft[col.key] = checked
      }
    },

    saveConfig() {
      const cols = allColumns()
      const selected = cols.filter((col) => state.draft[col.key]).map((col) => col.key)
      if (!selected.length) {
        ElMessage.warning('Vui lòng chọn ít nhất một cột')
        return
      }

      const baseKeySet = new Set(baseColumns.map((col) => col.key))
      const extraKeySet = new Set(extraColumns.map((col) => col.key))
      const baseSelected = selected.filter((key) => baseKeySet.has(key))
      const extraSelected = selected.filter((key) => extraKeySet.has(key))

      if (!baseSelected.length) {
        ElMessage.warning('Vui lòng chọn ít nhất một cột')
        return
      }

      for (const col of cols) {
        state.visibility[col.key] = !!state.draft[col.key]
      }

      localStorage.setItem(STORAGE_PREFIX + storageKey, JSON.stringify(baseSelected))
      if (extraStorageKey) {
        localStorage.setItem(STORAGE_PREFIX + extraStorageKey, JSON.stringify(extraSelected))
      }

      state.dialogVisible = false
      ElMessage.success('Đã lưu cấu hình cột')
    },
  })

  state.visibility = buildVisibility()
  syncDraft()

  return state
}
