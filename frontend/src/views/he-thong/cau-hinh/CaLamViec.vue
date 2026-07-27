<template>
  <ConfigSettingPage title="Ca làm việc">
    <div class="ca-lam-viec">
      <CustomCard shadow="hover" class="filter-card">
        <div class="toolbar">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo tên ca, ghi chú..."
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
            v-model="trangThaiFilter"
            placeholder="Trạng thái"
            clearable
            style="width: 180px"
            @change="onSearch"
          >
            <CustomOption label="Đang dùng" value="co" />
            <CustomOption label="Không dùng" value="khong" />
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
            <span class="card-title">Danh sách ca làm việc</span>
            <CustomButton type="primary" @click="openCreate">
              <CustomIcon><Plus /></CustomIcon>
              Thêm ca
            </CustomButton>
          </div>
        </template>

        <CustomTable v-loading="loading" :data="items" stripe style="width: 100%">
          <CustomTableColumn label="STT" width="60" align="center">
            <template #default="{ $index }">
              {{ (page - 1) * perPage + $index + 1 }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="ten_ca" label="Tên ca" min-width="160" show-overflow-tooltip />
          <CustomTableColumn prop="gio_bat_dau" label="Giờ bắt đầu" min-width="120" align="center">
            <template #default="{ row }">
              {{ formatTime(row.gio_bat_dau) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="gio_ket_thuc" label="Giờ kết thúc" min-width="120" align="center">
            <template #default="{ row }">
              {{ formatTime(row.gio_ket_thuc) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="ghi_chu" label="Ghi chú" min-width="200" show-overflow-tooltip>
            <template #default="{ row }">
              {{ row.ghi_chu || '—' }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="trang_thai" label="Trạng thái" width="180" align="center">
            <template #default="{ row }">
              <div class="status-cell">
                <el-switch
                  :model-value="row.trang_thai"
                  active-value="co"
                  inactive-value="khong"
                  :loading="togglingId === row.id"
                  :disabled="togglingId === row.id"
                  :before-change="() => toggleStatus(row)"
                />
                <span
                  class="status-label"
                  :class="row.trang_thai === 'co' ? 'is-active' : 'is-inactive'"
                >
                  {{ row.trang_thai === 'co' ? 'Đang dùng' : 'Không dùng' }}
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
        :title="editingId ? 'Sửa ca làm việc' : 'Thêm ca làm việc'"
        :width="640"
      >
        <CustomForm ref="formRef" :model="form" :rules="rules">
          <CustomRow :gutter="16">
            <CustomCol :span="24">
              <CustomFormItem label="Tên ca" prop="ten_ca">
                <CustomInput v-model="form.ten_ca" placeholder="VD: Ca sáng, Ca chiều" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Giờ bắt đầu" prop="gio_bat_dau">
                <el-time-picker
                  v-model="form.gio_bat_dau"
                  format="HH:mm"
                  value-format="HH:mm"
                  placeholder="Chọn giờ"
                  style="width: 100%"
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Giờ kết thúc" prop="gio_ket_thuc">
                <el-time-picker
                  v-model="form.gio_ket_thuc"
                  format="HH:mm"
                  value-format="HH:mm"
                  placeholder="Chọn giờ"
                  style="width: 100%"
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Trạng thái" prop="trang_thai">
                <CustomSelect v-model="form.trang_thai" style="width: 100%">
                  <CustomOption label="Đang dùng" value="co" />
                  <CustomOption label="Không dùng" value="khong" />
                </CustomSelect>
              </CustomFormItem>
            </CustomCol>
            <CustomCol :span="24">
              <CustomFormItem label="Ghi chú" prop="ghi_chu">
                <CustomInput
                  v-model="form.ghi_chu"
                  type="textarea"
                  :rows="3"
                  placeholder="Ghi chú (tuỳ chọn)"
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
  </ConfigSettingPage>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createCaLamViec,
  deleteCaLamViec,
  fetchCaLamViec,
  updateCaLamViec,
} from '@/api/caLamViec'
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
import ConfigSettingPage from './ConfigSettingPage.vue'

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const togglingId = ref(null)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const trangThaiFilter = ref('')

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)

function formatTime(value) {
  if (!value) return '—'
  return String(value).slice(0, 5)
}

const emptyForm = () => ({
  ten_ca: '',
  gio_bat_dau: '',
  gio_ket_thuc: '',
  trang_thai: 'co',
  ghi_chu: '',
})

const form = reactive(emptyForm())

const rules = {
  ten_ca: [{ required: true, message: 'Vui lòng nhập tên ca', trigger: 'blur' }],
  gio_bat_dau: [{ required: true, message: 'Vui lòng chọn giờ bắt đầu', trigger: 'change' }],
  gio_ket_thuc: [{ required: true, message: 'Vui lòng chọn giờ kết thúc', trigger: 'change' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

async function toggleStatus(row) {
  if (!row?.id) return false

  const value = row.trang_thai === 'co' ? 'khong' : 'co'
  togglingId.value = row.id

  try {
    await updateCaLamViec(row.id, {
      ten_ca: row.ten_ca,
      gio_bat_dau: formatTime(row.gio_bat_dau),
      gio_ket_thuc: formatTime(row.gio_ket_thuc),
      ghi_chu: row.ghi_chu || null,
      trang_thai: value,
    })
    row.trang_thai = value
    ElMessage.success(value === 'co' ? 'Đã bật ca làm việc.' : 'Đã tắt ca làm việc.')
    return true
  } catch {
    return false
  } finally {
    togglingId.value = null
  }
}

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchCaLamViec({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
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
    ten_ca: row.ten_ca,
    gio_bat_dau: formatTime(row.gio_bat_dau),
    gio_ket_thuc: formatTime(row.gio_ket_thuc),
    trang_thai: row.trang_thai || 'co',
    ghi_chu: row.ghi_chu || '',
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    ten_ca: form.ten_ca.trim(),
    gio_bat_dau: form.gio_bat_dau,
    gio_ket_thuc: form.gio_ket_thuc,
    trang_thai: form.trang_thai,
    ghi_chu: form.ghi_chu?.trim() || null,
  }

  try {
    if (editingId.value) {
      await updateCaLamViec(editingId.value, payload)
      ElMessage.success('Đã cập nhật ca làm việc.')
    } else {
      await createCaLamViec(payload)
      ElMessage.success('Đã thêm ca làm việc.')
    }
    dialogVisible.value = false
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa ca làm việc "${row.ten_ca}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteCaLamViec(row.id)
    ElMessage.success('Đã xóa ca làm việc.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadItems)
</script>

<style scoped>
.ca-lam-viec {
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
</style>
