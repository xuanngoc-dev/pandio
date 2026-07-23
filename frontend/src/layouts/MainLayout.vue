<template>
  <el-container class="main-layout">
    <!-- Sidebar -->
    <el-aside :width="collapsed ? '64px' : '220px'" class="aside">
      <div class="brand">
        <el-icon :size="22"><Monitor /></el-icon>
        <span v-show="!collapsed" class="brand-text">Pandio</span>
      </div>

      <SideMenu :collapsed="collapsed" />
    </el-aside>

    <el-container>
      <!-- Header -->
      <el-header class="header">
        <div class="header-left">
          <el-button text @click="collapsed = !collapsed">
            <el-icon :size="20">
              <Fold v-if="!collapsed" />
              <Expand v-else />
            </el-icon>
          </el-button>
          <span class="page-title">{{ pageTitle }}</span>
        </div>

        <div class="header-right">
          <el-switch
            v-model="isDark"
            inline-prompt
            active-text="🌙"
            inactive-text="☀️"
            @change="toggleDark"
          />

          <template v-if="authStore.isAuthenticated">
            <el-dropdown trigger="click" @command="onCommand">
              <span class="user-trigger">
                <el-avatar :size="32">{{ avatarLetter }}</el-avatar>
                <span class="user-name">{{ authStore.user?.name }}</span>
                <el-icon><ArrowDown /></el-icon>
              </span>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item disabled>
                    {{ authStore.user?.email }}
                  </el-dropdown-item>
                  <el-dropdown-item divided command="logout">
                    Đăng xuất
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
          <template v-else>
            <el-button type="primary" @click="$router.push({ name: 'login' })">
              Đăng nhập
            </el-button>
            <el-button @click="$router.push({ name: 'register' })">Đăng ký</el-button>
          </template>
        </div>
      </el-header>

      <!-- Nội dung trang -->
      <el-main class="main">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Monitor, Fold, Expand, ArrowDown } from '@element-plus/icons-vue'
import SideMenu from '@/components/SideMenu.vue'

/** Dưới breakpoint này sidebar tự chuyển sang collapsed */
const COLLAPSE_BREAKPOINT = 992

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const collapsed = ref(false)
const isDark = ref(document.documentElement.classList.contains('dark'))

const pageTitle = computed(() => route.meta.title || 'Pandio')
const avatarLetter = computed(() =>
  (authStore.user?.name || 'U').charAt(0).toUpperCase()
)

let mediaQuery = null

function syncCollapseByViewport(e) {
  collapsed.value = e.matches
}

function toggleDark(val) {
  document.documentElement.classList.toggle('dark', val)
  localStorage.setItem('darkMode', val ? '1' : '0')
}

async function onCommand(cmd) {
  if (cmd === 'logout') {
    await authStore.logout()
    router.push({ name: 'login' })
  }
}

onMounted(() => {
  const saved = localStorage.getItem('darkMode')
  if (saved === '1') {
    isDark.value = true
    document.documentElement.classList.add('dark')
  }

  mediaQuery = window.matchMedia(`(max-width: ${COLLAPSE_BREAKPOINT - 1}px)`)
  collapsed.value = mediaQuery.matches
  mediaQuery.addEventListener('change', syncCollapseByViewport)
})

onUnmounted(() => {
  mediaQuery?.removeEventListener('change', syncCollapseByViewport)
})
</script>

<style scoped lang="scss">
.main-layout {
  min-height: 100vh;
}

.aside {
  border-right: 1px solid var(--el-border-color);
  background: var(--el-bg-color);
  transition: width 0.2s ease;
}

.brand {
  height: 60px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 18px;
  font-weight: 700;
  font-size: 18px;
  color: var(--el-color-primary);
  border-bottom: 1px solid var(--el-border-color);
}

.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--el-border-color);
  background: var(--el-bg-color);
}

.header-left,
.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-title {
  font-weight: 600;
}

.user-trigger {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.user-name {
  font-size: 14px;
}

.main {
  background: var(--el-bg-color-page);
}
</style>
