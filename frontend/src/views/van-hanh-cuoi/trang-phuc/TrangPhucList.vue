<template>
  <div class="trang-phuc-list">
    <CustomCard shadow="hover" class="filter-card">
      <div class="toolbar">
        <CustomInput
          v-model="keyword"
          placeholder="Tìm theo mã, tên sản phẩm..."
          clearable
          style="max-width: 300px"
          @clear="onSearch"
          @keyup.enter="onSearch"
        >
          <template #prefix>
            <CustomIcon><Search /></CustomIcon>
          </template>
        </CustomInput>
        <CustomSelect
          v-model="danhMucFilter"
          placeholder="Danh mục"
          clearable
          filterable
          style="width: 200px"
          @change="onSearch"
        >
          <CustomOption
            v-for="item in danhMucOptions"
            :key="item.id"
            :label="item.ten_danh_muc"
            :value="item.id"
          />
        </CustomSelect>
        <CustomSelect
          v-model="chiNhanhFilter"
          placeholder="Chi nhánh"
          clearable
          filterable
          style="width: 200px"
          @change="onSearch"
        >
          <CustomOption
            v-for="item in chiNhanhOptions"
            :key="item.id"
            :label="item.ten_chi_nhanh"
            :value="item.id"
          />
        </CustomSelect>
        <CustomSelect
          v-model="trangThaiFilter"
          placeholder="Trạng thái"
          clearable
          style="width: 160px"
          @change="onSearch"
        >
          <CustomOption label="Hoạt động" :value="1" />
          <CustomOption label="Ngừng hoạt động" :value="0" />
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
          <span class="card-title">Danh sách trang phục</span>
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
          v-if="columnSettings.isColumnVisible('hinh_anh')"
          label="Hình ảnh"
          width="90"
          align="center"
        >
          <template #default="{ row }">
            <el-avatar
              v-if="row.hinh_anh"
              :size="48"
              :src="mediaUrl(row.hinh_anh)"
              shape="square"
              class="product-thumb"
            />
            <span v-else class="no-image">—</span>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ma_san_pham')"
          prop="ma_san_pham"
          label="Mã SP"
          width="120"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ten_san_pham')"
          prop="ten_san_pham"
          label="Tên sản phẩm"
          min-width="180"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('danh_muc')"
          label="Danh mục"
          min-width="150"
        >
          <template #default="{ row }">
            {{ row.danh_muc_trang_phuc?.ten_danh_muc || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nha_cung_cap')"
          label="Nhà cung cấp"
          min-width="160"
        >
          <template #default="{ row }">
            {{ row.nha_cung_cap_trang_phuc?.ten_nha_cung_cap || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('chi_nhanh')"
          label="Chi nhánh"
          min-width="140"
        >
          <template #default="{ row }">
            {{ row.cau_hinh_chi_nhanh?.ten_chi_nhanh || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('gia_tri')"
          label="Giá trị"
          width="120"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.gia_tri) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('gia_cho_thue')"
          label="Giá cho thuê"
          width="130"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.gia_cho_thue) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('tinh_trang')"
          label="Tình trạng"
          width="130"
        >
          <template #default="{ row }">
            {{ tinhTrangLabel(row.tinh_trang) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ngay_nhap')"
          label="Ngày nhập"
          width="120"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('trang_thai')"
          label="Trạng thái"
          width="150"
          align="center"
        >
          <template #default="{ row }">
            <div class="status-cell">
              <el-switch
                :model-value="row.trang_thai"
                :active-value="1"
                :inactive-value="0"
                :loading="togglingId === row.id"
                :disabled="togglingId === row.id"
                :before-change="() => toggleStatus(row)"
              />
              <span
                class="status-label"
                :class="row.trang_thai === 1 ? 'is-active' : 'is-inactive'"
              >
                {{ row.trang_thai === 1 ? 'Hoạt động' : 'Ngừng' }}
              </span>
            </div>
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
      :title="editingId ? 'Sửa trang phục' : 'Thêm trang phục'"
      :width="1300"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <CustomRow :gutter="16" class="main-form-row">
          <CustomCol :xs="24" :md="6">
            <CustomFormItem label="Hình ảnh" prop="hinh_anh">
              <div class="image-slot">
                <el-upload
                  class="image-uploader"
                  :show-file-list="false"
                  :auto-upload="false"
                  accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                  :on-change="onImageChange"
                >
                  <img
                    v-if="imagePreviewUrl"
                    :src="imagePreviewUrl"
                    class="image-preview"
                    alt="Ảnh trang phục"
                  />
                  <div v-else class="image-placeholder">
                    <el-icon><Plus /></el-icon>
                    <span>Chọn ảnh</span>
                  </div>
                </el-upload>
                <button
                  v-if="imagePreviewUrl"
                  type="button"
                  class="image-remove"
                  title="Xóa ảnh"
                  @click.stop="onImageRemove"
                >
                  <el-icon><Delete /></el-icon>
                </button>
              </div>
            </CustomFormItem>
          </CustomCol>

          <CustomCol :xs="24" :md="18">
            <CustomRow :gutter="16">
              <CustomCol :xs="24" :sm="12" :md="6">
                <CustomFormItem label="Mã sản phẩm" prop="ma_san_pham">
                  <CustomInput
                    v-model="form.ma_san_pham"
                    :disabled="!!editingId"
                    placeholder="VD: TP001"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="6">
                <CustomFormItem label="Tên sản phẩm" prop="ten_san_pham">
                  <CustomInput v-model="form.ten_san_pham" placeholder="Tên sản phẩm" />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="6">
                <CustomFormItem label="Danh mục" prop="danh_muc">
                  <CustomSelect
                    v-model="form.danh_muc"
                    placeholder="Chọn danh mục"
                    filterable
                    style="width: 100%"
                  >
                    <CustomOption
                      v-for="item in danhMucOptions"
                      :key="item.id"
                      :label="`${item.ma_danh_muc} - ${item.ten_danh_muc}`"
                      :value="item.id"
                    />
                  </CustomSelect>
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="6">
                <CustomFormItem label="Nhà cung cấp" prop="nha_cung_cap">
                  <CustomSelect
                    v-model="form.nha_cung_cap"
                    placeholder="Chọn nhà cung cấp"
                    filterable
                    style="width: 100%"
                  >
                    <CustomOption
                      v-for="item in nhaCungCapOptions"
                      :key="item.id"
                      :label="`${item.ma_nha_cung_cap} - ${item.ten_nha_cung_cap}`"
                      :value="item.id"
                    />
                  </CustomSelect>
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="6">
                <CustomFormItem label="Chi nhánh" prop="chi_nhanh">
                  <CustomSelect
                    v-model="form.chi_nhanh"
                    placeholder="Chọn chi nhánh"
                    filterable
                    style="width: 100%"
                  >
                    <CustomOption
                      v-for="item in chiNhanhOptions"
                      :key="item.id"
                      :label="item.ten_chi_nhanh"
                      :value="item.id"
                    />
                  </CustomSelect>
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="6">
                <CustomFormItem label="Phân loại chi phí" prop="phan_loai_chi_phi">
                  <CustomSelect v-model="form.phan_loai_chi_phi" placeholder="Chọn phân loại" style="width: 100%">
                    <CustomOption
                      v-for="opt in phanLoaiChiPhiOptions"
                      :key="opt.value"
                      :label="opt.label"
                      :value="opt.value"
                    />
                  </CustomSelect>
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="6">
                <CustomFormItem label="Giá trị" prop="gia_tri">
                  <CustomInput v-model="form.gia_tri" type="number" style="width: 100%" />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="6">
                <CustomFormItem label="Giá cho thuê" prop="gia_cho_thue">
                  <CustomInput v-model="form.gia_cho_thue" type="number" style="width: 100%" />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="6">
                <CustomFormItem label="Tình trạng" prop="tinh_trang">
                  <CustomSelect v-model="form.tinh_trang" placeholder="Chọn tình trạng" style="width: 100%">
                    <CustomOption
                      v-for="opt in tinhTrangOptions"
                      :key="opt.value"
                      :label="opt.label"
                      :value="opt.value"
                    />
                  </CustomSelect>
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="6">
                <CustomFormItem label="Trạng thái" prop="trang_thai">
                  <CustomSelect v-model="form.trang_thai" style="width: 100%">
                    <CustomOption label="Hoạt động" :value="1" />
                    <CustomOption label="Ngừng hoạt động" :value="0" />
                  </CustomSelect>
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="6">
                <CustomFormItem label="Ghi chú" prop="ghi_chu">
                  <CustomInput
                    v-model="form.ghi_chu"
                    type="text"
                    placeholder="Ghi chú (tuỳ chọn)"
                  />
                </CustomFormItem>
              </CustomCol>
            </CustomRow>
          </CustomCol>
        </CustomRow>

        <div class="thong-tin-them-section">
          <div class="section-header">
            <span class="section-title">Thông tin thêm</span>
            <CustomButton type="primary" plain size="small" @click="addThongTinThemRow">
              <CustomIcon><Plus /></CustomIcon>
              Thêm thuộc tính
            </CustomButton>
          </div>

          <div v-if="!form.thong_tin_them.length" class="empty-hint">
            Chưa có thuộc tính bổ sung. Nhấn "Thêm thuộc tính" để bắt đầu.
          </div>

          <div
            v-for="(item, index) in form.thong_tin_them"
            :key="index"
            class="thong-tin-them-row"
          >
            <CustomRow :gutter="12" class="thong-tin-them-row-inner">
              <CustomCol :xs="24" :sm="12" :md="7">
                <CustomFormItem
                  :label="index === 0 ? 'Tên thuộc tính' : ''"
                  :prop="`thong_tin_them.${index}.ten_thuoc_tinh`"
                  :rules="thongTinThemRules.ten_thuoc_tinh"
                >
                  <CustomInput v-model="item.ten_thuoc_tinh" placeholder="VD: Size, Màu sắc..." />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="7">
                <CustomFormItem
                  :label="index === 0 ? 'Giá trị' : ''"
                  :prop="`thong_tin_them.${index}.gia_tri`"
                >
                  <CustomInput v-model="item.gia_tri" placeholder="VD: M, Đỏ..." />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="7">
                <CustomFormItem
                  :label="index === 0 ? 'Ghi chú' : ''"
                  :prop="`thong_tin_them.${index}.ghi_chu`"
                >
                  <CustomInput v-model="item.ghi_chu" placeholder="Ghi chú (tuỳ chọn)" />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12" :md="3" class="remove-col">
                <div v-if="index === 0" class="remove-label-spacer" aria-hidden="true" />
                <div class="remove-btn-cell">
                  <CustomButton type="danger" link :icon="Delete" @click="removeThongTinThemRow(index)" />
                </div>
              </CustomCol>
            </CustomRow>
          </div>
        </div>
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
import { fetchChiNhanh } from '@/api/chiNhanh'
import { fetchDanhMucTrangPhuc } from '@/api/danhMucTrangPhuc'
import { fetchNhaCungCapTrangPhuc } from '@/api/nhaCungCapTrangPhuc'
import {
  createTrangPhuc,
  deleteTrangPhuc,
  fetchTrangPhuc,
  updateTrangPhuc,
  uploadTrangPhucHinhAnh,
} from '@/api/trangPhuc'
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
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'
import { mediaUrl } from '@/utils/media'

const ACTIVE = 1
const INACTIVE = 0

const tableColumns = [
  { key: 'hinh_anh', label: 'Hình ảnh' },
  { key: 'ma_san_pham', label: 'Mã SP' },
  { key: 'ten_san_pham', label: 'Tên sản phẩm' },
  { key: 'danh_muc', label: 'Danh mục' },
  { key: 'nha_cung_cap', label: 'Nhà cung cấp' },
  { key: 'chi_nhanh', label: 'Chi nhánh' },
  { key: 'gia_tri', label: 'Giá trị' },
  { key: 'gia_cho_thue', label: 'Giá cho thuê' },
  { key: 'tinh_trang', label: 'Tình trạng' },
  { key: 'ngay_nhap', label: 'Ngày nhập' },
  { key: 'trang_thai', label: 'Trạng thái' },
]
const columnSettings = useTableColumns('van-hanh-cuoi.trang-phuc-list', tableColumns)

const phanLoaiChiPhiOptions = [
  { value: 'dau_tu_tai_san', label: 'Đầu tư tài sản' },
  { value: 'vat_tu_tieu_hao', label: 'Vật tư tiêu hao' },
]

const tinhTrangOptions = [
  { value: 'con_hang', label: 'Còn hàng' },
  { value: 'dang_cho_thue', label: 'Đang cho thuê' },
  { value: 'dang_sua_chua', label: 'Đang sửa chữa' },
  { value: 'ngung_su_dung', label: 'Ngừng sử dụng' },
]

const items = ref([])
const danhMucOptions = ref([])
const nhaCungCapOptions = ref([])
const chiNhanhOptions = ref([])
const loading = ref(false)
const saving = ref(false)
const togglingId = ref(null)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const danhMucFilter = ref(null)
const chiNhanhFilter = ref(null)
const trangThaiFilter = ref(null)

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)
const pendingImageFile = ref(null)
const pendingPreviewUrl = ref('')
const bulkActivating = ref(false)
const bulkDeactivating = ref(false)
const bulkDeleting = ref(false)

const { selectedCount, onSelectionChange, clearSelection, countByStatus, idsByStatus, selectedIds } =
  useBulkSelection(
    () => true,
    (row) => Number(row.trang_thai),
  )

const bulkActions = computed(() => {
  const activeCount = countByStatus(INACTIVE)
  const inactiveCount = countByStatus(ACTIVE)
  return [
    {
      key: 'activate',
      label: 'Bật',
      type: 'success',
      badge: activeCount,
      badgeType: 'success',
      loading: bulkActivating.value,
      tooltip: activeCount
        ? `Bật ${activeCount} trang phục đang ngừng`
        : 'Chọn trang phục ngừng hoạt động để bật',
    },
    {
      key: 'deactivate',
      label: 'Tắt',
      type: 'warning',
      badge: inactiveCount,
      badgeType: 'warning',
      loading: bulkDeactivating.value,
      tooltip: inactiveCount
        ? `Tắt ${inactiveCount} trang phục đang hoạt động`
        : 'Chọn trang phục đang hoạt động để tắt',
    },
    {
      key: 'delete',
      label: 'Xóa',
      type: 'danger',
      badge: selectedCount.value,
      badgeType: 'danger',
      loading: bulkDeleting.value,
      tooltip: selectedCount.value
        ? `Xóa ${selectedCount.value} trang phục đã chọn`
        : 'Chọn trang phục để xóa',
    },
  ]
})

const emptyThongTinThemRow = () => ({
  ten_thuoc_tinh: '',
  gia_tri: '',
  ghi_chu: '',
})

const emptyForm = () => ({
  hinh_anh: '',
  ma_san_pham: '',
  ten_san_pham: '',
  danh_muc: null,
  nha_cung_cap: null,
  chi_nhanh: null,
  gia_tri: 0,
  gia_cho_thue: 0,
  phan_loai_chi_phi: 'dau_tu_tai_san',
  tinh_trang: 'con_hang',
  ghi_chu: '',
  trang_thai: 1,
  thong_tin_them: [],
})

const form = reactive(emptyForm())

const rules = {
  ma_san_pham: [{ required: true, message: 'Vui lòng nhập mã sản phẩm', trigger: 'blur' }],
  ten_san_pham: [{ required: true, message: 'Vui lòng nhập tên sản phẩm', trigger: 'blur' }],
  danh_muc: [{ required: true, message: 'Vui lòng chọn danh mục', trigger: 'change' }],
  nha_cung_cap: [{ required: true, message: 'Vui lòng chọn nhà cung cấp', trigger: 'change' }],
  chi_nhanh: [{ required: true, message: 'Vui lòng chọn chi nhánh', trigger: 'change' }],
  gia_tri: [{ required: true, message: 'Vui lòng nhập giá trị', trigger: 'blur' }],
  gia_cho_thue: [{ required: true, message: 'Vui lòng nhập giá cho thuê', trigger: 'blur' }],
  phan_loai_chi_phi: [{ required: true, message: 'Vui lòng chọn phân loại chi phí', trigger: 'change' }],
  tinh_trang: [{ required: true, message: 'Vui lòng chọn tình trạng', trigger: 'change' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

const thongTinThemRules = {
  ten_thuoc_tinh: [{ required: true, message: 'Vui lòng nhập tên thuộc tính', trigger: 'blur' }],
}

const imagePreviewUrl = computed(() => {
  if (pendingPreviewUrl.value) return pendingPreviewUrl.value
  return mediaUrl(form.hinh_anh)
})

function formatMoney(value) {
  return new Intl.NumberFormat('vi-VN').format(Number(value) || 0)
}

function formatDate(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '—'
  return date.toLocaleDateString('vi-VN')
}

function tinhTrangLabel(value) {
  return tinhTrangOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function clearPendingPreview() {
  if (pendingPreviewUrl.value) {
    URL.revokeObjectURL(pendingPreviewUrl.value)
  }
  pendingPreviewUrl.value = ''
  pendingImageFile.value = null
}

function onImageChange(uploadFile) {
  const file = uploadFile?.raw
  if (!file) return
  clearPendingPreview()
  pendingImageFile.value = file
  pendingPreviewUrl.value = URL.createObjectURL(file)
}

function onImageRemove() {
  clearPendingPreview()
  form.hinh_anh = ''
}

function addThongTinThemRow() {
  form.thong_tin_them.push(emptyThongTinThemRow())
}

function removeThongTinThemRow(index) {
  form.thong_tin_them.splice(index, 1)
}

function normalizeThongTinThem(value) {
  if (!Array.isArray(value)) return []
  return value.map((item) => ({
    ten_thuoc_tinh: item?.ten_thuoc_tinh || '',
    gia_tri: item?.gia_tri || '',
    ghi_chu: item?.ghi_chu || '',
  }))
}

function serializeThongTinThem(rows) {
  const validRows = rows
    .map((item) => ({
      ten_thuoc_tinh: item.ten_thuoc_tinh?.trim() || '',
      gia_tri: item.gia_tri?.trim() || null,
      ghi_chu: item.ghi_chu?.trim() || null,
    }))
    .filter((item) => item.ten_thuoc_tinh)

  return validRows.length ? validRows : null
}

function resolveFkId(value) {
  if (value == null) return null
  if (typeof value === 'object') return value.id ?? null
  return value
}

async function loadOptions() {
  try {
    const [danhMucRes, nhaCungCapRes, chiNhanhRes] = await Promise.all([
      fetchDanhMucTrangPhuc({ per_page: 100 }),
      fetchNhaCungCapTrangPhuc({ per_page: 100 }),
      fetchChiNhanh({ per_page: 100 }),
    ])
    danhMucOptions.value = danhMucRes.data.data || []
    nhaCungCapOptions.value = nhaCungCapRes.data.data || []
    chiNhanhOptions.value = chiNhanhRes.data.data || []
  } catch {
    danhMucOptions.value = []
    nhaCungCapOptions.value = []
    chiNhanhOptions.value = []
  }
}

function statusPayload(row, trangThai) {
  return {
    hinh_anh: row.hinh_anh,
    ma_san_pham: row.ma_san_pham,
    ten_san_pham: row.ten_san_pham,
    danh_muc: resolveFkId(row.danh_muc),
    nha_cung_cap: resolveFkId(row.nha_cung_cap),
    chi_nhanh: resolveFkId(row.chi_nhanh),
    gia_tri: row.gia_tri,
    gia_cho_thue: row.gia_cho_thue,
    phan_loai_chi_phi: row.phan_loai_chi_phi,
    tinh_trang: row.tinh_trang,
    ghi_chu: row.ghi_chu,
    trang_thai: trangThai,
    thong_tin_them: row.thong_tin_them,
  }
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchTrangPhuc({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      danh_muc: danhMucFilter.value || undefined,
      chi_nhanh: chiNhanhFilter.value || undefined,
      trang_thai: trangThaiFilter.value ?? undefined,
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
  clearPendingPreview()
  Object.assign(form, emptyForm())
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  clearPendingPreview()
  Object.assign(form, {
    hinh_anh: row.hinh_anh || '',
    ma_san_pham: row.ma_san_pham,
    ten_san_pham: row.ten_san_pham,
    danh_muc: resolveFkId(row.danh_muc),
    nha_cung_cap: resolveFkId(row.nha_cung_cap),
    chi_nhanh: resolveFkId(row.chi_nhanh),
    gia_tri: row.gia_tri ?? 0,
    gia_cho_thue: row.gia_cho_thue ?? 0,
    phan_loai_chi_phi: row.phan_loai_chi_phi,
    tinh_trang: row.tinh_trang,
    ghi_chu: row.ghi_chu || '',
    trang_thai: row.trang_thai ?? 1,
    thong_tin_them: normalizeThongTinThem(row.thong_tin_them),
  })
  dialogVisible.value = true
}

function buildPayload() {
  return {
    hinh_anh: form.hinh_anh?.trim() || null,
    ma_san_pham: form.ma_san_pham.trim(),
    ten_san_pham: form.ten_san_pham.trim(),
    danh_muc: resolveFkId(form.danh_muc),
    nha_cung_cap: resolveFkId(form.nha_cung_cap),
    chi_nhanh: resolveFkId(form.chi_nhanh),
    gia_tri: Number(form.gia_tri) || 0,
    gia_cho_thue: Number(form.gia_cho_thue) || 0,
    phan_loai_chi_phi: form.phan_loai_chi_phi,
    tinh_trang: form.tinh_trang,
    ghi_chu: form.ghi_chu?.trim() || null,
    trang_thai: form.trang_thai,
    thong_tin_them: serializeThongTinThem(form.thong_tin_them),
  }
}

async function save() {
  form.thong_tin_them = form.thong_tin_them.filter(
    (item) => item.ten_thuoc_tinh?.trim() || item.gia_tri?.trim() || item.ghi_chu?.trim(),
  )

  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  try {
    if (pendingImageFile.value) {
      const { data } = await uploadTrangPhucHinhAnh(pendingImageFile.value)
      form.hinh_anh = data.path
      clearPendingPreview()
    }

    const payload = buildPayload()

    if (editingId.value) {
      await updateTrangPhuc(editingId.value, payload)
      ElMessage.success('Đã cập nhật trang phục.')
    } else {
      await createTrangPhuc(payload)
      ElMessage.success('Đã thêm trang phục.')
    }
    dialogVisible.value = false
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
}

async function toggleStatus(row) {
  if (!row?.id) return false

  const value = Number(row.trang_thai) === ACTIVE ? INACTIVE : ACTIVE

  togglingId.value = row.id
  try {
    await updateTrangPhuc(row.id, statusPayload(row, value))
    row.trang_thai = value
    ElMessage.success('Đã cập nhật trạng thái.')
    return true
  } catch {
    return false
  } finally {
    togglingId.value = null
  }
}

async function onBulkAction(key) {
  if (key === 'activate') await bulkSetStatus(ACTIVE)
  else if (key === 'deactivate') await bulkSetStatus(INACTIVE)
  else if (key === 'delete') await bulkRemove()
}

async function bulkSetStatus(target) {
  const fromStatus = target === ACTIVE ? INACTIVE : ACTIVE
  const ids = idsByStatus(fromStatus)
  if (!ids.length) return

  const label = target === ACTIVE ? 'Bật' : 'Tắt'
  await ElMessageBox.confirm(`${label} ${ids.length} trang phục đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: label,
    cancelButtonText: 'Hủy',
  })

  const loadingRef = target === ACTIVE ? bulkActivating : bulkDeactivating
  loadingRef.value = true
  try {
    const rows = items.value.filter((item) => ids.includes(item.id))
    await runBulk(ids, async (id) => {
      const row = rows.find((item) => item.id === id)
      await updateTrangPhuc(id, statusPayload(row, target))
    })
    ElMessage.success(`Đã ${label.toLowerCase()} ${ids.length} trang phục.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    loadingRef.value = false
  }
}

async function bulkRemove() {
  const ids = selectedIds.value
  if (!ids.length) return

  await ElMessageBox.confirm(`Xóa ${ids.length} trang phục đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteTrangPhuc(id))
    ElMessage.success(`Đã xóa ${ids.length} trang phục.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa trang phục "${row.ten_san_pham}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteTrangPhuc(row.id)
    ElMessage.success('Đã xóa trang phục.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(async () => {
  await loadOptions()
  await loadItems()
})
</script>

<style scoped lang="scss">
.trang-phuc-list {
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

.product-thumb {
  border-radius: 6px;
}

.no-image {
  color: var(--el-text-color-placeholder);
}

.status-cell {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.status-label {
  font-size: 13px;

  &.is-active {
    color: var(--el-color-success);
  }

  &.is-inactive {
    color: var(--el-text-color-secondary);
  }
}

.image-slot {
  position: relative;
  width: 100%;
}

.image-uploader {
  width: 100%;

  :deep(.el-upload) {
    display: block;
    width: 100%;
    aspect-ratio: 1 / 1;
    border: 1px dashed var(--el-border-color);
    border-radius: 8px;
    cursor: pointer;
    overflow: hidden;
    transition: border-color 0.2s;

    &:hover {
      border-color: var(--el-color-primary);
    }
  }
}

.image-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.image-remove {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.thong-tin-them-section {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--el-border-color-lighter);
}

.main-form-row {
  align-items: flex-start;
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

.empty-hint {
  padding: 12px 16px;
  border-radius: 8px;
  background: var(--el-fill-color-light);
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.thong-tin-them-row {
  & + & {
    margin-top: 4px;
  }
}

.thong-tin-them-row-inner {
  align-items: flex-end;
}

.remove-col {
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding-bottom: 18px;
}

.remove-label-spacer {
  height: 30px;
  flex-shrink: 0;
}

.remove-btn-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 32px;
}
</style>
