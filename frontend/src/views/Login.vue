<template>
  <div class="auth-wrapper">
    <el-card class="page-card" shadow="always">
      <template #header>
        <div class="auth-header">
          <h2>Đăng nhập</h2>
          <p>Sử dụng tài khoản đã đăng ký</p>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-position="top"
        @submit.prevent="onSubmit"
      >
        <el-form-item label="Email" prop="email">
          <el-input
            v-model="form.email"
            type="email"
            placeholder="you@example.com"
            clearable
            :prefix-icon="Message"
          />
        </el-form-item>

        <el-form-item label="Mật khẩu" prop="password">
          <el-input
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            show-password
            :prefix-icon="Lock"
            @keyup.enter="onSubmit"
          />
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            native-type="submit"
            :loading="authStore.loading"
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
import { Message, Lock } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const formRef = ref()
const form = reactive({
  email: '',
  password: '',
})

const rules = {
  email: [
    { required: true, message: 'Vui lòng nhập email', trigger: 'blur' },
    { type: 'email', message: 'Email không hợp lệ', trigger: 'blur' },
  ],
  password: [
    { required: true, message: 'Vui lòng nhập mật khẩu', trigger: 'blur' },
    { min: 6, message: 'Tối thiểu 6 ký tự', trigger: 'blur' },
  ],
}

async function onSubmit() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  try {
    await authStore.login({
      email: form.email,
      password: form.password,
    })
    const redirect = route.query.redirect || '/dashboard'
    router.push(String(redirect))
  } catch {
    // Lỗi đã được interceptor / store xử lý hiển thị
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
