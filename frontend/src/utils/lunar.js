import { getLunarDate } from '@dqcai/vn-lunar'

/**
 * @param {Date|string} date — Date hoặc chuỗi YYYY-MM-DD từ el-calendar
 * @returns {{ day: number, month: number, year: number } | null}
 */
function toSolarParts(date) {
  if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}/.test(date)) {
    const [year, month, day] = date.slice(0, 10).split('-').map(Number)
    return { day, month, year }
  }

  const d = date instanceof Date ? date : new Date(date)
  if (Number.isNaN(d.getTime())) return null

  return {
    day: d.getDate(),
    month: d.getMonth() + 1,
    year: d.getFullYear(),
  }
}

/**
 * Chuyển ngày dương lịch sang nhãn âm lịch ngắn (hiển thị trên calendar).
 * - Ngày mùng 1: "1/tháng" (tháng nhuận thêm dấu +)
 * - Ngày khác: chỉ số ngày âm
 *
 * @param {Date|string} date
 * @returns {string}
 */
export function formatLunarLabel(date) {
  const lunar = getLunarParts(date)
  if (!lunar) return ''

  if (lunar.day === 1) {
    return `1/${lunar.month}${lunar.leap ? '+' : ''}`
  }

  return String(lunar.day)
}

/**
 * Có phải mùng 1 âm lịch (đầu tháng âm) không.
 * @param {Date|string} date
 * @returns {boolean}
 */
export function isLunarMonthStart(date) {
  const lunar = getLunarParts(date)
  return Boolean(lunar && lunar.day === 1)
}

/**
 * Nhãn tooltip ngày âm lịch đầy đủ (ngày / tháng / năm).
 * @param {Date|string} date
 * @returns {string}
 */
export function formatLunarTooltip(date) {
  const label = formatLunarDate(date)
  return label ? `Âm lịch: ${label}` : ''
}

/**
 * Ngày âm lịch dạng dd/mm/yyyy (tháng nhuận ghi rõ).
 * @param {Date|string} date
 * @returns {string}
 */
export function formatLunarDate(date) {
  const lunar = getLunarParts(date)
  if (!lunar) return ''

  const day = String(lunar.day).padStart(2, '0')
  const month = String(lunar.month).padStart(2, '0')
  const monthLabel = lunar.leap ? `${month} nhuận` : month
  return `${day}/${monthLabel}/${lunar.year}`
}

/**
 * @param {Date|string} date
 * @returns {{ day: number, month: number, year: number, leap?: boolean } | null}
 */
function getLunarParts(date) {
  const solar = toSolarParts(date)
  if (!solar) return null
  return getLunarDate(solar.day, solar.month, solar.year) || null
}
