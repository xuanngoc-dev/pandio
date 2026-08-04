<template>
  <div class="hop-dong-cho-thue">
    <CustomCard shadow="hover" class="filter-card">
      <div class="toolbar">
        <CustomInput
          v-model="keyword"
          placeholder="Tìm theo mã HĐ, tên khách hàng, SĐT..."
          clearable
          style="max-width: 360px"
          @clear="onSearch"
          @keyup.enter="onSearch"
        >
          <template #prefix>
            <CustomIcon><Search /></CustomIcon>
          </template>
        </CustomInput>
        <CustomSelect
          v-model="filterTrangThai"
          placeholder="Trạng thái"
          clearable
          style="width: 180px"
          @change="onSearch"
        >
          <CustomOption
            v-for="opt in trangThaiOptions"
            :key="opt.value"
            :label="opt.label"
            :value="opt.value"
          />
        </CustomSelect>
        <CustomButton type="primary" plain @click="onSearch">
          <CustomIcon><Search /></CustomIcon>
          Tìm kiếm
        </CustomButton>
      </div>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách hợp đồng cho thuê trang phục</span>
          <BulkActionBar :actions="bulkActions" @action="onBulkAction">
            <TableColumnConfig :settings="columnSettings" />
            <CustomTooltip content="Danh sách hợp đồng nháp" placement="top">
              <CustomButton @click="openDrafts">
                <CustomIcon><Document /></CustomIcon>
                Nháp
              </CustomButton>
            </CustomTooltip>
            <CustomTooltip content="Thêm mới" placement="top">
              <CustomButton type="primary" :loading="creating" @click="openCreate">
                <CustomIcon><Plus /></CustomIcon>
                Thêm
              </CustomButton>
            </CustomTooltip>
          </BulkActionBar>
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
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ma_hop_dong')"
          label="Mã HĐ"
          prop="ma_hop_dong"
          min-width="140"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('khach_hang')"
          label="Khách hàng"
          min-width="160"
        >
          <template #default="{ row }">
            <div>{{ row.ten_khach_hang }}</div>
            <div class="sub-text">{{ row.sdt_khach_hang }}</div>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('thoi_gian')"
          label="Thời gian"
          min-width="200"
          align="center"
        >
          <template #default="{ row }">
            {{ formatThoiGianThue(row) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('tong_tien')"
          label="Tổng tiền"
          width="130"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.tong_tien) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('thanh_toan')"
          label="Thanh toán"
          min-width="160"
        >
          <template #default="{ row }">
            <div class="thanh-toan-cell">
              <div class="thanh-toan-cell__text">
                {{ formatMoney(row.tien_coc) }} / {{ formatMoney(row.tong_tien) }}
              </div>
              <div class="thanh-toan-progress" :title="`${thanhToanPercent(row)}%`">
                <div
                  class="thanh-toan-progress__bar"
                  :style="{ width: `${thanhToanPercent(row)}%` }"
                />
              </div>
            </div>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguoi_cho_thue')"
          label="Người cho thuê"
          min-width="140"
        >
          <template #default="{ row }">
            {{ row.nguoi_cho_thue_user?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('san_pham')"
          label="Sản phẩm"
          width="90"
          align="center"
        >
          <template #default="{ row }">
            {{ row.san_pham_cho_thue?.length || 0 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('trang_thai')"
          label="Trạng thái"
          width="130"
          align="center"
        >
          <template #default="{ row }">
            <el-tag :type="trangThaiTagType(row.trang_thai)" size="small">
              {{ trangThaiLabel(row.trang_thai) }}
            </el-tag>
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Thao tác" width="140" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip content="Sửa" placement="top">
                <CustomButton type="primary" link :icon="Edit" @click="openEdit(row)" />
              </CustomTooltip>
              <CustomTooltip content="Thanh toán" placement="top">
                <CustomButton type="success" link :icon="Wallet" @click="openThanhToan(row)" />
              </CustomTooltip>
              <CustomTooltip content="Xóa" placement="top">
                <CustomButton type="danger" link :icon="Delete" @click="remove(row)" />
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

    <HopDongChoThueDraftModal
      ref="draftModalRef"
      v-model="draftModalVisible"
      @continue="onContinueDraft"
      @changed="loadItems"
    />

    <HopDongChoThueFormModal
      v-model="formModalVisible"
      :hop-dong="currentHopDong"
      @saved="onFormSaved"
      @closed="onFormClosed"
    />

    <HopDongChoThueThanhToanModal
      v-model="thanhToanModalVisible"
      :hop-dong="thanhToanHopDong"
      @saved="onThanhToanSaved"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Document, Edit, Plus, Search, Wallet } from '@element-plus/icons-vue'
import {
  deleteHopDongChoThueTrangPhuc,
  fetchHopDongChoThueTrangPhuc,
  getHopDongChoThueTrangPhuc,
  khoiTaoHopDongChoThueTrangPhuc,
} from '@/api/hopDongChoThueTrangPhuc'
import HopDongChoThueDraftModal from '@/views/van-hanh-cuoi/hop-dong-cho-thue/HopDongChoThueDraftModal.vue'
import HopDongChoThueFormModal from '@/views/van-hanh-cuoi/hop-dong-cho-thue/HopDongChoThueFormModal.vue'
import HopDongChoThueThanhToanModal from '@/views/van-hanh-cuoi/hop-dong-cho-thue/HopDongChoThueThanhToanModal.vue'
import BulkActionBar from '@/components/BulkActionBar.vue'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { runBulk, useBulkSelection } from '@/composables/useBulkSelection'
import { useTableColumns } from '@/composables/useTableColumns'
import {
  CustomButton,
  CustomCard,
  CustomIcon,
  CustomInput,
  CustomOption,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'

const tableColumns = [
  { key: 'ma_hop_dong', label: 'Mã HĐ' },
  { key: 'khach_hang', label: 'Khách hàng' },
  { key: 'thoi_gian', label: 'Thời gian' },
  { key: 'tong_tien', label: 'Tổng tiền' },
  { key: 'thanh_toan', label: 'Thanh toán' },
  { key: 'nguoi_cho_thue', label: 'Người cho thuê' },
  { key: 'san_pham', label: 'Sản phẩm' },
  { key: 'trang_thai', label: 'Trạng thái' },
]
const columnSettings = useTableColumns('van-hanh-cuoi.hop-dong-cho-thue', tableColumns)

const trangThaiOptions = [
  { value: 'cho_xac_nhan', label: 'Chờ xác nhận' },
  { value: 'dang_thue', label: 'Đang thuê' },
  { value: 'da_tra', label: 'Đã trả' },
  { value: 'qua_han', label: 'Quá hạn' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
  { value: 'da_huy', label: 'Đã hủy' },
]

const trangThaiAllOptions = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
  ...trangThaiOptions,
]

const items = ref([])
const loading = ref(false)
const creating = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const filterTrangThai = ref('')
const bulkDeleting = ref(false)
const formModalVisible = ref(false)
const draftModalVisible = ref(false)
const draftModalRef = ref(null)
const currentHopDong = ref(null)
const thanhToanModalVisible = ref(false)
const thanhToanHopDong = ref(null)

const { selectedCount, onSelectionChange, clearSelection, selectedIds } = useBulkSelection()

const bulkActions = computed(() => [
  {
    key: 'delete',
    label: 'Xóa',
    type: 'danger',
    badge: selectedCount.value,
    badgeType: 'danger',
    loading: bulkDeleting.value,
    tooltip: selectedCount.value
      ? `Xóa ${selectedCount.value} hợp đồng đã chọn`
      : 'Chọn hợp đồng để xóa',
  },
])

function trangThaiLabel(value) {
  return trangThaiAllOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function trangThaiTagType(value) {
  const map = {
    moi_tao: 'info',
    nhap: '',
    cho_xac_nhan: 'info',
    dang_thue: 'warning',
    da_tra: 'success',
    qua_han: 'danger',
    hoan_thanh: 'success',
    da_huy: '',
  }
  return map[value] || 'info'
}

function formatMoney(value) {
  if (value == null || value === '') return '—'
  const num = Number(value)
  if (Number.isNaN(num)) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function formatDate(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('vi-VN')
}

function getNgayTraHienThi(row) {
  return row?.ngay_tra_chinh_thuc || row?.ngay_tra_du_kien || null
}

function calcSoNgayThue(from, to) {
  if (!from || !to) return null
  const start = new Date(from)
  const end = new Date(to)
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) return null
  const diff = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1
  return Math.max(1, diff)
}

function formatThoiGianThue(row) {
  const from = formatDate(row?.ngay_thue)
  const toRaw = getNgayTraHienThi(row)
  const to = formatDate(toRaw)
  const soNgay = Number(row?.so_ngay_thue) || calcSoNgayThue(row?.ngay_thue, toRaw)
  const range = from === '—' && to === '—' ? '—' : `${from} – ${to}`
  if (soNgay == null) return range
  return `${range} (${soNgay} ngày)`
}

function thanhToanPercent(row) {
  const tong = Number(row?.tong_tien) || 0
  if (tong <= 0) return 0
  const coc = Number(row?.tien_coc) || 0
  return Math.min(100, Math.max(0, Math.round((coc / tong) * 100)))
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchHopDongChoThueTrangPhuc({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      trang_thai: filterTrangThai.value || undefined,
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

async function openCreate() {
  creating.value = true
  try {
    const { data } = await khoiTaoHopDongChoThueTrangPhuc()
    currentHopDong.value = data
    formModalVisible.value = true
  } catch {
    // interceptor
  } finally {
    creating.value = false
  }
}

function openDrafts() {
  draftModalVisible.value = true
}

async function openEdit(row) {
  try {
    const { data } = await getHopDongChoThueTrangPhuc(row.id)
    currentHopDong.value = data
  } catch {
    currentHopDong.value = row
  }
  formModalVisible.value = true
}

async function openThanhToan(row) {
  thanhToanHopDong.value = row
  thanhToanModalVisible.value = true
  try {
    const { data } = await getHopDongChoThueTrangPhuc(row.id)
    thanhToanHopDong.value = data
  } catch {
    // keep list row as fallback; modal will also try reload
  }
}

function onThanhToanSaved() {
  loadItems()
}

async function onContinueDraft(row) {
  draftModalVisible.value = false
  try {
    const { data } = await getHopDongChoThueTrangPhuc(row.id)
    currentHopDong.value = data
  } catch {
    currentHopDong.value = row
  }
  formModalVisible.value = true
}

function onFormSaved(hopDong) {
  if (hopDong) currentHopDong.value = hopDong
  loadItems()
  if (draftModalVisible.value) {
    draftModalRef.value?.reload?.()
  }
}

function onFormClosed() {
  if (draftModalVisible.value) {
    draftModalRef.value?.reload?.()
  }
}

async function onBulkAction(key) {
  if (key === 'delete') await bulkRemove()
}

async function bulkRemove() {
  const ids = selectedIds.value
  if (!ids.length) return

  await ElMessageBox.confirm(`Xóa ${ids.length} hợp đồng đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteHopDongChoThueTrangPhuc(id))
    ElMessage.success(`Đã xóa ${ids.length} hợp đồng.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa hợp đồng "${row.ma_hop_dong}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteHopDongChoThueTrangPhuc(row.id)
    ElMessage.success('Đã xóa hợp đồng cho thuê.')
    await loadItems()
  } catch {
    // interceptor
  }
}

onMounted(() => {
  loadItems()
})
</script>

<style scoped lang="scss">
.hop-dong-cho-thue {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.card-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.sub-text {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.thanh-toan-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.thanh-toan-cell__text {
  font-size: 13px;
  line-height: 1.3;
  white-space: nowrap;
}

.thanh-toan-progress {
  width: 100%;
  height: 4px;
  border-radius: 999px;
  background: var(--el-fill-color);
  overflow: hidden;
}

.thanh-toan-progress__bar {
  height: 100%;
  border-radius: inherit;
  background: var(--el-color-primary);
  transition: width 0.2s ease;
}

.action-btns {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
</style>
