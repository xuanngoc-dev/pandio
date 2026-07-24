import api from '@/api/axios'

/**
 * Danh sách nhà cung cấp trang phục — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string }} params
 */
export function fetchNhaCungCapTrangPhuc(params = {}) {
  return api.get('/nha-cung-cap-trang-phuc', { params })
}

/**
 * Chi tiết nhà cung cấp trang phục.
 * @param {number|string} id
 */
export function getNhaCungCapTrangPhuc(id) {
  return api.get(`/nha-cung-cap-trang-phuc/${id}`)
}

/**
 * Tạo nhà cung cấp trang phục.
 * @param {object} payload
 */
export function createNhaCungCapTrangPhuc(payload) {
  return api.post('/nha-cung-cap-trang-phuc', payload)
}

/**
 * Cập nhật nhà cung cấp trang phục.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateNhaCungCapTrangPhuc(id, payload) {
  return api.put(`/nha-cung-cap-trang-phuc/${id}`, payload)
}

/**
 * Xóa nhà cung cấp trang phục.
 * @param {number|string} id
 */
export function deleteNhaCungCapTrangPhuc(id) {
  return api.delete(`/nha-cung-cap-trang-phuc/${id}`)
}
