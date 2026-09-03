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

        <CustomTableColumn label="Lương" align="center">
          <CustomTableColumn label="Lương cứng" min-width="120" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.luong?.luong_cung) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Lương mềm" min-width="120" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.luong?.luong_mem) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Phụ cấp" min-width="120" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.luong?.phu_cap) }}
            </template>
          </CustomTableColumn>
        </CustomTableColumn>

        <CustomTableColumn label="Phát sinh" align="center">
          <CustomTableColumn label="Lương theo giờ" min-width="130" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.phat_sinh?.luong_theo_gio) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Lương tăng ca" min-width="120" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.phat_sinh?.luong_tang_ca) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Thưởng" min-width="110" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.phat_sinh?.thuong) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Chuyên cần" min-width="110" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.phat_sinh?.chuyen_can) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Hoa hồng" min-width="110" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.phat_sinh?.hoa_hong) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Hậu kỳ" min-width="110" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.phat_sinh?.hau_ky) }}
            </template>
          </CustomTableColumn>
        </CustomTableColumn>

        <CustomTableColumn label="Khấu trừ" align="center">
          <CustomTableColumn label="Đi muộn" min-width="110" align="right">
            <template #default="{ row }">
              <span class="money-danger">{{ formatMoney(row.khau_tru?.di_muon) }}</span>
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Về sớm" min-width="110" align="right">
            <template #default="{ row }">
              <span class="money-danger">{{ formatMoney(row.khau_tru?.ve_som) }}</span>
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Phát sinh" min-width="110" align="right">
            <template #default="{ row }">
              <span class="money-danger">{{ formatMoney(row.khau_tru?.phat_sinh) }}</span>
            </template>
          </CustomTableColumn>
        </CustomTableColumn>

        <CustomTableColumn label="Thực nhận" min-width="140" align="right" fixed="right">
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

const loading = ref(false)
const items = ref([])
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const selectedMonth = ref(currentMonthValue())
const chiTietModalRef = ref(null)

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

.salary-summary-table {
  :deep(.el-table__header th) {
    background: var(--el-fill-color-light);
  }
}
</style>
