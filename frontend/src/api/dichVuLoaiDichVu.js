import api from '@/api/axios'

/**
 * Danh sách loại dịch vụ — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string }} params
 */
export function fetchDichVuLoaiDichVu(params = {}) {
  return api.get('/dich-vu-loai-dich-vu', { params })
}

/**
 * Chi tiết loại dịch vụ.
 * @param {number|string} id
 */
export function getDichVuLoaiDichVu(id) {
  return api.get(`/dich-vu-loai-dich-vu/${id}`)
}

/**
 * Tạo loại dịch vụ.
 * @param {object} payload
 */
export function createDichVuLoaiDichVu(payload) {
  return api.post('/dich-vu-loai-dich-vu', payload)
}

/**
 * Cập nhật loại dịch vụ.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateDichVuLoaiDichVu(id, payload) {
  return api.put(`/dich-vu-loai-dich-vu/${id}`, payload)
}

/**
 * Xóa loại dịch vụ.
 * @param {number|string} id
 */
export function deleteDichVuLoaiDichVu(id) {
  return api.delete(`/dich-vu-loai-dich-vu/${id}`)
}
