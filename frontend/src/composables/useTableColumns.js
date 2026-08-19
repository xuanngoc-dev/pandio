import { reactive } from 'vue'
import { ElMessage } from 'element-plus'

const STORAGE_PREFIX = 'pandio.tableColumns.'
const FIXED_STORAGE_PREFIX = 'pandio.tableFixed.'

export const TABLE_COLUMN_SETTINGS_KEY = 'tableColumnSettings'

export const FIXED_COL = {
  selection: '__selection__',
  stt: '__stt__',
  actions: '__actions__',
}

const SPECIAL_LABEL_TO_KEY = {
  Checkbox: FIXED_COL.selection,
  'Chọn hàng': FIXED_COL.selection,
  STT: FIXED_COL.stt,
  'Số thứ tự': FIXED_COL.stt,
  'Thao tác': FIXED_COL.actions,
}

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

function readFixedKeys(storageKey, defaults) {
  try {
    const raw = localStorage.getItem(FIXED_STORAGE_PREFIX + storageKey)
    if (!raw) return { left: [...defaults.left], right: [...defaults.right] }
    const saved = JSON.parse(raw)
    if (!saved || typeof saved !== 'object') {
      return { left: [...defaults.left], right: [...defaults.right] }
    }
    return {
      left: Array.isArray(saved.left) ? saved.left : [...defaults.left],
      right: Array.isArray(saved.right) ? saved.right : [...defaults.right],
    }
  } catch {
    return { left: [...defaults.left], right: [...defaults.right] }
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

function uniqueKeys(keys) {
  const seen = new Set()
  const result = []
  for (const key of keys) {
    if (!key || seen.has(key)) continue
    seen.add(key)
    result.push(key)
  }
  return result
}

function resolvePinOptions(columns, options = {}) {
  const pin = options.pin || {}
  const hasSelection = pin.selection !== false
  const hasStt = pin.stt !== false
  const hasActions = pin.actions !== false

  const specialLeft = [
    hasSelection ? { key: FIXED_COL.selection, label: 'Checkbox' } : null,
    hasStt ? { key: FIXED_COL.stt, label: 'Số thứ tự' } : null,
  ].filter(Boolean)

  const specialRight = [
    hasActions ? { key: FIXED_COL.actions, label: 'Thao tác' } : null,
  ].filter(Boolean)

  const statusCol = columns.find((col) => col.key === 'trang_thai' || col.label === 'Trạng thái')

  const defaultLeft = uniqueKeys(
    pin.defaultLeft || [
      ...(hasSelection ? [FIXED_COL.selection] : []),
      ...(hasStt ? [FIXED_COL.stt] : []),
    ],
  )
  const defaultRight = uniqueKeys(
    pin.defaultRight || [
      ...(hasActions ? [FIXED_COL.actions] : []),
      ...(statusCol ? [statusCol.key] : []),
    ],
  )

  return { specialLeft, specialRight, defaultLeft, defaultRight }
}

function sanitizeFixedKeys(keys, allowed) {
  const allowedSet = new Set(allowed)
  return uniqueKeys(keys).filter((key) => allowedSet.has(key))
}

/**
 * Ẩn/hiện cột bảng + cấu hình cột cố định (left/right), lưu localStorage theo từng trang.
 *
 * @param {string} storageKey — khóa duy nhất (vd: 'van-hanh-cuoi.concept-list')
 * @param {Array<{ key: string, label: string, defaultVisible?: boolean, group?: string }>} baseColumns
 *   — cột dữ liệu (không gồm STT / selection / Thao tác).
 * @param {{
 *   onBeforeOpen?: () => void | Promise<void>,
 *   pin?: {
 *     selection?: boolean,
 *     stt?: boolean,
 *     actions?: boolean,
 *     defaultLeft?: string[],
 *     defaultRight?: string[],
 *   },
 * }} [options]
 */
export function useTableColumns(storageKey, baseColumns, options = {}) {
  const { onBeforeOpen } = options

  let extraColumns = []
  /** @type {string | null} */
  let extraStorageKey = null

  function allColumns() {
    return [...baseColumns, ...extraColumns]
  }

  function pinOptions() {
    return resolvePinOptions(allColumns(), options)
  }

  function allowedFixedKeys() {
    const { specialLeft, specialRight } = pinOptions()
    return [
      ...specialLeft.map((col) => col.key),
      ...allColumns().map((col) => col.key),
      ...specialRight.map((col) => col.key),
    ]
  }

  function buildVisibility() {
    const baseVis = visibilityForColumns(baseColumns, storageKey)
    if (!extraColumns.length) return { ...baseVis }

    const extraVis = extraStorageKey
      ? visibilityForColumns(extraColumns, extraStorageKey, { allowAllHidden: true })
      : Object.fromEntries(extraColumns.map((col) => [col.key, col.defaultVisible !== false]))

    return { ...baseVis, ...extraVis }
  }

  function buildFixed() {
    const { defaultLeft, defaultRight } = pinOptions()
    const saved = readFixedKeys(storageKey, { left: defaultLeft, right: defaultRight })
    const allowed = allowedFixedKeys()
    const left = sanitizeFixedKeys(saved.left, allowed)
    const right = sanitizeFixedKeys(
      saved.right.filter((key) => !left.includes(key)),
      allowed,
    )
    return { left, right }
  }

  function buildPinGroups() {
    const { specialLeft, specialRight } = pinOptions()
    const dataColumns = allColumns().map((col) => ({ key: col.key, label: col.label }))
    return {
      left: [...specialLeft, ...dataColumns],
      right: [...dataColumns, ...specialRight],
    }
  }

  function syncDraft() {
    for (const col of allColumns()) {
      state.draft[col.key] = state.visibility[col.key] !== false
    }
    state.fixedDraft.left = [...state.fixedLeft]
    state.fixedDraft.right = [...state.fixedRight]
  }

  function applyColumnsState() {
    const cols = allColumns()
    const fixed = buildFixed()
    state.columns = cols
    state.columnGroups = buildColumnGroups(cols)
    state.visibility = buildVisibility()
    state.pinGroups = buildPinGroups()
    state.fixedLeft = fixed.left
    state.fixedRight = fixed.right
    state.fixedSignature = `${fixed.left.join(',')}|${fixed.right.join(',')}`
  }

  function resolvePinKey(key) {
    if (!key) return null
    if (SPECIAL_LABEL_TO_KEY[key]) return SPECIAL_LABEL_TO_KEY[key]
    const match = allColumns().find((col) => col.key === key || col.label === key)
    return match ? match.key : key
  }

  const state = reactive({
    columns: allColumns(),
    columnGroups: buildColumnGroups(allColumns()),
    pinGroups: buildPinGroups(),
    visibility: {},
    fixedLeft: [],
    fixedRight: [],
    fixedSignature: '|',
    dialogVisible: false,
    configLoading: false,
    draft: {},
    fixedDraft: {
      left: [],
      right: [],
    },

    isColumnVisible(key) {
      return state.visibility[key] !== false
    },

    /**
     * @param {string} keyOrLabel
     * @returns {'left' | 'right' | undefined}
     */
    columnFixed(keyOrLabel) {
      const key = resolvePinKey(keyOrLabel)
      if (!key) return undefined
      if (state.fixedLeft.includes(key)) return 'left'
      if (state.fixedRight.includes(key)) return 'right'
      return undefined
    },

    isPinColumn(keyOrLabel) {
      const key = resolvePinKey(keyOrLabel)
      if (!key) return false
      return allowedFixedKeys().includes(key)
    },

    isFixedDraftChecked(key, side) {
      return state.fixedDraft[side].includes(key)
    },

    toggleFixedDraft(key, side, checked) {
      const other = side === 'left' ? 'right' : 'left'
      if (checked) {
        if (!state.fixedDraft[side].includes(key)) {
          state.fixedDraft[side] = [...state.fixedDraft[side], key]
        }
        state.fixedDraft[other] = state.fixedDraft[other].filter((item) => item !== key)
        return
      }
      state.fixedDraft[side] = state.fixedDraft[side].filter((item) => item !== key)
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

      const allowed = allowedFixedKeys()
      const nextLeft = sanitizeFixedKeys(state.fixedDraft.left, allowed)
      const nextRight = sanitizeFixedKeys(
        state.fixedDraft.right.filter((key) => !nextLeft.includes(key)),
        allowed,
      )
      state.fixedLeft = nextLeft
      state.fixedRight = nextRight
      state.fixedSignature = `${nextLeft.join(',')}|${nextRight.join(',')}`

      localStorage.setItem(STORAGE_PREFIX + storageKey, JSON.stringify(baseSelected))
      localStorage.setItem(
        FIXED_STORAGE_PREFIX + storageKey,
        JSON.stringify({ left: nextLeft, right: nextRight }),
      )
      if (extraStorageKey) {
        localStorage.setItem(STORAGE_PREFIX + extraStorageKey, JSON.stringify(extraSelected))
      }

      state.dialogVisible = false
      ElMessage.success('Đã lưu cấu hình cột')
    },
  })

  applyColumnsState()
  syncDraft()

  return state
}
