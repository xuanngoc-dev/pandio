export const THO_DUNG_VIDEO_KEY = 'tho_dung_video'
export const THO_DUNG_VIDEO_NGOAI_KEY = 'tho_dung_video_ngoai'

/** Field nhân sự trong thông tin điều phối (gia_tri = mảng user id) */
export const DIEU_PHOI_STAFF_KEYS = new Set([
  'tho_chup',
  'tho_make',
  'tho_edit',
  'quay_phim',
  THO_DUNG_VIDEO_KEY,
])

export function staffSelectMax(field) {
  if (!field) return null
  if (field.key === THO_DUNG_VIDEO_KEY) return 1
  const max = Number(field.gia_tri_toi_da)
  return Number.isFinite(max) && max > 0 ? max : null
}

export function clampStaffArrayValue(field, value) {
  const list = Array.isArray(value)
    ? value.filter((item) => item != null && item !== '')
    : value == null || value === ''
      ? []
      : [value]
  const max = staffSelectMax(field)
  if (max != null) return list.slice(0, max)
  return list
}

export function insertDieuPhoiSchemaFields(schema, inserts, afterKey) {
  const source = schema && typeof schema === 'object' && !Array.isArray(schema) ? { ...schema } : {}
  const pending = (inserts || []).filter((field) => field?.key && !source[field.key])
  if (!pending.length) return source

  const writePending = (target) => {
    for (const field of pending) {
      target[field.key] = {
        su_dung: true,
        ten_thong_tin: field.ten_thong_tin || field.key,
        loai_du_lieu: field.loai_du_lieu || 'string',
        gia_tri: field.loai_du_lieu === 'array' ? [] : null,
      }
      if (field.gia_tri_toi_da != null) {
        target[field.key].gia_tri_toi_da = field.gia_tri_toi_da
      }
    }
  }

  const result = {}
  let inserted = false
  for (const [key, value] of Object.entries(source)) {
    result[key] = value
    if (key === afterKey) {
      writePending(result)
      inserted = true
    }
  }
  if (!inserted) writePending(result)
  return result
}

/** Trạng thái workflow điều phối, lưu ở envelope thong_tin_dieu_phoi */
export const TRANG_THAI_DIEU_PHOI_KEY = 'trang_thai_dieu_phoi'
export const TRANG_THAI_DIEU_PHOI_CHO_NHAN = 'cho_nhan'
export const TRANG_THAI_DIEU_PHOI_TIEN_KY = 'tien_ky'
export const TRANG_THAI_DIEU_PHOI_LATER = [
  'hau_ky',
  'gui_in',
  'hoan_tat_san_xuat',
  'dang_xu_ly',
  'gui_khach_kiem_tra',
  'san_xuat_in_an',
  'cho_nghiem_thu',
  'hoan_thanh',
]

export const SO_DIEM_CHUP_KEY = 'so_diem_chup'
export const SO_DIEM_CHUP_MIN = 1
export const SO_DIEM_CHUP_MAX = 3
export const SO_DIEM_CHUP_DEFAULT = 1

/** Tổng số ảnh chỉnh sửa từ combo, lưu ở envelope thong_tin_dieu_phoi */
export const SO_ANH_CHINH_SUA_KEY = 'so_anh_chinh_sua'

export function clampSoDiemChup(value) {
  const n = Math.round(Number(value))
  if (!Number.isFinite(n)) return SO_DIEM_CHUP_DEFAULT
  return Math.min(SO_DIEM_CHUP_MAX, Math.max(SO_DIEM_CHUP_MIN, n))
}

export const SAP_XEP_TRANG_PHUC_KEY = 'sap_xep_trang_phuc'
export const SAP_XEP_TRANG_PHUC_DEFAULT = 'chua_xep_do'
export const SAP_XEP_TRANG_PHUC_OPTIONS = [
  { value: 'chua_xep_do', label: 'Chưa xếp đồ' },
  { value: 'da_xep_do', label: 'Đã xếp đồ' },
  { value: 'da_hoan_tra', label: 'Đã hoàn trả' },
]

export function defaultSapXepTrangPhucGiaTri() {
  return SAP_XEP_TRANG_PHUC_DEFAULT
}

export function normalizeSapXepTrangPhucValue(value) {
  if (Array.isArray(value)) {
    const first = value.find((item) => item != null && item !== '')
    return first != null ? String(first) : null
  }
  if (value == null || value === '') return null
  return String(value)
}

export function formatSapXepTrangPhucLabel(value) {
  const normalized = normalizeSapXepTrangPhucValue(value)
  if (!normalized) return ''
  const map = Object.fromEntries(SAP_XEP_TRANG_PHUC_OPTIONS.map((opt) => [opt.value, opt.label]))
  return map[normalized] || normalized
}

export function resolveSapXepTrangPhucValue(value) {
  return normalizeSapXepTrangPhucValue(value) || SAP_XEP_TRANG_PHUC_DEFAULT
}

export function sapXepTrangPhucTagType(value) {
  const map = {
    chua_xep_do: 'info',
    da_xep_do: 'success',
    da_hoan_tra: 'primary',
  }
  return map[resolveSapXepTrangPhucValue(value)] || 'info'
}

export function sapXepTrangPhucIconColor(value) {
  const map = {
    chua_xep_do: '#909399',
    da_xep_do: '#67c23a',
    da_hoan_tra: '#409eff',
  }
  return map[resolveSapXepTrangPhucValue(value)] || '#909399'
}

/** Field lịch quay chụp hiển thị ở bước tạo/sửa hợp đồng */
export const LICH_QUAY_CHUP_KEYS = [
  'buoi_chup',
  'gio_chup',
  'ngay_chup',
  'so_diem_chup',
  SAP_XEP_TRANG_PHUC_KEY,
  'ngay_tra_file_le',
  'ngay_tra_file_in',
  'ngay_khach_hen_qua',
  'dia_diem_chup',
  'ghi_chu_trang_phuc_phu_kien',
  'ghi_chu_dieu_phoi',
]

/** Ngày dùng chung cho mọi buổi chụp */
export const SHARED_LICH_QUAY_CHUP_KEYS = [
  'ngay_tra_file_le',
  'ngay_tra_file_in',
  'ngay_khach_hen_qua',
]

export const SHARED_LICH_QUAY_CHUP_LABELS = {
  ngay_tra_file_le: 'Ngày trả file lẻ',
  ngay_tra_file_in: 'Ngày trả file in',
  ngay_khach_hen_qua: 'Ngày khách hẹn qua',
}

export function sharedLichQuayChupLabel(key) {
  return SHARED_LICH_QUAY_CHUP_LABELS[key] || key
}

export function emptySharedLichQuayChupDates() {
  return Object.fromEntries(SHARED_LICH_QUAY_CHUP_KEYS.map((key) => [key, null]))
}

export function isSharedLichQuayChupKey(key) {
  return SHARED_LICH_QUAY_CHUP_KEYS.includes(String(key || ''))
}

/** Field metadata tên buổi chụp, lưu trong từng phần tử danh_sach_buoi_chup */
export const TEN_LICH_KEY = 'ten_lich'
export const TEN_LICH_MAX_LENGTH = 30
export const MAX_LICH_QUAY_CHUP = 6
export const DANH_SACH_BUOI_CHUP_KEY = 'danh_sach_buoi_chup'

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

/** Payload chuẩn: { ngay_tra_file_le, ngay_tra_file_in, ngay_khach_hen_qua, trang_thai_dieu_phoi, danh_sach_buoi_chup } */
export function isDieuPhoiEnvelope(value) {
  return isDieuPhoiSessionMap(value) && Array.isArray(value[DANH_SACH_BUOI_CHUP_KEY])
}

export function emptyDieuPhoiEnvelope() {
  return {
    ngay_tra_file_le: '',
    ngay_tra_file_in: '',
    ngay_khach_hen_qua: '',
    [TRANG_THAI_DIEU_PHOI_KEY]: '',
    [DANH_SACH_BUOI_CHUP_KEY]: [],
  }
}

export function getSoAnhChinhSua(raw) {
  if (!isDieuPhoiSessionMap(raw)) return 0
  const item = raw[SO_ANH_CHINH_SUA_KEY]
  if (item == null || item === '') return 0
  if (typeof item === 'number') return Math.max(0, Math.round(item))
  if (typeof item === 'object' && !Array.isArray(item)) {
    const value = item.gia_tri
    const n = Math.round(Number(value))
    return Number.isFinite(n) ? Math.max(0, n) : 0
  }
  const n = Math.round(Number(item))
  return Number.isFinite(n) ? Math.max(0, n) : 0
}

export function getTrangThaiDieuPhoi(raw) {
  if (!isDieuPhoiSessionMap(raw)) return ''
  const value = raw[TRANG_THAI_DIEU_PHOI_KEY]
  if (value == null || value === '') return ''
  return String(value)
}

/** Ưu tiên thong_tin_dieu_phoi.trang_thai_dieu_phoi, fallback ket_qua_hop_dong.trang_thai. */
export function resolveTrangThaiDieuPhoi(hopDong) {
  const fromEnvelope = getTrangThaiDieuPhoi(hopDong?.thong_tin_dieu_phoi)
  if (fromEnvelope) return fromEnvelope
  const giaTri = hopDong?.ket_qua_hop_dong?.trang_thai?.gia_tri
  if (giaTri == null || giaTri === '') return ''
  return String(giaTri)
}

export function dieuPhoiHasAssignedStaff(raw) {
  for (const session of normalizeDieuPhoiSessions(raw)) {
    for (const key of DIEU_PHOI_STAFF_KEYS) {
      const value = getDieuPhoiGiaTriFromSession(session, key)
      if (Array.isArray(value)) {
        if (value.some((id) => id != null && id !== '')) return true
      } else if (value != null && value !== '') {
        return true
      }
      const ngoai = getDieuPhoiGiaTriFromSession(session, `${key}_ngoai`)
      if (ngoai != null && String(ngoai).trim() !== '') return true
    }
  }
  return false
}

/** Gán thợ lần đầu → tien_ky. Không ghi đè workflow đã đi tiếp. */
export function withTienKyIfStaffAssigned(envelope) {
  if (!dieuPhoiHasAssignedStaff(envelope)) return envelope
  const current = getTrangThaiDieuPhoi(envelope)
  if (TRANG_THAI_DIEU_PHOI_LATER.includes(current)) return envelope
  return { ...envelope, [TRANG_THAI_DIEU_PHOI_KEY]: TRANG_THAI_DIEU_PHOI_TIEN_KY }
}

function readTopLevelDieuPhoiValue(raw, fieldKey) {
  if (!isDieuPhoiSessionMap(raw)) return null
  const item = raw[fieldKey]
  if (item == null || item === '') return null
  if (typeof item === 'string' || typeof item === 'number') return item
  return null
}

export function normalizeSharedDieuPhoiDate(value) {
  if (value == null || value === '') return ''
  const text = String(value).slice(0, 10)
  return /^\d{4}-\d{2}-\d{2}$/.test(text) ? text : ''
}

/** Lấy mảng buổi chụp từ thong_tin_dieu_phoi (chuẩn: { danh_sach_buoi_chup }). */
export function normalizeDieuPhoiSessions(raw) {
  if (!isDieuPhoiEnvelope(raw)) return []
  return raw[DANH_SACH_BUOI_CHUP_KEY].filter((item) => isDieuPhoiSessionMap(item))
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
  if (isSharedLichQuayChupKey(fieldKey)) {
    const top = readTopLevelDieuPhoiValue(raw, fieldKey)
    if (top != null && top !== '') pushUnique(result, seen, top)
    return result
  }
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

/** Lấy gia_tri đầu tiên của field dùng chung (ngày trả file lẻ / file in / khách hẹn qua). */
export function firstDieuPhoiGiaTri(raw, fieldKey) {
  const values = collectDieuPhoiGiaTri(raw, fieldKey)
  return values.length ? values[0] : null
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

export function stripSharedLichQuayChupKeys(session) {
  if (!isDieuPhoiSessionMap(session)) return {}
  const next = { ...session }
  for (const key of SHARED_LICH_QUAY_CHUP_KEYS) {
    delete next[key]
  }
  delete next[TRANG_THAI_DIEU_PHOI_KEY]
  return next
}

export function mergeDieuPhoiSessions(existingRaw, nextSessions) {
  const existing = normalizeDieuPhoiSessions(existingRaw)
  const next = Array.isArray(nextSessions) ? nextSessions : []
  return next.map((session, index) =>
    stripSharedLichQuayChupKeys({
      ...(existing[index] || {}),
      ...(isDieuPhoiSessionMap(session) ? session : {}),
    }),
  )
}

export function buildDieuPhoiEnvelope(existingRaw, nextSessions, sharedDates = {}) {
  const dates = {}
  for (const key of SHARED_LICH_QUAY_CHUP_KEYS) {
    const value =
      sharedDates[key] !== undefined
        ? sharedDates[key]
        : firstDieuPhoiGiaTri(existingRaw, key)
    dates[key] = normalizeSharedDieuPhoiDate(value)
  }

  return {
    ...dates,
    [TRANG_THAI_DIEU_PHOI_KEY]:
      sharedDates[TRANG_THAI_DIEU_PHOI_KEY] !== undefined
        ? sharedDates[TRANG_THAI_DIEU_PHOI_KEY]
        : getTrangThaiDieuPhoi(existingRaw),
    [DANH_SACH_BUOI_CHUP_KEY]: mergeDieuPhoiSessions(existingRaw, nextSessions),
  }
}

export function isDieuPhoiExtraSessionKey(key) {
  return (
    key === TEN_LICH_KEY ||
    key === CONCEPT_FIELD_KEY ||
    key === TRANG_PHUC_FIELD_KEY ||
    key === LOAI_QUAY_CHUP_KEY ||
    key === DANH_SACH_BUOI_CHUP_KEY ||
    key === TRANG_THAI_DIEU_PHOI_KEY ||
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
