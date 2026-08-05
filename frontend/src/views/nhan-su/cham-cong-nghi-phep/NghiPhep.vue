<template>
  <div class="nghi-phep page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="6" :lg="7">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo tên nhân viên, lý do..."
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
            v-model="loaiFilter"
            placeholder="Loại nghỉ phép"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="item in LOAI_NGHI_PHEP_OPTIONS"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="6" :lg="5">
          <CustomSelect
            v-model="trangThaiFilter"
            placeholder="Trạng thái"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="item in TRANG_THAI_OPTIONS"
              :key="item.value"
              :label="item.label"
              :value="item.value"
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
          <span class="card-title">Danh sách xin nghỉ phép</span>
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

      <CustomTable v-loading="loading" :data="items" stripe style="width: 100%">
        <CustomTableColumn label="STT" width="60" align="center">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nhan_vien')"
          label="Nhân viên"
          min-width="160"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.user?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('loai_nghi')"
          label="Loại nghỉ"
          min-width="140"
        >
          <template #default="{ row }">
            {{ loaiLabel(row.loai_nghi_phep) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('buoi_nghi')"
          label="Buổi nghỉ"
          width="110"
          align="center"
        >
          <template #default="{ row }">
            {{ buoiLabel(row.buoi_nghi) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('thoi_gian')"
          label="Thời gian"
          min-width="160"
          align="center"
        >
          <template #default="{ row }">
            {{ formatThoiGian(row) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ly_do')"
          prop="ly_do"
          label="Lý do"
          min-width="180"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.ly_do || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('trang_thai')"
          label="Trạng thái"
          width="130"
          align="center"
        >
          <template #default="{ row }">
            <CustomTag :type="trangThaiTagType(row.trang_thai)" effect="light">
              {{ trangThaiLabel(row.trang_thai) }}
            </CustomTag>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguoi_duyet')"
          label="Người duyệt"
          min-width="140"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.nguoi_duyet?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Thao tác" width="160" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip v-if="row.trang_thai === 'cho_duyet'" content="Duyệt" placement="top">
                <CustomButton
                  type="success"
                  link
                  :icon="CircleCheck"
                  :loading="processingId === row.id"
                  @click="duyet(row)"
                />
              </CustomTooltip>
              <CustomTooltip v-if="row.trang_thai === 'cho_duyet'" content="Từ chối" placement="top">
                <CustomButton
                  type="warning"
                  link
                  :icon="CircleClose"
                  :loading="processingId === row.id"
                  @click="tuChoi(row)"
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

    <CustomDialog
      v-model="dialogVisible"
      :title="editingId ? 'Sửa đơn nghỉ phép' : 'Thêm đơn nghỉ phép'"
      :width="720"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Nhân viên nghỉ phép" prop="user_id">
              <CustomSelect
                v-model="form.user_id"
                filterable
                placeholder="Chọn nhân viên"
                style="width: 100%"
              >
                <CustomOption
                  v-for="user in userOptions"
                  :key="user.id"
                  :label="user.name"
                  :value="user.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Loại nghỉ phép" prop="loai_nghi_phep">
              <CustomSelect
                v-model="form.loai_nghi_phep"
                placeholder="Chọn loại nghỉ"
                style="width: 100%"
                @change="onLoaiChange"
              >
                <CustomOption
                  v-for="item in LOAI_NGHI_PHEP_OPTIONS"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>

          <!-- Đi muộn / Về sớm / Nghỉ 1 ngày: chỉ ngày nghỉ phép -->
          <CustomCol v-if="isNghiMotNgay" :xs="24" :sm="12">
            <CustomFormItem label="Ngày nghỉ phép" prop="ngay_bat_dau">
              <el-date-picker
                v-model="form.ngay_bat_dau"
                type="date"
                value-format="YYYY-MM-DD"
                placeholder="Chọn ngày nghỉ"
                style="width: 100%"
              />
            </CustomFormItem>
          </CustomCol>

          <!-- Nghỉ nửa ngày: buổi nghỉ + ngày nghỉ -->
          <template v-if="isNghiNuaNgay">
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Buổi nghỉ" prop="buoi_nghi">
                <CustomSelect
                  v-model="form.buoi_nghi"
                  placeholder="Chọn buổi nghỉ"
                  style="width: 100%"
                >
                  <CustomOption
                    v-for="item in BUOI_NGHI_OPTIONS"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </CustomSelect>
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Ngày nghỉ phép" prop="ngay_bat_dau">
                <el-date-picker
                  v-model="form.ngay_bat_dau"
                  type="date"
                  value-format="YYYY-MM-DD"
                  placeholder="Chọn ngày nghỉ"
                  style="width: 100%"
                />
              </CustomFormItem>
            </CustomCol>
          </template>

          <!-- Nghỉ nhiều ngày: ngày bắt đầu + ngày kết thúc -->
          <template v-if="isNghiNhieuNgay">
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Ngày bắt đầu" prop="ngay_bat_dau">
                <el-date-picker
                  v-model="form.ngay_bat_dau"
                  type="date"
                  value-format="YYYY-MM-DD"
                  placeholder="Chọn ngày bắt đầu"
                  style="width: 100%"
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Ngày kết thúc" prop="ngay_ket_thuc">
                <el-date-picker
                  v-model="form.ngay_ket_thuc"
                  type="date"
                  value-format="YYYY-MM-DD"
                  placeholder="Chọn ngày kết thúc"
                  style="width: 100%"
                />
              </CustomFormItem>
            </CustomCol>
          </template>

          <CustomCol :span="24">
            <CustomFormItem label="Lý do" prop="ly_do">
              <CustomInput
                v-model="form.ly_do"
                type="textarea"
                :rows="3"
                placeholder="Nhập lý do nghỉ phép"
              />
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
import { CircleCheck, CircleClose, Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import { fetchUsers } from '@/api/users'
import {
  createXinNghiPhep,
  deleteXinNghiPhep,
  duyetXinNghiPhep,
  fetchXinNghiPhep,
  tuChoiXinNghiPhep,
  updateXinNghiPhep,
} from '@/api/xinNghiPhep'
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

const tableColumns = [
  { key: 'nhan_vien', label: 'Nhân viên' },
  { key: 'loai_nghi', label: 'Loại nghỉ' },
  { key: 'buoi_nghi', label: 'Buổi nghỉ' },
  { key: 'thoi_gian', label: 'Thời gian' },
  { key: 'ly_do', label: 'Lý do' },
  { key: 'trang_thai', label: 'Trạng thái' },
  { key: 'nguoi_duyet', label: 'Người duyệt' },
]
const columnSettings = useTableColumns('nhan-su.nghi-phep', tableColumns)

const LOAI_NGHI_PHEP_OPTIONS = [
  { value: 'di_muon', label: 'Đi muộn' },
  { value: 've_som', label: 'Về sớm' },
  { value: 'nghi_nua_ngay', label: 'Nghỉ nửa ngày' },
  { value: 'nghi_1_ngay', label: 'Nghỉ 1 ngày' },
  { value: 'nghi_nhieu_ngay', label: 'Nghỉ nhiều ngày' },
]

const BUOI_NGHI_OPTIONS = [
  { value: 'sang', label: 'Buổi sáng' },
  { value: 'chieu', label: 'Buổi chiều' },
]

const TRANG_THAI_OPTIONS = [
  { value: 'cho_duyet', label: 'Chờ duyệt' },
  { value: 'da_duyet', label: 'Đã duyệt' },
  { value: 'tu_choi', label: 'Từ chối' },
]

const items = ref([])
const userOptions = ref([])
const loading = ref(false)
const saving = ref(false)
const processingId = ref(null)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const loaiFilter = ref('')
const trangThaiFilter = ref('')

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)

const emptyForm = () => ({
  user_id: null,
  loai_nghi_phep: 'nghi_nua_ngay',
  buoi_nghi: null,
  ngay_bat_dau: '',
  ngay_ket_thuc: '',
  ly_do: '',
})

const form = reactive(emptyForm())

const isNghiMotNgay = computed(() =>
  ['di_muon', 've_som', 'nghi_1_ngay'].includes(form.loai_nghi_phep),
)
const isNghiNuaNgay = computed(() => form.loai_nghi_phep === 'nghi_nua_ngay')
const isNghiNhieuNgay = computed(() => form.loai_nghi_phep === 'nghi_nhieu_ngay')

const ngayBatDauMessage = computed(() => {
  if (isNghiNhieuNgay.value) return 'Vui lòng chọn ngày bắt đầu'
  return 'Vui lòng chọn ngày nghỉ phép'
})

const rules = computed(() => ({
  user_id: [{ required: true, message: 'Vui lòng chọn nhân viên', trigger: 'change' }],
  loai_nghi_phep: [{ required: true, message: 'Vui lòng chọn loại nghỉ phép', trigger: 'change' }],
  ngay_bat_dau: [{ required: true, message: ngayBatDauMessage.value, trigger: 'change' }],
  buoi_nghi: isNghiNuaNgay.value
    ? [{ required: true, message: 'Vui lòng chọn buổi nghỉ', trigger: 'change' }]
    : [],
  ngay_ket_thuc: isNghiNhieuNgay.value
    ? [{ required: true, message: 'Vui lòng chọn ngày kết thúc', trigger: 'change' }]
    : [],
}))

function loaiLabel(value) {
  return LOAI_NGHI_PHEP_OPTIONS.find((item) => item.value === value)?.label || value || '—'
}

function buoiLabel(value) {
  return BUOI_NGHI_OPTIONS.find((item) => item.value === value)?.label || '—'
}

function trangThaiLabel(value) {
  return TRANG_THAI_OPTIONS.find((item) => item.value === value)?.label || value || '—'
}

function trangThaiTagType(value) {
  if (value === 'da_duyet') return 'success'
  if (value === 'tu_choi') return 'danger'
  return 'warning'
}

function formatDate(value) {
  if (!value) return '—'
  const raw = String(value).slice(0, 10)
  const [year, month, day] = raw.split('-')
  if (!year || !month || !day) return raw
  return `${day}/${month}/${year}`
}

function formatThoiGian(row) {
  const batDau = formatDate(row.ngay_bat_dau)
  if (row.loai_nghi_phep !== 'nghi_nhieu_ngay') {
    return batDau
  }
  const ketThuc = formatDate(row.ngay_ket_thuc)
  if (batDau === '—' || ketThuc === '—' || batDau === ketThuc) {
    return batDau
  }
  return `${batDau} - ${ketThuc}`
}

function onLoaiChange() {
  if (!isNghiNuaNgay.value) {
    form.buoi_nghi = null
  }
  if (!isNghiNhieuNgay.value) {
    form.ngay_ket_thuc = ''
  }
  formRef.value?.clearValidate(['buoi_nghi', 'ngay_bat_dau', 'ngay_ket_thuc'])
}

async function loadUsers() {
  try {
    const { data } = await fetchUsers({ per_page: 100, status: 'active' })
    userOptions.value = data.data || []
  } catch {
    userOptions.value = []
  }
}

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchXinNghiPhep({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      loai_nghi_phep: loaiFilter.value || undefined,
      trang_thai: trangThaiFilter.value || undefined,
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
    user_id: row.user_id,
    loai_nghi_phep: row.loai_nghi_phep,
    buoi_nghi: row.buoi_nghi || null,
    ngay_bat_dau: row.ngay_bat_dau ? String(row.ngay_bat_dau).slice(0, 10) : '',
    ngay_ket_thuc: row.ngay_ket_thuc ? String(row.ngay_ket_thuc).slice(0, 10) : '',
    ly_do: row.ly_do || '',
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    user_id: form.user_id,
    loai_nghi_phep: form.loai_nghi_phep,
    buoi_nghi: isNghiNuaNgay.value ? form.buoi_nghi || null : null,
    ngay_bat_dau: form.ngay_bat_dau,
    ngay_ket_thuc: isNghiNhieuNgay.value ? form.ngay_ket_thuc || null : form.ngay_bat_dau,
    ly_do: form.ly_do?.trim() || null,
  }

  try {
    if (editingId.value) {
      await updateXinNghiPhep(editingId.value, payload)
      ElMessage.success('Đã cập nhật đơn nghỉ phép.')
    } else {
      await createXinNghiPhep(payload)
      ElMessage.success('Đã thêm đơn nghỉ phép.')
    }
    dialogVisible.value = false
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
}

async function duyet(row) {
  const name = row.user?.name || 'nhân viên'
  await ElMessageBox.confirm(
    `Duyệt đơn nghỉ phép của "${name}" ngày ${formatDate(row.ngay_bat_dau)}?`,
    'Xác nhận duyệt',
    {
      type: 'success',
      confirmButtonText: 'Duyệt',
      cancelButtonText: 'Hủy',
    },
  )

  processingId.value = row.id
  try {
    await duyetXinNghiPhep(row.id)
    ElMessage.success('Đã duyệt đơn nghỉ phép.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    processingId.value = null
  }
}

async function tuChoi(row) {
  const name = row.user?.name || 'nhân viên'
  await ElMessageBox.confirm(
    `Từ chối đơn nghỉ phép của "${name}" ngày ${formatDate(row.ngay_bat_dau)}?`,
    'Xác nhận từ chối',
    {
      type: 'warning',
      confirmButtonText: 'Từ chối',
      cancelButtonText: 'Hủy',
    },
  )

  processingId.value = row.id
  try {
    await tuChoiXinNghiPhep(row.id)
    ElMessage.success('Đã từ chối đơn nghỉ phép.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    processingId.value = null
  }
}

async function remove(row) {
  const name = row.user?.name || 'nhân viên'
  await ElMessageBox.confirm(
    `Xóa đơn nghỉ phép của "${name}" ngày ${formatDate(row.ngay_bat_dau)}?`,
    'Xác nhận',
    {
      type: 'warning',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
    },
  )

  try {
    await deleteXinNghiPhep(row.id)
    ElMessage.success('Đã xóa đơn nghỉ phép.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(async () => {
  await Promise.all([loadUsers(), loadItems()])
})
</script>

