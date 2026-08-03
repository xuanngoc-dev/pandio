<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1380"
    class="hop-dong-sddv-form-modal"
    @closed="onClosed"
  >
    <div class="steps-wrap">
      <el-steps
        class="sddv-steps"
        :active="activeStep"
        finish-status="success"
        process-status="process"
        align-center
      >
        <el-step
          v-for="(step, index) in steps"
          :key="step.key"
          :title="step.title"
          :class="{ 'is-clickable': index <= activeStep }"
          @click="goToStep(index)"
        />
      </el-steps>
    </div>

    <div v-show="activeStep === 0" class="step-panel">
      <CustomForm ref="step1FormRef" :model="form" :rules="step1Rules" label-position="top">
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
                @change="onLoaiHopDongChange"
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

    <div v-show="activeStep > 0" class="step-panel step-panel--placeholder">
      <p>Nội dung bước "{{ steps[activeStep]?.title }}" sẽ được bổ sung ở bước tiếp theo.</p>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Đóng</CustomButton>
        <div class="footer-actions__right">
          <CustomButton v-if="activeStep > 0" @click="activeStep -= 1">Quay lại</CustomButton>
          <CustomButton
            v-if="activeStep === 0"
            type="primary"
            plain
            :loading="saving"
            @click="saveStep1(false)"
          >
            Lưu
          </CustomButton>
          <CustomButton
            v-if="activeStep < steps.length - 1"
            type="primary"
            :loading="saving"
            @click="onNext"
          >
            Tiếp tục
          </CustomButton>
        </div>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { updateHopDongSuDungDichVu } from '@/api/hopDongSuDungDichVu'
import { fetchLoaiHopDong } from '@/api/loaiHopDong'
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
  MoneyInput,
} from '@/components/element'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  hopDong: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved', 'closed'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

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

const steps = [
  { key: 'thong-tin-chung', title: 'Thông tin chung', description: 'Mã HĐ & sale' },
  { key: 'dich-vu', title: 'Dịch vụ', description: 'Sắp có' },
  { key: 'thanh-toan', title: 'Thanh toán', description: 'Sắp có' },
]

const kenhTiepCanOptions = [
  'Facebook',
  'Zalo',
  'TikTok',
  'Instagram',
  'Hotline',
  'Website',
  'Giới thiệu',
  'Walk-in',
  'Khác',
]

const activeStep = ref(0)
const saving = ref(false)
const step1FormRef = ref(null)
const loaiHopDongOptions = ref([])
const userOptions = ref([])
const optionsLoaded = ref(false)

const form = reactive({
  id: null,
  ma_hop_dong: '',
  loai_hop_dong_id: null,
  ten_khach_hang: '',
  sdt_khach_hang: '',
  dia_chi: '',
  kenh_tiep_can: '',
  thong_tin_hop_dong: {},
  nguoi_tham_gia_ids: [],
  ghi_chu_sale: '',
})

const step1Rules = {
  loai_hop_dong_id: [{ required: true, message: 'Vui lòng chọn loại hợp đồng', trigger: 'change' }],
  ten_khach_hang: [{ required: true, message: 'Vui lòng nhập tên khách hàng', trigger: 'blur' }],
  sdt_khach_hang: [{ required: true, message: 'Vui lòng nhập SĐT khách hàng', trigger: 'blur' }],
}

const dialogTitle = computed(() => {
  const ma = form.ma_hop_dong || props.hopDong?.ma_hop_dong
  return ma ? `Hợp đồng ${ma}` : 'Thêm hợp đồng sử dụng dịch vụ'
})

const selectedLoaiHopDong = computed(() => {
  if (!form.loai_hop_dong_id) return null
  return loaiHopDongOptions.value.find((item) => item.id === form.loai_hop_dong_id) || null
})

const dynamicFields = computed(() => {
  const truong = selectedLoaiHopDong.value?.noi_dung?.truong
  if (!Array.isArray(truong)) return []
  return truong.filter((item) => item?.key && item?.ten_truong)
})

function defaultValueForKieu(kieu) {
  if (kieu === 'checkbox_group') return []
  if (kieu === 'checkbox' || kieu === 'switch') return false
  if (kieu === 'number' || kieu === 'money' || kieu === 'percent') return null
  return ''
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

function buildThongTinHopDong(fields, existing = {}) {
  const next = {}
  for (const field of fields) {
    if (Object.prototype.hasOwnProperty.call(existing, field.key)) {
      next[field.key] = existing[field.key]
    } else {
      next[field.key] = defaultValueForKieu(field.kieu)
    }
  }
  return next
}

function syncDynamicFields(preserveExisting = true) {
  const existing = preserveExisting ? { ...(form.thong_tin_hop_dong || {}) } : {}
  form.thong_tin_hop_dong = buildThongTinHopDong(dynamicFields.value, existing)
}

function onLoaiHopDongChange() {
  syncDynamicFields(false)
}

function syncFormFromHopDong(hopDong) {
  if (!hopDong) return
  form.id = hopDong.id ?? null
  form.ma_hop_dong = hopDong.ma_hop_dong || ''
  form.loai_hop_dong_id = hopDong.loai_hop_dong_id ?? null
  form.ten_khach_hang = hopDong.ten_khach_hang || ''
  form.sdt_khach_hang = hopDong.sdt_khach_hang || ''
  form.dia_chi = hopDong.dia_chi || ''
  form.kenh_tiep_can = hopDong.kenh_tiep_can || ''
  form.nguoi_tham_gia_ids = Array.isArray(hopDong.nguoi_tham_gia_ids)
    ? [...hopDong.nguoi_tham_gia_ids]
    : []
  form.ghi_chu_sale = hopDong.ghi_chu_sale || ''
  form.thong_tin_hop_dong =
    hopDong.thong_tin_hop_dong && typeof hopDong.thong_tin_hop_dong === 'object'
      ? { ...hopDong.thong_tin_hop_dong }
      : {}
  syncDynamicFields(true)
}

async function loadOptions() {
  if (optionsLoaded.value) return
  try {
    const [loaiRes, userRes] = await Promise.all([
      fetchLoaiHopDong({ per_page: 100, trang_thai: 'hoat_dong' }),
      fetchUsers({ per_page: 100, status: 'active' }),
    ])
    loaiHopDongOptions.value = loaiRes.data.data || []
    userOptions.value = userRes.data.data || []
    optionsLoaded.value = true
  } catch {
    loaiHopDongOptions.value = []
    userOptions.value = []
  }
}

function goToStep(index) {
  if (index <= activeStep.value) {
    activeStep.value = index
  }
}

function buildThongTinHopDongPayload() {
  const source = form.thong_tin_hop_dong || {}
  const payload = {}
  for (const field of dynamicFields.value) {
    const value = source[field.key]
    payload[field.key] = value === undefined ? defaultValueForKieu(field.kieu) : value
  }
  return payload
}

async function saveStep1(silent = false) {
  const valid = await step1FormRef.value?.validate().catch(() => false)
  if (!valid) return false
  if (!form.id) {
    ElMessage.error('Không tìm thấy hợp đồng để cập nhật.')
    return false
  }

  saving.value = true
  try {
    const thongTinHopDong = buildThongTinHopDongPayload()
    const { data } = await updateHopDongSuDungDichVu(form.id, {
      loai_hop_dong_id: form.loai_hop_dong_id,
      ten_khach_hang: form.ten_khach_hang?.trim() || null,
      sdt_khach_hang: form.sdt_khach_hang?.trim() || null,
      dia_chi: form.dia_chi?.trim() || null,
      kenh_tiep_can: form.kenh_tiep_can?.trim() || null,
      thong_tin_hop_dong: thongTinHopDong,
      nguoi_tham_gia_ids: form.nguoi_tham_gia_ids || [],
      ghi_chu_sale: form.ghi_chu_sale?.trim() || null,
      trang_thai: 'nhap',
    })
    syncFormFromHopDong(data)
    emit('saved', data)
    if (!silent) ElMessage.success('Đã lưu thông tin chung.')
    return true
  } catch {
    return false
  } finally {
    saving.value = false
  }
}

async function onNext() {
  // Bước 1: lưu dynamicFields vào thong_tin_hop_dong trước khi sang bước 2
  if (activeStep.value === 0) {
    const ok = await saveStep1(true)
    if (!ok) return
  }
  if (activeStep.value < steps.length - 1) {
    activeStep.value += 1
  }
}

function onClosed() {
  activeStep.value = 0
  emit('closed')
}

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return
    activeStep.value = 0
    syncFormFromHopDong(props.hopDong)
    await loadOptions()
    syncDynamicFields(true)
  },
)

watch(
  () => props.hopDong,
  (hopDong) => {
    if (props.modelValue) syncFormFromHopDong(hopDong)
  },
)

watch(dynamicFields, () => {
  if (props.modelValue) syncDynamicFields(true)
})
</script>

<style scoped lang="scss">
.steps-wrap {
  margin-bottom: 28px;
  padding: 8px 8px 20px;
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.sddv-steps {
  :deep(.el-step__title) {
    font-size: 14px;
    font-weight: 500;
    line-height: 1.35;
    transition: color 0.25s ease, transform 0.25s ease;
  }

  :deep(.el-step__head) {
    .el-step__icon {
      width: 36px;
      height: 36px;
      font-size: 15px;
      font-weight: 600;
      border-width: 2px;
      transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }
  }

  :deep(.el-step__line) {
    top: 18px;
    background-color: var(--el-border-color-lighter);
  }

  :deep(.el-step.is-success) {
    .el-step__head {
      color: var(--el-color-success);
      border-color: var(--el-color-success);
    }

    .el-step__icon {
      background: var(--el-color-success);
      border-color: var(--el-color-success);
      color: #fff;
      box-shadow: 0 4px 12px color-mix(in srgb, var(--el-color-success) 35%, transparent);
    }

    .el-step__line {
      background-color: var(--el-color-success-light-5);
    }

    .el-step__title {
      color: var(--el-color-success);
      font-weight: 600;
    }
  }

  :deep(.el-step.is-process) {
    .el-step__head {
      color: var(--el-color-primary);
      border-color: var(--el-color-primary);
    }

    .el-step__icon {
      position: relative;
      z-index: 1;
      background: var(--el-color-primary);
      border-color: var(--el-color-primary);
      color: #fff;
      transform: scale(1.08);
      box-shadow: 0 6px 16px color-mix(in srgb, var(--el-color-primary) 35%, transparent);

      &::before,
      &::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 2px solid var(--el-color-primary);
        opacity: 0.65;
        animation: sddv-step-wave 2s ease-out infinite;
        pointer-events: none;
      }

      &::after {
        animation-delay: 1s;
      }
    }

    .el-step__title {
      color: var(--el-color-primary);
      font-weight: 700;
      transform: translateY(1px);
    }
  }

  :deep(.el-step.is-wait) {
    .el-step__icon {
      background: var(--el-fill-color-blank);
      border-color: var(--el-border-color);
      color: var(--el-text-color-secondary);
    }

    .el-step__title {
      color: var(--el-text-color-secondary);
    }
  }

  :deep(.el-step.is-clickable) {
    cursor: pointer;

    &:hover .el-step__title {
      color: var(--el-color-primary);
    }
  }
}

@keyframes sddv-step-wave {
  0% {
    transform: scale(1);
    opacity: 0.55;
  }
  70% {
    opacity: 0.15;
  }
  100% {
    transform: scale(2.15);
    opacity: 0;
  }
}

.step-panel {
  min-height: 220px;
}

.step-panel--placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--el-text-color-secondary);
  font-size: 14px;
  text-align: center;
  padding: 48px 16px;
}

.footer-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.footer-actions__right {
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>
