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

const HINH_ANH_UPLOAD_TIMEOUT = 120000

/**
 * Danh sách hình ảnh trong thư mục concept (public + storage).
 * @param {{ page?: number, per_page?: number, keyword?: string }} params
 */
export function fetchHinhAnhConcept(params = {}) {
  return api.get('/concept/hinh-anh', { params })
}

/**
 * Đổi tên file hình ảnh concept.
 * @param {{ path: string, name: string }} payload
 */
export function updateHinhAnhConcept(payload) {
  return api.put('/concept/hinh-anh', payload)
}

/**
 * Tải 1 chunk ảnh hoặc zip.
 * @param {FormData} formData
 */
export function uploadHinhAnhConceptChunk(formData) {
  return api.post('/concept/hinh-anh/chunk', formData, {
    skipLoading: true,
    timeout: HINH_ANH_UPLOAD_TIMEOUT,
  })
}

/**
 * Ghép chunk thành file.
 * @param {{ upload_id: string }} payload
 */
export function completeHinhAnhConceptUpload(payload) {
  return api.post('/concept/hinh-anh/complete', payload, {
    skipLoading: true,
    timeout: HINH_ANH_UPLOAD_TIMEOUT,
  })
}

/**
 * Giải nén ảnh từ zip trong thư mục concept.
 * @param {{ path: string, cursor?: number, limit?: number }} payload
 */
export function giaiNenHinhAnhConcept(payload) {
  return api.post('/concept/hinh-anh/giai-nen', payload, {
    skipLoading: true,
    timeout: HINH_ANH_UPLOAD_TIMEOUT,
  })
}

/**
 * Xóa danh sách file ảnh / zip.
 * @param {{ paths: string[] }} payload
 */
export function deleteHinhAnhConcept(payload) {
  return api.delete('/concept/hinh-anh', { data: payload })
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
