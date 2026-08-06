import api from '@/api/axios'

/**
 * Danh sách đặt mua trang phục — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, trang_thai?: string }} params
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchDatMuaTrangPhuc(params = {}, config = {}) {
  return api.get('/dat-mua-trang-phuc', { params, ...config })
}

/**
 * Chi tiết đơn đặt mua trang phục.
 * @param {number|string} id
 */
export function getDatMuaTrangPhuc(id) {
  return api.get(`/dat-mua-trang-phuc/${id}`)
}

/**
 * Tạo đơn đặt mua trang phục.
 * @param {object} payload
 */
export function createDatMuaTrangPhuc(payload) {
  return api.post('/dat-mua-trang-phuc', payload)
}

/**
 * Cập nhật đơn đặt mua trang phục.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateDatMuaTrangPhuc(id, payload) {
  return api.put(`/dat-mua-trang-phuc/${id}`, payload)
}

/**
 * Duyệt đơn đặt mua trang phục.
 * @param {number|string} id
 */
export function duyetDatMuaTrangPhuc(id) {
  return api.post(`/dat-mua-trang-phuc/${id}/duyet`)
}

/**
 * Hủy duyệt đơn đặt mua trang phục.
 * @param {number|string} id
 */
export function huyDuyetDatMuaTrangPhuc(id) {
  return api.post(`/dat-mua-trang-phuc/${id}/huy-duyet`)
}

/**
 * Duyệt nhiều đơn đặt mua trang phục.
 * @param {number[]} ids
 */
export function bulkDuyetDatMuaTrangPhuc(ids) {
  return api.post('/dat-mua-trang-phuc/bulk-duyet', { ids })
}

/**
 * Hủy duyệt nhiều đơn đặt mua trang phục.
 * @param {number[]} ids
 */
export function bulkHuyDuyetDatMuaTrangPhuc(ids) {
  return api.post('/dat-mua-trang-phuc/bulk-huy-duyet', { ids })
}

/**
 * Xóa đơn đặt mua trang phục.
 * @param {number|string} id
 */
export function deleteDatMuaTrangPhuc(id) {
  return api.delete(`/dat-mua-trang-phuc/${id}`)
}
