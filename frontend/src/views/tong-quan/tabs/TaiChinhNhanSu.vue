<template>
  <div class="tai-chinh-nhan-su page-list" v-loading="loading">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar" align="middle">
        <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
          <CustomDatePicker
            v-model="selectedMonth"
            type="month"
            placeholder="Chọn tháng"
            format="MM/YYYY"
            value-format="YYYY-MM"
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
              v-for="preset in monthPresets"
              :key="preset.key"
              :type="activePreset === preset.key ? 'primary' : 'default'"
              plain
              @click="applyMonthPreset(preset.key)"
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
          :pending="card.pending"
          :pending-text="card.pendingText"
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
                <div class="card-title">Nhân sự theo phòng ban</div>
                <p class="card-sub">NV đang hoạt động · chia Full time / Part time / Chưa phân công</p>
              </div>
            </div>
          </template>
          <ChartColumnBasic
            :series="phongBanSeries"
            :categories="phongBanCategories"
            :colors="['#409eff', '#e6a23c', '#909399']"
            :height="320"
            :y-formatter="formatCount"
            :min-category-width="120"
            column-width="60%"
          />
        </CustomCard>
      </CustomCol>

      <CustomCol :xs="24" :lg="12">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Thu & chi theo phiếu — 12 tháng</div>
                <p class="card-sub">Phiếu đã duyệt · theo ngày cập nhật trạng thái</p>
              </div>
            </div>
          </template>
          <ChartColumnBasic
            :series="thuChiSeries"
            :categories="thuChiCategories"
            :colors="['#67c23a', '#f56c6c']"
            :height="320"
            :y-formatter="formatCompactMoney"
            :tooltip-formatter="formatMoney"
            :min-category-width="88"
            column-width="55%"
            :show-data-labels="false"
          />
        </CustomCard>
      </CustomCol>
    </CustomRow>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Coin, Money, TrendCharts, User, Wallet } from '@element-plus/icons-vue'
import { fetchTongQuanTaiChinhNhanSu } from '@/api/tongQuanTaiChinhNhanSu'
import { ChartColumnBasic } from '@/components/charts'
import StatCard from '@/components/dashboard/StatCard.vue'

defineOptions({ name: 'TaiChinhNhanSu' })

const monthPresets = [
  { key: 'this_month', label: 'Tháng này' },
  { key: 'last_month', label: 'Tháng trước' },
]

const selectedMonth = ref(currentMonthValue())
const appliedMonth = ref(currentMonthValue())
const loading = ref(false)
const stats = ref(null)

const activePreset = computed(() => {
  const month = selectedMonth.value
  if (!month) return null
  for (const preset of monthPresets) {
    if (getPresetMonth(preset.key) === month) return preset.key
  }
  return null
})

const statCards = computed(() => {
  const s = stats.value || emptyStats()
  const daChotLuong = s.da_chot_luong === true
  const tongDoanhThu = Number(s.doanh_thu_sddv || 0) + Number(s.doanh_thu_tp || 0)

  return [
    {
      key: 'doanh_thu_sddv',
      title: 'Tổng doanh thu SDDV',
      value: formatMoney(s.doanh_thu_sddv),
      hint: 'Tổng doanh thu SDDV',
      tone: 'success',
      icon: Money,
    },
    {
      key: 'doanh_thu_tp',
      title: 'Tổng doanh thu TP',
      value: formatMoney(s.doanh_thu_tp),
      hint: 'Tổng doanh thu TP',
      tone: 'warning',
      icon: Coin,
    },
    {
      key: 'loi_nhuan_truoc_thue',
      title: 'Lợi nhuận trước thuế',
      value: daChotLuong ? formatMoney(s.loi_nhuan_truoc_thue) : '',
      hint: daChotLuong
        ? `DT ${formatMoney(tongDoanhThu)} − (Chi ${formatMoney(s.tong_chi)} + Quỹ lương ${formatMoney(s.quy_luong)})`
        : '',
      pending: !daChotLuong,
      pendingText: 'Đợi chốt lương...',
      tone: daChotLuong ? 'success' : 'warning',
      icon: TrendCharts,
    },
    {
      key: 'tong_thu',
      title: 'Tổng thu theo phiếu',
      value: formatMoney(s.tong_thu),
      hint: 'Phiếu thu đã duyệt trong tháng',
      tone: 'success',
      icon: Money,
    },
    {
      key: 'tong_chi',
      title: 'Tổng chi theo phiếu',
      value: formatMoney(s.tong_chi),
      hint: 'Phiếu chi đã duyệt trong tháng',
      tone: 'danger',
      icon: Wallet,
    },
    {
      key: 'tong_nhan_su',
      title: 'Tổng nhân sự',
      value: `${formatCount(s.tong_nhan_su)} (${formatCount(s.nhan_su_active)} hoạt động)`,
      hint: 'User có hồ sơ nhân viên',
      tone: 'info',
      icon: User,
    },
  ]
})

const phongBanCategories = computed(
  () => stats.value?.bieu_do_nhan_su_theo_phong_ban?.categories || [],
)

const phongBanSeries = computed(() => {
  const chart = stats.value?.bieu_do_nhan_su_theo_phong_ban
  const len = phongBanCategories.value.length
  const pad = (arr) => Array.from({ length: len }, (_, i) => Number(arr?.[i]) || 0)

  return [
    { name: 'Full time', data: pad(chart?.full_time) },
    { name: 'Part time', data: pad(chart?.part_time) },
    { name: 'Chưa phân công', data: pad(chart?.chua_phan_cong) },
  ]
})

const thuChiCategories = computed(
  () => stats.value?.bieu_do_thu_chi_12_thang?.categories || [],
)

const thuChiSeries = computed(() => {
  const chart = stats.value?.bieu_do_thu_chi_12_thang
  const len = thuChiCategories.value.length
  const pad = (arr) => Array.from({ length: len }, (_, i) => Number(arr?.[i]) || 0)

  return [
    { name: 'Tổng thu', data: pad(chart?.tong_thu) },
    { name: 'Tổng chi', data: pad(chart?.tong_chi) },
  ]
})

onMounted(() => {
  loadStats()
})

function applyFilter() {
  if (!selectedMonth.value) {
    ElMessage.warning('Vui lòng chọn tháng')
    return
  }
  appliedMonth.value = selectedMonth.value
  loadStats()
}

function applyMonthPreset(key) {
  selectedMonth.value = getPresetMonth(key)
  applyFilter()
}

async function loadStats() {
  const thang = appliedMonth.value
  if (!thang) return

  loading.value = true
  try {
    const { data } = await fetchTongQuanTaiChinhNhanSu(
      { thang },
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
    tong_thu: 0,
    tong_chi: 0,
    da_chot_luong: false,
    loi_nhuan_truoc_thue: null,
    doanh_thu_sddv: 0,
    doanh_thu_tp: 0,
    quy_luong: 0,
    quy_luong_meta: {
      so_nhan_vien: 0,
      da_chot: false,
      nguon: '',
    },
    tong_nhan_su: 0,
    nhan_su_active: 0,
    bieu_do_nhan_su_theo_phong_ban: {
      categories: [],
      ids: [],
      full_time: [],
      part_time: [],
      chua_phan_cong: [],
    },
    bieu_do_thu_chi_12_thang: {
      categories: [],
      tong_thu: [],
      tong_chi: [],
    },
  }
}

function currentMonthValue() {
  const now = new Date()
  const y = now.getFullYear()
  const m = String(now.getMonth() + 1).padStart(2, '0')
  return `${y}-${m}`
}

function getPresetMonth(key) {
  const now = new Date()
  if (key === 'this_month') {
    return currentMonthValue()
  }
  if (key === 'last_month') {
    const d = new Date(now.getFullYear(), now.getMonth() - 1, 1)
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    return `${y}-${m}`
  }
  return currentMonthValue()
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

function formatCount(val) {
  return Number(val || 0).toLocaleString('vi-VN')
}
</script>

<style scoped lang="scss">
.tai-chinh-nhan-su.page-list {
  gap: 14px;
}

.filter-card {
  .toolbar {
    :deep(> .el-col) {
      min-width: 0;
    }

    :deep(.el-date-editor) {
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
