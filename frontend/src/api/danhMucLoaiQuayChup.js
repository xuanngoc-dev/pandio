import api from '@/api/axios'

/**
 * Danh sách loại quay chụp — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string }} params
 */
export function fetchDanhMucLoaiQuayChup(params = {}) {
  return api.get('/danh-muc-loai-quay-chup', { params })
}

/**
 * Chi tiết loại quay chụp.
 * @param {number|string} id
 */
export function getDanhMucLoaiQuayChup(id) {
  return api.get(`/danh-muc-loai-quay-chup/${id}`)
}

/**
 * Tạo loại quay chụp.
 * @param {object} payload
 */
export function createDanhMucLoaiQuayChup(payload) {
  return api.post('/danh-muc-loai-quay-chup', payload)
}

/**
 * Cập nhật loại quay chụp.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateDanhMucLoaiQuayChup(id, payload) {
  return api.put(`/danh-muc-loai-quay-chup/${id}`, payload)
}

/**
 * Xóa loại quay chụp.
 * @param {number|string} id
 */
export function deleteDanhMucLoaiQuayChup(id) {
  return api.delete(`/danh-muc-loai-quay-chup/${id}`)
}
