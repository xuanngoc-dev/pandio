<template>
  <div class="duyet-dat-mua-trang-phuc page-list">
    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">
            Đặt mua trang phục chờ duyệt
            <el-badge
              v-if="total > 0"
              :value="total"
              :max="99"
              class="title-badge"
            />
          </span>
          <div class="card-header-right">
            <CustomInput
              v-model="keyword"
              placeholder="Tìm NCC, loại đơn..."
              clearable
              style="width: 220px"
              @clear="onSearch"
              @keyup.enter="onSearch"
            >
              <template #prefix>
                <CustomIcon><Search /></CustomIcon>
              </template>
            </CustomInput>
            <BulkActionBar :actions="bulkActions" @action="onBulkAction" />
          </div>
        </div>
      </template>

      <CustomTable
        v-loading="loading"
        :data="items"
        stripe
        row-key="id"
        style="width: 100%"
        @selection-change="onSelectionChange"
      >
        <CustomTableColumn type="selection" width="48" align="center" />
        <CustomTableColumn label="STT" width="60" align="center">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Nhà cung cấp" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.nha_cung_cap?.ten_nha_cung_cap || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Loại đơn hàng" width="160">
          <template #default="{ row }">
            {{ loaiDonHangLabel(row.loai_don_hang) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Nguồn hàng hóa" width="130">
          <template #default="{ row }">
            {{ nguonHangHoaLabel(row.nguon_hang_hoa) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Ngày đặt" width="120" align="center">
          <template #default="{ row }">
            {{ formatDate(row.ngay_dat) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Tổng tiền hàng" width="140" align="right">
          <template #default="{ row }">
            {{ formatMoney(row.tong_tien_hang) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Dư nợ" width="140" align="right">
          <template #default="{ row }">
            {{ formatMoney(row.du_no) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Thao tác" width="120" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip content="Duyệt" placement="top">
                <CustomButton
                  type="success"
                  link
                  :icon="CircleCheck"
                  :loading="processingId === row.id"
                  @click="confirmAction('da_duyet', [row.id])"
                />
              </CustomTooltip>
              <CustomTooltip content="Hủy duyệt" placement="top">
                <CustomButton
                  type="warning"
                  link
                  :icon="CircleClose"
                  :loading="processingId === row.id"
                  @click="confirmAction('huy_duyet', [row.id])"
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
    </CustomCard>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { CircleCheck, CircleClose, Search } from '@element-plus/icons-vue'
import {
  bulkDuyetDatMuaTrangPhuc,
  bulkHuyDuyetDatMuaTrangPhuc,
  duyetDatMuaTrangPhuc,
  fetchDatMuaTrangPhuc,
  huyDuyetDatMuaTrangPhuc,
} from '@/api/datMuaTrangPhuc'
import { formatInteger } from '@/utils/number'
import BulkActionBar from '@/components/BulkActionBar.vue'
import {
  CustomButton,
  CustomCard,
  CustomIcon,
  CustomInput,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'

const loaiDonHangOptions = [
  { value: 'dau_tu_tai_san', label: 'Đầu tư/tài sản' },
  { value: 'vat_tu_tieu_hao', label: 'Vật tư tiêu hao' },
]

const nguonHangHoaOptions = [
  { value: 'trong_nuoc', label: 'Trong nước' },
  { value: 'nhap_khau', label: 'Nhập khẩu' },
]

const items = ref([])
const loading = ref(false)
const processingId = ref(null)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const selectedRows = ref([])
const bulkApproving = ref(false)
const bulkRejecting = ref(false)

const selectedCount = computed(() => selectedRows.value.length)

const bulkActions = computed(() => [
  {
    key: 'huy_duyet',
    label: 'Hủy duyệt',
    type: 'warning',
    badge: selectedCount.value,
    badgeType: 'warning',
    loading: bulkRejecting.value,
    tooltip: selectedCount.value
      ? `Hủy duyệt ${selectedCount.value} đơn đã chọn`
      : 'Chọn đơn để hủy duyệt',
  },
  {
    key: 'da_duyet',
    label: 'Duyệt',
    type: 'success',
    badge: selectedCount.value,
    badgeType: 'success',
    loading: bulkApproving.value,
    tooltip: selectedCount.value
      ? `Duyệt ${selectedCount.value} đơn đã chọn`
      : 'Chọn đơn để duyệt',
  },
])

function loaiDonHangLabel(value) {
  return loaiDonHangOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function nguonHangHoaLabel(value) {
  return nguonHangHoaOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function formatMoney(value) {
  if (value == null || value === '') return '—'
  return `${formatInteger(value) || '0'} ₫`
}

function formatDate(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('vi-VN')
}

function onSelectionChange(rows) {
  selectedRows.value = rows || []
}

function selectedIds() {
  return selectedRows.value.map((row) => row.id).filter(Boolean)
}

function onBulkAction(key) {
  if (key === 'da_duyet' || key === 'huy_duyet') {
    confirmAction(key, selectedIds())
  }
}

async function confirmAction(trangThai, ids = []) {
  const targetIds = (ids || []).filter(Boolean)
  if (!targetIds.length) {
    ElMessage.warning('Vui lòng chọn ít nhất một đơn.')
    return
  }

  const isApprove = trangThai === 'da_duyet'
  const count = targetIds.length
  try {
    await ElMessageBox.confirm(
      isApprove
        ? `Duyệt ${count} đơn đặt mua trang phục đã chọn?`
        : `Hủy duyệt ${count} đơn đặt mua trang phục đã chọn?`,
      isApprove ? 'Xác nhận duyệt' : 'Xác nhận hủy duyệt',
      {
        type: isApprove ? 'success' : 'warning',
        confirmButtonText: isApprove ? 'Duyệt' : 'Hủy duyệt',
        cancelButtonText: 'Đóng',
      },
    )
  } catch {
    return
  }

  await runAction(trangThai, targetIds)
}

async function runAction(trangThai, ids) {
  const isApprove = trangThai === 'da_duyet'
  const loadingRef = isApprove ? bulkApproving : bulkRejecting
  loadingRef.value = true
  if (ids.length === 1) processingId.value = ids[0]

  try {
    if (ids.length === 1) {
      if (isApprove) await duyetDatMuaTrangPhuc(ids[0])
      else await huyDuyetDatMuaTrangPhuc(ids[0])
    } else if (isApprove) {
      await bulkDuyetDatMuaTrangPhuc(ids)
    } else {
      await bulkHuyDuyetDatMuaTrangPhuc(ids)
    }
    ElMessage.success(
      isApprove ? `Đã duyệt ${ids.length} đơn.` : `Đã hủy duyệt ${ids.length} đơn.`,
    )
    await loadItems()
  } catch {
    // interceptor
  } finally {
    loadingRef.value = false
    processingId.value = null
  }
}

async function loadItems() {
  loading.value = true
  selectedRows.value = []
  try {
    const { data } = await fetchDatMuaTrangPhuc({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      trang_thai: 'cho_duyet',
    })
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

function onSearch() {
  page.value = 1
  loadItems()
}

onMounted(loadItems)
</script>

<style scoped lang="scss">
.card-title {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.title-badge {
  :deep(.el-badge__content) {
    position: static;
    transform: none;
  }
}

.card-header-right {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}
</style>
