<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1280"
    class="hop-dong-sddv-dieu-phoi-modal"
    @closed="onClosed"
  >
    <div v-loading="loading" class="dieu-phoi-body">
      <el-empty
        v-if="!loading && !fields.length"
        description="Loại hợp đồng chưa cấu hình thông tin điều phối."
      />

      <CustomForm
        v-else-if="fields.length"
        ref="formRef"
        :model="values"
        label-position="top"
      >
        <CustomRow v-if="normalFields.length" :gutter="16">
          <CustomCol
            v-for="field in normalFields"
            :key="field.key"
            v-bind="normalFieldColProps"
          >
            <CustomFormItem :label="field.ten_thong_tin" :prop="field.key">
              <template v-if="field.loai_du_lieu === 'date'">
                <el-date-picker
                  v-model="values[field.key]"
                  type="date"
                  format="DD/MM/YYYY"
                  value-format="YYYY-MM-DD"
                  :placeholder="`Chọn ${field.ten_thong_tin.toLowerCase()}`"
                  style="width: 100%"
                  clearable
                />
              </template>

              <template v-else-if="field.loai_du_lieu === 'time'">
                <el-time-picker
                  v-model="values[field.key]"
                  format="HH:mm"
                  value-format="HH:mm"
                  :placeholder="`Chọn ${field.ten_thong_tin.toLowerCase()}`"
                  style="width: 100%"
                  clearable
                />
              </template>

              <template v-else-if="field.loai_du_lieu === 'array' && isStaffField(field.key)">
                <CustomSelect
                  v-model="values[field.key]"
                  :placeholder="`Chọn ${field.ten_thong_tin.toLowerCase()}`"
                  multiple
                  filterable
                  collapse-tags
                  collapse-tags-tooltip
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
              </template>

              <template v-else-if="field.loai_du_lieu === 'array'">
                <CustomSelect
                  v-model="values[field.key]"
                  :placeholder="`Chọn hoặc nhập ${field.ten_thong_tin.toLowerCase()}`"
                  multiple
                  filterable
                  allow-create
                  default-first-option
                  collapse-tags
                  collapse-tags-tooltip
                  clearable
                  style="width: 100%"
                >
                  <CustomOption
                    v-for="opt in getArrayOptions(field.key)"
                    :key="opt"
                    :label="opt"
                    :value="opt"
                  />
                </CustomSelect>
              </template>

              <template v-else>
                <CustomInput
                  v-model="values[field.key]"
                  :placeholder="`Nhập ${field.ten_thong_tin.toLowerCase()}`"
                  clearable
                />
              </template>
            </CustomFormItem>
          </CustomCol>
        </CustomRow>

        <CustomRow v-if="textareaFields.length" :gutter="16" class="textarea-row">
          <CustomCol
            v-for="field in textareaFields"
            :key="field.key"
            v-bind="textareaFieldColProps"
          >
            <CustomFormItem :label="field.ten_thong_tin" :prop="field.key">
              <CustomInput
                v-model="values[field.key]"
                type="textarea"
                :rows="3"
                :placeholder="`Nhập ${field.ten_thong_tin.toLowerCase()}`"
              />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Hủy</CustomButton>
        <CustomButton
          type="primary"
          :loading="saving"
          :disabled="loading || !fields.length"
          @click="save"
        >
          Lưu
        </CustomButton>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import {
  getHopDongSuDungDichVu,
  updateHopDongSuDungDichVu,
} from '@/api/hopDongSuDungDichVu'
import { getLoaiHopDong } from '@/api/loaiHopDong'
import { fetchUsers } from '@/api/users'
import {
  CustomButton,
  CustomCol,
  CustomDialog,
  CustomForm,
  CustomFormItem,
  CustomInput,
  CustomOption,
  CustomRow,
  CustomSelect,
} from '@/components/element'

const STAFF_FIELD_KEYS = new Set(['tho_chup', 'tho_make', 'tho_edit', 'quay_phim'])

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  hopDongId: { type: [Number, String], default: null },
})

const emit = defineEmits(['update:modelValue', 'saved', 'closed'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const loading = ref(false)
const saving = ref(false)
const formRef = ref(null)
const hopDong = ref(null)
const fields = ref([])
const values = reactive({})
const fieldMeta = ref({})
const userOptions = ref([])

const dialogTitle = computed(() => {
  const ma = hopDong.value?.ma_hop_dong
  return ma ? `Điều phối — ${ma}` : 'Điều phối'
})

const normalFields = computed(() =>
  fields.value.filter((field) => field.loai_du_lieu !== 'textarea'),
)

const textareaFields = computed(() =>
  fields.value.filter((field) => field.loai_du_lieu === 'textarea'),
)

const normalFieldColProps = { xs: 12, sm: 8, md: 6, lg: 6, xl: 6 }
const textareaFieldColProps = { xs: 24, sm: 12, md: 12, lg: 12, xl: 12 }

function isStaffField(key) {
  return STAFF_FIELD_KEYS.has(key)
}

function defaultValueByLoai(loai) {
  return loai === 'array' ? [] : null
}

function normalizeArrayValue(value) {
  if (Array.isArray(value)) return [...value]
  if (value == null || value === '') return []
  return [value]
}

function clearValues() {
  Object.keys(values).forEach((key) => {
    delete values[key]
  })
}

function getArrayOptions(key) {
  const current = normalizeArrayValue(values[key])
  return [...new Set(current.map((item) => String(item)).filter(Boolean))]
}

function buildFieldsFromSchema(schema, saved) {
  const source =
    schema && typeof schema === 'object' && !Array.isArray(schema) ? schema : {}
  const savedMap =
    saved && typeof saved === 'object' && !Array.isArray(saved) ? saved : {}

  const nextFields = []
  const nextMeta = {}

  for (const [key, item] of Object.entries(source)) {
    if (!item || typeof item !== 'object') continue
    if (item.su_dung === false) continue

    const loai = item.loai_du_lieu || 'string'
    const savedItem =
      savedMap[key] && typeof savedMap[key] === 'object' ? savedMap[key] : null
    const rawValue =
      savedItem?.gia_tri !== undefined
        ? savedItem.gia_tri
        : item.gia_tri !== undefined
          ? item.gia_tri
          : defaultValueByLoai(loai)

    nextFields.push({
      key,
      ten_thong_tin: item.ten_thong_tin || key,
      loai_du_lieu: loai,
    })
    nextMeta[key] = {
      su_dung: true,
      ten_thong_tin: item.ten_thong_tin || key,
      loai_du_lieu: loai,
    }
    values[key] =
      loai === 'array' ? normalizeArrayValue(rawValue) : rawValue ?? null
  }

  fields.value = nextFields
  fieldMeta.value = nextMeta
}

async function loadData() {
  if (!props.hopDongId) return

  loading.value = true
  clearValues()
  fields.value = []
  fieldMeta.value = {}
  hopDong.value = null

  try {
    const [hopDongRes, usersRes] = await Promise.all([
      getHopDongSuDungDichVu(props.hopDongId),
      fetchUsers({ per_page: 100, status: 'active' }),
    ])

    hopDong.value = hopDongRes.data
    userOptions.value = usersRes.data.data || []

    const loaiId = hopDong.value?.loai_hop_dong_id
    if (!loaiId) {
      ElMessage.warning('Hợp đồng chưa chọn loại hợp đồng.')
      return
    }

    const { data: loaiHopDong } = await getLoaiHopDong(loaiId)
    buildFieldsFromSchema(
      loaiHopDong?.thong_tin_dieu_phoi,
      hopDong.value?.thong_tin_dieu_phoi,
    )
  } catch {
    fields.value = []
    userOptions.value = []
  } finally {
    loading.value = false
  }
}

function buildPayload() {
  const result = {}
  for (const field of fields.value) {
    const meta = fieldMeta.value[field.key] || {}
    const loai = field.loai_du_lieu || 'string'
    let giaTri = values[field.key]

    if (loai === 'array') {
      giaTri = normalizeArrayValue(giaTri)
    } else if (giaTri === '' || giaTri === undefined) {
      giaTri = null
    }

    result[field.key] = {
      su_dung: true,
      ten_thong_tin: meta.ten_thong_tin || field.ten_thong_tin,
      loai_du_lieu: loai,
      gia_tri: giaTri,
    }
  }
  return result
}

async function save() {
  if (!props.hopDongId || !fields.value.length) return

  saving.value = true
  try {
    const { data } = await updateHopDongSuDungDichVu(props.hopDongId, {
      thong_tin_dieu_phoi: buildPayload(),
    })
    ElMessage.success('Đã lưu thông tin điều phối.')
    emit('saved', data)
    visible.value = false
  } catch {
    // interceptor
  } finally {
    saving.value = false
  }
}

function onClosed() {
  clearValues()
  fields.value = []
  fieldMeta.value = {}
  hopDong.value = null
  userOptions.value = []
  saving.value = false
  emit('closed')
}

watch(
  () => props.modelValue,
  (isOpen) => {
    if (!isOpen) return
    loadData()
  },
)
</script>

<style scoped lang="scss">
.dieu-phoi-body {
  min-height: 180px;
}

.textarea-row {
  margin-top: 0;
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
