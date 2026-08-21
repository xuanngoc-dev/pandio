<template>
  <div class="employee-list page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="8" :lg="8">
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
        <CustomCol :xs="12" :sm="12" :md="8" :lg="8">
          <CustomSelect
            v-model="statusFilter"
            placeholder="Trạng thái"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption label="Đang hoạt động" value="active" />
            <CustomOption label="Không hoạt động" value="inactive" />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="8" :lg="4">
          <CustomButton type="primary" plain @click="onSearch">
            Tìm kiếm
          </CustomButton>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách nhân sự</span>
          <div class="card-header-actions">
            <TableColumnConfig :settings="columnSettings" />
            <CustomTooltip content="Thêm mới" placement="top">
              <CustomButton type="primary" @click="openCreate">
                Thêm
              </CustomButton>
            </CustomTooltip>
          </div>
        </div>
      </template>

      <CustomTable :column-settings="columnSettings" v-loading="loading" :data="employees" stripe row-key="id" style="width: 100%">
        <CustomTableColumn type="expand" width="40">
          <template #default="{ row }">
            <div class="expand-panel">
              <section class="expand-block expand-block--form expand-block--wide">
                <h4 class="expand-title">Thông tin cá nhân</h4>
                <div class="expand-fields">
                  <div class="expand-field">
                    <label class="expand-field__label">CCCD</label>
                    <CustomInput :model-value="nv(row).cccd || '—'" readonly />
                  </div>
                  <div class="expand-field">
                    <label class="expand-field__label">Ngày sinh</label>
                    <CustomInput :model-value="formatDate(nv(row).ngay_sinh)" readonly />
                  </div>
                  <div class="expand-field">
                    <label class="expand-field__label">Giới tính</label>
                    <CustomInput :model-value="genderLabel(nv(row).gioi_tinh)" readonly />
                  </div>
                  <div class="expand-field">
                    <label class="expand-field__label">Ngày ký HĐ</label>
                    <CustomInput :model-value="formatDate(nv(row).ngay_ky_hop_dong)" readonly />
                  </div>
                  <div class="expand-field">
                    <label class="expand-field__label">Bảo hiểm</label>
                    <CustomInput
                      :model-value="nv(row).tham_gia_bao_hiem ? 'Có tham gia' : 'Không'"
                      readonly
                    />
                  </div>
                </div>
              </section>

              <section class="expand-block expand-block--form expand-block--wide">
                <h4 class="expand-title">Tài khoản ngân hàng</h4>
                <div class="expand-fields">
                  <div class="expand-field">
                    <label class="expand-field__label">Ngân hàng</label>
                    <CustomInput :model-value="nv(row).ngan_hang || '—'" readonly />
                  </div>
                  <div class="expand-field">
                    <label class="expand-field__label">Số tài khoản</label>
                    <CustomInput :model-value="nv(row).so_tai_khoan || '—'" readonly />
                  </div>
                  <div class="expand-field">
                    <label class="expand-field__label">Chủ tài khoản</label>
                    <CustomInput :model-value="nv(row).chu_tai_khoan || '—'" readonly />
                  </div>
                </div>
              </section>

              <section
                v-for="group in salaryGroupsOf(nv(row))"
                :key="group.key"
                class="expand-block expand-block--form expand-block--wide"
              >
                <h4 class="expand-title">{{ group.title }}</h4>
                <div v-if="group.items.length" class="expand-fields">
                  <template v-if="group.key === 'luong'">
                    <div class="expand-field">
                      <label class="expand-field__label">Công chuẩn</label>
                      <CustomInput :model-value="formatNumber(nv(row).cong_chuan)" readonly />
                    </div>
                    <div class="expand-field">
                      <label class="expand-field__label">Người phụ thuộc</label>
                      <CustomInput :model-value="String(nv(row).so_nguoi_phu_thuoc ?? 0)" readonly />
                    </div>
                  </template>
                  <div
                    v-for="item in group.items"
                    :key="item.key"
                    class="expand-field"
                  >
                    <label class="expand-field__label">{{ item.name }}</label>
                    <CustomInput :model-value="formatMoney(item.value)" readonly />
                    <span v-if="item.note" class="expand-field__note">{{ item.note }}</span>
                  </div>
                </div>
                <EmployeeLuongDiemTheoLoai
                  v-if="group.table"
                  class="expand-loai-table"
                  :luong="nv(row).luong_thuong_phu_cap || {}"
                  :loai-list="loaiQuayChupOptions"
                  readonly
                />
              </section>
            </div>
          </template>
        </CustomTableColumn>

        <CustomTableColumn label="STT" width="60" align="center">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nhan_vien')"
          label="Nhân viên"
          min-width="220"
        >
          <template #default="{ row }">
            <div class="cell-person">
              <el-avatar :size="40" :src="mediaUrl(nv(row).hinh_anh) || undefined" class="cell-avatar">
                {{ avatarInitial(row.name) }}
              </el-avatar>
              <div class="cell-person-meta">
                <div class="cell-primary">{{ row.name }}</div>
                <div class="cell-secondary">
                  <span>#{{ row.id }}</span>
                  <span v-if="nv(row).gioi_tinh">· {{ genderLabel(nv(row).gioi_tinh) }}</span>
                  <span v-if="nv(row).cccd">· {{ nv(row).cccd }}</span>
                </div>
              </div>
            </div>
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('lien_he')"
          label="Liên hệ"
          min-width="180"
        >
          <template #default="{ row }">
            <div class="cell-stack">
              <span class="cell-primary">{{ row.email || '—' }}</span>
              <span class="cell-secondary">{{ row.phone || '—' }}</span>
            </div>
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('cong_viec')"
          label="Công việc"
          min-width="200"
        >
          <template #default="{ row }">
            <div class="cell-stack">
              <span class="cell-primary">{{ deptName(row) }}</span>
              <span class="cell-secondary">{{ nv(row).vi_tri_lam_viec || 'Chưa có vị trí' }}</span>
              <div class="cell-tags">
                <CustomTag v-if="nv(row).loai_nhan_vien" size="small" effect="plain">
                  {{ employeeTypeLabel(nv(row).loai_nhan_vien) }}
                </CustomTag>
                <CustomTag v-if="nv(row).loai_hop_dong" size="small" type="info" effect="plain">
                  {{ contractTypeLabel(nv(row).loai_hop_dong) }}
                </CustomTag>
              </div>
            </div>
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ngay_vao')"
          label="Ngày vào"
          width="120"
        >
          <template #default="{ row }">
            <div class="cell-stack">
              <span class="cell-primary">{{ formatDate(nv(row).ngay_vao_cong_ty) }}</span>
              <span class="cell-secondary">
                HĐ: {{ formatDate(nv(row).ngay_ky_hop_dong) }}
              </span>
            </div>
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('luong')"
          label="Lương"
          min-width="140"
          align="right"
        >
          <template #default="{ row }">
            <div class="cell-stack cell-stack--right">
              <span class="cell-primary cell-money">{{ formatMoney(salaryValueOf(nv(row), 'luong_1_gio')) }}</span>
              <span class="cell-secondary">Cứng: {{ formatMoney(salaryValueOf(nv(row), 'luong_cung')) }}</span>
            </div>
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('bhxh')"
          label="BHXH"
          width="100"
          align="center"
        >
          <template #default="{ row }">
            <CustomTag :type="nv(row).tham_gia_bao_hiem ? 'success' : 'info'" size="small">
              {{ nv(row).tham_gia_bao_hiem ? 'Có' : 'Không' }}
            </CustomTag>
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('tai_khoan')"
          label="Tài khoản"
          width="130"
        >
          <template #default="{ row }">
            <div class="cell-stack">
              <CustomTag size="small" effect="plain">{{ roleLabel(row.role) }}</CustomTag>
              <CustomTag :type="statusType(row.status)" size="small">
                {{ statusLabel(row.status) }}
              </CustomTag>
            </div>
          </template>
        </CustomTableColumn>

        <CustomTableColumn label="Thao tác" width="130" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip content="Sửa" placement="top">
                <CustomButton type="primary" link :icon="Edit" @click="openEdit(row)" />
              </CustomTooltip>
              <CustomTooltip content="Đổi mật khẩu" placement="top">
                <CustomButton type="warning" link :icon="Key" @click="openChangePassword(row)" />
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
        @change="loadEmployees"
      />
    </CustomCard>

    <EmployeeFormModal
      v-model="dialogVisible"
      :employee="editingEmployee"
      :departments="departments"
      :vai-tro-options="vaiTroOptions"
      :loai-quay-chup="loaiQuayChupOptions"
      @saved="loadEmployees"
    />

    <CustomDialog
      v-model="passwordDialogVisible"
      title="Đổi mật khẩu"
      :width="820"
      @closed="resetPasswordForm"
    >
      <CustomForm
        ref="passwordFormRef"
        :model="passwordForm"
        :rules="passwordRules"
        label-position="top"
      >
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Họ tên">
              <CustomInput :model-value="passwordUser?.name || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Email">
              <CustomInput :model-value="passwordUser?.email || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Số điện thoại">
              <CustomInput :model-value="passwordUser?.phone || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Phòng ban">
              <CustomInput :model-value="passwordUser ? deptName(passwordUser) : '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="12" :lg="12">
            <CustomFormItem label="Mật khẩu mới" prop="password">
              <CustomInput
                v-model="passwordForm.password"
                type="password"
                show-password
                placeholder="Nhập mật khẩu mới"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="12" :lg="12">
            <CustomFormItem label="Nhập lại mật khẩu mới" prop="password_confirmation">
              <CustomInput
                v-model="passwordForm.password_confirmation"
                type="password"
                show-password
                placeholder="Nhập lại mật khẩu mới"
              />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>

      <template #footer>
        <CustomButton @click="passwordDialogVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="passwordSaving" @click="savePassword">
          Đổi mật khẩu
        </CustomButton>
      </template>
    </CustomDialog>

  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Key, Plus, Search } from '@element-plus/icons-vue'
import { deleteUser, fetchUsers, updateUser } from '@/api/users'
import { fetchDanhMucLoaiQuayChup } from '@/api/danhMucLoaiQuayChup'
import { fetchPhongBan } from '@/api/phongBan'
import { fetchVaiTro } from '@/api/vaiTro'
import { mediaUrl } from '@/utils/media'
import { formatInteger } from '@/utils/number'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
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
  CustomTag,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'
import EmployeeFormModal from './EmployeeFormModal.vue'
import EmployeeLuongDiemTheoLoai from './EmployeeLuongDiemTheoLoai.vue'
import {
  buildSalaryGroups,
  formatMoney,
  salaryValueOf,
} from './employeeSalaryFields'

const tableColumns = [
  { key: 'nhan_vien', label: 'Nhân viên' },
  { key: 'lien_he', label: 'Liên hệ' },
  { key: 'cong_viec', label: 'Công việc' },
  { key: 'ngay_vao', label: 'Ngày vào' },
  { key: 'luong', label: 'Lương' },
  { key: 'bhxh', label: 'BHXH' },
  { key: 'tai_khoan', label: 'Tài khoản' },
]
const columnSettings = useTableColumns('nhan-su.employee-list', tableColumns, {
  pin: { selection: false },
})

const route = useRoute()

const employees = ref([])
const departments = ref([])
const vaiTroOptions = ref([])
const loaiQuayChupOptions = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)

const keyword = ref(String(route.query.keyword || ''))
const statusFilter = ref('')
const dialogVisible = ref(false)
const editingEmployee = ref(null)

const passwordDialogVisible = ref(false)
const passwordSaving = ref(false)
const passwordUser = ref(null)
const passwordFormRef = ref(null)
const passwordForm = reactive({
  password: '',
  password_confirmation: '',
})

const passwordRules = {
  password: [
    { required: true, message: 'Vui lòng nhập mật khẩu mới', trigger: 'blur' },
    { min: 8, message: 'Mật khẩu tối thiểu 8 ký tự', trigger: 'blur' },
  ],
  password_confirmation: [
    { required: true, message: 'Vui lòng nhập lại mật khẩu mới', trigger: 'blur' },
    {
      validator: (_rule, value, callback) => {
        if (value !== passwordForm.password) {
          callback(new Error('Mật khẩu nhập lại không khớp'))
          return
        }
        callback()
      },
      trigger: 'blur',
    },
  ],
}

async function loadEmployees() {
  loading.value = true
  try {
    const { data } = await fetchUsers({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      status: statusFilter.value || undefined,
    })
    employees.value = data.data || []
    total.value = data.total || 0
    page.value = data.current_page || page.value
  } catch {
    employees.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

async function loadDepartments() {
  try {
    const { data } = await fetchPhongBan({ per_page: 100 })
    departments.value = data.data || []
  } catch {
    departments.value = []
  }
}

async function loadVaiTroOptions() {
  try {
    const { data } = await fetchVaiTro({ per_page: 100 })
    vaiTroOptions.value = data.data || []
  } catch {
    vaiTroOptions.value = []
  }
}

async function loadLoaiQuayChup() {
  try {
    const { data } = await fetchDanhMucLoaiQuayChup({ per_page: 100, trang_thai: 'active' })
    loaiQuayChupOptions.value = (data.data || []).slice().sort((a, b) =>
      String(a.ten_dich_vu || '').localeCompare(String(b.ten_dich_vu || ''), 'vi'),
    )
  } catch {
    loaiQuayChupOptions.value = []
  }
}

function onSearch() {
  page.value = 1
  loadEmployees()
}

function nv(row) {
  return row?.nhan_vien || {}
}

function salaryGroupsOf(nvData) {
  return buildSalaryGroups(nvData?.luong_thuong_phu_cap || {})
}

function deptName(row) {
  const list = nv(row).phong_bans
  if (Array.isArray(list) && list.length) {
    return list.map((pb) => pb.ten_phong_ban).filter(Boolean).join(', ')
  }
  return 'Chưa có phòng ban'
}

function avatarInitial(name) {
  const text = String(name || '').trim()
  return text ? text.charAt(0).toUpperCase() : '?'
}

function roleLabel(role) {
  return { user: 'User', admin: 'Admin' }[role] || role
}

function statusLabel(status) {
  return { active: 'Đang hoạt động', inactive: 'Không hoạt động' }[status] || status
}

function statusType(status) {
  return { active: 'success', inactive: 'info' }[status] || 'info'
}

function genderLabel(value) {
  return { nam: 'Nam', nu: 'Nữ', khac: 'Khác' }[value] || '—'
}

function employeeTypeLabel(value) {
  return { full_time: 'Full time', part_time: 'Part time' }[value] || value || '—'
}

function contractTypeLabel(value) {
  return {
    chinh_thuc: 'Chính thức',
    hoc_viec: 'Học việc',
    thu_viec: 'Thử việc',
  }[value] || value || '—'
}

function formatDate(value) {
  if (!value) return '—'
  const raw = String(value).slice(0, 10)
  const [y, m, d] = raw.split('-')
  if (!y || !m || !d) return raw
  return `${d}/${m}/${y}`
}

function formatNumber(value) {
  if (value == null || value === '') return '—'
  const formatted = formatInteger(value)
  return formatted || '—'
}

function openCreate() {
  editingEmployee.value = null
  dialogVisible.value = true
}

function openEdit(row) {
  editingEmployee.value = row
  dialogVisible.value = true
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa nhân sự "${row.name}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteUser(row.id)
    ElMessage.success('Đã xóa nhân sự.')
    await loadEmployees()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

function resetPasswordForm() {
  passwordUser.value = null
  passwordForm.password = ''
  passwordForm.password_confirmation = ''
  passwordFormRef.value?.clearValidate?.()
}

function openChangePassword(row) {
  resetPasswordForm()
  passwordUser.value = row
  passwordDialogVisible.value = true
}

async function savePassword() {
  const valid = await passwordFormRef.value?.validate().catch(() => false)
  if (!valid || !passwordUser.value) return

  passwordSaving.value = true
  try {
    const user = passwordUser.value
    await updateUser(user.id, {
      name: user.name,
      email: user.email,
      phone: user.phone || '',
      role: user.role || 'user',
      status: user.status || 'active',
      password: passwordForm.password,
    })
    ElMessage.success('Đã đổi mật khẩu.')
    passwordDialogVisible.value = false
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    passwordSaving.value = false
  }
}

onMounted(() => {
  loadEmployees()
  loadDepartments()
  loadVaiTroOptions()
  loadLoaiQuayChup()
})
</script>

<style scoped>
.cell-person {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.cell-avatar {
  flex-shrink: 0;
  background: var(--el-color-primary-light-7);
  color: var(--el-color-primary);
  font-weight: 600;
}

.cell-person-meta,
.cell-stack {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.cell-stack--right {
  align-items: flex-end;
}

.cell-primary {
  font-size: 13px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  line-height: 1.35;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.cell-secondary {
  font-size: 12px;
  color: var(--el-text-color-regular);
  line-height: 1.35;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.cell-money {
  font-variant-numeric: tabular-nums;
  font-feature-settings: 'tnum';
}

.cell-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 2px;
}

.expand-panel {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 0 4px 2px;
}

.expand-block--wide {
  width: 100%;
}

.expand-title {
  margin: 0 0 4px;
  font-size: 12px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  line-height: 1.3;
}

.expand-block--form {
  padding: 4px 8px 4px 16px;
}

.expand-block--form .expand-title {
  margin-bottom: 8px;
}

.expand-fields {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px 12px;
}

.expand-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.expand-field__label {
  font-size: 12px;
  font-weight: 500;
  color: var(--el-text-color-regular);
  line-height: 1.3;
}

.expand-field :deep(.el-input__wrapper) {
  background-color: var(--el-fill-color-light);
  box-shadow: 0 0 0 1px var(--el-border-color-lighter) inset;
}

.expand-field :deep(.el-input__inner) {
  font-size: 12px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  cursor: default;
}

.expand-field__note {
  font-size: 11px;
  color: var(--el-text-color-secondary);
  line-height: 1.3;
  margin-top: 2px;
}

.expand-loai-table {
  margin-top: 8px;
}

@media (max-width: 1400px) {
  .expand-fields {
    grid-template-columns: repeat(5, minmax(0, 1fr));
  }
}

@media (max-width: 1200px) {
  .expand-fields {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 992px) {
  .expand-fields {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .expand-fields {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 480px) {
  .expand-fields {
    grid-template-columns: 1fr;
  }
}
</style>
