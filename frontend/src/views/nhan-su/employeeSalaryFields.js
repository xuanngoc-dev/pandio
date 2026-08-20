import { formatInteger } from '@/utils/number'

export const LUONG_THEO_DICH_VU_KEY = 'luong_theo_dich_vu'
export const SALARY_DIEM_LEVELS = [1, 2, 3]

export const LUONG_DICH_VU_ROLES = [
  { key: 'chup', label: 'Chụp ảnh' },
  { key: 'make', label: 'Make up' },
  { key: 'quay_phim', label: 'Quay phim' },
]

/** Định nghĩa các khoản lương/thưởng/phụ cấp (khớp backend). */
export const SALARY_FIELD_DEFINITIONS = [
  { key: 'luong_cung', name: 'Lương cứng' },
  { key: 'luong_mem', name: 'Lương mềm' },
  { key: 'phu_cap', name: 'Phụ cấp' },
  { key: 'luong_1_gio', name: 'Lương 1 giờ', note: 'Dành cho part time' },
  { key: 'luong_tang_ca_1_gio', name: 'Lương tăng ca 1 giờ', note: 'Dành cho cả part_time và full_time' },
  { key: 'phu_cap_xang', name: 'Phụ cấp xăng' },
  { key: 'phu_cap_an_trua', name: 'Phụ cấp ăn trưa' },
  { key: 'phu_cap_dien_thoai', name: 'Phụ cấp điện thoại' },
  { key: 'phu_cap_nha_o', name: 'Phụ cấp nhà ở' },
  { key: 'thuong_chuyen_can', name: 'Thưởng chuyên cần' },
  { key: 'hoa_hong_hop_dong_sddv', name: 'Hoa hồng HĐ sử dụng dịch vụ' },
  { key: 'hoa_hong_hop_dong_trang_phuc', name: 'Hoa hồng HĐ trang phục' },
  { key: 'phi_xu_ly_hd_thue_trang_phuc', name: 'Phí xử lý HĐ thuê trang phục' },
  { key: LUONG_THEO_DICH_VU_KEY, name: 'Lương theo dịch vụ', kind: 'dich_vu' },
]

export const SALARY_FIELD_GROUPS = [
  {
    key: 'luong',
    title: 'Lương',
    keys: ['luong_cung', 'luong_mem', 'luong_1_gio', 'luong_tang_ca_1_gio'],
  },
  {
    key: 'phu_cap',
    title: 'Phụ cấp',
    keys: ['phu_cap', 'phu_cap_xang', 'phu_cap_an_trua', 'phu_cap_dien_thoai', 'phu_cap_nha_o'],
  },
  {
    key: 'thuong',
    title: 'Thưởng',
    keys: [
      'thuong_chuyen_can',
      'hoa_hong_hop_dong_sddv',
      'hoa_hong_hop_dong_trang_phuc',
      'phi_xu_ly_hd_thue_trang_phuc',
    ],
    cols: { xs: 12, sm: 12, md: 8, lg: 6 },
  },
  {
    key: LUONG_THEO_DICH_VU_KEY,
    title: 'Lương theo dịch vụ',
    keys: [],
    table: true,
  },
]

const DEFAULT_SALARY_COLS = { xs: 12, sm: 8, md: 6, lg: 4 }

function emptyDiemMap() {
  return { 1: null, 2: null, 3: null }
}

function normalizeDiemMap(raw) {
  const result = emptyDiemMap()
  if (!raw || typeof raw !== 'object' || Array.isArray(raw)) return result
  for (const level of SALARY_DIEM_LEVELS) {
    const amount = raw[level] ?? raw[String(level)]
    result[level] = amount != null && amount !== '' ? Number(amount) : null
  }
  return result
}

function emptyDichVuItem(loai = {}) {
  return {
    id: loai.id != null ? Number(loai.id) : null,
    ten_dich_vu: loai.ten_dich_vu || '',
    chup: emptyDiemMap(),
    make: emptyDiemMap(),
    quay_phim: emptyDiemMap(),
  }
}

function itemsToMap(raw) {
  const map = {}
  if (!raw) return map
  const list = Array.isArray(raw) ? raw : Object.values(raw)
  for (const row of list) {
    if (!row || typeof row !== 'object') continue
    const id = row.id ?? row.danh_muc_loai_quay_chup_id
    if (id == null || id === '') continue
    const key = String(id)
    map[key] = {
      id: Number(id),
      ten_dich_vu: row.ten_dich_vu || '',
      chup: normalizeDiemMap(row.chup),
      make: normalizeDiemMap(row.make),
      quay_phim: normalizeDiemMap(row.quay_phim),
    }
  }
  return map
}

export function createDefaultLuongThuongPhuCap(overrides = {}) {
  const result = {}
  for (const def of SALARY_FIELD_DEFINITIONS) {
    const src = overrides?.[def.key] || {}
    if (def.kind === 'dich_vu') {
      result[def.key] = {
        name: src.name || def.name,
        items: itemsToMap(src.items),
      }
      continue
    }
    result[def.key] = {
      name: src.name || def.name,
      value: src.value != null && src.value !== '' ? Number(src.value) : null,
      note: src.note != null && src.note !== '' ? String(src.note) : (def.note || ''),
    }
  }
  return result
}

export function ensureLuongTheoDichVu(luong, loaiList = []) {
  if (!luong) return luong
  if (!luong[LUONG_THEO_DICH_VU_KEY] || typeof luong[LUONG_THEO_DICH_VU_KEY] !== 'object') {
    luong[LUONG_THEO_DICH_VU_KEY] = { name: 'Lương theo dịch vụ', items: {} }
  }
  const block = luong[LUONG_THEO_DICH_VU_KEY]
  if (Array.isArray(block.items)) {
    block.items = itemsToMap(block.items)
  } else if (!block.items || typeof block.items !== 'object') {
    block.items = {}
  } else {
    block.items = itemsToMap(block.items)
  }

  for (const loai of loaiList || []) {
    const id = String(loai.id)
    if (!block.items[id]) {
      block.items[id] = emptyDichVuItem(loai)
      continue
    }
    const item = block.items[id]
    item.id = Number(loai.id)
    item.ten_dich_vu = loai.ten_dich_vu || item.ten_dich_vu || ''
    for (const role of LUONG_DICH_VU_ROLES) {
      item[role.key] = normalizeDiemMap(item[role.key])
    }
  }
  return luong
}

export function findDichVuItem(luong, loaiId) {
  const items = luong?.[LUONG_THEO_DICH_VU_KEY]?.items
  if (!items) return null
  const id = String(loaiId)
  if (Array.isArray(items)) {
    return items.find((row) => String(row?.id) === id) || null
  }
  return items[id] || items[loaiId] || null
}

export function dichVuRate(luong, loaiId, role, level) {
  const item = findDichVuItem(luong, loaiId)
  const map = item?.[role] || {}
  return map[level] ?? map[String(level)] ?? null
}

export function buildSalaryGroups(luongData) {
  const data = createDefaultLuongThuongPhuCap(luongData || {})
  return SALARY_FIELD_GROUPS.map((group) => ({
    key: group.key,
    title: group.title,
    cols: group.cols || DEFAULT_SALARY_COLS,
    table: Boolean(group.table),
    items: (group.keys || []).map((key) => ({
      key,
      ...data[key],
    })),
  }))
}

export function salaryValueOf(nvData, key) {
  const item = nvData?.luong_thuong_phu_cap?.[key]
  return item?.value ?? null
}

export function formatMoney(value) {
  if (value == null || value === '') return '—'
  const formatted = formatInteger(value)
  if (!formatted) return '—'
  return `${formatted} ₫`
}

function serializeLuongTheoDichVuItems(itemsMap, loaiList = []) {
  const map = itemsToMap(itemsMap)
  const seen = new Set()
  const result = []

  for (const loai of loaiList || []) {
    const id = String(loai.id)
    seen.add(id)
    const src = map[id] || emptyDichVuItem(loai)
    result.push({
      id: Number(loai.id),
      ten_dich_vu: loai.ten_dich_vu || src.ten_dich_vu || '',
      chup: normalizeDiemMap(src.chup),
      make: normalizeDiemMap(src.make),
      quay_phim: normalizeDiemMap(src.quay_phim),
    })
  }

  for (const [id, src] of Object.entries(map)) {
    if (seen.has(id)) continue
    result.push({
      id: Number(src.id),
      ten_dich_vu: src.ten_dich_vu || '',
      chup: normalizeDiemMap(src.chup),
      make: normalizeDiemMap(src.make),
      quay_phim: normalizeDiemMap(src.quay_phim),
    })
  }

  return result
}

export function serializeLuongThuongPhuCap(luong, loaiList = []) {
  const result = {}
  for (const def of SALARY_FIELD_DEFINITIONS) {
    const item = luong?.[def.key] || {}
    if (def.kind === 'dich_vu') {
      result[def.key] = {
        name: item.name || def.name,
        items: serializeLuongTheoDichVuItems(item.items, loaiList),
      }
      continue
    }
    result[def.key] = {
      name: item.name || def.name,
      value: item.value != null && item.value !== '' ? Number(item.value) : null,
      note: item.note?.trim() ? item.note.trim() : (def.note || null),
    }
  }
  return result
}

export function sampleLuongTheoDichVu(loaiList = [], randomInt) {
  const items = {}
  for (const loai of loaiList) {
    items[String(loai.id)] = {
      id: Number(loai.id),
      ten_dich_vu: loai.ten_dich_vu || '',
      chup: {
        1: randomInt(3, 8) * 100_000,
        2: randomInt(8, 14) * 100_000,
        3: randomInt(14, 22) * 100_000,
      },
      make: {
        1: randomInt(2, 6) * 100_000,
        2: randomInt(6, 12) * 100_000,
        3: randomInt(12, 18) * 100_000,
      },
      quay_phim: {
        1: randomInt(4, 9) * 100_000,
        2: randomInt(9, 15) * 100_000,
        3: randomInt(15, 24) * 100_000,
      },
    }
  }
  return {
    name: 'Lương theo dịch vụ',
    items,
  }
}
