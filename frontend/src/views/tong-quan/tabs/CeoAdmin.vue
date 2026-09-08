<template>
  <div class="ceo-admin page-list" v-loading="loading">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="6" :lg="5">
          <CustomDatePicker
            v-model="selectedMonth"
            type="month"
            placeholder="Chọn tháng"
            format="MM/YYYY"
            value-format="YYYY-MM"
            :clearable="false"
            style="width: 100%"
            @change="loadStats"
          />
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="18" :lg="19">
          <div class="toolbar-actions">
            <CustomButton
              :type="selectedMonth === previousMonthValue() ? 'primary' : 'default'"
              :plain="selectedMonth !== previousMonthValue()"
              @click="selectMonth(previousMonthValue())"
            >
              Tháng trước
            </CustomButton>
            <CustomButton
              :type="selectedMonth === currentMonthValue() ? 'primary' : 'default'"
              :plain="selectedMonth !== currentMonthValue()"
              @click="selectMonth(currentMonthValue())"
            >
              Tháng này
            </CustomButton>
            <CustomButton type="primary" plain :loading="loading" @click="loadStats">
              Làm mới
            </CustomButton>
            <span class="filter-hint">Kỳ báo cáo · {{ formatMonthLabel(selectedMonth) }}</span>
          </div>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomRow :gutter="12" class="dash-row">
      <CustomCol
        v-for="card in statCards"
        :key="card.key"
        :xs="24"
        :sm="12"
        :md="8"
        :lg="8"
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
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  Coin,
  Document,
  Money,
  TrendCharts,
  User,
  UserFilled,
  Wallet,
} from '@element-plus/icons-vue'
import { fetchTongQuanCeoAdmin } from '@/api/tongQuanCeo'
import StatCard from '@/components/dashboard/StatCard.vue'

defineOptions({ name: 'CeoAdmin' })

const selectedMonth = ref(currentMonthValue())
const loading = ref(false)
const stats = ref(null)

const statCards = computed(() => {
  const s = stats.value || emptyStats()

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
      key: 'loi_nhuan_truoc_thue',
      title: 'Lợi nhuận trước thuế',
      value: formatMoney(s.loi_nhuan_truoc_thue),
      hint: `Thu ${formatMoney(s.tong_thu_da_duyet)} − Chi ${formatMoney(s.tong_chi_da_duyet)} (đã duyệt)`,
      tone: 'success',
      icon: TrendCharts,
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
      key: 'tong_khach_hang',
      title: 'Tổng khách hàng',
      value: formatCount(s.tong_khach_hang),
      hint: khachHangHint(s.khach_hang),
      tone: 'info',
      icon: User,
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
      icon: TrendCharts,
    },
    {
      key: 'tong_nhan_su',
      title: 'Tổng nhân sự',
      value: `${formatCount(s.tong_nhan_su)} (${formatCount(s.nhan_su_active)} active)`,
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

onMounted(() => {
  loadStats()
})

async function loadStats() {
  if (!selectedMonth.value) return
  loading.value = true
  try {
    const { data } = await fetchTongQuanCeoAdmin(
      { thang: selectedMonth.value },
      { skipLoading: true },
    )
    stats.value = data
  } catch {
    stats.value = emptyStats()
  } finally {
    loading.value = false
  }
}

function selectMonth(value) {
  selectedMonth.value = value
  loadStats()
}

function emptyStats() {
  return {
    tong_doanh_thu: 0,
    doanh_thu_sddv: 0,
    loi_nhuan_truoc_thue: 0,
    tong_thu_da_duyet: 0,
    tong_chi_da_duyet: 0,
    so_hop_dong_ky: 0,
    so_hop_dong_sddv_ky: 0,
    so_hop_dong_cho_thue_ky: 0,
    tong_khach_hang: 0,
    khach_hang: null,
    tong_cp_quang_cao: 0,
    tong_lead_quang_cao: 0,
    cpl_trung_binh: 0,
    tong_nhan_su: 0,
    nhan_su_active: 0,
    quy_luong: 0,
    quy_luong_meta: null,
  }
}

function khachHangHint(kh) {
  if (!kh) return 'Gom theo SĐT từ note, HĐ SDDV, HĐ cho thuê'
  return `Note ${formatCount(kh.tu_note_khach_moi)} · SDDV ${formatCount(kh.tu_hop_dong_sddv)} · Thuê TP ${formatCount(kh.tu_hop_dong_cho_thue)}`
}

function quyLuongHint(meta) {
  if (!meta) return 'SUM thực nhận toàn NV trong kỳ'
  if (meta.nguon === 'khong_co_chot') {
    return 'Tháng cũ chưa chốt lương — không có dữ liệu'
  }
  const source = meta.da_chot ? 'đã chốt' : 'tính realtime'
  return `SUM thực nhận · ${formatCount(meta.so_nhan_vien)} NV (${source})`
}

function currentMonthValue() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

function previousMonthValue() {
  const now = new Date()
  now.setDate(1)
  now.setMonth(now.getMonth() - 1)
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

function formatMonthLabel(value) {
  if (!value || !String(value).includes('-')) return '—'
  const [y, m] = String(value).split('-')
  return `${m}/${y}`
}

function formatMoney(val) {
  return `${Number(val || 0).toLocaleString('vi-VN')} ₫`
}

function formatCount(val) {
  return Number(val || 0).toLocaleString('vi-VN')
}
</script>

<style scoped lang="scss">
.ceo-admin.page-list {
  gap: 14px;
}

.filter-hint {
  margin-left: 4px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.dash-row {
  row-gap: 12px;

  :deep(> .el-col) {
    display: flex;
    min-width: 0;
  }

  :deep(> .el-col > .stat-card) {
    width: 100%;
    flex: 1;
    min-width: 0;
  }
}
</style>
