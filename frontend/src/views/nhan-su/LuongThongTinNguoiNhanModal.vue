<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="680"
    class="luong-thong-tin-nguoi-nhan-dialog"
    @closed="onClosed"
  >
    <div class="receiver-body">
      <div class="amount-box">
        <div class="amount-box__label">Số tiền thực nhận</div>
        <div class="amount-box__value">{{ formatMoney(thucNhan) }}</div>
        <div class="amount-box__hint">
          Tháng {{ formatMonthLabel(month) }}
          <template v-if="employeeName">  {{ employeeName?'':'' }}</template>
        </div>
        <!-- <div
          v-if="trangThaiThanhToan"
          class="amount-box__status"
          :class="
            trangThaiThanhToan === 'da_thanh_toan'
              ? 'amount-box__status--paid'
              : 'amount-box__status--unpaid'
          "
        >
          {{
            trangThaiThanhToan === 'da_thanh_toan'
              ? 'Đã thanh toán'
              : 'Chưa thanh toán'
          }}
        </div> -->
      </div>

      <div class="section-title">Thông tin người nhận</div>
      <CustomForm v-if="hasBankInfo" label-position="top" class="bank-form">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Ngân hàng">
              <CustomInput :model-value="bank.ngan_hang || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Chi nhánh">
              <CustomInput :model-value="bank.chi_nhanh || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Số tài khoản">
              <CustomInput :model-value="bank.so_tai_khoan || '—'" readonly class="input-mono" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Chủ tài khoản">
              <CustomInput :model-value="bank.chu_tai_khoan || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
      <div v-else class="empty-bank">
        Không có thông tin người nhận trong dữ liệu chốt lương.
      </div>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton type="primary" @click="visible = false">Đóng</CustomButton>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, ref } from 'vue'
import {
  CustomButton,
  CustomCol,
  CustomDialog,
  CustomForm,
  CustomFormItem,
  CustomInput,
  CustomRow,
} from '@/components/element'

defineOptions({ name: 'LuongThongTinNguoiNhanModal' })

const visible = ref(false)
const month = ref('')
const employeeName = ref('')
const thucNhan = ref(0)
const trangThaiThanhToan = ref('')
const bank = ref({
  ngan_hang: '',
  chi_nhanh: '',
  so_tai_khoan: '',
  chu_tai_khoan: '',
})

const dialogTitle = computed(() => {
  const name = employeeName.value
  const monthLabel = formatMonthLabel(month.value)
  if (name) return `Thông tin nhận lương · ${name}`
  return `Thông tin nhận lương · ${monthLabel}`
})

const hasBankInfo = computed(() => {
  const b = bank.value
  return Boolean(b.ngan_hang || b.chi_nhanh || b.so_tai_khoan || b.chu_tai_khoan)
})

function formatMonthLabel(value) {
  if (!value || !String(value).includes('-')) return '—'
  const [y, m] = String(value).split('-')
  return `${m}/${y}`
}

function formatMoney(value) {
  const num = Number(value)
  if (!Number.isFinite(num)) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function open({
  thang,
  name,
  thucNhan: amount,
  thongTinNguoiNhan,
  trangThaiThanhToan: paymentStatus,
} = {}) {
  month.value = thang ? String(thang) : ''
  employeeName.value = name || ''
  thucNhan.value = Number(amount) || 0
  trangThaiThanhToan.value = paymentStatus || ''
  const info = thongTinNguoiNhan && typeof thongTinNguoiNhan === 'object' ? thongTinNguoiNhan : {}
  bank.value = {
    ngan_hang: info.ngan_hang || '',
    chi_nhanh: info.chi_nhanh || '',
    so_tai_khoan: info.so_tai_khoan || '',
    chu_tai_khoan: info.chu_tai_khoan || '',
  }
  visible.value = true
}

function onClosed() {
  month.value = ''
  employeeName.value = ''
  thucNhan.value = 0
  trangThaiThanhToan.value = ''
  bank.value = {
    ngan_hang: '',
    chi_nhanh: '',
    so_tai_khoan: '',
    chu_tai_khoan: '',
  }
}

defineExpose({ open })
</script>

<style scoped lang="scss">
.receiver-body {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.amount-box {
  padding: 14px 16px;
  border-radius: 10px;
  background: var(--el-color-primary-light-9);
  border: 1px solid var(--el-color-primary-light-7);
}

.amount-box__label {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.amount-box__value {
  margin-top: 4px;
  font-size: 24px;
  font-weight: 700;
  color: var(--el-color-primary);
}

.amount-box__hint {
  margin-top: 6px;
  font-size: 13px;
  color: var(--el-text-color-regular);
}

.amount-box__status {
  display: inline-flex;
  margin-top: 10px;
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 650;
}

.amount-box__status--paid {
  background: color-mix(in srgb, var(--el-color-success) 16%, transparent);
  color: var(--el-color-success-dark-2);
}

.amount-box__status--unpaid {
  background: color-mix(in srgb, var(--el-color-warning) 16%, transparent);
  color: var(--el-color-warning-dark-2);
}

.section-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.bank-form {
  :deep(.el-form-item) {
    margin-bottom: 12px;
  }
}

.input-mono {
  :deep(.el-input__inner) {
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.02em;
  }
}

.empty-bank {
  padding: 16px 12px;
  text-align: center;
  color: var(--el-text-color-secondary);
  background: var(--el-fill-color-lighter);
  border-radius: 8px;
  font-size: 13px;
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
}
</style>
