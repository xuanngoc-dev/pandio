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
              v-model="values[field.key]"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              :placeholder="`Chọn ${field.ten_thong_tin.toLowerCase()}`"
              :disabled-date="disabledPastDate"
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

          <template v-else-if="field.loai_du_lieu === 'number'">
            <CustomInput
              v-model.number="values[field.key]"
              type="number"
              :min="field.key === 'so_diem_chup' ? 1 : 0"
              :placeholder="`Nhập ${field.ten_thong_tin.toLowerCase()}`"
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
  LOAI_QUAY_CHUP_KEY,
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

const REQUIRED_DATE_KEYS = new Set(['ngay_chup', 'ngay_tra_demo', 'ngay_tra_chinh_thuc'])

const props = defineProps({
  fields: { type: Array, default: () => [] },
  userOptions: { type: Array, default: () => [] },
  loaiQuayChupOptions: { type: Array, default: () => [] },
  propPrefix: { type: String, default: '' },
  requireDates: { type: Boolean, default: false },
  requireNgayChup: { type: Boolean, default: false },
})

const buoiChupOptions = BUOI_CHUP_OPTIONS
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

function isRequired(field) {
  if (!field) return false
  if (props.requireNgayChup && field.key === 'ngay_chup') return true
  if (props.requireDates && (field.loai_du_lieu === 'date' || REQUIRED_DATE_KEYS.has(field.key))) {
    return true
  }
  return false
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
