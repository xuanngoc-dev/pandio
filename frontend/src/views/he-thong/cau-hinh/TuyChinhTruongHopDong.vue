<template>
  <ConfigSettingPage title="Tuỳ chỉnh trường theo loại hợp đồng khách hàng">
    <div class="loai-hop-dong">
      <CustomCard shadow="hover" class="filter-card">
        <div class="toolbar">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo tên, mã hợp đồng..."
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
            v-model="trangThaiFilter"
            placeholder="Trạng thái"
            clearable
            style="width: 180px"
            @change="onSearch"
          >
            <CustomOption label="Đang hoạt động" value="hoat_dong" />
            <CustomOption label="Ngừng hoạt động" value="ngung_hoat_dong" />
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
            <span class="card-title">Danh sách loại hợp đồng khách hàng</span>
            <CustomButton type="primary" @click="openCreate">
              <CustomIcon><Plus /></CustomIcon>
              Thêm loại hợp đồng
            </CustomButton>
          </div>
        </template>

        <CustomTable v-loading="loading" :data="items" stripe style="width: 100%">
          <CustomTableColumn label="STT" width="60" align="center">
            <template #default="{ $index }">
              {{ (page - 1) * perPage + $index + 1 }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="ma_hop_dong" label="Mã" width="140" />
          <CustomTableColumn prop="ten_hop_dong" label="Tên hợp đồng" min-width="200" show-overflow-tooltip />
          <CustomTableColumn label="Số trường" width="100" align="center">
            <template #default="{ row }">
              {{ countFields(row.noi_dung) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="trang_thai" label="Trạng thái" width="190" align="center">
            <template #default="{ row }">
              <div class="status-cell">
                <el-switch
                  :model-value="row.trang_thai"
                  active-value="hoat_dong"
                  inactive-value="ngung_hoat_dong"
                  :loading="togglingId === row.id"
                  :disabled="togglingId === row.id"
                  :before-change="() => toggleStatus(row)"
                />
                <span
                  class="status-label"
                  :class="row.trang_thai === 'hoat_dong' ? 'is-active' : 'is-inactive'"
                >
                  {{ row.trang_thai === 'hoat_dong' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}
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
        :title="editingId ? 'Sửa loại hợp đồng khách hàng' : 'Thêm loại hợp đồng khách hàng'"
        :width="1200"
      >
        <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
          <CustomRow :gutter="16">
            <CustomCol :xs="24" :sm="8">
              <CustomFormItem label="Mã hợp đồng" prop="ma_hop_dong">
                <CustomInput
                  v-model="form.ma_hop_dong"
                  :disabled="!!editingId"
                  placeholder="VD: SDDV"
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="8">
              <CustomFormItem label="Tên hợp đồng" prop="ten_hop_dong">
                <CustomInput
                  v-model="form.ten_hop_dong"
                  placeholder="VD: Hợp đồng SDDV"
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="8">
              <CustomFormItem label="Trạng thái" prop="trang_thai">
                <CustomSelect v-model="form.trang_thai" style="width: 100%">
                  <CustomOption label="Đang hoạt động" value="hoat_dong" />
                  <CustomOption label="Ngừng hoạt động" value="ngung_hoat_dong" />
                </CustomSelect>
              </CustomFormItem>
            </CustomCol>
          </CustomRow>

          <div class="fields-header">
            <span class="fields-title">Nội dung trường hợp đồng</span>
            <CustomButton v-if="form.truong.length" type="primary" plain @click="addField">
              <CustomIcon><Plus /></CustomIcon>
              Thêm trường
            </CustomButton>
          </div>

          <div v-if="!form.truong.length" class="fields-empty">
            <p>Chưa có trường nào.</p>
            <CustomButton type="primary" @click="addField">
              <CustomIcon><Plus /></CustomIcon>
              Thêm trường
            </CustomButton>
          </div>

          <div
            v-for="(item, index) in form.truong"
            :key="item._key"
            class="field-card"
          >
            <CustomRow :gutter="12">
              <CustomCol :xs="24" :sm="6">
                <CustomFormItem
                  label="Tên trường"
                  :prop="`truong.${index}.ten_truong`"
                  :rules="fieldRules.ten_truong"
                >
                  <CustomInput
                    v-model="item.ten_truong"
                    placeholder="VD: Tên chú rể"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="6">
                <CustomFormItem
                  label="Key"
                  :prop="`truong.${index}.key`"
                  :rules="fieldRules.key"
                >
                  <CustomInput
                    v-model="item.key"
                    placeholder="VD: tenChuRe"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="6">
                <CustomFormItem
                  label="Kiểu dữ liệu"
                  :prop="`truong.${index}.kieu`"
                  :rules="fieldRules.kieu"
                >
                  <CustomSelect
                    v-model="item.kieu"
                    placeholder="Chọn kiểu"
                    style="width: 100%"
                    @change="(val) => onKieuChange(item, val)"
                  >
                    <template v-for="group in kieuDuLieuOptionGroups" :key="group.label">
                      <el-option-group :label="group.label">
                        <CustomOption
                          v-for="opt in group.options"
                          :key="opt.value"
                          :label="opt.label"
                          :value="opt.value"
                        />
                      </el-option-group>
                    </template>
                  </CustomSelect>
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="24" :sm="6">
                <CustomFormItem label="Bắt buộc">
                  <div class="field-required-row">
                    <el-switch
                      v-model="item.bat_buoc"
                      inline-prompt
                      active-text="Có"
                      inactive-text="Không"
                    />
                    <CustomButton type="danger" link :icon="Delete" @click="removeField(index)">
                      Xóa
                    </CustomButton>
                  </div>
                </CustomFormItem>
              </CustomCol>

              <CustomCol v-if="hasFieldOptions(item.kieu)" :span="24">
                <div class="options-block">
                  <div class="options-block__header">
                    <span class="options-block__title">Tùy chọn (options)</span>
                    <CustomButton link type="primary" @click="addOption(item)">
                      <CustomIcon><Plus /></CustomIcon>
                      Thêm option
                    </CustomButton>
                  </div>

                  <div v-if="!item.options.length" class="options-empty">
                    Chưa có option. Nhấn "Thêm option" để thêm.
                  </div>

                  <div
                    v-for="(opt, optIndex) in item.options"
                    :key="opt._key"
                    class="option-row"
                  >
                    <CustomFormItem
                      class="option-row__item"
                      label="Nhãn"
                      :prop="`truong.${index}.options.${optIndex}.label`"
                      :rules="fieldRules.optionLabel"
                    >
                      <CustomInput v-model="opt.label" placeholder="VD: Nam" />
                    </CustomFormItem>
                    <CustomFormItem
                      class="option-row__item"
                      label="Giá trị"
                      :prop="`truong.${index}.options.${optIndex}.value`"
                      :rules="fieldRules.optionValue"
                    >
                      <CustomInput v-model="opt.value" placeholder="VD: nam" />
                    </CustomFormItem>
                    <CustomButton
                      type="danger"
                      link
                      :icon="Delete"
                      class="option-row__remove"
                      @click="removeOption(item, optIndex)"
                    />
                  </div>
                </div>
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
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createLoaiHopDong,
  deleteLoaiHopDong,
  fetchLoaiHopDong,
  updateLoaiHopDong,
} from '@/api/loaiHopDong'
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

const KIEU_CO_TUY_CHON = ['select', 'radio', 'checkbox_group']

const kieuDuLieuOptionGroups = [
  {
    label: 'Văn bản',
    options: [
      { label: 'Văn bản ngắn', value: 'input' },
      { label: 'Văn bản dài', value: 'textarea' },
      { label: 'Email', value: 'email' },
      { label: 'Số điện thoại', value: 'phone' },
      { label: 'Liên kết (URL)', value: 'url' },
    ],
  },
  {
    label: 'Số liệu',
    options: [
      { label: 'Số', value: 'number' },
      { label: 'Tiền tệ', value: 'money' },
      { label: 'Phần trăm', value: 'percent' },
    ],
  },
  {
    label: 'Lựa chọn',
    options: [
      { label: 'Danh sách chọn', value: 'select' },
      { label: 'Lựa chọn đơn (radio)', value: 'radio' },
      { label: 'Checkbox', value: 'checkbox' },
      { label: 'Nhiều lựa chọn', value: 'checkbox_group' },
      { label: 'Bật/Tắt', value: 'switch' },
    ],
  },
  {
    label: 'Thời gian',
    options: [
      { label: 'Ngày', value: 'date' },
      { label: 'Ngày giờ', value: 'datetime' },
      { label: 'Giờ', value: 'time' },
      { label: 'Tháng', value: 'month' },
      { label: 'Năm', value: 'year' },
    ],
  },
  {
    label: 'Tệp tin',
    options: [
      { label: 'Tệp đính kèm', value: 'file' },
      { label: 'Hình ảnh', value: 'image' },
    ],
  },
]

function hasFieldOptions(kieu) {
  return KIEU_CO_TUY_CHON.includes(kieu)
}

let fieldKeySeed = 0
let optionKeySeed = 0

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const togglingId = ref(null)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const trangThaiFilter = ref('')

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)

const emptyForm = () => ({
  ten_hop_dong: '',
  ma_hop_dong: '',
  trang_thai: 'hoat_dong',
  truong: [],
})

const form = reactive(emptyForm())

const rules = {
  ma_hop_dong: [{ required: true, message: 'Vui lòng nhập mã hợp đồng', trigger: 'blur' }],
  ten_hop_dong: [{ required: true, message: 'Vui lòng nhập tên hợp đồng', trigger: 'blur' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

const fieldRules = {
  ten_truong: [{ required: true, message: 'Vui lòng nhập tên trường', trigger: 'blur' }],
  key: [
    { required: true, message: 'Vui lòng nhập key', trigger: 'blur' },
    {
      pattern: /^[a-zA-Z][a-zA-Z0-9_]*$/,
      message: 'Key chỉ gồm chữ, số, _ và bắt đầu bằng chữ',
      trigger: 'blur',
    },
  ],
  kieu: [{ required: true, message: 'Vui lòng chọn kiểu dữ liệu', trigger: 'change' }],
  optionLabel: [{ required: true, message: 'Vui lòng nhập nhãn', trigger: 'blur' }],
  optionValue: [{ required: true, message: 'Vui lòng nhập giá trị', trigger: 'blur' }],
}

function nextFieldKey() {
  fieldKeySeed += 1
  return `field-${fieldKeySeed}`
}

function nextOptionKey() {
  optionKeySeed += 1
  return `option-${optionKeySeed}`
}

function createOption(data = {}) {
  return {
    _key: nextOptionKey(),
    label: data.label || '',
    value: data.value || '',
  }
}

function createField(data = {}) {
  return {
    _key: nextFieldKey(),
    ten_truong: data.ten_truong || '',
    key: data.key || '',
    kieu: data.kieu || 'input',
    bat_buoc: !!data.bat_buoc,
    options: Array.isArray(data.options)
      ? data.options.map((opt) => createOption(opt))
      : [],
  }
}

function parseNoiDung(noiDung) {
  const truong = Array.isArray(noiDung?.truong) ? noiDung.truong : []
  return truong.map((item) => createField(item))
}

function buildNoiDungPayload() {
  return {
    truong: form.truong.map((item) => {
      const field = {
        ten_truong: item.ten_truong.trim(),
        key: item.key.trim(),
        kieu: item.kieu,
        bat_buoc: !!item.bat_buoc,
      }

      if (hasFieldOptions(item.kieu)) {
        field.options = item.options.map((opt) => ({
          label: opt.label.trim(),
          value: opt.value.trim(),
        }))
      }

      return field
    }),
  }
}

function countFields(noiDung) {
  return Array.isArray(noiDung?.truong) ? noiDung.truong.length : 0
}

function addField() {
  form.truong.push(createField())
}

function removeField(index) {
  form.truong.splice(index, 1)
}

function addOption(field) {
  field.options.push(createOption())
}

function removeOption(field, index) {
  field.options.splice(index, 1)
}

function onKieuChange(field, kieu) {
  if (hasFieldOptions(kieu) && !field.options.length) {
    field.options.push(createOption())
  }
  if (!hasFieldOptions(kieu)) {
    field.options = []
  }
}

function validateUniqueKeys() {
  const keys = form.truong.map((item) => item.key.trim()).filter(Boolean)
  const duplicates = keys.filter((key, index) => keys.indexOf(key) !== index)
  if (duplicates.length) {
    ElMessage.warning(`Key bị trùng: ${[...new Set(duplicates)].join(', ')}`)
    return false
  }
  return true
}

function validateSelectOptions() {
  for (const item of form.truong) {
    if (hasFieldOptions(item.kieu) && !item.options.length) {
      ElMessage.warning(`Trường "${item.ten_truong || item.key || 'chưa đặt tên'}" cần ít nhất 1 tùy chọn.`)
      return false
    }
  }
  return true
}

async function toggleStatus(row) {
  if (!row?.id) return false

  const value = row.trang_thai === 'hoat_dong' ? 'ngung_hoat_dong' : 'hoat_dong'
  togglingId.value = row.id

  try {
    await updateLoaiHopDong(row.id, {
      ten_hop_dong: row.ten_hop_dong,
      ma_hop_dong: row.ma_hop_dong,
      noi_dung: row.noi_dung || { truong: [] },
      trang_thai: value,
    })
    row.trang_thai = value
    ElMessage.success(
      value === 'hoat_dong' ? 'Đã bật loại hợp đồng.' : 'Đã ngừng loại hợp đồng.',
    )
    return true
  } catch {
    return false
  } finally {
    togglingId.value = null
  }
}

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchLoaiHopDong({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
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
  Object.assign(form, emptyForm())
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  Object.assign(form, {
    ten_hop_dong: row.ten_hop_dong,
    ma_hop_dong: row.ma_hop_dong,
    trang_thai: row.trang_thai || 'hoat_dong',
    truong: parseNoiDung(row.noi_dung),
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  if (!validateUniqueKeys()) return
  if (!validateSelectOptions()) return

  saving.value = true
  const payload = {
    ten_hop_dong: form.ten_hop_dong.trim(),
    ma_hop_dong: form.ma_hop_dong.trim(),
    noi_dung: buildNoiDungPayload(),
    trang_thai: form.trang_thai,
  }

  try {
    if (editingId.value) {
      await updateLoaiHopDong(editingId.value, payload)
      ElMessage.success('Đã cập nhật loại hợp đồng.')
    } else {
      await createLoaiHopDong(payload)
      ElMessage.success('Đã thêm loại hợp đồng.')
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
  await ElMessageBox.confirm(
    `Xóa loại hợp đồng "${row.ten_hop_dong}" (${row.ma_hop_dong})?`,
    'Xác nhận',
    {
      type: 'warning',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
    },
  )

  try {
    await deleteLoaiHopDong(row.id)
    ElMessage.success('Đã xóa loại hợp đồng.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadItems)
</script>

<style scoped>
.loai-hop-dong {
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

.status-cell {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.status-label {
  font-size: 13px;
  line-height: 1.2;
  white-space: nowrap;
}

.status-label.is-active {
  color: var(--el-color-success);
}

.status-label.is-inactive {
  color: var(--el-text-color-secondary);
}

.fields-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 8px 0 12px;
}

.fields-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.fields-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 28px 16px;
  border: 1px dashed var(--el-border-color);
  border-radius: 8px;
  color: var(--el-text-color-secondary);
  text-align: center;
}

.fields-empty p {
  margin: 0;
}

.field-card {
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  padding: 12px 14px 4px;
  margin-bottom: 12px;
  background: var(--el-fill-color-blank);
}

.field-required-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.options-block {
  width: 100%;
  padding: 10px 12px;
  border: 1px dashed var(--el-border-color-lighter);
  border-radius: 8px;
  margin-bottom: 8px;
  background: var(--el-fill-color-light);
}

.options-block__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.options-block__title {
  font-size: 13px;
  font-weight: 600;
  color: var(--el-text-color-regular);
}

.options-empty {
  font-size: 13px;
  color: var(--el-text-color-secondary);
  margin-bottom: 8px;
}

.option-row {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  margin-bottom: 4px;
}

.option-row__item {
  flex: 1;
  margin-bottom: 0;
}

.option-row__remove {
  margin-bottom: 18px;
}
</style>
