<template>
  <div class="lich-dieu-phoi-calendar">
    <CustomCard shadow="hover" class="calendar-card" v-loading="loadingNgayNghi">
      <template #header>
        <div class="card-header">
          <span class="card-title">{{ title }}</span>
          <div class="legend">
            <span class="legend-swatch is-ngay-nghi" />
            <span class="legend-text">Ngày nghỉ</span>
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
            :class="{ 'is-ngay-nghi': isNgayNghi(data.day) }"
            :title="ngayNghiTitle(data.day)"
          >
            <div class="day-head">
              <span class="day-solar">{{ data.day.split('-').pop() }}</span>
              <span class="day-lunar">{{ formatLunarLabel(data.day) }}</span>
            </div>
            <span v-if="isNgayNghi(data.day)" class="day-nghi-label">
              {{ ngayNghiLabel(data.day) }}
            </span>
          </div>
        </template>
      </el-calendar>
    </CustomCard>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { fetchNgayNghi } from '@/api/ngayNghi'
import { formatLunarLabel } from '@/utils/lunar'

defineProps({
  title: {
    type: String,
    required: true,
  },
})

/** Mặc định: tháng hiện tại */
const selectedDate = ref(new Date())
const loadingNgayNghi = ref(false)

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

function dayKey(day) {
  return String(day || '').slice(0, 10)
}

function isNgayNghi(day) {
  return Boolean(ngayNghiByDate.value[dayKey(day)])
}

function ngayNghiLabel(day) {
  return ngayNghiByDate.value[dayKey(day)]?.ten_ngay_nghi || 'Nghỉ'
}

function ngayNghiTitle(day) {
  const item = ngayNghiByDate.value[dayKey(day)]
  return item ? `Ngày nghỉ: ${item.ten_ngay_nghi}` : ''
}

onMounted(() => {
  loadActiveNgayNghi()
})
</script>

<style scoped lang="scss">
.lich-dieu-phoi-calendar {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.card-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
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

  &.is-ngay-nghi {
    background: color-mix(in srgb, var(--el-color-danger) 16%, transparent);
    border-color: color-mix(in srgb, var(--el-color-danger) 35%, transparent);
  }
}

.calendar-card {
  :deep(.el-calendar-table .el-calendar-day) {
    height: 96px;
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

  &.is-ngay-nghi {
    background: color-mix(in srgb, var(--el-color-danger) 14%, transparent);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--el-color-danger) 28%, transparent);
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
}

.day-cell.is-ngay-nghi .day-solar {
  color: var(--el-color-danger);
}
</style>
