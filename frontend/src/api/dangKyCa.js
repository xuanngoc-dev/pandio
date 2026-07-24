import api from '@/api/axios'

/**
 * Danh sách đăng ký ca theo khoảng ngày.
 * @param {{
 *   tu_ngay?: string,
 *   den_ngay?: string,
 *   nguoi_dung_id?: number|string,
 * }} params
 */
export function fetchDangKyCa(params = {}) {
  return api.get('/dang-ky-ca-lam-viec', { params })
}

/**
 * Chi tiết đăng ký ca.
 * @param {number|string} id
 */
export function getDangKyCa(id) {
  return api.get(`/dang-ky-ca-lam-viec/${id}`)
}

/**
 * Tạo / cập nhật đăng ký ca (upsert theo nhân viên + ngày).
 * @param {{ ca_lam_id: number, nguoi_dung_id: number, ngay_lam: string }} payload
 */
export function createDangKyCa(payload) {
  return api.post('/dang-ky-ca-lam-viec', payload)
}

/**
 * Cập nhật đăng ký ca.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateDangKyCa(id, payload) {
  return api.put(`/dang-ky-ca-lam-viec/${id}`, payload)
}

/**
 * Đồng bộ đăng ký ca trong tuần.
 * @param {{
 *   tu_ngay: string,
 *   den_ngay: string,
 *   items: Array<{ nguoi_dung_id: number, ngay_lam: string, ca_lam_id: number|null }>
 * }} payload
 */
export function syncDangKyCaTuan(payload) {
  return api.post('/dang-ky-ca-lam-viec/sync-tuan', payload)
}

/**
 * Xóa đăng ký ca.
 * @param {number|string} id
 */
export function deleteDangKyCa(id) {
  return api.delete(`/dang-ky-ca-lam-viec/${id}`)
}
