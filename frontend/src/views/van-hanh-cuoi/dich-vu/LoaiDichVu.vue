<template>
  <div class="loai-dich-vu">
    <CustomCard shadow="hover" class="filter-card">
      <div class="toolbar">
        <CustomInput
          v-model="keyword"
          placeholder="Tìm theo tên loại dịch vụ..."
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
          v-model="filterTrangThai"
          placeholder="Trạng thái"
          clearable
          style="width: 180px"
          @change="onSearch"
        >
          <CustomOption label="Đang hoạt động" value="dang_hoat_dong" />
          <CustomOption label="Ngừng hoạt động" value="ngung_hoat_dong" />
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
          <span class="card-title">Danh sách loại dịch vụ</span>
          <CustomButton type="primary" @click="openCreate">
            <CustomIcon><Plus /></CustomIcon>
            Thêm loại dịch vụ
          </CustomButton>
        </div>
      </template>

      <CustomTable v-loading="loading" :data="items" stripe style="width: 100%">
        <CustomTableColumn label="STT" width="60" align="center">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="ten_dich_vu" label="Tên loại dịch vụ" min-width="220" />
        <CustomTableColumn prop="mo_ta" label="Mô tả" min-width="260" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.mo_ta || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Trạng thái" width="150" align="center">
          <template #default="{ row }">
            <CustomTag :type="row.trang_thai === 'dang_hoat_dong' ? 'success' : 'info'">
              {{ trangThaiLabel(row.trang_thai) }}
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
        @change="loadItems"
      />
    </CustomCard>

    <CustomDialog
      v-model="dialogVisible"
      :title="editingId ? 'Sửa loại dịch vụ' : 'Thêm loại dịch vụ'"
      :width="640"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules">
        <CustomRow :gutter="16">
          <CustomCol :span="24">
            <CustomFormItem label="Tên loại dịch vụ" prop="ten_dich_vu">
              <CustomInput v-model="form.ten_dich_vu" placeholder="Nhập tên loại dịch vụ" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :span="24">
            <CustomFormItem label="Mô tả" prop="mo_ta">
              <CustomInput
                v-model="form.mo_ta"
                type="textarea"
                :rows="3"
                placeholder="Mô tả (tuỳ chọn)"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="form.trang_thai" style="width: 100%">
                <CustomOption label="Đang hoạt động" value="dang_hoat_dong" />
                <CustomOption label="Ngừng hoạt động" value="ngung_hoat_dong" />
              </CustomSelect>
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
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createDichVuLoaiDichVu,
  deleteDichVuLoaiDichVu,
  fetchDichVuLoaiDichVu,
  updateDichVuLoaiDichVu,
} from '@/api/dichVuLoaiDichVu'
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

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const filterTrangThai = ref('')

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)

const emptyForm = () => ({
  ten_dich_vu: '',
  mo_ta: '',
  trang_thai: 'dang_hoat_dong',
})

const form = reactive(emptyForm())

const rules = {
  ten_dich_vu: [{ required: true, message: 'Vui lòng nhập tên loại dịch vụ', trigger: 'blur' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

function trangThaiLabel(value) {
  return value === 'dang_hoat_dong' ? 'Đang hoạt động' : 'Ngừng hoạt động'
}

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchDichVuLoaiDichVu({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      trang_thai: filterTrangThai.value || undefined,
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
    ten_dich_vu: row.ten_dich_vu,
    mo_ta: row.mo_ta || '',
    trang_thai: row.trang_thai,
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    ten_dich_vu: form.ten_dich_vu.trim(),
    mo_ta: form.mo_ta?.trim() || null,
    trang_thai: form.trang_thai,
  }

  try {
    if (editingId.value) {
      await updateDichVuLoaiDichVu(editingId.value, payload)
      ElMessage.success('Đã cập nhật loại dịch vụ.')
    } else {
      await createDichVuLoaiDichVu(payload)
      ElMessage.success('Đã thêm loại dịch vụ.')
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
  await ElMessageBox.confirm(`Xóa loại dịch vụ "${row.ten_dich_vu}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteDichVuLoaiDichVu(row.id)
    ElMessage.success('Đã xóa loại dịch vụ.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadItems)
</script>

<style scoped lang="scss">
.loai-dich-vu {
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
