import api from '@/api/axios'

/**
 * Danh sách vai trò — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string }} params
 */
export function fetchVaiTro(params = {}) {
  return api.get('/vai-tro', { params })
}

/**
 * Chi tiết vai trò.
 * @param {number|string} id
 */
export function getVaiTro(id) {
  return api.get(`/vai-tro/${id}`)
}

/**
 * Tạo vai trò.
 * @param {object} payload
 */
export function createVaiTro(payload) {
  return api.post('/vai-tro', payload)
}

/**
 * Cập nhật vai trò.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateVaiTro(id, payload) {
  return api.put(`/vai-tro/${id}`, payload)
}

/**
 * Xóa vai trò.
 * @param {number|string} id
 */
export function deleteVaiTro(id) {
  return api.delete(`/vai-tro/${id}`)
}
