<template>
  <div class="employee-list">
    <CustomCard shadow="hover">
      <template #header>
        <div class="card-header">
          <span>Danh sách nhân sự</span>
          <CustomButton type="primary" @click="openCreate">
            <CustomIcon><Plus /></CustomIcon>
            Thêm nhân sự
          </CustomButton>
        </div>
      </template>

      <div class="toolbar">
        <CustomInput
          v-model="keyword"
          placeholder="Tìm theo tên, email, SĐT..."
          clearable
          style="max-width: 280px"
          @clear="onSearch"
          @keyup.enter="onSearch"
        >
          <template #prefix>
            <CustomIcon><Search /></CustomIcon>
          </template>
        </CustomInput>
        <CustomSelect
          v-model="statusFilter"
          placeholder="Trạng thái"
          clearable
          style="width: 180px"
          @change="onSearch"
        >
          <CustomOption label="Đang hoạt động" value="active" />
          <CustomOption label="Không hoạt động" value="inactive" />
        </CustomSelect>
        <CustomButton type="primary" plain @click="onSearch">
          <CustomIcon><Search /></CustomIcon>
          Tìm kiếm
        </CustomButton>
      </div>

      <CustomTable v-loading="loading" :data="employees" stripe style="width: 100%">
        <CustomTableColumn prop="id" label="Mã" width="80" />
        <CustomTableColumn prop="name" label="Họ tên" min-width="160" />
        <CustomTableColumn prop="email" label="Email" min-width="200" />
        <CustomTableColumn prop="phone" label="SĐT" width="140" />
        <CustomTableColumn prop="role" label="Vai trò" width="120">
          <template #default="{ row }">
            <CustomTag size="small" effect="plain">{{ roleLabel(row.role) }}</CustomTag>
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="status" label="Trạng thái" width="140">
          <template #default="{ row }">
            <CustomTag :type="statusType(row.status)" size="small">
              {{ statusLabel(row.status) }}
            </CustomTag>
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
        @change="loadEmployees"
      />
    </CustomCard>

    <CustomDialog
      v-model="dialogVisible"
      :title="editingId ? 'Sửa nhân sự' : 'Thêm nhân sự'"
      :width="1300"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <el-tabs v-model="activeTab">
          <el-tab-pane label="Thông tin cá nhân" name="personal">
            <div class="personal-layout">
              <aside class="personal-avatar">
                <CustomFormItem label="Hình ảnh" prop="hinh_anh">
                  <div class="avatar-slot">
                    <el-upload
                      class="avatar-uploader"
                      :show-file-list="false"
                      :auto-upload="false"
                      accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                      :on-change="onImageChange"
                    >
                      <img
                        v-if="avatarPreviewUrl"
                        :src="avatarPreviewUrl"
                        class="avatar-image"
                        alt="Ảnh nhân viên"
                      />
                      <div v-else class="avatar-placeholder">
                        <el-icon><Plus /></el-icon>
                        <span>Chọn ảnh</span>
                      </div>
                    </el-upload>
                    <button
                      v-if="avatarPreviewUrl"
                      type="button"
                      class="avatar-remove"
                      title="Xóa ảnh"
                      @click.stop="onImageRemove"
                    >
                      <el-icon><Delete /></el-icon>
                    </button>
                  </div>
                  <div class="upload-hint">
                    {{ avatarPreviewUrl ? '' : '' }}
                  </div>
                </CustomFormItem>
              </aside>

              <div class="personal-fields">
                <CustomRow :gutter="16">
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Họ tên" prop="name">
                      <CustomInput v-model="form.name" placeholder="Họ và tên" />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Email" prop="email">
                      <CustomInput v-model="form.email" placeholder="email@example.com" />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="SĐT" prop="phone">
                      <CustomInput v-model="form.phone" placeholder="Số điện thoại" />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Mật khẩu" prop="password">
                      <CustomInput
                        v-model="form.password"
                        type="password"
                        show-password
                        :placeholder="editingId ? 'Để trống nếu không đổi' : 'Mật khẩu đăng nhập'"
                      />
                    </CustomFormItem>
                  </CustomCol>

                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Vai trò" prop="role">
                      <CustomSelect v-model="form.role" style="width: 100%">
                        <CustomOption label="User" value="user" />
                        <CustomOption label="Admin" value="admin" />
                      </CustomSelect>
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Trạng thái" prop="status">
                      <CustomSelect v-model="form.status" style="width: 100%">
                        <CustomOption label="Đang hoạt động" value="active" />
                        <CustomOption label="Không hoạt động" value="inactive" />
                      </CustomSelect>
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Phòng ban" prop="phong_ban_id">
                      <CustomSelect
                        v-model="form.phong_ban_id"
                        clearable
                        filterable
                        placeholder="Chọn phòng ban"
                        style="width: 100%"
                      >
                        <CustomOption
                          v-for="pb in departments"
                          :key="pb.id"
                          :label="pb.ten_phong_ban"
                          :value="pb.id"
                        />
                      </CustomSelect>
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Giới tính" prop="gioi_tinh">
                      <CustomSelect
                        v-model="form.gioi_tinh"
                        clearable
                        placeholder="Chọn"
                        style="width: 100%"
                      >
                        <CustomOption label="Nam" value="nam" />
                        <CustomOption label="Nữ" value="nu" />
                        <CustomOption label="Khác" value="khac" />
                      </CustomSelect>
                    </CustomFormItem>
                  </CustomCol>

                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Ngày sinh" prop="ngay_sinh">
                      <el-date-picker
                        v-model="form.ngay_sinh"
                        type="date"
                        value-format="YYYY-MM-DD"
                        placeholder="Chọn ngày"
                        style="width: 100%"
                      />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="CCCD" prop="cccd">
                      <CustomInput v-model="form.cccd" placeholder="Số CCCD" />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Vị trí làm việc" prop="vi_tri_lam_viec">
                      <CustomInput
                        v-model="form.vi_tri_lam_viec"
                        placeholder="VD: Nhân viên kinh doanh"
                      />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Ngày vào công ty" prop="ngay_vao_cong_ty">
                      <el-date-picker
                        v-model="form.ngay_vao_cong_ty"
                        type="date"
                        value-format="YYYY-MM-DD"
                        placeholder="Chọn ngày"
                        style="width: 100%"
                      />
                    </CustomFormItem>
                  </CustomCol>

                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Ngày ký hợp đồng" prop="ngay_ky_hop_dong">
                      <el-date-picker
                        v-model="form.ngay_ky_hop_dong"
                        type="date"
                        value-format="YYYY-MM-DD"
                        placeholder="Chọn ngày"
                        style="width: 100%"
                      />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Loại nhân viên" prop="loai_nhan_vien">
                      <CustomSelect
                        v-model="form.loai_nhan_vien"
                        clearable
                        placeholder="Chọn"
                        style="width: 100%"
                      >
                        <CustomOption label="Full time" value="full_time" />
                        <CustomOption label="Part time" value="part_time" />
                      </CustomSelect>
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Loại hợp đồng" prop="loai_hop_dong">
                      <CustomSelect
                        v-model="form.loai_hop_dong"
                        clearable
                        placeholder="Chọn"
                        style="width: 100%"
                      >
                        <CustomOption label="Chính thức" value="chinh_thuc" />
                        <CustomOption label="Học việc" value="hoc_viec" />
                        <CustomOption label="Thử việc" value="thu_viec" />
                      </CustomSelect>
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Ngân hàng" prop="ngan_hang">
                      <CustomInput v-model="form.ngan_hang" placeholder="Tên ngân hàng" />
                    </CustomFormItem>
                  </CustomCol>

                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Chi nhánh" prop="chi_nhanh">
                      <CustomInput v-model="form.chi_nhanh" placeholder="Chi nhánh" />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Số tài khoản" prop="so_tai_khoan">
                      <CustomInput v-model="form.so_tai_khoan" placeholder="Số tài khoản" />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Chủ tài khoản" prop="chu_tai_khoan">
                      <CustomInput v-model="form.chu_tai_khoan" placeholder="Tên chủ tài khoản" />
                    </CustomFormItem>
                  </CustomCol>
                </CustomRow>
              </div>
            </div>
          </el-tab-pane>

          <el-tab-pane label="Thông tin lương" name="salary">
            <CustomRow :gutter="16" class="salary-fields">
              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Công chuẩn" prop="cong_chuan">
                  <el-input-number
                    v-model="form.cong_chuan"
                    :min="0"
                    :precision="2"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Số người phụ thuộc" prop="so_nguoi_phu_thuoc">
                  <el-input-number
                    v-model="form.so_nguoi_phu_thuoc"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>

              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Lương cứng" prop="luong_cung">
                  <el-input-number
                    v-model="form.luong_cung"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Lương mềm" prop="luong_mem">
                  <el-input-number
                    v-model="form.luong_mem"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Phụ cấp" prop="phu_cap">
                  <el-input-number
                    v-model="form.phu_cap"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>

              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Lương cơ bản" prop="luong_co_ban">
                  <el-input-number
                    v-model="form.luong_co_ban"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Lương tăng ca" prop="luong_tang_ca">
                  <el-input-number
                    v-model="form.luong_tang_ca"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Phụ cấp xăng" prop="phu_cap_xang">
                  <el-input-number
                    v-model="form.phu_cap_xang"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>

              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Phụ cấp ăn trưa" prop="phu_cap_an_trua">
                  <el-input-number
                    v-model="form.phu_cap_an_trua"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Phụ cấp điện thoại" prop="phu_cap_dien_thoai">
                  <el-input-number
                    v-model="form.phu_cap_dien_thoai"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Phụ cấp nhà ở" prop="phu_cap_nha_o">
                  <el-input-number
                    v-model="form.phu_cap_nha_o"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>

              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Thưởng chuyên cần" prop="thuong_chuyen_can">
                  <el-input-number
                    v-model="form.thuong_chuyen_can"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Hoa hồng HĐ cuối" prop="hoa_hong_hop_dong_cuoi">
                  <el-input-number
                    v-model="form.hoa_hong_hop_dong_cuoi"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Hoa hồng HĐ trang phục" prop="hoa_hong_hop_dong_trang_phuc">
                  <el-input-number
                    v-model="form.hoa_hong_hop_dong_trang_phuc"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    align="left"
                    placeholder="0"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                <CustomFormItem label="Tham gia bảo hiểm" prop="tham_gia_bao_hiem">
                  <el-switch v-model="form.tham_gia_bao_hiem" />
                </CustomFormItem>
              </CustomCol>
            </CustomRow>
          </el-tab-pane>
        </el-tabs>
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
import { createUser, deleteUser, fetchUsers, updateUser, uploadNhanVienHinh } from '@/api/users'
import { fetchPhongBan } from '@/api/phongBan'
import { mediaUrl } from '@/utils/media'
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

const employees = ref([])
const departments = ref([])
const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)

const keyword = ref('')
const statusFilter = ref('')
const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)
const activeTab = ref('personal')
const pendingImageFile = ref(null)
const pendingPreviewUrl = ref('')

const avatarPreviewUrl = computed(() => {
  if (pendingPreviewUrl.value) return pendingPreviewUrl.value
  return mediaUrl(form.hinh_anh)
})

const emptyForm = () => ({
  // users
  name: '',
  email: '',
  phone: '',
  password: '',
  role: 'user',
  status: 'active',
  // nhan_vien — thông tin cá nhân
  hinh_anh: '',
  phong_ban_id: null,
  ngan_hang: '',
  chi_nhanh: '',
  so_tai_khoan: '',
  chu_tai_khoan: '',
  gioi_tinh: null,
  ngay_sinh: null,
  cccd: '',
  vi_tri_lam_viec: '',
  ngay_vao_cong_ty: null,
  ngay_ky_hop_dong: null,
  loai_nhan_vien: null,
  loai_hop_dong: null,
  // nhan_vien — thông tin lương
  cong_chuan: null,
  tham_gia_bao_hiem: false,
  so_nguoi_phu_thuoc: 0,
  luong_cung: null,
  luong_mem: null,
  phu_cap: null,
  luong_co_ban: null,
  luong_tang_ca: null,
  phu_cap_xang: null,
  phu_cap_an_trua: null,
  phu_cap_dien_thoai: null,
  phu_cap_nha_o: null,
  thuong_chuyen_can: null,
  hoa_hong_hop_dong_cuoi: null,
  hoa_hong_hop_dong_trang_phuc: null,
})

const form = reactive(emptyForm())

const rules = {
  name: [{ required: true, message: 'Vui lòng nhập họ tên', trigger: 'blur' }],
  email: [
    { required: true, message: 'Vui lòng nhập email', trigger: 'blur' },
    { type: 'email', message: 'Email không hợp lệ', trigger: 'blur' },
  ],
  phone: [{ required: true, message: 'Vui lòng nhập SĐT', trigger: 'blur' }],
  password: [
    {
      validator: (_rule, value, callback) => {
        if (!editingId.value && !value) {
          callback(new Error('Vui lòng nhập mật khẩu'))
          return
        }
        if (value && value.length < 8) {
          callback(new Error('Mật khẩu tối thiểu 8 ký tự'))
          return
        }
        callback()
      },
      trigger: 'blur',
    },
  ],
  role: [{ required: true, message: 'Vui lòng chọn vai trò', trigger: 'change' }],
  status: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
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

function onSearch() {
  page.value = 1
  loadEmployees()
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

function clearPendingPreview() {
  if (pendingPreviewUrl.value) {
    URL.revokeObjectURL(pendingPreviewUrl.value)
    pendingPreviewUrl.value = ''
  }
  pendingImageFile.value = null
}

function resetImageState() {
  clearPendingPreview()
}

function onImageChange(uploadFile) {
  const file = uploadFile.raw
  if (!file) return

  const okTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif']
  if (!okTypes.includes(file.type)) {
    ElMessage.error('Chỉ chấp nhận ảnh JPEG, PNG, WEBP, GIF.')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    ElMessage.error('Hình ảnh tối đa 2MB.')
    return
  }

  clearPendingPreview()
  pendingPreviewUrl.value = URL.createObjectURL(file)
  pendingImageFile.value = file
}

function onImageRemove() {
  clearPendingPreview()
  form.hinh_anh = ''
}

function openCreate() {
  editingId.value = null
  activeTab.value = 'personal'
  Object.assign(form, emptyForm())
  resetImageState()
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  activeTab.value = 'personal'
  const nv = row.nhan_vien || {}
  Object.assign(form, emptyForm(), {
    name: row.name,
    email: row.email,
    phone: row.phone || '',
    password: '',
    role: row.role || 'user',
    status: row.status || 'active',
    hinh_anh: nv.hinh_anh || '',
    phong_ban_id: nv.phong_ban_id ?? null,
    ngan_hang: nv.ngan_hang || '',
    chi_nhanh: nv.chi_nhanh || '',
    so_tai_khoan: nv.so_tai_khoan || '',
    chu_tai_khoan: nv.chu_tai_khoan || '',
    gioi_tinh: nv.gioi_tinh || null,
    ngay_sinh: toDateOnly(nv.ngay_sinh),
    cccd: nv.cccd || '',
    vi_tri_lam_viec: nv.vi_tri_lam_viec || '',
    ngay_vao_cong_ty: toDateOnly(nv.ngay_vao_cong_ty),
    ngay_ky_hop_dong: toDateOnly(nv.ngay_ky_hop_dong),
    loai_nhan_vien: nv.loai_nhan_vien || null,
    loai_hop_dong: nv.loai_hop_dong || null,
    cong_chuan: nv.cong_chuan != null ? Number(nv.cong_chuan) : null,
    tham_gia_bao_hiem: !!nv.tham_gia_bao_hiem,
    so_nguoi_phu_thuoc: nv.so_nguoi_phu_thuoc ?? 0,
    luong_cung: nv.luong_cung != null ? Number(nv.luong_cung) : null,
    luong_mem: nv.luong_mem != null ? Number(nv.luong_mem) : null,
    phu_cap: nv.phu_cap != null ? Number(nv.phu_cap) : null,
    luong_co_ban: nv.luong_co_ban != null ? Number(nv.luong_co_ban) : null,
    luong_tang_ca: nv.luong_tang_ca != null ? Number(nv.luong_tang_ca) : null,
    phu_cap_xang: nv.phu_cap_xang != null ? Number(nv.phu_cap_xang) : null,
    phu_cap_an_trua: nv.phu_cap_an_trua != null ? Number(nv.phu_cap_an_trua) : null,
    phu_cap_dien_thoai: nv.phu_cap_dien_thoai != null ? Number(nv.phu_cap_dien_thoai) : null,
    phu_cap_nha_o: nv.phu_cap_nha_o != null ? Number(nv.phu_cap_nha_o) : null,
    thuong_chuyen_can: nv.thuong_chuyen_can != null ? Number(nv.thuong_chuyen_can) : null,
    hoa_hong_hop_dong_cuoi:
      nv.hoa_hong_hop_dong_cuoi != null ? Number(nv.hoa_hong_hop_dong_cuoi) : null,
    hoa_hong_hop_dong_trang_phuc:
      nv.hoa_hong_hop_dong_trang_phuc != null ? Number(nv.hoa_hong_hop_dong_trang_phuc) : null,
  })
  resetImageState()
  dialogVisible.value = true
}

function toDateOnly(value) {
  if (!value) return null
  return String(value).slice(0, 10)
}

function buildPayload() {
  const payload = {
    name: form.name.trim(),
    email: form.email.trim(),
    phone: form.phone.trim(),
    role: form.role,
    status: form.status,
    hinh_anh: form.hinh_anh?.trim() || null,
    phong_ban_id: form.phong_ban_id || null,
    ngan_hang: form.ngan_hang?.trim() || null,
    chi_nhanh: form.chi_nhanh?.trim() || null,
    so_tai_khoan: form.so_tai_khoan?.trim() || null,
    chu_tai_khoan: form.chu_tai_khoan?.trim() || null,
    gioi_tinh: form.gioi_tinh || null,
    ngay_sinh: form.ngay_sinh || null,
    cccd: form.cccd?.trim() || null,
    vi_tri_lam_viec: form.vi_tri_lam_viec?.trim() || null,
    ngay_vao_cong_ty: form.ngay_vao_cong_ty || null,
    ngay_ky_hop_dong: form.ngay_ky_hop_dong || null,
    loai_nhan_vien: form.loai_nhan_vien || null,
    loai_hop_dong: form.loai_hop_dong || null,
    cong_chuan: form.cong_chuan,
    tham_gia_bao_hiem: !!form.tham_gia_bao_hiem,
    so_nguoi_phu_thuoc: form.so_nguoi_phu_thuoc ?? 0,
    luong_cung: form.luong_cung,
    luong_mem: form.luong_mem,
    phu_cap: form.phu_cap,
    luong_co_ban: form.luong_co_ban,
    luong_tang_ca: form.luong_tang_ca,
    phu_cap_xang: form.phu_cap_xang,
    phu_cap_an_trua: form.phu_cap_an_trua,
    phu_cap_dien_thoai: form.phu_cap_dien_thoai,
    phu_cap_nha_o: form.phu_cap_nha_o,
    thuong_chuyen_can: form.thuong_chuyen_can,
    hoa_hong_hop_dong_cuoi: form.hoa_hong_hop_dong_cuoi,
    hoa_hong_hop_dong_trang_phuc: form.hoa_hong_hop_dong_trang_phuc,
  }

  if (form.password) {
    payload.password = form.password
  }

  return payload
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) {
    activeTab.value = 'personal'
    return
  }

  saving.value = true
  try {
    // Có ảnh mới chọn → upload lên server rồi mới lưu path
    if (pendingImageFile.value) {
      const { data } = await uploadNhanVienHinh(pendingImageFile.value)
      form.hinh_anh = data.path
      clearPendingPreview()
    }

    const payload = buildPayload()
    if (editingId.value) {
      await updateUser(editingId.value, payload)
      ElMessage.success('Đã cập nhật nhân sự.')
    } else {
      await createUser(payload)
      ElMessage.success('Đã thêm nhân sự.')
    }
    dialogVisible.value = false
    await loadEmployees()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
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

onMounted(() => {
  loadEmployees()
  loadDepartments()
})
</script>

<style scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
}

.action-btns {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.salary-fields :deep(.el-input-number .el-input__inner) {
  text-align: left;
}

.personal-layout {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

.personal-avatar {
  flex: 0 0 168px;
  width: 168px;
}

.personal-fields {
  flex: 1;
  min-width: 0;
}

.avatar-slot {
  position: relative;
  width: 148px;
  height: 148px;
}

.avatar-uploader :deep(.el-upload) {
  width: 148px;
  height: 148px;
  border: 1px dashed var(--el-border-color);
  border-radius: 8px;
  cursor: pointer;
  overflow: hidden;
  background: var(--el-fill-color-lighter);
  transition: border-color 0.2s;
}

.avatar-uploader :deep(.el-upload:hover) {
  border-color: var(--el-color-primary);
}

.avatar-image {
  display: block;
  width: 148px;
  height: 148px;
  object-fit: cover;
}

.avatar-placeholder {
  width: 148px;
  height: 148px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.avatar-placeholder .el-icon {
  font-size: 28px;
}

.avatar-remove {
  position: absolute;
  top: 6px;
  right: 6px;
  z-index: 2;
  width: 26px;
  height: 26px;
  border: none;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #fff;
  background: rgba(0, 0, 0, 0.55);
  padding: 0;
}

.avatar-remove:hover {
  background: var(--el-color-danger);
}

.upload-hint {
  margin-top: 8px;
  width: 148px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  line-height: 1.4;
}

@media (max-width: 767px) {
  .personal-layout {
    flex-direction: column;
  }

  .personal-avatar {
    flex-basis: auto;
    width: 100%;
  }
}
</style>
