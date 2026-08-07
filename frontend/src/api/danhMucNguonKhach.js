import api from '@/api/axios'

/**
 * Danh sách nguồn khách — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string }} params
 */
export function fetchDanhMucNguonKhach(params = {}) {
  return api.get('/danh-muc-nguon-khach', { params })
}

/**
 * Chi tiết nguồn khách.
 * @param {number|string} id
 */
export function getDanhMucNguonKhach(id) {
  return api.get(`/danh-muc-nguon-khach/${id}`)
}

/**
 * Tạo nguồn khách.
 * @param {object} payload
 */
export function createDanhMucNguonKhach(payload) {
  return api.post('/danh-muc-nguon-khach', payload)
}

/**
 * Cập nhật nguồn khách.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateDanhMucNguonKhach(id, payload) {
  return api.put(`/danh-muc-nguon-khach/${id}`, payload)
}

/**
 * Xóa nguồn khách.
 * @param {number|string} id
 */
export function deleteDanhMucNguonKhach(id) {
  return api.delete(`/danh-muc-nguon-khach/${id}`)
}
