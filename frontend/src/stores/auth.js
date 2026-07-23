import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/axios'
import { ElMessage, ElNotification } from 'element-plus'

/**
 * Pinia Auth Store — quản lý token + thông tin user.
 */
export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  /** Lưu token vào state + localStorage */
  function setToken(newToken) {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('token', newToken)
    } else {
      localStorage.removeItem('token')
    }
  }

  /** Xoá toàn bộ trạng thái đăng nhập */
  function clearAuth() {
    user.value = null
    setToken(null)
  }

  /** Đăng ký */
  async function register(payload) {
    loading.value = true
    try {
      const { data } = await api.post('/auth/register', payload)
      setToken(data.token)
      user.value = data.user
      ElNotification.success({
        title: 'Thành công',
        message: data.message || 'Đăng ký thành công.',
      })
      return data
    } finally {
      loading.value = false
    }
  }

  /** Đăng nhập */
  async function login(payload) {
    loading.value = true
    try {
      const { data } = await api.post('/auth/login', payload)
      setToken(data.token)
      user.value = data.user
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
      user.value = null
      return false
    }

    loading.value = true
    try {
      // silent401: không hiện message khi token cũ hết hạn lúc F5
      const { data } = await api.get('/user', { silent401: true })
      user.value = data.user
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
    clearAuth,
    register,
    login,
    logout,
    fetchUser,
  }
})
