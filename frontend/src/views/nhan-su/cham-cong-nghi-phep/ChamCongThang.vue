<template>
  <div class="cham-cong-thang page-list">
    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Chấm công tháng</span>
          <div class="card-header-actions">
            <CustomDatePicker
              v-model="selectedMonth"
              type="month"
              placeholder="Chọn tháng"
              format="MM/YYYY"
              value-format="YYYY-MM"
              :clearable="false"
              style="width: 140px"
              @change="onMonthChange"
            />
            <CustomInput
              v-model="keyword"
              placeholder="Tìm nhân viên..."
              clearable
              style="width: 220px"
              @clear="onSearch"
              @keyup.enter="onSearch"
            >
              <template #prefix>
                <CustomIcon><Search /></CustomIcon>
              </template>
            </CustomInput>
            <CustomButton type="primary" plain @click="onSearch">
              <CustomIcon><Search /></CustomIcon>
              Tìm
            </CustomButton>
          </div>
        </div>
      </template>

      <div class="legend">
        <span class="legend__item">
          <span class="cell-mark cell-mark--leave-approved" /> Nghỉ phép (đã duyệt)
        </span>
        <span class="legend__item">
          <span class="cell-mark cell-mark--leave-pending" /> Nghỉ phép (chờ duyệt)
        </span>
        <span class="legend__item">
          <span class="legend-swatch is-ngay-nghi" /> Ngày nghỉ
        </span>
        <span class="legend__item">
          <span class="legend__sample legend__sample--phat-muon">50k</span> Phạt đi muộn
        </span>
        <span class="legend__item">
          <span class="legend__sample legend__sample--phat-som">30k</span> Phạt về sớm
        </span>
        <span class="legend__item">
          <CustomIcon class="employee-type-icon employee-type-icon--full"><Briefcase /></CustomIcon>
          Full time
        </span>
        <span class="legend__item">
          <CustomIcon class="employee-type-icon employee-type-icon--part"><Clock /></CustomIcon>
          Part time
        </span>
      </div>

      <CustomTable
        v-loading="loading"
        :data="rows"
        stripe
        row-key="id"
        style="width: 100%"
        :empty-text="loading ? 'Đang tải...' : 'Chưa có nhân viên'"
        class="attendance-table"
      >
        <CustomTableColumn label="STT" width="56" align="center" fixed="left">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          label="Nhân viên"
          prop="name"
          min-width="200"
          fixed="left"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            <div class="employee-cell">
              <div class="employee-cell__name-row">
                <span class="employee-cell__name">{{ row.name }}</span>
                <CustomTooltip
                  v-if="row.loai_nhan_vien"
                  :content="employeeTypeLabel(row.loai_nhan_vien)"
                  placement="top"
                >
                  <CustomIcon
                    class="employee-type-icon"
                    :class="
                      row.loai_nhan_vien === 'full_time'
                        ? 'employee-type-icon--full'
                        : 'employee-type-icon--part'
                    "
                  >
                    <component :is="employeeTypeIcon(row.loai_nhan_vien)" />
                  </CustomIcon>
                </CustomTooltip>
              </div>
              <span v-if="row.email" class="employee-cell__email">{{ row.email }}</span>
            </div>
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-for="day in daysInMonth"
          :key="day.date"
          :label="String(day.day)"
          :prop="day.date"
          width="100"
          align="center"
          :class-name="dayColumnClass(day)"
          :label-class-name="dayColumnClass(day)"
        >
          <template #header>
            <CustomTooltip
              :content="day.isNgayNghi ? day.ngayNghiName : ''"
              :disabled="!day.isNgayNghi"
              placement="top"
            >
              <div
                class="day-header"
                :class="{
                  'day-header--weekend': day.isWeekend && !day.isNgayNghi,
                  'day-header--ngay-nghi': day.isNgayNghi,
                }"
              >
                <span class="day-header__num">{{ day.day }}</span>
                <span class="day-header__dow">{{ day.dowLabel }}</span>
              </div>
            </CustomTooltip>
          </template>
          <template #default="{ row }">
            <template v-for="cell in [getDayCell(row, day.date)]" :key="day.date">
              <div class="day-cell">
                <CustomTooltip
                  v-if="cell?.leave"
                  :content="leaveTooltip(cell.leave)"
                  placement="top"
                >
                  <span
                    class="cell-mark cell-mark--corner"
                    :class="leaveMarkClass(cell.leave)"
                  />
                </CustomTooltip>

                <CustomTooltip
                  :content="attendanceTooltip(cell?.attendance)"
                  :disabled="!attendanceTooltip(cell?.attendance)"
                  placement="top"
                >
                  <div class="day-cell__body">
                    <template v-if="cell?.attendance">
                      <div class="time-row">
                        <span class="time-line" :class="checkinClass(cell.attendance)">
                          {{ formatTime(cell.attendance.gio_vao) }}
                        </span>
                        <span class="time-row__sep">-</span>
                        <span class="time-line" :class="checkoutClass(cell.attendance)">
                          {{ formatTime(cell.attendance.gio_ra) }}
                        </span>
                      </div>
                    </template>
                  </div>
                </CustomTooltip>

                <CustomTooltip
                  v-if="penaltyAmount(cell?.attendance?.tien_phat_di_muon)"
                  :content="penaltyLateTooltip(cell.attendance)"
                  placement="top"
                >
                  <span class="penalty-line penalty-line--muon penalty-line--corner-bl">
                    {{ formatMoneyK(cell.attendance.tien_phat_di_muon) }}
                  </span>
                </CustomTooltip>
                <CustomTooltip
                  v-if="penaltyAmount(cell?.attendance?.tien_phat_ve_som)"
                  :content="penaltyEarlyTooltip(cell.attendance)"
                  placement="top"
                >
                  <span class="penalty-line penalty-line--som penalty-line--corner-br">
                    {{ formatMoneyK(cell.attendance.tien_phat_ve_som) }}
                  </span>
                </CustomTooltip>

                <CustomTooltip
                  v-if="canProxyCheckin(day, cell)"
                  content="Điểm danh hộ"
                  placement="top"
                >
                  <CustomButton
                    class="proxy-btn"
                    type="primary"
                    link
                    size="small"
                    @click.stop="openProxyModal(row, day)"
                  >
                    <CustomIcon><EditPen /></CustomIcon>
                  </CustomButton>
                </CustomTooltip>
              </div>
            </template>
          </template>
        </CustomTableColumn>

        <el-table-column label="Tổng" align="center" fixed="right">
          <el-table-column
            label="Cơ bản"
            width="72"
            align="center"
            fixed="right"
          >
            <template #default="{ row }">
              <span class="total-hours">{{ formatHours(row.totalCoBan) }}</span>
            </template>
          </el-table-column>
          <el-table-column
            label="Tăng ca"
            width="72"
            align="center"
            fixed="right"
          >
            <template #default="{ row }">
              <span class="total-hours total-hours--ot">{{ formatHours(row.totalTangCa) }}</span>
            </template>
          </el-table-column>
          <el-table-column
            label="Phạt"
            width="72"
            align="center"
            fixed="right"
          >
            <template #default="{ row }">
              <span
                class="total-hours"
                :class="{ 'total-hours--phat': row.totalPhat > 0 }"
              >
                {{ row.totalPhat > 0 ? formatMoneyK(row.totalPhat) : '0' }}
              </span>
            </template>
          </el-table-column>
        </el-table-column>
      </CustomTable>

      <Pagination
        v-model="page"
        v-model:page-size="perPage"
        :total="total"
        :disabled="loading"
        @change="loadData"
      />
    </CustomCard>

    <DiemDanhHoModal ref="proxyModalRef" @saved="loadData" />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Briefcase, Clock, EditPen, Search } from '@element-plus/icons-vue'
import { fetchDiemDanh } from '@/api/diemDanh'
import { fetchNgayNghi } from '@/api/ngayNghi'
import { fetchUsers } from '@/api/users'
import { fetchXinNghiPhep } from '@/api/xinNghiPhep'
import Pagination from '@/components/Pagination.vue'
import {
  CustomButton,
  CustomCard,
  CustomDatePicker,
  CustomIcon,
  CustomInput,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import DiemDanhHoModal from './DiemDanhHoModal.vue'

const DOW_LABELS = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']

const LOAI_NGHI_LABELS = {
  di_muon: 'Đi muộn',
  ve_som: 'Về sớm',
  nghi_nua_ngay: 'Nghỉ nửa ngày',
  nghi_1_ngay: 'Nghỉ 1 ngày',
  nghi_nhieu_ngay: 'Nghỉ nhiều ngày',
}

const BUOI_NGHI_LABELS = {
  sang: 'Buổi sáng',
  chieu: 'Buổi chiều',
}

const selectedMonth = ref(currentMonthValue())
const keyword = ref('')
const loading = ref(false)
const page = ref(1)
const perPage = ref(20)
const total = ref(0)
const rows = ref([])
/** @type {import('vue').Ref<Record<string, { ten_ngay_nghi?: string }>>} */
const ngayNghiByDate = ref({})

const proxyModalRef = ref(null)

const todayKey = computed(() =>
  new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Ho_Chi_Minh',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(new Date()),
)

const daysInMonth = computed(() => {
  const [yearStr, monthStr] = (selectedMonth.value || currentMonthValue()).split('-')
  const year = Number(yearStr)
  const month = Number(monthStr)
  const count = new Date(year, month, 0).getDate()
  const days = []

  for (let day = 1; day <= count; day += 1) {
    const date = new Date(year, month - 1, day)
    const dow = date.getDay()
    const dateKey = `${yearStr}-${monthStr}-${String(day).padStart(2, '0')}`
    const holiday = ngayNghiByDate.value[dateKey]
    days.push({
      day,
      date: dateKey,
      dow,
      dowLabel: DOW_LABELS[dow],
      isWeekend: dow === 0 || dow === 6,
      isNgayNghi: Boolean(holiday),
      ngayNghiName: holiday?.ten_ngay_nghi || 'Ngày nghỉ',
    })
  }

  return days
})

function currentMonthValue() {
  const now = new Date()
  const y = now.getFullYear()
  const m = String(now.getMonth() + 1).padStart(2, '0')
  return `${y}-${m}`
}

function monthRange(monthValue) {
  const [yearStr, monthStr] = monthValue.split('-')
  const year = Number(yearStr)
  const month = Number(monthStr)
  const lastDay = new Date(year, month, 0).getDate()
  return {
    tu_ngay: `${yearStr}-${monthStr}-01`,
    den_ngay: `${yearStr}-${monthStr}-${String(lastDay).padStart(2, '0')}`,
  }
}

function toDateKey(value) {
  return String(value || '').slice(0, 10)
}

function eachDateKey(startKey, endKey) {
  const keys = []
  if (!startKey) return keys
  const end = endKey || startKey
  const cursor = new Date(`${startKey}T00:00:00`)
  const last = new Date(`${end}T00:00:00`)
  if (Number.isNaN(cursor.getTime()) || Number.isNaN(last.getTime())) return keys

  while (cursor <= last) {
    const y = cursor.getFullYear()
    const m = String(cursor.getMonth() + 1).padStart(2, '0')
    const d = String(cursor.getDate()).padStart(2, '0')
    keys.push(`${y}-${m}-${d}`)
    cursor.setDate(cursor.getDate() + 1)
  }
  return keys
}

function dayColumnClass(day) {
  if (day.isNgayNghi) return 'is-ngay-nghi-col'
  if (day.isWeekend) return 'is-weekend-col'
  return ''
}

function canProxyCheckin(day, cell) {
  if (!day?.date) return false
  // Chỉ từ đầu tháng → hôm nay
  if (day.date > todayKey.value) return false
  // Chưa có checkin
  if (cell?.attendance?.gio_vao) return false
  return true
}

function openProxyModal(row, day) {
  proxyModalRef.value?.open({
    userId: row.id,
    ngayLam: day.date,
    name: row.name,
    email: row.email,
  })
}

function employeeTypeLabel(value) {
  if (value === 'full_time') return 'Full time'
  if (value === 'part_time') return 'Part time'
  return ''
}

function employeeTypeIcon(value) {
  return value === 'part_time' ? Clock : Briefcase
}

function formatTime(value) {
  if (!value) return '—'
  const str = String(value)
  if (str.includes('T')) return str.slice(11, 16)
  if (str.includes(' ')) return str.slice(11, 16)
  return str.slice(0, 5)
}

function formatHours(value) {
  const num = Number(value)
  if (!Number.isFinite(num) || num === 0) return '0'
  return Number.isInteger(num) ? String(num) : num.toFixed(1).replace(/\.0$/, '')
}

/** 100000 → 100k, 45000 → 45k */
function formatMoneyK(value) {
  const num = Number(value)
  if (!Number.isFinite(num) || num === 0) return ''
  if (Math.abs(num) >= 1000) {
    const k = num / 1000
    const label = Number.isInteger(k) ? String(k) : k.toFixed(1).replace(/\.0$/, '')
    return `${label}k`
  }
  return String(Math.round(num))
}

function penaltyAmount(value) {
  const num = Number(value)
  return Number.isFinite(num) && num > 0 ? num : 0
}

function formatMoneyFull(value) {
  const num = Number(value)
  if (!Number.isFinite(num) || num === 0) return ''
  return `${num.toLocaleString('vi-VN')} ₫`
}

function penaltyLateTooltip(record) {
  const amount = penaltyAmount(record?.tien_phat_di_muon)
  if (!amount) return ''
  const parts = ['Phạt đi muộn']
  if (record?.thoi_gian_di_muon) {
    parts.push(`${record.thoi_gian_di_muon} phút`)
  }
  parts.push(formatMoneyFull(amount))
  return parts.join(' · ')
}

function penaltyEarlyTooltip(record) {
  const amount = penaltyAmount(record?.tien_phat_ve_som)
  if (!amount) return ''
  const parts = ['Phạt về sớm']
  if (record?.thoi_gian_ve_som) {
    parts.push(`${record.thoi_gian_ve_som} phút`)
  }
  parts.push(formatMoneyFull(amount))
  return parts.join(' · ')
}

function getDayCell(row, dateKey) {
  return row.days?.[dateKey] || null
}

function leaveMarkClass(leave) {
  if (!leave) return ''
  return leave.trang_thai === 'da_duyet'
    ? 'cell-mark--leave-approved'
    : 'cell-mark--leave-pending'
}

/** Giờ checkin: xanh nếu hợp lệ, đỏ nếu đi muộn, vàng nếu mới checkin. */
function checkinClass(record) {
  if (!record?.gio_vao) return 'time-line--muted'
  if (record.di_muon === 'co') return 'time-line--late'
  if (!record.gio_ra) return 'time-line--partial'
  return 'time-line--ok'
}

/** Giờ checkout: xanh nếu hợp lệ, cam nếu về sớm, mờ nếu chưa checkout. */
function checkoutClass(record) {
  if (!record?.gio_ra) return 'time-line--muted'
  if (record.ve_som === 'co') return 'time-line--early'
  return 'time-line--ok'
}

function leaveTooltip(leave) {
  if (!leave) return ''
  const parts = [
    leave.trang_thai === 'da_duyet' ? 'Nghỉ phép · Đã duyệt' : 'Nghỉ phép · Chờ duyệt',
    LOAI_NGHI_LABELS[leave.loai_nghi_phep] || leave.loai_nghi_phep,
  ]
  if (leave.buoi_nghi) {
    parts.push(BUOI_NGHI_LABELS[leave.buoi_nghi] || leave.buoi_nghi)
  }
  if (leave.ly_do) parts.push(leave.ly_do)
  return parts.join(' · ')
}

function attendanceTooltip(record) {
  if (!record) return ''
  const parts = []
  if (record.di_muon === 'co') {
    parts.push(
      record.thoi_gian_di_muon
        ? `Đi muộn: ${record.thoi_gian_di_muon} phút`
        : 'Đi muộn',
    )
  }
  if (record.ve_som === 'co') {
    parts.push(
      record.thoi_gian_ve_som
        ? `Về sớm: ${record.thoi_gian_ve_som} phút`
        : 'Về sớm',
    )
  }
  const phatMuon = penaltyAmount(record.tien_phat_di_muon)
  if (phatMuon) parts.push(`Phạt muộn: ${formatMoneyFull(phatMuon)}`)
  const phatSom = penaltyAmount(record.tien_phat_ve_som)
  if (phatSom) parts.push(`Phạt về sớm: ${formatMoneyFull(phatSom)}`)
  if (!record.gio_ra && record.gio_vao) {
    parts.push('Chưa checkout')
  }
  return parts.join(' · ')
}

function sumAttendanceHours(days, field) {
  return Object.values(days || {}).reduce((sum, cell) => {
    const val = Number(cell?.attendance?.[field])
    return sum + (Number.isFinite(val) ? val : 0)
  }, 0)
}

function sumPenalties(days) {
  return Object.values(days || {}).reduce((sum, cell) => {
    const record = cell?.attendance
    if (!record) return sum
    return (
      sum
      + penaltyAmount(record.tien_phat_di_muon)
      + penaltyAmount(record.tien_phat_ve_som)
    )
  }, 0)
}

async function fetchAllPages(fetcher) {
  const all = []
  let currentPage = 1
  let lastPage = 1

  do {
    const { data } = await fetcher(currentPage)
    all.push(...(data.data || []))
    lastPage = data.last_page || 1
    currentPage += 1
  } while (currentPage <= lastPage)

  return all
}

async function fetchAllDiemDanhInMonth(monthValue) {
  const { tu_ngay, den_ngay } = monthRange(monthValue)
  return fetchAllPages((pageNum) =>
    fetchDiemDanh(
      { page: pageNum, per_page: 100, tu_ngay, den_ngay },
      { skipLoading: true },
    ),
  )
}

async function fetchAllNghiPhepInMonth(monthValue) {
  const { tu_ngay, den_ngay } = monthRange(monthValue)
  return fetchAllPages((pageNum) =>
    fetchXinNghiPhep(
      { page: pageNum, per_page: 100, tu_ngay, den_ngay },
      { skipLoading: true },
    ),
  )
}

async function fetchAllActiveNgayNghi() {
  return fetchAllPages((pageNum) =>
    fetchNgayNghi(
      { page: pageNum, per_page: 100, trang_thai: 'active' },
      { skipLoading: true },
    ),
  )
}

function buildNgayNghiMap(items) {
  const map = {}
  for (const item of items) {
    const start = toDateKey(item.ngay_bat_dau)
    const end = toDateKey(item.ngay_ket_thuc) || start
    for (const dateKey of eachDateKey(start, end)) {
      if (!map[dateKey]) map[dateKey] = item
    }
  }
  return map
}

function buildAttendanceMap(records) {
  const map = new Map()
  for (const record of records) {
    const userId = record.user_id
    if (!userId) continue
    const dateKey = toDateKey(record.ngay_lam)
    if (!map.has(userId)) map.set(userId, {})
    map.get(userId)[dateKey] = record
  }
  return map
}

function leavePriority(trangThai) {
  if (trangThai === 'da_duyet') return 2
  if (trangThai === 'cho_duyet') return 1
  return 0
}

function buildLeaveMap(records, monthValue) {
  const { tu_ngay, den_ngay } = monthRange(monthValue)
  const map = new Map()

  for (const record of records) {
    if (!['cho_duyet', 'da_duyet'].includes(record.trang_thai)) continue
    const userId = record.user_id
    if (!userId) continue

    const start = toDateKey(record.ngay_bat_dau)
    const end = toDateKey(record.ngay_ket_thuc) || start
    if (!map.has(userId)) map.set(userId, {})
    const userDays = map.get(userId)

    for (const dateKey of eachDateKey(start, end)) {
      if (dateKey < tu_ngay || dateKey > den_ngay) continue
      const existing = userDays[dateKey]
      if (!existing || leavePriority(record.trang_thai) >= leavePriority(existing.trang_thai)) {
        userDays[dateKey] = record
      }
    }
  }

  return map
}

function buildDayCells(userId, attendanceMap, leaveMap) {
  const days = {}
  const attendance = attendanceMap.get(userId) || {}
  const leave = leaveMap.get(userId) || {}
  const dateKeys = new Set([...Object.keys(attendance), ...Object.keys(leave)])

  for (const dateKey of dateKeys) {
    const leaveRecord = leave[dateKey] || null
    const attendanceRecord = attendance[dateKey] || null
    if (!leaveRecord && !attendanceRecord) continue
    days[dateKey] = {
      leave: leaveRecord,
      attendance: attendanceRecord,
    }
  }

  return days
}

async function loadData() {
  loading.value = true
  try {
    const [usersRes, diemDanhRecords, nghiPhepRecords, ngayNghiRecords] = await Promise.all([
      fetchUsers(
        {
          page: page.value,
          per_page: perPage.value,
          keyword: keyword.value.trim() || undefined,
          status: 'active',
        },
        { skipLoading: true },
      ),
      fetchAllDiemDanhInMonth(selectedMonth.value),
      fetchAllNghiPhepInMonth(selectedMonth.value),
      fetchAllActiveNgayNghi(),
    ])

    const users = usersRes.data?.data || []
    total.value = usersRes.data?.total || 0
    ngayNghiByDate.value = buildNgayNghiMap(ngayNghiRecords)

    const attendanceMap = buildAttendanceMap(diemDanhRecords)
    const leaveMap = buildLeaveMap(nghiPhepRecords, selectedMonth.value)

    rows.value = users.map((user) => {
      const days = buildDayCells(user.id, attendanceMap, leaveMap)
      return {
        id: user.id,
        name: user.name || '—',
        email: user.email || '',
        loai_nhan_vien: user.nhan_vien?.loai_nhan_vien || null,
        days,
        totalCoBan: sumAttendanceHours(days, 'gio_lam_co_ban'),
        totalTangCa: sumAttendanceHours(days, 'gio_lam_tang_ca'),
        totalPhat: sumPenalties(days),
      }
    })
  } catch {
    rows.value = []
    total.value = 0
    ngayNghiByDate.value = {}
  } finally {
    loading.value = false
  }
}

function onMonthChange() {
  page.value = 1
  loadData()
}

function onSearch() {
  page.value = 1
  loadData()
}

const props = defineProps({
  active: { type: Boolean, default: false },
})

watch(
  () => props.active,
  (isActive) => {
    if (isActive) loadData()
  },
  { immediate: true },
)
</script>

<style scoped>
.card-header {
  flex-wrap: wrap;
}

.card-header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.legend {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 12px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.legend__item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.legend-swatch {
  width: 14px;
  height: 14px;
  border-radius: 4px;
  border: 1px solid transparent;
}

.legend-swatch.is-ngay-nghi {
  background: color-mix(in srgb, var(--el-color-danger) 12%, var(--el-bg-color));
  border-color: color-mix(in srgb, var(--el-color-danger) 35%, var(--el-bg-color));
}

.legend__sample {
  font-size: 11px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.legend__sample--phat-muon {
  color: var(--el-color-danger);
}

.legend__sample--phat-som {
  color: var(--el-color-warning-dark-2, #b88230);
}

.employee-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  line-height: 1.3;
  padding: 2px 0;
}

.employee-cell__name-row {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
}

.employee-cell__name {
  font-weight: 500;
  color: var(--el-text-color-primary);
  opacity: 1;
}

.employee-cell__email {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.employee-type-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
  cursor: default;
}

.employee-type-icon--full {
  color: var(--el-color-primary);
}

.employee-type-icon--part {
  color: var(--el-color-warning);
}

.day-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1px;
  line-height: 1.2;
}

.day-header__num {
  font-weight: 600;
}

.day-header__dow {
  font-size: 10px;
  color: var(--el-text-color-secondary);
  font-weight: 400;
}

.day-header--weekend .day-header__num,
.day-header--weekend .day-header__dow {
  color: var(--el-color-danger);
}

.day-header--ngay-nghi .day-header__num,
.day-header--ngay-nghi .day-header__dow {
  color: var(--el-color-danger);
  font-weight: 700;
}

.day-cell {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  min-height: 42px;
  padding: 2px 0 10px;
  line-height: 1.2;
}

.day-cell__body {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1px;
}

.day-cell__empty {
  color: var(--el-text-color-placeholder);
  font-size: 12px;
}

.time-row {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 3px;
  white-space: nowrap;
}

.time-row__sep {
  font-size: 10px;
  color: var(--el-text-color-placeholder);
  font-weight: 400;
}

.time-line {
  font-size: 11px;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.time-line--ok {
  color: var(--el-color-success);
}

.time-line--late {
  color: var(--el-color-danger);
}

.time-line--early {
  color: var(--el-color-warning-dark-2, #b88230);
}

.time-line--partial {
  color: var(--el-color-warning);
}

.time-line--muted {
  color: var(--el-text-color-placeholder);
  font-weight: 400;
}

.cell-mark {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  vertical-align: middle;
  cursor: default;
  box-sizing: border-box;
}

.cell-mark--corner {
  position: absolute;
  top: 1px;
  right: 0px;
  z-index: 1;
  width: 8px;
  height: 8px;
}

.cell-mark--leave-approved {
  background: var(--el-color-primary);
}

.cell-mark--leave-pending {
  background: transparent;
  border: 2px solid var(--el-color-primary);
}

.total-hours {
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  color: var(--el-text-color-primary);
}

.total-hours--ot {
  color: var(--el-color-warning-dark-2, #b88230);
}

.total-hours--phat {
  color: var(--el-color-danger);
}

.penalty-line {
  font-size: 9px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  line-height: 1;
  white-space: nowrap;
}

.penalty-line--corner-bl,
.penalty-line--corner-br {
  position: absolute;
  bottom: 0;
  z-index: 1;
  cursor: default;
}

.penalty-line--corner-bl {
  left: 2px;
}

.penalty-line--corner-br {
  right: 2px;
}

.penalty-line--muon {
  color: var(--el-color-danger);
}

.penalty-line--som {
  color: var(--el-color-warning-dark-2, #b88230);
}

.proxy-btn {
  margin-top: 0;
  padding: 0 !important;
  height: auto !important;
  min-height: 0 !important;
  font-size: 14px !important;
  line-height: 1;
}

.attendance-table :deep(.is-weekend-col) {
  background-color: var(--el-fill-color-lighter) !important;
}

.attendance-table :deep(.el-table__header .is-weekend-col) {
  background-color: var(--el-fill-color) !important;
}

/* Dùng nền đặc (không transparent) để stripe/hover hàng không làm lệch màu cột ngày nghỉ */
.attendance-table :deep(td.is-ngay-nghi-col),
.attendance-table :deep(.el-table__body tr.el-table__row--striped td.is-ngay-nghi-col),
.attendance-table :deep(.el-table__body tr:hover > td.is-ngay-nghi-col),
.attendance-table :deep(.el-table__body tr.hover-row > td.is-ngay-nghi-col),
.attendance-table :deep(.el-table__body tr.current-row > td.is-ngay-nghi-col) {
  background-color: color-mix(in srgb, var(--el-color-danger) 12%, var(--el-bg-color)) !important;
}

.attendance-table :deep(.el-table__header th.is-ngay-nghi-col) {
  background-color: color-mix(in srgb, var(--el-color-danger) 18%, var(--el-bg-color)) !important;
}

.attendance-table :deep(.el-table__cell) {
  padding: 4px 0;
}

/* Cột fixed trái/phải: nền đặc để không lộ ô phía sau khi cuộn ngang */
.attendance-table :deep(.el-table-fixed-column--left),
.attendance-table :deep(.el-table-fixed-column--right) {
  background-color: var(--el-bg-color) !important;
  opacity: 1;
}

.attendance-table :deep(.el-table__body tr.el-table__row--striped .el-table-fixed-column--left),
.attendance-table :deep(.el-table__body tr.el-table__row--striped .el-table-fixed-column--right) {
  background-color: var(--el-fill-color-lighter) !important;
}

.attendance-table :deep(.el-table__body tr:hover > .el-table-fixed-column--left),
.attendance-table :deep(.el-table__body tr:hover > .el-table-fixed-column--right),
.attendance-table :deep(.el-table__body tr.hover-row > .el-table-fixed-column--left),
.attendance-table :deep(.el-table__body tr.hover-row > .el-table-fixed-column--right) {
  background-color: var(--el-table-row-hover-bg-color, var(--el-fill-color-light)) !important;
}

.attendance-table :deep(.el-table__header .el-table-fixed-column--left),
.attendance-table :deep(.el-table__header .el-table-fixed-column--right) {
  background-color: var(--el-table-header-bg-color, var(--el-fill-color-light)) !important;
}
</style>
