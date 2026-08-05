<template>
  <div class="employee-list">
    <CustomCard shadow="hover" class="filter-card">
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
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách nhân sự</span>
          <div class="card-header-actions">
            <TableColumnConfig :settings="columnSettings" />
            <CustomTooltip content="Thêm mới" placement="top">
              <CustomButton type="primary" @click="openCreate">
                <CustomIcon><Plus /></CustomIcon>
                Thêm
              </CustomButton>
            </CustomTooltip>
          </div>
        </div>
      </template>

      <CustomTable v-loading="loading" :data="employees" stripe row-key="id" style="width: 100%">
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
                    <label class="expand-field__label">Chi nhánh</label>
                    <CustomInput :model-value="nv(row).chi_nhanh || '—'" readonly />
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

              <section class="expand-block expand-block--form expand-block--wide">
                <h4 class="expand-title">Lương & phụ cấp</h4>
                <div class="expand-fields">
                  <div class="expand-field">
                    <label class="expand-field__label">Công chuẩn</label>
                    <CustomInput :model-value="formatNumber(nv(row).cong_chuan)" readonly />
                  </div>
                  <div class="expand-field">
                    <label class="expand-field__label">Người phụ thuộc</label>
                    <CustomInput :model-value="String(nv(row).so_nguoi_phu_thuoc ?? 0)" readonly />
                  </div>
                  <div
                    v-for="item in salaryItemsOf(nv(row))"
                    :key="item.key"
                    class="expand-field"
                  >
                    <label class="expand-field__label">{{ item.name }}</label>
                    <CustomInput :model-value="formatMoney(item.value)" readonly />
                    <span v-if="item.note" class="expand-field__note">{{ item.note }}</span>
                  </div>
                </div>
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
                    <CustomFormItem label="Phòng ban" prop="phong_ban_ids">
                      <CustomSelect
                        v-model="form.phong_ban_ids"
                        multiple
                        clearable
                        filterable
                        collapse-tags
                        collapse-tags-tooltip
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
                        format="DD/MM/YYYY"
                        value-format="YYYY-MM-DD"
                        placeholder="dd/mm/yyyy"
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
                        format="DD/MM/YYYY"
                        value-format="YYYY-MM-DD"
                        placeholder="dd/mm/yyyy"
                        style="width: 100%"
                      />
                    </CustomFormItem>
                  </CustomCol>

                  <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                    <CustomFormItem label="Ngày ký hợp đồng" prop="ngay_ky_hop_dong">
                      <el-date-picker
                        v-model="form.ngay_ky_hop_dong"
                        type="date"
                        format="DD/MM/YYYY"
                        value-format="YYYY-MM-DD"
                        placeholder="dd/mm/yyyy"
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
              <CustomCol
                v-for="item in salaryFormItems"
                :key="item.key"
                :xs="12"
                :sm="8"
                :md="6"
                :lg="4"
              >
                <CustomFormItem
                  :label="item.name"
                  :prop="`luong_thuong_phu_cap.${item.key}.value`"
                >
                  <MoneyInput
                    v-model="form.luong_thuong_phu_cap[item.key].value"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>
            </CustomRow>
          </el-tab-pane>
        </el-tabs>
      </CustomForm>
      <template #footer>
        <CustomButton @click="dialogVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="saving" @click="save">Lưu</CustomButton>
        <CustomButton v-if="!editingId" plain @click="fillSampleData">Dữ liệu mẫu</CustomButton>
      </template>
    </CustomDialog>

  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import { createUser, deleteUser, fetchUsers, updateUser, uploadNhanVienHinh } from '@/api/users'
import { fetchPhongBan } from '@/api/phongBan'
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
  MoneyInput,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
  CustomTag,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'

/** Định nghĩa các khoản lương/thưởng/phụ cấp (khớp backend). */
const SALARY_FIELD_DEFINITIONS = [
  { key: 'luong_cung', name: 'Lương cứng' },
  { key: 'luong_mem', name: 'Lương mềm' },
  { key: 'phu_cap', name: 'Phụ cấp' },
  { key: 'luong_1_gio', name: 'Lương 1 giờ', note: 'Dành cho part time' },
  { key: 'luong_tang_ca_1_gio', name: 'Lương tăng ca 1 giờ', note: 'Dành cho cả part_time và full_time' },
  { key: 'phu_cap_xang', name: 'Phụ cấp xăng' },
  { key: 'phu_cap_an_trua', name: 'Phụ cấp ăn trưa' },
  { key: 'phu_cap_dien_thoai', name: 'Phụ cấp điện thoại' },
  { key: 'phu_cap_nha_o', name: 'Phụ cấp nhà ở' },
  { key: 'thuong_chuyen_can', name: 'Thưởng chuyên cần' },
  { key: 'hoa_hong_hop_dong_sddv', name: 'Hoa hồng HĐ sử dụng dịch vụ' },
  { key: 'hoa_hong_hop_dong_trang_phuc', name: 'Hoa hồng HĐ trang phục' },
  { key: 'chup_1_diem', name: 'Chụp 1 điểm' },
  { key: 'chup_2_diem', name: 'Chụp 2 điểm' },
  { key: 'chup_3_diem', name: 'Chụp 3 điểm' },
  { key: 'make_1_diem', name: 'Make 1 điểm' },
  { key: 'make_2_diem', name: 'Make 2 điểm' },
  { key: 'make_3_diem', name: 'Make 3 điểm' },
  { key: 'phi_xu_ly_hd_thue_trang_phuc', name: 'Phí xử lý HĐ thuê trang phục' },
]

function createDefaultLuongThuongPhuCap(overrides = {}) {
  const result = {}
  for (const def of SALARY_FIELD_DEFINITIONS) {
    const src = overrides?.[def.key] || {}
    result[def.key] = {
      name: src.name || def.name,
      value: src.value != null && src.value !== '' ? Number(src.value) : null,
      note: src.note != null && src.note !== '' ? String(src.note) : (def.note || ''),
    }
  }
  return result
}

const tableColumns = [
  { key: 'nhan_vien', label: 'Nhân viên' },
  { key: 'lien_he', label: 'Liên hệ' },
  { key: 'cong_viec', label: 'Công việc' },
  { key: 'ngay_vao', label: 'Ngày vào' },
  { key: 'luong', label: 'Lương' },
  { key: 'bhxh', label: 'BHXH' },
  { key: 'tai_khoan', label: 'Tài khoản' },
]
const columnSettings = useTableColumns('nhan-su.employee-list', tableColumns)

const route = useRoute()

const employees = ref([])
const departments = ref([])
const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)

const keyword = ref(String(route.query.keyword || ''))
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
  phong_ban_ids: [],
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
  luong_thuong_phu_cap: createDefaultLuongThuongPhuCap(),
})

const form = reactive(emptyForm())

const salaryFormItems = computed(() =>
  SALARY_FIELD_DEFINITIONS.map((def) => ({
    key: def.key,
    ...form.luong_thuong_phu_cap[def.key],
  })),
)

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

function nv(row) {
  return row?.nhan_vien || {}
}

function salaryValueOf(nvData, key) {
  const item = nvData?.luong_thuong_phu_cap?.[key]
  return item?.value ?? null
}

function salaryItemsOf(nvData) {
  const data = createDefaultLuongThuongPhuCap(nvData?.luong_thuong_phu_cap || {})
  return SALARY_FIELD_DEFINITIONS.map((def) => ({
    key: def.key,
    ...data[def.key],
  }))
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

function formatMoney(value) {
  if (value == null || value === '') return '—'
  const formatted = formatInteger(value)
  if (!formatted) return '—'
  return `${formatted} ₫`
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

function pick(list) {
  return list[Math.floor(Math.random() * list.length)]
}

function randomInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min
}

function randomDigits(length) {
  let out = ''
  for (let i = 0; i < length; i += 1) out += String(randomInt(0, 9))
  return out
}

function pad2(n) {
  return String(n).padStart(2, '0')
}

function randomDate(startYear, endYear) {
  const year = randomInt(startYear, endYear)
  const month = randomInt(1, 12)
  const day = randomInt(1, 28)
  return `${year}-${pad2(month)}-${pad2(day)}`
}

function toUnsign(text) {
  return String(text)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/đ/g, 'd')
    .replace(/Đ/g, 'D')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '')
}

function fillSampleData() {
  const ho = pick(['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng'])
  const dem = pick(['Văn', 'Thị', 'Minh', 'Hoàng', 'Quốc', 'Thanh', 'Hữu', 'Ngọc', 'Đức', 'Anh'])
  const ten = pick([
    'An', 'Bình', 'Cường', 'Dũng', 'Hà', 'Hùng', 'Lan', 'Linh', 'Long', 'Mai',
    'Nam', 'Nga', 'Phong', 'Quân', 'Son', 'Trang', 'Tuấn', 'Vy', 'Yến', 'Khoa',
  ])
  const fullName = `${ho} ${dem} ${ten}`
  const emailLocal = `${toUnsign(ho)}${toUnsign(ten)}${randomDigits(4)}`
  const gioiTinh = pick(['nam', 'nu', 'khac'])
  const viTri = pick([
    'Nhân viên kinh doanh',
    'Nhân viên kế toán',
    'Nhân viên marketing',
    'Nhân viên hành chính',
    'Nhân viên kỹ thuật',
    'Chuyên viên nhân sự',
    'Trợ lý giám đốc',
  ])
  const nganHang = pick(['Vietcombank', 'Techcombank', 'MB Bank', 'BIDV', 'VPBank', 'ACB', 'TPBank'])
  const chiNhanh = pick([
    'Chi nhánh Hà Nội',
    'Chi nhánh TP.HCM',
    'Chi nhánh Đà Nẵng',
    'Chi nhánh Cầu Giấy',
    'Chi nhánh Quận 1',
  ])
  const luongCung = randomInt(6, 15) * 1_000_000
  const luongMem = randomInt(1, 5) * 1_000_000
  const phuCap = randomInt(5, 20) * 100_000
  const ngayVao = randomDate(2022, 2025)

  Object.assign(form, emptyForm(), {
    name: fullName,
    email: `${emailLocal}@example.com`,
    phone: `09${randomDigits(8)}`,
    password: `Pass${randomDigits(6)}`,
    role: pick(['user', 'admin']),
    status: pick(['active', 'inactive']),
    phong_ban_ids: departments.value.length
      ? [pick(departments.value).id]
      : [],
    ngan_hang: nganHang,
    chi_nhanh: chiNhanh,
    so_tai_khoan: randomDigits(10),
    chu_tai_khoan: toUnsign(fullName).toUpperCase(),
    gioi_tinh: gioiTinh,
    ngay_sinh: randomDate(1988, 2002),
    cccd: `0${randomDigits(11)}`,
    vi_tri_lam_viec: viTri,
    ngay_vao_cong_ty: ngayVao,
    ngay_ky_hop_dong: ngayVao,
    loai_nhan_vien: pick(['full_time', 'part_time']),
    loai_hop_dong: pick(['chinh_thuc', 'hoc_viec', 'thu_viec']),
    cong_chuan: randomInt(22, 26),
    tham_gia_bao_hiem: Math.random() > 0.3,
    so_nguoi_phu_thuoc: randomInt(0, 3),
    luong_thuong_phu_cap: createDefaultLuongThuongPhuCap({
      luong_cung: { value: luongCung },
      luong_mem: { value: luongMem },
      phu_cap: { value: phuCap },
      luong_1_gio: { value: randomInt(3, 8) * 10_000 },
      luong_tang_ca_1_gio: { value: randomInt(3, 8) * 10_000 },
      phu_cap_xang: { value: randomInt(3, 8) * 100_000 },
      phu_cap_an_trua: { value: randomInt(5, 10) * 100_000 },
      phu_cap_dien_thoai: { value: randomInt(1, 4) * 100_000 },
      phu_cap_nha_o: { value: randomInt(5, 15) * 100_000 },
      thuong_chuyen_can: { value: randomInt(2, 8) * 100_000 },
      hoa_hong_hop_dong_sddv: { value: randomInt(0, 5) * 100_000 },
      hoa_hong_hop_dong_trang_phuc: { value: randomInt(0, 3) * 100_000 },
    }),
  })
  resetImageState()
  formRef.value?.clearValidate?.()
  ElMessage.success('Đã điền dữ liệu mẫu ngẫu nhiên')
}

function openEdit(row) {
  editingId.value = row.id
  activeTab.value = 'personal'
  const nvData = row.nhan_vien || {}
  Object.assign(form, emptyForm(), {
    name: row.name,
    email: row.email,
    phone: row.phone || '',
    password: '',
    role: row.role || 'user',
    status: row.status || 'active',
    hinh_anh: nvData.hinh_anh || '',
    phong_ban_ids: Array.isArray(nvData.phong_ban_ids) ? [...nvData.phong_ban_ids] : [],
    ngan_hang: nvData.ngan_hang || '',
    chi_nhanh: nvData.chi_nhanh || '',
    so_tai_khoan: nvData.so_tai_khoan || '',
    chu_tai_khoan: nvData.chu_tai_khoan || '',
    gioi_tinh: nvData.gioi_tinh || null,
    ngay_sinh: toDateOnly(nvData.ngay_sinh),
    cccd: nvData.cccd || '',
    vi_tri_lam_viec: nvData.vi_tri_lam_viec || '',
    ngay_vao_cong_ty: toDateOnly(nvData.ngay_vao_cong_ty),
    ngay_ky_hop_dong: toDateOnly(nvData.ngay_ky_hop_dong),
    loai_nhan_vien: nvData.loai_nhan_vien || null,
    loai_hop_dong: nvData.loai_hop_dong || null,
    cong_chuan: nvData.cong_chuan != null ? Number(nvData.cong_chuan) : null,
    tham_gia_bao_hiem: !!nvData.tham_gia_bao_hiem,
    so_nguoi_phu_thuoc: nvData.so_nguoi_phu_thuoc ?? 0,
    luong_thuong_phu_cap: createDefaultLuongThuongPhuCap(nvData.luong_thuong_phu_cap || {}),
  })
  resetImageState()
  dialogVisible.value = true
}

function toDateOnly(value) {
  if (!value) return null
  return String(value).slice(0, 10)
}

function buildPayload() {
  const luongThuongPhuCap = {}
  for (const def of SALARY_FIELD_DEFINITIONS) {
    const item = form.luong_thuong_phu_cap[def.key] || {}
    luongThuongPhuCap[def.key] = {
      name: item.name || def.name,
      value: item.value != null && item.value !== '' ? Number(item.value) : null,
      note: item.note?.trim() ? item.note.trim() : (def.note || null),
    }
  }

  const payload = {
    name: form.name.trim(),
    email: form.email.trim(),
    phone: form.phone.trim(),
    role: form.role,
    status: form.status,
    hinh_anh: form.hinh_anh?.trim() || null,
    phong_ban_ids: Array.isArray(form.phong_ban_ids) ? form.phong_ban_ids : [],
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
    luong_thuong_phu_cap: luongThuongPhuCap,
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
.employee-list {
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

.card-header-actions {
  display: flex;
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
