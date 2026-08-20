/** Field nhân sự trong thông tin điều phối (gia_tri = mảng user id) */
export const DIEU_PHOI_STAFF_KEYS = new Set(['tho_chup', 'tho_make', 'tho_edit', 'quay_phim'])

/** Field lịch quay chụp hiển thị ở bước tạo/sửa hợp đồng */
export const LICH_QUAY_CHUP_KEYS = [
  'buoi_chup',
  'gio_chup',
  'ngay_chup',
  'ngay_tra_demo',
  'ngay_tra_chinh_thuc',
  'dia_diem_chup',
  'ghi_chu_trang_phuc_phu_kien',
  'ghi_chu_dieu_phoi',
]

/** Field metadata tên buổi chụp, lưu trong từng phần tử thong_tin_dieu_phoi */
export const TEN_LICH_KEY = 'ten_lich'
export const TEN_LICH_MAX_LENGTH = 30
export const MAX_LICH_QUAY_CHUP = 6

export const BUOI_CHUP_OPTIONS = [
  { value: 'sang', label: 'Sáng' },
  { value: 'chieu', label: 'Chiều' },
  { value: 'toi', label: 'Tối' },
]

export function isDieuPhoiSessionMap(value) {
  return Boolean(value) && typeof value === 'object' && !Array.isArray(value)
}

/**
 * Chuẩn hóa thong_tin_dieu_phoi về mảng buổi chụp.
 * Dữ liệu cũ (object 1 buổi) được bọc thành [object].
 */
export function normalizeDieuPhoiSessions(raw) {
  if (Array.isArray(raw)) {
    return raw.filter((item) => isDieuPhoiSessionMap(item))
  }
  if (isDieuPhoiSessionMap(raw) && Object.keys(raw).length) {
    return [raw]
  }
  return []
}

export function defaultTenLichQuayChup(index = 0) {
  return `Lịch quay chụp ${index + 1}`
}

export function normalizeTenLichQuayChup(tenLich, index = 0) {
  const text = String(tenLich || '').trim().slice(0, TEN_LICH_MAX_LENGTH)
  return text || defaultTenLichQuayChup(index)
}

export function getTenLichQuayChup(session, index = 0) {
  if (typeof session?._ten_lich === 'string' && session._ten_lich.trim()) {
    return normalizeTenLichQuayChup(session._ten_lich, index)
  }
  const item = session?.[TEN_LICH_KEY]
  if (typeof item === 'string' && item.trim()) {
    return normalizeTenLichQuayChup(item, index)
  }
  if (item && typeof item === 'object' && !Array.isArray(item)) {
    const value = item.gia_tri
    if (value != null && String(value).trim()) {
      return normalizeTenLichQuayChup(value, index)
    }
  }
  return defaultTenLichQuayChup(index)
}

export function buildTenLichField(tenLich, index = 0) {
  return {
    su_dung: true,
    ten_thong_tin: 'Tên lịch',
    loai_du_lieu: 'string',
    gia_tri: normalizeTenLichQuayChup(tenLich, index),
  }
}

export function getDieuPhoiGiaTriFromSession(session, fieldKey) {
  const item = session?.[fieldKey]
  if (!item || typeof item !== 'object' || Array.isArray(item)) return null
  return item.gia_tri !== undefined ? item.gia_tri : null
}

function pushUnique(result, seen, value) {
  const key = typeof value === 'object' ? JSON.stringify(value) : String(value)
  if (seen.has(key)) return
  seen.add(key)
  result.push(value)
}

/** Gom gia_tri của 1 field từ mọi buổi chụp (array field thì flatten). */
export function collectDieuPhoiGiaTri(raw, fieldKey) {
  const result = []
  const seen = new Set()
  for (const session of normalizeDieuPhoiSessions(raw)) {
    const value = getDieuPhoiGiaTriFromSession(session, fieldKey)
    if (value == null || value === '') continue
    if (Array.isArray(value)) {
      for (const item of value) {
        if (item == null || item === '') continue
        pushUnique(result, seen, item)
      }
    } else {
      pushUnique(result, seen, value)
    }
  }
  return result
}

export function firstDieuPhoiSession(raw) {
  return normalizeDieuPhoiSessions(raw)[0] || null
}

export function getDieuPhoiFieldMeta(raw, fieldKey) {
  for (const session of normalizeDieuPhoiSessions(raw)) {
    const item = session?.[fieldKey]
    if (item && typeof item === 'object' && !Array.isArray(item)) return item
  }
  return null
}

export function cloneDieuPhoiMap(source) {
  if (!isDieuPhoiSessionMap(source)) return {}
  return JSON.parse(JSON.stringify(source))
}

export function mergeDieuPhoiSessions(existingRaw, nextSessions) {
  const existing = normalizeDieuPhoiSessions(existingRaw)
  const next = Array.isArray(nextSessions) ? nextSessions : []
  return next.map((session, index) => ({
    ...(existing[index] || {}),
    ...(isDieuPhoiSessionMap(session) ? session : {}),
  }))
}
