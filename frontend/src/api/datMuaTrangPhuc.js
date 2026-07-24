import api from '@/api/axios'

/**
 * Danh sách đặt mua trang phục — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string }} params
 */
export function fetchDatMuaTrangPhuc(params = {}) {
  return api.get('/dat-mua-trang-phuc', { params })
}

/**
 * Chi tiết đơn đặt mua trang phục.
 * @param {number|string} id
 */
export function getDatMuaTrangPhuc(id) {
  return api.get(`/dat-mua-trang-phuc/${id}`)
}

/**
 * Tạo đơn đặt mua trang phục.
 * @param {object} payload
 */
export function createDatMuaTrangPhuc(payload) {
  return api.post('/dat-mua-trang-phuc', payload)
}

/**
 * Cập nhật đơn đặt mua trang phục.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateDatMuaTrangPhuc(id, payload) {
  return api.put(`/dat-mua-trang-phuc/${id}`, payload)
}

/**
 * Xóa đơn đặt mua trang phục.
 * @param {number|string} id
 */
export function deleteDatMuaTrangPhuc(id) {
  return api.delete(`/dat-mua-trang-phuc/${id}`)
}
