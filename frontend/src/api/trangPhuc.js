import api from '@/api/axios'

/**
 * Danh sách trang phục — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, danh_muc?: number, nha_cung_cap?: number, trang_thai?: number, gia_tu?: number, gia_den?: number, ngay_thue?: string, ngay_tra_du_kien?: string, exclude_hop_dong_id?: number }} params
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
 * Danh sách hình ảnh trong thư mục trang-phuc (public + storage).
 * @param {{ page?: number, per_page?: number, keyword?: string }} params
 */
export function fetchHinhAnhTrangPhuc(params = {}) {
  return api.get('/trang-phuc/hinh-anh', { params })
}

/**
 * Đổi tên file hình ảnh trang phục.
 * @param {{ path: string, name: string }} payload
 */
export function updateHinhAnhTrangPhuc(payload) {
  return api.put('/trang-phuc/hinh-anh', payload)
}

const HINH_ANH_UPLOAD_TIMEOUT = 120000

/**
 * Tải 1 chunk ảnh hoặc zip.
 * @param {FormData} formData
 */
export function uploadHinhAnhTrangPhucChunk(formData) {
  return api.post('/trang-phuc/hinh-anh/chunk', formData, {
    skipLoading: true,
    timeout: HINH_ANH_UPLOAD_TIMEOUT,
  })
}

/**
 * Ghép chunk thành file (ghi đè nếu trùng tên).
 * @param {{ upload_id: string }} payload
 */
export function completeHinhAnhTrangPhucUpload(payload) {
  return api.post('/trang-phuc/hinh-anh/complete', payload, {
    skipLoading: true,
    timeout: HINH_ANH_UPLOAD_TIMEOUT,
  })
}

/**
 * Giải nén ảnh từ zip trong thư mục trang phục.
 * @param {{ path: string, cursor?: number, limit?: number }} payload
 */
export function giaiNenHinhAnhTrangPhuc(payload) {
  return api.post('/trang-phuc/hinh-anh/giai-nen', payload, {
    skipLoading: true,
    timeout: HINH_ANH_UPLOAD_TIMEOUT,
  })
}

/**
 * Xóa danh sách file ảnh / zip.
 * @param {{ paths: string[] }} payload
 */
export function deleteHinhAnhTrangPhuc(payload) {
  return api.delete('/trang-phuc/hinh-anh', { data: payload })
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
