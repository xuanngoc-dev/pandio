<template>
  <el-drawer
    v-model="visible"
    direction="rtl"
    size="400px"
    class="notification-drawer"
  >
    <template #header>
      <div class="drawer-header">
        <div class="drawer-header__title">
          <span>Thông báo</span>
          <el-tag v-if="unreadCount" size="small" type="danger" round>
            {{ unreadCount }} mới
          </el-tag>
        </div>
        <el-button
          v-if="unreadCount"
          link
          type="primary"
          :loading="markingAll"
          @click="markAllRead"
        >
          Đánh dấu đã đọc
        </el-button>
      </div>
    </template>

    <div v-loading="loading" class="notification-list">
      <div
        v-for="item in notifications"
        :key="item.id"
        class="notification-item"
        :class="{
          'is-unread': !item.read,
          'is-checked': checkedIds.includes(item.id),
          'is-selecting': selecting,
        }"
        @click="onItemClick(item)"
      >
        <div v-if="selecting" class="notification-item__check" @click.stop>
          <el-checkbox
            :model-value="checkedIds.includes(item.id)"
            @change="(val) => toggleChecked(item.id, val)"
          />
        </div>

        <div class="notification-item__icon" :class="`is-${item.type}`">
          <el-icon :size="18">
            <component :is="item.icon || Bell" />
          </el-icon>
        </div>

        <div class="notification-item__body">
          <div class="notification-item__top">
            <span class="notification-item__title">{{ item.title }}</span>
            <span v-if="!item.read" class="notification-item__dot" />
          </div>
          <p class="notification-item__message">{{ item.message }}</p>
          <div class="notification-item__bottom">
            <span class="notification-item__time">{{ item.time }}</span>
            <el-button
              v-if="selecting"
              type="danger"
              link
              size="small"
              :icon="Delete"
              :loading="removingId === item.id"
              @click.stop="removeOne(item)"
            >
              Xoá
            </el-button>
          </div>
        </div>
      </div>

      <el-empty
        v-if="!loading && !notifications.length"
        description="Không có thông báo"
        :image-size="80"
      />
    </div>

    <template #footer>
      <div class="drawer-footer">
        <div class="drawer-footer__left">
          <el-tooltip
            v-if="selecting"
            :content="bulkDeleteTooltip"
            placement="top"
          >
            <el-badge :value="checkedCount" :hidden="!checkedCount" type="danger">
              <el-button
                type="danger"
                plain
                :icon="Delete"
                :loading="removing"
                :disabled="!checkedCount"
                @click="removeChecked"
              >
                Xoá
              </el-button>
            </el-badge>
          </el-tooltip>
        </div>
        <div class="drawer-footer__right">
          <el-button v-if="selecting" link @click="exitSelecting">Huỷ chọn</el-button>
          <el-button @click="visible = false">Đóng</el-button>
        </div>
      </div>
    </template>
  </el-drawer>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import * as Icons from '@element-plus/icons-vue'
import { Bell, Delete } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import {
  danhDauTatCaThongBaoDaDoc,
  danhDauThongBaoDaDoc,
  fetchThongBaoCuaToi,
  xoaThongBaoCuaToi,
} from '@/api/heThongThongBao'
import { useAuthStore } from '@/stores/auth'

const visible = defineModel({ type: Boolean, default: false })
const unreadCountModel = defineModel('unreadCount', { type: Number, default: 0 })

const authStore = useAuthStore()
const loading = ref(false)
const markingAll = ref(false)
const removing = ref(false)
const removingId = ref(null)
const selecting = ref(false)
const checkedIds = ref([])
const notifications = ref([])

const MAU_SAC_TYPE = {
  green: 'success',
  yellow: 'warning',
  orange: 'warning',
  red: 'warning',
  blue: 'info',
  purple: 'user',
  gray: 'info',
}

const ICON_ALIASES = {
  handshake: 'Opportunity',
  'clipboard-list': 'List',
  'message-circle': 'ChatDotRound',
  'user-plus': 'User',
  'calendar-off': 'Calendar',
  clock: 'Clock',
  'file-check': 'DocumentChecked',
  wallet: 'Wallet',
  megaphone: 'Promotion',
  'at-sign': 'Message',
}

function toPascalCase(name) {
  return String(name || '')
    .trim()
    .split(/[-_\s]+/)
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('')
}

function resolveIcon(name) {
  if (!name) return Bell
  const raw = String(name).trim()
  if (Icons[raw]) return Icons[raw]
  const alias = ICON_ALIASES[raw.toLowerCase()]
  if (alias && Icons[alias]) return Icons[alias]
  const pascal = toPascalCase(raw)
  return Icons[pascal] || Bell
}

function formatRelativeTime(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''

  const diffMs = Date.now() - date.getTime()
  const diffSec = Math.max(0, Math.floor(diffMs / 1000))
  if (diffSec < 60) return 'Vừa xong'
  const diffMin = Math.floor(diffSec / 60)
  if (diffMin < 60) return `${diffMin} phút trước`
  const diffHour = Math.floor(diffMin / 60)
  if (diffHour < 24) return `${diffHour} giờ trước`
  const diffDay = Math.floor(diffHour / 24)
  if (diffDay === 1) return 'Hôm qua'
  if (diffDay < 7) return `${diffDay} ngày trước`

  return date.toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function mapNotification(row) {
  return {
    id: row.id,
    type: MAU_SAC_TYPE[row.loai_mau_sac] || 'info',
    icon: resolveIcon(row.loai_thong_bao?.icon),
    title: row.tieu_de || '',
    message: row.noi_dung || '',
    time: formatRelativeTime(row.created_at),
    read: Boolean(row.da_doc),
  }
}

const unreadCount = computed(
  () => notifications.value.filter((n) => !n.read).length
)

const checkedCount = computed(() => checkedIds.value.length)

const bulkDeleteTooltip = computed(() => {
  if (!checkedCount.value) return 'Chọn thông báo để xoá'
  return `Xoá ${checkedCount.value} thông báo đã chọn`
})

watch(
  unreadCount,
  (count) => {
    unreadCountModel.value = count
  },
  { immediate: true }
)

watch(visible, (open) => {
  if (open) {
    loadNotifications()
  } else {
    exitSelecting()
  }
})

watch(
  () => authStore.user?.id,
  (id) => {
    if (id) loadNotifications()
    else {
      notifications.value = []
      exitSelecting()
    }
  }
)

function exitSelecting() {
  selecting.value = false
  checkedIds.value = []
}

function toggleChecked(id, checked) {
  if (checked) {
    if (!checkedIds.value.includes(id)) {
      checkedIds.value = [...checkedIds.value, id]
    }
    return
  }
  checkedIds.value = checkedIds.value.filter((itemId) => itemId !== id)
}

async function loadNotifications() {
  if (!authStore.user?.id) {
    notifications.value = []
    exitSelecting()
    return
  }

  loading.value = true
  try {
    const { data } = await fetchThongBaoCuaToi({ per_page: 50 })
    const rows = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []
    notifications.value = rows.map(mapNotification)
    checkedIds.value = checkedIds.value.filter((id) =>
      notifications.value.some((n) => n.id === id)
    )
    if (!notifications.value.length) exitSelecting()
  } catch {
    notifications.value = []
    exitSelecting()
  } finally {
    loading.value = false
  }
}

async function onItemClick(item) {
  if (!item) return

  if (!selecting.value) {
    selecting.value = true
    checkedIds.value = [item.id]
  } else {
    const already = checkedIds.value.includes(item.id)
    toggleChecked(item.id, !already)
  }

  await markRead(item)
}

async function markRead(item) {
  if (!item || item.read) return
  item.read = true
  try {
    await danhDauThongBaoDaDoc(item.id)
  } catch {
    item.read = false
  }
}

async function softDeleteIds(ids) {
  const uniqueIds = [...new Set(ids)].filter(Boolean)
  if (!uniqueIds.length) return []

  const results = await Promise.allSettled(
    uniqueIds.map((id) => xoaThongBaoCuaToi(id))
  )
  const successIds = uniqueIds.filter((_, index) => results[index].status === 'fulfilled')
  if (successIds.length) {
    const successSet = new Set(successIds)
    notifications.value = notifications.value.filter((n) => !successSet.has(n.id))
    checkedIds.value = checkedIds.value.filter((id) => !successSet.has(id))
  }
  return successIds
}

async function removeOne(item) {
  if (!item?.id || removingId.value || removing.value) return
  removingId.value = item.id
  try {
    const successIds = await softDeleteIds([item.id])
    if (successIds.length) {
      ElMessage.success('Đã ẩn thông báo')
      if (!notifications.value.length) exitSelecting()
    } else {
      ElMessage.error('Không thể ẩn thông báo')
    }
  } finally {
    removingId.value = null
  }
}

async function removeChecked() {
  if (!checkedCount.value || removing.value) return
  removing.value = true
  const ids = [...checkedIds.value]
  try {
    const successIds = await softDeleteIds(ids)
    if (successIds.length === ids.length) {
      ElMessage.success(`Đã xoá  ${successIds.length} thông báo`)
    } else if (successIds.length) {
      ElMessage.warning(`Đã xoá ${successIds.length}/${ids.length} thông báo`)
    } else {
      ElMessage.error('Không thể xoá thông báo')
    }
    if (!notifications.value.length || !checkedIds.value.length) {
      exitSelecting()
    }
  } finally {
    removing.value = false
  }
}

async function markAllRead() {
  if (!unreadCount.value) return
  markingAll.value = true
  const previous = notifications.value.map((n) => n.read)
  notifications.value.forEach((n) => {
    n.read = true
  })
  try {
    await danhDauTatCaThongBaoDaDoc()
  } catch {
    notifications.value.forEach((n, index) => {
      n.read = previous[index]
    })
  } finally {
    markingAll.value = false
  }
}

onMounted(() => {
  loadNotifications()
})
</script>

<style scoped lang="scss">
.drawer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  padding-right: 12px;
  box-sizing: border-box;

  &__title {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    font-size: 16px;
    font-weight: 600;
  }
}

.notification-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-height: 120px;
  max-width: 100%;
  overflow-x: hidden;
  box-sizing: border-box;
}

.notification-item {
  display: flex;
  gap: 12px;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  padding: 12px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
  background: var(--el-bg-color);
  text-align: left;
  cursor: pointer;
  box-sizing: border-box;
  transition: background 0.15s ease, border-color 0.15s ease;

  &:hover {
    border-color: var(--el-color-primary-light-5);
  }

  &.is-unread {
    background: var(--el-color-primary-light-9);
    border-color: var(--el-color-primary-light-7);
  }

  &.is-checked {
    border-color: var(--el-color-primary);
    box-shadow: inset 0 0 0 1px var(--el-color-primary-light-5);
  }

  &__check {
    display: flex;
    align-items: flex-start;
    padding-top: 8px;
    flex-shrink: 0;
  }

  &__icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: var(--el-fill-color-light);
    color: var(--el-text-color-regular);

    &.is-success {
      background: var(--el-color-success-light-9);
      color: var(--el-color-success);
    }

    &.is-warning {
      background: var(--el-color-warning-light-9);
      color: var(--el-color-warning);
    }

    &.is-info {
      background: var(--el-color-primary-light-9);
      color: var(--el-color-primary);
    }

    &.is-user {
      background: var(--el-color-info-light-9);
      color: var(--el-color-info);
    }

    &.is-finance {
      background: #fdf6ec;
      color: #e6a23c;
    }
  }

  &__body {
    min-width: 0;
    flex: 1;
    overflow: hidden;
  }

  &__top {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
  }

  &__title {
    min-width: 0;
    flex: 1;
    font-size: 14px;
    font-weight: 600;
    color: var(--el-text-color-primary);
    overflow-wrap: anywhere;
    word-break: break-word;
  }

  &__dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--el-color-danger);
    flex-shrink: 0;
  }

  &__message {
    margin: 4px 0 6px;
    font-size: 13px;
    line-height: 1.45;
    color: var(--el-text-color-regular);
    overflow-wrap: anywhere;
    word-break: break-word;
  }

  &__bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    min-width: 0;
  }

  &__time {
    min-width: 0;
    font-size: 12px;
    color: var(--el-text-color-secondary);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.drawer-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  box-sizing: border-box;
  overflow: visible;

  &__left,
  &__right {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 32px;
    min-width: 0;
  }

  &__left {
    padding-top: 4px;
    padding-right: 8px;
  }
}

html.dark .notification-item__icon.is-finance {
  background: rgba(230, 162, 60, 0.16);
  color: #e6a23c;
}
</style>

<style lang="scss">
/* Drawer teleport ra body — cần style global theo class */
.notification-drawer.el-drawer {
  .el-drawer__body {
    overflow-x: hidden;
    overflow-y: auto;
  }
}
</style>
