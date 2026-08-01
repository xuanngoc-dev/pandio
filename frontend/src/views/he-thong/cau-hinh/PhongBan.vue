<template>
  <ConfigSettingPage title="Phòng ban">
    <div class="phong-ban">
      <CustomCard shadow="hover" class="filter-card">
        <div class="toolbar">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo mã, tên, trưởng phòng..."
            clearable
            style="max-width: 300px"
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
            <span class="card-title">Danh sách phòng ban</span>
            <BulkActionBar :actions="bulkActions" @action="onBulkAction">
              <TableColumnConfig :settings="columnSettings" />
              <CustomTooltip content="Thêm mới" placement="top">
                <CustomButton type="primary" @click="openCreate">
                  <CustomIcon><Plus /></CustomIcon>
                  Thêm
                </CustomButton>
              </CustomTooltip>
            </BulkActionBar>
          </div>
        </template>

        <CustomTable
          v-loading="loading"
          :data="departments"
          stripe
          row-key="id"
          style="width: 100%"
          @selection-change="onSelectionChange"
        >
          <CustomTableColumn type="selection" width="48" align="center" />
          <CustomTableColumn label="STT" width="60" align="center">
            <template #default="{ $index }">
              {{ (page - 1) * perPage + $index + 1 }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('ma_phong_ban')"
            prop="ma_phong_ban"
            label="Mã"
            width="120"
          />
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('ten_phong_ban')"
            prop="ten_phong_ban"
            label="Tên phòng ban"
            min-width="180"
          />
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('truong_phong')"
            prop="truong_phong"
            label="Trưởng phòng"
            min-width="160"
          >
            <template #default="{ row }">
              {{ row.truong_phong || '—' }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('mo_ta')"
            prop="mo_ta"
            label="Mô tả"
            min-width="200"
            show-overflow-tooltip
          >
            <template #default="{ row }">
              {{ row.mo_ta || '—' }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('ghi_chu')"
            prop="ghi_chu"
            label="Ghi chú"
            min-width="160"
            show-overflow-tooltip
          >
            <template #default="{ row }">
              {{ row.ghi_chu || '—' }}
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
          @change="loadDepartments"
        />
      </CustomCard>

      <CustomDialog
        v-model="dialogVisible"
        :title="editingId ? 'Sửa phòng ban' : 'Thêm phòng ban'"
        :width="780"
      >
        <CustomForm ref="formRef" :model="form" :rules="rules">
          <CustomRow :gutter="16">
            <CustomCol :xs="24" :sm="12" :md="8">
              <CustomFormItem label="Mã" prop="ma_phong_ban">
                <CustomInput
                  v-model="form.ma_phong_ban"
                  :disabled="!!editingId"
                  placeholder="VD: PB01"
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12" :md="8">
              <CustomFormItem label="Tên" prop="ten_phong_ban">
                <CustomInput v-model="form.ten_phong_ban" placeholder="Tên phòng ban" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12" :md="8">
              <CustomFormItem label="Trưởng phòng" prop="truong_phong">
                <CustomInput v-model="form.truong_phong" placeholder="Để trống nếu chưa có" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12" :md="12">
              <CustomFormItem label="Mô tả" prop="mo_ta">
                <CustomInput
                  v-model="form.mo_ta"
                  type="textarea"
                  :rows="2"
                  placeholder="Mô tả (tuỳ chọn)"
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12" :md="12">
              <CustomFormItem label="Ghi chú" prop="ghi_chu">
                <CustomInput
                  v-model="form.ghi_chu"
                  type="textarea"
                  :rows="2"
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
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createPhongBan,
  deletePhongBan,
  fetchPhongBan,
  updatePhongBan,
} from '@/api/phongBan'
import BulkActionBar from '@/components/BulkActionBar.vue'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { runBulk, useBulkSelection } from '@/composables/useBulkSelection'
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
  CustomRow,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'
import ConfigSettingPage from './ConfigSettingPage.vue'

const tableColumns = [
  { key: 'ma_phong_ban', label: 'Mã' },
  { key: 'ten_phong_ban', label: 'Tên phòng ban' },
  { key: 'truong_phong', label: 'Trưởng phòng' },
  { key: 'mo_ta', label: 'Mô tả' },
  { key: 'ghi_chu', label: 'Ghi chú' },
]
const columnSettings = useTableColumns('he-thong.phong-ban', tableColumns)

const departments = ref([])
const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)
const bulkDeleting = ref(false)

const { selectedCount, onSelectionChange, clearSelection, selectedIds } = useBulkSelection()

const bulkActions = computed(() => [
  {
    key: 'delete',
    label: 'Xóa',
    type: 'danger',
    badge: selectedCount.value,
    badgeType: 'danger',
    loading: bulkDeleting.value,
    tooltip: selectedCount.value
      ? `Xóa ${selectedCount.value} phòng ban đã chọn`
      : 'Chọn phòng ban để xóa',
  },
])

const emptyForm = () => ({
  ma_phong_ban: '',
  ten_phong_ban: '',
  truong_phong: '',
  mo_ta: '',
  ghi_chu: '',
})

const form = reactive(emptyForm())

const rules = {
  ma_phong_ban: [{ required: true, message: 'Vui lòng nhập mã phòng ban', trigger: 'blur' }],
  ten_phong_ban: [{ required: true, message: 'Vui lòng nhập tên phòng ban', trigger: 'blur' }],
}

async function loadDepartments() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchPhongBan({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
    })
    departments.value = data.data || []
    total.value = data.total || 0
    page.value = data.current_page || page.value
  } catch {
    departments.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

function onSearch() {
  page.value = 1
  loadDepartments()
}

function openCreate() {
  editingId.value = null
  Object.assign(form, emptyForm())
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  Object.assign(form, {
    ma_phong_ban: row.ma_phong_ban,
    ten_phong_ban: row.ten_phong_ban,
    truong_phong: row.truong_phong || '',
    mo_ta: row.mo_ta || '',
    ghi_chu: row.ghi_chu || '',
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    ma_phong_ban: form.ma_phong_ban.trim(),
    ten_phong_ban: form.ten_phong_ban.trim(),
    truong_phong: form.truong_phong?.trim() || null,
    mo_ta: form.mo_ta?.trim() || null,
    ghi_chu: form.ghi_chu?.trim() || null,
  }

  try {
    if (editingId.value) {
      await updatePhongBan(editingId.value, payload)
      ElMessage.success('Đã cập nhật phòng ban.')
    } else {
      await createPhongBan(payload)
      ElMessage.success('Đã thêm phòng ban.')
    }
    dialogVisible.value = false
    await loadDepartments()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
}

async function onBulkAction(key) {
  if (key === 'delete') await bulkRemove()
}

async function bulkRemove() {
  const ids = selectedIds.value
  if (!ids.length) return

  await ElMessageBox.confirm(`Xóa ${ids.length} phòng ban đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deletePhongBan(id))
    ElMessage.success(`Đã xóa ${ids.length} phòng ban.`)
    await loadDepartments()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa phòng ban "${row.ten_phong_ban}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deletePhongBan(row.id)
    ElMessage.success('Đã xóa phòng ban.')
    await loadDepartments()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadDepartments)
</script>

<style scoped>
.phong-ban {
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
