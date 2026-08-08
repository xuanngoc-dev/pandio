<template>
  <div class="hop-dong-sddv page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="6" :lg="7">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo mã HĐ, thông tin khách hàng..."
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
        <CustomCol :xs="12" :sm="12" :md="6" :lg="5">
          <CustomSelect
            v-model="filterLoaiHopDongId"
            placeholder="Loại hợp đồng"
            clearable
            filterable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="item in loaiHopDongOptions"
              :key="item.id"
              :label="item.ten_hop_dong"
              :value="item.id"
            />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="6" :lg="5">
          <CustomSelect
            v-model="filterTrangThai"
            placeholder="Trạng thái"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="opt in trangThaiOptions"
              :key="opt.value"
              :label="opt.label"
              :value="opt.value"
            />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="6" :lg="4">
          <CustomButton type="primary" plain @click="onSearch">
            <CustomIcon><Search /></CustomIcon>
            Tìm kiếm
          </CustomButton>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách hợp đồng</span>
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
        <CustomTableColumn label="Thao tác" width="200" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip content="Xem hợp đồng" placement="top">
                <CustomButton type="info" link :icon="View" @click="openDetail(row)" />
              </CustomTooltip>
              <CustomTooltip content="Điều phối" placement="top">
                <CustomButton type="warning" link :icon="Position" @click="openDieuPhoi(row)" />
              </CustomTooltip>
              <CustomTooltip :content="thanhToanTooltip(row)" placement="top">
                <CustomButton
                  type="success"
                  link
                  :icon="Wallet"
                  :disabled="!canOpenThanhToan(row)"
                  @click="openThanhToan(row)"
                />
              </CustomTooltip>
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

    <HopDongSddvDraftModal
      ref="draftModalRef"
      v-model="draftModalVisible"
      @continue="onContinueDraft"
      @changed="loadItems"
    />

    <HopDongSddvFormModal
      v-model="formModalVisible"
      :hop-dong="currentHopDong"
      @saved="onFormSaved"
      @closed="onFormClosed"
    />

    <HopDongSddvDieuPhoiModal
      v-model="dieuPhoiModalVisible"
      :hop-dong-id="dieuPhoiHopDongId"
      @saved="loadItems"
    />

    <HopDongSddvDetailModal
      v-model="detailModalVisible"
      :hop-dong-id="detailHopDongId"
    />

    <HopDongSddvThanhToanModal
      v-model="thanhToanModalVisible"
      :hop-dong="thanhToanHopDong"
      @saved="onThanhToanSaved"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Document, Edit, Plus, Position, Search, View, Wallet } from '@element-plus/icons-vue'
import {
  deleteHopDongSuDungDichVu,
  fetchHopDongSuDungDichVu,
  getHopDongSuDungDichVu,
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
  CustomCol,
  CustomIcon,
  CustomInput,
  CustomOption,
  CustomRow,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
  CustomTag,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'
import HopDongSddvDetailModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvDetailModal.vue'
import HopDongSddvDieuPhoiModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvDieuPhoiModal.vue'
import HopDongSddvDraftModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvDraftModal.vue'
import HopDongSddvFormModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvFormModal.vue'
import HopDongSddvThanhToanModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvThanhToanModal.vue'

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
const route = useRoute()
const keyword = ref(String(route.query.keyword || ''))
const filterLoaiHopDongId = ref(null)
const filterTrangThai = ref('')
const loaiHopDongOptions = ref([])
const bulkDeleting = ref(false)
const creating = ref(false)
const formModalVisible = ref(false)
const draftModalVisible = ref(false)
const draftModalRef = ref(null)
const currentHopDong = ref(null)
const dieuPhoiModalVisible = ref(false)
const dieuPhoiHopDongId = ref(null)
const detailModalVisible = ref(false)
const detailHopDongId = ref(null)
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
  } catch {
    // interceptor
  } finally {
    creating.value = false
  }
}

function openDrafts() {
  draftModalVisible.value = true
}

function openDetail(row) {
  detailHopDongId.value = row.id
  detailModalVisible.value = true
}

function openDieuPhoi(row) {
  if (!row?.loai_hop_dong_id) {
    ElMessage.warning('Hợp đồng chưa chọn loại hợp đồng.')
    return
  }
  dieuPhoiHopDongId.value = row.id
  dieuPhoiModalVisible.value = true
}

function getDaThanhToan(row) {
  return (
    (Number(row?.so_tien_thanh_toan_lan_1) || 0) +
    (Number(row?.so_tien_thanh_toan_lan_2) || 0) +
    (Number(row?.so_tien_thanh_toan_lan_3) || 0)
  )
}

function getKhachPhaiThanhToan(row) {
  const tong = Number(row?.tong_tien) || 0
  const phatSinh = Number(row?.phat_sinh) || 0
  const chietKhau = Number(row?.chiet_khau) || 0
  const giamGia = Number(row?.khuyen_mai_theo_ma_giam_gia) || 0
  return Math.max(0, tong + phatSinh - chietKhau - giamGia)
}

function getConLai(row) {
  return Math.max(0, getKhachPhaiThanhToan(row) - getDaThanhToan(row))
}

function hasPaymentSlot(row) {
  return (
    (Number(row?.so_tien_thanh_toan_lan_1) || 0) <= 0 ||
    (Number(row?.so_tien_thanh_toan_lan_2) || 0) <= 0 ||
    (Number(row?.so_tien_thanh_toan_lan_3) || 0) <= 0
  )
}

function canThanhToan(row) {
  return (
    ['da_coc', 'dang_thuc_hien'].includes(row?.trang_thai) &&
    getConLai(row) > 0 &&
    hasPaymentSlot(row)
  )
}

function canViewLichSuThanhToan(row) {
  return getDaThanhToan(row) > 0
}

function canOpenThanhToan(row) {
  return canThanhToan(row) || canViewLichSuThanhToan(row)
}

function thanhToanTooltip(row) {
  if (canThanhToan(row)) return 'Thanh toán'
  if (canViewLichSuThanhToan(row)) return 'Xem lịch sử thanh toán'
  return thanhToanDisabledReason(row)
}

function thanhToanDisabledReason(row) {
  if (row?.trang_thai === 'da_huy') return 'Hợp đồng đã hủy'
  if (row?.trang_thai === 'nhap' || row?.trang_thai === 'moi_tao') {
    return 'Hợp đồng chưa đủ điều kiện thanh toán'
  }
  if (!['da_coc', 'dang_thuc_hien', 'hoan_thanh'].includes(row?.trang_thai)) {
    return 'Không thể mở thanh toán'
  }
  if (getDaThanhToan(row) <= 0) return 'Chưa có lịch sử thanh toán'
  return 'Không thể thanh toán'
}

async function openThanhToan(row) {
  if (!canOpenThanhToan(row)) {
    ElMessage.warning(thanhToanDisabledReason(row))
    return
  }
  thanhToanHopDong.value = row
  thanhToanModalVisible.value = true
  try {
    const { data } = await getHopDongSuDungDichVu(row.id)
    thanhToanHopDong.value = data
  } catch {
    // keep list row as fallback; modal will also try reload
  }
}

function onThanhToanSaved() {
  loadItems()
}

async function openEdit(row) {
  try {
    const { data } = await getHopDongSuDungDichVu(row.id)
    currentHopDong.value = data
  } catch {
    currentHopDong.value = row
  }
  formModalVisible.value = true
}

async function onContinueDraft(row) {
  draftModalVisible.value = false
  try {
    const { data } = await getHopDongSuDungDichVu(row.id)
    currentHopDong.value = data
  } catch {
    currentHopDong.value = row
  }
  formModalVisible.value = true
}

function onFormSaved(hopDong) {
  currentHopDong.value = hopDong
  // Sau khi hoàn thành bước thanh toán → tìm đúng HĐ vừa lưu trên danh sách
  if (hopDong?.trang_thai === 'dang_thuc_hien' && hopDong?.ma_hop_dong) {
    keyword.value = hopDong.ma_hop_dong
    filterLoaiHopDongId.value = null
    filterTrangThai.value = 'dang_thuc_hien'
    page.value = 1
  }
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
.sub-text {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>
