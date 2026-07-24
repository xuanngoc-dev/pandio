<template>
  <div class="concept-list">
    <CustomCard shadow="hover" class="filter-card">
      <div class="toolbar">
        <CustomInput
          v-model="keyword"
          placeholder="Tìm theo mã, tên, địa điểm..."
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
          v-model="loaiFilter"
          placeholder="Loại concept"
          clearable
          filterable
          style="width: 220px"
          @change="onSearch"
        >
          <CustomOption
            v-for="item in danhMucOptions"
            :key="item.id"
            :label="item.ten_danh_muc"
            :value="item.id"
          />
        </CustomSelect>
        <CustomSelect
          v-model="trangThaiFilter"
          placeholder="Trạng thái"
          clearable
          style="width: 180px"
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
          <span class="card-title">Danh sách concept</span>
          <CustomButton type="primary" @click="openCreate">
            <CustomIcon><Plus /></CustomIcon>
            Thêm concept
          </CustomButton>
        </div>
      </template>

      <CustomTable v-loading="loading" :data="items" stripe style="width: 100%">
        <CustomTableColumn label="STT" width="60" align="center">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Hình ảnh" width="90" align="center">
          <template #default="{ row }">
            <el-avatar
              v-if="row.hinh_anh"
              :size="48"
              :src="mediaUrl(row.hinh_anh)"
              shape="square"
              class="concept-thumb"
            />
            <span v-else class="no-image">—</span>
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="ma_concept" label="Mã" width="120" />
        <CustomTableColumn prop="ten_concept" label="Tên concept" min-width="180" />
        <CustomTableColumn label="Loại" min-width="160">
          <template #default="{ row }">
            {{ row.danh_muc?.ten_danh_muc || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="dia_diem" label="Địa điểm" min-width="160">
          <template #default="{ row }">
            {{ row.dia_diem || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="trang_thai" label="Trạng thái" width="180" align="center">
          <template #default="{ row }">
            <div class="status-cell">
              <el-switch
                :model-value="row.trang_thai"
                active-value="dang_su_dung"
                inactive-value="ngung_su_dung"
                :loading="togglingId === row.id"
                :disabled="togglingId === row.id"
                @change="(val) => toggleStatus(row, val)"
              />
              <span
                class="status-label"
                :class="row.trang_thai === 'dang_su_dung' ? 'is-active' : 'is-inactive'"
              >
                {{ row.trang_thai === 'dang_su_dung' ? 'Đang sử dụng' : 'Ngừng sử dụng' }}
              </span>
            </div>
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="mo_ta" label="Mô tả" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.mo_ta || '—' }}
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
      :title="editingId ? 'Sửa concept' : 'Thêm concept'"
      :width="780"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="8">
            <CustomFormItem label="Hình ảnh" prop="hinh_anh">
              <div class="image-slot">
                <el-upload
                  class="image-uploader"
                  :show-file-list="false"
                  :auto-upload="false"
                  accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                  :on-change="onImageChange"
                >
                  <img
                    v-if="imagePreviewUrl"
                    :src="imagePreviewUrl"
                    class="image-preview"
                    alt="Ảnh concept"
                  />
                  <div v-else class="image-placeholder">
                    <el-icon><Plus /></el-icon>
                    <span>Chọn ảnh</span>
                  </div>
                </el-upload>
                <button
                  v-if="imagePreviewUrl"
                  type="button"
                  class="image-remove"
                  title="Xóa ảnh"
                  @click.stop="onImageRemove"
                >
                  <el-icon><Delete /></el-icon>
                </button>
              </div>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="16">
            <CustomRow :gutter="16">
              <CustomCol :xs="24" :sm="12">
                <CustomFormItem label="Mã concept" prop="ma_concept">
                  <CustomInput
                    v-model="form.ma_concept"
                    :disabled="!!editingId"
                    placeholder="VD: CP01"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12">
                <CustomFormItem label="Tên concept" prop="ten_concept">
                  <CustomInput v-model="form.ten_concept" placeholder="Tên concept" />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12">
                <CustomFormItem label="Loại concept" prop="loai_concept">
                  <CustomSelect
                    v-model="form.loai_concept"
                    placeholder="Chọn danh mục"
                    filterable
                    style="width: 100%"
                  >
                    <CustomOption
                      v-for="item in danhMucOptions"
                      :key="item.id"
                      :label="item.ten_danh_muc"
                      :value="item.id"
                    />
                  </CustomSelect>
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="12">
                <CustomFormItem label="Địa điểm" prop="dia_diem">
                  <CustomInput v-model="form.dia_diem" placeholder="Địa điểm (tuỳ chọn)" />
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
              <CustomCol :span="24">
                <CustomFormItem label="Mô tả" prop="mo_ta">
                  <CustomInput
                    v-model="form.mo_ta"
                    type="textarea"
                    :rows="3"
                    placeholder="Mô tả (tuỳ chọn)"
                  />
                </CustomFormItem>
              </CustomCol>
            </CustomRow>
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
  createConcept,
  deleteConcept,
  fetchConcept,
  updateConcept,
  uploadConceptHinhAnh,
} from '@/api/concept'
import { fetchDanhMucConcept } from '@/api/danhMucConcept'
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
import { mediaUrl } from '@/utils/media'

const items = ref([])
const danhMucOptions = ref([])
const loading = ref(false)
const saving = ref(false)
const togglingId = ref(null)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const loaiFilter = ref(null)
const trangThaiFilter = ref(null)

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)
const pendingImageFile = ref(null)
const pendingPreviewUrl = ref('')

const emptyForm = () => ({
  hinh_anh: '',
  loai_concept: null,
  ma_concept: '',
  ten_concept: '',
  dia_diem: '',
  trang_thai: 'dang_su_dung',
  mo_ta: '',
})

const form = reactive(emptyForm())

const rules = {
  ma_concept: [{ required: true, message: 'Vui lòng nhập mã concept', trigger: 'blur' }],
  ten_concept: [{ required: true, message: 'Vui lòng nhập tên concept', trigger: 'blur' }],
  loai_concept: [{ required: true, message: 'Vui lòng chọn loại concept', trigger: 'change' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

const imagePreviewUrl = computed(() => {
  if (pendingPreviewUrl.value) return pendingPreviewUrl.value
  return mediaUrl(form.hinh_anh)
})

function clearPendingPreview() {
  if (pendingPreviewUrl.value) {
    URL.revokeObjectURL(pendingPreviewUrl.value)
  }
  pendingPreviewUrl.value = ''
  pendingImageFile.value = null
}

function onImageChange(uploadFile) {
  const file = uploadFile?.raw
  if (!file) return
  clearPendingPreview()
  pendingImageFile.value = file
  pendingPreviewUrl.value = URL.createObjectURL(file)
}

function onImageRemove() {
  clearPendingPreview()
  form.hinh_anh = ''
}

async function loadDanhMucOptions() {
  try {
    const { data } = await fetchDanhMucConcept({ per_page: 100 })
    danhMucOptions.value = data.data || []
  } catch {
    danhMucOptions.value = []
  }
}

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchConcept({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      loai_concept: loaiFilter.value || undefined,
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
  clearPendingPreview()
  Object.assign(form, emptyForm())
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  clearPendingPreview()
  Object.assign(form, {
    hinh_anh: row.hinh_anh || '',
    loai_concept: row.loai_concept,
    ma_concept: row.ma_concept,
    ten_concept: row.ten_concept,
    dia_diem: row.dia_diem || '',
    trang_thai: row.trang_thai,
    mo_ta: row.mo_ta || '',
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  try {
    if (pendingImageFile.value) {
      const { data } = await uploadConceptHinhAnh(pendingImageFile.value)
      form.hinh_anh = data.path
      clearPendingPreview()
    }

    const payload = {
      hinh_anh: form.hinh_anh?.trim() || null,
      loai_concept: form.loai_concept,
      ma_concept: form.ma_concept.trim(),
      ten_concept: form.ten_concept.trim(),
      dia_diem: form.dia_diem?.trim() || null,
      trang_thai: form.trang_thai,
      mo_ta: form.mo_ta?.trim() || null,
    }

    if (editingId.value) {
      await updateConcept(editingId.value, payload)
      ElMessage.success('Đã cập nhật concept.')
    } else {
      await createConcept(payload)
      ElMessage.success('Đã thêm concept.')
    }
    dialogVisible.value = false
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
}

async function toggleStatus(row, value) {
  togglingId.value = row.id
  try {
    await updateConcept(row.id, {
      hinh_anh: row.hinh_anh,
      loai_concept: row.loai_concept,
      ma_concept: row.ma_concept,
      ten_concept: row.ten_concept,
      dia_diem: row.dia_diem,
      trang_thai: value,
      mo_ta: row.mo_ta,
    })
    row.trang_thai = value
    ElMessage.success('Đã cập nhật trạng thái.')
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    togglingId.value = null
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa concept "${row.ten_concept}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteConcept(row.id)
    ElMessage.success('Đã xóa concept.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(async () => {
  await loadDanhMucOptions()
  await loadItems()
})
</script>

<style scoped lang="scss">
.concept-list {
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

.concept-thumb {
  border-radius: 6px;
}

.no-image {
  color: var(--el-text-color-placeholder);
}

.status-cell {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.status-label {
  font-size: 13px;

  &.is-active {
    color: var(--el-color-success);
  }

  &.is-inactive {
    color: var(--el-text-color-secondary);
  }
}

.image-slot {
  position: relative;
  width: 100%;
}

.image-uploader {
  width: 100%;

  :deep(.el-upload) {
    display: block;
    width: 100%;
    aspect-ratio: 1 / 1;
    border: 1px dashed var(--el-border-color);
    border-radius: 8px;
    cursor: pointer;
    overflow: hidden;
    transition: border-color 0.2s;

    &:hover {
      border-color: var(--el-color-primary);
    }
  }
}

.image-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.image-remove {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
