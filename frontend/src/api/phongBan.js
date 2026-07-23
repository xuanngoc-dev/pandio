import api from '@/api/axios'

/**
 * Danh sách phòng ban — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string }} params
 */
export function fetchPhongBan(params = {}) {
  return api.get('/phong-ban', { params })
}

/**
 * Chi tiết phòng ban.
 * @param {number|string} id
 */
export function getPhongBan(id) {
  return api.get(`/phong-ban/${id}`)
}

/**
 * Tạo phòng ban.
 * @param {object} payload
 */
export function createPhongBan(payload) {
  return api.post('/phong-ban', payload)
}

/**
 * Cập nhật phòng ban.
 * @param {number|string} id
 * @param {object} payload
 */
export function updatePhongBan(id, payload) {
  return api.put(`/phong-ban/${id}`, payload)
}

/**
 * Xóa phòng ban.
 * @param {number|string} id
 */
export function deletePhongBan(id) {
  return api.delete(`/phong-ban/${id}`)
}
