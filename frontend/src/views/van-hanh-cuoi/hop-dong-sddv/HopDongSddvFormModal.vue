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

    <HopDongSddvStep1ThongTinChung
      v-show="activeStep === 0"
      ref="step1Ref"
      :form="form"
      :loai-hop-dong-options="loaiHopDongOptions"
      :user-options="userOptions"
      :dynamic-fields="dynamicFields"
      :kenh-tiep-can-options="kenhTiepCanOptions"
      @loai-hop-dong-change="onLoaiHopDongChange"
    />

    <HopDongSddvStep2DichVu
      v-show="activeStep === 1"
      ref="step2Ref"
      :loai-hop-dong-id="form.loai_hop_dong_id"
      @tong-tien-change="onStep2TongTienChange"
    />

    <HopDongSddvStep3ThanhToan
      v-show="activeStep === 2"
      ref="step3Ref"
      :form="form"
      :tong-tien-dich-vu="step2TongTienDisplay"
    />

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Đóng</CustomButton>
        <CustomButton v-if="activeStep > 0" @click="activeStep -= 1">Quay lại</CustomButton>
        <CustomButton
          v-if="activeStep === 0 || activeStep === 1 || activeStep === 2"
          type="primary"
          plain
          :loading="saving"
          @click="onSaveCurrentStep"
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
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { fetchDanhMucNguonKhach } from '@/api/danhMucNguonKhach'
import { updateHopDongSuDungDichVu } from '@/api/hopDongSuDungDichVu'
import { fetchLoaiHopDong } from '@/api/loaiHopDong'
import { fetchUsers } from '@/api/users'
import { CustomButton, CustomDialog } from '@/components/element'
import HopDongSddvStep1ThongTinChung from './HopDongSddvStep1ThongTinChung.vue'
import HopDongSddvStep2DichVu from './HopDongSddvStep2DichVu.vue'
import HopDongSddvStep3ThanhToan from './HopDongSddvStep3ThanhToan.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  hopDong: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved', 'closed'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const steps = [
  { key: 'thong-tin-chung', title: 'Thông tin chung', description: 'Mã HĐ & sale' },
  { key: 'dich-vu', title: 'Dịch vụ', description: 'Combo & dịch vụ lẻ' },
  { key: 'thanh-toan', title: 'Thanh toán', description: 'Concept & trang phục' },
]

const kenhTiepCanOptions = ref([])

const activeStep = ref(0)
const saving = ref(false)
const step1Ref = ref(null)
const step2Ref = ref(null)
const step3Ref = ref(null)
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
  tong_tien: 0,
  phat_sinh: 0,
  chiet_khau: 0,
  ma_giam_gia: '',
  khuyen_mai_theo_ma_giam_gia: 0,
  so_tien_thanh_toan_lan_1: 0,
  so_tien_thanh_toan_lan_2: 0,
  so_tien_thanh_toan_lan_3: 0,
  thoi_gian_thanh_toan_lan_1: null,
  thoi_gian_thanh_toan_lan_2: null,
  thoi_gian_thanh_toan_lan_3: null,
  qua_tang_kem: '',
  yeu_cau_dac_biet: '',
})

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

const step2TongTien = ref(0)
const step2HasSelection = ref(false)

const step2TongTienDisplay = computed(() => {
  if (step2HasSelection.value) return step2TongTien.value
  return Number(form.tong_tien) || 0
})

function onStep2TongTienChange(payload) {
  if (payload && typeof payload === 'object') {
    step2TongTien.value = Number(payload.total) || 0
    step2HasSelection.value = Boolean(payload.hasSelection)
    return
  }
  step2TongTien.value = Number(payload) || 0
}

function defaultValueForKieu(kieu) {
  if (kieu === 'checkbox_group') return []
  if (kieu === 'checkbox' || kieu === 'switch') return false
  if (kieu === 'number' || kieu === 'money' || kieu === 'percent') return null
  return ''
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
  // Chưa có schema dynamic (options loại HĐ chưa load) → không ghi đè dữ liệu đã lưu
  if (!dynamicFields.value.length) return
  const existing = preserveExisting ? { ...(form.thong_tin_hop_dong || {}) } : {}
  form.thong_tin_hop_dong = buildThongTinHopDong(dynamicFields.value, existing)
}

function onLoaiHopDongChange() {
  form.thong_tin_hop_dong = {}
  syncDynamicFields(false)
  step2Ref.value?.reset()
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
  form.tong_tien = Number(hopDong.tong_tien) || 0
  form.phat_sinh = Number(hopDong.phat_sinh) || 0
  form.chiet_khau = Number(hopDong.chiet_khau) || 0
  form.ma_giam_gia = hopDong.ma_giam_gia || ''
  form.khuyen_mai_theo_ma_giam_gia = Number(hopDong.khuyen_mai_theo_ma_giam_gia) || 0
  form.so_tien_thanh_toan_lan_1 = Number(hopDong.so_tien_thanh_toan_lan_1) || 0
  form.so_tien_thanh_toan_lan_2 = Number(hopDong.so_tien_thanh_toan_lan_2) || 0
  form.so_tien_thanh_toan_lan_3 = Number(hopDong.so_tien_thanh_toan_lan_3) || 0
  form.thoi_gian_thanh_toan_lan_1 = hopDong.thoi_gian_thanh_toan_lan_1 || null
  form.thoi_gian_thanh_toan_lan_2 = hopDong.thoi_gian_thanh_toan_lan_2 || null
  form.thoi_gian_thanh_toan_lan_3 = hopDong.thoi_gian_thanh_toan_lan_3 || null
  form.qua_tang_kem = hopDong.qua_tang_kem || ''
  form.yeu_cau_dac_biet = hopDong.yeu_cau_dac_biet || ''
  form.thong_tin_hop_dong =
    hopDong.thong_tin_hop_dong && typeof hopDong.thong_tin_hop_dong === 'object'
      ? { ...hopDong.thong_tin_hop_dong }
      : {}
  syncDynamicFields(true)
}

async function loadOptions() {
  if (optionsLoaded.value) return
  try {
    const [loaiRes, userRes, nguonRes] = await Promise.all([
      fetchLoaiHopDong({ per_page: 100, trang_thai: 'hoat_dong' }),
      fetchUsers({ per_page: 100, status: 'active' }),
      fetchDanhMucNguonKhach({ per_page: 100, trang_thai: 'active' }),
    ])
    loaiHopDongOptions.value = loaiRes.data.data || []
    userOptions.value = userRes.data.data || []
    kenhTiepCanOptions.value = (nguonRes.data.data || []).map((item) => item.ten_nguon_khach)
    optionsLoaded.value = true
  } catch {
    loaiHopDongOptions.value = []
    userOptions.value = []
    kenhTiepCanOptions.value = []
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

function hydrateChildSteps(hopDong) {
  step2Ref.value?.hydrate(hopDong)
  step3Ref.value?.hydrate(hopDong)
}

function resetChildSteps() {
  step2Ref.value?.reset()
  step3Ref.value?.reset()
}

async function saveStep1(silent = false) {
  const valid = await step1Ref.value?.validate()
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

async function saveStep2(silent = false) {
  if (!form.id) {
    ElMessage.error('Không tìm thấy hợp đồng để cập nhật.')
    return false
  }

  saving.value = true
  try {
    const payload = step2Ref.value?.getPayload() || { combos: [], dich_vu: [], tong_tien: 0 }
    const { data } = await updateHopDongSuDungDichVu(form.id, {
      ...payload,
      trang_thai: 'nhap',
    })
    syncFormFromHopDong(data)
    step2Ref.value?.hydrate(data)
    emit('saved', data)
    if (!silent) ElMessage.success('Đã lưu dịch vụ.')
    return true
  } catch {
    return false
  } finally {
    saving.value = false
  }
}

async function saveStep3(silent = false) {
  if (!form.id) {
    ElMessage.error('Không tìm thấy hợp đồng để cập nhật.')
    return false
  }

  saving.value = true
  try {
    const payload = step3Ref.value?.getPayload() || {}
    const { data } = await updateHopDongSuDungDichVu(form.id, {
      ...payload,
      trang_thai: 'dang_thuc_hien',
    })
    syncFormFromHopDong(data)
    step3Ref.value?.hydrate(data)
    emit('saved', data)
    if (!silent) ElMessage.success('Đã lưu thanh toán. Hợp đồng chuyển sang Đang thực hiện.')
    visible.value = false
    return true
  } catch {
    return false
  } finally {
    saving.value = false
  }
}

async function onSaveCurrentStep() {
  if (activeStep.value === 0) return saveStep1(false)
  if (activeStep.value === 1) return saveStep2(false)
  if (activeStep.value === 2) return saveStep3(false)
  return false
}

async function onNext() {
  if (activeStep.value === 0) {
    const ok = await saveStep1(true)
    if (!ok) return
    await step2Ref.value?.loadOptions()
  } else if (activeStep.value === 1) {
    const ok = await saveStep2(true)
    if (!ok) return
    await step3Ref.value?.loadOptions()
  }
  if (activeStep.value < steps.length - 1) {
    activeStep.value += 1
  }
}

function onClosed() {
  activeStep.value = 0
  resetChildSteps()
  emit('closed')
}

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return
    activeStep.value = 0
    await nextTick()
    resetChildSteps()
    syncFormFromHopDong(props.hopDong)
    hydrateChildSteps(props.hopDong)
    await loadOptions()
    // Load options xong mới gắn dynamicFields — tránh lần mở đầu bị xóa thong_tin_hop_dong
    syncFormFromHopDong(props.hopDong)
    hydrateChildSteps(props.hopDong)
  },
)

watch(
  () => props.hopDong,
  (hopDong) => {
    if (props.modelValue) {
      syncFormFromHopDong(hopDong)
      hydrateChildSteps(hopDong)
    }
  },
)

watch(dynamicFields, () => {
  if (props.modelValue) syncDynamicFields(true)
})

watch(
  () => form.loai_hop_dong_id,
  () => {
    if (!props.modelValue) return
    if (activeStep.value >= 1) {
      step2Ref.value?.loadOptions(true)
    }
  },
)

watch(activeStep, (step) => {
  if (step === 1) step2Ref.value?.loadOptions()
  if (step === 2) step3Ref.value?.loadOptions()
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

.footer-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
