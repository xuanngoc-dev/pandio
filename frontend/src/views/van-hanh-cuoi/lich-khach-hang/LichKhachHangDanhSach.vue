<template>
  <div class="lich-khach-hang-danh-sach page-list">
    <LichKhachHangDateFilter v-model="dateRange" />

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <div class="card-title-wrap">
            <span class="card-title">Danh sách theo ngày</span>
            <span class="card-subtitle">{{ rangeSummary }}</span>
          </div>
        </div>
      </template>

      <CustomTable
        v-loading="loading"
        :data="dayRows"
        stripe
        row-key="ngay"
        style="width: 100%"
        empty-text="Không có ngày trong khoảng đã chọn"
        :row-class-name="rowClassName"
        @row-click="onRowClick"
      >
        <CustomTableColumn label="STT" width="64" align="center">
          <template #default="{ $index }">
            {{ $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Thứ" width="120" header-align="left" align="left">
          <template #default="{ row }">
            {{ weekdayLabel(row.ngay) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Ngày dương lịch" width="200" align="center">
          <template #default="{ row }">
            <span class="day-solar" :class="{ 'is-today': row.isToday }">
              {{ formatDateVi(row.ngay) }}
            </span>
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Ngày âm lịch" width="150" align="center">
          <template #default="{ row }">
            {{ formatLunarDate(row.ngay) || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Hẹn lịch" min-width="220">
          <template #default="{ row }">
            <div v-if="row.henLich.length" class="name-list">
              <button
                v-for="item in row.henLich"
                :key="item.event_key"
                type="button"
                class="name-chip is-hen"
                :title="itemTooltip(item)"
                @click.stop="openChiTiet(row.ngay)"
              >
                {{ item.ten_khach || 'Khách hàng' }}
              </button>
            </div>
            <span v-else class="empty-cell">—</span>
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Đến" min-width="220">
          <template #default="{ row }">
            <div v-if="row.den.length" class="name-list">
              <button
                v-for="item in row.den"
                :key="item.event_key"
                type="button"
                class="name-chip is-den"
                :title="itemTooltip(item)"
                @click.stop="openChiTiet(row.ngay)"
              >
                {{ item.ten_khach || 'Khách hàng' }}
              </button>
            </div>
            <span v-else class="empty-cell">—</span>
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Tổng" width="88" align="center">
          <template #default="{ row }">
            <span v-if="row.total" class="day-total">{{ row.total }}</span>
            <span v-else class="empty-cell">0</span>
          </template>
        </CustomTableColumn>
      </CustomTable>
    </CustomCard>

    <LichKhachHangChiTietModal
      v-model="chiTietVisible"
      :ngay="chiTietNgay"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { fetchLichKhachHang } from '@/api/noteKhachMoi'
import { formatLunarDate } from '@/utils/lunar'
import LichKhachHangChiTietModal from './LichKhachHangChiTietModal.vue'
import LichKhachHangDateFilter from './LichKhachHangDateFilter.vue'
import {
  dayKey,
  eachDateInRange,
  formatDateVi,
  getPresetRange,
  toYmd,
  weekdayLabel,
} from './lichKhachHangDate'

const dateRange = defineModel('dateRange', {
  type: Array,
  default: () => getPresetRange('this_month'),
})

const TRANG_THAI_LABEL = {
  cho_hen: 'Chờ hẹn',
  da_den: 'Đã đến',
  khong_den: 'Không đến',
  da_ky_hd: 'Đã ký HĐ',
  da_huy: 'Đã hủy',
}

const LOAI_LABEL = {
  hen_lich: 'Hẹn lịch',
  den: 'Đến',
}

const loading = ref(false)
const items = ref([])
const chiTietVisible = ref(false)
const chiTietNgay = ref('')

function currentRange() {
  const range = dateRange.value
  if (Array.isArray(range) && range[0] && range[1]) {
    return { tu_ngay: range[0], den_ngay: range[1] }
  }
  const [tu_ngay, den_ngay] = getPresetRange('this_month')
  return { tu_ngay, den_ngay }
}

const suKienByDate = computed(() => {
  const map = {}
  for (const row of items.value) {
    const key = dayKey(row.ngay)
    if (!key) continue
    if (!map[key]) map[key] = []
    map[key].push(row)
  }
  return map
})

const dayRows = computed(() => {
  const { tu_ngay, den_ngay } = currentRange()
  const todayKey = toYmd(new Date())
  return eachDateInRange(tu_ngay, den_ngay).map((ngay) => {
    const events = suKienByDate.value[ngay] || []
    const henLich = events.filter((item) => item.loai === 'hen_lich')
    const den = events.filter((item) => item.loai === 'den')
    return {
      ngay,
      henLich,
      den,
      total: henLich.length + den.length,
      isToday: ngay === todayKey,
    }
  })
})

const rangeSummary = computed(() => {
  const { tu_ngay, den_ngay } = currentRange()
  const daysWithEvents = dayRows.value.filter((row) => row.total > 0).length
  const henCount = dayRows.value.reduce((sum, row) => sum + row.henLich.length, 0)
  const denCount = dayRows.value.reduce((sum, row) => sum + row.den.length, 0)
  return `${formatDateVi(tu_ngay)} – ${formatDateVi(den_ngay)} · ${daysWithEvents} ngày có khách · ${henCount} hẹn lịch · ${denCount} đến`
})

function rowClassName({ row }) {
  const classes = ['day-row']
  if (row.isToday) classes.push('is-today')
  if (row.total > 0) classes.push('has-events')
  return classes.join(' ')
}

function itemTooltip(item) {
  const parts = [
    LOAI_LABEL[item?.loai] || item?.loai,
    item?.ten_khach,
    item?.sdt,
    TRANG_THAI_LABEL[item?.trang_thai] || item?.trang_thai,
  ].filter(Boolean)
  return parts.join(' · ')
}

function openChiTiet(ngay) {
  chiTietNgay.value = dayKey(ngay)
  chiTietVisible.value = true
}

function onRowClick(row) {
  if (!row?.total) return
  openChiTiet(row.ngay)
}

async function loadItems() {
  const { tu_ngay, den_ngay } = currentRange()
  loading.value = true
  try {
    const { data } = await fetchLichKhachHang({ tu_ngay, den_ngay })
    items.value = data?.items || []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

watch(
  () => {
    const range = dateRange.value
    return Array.isArray(range) ? `${range[0]}|${range[1]}` : ''
  },
  () => {
    loadItems()
  },
)

onMounted(loadItems)
</script>

<style scoped lang="scss">
.card-title-wrap {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.card-subtitle {
  font-size: 12px;
  font-weight: 400;
  color: var(--el-text-color-secondary);
}

.day-solar {
  font-weight: 400;
  color: var(--el-text-color-primary);

  &.is-today {
    color: var(--el-color-primary);
    font-weight: 700;
  }
}

.name-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.name-chip {
  max-width: 100%;
  padding: 2px 8px;
  border-radius: 999px;
  border: 1px solid transparent;
  font: inherit;
  font-size: 12px;
  font-weight: 500;
  line-height: 1.4;
  cursor: pointer;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;

  &.is-hen {
    color: #1565c0;
    background: #1e88e526;
    border-color: #1e88e559;
  }

  &.is-den {
    color: #2e7d32;
    background: #43a04726;
    border-color: #43a04759;
  }

  &:hover {
    filter: brightness(0.96);
  }
}

.empty-cell {
  color: var(--el-text-color-placeholder);
}

.day-total {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

:deep(.el-table .day-row) {
  cursor: default;
}

:deep(.el-table .day-row.has-events) {
  cursor: pointer;
}

:deep(.el-table .day-row.is-today td) {
  background: color-mix(in srgb, var(--el-color-primary) 6%, transparent);
}

:deep(.el-table .day-row.has-events td) {
  background: color-mix(in srgb, var(--el-color-success) 5%, transparent);
}

:deep(.el-table .day-row.is-today.has-events td) {
  background: color-mix(in srgb, var(--el-color-primary) 8%, transparent);
}
</style>
