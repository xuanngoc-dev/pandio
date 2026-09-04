import api from '@/api/axios'

/**
 * Bảng lương chi tiết theo ngày trong tháng của user đang đăng nhập.
 * @param {{ thang: string }} params — thang: YYYY-MM
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchBangLuongChiTietTheoNgay(params, config = {}) {
  return api.get('/tinh-luong/chi-tiet-theo-ngay', { params, ...config })
}

/**
 * Bảng lương chi tiết theo ngày trong tháng của một nhân viên (theo user_id).
 * @param {{ user_id: number|string, thang: string }} params — thang: YYYY-MM
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchBangLuongChiTietTheoNgayNhanVien(params, config = {}) {
  return api.get('/tinh-luong/chi-tiet-theo-ngay-nhan-vien', { params, ...config })
}

/**
 * Lương tổng hợp theo tháng — danh sách nhân viên.
 * @param {{
 *   thang: string,
 *   page?: number,
 *   per_page?: number,
 *   keyword?: string,
 * }} params — thang: YYYY-MM
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchLuongTongHop(params, config = {}) {
  return api.get('/tinh-luong/tong-hop', { params, ...config })
}

/**
 * Trạng thái chốt lương theo tháng.
 * @param {{ thang: string }} params — thang: YYYY-MM
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchTrangThaiChotLuong(params, config = {}) {
  return api.get('/tinh-luong/chot-thang', { params, ...config })
}

/**
 * Chốt lương tháng — lưu snapshot tổng hợp vào chot_luong_thang.
 * @param {{ thang: string }} payload — thang: YYYY-MM
 * @param {{ skipLoading?: boolean }} [config]
 */
export function chotLuongThang(payload, config = {}) {
  return api.post('/tinh-luong/chot-thang', payload, config)
}

/**
 * Huỷ chốt lương tháng (chỉ trong kỳ chốt).
 * @param {{ thang: string }} payload — thang: YYYY-MM
 * @param {{ skipLoading?: boolean }} [config]
 */
export function huyChotLuongThang(payload, config = {}) {
  return api.delete('/tinh-luong/chot-thang', { data: payload, ...config })
}
