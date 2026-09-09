import api from '@/api/axios'

/**
 * Thống kê KPI tab Tài chính & nhân sự theo tháng.
 * @param {{ thang?: string }} [params] — thang: YYYY-MM (mặc định tháng hiện tại)
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchTongQuanTaiChinhNhanSu(params = {}, config = {}) {
  return api.get('/tong-quan/tai-chinh-nhan-su', { params, ...config })
}
