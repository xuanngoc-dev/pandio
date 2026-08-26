<template>
  <div class="lich-hau-ky-calendar">
    <CustomCard shadow="hover" class="calendar-card" v-loading="loading">
      <template #header>
        <div class="card-header">
          <span class="card-title">Lịch hậu kỳ</span>
          <div class="legend">
            <span class="legend-swatch is-ngay-nghi" />
            <span class="legend-text">Ngày nghỉ</span>
            <span class="legend-swatch is-file-le" />
            <span class="legend-text">Trả file lẻ</span>
            <span class="legend-swatch is-file-in" />
            <span class="legend-text">Trả file in</span>
            <span class="legend-swatch is-khach" />
            <span class="legend-text">Khách hẹn qua</span>
          </div>
        </div>
      </template>

      <el-calendar v-model="selectedDate">
        <template #header>
          <div class="el-calendar__title">{{ calendarTitle }}</div>
          <div class="el-calendar__button-group">
            <el-button-group>
              <el-button size="small" @click="shiftMonth(-1)">Tháng trước</el-button>
              <el-button size="small" @click="goToday">Hôm nay</el-button>
              <el-button size="small" @click="shiftMonth(1)">Tháng tới</el-button>
            </el-button-group>
          </div>
        </template>
        <template #date-cell="{ data }">
          <div
            v-if="data.type === 'current-month'"
            class="day-cell"
            :class="{
              'is-ngay-nghi': isNgayNghi(data.day),
              'is-lunar-month-start': isLunarMonthStart(data.day),
              'is-clickable': hasCounts(data.day),
            }"
            :title="dayCellTitle(data.day)"
            @click="openDayChiTiet(data.day)"
          >
            <div class="day-head">
              <span class="day-solar">{{ data.day.split('-').pop() }}</span>
              <span class="day-lunar">{{ formatLunarLabel(data.day) }}</span>
            </div>
            <span v-if="isNgayNghi(data.day)" class="day-nghi-label">
              {{ ngayNghiLabel(data.day) }}
            </span>
            <div v-if="hasCounts(data.day)" class="day-counts">
              <button
                v-for="row in countRows(data.day)"
                :key="row.key"
                type="button"
                class="day-count"
                :class="row.className"
                :title="`${row.label}: ${row.count}`"
                @click.stop="openChiTiet(data.day, row.key)"
              >
                {{ row.label }}: {{ row.count }}
              </button>
            </div>
          </div>
        </template>
      </el-calendar>
    </CustomCard>

    <LichHauKyChiTietModal
      v-model="chiTietVisible"
      :ngay="chiTietNgay"
      :loai="chiTietLoai"
      :counts="countsByDate(chiTietNgay)"
      @saved="loadLichHauKy"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { fetchLichHauKy } from '@/api/hopDongSuDungDichVu'
import { fetchNgayNghi } from '@/api/ngayNghi'
import { formatLunarLabel, isLunarMonthStart } from '@/utils/lunar'
import LichHauKyChiTietModal from './LichHauKyChiTietModal.vue'

const COUNT_ROWS = [
  { key: 'ngay_tra_file_le', countKey: 'tra_file_le', label: 'Trả file lẻ', className: 'is-file-le' },
  { key: 'ngay_tra_file_in', countKey: 'tra_file_in', label: 'Trả file in', className: 'is-file-in' },
  { key: 'ngay_khach_hen_qua', countKey: 'khach_qua', label: 'Khách hẹn qua', className: 'is-khach' },
]

const EMPTY_COUNTS = {
  tra_file_le: 0,
  tra_file_in: 0,
  khach_qua: 0,
}

/** Mặc định: tháng hiện tại */
const selectedDate = ref(new Date())
const loadingNgayNghi = ref(false)
const loadingHauKy = ref(false)

const loading = computed(() => loadingNgayNghi.value || loadingHauKy.value)

/** Tiêu đề dạng: Tháng N năm M */
const calendarTitle = computed(() => {
  const d = selectedDate.value instanceof Date
    ? selectedDate.value
    : new Date(selectedDate.value)
  return `Tháng ${d.getMonth() + 1} năm ${d.getFullYear()}`
})

function toSelectedDate() {
  return selectedDate.value instanceof Date
    ? selectedDate.value
    : new Date(selectedDate.value)
}

function shiftMonth(delta) {
  const d = toSelectedDate()
  selectedDate.value = new Date(d.getFullYear(), d.getMonth() + delta, 1)
}

function goToday() {
  selectedDate.value = new Date()
}

/** Map YYYY-MM-DD → cấu hình ngày nghỉ active */
const ngayNghiByDate = ref({})

/**
 * Map YYYY-MM-DD → { tra_file_le, tra_file_in, khach_qua }
 * @type {import('vue').Ref<Record<string, { tra_file_le: number, tra_file_in: number, khach_qua: number }>>}
 */
const hauKyByDateMap = ref({})
const chiTietVisible = ref(false)
const chiTietNgay = ref('')
const chiTietLoai = ref('ngay_tra_file_le')

function toYmd(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

function monthRange(date) {
  const d = date instanceof Date ? date : new Date(date)
  const first = new Date(d.getFullYear(), d.getMonth(), 1)
  const last = new Date(d.getFullYear(), d.getMonth() + 1, 0)
  return { tu_ngay: toYmd(first), den_ngay: toYmd(last) }
}

/**
 * Parse YYYY-MM-DD (hoặc datetime) thành Date local, tránh lệch timezone.
 * @param {string} value
 */
function parseLocalDate(value) {
  const raw = String(value || '').slice(0, 10)
  const [y, m, d] = raw.split('-').map(Number)
  if (!y || !m || !d) return null
  return new Date(y, m - 1, d)
}

/**
 * @param {Array<{ ngay_bat_dau: string, ngay_ket_thuc: string, ten_ngay_nghi: string }>} items
 */
function buildNgayNghiMap(items) {
  const map = {}

  for (const item of items) {
    const start = parseLocalDate(item.ngay_bat_dau)
    const end = parseLocalDate(item.ngay_ket_thuc)
    if (!start || !end || start > end) continue

    const cursor = new Date(start)
    while (cursor <= end) {
      const key = toYmd(cursor)
      if (!map[key]) {
        map[key] = item
      }
      cursor.setDate(cursor.getDate() + 1)
    }
  }

  return map
}

async function loadActiveNgayNghi() {
  loadingNgayNghi.value = true
  try {
    const all = []
    let page = 1
    let lastPage = 1

    do {
      const { data } = await fetchNgayNghi({
        page,
        per_page: 100,
        trang_thai: 'active',
      })
      all.push(...(data.data || []))
      lastPage = data.last_page || 1
      page += 1
    } while (page <= lastPage)

    ngayNghiByDate.value = buildNgayNghiMap(all)
  } catch {
    ngayNghiByDate.value = {}
  } finally {
    loadingNgayNghi.value = false
  }
}

/**
 * @param {Array<{ ngay: string, tra_file_le?: number, tra_file_in?: number, khach_qua?: number }>} items
 */
function buildHauKyByDateMap(items) {
  const map = {}
  for (const row of items || []) {
    const key = dayKey(row.ngay)
    if (!key) continue
    map[key] = {
      tra_file_le: Number(row.tra_file_le) || 0,
      tra_file_in: Number(row.tra_file_in) || 0,
      khach_qua: Number(row.khach_qua) || 0,
    }
  }
  return map
}

async function loadLichHauKy() {
  loadingHauKy.value = true
  try {
    const { data } = await fetchLichHauKy(monthRange(toSelectedDate()))
    hauKyByDateMap.value = buildHauKyByDateMap(data?.items || [])
  } catch {
    hauKyByDateMap.value = {}
  } finally {
    loadingHauKy.value = false
  }
}

function dayKey(day) {
  return String(day || '').slice(0, 10)
}

function countsByDate(day) {
  return hauKyByDateMap.value[dayKey(day)] || EMPTY_COUNTS
}

function countRows(day) {
  const counts = countsByDate(day)
  return COUNT_ROWS.filter((row) => counts[row.countKey] > 0).map((row) => ({
    ...row,
    count: counts[row.countKey],
  }))
}

function hasCounts(day) {
  const counts = countsByDate(day)
  return counts.tra_file_le > 0 || counts.tra_file_in > 0 || counts.khach_qua > 0
}

function openChiTiet(day, loai) {
  chiTietNgay.value = dayKey(day)
  chiTietLoai.value = loai
  chiTietVisible.value = true
}

function openDayChiTiet(day) {
  const first = countRows(day)[0]
  if (!first) return
  openChiTiet(day, first.key)
}

function isNgayNghi(day) {
  return Boolean(ngayNghiByDate.value[dayKey(day)])
}

function ngayNghiLabel(day) {
  return ngayNghiByDate.value[dayKey(day)]?.ten_ngay_nghi || 'Nghỉ'
}

function dayCellTitle(day) {
  const parts = []
  const nghi = ngayNghiByDate.value[dayKey(day)]
  if (nghi) parts.push(`Ngày nghỉ: ${nghi.ten_ngay_nghi}`)

  const counts = countsByDate(day)
  if (counts.tra_file_le > 0) parts.push(`Trả file lẻ: ${counts.tra_file_le}`)
  if (counts.tra_file_in > 0) parts.push(`Trả file in: ${counts.tra_file_in}`)
  if (counts.khach_qua > 0) parts.push(`Khách hẹn qua: ${counts.khach_qua}`)

  return parts.join(' | ')
}

watch(
  () => {
    const d = toSelectedDate()
    return `${d.getFullYear()}-${d.getMonth()}`
  },
  () => {
    loadLichHauKy()
  },
)

onMounted(() => {
  loadActiveNgayNghi()
  loadLichHauKy()
})
</script>

<style scoped lang="scss">
.lich-hau-ky-calendar {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  width: 100%;
}

.legend {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.legend-swatch {
  width: 14px;
  height: 14px;
  border-radius: 4px;
  border: 1px solid transparent;
  flex-shrink: 0;

  &.is-ngay-nghi {
    background: color-mix(in srgb, var(--el-color-danger) 16%, transparent);
    border-color: color-mix(in srgb, var(--el-color-danger) 35%, transparent);
  }

  &.is-file-le {
    background: color-mix(in srgb, var(--el-color-primary) 18%, transparent);
    border-color: color-mix(in srgb, var(--el-color-primary) 40%, transparent);
  }

  &.is-file-in {
    background: color-mix(in srgb, var(--el-color-warning) 18%, transparent);
    border-color: color-mix(in srgb, var(--el-color-warning) 40%, transparent);
  }

  &.is-khach {
    background: color-mix(in srgb, var(--el-color-success) 18%, transparent);
    border-color: color-mix(in srgb, var(--el-color-success) 40%, transparent);
  }
}

.legend-text {
  margin-right: 4px;
}

.calendar-card {
  :deep(.el-calendar-table .el-calendar-day) {
    height: 128px;
    padding: 4px;
  }

  /* Ẩn hoàn toàn ngày không thuộc tháng đang xem */
  :deep(.el-calendar-table td.prev),
  :deep(.el-calendar-table td.next) {
    pointer-events: none;

    .el-calendar-day {
      visibility: hidden;
      cursor: default;
    }
  }
}

.day-cell {
  height: 100%;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 4px 6px;
  border-radius: 6px;
  line-height: 1.2;
  overflow: hidden;

  &.is-clickable {
    cursor: pointer;
  }

  &.is-ngay-nghi {
    background: color-mix(in srgb, var(--el-color-danger) 14%, transparent);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--el-color-danger) 28%, transparent);
  }

  &.is-lunar-month-start {
    .day-lunar {
      color: #a8071a;
      font-weight: 700;
    }
  }
}

.day-head {
  display: flex;
  align-items: baseline;
  gap: 6px;
}

.day-solar {
  font-size: 14px;
  font-weight: 500;
  color: var(--el-text-color-primary);
}

.day-lunar {
  font-size: 11px;
  color: var(--el-text-color-secondary);
}

.day-nghi-label {
  font-size: 11px;
  line-height: 1.3;
  color: var(--el-color-danger);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex-shrink: 0;
}

.day-cell.is-ngay-nghi .day-solar {
  color: var(--el-color-danger);
}

.day-counts {
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow: hidden;
  flex: 1;
}

.day-count {
  display: flex;
  align-items: center;
  width: 100%;
  height: 20px;
  padding: 0 6px;
  border: none;
  border-radius: 4px;
  font: inherit;
  font-size: 10px;
  font-weight: 600;
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  cursor: pointer;
  box-sizing: border-box;

  &:hover {
    filter: brightness(0.96);
  }

  &.is-file-le {
    color: var(--el-color-primary);
    background: color-mix(in srgb, var(--el-color-primary) 12%, transparent);
  }

  &.is-file-in {
    color: var(--el-color-warning-dark-2, #b88230);
    background: color-mix(in srgb, var(--el-color-warning) 16%, transparent);
  }

  &.is-khach {
    color: var(--el-color-success);
    background: color-mix(in srgb, var(--el-color-success) 12%, transparent);
  }
}
</style>
