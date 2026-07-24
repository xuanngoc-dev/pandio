import api from '@/api/axios'

/**
 * Danh sách ca làm việc — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string }} params
 */
export function fetchCaLamViec(params = {}) {
  return api.get('/cau-hinh-ca-lam-viec', { params })
}

/**
 * Chi tiết ca làm việc.
 * @param {number|string} id
 */
export function getCaLamViec(id) {
  return api.get(`/cau-hinh-ca-lam-viec/${id}`)
}

/**
 * Tạo ca làm việc.
 * @param {object} payload
 */
export function createCaLamViec(payload) {
  return api.post('/cau-hinh-ca-lam-viec', payload)
}

/**
 * Cập nhật ca làm việc.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateCaLamViec(id, payload) {
  return api.put(`/cau-hinh-ca-lam-viec/${id}`, payload)
}

/**
 * Xóa ca làm việc.
 * @param {number|string} id
 */
export function deleteCaLamViec(id) {
  return api.delete(`/cau-hinh-ca-lam-viec/${id}`)
}
