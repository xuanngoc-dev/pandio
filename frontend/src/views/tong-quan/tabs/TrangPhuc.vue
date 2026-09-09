<template>
  <div class="trang-phuc page-list" v-loading="loading">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar" align="middle">
        <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
          <CustomDatePicker
            v-model="dateRange"
            type="daterange"
            range-separator="—"
            start-placeholder="Từ ngày"
            end-placeholder="Đến ngày"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            unlink-panels
            :clearable="false"
            style="width: 100%"
          />
        </CustomCol>
        <CustomCol :xs="24" :sm="24" :md="16" :lg="18">
          <div class="toolbar-actions">
            <CustomButton type="primary" :loading="loading" @click="applyFilter">
              Áp dụng
            </CustomButton>
            <CustomButton
              v-for="preset in datePresets"
              :key="preset.key"
              :type="activePreset === preset.key ? 'primary' : 'default'"
              plain
              @click="applyDatePreset(preset.key)"
            >
              {{ preset.label }}
            </CustomButton>
          </div>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomRow :gutter="12" class="dash-row">
      <CustomCol
        v-for="card in statCards"
        :key="card.key"
        :xs="12"
        :sm="12"
        :md="8"
        :lg="6"
      >
        <StatCard
          :title="card.title"
          :value="card.value"
          :hint="card.hint"
          :tone="card.tone"
        >
          <template #icon>
            <component :is="card.icon" />
          </template>
        </StatCard>
      </CustomCol>
    </CustomRow>

    <CustomRow :gutter="12" class="dash-row">
      <CustomCol :xs="24" :lg="12">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Top 5 sản phẩm cho thuê nhiều nhất</div>
                <p class="card-sub">Theo số lượt thuê (ngày bắt đầu trong kỳ)</p>
              </div>
            </div>
          </template>
          <RankList v-if="topSanPham.length" :items="topSanPham" />
          <el-empty v-else description="Chưa có dữ liệu trong kỳ" :image-size="72" />
        </CustomCard>
      </CustomCol>

      <CustomCol :xs="24" :lg="12">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">5 hợp đồng mới nhất</div>
                <p class="card-sub">HĐ tạo trong kỳ (loại nháp / hủy)</p>
              </div>
            </div>
          </template>
          <CustomTable
            v-if="hopDongMoiNhat.length"
            :data="hopDongMoiNhat"
            size="small"
            class="hd-table"
          >
            <CustomTableColumn label="Mã HĐ" min-width="120">
              <template #default="{ row }">
                <span class="hd-code">{{ row.ma_hop_dong || '—' }}</span>
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Khách hàng" min-width="120" show-overflow-tooltip>
              <template #default="{ row }">
                {{ row.ten_khach_hang || '—' }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Ngày thuê" width="100">
              <template #default="{ row }">
                {{ formatDate(row.ngay_thue) }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Tổng tiền" width="110" align="right">
              <template #default="{ row }">
                {{ formatMoney(row.tong_tien) }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Trạng thái" width="110">
              <template #default="{ row }">
                <el-tag size="small" :type="trangThaiTagType(row.trang_thai)" effect="plain">
                  {{ trangThaiLabel(row.trang_thai) }}
                </el-tag>
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Hoàn trả" width="110">
              <template #default="{ row }">
                <span class="hoan-tra" :class="`is-${row.hoan_tra_type}`">
                  {{ row.hoan_tra_label || '—' }}
                </span>
              </template>
            </CustomTableColumn>
          </CustomTable>
          <el-empty v-else description="Chưa có hợp đồng trong kỳ" :image-size="72" />
        </CustomCard>
      </CustomCol>
    </CustomRow>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import {
  Box,
  Calendar,
  CircleCheck,
  Clock,
  Goods,
  Money,
  WarningFilled,
} from '@element-plus/icons-vue'
import { fetchTongQuanTrangPhuc } from '@/api/tongQuanTrangPhuc'
import RankList from '@/components/dashboard/RankList.vue'
import StatCard from '@/components/dashboard/StatCard.vue'

defineOptions({ name: 'TrangPhuc' })

const RANK_COLORS = ['#e6a23c', '#909399', '#c47a3a', '#409eff', '#67c23a']

const datePresets = [
  { key: 'this_week', label: 'Tuần này' },
  { key: 'last_week', label: 'Tuần trước' },
  { key: 'this_month', label: 'Tháng này' },
  { key: 'last_month', label: 'Tháng trước' },
]

const trangThaiOptions = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
  { value: 'cho_xac_nhan', label: 'Chờ xác nhận' },
  { value: 'dang_thue', label: 'Đang thuê' },
  { value: 'da_tra', label: 'Đã trả' },
  { value: 'qua_han', label: 'Quá hạn' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
  { value: 'da_huy', label: 'Đã hủy' },
]

const dateRange = ref(getPresetRange('this_month'))
const appliedRange = ref(getPresetRange('this_month'))
const loading = ref(false)
const stats = ref(null)

const activePreset = computed(() => {
  const range = dateRange.value
  if (!range?.[0] || !range?.[1]) return null
  for (const preset of datePresets) {
    const expected = getPresetRange(preset.key)
    if (expected && expected[0] === range[0] && expected[1] === range[1]) {
      return preset.key
    }
  }
  return null
})

const statCards = computed(() => {
  const s = stats.value || emptyStats()

  return [
    {
      key: 'tong_trang_phuc',
      title: 'Tổng trang phục',
      value: formatCount(s.tong_trang_phuc),
      hint: 'Toàn bộ sản phẩm trong kho',
      tone: 'primary',
      icon: Goods,
    },
    {
      key: 'so_dang_hoat_dong',
      title: 'Số lượng đang hoạt động',
      value: formatCount(s.so_dang_hoat_dong),
      hint: 'Trạng thái hoạt động = đang dùng',
      tone: 'success',
      icon: CircleCheck,
    },
    {
      key: 'so_dang_cho_thue',
      title: 'Số lượng đang cho thuê',
      value: formatCount(s.so_dang_cho_thue),
      hint: 'Tình trạng sản phẩm = đang cho thuê',
      tone: 'warning',
      icon: Box,
    },
    {
      key: 'so_hd_dang_cho_thue',
      title: 'Số HĐ đang cho thuê',
      value: formatCount(s.so_hd_dang_cho_thue),
      hint: 'HĐ trạng thái đang thuê / quá hạn',
      tone: 'info',
      icon: Calendar,
    },
    {
      key: 'doanh_thu_hd',
      title: 'Doanh thu HĐ',
      value: formatMoney(s.doanh_thu_hd),
      hint: 'SUM tong_tien HĐ tạo trong kỳ',
      tone: 'success',
      icon: Money,
    },
    {
      key: 'so_hd_tra_som',
      title: 'Trả sớm',
      value: formatCount(s.so_hd_tra_som),
      hint: 'Ngày trả chính thức trước hạn (trong kỳ)',
      tone: 'success',
      icon: Clock,
    },
    {
      key: 'so_hd_dung_han',
      title: 'Đúng hạn',
      value: formatCount(s.so_hd_dung_han),
      hint: 'Trả đúng ngày dự kiến (trong kỳ)',
      tone: 'primary',
      icon: CircleCheck,
    },
    {
      key: 'so_hd_qua_han',
      title: 'Quá hạn',
      value: formatCount(s.so_hd_qua_han),
      hint: 'Trả muộn hoặc đang quá hạn (hạn trong kỳ)',
      tone: 'danger',
      icon: WarningFilled,
    },
  ]
})

const topSanPham = computed(() => {
  const rows = stats.value?.top_san_pham || []
  return rows.map((row, index) => ({
    id: row.id,
    name: row.name,
    value: Number(row.value) || 0,
    valueLabel: `${formatCount(row.value)} lượt`,
    color: RANK_COLORS[index] || '#409eff',
    meta: row.ma_san_pham ? `Mã ${row.ma_san_pham}` : undefined,
  }))
})

const hopDongMoiNhat = computed(() => stats.value?.hop_dong_moi_nhat || [])

onMounted(() => {
  loadStats()
})

function applyFilter() {
  if (!dateRange.value?.[0] || !dateRange.value?.[1]) {
    ElMessage.warning('Vui lòng chọn khoảng thời gian')
    return
  }
  appliedRange.value = [...dateRange.value]
  loadStats()
}

function applyDatePreset(key) {
  dateRange.value = getPresetRange(key)
  applyFilter()
}

async function loadStats() {
  const range = appliedRange.value
  if (!range?.[0] || !range?.[1]) return

  loading.value = true
  try {
    const { data } = await fetchTongQuanTrangPhuc(
      {
        tu_ngay: range[0],
        den_ngay: range[1],
      },
      { skipLoading: true },
    )
    stats.value = data
  } catch {
    stats.value = emptyStats()
    ElMessage.error('Không tải được thống kê Trang phục')
  } finally {
    loading.value = false
  }
}

function emptyStats() {
  return {
    tong_trang_phuc: 0,
    so_dang_hoat_dong: 0,
    so_dang_cho_thue: 0,
    so_hd_dang_cho_thue: 0,
    doanh_thu_hd: 0,
    so_hd_tra_som: 0,
    so_hd_dung_han: 0,
    so_hd_qua_han: 0,
    top_san_pham: [],
    hop_dong_moi_nhat: [],
  }
}

function toYmd(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

/** Thứ 2 trong tuần chứa `date` (tuần bắt đầu từ Thứ 2) */
function getMonday(date) {
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const day = d.getDay()
  const diff = day === 0 ? -6 : 1 - day
  d.setDate(d.getDate() + diff)
  return d
}

function addDays(date, days) {
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  d.setDate(d.getDate() + days)
  return d
}

/** Khoảng ngày cho preset — tuần: T2→CN */
function getPresetRange(key) {
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const monday = getMonday(today)

  if (key === 'this_week') {
    return [toYmd(monday), toYmd(addDays(monday, 6))]
  }
  if (key === 'last_week') {
    const start = addDays(monday, -7)
    return [toYmd(start), toYmd(addDays(start, 6))]
  }
  if (key === 'this_month') {
    const start = new Date(now.getFullYear(), now.getMonth(), 1)
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
    return [toYmd(start), toYmd(end)]
  }
  if (key === 'last_month') {
    const start = new Date(now.getFullYear(), now.getMonth() - 1, 1)
    const end = new Date(now.getFullYear(), now.getMonth(), 0)
    return [toYmd(start), toYmd(end)]
  }
  return null
}

function formatMoney(val) {
  return `${Number(val || 0).toLocaleString('vi-VN')} ₫`
}

function formatCount(val) {
  return Number(val || 0).toLocaleString('vi-VN')
}

function formatDate(val) {
  if (!val) return '—'
  const [y, m, d] = String(val).slice(0, 10).split('-')
  if (!y || !m || !d) return String(val)
  return `${d}/${m}/${y}`
}

function trangThaiLabel(value) {
  return trangThaiOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function trangThaiTagType(value) {
  const map = {
    moi_tao: 'info',
    nhap: 'info',
    cho_xac_nhan: 'info',
    dang_thue: 'warning',
    da_tra: 'success',
    qua_han: 'danger',
    hoan_thanh: 'success',
    da_huy: 'info',
  }
  return map[value] || 'info'
}
</script>

<style scoped lang="scss">
.trang-phuc.page-list {
  gap: 14px;
}

.filter-card {
  .toolbar {
    :deep(> .el-col) {
      min-width: 0;
    }

    :deep(.el-date-editor),
    :deep(.el-date-editor.el-date-editor--daterange) {
      width: 100% !important;
      max-width: 100%;
      min-width: 0 !important;
      box-sizing: border-box;
    }
  }
}

.dash-row {
  row-gap: 12px;

  :deep(> .el-col) {
    display: flex;
    min-width: 0;
  }

  :deep(> .el-col > .stat-card),
  :deep(> .el-col > .chart-card) {
    width: 100%;
    flex: 1;
    min-width: 0;
  }
}

.chart-card {
  min-width: 0;

  :deep(.el-card__header) {
    padding: 10px 12px 4px;
    border-bottom: none;
  }

  :deep(.el-card__body) {
    padding: 4px 12px 12px;
  }
}

.card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.card-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.card-sub {
  margin: 1px 0 0;
  font-size: 11px;
  font-weight: 400;
  color: var(--el-text-color-placeholder);
}

.hd-table {
  width: 100%;
}

.hd-code {
  font-weight: 500;
  font-size: 12px;
}

.hoan-tra {
  font-size: 12px;
  font-weight: 500;

  &.is-early {
    color: var(--el-color-success);
  }
  &.is-ontime {
    color: var(--el-color-primary);
  }
  &.is-late,
  &.is-overdue {
    color: var(--el-color-danger);
  }
  &.is-remaining,
  &.is-today {
    color: var(--el-color-warning);
  }
  &.is-muted {
    color: var(--el-text-color-placeholder);
  }
}
</style>
