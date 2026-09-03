<template>
  <div class="bang-luong-chi-tiet" :class="{ 'bang-luong-chi-tiet--embedded': embedded }">
    <CustomCard shadow="hover" class="table-card" v-loading="loading">
      <template v-if="showDailyHeader" #header>
        <div class="card-header">
          <div class="summary-title-block">
            <span class="card-title">{{ dailyTitle }}</span>
            <span v-if="employeeSubtitle" class="summary-subtitle">
              {{ employeeSubtitle }}
            </span>
          </div>
          <div v-if="$slots.actions" class="card-header-actions">
            <slot name="actions" />
          </div>
        </div>
      </template>

      <el-collapse v-model="calendarActiveNames" class="calendar-collapse">
        <el-collapse-item name="daily">
          <template #title>
            <div class="daily-collapse-title">
              <span class="daily-collapse-title__text">
                Chi tiết theo ngày · tháng {{ formatMonthLabel(month) }}
              </span>
              <div class="daily-toolbar" @click.stop>
                <el-switch
                  v-model="showFutureDays"
                  inline-prompt
                  active-text="Hiện"
                  inactive-text="Ẩn"
                />
                <span class="daily-toolbar__label">Hiển thị ngày chưa đến</span>
              </div>
            </div>
          </template>
          <CustomTable
            :data="visibleDays"
            stripe
            border
            row-key="ngay"
            show-summary
            :summary-method="getSummaries"
            :max-height="dailyTableMaxHeight"
            style="width: 100%"
            :empty-text="loading ? 'Đang tải...' : 'Chưa có dữ liệu'"
            class="salary-table"
          >
            <CustomTableColumn label="Ngày" width="120" fixed="left" align="center">
              <template #default="{ row }">
                <div class="day-cell" :class="{ 'day-cell--weekend': row.is_weekend }">
                  <span class="day-cell__date">{{ row.thu }}-{{ formatDateLabel(row.ngay) }}</span>
                </div>
              </template>
            </CustomTableColumn>

            <CustomTableColumn :label="isPartTime ? 'Giờ vào/ra' : 'Giờ làm'" min-width="140" align="center">
              <template #default="{ row }">
                <span v-if="row.co_diem_danh" class="time-range">
                  {{ formatTime(row.gio_vao) }} - {{ formatTime(row.gio_ra) }}
                </span>
                <span v-else class="muted">—</span>
              </template>
            </CustomTableColumn>

            <template v-if="isPartTime">
              <CustomTableColumn
                prop="gio_lam_co_ban"
                label="Giờ làm"
                width="100"
                align="center"
              >
                <template #default="{ row }">
                  {{ formatHoursValue(row.gio_lam_co_ban) }}
                </template>
              </CustomTableColumn>

              <CustomTableColumn
                prop="luong_co_ban"
                label="Thành tiền"
                min-width="120"
                align="right"
              >
                <template #default="{ row }">
                  {{ formatMoney(row.luong_co_ban) }}
                </template>
              </CustomTableColumn>
            </template>

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

            <CustomTableColumn label="Hoa hồng" align="center">
              <CustomTableColumn
                prop="hoa_hong_hd_tp"
                label="HĐ TP"
                min-width="120"
                align="right"
              >
                <template #default="{ row }">
                  <CustomTooltip
                    v-if="hasHoaHongChiTiet(row, 'hd_tp')"
                    content="Xem chi tiết hoa hồng HĐ trang phục"
                    placement="top"
                  >
                    <button
                      type="button"
                      class="money-link"
                      @click="openHoaHongChiTiet(row, 'hd_tp')"
                    >
                      {{ formatMoney(row.hoa_hong?.hd_tp) }}
                    </button>
                  </CustomTooltip>
                  <span v-else>{{ formatMoney(row.hoa_hong?.hd_tp) }}</span>
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                prop="hoa_hong_hd_sddv"
                label="HĐ SDDV"
                min-width="120"
                align="right"
              >
                <template #default="{ row }">
                  <CustomTooltip
                    v-if="hasHoaHongChiTiet(row, 'hd_sddv')"
                    content="Xem chi tiết hoa hồng HĐ sử dụng dịch vụ"
                    placement="top"
                  >
                    <button
                      type="button"
                      class="money-link"
                      @click="openHoaHongChiTiet(row, 'hd_sddv')"
                    >
                      {{ formatMoney(row.hoa_hong?.hd_sddv) }}
                    </button>
                  </CustomTooltip>
                  <span v-else>{{ formatMoney(row.hoa_hong?.hd_sddv) }}</span>
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
                  <CustomTooltip
                    v-if="hasSanXuatChiTiet(row, 'make')"
                    content="Xem chi tiết lương make"
                    placement="top"
                  >
                    <button
                      type="button"
                      class="money-link"
                      @click="openSanXuatChiTiet(row, 'make')"
                    >
                      {{ formatMoney(row.san_xuat?.make) }}
                    </button>
                  </CustomTooltip>
                  <span v-else>{{ formatMoney(row.san_xuat?.make) }}</span>
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                prop="san_xuat_chup"
                label="Chụp"
                min-width="110"
                align="right"
              >
                <template #default="{ row }">
                  <CustomTooltip
                    v-if="hasSanXuatChiTiet(row, 'chup')"
                    content="Xem chi tiết lương chụp"
                    placement="top"
                  >
                    <button
                      type="button"
                      class="money-link"
                      @click="openSanXuatChiTiet(row, 'chup')"
                    >
                      {{ formatMoney(row.san_xuat?.chup) }}
                    </button>
                  </CustomTooltip>
                  <span v-else>{{ formatMoney(row.san_xuat?.chup) }}</span>
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                prop="san_xuat_quay_phim"
                label="Quay phim"
                min-width="110"
                align="right"
              >
                <template #default="{ row }">
                  <CustomTooltip
                    v-if="hasSanXuatChiTiet(row, 'quay_phim')"
                    content="Xem chi tiết lương quay phim"
                    placement="top"
                  >
                    <button
                      type="button"
                      class="money-link"
                      @click="openSanXuatChiTiet(row, 'quay_phim')"
                    >
                      {{ formatMoney(row.san_xuat?.quay_phim) }}
                    </button>
                  </CustomTooltip>
                  <span v-else>{{ formatMoney(row.san_xuat?.quay_phim) }}</span>
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                prop="san_xuat_edit"
                label="Edit"
                min-width="110"
                align="right"
              >
                <template #default="{ row }">
                  <CustomTooltip
                    v-if="hasSanXuatChiTiet(row, 'edit')"
                    content="Xem chi tiết lương edit"
                    placement="top"
                  >
                    <button
                      type="button"
                      class="money-link"
                      @click="openSanXuatChiTiet(row, 'edit')"
                    >
                      {{ formatMoney(row.san_xuat?.edit) }}
                    </button>
                  </CustomTooltip>
                  <span v-else>{{ formatMoney(row.san_xuat?.edit) }}</span>
                </template>
              </CustomTableColumn>
            </CustomTableColumn>
          </CustomTable>
        </el-collapse-item>
      </el-collapse>
    </CustomCard>

    <SanXuatChiTietModal ref="sanXuatChiTietModalRef" />
    <HoaHongChiTietModal ref="hoaHongChiTietModalRef" />

    <CustomCard shadow="hover" class="fixed-salary-card" v-loading="loading">
      <template #header>
        <div class="card-header">
          <span class="card-title">Tổng hợp lương tháng</span>
          <span class="summary-subtitle">
            Tháng {{ formatMonthLabel(month) }}
          </span>
        </div>
      </template>

      <CustomTable
        :data="summaryTableRows"
        stripe
        border
        row-key="key"
        style="width: 100%"
        class="summary-table"
        :row-class-name="getSummaryRowClassName"
        :empty-text="'Chưa có dữ liệu'"
      >
        <CustomTableColumn label="STT" width="88" align="center">
          <template #default="{ row }">
            <span v-if="row.kind === 'item'">{{ row.stt }}</span>
            <button
              v-else-if="row.kind === 'section'"
              type="button"
              class="summary-section-toggle"
              :aria-expanded="isSummarySectionExpanded(row.code)"
              :aria-label="`${isSummarySectionExpanded(row.code) ? 'Thu gọn' : 'Mở rộng'} nhóm ${row.code}`"
              @click="toggleSummarySection(row.code)"
            >
              <CustomIcon
                class="summary-section-toggle__icon"
                :class="{ 'is-expanded': isSummarySectionExpanded(row.code) }"
              >
                <ArrowRight />
              </CustomIcon>
              <span class="summary-section-code">{{ row.code }}</span>
            </button>
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="label" label="Khoản mục" min-width="260">
          <template #default="{ row }">
            <button
              v-if="row.kind === 'section'"
              type="button"
              class="summary-section-label-btn"
              @click="toggleSummarySection(row.code)"
            >
              {{ row.label }}
            </button>
            <span
              v-else
              :class="{
                'summary-total-label': row.kind === 'total',
                'summary-net-label': row.kind === 'net',
              }"
            >
              {{ row.label }}
            </span>
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="value" label="Số tiền" min-width="160" align="right">
          <template #default="{ row }">
            <span
              v-if="row.kind === 'section' ? !isSummarySectionExpanded(row.code) : true"
              :class="{
                'summary-total-value': row.kind === 'section' || row.kind === 'total',
                'summary-net-value': row.kind === 'net',
              }"
            >
              {{ formatMoneyCell(row.value) }}
            </span>
          </template>
        </CustomTableColumn>
      </CustomTable>
    </CustomCard>
  </div>
</template>

<script setup>
import { computed, ref, useSlots, watch } from 'vue'
import { ArrowRight } from '@element-plus/icons-vue'
import {
  CustomCard,
  CustomIcon,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import SanXuatChiTietModal from './SanXuatChiTietModal.vue'
import HoaHongChiTietModal from './HoaHongChiTietModal.vue'

defineOptions({ name: 'BangLuongChiTiet' })

const props = defineProps({
  /** Payload từ API bảng lương chi tiết theo ngày. */
  payload: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  /** Tháng YYYY-MM dùng cho nhãn hiển thị. */
  month: {
    type: String,
    default: '',
  },
  /** Tiêu đề card chi tiết theo ngày. */
  dailyTitle: {
    type: String,
    default: 'Bảng lương',
  },
  /** Layout gọn hơn khi đặt trong modal. */
  embedded: {
    type: Boolean,
    default: false,
  },
  /** Chiều cao tối đa bảng ngày. */
  dailyTableMaxHeight: {
    type: String,
    default: 'min(55vh, 560px)',
  },
})

const slots = useSlots()

const MONEY_PROPS = [
  'luong_co_ban',
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

const GROUP_B_DEFS = [
  { key: 'tong_luong_theo_gio', label: 'Tổng lương theo giờ' },
  { key: 'tong_tang_ca', label: 'Tổng tăng ca' },
  { key: 'hoa_hong_hd_tp', label: 'Hoa hồng HĐ TP' },
  { key: 'hoa_hong_hd_sddv', label: 'Hoa hồng HĐ SDDV' },
  { key: 'san_xuat_make', label: 'Lương make' },
  { key: 'san_xuat_chup', label: 'Lương chụp' },
  { key: 'san_xuat_quay_phim', label: 'Lương quay phim' },
  { key: 'san_xuat_edit', label: 'Lương edit' },
  { key: 'phu_cap_thu_bay_va_chu_nhat', label: 'Phụ cấp thứ 7 và chủ nhật' },
  { key: 'thuong_chuyen_can', label: 'Thưởng chuyên cần' },
]

const GROUP_C_DEFS = [
  { key: 'tien_phat_di_muon', label: 'Tiền đi muộn' },
  { key: 'tien_phat_ve_som', label: 'Tiền về sớm' },
  { key: 'phat_phat_sinh', label: 'Phạt phát sinh' },
]

const calendarActiveNames = ref([])
const sanXuatChiTietModalRef = ref(null)
const hoaHongChiTietModalRef = ref(null)
const showFutureDays = ref(false)
const summaryExpanded = ref({ A: true, B: true, C: true })

const showDailyHeader = computed(() => Boolean(slots.actions) || Boolean(props.dailyTitle))

const days = computed(() => props.payload?.days || [])

const visibleDays = computed(() => {
  const list = days.value
  if (showFutureDays.value || !list.length) return list
  const today = todayDateValue()
  return list.filter((row) => String(row.ngay || '').slice(0, 10) <= today)
})

const employeeTypeLabel = computed(() => {
  const type = props.payload?.nhan_vien?.loai_nhan_vien
  if (type === 'full_time') return 'Full time'
  if (type === 'part_time') return 'Part time'
  return ''
})

const employeeSubtitle = computed(() => {
  const name = props.payload?.nhan_vien?.name
  if (!name) return ''
  return employeeTypeLabel.value ? `${name} · ${employeeTypeLabel.value}` : name
})

const isPartTime = computed(() => props.payload?.nhan_vien?.loai_nhan_vien === 'part_time')

const summaryTableRows = computed(() => {
  const nv = props.payload?.nhan_vien || {}
  const tong = props.payload?.tong_ket || {}

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

  const tongA = Number(tong.tong_a || sumRows(rowsA))
  const tongB = Number(tong.tong_b || sumRows(rowsB))
  const tongC = Number(tong.tong_c || sumRows(rowsC))

  return [
    ...buildSummarySection('A', 'Lương cứng & phụ cấp', rowsA, tongA),
    ...buildSummarySection('B', 'Thu nhập phát sinh', rowsB, tongB),
    ...buildSummarySection('C', 'Khấu trừ', rowsC, tongC),
    {
      key: 'thuc_nhan',
      kind: 'net',
      label: 'Thực nhận (A + B − C)',
      value: Number(tong.thuc_nhan ?? tongA + tongB - tongC),
    },
  ]
})

watch(
  () => props.payload,
  () => {
    summaryExpanded.value = { A: true, B: true, C: true }
    calendarActiveNames.value = []
    showFutureDays.value = false
  },
)

function buildSummarySection(code, title, rows, total) {
  const expanded = isSummarySectionExpanded(code)
  const section = [
    {
      key: `section_${code}`,
      kind: 'section',
      code,
      label: title,
      value: total,
    },
  ]

  if (!expanded) return section

  return [
    ...section,
    ...rows.map((row, index) => ({
      ...row,
      kind: 'item',
      stt: index + 1,
    })),
    {
      key: `tong_${code.toLowerCase()}`,
      kind: 'total',
      label: `Tổng ${code}`,
      value: total,
    },
  ]
}

function isSummarySectionExpanded(code) {
  return Boolean(summaryExpanded.value[code])
}

function toggleSummarySection(code) {
  summaryExpanded.value = {
    ...summaryExpanded.value,
    [code]: !summaryExpanded.value[code],
  }
}

function sumRows(rows) {
  return rows.reduce((sum, row) => sum + (Number(row.value) || 0), 0)
}

function getSummaryRowClassName({ row }) {
  if (row.kind === 'section') {
    return isSummarySectionExpanded(row.code)
      ? 'summary-row--section'
      : 'summary-row--section summary-row--section-collapsed'
  }
  if (row.kind === 'total') return 'summary-row--total'
  if (row.kind === 'net') return 'summary-row--net'
  return ''
}

function todayDateValue() {
  const now = new Date()
  const y = now.getFullYear()
  const m = String(now.getMonth() + 1).padStart(2, '0')
  const d = String(now.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
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

function hasSanXuatChiTiet(row, role) {
  const items = row?.san_xuat?.chi_tiet?.[role]
  return Array.isArray(items) && items.length > 0
}

function openSanXuatChiTiet(row, role) {
  if (!hasSanXuatChiTiet(row, role)) return
  sanXuatChiTietModalRef.value?.open({
    ngay: row.ngay,
    role,
    items: row.san_xuat.chi_tiet[role],
  })
}

function hasHoaHongChiTiet(row, type) {
  const items = row?.hoa_hong?.chi_tiet?.[type]
  return Array.isArray(items) && items.length > 0
}

function openHoaHongChiTiet(row, type) {
  if (!hasHoaHongChiTiet(row, type)) return
  hoaHongChiTietModalRef.value?.open({
    ngay: row.ngay,
    type,
    items: row.hoa_hong.chi_tiet[type],
  })
}

function getSummaries({ columns }) {
  const tong = props.payload?.tong_ket || {}
  return columns.map((column, index) => {
    if (index === 0) return 'Tổng'

    const prop = column.property
    if (!prop) return ''

    if (prop === 'gio_lam_co_ban') {
      return formatHoursValue(tong.gio_lam_co_ban)
    }

    if (prop === 'gio_lam_tang_ca') {
      return formatHoursValue(tong.gio_lam_tang_ca)
    }

    if (MONEY_PROPS.includes(prop)) {
      const tongKey = prop === 'luong_co_ban' ? 'tong_luong_theo_gio' : prop
      return formatMoney(tong[tongKey])
    }

    return ''
  })
}
</script>

<style scoped lang="scss">
.bang-luong-chi-tiet {
  display: flex;
  flex-direction: column;
  gap: 16px;

  &--embedded {
    gap: 12px;

    :deep(.table-card),
    :deep(.fixed-salary-card) {
      margin: 0;
    }
  }
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.card-header-actions {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.card-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.summary-title-block {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.summary-subtitle {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.calendar-collapse {
  border: none;

  :deep(.el-collapse-item__header) {
    padding: 0 14px;
    height: 44px;
    line-height: 44px;
    font-weight: 600;
    border-radius: 10px;
    border: 1px solid var(--el-border-color-lighter);
    background: var(--el-fill-color-light);
  }

  :deep(.el-collapse-item__title) {
    flex: 1;
    min-width: 0;
  }

  :deep(.el-collapse-item__wrap) {
    border: none;
  }

  :deep(.el-collapse-item__content) {
    padding: 12px 0 0;
  }
}

.daily-collapse-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  min-width: 0;
  padding-right: 8px;

  &__text {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.daily-toolbar {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  font-weight: 400;

  &__label {
    font-size: 13px;
    color: var(--el-text-color-regular);
    white-space: nowrap;
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

  &--weekend {
    .day-cell__date {
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

.money-link {
  appearance: none;
  border: 0;
  padding: 0;
  margin: 0;
  background: transparent;
  color: var(--el-color-primary);
  font: inherit;
  font-variant-numeric: tabular-nums;
  cursor: pointer;
  text-decoration: underline;
  text-underline-offset: 2px;

  &:hover {
    color: var(--el-color-primary-light-3);
  }
}

.salary-table {
  :deep(.el-table__footer-wrapper td) {
    font-weight: 700;
    background: var(--el-fill-color-light);
  }
}

.summary-table {
  :deep(.el-table__footer-wrapper td) {
    font-weight: 700;
    background: var(--el-fill-color-light);
  }

  :deep(.summary-row--section td) {
    background: var(--el-fill-color-light);
    font-weight: 650;
  }

  :deep(.summary-row--section-collapsed td) {
    background: color-mix(in srgb, var(--el-color-primary) 6%, transparent);
  }

  :deep(.summary-row--total td) {
    font-weight: 700;
    background: color-mix(in srgb, var(--el-color-primary) 6%, transparent);
  }

  :deep(.summary-row--net td) {
    font-weight: 700;
    background: color-mix(in srgb, var(--el-color-primary) 12%, transparent);
  }
}

.summary-section-toggle {
  appearance: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border: 0;
  padding: 0;
  margin: 0;
  background: transparent;
  cursor: pointer;
  color: inherit;

  &__icon {
    font-size: 12px;
    color: var(--el-text-color-secondary);
    transition: transform 0.2s ease;

    &.is-expanded {
      transform: rotate(90deg);
    }
  }
}

.summary-section-code {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
  height: 24px;
  padding: 0 6px;
  border-radius: 6px;
  background: var(--el-color-primary);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
}

.summary-section-label-btn {
  appearance: none;
  border: 0;
  padding: 0;
  margin: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  font-weight: 650;
  cursor: pointer;
  text-align: left;

  &:hover {
    color: var(--el-color-primary);
  }
}

.summary-total-label,
.summary-total-value {
  font-weight: 700;
}

.summary-net-label,
.summary-net-value {
  font-weight: 700;
  color: var(--el-color-primary);
}
</style>
