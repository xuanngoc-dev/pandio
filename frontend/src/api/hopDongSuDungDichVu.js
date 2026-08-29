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
 *   loai_quay_chup_id?: number,
 *   ngay_chup_tu?: string,
 *   ngay_chup_den?: string,
 *   so_diem_chup?: number,
 *   co_tho_chup?: '0'|'1'|0|1,
 *   co_tho_make?: '0'|'1'|0|1,
 *   co_quay_phim?: '0'|'1'|0|1,
 *   co_tho_edit?: '0'|'1'|0|1,
 * }} params
 */
export function fetchHopDongSuDungDichVu(params = {}) {
  return api.get('/hop-dong-su-dung-dich-vu', { params })
}

/**
 * Lịch chụp-make: danh sách HĐ theo khoảng ngày chụp (sort theo gio_chup).
 * @param {{ tu_ngay: string, den_ngay: string }} params
 * @returns {Promise<{ data: { loai_hop_dong: Array, items: Array } }>}
 */
export function fetchLichChupMake(params = {}) {
  return api.get('/hop-dong-su-dung-dich-vu/lich-chup-make', { params })
}

/**
 * Danh sách HĐ lịch chụp-make theo ngày chụp (+ loại HĐ).
 * @param {{
 *   ngay_chup: string,
 *   loai_hop_dong_id?: number,
 *   page?: number,
 *   per_page?: number,
 * }} params
 */
export function fetchLichChupMakeChiTiet(params = {}) {
  return api.get('/hop-dong-su-dung-dich-vu/lich-chup-make/chi-tiet', { params })
}

/**
 * Lịch hậu kỳ: số lượng HĐ theo ngày trả file lẻ / file in / khách hẹn qua.
 * @param {{ tu_ngay: string, den_ngay: string }} params
 * @returns {Promise<{ data: { items: Array<{ ngay: string, tra_file_le: number, tra_file_in: number, khach_qua: number }> } }>}
 */
export function fetchLichHauKy(params = {}) {
  return api.get('/hop-dong-su-dung-dich-vu/lich-hau-ky', { params, skipLoading: true })
}

/**
 * Danh sách HĐ lịch hậu kỳ theo ngày + loại mốc.
 * @param {{
 *   ngay: string,
 *   loai: 'ngay_tra_file_le'|'ngay_tra_file_in'|'ngay_khach_hen_qua',
 *   page?: number,
 *   per_page?: number,
 * }} params
 */
export function fetchLichHauKyChiTiet(params = {}) {
  return api.get('/hop-dong-su-dung-dich-vu/lich-hau-ky/chi-tiet', { params, skipLoading: true })
}

/**
 * Công việc điều phối của user đang đăng nhập.
 * Tab tiền kỳ: trang_thai_dieu_phoi = tien_ky và user id nằm trong
 * tho_chup / tho_make / quay_phim của một buổi trong danh_sach_buoi_chup.
 * @param {{
 *   page?: number,
 *   per_page?: number,
 *   ket_qua_trang_thai?: 'tien_ky'|'hau_ky'|'gui_in'|'hoan_tat_san_xuat',
 *   keyword?: string,
 *   loai_hop_dong_id?: number,
 *   ngay_chup?: string,
 *   ngay_tra_file_le?: string,
 *   ngay_tra_file_in?: string,
 *   ngay_khach_hen_qua?: string,
 *   co_file_goc?: 0|1,
 *   co_file_le?: 0|1,
 *   co_file_in?: 0|1,
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
 * Cập nhật một field trong ket_qua_hop_dong (link_file_in, link_file_goc, ...).
 * File gốc / file lẻ / file in: đồng thời ghi thoi_gian_up_file.
 * @param {number|string} id
 * @param {{ key: string, gia_tri: string }} payload
 */
export function capNhatKetQuaHopDong(id, payload) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/ket-qua-hop-dong`, payload)
}

/**
 * Cập nhật toàn bộ thong_tin_dieu_phoi (modal điều phối).
 * Chỉ admin / coordinator.
 * @param {number|string} id
 * @param {{ thong_tin_dieu_phoi: object }} payload
 */
export function capNhatThongTinDieuPhoi(id, payload) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/dieu-phoi`, payload)
}

/**
 * Cập nhật ngày dùng chung trong thong_tin_dieu_phoi (file lẻ, file in, khách hẹn qua).
 * Admin/coordinator được sửa ở bước tiền kỳ và hậu kỳ.
 * @param {number|string} id
 * @param {{ key: 'ngay_tra_file_le'|'ngay_tra_file_in'|'ngay_khach_hen_qua', gia_tri?: string|null }} payload
 */
export function capNhatNgayDieuPhoi(id, payload) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/ngay-dieu-phoi`, payload)
}

/**
 * Chuyển công việc từ tiền kỳ sang hậu kỳ.
 * @param {number|string} id
 */
export function chuyenHauKyCongViec(id) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/chuyen-hau-ky`)
}

/**
 * Chuyển công việc từ hậu kỳ sang gửi in.
 * @param {number|string} id
 */
export function chuyenGuiInCongViec(id) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/chuyen-gui-in`)
}

/**
 * Chuyển công việc từ gửi in sang hoàn tất sản xuất.
 * @param {number|string} id
 */
export function chuyenHoanTatSanXuatCongViec(id) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/chuyen-hoan-tat-san-xuat`)
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
 * Kiểm tra mã giảm giá (mã mặc định hoặc SĐT khách hàng HĐ hoàn thành).
 * @param {{ ma_giam_gia: string, co_so_tinh?: number }} payload
 * @param {{ skipLoading?: boolean }} [config]
 */
export function kiemTraMaGiamGiaHopDongSuDungDichVu(payload, config = {}) {
  return api.post('/hop-dong-su-dung-dich-vu/kiem-tra-ma-giam-gia', payload, config)
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

/**
 * Đổi trạng thái hợp đồng từ vận hành cuối
 * (hủy, tất toán, khách đồng ý/không đồng ý, nghiệm thu).
 * @param {number|string} id
 * @param {{
 *   hanh_dong: 'huy'|'tat_toan'|'khach_dong_y'|'khach_khong_dong_y'|'nghiem_thu_hoan_thanh'|'nghiem_thu_lam_lai',
 *   y_kien_khach_hang?: string|null,
 *   ly_do?: string|null,
 * }} payload
 */
export function doiTrangThaiHopDongSuDungDichVu(id, payload) {
  return api.post(`/hop-dong-su-dung-dich-vu/${id}/doi-trang-thai`, payload)
}
