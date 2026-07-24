import api from '@/api/axios'

/**
 * Danh sách ngày nghỉ — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string }} params
 */
export function fetchNgayNghi(params = {}) {
  return api.get('/cau-hinh-ngay-nghi', { params })
}

function assertId(id) {
  if (id === undefined || id === null || id === '' || id === 'undefined') {
    return Promise.reject(new Error('ID ngày nghỉ không hợp lệ.'))
  }
  return null
}

/**
 * Chi tiết ngày nghỉ.
 * @param {number|string} id
 */
export function getNgayNghi(id) {
  const err = assertId(id)
  if (err) return err
  return api.get(`/cau-hinh-ngay-nghi/${id}`)
}

/**
 * Tạo ngày nghỉ.
 * @param {object} payload
 */
export function createNgayNghi(payload) {
  return api.post('/cau-hinh-ngay-nghi', payload)
}

/**
 * Cập nhật ngày nghỉ.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateNgayNghi(id, payload) {
  const err = assertId(id)
  if (err) return err
  return api.put(`/cau-hinh-ngay-nghi/${id}`, payload)
}

/**
 * Xóa ngày nghỉ.
 * @param {number|string} id
 */
export function deleteNgayNghi(id) {
  const err = assertId(id)
  if (err) return err
  return api.delete(`/cau-hinh-ngay-nghi/${id}`)
}
