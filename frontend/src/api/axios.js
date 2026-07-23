import axios from 'axios'
import { ElMessage } from 'element-plus'
import router from '@/router'

/**
 * Axios instance dùng chung cho toàn bộ API.
 * - baseURL lấy từ .env
 * - Tự gắn Bearer token
 * - Xử lý 401: xoá session + chuyển về Login
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  timeout: 15000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

// Request interceptor: gắn token vào mỗi request
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor: xử lý lỗi chung + 401
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status
    const originalRequest = error.config

    // Token hết hạn / không hợp lệ
    if (status === 401 && originalRequest && !originalRequest._retry) {
      originalRequest._retry = true

      // Dynamic import tránh circular dependency với Pinia store
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.clearAuth()

      // silent401: bootstrap / refresh user — để router guard quyết định điều hướng
      if (!originalRequest.silent401) {
        ElMessage.error('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.')

        const current = router.currentRoute.value
        if (current.name !== 'login' && current.name !== 'register') {
          router.push({ name: 'login', query: { redirect: current.fullPath } })
        }
      }

      return Promise.reject(error)
    }

    // Lỗi validate Laravel (422)
    if (status === 422) {
      const errors = error.response?.data?.errors
      if (errors) {
        const first = Object.values(errors)[0]
        ElMessage.error(Array.isArray(first) ? first[0] : String(first))
      } else {
        ElMessage.error(error.response?.data?.message || 'Dữ liệu không hợp lệ.')
      }
      return Promise.reject(error)
    }

    // Lỗi server / mạng
    if (!error.response) {
      ElMessage.error('Không thể kết nối tới máy chủ.')
    } else if (status >= 500) {
      ElMessage.error('Lỗi máy chủ. Vui lòng thử lại sau.')
    }

    return Promise.reject(error)
  }
)

export default api
