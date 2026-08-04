import api from '@/api/axios'

/**
 * Danh sách hợp đồng cho thuê trang phục — phân trang.
 * @param {{
 *   page?: number,
 *   per_page?: number,
 *   keyword?: string,
 *   trang_thai?: string,
 *   chi_nhap?: boolean|number,
 *   tu_ngay?: string,
 *   den_ngay?: string,
 * }} params
 */
export function fetchHopDongChoThueTrangPhuc(params = {}) {
  return api.get('/hop-dong-cho-thue-trang-phuc', { params })
}

/**
 * Chi tiết hợp đồng cho thuê trang phục.
 * @param {number|string} id
 */
export function getHopDongChoThueTrangPhuc(id) {
  return api.get(`/hop-dong-cho-thue-trang-phuc/${id}`)
}

/**
 * Khởi tạo hợp đồng nháp + sinh mã HDTTP_DDMMYYYY{id}.
 */
export function khoiTaoHopDongChoThueTrangPhuc() {
  return api.post('/hop-dong-cho-thue-trang-phuc/khoi-tao')
}

/**
 * Tạo hợp đồng cho thuê trang phục.
 * @param {object} payload
 */
export function createHopDongChoThueTrangPhuc(payload) {
  return api.post('/hop-dong-cho-thue-trang-phuc', payload)
}

/**
 * Cập nhật hợp đồng cho thuê trang phục.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateHopDongChoThueTrangPhuc(id, payload) {
  return api.put(`/hop-dong-cho-thue-trang-phuc/${id}`, payload)
}

/**
 * Xóa hợp đồng cho thuê trang phục.
 * @param {number|string} id
 */
export function deleteHopDongChoThueTrangPhuc(id) {
  return api.delete(`/hop-dong-cho-thue-trang-phuc/${id}`)
}
