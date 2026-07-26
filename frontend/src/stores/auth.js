import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/axios'
import { ElMessage, ElNotification } from 'element-plus'

const TOKEN_KEY = 'token'
const USER_KEY = 'user'

function readStoredUser() {
  try {
    const raw = localStorage.getItem(USER_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

/**
 * Pinia Auth Store — quản lý token + thông tin user.
 * Token lấy từ login/register, lưu localStorage và gắn Bearer khi gọi API.
 */
export const useAuthStore = defineStore('auth', () => {
  const user = ref(readStoredUser())
  const token = ref(localStorage.getItem(TOKEN_KEY) || null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  /** Lưu token vào state + localStorage */
  function setToken(newToken) {
    token.value = newToken
    if (newToken) {
      localStorage.setItem(TOKEN_KEY, newToken)
    } else {
      localStorage.removeItem(TOKEN_KEY)
    }
  }

  /** Lưu thông tin user vào state + localStorage */
  function setUser(newUser) {
    user.value = newUser
    if (newUser) {
      localStorage.setItem(USER_KEY, JSON.stringify(newUser))
    } else {
      localStorage.removeItem(USER_KEY)
    }
  }

  /** Lưu phiên đăng nhập (token + user) */
  function setAuth(newToken, newUser) {
    setToken(newToken)
    setUser(newUser)
  }

  /** Xoá toàn bộ trạng thái đăng nhập */
  function clearAuth() {
    setUser(null)
    setToken(null)
  }

  /** Đăng ký */
  async function register(payload) {
    if (loading.value) return null
    loading.value = true
    try {
      const { data } = await api.post('/auth/register', payload)
      setAuth(data.token, data.user)
      ElNotification.success({
        title: 'Thành công',
        message: data.message || 'Đăng ký thành công.',
      })
      return data
    } finally {
      loading.value = false
    }
  }

  /** Đăng nhập — lưu token + thông tin user để các API sau dùng Bearer */
  async function login(payload) {
    if (loading.value) return null
    loading.value = true
    try {
      const { data } = await api.post('/auth/login', payload)
      setAuth(data.token, data.user)
      ElNotification.success({
        title: 'Thành công',
        message: data.message || 'Đăng nhập thành công.',
      })
      return data
    } finally {
      loading.value = false
    }
  }

  /** Đăng xuất */
  async function logout() {
    loading.value = true
    try {
      await api.post('/auth/logout')
      ElMessage.success('Đã đăng xuất.')
    } catch {
      // Vẫn clear local dù API lỗi
    } finally {
      clearAuth()
      loading.value = false
    }
  }

  /**
   * Lấy thông tin user hiện tại (dùng khi F5 / mở lại app).
   * Trả về true nếu token còn hợp lệ.
   */
  async function fetchUser() {
    if (!token.value) {
      clearAuth()
      return false
    }

    loading.value = true
    try {
      // silent401: không hiện message khi token cũ hết hạn lúc F5
      const { data } = await api.get('/user', { silent401: true })
      setUser(data.user)
      return true
    } catch {
      clearAuth()
      return false
    } finally {
      loading.value = false
    }
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    setToken,
    setUser,
    setAuth,
    clearAuth,
    register,
    login,
    logout,
    fetchUser,
  }
})
