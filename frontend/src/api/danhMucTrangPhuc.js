import api from '@/api/axios'

/**
 * Danh sách danh mục trang phục — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string }} params
 */
export function fetchDanhMucTrangPhuc(params = {}) {
  return api.get('/danh-muc-trang-phuc', { params })
}

/**
 * Chi tiết danh mục trang phục.
 * @param {number|string} id
 */
export function getDanhMucTrangPhuc(id) {
  return api.get(`/danh-muc-trang-phuc/${id}`)
}

/**
 * Tạo danh mục trang phục.
 * @param {object} payload
 */
export function createDanhMucTrangPhuc(payload) {
  return api.post('/danh-muc-trang-phuc', payload)
}

/**
 * Cập nhật danh mục trang phục.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateDanhMucTrangPhuc(id, payload) {
  return api.put(`/danh-muc-trang-phuc/${id}`, payload)
}

/**
 * Xóa danh mục trang phục.
 * @param {number|string} id
 */
export function deleteDanhMucTrangPhuc(id) {
  return api.delete(`/danh-muc-trang-phuc/${id}`)
}
