import api from '@/api/axios'

/**
 * Danh sách loại hợp đồng khách hàng — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string }} params
 */
export function fetchLoaiHopDong(params = {}) {
  return api.get('/loai-hop-dong', { params })
}

/**
 * Chi tiết loại hợp đồng.
 * @param {number|string} id
 */
export function getLoaiHopDong(id) {
  return api.get(`/loai-hop-dong/${id}`)
}

/**
 * Tạo loại hợp đồng.
 * @param {object} payload
 */
export function createLoaiHopDong(payload) {
  return api.post('/loai-hop-dong', payload)
}

/**
 * Cập nhật loại hợp đồng.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateLoaiHopDong(id, payload) {
  return api.put(`/loai-hop-dong/${id}`, payload)
}

/**
 * Xóa loại hợp đồng.
 * @param {number|string} id
 */
export function deleteLoaiHopDong(id) {
  return api.delete(`/loai-hop-dong/${id}`)
}
