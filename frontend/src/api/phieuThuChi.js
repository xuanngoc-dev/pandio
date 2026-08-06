import api from '@/api/axios'

/**
 * Danh sách phiếu thu chi.
 * @param {{ page?: number, per_page?: number, keyword?: string, loai?: string, trang_thai?: string, hang_muc_id?: number }} params
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchPhieuThuChi(params = {}, config = {}) {
  return api.get('/phieu-thu-chi', { params, ...config })
}

/**
 * @param {number|string} id
 */
export function getPhieuThuChi(id) {
  return api.get(`/phieu-thu-chi/${id}`)
}

/**
 * @param {object} payload
 */
export function createPhieuThuChi(payload) {
  return api.post('/phieu-thu-chi', payload)
}

/**
 * @param {number|string} id
 * @param {object} payload
 */
export function updatePhieuThuChi(id, payload) {
  return api.put(`/phieu-thu-chi/${id}`, payload)
}

/**
 * @param {number|string} id
 */
export function deletePhieuThuChi(id) {
  return api.delete(`/phieu-thu-chi/${id}`)
}

/**
 * Xóa nhiều phiếu thu chi.
 * @param {number[]} ids
 */
export function bulkDeletePhieuThuChi(ids) {
  return api.post('/phieu-thu-chi/bulk-delete', { ids })
}

/**
 * Cập nhật trạng thái nhiều phiếu thu chi.
 * @param {number[]} ids
 * @param {string} trang_thai
 * @param {string} ghi_chu
 */
export function bulkUpdateStatusPhieuThuChi(ids, trang_thai, ghi_chu) {
  return api.post('/phieu-thu-chi/bulk-update-status', { ids, trang_thai, ghi_chu })
}
