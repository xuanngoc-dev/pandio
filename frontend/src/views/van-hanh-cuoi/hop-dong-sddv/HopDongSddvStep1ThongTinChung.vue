<template>
  <div class="step-panel">
    <CustomForm ref="formRef" :model="form" :rules="step1Rules" label-position="top">
      <CustomRow :gutter="16">
        <CustomCol v-bind="fieldColProps">
          <CustomFormItem label="Mã hợp đồng" prop="ma_hop_dong">
            <CustomInput v-model="form.ma_hop_dong" readonly />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="fieldColProps">
          <CustomFormItem label="Loại hợp đồng" prop="loai_hop_dong_id">
            <CustomSelect
              v-model="form.loai_hop_dong_id"
              placeholder="Chọn loại hợp đồng"
              filterable
              style="width: 100%"
              @change="emit('loai-hop-dong-change')"
            >
              <CustomOption
                v-for="item in loaiHopDongOptions"
                :key="item.id"
                :label="item.ten_hop_dong"
                :value="item.id"
              />
            </CustomSelect>
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="fieldColProps">
          <CustomFormItem label="Tên khách hàng" prop="ten_khach_hang">
            <CustomInput v-model="form.ten_khach_hang" placeholder="Nhập tên khách hàng" clearable />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="fieldColProps">
          <CustomFormItem label="SĐT khách hàng" prop="sdt_khach_hang">
            <CustomInput v-model="form.sdt_khach_hang" placeholder="Nhập số điện thoại" clearable />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="fieldColProps">
          <CustomFormItem label="Địa chỉ" prop="dia_chi">
            <CustomInput v-model="form.dia_chi" placeholder="Nhập địa chỉ" clearable />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="fieldColProps">
          <CustomFormItem label="Kênh tiếp cận" prop="kenh_tiep_can">
            <CustomSelect
              v-model="form.kenh_tiep_can"
              placeholder="Chọn kênh tiếp cận"
              clearable
              filterable
              style="width: 100%"
            >
              <CustomOption
                v-for="opt in kenhTiepCanOptions"
                :key="opt"
                :label="opt"
                :value="opt"
              />
            </CustomSelect>
          </CustomFormItem>
        </CustomCol>

        <CustomCol
          v-for="field in dynamicFields"
          :key="field.key"
          v-bind="getFieldColProps(field)"
        >
          <CustomFormItem
            :label="field.ten_truong"
            :prop="`thong_tin_hop_dong.${field.key}`"
            :rules="getDynamicFieldRules(field)"
          >
            <template v-if="isTextarea(field.kieu)">
              <CustomInput
                v-model="form.thong_tin_hop_dong[field.key]"
                type="textarea"
                :rows="3"
                :placeholder="`Nhập ${field.ten_truong.toLowerCase()}`"
              />
            </template>
            <template v-else-if="isMoney(field.kieu)">
              <MoneyInput
                v-model="form.thong_tin_hop_dong[field.key]"
                :placeholder="`Nhập ${field.ten_truong.toLowerCase()}`"
                style="width: 100%"
              />
            </template>
            <template v-else-if="isNumberLike(field.kieu)">
              <CustomInput
                v-model="form.thong_tin_hop_dong[field.key]"
                type="number"
                :placeholder="`Nhập ${field.ten_truong.toLowerCase()}`"
              />
            </template>
            <template v-else-if="field.kieu === 'select'">
              <CustomSelect
                v-model="form.thong_tin_hop_dong[field.key]"
                :placeholder="`Chọn ${field.ten_truong.toLowerCase()}`"
                clearable
                filterable
                style="width: 100%"
              >
                <CustomOption
                  v-for="opt in field.options || []"
                  :key="opt.value"
                  :label="opt.label"
                  :value="opt.value"
                />
              </CustomSelect>
            </template>
            <template v-else-if="field.kieu === 'radio'">
              <el-radio-group v-model="form.thong_tin_hop_dong[field.key]">
                <el-radio
                  v-for="opt in field.options || []"
                  :key="opt.value"
                  :value="opt.value"
                >
                  {{ opt.label }}
                </el-radio>
              </el-radio-group>
            </template>
            <template v-else-if="field.kieu === 'checkbox'">
              <el-checkbox v-model="form.thong_tin_hop_dong[field.key]">
                {{ field.ten_truong }}
              </el-checkbox>
            </template>
            <template v-else-if="field.kieu === 'checkbox_group'">
              <el-checkbox-group v-model="form.thong_tin_hop_dong[field.key]">
                <el-checkbox
                  v-for="opt in field.options || []"
                  :key="opt.value"
                  :value="opt.value"
                >
                  {{ opt.label }}
                </el-checkbox>
              </el-checkbox-group>
            </template>
            <template v-else-if="field.kieu === 'switch'">
              <el-switch v-model="form.thong_tin_hop_dong[field.key]" />
            </template>
            <template v-else-if="isDateLike(field.kieu)">
              <el-date-picker
                v-model="form.thong_tin_hop_dong[field.key]"
                :type="datePickerType(field.kieu)"
                :format="datePickerFormat(field.kieu)"
                :value-format="datePickerValueFormat(field.kieu)"
                :placeholder="`Chọn ${field.ten_truong.toLowerCase()}`"
                style="width: 100%"
              />
            </template>
            <template v-else-if="field.kieu === 'time'">
              <el-time-picker
                v-model="form.thong_tin_hop_dong[field.key]"
                format="HH:mm"
                value-format="HH:mm"
                :placeholder="`Chọn ${field.ten_truong.toLowerCase()}`"
                style="width: 100%"
              />
            </template>
            <template v-else>
              <CustomInput
                v-model="form.thong_tin_hop_dong[field.key]"
                :type="textInputType(field.kieu)"
                :placeholder="`Nhập ${field.ten_truong.toLowerCase()}`"
                clearable
              />
            </template>
          </CustomFormItem>
        </CustomCol>

        <CustomCol v-bind="fieldColProps">
          <CustomFormItem label="Người tham gia" prop="nguoi_tham_gia_ids">
            <CustomSelect
              v-model="form.nguoi_tham_gia_ids"
              placeholder="Chọn người tham gia"
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

        <CustomCol
          v-for="field in dieuPhoiNormalFields"
          :key="`dieu-phoi-${field.key}`"
          v-bind="getDieuPhoiFieldColProps(field)"
        >
          <CustomFormItem :label="field.ten_thong_tin">
            <template v-if="field.key === 'buoi_chup'">
              <CustomSelect
                v-model="dieuPhoiValues[field.key]"
                :placeholder="`Chọn ${field.ten_thong_tin.toLowerCase()}`"
                clearable
                style="width: 100%"
              >
                <CustomOption
                  v-for="opt in buoiChupOptions"
                  :key="opt.value"
                  :label="opt.label"
                  :value="opt.value"
                />
              </CustomSelect>
            </template>

            <template v-else-if="field.loai_du_lieu === 'date'">
              <el-date-picker
                v-model="dieuPhoiValues[field.key]"
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
                v-model="dieuPhoiValues[field.key]"
                format="HH:mm"
                value-format="HH:mm"
                :placeholder="`Chọn ${field.ten_thong_tin.toLowerCase()}`"
                style="width: 100%"
                clearable
              />
            </template>

            <template v-else-if="field.loai_du_lieu === 'array'">
              <CustomSelect
                v-model="dieuPhoiValues[field.key]"
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
                  v-for="opt in getDieuPhoiArrayOptions(field.key)"
                  :key="opt"
                  :label="opt"
                  :value="opt"
                />
              </CustomSelect>
            </template>

            <template v-else>
              <CustomInput
                v-model="dieuPhoiValues[field.key]"
                :placeholder="`Nhập ${field.ten_thong_tin.toLowerCase()}`"
                clearable
              />
            </template>
          </CustomFormItem>
        </CustomCol>
      </CustomRow>

      <CustomRow v-if="dieuPhoiTextareaFields.length" :gutter="16">
        <CustomCol
          v-for="field in dieuPhoiTextareaFields"
          :key="`dieu-phoi-${field.key}`"
          v-bind="wideFieldColProps"
        >
          <CustomFormItem :label="field.ten_thong_tin">
            <CustomInput
              v-model="dieuPhoiValues[field.key]"
              type="textarea"
              :rows="3"
              :placeholder="`Nhập ${field.ten_thong_tin.toLowerCase()}`"
            />
          </CustomFormItem>
        </CustomCol>
      </CustomRow>

      <CustomRow :gutter="16">
        <CustomCol :span="24">
          <CustomFormItem label="Ghi chú sale" prop="ghi_chu_sale">
            <CustomInput
              v-model="form.ghi_chu_sale"
              type="textarea"
              :rows="3"
              placeholder="Ghi chú nội bộ của sale"
            />
          </CustomFormItem>
        </CustomCol>
      </CustomRow>
    </CustomForm>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { getLoaiHopDong } from '@/api/loaiHopDong'
import {
  CustomCol,
  CustomForm,
  CustomFormItem,
  CustomInput,
  CustomOption,
  CustomRow,
  CustomSelect,
  MoneyInput,
} from '@/components/element'

/** Các trường điều phối cho phép cập nhật ngay tại bước thông tin chung */
const STEP1_DIEU_PHOI_KEYS = [
  'buoi_chup',
  'gio_chup',
  'ngay_chup',
  'ngay_tra_demo',
  'ngay_tra_chinh_thuc',
  'dia_diem_chup',
  'ghi_chu_trang_phuc_phu_kien',
  'ghi_chu_dieu_phoi',
]

const buoiChupOptions = [
  { value: 'sang', label: 'Sáng' },
  { value: 'chieu', label: 'Chiều' },
  { value: 'toi', label: 'Tối' },
]

const props = defineProps({
  form: { type: Object, required: true },
  loaiHopDongOptions: { type: Array, default: () => [] },
  userOptions: { type: Array, default: () => [] },
  dynamicFields: { type: Array, default: () => [] },
  kenhTiepCanOptions: { type: Array, default: () => [] },
})

const emit = defineEmits(['loai-hop-dong-change'])

const formRef = ref(null)
const dieuPhoiFields = ref([])
const dieuPhoiMeta = ref({})
const dieuPhoiValues = reactive({})
let dieuPhoiLoadToken = 0

/** xl/lg: 6/hàng · md: 3/hàng · sm/xs (mobile): 2/hàng */
const fieldColProps = {
  xs: 12,
  sm: 12,
  md: 8,
  lg: 4,
  xl: 4,
}

const wideFieldColProps = {
  xs: 24,
  sm: 24,
  md: 16,
  lg: 12,
  xl: 12,
}

const dieuPhoiNormalFields = computed(() =>
  dieuPhoiFields.value.filter((field) => field.loai_du_lieu !== 'textarea'),
)

const dieuPhoiTextareaFields = computed(() =>
  dieuPhoiFields.value.filter((field) => field.loai_du_lieu === 'textarea'),
)

const step1Rules = {
  loai_hop_dong_id: [{ required: true, message: 'Vui lòng chọn loại hợp đồng', trigger: 'change' }],
  ten_khach_hang: [{ required: true, message: 'Vui lòng nhập tên khách hàng', trigger: 'blur' }],
  sdt_khach_hang: [{ required: true, message: 'Vui lòng nhập SĐT khách hàng', trigger: 'blur' }],
}

function isTextarea(kieu) {
  return kieu === 'textarea'
}

function isMoney(kieu) {
  return kieu === 'money'
}

function isNumberLike(kieu) {
  return kieu === 'number' || kieu === 'percent'
}

function isDateLike(kieu) {
  return ['date', 'datetime', 'month', 'year'].includes(kieu)
}

function getFieldColProps(field) {
  if (isTextarea(field.kieu) || field.kieu === 'checkbox_group' || field.kieu === 'radio') {
    return wideFieldColProps
  }
  return fieldColProps
}

function getDieuPhoiFieldColProps(field) {
  if (field.loai_du_lieu === 'array') return wideFieldColProps
  return fieldColProps
}

function textInputType(kieu) {
  if (kieu === 'email') return 'email'
  if (kieu === 'phone') return 'tel'
  if (kieu === 'url') return 'url'
  return 'text'
}

function datePickerType(kieu) {
  if (kieu === 'datetime') return 'datetime'
  if (kieu === 'month') return 'month'
  if (kieu === 'year') return 'year'
  return 'date'
}

function datePickerFormat(kieu) {
  if (kieu === 'datetime') return 'DD/MM/YYYY HH:mm'
  if (kieu === 'month') return 'MM/YYYY'
  if (kieu === 'year') return 'YYYY'
  return 'DD/MM/YYYY'
}

function datePickerValueFormat(kieu) {
  if (kieu === 'datetime') return 'YYYY-MM-DD HH:mm:ss'
  if (kieu === 'month') return 'YYYY-MM'
  if (kieu === 'year') return 'YYYY'
  return 'YYYY-MM-DD'
}

function getDynamicFieldRules(field) {
  if (!field.bat_buoc) return []
  return [
    {
      required: true,
      message: `Vui lòng nhập ${field.ten_truong.toLowerCase()}`,
      trigger: ['blur', 'change'],
      validator: (_rule, value, callback) => {
        if (field.kieu === 'checkbox_group') {
          if (!Array.isArray(value) || value.length === 0) {
            callback(new Error(`Vui lòng chọn ${field.ten_truong.toLowerCase()}`))
            return
          }
        } else if (field.kieu === 'checkbox' || field.kieu === 'switch') {
          // optional boolean — required only means must be present
        } else if (value === null || value === undefined || value === '') {
          callback(new Error(`Vui lòng nhập ${field.ten_truong.toLowerCase()}`))
          return
        }
        callback()
      },
    },
  ]
}

function defaultDieuPhoiValue(loai) {
  return loai === 'array' ? [] : null
}

function normalizeDieuPhoiArray(value) {
  if (Array.isArray(value)) return [...value]
  if (value == null || value === '') return []
  return [value]
}

function normalizeDieuPhoiScalar(value) {
  if (Array.isArray(value)) {
    const text = value
      .map((item) => String(item).trim())
      .filter(Boolean)
      .join(', ')
    return text || null
  }
  if (value == null || value === '') return null
  return value
}

function clearDieuPhoiValues() {
  Object.keys(dieuPhoiValues).forEach((key) => {
    delete dieuPhoiValues[key]
  })
}

function getDieuPhoiArrayOptions(key) {
  const current = normalizeDieuPhoiArray(dieuPhoiValues[key])
  return [...new Set(current.map((item) => String(item)).filter(Boolean))]
}

function cloneDieuPhoiMap(source) {
  if (!source || typeof source !== 'object' || Array.isArray(source)) return {}
  return JSON.parse(JSON.stringify(source))
}

function resolveDieuPhoiLoai(key, item) {
  if (key === 'dia_diem_chup') return 'string'
  return item?.loai_du_lieu || 'string'
}

function buildDieuPhoiFieldsFromSchema(schema, saved) {
  const source =
    schema && typeof schema === 'object' && !Array.isArray(schema) ? schema : {}
  const savedMap =
    saved && typeof saved === 'object' && !Array.isArray(saved) ? saved : {}

  const nextFields = []
  const nextMeta = {}

  clearDieuPhoiValues()

  for (const key of STEP1_DIEU_PHOI_KEYS) {
    const item = source[key]
    if (!item || typeof item !== 'object') continue
    if (item.su_dung !== true) continue

    const loai = resolveDieuPhoiLoai(key, item)
    const savedItem =
      savedMap[key] && typeof savedMap[key] === 'object' ? savedMap[key] : null
    const rawValue =
      savedItem?.gia_tri !== undefined
        ? savedItem.gia_tri
        : item.gia_tri !== undefined
          ? item.gia_tri
          : defaultDieuPhoiValue(loai)

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
    dieuPhoiValues[key] =
      loai === 'array'
        ? normalizeDieuPhoiArray(rawValue)
        : normalizeDieuPhoiScalar(rawValue)
  }

  dieuPhoiFields.value = nextFields
  dieuPhoiMeta.value = nextMeta
}

async function loadDieuPhoiSchema() {
  const loaiId = props.form?.loai_hop_dong_id
  dieuPhoiLoadToken += 1
  const token = dieuPhoiLoadToken

  if (!loaiId) {
    clearDieuPhoiValues()
    dieuPhoiFields.value = []
    dieuPhoiMeta.value = {}
    return
  }

  try {
    const { data: loaiHopDong } = await getLoaiHopDong(loaiId)
    if (token !== dieuPhoiLoadToken) return
    buildDieuPhoiFieldsFromSchema(
      loaiHopDong?.thong_tin_dieu_phoi,
      props.form?.thong_tin_dieu_phoi,
    )
  } catch {
    if (token !== dieuPhoiLoadToken) return
    clearDieuPhoiValues()
    dieuPhoiFields.value = []
    dieuPhoiMeta.value = {}
  }
}

function getDieuPhoiPayload(existing = null) {
  const result = cloneDieuPhoiMap(
    existing !== null && existing !== undefined
      ? existing
      : props.form?.thong_tin_dieu_phoi,
  )

  for (const field of dieuPhoiFields.value) {
    const meta = dieuPhoiMeta.value[field.key] || {}
    const loai = field.loai_du_lieu || 'string'
    let giaTri = dieuPhoiValues[field.key]

    if (loai === 'array') {
      giaTri = normalizeDieuPhoiArray(giaTri)
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

async function validate() {
  return formRef.value?.validate().catch(() => false)
}

defineExpose({ validate, getDieuPhoiPayload, loadDieuPhoiSchema })
</script>

<style scoped lang="scss">
.step-panel {
  min-height: 220px;
}
</style>
