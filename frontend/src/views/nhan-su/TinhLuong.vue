<template>
  <div class="tinh-luong page-list">
    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <div class="summary-title-block">
            <span class="card-title">Bảng lương của tôi</span>
            <span v-if="payload?.nhan_vien?.name" class="summary-subtitle">
              {{ payload.nhan_vien.name }}
              <template v-if="employeeTypeLabel"> · {{ employeeTypeLabel }}</template>
            </span>
          </div>
          <div class="card-header-actions">
            <CustomDatePicker
              v-model="selectedMonth"
              type="month"
              placeholder="Chọn tháng"
              format="MM/YYYY"
              value-format="YYYY-MM"
              :clearable="false"
              style="width: 160px"
              @change="loadData"
            />
            <CustomButton type="primary" plain :loading="loading" @click="loadData">
              <CustomIcon><Search /></CustomIcon>
              Xem bảng lương
            </CustomButton>
          </div>
        </div>
      </template>

      <section class="calendar-section" :class="{ 'is-collapsed': !calendarExpanded }">
        <button
          type="button"
          class="calendar-section__header"
          @click="calendarExpanded = !calendarExpanded"
        >
          <div class="calendar-section__title-wrap">
            <span class="calendar-section__title">
              Chi tiết theo ngày · tháng {{ formatMonthLabel(selectedMonth) }}
            </span>
            <span class="calendar-section__hint">
              {{ calendarExpanded ? 'Thu gọn' : 'Mở rộng' }}
            </span>
          </div>
          <CustomIcon
            class="calendar-section__arrow"
            :class="{ 'is-expanded': calendarExpanded }"
          >
            <ArrowDown />
          </CustomIcon>
        </button>

        <div v-show="calendarExpanded" class="calendar-section__body">
          <CustomTable
            v-loading="loading"
            :data="days"
            stripe
            border
            row-key="ngay"
            show-summary
            :summary-method="getSummaries"
            style="width: 100%"
            :empty-text="loading ? 'Đang tải...' : 'Chưa có dữ liệu'"
            class="salary-table"
          >
            <CustomTableColumn label="Ngày" width="120" fixed="left" align="center">
              <template #default="{ row }">
                <div class="day-cell" :class="{ 'day-cell--weekend': row.is_weekend }">
                  <span class="day-cell__date">{{ row.thu }}-{{ formatDateLabel(row.ngay) }}</span>
                  <!-- <span class="day-cell__dow"></span> -->
                </div>
              </template>
            </CustomTableColumn>

            <CustomTableColumn label="Giờ làm" min-width="140" align="center">
              <template #default="{ row }">
                <span v-if="row.co_diem_danh" class="time-range">
                  {{ formatTime(row.gio_vao) }} - {{ formatTime(row.gio_ra) }}
                </span>
                <span v-else class="muted">—</span>
              </template>
            </CustomTableColumn>

            <CustomTableColumn
              prop="gio_lam_tang_ca"
              label="Giờ tăng ca"
              width="110"
              align="center"
            >
              <template #default="{ row }">
                {{ formatHoursValue(row.gio_lam_tang_ca) }}
              </template>
            </CustomTableColumn>

            <!--
              Hoa hồng theo ngày (backend tính sẵn):
              - HĐ TP: mỗi HĐ cho thuê trang phục có nguoi_cho_thue = user đăng nhập,
                gắn theo ngay_thue, loại trừ trạng thái moi_tao/nhap/da_huy.
                Công thức ngày = số HĐ trong ngày × đơn giá hoa_hong_hop_dong_trang_phuc (hồ sơ NV).
              - HĐ SDDV: mỗi HĐ sử dụng dịch vụ có nguoi_tao_id = user đăng nhập,
                gắn theo ngày tạo (created_at, Asia/Ho_Chi_Minh), loại trừ moi_tao/nhap/da_huy.
                Công thức ngày = số HĐ trong ngày × đơn giá hoa_hong_hop_dong_sddv (hồ sơ NV).
            -->
            <CustomTableColumn label="Hoa hồng" align="center">
              <CustomTableColumn
                prop="hoa_hong_hd_tp"
                label="HĐ TP"
                min-width="120"
                align="right"
              >
                <template #default="{ row }">
                  {{ formatMoney(row.hoa_hong?.hd_tp) }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                prop="hoa_hong_hd_sddv"
                label="HĐ SDDV"
                min-width="120"
                align="right"
              >
                <template #default="{ row }">
                  {{ formatMoney(row.hoa_hong?.hd_sddv) }}
                </template>
              </CustomTableColumn>
            </CustomTableColumn>

            <CustomTableColumn label="Sản xuất" align="center">
              <CustomTableColumn
                prop="san_xuat_make"
                label="Make"
                min-width="110"
                align="right"
              >
                <template #default="{ row }">
                  {{ formatMoney(row.san_xuat?.make) }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                prop="san_xuat_chup"
                label="Chụp"
                min-width="110"
                align="right"
              >
                <template #default="{ row }">
                  {{ formatMoney(row.san_xuat?.chup) }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                prop="san_xuat_quay_phim"
                label="Quay phim"
                min-width="110"
                align="right"
              >
                <template #default="{ row }">
                  {{ formatMoney(row.san_xuat?.quay_phim) }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                prop="san_xuat_edit"
                label="Edit"
                min-width="110"
                align="right"
              >
                <template #default="{ row }">
                  {{ formatMoney(row.san_xuat?.edit) }}
                </template>
              </CustomTableColumn>
            </CustomTableColumn>
          </CustomTable>
        </div>
      </section>
    </CustomCard>

    <CustomCard shadow="hover" class="fixed-salary-card" v-loading="loading">
      <template #header>
        <div class="card-header">
          <span class="card-title">Tổng hợp lương tháng</span>
          <span class="summary-subtitle">
            Tháng {{ formatMonthLabel(selectedMonth) }}
          </span>
        </div>
      </template>

      <div class="summary-groups">
        <div
          v-for="group in summaryGroups"
          :key="group.key"
          class="summary-group"
        >
          <div class="summary-group__title">
            <span class="summary-group__code">{{ group.code }}</span>
            {{ group.title }}
          </div>
          <CustomTable
            :data="group.rows"
            stripe
            border
            row-key="key"
            show-summary
            :summary-method="(param) => getGroupSummaries(param, group)"
            style="width: 100%"
            class="summary-table"
            :empty-text="'Chưa có dữ liệu'"
          >
            <CustomTableColumn label="STT" width="64" align="center">
              <template #default="{ $index }">
                {{ $index + 1 }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn prop="label" label="Khoản mục" min-width="220" />
            <CustomTableColumn prop="value" label="Số tiền" min-width="160" align="right">
              <template #default="{ row }">
                {{ formatMoneyCell(row.value) }}
              </template>
            </CustomTableColumn>
          </CustomTable>
        </div>

        <div class="net-pay-box">
          <CustomTable
            :data="netPayRows"
            border
            row-key="key"
            style="width: 100%"
            class="summary-table net-pay-table"
          >
            <CustomTableColumn prop="label" label="Công thức" min-width="220" />
            <CustomTableColumn prop="value" label="Số tiền" min-width="160" align="right">
              <template #default="{ row }">
                <span :class="{ 'net-pay-value': row.key === 'thuc_nhan' }">
                  {{ formatMoneyCell(row.value) }}
                </span>
              </template>
            </CustomTableColumn>
          </CustomTable>
        </div>
      </div>
    </CustomCard>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ArrowDown, Search } from '@element-plus/icons-vue'
import { fetchBangLuongChiTietTheoNgay } from '@/api/tinhLuong'
import {
  CustomButton,
  CustomCard,
  CustomDatePicker,
  CustomIcon,
  CustomTable,
  CustomTableColumn,
} from '@/components/element'

const MONEY_PROPS = [
  'hoa_hong_hd_tp',
  'hoa_hong_hd_sddv',
  'san_xuat_make',
  'san_xuat_chup',
  'san_xuat_quay_phim',
  'san_xuat_edit',
]

const GROUP_A_DEFS = [
  { key: 'luong_cung', label: 'Lương cứng', source: 'luong_co_dinh' },
  { key: 'luong_mem', label: 'Lương mềm', source: 'luong_co_dinh' },
  { key: 'phu_cap', label: 'Phụ cấp', source: 'phu_cap' },
  { key: 'phu_cap_xang', label: 'Phụ cấp xăng', source: 'phu_cap' },
  { key: 'phu_cap_an_trua', label: 'Phụ cấp ăn trưa', source: 'phu_cap' },
  { key: 'phu_cap_dien_thoai', label: 'Phụ cấp điện thoại', source: 'phu_cap' },
  { key: 'phu_cap_nha_o', label: 'Phụ cấp nhà ở', source: 'phu_cap' },
]

/**
 * Nhóm B — thu nhập phát sinh trong tháng.
 *
 * Hoa hồng HĐ TP (hoa_hong_hd_tp):
 * - Nguồn: hop_dong_cho_thue_trang_phuc với nguoi_cho_thue = user đăng nhập
 * - Ngày tính: ngay_thue; bỏ HĐ trạng thái moi_tao / nhap / da_huy
 * - Đơn giá: nhan_vien.luong_thuong_phu_cap.hoa_hong_hop_dong_trang_phuc.value
 * - Công thức ngày: so_hop_dong_trong_ngay × don_gia
 * - Tổng tháng: Σ hoa hồng theo từng ngày trong tháng
 *
 * Hoa hồng HĐ SDDV (hoa_hong_hd_sddv):
 * - Nguồn: hop_dong_su_dung_dich_vu với nguoi_tao_id = user đăng nhập
 * - Ngày tính: DATE(created_at) theo Asia/Ho_Chi_Minh; bỏ HĐ moi_tao / nhap / da_huy
 * - Đơn giá: nhan_vien.luong_thuong_phu_cap.hoa_hong_hop_dong_sddv.value
 * - Công thức ngày: so_hop_dong_trong_ngay × don_gia
 * - Tổng tháng: Σ hoa hồng theo từng ngày trong tháng
 */
const GROUP_B_DEFS = [
  { key: 'tong_luong_theo_gio', label: 'Tổng lương theo giờ' },
  { key: 'tong_tang_ca', label: 'Tổng tăng ca' },
  { key: 'hoa_hong_hd_tp', label: 'Hoa hồng HĐ TP' },
  { key: 'hoa_hong_hd_sddv', label: 'Hoa hồng HĐ SDDV' },
  { key: 'san_xuat_make', label: 'Lương make' },
  { key: 'san_xuat_chup', label: 'Lương chụp' },
  { key: 'san_xuat_quay_phim', label: 'Lương quay phim' },
  { key: 'san_xuat_edit', label: 'Lương edit' },
  { key: 'thuong_chuyen_can', label: 'Thưởng chuyên cần' },
]

const GROUP_C_DEFS = [
  { key: 'tien_phat_di_muon', label: 'Tiền đi muộn' },
  { key: 'tien_phat_ve_som', label: 'Tiền về sớm' },
  { key: 'phat_phat_sinh', label: 'Phạt phát sinh' },
]

const loading = ref(false)
const selectedMonth = ref(currentMonthValue())
const payload = ref(null)
const calendarExpanded = ref(true)

const days = computed(() => payload.value?.days || [])

const employeeTypeLabel = computed(() => {
  const type = payload.value?.nhan_vien?.loai_nhan_vien
  if (type === 'full_time') return 'Full time'
  if (type === 'part_time') return 'Part time'
  return ''
})

const summaryGroups = computed(() => {
  const nv = payload.value?.nhan_vien || {}
  const tong = payload.value?.tong_ket || {}

  const rowsA = GROUP_A_DEFS.map((item) => ({
    key: item.key,
    label: item.label,
    value: Number(nv[item.source]?.[item.key] || 0),
  }))
  const rowsB = GROUP_B_DEFS.map((item) => ({
    key: item.key,
    label: item.label,
    value: Number(tong[item.key] || 0),
  }))
  const rowsC = GROUP_C_DEFS.map((item) => ({
    key: item.key,
    label: item.label,
    value: Number(tong[item.key] || 0),
  }))

  return [
    {
      key: 'a',
      code: 'A',
      title: 'Lương cứng & phụ cấp',
      totalLabel: 'Tổng A',
      total: Number(tong.tong_a || sumRows(rowsA)),
      rows: rowsA,
    },
    {
      key: 'b',
      code: 'B',
      title: 'Thu nhập phát sinh',
      totalLabel: 'Tổng B',
      total: Number(tong.tong_b || sumRows(rowsB)),
      rows: rowsB,
    },
    {
      key: 'c',
      code: 'C',
      title: 'Khấu trừ',
      totalLabel: 'Tổng C',
      total: Number(tong.tong_c || sumRows(rowsC)),
      rows: rowsC,
    },
  ]
})

const netPayRows = computed(() => {
  const tong = payload.value?.tong_ket || {}
  const tongA = Number(tong.tong_a || 0)
  const tongB = Number(tong.tong_b || 0)
  const tongC = Number(tong.tong_c || 0)
  return [
    { key: 'tong_a', label: 'Tổng A', value: tongA },
    { key: 'tong_b', label: 'Tổng B', value: tongB },
    { key: 'tong_c', label: 'Tổng C', value: tongC },
    {
      key: 'thuc_nhan',
      label: 'Thực nhận (A + B − C)',
      value: Number(tong.thuc_nhan ?? tongA + tongB - tongC),
    },
  ]
})

function sumRows(rows) {
  return rows.reduce((sum, row) => sum + (Number(row.value) || 0), 0)
}

function currentMonthValue() {
  const now = new Date()
  const y = now.getFullYear()
  const m = String(now.getMonth() + 1).padStart(2, '0')
  return `${y}-${m}`
}

function formatMonthLabel(value) {
  if (!value || !String(value).includes('-')) return '—'
  const [y, m] = String(value).split('-')
  return `${m}/${y}`
}

function formatDateLabel(value) {
  if (!value) return '—'
  const [y, m, d] = String(value).slice(0, 10).split('-')
  if (!y || !m || !d) return '—'
  return `${d}/${m}`
}

function formatTime(value) {
  if (!value) return '—'
  const str = String(value)
  if (str.includes('T')) return str.slice(11, 16)
  if (str.includes(' ')) return str.slice(11, 16)
  return str.slice(0, 5)
}

function formatHoursValue(value) {
  const num = Number(value)
  if (!Number.isFinite(num) || num <= 0) return '—'
  return num.toLocaleString('vi-VN', { maximumFractionDigits: 2 })
}

function formatMoney(value) {
  const num = Number(value)
  if (!Number.isFinite(num) || num === 0) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function formatMoneyCell(value) {
  const num = Number(value)
  if (!Number.isFinite(num)) return '0 ₫'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function getSummaries({ columns }) {
  const tong = payload.value?.tong_ket || {}
  return columns.map((column, index) => {
    if (index === 0) return 'Tổng'

    const prop = column.property
    if (!prop) return ''

    if (prop === 'gio_lam_tang_ca') {
      return formatHoursValue(tong.gio_lam_tang_ca)
    }

    if (MONEY_PROPS.includes(prop)) {
      return formatMoney(tong[prop])
    }

    return ''
  })
}

function getGroupSummaries({ columns }, group) {
  return columns.map((column, index) => {
    if (index === 0) return ''
    if (column.property === 'label') return group.totalLabel
    if (column.property === 'value') return formatMoneyCell(group.total)
    return ''
  })
}

async function loadData() {
  if (!selectedMonth.value) {
    payload.value = null
    return
  }

  loading.value = true
  try {
    const { data } = await fetchBangLuongChiTietTheoNgay(
      { thang: selectedMonth.value },
      { skipLoading: true },
    )
    payload.value = data
  } catch {
    payload.value = null
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped lang="scss">
.summary-title-block {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.summary-subtitle {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.calendar-section {
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
  overflow: hidden;

  &__header {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border: 0;
    background: var(--el-fill-color-light);
    cursor: pointer;
    text-align: left;
  }

  &__title-wrap {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  &__title {
    font-weight: 600;
    color: var(--el-text-color-primary);
  }

  &__hint {
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }

  &__arrow {
    transition: transform 0.2s ease;
    color: var(--el-text-color-secondary);

    &.is-expanded {
      transform: rotate(180deg);
    }
  }

  &__body {
    padding: 12px;
  }

  &.is-collapsed {
    .calendar-section__header {
      border-radius: 10px;
    }
  }
}

.day-cell {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  line-height: 1.2;

  &__date {
    font-weight: 600;
  }

  &__dow {
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }

  &--weekend {
    .day-cell__date,
    .day-cell__dow {
      color: var(--el-color-danger);
    }
  }
}

.time-range {
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.muted {
  color: var(--el-text-color-placeholder);
}

.salary-table {
  :deep(.el-table__footer-wrapper td) {
    font-weight: 700;
    background: var(--el-fill-color-light);
  }
}

.summary-groups {
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
  align-items: start;

  @media (min-width: 768px) {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.summary-group {
  min-width: 0;

  &__title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 650;
    margin-bottom: 10px;
    color: var(--el-text-color-primary);
  }

  &__code {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    padding: 0 8px;
    border-radius: 8px;
    background: var(--el-color-primary);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
  }
}

.summary-table {
  :deep(.el-table__footer-wrapper td) {
    font-weight: 700;
    background: var(--el-fill-color-light);
  }
}

.net-pay-box {
  min-width: 0;
  padding: 12px;
  border-radius: 10px;
  border: 1px solid color-mix(in srgb, var(--el-color-primary) 28%, transparent);
  background: color-mix(in srgb, var(--el-color-primary) 6%, transparent);
}

.net-pay-value {
  font-weight: 700;
  color: var(--el-color-primary);
}

.net-pay-table {
  :deep(.el-table__row:last-child td) {
    font-weight: 700;
  }
}
</style>
