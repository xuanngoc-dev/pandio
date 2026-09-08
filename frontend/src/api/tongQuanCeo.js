import api from '@/api/axios'

/**
 * Thống kê KPI tab CEO & Admin theo khoảng ngày.
 * @param {{
 *   tu_ngay?: string,
 *   den_ngay?: string,
 *   thang?: string,
 * }} [params] — tu_ngay/den_ngay: YYYY-MM-DD; thang: YYYY-MM (tương thích cũ)
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchTongQuanCeoAdmin(params = {}, config = {}) {
  return api.get('/tong-quan/ceo-admin', { params, ...config })
}
