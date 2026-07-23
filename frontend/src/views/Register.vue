<template>
  <div class="auth-wrapper">
    <el-card class="page-card" shadow="always">
      <template #header>
        <div class="auth-header">
          <h2>Đăng ký</h2>
          <p>Tạo tài khoản mới để sử dụng hệ thống</p>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-position="top"
        @submit.prevent="onSubmit"
      >
        <el-form-item label="Họ tên" prop="name">
          <el-input
            v-model="form.name"
            placeholder="Nguyễn Văn A"
            clearable
            :prefix-icon="User"
          />
        </el-form-item>

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
          />
        </el-form-item>

        <el-form-item label="Xác nhận mật khẩu" prop="password_confirmation">
          <el-input
            v-model="form.password_confirmation"
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
            Đăng ký
          </el-button>
        </el-form-item>
      </el-form>

      <div class="auth-footer">
        Đã có tài khoản?
        <router-link :to="{ name: 'login' }">Đăng nhập</router-link>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { User, Message, Lock } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

const formRef = ref()
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const validateConfirm = (_rule, value, callback) => {
  if (value !== form.password) {
    callback(new Error('Mật khẩu xác nhận không khớp'))
  } else {
    callback()
  }
}

const rules = {
  name: [{ required: true, message: 'Vui lòng nhập họ tên', trigger: 'blur' }],
  email: [
    { required: true, message: 'Vui lòng nhập email', trigger: 'blur' },
    { type: 'email', message: 'Email không hợp lệ', trigger: 'blur' },
  ],
  password: [
    { required: true, message: 'Vui lòng nhập mật khẩu', trigger: 'blur' },
    { min: 8, message: 'Tối thiểu 8 ký tự', trigger: 'blur' },
  ],
  password_confirmation: [
    { required: true, message: 'Vui lòng xác nhận mật khẩu', trigger: 'blur' },
    { validator: validateConfirm, trigger: 'blur' },
  ],
}

async function onSubmit() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  try {
    await authStore.register({ ...form })
    router.push({ name: 'dashboard' })
  } catch {
    // Lỗi đã được interceptor xử lý
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
