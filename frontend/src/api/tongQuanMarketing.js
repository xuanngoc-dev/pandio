import api from '@/api/axios'

/**
 * Thống kê KPI tab Marketing theo khoảng ngày (report quảng cáo).
 * @param {{
 *   tu_ngay?: string,
 *   den_ngay?: string,
 *   thang?: string,
 * }} [params] — tu_ngay/den_ngay: YYYY-MM-DD; thang: YYYY-MM (tương thích cũ)
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchTongQuanMarketing(params = {}, config = {}) {
  return api.get('/tong-quan/marketing', { params, ...config })
}
