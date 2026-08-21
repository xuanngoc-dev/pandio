<template>
  <div class="loai-thong-bao page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="8" :lg="10">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo mã, tên, icon..."
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
        <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
          <CustomSelect
            v-model="trangThaiFilter"
            placeholder="Trạng thái"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption label="Đang sử dụng" value="dang_su_dung" />
            <CustomOption label="Ngừng sử dụng" value="ngung_su_dung" />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="8" :lg="4">
          <CustomButton type="primary" plain @click="onSearch">
            Tìm kiếm
          </CustomButton>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách loại thông báo</span>
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
          v-if="columnSettings.isColumnVisible('ma_loai_thong_bao')"
          prop="ma_loai_thong_bao"
          label="Mã"
          width="180"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ten_loai_thong_bao')"
          prop="ten_loai_thong_bao"
          label="Tên loại thông báo"
          min-width="200"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('icon')"
          prop="icon"
          label="Icon"
          min-width="180"
        >
          <template #default="{ row }">
            <div v-if="row.icon" class="icon-cell">
              <el-icon :size="18" class="icon-preview">
                <component :is="resolveIcon(row.icon)" />
              </el-icon>
              <code class="icon-code">{{ row.icon }}</code>
            </div>
            <span v-else>—</span>
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
                active-value="dang_su_dung"
                inactive-value="ngung_su_dung"
                :loading="togglingId === row.id"
                :disabled="togglingId === row.id"
                :before-change="() => toggleStatus(row)"
              />
              <span
                class="status-label"
                :class="row.trang_thai === ACTIVE ? 'is-active' : 'is-inactive'"
              >
                {{ row.trang_thai === ACTIVE ? 'Đang sử dụng' : 'Ngừng sử dụng' }}
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
      :title="editingId ? 'Sửa loại thông báo' : 'Thêm loại thông báo'"
      :width="640"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Mã loại thông báo" prop="ma_loai_thong_bao">
              <CustomInput
                v-model="form.ma_loai_thong_bao"
                :disabled="!!editingId"
                placeholder="VD: deal_updated"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Tên loại thông báo" prop="ten_loai_thong_bao">
              <CustomInput v-model="form.ten_loai_thong_bao" placeholder="Tên loại thông báo" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Icon" prop="icon">
              <div class="icon-input-row">
                <el-icon :size="20" class="icon-preview">
                  <component :is="resolveIcon(form.icon)" />
                </el-icon>
                <CustomInput v-model="form.icon" placeholder="VD: handshake, clock..." />
              </div>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="form.trang_thai" style="width: 100%">
                <CustomOption label="Đang sử dụng" value="dang_su_dung" />
                <CustomOption label="Ngừng sử dụng" value="ngung_su_dung" />
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
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import * as Icons from '@element-plus/icons-vue'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createDanhMucLoaiThongBao,
  deleteDanhMucLoaiThongBao,
  fetchDanhMucLoaiThongBao,
  updateDanhMucLoaiThongBao,
} from '@/api/danhMucLoaiThongBao'
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

const ACTIVE = 'dang_su_dung'
const INACTIVE = 'ngung_su_dung'

/** Map tên icon lucide/kebab → Element Plus Icons gần nhất */
const ICON_ALIASES = {
  handshake: 'Opportunity',
  'clipboard-list': 'List',
  'message-circle': 'ChatDotRound',
  'user-plus': 'User',
  'calendar-off': 'Calendar',
  clock: 'Clock',
  'file-check': 'DocumentChecked',
  wallet: 'Wallet',
  megaphone: 'Promotion',
  'at-sign': 'Message',
}

function toPascalCase(name) {
  return String(name || '')
    .trim()
    .split(/[-_\s]+/)
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('')
}

function resolveIcon(name) {
  if (!name) return Icons.Bell
  const raw = String(name).trim()
  if (Icons[raw]) return Icons[raw]
  const alias = ICON_ALIASES[raw.toLowerCase()]
  if (alias && Icons[alias]) return Icons[alias]
  const pascal = toPascalCase(raw)
  return Icons[pascal] || Icons.Bell
}

const tableColumns = [
  { key: 'ma_loai_thong_bao', label: 'Mã' },
  { key: 'ten_loai_thong_bao', label: 'Tên loại thông báo' },
  { key: 'icon', label: 'Icon' },
  { key: 'trang_thai', label: 'Trạng thái' },
]
const columnSettings = useTableColumns('he-thong.loai-thong-bao', tableColumns)

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
const trangThaiFilter = ref(null)

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
        ? `Bật ${activeCount} loại đang ngừng`
        : 'Chọn loại ngừng sử dụng để bật',
    },
    {
      key: 'deactivate',
      label: 'Tắt',
      type: 'warning',
      badge: inactiveCount,
      badgeType: 'warning',
      loading: bulkDeactivating.value,
      tooltip: inactiveCount
        ? `Tắt ${inactiveCount} loại đang dùng`
        : 'Chọn loại đang sử dụng để tắt',
    },
    {
      key: 'delete',
      label: 'Xóa',
      type: 'danger',
      badge: selectedCount.value,
      badgeType: 'danger',
      loading: bulkDeleting.value,
      tooltip: selectedCount.value
        ? `Xóa ${selectedCount.value} loại đã chọn`
        : 'Chọn loại thông báo để xóa',
    },
  ]
})

const emptyForm = () => ({
  ma_loai_thong_bao: '',
  ten_loai_thong_bao: '',
  icon: '',
  trang_thai: ACTIVE,
})

const form = reactive(emptyForm())

const rules = {
  ma_loai_thong_bao: [{ required: true, message: 'Vui lòng nhập mã loại thông báo', trigger: 'blur' }],
  ten_loai_thong_bao: [
    { required: true, message: 'Vui lòng nhập tên loại thông báo', trigger: 'blur' },
  ],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

function statusPayload(row, trangThai) {
  return {
    ma_loai_thong_bao: row.ma_loai_thong_bao,
    ten_loai_thong_bao: row.ten_loai_thong_bao,
    icon: row.icon || null,
    trang_thai: trangThai,
  }
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchDanhMucLoaiThongBao({
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
    ma_loai_thong_bao: row.ma_loai_thong_bao,
    ten_loai_thong_bao: row.ten_loai_thong_bao,
    icon: row.icon || '',
    trang_thai: row.trang_thai,
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    ma_loai_thong_bao: form.ma_loai_thong_bao.trim(),
    ten_loai_thong_bao: form.ten_loai_thong_bao.trim(),
    icon: form.icon?.trim() || null,
    trang_thai: form.trang_thai,
  }

  try {
    if (editingId.value) {
      await updateDanhMucLoaiThongBao(editingId.value, payload)
      ElMessage.success('Đã cập nhật loại thông báo.')
    } else {
      await createDanhMucLoaiThongBao(payload)
      ElMessage.success('Đã thêm loại thông báo.')
    }
    dialogVisible.value = false
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
}

async function toggleStatus(row) {
  if (!row?.id) return false

  const value = row.trang_thai === ACTIVE ? INACTIVE : ACTIVE

  togglingId.value = row.id
  try {
    await updateDanhMucLoaiThongBao(row.id, statusPayload(row, value))
    row.trang_thai = value
    ElMessage.success('Đã cập nhật trạng thái.')
    return true
  } catch {
    return false
  } finally {
    togglingId.value = null
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
  await ElMessageBox.confirm(`${label} ${ids.length} loại thông báo đã chọn?`, 'Xác nhận', {
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
      await updateDanhMucLoaiThongBao(id, statusPayload(row, target))
    })
    ElMessage.success(`Đã ${label.toLowerCase()} ${ids.length} loại thông báo.`)
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

  await ElMessageBox.confirm(`Xóa ${ids.length} loại thông báo đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteDanhMucLoaiThongBao(id))
    ElMessage.success(`Đã xóa ${ids.length} loại thông báo.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa loại thông báo "${row.ten_loai_thong_bao}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteDanhMucLoaiThongBao(row.id)
    ElMessage.success('Đã xóa loại thông báo.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadItems)
</script>

<style scoped lang="scss">
.icon-cell {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.icon-input-row {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
}

.icon-preview {
  flex-shrink: 0;
  color: var(--el-color-primary);
}

.icon-code {
  font-size: 12px;
  padding: 2px 6px;
  border-radius: 4px;
  background: var(--el-fill-color-light);
  color: var(--el-text-color-regular);
}
</style>
