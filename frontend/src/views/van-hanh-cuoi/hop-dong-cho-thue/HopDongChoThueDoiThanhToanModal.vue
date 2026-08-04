<template>
  <CustomDialog
    v-model="visible"
    title="Đợi thanh toán chuyển khoản"
    :width="860"
    class="doi-thanh-toan-modal"
    @closed="onClosed"
  >
    <div v-loading="loading" class="doi-thanh-toan-body">
      <div class="amount-box">
        <div class="amount-box__label">Số tiền cần chuyển</div>
        <div class="amount-box__value">{{ formatMoney(soTien) }}</div>
        <div v-if="maHopDong" class="amount-box__hint">
          Nội dung CK gợi ý: <strong>{{ maHopDong }}</strong>
        </div>
      </div>

      <div class="payment-layout">
        <aside class="bank-panel">
          <div class="section-title">Chọn ngân hàng</div>
          <div v-if="accounts.length" class="bank-list">
            <button
              v-for="item in accounts"
              :key="item.id"
              type="button"
              class="bank-logo-card"
              :class="{ 'is-selected': selectedId === item.id }"
              :title="item.ngan_hang"
              @click="selectedId = item.id"
            >
              <img
                v-if="item.hinh_anh_logo"
                :src="mediaUrl(item.hinh_anh_logo)"
                :alt="item.ngan_hang"
                class="bank-logo-card__img"
              />
              <span v-else class="bank-logo-card__fallback">
                {{ bankInitial(item.ngan_hang) }}
              </span>
            </button>
          </div>
          <div v-else class="empty-accounts">
            Không có tài khoản thanh toán đang hoạt động.
          </div>
        </aside>

        <section class="qr-panel">
          <div class="section-title">Quét mã QR để thanh toán</div>
          <div class="qr-frame">
            <div v-if="!selectedAccount" class="qr-placeholder">
              <el-icon class="is-loading qr-placeholder__icon" :size="36">
                <Loading />
              </el-icon>
              <div class="qr-placeholder__text">Vui lòng chọn ngân hàng</div>
            </div>
            <div v-else class="qr-content">
              <img
                :src="vietQrUrl"
                :alt="`QR ${selectedAccount.ngan_hang}`"
                class="qr-image"
              />
              <div class="qr-meta" v-if="false">
                <div class="qr-meta__bank">{{ selectedAccount.ngan_hang }}</div>
                <div class="qr-meta__row">
                  <span>{{ selectedAccount.so_tai_khoan }}</span>
                  <CustomButton
                    type="primary"
                    link
                    size="small"
                    @click="copyText(selectedAccount.so_tai_khoan, 'Đã sao chép số tài khoản')"
                  >
                    Sao chép
                  </CustomButton>
                </div>
                <div class="qr-meta__holder">{{ selectedAccount.chu_tai_khoan }}</div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Hủy</CustomButton>
        <CustomButton
          type="primary"
          :loading="confirming"
          :disabled="!selectedAccount"
          @click="confirmPaid"
        >
          Đã chuyển khoản
        </CustomButton>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Loading } from '@element-plus/icons-vue'
import { fetchTaiKhoanThanhToan } from '@/api/taiKhoanThanhToan'
import {
  CustomButton,
  CustomDialog,
} from '@/components/element'
import { mediaUrl } from '@/utils/media'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  soTien: { type: Number, default: 0 },
  maHopDong: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'confirmed', 'closed'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const loading = ref(false)
const confirming = ref(false)
const accounts = ref([])
const selectedId = ref(null)

const selectedAccount = computed(
  () => accounts.value.find((item) => item.id === selectedId.value) || null,
)

const vietQrUrl = computed(() => {
  if (!selectedAccount.value) return ''
  return buildVietQrUrl({
    so_tai_khoan: selectedAccount.value.so_tai_khoan,
    ngan_hang: selectedAccount.value.ngan_hang,
    so_tien: props.soTien,
    noi_dung: props.maHopDong || 'Thanh toan hop dong',
    chu_tai_khoan: selectedAccount.value.chu_tai_khoan,
  })
})

/**
 * Tạo URL ảnh VietQR.
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
  params.set('fullacc', 'true')
  params.set('holder', String(data.chu_tai_khoan || '').toUpperCase())
  params.set('showinfo', 'true')
  return `https://vietqr.app/img?${params.toString()}`
}

function formatMoney(value) {
  const num = Number(value) || 0
  return `${num.toLocaleString('vi-VN')} ₫`
}

function bankInitial(name) {
  const text = String(name || '').trim()
  if (!text) return '?'
  return text.slice(0, 2).toUpperCase()
}

async function loadAccounts() {
  loading.value = true
  try {
    const { data } = await fetchTaiKhoanThanhToan({
      page: 1,
      per_page: 100,
      trang_thai: 'dang_hoat_dong',
    })
    accounts.value = data.data || []
    selectedId.value = null
  } catch {
    accounts.value = []
    selectedId.value = null
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

function confirmPaid() {
  if (!selectedAccount.value) {
    ElMessage.warning('Vui lòng chọn ngân hàng để thanh toán.')
    return
  }
  confirming.value = true
  emit('confirmed', {
    tai_khoan_id: selectedId.value,
    tai_khoan: selectedAccount.value,
  })
  confirming.value = false
}

function onClosed() {
  accounts.value = []
  selectedId.value = null
  confirming.value = false
  emit('closed')
}

watch(
  () => props.modelValue,
  (isOpen) => {
    if (!isOpen) return
    loadAccounts()
  },
)
</script>

<style scoped lang="scss">
.doi-thanh-toan-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: 280px;
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

.section-title {
  margin-bottom: 10px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.payment-layout {
  display: grid;
  grid-template-columns: 168px minmax(0, 1fr);
  gap: 16px;
  align-items: start;
}

@media (max-width: 720px) {
  .payment-layout {
    grid-template-columns: 1fr;
  }
}

.bank-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 360px;
  overflow: auto;
  padding-right: 2px;
}

.bank-logo-card {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 56px;
  padding: 8px 14px;
  border: 1px solid var(--el-border-color);
  border-radius: 10px;
  background: var(--el-bg-color);
  cursor: pointer;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;

  &:hover {
    border-color: var(--el-color-primary-light-5);
  }

  &.is-selected {
    border-color: var(--el-color-primary);
    box-shadow: 0 0 0 1px var(--el-color-primary-light-7);
  }
}

.bank-logo-card__img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.bank-logo-card__fallback {
  font-size: 14px;
  font-weight: 700;
  color: var(--el-text-color-secondary);
}

.qr-frame {
  min-height: 320px;
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

.qr-placeholder__icon {
  color: var(--el-color-primary);
}

.qr-placeholder__text {
  font-size: 14px;
  color: var(--el-text-color-secondary);
}

.qr-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  width: 100%;
}

.qr-image {
  width: min(280px, 100%);
  aspect-ratio: 1 / 1;
  object-fit: contain;
  border-radius: 8px;
  background: #fff;
}

.qr-meta {
  text-align: center;
}

.qr-meta__bank {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.qr-meta__row {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 4px;
  font-size: 14px;
  color: var(--el-text-color-regular);
}

.qr-meta__holder {
  margin-top: 2px;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.empty-accounts {
  padding: 16px 8px;
  text-align: center;
  color: var(--el-text-color-secondary);
  background: var(--el-fill-color-lighter);
  border-radius: 8px;
  font-size: 13px;
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
