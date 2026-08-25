export const DATE_PRESETS = [
  { key: 'this_month', label: 'Tháng này' },
  { key: 'last_month', label: 'Tháng trước' },
]

/**
 * @param {Date} date
 * @returns {string} YYYY-MM-DD
 */
export function toYmd(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

/**
 * Parse YYYY-MM-DD (hoặc datetime) thành Date local, tránh lệch timezone.
 * @param {string} value
 * @returns {Date|null}
 */
export function parseLocalDate(value) {
  const raw = String(value || '').slice(0, 10)
  const [y, m, d] = raw.split('-').map(Number)
  if (!y || !m || !d) return null
  return new Date(y, m - 1, d)
}

/**
 * @param {string} value
 * @returns {string}
 */
export function dayKey(value) {
  return String(value || '').slice(0, 10)
}

/**
 * @param {Date} date
 * @returns {[string, string]}
 */
export function monthRangeOf(date) {
  const d = date instanceof Date ? date : new Date(date)
  const start = new Date(d.getFullYear(), d.getMonth(), 1)
  const end = new Date(d.getFullYear(), d.getMonth() + 1, 0)
  return [toYmd(start), toYmd(end)]
}

/**
 * @param {'this_month'|'last_month'} key
 * @returns {[string, string]}
 */
export function getPresetRange(key) {
  const now = new Date()
  if (key === 'last_month') {
    return monthRangeOf(new Date(now.getFullYear(), now.getMonth() - 1, 1))
  }
  return monthRangeOf(now)
}

/**
 * @param {[string, string]|null|undefined} range
 * @returns {string|null}
 */
export function activePresetKey(range) {
  if (!range?.[0] || !range?.[1]) return null
  for (const preset of DATE_PRESETS) {
    const expected = getPresetRange(preset.key)
    if (expected[0] === range[0] && expected[1] === range[1]) {
      return preset.key
    }
  }
  return null
}

/**
 * @param {string} tuNgay
 * @param {string} denNgay
 * @returns {string[]}
 */
export function eachDateInRange(tuNgay, denNgay) {
  const start = parseLocalDate(tuNgay)
  const end = parseLocalDate(denNgay)
  if (!start || !end || start > end) return []

  const days = []
  const cursor = new Date(start)
  while (cursor <= end) {
    days.push(toYmd(cursor))
    cursor.setDate(cursor.getDate() + 1)
  }
  return days
}

/**
 * @param {string} ymd
 * @returns {string}
 */
export function formatDateVi(ymd) {
  const raw = dayKey(ymd)
  const [y, m, d] = raw.split('-')
  if (!y || !m || !d) return raw || '—'
  return `${d}/${m}/${y}`
}

const WEEKDAYS = [
  'Chủ nhật',
  'Thứ hai',
  'Thứ ba',
  'Thứ tư',
  'Thứ năm',
  'Thứ sáu',
  'Thứ bảy',
]

/**
 * @param {string} ymd
 * @returns {string}
 */
export function weekdayLabel(ymd) {
  const date = parseLocalDate(ymd)
  if (!date) return ''
  return WEEKDAYS[date.getDay()]
}
