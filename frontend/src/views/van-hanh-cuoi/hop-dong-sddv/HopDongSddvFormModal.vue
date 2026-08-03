<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1280"
    class="hop-dong-sddv-form-modal"
    @closed="onClosed"
  >
    <div class="steps-wrap">
      <el-steps :active="activeStep" finish-status="success" align-center>
        <el-step
          v-for="(step, index) in steps"
          :key="step.key"
          :title="step.title"
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

const steps = [
  { key: 'thong-tin-chung', title: 'Thông tin chung', description: 'Mã HĐ & sale' },
  { key: 'thong-tin-hop-dong', title: 'Thông tin HĐ', description: 'Sắp có' },
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

async function saveStep1(silent = false) {
  const valid = await step1FormRef.value?.validate().catch(() => false)
  if (!valid) return false
  if (!form.id) {
    ElMessage.error('Không tìm thấy hợp đồng để cập nhật.')
    return false
  }

  saving.value = true
  try {
    const { data } = await updateHopDongSuDungDichVu(form.id, {
      loai_hop_dong_id: form.loai_hop_dong_id,
      ten_khach_hang: form.ten_khach_hang?.trim() || null,
      sdt_khach_hang: form.sdt_khach_hang?.trim() || null,
      dia_chi: form.dia_chi?.trim() || null,
      kenh_tiep_can: form.kenh_tiep_can?.trim() || null,
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
  },
)

watch(
  () => props.hopDong,
  (hopDong) => {
    if (props.modelValue) syncFormFromHopDong(hopDong)
  },
)
</script>

<style scoped lang="scss">
.steps-wrap {
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--el-border-color-lighter);
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
