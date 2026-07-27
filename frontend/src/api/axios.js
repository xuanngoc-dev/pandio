import axios from 'axios'
import { ElLoading, ElMessage } from 'element-plus'
import router from '@/router'

/**
 * Axios instance dùng chung cho toàn bộ API.
 * - baseURL lấy từ .env
 * - Tự gắn Bearer token từ localStorage (sau khi đăng nhập)
 * - Fullscreen loading đến khi có phản hồi (bỏ qua nếu config.skipLoading = true)
 * - Xử lý 401: xoá session + thông báo + chuyển về Login
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  timeout: 15000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

let loadingInstance = null
let pendingCount = 0

function startLoading() {
  if (pendingCount === 0) {
    loadingInstance = ElLoading.service({
      fullscreen: true,
      lock: true,
      text: 'Đang tải...',
      background: 'rgba(0, 0, 0, 0.45)',
    })
  }
  pendingCount += 1
}

function stopLoading() {
  if (pendingCount > 0) {
    pendingCount -= 1
  }
  if (pendingCount === 0 && loadingInstance) {
    loadingInstance.close()
    loadingInstance = null
  }
}

// Request interceptor: gắn token + bật loading
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')

    if (token) {
      config.headers = config.headers || {}
      config.headers.Authorization = `Bearer ${token}`
    }

    // FormData: bỏ Content-Type để browser tự set multipart boundary
    if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
      if (config.headers) {
        delete config.headers['Content-Type']
      }
    }

    if (!config.skipLoading) {
      startLoading()
      config._showLoading = true
    }

    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor: tắt loading + xử lý lỗi chung + 401
api.interceptors.response.use(
  (response) => {
    if (response.config?._showLoading) {
      stopLoading()
    }
    return response
  },
  async (error) => {
    if (error.config?._showLoading) {
      stopLoading()
    }

    const status = error.response?.status
    const originalRequest = error.config
    const message = error.response?.data?.message

    // Token thiếu / hết hạn / không hợp lệ — backend chặn bằng auth:sanctum
    if (status === 401 && originalRequest && !originalRequest._retry) {
      originalRequest._retry = true

      // Dynamic import tránh circular dependency với Pinia store
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.clearAuth()

      // silent401: bootstrap / refresh user — để router guard quyết định điều hướng
      if (!originalRequest.silent401) {
        ElMessage.error(
          message || 'Token không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.'
        )

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
        ElMessage.error(message || 'Dữ liệu không hợp lệ.')
      }
      return Promise.reject(error)
    }

    // Lỗi server / mạng
    if (!error.response) {
      ElMessage.error('Không thể kết nối tới máy chủ.')
    } else if (status === 403) {
      ElMessage.error(message || 'Bạn không có quyền thực hiện thao tác này.')
    } else if (status === 404) {
      ElMessage.error(message || 'Không tìm thấy dữ liệu.')
    } else if (status === 400 || status === 405 || status === 409 || status === 429) {
      ElMessage.error(message || 'Yêu cầu không hợp lệ.')
    } else if (status >= 500) {
      ElMessage.error(message || 'Lỗi máy chủ. Vui lòng thử lại sau.')
    } else if (status && status !== 401 && message) {
      ElMessage.error(message)
    }

    return Promise.reject(error)
  }
)

export default api
