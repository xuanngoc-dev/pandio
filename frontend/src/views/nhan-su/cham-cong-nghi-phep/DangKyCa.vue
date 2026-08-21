<template>
  <div class="dang-ky-ca page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar dang-ky-ca-toolbar" align="middle">
        <CustomCol :xs="24" :sm="14" :md="14" :lg="14">
          <div class="toolbar-group toolbar-group--left">
            <CustomInput
              v-model="keyword"
              class="toolbar-search-input"
              placeholder="Tìm nhân viên..."
              clearable
              @clear="onSearch"
              @keyup.enter="onSearch"
            >
              <template #prefix>
                <CustomIcon><Search /></CustomIcon>
              </template>
            </CustomInput>
            <CustomButton type="primary" plain @click="onSearch">
              Tìm kiếm
            </CustomButton>
          </div>
        </CustomCol>
        <CustomCol :xs="24" :sm="10" :md="10" :lg="10">
          <div class="toolbar-group toolbar-group--right">
            <CustomButton :type="isThisWeek ? 'primary' : 'default'" plain @click="goToThisWeek">
              Tuần này
            </CustomButton>
            <CustomButton :type="isNextWeek ? 'primary' : 'default'" plain @click="goToNextWeek">
              Tuần sau
            </CustomButton>
          </div>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomCard
      shadow="hover"
      class="summary-card"
      :class="{ 'summary-card--collapsed': !summaryExpanded }"
    >
      <template #header>
        <button
          type="button"
          class="summary-card-header"
          :aria-expanded="summaryExpanded"
          @click="summaryExpanded = !summaryExpanded"
        >
          <div class="summary-card-header__left">
            <span class="card-title">Đăng ký ca làm theo tuần</span>
            <span class="employee-badge">{{ activeEmployeeTotal }} nhân viên</span>
          </div>
          <CustomIcon class="summary-card-arrow" :class="{ 'is-expanded': summaryExpanded }">
            <ArrowDown />
          </CustomIcon>
        </button>
      </template>

      <div v-show="summaryExpanded" class="overview-row">
        <div class="overview-tile overview-tile--week">
          <div class="overview-tile__head">
            <span class="overview-tile__title">{{ weekShortLabel }}</span>
            <span class="overview-tile__badge">{{ weekOverview.registered }}</span>
          </div>
          <div class="overview-tile__body">
            <div
              v-for="ca in weekOverview.byCa"
              :key="ca.caId"
              class="overview-ca-row"
            >
              <span>{{ ca.label }}</span>
              <strong>{{ ca.count }}</strong>
            </div>
          </div>
          <div class="overview-tile__foot">
            <em>Chưa ĐK</em>
            <strong>{{ weekOverview.unregistered }}</strong>
          </div>
        </div>

        <div
          v-for="day in dayOverviews"
          :key="day.date"
          class="overview-tile"
          :class="{
            'overview-tile--weekend': day.isWeekend,
            'overview-tile--today': day.isToday,
          }"
        >
          <div class="overview-tile__head">
            <span class="overview-tile__title">{{ day.shortLabel }} {{ day.shortDate }}</span>
            <span class="overview-tile__badge">{{ day.registered }}</span>
          </div>
          <div class="overview-tile__body">
            <div
              v-for="ca in day.byCa"
              :key="ca.caId"
              class="overview-ca-row"
            >
              <span>{{ ca.label }}</span>
              <strong>{{ ca.count }}</strong>
            </div>
          </div>
          <div class="overview-tile__foot">
            <em>Chưa ĐK</em>
            <strong>{{ day.unregistered }}</strong>
          </div>
        </div>
      </div>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Lịch đăng ký ca</span>
          <!-- <span class="card-hint">Chỉ đăng ký từ ngày mai trở đi · Ca cả tuần áp dụng các ngày còn chỉnh được</span> -->
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
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { ArrowDown, Search } from '@element-plus/icons-vue'
import { fetchUsers } from '@/api/users'
import { fetchCaLamViec } from '@/api/caLamViec'
import { createDangKyCa, deleteDangKyCa, fetchDangKyCa, syncDangKyCaTuan } from '@/api/dangKyCa'
import { useAuthStore } from '@/stores/auth'
import {
  CustomButton,
  CustomCard,
  CustomCol,
  CustomIcon,
  CustomInput,
  CustomOption,
  CustomRow,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'

const authStore = useAuthStore()
const isAdmin = computed(() => authStore.user?.role === 'admin')

const DAY_LABELS = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật']
const DAY_SHORT_LABELS = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']

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
/** Tổng NV đang hoạt động — dùng cho tổng quan (không phụ thuộc keyword) */
const activeEmployeeTotal = ref(0)
/** Card tổng quan tuần — mặc định mở, click header để ẩn/hiện */
const summaryExpanded = ref(true)
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

/**
 * Khoá ngày quá khứ.
 * Admin được đăng ký từ hôm nay; role khác chỉ từ ngày mai trở đi.
 */
function isDateLocked(dateKey) {
  const today = todayKey()
  return isAdmin.value ? dateKey < today : dateKey <= today
}

const weekDays = computed(() => {
  const today = todayKey()
  return DAY_LABELS.map((label, index) => {
    const date = addDays(weekStart.value, index)
    const dateKey = toDateKey(date)
    return {
      label,
      shortLabel: DAY_SHORT_LABELS[index],
      date: dateKey,
      displayDate: `${pad(date.getDate())}/${pad(date.getMonth() + 1)}`,
      shortDate: `${date.getDate()}/${date.getMonth() + 1}`,
      isToday: dateKey === today,
      isLocked: isDateLocked(dateKey),
      isWeekend: index >= 5,
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

const weekShortLabel = computed(() => {
  const days = weekDays.value
  if (!days.length) return ''
  return `Tuần ${days[0].shortDate}–${days[6].shortDate}`
})

const thisWeekMonday = computed(() => getMonday(new Date()))

const isThisWeek = computed(
  () => toDateKey(weekStart.value) === toDateKey(thisWeekMonday.value),
)

const isNextWeek = computed(
  () => toDateKey(weekStart.value) === toDateKey(addDays(thisWeekMonday.value, 7)),
)

/** Đếm ca_lam_id theo từng ngày trong tuần */
const countsByDate = computed(() => {
  /** @type {Record<string, Record<number, number>>} */
  const byDate = {}
  for (const day of weekDays.value) {
    byDate[day.date] = {}
  }
  for (const [key, entry] of Object.entries(scheduleMap.value)) {
    const dateKey = key.split('|')[1]
    if (!byDate[dateKey] || entry.ca_lam_id == null) continue
    const caId = entry.ca_lam_id
    byDate[dateKey][caId] = (byDate[dateKey][caId] || 0) + 1
  }
  return byDate
})

function buildCaRows(countMap) {
  return caOptions.value.map((ca) => ({
    caId: ca.id,
    label: formatCaLabel(ca),
    count: countMap[ca.id] || 0,
  }))
}

const dayOverviews = computed(() => {
  const employeeCount = activeEmployeeTotal.value
  return weekDays.value.map((day) => {
    const countMap = countsByDate.value[day.date] || {}
    const byCa = buildCaRows(countMap)
    const registered = byCa.reduce((sum, item) => sum + item.count, 0)
    return {
      ...day,
      byCa,
      registered,
      unregistered: Math.max(0, employeeCount - registered),
    }
  })
})

const weekOverview = computed(() => {
  const employeeCount = activeEmployeeTotal.value
  /** @type {Record<number, number>} */
  const countMap = {}
  for (const day of dayOverviews.value) {
    for (const ca of day.byCa) {
      countMap[ca.caId] = (countMap[ca.caId] || 0) + ca.count
    }
  }
  const byCa = buildCaRows(countMap)
  const registered = byCa.reduce((sum, item) => sum + item.count, 0)
  return {
    byCa,
    registered,
    unregistered: Math.max(0, employeeCount * 7 - registered),
  }
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

async function loadWeek(mondayDate) {
  weekStart.value = mondayDate
  loading.value = true
  try {
    await loadSchedule()
  } finally {
    loading.value = false
  }
}

function goToThisWeek() {
  loadWeek(getMonday(new Date()))
}

function goToNextWeek() {
  loadWeek(addDays(getMonday(new Date()), 7))
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

async function loadActiveEmployeeTotal() {
  try {
    const { data } = await fetchUsers({ page: 1, per_page: 1, status: 'active' })
    activeEmployeeTotal.value = data.total || 0
  } catch {
    activeEmployeeTotal.value = 0
  }
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
    ElMessage.warning(
      isAdmin.value
        ? 'Không được đăng ký ca cho ngày trong quá khứ.'
        : 'Chỉ được đăng ký ca từ ngày mai trở đi.',
    )
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
        : isAdmin.value
          ? 'Đã áp dụng ca cho cả tuần (từ hôm nay trở đi).'
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

async function refresh() {
  loading.value = true
  try {
    await loadCaOptions()
    await Promise.all([
      fetchUserPage().catch(() => {
        users.value = []
        total.value = 0
      }),
      loadActiveEmployeeTotal(),
      loadSchedule(),
    ])
  } finally {
    loading.value = false
    await refreshTableLayout()
  }
}

const props = defineProps({
  active: { type: Boolean, default: false },
})

watch(
  () => props.active,
  (isActive) => {
    if (isActive) refresh()
  },
  { immediate: true },
)

onMounted(() => {
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
.dang-ky-ca-toolbar {
  width: 100%;
}

.toolbar-group {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
}

.toolbar-group--left {
  justify-content: flex-start;
}

.toolbar-group--right {
  justify-content: flex-end;
}

.toolbar-search-input {
  flex: 1 1 220px;
  max-width: 320px;
  min-width: 160px;
}

@media (max-width: 767px) {
  .toolbar-group--right {
    justify-content: flex-start;
  }

  .toolbar-search-input {
    max-width: none;
  }
}

.week-label {
  margin-left: 4px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  white-space: nowrap;
}

.card-header {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 8px 16px;
}

.card-hint {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.summary-card-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px 12px;
  width: 100%;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
  text-align: left;
}

.summary-card-header__left {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 12px;
  min-width: 0;
}

.summary-card-arrow {
  flex-shrink: 0;
  color: var(--el-text-color-secondary);
  transition: transform 0.2s ease;
  transform: rotate(-90deg);
}

.summary-card-arrow.is-expanded {
  transform: rotate(0deg);
}

.summary-card--collapsed :deep(.el-card__body) {
  padding: 0;
  border: 0;
}

.employee-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  color: var(--el-color-primary);
  background: var(--el-color-primary-light-9);
}

.overview-row {
  display: grid;
  grid-template-columns: repeat(8, minmax(0, 1fr));
  gap: 10px;
  overflow-x: auto;
}

.overview-tile {
  display: flex;
  flex-direction: column;
  min-width: 0;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
  background: var(--el-bg-color);
  overflow: hidden;
}

.overview-tile__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  padding: 8px 10px;
  background: var(--el-fill-color-light);
}

.overview-tile--week .overview-tile__head {
  background: var(--el-color-primary-light-9);
}

.overview-tile--weekend .overview-tile__head {
  background: var(--el-color-success-light-9);
}

.overview-tile--today:not(.overview-tile--weekend) .overview-tile__head {
  background: var(--el-color-primary-light-8);
}

.overview-tile__title {
  font-size: 13px;
  font-weight: 700;
  color: var(--el-text-color-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.overview-tile--week .overview-tile__title {
  color: var(--el-color-primary);
}

.overview-tile--weekend .overview-tile__title {
  color: var(--el-color-success);
}

.overview-tile__badge {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  color: var(--el-text-color-regular);
  background: var(--el-bg-color);
  border: 1px solid var(--el-border-color-lighter);
}

.overview-tile--week .overview-tile__badge {
  color: var(--el-color-primary);
  border-color: var(--el-color-primary-light-5);
  background: var(--el-bg-color);
}

.overview-tile--weekend .overview-tile__badge {
  color: var(--el-color-success);
  border-color: var(--el-color-success-light-5);
  background: var(--el-bg-color);
}

.overview-tile__body {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px;
  flex: 1;
}

.overview-ca-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  font-size: 13px;
  color: var(--el-text-color-regular);
}

.overview-ca-row strong {
  font-weight: 700;
  color: var(--el-text-color-primary);
}

.overview-tile__foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 8px 10px 10px;
  border-top: 1px solid var(--el-border-color-extra-light);
}

.overview-tile__foot em {
  font-size: 12px;
  font-style: italic;
  color: var(--el-color-warning);
}

.overview-tile__foot strong {
  font-size: 14px;
  font-weight: 700;
  color: var(--el-color-warning);
}

@media (max-width: 1200px) {
  .overview-row {
    grid-template-columns: repeat(8, minmax(120px, 1fr));
  }
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
