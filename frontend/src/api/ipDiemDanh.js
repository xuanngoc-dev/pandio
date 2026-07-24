import api from '@/api/axios'

/**
 * Danh sách IP điểm danh — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string }} params
 */
export function fetchIpDiemDanh(params = {}) {
  return api.get('/ip-diem-danh', { params })
}

/**
 * Chi tiết IP điểm danh.
 * @param {number|string} id
 */
export function getIpDiemDanh(id) {
  return api.get(`/ip-diem-danh/${id}`)
}

/**
 * Tạo IP điểm danh.
 * @param {object} payload
 */
export function createIpDiemDanh(payload) {
  return api.post('/ip-diem-danh', payload)
}

/**
 * Cập nhật IP điểm danh.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateIpDiemDanh(id, payload) {
  return api.put(`/ip-diem-danh/${id}`, payload)
}

/**
 * Xóa IP điểm danh.
 * @param {number|string} id
 */
export function deleteIpDiemDanh(id) {
  return api.delete(`/ip-diem-danh/${id}`)
}
