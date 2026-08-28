<template>
  <div class="auth-wrapper login-page">
    <div class="login-shell">
      <!-- Panel trái: giới thiệu + minh họa -->
      <aside class="login-aside" aria-hidden="true">
        <div class="aside-content">
          <div class="aside-brand">
            <span class="brand-mark">P</span>
            <span class="brand-name">Pandio</span>
          </div>
          <h1 class="aside-title">Quản lý studio chuyên nghiệp</h1>
          <p class="aside-desc">
            Một nền tảng giúp vận hành khách hàng, lịch quay, hợp đồng và nhân sự
            gọn gàng trong cùng một nơi.
          </p>

          <div class="aside-art">
            <svg
              class="aside-svg"
              viewBox="0 0 420 320"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <defs>
                <linearGradient id="loginGradA" x1="40" y1="40" x2="380" y2="280" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#ffffff" stop-opacity="0.95" />
                  <stop offset="1" stop-color="#ffffff" stop-opacity="0.55" />
                </linearGradient>
                <linearGradient id="loginGradB" x1="80" y1="60" x2="340" y2="260" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#b3d8ff" />
                  <stop offset="1" stop-color="#79bbff" />
                </linearGradient>
              </defs>

              <!-- Backdrop panels -->
              <rect x="48" y="52" width="260" height="180" rx="22" fill="url(#loginGradA)" opacity="0.35" />
              <rect x="88" y="78" width="260" height="180" rx="22" fill="url(#loginGradA)" opacity="0.55" />

              <!-- Main card -->
              <rect x="118" y="98" width="240" height="168" rx="20" fill="url(#loginGradA)" />
              <rect x="138" y="120" width="120" height="14" rx="7" fill="url(#loginGradB)" opacity="0.9" />
              <rect x="138" y="148" width="200" height="10" rx="5" fill="#ffffff" opacity="0.45" />
              <rect x="138" y="168" width="168" height="10" rx="5" fill="#ffffff" opacity="0.35" />

              <!-- Mini metric cards -->
              <rect x="138" y="198" width="88" height="48" rx="12" fill="#ffffff" opacity="0.55" />
              <rect x="150" y="210" width="40" height="8" rx="4" fill="url(#loginGradB)" />
              <rect x="150" y="226" width="64" height="8" rx="4" fill="#ffffff" opacity="0.5" />

              <rect x="238" y="198" width="88" height="48" rx="12" fill="#ffffff" opacity="0.55" />
              <rect x="250" y="210" width="40" height="8" rx="4" fill="url(#loginGradB)" />
              <rect x="250" y="226" width="64" height="8" rx="4" fill="#ffffff" opacity="0.5" />

              <!-- Floating calendar chip -->
              <g transform="translate(36 116)">
                <rect width="86" height="96" rx="16" fill="#ffffff" opacity="0.92" />
                <rect x="14" y="16" width="58" height="10" rx="5" fill="url(#loginGradB)" />
                <circle cx="28" cy="48" r="7" fill="#79bbff" opacity="0.85" />
                <circle cx="50" cy="48" r="7" fill="#a0cfff" opacity="0.85" />
                <circle cx="72" cy="48" r="7" fill="#c6e2ff" opacity="0.9" />
                <circle cx="28" cy="72" r="7" fill="#c6e2ff" opacity="0.9" />
                <circle cx="50" cy="72" r="7" fill="#79bbff" opacity="0.85" />
                <circle cx="72" cy="72" r="7" fill="#a0cfff" opacity="0.85" />
              </g>

              <!-- Floating user chip -->
              <g transform="translate(318 56)">
                <rect width="78" height="54" rx="14" fill="#ffffff" opacity="0.92" />
                <circle cx="24" cy="27" r="10" fill="url(#loginGradB)" />
                <rect x="40" y="18" width="26" height="7" rx="3.5" fill="#79bbff" opacity="0.8" />
                <rect x="40" y="30" width="20" height="6" rx="3" fill="#a0cfff" opacity="0.7" />
              </g>

              <!-- Soft rings -->
              <circle cx="360" cy="250" r="42" stroke="#ffffff" stroke-opacity="0.35" stroke-width="2" />
              <circle cx="360" cy="250" r="26" stroke="#ffffff" stroke-opacity="0.25" stroke-width="2" />
            </svg>
          </div>
        </div>
      </aside>

      <!-- Panel phải: form đăng nhập -->
      <section class="login-panel">
        <div class="login-panel-inner">
          <!-- Chỉ hiện trên mobile khi panel hình bị ẩn -->
          <div class="mobile-brand">
            <div class="mobile-brand-logo">
              <span class="brand-mark">P</span>
              <span class="brand-name">Pandio</span>
            </div>
            <p class="mobile-brand-slogan">Quản lý studio chuyên nghiệp</p>
          </div>

          <div class="auth-header">
            <h2>Đăng nhập</h2>
            <!-- <p>Đăng nhập bằng email hoặc số điện thoại</p> -->
          </div>

          <el-form
            ref="formRef"
            class="login-form"
            :model="form"
            :rules="rules"
            label-position="top"
            size="large"
            @submit.prevent="onSubmit"
          >
            <el-form-item label="Email hoặc số điện thoại" prop="login">
              <el-input
                v-model="form.login"
                placeholder="Nhập email hoặc số điện thoại"
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

            <div class="login-options">
              <el-checkbox v-model="form.remember">Ghi nhớ tài khoản</el-checkbox>
            </div>

            <el-form-item class="login-submit">
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
            Bằng việc đăng nhập, bạn đồng ý với
            <a href="#" @click.prevent="openTerms('terms')">Điều khoản sử dụng</a>
            và
            <a href="#" @click.prevent="openTerms('privacy')">Chính sách bảo mật</a>
          </div>
        </div>
      </section>
    </div>

    <TermsPrivacyModal v-model="termsVisible" :initial-tab="termsTab" />
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { User, Lock } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { isMenuPathAllowed } from '@/utils/menuAccess'
import TermsPrivacyModal from '@/components/TermsPrivacyModal.vue'

const REMEMBER_KEY = 'pandio_remember_login'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const formRef = ref()
const submitting = ref(false)
const termsVisible = ref(false)
const termsTab = ref('terms')

const form = reactive({
  login: '',
  password: '',
  remember: false,
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

function loadRememberedLogin() {
  try {
    const raw = localStorage.getItem(REMEMBER_KEY)
    if (!raw) return
    const saved = JSON.parse(raw)
    if (saved?.login) {
      form.login = String(saved.login)
      form.remember = true
    }
  } catch {
    localStorage.removeItem(REMEMBER_KEY)
  }
}

function persistRememberedLogin() {
  if (form.remember) {
    localStorage.setItem(
      REMEMBER_KEY,
      JSON.stringify({ login: form.login.trim() })
    )
  } else {
    localStorage.removeItem(REMEMBER_KEY)
  }
}

function openTerms(tab) {
  termsTab.value = tab
  termsVisible.value = true
}

onMounted(loadRememberedLogin)

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

    persistRememberedLogin()

    const requested = String(route.query.redirect || '')
    const redirect =
      requested && isMenuPathAllowed(requested, authStore.allowedMenuPaths)
        ? requested
        : authStore.defaultHomePath
    router.push(redirect)
  } catch {
    // Lỗi đã được interceptor / store xử lý hiển thị
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.login-page {
  padding: 0;
  background: var(--el-bg-color-page);
}

.login-shell {
  display: grid;
  grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
  width: min(1080px, calc(100vw - 48px));
  min-height: min(640px, calc(100vh - 48px));
  margin: 24px auto;
  border-radius: 20px;
  overflow: hidden;
  background: var(--el-bg-color);
  box-shadow:
    0 18px 48px rgba(15, 35, 70, 0.1),
    0 2px 8px rgba(15, 35, 70, 0.04);
}

.login-aside {
  position: relative;
  display: flex;
  align-items: stretch;
  padding: 40px 36px;
  color: #fff;
  background:
    radial-gradient(circle at 18% 18%, rgba(255, 255, 255, 0.22), transparent 42%),
    radial-gradient(circle at 88% 78%, rgba(64, 158, 255, 0.35), transparent 40%),
    linear-gradient(145deg, #1d6fd8 0%, #409eff 48%, #79bbff 100%);
}

.aside-content {
  display: flex;
  flex-direction: column;
  width: 100%;
  z-index: 1;
}

.aside-brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 28px;
}

.brand-mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.2);
  font-weight: 700;
  font-size: 18px;
  backdrop-filter: blur(6px);
}

.brand-name {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.aside-title {
  margin: 0 0 12px;
  font-size: clamp(26px, 2.6vw, 34px);
  line-height: 1.25;
  font-weight: 700;
  max-width: 16ch;
}

.aside-desc {
  margin: 0;
  max-width: 34ch;
  font-size: 15px;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.88);
}

.aside-art {
  margin-top: auto;
  padding-top: 28px;
}

.aside-svg {
  display: block;
  width: 100%;
  max-width: 420px;
  height: auto;
}

.login-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 36px;
  background: var(--el-bg-color);
}

.login-panel-inner {
  width: 100%;
  max-width: 400px;
}

.mobile-brand {
  display: none;
}

.auth-header h2 {
  margin: 0 0 6px;
  font-size: 28px;
  font-weight: 700;
  color: var(--el-text-color-primary);
}

.auth-header p {
  margin: 0 0 28px;
  color: var(--el-text-color-secondary);
  font-size: 14px;
}

.login-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: -4px 0 18px;
}

.login-submit {
  margin-bottom: 8px;
}

.auth-footer {
  margin-top: 20px;
  text-align: center;
  font-size: 13px;
  line-height: 1.55;
  color: var(--el-text-color-secondary);
}

.auth-footer a {
  font-weight: 500;
}

html.dark .login-shell {
  box-shadow:
    0 18px 48px rgba(0, 0, 0, 0.35),
    0 2px 8px rgba(0, 0, 0, 0.2);
}

html.dark .login-aside {
  background:
    radial-gradient(circle at 18% 18%, rgba(255, 255, 255, 0.12), transparent 42%),
    radial-gradient(circle at 88% 78%, rgba(64, 158, 255, 0.28), transparent 40%),
    linear-gradient(145deg, #0f3f7a 0%, #1a5fb4 48%, #337ecc 100%);
}

@media (max-width: 900px) {
  .login-page {
    padding: 16px;
    background: linear-gradient(135deg, #ecf5ff 0%, #f5f7fa 50%, #e8f4ff 100%);
  }

  html.dark .login-page {
    background: linear-gradient(135deg, #1a1a1a 0%, #141414 50%, #1d1e1f 100%);
  }

  .login-shell {
    grid-template-columns: 1fr;
    width: 100%;
    min-height: auto;
    margin: 0;
    border-radius: 16px;
  }

  /* Mobile / tablet: chỉ giữ form, ẩn panel hình */
  .login-aside {
    display: none;
  }

  .mobile-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    margin-bottom: 28px;
  }

  .mobile-brand-logo {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
  }

  .mobile-brand .brand-mark {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(145deg, #1d6fd8 0%, #409eff 100%);
    color: #fff;
    font-size: 20px;
    backdrop-filter: none;
  }

  .mobile-brand .brand-name {
    font-size: 26px;
    font-weight: 700;
    letter-spacing: 0.02em;
    color: var(--el-text-color-primary);
  }

  .mobile-brand-slogan {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    color: var(--el-text-color-secondary);
  }

  .login-panel {
    padding: 28px 24px;
  }

  .auth-header h2 {
    font-size: 24px;
  }

  .auth-header p {
    margin-bottom: 20px;
  }
}
</style>
