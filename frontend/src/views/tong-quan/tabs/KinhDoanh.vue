<template>
  <div class="kinh-doanh page-list" v-loading="loading">
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
        :lg="4"
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
                <div class="card-title">Note khách mới theo trạng thái</div>
                <p class="card-sub">Số note có ngày tạo trong kỳ lọc</p>
              </div>
            </div>
          </template>
          <ChartBarBasic
            :series="noteStatusSeries"
            :categories="noteStatusCategories"
            :colors="noteStatusColors"
            :height="320"
            distributed
            :x-formatter="formatCount"
          />
        </CustomCard>
      </CustomCol>

      <CustomCol :xs="24" :lg="12">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Nguồn khách hàng</div>
                <p class="card-sub">Note (nguồn) · HĐ SDDV (kênh tiếp cận)</p>
              </div>
            </div>
          </template>
          <ChartColumnBasic
            :series="nguonKhachSeries"
            :categories="nguonKhachCategories"
            :colors="['#409eff', '#67c23a']"
            :height="320"
            :y-formatter="formatCount"
            :min-category-width="100"
            column-width="55%"
          />
        </CustomCard>
      </CustomCol>
    </CustomRow>

    <CustomRow :gutter="12" class="dash-row">
      <CustomCol :xs="24" :lg="12">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Top 5 sale — số HĐ ký</div>
                <p class="card-sub">SDDV theo người tạo · TP theo người cho thuê</p>
              </div>
            </div>
          </template>
          <RankList v-if="topSaleSoHd.length" :items="topSaleSoHd" />
          <el-empty v-else description="Chưa có dữ liệu trong kỳ" :image-size="72" />
        </CustomCard>
      </CustomCol>

      <CustomCol :xs="24" :lg="12">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Top 5 sale — doanh thu HĐ ký</div>
                <p class="card-sub">SUM tong_tien HĐ ký trong kỳ (SDDV + TP)</p>
              </div>
            </div>
          </template>
          <RankList v-if="topSaleDoanhThu.length" :items="topSaleDoanhThu" />
          <el-empty v-else description="Chưa có dữ liệu trong kỳ" :image-size="72" />
        </CustomCard>
      </CustomCol>
    </CustomRow>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import {
  Document,
  Money,
  Opportunity,
  Position,
  TrendCharts,
  Wallet,
} from '@element-plus/icons-vue'
import { fetchTongQuanKinhDoanh } from '@/api/tongQuanKinhDoanh'
import { ChartBarBasic, ChartColumnBasic } from '@/components/charts'
import RankList from '@/components/dashboard/RankList.vue'
import StatCard from '@/components/dashboard/StatCard.vue'

defineOptions({ name: 'KinhDoanh' })

const RANK_COLORS = ['#e6a23c', '#909399', '#c47a3a', '#409eff', '#67c23a']

const noteStatusColors = ['#909399', '#67c23a', '#f56c6c', '#409eff', '#e6a23c']

const datePresets = [
  { key: 'this_week', label: 'Tuần này' },
  { key: 'last_week', label: 'Tuần trước' },
  { key: 'this_month', label: 'Tháng này' },
  { key: 'last_month', label: 'Tháng trước' },
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
      key: 'doanh_thu_sddv',
      title: 'Doanh thu SDDV',
      value: formatMoney(s.doanh_thu_sddv),
      hint: 'Tổng các lần thanh toán SDDV có thời gian trong kỳ',
      tone: 'success',
      icon: Money,
    },
    {
      key: 'doanh_thu_tp',
      title: 'Doanh thu TP',
      value: formatMoney(s.doanh_thu_tp),
      hint: 'SUM tong_tien HĐ thuê TP tạo trong kỳ',
      tone: 'warning',
      icon: Wallet,
    },
    {
      key: 'so_hop_dong',
      title: 'Số hợp đồng',
      value: formatCount(s.so_hop_dong),
      hint: `SDDV ${formatCount(s.so_hop_dong_sddv_ky)} · TP ${formatCount(s.so_hop_dong_cho_thue_ky)}`,
      tone: 'primary',
      icon: Document,
    },
    {
      key: 'ty_le_chot',
      title: 'Tỷ lệ chốt',
      value: formatPercent(s.ty_le_chot),
      hint: `HĐ SDDV ký ${formatCount(s.so_hop_dong_sddv_ky)} / Đã đến ${formatCount(s.so_note_da_den)}`,
      tone: 'success',
      icon: TrendCharts,
    },
    {
      key: 'tong_note_khach_moi',
      title: 'Tổng note khách mới',
      value: formatCount(s.tong_note_khach_moi),
      hint: 'Note có ngày tạo trong kỳ',
      tone: 'primary',
      icon: Opportunity,
    },
    {
      key: 'ty_le_den',
      title: 'Tỷ lệ đến',
      value: formatPercent(s.ty_le_den),
      hint: `Đã đến/ký HĐ ${formatCount(s.so_note_den_theo_tao)} / Note tạo ${formatCount(s.tong_note_khach_moi)}`,
      tone: 'info',
      icon: Position,
    },
  ]
})

const noteStatusCategories = computed(() => {
  const cats = stats.value?.bieu_do_trang_thai_note?.categories
  if (cats?.length) return cats
  return ['Chờ hẹn', 'Đã đến', 'Không đến', 'Đã ký HĐ', 'Đã hủy']
})

const noteStatusSeries = computed(() => {
  const raw = stats.value?.bieu_do_trang_thai_note?.data
  const data = noteStatusCategories.value.map((_, i) => Number(raw?.[i]) || 0)
  return [{ name: 'Số note', data }]
})

const nguonKhachCategories = computed(
  () => stats.value?.bieu_do_nguon_khach?.categories?.length
    ? stats.value.bieu_do_nguon_khach.categories
    : ['Không có nguồn'],
)

const nguonKhachSeries = computed(() => {
  const chart = stats.value?.bieu_do_nguon_khach
  const len = nguonKhachCategories.value.length
  const pad = (arr) => Array.from({ length: len }, (_, i) => Number(arr?.[i]) || 0)

  return [
    { name: 'Note khách mới', data: pad(chart?.note_khach_moi) },
    { name: 'Hợp đồng SDDV', data: pad(chart?.hop_dong_sddv) },
  ]
})

const topSaleSoHd = computed(() => {
  const rows = stats.value?.top_sale_so_hd || []
  return rows.map((row, index) => ({
    id: row.id,
    name: row.name,
    value: Number(row.value) || 0,
    valueLabel: `${formatCount(row.value)} HĐ`,
    color: RANK_COLORS[index] || '#409eff',
    meta: `SDDV ${formatCount(row.so_hd_sddv)} · TP ${formatCount(row.so_hd_tp)} · DT ${formatCompactMoney(row.doanh_thu)}`,
  }))
})

const topSaleDoanhThu = computed(() => {
  const rows = stats.value?.top_sale_doanh_thu || []
  return rows.map((row, index) => ({
    id: row.id,
    name: row.name,
    value: Number(row.value) || 0,
    valueLabel: formatMoney(row.value),
    color: RANK_COLORS[index] || '#409eff',
    meta: `${formatCount(row.so_hd)} HĐ · SDDV ${formatCompactMoney(row.doanh_thu_sddv)} · TP ${formatCompactMoney(row.doanh_thu_tp)}`,
  }))
})

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
    const { data } = await fetchTongQuanKinhDoanh(
      {
        tu_ngay: range[0],
        den_ngay: range[1],
      },
      { skipLoading: true },
    )
    stats.value = data
  } catch {
    stats.value = emptyStats()
  } finally {
    loading.value = false
  }
}

function emptyStats() {
  return {
    doanh_thu_sddv: 0,
    doanh_thu_tp: 0,
    so_hop_dong: 0,
    so_hop_dong_sddv_ky: 0,
    so_hop_dong_cho_thue_ky: 0,
    ty_le_chot: 0,
    so_note_da_den: 0,
    tong_note_khach_moi: 0,
    ty_le_den: 0,
    so_note_den_theo_tao: 0,
    bieu_do_trang_thai_note: {
      categories: ['Chờ hẹn', 'Đã đến', 'Không đến', 'Đã ký HĐ', 'Đã hủy'],
      data: [0, 0, 0, 0, 0],
      keys: ['cho_hen', 'da_den', 'khong_den', 'da_ky_hd', 'da_huy'],
    },
    bieu_do_nguon_khach: {
      categories: ['Không có nguồn'],
      note_khach_moi: [0],
      hop_dong_sddv: [0],
      hop_dong_tp: [0],
    },
    top_sale_so_hd: [],
    top_sale_doanh_thu: [],
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

function formatCompactMoney(val) {
  const n = Number(val) || 0
  if (Math.abs(n) >= 1_000_000_000) return `${(n / 1_000_000_000).toFixed(1)} tỷ`
  if (Math.abs(n) >= 1_000_000) return `${Math.round(n / 1_000_000)} tr`
  if (Math.abs(n) >= 1_000) return `${Math.round(n / 1_000)}k`
  return n.toLocaleString('vi-VN')
}

function formatPercent(val) {
  return `${Number(val || 0).toLocaleString('vi-VN', { maximumFractionDigits: 1 })}%`
}

function formatCount(val) {
  return Number(val || 0).toLocaleString('vi-VN')
}
</script>

<style scoped lang="scss">
.kinh-doanh.page-list {
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

.card-sub {
  margin: 1px 0 0;
  font-size: 11px;
  font-weight: 400;
  color: var(--el-text-color-placeholder);
}
</style>
