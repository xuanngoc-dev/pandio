import api from '@/api/axios'

/**
 * Lịch khách hàng theo khoảng ngày (Hẹn lịch / Đến).
 * @param {{ tu_ngay: string, den_ngay: string }} params
 */
export function fetchLichKhachHang(params = {}) {
  return api.get('/khach-hang-note-khach-moi/lich', { params })
}

/**
 * Chi tiết lịch khách hàng theo ngày.
 * @param {{ ngay: string, loai?: string, trang_thai?: string }} params
 */
export function fetchLichKhachHangChiTiet(params = {}) {
  return api.get('/khach-hang-note-khach-moi/lich/chi-tiet', { params, skipLoading: true })
}

/**
 * Danh sách note khách mới — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string, ngay_hen_tu?: string, ngay_hen_den?: string }} params
 */
export function fetchNoteKhachMoi(params = {}) {
  return api.get('/khach-hang-note-khach-moi', { params })
}

/**
 * Chi tiết note khách mới.
 * @param {number|string} id
 */
export function getNoteKhachMoi(id) {
  return api.get(`/khach-hang-note-khach-moi/${id}`)
}

/**
 * Tạo note khách mới.
 * @param {object} payload
 */
export function createNoteKhachMoi(payload) {
  return api.post('/khach-hang-note-khach-moi', payload)
}

/**
 * Cập nhật note khách mới.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateNoteKhachMoi(id, payload) {
  return api.put(`/khach-hang-note-khach-moi/${id}`, payload)
}

/**
 * Xóa note khách mới.
 * @param {number|string} id
 */
export function deleteNoteKhachMoi(id) {
  return api.delete(`/khach-hang-note-khach-moi/${id}`)
}
