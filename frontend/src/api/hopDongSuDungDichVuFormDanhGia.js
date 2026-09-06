import api from '@/api/axios'

/**
 * Danh sách đánh giá đã nộp — lọc theo form (bắt buộc) + từ khoá.
 * @param {{ form_danh_gia_id: number, keyword?: string, page?: number, per_page?: number }} params
 */
export function fetchHopDongSuDungDichVuFormDanhGia(params = {}) {
  return api.get('/hop-dong-su-dung-dich-vu-form-danh-gia', { params })
}

/**
 * Tạo bản ghi link đánh giá (hợp đồng + form mẫu).
 * @param {{ hop_dong_danh_gia_id: number, form_danh_gia_id: number }} payload
 */
export function createHopDongSuDungDichVuFormDanhGia(payload) {
  return api.post('/hop-dong-su-dung-dich-vu-form-danh-gia', payload)
}

/**
 * Xóa nội dung đánh giá (giữ nguyên bản ghi link).
 * @param {number|string} id
 */
export function xoaNoiDungHopDongSuDungDichVuFormDanhGia(id) {
  return api.post(`/hop-dong-su-dung-dich-vu-form-danh-gia/${id}/xoa-noi-dung`)
}
