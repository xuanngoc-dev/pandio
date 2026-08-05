<template>
  <ConfigSettingPage title="IP điểm danh">
    <div class="ip-diem-danh page-list">
      <CustomCard shadow="hover" class="filter-card">
        <CustomRow :gutter="12" class="toolbar">
          <CustomCol :xs="12" :sm="12" :md="8" :lg="8">
            <CustomInput
              v-model="keyword"
              placeholder="Tìm theo tên IP, địa chỉ IP, ghi chú..."
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
              <CustomOption label="Đang hoạt động" value="active" />
              <CustomOption label="Không hoạt động" value="inactive" />
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
            <span class="card-title">Danh sách IP điểm danh</span>
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
            v-if="columnSettings.isColumnVisible('ten_ip')"
            prop="ten_ip"
            label="Tên IP"
            min-width="160"
          />
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('dia_chi_ip')"
            prop="dia_chi_ip"
            label="Địa chỉ IP"
            min-width="160"
          />
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
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createIpDiemDanh,
  deleteIpDiemDanh,
  fetchIpDiemDanh,
  updateIpDiemDanh,
} from '@/api/ipDiemDanh'
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

const ACTIVE = 'active'
const INACTIVE = 'inactive'

const tableColumns = [
  { key: 'ten_ip', label: 'Tên IP' },
  { key: 'dia_chi_ip', label: 'Địa chỉ IP' },
  { key: 'ghi_chu', label: 'Ghi chú' },
  { key: 'trang_thai', label: 'Trạng thái' },
]
const columnSettings = useTableColumns('he-thong.ip-diem-danh', tableColumns)

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
        ? `Bật ${activeCount} IP đang tắt`
        : 'Chọn IP không hoạt động để bật',
    },
    {
      key: 'deactivate',
      label: 'Tắt',
      type: 'warning',
      badge: inactiveCount,
      badgeType: 'warning',
      loading: bulkDeactivating.value,
      tooltip: inactiveCount
        ? `Tắt ${inactiveCount} IP đang hoạt động`
        : 'Chọn IP đang hoạt động để tắt',
    },
    {
      key: 'delete',
      label: 'Xóa',
      type: 'danger',
      badge: selectedCount.value,
      badgeType: 'danger',
      loading: bulkDeleting.value,
      tooltip: selectedCount.value
        ? `Xóa ${selectedCount.value} IP đã chọn`
        : 'Chọn IP để xóa',
    },
  ]
})

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
  clearSelection()
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
  await ElMessageBox.confirm(`${label} ${ids.length} IP đã chọn?`, 'Xác nhận', {
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
      await updateIpDiemDanh(id, {
        ten_ip: row?.ten_ip,
        dia_chi_ip: row?.dia_chi_ip,
        ghi_chu: row?.ghi_chu || null,
        trang_thai: target,
      })
    })
    ElMessage.success(`Đã ${label.toLowerCase()} ${ids.length} IP.`)
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

  await ElMessageBox.confirm(`Xóa ${ids.length} IP đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteIpDiemDanh(id))
    ElMessage.success(`Đã xóa ${ids.length} IP.`)
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

