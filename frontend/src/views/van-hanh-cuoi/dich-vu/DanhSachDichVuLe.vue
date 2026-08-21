<template>
  <div class="danh-sach-dich-vu-le page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="6" :lg="7">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo mã, tên dịch vụ..."
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
            v-model="filterLoaiDichVuId"
            placeholder="Loại dịch vụ"
            clearable
            filterable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="item in loaiDichVuOptions"
              :key="item.id"
              :label="item.ten_dich_vu"
              :value="item.id"
            />
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
            <CustomOption label="Đang sử dụng" value="dang_su_dung" />
            <CustomOption label="Ngừng sử dụng" value="ngung_su_dung" />
          </CustomSelect>
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
          <span class="card-title">Danh sách dịch vụ</span>
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
          v-if="columnSettings.isColumnVisible('ma_dich_vu')"
          prop="ma_dich_vu"
          label="Mã"
          width="120"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ten_dich_vu')"
          prop="ten_dich_vu"
          label="Tên dịch vụ"
          min-width="180"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('loai_dich_vu')"
          label="Loại dịch vụ"
          min-width="160"
        >
          <template #default="{ row }">
            {{ row.loai_dich_vu?.ten_dich_vu || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('loai_hop_dong')"
          label="Loại hợp đồng áp dụng"
          min-width="220"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ formatLoaiHopDong(row) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('gia_goc')"
          label="Giá gốc"
          width="130"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.gia_goc) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('gia_khuyen_mai')"
          label="Giá KM"
          width="130"
          align="right"
        >
          <template #default="{ row }">
            {{ row.gia_khuyen_mai != null ? formatMoney(row.gia_khuyen_mai) : '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('trang_thai')"
          label="Trạng thái"
          width="130"
          align="center"
        >
          <template #default="{ row }">
            <CustomTag :type="row.trang_thai === 'dang_su_dung' ? 'success' : 'info'">
              {{ trangThaiLabel(row.trang_thai) }}
            </CustomTag>
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
      :title="editingId ? 'Sửa dịch vụ' : 'Thêm dịch vụ'"
      :width="960"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Mã dịch vụ" prop="ma_dich_vu">
              <CustomInput
                v-model="form.ma_dich_vu"
                :disabled="!!editingId"
                placeholder="VD: DV001"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Tên dịch vụ" prop="ten_dich_vu">
              <CustomInput v-model="form.ten_dich_vu" placeholder="Nhập tên dịch vụ" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Loại dịch vụ" prop="loai_dich_vu_id">
              <CustomSelect
                v-model="form.loai_dich_vu_id"
                placeholder="Chọn loại dịch vụ"
                filterable
                style="width: 100%"
              >
                <CustomOption
                  v-for="item in loaiDichVuOptions"
                  :key="item.id"
                  :label="item.ten_dich_vu"
                  :value="item.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Giá gốc" prop="gia_goc">
              <CustomInput
                v-model.number="form.gia_goc"
                type="number"
                :min="0"
                placeholder="0"
                @input="onGiaGocInput"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Giá khuyến mãi" prop="gia_khuyen_mai">
              <CustomInput
                v-model.number="form.gia_khuyen_mai"
                type="number"
                :min="0"
                placeholder="Bằng giá gốc"
                @input="onGiaKhuyenMaiInput"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="form.trang_thai" style="width: 100%">
                <CustomOption label="Đang sử dụng" value="dang_su_dung" />
                <CustomOption label="Ngừng sử dụng" value="ngung_su_dung" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :span="24">
            <CustomFormItem label="Loại hợp đồng áp dụng" prop="loai_hop_dong_ids">
              <div class="loai-hop-dong-section">
                <div class="loai-hop-dong-card-grid">
                  <button
                    v-for="item in loaiHopDongOptions"
                    :key="item.id"
                    type="button"
                    class="loai-hop-dong-card"
                    :class="{ 'is-selected': isLoaiHopDongSelected(item.id) }"
                    @click="toggleLoaiHopDong(item.id)"
                  >
                    <span v-if="isLoaiHopDongSelected(item.id)" class="loai-hop-dong-card__check">
                      <CustomIcon><Check /></CustomIcon>
                    </span>
                    <div class="loai-hop-dong-card__name" :title="item.ten_hop_dong">
                      {{ item.ten_hop_dong }}
                    </div>
                  </button>
                  <div v-if="!loaiHopDongOptions.length" class="loai-hop-dong-empty">
                    Chưa có loại hợp đồng nào.
                  </div>
                </div>
              </div>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Mô tả" prop="mo_ta">
              <CustomInput
                v-model="form.mo_ta"
                type="textarea"
                :rows="3"
                placeholder="Mô tả dịch vụ"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
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
import { Check, Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createDichVuDanhSachDichVuLe,
  deleteDichVuDanhSachDichVuLe,
  fetchDichVuDanhSachDichVuLe,
  updateDichVuDanhSachDichVuLe,
} from '@/api/dichVuDanhSachDichVuLe'
import { fetchDichVuLoaiDichVu } from '@/api/dichVuLoaiDichVu'
import { fetchLoaiHopDong } from '@/api/loaiHopDong'
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
  CustomTag,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'

const ACTIVE = 'dang_su_dung'
const INACTIVE = 'ngung_su_dung'

const tableColumns = [
  { key: 'ma_dich_vu', label: 'Mã' },
  { key: 'ten_dich_vu', label: 'Tên dịch vụ' },
  { key: 'loai_dich_vu', label: 'Loại dịch vụ' },
  { key: 'loai_hop_dong', label: 'Loại hợp đồng áp dụng' },
  { key: 'gia_goc', label: 'Giá gốc' },
  { key: 'gia_khuyen_mai', label: 'Giá KM' },
  { key: 'trang_thai', label: 'Trạng thái' },
]
const columnSettings = useTableColumns('van-hanh-cuoi.dich-vu-le', tableColumns)

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const route = useRoute()
const keyword = ref(String(route.query.keyword || ''))
const filterTrangThai = ref('')
const filterLoaiDichVuId = ref(null)

const loaiDichVuOptions = ref([])
const loaiHopDongOptions = ref([])

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)
const giaKhuyenMaiSynced = ref(true)
const bulkActivating = ref(false)
const bulkDeactivating = ref(false)
const bulkDeleting = ref(false)

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
        ? `Bật ${activeCount} dịch vụ đang ngừng`
        : 'Chọn dịch vụ ngừng sử dụng để bật',
    },
    {
      key: 'deactivate',
      label: 'Tắt',
      type: 'warning',
      badge: inactiveCount,
      badgeType: 'warning',
      loading: bulkDeactivating.value,
      tooltip: inactiveCount
        ? `Tắt ${inactiveCount} dịch vụ đang sử dụng`
        : 'Chọn dịch vụ đang sử dụng để tắt',
    },
    {
      key: 'delete',
      label: 'Xóa',
      type: 'danger',
      badge: selectedCount.value,
      badgeType: 'danger',
      loading: bulkDeleting.value,
      tooltip: selectedCount.value
        ? `Xóa ${selectedCount.value} dịch vụ đã chọn`
        : 'Chọn dịch vụ để xóa',
    },
  ]
})

const emptyForm = () => ({
  ma_dich_vu: '',
  ten_dich_vu: '',
  loai_dich_vu_id: null,
  loai_hop_dong_ids: [],
  gia_goc: 0,
  gia_khuyen_mai: 0,
  mo_ta: '',
  trang_thai: ACTIVE,
  ghi_chu: '',
})

const form = reactive(emptyForm())

const rules = {
  ma_dich_vu: [{ required: true, message: 'Vui lòng nhập mã dịch vụ', trigger: 'blur' }],
  ten_dich_vu: [{ required: true, message: 'Vui lòng nhập tên dịch vụ', trigger: 'blur' }],
  loai_dich_vu_id: [{ required: true, message: 'Vui lòng chọn loại dịch vụ', trigger: 'change' }],
  gia_goc: [{ required: true, message: 'Vui lòng nhập giá gốc', trigger: 'blur' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

function formatMoney(value) {
  const num = Number(value)
  if (Number.isNaN(num)) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function trangThaiLabel(value) {
  return value === ACTIVE ? 'Đang sử dụng' : 'Ngừng sử dụng'
}

function statusPayload(row, trangThai) {
  return {
    ma_dich_vu: row.ma_dich_vu,
    ten_dich_vu: row.ten_dich_vu,
    loai_dich_vu_id: row.loai_dich_vu_id,
    loai_hop_dong_ids: row.loai_hop_dong_ids?.length ? row.loai_hop_dong_ids : [],
    gia_goc: Number(row.gia_goc) || 0,
    gia_khuyen_mai: row.gia_khuyen_mai != null && row.gia_khuyen_mai !== ''
      ? Number(row.gia_khuyen_mai)
      : null,
    mo_ta: row.mo_ta || null,
    trang_thai: trangThai,
    ghi_chu: row.ghi_chu || null,
  }
}

function formatLoaiHopDong(row) {
  const labels = row.loai_hop_dong_labels || []
  return labels.length ? labels.join(', ') : '—'
}

function isLoaiHopDongSelected(id) {
  return form.loai_hop_dong_ids.includes(id)
}

function toggleLoaiHopDong(id) {
  const index = form.loai_hop_dong_ids.indexOf(id)
  if (index >= 0) {
    form.loai_hop_dong_ids.splice(index, 1)
  } else {
    form.loai_hop_dong_ids.push(id)
  }
}

function normalizeGia(value) {
  if (value == null || value === '') return 0
  const num = Number(value)
  return Number.isNaN(num) ? 0 : num
}

function onGiaGocInput() {
  if (giaKhuyenMaiSynced.value) {
    form.gia_khuyen_mai = normalizeGia(form.gia_goc)
  }
}

function onGiaKhuyenMaiInput() {
  giaKhuyenMaiSynced.value = false
}

async function loadOptions() {
  try {
    const [loaiDichVuRes, loaiHopDongRes] = await Promise.all([
      fetchDichVuLoaiDichVu({ per_page: 100, trang_thai: 'dang_hoat_dong' }),
      fetchLoaiHopDong({ per_page: 100, trang_thai: 'hoat_dong' }),
    ])
    loaiDichVuOptions.value = loaiDichVuRes.data?.data || []
    loaiHopDongOptions.value = loaiHopDongRes.data?.data || []
  } catch {
    loaiDichVuOptions.value = []
    loaiHopDongOptions.value = []
  }
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchDichVuDanhSachDichVuLe({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      trang_thai: filterTrangThai.value || undefined,
      loai_dich_vu_id: filterLoaiDichVuId.value || undefined,
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
  giaKhuyenMaiSynced.value = true
  Object.assign(form, emptyForm(), {
    loai_hop_dong_ids: loaiHopDongOptions.value.map((item) => item.id),
  })
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  const giaGoc = normalizeGia(row.gia_goc)
  const giaKhuyenMai = row.gia_khuyen_mai != null ? normalizeGia(row.gia_khuyen_mai) : giaGoc
  giaKhuyenMaiSynced.value = row.gia_khuyen_mai == null || giaKhuyenMai === giaGoc
  Object.assign(form, {
    ma_dich_vu: row.ma_dich_vu,
    ten_dich_vu: row.ten_dich_vu,
    loai_dich_vu_id: row.loai_dich_vu_id,
    loai_hop_dong_ids: [...(row.loai_hop_dong_ids || [])],
    gia_goc: giaGoc,
    gia_khuyen_mai: giaKhuyenMai,
    mo_ta: row.mo_ta || '',
    trang_thai: row.trang_thai,
    ghi_chu: row.ghi_chu || '',
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    ma_dich_vu: form.ma_dich_vu.trim(),
    ten_dich_vu: form.ten_dich_vu.trim(),
    loai_dich_vu_id: form.loai_dich_vu_id,
    loai_hop_dong_ids: form.loai_hop_dong_ids?.length ? form.loai_hop_dong_ids : [],
    gia_goc: Number(form.gia_goc) || 0,
    gia_khuyen_mai: form.gia_khuyen_mai != null && form.gia_khuyen_mai !== ''
      ? Number(form.gia_khuyen_mai)
      : null,
    mo_ta: form.mo_ta?.trim() || null,
    trang_thai: form.trang_thai,
    ghi_chu: form.ghi_chu?.trim() || null,
  }

  try {
    if (editingId.value) {
      await updateDichVuDanhSachDichVuLe(editingId.value, payload)
      ElMessage.success('Đã cập nhật dịch vụ.')
    } else {
      await createDichVuDanhSachDichVuLe(payload)
      ElMessage.success('Đã thêm dịch vụ.')
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
  if (key === 'activate') await bulkSetStatus(ACTIVE)
  else if (key === 'deactivate') await bulkSetStatus(INACTIVE)
  else if (key === 'delete') await bulkRemove()
}

async function bulkSetStatus(target) {
  const fromStatus = target === ACTIVE ? INACTIVE : ACTIVE
  const ids = idsByStatus(fromStatus)
  if (!ids.length) return

  const label = target === ACTIVE ? 'Bật' : 'Tắt'
  await ElMessageBox.confirm(`${label} ${ids.length} dịch vụ đã chọn?`, 'Xác nhận', {
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
      await updateDichVuDanhSachDichVuLe(id, statusPayload(row, target))
    })
    ElMessage.success(`Đã ${label.toLowerCase()} ${ids.length} dịch vụ.`)
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

  await ElMessageBox.confirm(`Xóa ${ids.length} dịch vụ đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteDichVuDanhSachDichVuLe(id))
    ElMessage.success(`Đã xóa ${ids.length} dịch vụ.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa dịch vụ "${row.ten_dich_vu}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteDichVuDanhSachDichVuLe(row.id)
    ElMessage.success('Đã xóa dịch vụ.')
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
.loai-hop-dong-section {
  width: 100%;
}

.loai-hop-dong-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 10px;
  max-height: 240px;
  overflow-y: auto;
  padding: 4px;
}

.loai-hop-dong-card {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  gap: 4px;
  min-height: 56px;
  padding: 12px;
  border: 2px solid var(--el-border-color-lighter);
  border-radius: 8px;
  background: var(--el-fill-color-blank);
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
  text-align: left;

  &:hover {
    border-color: var(--el-color-primary-light-5);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  }

  &.is-selected {
    border-color: var(--el-color-primary);
    box-shadow: 0 0 0 1px var(--el-color-primary-light-7);
    background: var(--el-color-primary-light-9);
  }
}

.loai-hop-dong-card__check {
  position: absolute;
  top: 8px;
  right: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--el-color-primary);
  color: #fff;
  font-size: 10px;
}

.loai-hop-dong-card__name {
  font-size: 13px;
  font-weight: 500;
  color: var(--el-text-color-primary);
  line-height: 1.35;
  padding-right: 20px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.loai-hop-dong-empty {
  grid-column: 1 / -1;
  padding: 16px;
  text-align: center;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}
</style>
