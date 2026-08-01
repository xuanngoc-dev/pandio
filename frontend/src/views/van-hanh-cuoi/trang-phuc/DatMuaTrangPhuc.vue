<template>
  <div class="dat-mua-trang-phuc">
    <CustomCard shadow="hover" class="filter-card">
      <div class="toolbar">
        <CustomInput
          v-model="keyword"
          placeholder="Tìm theo nhà cung cấp, loại đơn hàng..."
          clearable
          style="max-width: 360px"
          @clear="onSearch"
          @keyup.enter="onSearch"
        >
          <template #prefix>
            <CustomIcon><Search /></CustomIcon>
          </template>
        </CustomInput>
        <CustomButton type="primary" plain @click="onSearch">
          <CustomIcon><Search /></CustomIcon>
          Tìm kiếm
        </CustomButton>
      </div>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách đặt mua trang phục</span>
          <BulkActionBar :actions="bulkActions" @action="onBulkAction">
            <TableColumnConfig :settings="columnSettings" />
            <CustomTooltip content="Thêm mới" placement="top">
              <CustomButton type="primary" @click="openCreate">
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
          v-if="columnSettings.isColumnVisible('nha_cung_cap')"
          label="Nhà cung cấp"
          min-width="180"
        >
          <template #default="{ row }">
            {{ row.nha_cung_cap?.ten_nha_cung_cap || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('loai_don_hang')"
          label="Loại đơn hàng"
          width="160"
        >
          <template #default="{ row }">
            {{ loaiDonHangLabel(row.loai_don_hang) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguon_hang_hoa')"
          label="Nguồn hàng hóa"
          width="130"
        >
          <template #default="{ row }">
            {{ nguonHangHoaLabel(row.nguon_hang_hoa) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ngay_dat')"
          label="Ngày đặt"
          width="120"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDate(row.ngay_dat) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('tong_tien_hang')"
          label="Tổng tiền hàng"
          width="140"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.tong_tien_hang) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('du_no')"
          label="Dư nợ"
          width="140"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.du_no) }}
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

    <CustomDialog
      v-model="dialogVisible"
      :title="editingId ? 'Sửa đơn đặt mua trang phục' : 'Thêm đơn đặt mua trang phục'"
      :width="1100"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Nhà cung cấp" prop="nha_cung_cap_id">
              <CustomSelect
                v-model="form.nha_cung_cap_id"
                placeholder="Chọn nhà cung cấp"
                filterable
                style="width: 100%"
              >
                <CustomOption
                  v-for="ncc in nhaCungCapOptions"
                  :key="ncc.id"
                  :label="`${ncc.ma_nha_cung_cap} - ${ncc.ten_nha_cung_cap}`"
                  :value="ncc.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Loại đơn hàng" prop="loai_don_hang">
              <CustomSelect v-model="form.loai_don_hang" placeholder="Chọn loại đơn hàng" style="width: 100%">
                <CustomOption
                  v-for="opt in loaiDonHangOptions"
                  :key="opt.value"
                  :label="opt.label"
                  :value="opt.value"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Nguồn hàng hóa" prop="nguon_hang_hoa">
              <CustomSelect v-model="form.nguon_hang_hoa" placeholder="Chọn nguồn hàng hóa" style="width: 100%">
                <CustomOption
                  v-for="opt in nguonHangHoaOptions"
                  :key="opt.value"
                  :label="opt.label"
                  :value="opt.value"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Ngày đặt" prop="ngay_dat">
              <el-date-picker
                v-model="form.ngay_dat"
                type="date"
                placeholder="Chọn ngày đặt"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                style="width: 100%"
              />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>

        <div class="mat-hang-section">
          <div class="section-header">
            <span class="section-title">Mặt hàng</span>
            <CustomButton type="primary" plain size="small" @click="addMatHangRow">
              <CustomIcon><Plus /></CustomIcon>
              Thêm mặt hàng
            </CustomButton>
          </div>

          <div v-for="(item, index) in form.mat_hang" :key="index" class="mat-hang-row">
            <CustomRow :gutter="12" align="middle">
              <CustomCol :xs="24" :sm="12" :md="8">
                <CustomFormItem
                  :label="index === 0 ? 'Tên mặt hàng' : ''"
                  :prop="`mat_hang.${index}.ten_mat_hang`"
                  :rules="matHangRules.ten_mat_hang"
                >
                  <CustomInput v-model="item.ten_mat_hang" placeholder="Tên mặt hàng" />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="4">
                <CustomFormItem
                  :label="index === 0 ? 'Số lượng' : ''"
                  :prop="`mat_hang.${index}.so_luong`"
                  :rules="matHangRules.so_luong"
                >
                  <el-input-number
                    v-model="item.so_luong"
                    :min="1"
                    :step="1"
                    controls-position="right"
                    style="width: 100%"
                    @change="updateThanhTien(item)"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="5">
                <CustomFormItem
                  :label="index === 0 ? 'Đơn giá' : ''"
                  :prop="`mat_hang.${index}.don_gia`"
                  :rules="matHangRules.don_gia"
                >
                  <MoneyInput
                    v-model="item.don_gia"
                    style="width: 100%"
                    @update:model-value="updateThanhTien(item)"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="18" :sm="10" :md="5">
                <CustomFormItem :label="index === 0 ? 'Thành tiền' : ''">
                  <CustomInput :model-value="formatMoney(item.thanh_tien)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="6" :sm="2" :md="2" class="remove-col">
                <CustomButton
                  v-if="form.mat_hang.length > 1"
                  type="danger"
                  link
                  :icon="Delete"
                  @click="removeMatHangRow(index)"
                />
              </CustomCol>
            </CustomRow>
          </div>
        </div>

        <CustomRow :gutter="16" class="summary-row">
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Tổng tiền hàng">
              <CustomInput :model-value="formatMoney(tongTienHang)" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Phí vận chuyển" prop="phi_van_chuyen">
              <MoneyInput v-model="form.phi_van_chuyen" style="width: 100%" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Tiền cọc" prop="tien_coc">
              <MoneyInput v-model="form.tien_coc" style="width: 100%" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Dư nợ">
              <CustomInput :model-value="formatMoney(duNo)" readonly class="du-no-input" />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
      <template #footer>
        <CustomButton @click="dialogVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="saving" @click="save">Lưu</CustomButton>
      </template>
    </CustomDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createDatMuaTrangPhuc,
  deleteDatMuaTrangPhuc,
  fetchDatMuaTrangPhuc,
  updateDatMuaTrangPhuc,
} from '@/api/datMuaTrangPhuc'
import { fetchNhaCungCapTrangPhuc } from '@/api/nhaCungCapTrangPhuc'
import BulkActionBar from '@/components/BulkActionBar.vue'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { runBulk, useBulkSelection } from '@/composables/useBulkSelection'
import { useTableColumns } from '@/composables/useTableColumns'
import {
  CustomButton,
  CustomCard,
  CustomCol,
  CustomDialog,
  CustomForm,
  CustomFormItem,
  CustomIcon,
  CustomInput,
  CustomOption,
  CustomRow,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
  MoneyInput,
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

const tableColumns = [
  { key: 'nha_cung_cap', label: 'Nhà cung cấp' },
  { key: 'loai_don_hang', label: 'Loại đơn hàng' },
  { key: 'nguon_hang_hoa', label: 'Nguồn hàng hóa' },
  { key: 'ngay_dat', label: 'Ngày đặt' },
  { key: 'tong_tien_hang', label: 'Tổng tiền hàng' },
  { key: 'du_no', label: 'Dư nợ' },
]
const columnSettings = useTableColumns('van-hanh-cuoi.dat-mua-trang-phuc', tableColumns)

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const nhaCungCapOptions = ref([])

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)
const bulkDeleting = ref(false)

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
      ? `Xóa ${selectedCount.value} đơn đặt mua đã chọn`
      : 'Chọn đơn đặt mua để xóa',
  },
])

const emptyMatHangRow = () => ({
  ten_mat_hang: '',
  so_luong: 1,
  don_gia: 0,
  thanh_tien: 0,
})

const emptyForm = () => ({
  nha_cung_cap_id: null,
  loai_don_hang: '',
  nguon_hang_hoa: '',
  ngay_dat: '',
  mat_hang: [emptyMatHangRow()],
  phi_van_chuyen: 0,
  tien_coc: 0,
})

const form = reactive(emptyForm())

const rules = {
  nha_cung_cap_id: [{ required: true, message: 'Vui lòng chọn nhà cung cấp', trigger: 'change' }],
  loai_don_hang: [{ required: true, message: 'Vui lòng chọn loại đơn hàng', trigger: 'change' }],
  nguon_hang_hoa: [{ required: true, message: 'Vui lòng chọn nguồn hàng hóa', trigger: 'change' }],
  ngay_dat: [{ required: true, message: 'Vui lòng chọn ngày đặt', trigger: 'change' }],
}

const matHangRules = {
  ten_mat_hang: [{ required: true, message: 'Vui lòng nhập tên mặt hàng', trigger: 'blur' }],
  so_luong: [{ required: true, message: 'Vui lòng nhập số lượng', trigger: 'change' }],
  don_gia: [{ required: true, message: 'Vui lòng nhập đơn giá', trigger: 'blur' }],
}

const tongTienHang = computed(() =>
  form.mat_hang.reduce((sum, item) => sum + (Number(item.thanh_tien) || 0), 0),
)

const duNo = computed(() => {
  const phiVanChuyen = Number(form.phi_van_chuyen) || 0
  const tienCoc = Number(form.tien_coc) || 0
  return tongTienHang.value + phiVanChuyen - tienCoc
})

function loaiDonHangLabel(value) {
  return loaiDonHangOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function nguonHangHoaLabel(value) {
  return nguonHangHoaOptions.find((opt) => opt.value === value)?.label || value || '—'
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

function updateThanhTien(item) {
  const soLuong = Number(item.so_luong) || 0
  const donGia = Number(item.don_gia) || 0
  item.thanh_tien = soLuong * donGia
}

function addMatHangRow() {
  form.mat_hang.push(emptyMatHangRow())
}

function removeMatHangRow(index) {
  form.mat_hang.splice(index, 1)
}

async function loadNhaCungCapOptions() {
  try {
    const { data } = await fetchNhaCungCapTrangPhuc({ per_page: 100 })
    nhaCungCapOptions.value = data.data || []
  } catch {
    nhaCungCapOptions.value = []
  }
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchDatMuaTrangPhuc({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
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

function openCreate() {
  editingId.value = null
  Object.assign(form, emptyForm())
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  Object.assign(form, {
    nha_cung_cap_id: row.nha_cung_cap_id,
    loai_don_hang: row.loai_don_hang,
    nguon_hang_hoa: row.nguon_hang_hoa,
    ngay_dat: row.ngay_dat?.slice?.(0, 10) || row.ngay_dat || '',
    mat_hang: (row.mat_hang || []).map((item) => ({
      ten_mat_hang: item.ten_mat_hang || '',
      so_luong: Number(item.so_luong) || 1,
      don_gia: Number(item.don_gia) || 0,
      thanh_tien: Number(item.thanh_tien) || 0,
    })),
    phi_van_chuyen: Number(row.phi_van_chuyen) || 0,
    tien_coc: Number(row.tien_coc) || 0,
  })

  if (!form.mat_hang.length) {
    form.mat_hang = [emptyMatHangRow()]
  }

  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    nha_cung_cap_id: form.nha_cung_cap_id,
    loai_don_hang: form.loai_don_hang,
    nguon_hang_hoa: form.nguon_hang_hoa,
    ngay_dat: form.ngay_dat,
    mat_hang: form.mat_hang.map((item) => ({
      ten_mat_hang: item.ten_mat_hang.trim(),
      so_luong: Number(item.so_luong) || 0,
      don_gia: Number(item.don_gia) || 0,
      thanh_tien: (Number(item.so_luong) || 0) * (Number(item.don_gia) || 0),
    })),
    phi_van_chuyen: Number(form.phi_van_chuyen) || 0,
    tien_coc: Number(form.tien_coc) || 0,
  }

  try {
    if (editingId.value) {
      await updateDatMuaTrangPhuc(editingId.value, payload)
      ElMessage.success('Đã cập nhật đơn đặt mua trang phục.')
    } else {
      await createDatMuaTrangPhuc(payload)
      ElMessage.success('Đã thêm đơn đặt mua trang phục.')
    }
    dialogVisible.value = false
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
}

async function onBulkAction(key) {
  if (key === 'delete') await bulkRemove()
}

async function bulkRemove() {
  const ids = selectedIds.value
  if (!ids.length) return

  await ElMessageBox.confirm(`Xóa ${ids.length} đơn đặt mua đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteDatMuaTrangPhuc(id))
    ElMessage.success(`Đã xóa ${ids.length} đơn đặt mua.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  const tenNcc = row.nha_cung_cap?.ten_nha_cung_cap || 'này'
  await ElMessageBox.confirm(`Xóa đơn đặt mua từ nhà cung cấp "${tenNcc}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteDatMuaTrangPhuc(row.id)
    ElMessage.success('Đã xóa đơn đặt mua trang phục.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(() => {
  loadNhaCungCapOptions()
  loadItems()
})
</script>

<style scoped lang="scss">
.dat-mua-trang-phuc {
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

.action-btns {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.mat-hang-section {
  margin: 8px 0 16px;
  padding: 16px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  background: var(--el-fill-color-blank);
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.section-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.mat-hang-row {
  &:not(:last-child) {
    margin-bottom: 4px;
  }
}

.remove-col {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding-bottom: 18px;
}

.summary-row {
  margin-top: 8px;
}

.du-no-input :deep(.el-input__inner) {
  font-weight: 600;
  color: var(--el-color-primary);
}
</style>
