import api from '@/api/axios'

/**
 * Thống kê KPI / bảng tab Trang phục theo khoảng ngày.
 * @param {{
 *   tu_ngay?: string,
 *   den_ngay?: string,
 *   thang?: string,
 * }} [params] — tu_ngay/den_ngay: YYYY-MM-DD; thang: YYYY-MM (tương thích cũ)
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchTongQuanTrangPhuc(params = {}, config = {}) {
  return api.get('/tong-quan/trang-phuc', { params, ...config })
}
