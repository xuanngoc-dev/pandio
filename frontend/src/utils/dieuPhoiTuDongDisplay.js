import {
  collectDieuPhoiGiaTri,
  firstDieuPhoiGiaTri,
  formatLoaiQuayChupLabel,
  formatNoteThoShopLabel,
  getDieuPhoiGiaTriFromSession,
  normalizeDieuPhoiSessions,
  normalizeNoteThoShopValue,
  NOTE_THO_SHOP_KEY,
  parseSessionLoaiQuayChup,
  sharedLichQuayChupLabel,
} from './thongTinDieuPhoi'

const STAFF_ROLE_LABELS = {
  tho_chup: 'Thợ chụp',
  tho_make: 'Thợ make',
  tho_edit: 'Thợ edit',
  quay_phim: 'Quay phim',
  tho_dung_video: 'Thợ dựng video',
}

const FILE_LINK_DEFS = [
  { key: 'link_file_goc', label: 'File gốc' },
  { key: 'link_file_le', label: 'File lẻ' },
  { key: 'link_file_in', label: 'File in' },
]

function getThongTin(row) {
  const info = row?.thong_tin_hop_dong
  return info && typeof info === 'object' && !Array.isArray(info) ? info : {}
}

export function formatDieuPhoiKhachHang(row) {
  if (row?.ten_khach_hang) return row.ten_khach_hang
  const info = getThongTin(row)
  const tenChuRe = info.tenChuRe || info.ten_chu_re
  const tenCoDau = info.tenCoDau || info.ten_co_dau
  if (tenChuRe || tenCoDau) {
    return [tenChuRe, tenCoDau].filter(Boolean).join(' & ')
  }
  return info.hoTenKhachHang || info.ho_ten_khach_hang || info.hoTenKhach || '—'
}

export function formatDieuPhoiDate(value) {
  if (value == null || value === '') return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    const raw = String(value).trim()
    return raw || ''
  }
  const dd = String(date.getDate()).padStart(2, '0')
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const yyyy = date.getFullYear()
  return `${dd}/${mm}/${yyyy}`
}

function formatTime(value) {
  if (value == null || value === '') return ''
  const raw = String(value).trim()
  const match = raw.match(/^(\d{1,2}):(\d{2})(?::\d{2})?/)
  if (match) {
    return `${match[1].padStart(2, '0')}:${match[2]}`
  }
  const date = new Date(raw)
  if (!Number.isNaN(date.getTime())) {
    return date.toLocaleTimeString('vi-VN', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    })
  }
  return raw
}

function formatBuoi(value) {
  if (value == null || value === '') return ''
  return String(value).trim().toLowerCase()
}

export function buildDieuPhoiThoiGianChupItems(row) {
  return normalizeDieuPhoiSessions(row?.thong_tin_dieu_phoi)
    .map((session) => {
      const gio = formatTime(getDieuPhoiGiaTriFromSession(session, 'gio_chup'))
      const buoi = formatBuoi(getDieuPhoiGiaTriFromSession(session, 'buoi_chup'))
      const ngay = formatDieuPhoiDate(getDieuPhoiGiaTriFromSession(session, 'ngay_chup'))
      const text = [gio, buoi, ngay].filter(Boolean).join(' ')
      if (!text) return null
      return {
        text,
        loaiLabel: formatLoaiQuayChupLabel(parseSessionLoaiQuayChup(session)),
      }
    })
    .filter(Boolean)
}

export function formatDieuPhoiThoiGianChup(row) {
  return buildDieuPhoiThoiGianChupItems(row)
    .map((item) => item.text)
    .join('; ')
}

export function formatDieuPhoiSharedDate(row, key) {
  return collectDieuPhoiGiaTri(row?.thong_tin_dieu_phoi, key)
    .map((item) => formatDieuPhoiDate(item))
    .filter(Boolean)
    .join(', ')
}

export function getDieuPhoiSharedDateIso(row, key) {
  const raw = firstDieuPhoiGiaTri(row?.thong_tin_dieu_phoi, key)
  const text = String(raw || '').slice(0, 10)
  return /^\d{4}-\d{2}-\d{2}$/.test(text) ? text : ''
}

export function buildSharedDateField(row, key, status = null) {
  return {
    key,
    label: sharedLichQuayChupLabel(key),
    display: formatDieuPhoiSharedDate(row, key),
    iso: getDieuPhoiSharedDateIso(row, key),
    status,
  }
}

function toAscii(value) {
  return String(value || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
}

function getVaiTroTen(user) {
  return String(
    user?.nhan_vien?.vai_tro?.ten_vai_tro ||
      user?.nhanVien?.vaiTro?.ten_vai_tro ||
      user?.nhan_vien?.vaiTro?.ten_vai_tro ||
      user?.nhanVien?.vai_tro?.ten_vai_tro ||
      '',
  )
}

export function isAdminOrCoordinator(user) {
  const role = String(user?.role || '').toLowerCase()
  return role === 'admin' || role === 'coordinator'
}

export function userHasHauKyJobRole(user) {
  const ten = getVaiTroTen(user).toLowerCase()
  if (!ten) return false
  const ascii = toAscii(ten)
  return (
    ascii.includes('tho edit') ||
    /\beditor\b/.test(ascii) ||
    ascii.includes('dung video') ||
    ascii.includes('tho dung') ||
    ascii.includes('hau ky')
  )
}

export function userHasStaffRole(row, userId, ...keys) {
  const id = Number(userId)
  if (!Number.isFinite(id)) return false
  return keys.some((key) =>
    collectDieuPhoiGiaTri(row?.thong_tin_dieu_phoi, key).some(
      (staffId) => toStaffId(staffId) === id,
    ),
  )
}

export function canEditSharedDates(step, user) {
  return ['tien_ky', 'hau_ky'].includes(step) && isAdminOrCoordinator(user)
}

export function canEditNoteThoShop(step, user) {
  return (
    ['tien_ky', 'hau_ky', 'gui_in', 'hoan_tat_san_xuat'].includes(step) &&
    isAdminOrCoordinator(user)
  )
}

export function canEditDieuPhoiFile(row, step, user, fieldKey) {
  if (step === 'tien_ky') {
    return (
      fieldKey === 'link_file_goc' &&
      (isAdminOrCoordinator(user) || userHasStaffRole(row, user?.id, 'tho_chup', 'quay_phim'))
    )
  }
  if (step === 'hau_ky') {
    if (fieldKey !== 'link_file_le' && fieldKey !== 'link_file_in') return false
    if (isAdminOrCoordinator(user) || userHasHauKyJobRole(user)) return true
    return userHasStaffRole(row, user?.id, 'tho_edit', 'tho_dung_video')
  }
  return false
}

export function getDieuPhoiNoteThoShopValue(row) {
  return normalizeNoteThoShopValue(firstDieuPhoiGiaTri(row?.thong_tin_dieu_phoi, NOTE_THO_SHOP_KEY))
}

export function getDieuPhoiNoteThoShopLabel(row) {
  return formatNoteThoShopLabel(getDieuPhoiNoteThoShopValue(row))
}

function toStaffId(value) {
  if (value == null || value === '') return null
  if (typeof value === 'object') {
    const id = value.id ?? value.user_id ?? value.userId
    return id == null || id === '' ? null : Number(id)
  }
  const n = Number(value)
  return Number.isFinite(n) ? n : null
}

export function getDieuPhoiVaiTroLabels(row, userId) {
  if (userId == null) return []

  const roles = []
  for (const [key, label] of Object.entries(STAFF_ROLE_LABELS)) {
    const list = collectDieuPhoiGiaTri(row?.thong_tin_dieu_phoi, key)
    const matched = list.some((id) => Number(id) === Number(userId))
    if (matched) roles.push(label)
  }
  return roles
}

export function getDieuPhoiFileLinks(row, step = 'hau_ky') {
  const ketQua =
    row?.ket_qua_hop_dong && typeof row.ket_qua_hop_dong === 'object' && !Array.isArray(row.ket_qua_hop_dong)
      ? row.ket_qua_hop_dong
      : {}

  let defs = FILE_LINK_DEFS
  if (step === 'tien_ky') {
    defs = FILE_LINK_DEFS.filter((def) => def.key === 'link_file_goc')
  } else if (step === 'hau_ky') {
    defs = FILE_LINK_DEFS
  }

  return defs.map((def) => {
    const giaTri = ketQua?.[def.key]?.gia_tri
    const url = giaTri != null && String(giaTri).trim() !== '' ? String(giaTri).trim() : null
    return { ...def, url }
  })
}

export function normalizeDieuPhoiUrl(url) {
  const raw = String(url || '').trim()
  if (!raw) return '#'
  if (/^https?:\/\//i.test(raw)) return raw
  return `https://${raw}`
}

function toDateOnlyString(value) {
  if (value == null || value === '') return ''
  const raw = String(value).trim()
  const iso = raw.match(/^(\d{4})-(\d{2})-(\d{2})/)
  if (iso) return `${iso[1]}-${iso[2]}-${iso[3]}`

  const dmy = raw.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/)
  if (dmy) {
    return `${dmy[3]}-${dmy[2].padStart(2, '0')}-${dmy[1].padStart(2, '0')}`
  }

  const date = new Date(raw)
  if (Number.isNaN(date.getTime())) return ''
  const yyyy = date.getFullYear()
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const dd = String(date.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

function todayDateOnlyString() {
  const today = new Date()
  const yyyy = today.getFullYear()
  const mm = String(today.getMonth() + 1).padStart(2, '0')
  const dd = String(today.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

function earliestDate(values) {
  const dates = (Array.isArray(values) ? values : [])
    .map((item) => String(item || '').slice(0, 10))
    .filter((item) => /^\d{4}-\d{2}-\d{2}$/.test(item))
    .sort()
  return dates[0] || null
}

export function buildDieuPhoiDeadlineStatus({ dateValue, hasFile, lateLabel, okLabel }) {
  if (dateValue == null || String(dateValue).trim() === '') return null
  const dateStr = toDateOnlyString(dateValue)
  if (!dateStr) return null
  const late = dateStr < todayDateOnlyString() && !hasFile
  return {
    late,
    tooltip: late ? lateLabel : okLabel,
  }
}

export function getDieuPhoiDateDeadlineStatus(row, key, hasFile, lateLabel, okLabel) {
  return buildDieuPhoiDeadlineStatus({
    dateValue: earliestDate(collectDieuPhoiGiaTri(row?.thong_tin_dieu_phoi, key)),
    hasFile,
    lateLabel,
    okLabel,
  })
}

export function canChuyenGuiIn(row) {
  const ketQua =
    row?.ket_qua_hop_dong && typeof row.ket_qua_hop_dong === 'object' && !Array.isArray(row.ket_qua_hop_dong)
      ? row.ket_qua_hop_dong
      : {}
  const hasLe = ketQua?.link_file_le?.gia_tri != null && String(ketQua.link_file_le.gia_tri).trim() !== ''
  const hasIn = ketQua?.link_file_in?.gia_tri != null && String(ketQua.link_file_in.gia_tri).trim() !== ''
  return hasLe && hasIn
}
