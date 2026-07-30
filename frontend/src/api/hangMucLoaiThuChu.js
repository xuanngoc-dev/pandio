import api from '@/api/axios'

/**
 * Danh sách hạng mục loại thu chi.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string }} params
 */
export function fetchHangMucLoaiThuChu(params = {}) {
  return api.get('/hang-muc-loai-thu-chu', { params })
}

/**
 * @param {number|string} id
 */
export function getHangMucLoaiThuChu(id) {
  return api.get(`/hang-muc-loai-thu-chu/${id}`)
}

/**
 * @param {object} payload
 */
export function createHangMucLoaiThuChu(payload) {
  return api.post('/hang-muc-loai-thu-chu', payload)
}

/**
 * @param {number|string} id
 * @param {object} payload
 */
export function updateHangMucLoaiThuChu(id, payload) {
  return api.put(`/hang-muc-loai-thu-chu/${id}`, payload)
}

/**
 * @param {number|string} id
 */
export function deleteHangMucLoaiThuChu(id) {
  return api.delete(`/hang-muc-loai-thu-chu/${id}`)
}
