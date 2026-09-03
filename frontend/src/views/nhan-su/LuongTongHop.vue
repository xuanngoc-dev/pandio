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
          <span class="card-title">Lương tổng hợp · tháng {{ formatMonthLabel(selectedMonth) }}</span>
        </div>
      </template>

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
              <span class="employee-cell__name">{{ row.name || '—' }}</span>
              <span v-if="row.email" class="employee-cell__email">{{ row.email }}</span>
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
              <CustomTooltip content="Xem chi tiết" placement="top">
                <CustomButton type="primary" link :icon="View" @click="viewDetail(row)" />
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
    </CustomCard>

    <LuongNhanVienChiTietModal ref="chiTietModalRef" />
    <LuongKhoanMucChiTietModal ref="khoanMucModalRef" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { Search, View } from '@element-plus/icons-vue'
import { fetchLuongTongHop } from '@/api/tinhLuong'
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
const items = ref([])
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const selectedMonth = ref(currentMonthValue())
const chiTietModalRef = ref(null)
const khoanMucModalRef = ref(null)

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

function formatMoney(value) {
  const num = Number(value)
  if (!Number.isFinite(num) || num === 0) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function onSearch() {
  page.value = 1
  loadItems()
}

async function loadItems() {
  if (!selectedMonth.value) {
    items.value = []
    total.value = 0
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
  } catch {
    items.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

function viewDetail(row) {
  chiTietModalRef.value?.open({
    userId: row.user_id,
    thang: selectedMonth.value,
    name: row.name,
  })
}

function viewKhoanMuc(row, group, col) {
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
  loadItems()
})
</script>

<style scoped lang="scss">
.employee-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  line-height: 1.25;

  &__name {
    font-weight: 600;
  }

  &__email {
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }
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
