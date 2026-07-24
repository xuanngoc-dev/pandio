import api from '@/api/axios'

/**
 * Danh sách chi nhánh — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string }} params
 */
export function fetchChiNhanh(params = {}) {
  return api.get('/cau-hinh-chi-nhanh', { params })
}

/**
 * Chi tiết chi nhánh.
 * @param {number|string} id
 */
export function getChiNhanh(id) {
  return api.get(`/cau-hinh-chi-nhanh/${id}`)
}

/**
 * Tạo chi nhánh.
 * @param {object} payload
 */
export function createChiNhanh(payload) {
  return api.post('/cau-hinh-chi-nhanh', payload)
}

/**
 * Cập nhật chi nhánh.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateChiNhanh(id, payload) {
  return api.put(`/cau-hinh-chi-nhanh/${id}`, payload)
}

/**
 * Xóa chi nhánh.
 * @param {number|string} id
 */
export function deleteChiNhanh(id) {
  return api.delete(`/cau-hinh-chi-nhanh/${id}`)
}
