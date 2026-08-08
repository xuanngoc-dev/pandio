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
 *   ket_qua_trang_thai?: 'cho_nhan'|'dang_xu_ly'|'gui_khach_kiem_tra'|'san_xuat_in_an'|'cho_nghiem_thu'|'hoan_thanh',
 *   keyword?: string,
 *   loai_hop_dong_id?: number,
 *   ngay_chup?: string,
 *   ngay_tra_demo?: string,
 *   ngay_tra_chinh_thuc?: string,
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
 * Cập nhật một field trong ket_qua_hop_dong (link_file_demo, link_file_goc, ...).
 * @param {number|string} id
 * @param {{ key: string, gia_tri: string }} payload
 */
export function capNhatKetQuaHopDong(id, payload) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/ket-qua-hop-dong`, payload)
}

/**
 * Gửi khách kiểm tra → ket_qua_hop_dong.trang_thai = gui_khach_kiem_tra.
 * @param {number|string} id
 */
export function guiKhachKiemTra(id) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/gui-khach-kiem-tra`)
}

/**
 * Xử lý phản hồi khách kiểm tra.
 * @param {number|string} id
 * @param {{ ket_qua: 'dong_y'|'khong_dong_y', y_kien_khach_hang?: string }} payload
 */
export function xuLyKhachKiemTra(id, payload) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/xu-ly-khach-kiem-tra`, payload)
}

/**
 * Bàn giao → ket_qua_hop_dong.trang_thai = cho_nghiem_thu.
 * @param {number|string} id
 */
export function banGiaoCongViec(id) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/ban-giao`)
}

/**
 * Xử lý nghiệm thu (làm lại / hoàn thành).
 * @param {number|string} id
 * @param {{ hanh_dong: 'lam_lai'|'hoan_thanh', y_kien_khach_hang?: string }} payload
 */
export function xuLyNghiemThu(id, payload) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/xu-ly-nghiem-thu`, payload)
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

/**
 * Ghi nhận thanh toán (lần 2 / lần 3).
 * @param {number|string} id
 * @param {{
 *   so_tien_thanh_toan: number,
 *   hinh_thuc_thanh_toan: 'tien_mat'|'chuyen_khoan',
 *   ghi_chu_sale?: string|null,
 * }} payload
 */
export function thanhToanHopDongSuDungDichVu(id, payload) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/thanh-toan`, payload)
}
