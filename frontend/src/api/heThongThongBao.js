import api from '@/api/axios'

/**
 * Danh sách thông báo hệ thống — phân trang.
 * @param {{
 *   page?: number,
 *   per_page?: number,
 *   keyword?: string,
 *   loai_thong_bao_id?: number,
 *   loai_mau_sac?: string,
 * }} params
 */
export function fetchHeThongThongBao(params = {}) {
  return api.get('/he-thong-thong-bao', { params })
}

/**
 * Thông báo của người đăng nhập (người nhận, chưa tự xoá).
 * @param {{ page?: number, per_page?: number }} params
 */
export function fetchThongBaoCuaToi(params = {}) {
  return api.get('/he-thong-thong-bao/cua-toi', { params })
}

/**
 * Đánh dấu một thông báo đã đọc.
 * @param {number|string} id
 */
export function danhDauThongBaoDaDoc(id) {
  return api.post(`/he-thong-thong-bao/${id}/da-doc`)
}

/**
 * Đánh dấu tất cả thông báo của tôi đã đọc.
 */
export function danhDauTatCaThongBaoDaDoc() {
  return api.post('/he-thong-thong-bao/da-doc-tat-ca')
}

/**
 * Ẩn thông báo với người dùng hiện tại.
 * @param {number|string} id
 */
export function xoaThongBaoCuaToi(id) {
  return api.post(`/he-thong-thong-bao/${id}/xoa-cua-toi`)
}

/**
 * Chi tiết thông báo.
 * @param {number|string} id
 */
export function getHeThongThongBao(id) {
  return api.get(`/he-thong-thong-bao/${id}`)
}

/**
 * Tạo thông báo.
 * @param {object} payload
 */
export function createHeThongThongBao(payload) {
  return api.post('/he-thong-thong-bao', payload)
}

/**
 * Cập nhật thông báo.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateHeThongThongBao(id, payload) {
  return api.put(`/he-thong-thong-bao/${id}`, payload)
}

/**
 * Xóa thông báo.
 * @param {number|string} id
 */
export function deleteHeThongThongBao(id) {
  return api.delete(`/he-thong-thong-bao/${id}`)
}
