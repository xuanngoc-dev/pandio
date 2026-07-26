import api from '@/api/axios'

/**
 * Danh sách dịch vụ lẻ — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string, loai_dich_vu_id?: number, loai_hop_dong_id?: number }} params
 */
export function fetchDichVuDanhSachDichVuLe(params = {}) {
  return api.get('/dich-vu-danh-sach-dich-vu-le', { params })
}

/**
 * Chi tiết dịch vụ lẻ.
 * @param {number|string} id
 */
export function getDichVuDanhSachDichVuLe(id) {
  return api.get(`/dich-vu-danh-sach-dich-vu-le/${id}`)
}

/**
 * Tạo dịch vụ lẻ.
 * @param {object} payload
 */
export function createDichVuDanhSachDichVuLe(payload) {
  return api.post('/dich-vu-danh-sach-dich-vu-le', payload)
}

/**
 * Cập nhật dịch vụ lẻ.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateDichVuDanhSachDichVuLe(id, payload) {
  return api.put(`/dich-vu-danh-sach-dich-vu-le/${id}`, payload)
}

/**
 * Xóa dịch vụ lẻ.
 * @param {number|string} id
 */
export function deleteDichVuDanhSachDichVuLe(id) {
  return api.delete(`/dich-vu-danh-sach-dich-vu-le/${id}`)
}
