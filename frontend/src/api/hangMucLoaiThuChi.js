import api from '@/api/axios'

/**
 * Danh sách hạng mục loại thu chi.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string }} params
 */
export function fetchHangMucLoaiThuChi(params = {}) {
  return api.get('/hang-muc-loai-thu-chi', { params })
}

/**
 * @param {number|string} id
 */
export function getHangMucLoaiThuChi(id) {
  return api.get(`/hang-muc-loai-thu-chi/${id}`)
}

/**
 * @param {object} payload
 */
export function createHangMucLoaiThuChi(payload) {
  return api.post('/hang-muc-loai-thu-chi', payload)
}

/**
 * @param {number|string} id
 * @param {object} payload
 */
export function updateHangMucLoaiThuChi(id, payload) {
  return api.put(`/hang-muc-loai-thu-chi/${id}`, payload)
}

/**
 * @param {number|string} id
 */
export function deleteHangMucLoaiThuChi(id) {
  return api.delete(`/hang-muc-loai-thu-chi/${id}`)
}
