<template>
  <div class="nha-cung-cap-trang-phuc page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="12" :lg="16">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo mã, tên, địa chỉ, SĐT, email..."
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
        <CustomCol :xs="12" :sm="12" :md="6" :lg="4">
          <CustomButton type="primary" plain @click="onSearch">
            Tìm kiếm
          </CustomButton>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách nhà cung cấp trang phục</span>
          <BulkActionBar :actions="bulkActions" @action="onBulkAction">
            <TableColumnConfig :settings="columnSettings" />
            <CustomTooltip content="Thêm mới" placement="top">
              <CustomButton type="primary" @click="openCreate">
                Thêm
              </CustomButton>
            </CustomTooltip>
          </BulkActionBar>
        </div>
      </template>

      <CustomTable
        :column-settings="columnSettings"
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
          v-if="columnSettings.isColumnVisible('ma_nha_cung_cap')"
          prop="ma_nha_cung_cap"
          label="Mã"
          min-width="200"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ten_nha_cung_cap')"
          prop="ten_nha_cung_cap"
          label="Tên nhà cung cấp"
          min-width="200"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('dia_chi')"
          prop="dia_chi"
          label="Địa chỉ"
          min-width="220"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.dia_chi || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('so_dien_thoai')"
          prop="so_dien_thoai"
          label="Số điện thoại"
          width="140"
        >
          <template #default="{ row }">
            {{ row.so_dien_thoai || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('email')"
          prop="email"
          label="Email"
          min-width="180"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.email || '—' }}
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
      :title="editingId ? 'Sửa nhà cung cấp trang phục' : 'Thêm nhà cung cấp trang phục'"
      :width="960"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Mã nhà cung cấp" prop="ma_nha_cung_cap">
              <CustomInput
                v-model="form.ma_nha_cung_cap"
                :disabled="!!editingId"
                placeholder="VD: NCC01"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Tên nhà cung cấp" prop="ten_nha_cung_cap">
              <CustomInput v-model="form.ten_nha_cung_cap" placeholder="Tên nhà cung cấp" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Số điện thoại" prop="so_dien_thoai">
              <CustomInput v-model="form.so_dien_thoai" placeholder="Số điện thoại (tuỳ chọn)" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Email" prop="email">
              <CustomInput v-model="form.email" placeholder="Email (tuỳ chọn)" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Địa chỉ" prop="dia_chi">
              <CustomInput v-model="form.dia_chi" placeholder="Địa chỉ (tuỳ chọn)" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Ghi chú" prop="ghi_chu">
              <CustomInput v-model="form.ghi_chu" placeholder="Ghi chú (tuỳ chọn)" />
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
  createNhaCungCapTrangPhuc,
  deleteNhaCungCapTrangPhuc,
  fetchNhaCungCapTrangPhuc,
  updateNhaCungCapTrangPhuc,
} from '@/api/nhaCungCapTrangPhuc'
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
  { key: 'ma_nha_cung_cap', label: 'Mã' },
  { key: 'ten_nha_cung_cap', label: 'Tên nhà cung cấp' },
  { key: 'dia_chi', label: 'Địa chỉ' },
  { key: 'so_dien_thoai', label: 'Số điện thoại' },
  { key: 'email', label: 'Email' },
]
const columnSettings = useTableColumns('van-hanh-cuoi.nha-cung-cap-trang-phuc', tableColumns)

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
      ? `Xóa ${selectedCount.value} nhà cung cấp đã chọn`
      : 'Chọn nhà cung cấp để xóa',
  },
])

const emptyForm = () => ({
  ma_nha_cung_cap: '',
  ten_nha_cung_cap: '',
  dia_chi: '',
  so_dien_thoai: '',
  email: '',
  ghi_chu: '',
})

const form = reactive(emptyForm())

const rules = {
  ma_nha_cung_cap: [{ required: true, message: 'Vui lòng nhập mã nhà cung cấp', trigger: 'blur' }],
  ten_nha_cung_cap: [
    { required: true, message: 'Vui lòng nhập tên nhà cung cấp', trigger: 'blur' },
  ],
  email: [{ type: 'email', message: 'Email không hợp lệ', trigger: 'blur' }],
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchNhaCungCapTrangPhuc({
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
    ma_nha_cung_cap: row.ma_nha_cung_cap,
    ten_nha_cung_cap: row.ten_nha_cung_cap,
    dia_chi: row.dia_chi || '',
    so_dien_thoai: row.so_dien_thoai || '',
    email: row.email || '',
    ghi_chu: row.ghi_chu || '',
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    ma_nha_cung_cap: form.ma_nha_cung_cap.trim(),
    ten_nha_cung_cap: form.ten_nha_cung_cap.trim(),
    dia_chi: form.dia_chi?.trim() || null,
    so_dien_thoai: form.so_dien_thoai?.trim() || null,
    email: form.email?.trim() || null,
    ghi_chu: form.ghi_chu?.trim() || null,
  }

  try {
    if (editingId.value) {
      await updateNhaCungCapTrangPhuc(editingId.value, payload)
      ElMessage.success('Đã cập nhật nhà cung cấp trang phục.')
    } else {
      await createNhaCungCapTrangPhuc(payload)
      ElMessage.success('Đã thêm nhà cung cấp trang phục.')
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

  await ElMessageBox.confirm(`Xóa ${ids.length} nhà cung cấp đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteNhaCungCapTrangPhuc(id))
    ElMessage.success(`Đã xóa ${ids.length} nhà cung cấp.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa nhà cung cấp "${row.ten_nha_cung_cap}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteNhaCungCapTrangPhuc(row.id)
    ElMessage.success('Đã xóa nhà cung cấp trang phục.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadItems)
</script>
