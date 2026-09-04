<template>
  <div class="luong-tong-hop page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="24" :sm="12" :md="8" :lg="6">
          <CustomDatePicker
            v-model="selectedMonth"
            type="month"
            placeholder="Chọn tháng"
            format="MM/YYYY"
            value-format="YYYY-MM"
            :clearable="false"
            style="width: 100%"
            @change="onSearch"
          />
        </CustomCol>
        <CustomCol :xs="24" :sm="12" :md="10" :lg="8">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo tên, email, SĐT..."
            clearable
            style="width: 100%"
            @clear="onSearch"
            @keyup.enter="onSearch"
          >
            <template #prefix>
              <CustomIcon><Search /></CustomIcon>
            </template>
          </CustomInput>
        </CustomCol>
        <CustomCol :xs="24" :sm="12" :md="6" :lg="4">
          <CustomButton type="primary" plain :loading="loading" @click="onSearch">
            Tìm kiếm
          </CustomButton>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">
            Lương tổng hợp · tháng {{ formatMonthLabel(selectedMonth) }}
            <span v-if="isLockedData" class="locked-badge">Đã chốt</span>
          </span>
          <div class="card-header-actions">
            <CustomTooltip v-if="canHuyChotLuong" content="Huỷ chốt lương tháng đang chọn" placement="top">
              <span class="chot-btn-wrap">
                <CustomButton
                  type="danger"
                  plain
                  :loading="chotting"
                  @click="onHuyChotLuong"
                >
                  Huỷ chốt
                </CustomButton>
              </span>
            </CustomTooltip>
            <CustomTooltip :content="chotButtonTooltip" placement="top">
              <span class="chot-btn-wrap">
                <CustomButton
                  type="warning"
                  :loading="chotting"
                  :disabled="!canChotLuong"
                  @click="onChotLuong"
                >
                  {{ daChotLuong ? 'Đã chốt lương' : 'Chốt lương' }}
                </CustomButton>
              </span>
            </CustomTooltip>
          </div>
        </div>
      </template>

      <div v-if="isEmptyNoChot" class="empty-no-chot">
        <p class="empty-no-chot__title">Không có dữ liệu</p>
        <p class="empty-no-chot__desc">
          Tháng {{ formatMonthLabel(selectedMonth) }} chưa được chốt lương nên không có dữ liệu lịch sử để xem.
        </p>
      </div>

      <template v-else>
        <CustomTable
          v-loading="loading"
          :data="items"
          stripe
          border
          row-key="user_id"
          style="width: 100%"
          class="salary-summary-table"
          :empty-text="loading ? 'Đang tải...' : 'Chưa có dữ liệu'"
        >
          <CustomTableColumn label="STT" width="56" align="center" fixed="left">
            <template #default="{ $index }">
              {{ (page - 1) * perPage + $index + 1 }}
            </template>
          </CustomTableColumn>

          <CustomTableColumn
            label="Nhân viên"
            min-width="180"
            fixed="left"
            show-overflow-tooltip
          >
            <template #default="{ row }">
              <div class="employee-cell">
                <div class="employee-cell__name-row">
                  <span class="employee-cell__name">{{ row.name || '—' }}</span>
                  <CustomTooltip
                    v-if="row.loai_nhan_vien"
                    :content="employeeTypeLabel(row.loai_nhan_vien)"
                    placement="top"
                  >
                    <CustomIcon
                      class="employee-type-icon"
                      :class="
                        row.loai_nhan_vien === 'full_time'
                          ? 'employee-type-icon--full'
                          : 'employee-type-icon--part'
                      "
                    >
                      <component :is="employeeTypeIcon(row.loai_nhan_vien)" />
                    </CustomIcon>
                  </CustomTooltip>
                </div>
                <span v-if="row.phone" class="employee-cell__email">{{ row.phone }}</span>
              </div>
            </template>
          </CustomTableColumn>

          <CustomTableColumn
            v-for="group in salaryGroups"
            :key="group.code"
            align="center"
            :label-class-name="`salary-group-header salary-group-header--${group.code.toLowerCase()}`"
          >
            <template #header>
              <span class="salary-group-title">
                <span class="salary-group-code">{{ group.code }}</span>
                {{ group.label }}
              </span>
            </template>
            <CustomTableColumn
              v-for="col in group.columns"
              :key="col.key"
              :label="col.label"
              :min-width="col.minWidth"
              align="right"
            >
              <template #default="{ row }">
                <button
                  v-if="!isLockedData"
                  type="button"
                  class="money-link"
                  :class="{
                    'money-danger': group.danger && !col.total,
                    'money-total': col.total,
                  }"
                  @click="viewKhoanMuc(row, group, col)"
                >
                  {{ formatMoney(row[group.key]?.[col.key]) }}
                </button>
                <span
                  v-else
                  class="money-locked"
                  :class="{
                    'money-danger': group.danger && !col.total,
                    'money-total': col.total,
                  }"
                >
                  {{ formatMoney(row[group.key]?.[col.key]) }}
                </span>
              </template>
            </CustomTableColumn>
          </CustomTableColumn>

          <CustomTableColumn label="Thực nhận (A + B − C)" min-width="160" align="right" fixed="right">
            <template #default="{ row }">
              <span class="money-primary">{{ formatMoney(row.thuc_nhan) }}</span>
            </template>
          </CustomTableColumn>

          <CustomTableColumn label="Thao tác" width="90" fixed="right" align="center">
            <template #default="{ row }">
              <div class="action-btns">
                <CustomTooltip
                  :content="isLockedData ? 'Tháng đã chốt — không xem chi tiết' : 'Xem chi tiết'"
                  placement="top"
                >
                  <CustomButton
                    type="primary"
                    link
                    :icon="View"
                    :disabled="isLockedData"
                    @click="viewDetail(row)"
                  />
                </CustomTooltip>
              </div>
            </template>
          </CustomTableColumn>
        </CustomTable>

        <Pagination
          v-model="page"
          v-model:page-size="perPage"
          :total="total"
          :disabled="loading"
          @change="loadItems"
        />
      </template>
    </CustomCard>

    <LuongNhanVienChiTietModal ref="chiTietModalRef" />
    <LuongKhoanMucChiTietModal ref="khoanMucModalRef" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { Briefcase, Clock, Search, View } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { chotLuongThang, fetchLuongTongHop, fetchTrangThaiChotLuong, huyChotLuongThang } from '@/api/tinhLuong'
import Pagination from '@/components/Pagination.vue'
import {
  CustomButton,
  CustomCard,
  CustomCol,
  CustomDatePicker,
  CustomIcon,
  CustomInput,
  CustomRow,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import LuongNhanVienChiTietModal from './LuongNhanVienChiTietModal.vue'
import LuongKhoanMucChiTietModal from './LuongKhoanMucChiTietModal.vue'

const loading = ref(false)
const chotting = ref(false)
const items = ref([])
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const selectedMonth = ref(currentMonthValue())
const chiTietModalRef = ref(null)
const khoanMucModalRef = ref(null)

const chotStatus = ref({
  da_chot: false,
  co_the_chot: false,
  co_the_huy_chot: false,
  trong_ky_chot: false,
  ky_chot_luong: null,
})
/** Dữ liệu đang hiển thị lấy từ snapshot du_lieu_chot (không cho xem chi tiết động). */
const isLockedData = ref(false)
/** Tháng cũ (≥ 2 tháng) chưa có bản ghi chốt → ẩn bảng. */
const isEmptyNoChot = ref(false)

const daChotLuong = computed(() => Boolean(chotStatus.value.da_chot) || isLockedData.value)
const canChotLuong = computed(() => Boolean(chotStatus.value.co_the_chot) && !chotting.value)
const canHuyChotLuong = computed(() => Boolean(chotStatus.value.co_the_huy_chot) && !chotting.value)

const chotButtonTooltip = computed(() => {
  if (daChotLuong.value) {
    return canHuyChotLuong.value
      ? 'Tháng đã chốt — có thể huỷ chốt trong kỳ'
      : 'Tháng này đã được chốt lương'
  }
  if (chotStatus.value.trong_ky_chot) return 'Chốt lương tháng đang chọn'
  const ky = chotStatus.value.ky_chot_luong
  if (ky?.tu_ngay && ky?.den_ngay) {
    return `Chỉ mở trong kỳ chốt: ${formatDateLabel(ky.tu_ngay)} → ${formatDateLabel(ky.den_ngay)}`
  }
  return 'Ngoài kỳ chốt lương'
})

const salaryGroups = [
  {
    code: 'A',
    key: 'a',
    label: 'Lương cứng & phụ cấp',
    columns: [
      { key: 'luong_cung', label: 'Lương cứng', minWidth: 120 },
      { key: 'luong_mem', label: 'Lương mềm', minWidth: 120 },
      { key: 'phu_cap', label: 'Phụ cấp', minWidth: 110 },
      { key: 'phu_cap_xang', label: 'Phụ cấp xăng', minWidth: 120 },
      { key: 'phu_cap_an_trua', label: 'Phụ cấp ăn trưa', minWidth: 130 },
      { key: 'phu_cap_dien_thoai', label: 'Phụ cấp điện thoại', minWidth: 140 },
      { key: 'phu_cap_nha_o', label: 'Phụ cấp nhà ở', minWidth: 120 },
      { key: 'tong', label: 'Tổng A', minWidth: 120, total: true },
    ],
  },
  {
    code: 'B',
    key: 'b',
    label: 'Thu nhập phát sinh',
    columns: [
      { key: 'tong_luong_theo_gio', label: 'Tổng lương theo giờ', minWidth: 150 },
      { key: 'tong_tang_ca', label: 'Tổng tăng ca', minWidth: 120 },
      { key: 'hoa_hong_hd_tp', label: 'Hoa hồng HĐ TP', minWidth: 130 },
      { key: 'hoa_hong_hd_sddv', label: 'Hoa hồng HĐ SDDV', minWidth: 140 },
      { key: 'san_xuat_make', label: 'Lương make', minWidth: 110 },
      { key: 'san_xuat_chup', label: 'Lương chụp', minWidth: 110 },
      { key: 'san_xuat_quay_phim', label: 'Lương quay phim', minWidth: 130 },
      { key: 'san_xuat_edit', label: 'Lương edit', minWidth: 110 },
      { key: 'phu_cap_thu_bay_va_chu_nhat', label: 'Phụ cấp T7 & CN', minWidth: 130 },
      { key: 'thuong_chuyen_can', label: 'Thưởng chuyên cần', minWidth: 140 },
      { key: 'tong', label: 'Tổng B', minWidth: 150, total: true },
    ],
  },
  {
    code: 'C',
    key: 'c',
    label: 'Khấu trừ',
    danger: true,
    columns: [
      { key: 'tien_phat_di_muon', label: 'Tiền đi muộn', minWidth: 120 },
      { key: 'tien_phat_ve_som', label: 'Tiền về sớm', minWidth: 110 },
      { key: 'phat_phat_sinh', label: 'Phạt phát sinh', minWidth: 120 },
      { key: 'tong', label: 'Tổng C', minWidth: 110, total: true },
    ],
  },
]

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
  if (!value || !String(value).includes('-')) return '—'
  const [y, m, d] = String(value).split('-')
  return `${d}/${m}/${y}`
}

function formatMoney(value) {
  const num = Number(value)
  if (!Number.isFinite(num) || num === 0) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function employeeTypeLabel(value) {
  if (value === 'full_time') return 'Full time'
  if (value === 'part_time') return 'Part time'
  return ''
}

function employeeTypeIcon(value) {
  return value === 'part_time' ? Clock : Briefcase
}

function onSearch() {
  page.value = 1
  Promise.all([loadItems(), loadChotStatus()])
}

async function loadChotStatus() {
  if (!selectedMonth.value) {
    chotStatus.value = {
      da_chot: false,
      co_the_chot: false,
      co_the_huy_chot: false,
      trong_ky_chot: false,
      ky_chot_luong: null,
    }
    return
  }

  try {
    const { data } = await fetchTrangThaiChotLuong(
      { thang: selectedMonth.value },
      { skipLoading: true },
    )
    chotStatus.value = {
      da_chot: Boolean(data.da_chot),
      co_the_chot: Boolean(data.co_the_chot),
      co_the_huy_chot: Boolean(data.co_the_huy_chot),
      trong_ky_chot: Boolean(data.trong_ky_chot),
      ky_chot_luong: data.ky_chot_luong || null,
    }
  } catch {
    chotStatus.value = {
      da_chot: false,
      co_the_chot: false,
      co_the_huy_chot: false,
      trong_ky_chot: false,
      ky_chot_luong: null,
    }
  }
}

async function loadItems() {
  if (!selectedMonth.value) {
    items.value = []
    total.value = 0
    isLockedData.value = false
    isEmptyNoChot.value = false
    return
  }

  loading.value = true
  try {
    const { data } = await fetchLuongTongHop(
      {
        thang: selectedMonth.value,
        page: page.value,
        per_page: perPage.value,
        keyword: keyword.value.trim() || undefined,
      },
      { skipLoading: true },
    )
    items.value = data.data || []
    total.value = data.total || 0
    page.value = data.current_page || page.value
    isLockedData.value = Boolean(data.da_chot) || data.nguon === 'chot'
    isEmptyNoChot.value = data.nguon === 'khong_co_chot'
  } catch {
    items.value = []
    total.value = 0
    isLockedData.value = false
    isEmptyNoChot.value = false
  } finally {
    loading.value = false
  }
}

async function onChotLuong() {
  if (!canChotLuong.value || !selectedMonth.value) return

  try {
    await ElMessageBox.confirm(
      `Chốt lương tháng ${formatMonthLabel(selectedMonth.value)}? Dữ liệu tổng hợp hiện tại sẽ được lưu lại.`,
      'Xác nhận chốt lương',
      {
        type: 'warning',
        confirmButtonText: 'Chốt lương',
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  chotting.value = true
  try {
    const { data } = await chotLuongThang(
      { thang: selectedMonth.value },
      { skipLoading: true },
    )
    chotStatus.value = {
      da_chot: true,
      co_the_chot: false,
      co_the_huy_chot: Boolean(data.co_the_huy_chot ?? true),
      trong_ky_chot: Boolean(data.trong_ky_chot ?? true),
      ky_chot_luong: data.ky_chot_luong || chotStatus.value.ky_chot_luong,
    }
    ElMessage.success(data.message || `Đã chốt lương tháng ${formatMonthLabel(selectedMonth.value)}.`)
    await loadItems()
  } catch {
    await loadChotStatus()
  } finally {
    chotting.value = false
  }
}

async function onHuyChotLuong() {
  if (!canHuyChotLuong.value || !selectedMonth.value) return

  try {
    await ElMessageBox.confirm(
      `Huỷ chốt lương tháng ${formatMonthLabel(selectedMonth.value)}? Dữ liệu đã chốt sẽ bị xoá và hệ thống tính lại theo realtime.`,
      'Xác nhận huỷ chốt',
      {
        type: 'warning',
        confirmButtonText: 'Huỷ chốt',
        cancelButtonText: 'Đóng',
      },
    )
  } catch {
    return
  }

  chotting.value = true
  try {
    const { data } = await huyChotLuongThang(
      { thang: selectedMonth.value },
      { skipLoading: true },
    )
    chotStatus.value = {
      da_chot: false,
      co_the_chot: Boolean(data.co_the_chot ?? true),
      co_the_huy_chot: false,
      trong_ky_chot: Boolean(data.trong_ky_chot ?? true),
      ky_chot_luong: data.ky_chot_luong || chotStatus.value.ky_chot_luong,
    }
    isLockedData.value = false
    ElMessage.success(data.message || `Đã huỷ chốt lương tháng ${formatMonthLabel(selectedMonth.value)}.`)
    await loadItems()
    await loadChotStatus()
  } catch {
    await loadChotStatus()
  } finally {
    chotting.value = false
  }
}

function viewDetail(row) {
  if (isLockedData.value) return
  chiTietModalRef.value?.open({
    userId: row.user_id,
    thang: selectedMonth.value,
    name: row.name,
  })
}

function viewKhoanMuc(row, group, col) {
  if (isLockedData.value) return
  khoanMucModalRef.value?.open({
    userId: row.user_id,
    thang: selectedMonth.value,
    name: row.name,
    groupCode: group.code,
    groupLabel: group.label,
    itemKey: col.key,
    itemLabel: col.label,
    danger: Boolean(group.danger) && !col.total,
  })
}

onMounted(() => {
  Promise.all([loadItems(), loadChotStatus()])
})
</script>

<style scoped lang="scss">
.chot-btn-wrap {
  display: inline-flex;
}

.locked-badge {
  display: inline-flex;
  align-items: center;
  margin-left: 8px;
  padding: 2px 8px;
  border-radius: 6px;
  background: color-mix(in srgb, var(--el-color-warning) 16%, transparent);
  color: var(--el-color-warning-dark-2);
  font-size: 12px;
  font-weight: 650;
  vertical-align: middle;
}

.empty-no-chot {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 48px 16px;
  text-align: center;

  &__title {
    margin: 0;
    font-size: 16px;
    font-weight: 650;
    color: var(--el-text-color-primary);
  }

  &__desc {
    margin: 0;
    max-width: 420px;
    font-size: 13px;
    line-height: 1.5;
    color: var(--el-text-color-secondary);
  }
}

.employee-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  line-height: 1.25;

  &__name-row {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
  }

  &__name {
    font-weight: 600;
  }

  &__email {
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }
}

.employee-type-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
  cursor: default;
}

.employee-type-icon--full {
  color: var(--el-color-primary);
}

.employee-type-icon--part {
  color: var(--el-color-warning);
}

.money-primary {
  font-weight: 700;
  color: var(--el-color-primary);
}

.money-danger {
  color: var(--el-color-danger);
}

.money-total {
  font-weight: 700;
}

.money-locked {
  font-variant-numeric: tabular-nums;
  color: var(--el-text-color-regular);
  cursor: default;

  &.money-danger {
    color: var(--el-color-danger);
  }

  &.money-total {
    font-weight: 700;
  }
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
  text-decoration: none;

  &:hover {
    color: var(--el-color-primary-light-3);
  }

  &.money-danger {
    color: var(--el-color-danger);

    &:hover {
      color: var(--el-color-danger-light-3);
    }
  }

  &.money-total {
    font-weight: 700;
  }
}

.salary-group-title {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-weight: 650;
}

.salary-group-code {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  border-radius: 6px;
  background: var(--el-color-primary);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
}

.salary-summary-table {
  :deep(.el-table__header th) {
    background: var(--el-fill-color-light);
  }

  :deep(.salary-group-header--a) {
    background: color-mix(in srgb, var(--el-color-primary) 8%, var(--el-fill-color-light));
  }

  :deep(.salary-group-header--b) {
    background: color-mix(in srgb, var(--el-color-success) 10%, var(--el-fill-color-light));
  }

  :deep(.salary-group-header--c) {
    background: color-mix(in srgb, var(--el-color-danger) 8%, var(--el-fill-color-light));
  }
}
</style>
