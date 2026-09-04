<template>
  <div class="step-panel step-panel--thanh-toan">
    <CustomForm class="step4-summary" label-position="top">
      <CustomRow :gutter="16">
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Tổng tiền dịch vụ">
            <MoneyInput :model-value="tongTienDichVuHienThi" readonly style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Phát sinh">
            <MoneyInput v-model="form.phat_sinh" style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Chiết khấu">
            <MoneyInput v-model="form.chiet_khau" style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Mã giảm giá">
            <CustomInput
              v-model="form.ma_giam_gia"
              class="ma-giam-gia-input"
              placeholder="Nhập mã giảm giá"
              clearable
              @keyup.enter="kiemTraMaGiamGia"
            >
              <template #append>
                <CustomButton
                  :icon="Search"
                  :loading="checkingMaGiamGia"
                  aria-label="Kiểm tra mã giảm giá"
                  @click="kiemTraMaGiamGia"
                />
              </template>
            </CustomInput>
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Số tiền giảm giá">
            <MoneyInput v-model="form.khuyen_mai_theo_ma_giam_gia" style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Khách hàng phải thanh toán">
            <MoneyInput
              :model-value="khachHangPhaiThanhToan"
              readonly
              class="tong-tien-input"
              style="width: 100%"
            />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Số tiền thanh toán lần 1">
            <MoneyInput v-model="form.so_tien_thanh_toan_lan_1" style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryWideColProps">
          <CustomFormItem label="Quà tặng kèm">
            <CustomInput v-model="form.qua_tang_kem" placeholder="Nhập quà tặng kèm" clearable />
          </CustomFormItem>
        </CustomCol>
        <CustomCol :span="24">
          <CustomFormItem label="Yêu cầu đặc biệt">
            <CustomInput
              v-model="form.yeu_cau_dac_biet"
              type="textarea"
              :rows="3"
              placeholder="Nhập yêu cầu đặc biệt"
            />
          </CustomFormItem>
        </CustomCol>
      </CustomRow>
    </CustomForm>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import { kiemTraMaGiamGiaHopDongSuDungDichVu } from '@/api/hopDongSuDungDichVu'
import {
  CustomButton,
  CustomCol,
  CustomForm,
  CustomFormItem,
  CustomInput,
  CustomRow,
  MoneyInput,
} from '@/components/element'
import { formatInteger } from '@/utils/number'

const props = defineProps({
  form: { type: Object, required: true },
  tongTienDichVu: { type: Number, default: 0 },
})

const checkingMaGiamGia = ref(false)
const lastCheckedMa = ref(null)

/** xl/lg: 6/hàng · md: 3/hàng · sm/xs (mobile): 2/hàng */
const summaryFieldColProps = {
  xs: 12,
  sm: 12,
  md: 8,
  lg: 4,
  xl: 4,
}

const summaryWideColProps = {
  xs: 24,
  sm: 24,
  md: 16,
  lg: 12,
  xl: 12,
}

const tongTienDichVuHienThi = computed(() => Number(props.tongTienDichVu) || 0)

const khachHangPhaiThanhToan = computed(() => {
  const tongDichVu = tongTienDichVuHienThi.value
  const phatSinh = Number(props.form.phat_sinh) || 0
  const giamGia = Number(props.form.khuyen_mai_theo_ma_giam_gia) || 0
  const chietKhau = Number(props.form.chiet_khau) || 0
  return Math.max(0, tongDichVu + phatSinh - giamGia - chietKhau)
})

const coSoTinhMaGiamGia = computed(() => {
  const tongDichVu = tongTienDichVuHienThi.value
  const phatSinh = Number(props.form.phat_sinh) || 0
  return Math.max(0, tongDichVu + phatSinh)
})

watch(
  () => props.form.ma_giam_gia,
  (ma) => {
    if (lastCheckedMa.value == null) return
    if ((ma || '').trim() !== lastCheckedMa.value) {
      props.form.khuyen_mai_theo_ma_giam_gia = 0
      lastCheckedMa.value = null
    }
  },
)

async function kiemTraMaGiamGia() {
  const ma = props.form.ma_giam_gia?.trim() || ''
  if (!ma) {
    ElMessage.warning('Vui lòng nhập mã giảm giá.')
    return
  }

  checkingMaGiamGia.value = true
  try {
    const { data } = await kiemTraMaGiamGiaHopDongSuDungDichVu(
      {
        ma_giam_gia: ma,
        co_so_tinh: coSoTinhMaGiamGia.value,
      },
      { skipLoading: true },
    )

    if (!data?.hop_le) {
      props.form.khuyen_mai_theo_ma_giam_gia = 0
      lastCheckedMa.value = null
      ElMessage.error(data?.message || 'Mã giảm giá không khớp')
      return
    }

    const soTienGiam = Number(data.so_tien_giam) || 0
    props.form.khuyen_mai_theo_ma_giam_gia = soTienGiam
    lastCheckedMa.value = ma
    ElMessage.success(
      soTienGiam > 0
        ? `Đã áp dụng mã giảm giá: ${formatInteger(soTienGiam)} ₫`
        : 'Mã giảm giá hợp lệ.',
    )
  } catch {
    props.form.khuyen_mai_theo_ma_giam_gia = 0
    lastCheckedMa.value = null
  } finally {
    checkingMaGiamGia.value = false
  }
}

function getPayload() {
  return {
    phat_sinh: Number(props.form.phat_sinh) || 0,
    chiet_khau: Number(props.form.chiet_khau) || 0,
    ma_giam_gia: props.form.ma_giam_gia?.trim() || null,
    khuyen_mai_theo_ma_giam_gia: Number(props.form.khuyen_mai_theo_ma_giam_gia) || 0,
    tong_tien_khach_phai_thanh_toan: khachHangPhaiThanhToan.value,
    so_tien_thanh_toan_lan_1: Number(props.form.so_tien_thanh_toan_lan_1) || 0,
    so_tien_thanh_toan_lan_2: Number(props.form.so_tien_thanh_toan_lan_2) || 0,
    so_tien_thanh_toan_lan_3: Number(props.form.so_tien_thanh_toan_lan_3) || 0,
    thoi_gian_thanh_toan_lan_1: resolveThoiGianThanhToan(
      props.form.so_tien_thanh_toan_lan_1,
      props.form.thoi_gian_thanh_toan_lan_1,
    ),
    thoi_gian_thanh_toan_lan_2: resolveThoiGianThanhToan(
      props.form.so_tien_thanh_toan_lan_2,
      props.form.thoi_gian_thanh_toan_lan_2,
    ),
    thoi_gian_thanh_toan_lan_3: resolveThoiGianThanhToan(
      props.form.so_tien_thanh_toan_lan_3,
      props.form.thoi_gian_thanh_toan_lan_3,
    ),
    qua_tang_kem: props.form.qua_tang_kem?.trim() || null,
    yeu_cau_dac_biet: props.form.yeu_cau_dac_biet?.trim() || null,
  }
}

/**
 * Có số tiền → giữ thời gian cũ hoặc ghi nhận thời điểm hiện tại (Y-m-d H:i:s).
 * Không có số tiền → xóa thời gian thanh toán.
 */
function resolveThoiGianThanhToan(amount, existing) {
  if (!(Number(amount) > 0)) return null
  if (existing) return existing
  return formatNowPaymentDateTimeStorage()
}

/** Lưu DB datetime: Y-m-d H:i:s — hiển thị hh:mm:ss dd/mm/yyyy */
function formatNowPaymentDateTimeStorage(date = new Date()) {
  const pad = (n) => String(n).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`
}

function hydrate() {
  lastCheckedMa.value = null
}

function reset() {
  lastCheckedMa.value = null
}

defineExpose({
  hydrate,
  reset,
  getPayload,
})
</script>

<style scoped lang="scss">
.step-panel {
  min-height: 220px;
}

.step4-summary {
  margin-top: 4px;
  padding: 14px 16px 4px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
  background: var(--el-fill-color-blank);
}

.step4-summary :deep(.el-form-item) {
  margin-bottom: 12px;
}

.tong-tien-input {
  :deep(.el-input__inner) {
    font-weight: 700;
    color: var(--el-color-primary);
  }
}

.ma-giam-gia-input {
  width: 100%;
}
</style>
