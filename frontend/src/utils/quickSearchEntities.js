import { fetchUsers } from '@/api/users'
import { fetchHopDongSuDungDichVu } from '@/api/hopDongSuDungDichVu'
import { fetchHopDongChoThueTrangPhuc } from '@/api/hopDongChoThueTrangPhuc'
import { fetchTrangPhuc } from '@/api/trangPhuc'
import { fetchConcept } from '@/api/concept'
import { fetchDichVuDanhSachDichVuLe } from '@/api/dichVuDanhSachDichVuLe'
import { fetchDichVuDanhSachDichNhomDichVu } from '@/api/dichVuDanhSachDichNhomDichVu'
import { fetchNoteKhachMoi } from '@/api/noteKhachMoi'
import { fetchReportQuangCao } from '@/api/reportQuangCao'

function pick(...values) {
  for (const value of values) {
    const text = String(value ?? '').trim()
    if (text) return text
  }
  return ''
}

function formatKhachHangHopDong(row) {
  if (row?.ten_khach_hang) return row.ten_khach_hang
  const info =
    row?.thong_tin_hop_dong && typeof row.thong_tin_hop_dong === 'object'
      ? row.thong_tin_hop_dong
      : {}
  const tenChuRe = info.tenChuRe || info.ten_chu_re
  const tenCoDau = info.tenCoDau || info.ten_co_dau
  if (tenChuRe || tenCoDau) {
    return [tenChuRe, tenCoDau].filter(Boolean).join(' & ')
  }
  return pick(info.hoTenKhachHang, info.ho_ten_khach_hang, info.hoTenKhach)
}

function formatSdtHopDong(row) {
  if (row?.sdt_khach_hang) return row.sdt_khach_hang
  const info =
    row?.thong_tin_hop_dong && typeof row.thong_tin_hop_dong === 'object'
      ? row.thong_tin_hop_dong
      : {}
  return pick(info.soDienThoai, info.so_dien_thoai, info.sdt)
}

function formatMoney(value) {
  const num = Number(value)
  if (!Number.isFinite(num)) return ''
  return new Intl.NumberFormat('vi-VN').format(num)
}

function formatDate(value) {
  if (!value) return ''
  const text = String(value)
  if (/^\d{4}-\d{2}-\d{2}/.test(text)) {
    const [y, m, d] = text.slice(0, 10).split('-')
    return `${d}/${m}/${y}`
  }
  return text
}

function deptName(row) {
  const list = row?.nhan_vien?.phong_bans
  if (Array.isArray(list) && list.length) {
    return list.map((pb) => pb.ten_phong_ban).filter(Boolean).join(', ')
  }
  return ''
}

async function fetchList(fetcher, keyword, limit) {
  const { data } = await fetcher({
    page: 1,
    per_page: limit,
    keyword,
  })
  return data.data || []
}

/** Nguồn tìm kiếm nhanh theo từng loại dữ liệu. */
export const SEARCH_SOURCES = [
  {
    key: 'employees',
    label: 'Nhân viên',
    icon: 'User',
    async search(keyword, limit = 8) {
      const rows = await fetchList(fetchUsers, keyword, limit)
      return rows.map((row) => ({
        id: row.id,
        title: pick(row.name, 'Không tên'),
        meta: [row.email, row.phone, deptName(row)].filter(Boolean).join(' · '),
        avatar: true,
        route: {
          name: 'nhan-su-danh-sach',
          query: { keyword: pick(row.name) },
        },
      }))
    },
  },
  {
    key: 'hop-dong-sddv',
    label: 'Hợp đồng SDDV',
    icon: 'Document',
    async search(keyword, limit = 8) {
      const rows = await fetchList(fetchHopDongSuDungDichVu, keyword, limit)
      return rows.map((row) => {
        const ma = pick(row.ma_hop_dong)
        const khach = formatKhachHangHopDong(row)
        const sdt = formatSdtHopDong(row)
        return {
          id: row.id,
          title: ma || `HĐ #${row.id}`,
          meta: [khach, sdt, pick(row.trang_thai)].filter(Boolean).join(' · '),
          route: {
            name: 'hop-dong-sddv',
            query: { keyword: ma || khach || keyword },
          },
        }
      })
    },
  },
  {
    key: 'hop-dong-cho-thue',
    label: 'Hợp đồng cho thuê',
    icon: 'Tickets',
    async search(keyword, limit = 8) {
      const rows = await fetchList(fetchHopDongChoThueTrangPhuc, keyword, limit)
      return rows.map((row) => {
        const ma = pick(row.ma_hop_dong)
        const khach = pick(row.ten_khach_hang)
        const sdt = pick(row.sdt_khach_hang)
        return {
          id: row.id,
          title: ma || `HĐ thuê #${row.id}`,
          meta: [khach, sdt, pick(row.trang_thai)].filter(Boolean).join(' · '),
          route: {
            name: 'hop-dong-cho-thue',
            query: { keyword: ma || khach || keyword },
          },
        }
      })
    },
  },
  {
    key: 'trang-phuc',
    label: 'Trang phục',
    icon: 'Goods',
    async search(keyword, limit = 8) {
      const rows = await fetchList(fetchTrangPhuc, keyword, limit)
      return rows.map((row) => {
        const ma = pick(row.ma_san_pham)
        const ten = pick(row.ten_san_pham)
        const gia = formatMoney(row.gia_cho_thue)
        return {
          id: row.id,
          title: ten || ma || `SP #${row.id}`,
          meta: [ma, gia ? `${gia}đ` : '', pick(row.tinh_trang)].filter(Boolean).join(' · '),
          route: {
            name: 'trang-phuc',
            query: { keyword: ma || ten || keyword, tab: 'trang-phuc' },
          },
        }
      })
    },
  },
  {
    key: 'concept',
    label: 'Concept',
    icon: 'Picture',
    async search(keyword, limit = 8) {
      const rows = await fetchList(fetchConcept, keyword, limit)
      return rows.map((row) => {
        const ma = pick(row.ma_concept)
        const ten = pick(row.ten_concept)
        return {
          id: row.id,
          title: ten || ma || `Concept #${row.id}`,
          meta: [ma, pick(row.dia_diem)].filter(Boolean).join(' · '),
          route: {
            name: 'concept',
            query: { keyword: ma || ten || keyword, tab: 'concept' },
          },
        }
      })
    },
  },
  {
    key: 'dich-vu',
    label: 'Dịch vụ',
    icon: 'Briefcase',
    async search(keyword, limit = 8) {
      const half = Math.max(3, Math.ceil(limit / 2))
      const [leRows, nhomRows] = await Promise.all([
        fetchList(fetchDichVuDanhSachDichVuLe, keyword, half),
        fetchList(fetchDichVuDanhSachDichNhomDichVu, keyword, half),
      ])

      const le = leRows.map((row) => {
        const ma = pick(row.ma_dich_vu)
        const ten = pick(row.ten_dich_vu)
        const gia = formatMoney(row.gia_khuyen_mai ?? row.gia_goc)
        return {
          id: `le-${row.id}`,
          title: ten || ma || `DV #${row.id}`,
          meta: ['Dịch vụ lẻ', ma, gia ? `${gia}đ` : ''].filter(Boolean).join(' · '),
          route: {
            name: 'dich-vu',
            query: { keyword: ma || ten || keyword, tab: 'dich-vu' },
          },
        }
      })

      const nhom = nhomRows.map((row) => {
        const ma = pick(row.ma_nhom)
        const ten = pick(row.ten_nhom)
        const gia = formatMoney(row.gia_khuyen_mai ?? row.gia_goc)
        return {
          id: `nhom-${row.id}`,
          title: ten || ma || `Nhóm #${row.id}`,
          meta: ['Nhóm dịch vụ', ma, gia ? `${gia}đ` : ''].filter(Boolean).join(' · '),
          route: {
            name: 'dich-vu',
            query: { keyword: ma || ten || keyword, tab: 'nhom-dich-vu' },
          },
        }
      })

      return [...le, ...nhom].slice(0, limit)
    },
  },
  {
    key: 'note-khach-moi',
    label: 'Note khách mới',
    icon: 'Notebook',
    async search(keyword, limit = 8) {
      const rows = await fetchList(fetchNoteKhachMoi, keyword, limit)
      return rows.map((row) => {
        const ten = pick(row.ten_khach)
        const sdt = pick(row.sdt)
        return {
          id: row.id,
          title: ten || sdt || `Note #${row.id}`,
          meta: [sdt, pick(row.nguon_khach), pick(row.trang_thai)].filter(Boolean).join(' · '),
          route: {
            name: 'note-khach-moi',
            query: { keyword: ten || sdt || keyword },
          },
        }
      })
    },
  },
  {
    key: 'quang-cao',
    label: 'Quảng cáo',
    icon: 'Promotion',
    async search(keyword, limit = 8) {
      const rows = await fetchList(fetchReportQuangCao, keyword, limit)
      return rows.map((row) => {
        const ngay = formatDate(row.ngay)
        const ghiChu = pick(row.ghi_chu)
        return {
          id: row.id,
          title: ngay ? `QC ngày ${ngay}` : `QC #${row.id}`,
          meta: ghiChu || 'Report quảng cáo',
          route: {
            name: 'quang-cao',
            query: { keyword: ghiChu || keyword },
          },
        }
      })
    },
  },
]

export const SEARCH_SOURCE_MAP = Object.fromEntries(
  SEARCH_SOURCES.map((source) => [source.key, source])
)

export const ENTITY_TAB_KEYS = SEARCH_SOURCES.map((source) => source.key)

/**
 * Tìm theo 1 nguồn. Lỗi API trả về [] để không làm hỏng toàn bộ kết quả.
 */
export async function searchSource(key, keyword, limit = 8) {
  const source = SEARCH_SOURCE_MAP[key]
  if (!source) return []
  const trimmed = String(keyword || '').trim()
  if (!trimmed) return []
  try {
    return await source.search(trimmed, limit)
  } catch {
    return []
  }
}

/**
 * Tìm song song nhiều nguồn, trả về map key -> results.
 */
export async function searchSources(keys, keyword, limit = 5) {
  const trimmed = String(keyword || '').trim()
  if (!trimmed || !keys?.length) {
    return Object.fromEntries((keys || []).map((key) => [key, []]))
  }

  const entries = await Promise.all(
    keys.map(async (key) => [key, await searchSource(key, trimmed, limit)])
  )
  return Object.fromEntries(entries)
}
