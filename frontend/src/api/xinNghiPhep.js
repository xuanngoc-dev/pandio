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
 *   tu_ngay?: string,
 *   den_ngay?: string,
 * }} params
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchXinNghiPhep(params = {}, config = {}) {
  return api.get('/xin-nghi-phep', { params, ...config })
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
 * Duyệt nhiều đơn nghỉ phép.
 * @param {number[]} ids
 */
export function bulkDuyetXinNghiPhep(ids) {
  return api.post('/xin-nghi-phep/bulk-duyet', { ids })
}

/**
 * Từ chối nhiều đơn nghỉ phép.
 * @param {number[]} ids
 */
export function bulkTuChoiXinNghiPhep(ids) {
  return api.post('/xin-nghi-phep/bulk-tu-choi', { ids })
}

/**
 * Xóa đơn xin nghỉ phép.
 * @param {number|string} id
 */
export function deleteXinNghiPhep(id) {
  return api.delete(`/xin-nghi-phep/${id}`)
}
