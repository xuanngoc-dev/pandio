<template>
  <div class="cong-viec-ca-nhan page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="24" :sm="12" :md="8" :lg="8">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo tiêu đề, mô tả, ghi chú, liên kết..."
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
        <CustomCol :xs="12" :sm="12" :md="8" :lg="5">
          <CustomSelect
            v-model="trangThaiFilter"
            placeholder="Trạng thái"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption label="Chưa hoàn thành" value="chua_hoan_thanh" />
            <CustomOption label="Đã hoàn thành" value="da_hoan_thanh" />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="8" :lg="5">
          <CustomSelect
            v-model="mucDoUuTienFilter"
            placeholder="Mức ưu tiên"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption v-for="n in 5" :key="n" :label="`Mức ${n}`" :value="n" />
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
          <span class="card-title">Danh sách công việc cá nhân</span>
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
          v-if="columnSettings.isColumnVisible('nguoi_giao_viec')"
          label="Người giao"
          min-width="140"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.nguoi_giao_viec?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguoi_phu_trach')"
          label="Người phụ trách"
          min-width="180"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ formatNguoiPhuTrach(row) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('lien_ket')"
          label="Liên kết"
          min-width="160"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            <a
              v-if="row.lien_ket"
              :href="normalizeLienKet(row.lien_ket)"
              target="_blank"
              rel="noopener noreferrer"
              class="lien-ket-link"
              @click.stop
            >
              {{ row.lien_ket }}
            </a>
            <span v-else>—</span>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('thoi_gian_thuc_hien')"
          label="Thời gian thực hiện"
          min-width="180"
          align="center"
        >
          <template #default="{ row }">
            {{ formatThoiGian(row.thoi_gian_thuc_hien) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('muc_do_uu_tien')"
          label="Ưu tiên"
          width="90"
          align="center"
        >
          <template #default="{ row }">
            <CustomTag :type="uuTienTagType(row.muc_do_uu_tien)" size="small">
              {{ row.muc_do_uu_tien ? 'Mức ' + String(row.muc_do_uu_tien) : '—' }}
            </CustomTag>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('trang_thai')"
          prop="trang_thai"
          label="Trạng thái"
          width="200"
          align="center"
        >
          <template #default="{ row }">
            <div class="status-cell">
              <el-switch
                :model-value="row.trang_thai"
                active-value="da_hoan_thanh"
                inactive-value="chua_hoan_thanh"
                :loading="togglingId === row.id"
                :disabled="togglingId === row.id"
                :before-change="() => toggleTrangThai(row)"
              />
              <span
                class="status-label"
                :class="row.trang_thai === 'da_hoan_thanh' ? 'is-done' : 'is-pending'"
              >
                {{ trangThaiLabel(row.trang_thai) }}
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
      :title="editingId ? 'Sửa công việc cá nhân' : 'Thêm công việc cá nhân'"
      :width="720"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <CustomRow :gutter="16">
          <CustomCol :span="24">
            <CustomFormItem label="Tiêu đề" prop="tieu_de">
              <CustomInput v-model="form.tieu_de" placeholder="Tiêu đề công việc" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol v-if="isAdmin" :span="24">
            <CustomFormItem label="Người phụ trách" prop="nguoi_phu_trach_viec_ids">
              <CustomSelect
                v-model="form.nguoi_phu_trach_viec_ids"
                multiple
                filterable
                collapse-tags
                collapse-tags-tooltip
                placeholder="Chọn người nhận việc"
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
            <CustomFormItem label="Thời gian thực hiện" prop="thoi_gian_range">
              <div class="thoi-gian-field">
                <CustomDatePicker
                  v-model="form.thoi_gian_range"
                  type="daterange"
                  range-separator="—"
                  start-placeholder="Từ ngày"
                  end-placeholder="Đến ngày"
                  format="DD/MM/YYYY"
                  value-format="YYYY-MM-DD"
                  clearable
                  class="thoi-gian-picker"
                />
                <el-radio-group
                  class="thoi-gian-presets"
                  :model-value="activeThoiGianPreset"
                  @change="applyThoiGianPreset"
                >
                  <el-radio
                    v-for="preset in thoiGianPresets"
                    :key="preset.key"
                    :value="preset.key"
                  >
                    {{ preset.label }}
                  </el-radio>
                </el-radio-group>
              </div>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="12" :lg="12">
            <CustomFormItem label="Mức ưu tiên" prop="muc_do_uu_tien">
              <CustomSelect v-model="form.muc_do_uu_tien" style="width: 100%">
                <CustomOption v-for="n in 5" :key="n" :label=" 'Mức ' + String(n)" :value="n" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="12" :lg="12">
            <CustomFormItem label="Liên kết" prop="lien_ket">
              <CustomInput v-model="form.lien_ket" placeholder="https://... (tuỳ chọn)" clearable />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :span="24">
            <CustomFormItem label="Mô tả" prop="mo_ta">
              <CustomInput
                v-model="form.mo_ta"
                type="textarea"
                :rows="3"
                placeholder="Mô tả công việc (tuỳ chọn)"
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
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createCongViecCaNhan,
  deleteCongViecCaNhan,
  fetchCongViecCaNhan,
  updateCongViecCaNhan,
} from '@/api/congViecCaNhan'
import { fetchUsers } from '@/api/users'
import BulkActionBar from '@/components/BulkActionBar.vue'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { runBulk, useBulkSelection } from '@/composables/useBulkSelection'
import { useTableColumns } from '@/composables/useTableColumns'
import { useAuthStore } from '@/stores/auth'
import {
  CustomButton,
  CustomCard,
  CustomCol,
  CustomDatePicker,
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

const authStore = useAuthStore()
const isAdmin = computed(() => authStore.user?.role === 'admin')
const currentUserId = computed(() => authStore.user?.id ?? null)

const TRANG_THAI_MAP = {
  chua_hoan_thanh: 'Chưa hoàn thành',
  da_hoan_thanh: 'Đã hoàn thành',
}

const thoiGianPresets = [
  { key: 'hom_nay', label: 'Hôm nay' },
  { key: 'ngay_mai', label: 'Ngày mai' },
  { key: 'tuan_nay', label: 'Tuần này' },
  { key: 'thang_nay', label: 'Tháng này' },
]

const tableColumns = [
  { key: 'tieu_de', label: 'Tiêu đề' },
  { key: 'nguoi_giao_viec', label: 'Người giao' },
  { key: 'nguoi_phu_trach', label: 'Người phụ trách' },
  { key: 'lien_ket', label: 'Liên kết' },
  { key: 'thoi_gian_thuc_hien', label: 'Thời gian thực hiện' },
  { key: 'muc_do_uu_tien', label: 'Ưu tiên' },
  { key: 'trang_thai', label: 'Trạng thái' },
]
const columnSettings = useTableColumns('cong-viec.cong-viec-ca-nhan', tableColumns)

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const togglingId = ref(null)
const bulkDeleting = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const trangThaiFilter = ref('')
const mucDoUuTienFilter = ref(null)
const userOptions = ref([])

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
      ? `Xóa ${selectedCount.value} công việc đã chọn`
      : 'Chọn công việc để xóa',
  },
])

const emptyForm = () => ({
  tieu_de: '',
  nguoi_phu_trach_viec_ids: isAdmin.value
    ? []
    : currentUserId.value
      ? [currentUserId.value]
      : [],
  thoi_gian_range: getThoiGianPresetRange('hom_nay'),
  muc_do_uu_tien: 1,
  trang_thai: 'chua_hoan_thanh',
  mo_ta: '',
  ghi_chu: '',
  lien_ket: '',
})

const form = reactive(emptyForm())

const rules = computed(() => ({
  tieu_de: [{ required: true, message: 'Vui lòng nhập tiêu đề', trigger: 'blur' }],
  ...(isAdmin.value
    ? {
        nguoi_phu_trach_viec_ids: [
          {
            type: 'array',
            required: true,
            min: 1,
            message: 'Vui lòng chọn ít nhất một người phụ trách',
            trigger: 'change',
          },
        ],
      }
    : {}),
  muc_do_uu_tien: [{ required: true, message: 'Vui lòng chọn mức ưu tiên', trigger: 'change' }],
}))

const activeThoiGianPreset = computed(() => {
  const range = form.thoi_gian_range
  if (!range?.[0] || !range?.[1]) return null
  for (const preset of thoiGianPresets) {
    const expected = getThoiGianPresetRange(preset.key)
    if (expected && expected[0] === range[0] && expected[1] === range[1]) {
      return preset.key
    }
  }
  return null
})

function trangThaiLabel(value) {
  return TRANG_THAI_MAP[value] || value || '—'
}

function uuTienTagType(level) {
  if (level >= 5) return 'danger'
  if (level >= 4) return 'warning'
  if (level >= 3) return ''
  return 'info'
}

function formatNguoiPhuTrach(row) {
  const names = (row.nguoi_phu_trach || []).map((u) => u.name).filter(Boolean)
  if (!names.length) {
    const count = Array.isArray(row.nguoi_phu_trach_viec_ids)
      ? row.nguoi_phu_trach_viec_ids.length
      : 0
    return count ? `${count} người` : '—'
  }
  if (names.length <= 2) return names.join(', ')
  return `${names.slice(0, 2).join(', ')} +${names.length - 2}`
}

function normalizeLienKet(value) {
  const raw = String(value || '').trim()
  if (!raw) return '#'
  if (/^https?:\/\//i.test(raw)) return raw
  return `https://${raw}`
}

function formatThoiGian(value) {
  if (!value?.bat_dau) return '—'
  const batDau = formatDate(value.bat_dau)
  const ketThuc = formatDate(value.ket_thuc)
  if (!ketThuc || ketThuc === batDau) return batDau
  return `${batDau} — ${ketThuc}`
}

function formatDate(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

function toThoiGianPayload(range) {
  if (!Array.isArray(range) || range.length < 2 || !range[0] || !range[1]) return null
  return { bat_dau: range[0], ket_thuc: range[1] }
}

function fromThoiGianValue(value) {
  if (!value?.bat_dau || !value?.ket_thuc) return null
  return [value.bat_dau, value.ket_thuc]
}

function toYmd(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

function addDays(date, days) {
  const next = new Date(date)
  next.setDate(next.getDate() + days)
  return next
}

function startOfToday() {
  const now = new Date()
  return new Date(now.getFullYear(), now.getMonth(), now.getDate())
}

/** Preset: hôm nay→hôm nay; ngày mai: hôm nay→ngày mai; tuần: hôm nay→CN; tháng: hôm nay→cuối tháng */
function getThoiGianPresetRange(key) {
  const today = startOfToday()

  if (key === 'hom_nay') {
    const ymd = toYmd(today)
    return [ymd, ymd]
  }
  if (key === 'ngay_mai') {
    return [toYmd(today), toYmd(addDays(today, 1))]
  }
  if (key === 'tuan_nay') {
    // getDay: 0=CN … 6=T7 → số ngày còn lại đến Chủ nhật
    const daysToSunday = (7 - today.getDay()) % 7
    return [toYmd(today), toYmd(addDays(today, daysToSunday))]
  }
  if (key === 'thang_nay') {
    const end = new Date(today.getFullYear(), today.getMonth() + 1, 0)
    return [toYmd(today), toYmd(end)]
  }
  return null
}

function applyThoiGianPreset(key) {
  form.thoi_gian_range = getThoiGianPresetRange(key)
}

async function loadUsers() {
  try {
    const { data } = await fetchUsers({ per_page: 100, status: 'active' })
    userOptions.value = data?.data || []
  } catch {
    userOptions.value = []
  }
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchCongViecCaNhan({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      trang_thai: trangThaiFilter.value || undefined,
      muc_do_uu_tien: mucDoUuTienFilter.value || undefined,
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
  const assigneeIds = Array.isArray(row.nguoi_phu_trach_viec_ids)
    ? [...row.nguoi_phu_trach_viec_ids]
    : []
  Object.assign(form, {
    tieu_de: row.tieu_de || '',
    nguoi_phu_trach_viec_ids: isAdmin.value
      ? assigneeIds
      : currentUserId.value
        ? [currentUserId.value]
        : [],
    thoi_gian_range: fromThoiGianValue(row.thoi_gian_thuc_hien),
    muc_do_uu_tien: row.muc_do_uu_tien ?? 1,
    trang_thai: row.trang_thai || 'chua_hoan_thanh',
    mo_ta: row.mo_ta || '',
    ghi_chu: row.ghi_chu || '',
    lien_ket: row.lien_ket || '',
  })
  dialogVisible.value = true
}

function buildPayload() {
  const assigneeIds = isAdmin.value
    ? form.nguoi_phu_trach_viec_ids
    : currentUserId.value
      ? [currentUserId.value]
      : []

  return {
    tieu_de: form.tieu_de.trim(),
    nguoi_phu_trach_viec_ids: assigneeIds,
    thoi_gian_thuc_hien: toThoiGianPayload(form.thoi_gian_range),
    muc_do_uu_tien: form.muc_do_uu_tien,
    // Khi tạo mới luôn mặc định chưa hoàn thành; khi sửa giữ trạng thái hiện có (toggle riêng).
    trang_thai: editingId.value ? form.trang_thai : 'chua_hoan_thanh',
    mo_ta: form.mo_ta?.trim() || null,
    ghi_chu: form.ghi_chu?.trim() || null,
    lien_ket: form.lien_ket?.trim() || null,
  }
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = buildPayload()

  try {
    if (editingId.value) {
      await updateCongViecCaNhan(editingId.value, payload)
      ElMessage.success('Đã cập nhật công việc.')
    } else {
      await createCongViecCaNhan(payload)
      ElMessage.success('Đã thêm công việc.')
    }
    dialogVisible.value = false
    await loadItems()
  } catch {
    // interceptor
  } finally {
    saving.value = false
  }
}

async function toggleTrangThai(row) {
  if (!row?.id) return false

  const next =
    row.trang_thai === 'da_hoan_thanh' ? 'chua_hoan_thanh' : 'da_hoan_thanh'

  togglingId.value = row.id
  try {
    await updateCongViecCaNhan(row.id, {
      tieu_de: row.tieu_de,
      nguoi_phu_trach_viec_ids: row.nguoi_phu_trach_viec_ids || [],
      thoi_gian_thuc_hien: row.thoi_gian_thuc_hien || null,
      muc_do_uu_tien: row.muc_do_uu_tien ?? 1,
      trang_thai: next,
      mo_ta: row.mo_ta || null,
      ghi_chu: row.ghi_chu || null,
      lien_ket: row.lien_ket || null,
    })
    row.trang_thai = next
    ElMessage.success(
      next === 'da_hoan_thanh'
        ? 'Đã đánh dấu hoàn thành.'
        : 'Đã đánh dấu chưa hoàn thành.',
    )
    return true
  } catch {
    return false
  } finally {
    togglingId.value = null
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa công việc "${row.tieu_de}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteCongViecCaNhan(row.id)
    ElMessage.success('Đã xóa công việc.')
    await loadItems()
  } catch {
    // interceptor
  }
}

async function onBulkAction(key) {
  if (key === 'delete') await bulkRemove()
}

async function bulkRemove() {
  const ids = selectedIds.value
  if (!ids.length) return

  await ElMessageBox.confirm(`Xóa ${ids.length} công việc đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteCongViecCaNhan(id))
    ElMessage.success(`Đã xóa ${ids.length} công việc.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

onMounted(async () => {
  const tasks = [loadItems()]
  if (isAdmin.value) tasks.push(loadUsers())
  await Promise.all(tasks)
})
</script>

<style scoped>
.thoi-gian-field {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  width: 100%;
}

.thoi-gian-picker {
  flex: 0 1 320px;
  width: 320px;
  max-width: 100%;
}

.thoi-gian-presets {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px 12px;
}

.status-cell {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.status-label {
  font-size: 13px;
  white-space: nowrap;
}

.status-label.is-done {
  color: var(--el-color-success);
}

.status-label.is-pending {
  color: var(--el-color-warning);
}

.action-btns {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.lien-ket-link {
  color: var(--el-color-primary);
  text-decoration: none;
}

.lien-ket-link:hover {
  text-decoration: underline;
}
</style>
