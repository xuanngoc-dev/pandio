<template>
  <div class="hop-dong-sddv">
    <CustomCard shadow="hover" class="filter-card">
      <div class="toolbar">
        <CustomInput
          v-model="keyword"
          placeholder="Tìm theo mã HĐ, thông tin khách hàng..."
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
          v-model="filterLoaiHopDongId"
          placeholder="Loại hợp đồng"
          clearable
          filterable
          style="width: 240px"
          @change="onSearch"
        >
          <CustomOption
            v-for="item in loaiHopDongOptions"
            :key="item.id"
            :label="item.ten_hop_dong"
            :value="item.id"
          />
        </CustomSelect>
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
          <span class="card-title">Danh sách hợp đồng sử dụng dịch vụ</span>
          <BulkActionBar :actions="bulkActions" @action="onBulkAction">
            <TableColumnConfig :settings="columnSettings" />
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
          v-if="columnSettings.isColumnVisible('loai_hop_dong')"
          label="Loại hợp đồng"
          min-width="180"
        >
          <template #default="{ row }">
            {{ row.loai_hop_dong?.ten_hop_dong || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('khach_hang')"
          label="Khách hàng"
          min-width="180"
        >
          <template #default="{ row }">
            <div>{{ formatKhachHang(row) }}</div>
            <div v-if="formatSoDienThoai(row)" class="sub-text">
              {{ formatSoDienThoai(row) }}
            </div>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('kenh_tiep_can')"
          label="Kênh tiếp cận"
          min-width="130"
        >
          <template #default="{ row }">
            {{ row.kenh_tiep_can || '—' }}
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
          v-if="columnSettings.isColumnVisible('tien_coc')"
          label="Tiền cọc"
          width="120"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.tien_coc) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguoi_tao')"
          label="Người tạo"
          min-width="140"
        >
          <template #default="{ row }">
            {{ row.nguoi_tao?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('trang_thai')"
          label="Trạng thái"
          width="140"
          align="center"
        >
          <template #default="{ row }">
            <CustomTag :type="trangThaiTagType(row.trang_thai)" size="small">
              {{ trangThaiLabel(row.trang_thai) }}
            </CustomTag>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('created_at')"
          label="Ngày tạo"
          width="120"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Thao tác" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip content="Sửa" placement="top">
                <CustomButton type="primary" link :icon="Edit" @click="openEdit(row)" />
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

    <HopDongSddvFormModal
      v-model="formModalVisible"
      :hop-dong="currentHopDong"
      @saved="onFormSaved"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  deleteHopDongSuDungDichVu,
  fetchHopDongSuDungDichVu,
  khoiTaoHopDongSuDungDichVu,
} from '@/api/hopDongSuDungDichVu'
import { fetchLoaiHopDong } from '@/api/loaiHopDong'
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
  CustomTag,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'
import HopDongSddvFormModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvFormModal.vue'

const tableColumns = [
  { key: 'ma_hop_dong', label: 'Mã HĐ' },
  { key: 'loai_hop_dong', label: 'Loại hợp đồng' },
  { key: 'khach_hang', label: 'Khách hàng' },
  { key: 'kenh_tiep_can', label: 'Kênh tiếp cận' },
  { key: 'tong_tien', label: 'Tổng tiền' },
  { key: 'tien_coc', label: 'Tiền cọc' },
  { key: 'nguoi_tao', label: 'Người tạo' },
  { key: 'trang_thai', label: 'Trạng thái' },
  { key: 'created_at', label: 'Ngày tạo' },
]
const columnSettings = useTableColumns('van-hanh-cuoi.hop-dong-sddv', tableColumns)

const trangThaiOptions = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
  { value: 'da_coc', label: 'Đã cọc' },
  { value: 'dang_thuc_hien', label: 'Đang thực hiện' },
  { value: 'da_huy', label: 'Đã hủy' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
]

const items = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const filterLoaiHopDongId = ref(null)
const filterTrangThai = ref('')
const loaiHopDongOptions = ref([])
const bulkDeleting = ref(false)
const creating = ref(false)
const formModalVisible = ref(false)
const currentHopDong = ref(null)

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
  return trangThaiOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function trangThaiTagType(value) {
  const map = {
    moi_tao: 'info',
    nhap: '',
    da_coc: 'warning',
    dang_thuc_hien: 'primary',
    da_huy: 'danger',
    hoan_thanh: 'success',
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

function getThongTin(row) {
  return row?.thong_tin_hop_dong && typeof row.thong_tin_hop_dong === 'object'
    ? row.thong_tin_hop_dong
    : {}
}

function formatKhachHang(row) {
  if (row.ten_khach_hang) return row.ten_khach_hang
  const info = getThongTin(row)
  const tenChuRe = info.tenChuRe || info.ten_chu_re
  const tenCoDau = info.tenCoDau || info.ten_co_dau
  if (tenChuRe || tenCoDau) {
    return [tenChuRe, tenCoDau].filter(Boolean).join(' & ')
  }
  return info.hoTenKhachHang || info.ho_ten_khach_hang || info.hoTenKhach || '—'
}

function formatSoDienThoai(row) {
  if (row.sdt_khach_hang) return row.sdt_khach_hang
  const info = getThongTin(row)
  return info.soDienThoai || info.so_dien_thoai || info.sdt || ''
}

async function loadLoaiHopDongOptions() {
  try {
    const { data } = await fetchLoaiHopDong({ per_page: 100, trang_thai: 'hoat_dong' })
    loaiHopDongOptions.value = data.data || []
  } catch {
    loaiHopDongOptions.value = []
  }
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchHopDongSuDungDichVu({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      loai_hop_dong_id: filterLoaiHopDongId.value || undefined,
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
    const { data } = await khoiTaoHopDongSuDungDichVu()
    currentHopDong.value = data
    formModalVisible.value = true
    await loadItems()
  } catch {
    // interceptor
  } finally {
    creating.value = false
  }
}

function openEdit(row) {
  currentHopDong.value = row
  formModalVisible.value = true
}

function onFormSaved(hopDong) {
  currentHopDong.value = hopDong
  loadItems()
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
    await runBulk(ids, (id) => deleteHopDongSuDungDichVu(id))
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
    await deleteHopDongSuDungDichVu(row.id)
    ElMessage.success('Đã xóa hợp đồng sử dụng dịch vụ.')
    await loadItems()
  } catch {
    // interceptor
  }
}

onMounted(() => {
  loadLoaiHopDongOptions()
  loadItems()
})
</script>

<style scoped lang="scss">
.hop-dong-sddv {
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

.action-btns {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
</style>
