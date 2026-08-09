<template>
  <div class="lich-chup-make-calendar">
    <CustomCard shadow="hover" class="calendar-card" v-loading="loading">
      <template #header>
        <div class="card-header">
          <span class="card-title">Lịch chụp - make</span>
          <div class="legend legend-meta">
            <span class="legend-swatch is-ngay-nghi" />
            <span class="legend-text">Ngày nghỉ</span>
          </div>
        </div>
      </template>

      <el-calendar
        :key="calendarRenderKey"
        v-model="selectedDate"
        :class="{ 'is-show-empty': hienHopDongTrong }"
      >
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
            }"
            :title="dayCellTitle(data.day)"
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
            <span v-if="isNgayNghi(data.day)" class="day-nghi-label">
              {{ ngayNghiLabel(data.day) }}
            </span>
            <div v-if="hopDongVisibleByDate(data.day).length" class="day-badges">
              <CustomTooltip
                v-for="item in hopDongVisibleByDate(data.day)"
                :key="`${item.loai_hop_dong_id}-${loaiColorMap[item.loai_hop_dong_id]}`"
                :content="badgeTooltip(item)"
                placement="top"
              >
                <span
                  class="day-badge"
                  :class="{ 'is-empty': item.so_luong <= 0 }"
                  :style="{
                    background: loaiColorMap[item.loai_hop_dong_id],
                    borderColor: loaiColorMap[item.loai_hop_dong_id],
                  }"
                  @click.stop="openChiTiet(data.day, item)"
                >
                  {{ item.so_luong }}
                </span>
              </CustomTooltip>
            </div>
          </div>
        </template>
      </el-calendar>

      <div v-if="loaiHopDongLegend.length" class="loai-config">
        <div class="loai-config-head">
          <span class="loai-config-title">Ghi chú loại hợp đồng</span>
          <div class="loai-config-actions">
            <label class="show-empty-toggle">
              <span class="show-empty-label">Hiện HĐ trống</span>
              <el-switch
                v-model="hienHopDongTrong"
                size="small"
                inline-prompt
                active-text="Có"
                inactive-text="Không"
              />
            </label>
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
            v-for="item in loaiHopDongLegend"
            :key="item.loai_hop_dong_id"
            class="loai-config-item"
            :class="{ 'is-hidden': !loaiVisibleMap[item.loai_hop_dong_id] }"
          >
            <el-switch
              :model-value="loaiVisibleMap[item.loai_hop_dong_id]"
              size="small"
              inline-prompt
              active-text="Hiện"
              inactive-text="Ẩn"
              @change="(visible) => setLoaiVisible(item.loai_hop_dong_id, visible)"
            />
            <el-color-picker
              :model-value="loaiColorMap[item.loai_hop_dong_id]"
              color-format="hex"
              :predefine="PREDEFINE_COLORS"
              @change="(color) => setLoaiColor(item.loai_hop_dong_id, color)"
            />
            <span class="loai-config-name" :title="item.ten_hop_dong">
              {{ item.ten_hop_dong }}
            </span>
          </div>
        </div>
      </div>
    </CustomCard>

    <LichChupMakeChiTietModal
      v-model="chiTietVisible"
      :ngay-chup="chiTietNgayChup"
      :loai-hop-dong-id="chiTietLoaiId"
      :ten-hop-dong="chiTietTenHopDong"
      @saved="loadLichChupMake"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { Refresh } from '@element-plus/icons-vue'
import { fetchNgayNghi } from '@/api/ngayNghi'
import { fetchLichChupMake } from '@/api/hopDongSuDungDichVu'
import { fetchTienIchThoiTiet, weatherIconUrl } from '@/api/thoiTiet'
import { formatLunarLabel, formatLunarTooltip, isLunarMonthStart } from '@/utils/lunar'
import LichChupMakeChiTietModal from './LichChupMakeChiTietModal.vue'

const PREFS_STORAGE_KEY = 'pandio.lichChupMake.loaiPrefs'
const SHOW_EMPTY_STORAGE_KEY = 'pandio.lichChupMake.showEmpty'

/** Palette mặc định theo ma_hop_dong (fallback theo index) */
const LOAI_COLOR_BY_MA = {
  ANHOI: '#e53935',
  CHANDUNG: '#1e88e5',
  PREWED: '#fb8c00',
  PSC: '#8e24aa',
  GIADINH: '#43a047',
  KYYEU: '#00acc1',
  BEAUTY: '#ec407a',
  BABYNB: '#5c6bc0',
  COUPLE: '#f4511e',
  MEBAU: '#7cb342',
  SINHNHAT: '#3949ab',
  SUKIEN: '#00897b',
}

const FALLBACK_COLORS = [
  '#e53935',
  '#1e88e5',
  '#fb8c00',
  '#43a047',
  '#8e24aa',
  '#00acc1',
  '#ec407a',
  '#5c6bc0',
  '#f4511e',
  '#7cb342',
  '#3949ab',
  '#00897b',
]

const PREDEFINE_COLORS = [
  ...FALLBACK_COLORS,
  '#c62828',
  '#1565c0',
  '#ef6c00',
  '#2e7d32',
  '#6a1b9a',
  '#00838f',
  '#ad1457',
  '#283593',
]

/** Mặc định: tháng hiện tại */
const selectedDate = ref(new Date())
const loadingNgayNghi = ref(false)
const loadingHopDong = ref(false)
const loadingThoiTiet = ref(false)
/** @type {import('vue').Ref<Record<string, object>>} */
const thoiTietMap = ref({})

const loading = computed(
  () => loadingNgayNghi.value || loadingHopDong.value || loadingThoiTiet.value,
)

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
 * Map YYYY-MM-DD → danh sách { loai_hop_dong_id, ten_hop_dong, ma_hop_dong, so_luong }
 */
const hopDongStatsByDate = ref({})

/** Danh sách loại HĐ (màu mặc định theo mã) */
const loaiHopDongLegend = ref([])

/** Modal chi tiết theo ngày + loại HĐ */
const chiTietVisible = ref(false)
const chiTietNgayChup = ref('')
const chiTietLoaiId = ref(null)
const chiTietTenHopDong = ref('')

/**
 * Tuỳ chọn người dùng theo loai_hop_dong_id:
 * { [id]: { color?: string, visible?: boolean } }
 */
const loaiPrefs = ref({})

/** Hiện badge loại HĐ có so_luong = 0 trên lịch (mặc định: không) */
const hienHopDongTrong = ref(false)

function loadShowEmptyPref() {
  try {
    const raw = localStorage.getItem(SHOW_EMPTY_STORAGE_KEY)
    if (raw == null) return
    hienHopDongTrong.value = raw === '1' || raw === 'true'
  } catch {
    // ignore
  }
}

function persistShowEmptyPref() {
  localStorage.setItem(SHOW_EMPTY_STORAGE_KEY, hienHopDongTrong.value ? '1' : '0')
}

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
  const pref = loaiPrefs.value[prefKey(item.loai_hop_dong_id)]
  if (pref?.color) return pref.color
  return item.defaultColor
}

/** Map id → màu đang dùng (prefs hoặc mặc định) */
const loaiColorMap = computed(() => {
  const map = {}
  for (const item of loaiHopDongLegend.value) {
    map[item.loai_hop_dong_id] = resolveLoaiColor(item)
  }
  return map
})

/** Map id → đang hiện trên lịch */
const loaiVisibleMap = computed(() => {
  const map = {}
  for (const item of loaiHopDongLegend.value) {
    map[item.loai_hop_dong_id] = isLoaiVisible(item.loai_hop_dong_id)
  }
  return map
})

/** Ép el-calendar render lại khi đổi màu / ẩn hiện / hiện HĐ trống */
const calendarRenderKey = computed(() => {
  const prefs = loaiPrefs.value
  const prefsKey = Object.keys(prefs)
    .sort()
    .map((id) => `${id}:${prefs[id]?.color || ''}:${prefs[id]?.visible !== false ? 1 : 0}`)
    .join('|')
  return `${prefsKey}|empty:${hienHopDongTrong.value ? 1 : 0}`
})

function setLoaiColor(loaiId, color) {
  if (!color) return
  patchLoaiPref(loaiId, { color })
}

/** Có ít nhất một loại HĐ đang dùng màu tuỳ chỉnh */
const hasCustomLoaiColor = computed(() =>
  Object.values(loaiPrefs.value).some((pref) => Boolean(pref?.color)),
)

/** Xóa toàn bộ màu tuỳ chỉnh → dùng lại màu mặc định theo mã HĐ */
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

function toYmd(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
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

/**
 * @param {string|null|undefined} maHopDong
 * @param {number} index
 */
function colorForLoai(maHopDong, index) {
  const ma = String(maHopDong || '').toUpperCase()
  if (ma && LOAI_COLOR_BY_MA[ma]) return LOAI_COLOR_BY_MA[ma]
  return FALLBACK_COLORS[index % FALLBACK_COLORS.length]
}

/**
 * @param {Array<{
 *   ngay_chup: string,
 *   loai_hop_dong_id: number|null,
 *   ten_hop_dong: string|null,
 *   ma_hop_dong: string|null,
 *   so_luong: number,
 * }>} rows
 */
function buildHopDongStats(rows) {
  const legendMap = new Map()
  const map = {}

  for (const row of rows) {
    const loaiId = row.loai_hop_dong_id
    if (loaiId == null) continue
    if (!legendMap.has(loaiId)) {
      legendMap.set(loaiId, {
        loai_hop_dong_id: loaiId,
        ten_hop_dong: row.ten_hop_dong || 'Không loại',
        ma_hop_dong: row.ma_hop_dong || '',
      })
    }
  }

  const legend = [...legendMap.values()]
    .sort((a, b) => a.ten_hop_dong.localeCompare(b.ten_hop_dong, 'vi'))
    .map((item, index) => ({
      ...item,
      defaultColor: colorForLoai(item.ma_hop_dong, index),
    }))

  for (const row of rows) {
    const key = dayKey(row.ngay_chup)
    if (!key || row.loai_hop_dong_id == null) continue
    if (!map[key]) map[key] = []
    map[key].push({
      loai_hop_dong_id: row.loai_hop_dong_id,
      ten_hop_dong: row.ten_hop_dong,
      ma_hop_dong: row.ma_hop_dong,
      so_luong: Number(row.so_luong) || 0,
    })
  }

  return { map, legend }
}

function monthRange(date) {
  const d = date instanceof Date ? date : new Date(date)
  const first = new Date(d.getFullYear(), d.getMonth(), 1)
  const last = new Date(d.getFullYear(), d.getMonth() + 1, 0)
  return { tu_ngay: toYmd(first), den_ngay: toYmd(last) }
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

async function loadLichChupMake() {
  loadingHopDong.value = true
  try {
    const { tu_ngay, den_ngay } = monthRange(toSelectedDate())
    const { data } = await fetchLichChupMake({ tu_ngay, den_ngay })
    const { map, legend } = buildHopDongStats(Array.isArray(data) ? data : [])
    hopDongStatsByDate.value = map
    loaiHopDongLegend.value = legend
  } catch {
    hopDongStatsByDate.value = {}
    loaiHopDongLegend.value = []
  } finally {
    loadingHopDong.value = false
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
    const { tu_ngay, den_ngay } = monthRange(toSelectedDate())
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

function dayKey(day) {
  return String(day || '').slice(0, 10)
}

function hopDongByDate(day) {
  return hopDongStatsByDate.value[dayKey(day)] || []
}

/**
 * Badge trên lịch:
 * - Chỉ loại đang bật hiển thị
 * - Mặc định ẩn so_luong = 0; khi "Hiện HĐ trống" thì hiện đủ mọi loại
 */
function hopDongVisibleByDate(day) {
  const visibleMap = loaiVisibleMap.value
  const showEmpty = hienHopDongTrong.value
  return hopDongByDate(day).filter((item) => {
    if (visibleMap[item.loai_hop_dong_id] === false) return false
    return showEmpty || item.so_luong > 0
  })
}

function badgeTooltip(item) {
  const ten = item.ten_hop_dong || 'Loại hợp đồng'
  return `${ten}: ${item.so_luong} hợp đồng`
}

function openChiTiet(day, item) {
  chiTietNgayChup.value = dayKey(day)
  chiTietLoaiId.value = item.loai_hop_dong_id
  chiTietTenHopDong.value = item.ten_hop_dong || ''
  chiTietVisible.value = true
}

function isNgayNghi(day) {
  return Boolean(ngayNghiByDate.value[dayKey(day)])
}

function isToday(day) {
  const now = new Date()
  const y = now.getFullYear()
  const m = String(now.getMonth() + 1).padStart(2, '0')
  const d = String(now.getDate()).padStart(2, '0')
  return dayKey(day) === `${y}-${m}-${d}`
}

function ngayNghiLabel(day) {
  return ngayNghiByDate.value[dayKey(day)]?.ten_ngay_nghi || 'Nghỉ'
}

function dayCellTitle(day) {
  const parts = []
  const nghi = ngayNghiByDate.value[dayKey(day)]
  if (nghi) parts.push(`Ngày nghỉ: ${nghi.ten_ngay_nghi}`)

  const stats = hopDongVisibleByDate(day)
  if (stats.length) {
    parts.push(
      stats
        .map((item) => `${item.ten_hop_dong || 'Không loại'}: ${item.so_luong}`)
        .join(', '),
    )
  }

  return parts.join(' | ')
}

watch(
  () => {
    const d = toSelectedDate()
    return `${d.getFullYear()}-${d.getMonth()}`
  },
  () => {
    loadLichChupMake()
    loadThoiTiet()
  },
)

watch(hienHopDongTrong, () => {
  persistShowEmptyPref()
})

onMounted(() => {
  loadLoaiPrefs()
  loadShowEmptyPref()
  loadActiveNgayNghi()
  loadLichChupMake()
  loadThoiTiet()
})
</script>

<style scoped lang="scss">
.lich-chup-make-calendar {
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
    height: 105px;
    padding: 2px;
  }

  :deep(.el-calendar.is-show-empty .el-calendar-table .el-calendar-day) {
    height: 105px;
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
  gap: 2px;
  padding: 2px 4px 1px;
  border-radius: 6px;
  line-height: 1.2;
  overflow: hidden;

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

.day-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 3px;
  min-height: 0;
  margin-top: 4px;
  overflow: hidden;
}

.day-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 18px;
  padding: 0 5px;
  border-radius: 999px;
  border: 1px solid transparent;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  color: #fff;
  box-sizing: border-box;
  cursor: pointer;
  user-select: none;

  &:hover {
    filter: brightness(1.08);
  }

  &.is-empty {
    opacity: 0.42;
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

.show-empty-toggle {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  cursor: pointer;
  user-select: none;
}

.show-empty-label {
  font-size: 13px;
  color: var(--el-text-color-regular);
  white-space: nowrap;
}

.loai-config-hint {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.loai-config-list {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px 12px;

  @media (max-width: 1199px) {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  @media (max-width: 991px) {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  @media (max-width: 767px) {
    grid-template-columns: repeat(2, minmax(0, 1fr));
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

  /* Ô màu: thấp hơn, dài hơn — căn giữa theo hàng */
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
</style>
