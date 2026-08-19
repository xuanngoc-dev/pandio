<template>
  <div class="phieu-thu-chi page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="6" :lg="6">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo lý do, ghi chú..."
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
        <CustomCol :xs="12" :sm="12" :md="6" :lg="5">
          <CustomSelect
            v-model="filterLoai"
            placeholder="Loại"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption label="Thu" value="thu" />
            <CustomOption label="Chi" value="chi" />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="6" :lg="5">
          <CustomSelect
            v-model="filterTrangThai"
            placeholder="Trạng thái"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="opt in trangThaiOptions"
              :key="opt.value"
              :label="opt.label"
              :value="opt.value"
            />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="6" :lg="4">
          <CustomSelect
            v-model="filterHangMucId"
            placeholder="Hạng mục"
            clearable
            filterable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="item in hangMucOptions"
              :key="item.id"
              :label="item.ten_hang_muc"
              :value="item.id"
            />
          </CustomSelect>
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
          <span class="card-title">Danh sách phiếu thu chi</span>
          <div class="card-header-right">
            <BulkActionBar
              :actions="bulkActions"
              @action="onBulkAction"
            >
              <TableColumnConfig :settings="columnSettings" />
              <CustomTooltip content="Thêm mới" placement="top">
                <CustomButton type="primary" @click="openCreate">
                  <CustomIcon><Plus /></CustomIcon>
                  Thêm
                </CustomButton>
              </CustomTooltip>
            </BulkActionBar>
          </div>
        </div>
      </template>

      <CustomTable
        :column-settings="columnSettings"
        ref="tableRef"
        v-loading="loading"
        :data="items"
        stripe
        row-key="id"
        style="width: 100%"
        @selection-change="onSelectionChange"
      >
        <CustomTableColumn
          type="selection"
          width="48"
          align="center"
          :selectable="canSelectRow"
        />
        <CustomTableColumn label="STT" width="60" align="center">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('loai')"
          label="Loại"
          width="90"
          align="center"
        >
          <template #default="{ row }">
            <el-tag :type="row.loai === 'thu' ? 'success' : 'danger'" size="small">
              {{ row.loai === 'thu' ? 'Thu' : 'Chi' }}
            </el-tag>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('hang_muc')"
          label="Hạng mục"
          min-width="160"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.hang_muc?.ten_hang_muc || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('so_tien')"
          label="Số tiền"
          width="140"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.so_tien) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ly_do')"
          label="Lý do"
          min-width="180"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.ly_do || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('trang_thai')"
          label="Trạng thái"
          width="120"
          align="center"
        >
          <template #default="{ row }">
            <el-tag :type="trangThaiTagType(row.trang_thai)" size="small">
              {{ trangThaiLabel(row.trang_thai) }}
            </el-tag>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguoi_tao')"
          label="Người tạo"
          min-width="130"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.nguoi_tao?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguoi_duyet')"
          label="Người duyệt"
          min-width="130"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.nguoi_duyet?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('cap_nhat_tt')"
          label="Cập nhật TT"
          width="150"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDateTime(row.ngay_cap_nhat_trang_thai) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ghi_chu')"
          label="Ghi chú"
          min-width="140"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.ghi_chu || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Thao tác" width="160" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip
                :content="isLockedRow(row) ? 'Phiếu đã duyệt/từ chối không thể duyệt' : 'Duyệt'"
                placement="top"
              >
                <span class="btn-wrap">
                  <CustomButton
                    type="success"
                    link
                    :icon="CircleCheck"
                    :disabled="isLockedRow(row)"
                    @click="openStatusModal('da_duyet', [row.id])"
                  />
                </span>
              </CustomTooltip>
              <CustomTooltip
                :content="isLockedRow(row) ? 'Phiếu đã duyệt/từ chối không thể từ chối' : 'Từ chối'"
                placement="top"
              >
                <span class="btn-wrap">
                  <CustomButton
                    type="warning"
                    link
                    :icon="CircleClose"
                    :disabled="isLockedRow(row)"
                    @click="openStatusModal('tu_choi', [row.id])"
                  />
                </span>
              </CustomTooltip>
              <CustomTooltip
                :content="isLockedRow(row) ? 'Phiếu đã duyệt/từ chối không thể sửa' : 'Sửa'"
                placement="top"
              >
                <span class="btn-wrap">
                  <CustomButton
                    type="primary"
                    link
                    :icon="Edit"
                    :disabled="isLockedRow(row)"
                    @click="openEdit(row)"
                  />
                </span>
              </CustomTooltip>
              <CustomTooltip
                :content="isLockedRow(row) ? 'Phiếu đã duyệt/từ chối không thể xóa' : 'Xóa'"
                placement="top"
              >
                <span class="btn-wrap">
                  <CustomButton
                    type="danger"
                    link
                    :icon="Delete"
                    :disabled="isLockedRow(row)"
                    @click="remove(row)"
                  />
                </span>
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
      :title="editingId ? 'Sửa phiếu thu chi' : 'Thêm phiếu thu chi'"
      :width="860"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Loại" prop="loai">
              <CustomSelect v-model="form.loai" placeholder="Chọn loại" style="width: 100%">
                <CustomOption label="Thu" value="thu" />
                <CustomOption label="Chi" value="chi" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Hạng mục" prop="hang_muc_id">
              <CustomSelect
                v-model="form.hang_muc_id"
                placeholder="Chọn hạng mục"
                filterable
                clearable
                style="width: 100%"
              >
                <CustomOption
                  v-for="item in hangMucOptions"
                  :key="item.id"
                  :label="item.ten_hang_muc"
                  :value="item.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Số tiền" prop="so_tien">
              <CustomInput v-model="form.so_tien" type="number" style="width: 100%" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="form.trang_thai" style="width: 100%">
                <CustomOption
                  v-for="opt in trangThaiOptions"
                  :key="opt.value"
                  :label="opt.label"
                  :value="opt.value"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Người tạo">
              <CustomSelect
                :model-value="form.nguoi_tao_id"
                placeholder="Người tạo"
                disabled
                style="width: 100%"
              >
                <CustomOption
                  v-for="user in userOptions"
                  :key="user.id"
                  :label="user.name"
                  :value="user.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Người duyệt" prop="nguoi_duyet_id">
              <CustomSelect
                v-model="form.nguoi_duyet_id"
                placeholder="Chọn người duyệt (tuỳ chọn)"
                filterable
                clearable
                style="width: 100%"
              >
                <CustomOption
                  v-for="user in userOptions"
                  :key="user.id"
                  :label="user.name"
                  :value="user.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :span="24">
            <CustomFormItem label="Lý do" prop="ly_do">
              <CustomInput
                v-model="form.ly_do"
                type="textarea"
                :rows="2"
                placeholder="Lý do thu / chi"
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
        </CustomRow>
      </CustomForm>
      <template #footer>
        <CustomButton @click="dialogVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="saving" @click="save">Lưu</CustomButton>
      </template>
    </CustomDialog>

    <CustomDialog
      v-model="statusDialogVisible"
      :title="statusModalTitle"
      :width="520"
    >
      <CustomForm ref="statusFormRef" :model="statusForm" :rules="statusRules" label-position="top">
        <CustomFormItem label="Ghi chú / Lý do" prop="ghi_chu">
          <CustomInput
            v-model="statusForm.ghi_chu"
            type="textarea"
            :rows="4"
            :placeholder="
              statusForm.trang_thai === 'da_duyet'
                ? 'Nhập ghi chú khi duyệt...'
                : 'Nhập lý do từ chối...'
            "
          />
        </CustomFormItem>
      </CustomForm>
      <template #footer>
        <CustomButton @click="statusDialogVisible = false">Hủy</CustomButton>
        <CustomButton
          :type="statusForm.trang_thai === 'da_duyet' ? 'success' : 'warning'"
          :loading="statusSaving"
          @click="confirmStatusChange"
        >
          {{ statusForm.trang_thai === 'da_duyet' ? 'Duyệt' : 'Từ chối' }}
        </CustomButton>
      </template>
    </CustomDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search, CircleCheck, CircleClose } from '@element-plus/icons-vue'
import { fetchHangMucLoaiThuChi } from '@/api/hangMucLoaiThuChi'
import {
  bulkDeletePhieuThuChi,
  bulkUpdateStatusPhieuThuChi,
  createPhieuThuChi,
  deletePhieuThuChi,
  fetchPhieuThuChi,
  updatePhieuThuChi,
} from '@/api/phieuThuChi'
import { fetchUsers } from '@/api/users'
import { useAuthStore } from '@/stores/auth'
import { formatInteger } from '@/utils/number'
import BulkActionBar from '@/components/BulkActionBar.vue'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
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

const tableColumns = [
  { key: 'loai', label: 'Loại' },
  { key: 'hang_muc', label: 'Hạng mục' },
  { key: 'so_tien', label: 'Số tiền' },
  { key: 'ly_do', label: 'Lý do' },
  { key: 'trang_thai', label: 'Trạng thái' },
  { key: 'nguoi_tao', label: 'Người tạo' },
  { key: 'nguoi_duyet', label: 'Người duyệt' },
  { key: 'cap_nhat_tt', label: 'Cập nhật TT' },
  { key: 'ghi_chu', label: 'Ghi chú' },
]
const columnSettings = useTableColumns('tai-chinh.phieu-thu-chi', tableColumns)

const authStore = useAuthStore()

const trangThaiOptions = [
  { value: 'cho_duyet', label: 'Chờ duyệt' },
  { value: 'da_duyet', label: 'Đã duyệt' },
  { value: 'tu_choi', label: 'Từ chối' },
]

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const filterLoai = ref('')
const filterTrangThai = ref('')
const filterHangMucId = ref(null)
const hangMucOptions = ref([])
const userOptions = ref([])

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)
const tableRef = ref(null)
const selectedRows = ref([])
const bulkApproving = ref(false)
const bulkRejecting = ref(false)
const bulkDeleting = ref(false)

const statusDialogVisible = ref(false)
const statusSaving = ref(false)
const statusFormRef = ref(null)
const statusForm = reactive({
  trang_thai: 'da_duyet',
  ghi_chu: '',
  ids: [],
})
const statusRules = {
  ghi_chu: [{ required: true, message: 'Vui lòng nhập ghi chú / lý do', trigger: 'blur' }],
}

const statusModalTitle = computed(() => {
  const count = statusForm.ids?.length || 0
  if (statusForm.trang_thai === 'da_duyet') {
    return count > 1 ? `Duyệt ${count} phiếu` : 'Duyệt phiếu'
  }
  return count > 1 ? `Từ chối ${count} phiếu` : 'Từ chối phiếu'
})

const selectedCount = computed(() => selectedRows.value.length)
const selectedChoDuyetRows = computed(() =>
  selectedRows.value.filter((row) => row.trang_thai === 'cho_duyet'),
)
const selectedChoDuyetCount = computed(() => selectedChoDuyetRows.value.length)

const bulkActions = computed(() => [
  {
    key: 'tu_choi',
    label: 'Từ chối',
    type: 'warning',
    badge: selectedChoDuyetCount.value,
    badgeType: 'warning',
    loading: bulkRejecting.value,
    tooltip: selectedChoDuyetCount.value
      ? `Từ chối ${selectedChoDuyetCount.value} phiếu chờ duyệt`
      : 'Chọn phiếu chờ duyệt để từ chối',
  },
  {
    key: 'da_duyet',
    label: 'Duyệt',
    type: 'success',
    badge: selectedChoDuyetCount.value,
    badgeType: 'success',
    loading: bulkApproving.value,
    tooltip: selectedChoDuyetCount.value
      ? `Duyệt ${selectedChoDuyetCount.value} phiếu chờ duyệt`
      : 'Chọn phiếu chờ duyệt để duyệt',
  },
  {
    key: 'delete',
    label: 'Xóa',
    type: 'danger',
    badge: selectedCount.value,
    badgeType: 'danger',
    loading: bulkDeleting.value,
    tooltip: selectedCount.value
      ? `Xóa ${selectedCount.value} phiếu đã chọn`
      : 'Chọn phiếu để xóa',
  },
])

function onBulkAction(key) {
  if (key === 'delete') {
    bulkRemove()
    return
  }
  if (key === 'da_duyet' || key === 'tu_choi') {
    openStatusModal(key, selectedChoDuyetIds())
  }
}

function openStatusModal(trangThai, ids = []) {
  const targetIds = (ids || []).filter(Boolean)
  if (!targetIds.length || !trangThai) return

  statusForm.trang_thai = trangThai
  statusForm.ghi_chu = ''
  statusForm.ids = targetIds
  statusDialogVisible.value = true
}

async function confirmStatusChange() {
  const valid = await statusFormRef.value?.validate().catch(() => false)
  if (!valid) return

  const ids = statusForm.ids || []
  const trangThai = statusForm.trang_thai
  const ghiChu = statusForm.ghi_chu.trim()
  if (!ids.length || !trangThai || !ghiChu) return

  const label = trangThaiLabel(trangThai)
  const loadingRef = trangThai === 'da_duyet' ? bulkApproving : bulkRejecting
  statusSaving.value = true
  loadingRef.value = true

  try {
    if (ids.length === 1) {
      const row = items.value.find((item) => item.id === ids[0])
      if (!row || isLockedRow(row)) {
        ElMessage.warning('Phiếu không thể thay đổi trạng thái.')
        return
      }
      await updatePhieuThuChi(row.id, {
        loai: row.loai,
        hang_muc_id: row.hang_muc_id || null,
        so_tien: Number(row.so_tien) || 0,
        ly_do: row.ly_do || null,
        trang_thai: trangThai,
        ghi_chu: ghiChu,
      })
    } else {
      await bulkUpdateStatusPhieuThuChi(ids, trangThai, ghiChu)
    }
    ElMessage.success(`Đã ${label.toLowerCase()} ${ids.length} phiếu.`)
    statusDialogVisible.value = false
    await loadItems()
  } catch {
    // interceptor
  } finally {
    statusSaving.value = false
    loadingRef.value = false
  }
}

const emptyForm = () => ({
  loai: 'chi',
  hang_muc_id: null,
  so_tien: 0,
  ly_do: '',
  trang_thai: 'cho_duyet',
  nguoi_tao_id: null,
  nguoi_duyet_id: null,
  ghi_chu: '',
})

const form = reactive(emptyForm())

const rules = {
  loai: [{ required: true, message: 'Vui lòng chọn loại', trigger: 'change' }],
  so_tien: [{ required: true, message: 'Vui lòng nhập số tiền', trigger: 'blur' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

function trangThaiLabel(value) {
  return trangThaiOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function trangThaiTagType(value) {
  const map = {
    cho_duyet: 'warning',
    da_duyet: 'success',
    tu_choi: 'danger',
  }
  return map[value] || 'info'
}

function isLockedRow(row) {
  return row?.trang_thai === 'da_duyet' || row?.trang_thai === 'tu_choi'
}

function canSelectRow(row) {
  return !isLockedRow(row)
}

function formatMoney(value) {
  if (value == null || value === '') return '—'
  return `${formatInteger(value) || '0'} ₫`
}

function formatDateTime(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleString('vi-VN')
}

function ensureUserInOptions(user) {
  if (!user?.id) return
  if (!userOptions.value.some((item) => item.id === user.id)) {
    userOptions.value = [{ id: user.id, name: user.name }, ...userOptions.value]
  }
}

function ensureHangMucInOptions(hangMuc) {
  if (!hangMuc?.id) return
  if (!hangMucOptions.value.some((item) => item.id === hangMuc.id)) {
    hangMucOptions.value = [hangMuc, ...hangMucOptions.value]
  }
}

async function loadHangMucOptions() {
  try {
    const { data } = await fetchHangMucLoaiThuChi({ per_page: 100, trang_thai: 'hoat_dong' })
    hangMucOptions.value = data.data || []
  } catch {
    hangMucOptions.value = []
  }
}

async function loadUserOptions() {
  try {
    const { data } = await fetchUsers({ per_page: 100, status: 'active' })
    userOptions.value = data.data || []
  } catch {
    userOptions.value = []
  }
}

async function loadItems() {
  loading.value = true
  selectedRows.value = []
  try {
    const { data } = await fetchPhieuThuChi({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      loai: filterLoai.value || undefined,
      trang_thai: filterTrangThai.value || undefined,
      hang_muc_id: filterHangMucId.value || undefined,
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

function onSelectionChange(rows) {
  selectedRows.value = (rows || []).filter((row) => !isLockedRow(row))
}

function selectedIds() {
  return selectedRows.value.map((row) => row.id).filter(Boolean)
}

function selectedChoDuyetIds() {
  return selectedChoDuyetRows.value.map((row) => row.id).filter(Boolean)
}

async function bulkRemove() {
  const ids = selectedIds()
  if (!ids.length) return

  await ElMessageBox.confirm(`Xóa ${ids.length} phiếu đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await bulkDeletePhieuThuChi(ids)
    ElMessage.success(`Đã xóa ${ids.length} phiếu thu chi.`)
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
  ensureUserInOptions(authStore.user)
  form.nguoi_tao_id = authStore.user?.id ?? null
  dialogVisible.value = true
}

function openEdit(row) {
  if (isLockedRow(row)) {
    ElMessage.warning('Phiếu đã duyệt hoặc từ chối không thể thay đổi.')
    return
  }

  editingId.value = row.id
  ensureUserInOptions(row.nguoi_tao)
  ensureUserInOptions(row.nguoi_duyet)
  ensureHangMucInOptions(row.hang_muc)

  Object.assign(form, {
    loai: row.loai || 'chi',
    hang_muc_id: row.hang_muc_id || null,
    so_tien: Number(row.so_tien) || 0,
    ly_do: row.ly_do || '',
    trang_thai: row.trang_thai || 'cho_duyet',
    nguoi_tao_id: row.nguoi_tao_id || null,
    nguoi_duyet_id: row.nguoi_duyet_id || null,
    ghi_chu: row.ghi_chu || '',
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    loai: form.loai,
    hang_muc_id: form.hang_muc_id || null,
    so_tien: Number(form.so_tien) || 0,
    ly_do: form.ly_do?.trim() || null,
    trang_thai: form.trang_thai,
    nguoi_duyet_id: form.nguoi_duyet_id || null,
    ghi_chu: form.ghi_chu?.trim() || null,
  }

  try {
    if (editingId.value) {
      await updatePhieuThuChi(editingId.value, payload)
      ElMessage.success('Đã cập nhật phiếu thu chi.')
    } else {
      await createPhieuThuChi(payload)
      ElMessage.success('Đã thêm phiếu thu chi.')
    }
    dialogVisible.value = false
    await loadItems()
  } catch {
    // interceptor
  } finally {
    saving.value = false
  }
}

async function remove(row) {
  if (isLockedRow(row)) {
    ElMessage.warning('Phiếu đã duyệt hoặc từ chối không thể xóa.')
    return
  }

  await ElMessageBox.confirm(
    `Xóa phiếu ${row.loai === 'thu' ? 'thu' : 'chi'} ${formatMoney(row.so_tien)}?`,
    'Xác nhận',
    {
      type: 'warning',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
    },
  )

  try {
    await deletePhieuThuChi(row.id)
    ElMessage.success('Đã xóa phiếu thu chi.')
    await loadItems()
  } catch {
    // interceptor
  }
}

onMounted(async () => {
  await Promise.all([loadHangMucOptions(), loadUserOptions()])
  await loadItems()
})
</script>

<style scoped lang="scss">
.card-header-right {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 12px;
  margin-left: auto;
}

.btn-wrap {
  display: inline-flex;
}
</style>
