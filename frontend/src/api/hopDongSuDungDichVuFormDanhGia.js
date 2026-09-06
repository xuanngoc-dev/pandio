import api from '@/api/axios'

/**
 * Tạo bản ghi link đánh giá (hợp đồng + form mẫu).
 * @param {{ hop_dong_danh_gia_id: number, form_danh_gia_id: number }} payload
 */
export function createHopDongSuDungDichVuFormDanhGia(payload) {
  return api.post('/hop-dong-su-dung-dich-vu-form-danh-gia', payload)
}
