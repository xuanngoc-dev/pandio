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

/** Field concept / trang phục lưu kèm từng buổi trong thong_tin_dieu_phoi */
export const CONCEPT_FIELD_KEY = 'concepts'
export const TRANG_PHUC_FIELD_KEY = 'trang_phucs'

/** Field loại quay chụp (danh_muc_loai_quay_chup) lưu kèm từng buổi */
export const LOAI_QUAY_CHUP_KEY = 'loai_quay_chup'

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

export function isDieuPhoiExtraSessionKey(key) {
  return (
    key === TEN_LICH_KEY ||
    key === CONCEPT_FIELD_KEY ||
    key === TRANG_PHUC_FIELD_KEY ||
    key === LOAI_QUAY_CHUP_KEY ||
    String(key).startsWith('_')
  )
}

function normalizeDateValue(value) {
  if (value == null || value === '') return null
  const text = String(value).slice(0, 10)
  return /^\d{4}-\d{2}-\d{2}$/.test(text) ? text : null
}

export function normalizeLoaiQuayChupGiaTri(raw) {
  if (raw == null || raw === '') return null
  if (typeof raw === 'number' || (typeof raw === 'string' && /^\d+$/.test(String(raw).trim()))) {
    const id = Number(raw)
    if (!Number.isFinite(id) || id <= 0) return null
    return { id, ten_dich_vu: '' }
  }
  if (typeof raw !== 'object' || Array.isArray(raw)) return null
  const id = Number(raw.id ?? raw.danh_muc_loai_quay_chup_id)
  if (!Number.isFinite(id) || id <= 0) return null
  return {
    id,
    ten_dich_vu: String(raw.ten_dich_vu || '').trim(),
  }
}

export function parseSessionLoaiQuayChup(session) {
  const fromField = getDieuPhoiGiaTriFromSession(session, LOAI_QUAY_CHUP_KEY)
  if (fromField != null && fromField !== '') {
    return normalizeLoaiQuayChupGiaTri(fromField)
  }
  return normalizeLoaiQuayChupGiaTri(session?.[LOAI_QUAY_CHUP_KEY])
}

export function buildLoaiQuayChupField(value) {
  return {
    su_dung: true,
    ten_thong_tin: 'Loại quay chụp',
    loai_du_lieu: 'object',
    gia_tri: normalizeLoaiQuayChupGiaTri(value),
  }
}

export function formatLoaiQuayChupLabel(value) {
  const item = normalizeLoaiQuayChupGiaTri(value)
  if (!item) return ''
  return item.ten_dich_vu || `Loại #${item.id}`
}

export function getLoaiQuayChupId(value) {
  return normalizeLoaiQuayChupGiaTri(value)?.id ?? null
}

export function loaiQuayChupRequiredRule() {
  return {
    required: true,
    validator(_rule, value, callback) {
      if (!getLoaiQuayChupId(value)) {
        callback(new Error('Vui lòng chọn loại quay chụp'))
        return
      }
      callback()
    },
    trigger: 'change',
  }
}

export function buildConceptField(items) {
  return {
    su_dung: true,
    ten_thong_tin: 'Concept',
    loai_du_lieu: 'array',
    gia_tri: Array.isArray(items) ? items : [],
  }
}

export function buildTrangPhucField(items) {
  return {
    su_dung: true,
    ten_thong_tin: 'Trang phục',
    loai_du_lieu: 'array',
    gia_tri: Array.isArray(items) ? items : [],
  }
}

export function parseSessionConceptItems(session) {
  const raw = getDieuPhoiGiaTriFromSession(session, CONCEPT_FIELD_KEY)
  if (!Array.isArray(raw)) return []
  return raw
    .map((item) => {
      if (item == null) return null
      if (typeof item === 'number') {
        return { id: item, ten: `Concept #${item}`, dia_diem: '' }
      }
      if (typeof item !== 'object') return null
      const id = Number(item.id ?? item.concept_id)
      if (!Number.isFinite(id) || id <= 0) return null
      return {
        id,
        ten: item.ten || item.ten_concept || `Concept #${id}`,
        dia_diem: item.dia_diem || '',
      }
    })
    .filter(Boolean)
}

export function parseSessionTrangPhucItems(session) {
  const raw = getDieuPhoiGiaTriFromSession(session, TRANG_PHUC_FIELD_KEY)
  if (!Array.isArray(raw)) return []
  return raw
    .map((item) => {
      if (item == null || typeof item !== 'object') return null
      const id = Number(item.id ?? item.trang_phuc_id)
      if (!Number.isFinite(id) || id <= 0) return null
      return {
        id,
        ten: item.ten || item.ten_san_pham || `Trang phục #${id}`,
        ma_san_pham: item.ma_san_pham || '',
        gia_cho_thue: Number(item.gia_cho_thue) || 0,
        ngay_bat_dau: normalizeDateValue(item.ngay_bat_dau),
        ngay_ket_thuc: normalizeDateValue(item.ngay_ket_thuc),
      }
    })
    .filter(Boolean)
}

export function mapHopDongConceptRows(rows, ngaySuDung = null) {
  const target = normalizeDateValue(ngaySuDung)
  const list = Array.isArray(rows) ? rows : []
  return list
    .filter((row) => {
      const rowDate = normalizeDateValue(row?.ngay_su_dung)
      if (target) return rowDate === target
      return !rowDate
    })
    .map((row) => {
      const catalog = row.concept || {}
      const id = Number(row.concept_id)
      return {
        id,
        ten: catalog.ten_concept || `Concept #${id}`,
        dia_diem: catalog.dia_diem || '',
      }
    })
    .filter((row) => Number.isFinite(row.id) && row.id > 0)
}

export function mapHopDongTrangPhucRows(rows, ngaySuDung = null) {
  const target = normalizeDateValue(ngaySuDung)
  const list = Array.isArray(rows) ? rows : []
  return list
    .filter((row) => {
      const rowDate = normalizeDateValue(row?.ngay_su_dung)
      if (target) return rowDate === target
      return !rowDate
    })
    .map((row) => {
      const catalog = row.trang_phuc || {}
      const id = Number(row.trang_phuc_id)
      return {
        id,
        ten: catalog.ten_san_pham || `Trang phục #${id}`,
        ma_san_pham: catalog.ma_san_pham || '',
        gia_cho_thue: Number(catalog.gia_cho_thue) || 0,
        ngay_bat_dau: normalizeDateValue(row.ngay_bat_dau),
        ngay_ket_thuc: normalizeDateValue(row.ngay_ket_thuc),
      }
    })
    .filter((row) => Number.isFinite(row.id) && row.id > 0)
}

export function formatConceptDieuPhoiLabel(item) {
  if (item == null) return ''
  if (typeof item === 'string' || typeof item === 'number') return String(item)
  return item.ten || item.ten_concept || ''
}

export function formatTrangPhucDieuPhoiLabel(item) {
  if (item == null) return ''
  if (typeof item === 'string' || typeof item === 'number') return String(item)
  const ten = item.ten || item.ten_san_pham || ''
  const ma = String(item.ma_san_pham || '').trim()
  if (!ma) return ten
  return ten ? `${ten} - [${ma}]` : `[${ma}]`
}
