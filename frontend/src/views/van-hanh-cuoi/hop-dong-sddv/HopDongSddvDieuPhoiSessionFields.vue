<template>
  <div class="dieu-phoi-session-fields">
    <CustomRow :gutter="16">
      <CustomCol v-bind="fieldColProps">
        <CustomFormItem
          label="Loại quay chụp"
          :prop="propFor(LOAI_QUAY_CHUP_KEY)"
          required
        >
          <CustomSelect
            v-model="selectedLoaiQuayChupId"
            placeholder="Chọn loại quay chụp"
            filterable
            clearable
            :disabled="disabled"
            style="width: 100%"
          >
            <CustomOption
              v-for="opt in loaiQuayChupSelectOptions"
              :key="opt.id"
              :label="opt.ten_dich_vu || `Loại #${opt.id}`"
              :value="opt.id"
            />
          </CustomSelect>
        </CustomFormItem>
      </CustomCol>
      <CustomCol
        v-for="field in normalFields"
        :key="field.key"
        v-bind="getFieldColProps(field)"
      >
        <CustomFormItem
          :label="field.ten_thong_tin"
          :prop="propFor(field.key)"
          :required="isRequired(field)"
        >
          <template v-if="field.key === 'buoi_chup'">
            <CustomSelect
              v-model="values[field.key]"
              :placeholder="`Chọn ${field.ten_thong_tin.toLowerCase()}`"
              clearable
              :disabled="disabled"
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

          <template v-else-if="field.key === SAP_XEP_TRANG_PHUC_KEY">
            <CustomSelect
              v-model="values[field.key]"
              :placeholder="`Chọn ${field.ten_thong_tin.toLowerCase()}`"
              clearable
              :disabled="disabled"
              style="width: 100%"
            >
              <CustomOption
                v-for="opt in sapXepTrangPhucOptions"
                :key="opt.value"
                :label="opt.label"
                :value="opt.value"
              />
            </CustomSelect>
          </template>

          <template v-else-if="field.loai_du_lieu === 'date'">
            <el-date-picker
              v-model="values[field.key]"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              :placeholder="`Chọn ${field.ten_thong_tin.toLowerCase()}`"
              :disabled="disabled || isDateLocked(field)"
              :disabled-date="disabledPastDate"
              style="width: 100%"
              :clearable="!isDateLocked(field)"
            />
          </template>

          <template v-else-if="field.loai_du_lieu === 'time'">
            <el-time-picker
              v-model="values[field.key]"
              format="HH:mm"
              value-format="HH:mm"
              :placeholder="`Chọn ${field.ten_thong_tin.toLowerCase()}`"
              :disabled="disabled"
              style="width: 100%"
              clearable
            />
          </template>

          <template v-else-if="field.loai_du_lieu === 'number'">
            <CustomInput
              :model-value="values[field.key]"
              type="number"
              :min="numberFieldMin(field)"
              :max="numberFieldMax(field)"
              :placeholder="`Nhập ${field.ten_thong_tin.toLowerCase()}`"
              :disabled="disabled"
              @update:model-value="onNumberFieldInput(field, $event)"
              @blur="onNumberFieldBlur(field)"
            />
          </template>

          <template v-else-if="field.loai_du_lieu === 'array' && isStaffField(field.key)">
            <CustomSelect
              :model-value="staffSelectValue(field)"
              :placeholder="`Chọn ${field.ten_thong_tin.toLowerCase()}`"
              :multiple="!isSingleStaffSelect(field)"
              :show-select-all="!isSingleStaffSelect(field)"
              filterable
              :collapse-tags="!isSingleStaffSelect(field)"
              :collapse-tags-tooltip="!isSingleStaffSelect(field)"
              clearable
              :disabled="disabled"
              style="width: 100%"
              @update:model-value="onStaffSelect(field, $event)"
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
              :disabled="disabled"
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
              :disabled="disabled"
            />
          </template>
        </CustomFormItem>
      </CustomCol>
    </CustomRow>

    <slot />

    <CustomRow v-if="textareaFields.length" :gutter="16">
      <CustomCol
        v-for="field in textareaFields"
        :key="field.key"
        v-bind="wideFieldColProps"
      >
        <CustomFormItem :label="field.ten_thong_tin" :prop="propFor(field.key)">
          <CustomInput
            v-model="values[field.key]"
            type="textarea"
            :rows="3"
            :placeholder="`Nhập ${field.ten_thong_tin.toLowerCase()}`"
            :disabled="disabled"
          />
        </CustomFormItem>
      </CustomCol>
    </CustomRow>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { fetchDanhMucLoaiQuayChup } from '@/api/danhMucLoaiQuayChup'
import {
  BUOI_CHUP_OPTIONS,
  DIEU_PHOI_STAFF_KEYS,
  SAP_XEP_TRANG_PHUC_KEY,
  SAP_XEP_TRANG_PHUC_OPTIONS,
  clampStaffArrayValue,
  LOAI_QUAY_CHUP_KEY,
  staffSelectMax,
  SO_DIEM_CHUP_DEFAULT,
  SO_DIEM_CHUP_KEY,
  SO_DIEM_CHUP_MAX,
  SO_DIEM_CHUP_MIN,
  clampSoDiemChup,
  getLoaiQuayChupId,
  normalizeLoaiQuayChupGiaTri,
} from '@/utils/thongTinDieuPhoi'
import {
  CustomCol,
  CustomFormItem,
  CustomInput,
  CustomOption,
  CustomRow,
  CustomSelect,
} from '@/components/element'

const values = defineModel({ type: Object, required: true })

const REQUIRED_DATE_KEYS = new Set(['ngay_chup'])

const props = defineProps({
  fields: { type: Array, default: () => [] },
  userOptions: { type: Array, default: () => [] },
  loaiQuayChupOptions: { type: Array, default: () => [] },
  propPrefix: { type: String, default: '' },
  requireDates: { type: Boolean, default: false },
  requireNgayChup: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  /** Khóa field ngày chụp (đã chọn từ lịch) */
  lockNgayChup: { type: Boolean, default: false },
})

const buoiChupOptions = BUOI_CHUP_OPTIONS
const sapXepTrangPhucOptions = SAP_XEP_TRANG_PHUC_OPTIONS
const loadedLoaiQuayChupOptions = ref([])

const resolvedLoaiQuayChupOptions = computed(() =>
  props.loaiQuayChupOptions.length ? props.loaiQuayChupOptions : loadedLoaiQuayChupOptions.value,
)

const selectedLoaiQuayChupId = computed({
  get() {
    return getLoaiQuayChupId(values.value?.[LOAI_QUAY_CHUP_KEY])
  },
  set(id) {
    const nextId = Number(id)
    if (!Number.isFinite(nextId) || nextId <= 0) {
      values.value[LOAI_QUAY_CHUP_KEY] = null
      return
    }
    const opt = loaiQuayChupSelectOptions.value.find((item) => Number(item.id) === nextId)
    values.value[LOAI_QUAY_CHUP_KEY] = normalizeLoaiQuayChupGiaTri(
      opt || { id: nextId, ten_dich_vu: '' },
    )
  },
})

const loaiQuayChupSelectOptions = computed(() => {
  const list = (resolvedLoaiQuayChupOptions.value || [])
    .map((item) => normalizeLoaiQuayChupGiaTri(item))
    .filter(Boolean)
  const current = normalizeLoaiQuayChupGiaTri(values.value?.[LOAI_QUAY_CHUP_KEY])
  if (current && !list.some((item) => item.id === current.id)) {
    list.unshift(current)
  }
  return list
})

const fieldColProps = {
  xs: 12,
  sm: 12,
  md: 8,
  lg: 6,
  xl: 6,
}

const wideFieldColProps = {
  xs: 24,
  sm: 24,
  md: 16,
  lg: 12,
  xl: 12,
}

const normalFields = computed(() =>
  props.fields.filter((field) => field.loai_du_lieu !== 'textarea'),
)

const textareaFields = computed(() =>
  props.fields.filter((field) => field.loai_du_lieu === 'textarea'),
)

function isStaffField(key) {
  return DIEU_PHOI_STAFF_KEYS.has(key)
}

function isSingleStaffSelect(field) {
  return staffSelectMax(field) === 1
}

function staffSelectValue(field) {
  const raw = values.value?.[field.key]
  if (isSingleStaffSelect(field)) {
    if (Array.isArray(raw)) return raw[0] ?? null
    return raw ?? null
  }
  return Array.isArray(raw) ? raw : []
}

function onStaffSelect(field, next) {
  values.value[field.key] = clampStaffArrayValue(field, next)
}

function isRequired(field) {
  if (!field) return false
  if (props.requireNgayChup && field.key === 'ngay_chup') return true
  if (props.requireDates && (field.loai_du_lieu === 'date' || REQUIRED_DATE_KEYS.has(field.key))) {
    return true
  }
  return false
}

function isDateLocked(field) {
  return Boolean(props.lockNgayChup && field?.key === 'ngay_chup')
}

function propFor(key) {
  return props.propPrefix ? `${props.propPrefix}.${key}` : key
}

function getFieldColProps(field) {
  if (field.loai_du_lieu === 'array' && !isStaffField(field.key)) return wideFieldColProps
  return fieldColProps
}

function startOfToday() {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return today
}

function disabledPastDate(date) {
  return date.getTime() < startOfToday().getTime()
}

function getArrayOptions(key) {
  const current = Array.isArray(values.value?.[key]) ? values.value[key] : []
  return [...new Set(current.map((item) => String(item)).filter(Boolean))]
}

function numberFieldMin(field) {
  if (field.key === SO_DIEM_CHUP_KEY) {
    const min = Number(field.gia_tri_toi_thieu ?? SO_DIEM_CHUP_MIN)
    return Number.isFinite(min) ? min : SO_DIEM_CHUP_MIN
  }
  return 0
}

function numberFieldMax(field) {
  if (field.key === SO_DIEM_CHUP_KEY) {
    const max = Number(field.gia_tri_toi_da ?? SO_DIEM_CHUP_MAX)
    return Number.isFinite(max) ? max : SO_DIEM_CHUP_MAX
  }
  return undefined
}

function clampNumberField(field, value) {
  if (field.key !== SO_DIEM_CHUP_KEY) return value
  if (value == null || value === '') return SO_DIEM_CHUP_DEFAULT
  return clampSoDiemChup(value)
}

function onNumberFieldInput(field, value) {
  if (value == null || value === '') {
    values.value[field.key] = value
    return
  }
  values.value[field.key] = clampNumberField(field, value)
}

function onNumberFieldBlur(field) {
  values.value[field.key] = clampNumberField(field, values.value[field.key])
}

async function loadLoaiQuayChupOptions() {
  if (props.loaiQuayChupOptions.length) return
  try {
    const { data } = await fetchDanhMucLoaiQuayChup({ per_page: 100, trang_thai: 'active' })
    loadedLoaiQuayChupOptions.value = (data.data || []).slice().sort((a, b) =>
      String(a.ten_dich_vu || '').localeCompare(String(b.ten_dich_vu || ''), 'vi'),
    )
  } catch {
    loadedLoaiQuayChupOptions.value = []
  }
}

onMounted(() => {
  loadLoaiQuayChupOptions()
})
</script>
