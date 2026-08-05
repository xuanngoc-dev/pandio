import api from '@/api/axios'

/**
 * Danh sách user (nhân sự) — phân trang.
 * @param {{ page?: number, per_page?: number, keyword?: string, status?: string }} params
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchUsers(params = {}, config = {}) {
  return api.get('/users', { params, ...config })
}

/**
 * Chi tiết nhân sự.
 * @param {number|string} id
 */
export function getUser(id) {
  return api.get(`/users/${id}`)
}

/**
 * Upload hình ảnh nhân viên → lưu storage/app/public/nhan-vien
 * @param {File} file
 */
export function uploadNhanVienHinh(file) {
  const formData = new FormData()
  formData.append('hinh_anh', file)
  return api.post('/users/upload-hinh-anh', formData, {
    // Xóa application/json mặc định để browser tự gắn multipart boundary
    headers: { 'Content-Type': undefined },
  })
}

/**
 * Tạo nhân sự (users + nhan_vien).
 * @param {object} payload
 */
export function createUser(payload) {
  return api.post('/users', payload)
}

/**
 * Cập nhật nhân sự.
 * @param {number|string} id
 * @param {object} payload
 */
export function updateUser(id, payload) {
  return api.put(`/users/${id}`, payload)
}

/**
 * Xóa nhân sự.
 * @param {number|string} id
 */
export function deleteUser(id) {
  return api.delete(`/users/${id}`)
}
