import api from '@/api/axios'

/**
 * Danh sách thông tin studio — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, mac_dinh?: string }} params
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchThongTinStudio(params = {}, config = {}) {
  return api.get('/cau-hinh-thong-tin-studio', { params, ...config })
}

/**
 * Chi tiết thông tin studio.
 * @param {number|string} id
 */
export function getThongTinStudio(id) {
  return api.get(`/cau-hinh-thong-tin-studio/${id}`)
}

/**
 * Upload logo studio → storage/app/public/studio-logo
 * @param {File} file
 */
export function uploadStudioLogo(file) {
  const formData = new FormData()
  formData.append('logo', file)
  return api.post('/cau-hinh-thong-tin-studio/upload-logo', formData, {
    headers: { 'Content-Type': undefined },
  })
}

/**
 * Tạo thông tin studio.
 * @param {object} payload
 */
export function createThongTinStudio(payload) {
  return api.post('/cau-hinh-thong-tin-studio', payload)
}

/**
 * Cập nhật thông tin studio.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateThongTinStudio(id, payload) {
  return api.put(`/cau-hinh-thong-tin-studio/${id}`, payload)
}

/**
 * Xóa thông tin studio.
 * @param {number|string} id
 */
export function deleteThongTinStudio(id) {
  return api.delete(`/cau-hinh-thong-tin-studio/${id}`)
}
