import api from '@/api/axios'

/**
 * Thống kê KPI tab CEO & Admin theo tháng.
 * @param {{ thang?: string }} [params] — thang: YYYY-MM (mặc định tháng hiện tại phía API)
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchTongQuanCeoAdmin(params = {}, config = {}) {
  return api.get('/tong-quan/ceo-admin', { params, ...config })
}
