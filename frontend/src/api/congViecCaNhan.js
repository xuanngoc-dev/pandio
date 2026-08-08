import api from '@/api/axios'

/**
 * Danh sách công việc cá nhân — phân trang.
 * API chỉ trả việc do user hiện tại giao hoặc được giao phụ trách.
 * @param {{
 *   page?: number,
 *   per_page?: number,
 *   keyword?: string,
 *   trang_thai?: string,
 *   muc_do_uu_tien?: number,
 * }} params
 */
export function fetchCongViecCaNhan(params = {}) {
  return api.get('/cong-viec-ca-nhan', { params })
}

/**
 * Chi tiết công việc cá nhân.
 * @param {number|string} id
 */
export function getCongViecCaNhan(id) {
  return api.get(`/cong-viec-ca-nhan/${id}`)
}

/**
 * Tạo công việc cá nhân.
 * @param {object} payload
 */
export function createCongViecCaNhan(payload) {
  return api.post('/cong-viec-ca-nhan', payload)
}

/**
 * Cập nhật công việc cá nhân.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateCongViecCaNhan(id, payload) {
  return api.put(`/cong-viec-ca-nhan/${id}`, payload)
}

/**
 * Xóa công việc cá nhân.
 * @param {number|string} id
 */
export function deleteCongViecCaNhan(id) {
  return api.delete(`/cong-viec-ca-nhan/${id}`)
}
