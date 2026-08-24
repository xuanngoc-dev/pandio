import api from '@/api/axios'

/**
 * Danh sách khách hàng gom từ note / HĐ dịch vụ / HĐ thuê — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, loai_hop_dong?: string }} params
 */
export function fetchKhachHang(params = {}) {
  return api.get('/khach-hang', { params })
}
