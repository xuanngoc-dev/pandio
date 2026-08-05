<template>
  <ConfigSettingPage title="Ca làm việc">
    <div class="ca-lam-viec page-list">
      <CustomCard shadow="hover" class="filter-card">
        <CustomRow :gutter="12" class="toolbar">
          <CustomCol :xs="12" :sm="12" :md="8" :lg="8">
            <CustomInput
              v-model="keyword"
              placeholder="Tìm theo tên ca, ghi chú..."
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
          <CustomCol :xs="12" :sm="12" :md="8" :lg="8">
            <CustomSelect
              v-model="trangThaiFilter"
              placeholder="Trạng thái"
              clearable
              style="width: 100%"
              @change="onSearch"
            >
              <CustomOption label="Đang dùng" value="co" />
              <CustomOption label="Không dùng" value="khong" />
            </CustomSelect>
          </CustomCol>
          <CustomCol :xs="12" :sm="12" :md="8" :lg="4">
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
            <span class="card-title">Danh sách ca làm việc</span>
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
            v-if="columnSettings.isColumnVisible('ten_ca')"
            prop="ten_ca"
            label="Tên ca"
            min-width="160"
            show-overflow-tooltip
          />
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('gio_bat_dau')"
            prop="gio_bat_dau"
            label="Giờ bắt đầu"
            min-width="120"
            align="center"
          >
            <template #default="{ row }">
              {{ formatTime(row.gio_bat_dau) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('gio_ket_thuc')"
            prop="gio_ket_thuc"
            label="Giờ kết thúc"
            min-width="120"
            align="center"
          >
            <template #default="{ row }">
              {{ formatTime(row.gio_ket_thuc) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('ghi_chu')"
            prop="ghi_chu"
            label="Ghi chú"
            min-width="200"
            show-overflow-tooltip
          >
            <template #default="{ row }">
              {{ row.ghi_chu || '—' }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('trang_thai')"
            prop="trang_thai"
            label="Trạng thái"
            width="180"
            align="center"
          >
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
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createCaLamViec,
  deleteCaLamViec,
  fetchCaLamViec,
  updateCaLamViec,
} from '@/api/caLamViec'
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
  CustomOption,
  CustomRow,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'
import ConfigSettingPage from './ConfigSettingPage.vue'

const ACTIVE = 'co'
const INACTIVE = 'khong'

const tableColumns = [
  { key: 'ten_ca', label: 'Tên ca' },
  { key: 'gio_bat_dau', label: 'Giờ bắt đầu' },
  { key: 'gio_ket_thuc', label: 'Giờ kết thúc' },
  { key: 'ghi_chu', label: 'Ghi chú' },
  { key: 'trang_thai', label: 'Trạng thái' },
]
const columnSettings = useTableColumns('he-thong.ca-lam-viec', tableColumns)

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const togglingId = ref(null)
const bulkActivating = ref(false)
const bulkDeactivating = ref(false)
const bulkDeleting = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const trangThaiFilter = ref('')

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)

const { selectedCount, onSelectionChange, clearSelection, countByStatus, idsByStatus, selectedIds } =
  useBulkSelection(
    () => true,
    (row) => row.trang_thai,
  )

const bulkActions = computed(() => {
  const activeCount = countByStatus(INACTIVE)
  const inactiveCount = countByStatus(ACTIVE)
  return [
    {
      key: 'activate',
      label: 'Bật',
      type: 'success',
      badge: activeCount,
      badgeType: 'success',
      loading: bulkActivating.value,
      tooltip: activeCount
        ? `Bật ${activeCount} ca đang tắt`
        : 'Chọn ca không dùng để bật',
    },
    {
      key: 'deactivate',
      label: 'Tắt',
      type: 'warning',
      badge: inactiveCount,
      badgeType: 'warning',
      loading: bulkDeactivating.value,
      tooltip: inactiveCount
        ? `Tắt ${inactiveCount} ca đang dùng`
        : 'Chọn ca đang dùng để tắt',
    },
    {
      key: 'delete',
      label: 'Xóa',
      type: 'danger',
      badge: selectedCount.value,
      badgeType: 'danger',
      loading: bulkDeleting.value,
      tooltip: selectedCount.value
        ? `Xóa ${selectedCount.value} ca đã chọn`
        : 'Chọn ca để xóa',
    },
  ]
})

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
  clearSelection()
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

async function onBulkAction(key) {
  if (key === 'activate') await bulkSetStatus(ACTIVE)
  else if (key === 'deactivate') await bulkSetStatus(INACTIVE)
  else if (key === 'delete') await bulkRemove()
}

async function bulkSetStatus(target) {
  const fromStatus = target === ACTIVE ? INACTIVE : ACTIVE
  const ids = idsByStatus(fromStatus)
  if (!ids.length) return

  const label = target === ACTIVE ? 'Bật' : 'Tắt'
  await ElMessageBox.confirm(`${label} ${ids.length} ca đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: label,
    cancelButtonText: 'Hủy',
  })

  const loadingRef = target === ACTIVE ? bulkActivating : bulkDeactivating
  loadingRef.value = true
  try {
    const rows = items.value.filter((item) => ids.includes(item.id))
    await runBulk(ids, async (id) => {
      const row = rows.find((item) => item.id === id)
      await updateCaLamViec(id, {
        ten_ca: row?.ten_ca,
        gio_bat_dau: formatTime(row?.gio_bat_dau),
        gio_ket_thuc: formatTime(row?.gio_ket_thuc),
        ghi_chu: row?.ghi_chu || null,
        trang_thai: target,
      })
    })
    ElMessage.success(`Đã ${label.toLowerCase()} ${ids.length} ca.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    loadingRef.value = false
  }
}

async function bulkRemove() {
  const ids = selectedIds.value
  if (!ids.length) return

  await ElMessageBox.confirm(`Xóa ${ids.length} ca đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteCaLamViec(id))
    ElMessage.success(`Đã xóa ${ids.length} ca.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
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

