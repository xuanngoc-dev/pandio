<template>
  <CustomDialog
    v-model="visible"
    :width="920"
    class="hop-dong-sddv-doi-trang-thai-modal"
    @closed="onClosed"
  >
    <template #header>
      <div class="modal-header">
        <div class="modal-header__title">Thay đổi trạng thái</div>
        <div v-if="headerMeta" class="modal-header__meta">{{ headerMeta }}</div>
      </div>
    </template>

    <div v-loading="loading" class="doi-trang-thai-body">
      <section class="info-section">
        <CustomForm label-position="top">
          <CustomRow :gutter="16">
            <CustomCol :xs="12" :sm="8">
              <CustomFormItem label="Mã hợp đồng">
                <CustomInput :model-value="display(hopDongData?.ma_hop_dong)" readonly />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8">
              <CustomFormItem label="Trạng thái HĐ">
                <CustomInput :model-value="trangThaiHdLabel" readonly />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8">
              <CustomFormItem label="Trạng thái điều phối">
                <CustomInput :model-value="ketQuaTrangThaiLabel" readonly />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8">
              <CustomFormItem label="Khách hàng">
                <CustomInput :model-value="display(tenKhachHang)" readonly />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8">
              <CustomFormItem label="SĐT">
                <CustomInput :model-value="display(soDienThoai)" readonly />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8">
              <CustomFormItem label="Còn phải TT">
                <CustomInput :model-value="formatMoney(conLai)" readonly />
              </CustomFormItem>
            </CustomCol>
          </CustomRow>
        </CustomForm>
      </section>

      <section class="actions-section">
        <div class="section-title">Chọn hành động</div>
        <el-empty
          v-if="!loading && !availableActions.length"
          description="Không có hành động trạng thái khả dụng cho hợp đồng này."
        />
        <div v-else class="action-grid">
          <button
            v-for="action in availableActions"
            :key="action.key"
            type="button"
            class="action-card"
            :class="{
              'is-selected': selectedAction === action.key,
              [`is-${action.tone}`]: true,
            }"
            @click="selectAction(action.key)"
          >
            <div class="action-card__title">{{ action.label }}</div>
            <div class="action-card__desc">{{ action.description }}</div>
          </button>
        </div>
      </section>

      <section v-if="selectedActionMeta?.needLyDo" class="note-section">
        <CustomForm label-position="top">
          <CustomFormItem :label="selectedActionMeta.noteLabel">
            <CustomInput
              v-model="lyDo"
              type="textarea"
              :rows="3"
              :placeholder="selectedActionMeta.notePlaceholder"
            />
          </CustomFormItem>
        </CustomForm>
      </section>

      <section v-if="selectedActionMeta?.needYKien" class="note-section">
        <CustomForm label-position="top">
          <CustomFormItem :label="selectedActionMeta.noteLabel" required>
            <CustomInput
              v-model="yKien"
              type="textarea"
              :rows="4"
              maxlength="5000"
              show-word-limit
              :placeholder="selectedActionMeta.notePlaceholder"
            />
          </CustomFormItem>
        </CustomForm>
      </section>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Đóng</CustomButton>
        <CustomButton
          v-if="selectedActionMeta"
          :type="selectedActionMeta.buttonType"
          :loading="saving"
          :disabled="!canConfirm"
          @click="confirm"
        >
          {{ selectedActionMeta.confirmText }}
        </CustomButton>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  doiTrangThaiHopDongSuDungDichVu,
  getHopDongSuDungDichVu,
} from '@/api/hopDongSuDungDichVu'
import {
  CustomButton,
  CustomCol,
  CustomDialog,
  CustomForm,
  CustomFormItem,
  CustomInput,
  CustomRow,
} from '@/components/element'

const TRANG_THAI_HD = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
  { value: 'da_coc', label: 'Đã cọc' },
  { value: 'dang_thuc_hien', label: 'Đang thực hiện' },
  { value: 'da_huy', label: 'Đã hủy' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
]

const KET_QUA_TRANG_THAI = [
  { value: 'cho_nhan', label: 'Chờ nhận' },
  { value: 'dang_xu_ly', label: 'Đang xử lý' },
  { value: 'gui_khach_kiem_tra', label: 'Gửi khách kiểm tra' },
  { value: 'san_xuat_in_an', label: 'Sản xuất & in ấn' },
  { value: 'cho_nghiem_thu', label: 'Chờ nghiệm thu' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
]

const ACTION_META = {
  huy: {
    label: 'Hủy hợp đồng',
    description: 'Chuyển trạng thái hợp đồng sang Đã hủy.',
    tone: 'danger',
    buttonType: 'danger',
    confirmText: 'Xác nhận hủy',
    needLyDo: true,
    noteLabel: 'Lý do hủy (tuỳ chọn)',
    notePlaceholder: 'Nhập lý do hủy hợp đồng',
  },
  tat_toan: {
    label: 'Tất toán',
    description: 'Kết thúc hợp đồng sau khi đã thanh toán đủ.',
    tone: 'success',
    buttonType: 'success',
    confirmText: 'Xác nhận tất toán',
  },
  khach_dong_y: {
    label: 'Khách đồng ý',
    description: 'Bước Gửi khách kiểm tra → chuyển Sản xuất & in ấn.',
    tone: 'success',
    buttonType: 'success',
    confirmText: 'Xác nhận khách đồng ý',
  },
  khach_khong_dong_y: {
    label: 'Khách không đồng ý',
    description: 'Bước Gửi khách kiểm tra → chuyển lại Đang xử lý.',
    tone: 'danger',
    buttonType: 'danger',
    confirmText: 'Xác nhận không đồng ý',
    needYKien: true,
    noteLabel: 'Ý kiến khách hàng',
    notePlaceholder: 'Nhập ý kiến / lý do khách không đồng ý',
  },
  nghiem_thu_hoan_thanh: {
    label: 'Nghiệm thu — Hoàn thành',
    description: 'Bước Chờ nghiệm thu → hoàn thành điều phối.',
    tone: 'success',
    buttonType: 'success',
    confirmText: 'Xác nhận hoàn thành',
  },
  nghiem_thu_lam_lai: {
    label: 'Nghiệm thu — Làm lại',
    description: 'Bước Chờ nghiệm thu → chuyển lại Sản xuất & in ấn.',
    tone: 'warning',
    buttonType: 'warning',
    confirmText: 'Xác nhận làm lại',
    needYKien: true,
    noteLabel: 'Yêu cầu của khách hàng',
    notePlaceholder: 'Nhập yêu cầu làm lại',
  },
}

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  hopDong: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved', 'closed'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const loading = ref(false)
const saving = ref(false)
const hopDongData = ref(null)
const selectedAction = ref('')
const lyDo = ref('')
const yKien = ref('')

const headerMeta = computed(() => {
  const parts = [
    hopDongData.value?.ma_hop_dong,
    tenKhachHang.value !== '—' ? tenKhachHang.value : null,
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

const trangThaiHd = computed(() => hopDongData.value?.trang_thai || '')
const trangThaiHdLabel = computed(
  () => TRANG_THAI_HD.find((item) => item.value === trangThaiHd.value)?.label || trangThaiHd.value || '—',
)

const ketQuaTrangThai = computed(() => {
  const raw = hopDongData.value?.ket_qua_hop_dong
  const ketQua = raw && typeof raw === 'object' && !Array.isArray(raw) ? raw : {}
  const giaTri = ketQua?.trang_thai?.gia_tri
  return giaTri == null || giaTri === '' ? null : String(giaTri)
})

const ketQuaTrangThaiLabel = computed(() => {
  if (!ketQuaTrangThai.value) return '—'
  return (
    KET_QUA_TRANG_THAI.find((item) => item.value === ketQuaTrangThai.value)?.label ||
    ketQuaTrangThai.value
  )
})

const conLai = computed(() => {
  const row = hopDongData.value
  if (!row) return 0
  const khachPhai =
    (Number(row.tong_tien) || 0) +
    (Number(row.phat_sinh) || 0) -
    (Number(row.chiet_khau) || 0) -
    (Number(row.khuyen_mai_theo_ma_giam_gia) || 0)
  const daTra =
    (Number(row.so_tien_thanh_toan_lan_1) || 0) +
    (Number(row.so_tien_thanh_toan_lan_2) || 0) +
    (Number(row.so_tien_thanh_toan_lan_3) || 0)
  return Math.max(0, khachPhai - daTra)
})

const availableActions = computed(() => {
  const actions = []
  const hd = trangThaiHd.value
  const ketQua = ketQuaTrangThai.value
  const ended = ['da_huy', 'hoan_thanh'].includes(hd)

  if (!ended) {
    actions.push({ key: 'huy', ...ACTION_META.huy })
  }

  if (['da_coc', 'dang_thuc_hien'].includes(hd) && conLai.value <= 0) {
    actions.push({ key: 'tat_toan', ...ACTION_META.tat_toan })
  }

  // Đồng bộ với DieuPhoiTuDongCard / Công việc của tôi
  if (ketQua === 'gui_khach_kiem_tra') {
    actions.push({ key: 'khach_dong_y', ...ACTION_META.khach_dong_y })
    actions.push({ key: 'khach_khong_dong_y', ...ACTION_META.khach_khong_dong_y })
  }

  if (ketQua === 'cho_nghiem_thu') {
    actions.push({ key: 'nghiem_thu_hoan_thanh', ...ACTION_META.nghiem_thu_hoan_thanh })
    actions.push({ key: 'nghiem_thu_lam_lai', ...ACTION_META.nghiem_thu_lam_lai })
  }

  return actions
})

const selectedActionMeta = computed(() => {
  if (!selectedAction.value) return null
  return ACTION_META[selectedAction.value] || null
})

const canConfirm = computed(() => {
  if (!selectedAction.value || !hopDongData.value?.id) return false
  if (selectedActionMeta.value?.needYKien && !String(yKien.value || '').trim()) return false
  return true
})

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

function selectAction(key) {
  selectedAction.value = key
  if (!ACTION_META[key]?.needYKien) yKien.value = ''
  if (!ACTION_META[key]?.needLyDo) lyDo.value = ''
}

function fillFromHopDong(row) {
  hopDongData.value = row || null
  selectedAction.value = ''
  lyDo.value = ''
  yKien.value = ''
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

async function confirm() {
  if (!canConfirm.value || !selectedActionMeta.value) return

  const action = selectedAction.value
  const meta = selectedActionMeta.value

  try {
    await ElMessageBox.confirm(
      `Xác nhận "${meta.label}" cho hợp đồng ${hopDongData.value?.ma_hop_dong || ''}?`,
      meta.label,
      {
        type: meta.buttonType === 'danger' ? 'warning' : 'info',
        confirmButtonText: meta.confirmText,
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  saving.value = true
  try {
    const payload = { hanh_dong: action }
    if (meta.needYKien) payload.y_kien_khach_hang = String(yKien.value || '').trim()
    if (meta.needLyDo && String(lyDo.value || '').trim()) {
      payload.ly_do = String(lyDo.value || '').trim()
    }

    const { data } = await doiTrangThaiHopDongSuDungDichVu(hopDongData.value.id, payload)
    ElMessage.success(`Đã ${meta.label.toLowerCase()}.`)
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
  selectedAction.value = ''
  lyDo.value = ''
  yKien.value = ''
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
.doi-trang-thai-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: 180px;
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
  color: var(--el-text-color-primary);
}

.modal-header__meta {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.info-section,
.actions-section,
.note-section {
  padding: 14px 16px 4px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
  background: var(--el-fill-color-blank);
}

.section-title {
  margin-bottom: 12px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.action-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
  padding-bottom: 12px;
}

.action-card {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  padding: 12px 14px;
  border: 1.5px solid var(--el-border-color-lighter);
  border-radius: 10px;
  background: var(--el-bg-color);
  text-align: left;
  cursor: pointer;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;

  &:hover {
    border-color: var(--el-color-primary-light-5);
  }

  &.is-selected {
    box-shadow: 0 0 0 1px var(--el-color-primary-light-7);
  }

  &.is-success.is-selected {
    border-color: var(--el-color-success);
    background: var(--el-color-success-light-9);
  }

  &.is-danger.is-selected {
    border-color: var(--el-color-danger);
    background: var(--el-color-danger-light-9);
  }

  &.is-warning.is-selected {
    border-color: var(--el-color-warning);
    background: var(--el-color-warning-light-9);
  }
}

.action-card__title {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.action-card__desc {
  font-size: 12px;
  line-height: 1.4;
  color: var(--el-text-color-secondary);
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

@media (max-width: 992px) {
  .action-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .action-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
