import api from '@/api/axios'

/**
 * Danh sách form đánh giá mẫu — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string }} params
 */
export function fetchFormDanhGia(params = {}) {
  return api.get('/cau-hinh-form-danh-gia-mau', { params })
}

/**
 * Chi tiết form đánh giá mẫu.
 * @param {number|string} id
 */
export function getFormDanhGia(id) {
  return api.get(`/cau-hinh-form-danh-gia-mau/${id}`)
}

/**
 * Tạo form đánh giá mẫu.
 * @param {object} payload
 */
export function createFormDanhGia(payload) {
  return api.post('/cau-hinh-form-danh-gia-mau', payload)
}

/**
 * Cập nhật form đánh giá mẫu.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateFormDanhGia(id, payload) {
  return api.put(`/cau-hinh-form-danh-gia-mau/${id}`, payload)
}

/**
 * Xóa form đánh giá mẫu.
 * @param {number|string} id
 */
export function deleteFormDanhGia(id) {
  return api.delete(`/cau-hinh-form-danh-gia-mau/${id}`)
}
