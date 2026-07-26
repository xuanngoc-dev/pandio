import api from '@/api/axios'

/**
 * Danh sách nhóm dịch vụ (combo) — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string, loai_dich_vu_id?: number }} params
 */
export function fetchDichVuDanhSachDichNhomDichVu(params = {}) {
  return api.get('/dich-vu-danh-sach-dich-nhom-dich-vu', { params })
}

/**
 * Chi tiết nhóm dịch vụ.
 * @param {number|string} id
 */
export function getDichVuDanhSachDichNhomDichVu(id) {
  return api.get(`/dich-vu-danh-sach-dich-nhom-dich-vu/${id}`)
}

/**
 * Tạo nhóm dịch vụ.
 * @param {object} payload
 */
export function createDichVuDanhSachDichNhomDichVu(payload) {
  return api.post('/dich-vu-danh-sach-dich-nhom-dich-vu', payload)
}

/**
 * Cập nhật nhóm dịch vụ.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateDichVuDanhSachDichNhomDichVu(id, payload) {
  return api.put(`/dich-vu-danh-sach-dich-nhom-dich-vu/${id}`, payload)
}

/**
 * Xóa nhóm dịch vụ.
 * @param {number|string} id
 */
export function deleteDichVuDanhSachDichNhomDichVu(id) {
  return api.delete(`/dich-vu-danh-sach-dich-nhom-dich-vu/${id}`)
}
