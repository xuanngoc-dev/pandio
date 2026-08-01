<template>
  <div class="danh-muc-trang-phuc">
    <CustomCard shadow="hover" class="filter-card">
      <div class="toolbar">
        <CustomInput
          v-model="keyword"
          placeholder="Tìm theo mã, tên danh mục..."
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
          <span class="card-title">Danh sách danh mục trang phục</span>
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
        :data="items"
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
          v-if="columnSettings.isColumnVisible('ma_danh_muc')"
          prop="ma_danh_muc"
          label="Mã"
          width="120"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ten_danh_muc')"
          prop="ten_danh_muc"
          label="Tên danh mục"
          min-width="200"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('mo_ta')"
          prop="mo_ta"
          label="Mô tả"
          min-width="260"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.mo_ta || '—' }}
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
      :title="editingId ? 'Sửa danh mục trang phục' : 'Thêm danh mục trang phục'"
      :width="640"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Mã danh mục" prop="ma_danh_muc">
              <CustomInput
                v-model="form.ma_danh_muc"
                :disabled="!!editingId"
                placeholder="VD: DMTP01"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Tên danh mục" prop="ten_danh_muc">
              <CustomInput v-model="form.ten_danh_muc" placeholder="Tên danh mục trang phục" />
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
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createDanhMucTrangPhuc,
  deleteDanhMucTrangPhuc,
  fetchDanhMucTrangPhuc,
  updateDanhMucTrangPhuc,
} from '@/api/danhMucTrangPhuc'
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

const tableColumns = [
  { key: 'ma_danh_muc', label: 'Mã' },
  { key: 'ten_danh_muc', label: 'Tên danh mục' },
  { key: 'mo_ta', label: 'Mô tả' },
]
const columnSettings = useTableColumns('van-hanh-cuoi.danh-muc-trang-phuc', tableColumns)

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
      ? `Xóa ${selectedCount.value} danh mục đã chọn`
      : 'Chọn danh mục để xóa',
  },
])

const emptyForm = () => ({
  ma_danh_muc: '',
  ten_danh_muc: '',
  mo_ta: '',
})

const form = reactive(emptyForm())

const rules = {
  ma_danh_muc: [{ required: true, message: 'Vui lòng nhập mã danh mục', trigger: 'blur' }],
  ten_danh_muc: [{ required: true, message: 'Vui lòng nhập tên danh mục', trigger: 'blur' }],
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchDanhMucTrangPhuc({
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
    ma_danh_muc: row.ma_danh_muc,
    ten_danh_muc: row.ten_danh_muc,
    mo_ta: row.mo_ta || '',
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    ma_danh_muc: form.ma_danh_muc.trim(),
    ten_danh_muc: form.ten_danh_muc.trim(),
    mo_ta: form.mo_ta?.trim() || null,
  }

  try {
    if (editingId.value) {
      await updateDanhMucTrangPhuc(editingId.value, payload)
      ElMessage.success('Đã cập nhật danh mục trang phục.')
    } else {
      await createDanhMucTrangPhuc(payload)
      ElMessage.success('Đã thêm danh mục trang phục.')
    }
    dialogVisible.value = false
    await loadItems()
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

  await ElMessageBox.confirm(`Xóa ${ids.length} danh mục đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteDanhMucTrangPhuc(id))
    ElMessage.success(`Đã xóa ${ids.length} danh mục.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa danh mục "${row.ten_danh_muc}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteDanhMucTrangPhuc(row.id)
    ElMessage.success('Đã xóa danh mục trang phục.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadItems)
</script>

<style scoped lang="scss">
.danh-muc-trang-phuc {
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
