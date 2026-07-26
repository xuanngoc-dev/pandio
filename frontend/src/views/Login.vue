<template>
  <div class="auth-wrapper">
    <el-card class="page-card" shadow="always">
      <template #header>
        <div class="auth-header">
          <h2>Đăng nhập</h2>
          <p>Đăng nhập bằng email hoặc số điện thoại</p>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-position="top"
        @submit.prevent="onSubmit"
      >
        <el-form-item label="Email hoặc số điện thoại" prop="login">
          <el-input
            v-model="form.login"
            placeholder="you@example.com hoặc 0912345678"
            clearable
            :prefix-icon="User"
          />
        </el-form-item>

        <el-form-item label="Mật khẩu" prop="password">
          <el-input
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            show-password
            :prefix-icon="Lock"
          />
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            native-type="submit"
            :loading="authStore.loading || submitting"
            :disabled="authStore.loading || submitting"
            style="width: 100%"
          >
            Đăng nhập
          </el-button>
        </el-form-item>
      </el-form>

      <div class="auth-footer">
        Chưa có tài khoản?
        <router-link :to="{ name: 'register' }">Đăng ký ngay</router-link>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { User, Lock } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const formRef = ref()
const submitting = ref(false)
const form = reactive({
  login: '',
  password: '',
})

const PHONE_RE = /^(0|\+84)(3|5|7|8|9)[0-9]{8}$/
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const validateLogin = (_rule, value, callback) => {
  const v = String(value || '').trim()
  if (!v) {
    callback(new Error('Vui lòng nhập email hoặc số điện thoại'))
    return
  }
  if (EMAIL_RE.test(v) || PHONE_RE.test(v)) {
    callback()
    return
  }
  callback(new Error('Email hoặc số điện thoại không hợp lệ'))
}

const rules = {
  login: [{ required: true, validator: validateLogin, trigger: 'blur' }],
  password: [
    { required: true, message: 'Vui lòng nhập mật khẩu', trigger: 'blur' },
    { min: 6, message: 'Tối thiểu 6 ký tự', trigger: 'blur' },
  ],
}

async function onSubmit() {
  // Khóa đồng bộ ngay từ đầu — tránh Enter/submit trùng hoặc double-click
  if (submitting.value || authStore.loading) return
  submitting.value = true

  try {
    const valid = await formRef.value?.validate().catch(() => false)
    if (!valid) return

    const data = await authStore.login({
      login: form.login.trim(),
      password: form.password,
    })
    if (!data) return

    const redirect = route.query.redirect || '/tong-quan'
    router.push(String(redirect))
  } catch {
    // Lỗi đã được interceptor / store xử lý hiển thị
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.auth-header h2 {
  margin: 0 0 4px;
}
.auth-header p {
  margin: 0;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}
.auth-footer {
  text-align: center;
  font-size: 14px;
  color: var(--el-text-color-secondary);
}
</style>
