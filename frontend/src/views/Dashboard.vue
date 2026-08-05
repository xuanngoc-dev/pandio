<template>
  <div class="dashboard" v-loading="authStore.loading">
    <el-row :gutter="20">
      <el-col :xs="24" :lg="14">
        <el-card shadow="hover">
          <template #header>
            <div class="card-header">
              <span>Thông tin tài khoản</span>
              <el-button type="primary" link @click="openDialog = true">
                Xem chi tiết
              </el-button>
            </div>
          </template>

          <el-descriptions :column="1" border>
            <el-descriptions-item label="ID">
              {{ authStore.user?.id }}
            </el-descriptions-item>
            <el-descriptions-item label="Họ tên">
              {{ authStore.user?.name }}
            </el-descriptions-item>
            <el-descriptions-item label="Email">
              {{ authStore.user?.email }}
            </el-descriptions-item>
            <el-descriptions-item label="Tạo lúc">
              {{ formatDate(authStore.user?.created_at) }}
            </el-descriptions-item>
          </el-descriptions>
        </el-card>
      </el-col>

      <el-col :xs="24" :lg="10">
        <el-card shadow="hover">
          <template #header>API nhanh</template>
          <el-space wrap>
            <el-button type="success" :loading="refreshing" @click="refreshUser">
              Refresh /api/user
            </el-button>
            <el-button type="danger" @click="handleLogout">Đăng xuất</el-button>
          </el-space>
          <el-alert
            style="margin-top: 16px"
            title="Route này yêu cầu đăng nhập (auth:sanctum)."
            type="info"
            show-icon
            :closable="false"
          />
        </el-card>
      </el-col>
    </el-row>

    <!-- Ví dụ ElTable -->
    <el-card shadow="hover" style="margin-top: 20px">
      <template #header>Danh sách phiên (demo table)</template>
      <el-table :data="sessions" stripe border style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="device" label="Thiết bị" />
        <el-table-column prop="ip" label="IP" width="140" />
        <el-table-column prop="status" label="Trạng thái" width="120">
          <template #default="{ row }">
            <el-tag :type="row.status === 'Active' ? 'success' : 'info'" size="small">
              {{ row.status }}
            </el-tag>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- Ví dụ ElDialog -->
    <el-dialog v-model="openDialog" title="Chi tiết user" width="480px">
      <pre class="json-box">{{ JSON.stringify(authStore.user, null, 2) }}</pre>
      <template #footer>
        <el-button @click="openDialog = false">Đóng</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

const openDialog = ref(false)
const refreshing = ref(false)

const sessions = ref([
  { id: 1, device: 'Chrome / macOS', ip: '127.0.0.1', status: 'Active' },
  { id: 2, device: 'SPA Token', ip: '127.0.0.1', status: 'Active' },
])

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('vi-VN')
}

async function refreshUser() {
  refreshing.value = true
  try {
    const ok = await authStore.fetchUser()
    if (ok) {
      ElMessage.success('Đã cập nhật thông tin user.')
    }
  } finally {
    refreshing.value = false
  }
}

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
.json-box {
  background: var(--el-fill-color-light);
  padding: 12px;
  border-radius: 8px;
  overflow: auto;
  font-size: 12px;
}
.dashboard :deep(.el-col) {
  margin-bottom: 16px;
}
</style>
