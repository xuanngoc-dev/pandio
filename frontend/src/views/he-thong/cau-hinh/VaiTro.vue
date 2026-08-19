<template>
  <ConfigSettingPage title="Vai trò (chức danh nhân sự)">
    <div class="vai-tro page-list">
      <CustomCard shadow="hover" class="filter-card">
        <CustomRow :gutter="12" class="toolbar">
          <CustomCol :xs="12" :sm="12" :md="12" :lg="16">
            <CustomInput
              v-model="keyword"
              placeholder="Tìm theo tên vai trò, ghi chú..."
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
              <CustomIcon><Search /></CustomIcon>
              Tìm kiếm
            </CustomButton>
          </CustomCol>
        </CustomRow>
      </CustomCard>

      <CustomCard shadow="hover" class="table-card">
        <template #header>
          <div class="card-header">
            <span class="card-title">Danh sách vai trò</span>
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
            v-if="columnSettings.isColumnVisible('ten_vai_tro')"
            prop="ten_vai_tro"
            label="Tên vai trò"
            min-width="180"
            show-overflow-tooltip
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
            v-if="columnSettings.isColumnVisible('danh_sach_menu')"
            label="Menu"
            width="100"
            align="center"
          >
            <template #default="{ row }">
              {{ countList(row.danh_sach_menu) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('cau_hinh')"
            label="Cấu hình"
            width="110"
            align="center"
          >
            <template #default="{ row }">
              {{ countList(row.cau_hinh) }}
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
        :title="editingId ? 'Sửa vai trò' : 'Thêm vai trò'"
        :width="860"
      >
        <CustomForm ref="formRef" :model="form" :rules="rules">
          <CustomRow :gutter="16">
            <CustomCol :span="24">
              <CustomFormItem label="Tên vai trò" prop="ten_vai_tro">
                <CustomInput
                  v-model="form.ten_vai_tro"
                  placeholder="VD: Quản lý, Nhân viên sale, Kế toán..."
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :span="24">
              <CustomFormItem label="Ghi chú" prop="ghi_chu">
                <CustomInput
                  v-model="form.ghi_chu"
                  type="textarea"
                  :rows="2"
                  placeholder="Ghi chú (tuỳ chọn)"
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Danh sách menu" prop="danh_sach_menu">
                <div class="tree-panel">
                  <div class="tree-panel__actions">
                    <CustomButton link type="primary" @click="checkAllMenu">Chọn tất cả</CustomButton>
                    <CustomButton link @click="clearAllMenu">Bỏ chọn</CustomButton>
                  </div>
                  <el-tree
                    ref="menuTreeRef"
                    :data="menuTreeData"
                    show-checkbox
                    node-key="id"
                    :default-expand-all="true"
                    :props="{ label: 'label', children: 'children' }"
                    @check-change="syncMenuChecked"
                  />
                </div>
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="12">
              <CustomFormItem label="Cấu hình được phép" prop="cau_hinh">
                <div class="tree-panel">
                  <div class="tree-panel__actions">
                    <CustomButton link type="primary" @click="checkAllCauHinh">Chọn tất cả</CustomButton>
                    <CustomButton link @click="clearAllCauHinh">Bỏ chọn</CustomButton>
                  </div>
                  <el-tree
                    ref="cauHinhTreeRef"
                    :data="cauHinhTreeData"
                    show-checkbox
                    node-key="id"
                    :default-expand-all="true"
                    :props="{ label: 'label', children: 'children' }"
                    @check-change="syncCauHinhChecked"
                  />
                </div>
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
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createVaiTro,
  deleteVaiTro,
  fetchVaiTro,
  updateVaiTro,
} from '@/api/vaiTro'
import { cauHinhSections } from '@/data/cauHinhQuanTri'
import menuGroups from '@/data/menu.json'
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
  { key: 'ten_vai_tro', label: 'Tên vai trò' },
  { key: 'ghi_chu', label: 'Ghi chú' },
  { key: 'danh_sach_menu', label: 'Menu' },
  { key: 'cau_hinh', label: 'Cấu hình' },
]
const columnSettings = useTableColumns('he-thong.vai-tro', tableColumns)

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
const menuTreeRef = ref(null)
const cauHinhTreeRef = ref(null)
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
      ? `Xóa ${selectedCount.value} vai trò đã chọn`
      : 'Chọn vai trò để xóa',
  },
])

const menuTreeData = menuGroups.map((group, groupIndex) => ({
  id: `group:${groupIndex}`,
  label: group.header || 'Chung',
  children: (group.items || []).flatMap((item) => {
    if (item.children?.length) {
      return [{
        id: `submenu:${item.index}`,
        label: item.title,
        children: item.children.map((child) => ({
          id: child.index,
          label: child.title,
        })),
      }]
    }
    return [{
      id: item.index,
      label: item.title,
    }]
  }),
}))

const cauHinhTreeData = cauHinhSections.map((section) => ({
  id: `section:${section.key}`,
  label: section.title,
  children: (section.items || []).map((item) => ({
    id: item.routeName,
    label: item.label,
  })),
}))

const allMenuLeafIds = menuTreeData.flatMap((g) =>
  (g.children || []).flatMap((node) =>
    node.children?.length
      ? node.children.map((c) => c.id)
      : [node.id],
  ),
)
const allCauHinhLeafIds = cauHinhTreeData.flatMap((s) => (s.children || []).map((c) => c.id))

const emptyForm = () => ({
  ten_vai_tro: '',
  ghi_chu: '',
  danh_sach_menu: [],
  cau_hinh: [],
})

const form = reactive(emptyForm())

const rules = {
  ten_vai_tro: [{ required: true, message: 'Vui lòng nhập tên vai trò', trigger: 'blur' }],
}

function countList(value) {
  return Array.isArray(value) ? value.length : 0
}

function onlyLeafKeys(keys, leafIds) {
  const leafSet = new Set(leafIds)
  return (keys || []).filter((key) => leafSet.has(key))
}

function syncMenuChecked() {
  const checked = menuTreeRef.value?.getCheckedKeys(false) || []
  form.danh_sach_menu = onlyLeafKeys(checked, allMenuLeafIds)
}

function syncCauHinhChecked() {
  const checked = cauHinhTreeRef.value?.getCheckedKeys(false) || []
  form.cau_hinh = onlyLeafKeys(checked, allCauHinhLeafIds)
}

function setTreeChecked(treeRef, keys) {
  treeRef?.setCheckedKeys(keys || [], false)
}

function checkAllMenu() {
  setTreeChecked(menuTreeRef.value, allMenuLeafIds)
  form.danh_sach_menu = [...allMenuLeafIds]
}

function clearAllMenu() {
  setTreeChecked(menuTreeRef.value, [])
  form.danh_sach_menu = []
}

function checkAllCauHinh() {
  setTreeChecked(cauHinhTreeRef.value, allCauHinhLeafIds)
  form.cau_hinh = [...allCauHinhLeafIds]
}

function clearAllCauHinh() {
  setTreeChecked(cauHinhTreeRef.value, [])
  form.cau_hinh = []
}

async function applyTreeSelection(menuKeys, cauHinhKeys) {
  await nextTick()
  setTreeChecked(menuTreeRef.value, menuKeys)
  setTreeChecked(cauHinhTreeRef.value, cauHinhKeys)
  form.danh_sach_menu = [...menuKeys]
  form.cau_hinh = [...cauHinhKeys]
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchVaiTro({
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

async function openCreate() {
  editingId.value = null
  Object.assign(form, emptyForm())
  dialogVisible.value = true
  await applyTreeSelection([], [])
}

async function openEdit(row) {
  editingId.value = row.id
  const menuKeys = onlyLeafKeys(row.danh_sach_menu || [], allMenuLeafIds)
  const cauHinhKeys = onlyLeafKeys(row.cau_hinh || [], allCauHinhLeafIds)
  Object.assign(form, {
    ten_vai_tro: row.ten_vai_tro,
    ghi_chu: row.ghi_chu || '',
    danh_sach_menu: menuKeys,
    cau_hinh: cauHinhKeys,
  })
  dialogVisible.value = true
  await applyTreeSelection(menuKeys, cauHinhKeys)
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  syncMenuChecked()
  syncCauHinhChecked()

  saving.value = true
  const payload = {
    ten_vai_tro: form.ten_vai_tro.trim(),
    ghi_chu: form.ghi_chu?.trim() || null,
    danh_sach_menu: form.danh_sach_menu,
    cau_hinh: form.cau_hinh,
  }

  try {
    if (editingId.value) {
      await updateVaiTro(editingId.value, payload)
      ElMessage.success('Đã cập nhật vai trò.')
    } else {
      await createVaiTro(payload)
      ElMessage.success('Đã thêm vai trò.')
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

  await ElMessageBox.confirm(`Xóa ${ids.length} vai trò đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteVaiTro(id))
    ElMessage.success(`Đã xóa ${ids.length} vai trò.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa vai trò "${row.ten_vai_tro}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteVaiTro(row.id)
    ElMessage.success('Đã xóa vai trò.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadItems)
</script>

<style scoped>
.tree-panel {
  width: 100%;
  max-height: 360px;
  overflow: auto;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  padding: 8px 10px 12px;
  background: var(--el-fill-color-blank);
}

.tree-panel__actions {
  display: flex;
  gap: 8px;
  margin-bottom: 6px;
}
</style>
