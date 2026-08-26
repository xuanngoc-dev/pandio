<template>
  <div class="lich-khach-hang-calendar">
    <LichKhachHangDateFilter v-model="dateRange" />

    <CustomCard shadow="hover" class="calendar-card" v-loading="loading">
      <template #header>
        <div class="card-header">
          <span class="card-title">Lịch khách hàng</span>
          <div class="legend legend-meta">
            <span class="legend-swatch is-ngay-nghi" />
            <span class="legend-text">Ngày nghỉ</span>
            <template v-for="item in loaiSuKienLegend" :key="item.id">
              <span
                class="legend-swatch"
                :style="{
                  background: `${loaiColorMap[item.id] || '#909399'}29`,
                  borderColor: `${loaiColorMap[item.id] || '#909399'}66`,
                }"
              />
              <span class="legend-text">{{ item.ten }}</span>
            </template>
          </div>
        </div>
      </template>

      <el-calendar
        :key="calendarRenderKey"
        v-model="selectedDate"
      >
        <template #header>
          <div class="el-calendar__title">{{ calendarTitle }}</div>
          <div class="el-calendar__button-group">
            <el-button-group>
              <el-button
                size="small"
                :icon="ArrowLeft"
                title="Tháng trước"
                aria-label="Tháng trước"
                @click="shiftMonth(-1)"
              />
              <el-button
                size="small"
                :icon="Calendar"
                title="Hôm nay"
                aria-label="Hôm nay"
                @click="goToday"
              />
              <el-button
                size="small"
                :icon="ArrowRight"
                title="Tháng tới"
                aria-label="Tháng tới"
                @click="shiftMonth(1)"
              />
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
              'is-out-of-range': isOutOfRange(data.day),
              'is-clickable': hasCounts(data.day),
            }"
            :title="dayCellTitle(data.day)"
            @click="openDayChiTiet(data.day)"
          >
            <div class="day-head">
              <span class="day-solar" :class="{ 'is-today': isToday(data.day) }">
                {{ data.day.split('-').pop() }}
              </span>
              <CustomTooltip
                v-if="formatLunarTooltip(data.day)"
                :content="formatLunarTooltip(data.day)"
                placement="top"
              >
                <span class="day-lunar" @click.stop>
                  {{ formatLunarLabel(data.day) }}
                </span>
              </CustomTooltip>
              <span v-else class="day-lunar">{{ formatLunarLabel(data.day) }}</span>
              <CustomTooltip
                v-if="thoiTietIconUrl(data.day)"
                placement="top"
              >
                <template #content>
                  <div class="day-weather-tooltip">
                    <div class="day-weather-tooltip__desc">{{ thoiTietMoTa(data.day) }}</div>
                    <div v-if="thoiTietByDate(data.day)?.dia_diem">
                      Địa điểm: {{ thoiTietByDate(data.day).dia_diem }}
                    </div>
                    <div v-if="thoiTietNhietDoLabel(data.day)">
                      Nhiệt độ: {{ thoiTietNhietDoLabel(data.day) }}
                    </div>
                    <div v-if="thoiTietByDate(data.day)?.ty_le_mua != null">
                      Tỷ lệ mưa: {{ thoiTietByDate(data.day).ty_le_mua }}%
                    </div>
                    <div>{{ thoiTietGioLabel(data.day) }}</div>
                  </div>
                </template>
                <span class="day-weather" style="cursor: pointer;" @click.stop>
                  <img
                    class="day-weather-icon"
                    :src="thoiTietIconUrl(data.day)"
                    :alt="thoiTietByDate(data.day)?.mo_ta || 'Thời tiết'"
                  />
                  <span v-if="thoiTietNhietDoLabel(data.day)" class="day-weather-temp">
                    {{ thoiTietNhietDoLabel(data.day) }}
                  </span>
                </span>
              </CustomTooltip>
            </div>
            <span
              v-if="isNgayNghi(data.day)"
              class="day-nghi-label"
              :title="ngayNghiLabel(data.day)"
            >
              {{ ngayNghiLabel(data.day) }}
            </span>
            <span
              v-if="isNgayNghi(data.day)"
              class="day-nghi-dot"
              :title="ngayNghiLabel(data.day)"
              aria-label="Ngày nghỉ"
            />
            <div v-if="hasCounts(data.day)" class="day-counts day-counts--list">
              <button
                v-for="row in countRows(data.day)"
                :key="row.key"
                type="button"
                class="day-count"
                :style="{
                  '--status-color': loaiColorMap[row.key] || '#909399',
                  background: `${loaiColorMap[row.key] || '#909399'}26`,
                }"
                :title="`${row.label}: ${row.count}`"
                @click.stop="openChiTiet(data.day, row.key)"
              >
                {{ row.label }}: {{ row.count }}
              </button>
            </div>
            <button
              v-if="hasCounts(data.day)"
              type="button"
              class="day-counts day-counts--dots"
              :title="dayCellTitle(data.day)"
              @click.stop="openDayChiTiet(data.day)"
            >
              <span
                v-for="row in countRows(data.day)"
                :key="row.key"
                class="day-dot"
                :style="{ background: loaiColorMap[row.key] || '#909399' }"
                :title="`${row.label}: ${row.count}`"
              />
            </button>
          </div>
        </template>
      </el-calendar>

      <div v-if="loaiSuKienLegend.length" class="loai-config">
        <div class="loai-config-head">
          <span class="loai-config-title">Ghi chú trạng thái</span>
          <div class="loai-config-actions">
            <span class="loai-config-hint">Bấm màu để đổi · bật/tắt để hiện/ẩn trên lịch</span>
            <CustomTooltip content="Khôi phục toàn bộ màu mặc định" placement="top">
              <el-button
                class="loai-color-reset-all"
                text
                size="small"
                :disabled="!hasCustomLoaiColor"
                aria-label="Khôi phục toàn bộ màu mặc định"
                @click="resetAllLoaiColors"
              >
                <el-icon :size="16"><Refresh /></el-icon>
              </el-button>
            </CustomTooltip>
          </div>
        </div>
        <div class="loai-config-list">
          <div
            v-for="item in loaiSuKienLegend"
            :key="item.id"
            class="loai-config-item"
            :class="{ 'is-hidden': !loaiVisibleMap[item.id] }"
          >
            <el-switch
              :model-value="loaiVisibleMap[item.id]"
              size="small"
              inline-prompt
              active-text="Hiện"
              inactive-text="Ẩn"
              @change="(visible) => setLoaiVisible(item.id, visible)"
            />
            <el-color-picker
              :model-value="loaiColorMap[item.id]"
              color-format="hex"
              :predefine="PREDEFINE_COLORS"
              @change="(color) => setLoaiColor(item.id, color)"
            />
            <span class="loai-config-name" :title="item.ten">
              {{ item.ten }}
            </span>
          </div>
        </div>
      </div>
    </CustomCard>

    <LichKhachHangChiTietModal
      v-model="chiTietVisible"
      :ngay="chiTietNgay"
      :trang-thai="chiTietTrangThai"
      :counts="countsByDate(chiTietNgay)"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { ArrowLeft, ArrowRight, Calendar, Refresh } from '@element-plus/icons-vue'
import { fetchNgayNghi } from '@/api/ngayNghi'
import { fetchLichKhachHang } from '@/api/noteKhachMoi'
import { fetchTienIchThoiTiet, weatherIconUrl } from '@/api/thoiTiet'
import { formatLunarLabel, formatLunarTooltip, isLunarMonthStart } from '@/utils/lunar'
import LichKhachHangChiTietModal from './LichKhachHangChiTietModal.vue'
import LichKhachHangDateFilter from './LichKhachHangDateFilter.vue'
import {
  dayKey,
  getPresetRange,
  monthRangeOf,
  parseLocalDate,
  toYmd,
} from './lichKhachHangDate'

const PREFS_STORAGE_KEY = 'pandio.lichKhachHang.trangThaiPrefs'

const TRANG_THAI_OPTIONS = [
  { value: 'cho_hen', label: 'Chờ hẹn', color: '#5c6bc0' },
  { value: 'da_den', label: 'Đã đến', color: '#43a047' },
  { value: 'khong_den', label: 'Không đến', color: '#fb8c00' },
  { value: 'da_ky_hd', label: 'Đã ký HĐ', color: '#1e88e5' },
  { value: 'da_huy', label: 'Đã hủy', color: '#e53935' },
]

const EMPTY_COUNTS = Object.fromEntries(TRANG_THAI_OPTIONS.map((opt) => [opt.value, 0]))

const TRANG_THAI_COLOR_BY_ID = Object.fromEntries(
  TRANG_THAI_OPTIONS.map((opt) => [opt.value, opt.color]),
)

const FALLBACK_COLORS = [
  '#1e88e5',
  '#43a047',
  '#fb8c00',
  '#8e24aa',
  '#e53935',
  '#00acc1',
]

const PREDEFINE_COLORS = [
  ...FALLBACK_COLORS,
  '#1565c0',
  '#2e7d32',
  '#ef6c00',
  '#6a1b9a',
  '#c62828',
  '#00838f',
]

const dateRange = defineModel('dateRange', {
  type: Array,
  default: () => getPresetRange('this_month'),
})

/** Tháng đang hiện trên el-calendar */
const selectedDate = ref(new Date())
const loadingNgayNghi = ref(false)
const loadingSuKien = ref(false)
const loadingThoiTiet = ref(false)
/** @type {import('vue').Ref<Record<string, object>>} */
const thoiTietMap = ref({})

const loading = computed(
  () => loadingNgayNghi.value || loadingSuKien.value || loadingThoiTiet.value,
)

/** Tiêu đề dạng: Tháng N năm M */
const calendarTitle = computed(() => {
  const d = selectedDate.value instanceof Date
    ? selectedDate.value
    : new Date(selectedDate.value)
  return `${d.getMonth() + 1}/${d.getFullYear()}`
})

function toSelectedDate() {
  return selectedDate.value instanceof Date
    ? selectedDate.value
    : new Date(selectedDate.value)
}

function shiftMonth(delta) {
  const d = toSelectedDate()
  const next = new Date(d.getFullYear(), d.getMonth() + delta, 1)
  dateRange.value = monthRangeOf(next)
}

function goToday() {
  dateRange.value = getPresetRange('this_month')
  selectedDate.value = new Date()
}

/** Map YYYY-MM-DD → cấu hình ngày nghỉ active */
const ngayNghiByDate = ref({})

/**
 * Map YYYY-MM-DD → danh sách sự kiện (Hẹn lịch / Đến)
 */
const suKienByDateMap = ref({})

/** Danh sách loại sự kiện (màu mặc định theo id) */
const loaiSuKienLegend = ref([])

/** Modal chi tiết theo ngày */
const chiTietVisible = ref(false)
const chiTietNgay = ref('')
const chiTietTrangThai = ref('cho_hen')

/**
 * Tuỳ chọn người dùng theo loai:
 * { [id]: { color?: string, visible?: boolean } }
 */
const loaiPrefs = ref({})

function loadLoaiPrefs() {
  try {
    const raw = localStorage.getItem(PREFS_STORAGE_KEY)
    if (!raw) return
    const parsed = JSON.parse(raw)
    if (!parsed || typeof parsed !== 'object') return
    const next = {}
    for (const [id, value] of Object.entries(parsed)) {
      if (!value || typeof value !== 'object') continue
      next[id] = {
        color: typeof value.color === 'string' ? value.color : undefined,
        visible: typeof value.visible === 'boolean' ? value.visible : undefined,
      }
    }
    loaiPrefs.value = next
  } catch {
    // ignore corrupt storage
  }
}

function persistLoaiPrefs() {
  localStorage.setItem(PREFS_STORAGE_KEY, JSON.stringify(loaiPrefs.value))
}

function prefKey(loaiId) {
  return String(loaiId)
}

function patchLoaiPref(loaiId, patch) {
  const key = prefKey(loaiId)
  loaiPrefs.value = {
    ...loaiPrefs.value,
    [key]: {
      ...loaiPrefs.value[key],
      ...patch,
    },
  }
  persistLoaiPrefs()
}

function isLoaiVisible(loaiId) {
  return loaiPrefs.value[prefKey(loaiId)]?.visible !== false
}

function resolveLoaiColor(item) {
  const pref = loaiPrefs.value[prefKey(item.id)]
  if (pref?.color) return pref.color
  return item.defaultColor
}

/** Map id → màu đang dùng (prefs hoặc mặc định) */
const loaiColorMap = computed(() => {
  const map = {}
  for (const item of loaiSuKienLegend.value) {
    map[item.id] = resolveLoaiColor(item)
  }
  return map
})

/** Map id → đang hiện trên lịch */
const loaiVisibleMap = computed(() => {
  const map = {}
  for (const item of loaiSuKienLegend.value) {
    map[item.id] = isLoaiVisible(item.id)
  }
  return map
})

/** Ép el-calendar render lại khi đổi màu / ẩn hiện */
const calendarRenderKey = computed(() => {
  const prefs = loaiPrefs.value
  return Object.keys(prefs)
    .sort()
    .map((id) => `${id}:${prefs[id]?.color || ''}:${prefs[id]?.visible !== false ? 1 : 0}`)
    .join('|')
})

function setLoaiColor(loaiId, color) {
  if (!color) return
  patchLoaiPref(loaiId, { color })
}

/** Có ít nhất một loại đang dùng màu tuỳ chỉnh */
const hasCustomLoaiColor = computed(() =>
  Object.values(loaiPrefs.value).some((pref) => Boolean(pref?.color)),
)

function resetAllLoaiColors() {
  if (!hasCustomLoaiColor.value) return

  const next = {}
  for (const [key, pref] of Object.entries(loaiPrefs.value)) {
    if (!pref || typeof pref !== 'object') continue
    if (typeof pref.visible === 'boolean') {
      next[key] = { visible: pref.visible }
    }
  }
  loaiPrefs.value = next
  persistLoaiPrefs()
}

function setLoaiVisible(loaiId, visible) {
  patchLoaiPref(loaiId, { visible: Boolean(visible) })
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

function colorForLoai(loaiId, index) {
  if (TRANG_THAI_COLOR_BY_ID[loaiId]) return TRANG_THAI_COLOR_BY_ID[loaiId]
  return FALLBACK_COLORS[index % FALLBACK_COLORS.length]
}

/**
 * Gom note theo ngày (unique theo id). Ngày = hẹn lịch hoặc ngày đến.
 * @param {Array<object>} items
 */
function buildCalendarData(items) {
  const legend = TRANG_THAI_OPTIONS.map((opt, index) => ({
    id: opt.value,
    ten: opt.label,
    defaultColor: colorForLoai(opt.value, index),
  }))

  const map = {}
  for (const row of items || []) {
    const key = dayKey(row.ngay)
    if (!key) continue
    if (!map[key]) map[key] = []
    if (map[key].some((item) => item.id === row.id)) continue
    map[key].push(row)
  }

  return { map, legend }
}

function currentRange() {
  const range = dateRange.value
  if (Array.isArray(range) && range[0] && range[1]) {
    return { tu_ngay: range[0], den_ngay: range[1] }
  }
  const [tu_ngay, den_ngay] = monthRangeOf(toSelectedDate())
  return { tu_ngay, den_ngay }
}

function syncSelectedDateFromRange() {
  const { tu_ngay, den_ngay } = currentRange()
  const today = new Date()
  const todayKey = toYmd(today)
  if (todayKey >= tu_ngay && todayKey <= den_ngay) {
    selectedDate.value = today
    return
  }
  selectedDate.value = parseLocalDate(tu_ngay) || new Date()
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

async function loadLichKhachHang() {
  loadingSuKien.value = true
  try {
    const { tu_ngay, den_ngay } = currentRange()
    const { data } = await fetchLichKhachHang({ tu_ngay, den_ngay })
    const { map, legend } = buildCalendarData(data?.items || [])
    suKienByDateMap.value = map
    loaiSuKienLegend.value = legend
  } catch {
    suKienByDateMap.value = {}
    loaiSuKienLegend.value = []
  } finally {
    loadingSuKien.value = false
  }
}

/**
 * @param {Array<object>} rows
 * @returns {Record<string, object>}
 */
function buildThoiTietMap(rows) {
  const map = {}
  for (const row of rows || []) {
    const key = dayKey(row.ngay)
    if (!key) continue
    map[key] = row
  }
  return map
}

async function loadThoiTiet() {
  loadingThoiTiet.value = true
  try {
    const { tu_ngay, den_ngay } = currentRange()
    const { data } = await fetchTienIchThoiTiet({
      tu_ngay,
      den_ngay,
      per_page: 100,
    })
    thoiTietMap.value = buildThoiTietMap(data?.data || [])
  } catch {
    thoiTietMap.value = {}
  } finally {
    loadingThoiTiet.value = false
  }
}

function thoiTietByDate(day) {
  return thoiTietMap.value[dayKey(day)] || null
}

function thoiTietIconUrl(day) {
  const item = thoiTietByDate(day)
  return weatherIconUrl(item?.icon_code) || ''
}

function thoiTietMoTa(day) {
  const item = thoiTietByDate(day)
  if (!item) return ''
  const moTa = item.mo_ta || item.icon || 'Thời tiết'
  return moTa.charAt(0).toUpperCase() + moTa.slice(1)
}

function thoiTietGioLabel(day) {
  const item = thoiTietByDate(day)
  if (!item || item.toc_do_gio == null || item.toc_do_gio === '') {
    return 'Tốc độ gió: —'
  }
  return `Tốc độ gió: ${Number(item.toc_do_gio).toFixed(1)} m/s`
}

function thoiTietNhietDoLabel(day) {
  const item = thoiTietByDate(day)
  if (!item) return ''
  const min = item.nhiet_do_min
  const max = item.nhiet_do_max
  if (min == null && max == null) return ''
  if (min != null && max != null) return `${min}-${max}°C`
  if (max != null) return `${max}°C`
  return `${min}°C`
}

function suKienByDate(day) {
  return suKienByDateMap.value[dayKey(day)] || []
}

function suKienVisibleByDate(day) {
  const visibleMap = loaiVisibleMap.value
  return suKienByDate(day).filter((item) => {
    const status = item.trang_thai || 'cho_hen'
    return visibleMap[status] !== false
  })
}

function countsByDate(day) {
  const counts = { ...EMPTY_COUNTS }
  for (const item of suKienVisibleByDate(day)) {
    const status = item.trang_thai
    if (Object.prototype.hasOwnProperty.call(counts, status)) {
      counts[status] += 1
    }
  }
  return counts
}

function countRows(day) {
  const counts = countsByDate(day)
  return TRANG_THAI_OPTIONS
    .filter((opt) => counts[opt.value] > 0)
    .map((opt) => ({
      key: opt.value,
      label: opt.label,
      count: counts[opt.value],
    }))
}

function hasCounts(day) {
  return countRows(day).length > 0
}

function openChiTiet(day, trangThai) {
  chiTietNgay.value = dayKey(day)
  chiTietTrangThai.value = trangThai
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

function isToday(day) {
  return dayKey(day) === toYmd(new Date())
}

function isOutOfRange(day) {
  const { tu_ngay, den_ngay } = currentRange()
  const key = dayKey(day)
  return key < tu_ngay || key > den_ngay
}

function ngayNghiLabel(day) {
  return ngayNghiByDate.value[dayKey(day)]?.ten_ngay_nghi || 'Nghỉ'
}

function dayCellTitle(day) {
  const parts = []
  const nghi = ngayNghiByDate.value[dayKey(day)]
  if (nghi) parts.push(`Ngày nghỉ: ${nghi.ten_ngay_nghi}`)

  const counts = countsByDate(day)
  for (const opt of TRANG_THAI_OPTIONS) {
    if (counts[opt.value] > 0) {
      parts.push(`${opt.label}: ${counts[opt.value]}`)
    }
  }

  return parts.join(' | ')
}

watch(
  () => {
    const range = dateRange.value
    return Array.isArray(range) ? `${range[0]}|${range[1]}` : ''
  },
  () => {
    syncSelectedDateFromRange()
    loadLichKhachHang()
    loadThoiTiet()
  },
)

onMounted(() => {
  loadLoaiPrefs()
  syncSelectedDateFromRange()
  loadActiveNgayNghi()
  loadLichKhachHang()
  loadThoiTiet()
})
</script>

<style scoped lang="scss">
.lich-khach-hang-calendar {
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
}

.calendar-card {
  :deep(.el-calendar-table .el-calendar-day) {
    height: 148px;
    padding: 2px;
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

  @media (max-width: 767px) {
    :deep(.el-card__header) {
      padding: 10px 8px;
    }

    :deep(.el-card__body) {
      padding: 4px 2px 10px;
    }

    :deep(.el-calendar) {
      --el-calendar-border: transparent;
    }

    :deep(.el-calendar__header) {
      flex-wrap: nowrap;
      gap: 8px;
      padding: 6px 2px 8px;
      border-bottom: none;
    }

    :deep(.el-calendar__title) {
      font-size: 14px;
      white-space: nowrap;
    }

    :deep(.el-calendar__body) {
      padding: 0;
    }

    :deep(.el-calendar-table) {
      th {
        padding: 4px 0;
        font-size: 12px;
        border: none;
      }

      td {
        border: none;
      }

      tr:first-child td {
        border-top: none;
      }
    }

    :deep(.el-calendar-table .el-calendar-day) {
      height: 68px;
      padding: 0;
    }
  }
}

.day-cell {
  height: 100%;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 2px 4px 1px;
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

  &.is-out-of-range {
    opacity: 0.38;
  }
}

.day-head {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
  min-height: 28px;
}

.day-solar {
  font-size: 14px;
  font-weight: 500;
  color: var(--el-text-color-primary);

  &.is-today {
    font-size: 18px;
    font-weight: 700;
    color: var(--el-color-primary);
    line-height: 1;
  }
}

.day-lunar {
  font-size: 11px;
  color: var(--el-text-color-secondary);
  cursor: pointer;
}

.day-weather {
  margin-left: auto;
  margin-right: -2px;
  display: inline-flex;
  flex-direction: row;
  align-items: center;
  gap: 1px;
  line-height: 1;
  flex-shrink: 0;
  cursor: default;
}

.day-weather-icon {
  width: 28px;
  height: 28px;
  display: block;
  object-fit: contain;
  image-rendering: -webkit-optimize-contrast;
  filter: contrast(1.15) saturate(1.2) drop-shadow(0 1px 3px rgba(0, 0, 0, 0.42));
}

.day-weather-temp {
  font-size: 10px;
  font-weight: 600;
  color: var(--el-text-color-regular);
  white-space: nowrap;
}

.day-weather-tooltip {
  display: flex;
  flex-direction: column;
  gap: 2px;
  line-height: 1.35;
  font-size: 12px;

  &__desc {
    font-weight: 600;
  }
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

.day-nghi-dot {
  display: none;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--el-color-danger);
  flex-shrink: 0;
}

.day-counts {
  min-height: 0;
  margin-top: 2px;
  overflow: hidden;
  flex: 1;
}

.day-counts--list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.day-counts--dots {
  display: none;
  flex-wrap: wrap;
  align-content: flex-start;
  align-items: center;
  gap: 3px;
  width: 100%;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  box-sizing: border-box;
}

.day-count {
  --status-color: #909399;
  display: flex;
  align-items: center;
  width: 100%;
  min-width: 0;
  height: 20px;
  padding: 0 6px;
  border: 1px solid color-mix(in srgb, var(--status-color) 35%, transparent);
  border-radius: 4px;
  color: var(--status-color);
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
    border-color: color-mix(in srgb, var(--status-color) 55%, transparent);
  }
}

.day-items {
  min-height: 0;
  margin-top: 2px;
  overflow: hidden;
  flex: 1;
}

.day-items--list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.day-items--dots {
  display: none;
  flex-wrap: wrap;
  align-content: flex-start;
  align-items: center;
  gap: 3px;
  width: 100%;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  box-sizing: border-box;
}

.day-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.day-dot-more {
  font-size: 9px;
  font-weight: 700;
  line-height: 1;
  color: var(--el-color-primary);
}

.day-item {
  --loai-color: #909399;
  display: flex;
  align-items: stretch;
  gap: 5px;
  width: 100%;
  min-width: 0;
  height: 20px;
  padding: 0 5px 0 0;
  border: 1px solid color-mix(in srgb, var(--loai-color) 35%, transparent);
  border-radius: 4px;
  background: var(--el-fill-color-light);
  font: inherit;
  text-align: left;
  cursor: pointer;
  box-sizing: border-box;
  overflow: hidden;

  &:hover {
    filter: brightness(0.97);
    border-color: color-mix(in srgb, var(--loai-color) 55%, transparent);
  }
}

.day-item-color {
  flex-shrink: 0;
  width: 8px;
  align-self: stretch;
  background: var(--loai-color);
  border-radius: 3px 0 0 3px;
}

.day-item-time {
  flex-shrink: 0;
  align-self: center;
  font-size: 10px;
  font-weight: 700;
  color: var(--el-text-color-primary);
}

.day-item-name {
  min-width: 0;
  flex: 1;
  align-self: center;
  font-size: 10px;
  font-weight: 500;
  color: var(--el-text-color-regular);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.day-more {
  display: inline-flex;
  align-items: center;
  justify-content: flex-start;
  width: fit-content;
  max-width: 100%;
  margin-top: 1px;
  padding: 0 2px;
  border: none;
  background: transparent;
  color: var(--el-color-primary);
  font-size: 10px;
  font-weight: 600;
  line-height: 1.3;
  cursor: pointer;
  white-space: nowrap;

  &:hover {
    text-decoration: underline;
  }
}

.day-cell.is-ngay-nghi .day-solar:not(.is-today) {
  color: var(--el-color-danger);
}

.loai-config {
  margin-top: 12px;
  padding-top: 14px;
  border-top: 1px solid var(--el-border-color-lighter);
}

.loai-config-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.loai-config-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.loai-config-actions {
  display: inline-flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

.loai-config-hint {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.loai-config-list {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 10px 12px;

  @media (max-width: 1199px) {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.loai-config-item {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  padding: 8px 10px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  background: var(--el-fill-color-blank);

  &.is-hidden {
    opacity: 0.55;
  }

  :deep(.el-switch) {
    flex-shrink: 0;
    height: 20px;
    line-height: 20px;
  }

  :deep(.el-color-picker) {
    flex-shrink: 0;
    height: 20px;
    display: inline-flex;
    align-items: center;
    line-height: 0;
    vertical-align: middle;
  }

  :deep(.el-color-picker__trigger) {
    width: 42px;
    height: 20px;
    padding: 2px;
    border-radius: 4px;
    margin: 0;
    vertical-align: middle;
    box-sizing: border-box;
  }

  :deep(.el-color-picker__color) {
    border-radius: 2px;
  }

  :deep(.el-color-picker__color-inner) {
    border-radius: 2px;
  }

  :deep(.el-color-picker__icon),
  :deep(.el-color-picker__empty) {
    display: none;
  }
}

.loai-color-reset-all {
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  padding: 0;
  margin: 0;
  color: var(--el-text-color-secondary);

  &:not(:disabled):hover {
    color: var(--el-color-primary);
  }

  &:disabled {
    opacity: 0.35;
  }
}

.loai-config-name {
  min-width: 0;
  flex: 1;
  font-size: 13px;
  line-height: 20px;
  color: var(--el-text-color-regular);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (max-width: 767px) {
  .lich-khach-hang-calendar {
    gap: 8px;
  }

  .day-cell {
    position: relative;
    padding: 2px 1px;
    gap: 2px;
    border-radius: 8px;
    align-items: center;
  }

  .day-head {
    min-height: 20px;
    gap: 2px;
    width: 100%;
    justify-content: center;
  }

  .day-solar {
    font-size: 13px;

    &.is-today {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 24px;
      height: 24px;
      font-size: 13px;
      border-radius: 50%;
      background: var(--el-color-primary);
      color: #fff;
    }
  }

  .day-lunar {
    display: none;
  }

  .day-weather {
    display: none;
  }

  .day-nghi-label {
    display: none;
  }

  .day-nghi-dot {
    display: block;
    position: absolute;
    top: 3px;
    left: 3px;
    z-index: 1;
  }

  .day-counts--list {
    display: none;
  }

  .day-counts--dots {
    display: flex;
    justify-content: center;
    margin-top: 0;
  }

  .day-items--list {
    display: none;
  }

  .day-items--dots {
    display: flex;
    justify-content: center;
    margin-top: 0;
  }

  .day-cell.is-ngay-nghi {
    box-shadow: none;
  }

  .loai-config {
    margin-top: 8px;
    padding: 10px 6px 0;
  }
}
</style>
