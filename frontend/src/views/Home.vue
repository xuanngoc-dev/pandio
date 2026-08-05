<template>
  <div class="home">
    <el-row :gutter="20">
      <el-col :xs="24" :md="16">
        <el-card shadow="hover">
          <template #header>
            <div class="card-header">
              <span>Chào mừng đến Pandio</span>
              <el-tag type="success">SPA Laravel + Vue 3</el-tag>
            </div>
          </template>

          <p>
            Hệ thống demo: Backend Laravel (API thuần + Sanctum), Frontend Vue 3
            (Vite, Pinia, Element Plus, Axios).
          </p>

          <el-space wrap>
            <el-button type="primary" @click="$router.push({ name: 'dashboard' })">
              Vào Dashboard
            </el-button>
            <el-button v-if="!authStore.isAuthenticated" @click="$router.push({ name: 'login' })">
              Đăng nhập
            </el-button>
            <el-button v-if="!authStore.isAuthenticated" @click="$router.push({ name: 'register' })">
              Đăng ký
            </el-button>
          </el-space>
        </el-card>
      </el-col>

      <el-col :xs="24" :md="8">
        <el-card shadow="hover">
          <template #header>Trạng thái đăng nhập</template>
          <el-descriptions :column="1" border size="small">
            <el-descriptions-item label="Đã login">
              <el-tag :type="authStore.isAuthenticated ? 'success' : 'info'">
                {{ authStore.isAuthenticated ? 'Có' : 'Chưa' }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item v-if="authStore.user" label="User">
              {{ authStore.user.name }}
            </el-descriptions-item>
          </el-descriptions>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
</script>

<style scoped>
.home :deep(.el-col) {
  margin-bottom: 16px;
}
</style>
