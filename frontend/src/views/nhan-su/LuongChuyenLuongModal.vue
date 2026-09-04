<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="920"
    class="luong-chuyen-luong-dialog"
    @closed="onClosed"
  >
    <div v-loading="loading" class="chuyen-luong-body">
      <div class="amount-box">
        <div class="amount-box__label">Số tiền thực nhận</div>
        <div class="amount-box__value">{{ formatMoney(thucNhan) }}</div>
        <div class="amount-box__hint">
          Tháng {{ formatMonthLabel(month) }}
          <template v-if="employeeName"> · {{ employeeName }}</template>
        </div>
        <div
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
        </div>
      </div>

      <div class="payment-layout">
        <section class="bank-panel">
          <div class="section-title-row">
            <div class="section-title">Tài khoản nhận lương</div>
            <CustomButton
              type="primary"
              plain
              size="small"
              :loading="fetchingBank"
              :disabled="!canFetchBank"
              @click="fetchBankInfo"
            >
              Lấy thông tin
            </CustomButton>
          </div>
          <div v-if="hasBankInfo" class="bank-card">
            <CustomForm label-position="top" class="bank-form">
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
                    <CustomInput
                      :model-value="bank.so_tai_khoan || '—'"
                      readonly
                      class="input-mono"
                    >
                      <template v-if="bank.so_tai_khoan" #append>
                        <CustomButton
                          type="primary"
                          @click="copyText(bank.so_tai_khoan, 'Đã sao chép số tài khoản')"
                        >
                          Sao chép
                        </CustomButton>
                      </template>
                    </CustomInput>
                  </CustomFormItem>
                </CustomCol>
                <CustomCol :xs="24" :sm="12">
                  <CustomFormItem label="Chủ tài khoản">
                    <CustomInput :model-value="bank.chu_tai_khoan || '—'" readonly />
                  </CustomFormItem>
                </CustomCol>
                <CustomCol v-if="transferNote" :span="24">
                  <CustomFormItem label="Nội dung CK">
                    <CustomInput :model-value="transferNote" readonly>
                      <template #append>
                        <CustomButton
                          type="primary"
                          @click="copyText(transferNote, 'Đã sao chép nội dung chuyển khoản')"
                        >
                          Sao chép
                        </CustomButton>
                      </template>
                    </CustomInput>
                  </CustomFormItem>
                </CustomCol>
              </CustomRow>
            </CustomForm>
            <div class="bank-note">
              <strong>Lưu ý:</strong>
              Kiểm tra kỹ số tài khoản, tên chủ tài khoản và chuyển đúng số tiền thực nhận
              trước khi xác nhận đã chuyển lương.
            </div>
          </div>
          <div v-else class="empty-bank">
            <div class="empty-bank__text">
              Chưa có thông tin tài khoản nhận lương trong dữ liệu chốt.
              Vui lòng bổ sung trong hồ sơ nhân viên, rồi nhấn
              <strong>Lấy thông tin</strong> để đồng bộ vào bảng lương tháng này.
            </div>
          </div>
        </section>

        <section class="qr-panel">
          <div class="section-title">Quét mã QR để chuyển lương</div>
          <div class="qr-frame">
            <div v-if="!hasBankInfo" class="qr-placeholder">
              <div class="qr-placeholder__text">
                Không thể tạo QR vì thiếu thông tin tài khoản. Hãy lấy thông tin từ hồ sơ nhân viên trước.
              </div>
            </div>
            <div v-else class="qr-content">
              <img
                :src="vietQrUrl"
                :alt="`QR ${bank.ngan_hang || 'chuyển lương'}`"
                class="qr-image"
              />
            </div>
          </div>
        </section>
      </div>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Đóng</CustomButton>
        <CustomButton
          type="primary"
          :loading="submitting"
          :disabled="!canConfirm"
          @click="confirmTransferred"
        >
          Đã chuyển lương
        </CustomButton>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getUser } from '@/api/users'
import { chuyenLuongNhanVien, layThongTinNguoiNhanLuong } from '@/api/tinhLuong'
import {
  CustomButton,
  CustomCol,
  CustomDialog,
  CustomForm,
  CustomFormItem,
  CustomInput,
  CustomRow,
} from '@/components/element'

defineOptions({ name: 'LuongChuyenLuongModal' })

const emit = defineEmits(['saved'])

const visible = ref(false)
const loading = ref(false)
const submitting = ref(false)
const fetchingBank = ref(false)
const month = ref('')
const employeeName = ref('')
const userId = ref(null)
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
  if (name) return `Chuyển lương · ${name} · ${monthLabel}`
  return `Chuyển lương · ${monthLabel}`
})

const hasBankInfo = computed(() => {
  return Boolean(bank.value.so_tai_khoan && bank.value.ngan_hang)
})

const isPaid = computed(() => trangThaiThanhToan.value === 'da_thanh_toan')

const canFetchBank = computed(() => {
  return Boolean(userId.value && month.value && !isPaid.value && !fetchingBank.value)
})

const canConfirm = computed(() => {
  return (
    hasBankInfo.value
    && Number(thucNhan.value) > 0
    && !isPaid.value
    && !submitting.value
  )
})

const transferNote = computed(() => {
  if (!month.value) return ''
  const [y, m] = String(month.value).split('-')
  const name = employeeName.value || 'NV'
  return `LUONG ${m}/${y} ${name}`.trim().toUpperCase()
})

const vietQrUrl = computed(() => {
  if (!hasBankInfo.value) return ''
  return buildVietQrUrl({
    so_tai_khoan: bank.value.so_tai_khoan,
    ngan_hang: bank.value.ngan_hang,
    so_tien: thucNhan.value,
    noi_dung: transferNote.value,
    chu_tai_khoan: bank.value.chu_tai_khoan,
  })
})

/**
 * @param {{
 *   so_tai_khoan?: string,
 *   ngan_hang?: string,
 *   so_tien?: number,
 *   noi_dung?: string,
 *   chu_tai_khoan?: string,
 * }} data
 */
function buildVietQrUrl(data) {
  const params = new URLSearchParams()
  params.set('acc', data.so_tai_khoan || '')
  params.set('bank', data.ngan_hang || '')
  params.set('amount', String(Math.round(Number(data.so_tien) || 0)))
  params.set('des', data.noi_dung || '')
  params.set('fullacc', 'false')
  params.set('holder', String(data.chu_tai_khoan || '').toUpperCase())
  params.set('showinfo', 'false')
  return `https://vietqr.app/img?${params.toString()}`
}

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

function applyBankInfo(source = {}) {
  bank.value = {
    ngan_hang: source.ngan_hang || '',
    chi_nhanh: source.chi_nhanh || '',
    so_tai_khoan: source.so_tai_khoan || '',
    chu_tai_khoan: source.chu_tai_khoan || '',
  }
}

async function open({
  userId: uid,
  thang,
  name,
  thucNhan: amount,
  thongTinNguoiNhan,
  trangThaiThanhToan: paymentStatus,
} = {}) {
  if (!uid || !thang) return

  visible.value = true
  userId.value = uid
  month.value = String(thang)
  employeeName.value = name || ''
  thucNhan.value = Number(amount) || 0
  trangThaiThanhToan.value = paymentStatus || 'chua_thanh_toan'
  applyBankInfo({})
  loading.value = true

  try {
    if (thongTinNguoiNhan && typeof thongTinNguoiNhan === 'object') {
      applyBankInfo(thongTinNguoiNhan)
    } else {
      const { data } = await getUser(uid)
      const nv = data?.nhan_vien || data?.nhanVien || {}
      applyBankInfo(nv)
      if (!employeeName.value && data?.name) {
        employeeName.value = data.name
      }
    }
  } catch {
    applyBankInfo({})
  } finally {
    loading.value = false
  }
}

async function copyText(text, successMessage) {
  if (!text) return
  try {
    await navigator.clipboard.writeText(String(text))
    ElMessage.success(successMessage)
  } catch {
    ElMessage.warning('Không thể sao chép. Vui lòng copy thủ công.')
  }
}

async function fetchBankInfo() {
  if (!canFetchBank.value) return

  fetchingBank.value = true
  try {
    const { data } = await layThongTinNguoiNhanLuong(
      {
        thang: month.value,
        user_id: userId.value,
      },
      { skipLoading: true },
    )
    applyBankInfo(data?.thong_tin_nguoi_nhan || {})
    ElMessage.success(data?.message || 'Đã lấy thông tin tài khoản nhận lương.')
    emit('saved')
  } catch {
    // Axios interceptor đã hiển thị lỗi
  } finally {
    fetchingBank.value = false
  }
}

async function confirmTransferred() {
  if (!canConfirm.value || !userId.value || !month.value) return

  try {
    await ElMessageBox.confirm(
      `Xác nhận đã chuyển lương ${formatMoney(thucNhan.value)} cho ${employeeName.value || 'nhân viên'} tháng ${formatMonthLabel(month.value)}?`,
      'Xác nhận chuyển lương',
      {
        type: 'warning',
        confirmButtonText: 'Đã chuyển lương',
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  submitting.value = true
  try {
    const { data } = await chuyenLuongNhanVien(
      {
        thang: month.value,
        user_id: userId.value,
      },
      { skipLoading: true },
    )
    trangThaiThanhToan.value = data.trang_thai_thanh_toan || 'da_thanh_toan'
    ElMessage.success(data.message || 'Đã cập nhật trạng thái thanh toán lương.')
    emit('saved')
    visible.value = false
  } catch {
    // Axios interceptor đã hiển thị lỗi
  } finally {
    submitting.value = false
  }
}

function onClosed() {
  loading.value = false
  submitting.value = false
  fetchingBank.value = false
  month.value = ''
  employeeName.value = ''
  userId.value = null
  thucNhan.value = 0
  trangThaiThanhToan.value = ''
  applyBankInfo({})
}

defineExpose({ open })
</script>

<style scoped lang="scss">
.chuyen-luong-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: 240px;
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
  margin-bottom: 10px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.section-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;

  .section-title {
    margin-bottom: 0;
  }
}

.payment-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.85fr);
  gap: 16px;
  align-items: stretch;
}

@media (max-width: 640px) {
  .payment-layout {
    grid-template-columns: 1fr;
  }
}

.bank-panel,
.qr-panel {
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.bank-card {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 12px 14px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
  background: var(--el-fill-color-blank);
}

.bank-form {
  :deep(.el-form-item) {
    margin-bottom: 12px;
  }
}

.bank-note {
  margin-top: 4px;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 13px;
  line-height: 1.45;
  color: var(--el-color-warning-dark-2);
  background: color-mix(in srgb, var(--el-color-warning) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--el-color-warning) 28%, transparent);

  strong {
    font-weight: 650;
  }
}

.input-mono {
  :deep(.el-input__inner) {
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.02em;
  }
}

.empty-bank {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px 16px;
  text-align: center;
  color: var(--el-text-color-regular);
  background: color-mix(in srgb, var(--el-color-warning) 8%, var(--el-fill-color-lighter));
  border: 1px solid color-mix(in srgb, var(--el-color-warning) 28%, transparent);
  border-radius: 8px;
  font-size: 13px;
  line-height: 1.5;
}

.empty-bank__text {
  max-width: 360px;

  strong {
    font-weight: 650;
  }
}

.qr-frame {
  flex: 1;
  min-height: 260px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 12px;
  background: var(--el-fill-color-blank);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.qr-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}

.qr-placeholder__text {
  font-size: 14px;
  color: var(--el-text-color-secondary);
  text-align: center;
}

.qr-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  width: 100%;
}

.qr-image {
  width: min(240px, 100%);
  aspect-ratio: 1 / 1;
  object-fit: contain;
  border-radius: 8px;
  background: #fff;
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
