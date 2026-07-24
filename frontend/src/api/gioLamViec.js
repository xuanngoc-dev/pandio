import api from '@/api/axios'

/**
 * Danh sách giờ làm việc — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, su_dung?: string }} params
 */
export function fetchGioLamViec(params = {}) {
  return api.get('/cau-hinh-gio-lam-viec', { params })
}

function assertId(id) {
  if (id === undefined || id === null || id === '' || id === 'undefined') {
    return Promise.reject(new Error('ID giờ làm việc không hợp lệ.'))
  }
  return null
}

/**
 * Chi tiết giờ làm việc.
 * @param {number|string} id
 */
export function getGioLamViec(id) {
  const err = assertId(id)
  if (err) return err
  return api.get(`/cau-hinh-gio-lam-viec/${id}`)
}

/**
 * Tạo giờ làm việc.
 * @param {object} payload
 */
export function createGioLamViec(payload) {
  return api.post('/cau-hinh-gio-lam-viec', payload)
}

/**
 * Cập nhật giờ làm việc.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateGioLamViec(id, payload) {
  const err = assertId(id)
  if (err) return err
  return api.put(`/cau-hinh-gio-lam-viec/${id}`, payload)
}

/**
 * Xóa giờ làm việc.
 * @param {number|string} id
 */
export function deleteGioLamViec(id) {
  const err = assertId(id)
  if (err) return err
  return api.delete(`/cau-hinh-gio-lam-viec/${id}`)
}
