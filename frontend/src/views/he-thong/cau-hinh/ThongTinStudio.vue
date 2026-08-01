<template>
  <ConfigSettingPage title="Thông tin studio">
    <el-tabs v-model="activeTab" class="studio-tabs">
      <!-- Tab 1: Thông tin studio -->
      <el-tab-pane label="Thông tin studio" name="studio">
        <div class="tab-panel">
          <CustomCard shadow="hover" class="filter-card">
            <div class="toolbar">
              <CustomInput
                v-model="studio.keyword"
                placeholder="Tìm theo tên, khẩu hiệu, email, SĐT..."
                clearable
                style="max-width: 320px"
                @clear="onStudioSearch"
                @keyup.enter="onStudioSearch"
              >
                <template #prefix>
                  <CustomIcon><Search /></CustomIcon>
                </template>
              </CustomInput>
              <CustomSelect
                v-model="studio.macDinhFilter"
                placeholder="Mặc định"
                clearable
                style="width: 160px"
                @change="onStudioSearch"
              >
                <CustomOption label="Có" value="co" />
                <CustomOption label="Không" value="khong" />
              </CustomSelect>
              <CustomButton type="primary" plain @click="onStudioSearch">
                <CustomIcon><Search /></CustomIcon>
                Tìm kiếm
              </CustomButton>
            </div>
          </CustomCard>

          <CustomCard shadow="hover" class="table-card">
            <template #header>
              <div class="card-header">
                <span class="card-title">Danh sách thông tin studio</span>
                <div class="header-actions">
                  <TableColumnConfig :settings="studioColumnSettings" />
                  <CustomTooltip content="Thêm mới" placement="top">
                    <CustomButton type="primary" @click="openStudioCreate">
                      <CustomIcon><Plus /></CustomIcon>
                      Thêm
                    </CustomButton>
                  </CustomTooltip>
                </div>
              </div>
            </template>

            <CustomTable v-loading="studio.loading" :data="studio.items" stripe style="width: 100%">
              <CustomTableColumn label="STT" width="60" align="center">
                <template #default="{ $index }">
                  {{ (studio.page - 1) * studio.perPage + $index + 1 }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="studioColumnSettings.isColumnVisible('logo')"
                label="Logo"
                width="80"
                align="center"
              >
                <template #default="{ row }">
                  <el-avatar
                    v-if="row.logo"
                    :size="40"
                    shape="square"
                    :src="mediaUrl(row.logo)"
                  />
                  <span v-else>—</span>
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="studioColumnSettings.isColumnVisible('ten_studio')"
                prop="ten_studio"
                label="Tên studio"
                min-width="160"
              />
              <CustomTableColumn
                v-if="studioColumnSettings.isColumnVisible('khau_hieu')"
                prop="khau_hieu"
                label="Khẩu hiệu"
                min-width="140"
                show-overflow-tooltip
              >
                <template #default="{ row }">
                  {{ row.khau_hieu || '—' }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="studioColumnSettings.isColumnVisible('dia_chi')"
                prop="dia_chi"
                label="Địa chỉ"
                min-width="200"
                show-overflow-tooltip
              >
                <template #default="{ row }">
                  {{ row.dia_chi || '—' }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="studioColumnSettings.isColumnVisible('email')"
                prop="email"
                label="Email"
                min-width="160"
                show-overflow-tooltip
              >
                <template #default="{ row }">
                  {{ row.email || '—' }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="studioColumnSettings.isColumnVisible('so_dien_thoai')"
                prop="so_dien_thoai"
                label="Số điện thoại"
                width="140"
              >
                <template #default="{ row }">
                  {{ row.so_dien_thoai || '—' }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="studioColumnSettings.isColumnVisible('ma_so_thue')"
                prop="ma_so_thue"
                label="Mã số thuế"
                width="140"
              >
                <template #default="{ row }">
                  {{ row.ma_so_thue || '—' }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="studioColumnSettings.isColumnVisible('mac_dinh')"
                prop="mac_dinh"
                label="Mặc định"
                width="160"
                align="center"
              >
                <template #default="{ row }">
                  <div class="status-cell">
                    <el-switch
                      :model-value="row.mac_dinh"
                      active-value="co"
                      inactive-value="khong"
                      :loading="studio.togglingId === row.id"
                      :disabled="studio.togglingId === row.id"
                      :before-change="() => toggleStudioMacDinh(row)"
                    />
                    <span
                      class="status-label"
                      :class="row.mac_dinh === 'co' ? 'is-active' : 'is-inactive'"
                    >
                      {{ row.mac_dinh === 'co' ? 'Có' : 'Không' }}
                    </span>
                  </div>
                </template>
              </CustomTableColumn>
              <CustomTableColumn label="Thao tác" width="100" fixed="right" align="center">
                <template #default="{ row }">
                  <div class="action-btns">
                    <CustomTooltip content="Sửa" placement="top">
                      <CustomButton type="primary" link :icon="Edit" @click="openStudioEdit(row)" />
                    </CustomTooltip>
                    <CustomTooltip content="Xóa" placement="top">
                      <CustomButton type="danger" link :icon="Delete" @click="removeStudio(row)" />
                    </CustomTooltip>
                  </div>
                </template>
              </CustomTableColumn>
            </CustomTable>

            <Pagination
              v-model="studio.page"
              v-model:page-size="studio.perPage"
              :total="studio.total"
              :disabled="studio.loading"
              @change="loadStudios"
            />
          </CustomCard>
        </div>
      </el-tab-pane>

      <!-- Tab 2: Tài khoản thanh toán -->
      <el-tab-pane label="Tài khoản thanh toán" name="payment">
        <div class="tab-panel">
          <CustomCard shadow="hover" class="filter-card">
            <div class="toolbar">
              <CustomInput
                v-model="payment.keyword"
                placeholder="Tìm theo ngân hàng, số TK, chủ TK..."
                clearable
                style="max-width: 320px"
                @clear="onPaymentSearch"
                @keyup.enter="onPaymentSearch"
              >
                <template #prefix>
                  <CustomIcon><Search /></CustomIcon>
                </template>
              </CustomInput>
              <CustomSelect
                v-model="payment.macDinhFilter"
                placeholder="Mặc định"
                clearable
                style="width: 160px"
                @change="onPaymentSearch"
              >
                <CustomOption label="Có" value="co" />
                <CustomOption label="Không" value="khong" />
              </CustomSelect>
              <CustomButton type="primary" plain @click="onPaymentSearch">
                <CustomIcon><Search /></CustomIcon>
                Tìm kiếm
              </CustomButton>
            </div>
          </CustomCard>

          <CustomCard shadow="hover" class="table-card">
            <template #header>
              <div class="card-header">
                <span class="card-title">Danh sách tài khoản thanh toán</span>
                <div class="header-actions">
                  <TableColumnConfig :settings="paymentColumnSettings" />
                  <CustomTooltip content="Thêm mới" placement="top">
                    <CustomButton type="primary" @click="openPaymentCreate">
                      <CustomIcon><Plus /></CustomIcon>
                      Thêm
                    </CustomButton>
                  </CustomTooltip>
                </div>
              </div>
            </template>

            <CustomTable v-loading="payment.loading" :data="payment.items" stripe style="width: 100%">
              <CustomTableColumn label="STT" width="60" align="center">
                <template #default="{ $index }">
                  {{ (payment.page - 1) * payment.perPage + $index + 1 }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="paymentColumnSettings.isColumnVisible('ngan_hang')"
                prop="ngan_hang"
                label="Ngân hàng"
                min-width="160"
              />
              <CustomTableColumn
                v-if="paymentColumnSettings.isColumnVisible('so_tai_khoan')"
                prop="so_tai_khoan"
                label="Số tài khoản"
                min-width="160"
              />
              <CustomTableColumn
                v-if="paymentColumnSettings.isColumnVisible('chu_tai_khoan')"
                prop="chu_tai_khoan"
                label="Chủ tài khoản"
                min-width="160"
              />
              <CustomTableColumn
                v-if="paymentColumnSettings.isColumnVisible('chi_nhanh')"
                prop="chi_nhanh"
                label="Chi nhánh"
                min-width="160"
                show-overflow-tooltip
              >
                <template #default="{ row }">
                  {{ row.chi_nhanh || '—' }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="paymentColumnSettings.isColumnVisible('mac_dinh')"
                prop="mac_dinh"
                label="Mặc định"
                width="160"
                align="center"
              >
                <template #default="{ row }">
                  <div class="status-cell">
                    <el-switch
                      :model-value="row.mac_dinh"
                      active-value="co"
                      inactive-value="khong"
                      :loading="payment.togglingId === row.id"
                      :disabled="payment.togglingId === row.id"
                      :before-change="() => togglePaymentMacDinh(row)"
                    />
                    <span
                      class="status-label"
                      :class="row.mac_dinh === 'co' ? 'is-active' : 'is-inactive'"
                    >
                      {{ row.mac_dinh === 'co' ? 'Có' : 'Không' }}
                    </span>
                  </div>
                </template>
              </CustomTableColumn>
              <CustomTableColumn label="Thao tác" width="100" fixed="right" align="center">
                <template #default="{ row }">
                  <div class="action-btns">
                    <CustomTooltip content="Sửa" placement="top">
                      <CustomButton type="primary" link :icon="Edit" @click="openPaymentEdit(row)" />
                    </CustomTooltip>
                    <CustomTooltip content="Xóa" placement="top">
                      <CustomButton type="danger" link :icon="Delete" @click="removePayment(row)" />
                    </CustomTooltip>
                  </div>
                </template>
              </CustomTableColumn>
            </CustomTable>

            <Pagination
              v-model="payment.page"
              v-model:page-size="payment.perPage"
              :total="payment.total"
              :disabled="payment.loading"
              @change="loadPayments"
            />
          </CustomCard>
        </div>
      </el-tab-pane>
    </el-tabs>

    <!-- Dialog: Thông tin studio -->
    <CustomDialog
      v-model="studio.dialogVisible"
      :title="studio.editingId ? 'Sửa thông tin studio' : 'Thêm thông tin studio'"
      :width="720"
    >
      <CustomForm ref="studioFormRef" :model="studioForm" :rules="studioRules">
        <CustomRow :gutter="16">
          <CustomCol :span="24">
            <CustomFormItem label="Logo" prop="logo">
              <div class="logo-slot">
                <el-upload
                  class="logo-uploader"
                  :show-file-list="false"
                  :auto-upload="false"
                  accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                  :on-change="onLogoChange"
                >
                  <img
                    v-if="logoPreviewUrl"
                    :src="logoPreviewUrl"
                    class="logo-image"
                    alt="Logo studio"
                  />
                  <div v-else class="logo-placeholder">
                    <el-icon><Plus /></el-icon>
                    <span>Chọn logo</span>
                  </div>
                </el-upload>
                <button
                  v-if="logoPreviewUrl"
                  type="button"
                  class="logo-remove"
                  title="Xóa logo"
                  @click.stop="onLogoRemove"
                >
                  <el-icon><Delete /></el-icon>
                </button>
              </div>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Tên studio" prop="ten_studio">
              <CustomInput v-model="studioForm.ten_studio" placeholder="VD: Pandio Studio" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Khẩu hiệu" prop="khau_hieu">
              <CustomInput v-model="studioForm.khau_hieu" placeholder="Khẩu hiệu (tuỳ chọn)" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :span="24">
            <CustomFormItem label="Địa chỉ" prop="dia_chi">
              <CustomInput v-model="studioForm.dia_chi" placeholder="Địa chỉ studio" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Email" prop="email">
              <CustomInput v-model="studioForm.email" placeholder="email@studio.com" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Số điện thoại" prop="so_dien_thoai">
              <CustomInput v-model="studioForm.so_dien_thoai" placeholder="0123456789" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Mã số thuế" prop="ma_so_thue">
              <CustomInput v-model="studioForm.ma_so_thue" placeholder="Mã số thuế" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Mặc định" prop="mac_dinh">
              <CustomSelect v-model="studioForm.mac_dinh" style="width: 100%">
                <CustomOption label="Có" value="co" />
                <CustomOption label="Không" value="khong" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
      <template #footer>
        <CustomButton @click="studio.dialogVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="studio.saving" @click="saveStudio">Lưu</CustomButton>
      </template>
    </CustomDialog>

    <!-- Dialog: Tài khoản thanh toán -->
    <CustomDialog
      v-model="payment.dialogVisible"
      :title="payment.editingId ? 'Sửa tài khoản thanh toán' : 'Thêm tài khoản thanh toán'"
      :width="640"
    >
      <CustomForm ref="paymentFormRef" :model="paymentForm" :rules="paymentRules">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Ngân hàng" prop="ngan_hang">
              <CustomInput v-model="paymentForm.ngan_hang" placeholder="VD: Vietcombank" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Số tài khoản" prop="so_tai_khoan">
              <CustomInput v-model="paymentForm.so_tai_khoan" placeholder="Số tài khoản" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Chủ tài khoản" prop="chu_tai_khoan">
              <CustomInput v-model="paymentForm.chu_tai_khoan" placeholder="Tên chủ tài khoản" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Chi nhánh" prop="chi_nhanh">
              <CustomInput v-model="paymentForm.chi_nhanh" placeholder="Chi nhánh (tuỳ chọn)" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Mặc định" prop="mac_dinh">
              <CustomSelect v-model="paymentForm.mac_dinh" style="width: 100%">
                <CustomOption label="Có" value="co" />
                <CustomOption label="Không" value="khong" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
      <template #footer>
        <CustomButton @click="payment.dialogVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="payment.saving" @click="savePayment">Lưu</CustomButton>
      </template>
    </CustomDialog>
  </ConfigSettingPage>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createThongTinStudio,
  deleteThongTinStudio,
  fetchThongTinStudio,
  updateThongTinStudio,
  uploadStudioLogo,
} from '@/api/thongTinStudio'
import {
  createTaiKhoanThanhToan,
  deleteTaiKhoanThanhToan,
  fetchTaiKhoanThanhToan,
  updateTaiKhoanThanhToan,
} from '@/api/taiKhoanThanhToan'
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
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { useTableColumns } from '@/composables/useTableColumns'
import { mediaUrl } from '@/utils/media'
import ConfigSettingPage from './ConfigSettingPage.vue'

const studioTableColumns = [
  { key: 'logo', label: 'Logo' },
  { key: 'ten_studio', label: 'Tên studio' },
  { key: 'khau_hieu', label: 'Khẩu hiệu' },
  { key: 'dia_chi', label: 'Địa chỉ' },
  { key: 'email', label: 'Email' },
  { key: 'so_dien_thoai', label: 'Số điện thoại' },
  { key: 'ma_so_thue', label: 'Mã số thuế' },
  { key: 'mac_dinh', label: 'Mặc định' },
]
const studioColumnSettings = useTableColumns('he-thong.thong-tin-studio', studioTableColumns)

const paymentTableColumns = [
  { key: 'ngan_hang', label: 'Ngân hàng' },
  { key: 'so_tai_khoan', label: 'Số tài khoản' },
  { key: 'chu_tai_khoan', label: 'Chủ tài khoản' },
  { key: 'chi_nhanh', label: 'Chi nhánh' },
  { key: 'mac_dinh', label: 'Mặc định' },
]
const paymentColumnSettings = useTableColumns('he-thong.tai-khoan-thanh-toan', paymentTableColumns)

const activeTab = ref('studio')
const paymentLoaded = ref(false)

const studio = reactive({
  items: [],
  loading: false,
  saving: false,
  togglingId: null,
  page: 1,
  perPage: 10,
  total: 0,
  keyword: '',
  macDinhFilter: '',
  dialogVisible: false,
  editingId: null,
})

const payment = reactive({
  items: [],
  loading: false,
  saving: false,
  togglingId: null,
  page: 1,
  perPage: 10,
  total: 0,
  keyword: '',
  macDinhFilter: '',
  dialogVisible: false,
  editingId: null,
})

const studioFormRef = ref(null)
const paymentFormRef = ref(null)
const pendingLogoFile = ref(null)
const pendingLogoPreview = ref('')

const emptyStudioForm = () => ({
  ten_studio: '',
  khau_hieu: '',
  logo: '',
  dia_chi: '',
  email: '',
  so_dien_thoai: '',
  ma_so_thue: '',
  mac_dinh: 'khong',
})

const emptyPaymentForm = () => ({
  ngan_hang: '',
  so_tai_khoan: '',
  chu_tai_khoan: '',
  chi_nhanh: '',
  mac_dinh: 'khong',
})

const studioForm = reactive(emptyStudioForm())
const paymentForm = reactive(emptyPaymentForm())

const studioRules = {
  ten_studio: [{ required: true, message: 'Vui lòng nhập tên studio', trigger: 'blur' }],
  email: [{ type: 'email', message: 'Email không hợp lệ', trigger: 'blur' }],
  mac_dinh: [{ required: true, message: 'Vui lòng chọn mặc định', trigger: 'change' }],
}

const paymentRules = {
  ngan_hang: [{ required: true, message: 'Vui lòng nhập ngân hàng', trigger: 'blur' }],
  so_tai_khoan: [{ required: true, message: 'Vui lòng nhập số tài khoản', trigger: 'blur' }],
  chu_tai_khoan: [{ required: true, message: 'Vui lòng nhập chủ tài khoản', trigger: 'blur' }],
  mac_dinh: [{ required: true, message: 'Vui lòng chọn mặc định', trigger: 'change' }],
}

const logoPreviewUrl = computed(() => {
  if (pendingLogoPreview.value) return pendingLogoPreview.value
  return mediaUrl(studioForm.logo)
})

function clearPendingLogo() {
  if (pendingLogoPreview.value) {
    URL.revokeObjectURL(pendingLogoPreview.value)
    pendingLogoPreview.value = ''
  }
  pendingLogoFile.value = null
}

function onLogoChange(uploadFile) {
  const file = uploadFile.raw
  if (!file) return

  const okTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif']
  if (!okTypes.includes(file.type)) {
    ElMessage.error('Chỉ chấp nhận ảnh JPEG, PNG, WEBP, GIF.')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    ElMessage.error('Logo tối đa 2MB.')
    return
  }

  clearPendingLogo()
  pendingLogoPreview.value = URL.createObjectURL(file)
  pendingLogoFile.value = file
}

function onLogoRemove() {
  clearPendingLogo()
  studioForm.logo = ''
}

async function loadStudios() {
  studio.loading = true
  try {
    const { data } = await fetchThongTinStudio({
      page: studio.page,
      per_page: studio.perPage,
      keyword: studio.keyword.trim() || undefined,
      mac_dinh: studio.macDinhFilter || undefined,
    })
    studio.items = data.data || []
    studio.total = data.total || 0
    studio.page = data.current_page || studio.page
  } catch {
    studio.items = []
    studio.total = 0
  } finally {
    studio.loading = false
  }
}

async function loadPayments() {
  payment.loading = true
  try {
    const { data } = await fetchTaiKhoanThanhToan({
      page: payment.page,
      per_page: payment.perPage,
      keyword: payment.keyword.trim() || undefined,
      mac_dinh: payment.macDinhFilter || undefined,
    })
    payment.items = data.data || []
    payment.total = data.total || 0
    payment.page = data.current_page || payment.page
  } catch {
    payment.items = []
    payment.total = 0
  } finally {
    payment.loading = false
  }
}

function onStudioSearch() {
  studio.page = 1
  loadStudios()
}

function onPaymentSearch() {
  payment.page = 1
  loadPayments()
}

async function toggleStudioMacDinh(row) {
  if (!row?.id) return false

  const value = row.mac_dinh === 'co' ? 'khong' : 'co'
  studio.togglingId = row.id

  try {
    await updateThongTinStudio(row.id, {
      ten_studio: row.ten_studio,
      khau_hieu: row.khau_hieu || null,
      logo: row.logo || null,
      dia_chi: row.dia_chi || null,
      email: row.email || null,
      so_dien_thoai: row.so_dien_thoai || null,
      ma_so_thue: row.ma_so_thue || null,
      mac_dinh: value,
    })
    if (value === 'co') {
      studio.items.forEach((item) => {
        item.mac_dinh = item.id === row.id ? 'co' : 'khong'
      })
    } else {
      row.mac_dinh = 'khong'
    }
    ElMessage.success(value === 'co' ? 'Đã đặt làm studio mặc định.' : 'Đã bỏ mặc định.')
    return true
  } catch {
    return false
  } finally {
    studio.togglingId = null
  }
}

async function togglePaymentMacDinh(row) {
  if (!row?.id) return false

  const value = row.mac_dinh === 'co' ? 'khong' : 'co'
  payment.togglingId = row.id

  try {
    await updateTaiKhoanThanhToan(row.id, {
      ngan_hang: row.ngan_hang,
      so_tai_khoan: row.so_tai_khoan,
      chu_tai_khoan: row.chu_tai_khoan,
      chi_nhanh: row.chi_nhanh || null,
      mac_dinh: value,
    })
    if (value === 'co') {
      payment.items.forEach((item) => {
        item.mac_dinh = item.id === row.id ? 'co' : 'khong'
      })
    } else {
      row.mac_dinh = 'khong'
    }
    ElMessage.success(
      value === 'co' ? 'Đã đặt làm tài khoản mặc định.' : 'Đã bỏ mặc định.',
    )
    return true
  } catch {
    return false
  } finally {
    payment.togglingId = null
  }
}

function openStudioCreate() {
  studio.editingId = null
  Object.assign(studioForm, emptyStudioForm())
  clearPendingLogo()
  studio.dialogVisible = true
}

function openStudioEdit(row) {
  studio.editingId = row.id
  Object.assign(studioForm, {
    ten_studio: row.ten_studio || '',
    khau_hieu: row.khau_hieu || '',
    logo: row.logo || '',
    dia_chi: row.dia_chi || '',
    email: row.email || '',
    so_dien_thoai: row.so_dien_thoai || '',
    ma_so_thue: row.ma_so_thue || '',
    mac_dinh: row.mac_dinh || 'khong',
  })
  clearPendingLogo()
  studio.dialogVisible = true
}

async function saveStudio() {
  const valid = await studioFormRef.value?.validate().catch(() => false)
  if (!valid) return

  studio.saving = true
  try {
    if (pendingLogoFile.value) {
      const { data } = await uploadStudioLogo(pendingLogoFile.value)
      studioForm.logo = data.path
      clearPendingLogo()
    }

    const payload = {
      ten_studio: studioForm.ten_studio.trim(),
      khau_hieu: studioForm.khau_hieu?.trim() || null,
      logo: studioForm.logo?.trim() || null,
      dia_chi: studioForm.dia_chi?.trim() || null,
      email: studioForm.email?.trim() || null,
      so_dien_thoai: studioForm.so_dien_thoai?.trim() || null,
      ma_so_thue: studioForm.ma_so_thue?.trim() || null,
      mac_dinh: studioForm.mac_dinh,
    }

    if (studio.editingId) {
      await updateThongTinStudio(studio.editingId, payload)
      ElMessage.success('Đã cập nhật thông tin studio.')
    } else {
      await createThongTinStudio(payload)
      ElMessage.success('Đã thêm thông tin studio.')
    }
    studio.dialogVisible = false
    await loadStudios()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    studio.saving = false
  }
}

async function removeStudio(row) {
  await ElMessageBox.confirm(`Xóa studio "${row.ten_studio}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteThongTinStudio(row.id)
    ElMessage.success('Đã xóa thông tin studio.')
    await loadStudios()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

function openPaymentCreate() {
  payment.editingId = null
  Object.assign(paymentForm, emptyPaymentForm())
  payment.dialogVisible = true
}

function openPaymentEdit(row) {
  payment.editingId = row.id
  Object.assign(paymentForm, {
    ngan_hang: row.ngan_hang || '',
    so_tai_khoan: row.so_tai_khoan || '',
    chu_tai_khoan: row.chu_tai_khoan || '',
    chi_nhanh: row.chi_nhanh || '',
    mac_dinh: row.mac_dinh || 'khong',
  })
  payment.dialogVisible = true
}

async function savePayment() {
  const valid = await paymentFormRef.value?.validate().catch(() => false)
  if (!valid) return

  payment.saving = true
  const payload = {
    ngan_hang: paymentForm.ngan_hang.trim(),
    so_tai_khoan: paymentForm.so_tai_khoan.trim(),
    chu_tai_khoan: paymentForm.chu_tai_khoan.trim(),
    chi_nhanh: paymentForm.chi_nhanh?.trim() || null,
    mac_dinh: paymentForm.mac_dinh,
  }

  try {
    if (payment.editingId) {
      await updateTaiKhoanThanhToan(payment.editingId, payload)
      ElMessage.success('Đã cập nhật tài khoản thanh toán.')
    } else {
      await createTaiKhoanThanhToan(payload)
      ElMessage.success('Đã thêm tài khoản thanh toán.')
    }
    payment.dialogVisible = false
    await loadPayments()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    payment.saving = false
  }
}

async function removePayment(row) {
  await ElMessageBox.confirm(
    `Xóa tài khoản "${row.so_tai_khoan}" (${row.ngan_hang})?`,
    'Xác nhận',
    {
      type: 'warning',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
    },
  )

  try {
    await deleteTaiKhoanThanhToan(row.id)
    ElMessage.success('Đã xóa tài khoản thanh toán.')
    await loadPayments()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

watch(activeTab, (tab) => {
  if (tab === 'payment' && !paymentLoaded.value) {
    paymentLoaded.value = true
    loadPayments()
  }
})

onMounted(loadStudios)
</script>

<style scoped>
.studio-tabs :deep(.el-tabs__header) {
  margin-bottom: 16px;
}

.tab-panel {
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

.header-actions {
  display: inline-flex;
  align-items: center;
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

.status-cell {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.status-label {
  font-size: 13px;
  line-height: 1.2;
  white-space: nowrap;
}

.status-label.is-active {
  color: var(--el-color-success);
}

.status-label.is-inactive {
  color: var(--el-text-color-secondary);
}

.logo-slot {
  position: relative;
  display: inline-block;
}

.logo-uploader :deep(.el-upload) {
  border: 1px dashed var(--el-border-color);
  border-radius: 8px;
  cursor: pointer;
  overflow: hidden;
  transition: border-color 0.2s;
}

.logo-uploader :deep(.el-upload:hover) {
  border-color: var(--el-color-primary);
}

.logo-image {
  width: 96px;
  height: 96px;
  object-fit: contain;
  display: block;
  background: var(--el-fill-color-light);
}

.logo-placeholder {
  width: 96px;
  height: 96px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  color: var(--el-text-color-secondary);
  font-size: 12px;
  background: var(--el-fill-color-blank);
}

.logo-remove {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 24px;
  height: 24px;
  border: none;
  border-radius: 50%;
  background: var(--el-color-danger);
  color: #fff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}
</style>
