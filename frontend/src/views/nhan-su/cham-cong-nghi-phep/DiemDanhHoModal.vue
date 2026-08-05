<template>
  <CustomDialog
    v-model="visible"
    title="Điểm danh hộ"
    :width="960"
    @closed="resetForm"
  >
    <div v-loading="loading" class="proxy-modal">
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <div class="proxy-section-title">Thông tin nhân viên</div>
        <CustomRow :gutter="16">
          <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
            <CustomFormItem label="Họ tên">
              <CustomInput :model-value="info.name || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
            <CustomFormItem label="Email">
              <CustomInput :model-value="info.email || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
            <CustomFormItem label="Phòng ban">
              <CustomInput :model-value="info.phongBan || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
            <CustomFormItem label="Số điện thoại">
              <CustomInput :model-value="info.phone || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
            <CustomFormItem label="Ngày làm">
              <CustomInput :model-value="formatDisplayDate(form.ngay_lam)" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
            <CustomFormItem label="Ca làm việc">
              <CustomInput :model-value="info.caLamLabel || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
            <CustomFormItem label="Thời gian vào làm (ca)">
              <CustomInput :model-value="info.gioBatDauCa || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
            <CustomFormItem label="Thời gian kết thúc ca">
              <CustomInput :model-value="info.gioKetThucCa || '—'" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
            <CustomFormItem label="Giờ vào (checkin)" prop="gio_vao">
              <el-time-picker
                v-model="form.gio_vao"
                value-format="HH:mm"
                format="HH:mm"
                placeholder="Chọn giờ vào"
                style="width: 100%"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
            <CustomFormItem label="Giờ ra (checkout)" prop="gio_ra">
              <el-time-picker
                v-model="form.gio_ra"
                value-format="HH:mm"
                format="HH:mm"
                placeholder="Chọn giờ ra"
                style="width: 100%"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="24" :md="16" :lg="12">
            <CustomFormItem label="Xin nghỉ phép (đi muộn / về sớm)">
              <div class="leave-flags">
                <div
                  class="leave-flag"
                  :class="leaveInfo.diMuon ? 'is-yes' : 'is-no'"
                >
                  <CustomIcon class="leave-flag__mark">
                    <CircleCheckFilled v-if="leaveInfo.diMuon" />
                    <CircleCloseFilled v-else />
                  </CustomIcon>
                  <span class="leave-flag__label">Xin đi muộn</span>
                  <CustomTooltip
                    v-if="leaveInfo.diMuon"
                    :content="leaveStatusTooltip(leaveInfo.diMuon)"
                    placement="top"
                  >
                    <CustomIcon
                      class="leave-flag__status-icon"
                      :class="leaveStatusIconClass(leaveInfo.diMuon.trang_thai)"
                    >
                      <component :is="leaveStatusIcon(leaveInfo.diMuon.trang_thai)" />
                    </CustomIcon>
                  </CustomTooltip>
                  <span
                    v-if="leaveInfo.diMuon"
                    class="leave-flag__status"
                    :class="leaveStatusTextClass(leaveInfo.diMuon.trang_thai)"
                  >
                    {{ leaveStatusLabel(leaveInfo.diMuon.trang_thai) }}
                  </span>
                </div>
                <div
                  class="leave-flag"
                  :class="leaveInfo.veSom ? 'is-yes' : 'is-no'"
                >
                  <CustomIcon class="leave-flag__mark">
                    <CircleCheckFilled v-if="leaveInfo.veSom" />
                    <CircleCloseFilled v-else />
                  </CustomIcon>
                  <span class="leave-flag__label">Xin về sớm</span>
                  <CustomTooltip
                    v-if="leaveInfo.veSom"
                    :content="leaveStatusTooltip(leaveInfo.veSom)"
                    placement="top"
                  >
                    <CustomIcon
                      class="leave-flag__status-icon"
                      :class="leaveStatusIconClass(leaveInfo.veSom.trang_thai)"
                    >
                      <component :is="leaveStatusIcon(leaveInfo.veSom.trang_thai)" />
                    </CustomIcon>
                  </CustomTooltip>
                  <span
                    v-if="leaveInfo.veSom"
                    class="leave-flag__status"
                    :class="leaveStatusTextClass(leaveInfo.veSom.trang_thai)"
                  >
                    {{ leaveStatusLabel(leaveInfo.veSom.trang_thai) }}
                  </span>
                </div>
              </div>
            </CustomFormItem>
          </CustomCol>
        </CustomRow>

        <div v-if="hasShiftTimes" class="proxy-penalty">
          <div class="proxy-section-title">Ước tính tiền phạt</div>
          <div class="proxy-penalty__grid">
            <div
              class="proxy-penalty__item"
              :class="{ 'is-active': penaltyPreview.diMuonPhut > 0 && !penaltyPreview.waiveLate }"
            >
              <div class="proxy-penalty__label">Đi muộn</div>
              <div class="proxy-penalty__value">
                <template v-if="penaltyPreview.waiveLate && penaltyPreview.diMuonPhut > 0">
                  {{ penaltyPreview.diMuonPhut }} phút
                  <span class="proxy-penalty__sep">·</span>
                  <span class="proxy-penalty__waived">Miễn phạt (đã duyệt)</span>
                </template>
                <template v-else-if="penaltyPreview.diMuonPhut > 0">
                  {{ penaltyPreview.diMuonPhut }} phút
                  <span class="proxy-penalty__sep">·</span>
                  {{ formatMoneyFull(penaltyPreview.tienPhatDiMuon) }}
                </template>
                <template v-else>Không phạt</template>
              </div>
            </div>
            <div
              class="proxy-penalty__item"
              :class="{ 'is-active': penaltyPreview.veSomPhut > 0 && !penaltyPreview.waiveEarly }"
            >
              <div class="proxy-penalty__label">Về sớm</div>
              <div class="proxy-penalty__value">
                <template v-if="penaltyPreview.waiveEarly && penaltyPreview.veSomPhut > 0">
                  {{ penaltyPreview.veSomPhut }} phút
                  <span class="proxy-penalty__sep">·</span>
                  <span class="proxy-penalty__waived">Miễn phạt (đã duyệt)</span>
                </template>
                <template v-else-if="penaltyPreview.veSomPhut > 0">
                  {{ penaltyPreview.veSomPhut }} phút
                  <span class="proxy-penalty__sep">·</span>
                  {{ formatMoneyFull(penaltyPreview.tienPhatVeSom) }}
                </template>
                <template v-else>Không phạt</template>
              </div>
            </div>
            <div
              class="proxy-penalty__item proxy-penalty__item--total"
              :class="{ 'is-active': penaltyPreview.tongPhat > 0 }"
            >
              <div class="proxy-penalty__label">Tổng phạt</div>
              <div class="proxy-penalty__value">
                {{
                  penaltyPreview.tongPhat > 0
                    ? formatMoneyFull(penaltyPreview.tongPhat)
                    : '0 ₫'
                }}
              </div>
            </div>
          </div>
          <p class="proxy-penalty__hint">
            Tính theo cấu hình phạt đi muộn / về sớm (1–30 phút hoặc &gt; 30 phút).
            Đơn xin đi muộn / về sớm đã duyệt sẽ được miễn phạt.
          </p>
        </div>
      </CustomForm>
    </div>

    <template #footer>
      <CustomButton @click="visible = false">Hủy</CustomButton>
      <CustomButton type="primary" :loading="saving" @click="submit">
        Lưu
      </CustomButton>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { CircleCheck, CircleCheckFilled, CircleCloseFilled, Clock } from '@element-plus/icons-vue'
import { getCauHinhJson } from '@/api/cauHinhJson'
import { diemDanhHo, getDiemDanhHoContext } from '@/api/diemDanh'
import {
  CustomButton,
  CustomCol,
  CustomDialog,
  CustomForm,
  CustomFormItem,
  CustomIcon,
  CustomInput,
  CustomRow,
  CustomTooltip,
} from '@/components/element'

const DEFAULT_PENALTY_CONFIG = {
  tien_phat_di_muon_1_30: 50000,
  tien_phat_di_muon_tren_30: 100000,
  tien_phat_ve_som_1_30: 30000,
  tien_phat_ve_som_tren_30: 50000,
}

const emit = defineEmits(['saved'])

const visible = ref(false)
const loading = ref(false)
const saving = ref(false)
const formRef = ref(null)

const info = reactive({
  name: '',
  email: '',
  phone: '',
  phongBan: '',
  caLamLabel: '',
  gioBatDauCa: '',
  gioKetThucCa: '',
})

const leaveInfo = reactive({
  diMuon: null,
  veSom: null,
})

const form = reactive({
  user_id: null,
  ngay_lam: '',
  gio_vao: '',
  gio_ra: '',
})

const rules = {
  gio_vao: [{ required: true, message: 'Vui lòng chọn giờ vào', trigger: 'change' }],
  gio_ra: [{ required: true, message: 'Vui lòng chọn giờ ra', trigger: 'change' }],
}

const penaltyConfig = reactive({ ...DEFAULT_PENALTY_CONFIG })

const hasShiftTimes = computed(
  () => Boolean(info.gioBatDauCa && info.gioKetThucCa),
)

const penaltyPreview = computed(() => {
  if (!hasShiftTimes.value || !form.gio_vao || !form.gio_ra) {
    return {
      diMuonPhut: 0,
      veSomPhut: 0,
      tienPhatDiMuon: 0,
      tienPhatVeSom: 0,
      tongPhat: 0,
      waiveLate: false,
      waiveEarly: false,
    }
  }

  const diMuonPhut = Math.max(0, minutesBetween(info.gioBatDauCa, form.gio_vao))
  const veSomPhut = Math.max(0, minutesBetween(form.gio_ra, info.gioKetThucCa))
  const waiveLate = leaveInfo.diMuon?.trang_thai === 'da_duyet'
  const waiveEarly = leaveInfo.veSom?.trang_thai === 'da_duyet'
  const tienPhatDiMuon = waiveLate ? 0 : tinhTienPhat('di_muon', diMuonPhut)
  const tienPhatVeSom = waiveEarly ? 0 : tinhTienPhat('ve_som', veSomPhut)

  return {
    diMuonPhut,
    veSomPhut,
    tienPhatDiMuon,
    tienPhatVeSom,
    tongPhat: tienPhatDiMuon + tienPhatVeSom,
    waiveLate,
    waiveEarly,
  }
})

function toDateKey(value) {
  return String(value || '').slice(0, 10)
}

function formatDisplayDate(value) {
  const key = toDateKey(value)
  if (!key) return '—'
  const [y, m, d] = key.split('-')
  return `${d}/${m}/${y}`
}

function formatTime(value) {
  if (!value) return ''
  const str = String(value)
  if (str.includes('T')) return str.slice(11, 16)
  if (str.includes(' ')) return str.slice(11, 16)
  return str.slice(0, 5)
}

function formatShiftTime(value) {
  if (!value) return ''
  return formatTime(value)
}

function phongBanLabel(user) {
  const list = user?.nhan_vien?.phong_bans || user?.nhan_vien?.phongBans || []
  if (!Array.isArray(list) || list.length === 0) return ''
  return list.map((pb) => pb.ten_phong_ban || pb.name).filter(Boolean).join(', ')
}

function parseHHMM(value) {
  const match = String(value || '').match(/^(\d{1,2}):(\d{2})/)
  if (!match) return null
  const hours = Number(match[1])
  const minutes = Number(match[2])
  if (!Number.isFinite(hours) || !Number.isFinite(minutes)) return null
  return hours * 60 + minutes
}

/** Số phút từ `from` đến `to` (có dấu). */
function minutesBetween(from, to) {
  const fromMin = parseHHMM(from)
  const toMin = parseHHMM(to)
  if (fromMin == null || toMin == null) return 0
  return toMin - fromMin
}

function configAmount(key) {
  const num = Number(penaltyConfig[key])
  return Number.isFinite(num) ? num : DEFAULT_PENALTY_CONFIG[key]
}

function tinhTienPhat(loai, phut) {
  if (phut <= 0) return 0
  const isOver30 = phut > 30
  if (loai === 've_som') {
    return configAmount(isOver30 ? 'tien_phat_ve_som_tren_30' : 'tien_phat_ve_som_1_30')
  }
  return configAmount(isOver30 ? 'tien_phat_di_muon_tren_30' : 'tien_phat_di_muon_1_30')
}

function formatMoneyFull(value) {
  const num = Number(value)
  if (!Number.isFinite(num) || num === 0) return '0 ₫'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function leaveStatusLabel(trangThai) {
  if (trangThai === 'da_duyet') return 'Đã duyệt'
  if (trangThai === 'cho_duyet') return 'Chờ duyệt'
  return trangThai || '—'
}

function leaveStatusIcon(trangThai) {
  return trangThai === 'da_duyet' ? CircleCheck : Clock
}

function leaveStatusIconClass(trangThai) {
  return trangThai === 'da_duyet'
    ? 'leave-flag__status-icon--approved'
    : 'leave-flag__status-icon--pending'
}

function leaveStatusTextClass(trangThai) {
  return trangThai === 'da_duyet'
    ? 'leave-flag__status--approved'
    : 'leave-flag__status--pending'
}

function leaveStatusTooltip(leave) {
  const status = leaveStatusLabel(leave?.trang_thai)
  const lyDo = leave?.ly_do?.trim()
  return lyDo ? `${status} · ${lyDo}` : status
}

function applyPenaltyConfig(group = {}) {
  for (const key of Object.keys(DEFAULT_PENALTY_CONFIG)) {
    const raw = group[key]?.gia_tri
    const num = Number(raw)
    penaltyConfig[key] = Number.isFinite(num) ? num : DEFAULT_PENALTY_CONFIG[key]
  }
}

async function loadPenaltyConfig() {
  try {
    const { data } = await getCauHinhJson({ skipLoading: true })
    applyPenaltyConfig(data?.thong_tin_cau_hinh?.cham_cong_tang_ca || {})
  } catch {
    applyPenaltyConfig({})
  }
}

function resetForm() {
  form.user_id = null
  form.ngay_lam = ''
  form.gio_vao = ''
  form.gio_ra = ''
  info.name = ''
  info.email = ''
  info.phone = ''
  info.phongBan = ''
  info.caLamLabel = ''
  info.gioBatDauCa = ''
  info.gioKetThucCa = ''
  leaveInfo.diMuon = null
  leaveInfo.veSom = null
  formRef.value?.clearValidate?.()
}

/**
 * @param {{
 *   userId: number|string,
 *   ngayLam: string,
 *   name?: string,
 *   email?: string,
 * }} payload
 */
async function open(payload) {
  resetForm()
  form.user_id = payload.userId
  form.ngay_lam = payload.ngayLam
  info.name = payload.name || ''
  info.email = payload.email || ''
  visible.value = true
  loading.value = true

  try {
    const [contextRes] = await Promise.all([
      getDiemDanhHoContext(
        { user_id: payload.userId, ngay_lam: payload.ngayLam },
        { skipLoading: true },
      ),
      loadPenaltyConfig(),
    ])

    const data = contextRes.data

    if (data.can_proxy === false) {
      ElMessage.warning('Nhân viên đã điểm danh ngày này.')
      visible.value = false
      return
    }

    const user = data.user || {}
    const caLam = data.dang_ky_ca?.ca_lam || data.dang_ky_ca?.caLam || null
    const leaves = data.xin_nghi_phep || {}

    info.name = user.name || payload.name || '—'
    info.email = user.email || payload.email || '—'
    info.phone = user.phone || '—'
    info.phongBan = phongBanLabel(user) || '—'
    info.caLamLabel = caLam?.ten_ca || 'Chưa đăng ký ca'
    info.gioBatDauCa = formatShiftTime(caLam?.gio_bat_dau) || ''
    info.gioKetThucCa = formatShiftTime(caLam?.gio_ket_thuc) || ''
    leaveInfo.diMuon = leaves.di_muon || null
    leaveInfo.veSom = leaves.ve_som || null

    form.gio_vao = formatShiftTime(caLam?.gio_bat_dau) || ''
    form.gio_ra = formatShiftTime(caLam?.gio_ket_thuc) || ''
  } catch {
    ElMessage.error('Không tải được thông tin điểm danh hộ.')
    visible.value = false
  } finally {
    loading.value = false
  }
}

async function submit() {
  const formEl = formRef.value
  if (!formEl) return

  try {
    await formEl.validate()
  } catch {
    return
  }

  saving.value = true
  try {
    await diemDanhHo({
      user_id: form.user_id,
      ngay_lam: form.ngay_lam,
      gio_vao: form.gio_vao,
      gio_ra: form.gio_ra,
    })
    ElMessage.success('Điểm danh hộ thành công')
    visible.value = false
    emit('saved')
  } catch {
    // Lỗi 422 đã được axios interceptor hiển thị
  } finally {
    saving.value = false
  }
}

defineExpose({ open })
</script>

<style scoped>
.proxy-modal {
  min-height: 120px;
}

.proxy-section-title {
  margin: 0 0 12px;
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.leave-flags {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 20px;
  min-height: 32px;
}

.leave-flag {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 0 12px;
  border-radius: 4px;
  border: 1px solid var(--el-border-color-lighter);
  background: var(--el-fill-color-lighter);
}

.leave-flag.is-yes {
  border-color: color-mix(in srgb, var(--el-color-primary) 35%, var(--el-border-color-lighter));
  background: color-mix(in srgb, var(--el-color-primary) 8%, var(--el-bg-color));
}

.leave-flag.is-no {
  border-color: var(--el-border-color-lighter);
  background: var(--el-fill-color-blank, var(--el-bg-color));
}

.leave-flag__mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.leave-flag.is-yes .leave-flag__mark {
  color: var(--el-color-primary);
}

.leave-flag.is-no .leave-flag__mark {
  color: var(--el-text-color-secondary);
}

.leave-flag__label {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.leave-flag__status-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  cursor: default;
}

.leave-flag__status-icon--approved {
  color: var(--el-color-success);
}

.leave-flag__status-icon--pending {
  color: var(--el-color-warning);
}

.leave-flag__status {
  font-size: 12px;
  font-weight: 600;
}

.leave-flag__status--approved {
  color: var(--el-color-success);
}

.leave-flag__status--pending {
  color: var(--el-color-warning-dark-2, #b88230);
}

.proxy-penalty {
  margin-top: 4px;
  padding-top: 8px;
  border-top: 1px solid var(--el-border-color-lighter);
}

.proxy-penalty__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.proxy-penalty__item {
  padding: 12px 14px;
  border-radius: 8px;
  border: 1px solid var(--el-border-color-lighter);
  background: var(--el-fill-color-lighter);
}

.proxy-penalty__item.is-active {
  border-color: color-mix(in srgb, var(--el-color-danger) 35%, var(--el-border-color-lighter));
  background: color-mix(in srgb, var(--el-color-danger) 8%, var(--el-bg-color));
}

.proxy-penalty__item--total.is-active .proxy-penalty__value {
  color: var(--el-color-danger);
  font-weight: 700;
}

.proxy-penalty__label {
  margin-bottom: 4px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.proxy-penalty__value {
  font-size: 14px;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  color: var(--el-text-color-primary);
}

.proxy-penalty__sep {
  margin: 0 4px;
  color: var(--el-text-color-placeholder);
  font-weight: 400;
}

.proxy-penalty__waived {
  color: var(--el-color-success);
  font-weight: 600;
}

.proxy-penalty__hint {
  margin: 10px 0 0;
  font-size: 12px;
  line-height: 1.45;
  color: var(--el-text-color-secondary);
}

@media (max-width: 767px) {
  .proxy-penalty__grid {
    grid-template-columns: 1fr;
  }
}
</style>
