<template>
  <div class="note-khach-moi">
    <CustomCard shadow="hover" class="filter-card">
      <div class="toolbar">
        <CustomInput
          v-model="keyword"
          placeholder="Tìm theo tên, SĐT, ghi chú, tra cứu HĐ..."
          clearable
          style="max-width: 320px"
          @clear="onSearch"
          @keyup.enter="onSearch"
        >
          <template #prefix>
            <CustomIcon><Search /></CustomIcon>
          </template>
        </CustomInput>
        <el-date-picker
          v-model="ngayHenTu"
          type="date"
          placeholder="Hẹn từ ngày"
          format="DD/MM/YYYY"
          value-format="YYYY-MM-DD"
          style="width: 160px"
          clearable
          @change="onSearch"
        />
        <el-date-picker
          v-model="ngayHenDen"
          type="date"
          placeholder="Hẹn đến ngày"
          format="DD/MM/YYYY"
          value-format="YYYY-MM-DD"
          style="width: 160px"
          clearable
          @change="onSearch"
        />
        <CustomSelect
          v-model="filterTrangThai"
          placeholder="Trạng thái"
          clearable
          style="width: 160px"
          @change="onSearch"
        >
          <CustomOption
            v-for="opt in trangThaiOptions"
            :key="opt.value"
            :label="opt.label"
            :value="opt.value"
          />
        </CustomSelect>
        <CustomButton type="primary" plain @click="onSearch">
          <CustomIcon><Search /></CustomIcon>
          Tìm kiếm
        </CustomButton>
      </div>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Note khách mới</span>
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
        <CustomTableColumn type="selection" width="48" align="center" fixed />
        <CustomTableColumn label="STT" width="60" align="center" fixed>
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('khach_hang')"
          label="Khách hàng"
          min-width="160"
          fixed
        >
          <template #default="{ row }">
            <div>{{ row.ten_khach }}</div>
            <div class="sub-text">{{ row.sdt || '—' }}</div>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ngay_hen')"
          label="Ngày hẹn"
          width="120"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDate(row.ngay_hen_lich) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ngay_den_tt')"
          label="Ngày đến TT"
          width="120"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDate(row.ngay_den_thuc_te) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('phu_trach_sale')"
          label="Phụ trách sale"
          min-width="180"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ formatSaleNames(row) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguon_khach')"
          label="Nguồn khách"
          width="120"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ nguonKhachLabel(row.nguon_khach) }}
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
          v-if="columnSettings.isColumnVisible('tra_cuu_hd')"
          label="Tra cứu HĐ"
          min-width="120"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.tra_cuu_hd || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('dat_coc')"
          label="Đặt cọc"
          width="120"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ hinhThucDatCocLabel(row.hinh_thuc_dat_coc) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguoi_tao')"
          label="Người tạo"
          min-width="130"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.nguoi_tao_user?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ghi_chu')"
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
        @change="loadItems"
      />
    </CustomCard>

    <CustomDialog
      v-model="dialogVisible"
      :title="editingId ? 'Sửa note khách mới' : 'Thêm note khách mới'"
      :width="1100"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Tên khách" prop="ten_khach">
              <CustomInput v-model="form.ten_khach" placeholder="Nhập tên khách" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="SĐT" prop="sdt">
              <CustomInput v-model="form.sdt" placeholder="Số điện thoại" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Ngày hẹn lịch" prop="ngay_hen_lich">
              <el-date-picker
                v-model="form.ngay_hen_lich"
                type="date"
                placeholder="Chọn ngày hẹn"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                style="width: 100%"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Ngày đến thực tế" prop="ngay_den_thuc_te">
              <el-date-picker
                v-model="form.ngay_den_thuc_te"
                type="date"
                placeholder="Chọn ngày đến"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                clearable
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="form.trang_thai" placeholder="Chọn trạng thái" style="width: 100%">
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
            <CustomFormItem label="Nguồn khách" prop="nguon_khach">
              <CustomSelect
                v-model="form.nguon_khach"
                placeholder="Chọn nguồn khách"
                clearable
                style="width: 100%"
              >
                <CustomOption
                  v-for="opt in nguonKhachOptions"
                  :key="opt.value"
                  :label="opt.label"
                  :value="opt.value"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Hình thức đặt cọc" prop="hinh_thuc_dat_coc">
              <CustomSelect
                v-model="form.hinh_thuc_dat_coc"
                placeholder="Chọn hình thức"
                clearable
                style="width: 100%"
              >
                <CustomOption
                  v-for="opt in hinhThucDatCocOptions"
                  :key="opt.value"
                  :label="opt.label"
                  :value="opt.value"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Tra cứu HĐ" prop="tra_cuu_hd">
              <CustomInput v-model="form.tra_cuu_hd" placeholder="Mã / tra cứu hợp đồng" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Người tạo" prop="nguoi_tao">
              <CustomSelect
                v-model="form.nguoi_tao"
                placeholder="Người tạo"
                filterable
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
          <CustomCol :xs="24" :sm="24" :md="16">
            <CustomFormItem label="Phụ trách sale" prop="phu_trach_sale">
              <CustomSelect
                v-model="form.phu_trach_sale"
                placeholder="Chọn nhân viên phụ trách"
                filterable
                multiple
                collapse-tags
                collapse-tags-tooltip
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
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createNoteKhachMoi,
  deleteNoteKhachMoi,
  fetchNoteKhachMoi,
  updateNoteKhachMoi,
} from '@/api/noteKhachMoi'
import { fetchUsers } from '@/api/users'
import { useAuthStore } from '@/stores/auth'
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

const tableColumns = [
  { key: 'khach_hang', label: 'Khách hàng' },
  { key: 'ngay_hen', label: 'Ngày hẹn' },
  { key: 'ngay_den_tt', label: 'Ngày đến TT' },
  { key: 'phu_trach_sale', label: 'Phụ trách sale' },
  { key: 'nguon_khach', label: 'Nguồn khách' },
  { key: 'trang_thai', label: 'Trạng thái' },
  { key: 'tra_cuu_hd', label: 'Tra cứu HĐ' },
  { key: 'dat_coc', label: 'Đặt cọc' },
  { key: 'nguoi_tao', label: 'Người tạo' },
  { key: 'ghi_chu', label: 'Ghi chú' },
]
const columnSettings = useTableColumns('khach-hang.note-khach-moi', tableColumns)

const authStore = useAuthStore()

const trangThaiOptions = [
  { value: 'cho_hen', label: 'Chờ hẹn' },
  { value: 'da_den', label: 'Đã đến' },
  { value: 'khong_den', label: 'Không đến' },
  { value: 'da_ky_hd', label: 'Đã ký HĐ' },
  { value: 'da_huy', label: 'Đã hủy' },
]

const nguonKhachOptions = [
  { value: 'tiktok', label: 'TikTok' },
  { value: 'facebook', label: 'Facebook' },
  { value: 'google', label: 'Google' },
  { value: 'gioi_thieu', label: 'Giới thiệu' },
  { value: 'walk_in', label: 'Walk-in' },
  { value: 'khac', label: 'Khác' },
]

const hinhThucDatCocOptions = [
  { value: 'tien_mat', label: 'Tiền mặt' },
  { value: 'chuyen_khoan', label: 'Chuyển khoản' },
  { value: 'khong_coc', label: 'Không cọc' },
  { value: 'khac', label: 'Khác' },
]

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const route = useRoute()
const keyword = ref(String(route.query.keyword || ''))
const filterTrangThai = ref('')
const ngayHenTu = ref('')
const ngayHenDen = ref('')
const userOptions = ref([])

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
      ? `Xóa ${selectedCount.value} note đã chọn`
      : 'Chọn note để xóa',
  },
])

const emptyForm = () => ({
  ten_khach: '',
  sdt: '',
  ngay_hen_lich: '',
  phu_trach_sale: [],
  ghi_chu: '',
  nguon_khach: '',
  ngay_den_thuc_te: '',
  trang_thai: 'cho_hen',
  tra_cuu_hd: '',
  hinh_thuc_dat_coc: '',
  nguoi_tao: null,
})

const form = reactive(emptyForm())

const rules = {
  ten_khach: [{ required: true, message: 'Vui lòng nhập tên khách', trigger: 'blur' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

function currentUserId() {
  return authStore.user?.id ?? null
}

function ensureUserInOptions(user) {
  if (!user?.id) return
  const exists = userOptions.value.some((item) => item.id === user.id)
  if (!exists) {
    userOptions.value = [{ id: user.id, name: user.name, phone: user.phone }, ...userOptions.value]
  }
}

function trangThaiLabel(value) {
  return trangThaiOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function trangThaiTagType(value) {
  const map = {
    cho_hen: 'info',
    da_den: 'success',
    khong_den: 'warning',
    da_ky_hd: 'success',
    da_huy: 'danger',
  }
  return map[value] || 'info'
}

function nguonKhachLabel(value) {
  return nguonKhachOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function hinhThucDatCocLabel(value) {
  return hinhThucDatCocOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function formatDate(value) {
  if (!value) return '—'
  const str = String(value).slice(0, 10)
  const [y, m, d] = str.split('-')
  if (!y || !m || !d) return str
  return `${d}/${m}/${y}`
}

function formatSaleNames(row) {
  const users = row.phu_trach_sale_users
  if (Array.isArray(users) && users.length) {
    return users.map((u) => u.name).filter(Boolean).join(', ') || '—'
  }
  const ids = Array.isArray(row.phu_trach_sale) ? row.phu_trach_sale : []
  if (!ids.length) return '—'
  return ids
    .map((id) => userOptions.value.find((u) => u.id === id)?.name)
    .filter(Boolean)
    .join(', ') || '—'
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
  clearSelection()
  try {
    const { data } = await fetchNoteKhachMoi({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      trang_thai: filterTrangThai.value || undefined,
      ngay_hen_tu: ngayHenTu.value || undefined,
      ngay_hen_den: ngayHenDen.value || undefined,
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
  ensureUserInOptions(authStore.user)
  form.nguoi_tao = currentUserId()
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  ensureUserInOptions(row.nguoi_tao_user)
  ;(row.phu_trach_sale_users || []).forEach(ensureUserInOptions)

  Object.assign(form, {
    ten_khach: row.ten_khach || '',
    sdt: row.sdt || '',
    ngay_hen_lich: String(row.ngay_hen_lich || '').slice(0, 10) || '',
    phu_trach_sale: Array.isArray(row.phu_trach_sale) ? [...row.phu_trach_sale] : [],
    ghi_chu: row.ghi_chu || '',
    nguon_khach: row.nguon_khach || '',
    ngay_den_thuc_te: String(row.ngay_den_thuc_te || '').slice(0, 10) || '',
    trang_thai: row.trang_thai || 'cho_hen',
    tra_cuu_hd: row.tra_cuu_hd || '',
    hinh_thuc_dat_coc: row.hinh_thuc_dat_coc || '',
    nguoi_tao: row.nguoi_tao || null,
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    ten_khach: form.ten_khach.trim(),
    sdt: form.sdt?.trim() || null,
    ngay_hen_lich: form.ngay_hen_lich || null,
    phu_trach_sale: form.phu_trach_sale || [],
    ghi_chu: form.ghi_chu?.trim() || null,
    nguon_khach: form.nguon_khach || null,
    ngay_den_thuc_te: form.ngay_den_thuc_te || null,
    trang_thai: form.trang_thai,
    tra_cuu_hd: form.tra_cuu_hd?.trim() || null,
    hinh_thuc_dat_coc: form.hinh_thuc_dat_coc || null,
  }

  try {
    if (editingId.value) {
      await updateNoteKhachMoi(editingId.value, payload)
      ElMessage.success('Đã cập nhật note khách mới.')
    } else {
      await createNoteKhachMoi(payload)
      ElMessage.success('Đã thêm note khách mới.')
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

  await ElMessageBox.confirm(`Xóa ${ids.length} note đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteNoteKhachMoi(id))
    ElMessage.success(`Đã xóa ${ids.length} note.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa note của "${row.ten_khach}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteNoteKhachMoi(row.id)
    ElMessage.success('Đã xóa note khách mới.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(async () => {
  await loadUserOptions()
  await loadItems()
})
</script>

<style scoped lang="scss">
.note-khach-moi {
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
  align-items: center;
}

.action-btns {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.sub-text {
  font-size: 12px;
  color: var(--el-text-color-secondary);
  margin-top: 2px;
}
</style>
