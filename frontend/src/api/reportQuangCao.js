import api from '@/api/axios'

/**
 * Danh sách report quảng cáo — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, ngay_tu?: string, ngay_den?: string }} params
 */
export function fetchReportQuangCao(params = {}) {
  return api.get('/report-quang-cao', { params })
}

/**
 * Chi tiết report quảng cáo.
 * @param {number|string} id
 */
export function getReportQuangCao(id) {
  return api.get(`/report-quang-cao/${id}`)
}

/**
 * Tạo report quảng cáo.
 * @param {object} payload
 */
export function createReportQuangCao(payload) {
  return api.post('/report-quang-cao', payload)
}

/**
 * Cập nhật report quảng cáo.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateReportQuangCao(id, payload) {
  return api.put(`/report-quang-cao/${id}`, payload)
}

/**
 * Xóa report quảng cáo.
 * @param {number|string} id
 */
export function deleteReportQuangCao(id) {
  return api.delete(`/report-quang-cao/${id}`)
}
