<template>
  <div class="thong-bao-list page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="8" :lg="8">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo tiêu đề, nội dung..."
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
            v-model="loaiThongBaoFilter"
            placeholder="Loại thông báo"
            clearable
            filterable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="item in loaiThongBaoOptions"
              :key="item.id"
              :label="item.ten_loai_thong_bao"
              :value="item.id"
            />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="8" :lg="5">
          <CustomSelect
            v-model="mauSacFilter"
            placeholder="Màu sắc"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="item in MAU_SAC_OPTIONS"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
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
          <span class="card-title">Danh sách thông báo</span>
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
          v-if="columnSettings.isColumnVisible('tieu_de')"
          prop="tieu_de"
          label="Tiêu đề"
          min-width="200"
          show-overflow-tooltip
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('loai_thong_bao')"
          label="Loại thông báo"
          min-width="160"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.loai_thong_bao?.ten_loai_thong_bao || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('loai_mau_sac')"
          label="Màu sắc"
          width="130"
          align="center"
        >
          <template #default="{ row }">
            <span class="mau-sac-badge" :class="`is-${row.loai_mau_sac}`">
              <span class="mau-sac-dot" />
              {{ mauSacLabel(row.loai_mau_sac) }}
            </span>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguoi_nhan')"
          label="Người nhận"
          min-width="180"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ formatNguoiNhan(row) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('actor')"
          label="Người gửi"
          min-width="140"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.actor?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('muc_do_uu_tien')"
          label="Ưu tiên"
          width="90"
          align="center"
        >
          <template #default="{ row }">
            {{ row.muc_do_uu_tien ?? '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('created_at')"
          label="Thời gian"
          width="160"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
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
      :title="editingId ? 'Sửa thông báo' : 'Thêm thông báo'"
      :width="720"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <CustomRow :gutter="16">
          <CustomCol :span="24">
            <CustomFormItem label="Tiêu đề" prop="tieu_de">
              <CustomInput v-model="form.tieu_de" placeholder="Tiêu đề thông báo" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Loại thông báo" prop="loai_thong_bao_id">
              <CustomSelect
                v-model="form.loai_thong_bao_id"
                placeholder="Chọn loại thông báo"
                filterable
                style="width: 100%"
              >
                <CustomOption
                  v-for="item in loaiThongBaoOptions"
                  :key="item.id"
                  :label="item.ten_loai_thong_bao"
                  :value="item.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Màu sắc" prop="loai_mau_sac">
              <CustomSelect v-model="form.loai_mau_sac" placeholder="Chọn màu" style="width: 100%">
                <CustomOption
                  v-for="item in MAU_SAC_OPTIONS"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                >
                  <span class="mau-sac-option">
                    <span class="mau-sac-dot" :class="`is-${item.value}`" />
                    {{ item.label }}
                  </span>
                </CustomOption>
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Phòng ban">
              <CustomSelect
                v-model="filterPhongBanId"
                placeholder="Lọc theo phòng ban"
                clearable
                filterable
                style="width: 100%"
                @change="onRecipientFilterChange"
              >
                <CustomOption
                  v-for="item in phongBanOptions"
                  :key="item.id"
                  :label="item.ten_phong_ban"
                  :value="item.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Vai trò">
              <CustomSelect
                v-model="filterVaiTroId"
                placeholder="Lọc theo vai trò"
                clearable
                filterable
                style="width: 100%"
                @change="onRecipientFilterChange"
              >
                <CustomOption
                  v-for="item in vaiTroOptions"
                  :key="item.id"
                  :label="item.ten_vai_tro"
                  :value="item.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :span="24">
            <CustomFormItem label="Người nhận" prop="nguoi_nhan_ids">
              <CustomSelect
                v-model="form.nguoi_nhan_ids"
                multiple
                filterable
                collapse-tags
                collapse-tags-tooltip
                placeholder="Chọn người nhận"
                style="width: 100%"
              >
                <CustomOption
                  v-for="user in recipientUserOptions"
                  :key="user.id"
                  :label="user.name"
                  :value="user.id"
                />
              </CustomSelect>
              <div v-if="hasRecipientFilter" class="recipient-hint">
                {{ recipientUserOptions.length }} người dùng tương ứng với bộ lọc
              </div>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Người gửi" prop="actor_id">
              <CustomSelect
                v-model="form.actor_id"
                placeholder="Chọn người gửi (mặc định: bạn)"
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
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Mức độ ưu tiên" prop="muc_do_uu_tien">
              <CustomSelect v-model="form.muc_do_uu_tien" style="width: 100%">
                <CustomOption v-for="n in 5" :key="n" :label="String(n)" :value="n" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :span="24">
            <CustomFormItem label="Nội dung" prop="noi_dung">
              <CustomInput
                v-model="form.noi_dung"
                type="textarea"
                :rows="4"
                placeholder="Nội dung thông báo"
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
import { fetchDanhMucLoaiThongBao } from '@/api/danhMucLoaiThongBao'
import {
  createHeThongThongBao,
  deleteHeThongThongBao,
  fetchHeThongThongBao,
  updateHeThongThongBao,
} from '@/api/heThongThongBao'
import { fetchPhongBan } from '@/api/phongBan'
import { fetchUsers } from '@/api/users'
import { fetchVaiTro } from '@/api/vaiTro'
import BulkActionBar from '@/components/BulkActionBar.vue'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { runBulk, useBulkSelection } from '@/composables/useBulkSelection'
import { useTableColumns } from '@/composables/useTableColumns'
import { useAuthStore } from '@/stores/auth'
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

const MAU_SAC_OPTIONS = [
  { value: 'red', label: 'Đỏ' },
  { value: 'green', label: 'Xanh lá' },
  { value: 'yellow', label: 'Vàng' },
  { value: 'blue', label: 'Xanh dương' },
  { value: 'orange', label: 'Cam' },
  { value: 'purple', label: 'Tím' },
  { value: 'gray', label: 'Xám' },
]

const MAU_SAC_MAP = Object.fromEntries(MAU_SAC_OPTIONS.map((item) => [item.value, item.label]))

const authStore = useAuthStore()

const tableColumns = [
  { key: 'tieu_de', label: 'Tiêu đề' },
  { key: 'loai_thong_bao', label: 'Loại thông báo' },
  { key: 'loai_mau_sac', label: 'Màu sắc' },
  { key: 'nguoi_nhan', label: 'Người nhận' },
  { key: 'actor', label: 'Người gửi' },
  { key: 'muc_do_uu_tien', label: 'Ưu tiên' },
  { key: 'created_at', label: 'Thời gian' },
]
const columnSettings = useTableColumns('he-thong.thong-bao', tableColumns)

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const bulkDeleting = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const loaiThongBaoFilter = ref(null)
const mauSacFilter = ref(null)

const loaiThongBaoOptions = ref([])
const userOptions = ref([])
const phongBanOptions = ref([])
const vaiTroOptions = ref([])
const filterPhongBanId = ref(null)
const filterVaiTroId = ref(null)

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)

const { selectedCount, onSelectionChange, clearSelection, selectedIds } = useBulkSelection(() => true)

const bulkActions = computed(() => [
  {
    key: 'delete',
    label: 'Xóa',
    type: 'danger',
    badge: selectedCount.value,
    badgeType: 'danger',
    loading: bulkDeleting.value,
    tooltip: selectedCount.value
      ? `Xóa ${selectedCount.value} thông báo đã chọn`
      : 'Chọn thông báo để xóa',
  },
])

const emptyForm = () => ({
  tieu_de: '',
  loai_thong_bao_id: null,
  loai_mau_sac: 'blue',
  nguoi_nhan_ids: [],
  actor_id: authStore.user?.id ?? null,
  muc_do_uu_tien: 1,
  noi_dung: '',
})

const form = reactive(emptyForm())

const hasRecipientFilter = computed(
  () => filterPhongBanId.value != null || filterVaiTroId.value != null
)

const recipientUserOptions = computed(() => {
  let list = userOptions.value
  if (filterPhongBanId.value != null) {
    const phongBanId = Number(filterPhongBanId.value)
    list = list.filter((user) => {
      const ids = user?.nhan_vien?.phong_ban_ids
      return Array.isArray(ids) && ids.map(Number).includes(phongBanId)
    })
  }
  if (filterVaiTroId.value != null) {
    const vaiTroId = Number(filterVaiTroId.value)
    list = list.filter((user) => Number(user?.nhan_vien?.vai_tro_id) === vaiTroId)
  }
  return list
})

const rules = {
  tieu_de: [{ required: true, message: 'Vui lòng nhập tiêu đề', trigger: 'blur' }],
  loai_thong_bao_id: [{ required: true, message: 'Vui lòng chọn loại thông báo', trigger: 'change' }],
  loai_mau_sac: [{ required: true, message: 'Vui lòng chọn màu sắc', trigger: 'change' }],
  nguoi_nhan_ids: [
    {
      type: 'array',
      required: true,
      min: 1,
      message: 'Vui lòng chọn ít nhất một người nhận',
      trigger: 'change',
    },
  ],
  muc_do_uu_tien: [{ required: true, message: 'Vui lòng chọn mức ưu tiên', trigger: 'change' }],
}

function mauSacLabel(value) {
  return MAU_SAC_MAP[value] || value || '—'
}

function formatNguoiNhan(row) {
  const names = (row.nguoi_nhan || []).map((u) => u.name).filter(Boolean)
  if (!names.length) {
    const count = Array.isArray(row.nguoi_nhan_ids) ? row.nguoi_nhan_ids.length : 0
    return count ? `${count} người` : '—'
  }
  if (names.length <= 2) return names.join(', ')
  return `${names.slice(0, 2).join(', ')} +${names.length - 2}`
}

function formatDateTime(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function onRecipientFilterChange() {
  if (!hasRecipientFilter.value) return
  form.nguoi_nhan_ids = recipientUserOptions.value.map((user) => user.id)
}

function resetRecipientFilters() {
  filterPhongBanId.value = null
  filterVaiTroId.value = null
}

async function loadOptions() {
  try {
    const [loaiRes, userRes, phongBanRes, vaiTroRes] = await Promise.all([
      fetchDanhMucLoaiThongBao({ per_page: 100 }),
      fetchUsers({ per_page: 100, status: 'active' }),
      fetchPhongBan({ per_page: 100 }),
      fetchVaiTro({ per_page: 100 }),
    ])
    loaiThongBaoOptions.value = loaiRes.data?.data || []
    userOptions.value = userRes.data?.data || []
    phongBanOptions.value = phongBanRes.data?.data || []
    vaiTroOptions.value = vaiTroRes.data?.data || []
  } catch {
    loaiThongBaoOptions.value = []
    userOptions.value = []
    phongBanOptions.value = []
    vaiTroOptions.value = []
  }
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchHeThongThongBao({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      loai_thong_bao_id: loaiThongBaoFilter.value || undefined,
      loai_mau_sac: mauSacFilter.value || undefined,
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
  resetRecipientFilters()
  Object.assign(form, emptyForm())
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  resetRecipientFilters()
  Object.assign(form, {
    tieu_de: row.tieu_de || '',
    loai_thong_bao_id: row.loai_thong_bao_id,
    loai_mau_sac: row.loai_mau_sac || 'blue',
    nguoi_nhan_ids: Array.isArray(row.nguoi_nhan_ids) ? [...row.nguoi_nhan_ids] : [],
    actor_id: row.actor_id ?? null,
    muc_do_uu_tien: row.muc_do_uu_tien ?? 1,
    noi_dung: row.noi_dung || '',
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    tieu_de: form.tieu_de.trim(),
    loai_thong_bao_id: form.loai_thong_bao_id,
    loai_mau_sac: form.loai_mau_sac,
    nguoi_nhan_ids: form.nguoi_nhan_ids,
    actor_id: form.actor_id || null,
    muc_do_uu_tien: form.muc_do_uu_tien,
    noi_dung: form.noi_dung?.trim() || null,
  }

  try {
    if (editingId.value) {
      await updateHeThongThongBao(editingId.value, payload)
      ElMessage.success('Đã cập nhật thông báo.')
    } else {
      await createHeThongThongBao(payload)
      ElMessage.success('Đã thêm thông báo.')
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

  await ElMessageBox.confirm(`Xóa ${ids.length} thông báo đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteHeThongThongBao(id))
    ElMessage.success(`Đã xóa ${ids.length} thông báo.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa thông báo "${row.tieu_de}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteHeThongThongBao(row.id)
    ElMessage.success('Đã xóa thông báo.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(async () => {
  await loadOptions()
  await loadItems()
})
</script>

<style scoped lang="scss">
.mau-sac-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 500;
  background: var(--el-fill-color-light);
  color: var(--el-text-color-regular);

  .mau-sac-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
  }

  &.is-red {
    color: #c0392b;
    background: rgba(192, 57, 43, 0.12);
  }
  &.is-green {
    color: #1e8449;
    background: rgba(30, 132, 73, 0.12);
  }
  &.is-yellow {
    color: #b7950b;
    background: rgba(183, 149, 11, 0.12);
  }
  &.is-blue {
    color: #2471a3;
    background: rgba(36, 113, 163, 0.12);
  }
  &.is-orange {
    color: #d35400;
    background: rgba(211, 84, 0, 0.12);
  }
  &.is-purple {
    color: #7d3c98;
    background: rgba(125, 60, 152, 0.12);
  }
  &.is-gray {
    color: #5d6d7e;
    background: rgba(93, 109, 126, 0.12);
  }
}

.mau-sac-option {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.mau-sac-dot {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;

  &.is-red {
    background: #c0392b;
  }
  &.is-green {
    background: #1e8449;
  }
  &.is-yellow {
    background: #b7950b;
  }
  &.is-blue {
    background: #2471a3;
  }
  &.is-orange {
    background: #d35400;
  }
  &.is-purple {
    background: #7d3c98;
  }
  &.is-gray {
    background: #5d6d7e;
  }
}

.recipient-hint {
  margin-top: 6px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>
