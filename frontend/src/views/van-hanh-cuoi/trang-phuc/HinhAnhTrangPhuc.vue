<template>
  <div class="hinh-anh-trang-phuc page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="6" :lg="6">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo tên file..."
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
        <CustomCol :xs="12" :sm="12" :md="6" :lg="6">
          <CustomButton type="primary" plain @click="onSearch">
            Tìm kiếm
          </CustomButton>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomCard shadow="hover" class="gallery-card">
      <template #header>
        <div class="card-header">
          <div class="card-header-left">
            <el-checkbox
              v-if="items.length"
              :model-value="allChecked"
              :indeterminate="isIndeterminate"
              :disabled="loading || progressBusy"
              @change="toggleAll"
            >
              Chọn tất cả
            </el-checkbox>
            <span class="card-title">Hình ảnh trang phục</span>
          </div>
          <div class="card-header-right view-toggle">
            <input
              ref="fileInputRef"
              type="file"
              multiple
              accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.svg,.zip,image/*"
              class="hidden-file-input"
              @change="onFilesPicked"
            >
            <CustomTooltip content="Ảnh mỗi file ≤ 5MB, hoặc 1 zip ≤ 1GB. Ảnh trùng tên sẽ ghi đè; zip trùng tên đổi thành fileName(1).zip và không tự giải nén." placement="top">
              <CustomButton type="primary" :icon="Upload" :disabled="progressBusy" @click="openFilePicker">
                Tải lên
              </CustomButton>
            </CustomTooltip>
            <CustomTooltip :content="selectedCount ? `Xóa ${selectedCount} file đã chọn` : 'Chọn file để xóa'" placement="top">
              <CustomButton type="danger" :disabled="!selectedCount || progressBusy" :loading="bulkDeleting" @click="removeSelected">
                Xóa{{ selectedCount ? ` (${selectedCount})` : '' }}
              </CustomButton>
            </CustomTooltip>
            <CustomTooltip content="Dạng lưới" placement="top">
              <CustomButton
                :type="viewMode === VIEW_GRID ? 'primary' : 'default'"
                circle
                size="small"
                :icon="Grid"
                @click="viewMode = VIEW_GRID"
              />
            </CustomTooltip>
            <CustomTooltip content="Dạng bảng" placement="top">
              <CustomButton
                :type="viewMode === VIEW_TABLE ? 'primary' : 'default'"
                circle
                size="small"
                :icon="List"
                @click="viewMode = VIEW_TABLE"
              />
            </CustomTooltip>
          </div>
        </div>
      </template>

      <div v-loading="loading" class="gallery-wrap">
        <el-empty
          v-if="!loading && !items.length"
          description="Chưa có hình ảnh trong thư mục trang phục."
        />

        <div v-else-if="viewMode === VIEW_GRID" class="gallery-grid">
          <div
            v-for="item in items"
            :key="`${item.path}-${item.modified_at}`"
            class="gallery-item"
            :class="{ 'is-selected': isSelected(item.path) }"
          >
            <el-checkbox
              class="gallery-check"
              :model-value="isSelected(item.path)"
              :disabled="progressBusy"
              @click.stop
              @change="(val) => toggleItem(item.path, val)"
            />
            <div v-if="item.kind === 'zip'" class="gallery-zip">
              <el-icon :size="42"><Files /></el-icon>
              <span>ZIP</span>
            </div>
            <el-image
              v-else
              :src="imageUrl(item)"
              :preview-src-list="previewUrls"
              :initial-index="previewIndex(item)"
              fit="cover"
              class="gallery-image"
              preview-teleported
            >
              <template #error>
                <div class="gallery-error">Không tải được ảnh</div>
              </template>
            </el-image>
            <div class="gallery-meta">
              <span class="gallery-name" :title="item.name">{{ item.name }}</span>
              <CustomTooltip v-if="item.kind === 'zip'" content="Giải nén" placement="top">
                <CustomButton type="primary" link :icon="FolderOpened" :disabled="progressBusy" @click="onExtract(item)" />
              </CustomTooltip>
              <CustomTooltip content="Sao chép tên" placement="top">
                <CustomButton type="primary" link :icon="CopyDocument" @click="copyFileName(item)" />
              </CustomTooltip>
              <CustomTooltip content="Sửa tên" placement="top">
                <CustomButton type="primary" link :icon="Edit" @click="openEdit(item)" />
              </CustomTooltip>
              <CustomTooltip content="Xóa" placement="top">
                <CustomButton type="danger" link :icon="Delete" :disabled="progressBusy || bulkDeleting" @click="removeItem(item)" />
              </CustomTooltip>
            </div>
          </div>
        </div>

        <CustomTable
          v-else
          :data="items"
          stripe
          row-key="path"
          style="width: 100%"
        >
          <CustomTableColumn width="48" align="center">
            <template #default="{ row }">
              <el-checkbox
                :model-value="isSelected(row.path)"
                :disabled="progressBusy"
                @change="(val) => toggleItem(row.path, val)"
              />
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="STT" width="60" align="center">
            <template #default="{ $index }">
              {{ (page - 1) * perPage + $index + 1 }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Hình ảnh" width="90" align="center">
            <template #default="{ row }">
              <div v-if="row.kind === 'zip'" class="table-zip">
                <el-icon :size="22"><Files /></el-icon>
              </div>
              <el-image
                v-else
                :src="imageUrl(row)"
                :preview-src-list="previewUrls"
                :initial-index="previewIndex(row)"
                fit="cover"
                class="table-thumb"
                preview-teleported
              >
                <template #error>
                  <span class="no-image">—</span>
                </template>
              </el-image>
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="name" label="Tên file" min-width="220" show-overflow-tooltip />
          <CustomTableColumn label="Dung lượng" width="120">
            <template #default="{ row }">
              {{ formatFileSize(row.size) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Ngày cập nhật" min-width="170">
            <template #default="{ row }">
              {{ formatDateTime(row.modified_at) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Thao tác" width="160" fixed="right" align="right">
            <template #default="{ row }">
              <div class="action-btns">
                <CustomTooltip v-if="row.kind === 'zip'" content="Giải nén" placement="top">
                  <CustomButton type="primary" link :icon="FolderOpened" :disabled="progressBusy" @click="onExtract(row)" />
                </CustomTooltip>
                <CustomTooltip content="Sao chép tên" placement="top">
                  <CustomButton type="primary" link :icon="CopyDocument" @click="copyFileName(row)" />
                </CustomTooltip>
                <CustomTooltip content="Sửa tên" placement="top">
                  <CustomButton type="primary" link :icon="Edit" @click="openEdit(row)" />
                </CustomTooltip>
                <CustomTooltip content="Xóa" placement="top">
                  <CustomButton type="danger" link :icon="Delete" :disabled="progressBusy || bulkDeleting" @click="removeItem(row)" />
                </CustomTooltip>
              </div>
            </template>
          </CustomTableColumn>
        </CustomTable>
      </div>

      <Pagination
        v-model="page"
        v-model:page-size="perPage"
        :total="total"
        :page-sizes="[12, 24, 48, 96]"
        :disabled="loading"
        @change="loadItems"
      />
    </CustomCard>

    <CustomDialog v-model="dialogVisible" title="Sửa tên hình ảnh" :width="480">
      <div v-if="editingItem" class="edit-preview">
        <div v-if="editingItem.kind === 'zip'" class="gallery-zip edit-preview-image">
          <el-icon :size="42"><Files /></el-icon>
          <span>ZIP</span>
        </div>
        <el-image v-else :src="imageUrl(editingItem)" fit="cover" class="edit-preview-image" />
      </div>
      <CustomForm ref="formRef" :model="form" :rules="rules">
        <CustomFormItem label="Tên file" prop="name">
          <CustomInput
            v-model="form.name"
            placeholder="vd: ao-cuoi.jpg"
            maxlength="255"
          />
        </CustomFormItem>
      </CustomForm>
      <template #footer>
        <CustomButton @click="dialogVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="saving" @click="save">Lưu</CustomButton>
      </template>
    </CustomDialog>
    <CustomDialog
      v-model="progressVisible"
      title="Tải / giải nén hình ảnh"
      :width="480"
      :close-on-click-modal="!progressBusy"
      :show-close="!progressBusy"
      :close-on-press-escape="!progressBusy"
    >
      <p class="progress-text">{{ progressText }}</p>
      <el-progress :percentage="progressPercent" :stroke-width="16" />
      <template #footer>
        <CustomButton :disabled="progressBusy" @click="progressVisible = false">Đóng</CustomButton>
      </template>
    </CustomDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { CopyDocument, Delete, Edit, Files, FolderOpened, Grid, List, Search, Upload } from '@element-plus/icons-vue'
import { deleteHinhAnhTrangPhuc, fetchHinhAnhTrangPhuc, updateHinhAnhTrangPhuc } from '@/api/trangPhuc'
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
import { useHinhAnhTrangPhucUpload } from '@/composables/useHinhAnhTrangPhucUpload'
import { apiOrigin, mediaUrl } from '@/utils/media'

const VIEW_GRID = 'grid'
const VIEW_TABLE = 'table'
const VIEW_MODE_KEY = 'hinh-anh-trang-phuc.viewMode'

const items = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(24)
const total = ref(0)
const keyword = ref('')
const viewMode = ref(localStorage.getItem(VIEW_MODE_KEY) === VIEW_TABLE ? VIEW_TABLE : VIEW_GRID)

const dialogVisible = ref(false)
const saving = ref(false)
const formRef = ref(null)
const editingItem = ref(null)
const form = reactive({ name: '' })
const fileInputRef = ref(null)
const selectedPaths = ref([])
const bulkDeleting = ref(false)
const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg']

const {
  progressVisible,
  progressPercent,
  progressText,
  progressBusy,
  uploadSelection,
  extractExisting,
} = useHinhAnhTrangPhucUpload()

const rules = {
  name: [
    { required: true, message: 'Vui lòng nhập tên file', trigger: 'blur' },
    { validator: validateFileName, trigger: 'blur' },
  ],
}

watch(viewMode, (val) => {
  localStorage.setItem(VIEW_MODE_KEY, val)
})

function fileExtension(filename) {
  const name = String(filename || '')
  const idx = name.lastIndexOf('.')
  return idx > 0 ? name.slice(idx + 1) : ''
}

function validateFileName(_rule, value, callback) {
  const name = String(value || '').trim()
  if (!name) {
    callback()
    return
  }

  const ext = fileExtension(name).toLowerCase()
  if (!ext || name.lastIndexOf('.') <= 0) {
    callback(new Error('Tên file phải gồm tên và đuôi (vd: ao-cuoi.jpg).'))
    return
  }

  if (!ALLOWED_EXTS.includes(ext) && !(editingItem.value?.kind === 'zip' && ext === 'zip')) {
    callback(new Error(`Đuôi file không hợp lệ. Chỉ chấp nhận: ${ALLOWED_EXTS.join(', ')}.`))
    return
  }

  callback()
}

function imageUrl(item) {
  const raw = item?.url || item?.path
  if (!raw) return ''

  try {
    const parsed = new URL(raw, `${apiOrigin()}/`)
    return `${apiOrigin()}${parsed.pathname}${parsed.search}`
  } catch {
    return mediaUrl(item.path) || String(raw)
  }
}

function formatFileSize(bytes) {
  const n = Number(bytes)
  if (!Number.isFinite(n) || n < 0) return '—'
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  return `${(n / (1024 * 1024)).toFixed(1)} MB`
}

function formatDateTime(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleString('vi-VN')
}

const previewItems = computed(() => items.value.filter((item) => item.kind !== 'zip'))
const previewUrls = computed(() => previewItems.value.map((item) => imageUrl(item)))

function previewIndex(item) {
  return previewItems.value.findIndex((row) => row.path === item.path)
}

const selectedCount = computed(() => selectedPaths.value.length)
const selectedSet = computed(() => new Set(selectedPaths.value))
const pageSelectedCount = computed(
  () => items.value.filter((item) => selectedSet.value.has(item.path)).length,
)
const allChecked = computed(
  () => items.value.length > 0 && pageSelectedCount.value === items.value.length,
)
const isIndeterminate = computed(
  () => pageSelectedCount.value > 0 && pageSelectedCount.value < items.value.length,
)

function isSelected(path) {
  return selectedSet.value.has(path)
}

function toggleItem(path, checked) {
  if (checked) {
    if (!selectedSet.value.has(path)) selectedPaths.value = [...selectedPaths.value, path]
    return
  }
  selectedPaths.value = selectedPaths.value.filter((item) => item !== path)
}

function toggleAll(checked) {
  const pagePaths = items.value.map((item) => item.path)
  if (checked) {
    selectedPaths.value = [...new Set([...selectedPaths.value, ...pagePaths])]
    return
  }
  const drop = new Set(pagePaths)
  selectedPaths.value = selectedPaths.value.filter((path) => !drop.has(path))
}

function clearSelection() {
  selectedPaths.value = []
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchHinhAnhTrangPhuc({
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

function openFilePicker() {
  fileInputRef.value?.click()
}

async function onFilesPicked(event) {
  const input = event.target
  const files = input.files ? [...input.files] : []
  input.value = ''
  if (!files.length) return

  try {
    const result = await uploadSelection(files)
    if (result) {
      page.value = 1
      await loadItems()
    }
  } catch {
    // interceptor
  }
}

async function onExtract(item) {
  try {
    const result = await extractExisting(item.path, item.name)
    if (result) {
      page.value = 1
      await loadItems()
    }
  } catch {
    // interceptor
  }
}

async function copyFileName(item) {
  const name = String(item?.name || '').trim()
  if (!name) return

  try {
    await navigator.clipboard.writeText(name)
    ElMessage.success('Đã sao chép tên file.')
  } catch {
    ElMessage.warning('Không thể sao chép. Vui lòng copy thủ công.')
  }
}

async function deleteFiles(paths, confirmText) {
  if (!paths.length) return false

  try {
    await ElMessageBox.confirm(confirmText, 'Xác nhận', {
      type: 'warning',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
    })
  } catch {
    return false
  }

  bulkDeleting.value = true
  try {
    const { data } = await deleteHinhAnhTrangPhuc({ paths })
    ElMessage.success(`Đã xóa ${data.count || paths.length} file.`)
    await loadItems()
    return true
  } catch {
    return false
  } finally {
    bulkDeleting.value = false
  }
}

async function removeItem(item) {
  if (!item?.path) return
  await deleteFiles([item.path], `Xóa "${item.name}"?`)
}

async function removeSelected() {
  const paths = [...selectedPaths.value]
  await deleteFiles(paths, `Xóa ${paths.length} file đã chọn?`)
}

function openEdit(item) {
  editingItem.value = item
  form.name = item?.name || ''
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid || !editingItem.value) return

  const newName = form.name.trim()
  if (!newName) return

  saving.value = true
  try {
    await updateHinhAnhTrangPhuc({
      path: editingItem.value.path,
      name: newName,
    })
    ElMessage.success('Đã cập nhật tên hình ảnh.')
    dialogVisible.value = false
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
}

onMounted(loadItems)
</script>

<style scoped lang="scss">
.view-toggle {
  gap: 6px;
}

.action-btns {
  display: flex;
  justify-content: flex-end;
  width: 100%;
}

.hidden-file-input {
  display: none;
}

.progress-text {
  margin: 0 0 12px;
  color: var(--el-text-color-regular);
}

.gallery-zip {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  height: 180px;
  background: var(--el-fill-color);
  color: var(--el-text-color-secondary);
  font-size: 12px;
  font-weight: 600;
}

.table-zip {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 4px;
  background: var(--el-fill-color);
  color: var(--el-text-color-secondary);
}

.gallery-wrap {
  min-height: 180px;
}

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 16px;
}

.card-header-left {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.gallery-item {
  position: relative;
  display: flex;
  flex-direction: column;
  min-width: 0;
  border-radius: 8px;
  overflow: hidden;
  background: var(--el-fill-color-light);

  &.is-selected {
    outline: 2px solid var(--el-color-primary);
    outline-offset: -2px;
  }
}

.gallery-check {
  position: absolute;
  top: 8px;
  left: 8px;
  z-index: 2;
  margin: 0;

  :deep(.el-checkbox__inner) {
    background-color: #fff;
  }
}

.gallery-image {
  width: 100%;
  height: 180px;
  display: block;
  background: var(--el-fill-color);
}

.gallery-error {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  font-size: 12px;
  color: var(--el-text-color-placeholder);
}

.gallery-meta {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 8px 6px 8px 10px;
}

.gallery-name {
  flex: 1;
  min-width: 0;
  font-size: 12px;
  line-height: 1.4;
  color: var(--el-text-color-regular);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.table-thumb {
  width: 48px;
  height: 48px;
  border-radius: 4px;
  overflow: hidden;
  background: var(--el-fill-color);
}

.edit-preview {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.edit-preview-image {
  width: 160px;
  height: 160px;
  border-radius: 8px;
  overflow: hidden;
  background: var(--el-fill-color);
}

@media (max-width: 991px) {
  .gallery-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 8px;
  }

  .gallery-image,
  .gallery-zip {
    height: 140px;
  }
}
</style>
