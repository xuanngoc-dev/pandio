import api from '@/api/axios'

/**
 * Danh sách tài khoản thanh toán — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, mac_dinh?: string, trang_thai?: string }} params
 */
export function fetchTaiKhoanThanhToan(params = {}) {
  return api.get('/cau-hinh-tai-khoan-thanh-toan', { params })
}

/**
 * Chi tiết tài khoản thanh toán.
 * @param {number|string} id
 */
export function getTaiKhoanThanhToan(id) {
  return api.get(`/cau-hinh-tai-khoan-thanh-toan/${id}`)
}

/**
 * Tạo tài khoản thanh toán.
 * @param {object} payload
 */
export function createTaiKhoanThanhToan(payload) {
  return api.post('/cau-hinh-tai-khoan-thanh-toan', payload)
}

/**
 * Cập nhật tài khoản thanh toán.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateTaiKhoanThanhToan(id, payload) {
  return api.put(`/cau-hinh-tai-khoan-thanh-toan/${id}`, payload)
}

/**
 * Xóa tài khoản thanh toán.
 * @param {number|string} id
 */
export function deleteTaiKhoanThanhToan(id) {
  return api.delete(`/cau-hinh-tai-khoan-thanh-toan/${id}`)
}
