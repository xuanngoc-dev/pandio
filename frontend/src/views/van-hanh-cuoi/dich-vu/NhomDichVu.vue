<template>
  <div class="nhom-dich-vu">
    <CustomCard shadow="hover" class="filter-card">
      <div class="toolbar">
        <CustomInput
          v-model="keyword"
          placeholder="Tìm theo mã, tên nhóm..."
          clearable
          style="max-width: 300px"
          @clear="onSearch"
          @keyup.enter="onSearch"
        >
          <template #prefix>
            <CustomIcon><Search /></CustomIcon>
          </template>
        </CustomInput>
        <CustomSelect
          v-model="filterLoaiDichVuId"
          placeholder="Loại dịch vụ"
          clearable
          filterable
          style="width: 220px"
          @change="onSearch"
        >
          <CustomOption
            v-for="item in loaiDichVuOptions"
            :key="item.id"
            :label="item.ten_dich_vu"
            :value="item.id"
          />
        </CustomSelect>
        <CustomSelect
          v-model="filterTrangThai"
          placeholder="Trạng thái"
          clearable
          style="width: 160px"
          @change="onSearch"
        >
          <CustomOption label="Đang sử dụng" value="dang_su_dung" />
          <CustomOption label="Ngừng sử dụng" value="ngung_su_dung" />
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
          <span class="card-title">Danh sách nhóm dịch vụ (combo)</span>
          <CustomButton type="primary" @click="openCreate">
            <CustomIcon><Plus /></CustomIcon>
            Thêm nhóm dịch vụ
          </CustomButton>
        </div>
      </template>

      <CustomTable v-loading="loading" :data="items" stripe style="width: 100%">
        <CustomTableColumn label="STT" width="60" align="center">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="ma_nhom" label="Mã nhóm" width="120" />
        <CustomTableColumn prop="ten_nhom" label="Tên nhóm" min-width="180" />
        <CustomTableColumn label="Loại dịch vụ" min-width="160">
          <template #default="{ row }">
            {{ row.loai_dich_vu?.ten_dich_vu || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Dịch vụ lẻ trong combo" min-width="220" show-overflow-tooltip>
          <template #default="{ row }">
            {{ formatDichVuLe(row) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Số điểm chụp" width="120" align="center">
          <template #default="{ row }">
            {{ row.so_diem_chup ?? 0 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Số ảnh chỉnh sửa" width="140" align="center">
          <template #default="{ row }">
            {{ row.so_anh_chinh_sua ?? 0 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Giá gốc" width="130" align="right">
          <template #default="{ row }">
            {{ formatMoney(row.gia_goc) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Giá KM" width="130" align="right">
          <template #default="{ row }">
            {{ row.gia_khuyen_mai != null ? formatMoney(row.gia_khuyen_mai) : '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Trạng thái" width="130" align="center">
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
      :title="editingId ? 'Sửa nhóm dịch vụ' : 'Thêm nhóm dịch vụ'"
      :width="1200"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Mã nhóm" prop="ma_nhom">
              <CustomInput
                v-model="form.ma_nhom"
                :disabled="!!editingId"
                placeholder="VD: COMBO001"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Tên nhóm" prop="ten_nhom">
              <CustomInput v-model="form.ten_nhom" placeholder="Nhập tên nhóm dịch vụ" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Loại dịch vụ" prop="loai_dich_vu_id">
              <CustomSelect
                v-model="form.loai_dich_vu_id"
                placeholder="Chọn loại dịch vụ"
                filterable
                style="width: 100%"
                @change="onLoaiDichVuChange"
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
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="form.trang_thai" style="width: 100%">
                <CustomOption label="Đang sử dụng" value="dang_su_dung" />
                <CustomOption label="Ngừng sử dụng" value="ngung_su_dung" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :span="24">
            <CustomFormItem label="Dịch vụ lẻ trong combo" prop="dich_vu_le_ids">
              <div class="dich-vu-le-section">
                <div v-if="loadingDichVuLe" class="dich-vu-le-empty">
                  Đang tải dịch vụ lẻ...
                </div>
                <div v-else-if="!form.loai_dich_vu_id" class="dich-vu-le-empty">
                  Chọn loại dịch vụ trước.
                </div>
                <div v-else class="dich-vu-le-card-grid">
                  <button
                    v-for="item in dichVuLeOptions"
                    :key="item.id"
                    type="button"
                    class="dich-vu-le-card"
                    :class="{ 'is-selected': isDichVuLeSelected(item.id) }"
                    @click="toggleDichVuLe(item.id)"
                  >
                    <span v-if="isDichVuLeSelected(item.id)" class="dich-vu-le-card__check">
                      <CustomIcon><Check /></CustomIcon>
                    </span>
                    <div class="dich-vu-le-card__name" :title="item.ten_dich_vu">
                      {{ item.ten_dich_vu }}
                    </div>
                    <div class="dich-vu-le-card__price">
                      <span class="dich-vu-le-card__price-original">
                        {{ formatMoney(item.gia_goc) }}
                      </span>
                      <span class="dich-vu-le-card__price-sale">
                        {{ formatMoney(getDichVuLeGiaKhuyenMai(item)) }}
                      </span>
                    </div>
                  </button>
                  <div v-if="!dichVuLeOptions.length" class="dich-vu-le-empty">
                    Không có dịch vụ lẻ cho loại này.
                  </div>
                </div>
              </div>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
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
          <CustomCol :xs="24" :sm="12" :md="6">
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
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Số điểm chụp" prop="so_diem_chup">
              <CustomInput
                v-model.number="form.so_diem_chup"
                type="number"
                :min="0"
                placeholder="0"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Số ảnh chỉnh sửa" prop="so_anh_chinh_sua">
              <CustomInput
                v-model.number="form.so_anh_chinh_sua"
                type="number"
                :min="0"
                placeholder="0"
              />
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
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Check, Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createDichVuDanhSachDichNhomDichVu,
  deleteDichVuDanhSachDichNhomDichVu,
  fetchDichVuDanhSachDichNhomDichVu,
  updateDichVuDanhSachDichNhomDichVu,
} from '@/api/dichVuDanhSachDichNhomDichVu'
import { fetchDichVuDanhSachDichVuLe } from '@/api/dichVuDanhSachDichVuLe'
import { fetchDichVuLoaiDichVu } from '@/api/dichVuLoaiDichVu'
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

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const loadingDichVuLe = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const filterTrangThai = ref('')
const filterLoaiDichVuId = ref(null)

const loaiDichVuOptions = ref([])
const dichVuLeOptions = ref([])

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)
const giaKhuyenMaiSynced = ref(true)

const emptyForm = () => ({
  ma_nhom: '',
  ten_nhom: '',
  loai_dich_vu_id: null,
  dich_vu_le_ids: [],
  gia_goc: 0,
  gia_khuyen_mai: 0,
  so_diem_chup: 0,
  so_anh_chinh_sua: 0,
  trang_thai: 'dang_su_dung',
  ghi_chu: '',
})

const form = reactive(emptyForm())

const rules = {
  ma_nhom: [{ required: true, message: 'Vui lòng nhập mã nhóm', trigger: 'blur' }],
  ten_nhom: [{ required: true, message: 'Vui lòng nhập tên nhóm', trigger: 'blur' }],
  loai_dich_vu_id: [{ required: true, message: 'Vui lòng chọn loại dịch vụ', trigger: 'change' }],
  gia_goc: [{ required: true, message: 'Vui lòng nhập giá gốc', trigger: 'blur' }],
  so_diem_chup: [{ required: true, message: 'Vui lòng nhập số điểm chụp', trigger: 'blur' }],
  so_anh_chinh_sua: [{ required: true, message: 'Vui lòng nhập số ảnh chỉnh sửa', trigger: 'blur' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

function isDichVuLeSelected(id) {
  return form.dich_vu_le_ids.includes(id)
}

function getDichVuLeGiaKhuyenMai(item) {
  return item.gia_khuyen_mai != null ? item.gia_khuyen_mai : item.gia_goc
}

function recalcGiaFromDichVuLe() {
  const total = form.dich_vu_le_ids.reduce((sum, id) => {
    const item = dichVuLeOptions.value.find((d) => d.id === id)
    if (!item) return sum
    return sum + normalizeGia(getDichVuLeGiaKhuyenMai(item))
  }, 0)
  form.gia_goc = total
  if (giaKhuyenMaiSynced.value) {
    form.gia_khuyen_mai = total
  }
}

function toggleDichVuLe(id) {
  const index = form.dich_vu_le_ids.indexOf(id)
  if (index >= 0) {
    form.dich_vu_le_ids.splice(index, 1)
  } else {
    form.dich_vu_le_ids.push(id)
  }
  recalcGiaFromDichVuLe()
}

function formatMoney(value) {
  const num = Number(value)
  if (Number.isNaN(num)) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function trangThaiLabel(value) {
  return value === 'dang_su_dung' ? 'Đang sử dụng' : 'Ngừng sử dụng'
}

function formatDichVuLe(row) {
  const labels = row.dich_vu_le_labels || []
  return labels.length ? labels.join(', ') : '—'
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

async function loadLoaiDichVuOptions() {
  try {
    const { data } = await fetchDichVuLoaiDichVu({ per_page: 100, trang_thai: 'dang_hoat_dong' })
    loaiDichVuOptions.value = data?.data || []
  } catch {
    loaiDichVuOptions.value = []
  }
}

async function loadDichVuLeByLoai(loaiDichVuId, keepSelected = false) {
  if (!loaiDichVuId) {
    dichVuLeOptions.value = []
    if (!keepSelected) form.dich_vu_le_ids = []
    return
  }

  loadingDichVuLe.value = true
  try {
    const { data } = await fetchDichVuDanhSachDichVuLe({
      per_page: 100,
      loai_dich_vu_id: loaiDichVuId,
      trang_thai: 'dang_su_dung',
    })
    dichVuLeOptions.value = data?.data || []

    if (!keepSelected) {
      form.dich_vu_le_ids = []
      form.gia_goc = 0
      if (giaKhuyenMaiSynced.value) {
        form.gia_khuyen_mai = 0
      }
    } else {
      const validIds = new Set(dichVuLeOptions.value.map((item) => item.id))
      form.dich_vu_le_ids = form.dich_vu_le_ids.filter((id) => validIds.has(id))
    }
  } catch {
    dichVuLeOptions.value = []
    if (!keepSelected) form.dich_vu_le_ids = []
  } finally {
    loadingDichVuLe.value = false
  }
}

function onLoaiDichVuChange(loaiDichVuId) {
  loadDichVuLeByLoai(loaiDichVuId, false)
}

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchDichVuDanhSachDichNhomDichVu({
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
  Object.assign(form, emptyForm())
  dichVuLeOptions.value = []
  dialogVisible.value = true
}

async function openEdit(row) {
  editingId.value = row.id
  const giaGoc = normalizeGia(row.gia_goc)
  const giaKhuyenMai = row.gia_khuyen_mai != null ? normalizeGia(row.gia_khuyen_mai) : giaGoc
  giaKhuyenMaiSynced.value = row.gia_khuyen_mai == null || giaKhuyenMai === giaGoc
  Object.assign(form, {
    ma_nhom: row.ma_nhom,
    ten_nhom: row.ten_nhom,
    loai_dich_vu_id: row.loai_dich_vu_id,
    dich_vu_le_ids: [...(row.dich_vu_le_ids || [])],
    gia_goc: giaGoc,
    gia_khuyen_mai: giaKhuyenMai,
    so_diem_chup: row.so_diem_chup ?? 0,
    so_anh_chinh_sua: row.so_anh_chinh_sua ?? 0,
    trang_thai: row.trang_thai,
    ghi_chu: row.ghi_chu || '',
  })
  dialogVisible.value = true
  await loadDichVuLeByLoai(row.loai_dich_vu_id, true)
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    ma_nhom: form.ma_nhom.trim(),
    ten_nhom: form.ten_nhom.trim(),
    loai_dich_vu_id: form.loai_dich_vu_id,
    dich_vu_le_ids: form.dich_vu_le_ids?.length ? form.dich_vu_le_ids : [],
    gia_goc: Number(form.gia_goc) || 0,
    gia_khuyen_mai: form.gia_khuyen_mai != null && form.gia_khuyen_mai !== ''
      ? Number(form.gia_khuyen_mai)
      : null,
    so_diem_chup: Number(form.so_diem_chup) || 0,
    so_anh_chinh_sua: Number(form.so_anh_chinh_sua) || 0,
    trang_thai: form.trang_thai,
    ghi_chu: form.ghi_chu?.trim() || null,
  }

  try {
    if (editingId.value) {
      await updateDichVuDanhSachDichNhomDichVu(editingId.value, payload)
      ElMessage.success('Đã cập nhật nhóm dịch vụ.')
    } else {
      await createDichVuDanhSachDichNhomDichVu(payload)
      ElMessage.success('Đã thêm nhóm dịch vụ.')
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
  await ElMessageBox.confirm(`Xóa nhóm dịch vụ "${row.ten_nhom}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteDichVuDanhSachDichNhomDichVu(row.id)
    ElMessage.success('Đã xóa nhóm dịch vụ.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(async () => {
  await loadLoaiDichVuOptions()
  await loadItems()
})
</script>

<style scoped lang="scss">
.nhom-dich-vu {
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
}

.action-btns {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.dich-vu-le-section {
  width: 100%;
}

.dich-vu-le-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 10px;
  max-height: 280px;
  overflow-y: auto;
  padding: 4px;
}

.dich-vu-le-card {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  gap: 6px;
  min-height: 72px;
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

.dich-vu-le-card__check {
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

.dich-vu-le-card__name {
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

.dich-vu-le-card__price {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding-right: 20px;
}

.dich-vu-le-card__price-original {
  font-size: 11px;
  color: var(--el-text-color-secondary);
  text-decoration: line-through;
  line-height: 1.2;
}

.dich-vu-le-card__price-sale {
  font-size: 13px;
  font-weight: 700;
  color: var(--el-color-primary);
  line-height: 1.2;
}

.dich-vu-le-empty {
  grid-column: 1 / -1;
  padding: 16px;
  text-align: center;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}
</style>
