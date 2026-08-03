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
              allow-create
              default-first-option
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
import { ref } from 'vue'
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

defineProps({
  form: { type: Object, required: true },
  loaiHopDongOptions: { type: Array, default: () => [] },
  userOptions: { type: Array, default: () => [] },
  dynamicFields: { type: Array, default: () => [] },
  kenhTiepCanOptions: { type: Array, default: () => [] },
})

const emit = defineEmits(['loai-hop-dong-change'])

const formRef = ref(null)

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

async function validate() {
  return formRef.value?.validate().catch(() => false)
}

defineExpose({ validate })
</script>

<style scoped lang="scss">
.step-panel {
  min-height: 220px;
}
</style>
