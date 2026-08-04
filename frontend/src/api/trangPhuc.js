import api from '@/api/axios'

/**
 * Danh sách trang phục — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, danh_muc?: number, nha_cung_cap?: number, chi_nhanh?: number, trang_thai?: number, gia_tu?: number, gia_den?: number, ngay_thue?: string, ngay_tra_du_kien?: string, exclude_hop_dong_id?: number }} params
 */
export function fetchTrangPhuc(params = {}) {
  return api.get('/trang-phuc', { params })
}

/**
 * Chi tiết trang phục.
 * @param {number|string} id
 */
export function getTrangPhuc(id) {
  return api.get(`/trang-phuc/${id}`)
}

/**
 * Lịch sử cho thuê của trang phục.
 * @param {number|string} id
 */
export function fetchTrangPhucLichChoThue(id) {
  return api.get(`/trang-phuc/${id}/lich-cho-thue`)
}

/**
 * Upload hình ảnh trang phục.
 * @param {File} file
 */
export function uploadTrangPhucHinhAnh(file) {
  const formData = new FormData()
  formData.append('hinh_anh', file)
  return api.post('/trang-phuc/upload-hinh-anh', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

/**
 * Tạo trang phục.
 * @param {object} payload
 */
export function createTrangPhuc(payload) {
  return api.post('/trang-phuc', payload)
}

/**
 * Cập nhật trang phục.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateTrangPhuc(id, payload) {
  if (id == null || id === '') {
    return Promise.reject(new Error('Thiếu id trang phục.'))
  }
  return api.put(`/trang-phuc/${id}`, payload)
}

/**
 * Xóa trang phục.
 * @param {number|string} id
 */
export function deleteTrangPhuc(id) {
  if (id == null || id === '') {
    return Promise.reject(new Error('Thiếu id trang phục.'))
  }
  return api.delete(`/trang-phuc/${id}`)
}
