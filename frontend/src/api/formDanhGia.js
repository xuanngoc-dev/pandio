import api from '@/api/axios'

/**
 * Danh sách form đánh giá mẫu — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string }} params
 */
export function fetchFormDanhGia(params = {}) {
  return api.get('/cau-hinh-form-danh-gia-mau', { params })
}

/**
 * Lấy form đánh giá theo slug (công khai, không cần đăng nhập).
 * @param {string} slug
 */
export function getFormDanhGiaBySlug(slug) {
  return api.get(`/public/form-danh-gia/${encodeURIComponent(slug)}`, {
    skipLoading: true,
    silent401: true,
  })
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
