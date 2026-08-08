<template>
  <CustomDialog
    v-model="visible"
    :width="1100"
    class="hop-dong-sddv-thanh-toan-modal"
    @closed="onClosed"
  >
    <template #header>
      <div class="modal-header">
        <div class="modal-header__title">{{ modalTitle }}</div>
        <div v-if="headerMeta" class="modal-header__meta">{{ headerMeta }}</div>
      </div>
    </template>

    <div v-loading="loading" class="thanh-toan-body">
      <section class="info-section">
        <div class="section-title">Thông tin khách hàng</div>
        <CustomForm label-position="top">
          <CustomRow :gutter="16">
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Mã hợp đồng">
                <CustomInput :model-value="display(hopDongData?.ma_hop_dong)" readonly />
              </CustomFormItem>
            </CustomCol>
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Họ tên khách hàng">
                <CustomInput :model-value="display(tenKhachHang)" readonly />
              </CustomFormItem>
            </CustomCol>
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Số điện thoại">
                <CustomInput :model-value="display(soDienThoai)" readonly />
              </CustomFormItem>
            </CustomCol>
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Địa chỉ">
                <CustomInput :model-value="display(hopDongData?.dia_chi)" readonly />
              </CustomFormItem>
            </CustomCol>
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Loại hợp đồng">
                <CustomInput
                  :model-value="display(hopDongData?.loai_hop_dong?.ten_hop_dong)"
                  readonly
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Trạng thái">
                <CustomInput :model-value="trangThaiLabel(hopDongData?.trang_thai)" readonly />
              </CustomFormItem>
            </CustomCol>
          </CustomRow>
        </CustomForm>
      </section>

      <section class="payment-section">
        <div class="section-title-row">
          <div class="section-title">Thông tin thanh toán</div>
          <CustomTag v-if="isFullyPaid" type="success" size="small">Đã thanh toán đủ</CustomTag>
        </div>
        <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
          <CustomRow :gutter="16">
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Tổng tiền dịch vụ">
                <MoneyInput :model-value="tongTien" readonly style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Phát sinh">
                <MoneyInput :model-value="phatSinh" readonly style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Chiết khấu">
                <MoneyInput :model-value="chietKhau" readonly style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Giảm giá">
                <MoneyInput :model-value="giamGia" readonly style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Khách phải thanh toán">
                <MoneyInput :model-value="khachPhaiThanhToan" readonly style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Đã thanh toán">
                <MoneyInput :model-value="daThanhToan" readonly style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol v-bind="fieldColProps">
              <CustomFormItem label="Còn phải thanh toán">
                <CustomInput
                  :model-value="formatMoney(conLai)"
                  readonly
                  class="con-lai-input"
                  :class="{ 'is-paid': isFullyPaid }"
                />
              </CustomFormItem>
            </CustomCol>
            <template v-if="canSubmit">
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem :label="lanThanhToanLabel" prop="so_tien_thanh_toan">
                  <MoneyInput
                    v-model="form.so_tien_thanh_toan"
                    :readonly="isLan3Payment"
                    style="width: 100%"
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Hình thức thanh toán" prop="hinh_thuc_thanh_toan">
                  <CustomSelect
                    v-model="form.hinh_thuc_thanh_toan"
                    placeholder="Chọn hình thức"
                    style="width: 100%"
                  >
                    <CustomOption
                      v-for="opt in hinhThucOptions"
                      :key="opt.value"
                      :label="opt.label"
                      :value="opt.value"
                    />
                  </CustomSelect>
                </CustomFormItem>
              </CustomCol>
            </template>
          </CustomRow>

          <CustomRow v-if="canSubmit" :gutter="16">
            <CustomCol :span="24">
              <CustomFormItem label="Ghi chú thanh toán" prop="ghi_chu_sale">
                <CustomInput
                  v-model="form.ghi_chu_sale"
                  type="textarea"
                  :rows="3"
                  placeholder="Ghi chú cho lần thanh toán này (tuỳ chọn)"
                />
              </CustomFormItem>
            </CustomCol>
          </CustomRow>
        </CustomForm>

        <div class="installment-summary">
          <div class="installment-summary__title">Lịch sử thanh toán</div>
          <CustomTable :data="installmentRows" stripe style="width: 100%">
            <CustomTableColumn prop="lan" label="Lần" width="90" align="center" />
            <CustomTableColumn label="Số tiền" min-width="140" align="right">
              <template #default="{ row }">{{ formatMoney(row.so_tien) }}</template>
            </CustomTableColumn>
            <CustomTableColumn label="Thời gian" min-width="140" align="center">
              <template #default="{ row }">{{ formatDate(row.thoi_gian) }}</template>
            </CustomTableColumn>
            <CustomTableColumn label="Hạn" min-width="140" align="center">
              <template #default="{ row }">{{ formatDate(row.han) }}</template>
            </CustomTableColumn>
            <CustomTableColumn label="Trạng thái" width="140" align="center">
              <template #default="{ row }">
                <CustomTag :type="row.so_tien > 0 ? 'success' : 'info'" size="small">
                  {{ row.so_tien > 0 ? 'Đã ghi nhận' : 'Chưa thanh toán' }}
                </CustomTag>
              </template>
            </CustomTableColumn>
          </CustomTable>
        </div>
      </section>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Đóng</CustomButton>
        <CustomButton
          v-if="canSubmit"
          type="primary"
          :loading="saving"
          @click="submit"
        >
          Xác nhận thanh toán
        </CustomButton>
      </div>
    </template>
  </CustomDialog>

  <HopDongChoThueDoiThanhToanModal
    v-model="doiThanhToanVisible"
    :so-tien="Number(form.so_tien_thanh_toan) || 0"
    :ma-hop-dong="hopDongData?.ma_hop_dong || ''"
    @confirmed="onDoiThanhToanConfirmed"
  />
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import {
  getHopDongSuDungDichVu,
  thanhToanHopDongSuDungDichVu,
} from '@/api/hopDongSuDungDichVu'
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
  CustomTable,
  CustomTableColumn,
  CustomTag,
  MoneyInput,
} from '@/components/element'
import HopDongChoThueDoiThanhToanModal from '@/views/van-hanh-cuoi/hop-dong-cho-thue/HopDongChoThueDoiThanhToanModal.vue'

const fieldColProps = { xs: 12, sm: 8, md: 6, lg: 4, xl: 4 }

const TRANG_THAI_OPTIONS = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
  { value: 'da_coc', label: 'Đã cọc' },
  { value: 'dang_thuc_hien', label: 'Đang thực hiện' },
  { value: 'da_huy', label: 'Đã hủy' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
]

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  hopDong: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved', 'closed'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const hinhThucOptions = [
  { value: 'tien_mat', label: 'Tiền mặt' },
  { value: 'chuyen_khoan', label: 'Chuyển khoản' },
]

const loading = ref(false)
const saving = ref(false)
const formRef = ref(null)
const hopDongData = ref(null)
const doiThanhToanVisible = ref(false)

const form = reactive({
  so_tien_thanh_toan: 0,
  hinh_thuc_thanh_toan: 'tien_mat',
  ghi_chu_sale: '',
})

const headerMeta = computed(() => {
  const parts = [
    hopDongData.value?.ma_hop_dong,
    tenKhachHang.value !== '—' ? tenKhachHang.value : null,
    soDienThoai.value !== '—' ? soDienThoai.value : null,
  ].filter(Boolean)
  return parts.join(' · ')
})

const tenKhachHang = computed(() => {
  if (hopDongData.value?.ten_khach_hang) return hopDongData.value.ten_khach_hang
  const info =
    hopDongData.value?.thong_tin_hop_dong &&
    typeof hopDongData.value.thong_tin_hop_dong === 'object'
      ? hopDongData.value.thong_tin_hop_dong
      : {}
  const tenChuRe = info.tenChuRe || info.ten_chu_re
  const tenCoDau = info.tenCoDau || info.ten_co_dau
  if (tenChuRe || tenCoDau) return [tenChuRe, tenCoDau].filter(Boolean).join(' & ')
  return info.hoTenKhachHang || info.ho_ten_khach_hang || info.hoTenKhach || '—'
})

const soDienThoai = computed(() => {
  if (hopDongData.value?.sdt_khach_hang) return hopDongData.value.sdt_khach_hang
  const info =
    hopDongData.value?.thong_tin_hop_dong &&
    typeof hopDongData.value.thong_tin_hop_dong === 'object'
      ? hopDongData.value.thong_tin_hop_dong
      : {}
  return info.soDienThoai || info.so_dien_thoai || info.sdt || '—'
})

const tongTien = computed(() => Number(hopDongData.value?.tong_tien) || 0)
const phatSinh = computed(() => Number(hopDongData.value?.phat_sinh) || 0)
const chietKhau = computed(() => Number(hopDongData.value?.chiet_khau) || 0)
const giamGia = computed(() => Number(hopDongData.value?.khuyen_mai_theo_ma_giam_gia) || 0)

const khachPhaiThanhToan = computed(() =>
  Math.max(0, tongTien.value + phatSinh.value - chietKhau.value - giamGia.value),
)

const lan1 = computed(() => Number(hopDongData.value?.so_tien_thanh_toan_lan_1) || 0)
const lan2 = computed(() => Number(hopDongData.value?.so_tien_thanh_toan_lan_2) || 0)
const lan3 = computed(() => Number(hopDongData.value?.so_tien_thanh_toan_lan_3) || 0)

const daThanhToan = computed(() => lan1.value + lan2.value + lan3.value)
const conLai = computed(() => Math.max(0, khachPhaiThanhToan.value - daThanhToan.value))

/** Lần 3 = phần còn lại sau lần 1 & 2 */
const soTienLan3 = computed(() =>
  Math.max(0, khachPhaiThanhToan.value - lan1.value - lan2.value),
)

const nextPaymentSlot = computed(() => {
  if (lan1.value <= 0) return 1
  if (lan2.value <= 0) return 2
  if (lan3.value <= 0) return 3
  return null
})

const isLan3Payment = computed(() => nextPaymentSlot.value === 3)

const lanThanhToanLabel = computed(() => {
  if (nextPaymentSlot.value) return `Số tiền thanh toán lần ${nextPaymentSlot.value}`
  return 'Số tiền thanh toán'
})

function defaultSoTienThanhToan(row = hopDongData.value) {
  const tong =
    (Number(row?.tong_tien) || 0) +
    (Number(row?.phat_sinh) || 0) -
    (Number(row?.chiet_khau) || 0) -
    (Number(row?.khuyen_mai_theo_ma_giam_gia) || 0)
  const paid1 = Number(row?.so_tien_thanh_toan_lan_1) || 0
  const paid2 = Number(row?.so_tien_thanh_toan_lan_2) || 0
  const paid3 = Number(row?.so_tien_thanh_toan_lan_3) || 0

  // Lần 3: luôn = khách phải TT − lần 1 − lần 2
  if (paid1 > 0 && paid2 > 0 && paid3 <= 0) {
    return Math.max(0, tong - paid1 - paid2)
  }

  return Math.max(0, tong - paid1 - paid2 - paid3)
}

const installmentRows = computed(() => [
  {
    lan: 'Lần 1',
    so_tien: lan1.value,
    thoi_gian: hopDongData.value?.thoi_gian_thanh_toan_lan_1,
    han: null,
  },
  {
    lan: 'Lần 2',
    so_tien: lan2.value,
    thoi_gian: hopDongData.value?.thoi_gian_thanh_toan_lan_2,
    han: hopDongData.value?.han_thanh_toan_lan_2,
  },
  {
    lan: 'Lần 3',
    so_tien: lan3.value,
    thoi_gian: hopDongData.value?.thoi_gian_thanh_toan_lan_3,
    han: hopDongData.value?.han_thanh_toan_lan_3,
  },
])

const canSubmit = computed(
  () =>
    Boolean(hopDongData.value?.id) &&
    conLai.value > 0 &&
    nextPaymentSlot.value != null &&
    ['da_coc', 'dang_thuc_hien'].includes(hopDongData.value?.trang_thai),
)

const isFullyPaid = computed(() => Boolean(hopDongData.value?.id) && conLai.value <= 0 && daThanhToan.value > 0)

const isViewOnly = computed(() => Boolean(hopDongData.value?.id) && !canSubmit.value)

const modalTitle = computed(() =>
  isViewOnly.value ? 'Lịch sử thanh toán' : 'Thanh toán hợp đồng',
)

const rules = {
  so_tien_thanh_toan: [
    {
      validator: (_rule, value, callback) => {
        const amount = Number(value) || 0
        if (amount <= 0) {
          callback(new Error('Nhập số tiền thanh toán'))
          return
        }
        if (amount > conLai.value) {
          callback(new Error('Không được vượt quá số tiền còn lại'))
          return
        }
        callback()
      },
      trigger: 'change',
    },
  ],
  hinh_thuc_thanh_toan: [
    { required: true, message: 'Chọn hình thức thanh toán', trigger: 'change' },
  ],
}

function trangThaiLabel(value) {
  return TRANG_THAI_OPTIONS.find((opt) => opt.value === value)?.label || value || '—'
}

function display(value) {
  if (value == null || value === '' || value === '—') return '—'
  return value
}

function formatMoney(value) {
  if (value == null || value === '') return '—'
  const num = Number(value)
  if (Number.isNaN(num)) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function formatDate(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString('vi-VN')
}

function fillFromHopDong(row) {
  hopDongData.value = row || null
  form.so_tien_thanh_toan = defaultSoTienThanhToan(row)
  form.hinh_thuc_thanh_toan = 'tien_mat'
  form.ghi_chu_sale = ''
}

async function loadDetail(id) {
  if (!id) return
  loading.value = true
  try {
    const { data } = await getHopDongSuDungDichVu(id)
    fillFromHopDong(data)
  } catch {
    fillFromHopDong(props.hopDong)
  } finally {
    loading.value = false
  }
}

async function submit() {
  if (!canSubmit.value) return

  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  if (form.hinh_thuc_thanh_toan === 'chuyen_khoan') {
    doiThanhToanVisible.value = true
    return
  }

  await submitThanhToan()
}

async function onDoiThanhToanConfirmed() {
  doiThanhToanVisible.value = false
  await submitThanhToan()
}

async function submitThanhToan() {
  if (!hopDongData.value?.id) return

  const soTien = isLan3Payment.value
    ? soTienLan3.value
    : Number(form.so_tien_thanh_toan) || 0

  if (isLan3Payment.value) {
    form.so_tien_thanh_toan = soTien
  }

  saving.value = true
  try {
    const { data } = await thanhToanHopDongSuDungDichVu(hopDongData.value.id, {
      so_tien_thanh_toan: soTien,
      hinh_thuc_thanh_toan: form.hinh_thuc_thanh_toan,
      ghi_chu_sale: form.ghi_chu_sale?.trim() || null,
    })
    ElMessage.success('Đã ghi nhận thanh toán thành công.')
    visible.value = false
    emit('saved', data)
  } catch {
    // interceptor
  } finally {
    saving.value = false
  }
}

function resetLocalState() {
  hopDongData.value = null
  doiThanhToanVisible.value = false
  form.so_tien_thanh_toan = 0
  form.hinh_thuc_thanh_toan = 'tien_mat'
  form.ghi_chu_sale = ''
  formRef.value?.clearValidate?.()
}

function onClosed() {
  resetLocalState()
  emit('closed')
}

watch(
  () => [props.modelValue, props.hopDong?.id],
  ([isOpen, id]) => {
    if (!isOpen) return
    if (id) {
      fillFromHopDong(props.hopDong)
      loadDetail(id)
    } else {
      fillFromHopDong(null)
    }
  },
)
</script>

<style scoped lang="scss">
.thanh-toan-body {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-height: 200px;
}

.modal-header {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding-right: 28px;
}

.modal-header__title {
  font-size: 16px;
  font-weight: 600;
  line-height: 1.4;
  color: var(--el-text-color-primary);
}

.modal-header__meta {
  font-size: 13px;
  line-height: 1.4;
  color: var(--el-text-color-secondary);
  word-break: break-word;
}

.section-title {
  margin-bottom: 12px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.section-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 12px;

  .section-title {
    margin-bottom: 0;
  }
}

.info-section,
.payment-section {
  padding: 14px 16px 4px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
  background: var(--el-fill-color-blank);
}

.con-lai-input :deep(.el-input__inner) {
  font-weight: 600;
  color: var(--el-color-danger);
}

.con-lai-input.is-paid :deep(.el-input__inner) {
  color: var(--el-color-success);
}

.installment-summary {
  margin-top: 8px;
  margin-bottom: 12px;
}

.installment-summary__title {
  margin-bottom: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
