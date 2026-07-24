import api from '@/api/axios'

/**
 * Danh sách xin nghỉ phép — phân trang.
 * @param {{
 *   page?: number,
 *   per_page?: number,
 *   keyword?: string,
 *   user_id?: number|string,
 *   loai_nghi_phep?: string,
 *   trang_thai?: string,
 * }} params
 */
export function fetchXinNghiPhep(params = {}) {
  return api.get('/xin-nghi-phep', { params })
}

/**
 * Chi tiết đơn xin nghỉ phép.
 * @param {number|string} id
 */
export function getXinNghiPhep(id) {
  return api.get(`/xin-nghi-phep/${id}`)
}

/**
 * Tạo đơn xin nghỉ phép.
 * @param {object} payload
 */
export function createXinNghiPhep(payload) {
  return api.post('/xin-nghi-phep', payload)
}

/**
 * Cập nhật đơn xin nghỉ phép.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateXinNghiPhep(id, payload) {
  return api.put(`/xin-nghi-phep/${id}`, payload)
}

/**
 * Duyệt đơn nghỉ phép.
 * @param {number|string} id
 */
export function duyetXinNghiPhep(id) {
  return api.post(`/xin-nghi-phep/${id}/duyet`)
}

/**
 * Từ chối đơn nghỉ phép.
 * @param {number|string} id
 */
export function tuChoiXinNghiPhep(id) {
  return api.post(`/xin-nghi-phep/${id}/tu-choi`)
}

/**
 * Xóa đơn xin nghỉ phép.
 * @param {number|string} id
 */
export function deleteXinNghiPhep(id) {
  return api.delete(`/xin-nghi-phep/${id}`)
}
