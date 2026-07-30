<template>
  <ConfigSettingPage title="Form đánh giá">
    <div class="form-danh-gia">
      <CustomCard shadow="hover" class="filter-card">
        <div class="toolbar">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo tên form, slug..."
            clearable
            style="max-width: 300px"
            @clear="onSearch"
            @keyup.enter="onSearch"
          >
            <template #prefix>
              <CustomIcon><Search /></CustomIcon>
            </template>
          </CustomInput>
          <CustomButton type="primary" plain @click="onSearch">
            <CustomIcon><Search /></CustomIcon>
            Tìm kiếm
          </CustomButton>
        </div>
      </CustomCard>

      <CustomCard shadow="hover" class="table-card">
        <template #header>
          <div class="card-header">
            <span class="card-title">Danh sách form đánh giá mẫu</span>
            <BulkActionBar :actions="bulkActions" @action="onBulkAction">
              <CustomButton type="primary" @click="openCreate">
                <CustomIcon><Plus /></CustomIcon>
                Thêm form
              </CustomButton>
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
          <CustomTableColumn prop="ten_form" label="Tên form" min-width="200" show-overflow-tooltip />
          <CustomTableColumn prop="slug" label="Slug" min-width="180" show-overflow-tooltip>
            <template #default="{ row }">
              <CustomTooltip content="Mở trang đánh giá khách hàng" placement="top">
                <button type="button" class="slug-link" @click="openCustomerForm(row)">
                  {{ row.slug }}
                </button>
              </CustomTooltip>
            </template>
          </CustomTableColumn>
          <CustomTableColumn label="Số câu hỏi" width="110" align="center">
            <template #default="{ row }">
              {{ countQuestions(row.cau_hoi) }}
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
        :title="editingId ? 'Sửa form đánh giá' : 'Thêm form đánh giá'"
        :width="920"
      >
        <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
          <CustomFormItem label="Tên form" prop="ten_form">
            <CustomInput
              v-model="form.ten_form"
              placeholder="VD: Đánh giá sau buổi chụp, NPS giới thiệu..."
            />
          </CustomFormItem>

          <div class="questions-header">
            <span class="questions-title">Danh sách câu hỏi</span>
            <CustomButton type="primary" plain @click="addQuestion">
              <CustomIcon><Plus /></CustomIcon>
              Thêm câu hỏi
            </CustomButton>
          </div>

          <div v-if="!form.cau_hoi.length" class="questions-empty">
            Chưa có câu hỏi. Nhấn "Thêm câu hỏi" để bắt đầu.
          </div>

          <div
            v-for="(item, index) in form.cau_hoi"
            :key="item._key"
            class="question-card"
          >
            <div class="question-card__header">
              <span class="question-card__index">Câu hỏi {{ index + 1 }}</span>
              <CustomButton type="danger" link :icon="Delete" @click="removeQuestion(index)">
                Xóa
              </CustomButton>
            </div>

            <CustomRow :gutter="12">
              <CustomCol :span="24">
                <CustomFormItem
                  :label="'Nội dung câu hỏi'"
                  :prop="`cau_hoi.${index}.cau_hoi`"
                  :rules="questionRules.cau_hoi"
                >
                  <CustomInput
                    v-model="item.cau_hoi"
                    type="textarea"
                    :rows="2"
                    placeholder="Nhập nội dung câu hỏi"
                  />
                </CustomFormItem>
              </CustomCol>

              <CustomCol :xs="24" :sm="8">
                <CustomFormItem
                  label="Loại đánh giá"
                  :prop="`cau_hoi.${index}.loai_danh_gia`"
                  :rules="questionRules.loai_danh_gia"
                >
                  <CustomSelect
                    v-model="item.loai_danh_gia"
                    placeholder="Chọn loại"
                    style="width: 100%"
                  >
                    <CustomOption label="Điểm" value="diem" />
                    <CustomOption label="Văn bản" value="van_ban" />
                  </CustomSelect>
                </CustomFormItem>
              </CustomCol>

              <CustomCol :xs="24" :sm="10">
                <CustomFormItem
                  label="Thông tin đánh giá"
                  :prop="`cau_hoi.${index}.thong_tin_danh_gia`"
                  :rules="questionRules.thong_tin_danh_gia"
                >
                  <CustomSelect
                    v-model="item.thong_tin_danh_gia"
                    filterable
                    allow-create
                    default-first-option
                    placeholder="Chọn hoặc tạo mới"
                    style="width: 100%"
                    @change="(val) => onThongTinChange(val)"
                  >
                    <CustomOption
                      v-for="opt in thongTinOptions"
                      :key="opt"
                      :label="opt"
                      :value="opt"
                    />
                  </CustomSelect>
                </CustomFormItem>
              </CustomCol>

              <CustomCol :xs="24" :sm="6">
                <CustomFormItem
                  label="Bắt buộc"
                  :prop="`cau_hoi.${index}.required`"
                >
                  <el-switch
                    v-model="item.required"
                    inline-prompt
                    active-text="Có"
                    inactive-text="Không"
                  />
                </CustomFormItem>
              </CustomCol>
            </CustomRow>
          </div>
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
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createFormDanhGia,
  deleteFormDanhGia,
  fetchFormDanhGia,
  updateFormDanhGia,
} from '@/api/formDanhGia'
import BulkActionBar from '@/components/BulkActionBar.vue'
import { runBulk, useBulkSelection } from '@/composables/useBulkSelection'
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
import ConfigSettingPage from './ConfigSettingPage.vue'

const router = useRouter()

const DEFAULT_THONG_TIN = [
  'Thợ chụp',
  'Thợ make',
  'Thợ hậu kỳ',
  'Giới thiệu cửa hàng (NPS)',
  'Màu ảnh',
  'Nắn chỉnh dáng',
  'Trang phục',
  'Thái độ phục vụ',
]

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
const thongTinOptions = ref([...DEFAULT_THONG_TIN])
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
      ? `Xóa ${selectedCount.value} form đã chọn`
      : 'Chọn form để xóa',
  },
])

let questionKey = 0

const emptyQuestion = () => ({
  _key: ++questionKey,
  cau_hoi: '',
  loai_danh_gia: 'diem',
  thong_tin_danh_gia: '',
  required: true,
})

const emptyForm = () => ({
  ten_form: '',
  cau_hoi: [],
})

const form = reactive(emptyForm())

const rules = {
  ten_form: [{ required: true, message: 'Vui lòng nhập tên form', trigger: 'blur' }],
}

const questionRules = {
  cau_hoi: [{ required: true, message: 'Vui lòng nhập nội dung câu hỏi', trigger: 'blur' }],
  loai_danh_gia: [{ required: true, message: 'Vui lòng chọn loại đánh giá', trigger: 'change' }],
  thong_tin_danh_gia: [
    { required: true, message: 'Vui lòng chọn thông tin đánh giá', trigger: 'change' },
  ],
}

function countQuestions(value) {
  return Array.isArray(value) ? value.length : 0
}

function openCustomerForm(row) {
  if (!row?.slug) return
  const resolved = router.resolve({
    name: 'danh-gia-khach',
    params: { slug: row.slug },
  })
  window.open(resolved.href, '_blank', 'noopener,noreferrer')
}

function resetThongTinOptions(extraValues = []) {
  const merged = [...DEFAULT_THONG_TIN]
  for (const value of extraValues) {
    const trimmed = (value || '').trim()
    if (trimmed && !merged.includes(trimmed)) {
      merged.push(trimmed)
    }
  }
  thongTinOptions.value = merged
}

function onThongTinChange(value) {
  const trimmed = (value || '').trim()
  if (trimmed && !thongTinOptions.value.includes(trimmed)) {
    thongTinOptions.value = [...thongTinOptions.value, trimmed]
  }
}

function addQuestion() {
  form.cau_hoi.push(emptyQuestion())
}

function removeQuestion(index) {
  form.cau_hoi.splice(index, 1)
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchFormDanhGia({
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

function openCreate() {
  editingId.value = null
  Object.assign(form, emptyForm())
  resetThongTinOptions()
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  const questions = (row.cau_hoi || []).map((q) => ({
    _key: ++questionKey,
    cau_hoi: q.cau_hoi || '',
    loai_danh_gia: q.loai_danh_gia || 'diem',
    thong_tin_danh_gia: q.thong_tin_danh_gia || '',
    required: q.required !== false,
  }))

  Object.assign(form, {
    ten_form: row.ten_form || '',
    cau_hoi: questions,
  })
  resetThongTinOptions(questions.map((q) => q.thong_tin_danh_gia))
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  if (!form.cau_hoi.length) {
    ElMessage.warning('Vui lòng thêm ít nhất một câu hỏi.')
    return
  }

  saving.value = true
  const payload = {
    ten_form: form.ten_form.trim(),
    cau_hoi: form.cau_hoi.map((q) => ({
      cau_hoi: q.cau_hoi.trim(),
      loai_danh_gia: q.loai_danh_gia,
      thong_tin_danh_gia: q.thong_tin_danh_gia.trim(),
      required: !!q.required,
    })),
  }

  try {
    if (editingId.value) {
      await updateFormDanhGia(editingId.value, payload)
      ElMessage.success('Đã cập nhật form đánh giá.')
    } else {
      await createFormDanhGia(payload)
      ElMessage.success('Đã thêm form đánh giá.')
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

  await ElMessageBox.confirm(`Xóa ${ids.length} form đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteFormDanhGia(id))
    ElMessage.success(`Đã xóa ${ids.length} form.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa form "${row.ten_form}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteFormDanhGia(row.id)
    ElMessage.success('Đã xóa form đánh giá.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadItems)
</script>

<style scoped>
.form-danh-gia {
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

.slug-link {
  padding: 0;
  border: none;
  background: none;
  color: var(--el-color-primary);
  cursor: pointer;
  font: inherit;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.slug-link:hover {
  color: var(--el-color-primary-light-3);
}

.questions-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 8px 0 12px;
}

.questions-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.questions-empty {
  padding: 20px;
  text-align: center;
  color: var(--el-text-color-secondary);
  border: 1px dashed var(--el-border-color);
  border-radius: 8px;
  margin-bottom: 12px;
}

.question-card {
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  padding: 12px 14px 4px;
  margin-bottom: 12px;
  background: var(--el-fill-color-blank);
}

.question-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.question-card__index {
  font-weight: 600;
  color: var(--el-text-color-regular);
}
</style>
