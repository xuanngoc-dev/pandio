<template>
  <div class="marketing page-list" v-loading="loading">
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
      <CustomCol :xs="24">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">{{ chartMetricMeta.title }} theo ngày</div>
                <p class="card-sub">{{ chartMetricMeta.sub }}</p>
              </div>
              <el-radio-group v-model="chartMetric" size="small">
                <el-radio-button
                  v-for="opt in chartMetricOptions"
                  :key="opt.key"
                  :value="opt.key"
                >
                  {{ opt.label }}
                </el-radio-button>
              </el-radio-group>
            </div>
          </template>
          <ChartLineBasic
            v-if="chartCategories.length"
            :series="chartSeries"
            :categories="chartCategories"
            :colors="channelColors"
            :height="350"
            :y-formatter="formatCompactMoney"
            :tooltip-formatter="formatMoney"
            :min-category-width="48"
          />
          <el-empty v-else description="Chưa có dữ liệu trong kỳ" :image-size="72" />
        </CustomCard>
      </CustomCol>
    </CustomRow>

    <el-empty
      v-if="!loading && isEmptyPeriod"
      description="Không có report quảng cáo trong khoảng ngày đã chọn (lọc theo cột ngày)."
      :image-size="80"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import {
  ChatDotRound,
  Coin,
  Money,
  Position,
  TrendCharts,
  Wallet,
} from '@element-plus/icons-vue'
import { fetchTongQuanMarketing } from '@/api/tongQuanMarketing'
import { ChartLineBasic } from '@/components/charts'
import StatCard from '@/components/dashboard/StatCard.vue'

defineOptions({ name: 'Marketing' })

const datePresets = [
  { key: 'this_week', label: 'Tuần này' },
  { key: 'last_week', label: 'Tuần trước' },
  { key: 'this_month', label: 'Tháng này' },
  { key: 'last_month', label: 'Tháng trước' },
]

const chartMetricOptions = [
  { key: 'chi_phi', label: 'Chi phí' },
  { key: 'cpl', label: 'CPL' },
  { key: 'cpi', label: 'CPI' },
]

const channelColors = ['#409eff', '#36cfc9', '#e6a23c']

/** Mặc định: 3 tháng gần nhất (bao gồm tháng hiện tại) — report QC thường lệch tháng */
const dateRange = ref(getDefaultRange())
const appliedRange = ref(getDefaultRange())
const loading = ref(false)
const stats = ref(null)
const chartMetric = ref('chi_phi')

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

const isEmptyPeriod = computed(() => {
  const s = stats.value
  if (!s) return false
  return (
    !Number(s.tong_chi_phi_qc) &&
    !Number(s.tong_inbox) &&
    !Number(s.tong_lead) &&
    !Number(s.lich_hen) &&
    !Number(s.khach_den_tu_hen)
  )
})

const chartMetricMeta = computed(() => {
  if (chartMetric.value === 'cpl') {
    return {
      title: 'CPL',
      sub: 'CPQC / lead theo ngày · Facebook · TikTok · Google',
    }
  }
  if (chartMetric.value === 'cpi') {
    return {
      title: 'CPI',
      sub: 'CPQC / inbox theo ngày · Facebook · TikTok (Google không có inbox)',
    }
  }
  return {
    title: 'Chi phí QC',
    sub: 'CPQC theo ngày · Facebook · TikTok · Google',
  }
})

const chartCategories = computed(
  () => stats.value?.bieu_do_theo_ngay?.categories || [],
)

const chartSeries = computed(() => {
  const chart = stats.value?.bieu_do_theo_ngay
  const metric = chart?.[chartMetric.value]
  if (!metric) {
    return [
      { name: 'Facebook', data: [] },
      { name: 'TikTok', data: [] },
      { name: 'Google', data: [] },
    ]
  }
  return [
    { name: 'Facebook', data: metric.facebook || [] },
    { name: 'TikTok', data: metric.tiktok || [] },
    { name: 'Google', data: metric.google || [] },
  ]
})

const statCards = computed(() => {
  const s = stats.value || emptyStats()

  return [
    {
      key: 'tong_chi_phi_qc',
      title: 'Tổng chi phí QC',
      value: formatMoney(s.tong_chi_phi_qc),
      hint: 'FB + TikTok + Google trong kỳ',
      tone: 'danger',
      icon: Money,
    },
    {
      key: 'chi_phi_facebook',
      title: 'Chi phí Facebook',
      value: formatMoney(s.chi_phi_facebook),
      hint: 'SUM CPQC Facebook',
      tone: 'primary',
      icon: Wallet,
    },
    {
      key: 'chi_phi_tiktok',
      title: 'Chi phí TikTok',
      value: formatMoney(s.chi_phi_tiktok),
      hint: 'SUM CPQC TikTok',
      tone: 'info',
      icon: Wallet,
    },
    {
      key: 'chi_phi_google',
      title: 'Chi phí Google',
      value: formatMoney(s.chi_phi_google),
      hint: 'SUM CPQC Google',
      tone: 'warning',
      icon: Wallet,
    },
    {
      key: 'tong_inbox',
      title: 'Tổng inbox',
      value: formatCount(s.tong_inbox),
      hint: `FB ${formatCount(s.inbox_facebook)} · TikTok ${formatCount(s.inbox_tiktok)}`,
      tone: 'primary',
      icon: ChatDotRound,
    },
    {
      key: 'cpi_trung_binh',
      title: 'CPI trung bình',
      value: formatMoney(s.cpi_trung_binh),
      hint: 'CPQC (FB+TikTok) / Tổng inbox',
      tone: 'success',
      icon: Coin,
    },
    {
      key: 'cpl_trung_binh',
      title: 'CPL trung bình',
      value: formatMoney(s.cpl_trung_binh),
      hint: `Tổng CPQC / ${formatCount(s.tong_lead)} lead`,
      tone: 'success',
      icon: TrendCharts,
    },
    {
      key: 'cpl_facebook',
      title: 'CPL Facebook',
      value: formatMoney(s.cpl_facebook),
      hint: `CPQC FB / ${formatCount(s.lead_facebook)} KH`,
      tone: 'primary',
      icon: Coin,
    },
    {
      key: 'cpl_tiktok',
      title: 'CPL TikTok',
      value: formatMoney(s.cpl_tiktok),
      hint: `CPQC TikTok / ${formatCount(s.lead_tiktok)} KH`,
      tone: 'info',
      icon: Coin,
    },
    {
      key: 'cpl_google',
      title: 'CPL Google',
      value: formatMoney(s.cpl_google),
      hint: `CPQC Google / ${formatCount(s.lead_google)} KH`,
      tone: 'warning',
      icon: Coin,
    },
    {
      key: 'khach_den_tu_hen',
      title: 'Khách đến từ hẹn',
      value: formatCount(s.khach_den_tu_hen),
      hint: `Trên ${formatCount(s.lich_hen)} lịch hẹn`,
      tone: 'success',
      icon: Position,
    },
    {
      key: 'ty_le_khach_den_hen',
      title: 'Tỷ lệ khách đến/hẹn',
      value: formatPercent(s.ty_le_khach_den_hen),
      hint: `${formatCount(s.khach_den_tu_hen)} / ${formatCount(s.lich_hen)}`,
      tone: 'info',
      icon: TrendCharts,
    },
  ]
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
    const { data } = await fetchTongQuanMarketing(
      {
        tu_ngay: range[0],
        den_ngay: range[1],
      },
      { skipLoading: true },
    )
    stats.value = data
  } catch {
    stats.value = emptyStats()
    ElMessage.error('Không tải được thống kê Marketing')
  } finally {
    loading.value = false
  }
}

function emptyStats() {
  return {
    tong_chi_phi_qc: 0,
    chi_phi_facebook: 0,
    chi_phi_tiktok: 0,
    chi_phi_google: 0,
    tong_inbox: 0,
    inbox_facebook: 0,
    inbox_tiktok: 0,
    cpi_trung_binh: 0,
    cpl_trung_binh: 0,
    cpl_facebook: 0,
    cpl_tiktok: 0,
    cpl_google: 0,
    tong_lead: 0,
    lead_facebook: 0,
    lead_tiktok: 0,
    lead_google: 0,
    khach_den_tu_hen: 0,
    lich_hen: 0,
    ty_le_khach_den_hen: 0,
    bieu_do_theo_ngay: {
      categories: [],
      chi_phi: { facebook: [], tiktok: [], google: [] },
      cpl: { facebook: [], tiktok: [], google: [] },
      cpi: { facebook: [], tiktok: [], google: [] },
    },
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

/** Khoảng mặc định: từ đầu tháng (hiện tại − 3) → cuối tháng hiện tại */
function getDefaultRange() {
  const now = new Date()
  const start = new Date(now.getFullYear(), now.getMonth() - 3, 1)
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  return [toYmd(start), toYmd(end)]
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
  const n = Number(val || 0)
  if (Math.abs(n) >= 1_000_000) {
    return `${(n / 1_000_000).toLocaleString('vi-VN', { maximumFractionDigits: 1 })}tr`
  }
  if (Math.abs(n) >= 1_000) {
    return `${(n / 1_000).toLocaleString('vi-VN', { maximumFractionDigits: 0 })}k`
  }
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
.marketing.page-list {
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
</style>
