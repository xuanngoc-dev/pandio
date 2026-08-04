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

/**
 * Thanh toán hợp đồng cho thuê trang phục.
 * @param {number|string} id
 * @param {{
 *   so_tien_thanh_toan: number,
 *   hinh_thuc_thanh_toan: 'tien_mat'|'chuyen_khoan',
 *   tien_den_bu?: number,
 *   phi_tra_muon?: number,
 *   phi_phu_thu?: number,
 *   uu_dai_tat_toan?: number,
 *   tong_tien_thanh_toan?: number,
 *   ngay_tra_chinh_thuc?: string,
 *   ghi_chu_sale?: string|null,
 *   ghi_chu_khach?: string|null,
 *   san_pham_cho_thue?: Array<{ id: number, trang_thai_hoan_tra: 'da_hoan_tra'|'chua_hoan_tra', ghi_chu?: string|null }>,
 * }} payload
 */
export function thanhToanHopDongChoThueTrangPhuc(id, payload) {
  return api.post(`/hop-dong-cho-thue-trang-phuc/${id}/thanh-toan`, payload)
}
