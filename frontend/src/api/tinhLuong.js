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
