<template>
  <div class="ceo-admin page-list" v-loading="loading">
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
        :md="6"
        :lg="6"
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
      <CustomCol :xs="24" :lg="8">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Cơ cấu doanh thu</div>
                <p class="card-sub">SDDV vs cho thuê TP · theo ngày tạo trong kỳ lọc</p>
              </div>
            </div>
          </template>
          <ChartDonut
            :series="revenueMixSeries"
            :labels="revenueMixLabels"
            :colors="['#409eff', '#e6a23c']"
            :height="320"
            total-label="Tổng DT"
            :total-formatter="formatCompactMoney"
            :tooltip-formatter="formatMoney"
            :options="revenueMixOptions"
          />
        </CustomCard>
      </CustomCol>

      <CustomCol :xs="24" :lg="16">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Doanh thu & số HĐ — 12 tháng</div>
                <p class="card-sub">Cột: DT (trục phải) · Đường: số HĐ (trục trái)</p>
              </div>
            </div>
          </template>
          <ChartCombo
            :series="trend12Series"
            :categories="trend12Categories"
            :height="320"
            :colors="['#67c23a', '#f56c6c', '#409eff', '#e6a23c']"
            :y-formatter="formatCount"
            :y-formatter-right="formatCompactMoney"
            left-axis-title="Số HĐ"
            right-axis-title="Doanh thu"
            :min-category-width="72"
            column-width="55%"
            dual-axis
          />
        </CustomCard>
      </CustomCol>
    </CustomRow>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import {
  ChatDotRound,
  Coin,
  DataAnalysis,
  Document,
  FolderOpened,
  Histogram,
  Money,
  Opportunity,
  Ticket,
  TrendCharts,
  User,
  UserFilled,
  Wallet,
} from '@element-plus/icons-vue'
import { fetchTongQuanCeoAdmin } from '@/api/tongQuanCeo'
import { ChartCombo, ChartDonut } from '@/components/charts'
import StatCard from '@/components/dashboard/StatCard.vue'

defineOptions({ name: 'CeoAdmin' })

const datePresets = [
  // { key: 'today', label: 'Hôm nay' },
  { key: 'this_week', label: 'Tuần này' },
  { key: 'last_week', label: 'Tuần trước' },
  { key: 'this_month', label: 'Tháng này' },
  { key: 'last_month', label: 'Tháng trước' },
  // { key: 'this_quarter', label: 'Quý này' },
  // { key: 'last_quarter', label: 'Quý trước' },
  // { key: 'this_year', label: 'Năm nay' },
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

const appliedRangeLabel = computed(() => {
  const range = appliedRange.value
  if (!range?.[0] || !range?.[1]) return ''
  return `${formatDateLabel(range[0])} — ${formatDateLabel(range[1])}`
})

const statCards = computed(() => {
  const s = stats.value || emptyStats()
  const daChotLuong = s.da_chot_luong === true

  return [
    {
      key: 'tong_doanh_thu',
      title: 'Tổng doanh thu',
      value: formatMoney(s.tong_doanh_thu),
      hint: 'DT SDDV (thanh toán trong kỳ) + DT cho thuê TP',
      tone: 'primary',
      icon: Wallet,
    },
    {
      key: 'doanh_thu_sddv',
      title: 'Doanh thu HĐ SDDV',
      value: formatMoney(s.doanh_thu_sddv),
      hint: 'Tổng các lần thanh toán SDDV có thời gian trong kỳ',
      tone: 'success',
      icon: Money,
    },
    {
      key: 'doanh_thu_trung_binh',
      title: 'Doanh thu HĐ SDDV trung bình',
      value: formatMoney(s.doanh_thu_trung_binh),
      hint: `DT SDDV ${formatMoney(s.doanh_thu_sddv)} / ${formatCount(s.so_hop_dong_sddv_ky)} HĐ SDDV`,
      tone: 'info',
      icon: Ticket,
    },
    {
      key: 'loi_nhuan_truoc_thue',
      title: 'Lợi nhuận trước thuế',
      value: daChotLuong ? formatMoney(s.loi_nhuan_truoc_thue) : '',
      hint: daChotLuong
        ? `DT ${formatMoney(s.tong_doanh_thu)} − (Chi ${formatMoney(s.tong_chi_da_duyet)} + Quỹ lương ${formatMoney(s.quy_luong)})`
        : '',
      pending: !daChotLuong,
      pendingText: 'Đợi chốt lương...',
      tone: daChotLuong ? 'success' : 'warning',
      icon: TrendCharts,
    },
    {
      key: 'bien_loi_nhuan_gop',
      title: 'Biên lợi nhuận gộp',
      value: daChotLuong ? formatPercent(s.bien_loi_nhuan_gop) : '',
      hint: daChotLuong
        ? `LNTT ${formatMoney(s.loi_nhuan_truoc_thue)} / DT ${formatMoney(s.tong_doanh_thu)}`
        : '',
      pending: !daChotLuong,
      pendingText: 'Đợi chốt lương...',
      tone: daChotLuong ? 'info' : 'warning',
      icon: DataAnalysis,
    },
    {
      key: 'so_hop_dong_ky',
      title: 'Hợp đồng ký trong kỳ',
      value: formatCount(s.so_hop_dong_ky),
      hint: `SDDV ${formatCount(s.so_hop_dong_sddv_ky)} · Cho thuê ${formatCount(s.so_hop_dong_cho_thue_ky)}`,
      tone: 'primary',
      icon: Document,
    },
    {
      key: 'hd_dang_xu_ly',
      title: 'HĐ đang xử lý',
      value: formatCount(s.hd_dang_xu_ly),
      hint: `SDDV đang thực hiện ${formatCount(s.hd_sddv_dang_thuc_hien)} · Thuê TP đang thuê ${formatCount(s.hd_cho_thue_dang_thue)}`,
      tone: 'warning',
      icon: FolderOpened,
    },
    {
      key: 'so_khach_den',
      title: 'Số khách đến',
      value: formatCount(s.so_khach_den),
      hint: 'Note khách mới có ngày đến trong kỳ',
      tone: 'info',
      icon: User,
    },
    {
      key: 'tlc_khach_den',
      title: 'TLC khách đến',
      value: formatPercent(s.tlc_khach_den),
      hint: `Có tra cứu HĐ ${formatCount(s.so_khach_den_co_tra_cuu_hd)} / Đến ${formatCount(s.so_khach_den)}`,
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
      key: 'ty_le_chot',
      title: 'Tỷ lệ chốt',
      value: formatPercent(s.ty_le_chot),
      hint: `HĐ SDDV ký ${formatCount(s.so_hop_dong_sddv_ky)} / Đã đến ${formatCount(s.so_note_da_den)}`,
      tone: 'success',
      icon: TrendCharts,
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
      key: 'tong_cp_quang_cao',
      title: 'Tổng CP quảng cáo',
      value: formatMoney(s.tong_cp_quang_cao),
      hint: 'CPQC TikTok + Facebook + Google trong kỳ',
      tone: 'danger',
      icon: Coin,
    },
    {
      key: 'cpl_trung_binh',
      title: 'CPL trung bình',
      value: formatMoney(s.cpl_trung_binh),
      hint: `Tổng CPQC / Tổng lead (${formatCount(s.tong_lead_quang_cao)} KH)`,
      tone: 'warning',
      icon: Histogram,
    },
    {
      key: 'tong_nhan_su',
      title: 'Tổng nhân sự',
      value: `${formatCount(s.tong_nhan_su)} (${formatCount(s.nhan_su_active)} hoạt động)`,
      hint: 'Số tài khoản có hồ sơ nhân viên',
      tone: 'info',
      icon: UserFilled,
    },
    {
      key: 'quy_luong',
      title: 'Quỹ lương',
      value: formatMoney(s.quy_luong),
      hint: quyLuongHint(s.quy_luong_meta),
      tone: 'warning',
      icon: Wallet,
    },
  ]
})

const revenueMixLabels = ['HĐ SDDV', 'Cho thuê TP']

const revenueMixSeries = computed(() => {
  const mix = stats.value?.co_cau_doanh_thu
  return [
    Number(mix?.doanh_thu_sddv) || 0,
    Number(mix?.doanh_thu_cho_thue) || 0,
  ]
})

const revenueMixOptions = {
  dataLabels: {
    formatter: (val, opts) => {
      const value = opts?.w?.config?.series?.[opts.seriesIndex]
      const rounded = Math.round(Number(val) || 0)
      if (value == null) return `${rounded}%`
      return `${formatCompactMoney(value)} (${rounded}%)`
    },
  },
}

const trend12Categories = computed(
  () => stats.value?.bieu_do_12_thang?.categories || [],
)

const trend12Series = computed(() => {
  const chart = stats.value?.bieu_do_12_thang
  // Line trước → trục trái (số HĐ); Column sau → trục phải (doanh thu)
  return [
    {
      name: 'Số HĐ SDDV',
      type: 'line',
      data: chart?.so_hop_dong_sddv || [],
    },
    {
      name: 'Số HĐ thuê TP',
      type: 'line',
      data: chart?.so_hop_dong_cho_thue || [],
    },
    {
      name: 'DT SDDV',
      type: 'column',
      data: chart?.doanh_thu_sddv || [],
    },
    {
      name: 'DT thuê TP',
      type: 'column',
      data: chart?.doanh_thu_cho_thue || [],
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
    const { data } = await fetchTongQuanCeoAdmin(
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
    tong_doanh_thu: 0,
    doanh_thu_sddv: 0,
    doanh_thu_trung_binh: 0,
    da_chot_luong: false,
    loi_nhuan_truoc_thue: null,
    bien_loi_nhuan_gop: null,
    tong_thu_da_duyet: 0,
    tong_chi_da_duyet: 0,
    so_hop_dong_ky: 0,
    so_hop_dong_sddv_ky: 0,
    so_hop_dong_cho_thue_ky: 0,
    hd_dang_xu_ly: 0,
    hd_sddv_dang_thuc_hien: 0,
    hd_cho_thue_dang_thue: 0,
    so_khach_den: 0,
    so_khach_den_co_tra_cuu_hd: 0,
    tlc_khach_den: 0,
    tong_note_khach_moi: 0,
    so_note_da_den: 0,
    ty_le_chot: 0,
    tong_inbox: 0,
    inbox_facebook: 0,
    inbox_tiktok: 0,
    tong_cp_quang_cao: 0,
    tong_lead_quang_cao: 0,
    cpl_trung_binh: 0,
    tong_nhan_su: 0,
    nhan_su_active: 0,
    quy_luong: 0,
    quy_luong_meta: null,
    co_cau_doanh_thu: {
      doanh_thu_sddv: 0,
      doanh_thu_cho_thue: 0,
      tong: 0,
    },
    bieu_do_12_thang: {
      categories: [],
      doanh_thu_sddv: [],
      doanh_thu_cho_thue: [],
      so_hop_dong_sddv: [],
      so_hop_dong_cho_thue: [],
    },
  }
}

function quyLuongHint(meta) {
  if (!meta) return 'SUM thực nhận toàn NV trong kỳ'
  if (meta.nguon === 'khong_co_chot') {
    return 'Tháng cũ chưa chốt lương — không có dữ liệu'
  }
  const source = meta.da_chot ? 'đã chốt' : meta.nguon === 'hon_hop' ? 'hỗn hợp' : 'tính realtime'
  const months = meta.so_thang > 1 ? ` · ${formatCount(meta.so_thang)} tháng` : ''
  return `SUM thực nhận · ${formatCount(meta.so_nhan_vien)} NV (${source})${months}`
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

function getQuarterStartMonth(monthIndex) {
  return Math.floor(monthIndex / 3) * 3
}

/** Khoảng ngày cho preset — tuần: T2→CN */
function getPresetRange(key) {
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const monday = getMonday(today)

  if (key === 'today') {
    const ymd = toYmd(today)
    return [ymd, ymd]
  }
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
  if (key === 'this_quarter') {
    const qStart = getQuarterStartMonth(now.getMonth())
    const start = new Date(now.getFullYear(), qStart, 1)
    const end = new Date(now.getFullYear(), qStart + 3, 0)
    return [toYmd(start), toYmd(end)]
  }
  if (key === 'last_quarter') {
    const qStart = getQuarterStartMonth(now.getMonth()) - 3
    const start = new Date(now.getFullYear(), qStart, 1)
    const end = new Date(now.getFullYear(), qStart + 3, 0)
    return [toYmd(start), toYmd(end)]
  }
  if (key === 'this_year') {
    const start = new Date(now.getFullYear(), 0, 1)
    const end = new Date(now.getFullYear(), 11, 31)
    return [toYmd(start), toYmd(end)]
  }
  return null
}

function formatDateLabel(value) {
  if (!value || !String(value).includes('-')) return '—'
  const [y, m, d] = String(value).split('-')
  return `${d}/${m}/${y}`
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
.ceo-admin.page-list {
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

.filter-hint {
  margin: 10px 0 0;
  font-size: 12px;
  color: var(--el-text-color-secondary);
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
