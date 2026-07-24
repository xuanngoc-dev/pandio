import api from '@/api/axios'

/**
 * Danh sách concept — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, loai_concept?: number, trang_thai?: string }} params
 */
export function fetchConcept(params = {}) {
  return api.get('/concept', { params })
}

/**
 * Chi tiết concept.
 * @param {number|string} id
 */
export function getConcept(id) {
  return api.get(`/concept/${id}`)
}

/**
 * Upload hình ảnh concept.
 * @param {File} file
 */
export function uploadConceptHinhAnh(file) {
  const formData = new FormData()
  formData.append('hinh_anh', file)
  return api.post('/concept/upload-hinh-anh', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

/**
 * Tạo concept.
 * @param {object} payload
 */
export function createConcept(payload) {
  return api.post('/concept', payload)
}

/**
 * Cập nhật concept.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateConcept(id, payload) {
  return api.put(`/concept/${id}`, payload)
}

/**
 * Xóa concept.
 * @param {number|string} id
 */
export function deleteConcept(id) {
  return api.delete(`/concept/${id}`)
}
