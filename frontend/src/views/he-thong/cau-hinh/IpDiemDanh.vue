<template>
  <ConfigSettingPage title="IP điểm danh">
    <div class="ip-diem-danh">
      <CustomCard shadow="hover" class="filter-card">
        <div class="toolbar">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo tên IP, địa chỉ IP, ghi chú..."
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
            <span class="card-title">Danh sách IP điểm danh</span>
            <CustomButton type="primary" @click="openCreate">
              <CustomIcon><Plus /></CustomIcon>
              Thêm IP
            </CustomButton>
          </div>
        </template>

        <CustomTable v-loading="loading" :data="items" stripe style="width: 100%">
          <CustomTableColumn label="STT" width="60" align="center">
            <template #default="{ $index }">
              {{ (page - 1) * perPage + $index + 1 }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="ten_ip" label="Tên IP" min-width="160" />
          <CustomTableColumn prop="dia_chi_ip" label="Địa chỉ IP" min-width="160" />
          <CustomTableColumn prop="ghi_chu" label="Ghi chú" min-width="200" show-overflow-tooltip>
            <template #default="{ row }">
              {{ row.ghi_chu || '—' }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="trang_thai" label="Trạng thái" width="180" align="center">
            <template #default="{ row }">
              <div class="status-cell">
                <el-switch
                  v-model="row.trang_thai"
                  active-value="active"
                  inactive-value="inactive"
                  :loading="togglingId === row.id"
                  :disabled="togglingId === row.id"
                  @change="(val) => toggleStatus(row, val)"
                />
                <span
                  class="status-label"
                  :class="row.trang_thai === 'active' ? 'is-active' : 'is-inactive'"
                >
                  {{ row.trang_thai === 'active' ? 'Đang hoạt động' : 'Không hoạt động' }}
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
        :title="editingId ? 'Sửa IP điểm danh' : 'Thêm IP điểm danh'"
        :width="640"
      >
        <CustomForm ref="formRef" :model="form" :rules="rules">
          <CustomRow :gutter="16">
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Tên IP" prop="ten_ip">
                <CustomInput v-model="form.ten_ip" placeholder="VD: Văn phòng chính" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Địa chỉ IP" prop="dia_chi_ip">
                <CustomInput v-model="form.dia_chi_ip" placeholder="VD: 192.168.1.100" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Trạng thái" prop="trang_thai">
                <CustomSelect v-model="form.trang_thai" style="width: 100%">
                  <CustomOption label="Đang hoạt động" value="active" />
                  <CustomOption label="Không hoạt động" value="inactive" />
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
  createIpDiemDanh,
  deleteIpDiemDanh,
  fetchIpDiemDanh,
  updateIpDiemDanh,
} from '@/api/ipDiemDanh'
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

const emptyForm = () => ({
  ten_ip: '',
  dia_chi_ip: '',
  ghi_chu: '',
  trang_thai: 'active',
})

const form = reactive(emptyForm())

const rules = {
  ten_ip: [{ required: true, message: 'Vui lòng nhập tên IP', trigger: 'blur' }],
  dia_chi_ip: [{ required: true, message: 'Vui lòng nhập địa chỉ IP', trigger: 'blur' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

async function toggleStatus(row, value) {
  const previous = value === 'active' ? 'inactive' : 'active'
  togglingId.value = row.id

  try {
    await updateIpDiemDanh(row.id, {
      ten_ip: row.ten_ip,
      dia_chi_ip: row.dia_chi_ip,
      ghi_chu: row.ghi_chu || null,
      trang_thai: value,
    })
    ElMessage.success(
      value === 'active' ? 'Đã bật IP điểm danh.' : 'Đã tắt IP điểm danh.',
    )
  } catch {
    row.trang_thai = previous
  } finally {
    togglingId.value = null
  }
}

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchIpDiemDanh({
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
    ten_ip: row.ten_ip,
    dia_chi_ip: row.dia_chi_ip,
    ghi_chu: row.ghi_chu || '',
    trang_thai: row.trang_thai || 'active',
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    ten_ip: form.ten_ip.trim(),
    dia_chi_ip: form.dia_chi_ip.trim(),
    ghi_chu: form.ghi_chu?.trim() || null,
    trang_thai: form.trang_thai,
  }

  try {
    if (editingId.value) {
      await updateIpDiemDanh(editingId.value, payload)
      ElMessage.success('Đã cập nhật IP điểm danh.')
    } else {
      await createIpDiemDanh(payload)
      ElMessage.success('Đã thêm IP điểm danh.')
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
  await ElMessageBox.confirm(`Xóa IP "${row.ten_ip}" (${row.dia_chi_ip})?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteIpDiemDanh(row.id)
    ElMessage.success('Đã xóa IP điểm danh.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadItems)
</script>

<style scoped>
.ip-diem-danh {
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
