import api from '@/api/axios'

/**
 * Danh sách danh mục concept — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string }} params
 */
export function fetchDanhMucConcept(params = {}) {
  return api.get('/danh-muc-concept', { params })
}

/**
 * Chi tiết danh mục concept.
 * @param {number|string} id
 */
export function getDanhMucConcept(id) {
  return api.get(`/danh-muc-concept/${id}`)
}

/**
 * Tạo danh mục concept.
 * @param {object} payload
 */
export function createDanhMucConcept(payload) {
  return api.post('/danh-muc-concept', payload)
}

/**
 * Cập nhật danh mục concept.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateDanhMucConcept(id, payload) {
  return api.put(`/danh-muc-concept/${id}`, payload)
}

/**
 * Xóa danh mục concept.
 * @param {number|string} id
 */
export function deleteDanhMucConcept(id) {
  return api.delete(`/danh-muc-concept/${id}`)
}
