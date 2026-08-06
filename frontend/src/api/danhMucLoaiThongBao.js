import api from '@/api/axios'

/**
 * Danh sách loại thông báo — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string }} params
 */
export function fetchDanhMucLoaiThongBao(params = {}) {
  return api.get('/danh-muc-loai-thong-bao', { params })
}

/**
 * Chi tiết loại thông báo.
 * @param {number|string} id
 */
export function getDanhMucLoaiThongBao(id) {
  return api.get(`/danh-muc-loai-thong-bao/${id}`)
}

/**
 * Tạo loại thông báo.
 * @param {object} payload
 */
export function createDanhMucLoaiThongBao(payload) {
  return api.post('/danh-muc-loai-thong-bao', payload)
}

/**
 * Cập nhật loại thông báo.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateDanhMucLoaiThongBao(id, payload) {
  return api.put(`/danh-muc-loai-thong-bao/${id}`, payload)
}

/**
 * Xóa loại thông báo.
 * @param {number|string} id
 */
export function deleteDanhMucLoaiThongBao(id) {
  return api.delete(`/danh-muc-loai-thong-bao/${id}`)
}
