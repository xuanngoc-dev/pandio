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
          <span class="card-title">Hình ảnh trang phục</span>
          <div class="card-header-right view-toggle">
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
            v-for="(item, index) in items"
            :key="item.path"
            class="gallery-item"
          >
            <el-image
              :src="imageUrl(item)"
              :preview-src-list="previewUrls"
              :initial-index="index"
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
              <CustomTooltip content="Sửa tên" placement="top">
                <CustomButton type="primary" link :icon="Edit" @click="openEdit(item)" />
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
          <CustomTableColumn label="STT" width="60" align="center">
            <template #default="{ $index }">
              {{ (page - 1) * perPage + $index + 1 }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Hình ảnh" width="90" align="center">
            <template #default="{ row, $index }">
              <el-image
                :src="imageUrl(row)"
                :preview-src-list="previewUrls"
                :initial-index="$index"
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
          <CustomTableColumn label="Thao tác" width="90" fixed="right" align="center">
            <template #default="{ row }">
              <CustomTooltip content="Sửa tên" placement="top">
                <CustomButton type="primary" link :icon="Edit" @click="openEdit(row)" />
              </CustomTooltip>
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
        <el-image :src="imageUrl(editingItem)" fit="cover" class="edit-preview-image" />
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
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Edit, Grid, List, Search } from '@element-plus/icons-vue'
import { fetchHinhAnhTrangPhuc, updateHinhAnhTrangPhuc } from '@/api/trangPhuc'
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
const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg']

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

  if (!ALLOWED_EXTS.includes(ext)) {
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

const previewUrls = computed(() => items.value.map((item) => imageUrl(item)))

async function loadItems() {
  loading.value = true
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

.gallery-wrap {
  min-height: 180px;
}

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 16px;
}

.gallery-item {
  display: flex;
  flex-direction: column;
  min-width: 0;
  border-radius: 8px;
  overflow: hidden;
  background: var(--el-fill-color-light);
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

  .gallery-image {
    height: 140px;
  }
}
</style>
