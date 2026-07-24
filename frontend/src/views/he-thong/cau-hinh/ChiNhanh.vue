<template>
  <ConfigSettingPage title="Chi nhánh">
    <div class="chi-nhanh">
      <CustomCard shadow="hover" class="filter-card">
        <div class="toolbar">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo tên, địa chỉ, SĐT, email, trưởng chi nhánh..."
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
            <span class="card-title">Danh sách chi nhánh</span>
            <CustomButton type="primary" @click="openCreate">
              <CustomIcon><Plus /></CustomIcon>
              Thêm chi nhánh
            </CustomButton>
          </div>
        </template>

        <CustomTable v-loading="loading" :data="items" stripe style="width: 100%">
          <CustomTableColumn label="STT" width="60" align="center">
            <template #default="{ $index }">
              {{ (page - 1) * perPage + $index + 1 }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="ten_chi_nhanh" label="Tên chi nhánh" min-width="180" />
          <CustomTableColumn prop="dia_chi" label="Địa chỉ" min-width="220" show-overflow-tooltip />
          <CustomTableColumn prop="so_dien_thoai" label="Số điện thoại" min-width="140" />
          <CustomTableColumn prop="email" label="Email" min-width="180" show-overflow-tooltip>
            <template #default="{ row }">
              {{ row.email || '—' }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="truong_chi_nhanh" label="Trưởng chi nhánh" min-width="160" />
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
        :title="editingId ? 'Sửa chi nhánh' : 'Thêm chi nhánh'"
        :width="640"
      >
        <CustomForm ref="formRef" :model="form" :rules="rules">
          <CustomRow :gutter="16">
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Tên chi nhánh" prop="ten_chi_nhanh">
                <CustomInput v-model="form.ten_chi_nhanh" placeholder="VD: Chi nhánh Quận 1" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Số điện thoại" prop="so_dien_thoai">
                <CustomInput v-model="form.so_dien_thoai" placeholder="VD: 0901234567" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Email" prop="email">
                <CustomInput v-model="form.email" placeholder="VD: chinhanh@studio.com" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Trưởng chi nhánh" prop="truong_chi_nhanh">
                <CustomInput v-model="form.truong_chi_nhanh" placeholder="VD: Nguyễn Văn A" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :span="24">
              <CustomFormItem label="Địa chỉ" prop="dia_chi">
                <CustomInput
                  v-model="form.dia_chi"
                  type="textarea"
                  :rows="2"
                  placeholder="Địa chỉ chi nhánh"
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
  createChiNhanh,
  deleteChiNhanh,
  fetchChiNhanh,
  updateChiNhanh,
} from '@/api/chiNhanh'
import {
  CustomButton,
  CustomCard,
  CustomCol,
  CustomDialog,
  CustomForm,
  CustomFormItem,
  CustomIcon,
  CustomInput,
  CustomRow,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'
import ConfigSettingPage from './ConfigSettingPage.vue'

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)

const emptyForm = () => ({
  ten_chi_nhanh: '',
  dia_chi: '',
  so_dien_thoai: '',
  email: '',
  truong_chi_nhanh: '',
})

const form = reactive(emptyForm())

const rules = {
  ten_chi_nhanh: [{ required: true, message: 'Vui lòng nhập tên chi nhánh', trigger: 'blur' }],
  dia_chi: [{ required: true, message: 'Vui lòng nhập địa chỉ', trigger: 'blur' }],
  so_dien_thoai: [{ required: true, message: 'Vui lòng nhập số điện thoại', trigger: 'blur' }],
  email: [{ type: 'email', message: 'Email không hợp lệ', trigger: 'blur' }],
  truong_chi_nhanh: [
    { required: true, message: 'Vui lòng nhập trưởng chi nhánh', trigger: 'blur' },
  ],
}

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchChiNhanh({
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
    ten_chi_nhanh: row.ten_chi_nhanh,
    dia_chi: row.dia_chi,
    so_dien_thoai: row.so_dien_thoai,
    email: row.email || '',
    truong_chi_nhanh: row.truong_chi_nhanh,
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    ten_chi_nhanh: form.ten_chi_nhanh.trim(),
    dia_chi: form.dia_chi.trim(),
    so_dien_thoai: form.so_dien_thoai.trim(),
    email: form.email?.trim() || null,
    truong_chi_nhanh: form.truong_chi_nhanh.trim(),
  }

  try {
    if (editingId.value) {
      await updateChiNhanh(editingId.value, payload)
      ElMessage.success('Đã cập nhật chi nhánh.')
    } else {
      await createChiNhanh(payload)
      ElMessage.success('Đã thêm chi nhánh.')
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
  await ElMessageBox.confirm(`Xóa chi nhánh "${row.ten_chi_nhanh}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteChiNhanh(row.id)
    ElMessage.success('Đã xóa chi nhánh.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadItems)
</script>

<style scoped>
.chi-nhanh {
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
</style>
