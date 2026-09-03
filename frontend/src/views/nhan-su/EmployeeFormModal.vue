<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1500"
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
                <div class="upload-hint" />
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
                  <CustomFormItem label="Loại nhân viên" prop="loai_nhan_vien">
                    <CustomSelect v-model="form.loai_nhan_vien" clearable placeholder="Chọn" style="width: 100%">
                      <CustomOption label="Full time" value="full_time" />
                      <CustomOption label="Part time" value="part_time" />
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
                  <CustomFormItem label="Vai trò (chức danh)" prop="vai_tro_id">
                    <CustomSelect
                      v-model="form.vai_tro_id"
                      clearable
                      filterable
                      placeholder="Chọn vai trò"
                      style="width: 100%"
                    >
                      <CustomOption
                        v-for="vt in vaiTroOptions"
                        :key="vt.id"
                        :label="vt.ten_vai_tro"
                        :value="vt.id"
                      />
                    </CustomSelect>
                  </CustomFormItem>
                </CustomCol>
                <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
                  <CustomFormItem label="Điều phối" prop="is_dieu_phoi">
                    <div class="dieu-phoi-field">
                      <el-switch
                        v-model="form.is_dieu_phoi"
                        inline-prompt
                        active-text="Bật"
                        inactive-text="Tắt"
                      />
                      <span class="dieu-phoi-hint">Bật → gán vai trò điều phối</span>
                    </div>
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
          <div class="salary-groups">
            <CustomCard
              v-for="group in salaryFormGroups"
              :key="group.key"
              shadow="never"
              class="salary-group-card"
            >
              <template #header>
                <span class="salary-group-title">{{ group.title }}</span>
              </template>
              <CustomRow v-if="group.items.length" :gutter="16" class="salary-fields">
                <CustomCol
                  v-if="group.key === 'luong'"
                  :xs="group.cols.xs"
                  :sm="group.cols.sm"
                  :md="group.cols.md"
                  :lg="group.cols.lg"
                >
                  <CustomFormItem label="Công chuẩn" prop="cong_chuan">
                    <el-input-number
                      v-model="form.cong_chuan"
                      :min="0"
                      :precision="2"
                      controls-position="right"
                      placeholder="Công chuẩn"
                      style="width: 100%"
                    />
                  </CustomFormItem>
                </CustomCol>
                <CustomCol
                  v-for="item in group.items"
                  :key="item.key"
                  :xs="group.cols.xs"
                  :sm="group.cols.sm"
                  :md="group.cols.md"
                  :lg="group.cols.lg"
                >
                  <CustomFormItem
                    :label="item.name"
                    :prop="`luong_thuong_phu_cap.${item.key}.value`"
                    :rules="salaryValueRules(item)"
                  >
                    <div v-if="item.kind === 'percent'" class="percent-input">
                      <el-input-number
                        v-model="form.luong_thuong_phu_cap[item.key].value"
                        :min="0"
                        :max="100"
                        :precision="2"
                        :step="0.01"
                        controls-position="right"
                        :placeholder="item.name"
                        style="width: 100%"
                      />
                    </div>
                    <MoneyInput
                      v-else
                      v-model="form.luong_thuong_phu_cap[item.key].value"
                      style="width: 100%"
                    />
                    <div v-if="item.note" class="salary-field-note">{{ item.note }}</div>
                  </CustomFormItem>
                </CustomCol>
              </CustomRow>
              <EmployeeLuongDiemTheoLoai
                v-if="group.table"
                :class="{ 'luong-dich-vu-table': group.items.length }"
                :luong="form.luong_thuong_phu_cap"
                :loai-list="loaiQuayChup"
              />
            </CustomCard>
          </div>
        </el-tab-pane>
      </el-tabs>
    </CustomForm>
    <template #footer>
      <CustomButton @click="visible = false">Hủy</CustomButton>
      <CustomButton type="primary" :loading="saving" @click="save">Lưu</CustomButton>
      <CustomButton v-if="!editingId" plain @click="fillSampleData">Dữ liệu mẫu</CustomButton>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Delete, Plus } from '@element-plus/icons-vue'
import { createUser, updateUser, uploadNhanVienHinh } from '@/api/users'
import { mediaUrl } from '@/utils/media'
import {
  CustomButton,
  CustomCard,
  CustomCol,
  CustomDialog,
  CustomForm,
  CustomFormItem,
  CustomInput,
  CustomOption,
  CustomRow,
  MoneyInput,
  CustomSelect,
} from '@/components/element'
import EmployeeLuongDiemTheoLoai from './EmployeeLuongDiemTheoLoai.vue'
import {
  buildSalaryGroups,
  createDefaultLuongThuongPhuCap,
  ensureLuongTheoDichVu,
  sampleLuongTheoDichVu,
  serializeLuongThuongPhuCap,
} from './employeeSalaryFields'

const PERCENT_MAX = 100

function salaryValueRules(item) {
  if (item?.kind !== 'percent') return []
  return [
    {
      validator: (_rule, value, callback) => {
        if (value == null || value === '') {
          callback()
          return
        }
        const n = Number(value)
        if (!Number.isFinite(n)) {
          callback(new Error(`${item.name} không hợp lệ`))
          return
        }
        if (n < 0 || n > PERCENT_MAX) {
          callback(new Error(`${item.name} phải từ 0 đến 100%`))
          return
        }
        const rounded = Math.round(n * 100) / 100
        if (Math.abs(n - rounded) > 1e-9) {
          callback(new Error(`${item.name} chỉ nhận tối đa 2 số sau dấu phẩy`))
          return
        }
        callback()
      },
      trigger: ['blur', 'change'],
    },
  ]
}

const visible = defineModel({ type: Boolean, default: false })

const props = defineProps({
  employee: { type: Object, default: null },
  departments: { type: Array, default: () => [] },
  vaiTroOptions: { type: Array, default: () => [] },
  loaiQuayChup: { type: Array, default: () => [] },
})

const emit = defineEmits(['saved'])

const saving = ref(false)
const formRef = ref(null)
const activeTab = ref('personal')
const pendingImageFile = ref(null)
const pendingPreviewUrl = ref('')

const emptyForm = () => ({
  name: '',
  email: '',
  phone: '',
  password: '',
  role: 'user',
  is_dieu_phoi: false,
  status: 'active',
  hinh_anh: '',
  phong_ban_ids: [],
  vai_tro_id: null,
  ngan_hang: '',
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
  cong_chuan: null,
  tham_gia_bao_hiem: false,
  so_nguoi_phu_thuoc: 0,
  luong_thuong_phu_cap: createDefaultLuongThuongPhuCap(),
})

const form = reactive(emptyForm())

const editingId = computed(() => props.employee?.id || null)

const dialogTitle = computed(() => {
  if (!editingId.value) return 'Thêm nhân sự'
  const name = String(props.employee?.name || form.name || '').trim()
  const phone = String(props.employee?.phone || form.phone || '').trim()
  if (name && phone) return `Sửa nhân sự: ${name} - ${phone}`
  if (name) return `Sửa nhân sự: ${name}`
  if (phone) return `Sửa nhân sự: ${phone}`
  return 'Sửa nhân sự'
})

const salaryFormGroups = computed(() => buildSalaryGroups(form.luong_thuong_phu_cap))

const avatarPreviewUrl = computed(() => {
  if (pendingPreviewUrl.value) return pendingPreviewUrl.value
  return mediaUrl(form.hinh_anh)
})

const rules = {
  name: [{ required: true, message: 'Vui lòng nhập họ tên', trigger: 'blur' }],
  email: [
    { required: true, message: 'Vui lòng nhập email', trigger: 'blur' },
    { type: 'email', message: 'Email không hợp lệ', trigger: 'blur' },
  ],
  phone: [{ required: true, message: 'Vui lòng nhập SĐT', trigger: 'blur' }],
}

function toDateOnly(value) {
  if (!value) return null
  return String(value).slice(0, 10)
}

function applyLuongTheoDichVu() {
  ensureLuongTheoDichVu(form.luong_thuong_phu_cap, props.loaiQuayChup)
}

function fillFromEmployee(row) {
  const nvData = row.nhan_vien || {}
  Object.assign(form, emptyForm(), {
    name: row.name,
    email: row.email,
    phone: row.phone || '',
    password: '',
    role: row.role || 'user',
    is_dieu_phoi: row.role === 'coordinator',
    status: row.status || 'active',
    hinh_anh: nvData.hinh_anh || '',
    phong_ban_ids: Array.isArray(nvData.phong_ban_ids) ? [...nvData.phong_ban_ids] : [],
    vai_tro_id: nvData.vai_tro_id ?? null,
    ngan_hang: nvData.ngan_hang || '',
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
  applyLuongTheoDichVu()
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

watch(visible, (open) => {
  if (!open) {
    resetImageState()
    return
  }
  activeTab.value = 'personal'
  if (props.employee?.id) {
    fillFromEmployee(props.employee)
  } else {
    Object.assign(form, emptyForm())
    applyLuongTheoDichVu()
    resetImageState()
  }
  nextTick(() => formRef.value?.clearValidate?.())
})

watch(
  () => props.loaiQuayChup,
  () => {
    if (visible.value) applyLuongTheoDichVu()
  },
)

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
    'Nam', 'Nga', 'Phong', 'Quân', 'Sơn', 'Trang', 'Tuấn', 'Vy', 'Yến', 'Khoa',
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
  const luongCung = randomInt(6, 15) * 1_000_000
  const luongMem = randomInt(1, 5) * 1_000_000
  const phuCap = randomInt(5, 20) * 100_000
  const ngayVao = randomDate(2022, 2025)

  Object.assign(form, emptyForm(), {
    name: fullName,
    email: `${emailLocal}@example.com`,
    phone: `09${randomDigits(8)}`,
    role: 'user',
    is_dieu_phoi: false,
    status: pick(['active', 'inactive']),
    phong_ban_ids: props.departments.length
      ? [pick(props.departments).id]
      : [],
    ngan_hang: nganHang,
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
      luong_chinh_sua_anh: { value: randomInt(3, 8) * 10_000 },
      luong_dung_video: { value: randomInt(3, 8) * 10_000 },
      phu_cap_xang: { value: randomInt(3, 8) * 100_000 },
      phu_cap_an_trua: { value: randomInt(5, 10) * 100_000 },
      phu_cap_dien_thoai: { value: randomInt(1, 4) * 100_000 },
      phu_cap_nha_o: { value: randomInt(5, 15) * 100_000 },
      phu_cap_thu_bay_va_chu_nhat: { value: 0 },
      thuong_chuyen_can: { value: randomInt(2, 8) * 100_000 },
      chuyen_can_khong_nghi: { value: 0 },
      chuyen_can_nghi_1_ngay: { value: 0 },
      chuyen_can_nghi_2_ngay: { value: 0 },
      chuyen_can_nghi_3_ngay: { value: 0 },
      hoa_hong_hop_dong_sddv: { value: Number((Math.random() * 5).toFixed(2)) },
      hoa_hong_hop_dong_trang_phuc: { value: Number((Math.random() * 3).toFixed(2)) },
      luong_theo_dich_vu: sampleLuongTheoDichVu(props.loaiQuayChup, randomInt),
    }),
  })
  applyLuongTheoDichVu()
  resetImageState()
  formRef.value?.clearValidate?.()
  ElMessage.success('Đã điền dữ liệu mẫu ngẫu nhiên')
}

function buildPayload() {
  const payload = {
    name: form.name.trim(),
    email: form.email.trim(),
    phone: form.phone.trim(),
    status: form.status,
    hinh_anh: form.hinh_anh?.trim() || null,
    phong_ban_ids: Array.isArray(form.phong_ban_ids) ? form.phong_ban_ids : [],
    vai_tro_id: form.vai_tro_id || null,
    ngan_hang: form.ngan_hang?.trim() || null,
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
    luong_thuong_phu_cap: serializeLuongThuongPhuCap(form.luong_thuong_phu_cap, props.loaiQuayChup),
  }

  if (editingId.value) {
    // Admin giữ nguyên role; còn lại: bật → coordinator, tắt → user
    if (form.role !== 'admin') {
      payload.role = form.is_dieu_phoi ? 'coordinator' : 'user'
    }
  } else {
    payload.password = '123456789'
    payload.role = form.is_dieu_phoi ? 'coordinator' : 'user'
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
    visible.value = false
    emit('saved')
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.dieu-phoi-field {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 32px;
}

.dieu-phoi-hint {
  font-size: 12px;
  color: var(--el-text-color-secondary);
  line-height: 1.3;
}

.salary-groups {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.salary-group-card {
  border: 1px solid var(--el-border-color-lighter);
}

.salary-group-card :deep(.el-card__header) {
  padding: 10px 16px;
  background: var(--el-fill-color-light);
}

.salary-group-card :deep(.el-card__body) {
  padding: 12px 16px 4px;
}

.salary-group-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.salary-fields :deep(.el-form-item) {
  margin-bottom: 12px;
}

.salary-fields :deep(.el-input-number .el-input__inner) {
  text-align: left;
}

.percent-input {
  width: 100%;

  :deep(.el-input-number) {
    width: 100%;
  }
}

.salary-field-note {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.35;
  color: var(--el-text-color-secondary);
}

.luong-dich-vu-table {
  margin-top: 8px;
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
