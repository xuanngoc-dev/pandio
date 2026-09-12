import api from '@/api/axios'

/**
 * Thống kê KPI / biểu đồ tab Kinh doanh theo khoảng ngày.
 * @param {{
 *   tu_ngay?: string,
 *   den_ngay?: string,
 *   thang?: string,
 * }} [params] — tu_ngay/den_ngay: YYYY-MM-DD; thang: YYYY-MM (tương thích cũ)
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchTongQuanKinhDoanh(params = {}, config = {}) {
  return api.get('/tong-quan/kinh-doanh', { params, ...config })
}

/**
 * Xếp hạng sale đầy đủ theo tiêu chí (phân trang).
 * @param {{
 *   tu_ngay?: string,
 *   den_ngay?: string,
 *   tieu_chi: 'so_hd' | 'doanh_thu',
 *   page?: number,
 *   per_page?: number,
 * }} params
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchXepHangSaleKinhDoanh(params, config = {}) {
  return api.get('/tong-quan/kinh-doanh/xep-hang-sale', { params, ...config })
}
