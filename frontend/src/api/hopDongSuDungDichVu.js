import api from '@/api/axios'

/**
 * Danh sách hợp đồng sử dụng dịch vụ — phân trang.
 * @param {{
 *   page?: number,
 *   per_page?: number,
 *   keyword?: string,
 *   loai_hop_dong_id?: number,
 *   trang_thai?: string,
 *   chi_nhap?: boolean|number,
 *   tu_ngay?: string,
 *   den_ngay?: string,
 * }} params
 */
export function fetchHopDongSuDungDichVu(params = {}) {
  return api.get('/hop-dong-su-dung-dich-vu', { params })
}

/**
 * Công việc điều phối của user đang đăng nhập
 * (user id nằm trong thong_tin_dieu_phoi staff fields).
 * @param {{
 *   page?: number,
 *   per_page?: number,
 *   ket_qua_trang_thai?: 'cho_nhan'|'dang_xu_ly'|'gui_khach_kiem_tra'|'san_xuat_in_an'|'hoan_thanh',
 * }} params
 */
export function fetchCongViecDieuPhoiCuaToi(params = {}) {
  return api.get('/hop-dong-su-dung-dich-vu/cong-viec-cua-toi', { params })
}

/**
 * Nhận công việc điều phối → ket_qua_hop_dong.trang_thai = dang_xu_ly.
 * @param {number|string} id
 */
export function nhanCongViecDieuPhoi(id) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/nhan-cong-viec`)
}

/**
 * Chi tiết hợp đồng sử dụng dịch vụ.
 * @param {number|string} id
 */
export function getHopDongSuDungDichVu(id) {
  return api.get(`/hop-dong-su-dung-dich-vu/${id}`)
}

/**
 * Khởi tạo hợp đồng nháp + sinh mã HDSDDV_DDMMYYYY{id}.
 */
export function khoiTaoHopDongSuDungDichVu() {
  return api.post('/hop-dong-su-dung-dich-vu/khoi-tao')
}

/**
 * Tạo hợp đồng sử dụng dịch vụ.
 * @param {object} payload
 */
export function createHopDongSuDungDichVu(payload) {
  return api.post('/hop-dong-su-dung-dich-vu', payload)
}

/**
 * Cập nhật hợp đồng sử dụng dịch vụ.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateHopDongSuDungDichVu(id, payload) {
  return api.put(`/hop-dong-su-dung-dich-vu/${id}`, payload)
}

/**
 * Xóa hợp đồng sử dụng dịch vụ.
 * @param {number|string} id
 */
export function deleteHopDongSuDungDichVu(id) {
  return api.delete(`/hop-dong-su-dung-dich-vu/${id}`)
}
