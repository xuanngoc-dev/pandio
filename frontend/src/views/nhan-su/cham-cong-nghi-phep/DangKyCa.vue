<template>
  <div class="dang-ky-ca">
    <CustomCard shadow="hover" class="filter-card">
      <div class="toolbar">
        <div class="week-nav">
          <CustomButton :icon="ArrowLeft" @click="shiftWeek(-1)" />
          <span class="week-label">{{ weekLabel }}</span>
          <CustomButton :icon="ArrowRight" @click="shiftWeek(1)" />
          <CustomButton plain @click="goToThisWeek">Tuần này</CustomButton>
        </div>
        <div class="toolbar-search">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm nhân viên..."
            clearable
            style="max-width: 260px"
            @clear="onSearch"
            @keyup.enter="onSearch"
          >
            <template #prefix>
              <CustomIcon><Search /></CustomIcon>
            </template>
          </CustomInput>
          <CustomButton type="primary" plain @click="onSearch">
            <CustomIcon><Search /></CustomIcon>
            Tìm kiếm
          </CustomButton>
        </div>
      </div>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Lịch đăng ký ca · Tuần này</span>
          <span class="card-hint">Chỉ đăng ký từ ngày mai trở đi · Ca cả tuần áp dụng các ngày còn chỉnh được</span>
        </div>
      </template>

      <div v-loading="loading" class="table-wrap">
        <div class="table-scroll">
          <CustomTable
            ref="tableRef"
            :data="users"
            stripe
            border
            class="schedule-table"
            :fit="false"
            :style="{ width: `${tableMinWidth}px` }"
            :empty-text="loading ? 'Đang tải...' : 'Không có nhân viên'"
          >
            <CustomTableColumn label="STT" width="60" align="center" fixed="left" class-name="col-sticky col-sticky--stt">
              <template #default="{ $index }">
                {{ (page - 1) * perPage + $index + 1 }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn
              fixed="left"
              label="Nhân viên"
              width="180"
              class-name="col-sticky col-sticky--name"
              label-class-name="col-sticky col-sticky--name"
              show-overflow-tooltip
            >
              <template #default="{ row }">
                {{ row.name }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Ca cả tuần" width="170" align="center">
              <template #header>
                <div class="day-header">
                  <span class="day-header__name">Ca làm</span>
                </div>
              </template>
              <template #default="{ row }">
                <CustomSelect
                  :model-value="getWeekCaLamId(row.id)"
                  clearable
                  filterable
                  placeholder="Chọn ca cả tuần"
                  size="small"
                  style="width: 100%"
                  :loading="isWeekSaving(row.id)"
                  :disabled="!hasEditableDays || isWeekSaving(row.id)"
                  @update:model-value="(value) => onWeekCaChange(row, value)"
                >
                  <CustomOption
                    v-for="ca in caOptions"
                    :key="ca.id"
                    :label="formatCaLabel(ca)"
                    :value="ca.id"
                  />
                </CustomSelect>
              </template>
            </CustomTableColumn>
            <CustomTableColumn
              v-for="day in weekDays"
              :key="day.date"
              width="150"
              align="center"
            >
              <template #header>
                <div
                  class="day-header"
                  :class="{
                    'is-today': day.isToday,
                    'is-locked': day.isLocked,
                  }"
                >
                  <span class="day-header__name">{{ day.label }}</span>
                  <span class="day-header__date">{{ day.displayDate }}</span>
                </div>
              </template>
              <template #default="{ row }">
                <CustomSelect
                  :model-value="getCaLamId(row.id, day.date)"
                  clearable
                  filterable
                  :placeholder="day.isLocked ? 'Không chỉnh' : 'Chọn ca'"
                  size="small"
                  style="width: 100%"
                  :loading="isCellSaving(row.id, day.date)"
                  :disabled="day.isLocked || isCellSaving(row.id, day.date)"
                  @update:model-value="(value) => onCaChange(row, day.date, value)"
                >
                  <CustomOption
                    v-for="ca in caOptions"
                    :key="ca.id"
                    :label="formatCaLabel(ca)"
                    :value="ca.id"
                  />
                </CustomSelect>
              </template>
            </CustomTableColumn>
          </CustomTable>
        </div>
      </div>

      <Pagination
        v-model="page"
        v-model:page-size="perPage"
        :total="total"
        :disabled="loading"
        @change="loadUsers"
      />
    </CustomCard>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { ArrowLeft, ArrowRight, Search } from '@element-plus/icons-vue'
import { fetchUsers } from '@/api/users'
import { fetchCaLamViec } from '@/api/caLamViec'
import { createDangKyCa, deleteDangKyCa, fetchDangKyCa, syncDangKyCaTuan } from '@/api/dangKyCa'
import {
  CustomButton,
  CustomCard,
  CustomIcon,
  CustomInput,
  CustomOption,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'

const DAY_LABELS = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật']

/** Tổng width các cột — đảm bảo luôn có thanh cuộn ngang khi viewport hẹp */
const COL_STT = 60
const COL_NAME = 180
const COL_WEEK = 170
const COL_DAY = 150
const tableMinWidth = COL_STT + COL_NAME + COL_WEEK + COL_DAY * 7

const tableRef = ref(null)
const users = ref([])
const caOptions = ref([])
/** @type {import('vue').Ref<Record<string, { id: number, ca_lam_id: number }>>} */
const scheduleMap = ref({})
const loading = ref(false)
const keyword = ref('')
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
/** @type {import('vue').Ref<Record<string, boolean>>} */
const savingKeys = ref({})
/** @type {import('vue').Ref<Record<number|string, boolean>>} */
const weekSavingIds = ref({})

/** Monday (00:00 local) of the displayed week */
const weekStart = ref(getMonday(new Date()))

let resizeObserver = null

function pad(n) {
  return String(n).padStart(2, '0')
}

function toDateKey(date) {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

function getMonday(date) {
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const day = d.getDay() // 0=CN ... 6=T7
  const diff = day === 0 ? -6 : 1 - day
  d.setDate(d.getDate() + diff)
  return d
}

function addDays(date, days) {
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  d.setDate(d.getDate() + days)
  return d
}

function cellKey(userId, dateKey) {
  return `${userId}|${dateKey}`
}

function todayKey() {
  return toDateKey(new Date())
}

/** Chỉ cho phép chọn từ ngày mai trở đi */
function isDateLocked(dateKey) {
  return dateKey <= todayKey()
}

const weekDays = computed(() => {
  const today = todayKey()
  return DAY_LABELS.map((label, index) => {
    const date = addDays(weekStart.value, index)
    const dateKey = toDateKey(date)
    return {
      label,
      date: dateKey,
      displayDate: `${pad(date.getDate())}/${pad(date.getMonth() + 1)}`,
      isToday: dateKey === today,
      isLocked: dateKey <= today,
    }
  })
})

const editableWeekDays = computed(() => weekDays.value.filter((day) => !day.isLocked))

const hasEditableDays = computed(() => editableWeekDays.value.length > 0)

const weekLabel = computed(() => {
  const start = weekDays.value[0]
  const end = weekDays.value[6]
  if (!start || !end) return ''
  const startDate = addDays(weekStart.value, 0)
  const endDate = addDays(weekStart.value, 6)
  const startFull = `${pad(startDate.getDate())}/${pad(startDate.getMonth() + 1)}/${startDate.getFullYear()}`
  const endFull = `${pad(endDate.getDate())}/${pad(endDate.getMonth() + 1)}/${endDate.getFullYear()}`
  return `${startFull} — ${endFull}`
})

function formatCaLabel(ca) {
  return ca.ten_ca
}

function getCaLamId(userId, dateKey) {
  return scheduleMap.value[cellKey(userId, dateKey)]?.ca_lam_id ?? null
}

/** Hiện ca chung nếu mọi ngày còn chỉnh được đang cùng 1 ca; ngược lại null */
function getWeekCaLamId(userId) {
  const days = editableWeekDays.value
  if (!days.length) return null
  const first = getCaLamId(userId, days[0].date)
  if (first == null) return null
  const allSame = days.every((day) => getCaLamId(userId, day.date) === first)
  return allSame ? first : null
}

function isCellSaving(userId, dateKey) {
  return !!savingKeys.value[cellKey(userId, dateKey)]
}

function isWeekSaving(userId) {
  return !!weekSavingIds.value[userId]
}

function shiftWeek(delta) {
  weekStart.value = addDays(weekStart.value, delta * 7)
  loading.value = true
  loadSchedule().finally(() => {
    loading.value = false
  })
}

function goToThisWeek() {
  weekStart.value = getMonday(new Date())
  loading.value = true
  loadSchedule().finally(() => {
    loading.value = false
  })
}

async function refreshTableLayout() {
  await nextTick()
  tableRef.value?.doLayout?.()
}

async function fetchUserPage() {
  const { data } = await fetchUsers({
    page: page.value,
    per_page: perPage.value,
    keyword: keyword.value.trim() || undefined,
    status: 'active',
  })
  users.value = data.data || []
  total.value = data.total || 0
  page.value = data.current_page || page.value
}

async function loadUsers() {
  loading.value = true
  try {
    await fetchUserPage()
  } catch {
    users.value = []
    total.value = 0
  } finally {
    loading.value = false
    await refreshTableLayout()
  }
}

function onSearch() {
  page.value = 1
  loadUsers()
}

async function loadCaOptions() {
  try {
    const { data } = await fetchCaLamViec({ per_page: 100, trang_thai: 'co' })
    caOptions.value = data.data || []
  } catch {
    caOptions.value = []
  }
}

async function loadSchedule() {
  try {
    const tuNgay = toDateKey(weekStart.value)
    const denNgay = toDateKey(addDays(weekStart.value, 6))
    const { data } = await fetchDangKyCa({ tu_ngay: tuNgay, den_ngay: denNgay })
    const map = {}
    for (const item of data || []) {
      const dateKey = String(item.ngay_lam).slice(0, 10)
      map[cellKey(item.nguoi_dung_id, dateKey)] = {
        id: item.id,
        ca_lam_id: item.ca_lam_id,
      }
    }
    scheduleMap.value = map
  } catch {
    scheduleMap.value = {}
  }
}

function applyScheduleItems(items) {
  const next = { ...scheduleMap.value }
  for (const item of items || []) {
    const dateKey = String(item.ngay_lam).slice(0, 10)
    const key = cellKey(item.nguoi_dung_id, dateKey)
    next[key] = { id: item.id, ca_lam_id: item.ca_lam_id }
  }
  scheduleMap.value = next
}

async function onCaChange(user, dateKey, caLamId) {
  if (isDateLocked(dateKey)) {
    ElMessage.warning('Chỉ được đăng ký ca từ ngày mai trở đi.')
    return
  }

  const key = cellKey(user.id, dateKey)
  const current = scheduleMap.value[key]

  if ((current?.ca_lam_id ?? null) === (caLamId ?? null)) return

  savingKeys.value = { ...savingKeys.value, [key]: true }
  try {
    if (caLamId == null) {
      if (current?.id) {
        await deleteDangKyCa(current.id)
      }
      const next = { ...scheduleMap.value }
      delete next[key]
      scheduleMap.value = next
      ElMessage.success('Đã xóa đăng ký ca.')
      return
    }

    const { data } = await createDangKyCa({
      nguoi_dung_id: user.id,
      ngay_lam: dateKey,
      ca_lam_id: caLamId,
    })
    scheduleMap.value = {
      ...scheduleMap.value,
      [key]: { id: data.id, ca_lam_id: data.ca_lam_id },
    }
    ElMessage.success('Đã lưu đăng ký ca.')
  } catch {
    // Lỗi đã được axios interceptor xử lý — giữ nguyên giá trị cũ
  } finally {
    const next = { ...savingKeys.value }
    delete next[key]
    savingKeys.value = next
  }
}

async function onWeekCaChange(user, caLamId) {
  const days = editableWeekDays.value
  if (!days.length) {
    ElMessage.warning('Tuần này không còn ngày nào có thể đăng ký ca.')
    return
  }

  weekSavingIds.value = { ...weekSavingIds.value, [user.id]: true }
  try {
    const tuNgay = toDateKey(weekStart.value)
    const denNgay = toDateKey(addDays(weekStart.value, 6))
    const items = days.map((day) => ({
      nguoi_dung_id: user.id,
      ngay_lam: day.date,
      ca_lam_id: caLamId ?? null,
    }))

    const { data } = await syncDangKyCaTuan({
      tu_ngay: tuNgay,
      den_ngay: denNgay,
      items,
    })

    // Xóa đăng ký cũ của các ngày editable, rồi gắn lại từ response
    const next = { ...scheduleMap.value }
    for (const day of days) {
      delete next[cellKey(user.id, day.date)]
    }
    scheduleMap.value = next
    applyScheduleItems(
      (data || []).filter(
        (item) =>
          item.nguoi_dung_id === user.id &&
          days.some((day) => day.date === String(item.ngay_lam).slice(0, 10)),
      ),
    )

    ElMessage.success(
      caLamId == null
        ? 'Đã xóa ca đăng ký các ngày còn lại trong tuần.'
        : 'Đã áp dụng ca cho cả tuần (từ ngày mai trở đi).',
    )
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    const next = { ...weekSavingIds.value }
    delete next[user.id]
    weekSavingIds.value = next
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await loadCaOptions()
    await Promise.all([
      fetchUserPage().catch(() => {
        users.value = []
        total.value = 0
      }),
      loadSchedule(),
    ])
  } finally {
    loading.value = false
    await refreshTableLayout()
  }

  window.addEventListener('resize', refreshTableLayout)
  const wrap = document.querySelector('.dang-ky-ca .table-wrap')
  if (wrap && typeof ResizeObserver !== 'undefined') {
    resizeObserver = new ResizeObserver(() => {
      refreshTableLayout()
    })
    resizeObserver.observe(wrap)
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', refreshTableLayout)
  resizeObserver?.disconnect()
  resizeObserver = null
})
</script>

<style scoped>
.dang-ky-ca {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.week-nav {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.toolbar-search {
  display: inline-flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.week-label {
  min-width: 190px;
  text-align: center;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.card-header {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 8px 16px;
}

.card-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.card-hint {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.table-wrap {
  width: 100%;
  max-width: 100%;
  overflow: hidden;
}

.schedule-table :deep(.el-table__fixed-left) {
  box-shadow: 6px 0 8px -4px rgba(0, 0, 0, 0.08);
}

.day-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  line-height: 1.3;
}

.day-header__name {
  font-weight: 600;
}

.day-header__date {
  font-size: 12px;
  font-weight: 400;
  color: var(--el-text-color-secondary);
}

.day-header.is-today .day-header__name,
.day-header.is-today .day-header__date {
  color: var(--el-color-primary);
}

.day-header.is-locked .day-header__name,
.day-header.is-locked .day-header__date {
  color: var(--el-text-color-disabled);
}
</style>
