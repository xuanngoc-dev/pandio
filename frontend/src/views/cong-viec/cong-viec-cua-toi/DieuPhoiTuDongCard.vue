<template>
  <CustomCard shadow="hover" class="cong-viec-card">
    <div class="cong-viec-card__header">
      <h3 class="cong-viec-card__title" :title="loaiHopDong">
        {{ loaiHopDong }}
      </h3>
    </div>

    <div class="cong-viec-card__body">
      <div class="cong-viec-card__customer" :title="customerLine">
        <span class="value">{{ khachHang }}</span>
        <span class="code">- [{{ maHopDong }}]</span>
      </div>

      <div v-if="thoiGianChupItems.length" class="cong-viec-card__row">
        <span class="label">
          Thời gian chụp
          <CustomTooltip
            v-if="trangThaiChup"
            :content="trangThaiChup.tooltip"
            placement="top"
          >
            <CustomIcon
              class="deadline-status-icon"
              :class="trangThaiChup.late ? 'is-late' : 'is-ok'"
            >
              <WarningFilled v-if="trangThaiChup.late" />
              <CircleCheckFilled v-else />
            </CustomIcon>
          </CustomTooltip>
        </span>
        <span class="value cong-viec-card__times">
          <template v-for="(item, index) in thoiGianChupItems" :key="index">
            <span v-if="index">; </span>
            <CustomTooltip
              v-if="item.loaiLabel"
              :content="item.loaiLabel"
              placement="top"
            >
              <span class="cong-viec-card__time">{{ item.text }}</span>
            </CustomTooltip>
            <span v-else class="cong-viec-card__time">{{ item.text }}</span>
          </template>
        </span>
      </div>
      <div v-if="ngayTraDemo" class="cong-viec-card__row">
        <span class="label">
          Ngày trả demo
          <CustomTooltip
            v-if="trangThaiGiaoDemo"
            :content="trangThaiGiaoDemo.tooltip"
            placement="top"
          >
            <CustomIcon
              class="deadline-status-icon"
              :class="trangThaiGiaoDemo.late ? 'is-late' : 'is-ok'"
            >
              <WarningFilled v-if="trangThaiGiaoDemo.late" />
              <CircleCheckFilled v-else />
            </CustomIcon>
          </CustomTooltip>
        </span>
        <span class="value">{{ ngayTraDemo }}</span>
      </div>
      <div v-if="ngayTraChinhThuc" class="cong-viec-card__row">
        <span class="label">
          Ngày trả chính thức
          <CustomTooltip
            v-if="trangThaiBanGiao"
            :content="trangThaiBanGiao.tooltip"
            placement="top"
          >
            <CustomIcon
              class="deadline-status-icon"
              :class="trangThaiBanGiao.late ? 'is-late' : 'is-ok'"
            >
              <WarningFilled v-if="trangThaiBanGiao.late" />
              <CircleCheckFilled v-else />
            </CustomIcon>
          </CustomTooltip>
        </span>
        <span class="value">{{ ngayTraChinhThuc }}</span>
      </div>

      <div v-if="step !== 'cho_nhan'" class="cong-viec-card__files">
        <div
          v-for="field in fileLinkFields"
          :key="field.key"
          class="cong-viec-card__file"
        >
          <span class="label">{{ field.label }}</span>
          <div class="cong-viec-card__file-actions">
            <template v-if="field.url">
              <a
                class="link-open"
                :href="normalizeUrl(field.url)"
                target="_blank"
                rel="noopener noreferrer"
                :title="field.url"
                @click.stop
              >
                Mở
              </a>
              <CustomTooltip
                v-if="canEditFile(field)"
                content="Sửa link"
                placement="top"
              >
                <CustomButton
                  type="primary"
                  circle
                  size="small"
                  :icon="Edit"
                  @click.stop="openLinkModal(field)"
                />
              </CustomTooltip>
            </template>
            <template v-else-if="canEditFile(field)">
              <CustomTooltip content="Thêm link" placement="top">
                <CustomButton
                  type="primary"
                  circle
                  size="small"
                  :icon="Plus"
                  @click.stop="openLinkModal(field)"
                />
              </CustomTooltip>
            </template>
            <span v-else class="file-empty">—</span>
          </div>
        </div>
      </div>

      <div v-if="vaiTroLabels.length" class="cong-viec-card__roles">
        <span class="label">Vai trò:</span>
        <div class="cong-viec-card__roles-tags">
          <CustomTag
            v-for="role in vaiTroLabels"
            :key="role"
            type="info"
            size="small"
            effect="plain"
          >
            {{ role }}
          </CustomTag>
        </div>
      </div>

    </div>

    <div class="cong-viec-card__footer">
      <div
        v-if="showStatusManagedElsewhere"
        class="cong-viec-card__footer-hint"
      >
        <span class="status-hint">
          Trạng thái bước này được cập nhật tại Hợp đồng SDĐV → Thay đổi trạng thái.
        </span>
      </div>
      <div class="cong-viec-card__footer-actions">
        <CustomTooltip content="Xem chi tiết hợp đồng" placement="top">
          <CustomButton
            type="info"
            circle
            size="small"
            :icon="View"
            @click.stop="openDetail"
          />
        </CustomTooltip>
        <CustomTooltip
          v-if="showYKienIcon"
          content="Xem ý kiến khách hàng"
          placement="top"
        >
          <CustomButton
            type="warning"
            circle
            size="small"
            :icon="ChatDotRound"
            @click.stop="openYKienView"
          />
        </CustomTooltip>
        <CustomTooltip v-if="canNhan" content="Nhận việc" placement="top">
          <CustomButton
            type="primary"
            circle
            size="small"
            :icon="Select"
            :loading="accepting"
            @click.stop="onNhan"
          />
        </CustomTooltip>
        <CustomTooltip
          v-if="showGuiKhachFooter"
          :content="
            canGuiKhachKiemTra
              ? 'Gửi khách kiểm tra'
              : 'Cần có đủ File gốc và File lẻ'
          "
          placement="top"
        >
          <span class="cong-viec-card__footer-btn-wrap">
            <CustomButton
              type="primary"
              circle
              size="small"
              :icon="Promotion"
              :disabled="!canGuiKhachKiemTra"
              :loading="sendingKhach"
              @click.stop="onGuiKhachKiemTra"
            />
          </span>
        </CustomTooltip>
        <CustomTooltip
          v-if="showBanGiaoFooter"
          :content="
            canBanGiao
              ? 'Bàn giao sản phẩm'
              : 'Cần có File in trước khi bàn giao'
          "
          placement="top"
        >
          <span class="cong-viec-card__footer-btn-wrap">
            <CustomButton
              type="success"
              circle
              size="small"
              :icon="Finished"
              :disabled="!canBanGiao"
              :loading="banningGiao"
              @click.stop="onBanGiao"
            />
          </span>
        </CustomTooltip>
      </div>
    </div>

    <CustomDialog
      v-model="linkModalVisible"
      :title="linkModalTitle"
      :width="460"
    >
      <el-form label-position="top" @submit.prevent="saveLink">
        <el-form-item :label="linkModalField?.label || 'Link'" required>
          <el-input
            v-model="linkInput"
            placeholder="https://..."
            clearable
            @keyup.enter="saveLink"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <CustomButton @click="linkModalVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="savingLink" @click="saveLink">
          Lưu
        </CustomButton>
      </template>
    </CustomDialog>

    <CustomDialog
      v-model="yKienViewVisible"
      title="Ý kiến khách hàng"
      :width="520"
    >
      <div class="y-kien-view">
        <p class="y-kien-view__meta">
          Hợp đồng <strong>{{ maHopDong }}</strong>
        </p>
        <div class="y-kien-view__content">
          {{ yKienKhachHang || '—' }}
        </div>
      </div>
      <template #footer>
        <CustomButton type="primary" @click="yKienViewVisible = false">
          Đóng
        </CustomButton>
      </template>
    </CustomDialog>

    <DieuPhoiTuDongDetailModal
      v-model="detailModalVisible"
      :hop-dong-id="item.id"
    />
  </CustomCard>
</template>

<script setup>
import { computed, ref } from 'vue'
import { ChatDotRound, CircleCheckFilled, Edit, Finished, Plus, Promotion, Select, View, WarningFilled } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  banGiaoCongViec,
  capNhatKetQuaHopDong,
  guiKhachKiemTra,
  nhanCongViecDieuPhoi,
} from '@/api/hopDongSuDungDichVu'
import {
  CustomButton,
  CustomCard,
  CustomDialog,
  CustomIcon,
  CustomTag,
  CustomTooltip,
} from '@/components/element'
import { useAuthStore } from '@/stores/auth'
import {
  collectDieuPhoiGiaTri,
  formatLoaiQuayChupLabel,
  getDieuPhoiGiaTriFromSession,
  normalizeDieuPhoiSessions,
  parseSessionLoaiQuayChup,
  resolveTrangThaiDieuPhoi,
  TRANG_THAI_DIEU_PHOI_CHO_NHAN,
} from '@/utils/thongTinDieuPhoi'
import DieuPhoiTuDongDetailModal from './DieuPhoiTuDongDetailModal.vue'

const STAFF_ROLE_LABELS = {
  tho_chup: 'Thợ chụp',
  tho_make: 'Thợ make',
  tho_edit: 'Thợ edit',
  quay_phim: 'Quay phim',
}

const TRANG_THAI_OPTIONS = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
  { value: 'da_coc', label: 'Đã cọc' },
  { value: 'dang_thuc_hien', label: 'Đang thực hiện' },
  { value: 'da_huy', label: 'Đã hủy' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
]

const FILE_LINK_DEFS = [
  { key: 'link_file_goc', label: 'File gốc' },
  { key: 'link_file_demo', label: 'File lẻ' },
  { key: 'link_giao_san_pham', label: 'File in' },
]

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  step: {
    type: String,
    default: 'cho_nhan',
  },
})

const emit = defineEmits(['accepted', 'updated', 'status-changed'])

const authStore = useAuthStore()
const accepting = ref(false)
const sendingKhach = ref(false)
const banningGiao = ref(false)
const linkModalVisible = ref(false)
const linkModalField = ref(null)
const linkInput = ref('')
const savingLink = ref(false)
const yKienViewVisible = ref(false)
const detailModalVisible = ref(false)

function openDetail() {
  if (!props.item?.id) return
  detailModalVisible.value = true
}

const ketQua = computed(() => {
  const raw = props.item?.ket_qua_hop_dong
  return raw && typeof raw === 'object' && !Array.isArray(raw) ? raw : {}
})

const ketQuaTrangThai = computed(() => resolveTrangThaiDieuPhoi(props.item) || null)

const ketQuaTrangThaiTag = computed(() => {
  const map = {
    cho_nghiem_thu: { label: 'Chờ nghiệm thu', type: 'warning' },
    hoan_thanh: { label: 'Hoàn thành', type: 'success' },
  }
  if (!ketQuaTrangThai.value) return null
  return map[ketQuaTrangThai.value] || null
})

const canNhan = computed(
  () =>
    props.step === 'cho_nhan' &&
    (!ketQuaTrangThai.value || ketQuaTrangThai.value === TRANG_THAI_DIEU_PHOI_CHO_NHAN),
)
const showGuiKhachFooter = computed(() => props.step === 'dang_xu_ly')
const showBanGiaoFooter = computed(() => props.step === 'san_xuat_in_an')
/** Gửi khách kiểm tra / nghiệm thu: đổi trạng thái tại HopDongSddv, không tự đổi ở đây */
const showStatusManagedElsewhere = computed(
  () => props.step === 'gui_khach_kiem_tra' || props.step === 'cho_nghiem_thu',
)

const hasLinkFileGoc = computed(() => {
  const giaTri = ketQua.value?.link_file_goc?.gia_tri
  return giaTri != null && String(giaTri).trim() !== ''
})

const hasLinkFileDemo = computed(() => {
  const giaTri = ketQua.value?.link_file_demo?.gia_tri
  return giaTri != null && String(giaTri).trim() !== ''
})

const hasLinkFileChinhThuc = computed(() => {
  const giaTri = ketQua.value?.link_giao_san_pham?.gia_tri
  return giaTri != null && String(giaTri).trim() !== ''
})

const canGuiKhachKiemTra = computed(
  () => showGuiKhachFooter.value && hasLinkFileGoc.value && hasLinkFileDemo.value,
)

const canBanGiao = computed(
  () => showBanGiaoFooter.value && hasLinkFileChinhThuc.value,
)

const yKienKhachHang = computed(() => {
  const giaTri = ketQua.value?.y_kien_khach_hang?.gia_tri
  if (giaTri == null || String(giaTri).trim() === '') return ''
  return String(giaTri).trim()
})

const showYKienIcon = computed(
  () =>
    !!yKienKhachHang.value &&
    (props.step === 'dang_xu_ly' || props.step === 'san_xuat_in_an'),
)

function canEditFile(field) {
  if (props.step === 'dang_xu_ly') return true
  if (props.step === 'san_xuat_in_an') {
    return field?.key === 'link_giao_san_pham'
  }
  return false
}

const fileLinkFields = computed(() =>
  FILE_LINK_DEFS.map((def) => {
    const giaTri = ketQua.value?.[def.key]?.gia_tri
    const url =
      giaTri != null && String(giaTri).trim() !== '' ? String(giaTri).trim() : null
    return { ...def, url }
  }),
)

const linkModalTitle = computed(() => {
  if (!linkModalField.value) return 'Thêm link'
  const label = linkModalField.value.label.toLowerCase()
  return linkModalField.value.url ? `Sửa ${label}` : `Thêm ${label}`
})

const khachHang = computed(() => formatKhachHang(props.item))
const maHopDong = computed(() => props.item?.ma_hop_dong || '—')
const loaiHopDong = computed(
  () => props.item?.loai_hop_dong?.ten_hop_dong || '—',
)
const customerLine = computed(
  () => `Khách hàng: ${khachHang.value} - [${maHopDong.value}]`,
)

const dieuPhoiSessions = computed(() =>
  normalizeDieuPhoiSessions(props.item?.thong_tin_dieu_phoi),
)

const thoiGianChupItems = computed(() =>
  dieuPhoiSessions.value
    .map((session) => {
      const gio = formatTime(getDieuPhoiGiaTriFromSession(session, 'gio_chup'))
      const buoi = formatBuoi(getDieuPhoiGiaTriFromSession(session, 'buoi_chup'))
      const ngay = formatDate(getDieuPhoiGiaTriFromSession(session, 'ngay_chup'))
      const text = [gio, buoi, ngay].filter(Boolean).join(' ')
      if (!text) return null
      return {
        text,
        loaiLabel: formatLoaiQuayChupLabel(parseSessionLoaiQuayChup(session)),
      }
    })
    .filter(Boolean),
)

const ngayTraDemo = computed(() =>
  collectDieuPhoiGiaTri(props.item?.thong_tin_dieu_phoi, 'ngay_tra_demo')
    .map((item) => formatDate(item))
    .filter(Boolean)
    .join(', '),
)
const ngayTraChinhThuc = computed(() =>
  collectDieuPhoiGiaTri(props.item?.thong_tin_dieu_phoi, 'ngay_tra_chinh_thuc')
    .map((item) => formatDate(item))
    .filter(Boolean)
    .join(', '),
)

const trangThaiChup = computed(() =>
  buildDeadlineStatus({
    dateValue: earliestDate(
      collectDieuPhoiGiaTri(props.item?.thong_tin_dieu_phoi, 'ngay_chup'),
    ),
    hasFile: hasLinkFileGoc.value,
    lateLabel: 'Trễ chụp',
    okLabel: 'Đúng hạn chụp',
  }),
)

const trangThaiGiaoDemo = computed(() =>
  buildDeadlineStatus({
    dateValue: earliestDate(
      collectDieuPhoiGiaTri(props.item?.thong_tin_dieu_phoi, 'ngay_tra_demo'),
    ),
    hasFile: hasLinkFileDemo.value,
    lateLabel: 'Trễ giao demo',
    okLabel: 'Đúng hạn giao demo',
  }),
)

const trangThaiBanGiao = computed(() =>
  buildDeadlineStatus({
    dateValue: earliestDate(
      collectDieuPhoiGiaTri(props.item?.thong_tin_dieu_phoi, 'ngay_tra_chinh_thuc'),
    ),
    hasFile: hasLinkFileChinhThuc.value,
    lateLabel: 'Trễ bàn giao',
    okLabel: 'Đúng hạn bàn giao',
  }),
)

const vaiTroLabels = computed(() => {
  const userId = authStore.user?.id
  if (userId == null) return []

  const roles = []
  for (const [key, label] of Object.entries(STAFF_ROLE_LABELS)) {
    const list = collectDieuPhoiGiaTri(props.item?.thong_tin_dieu_phoi, key)
    const matched = list.some((id) => Number(id) === Number(userId))
    if (matched) roles.push(label)
  }
  return roles
})

function getThongTin(row) {
  const info = row?.thong_tin_hop_dong
  return info && typeof info === 'object' && !Array.isArray(info) ? info : {}
}

function formatKhachHang(row) {
  if (row?.ten_khach_hang) return row.ten_khach_hang
  const info = getThongTin(row)
  const tenChuRe = info.tenChuRe || info.ten_chu_re
  const tenCoDau = info.tenCoDau || info.ten_co_dau
  if (tenChuRe || tenCoDau) {
    return [tenChuRe, tenCoDau].filter(Boolean).join(' & ')
  }
  return info.hoTenKhachHang || info.ho_ten_khach_hang || info.hoTenKhach || '—'
}

function trangThaiLabel(value) {
  return TRANG_THAI_OPTIONS.find((opt) => opt.value === value)?.label || value || '—'
}

function trangThaiTagType(value) {
  const map = {
    moi_tao: 'info',
    nhap: '',
    da_coc: 'warning',
    dang_thuc_hien: 'primary',
    da_huy: 'danger',
    hoan_thanh: 'success',
  }
  return map[value] || 'info'
}

function formatDate(value) {
  if (value == null || value === '') return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    const raw = String(value).trim()
    return raw || ''
  }
  const dd = String(date.getDate()).padStart(2, '0')
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const yyyy = date.getFullYear()
  return `${dd}/${mm}/${yyyy}`
}

function toDateOnlyString(value) {
  if (value == null || value === '') return ''
  const raw = String(value).trim()
  const iso = raw.match(/^(\d{4})-(\d{2})-(\d{2})/)
  if (iso) return `${iso[1]}-${iso[2]}-${iso[3]}`

  const dmy = raw.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/)
  if (dmy) {
    return `${dmy[3]}-${dmy[2].padStart(2, '0')}-${dmy[1].padStart(2, '0')}`
  }

  const date = new Date(raw)
  if (Number.isNaN(date.getTime())) return ''
  const yyyy = date.getFullYear()
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const dd = String(date.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

function todayDateOnlyString() {
  const today = new Date()
  const yyyy = today.getFullYear()
  const mm = String(today.getMonth() + 1).padStart(2, '0')
  const dd = String(today.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

function isDatePast(value) {
  const dateStr = toDateOnlyString(value)
  if (!dateStr) return false
  return dateStr < todayDateOnlyString()
}

function buildDeadlineStatus({ dateValue, hasFile, lateLabel, okLabel }) {
  if (dateValue == null || String(dateValue).trim() === '') return null
  const late = isDatePast(dateValue) && !hasFile
  return {
    late,
    tooltip: late ? lateLabel : okLabel,
  }
}

function earliestDate(values) {
  const dates = (Array.isArray(values) ? values : [])
    .map((item) => String(item || '').slice(0, 10))
    .filter((item) => /^\d{4}-\d{2}-\d{2}$/.test(item))
    .sort()
  return dates[0] || null
}

function formatTime(value) {
  if (value == null || value === '') return ''
  const raw = String(value).trim()
  const match = raw.match(/^(\d{1,2}):(\d{2})(?::\d{2})?/)
  if (match) {
    return `${match[1].padStart(2, '0')}:${match[2]}`
  }
  const date = new Date(raw)
  if (!Number.isNaN(date.getTime())) {
    return date.toLocaleTimeString('vi-VN', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    })
  }
  return raw
}

function formatBuoi(value) {
  if (value == null || value === '') return ''
  return String(value).trim().toLowerCase()
}

function normalizeUrl(url) {
  const raw = String(url || '').trim()
  if (!raw) return '#'
  if (/^https?:\/\//i.test(raw)) return raw
  return `https://${raw}`
}

function openLinkModal(field) {
  linkModalField.value = field
  linkInput.value = field.url || ''
  linkModalVisible.value = true
}

async function saveLink() {
  const field = linkModalField.value
  if (!field) return

  const value = String(linkInput.value || '').trim()
  if (!value) {
    ElMessage.warning('Vui lòng nhập link')
    return
  }

  savingLink.value = true
  try {
    const { data } = await capNhatKetQuaHopDong(props.item.id, {
      key: field.key,
      gia_tri: value,
    })
    ElMessage.success('Đã lưu link')
    linkModalVisible.value = false
    emit('updated', data)
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      'Không thể lưu link'
    ElMessage.error(msg)
  } finally {
    savingLink.value = false
  }
}

async function onNhan() {
  try {
    await ElMessageBox.confirm(
      `Nhận công việc ${props.item.ma_hop_dong || ''}? Trạng thái sẽ chuyển sang Đang xử lý.`,
      'Nhận việc',
      {
        type: 'info',
        confirmButtonText: 'Nhận',
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  accepting.value = true
  try {
    await nhanCongViecDieuPhoi(props.item.id)
    ElMessage.success('Đã nhận công việc')
    emit('accepted')
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      'Không thể nhận công việc'
    ElMessage.error(msg)
  } finally {
    accepting.value = false
  }
}

async function onGuiKhachKiemTra() {
  if (!canGuiKhachKiemTra.value) return

  try {
    await ElMessageBox.confirm(
      `Gửi khách kiểm tra cho hợp đồng ${props.item.ma_hop_dong || ''}?`,
      'Gửi khách kiểm tra',
      {
        type: 'info',
        confirmButtonText: 'Gửi',
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  sendingKhach.value = true
  try {
    await guiKhachKiemTra(props.item.id)
    ElMessage.success('Đã gửi khách kiểm tra')
    emit('status-changed')
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      'Không thể gửi khách kiểm tra'
    ElMessage.error(msg)
  } finally {
    sendingKhach.value = false
  }
}

function openYKienView() {
  yKienViewVisible.value = true
}

async function onBanGiao() {
  if (!canBanGiao.value) return

  try {
    await ElMessageBox.confirm(
      `Bàn giao hợp đồng ${props.item.ma_hop_dong || ''}? Trạng thái sẽ chuyển sang Chờ nghiệm thu.`,
      'Bàn giao',
      {
        type: 'info',
        confirmButtonText: 'Bàn giao',
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  banningGiao.value = true
  try {
    await banGiaoCongViec(props.item.id)
    ElMessage.success('Đã bàn giao — chờ nghiệm thu')
    emit('status-changed')
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      'Không thể bàn giao'
    ElMessage.error(msg)
  } finally {
    banningGiao.value = false
  }
}
</script>

<style scoped lang="scss">
.cong-viec-card {
  height: 100%;

  :deep(.el-card__body) {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 14px;
    height: 100%;
  }

  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
  }

  &__title {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.35;
    color: var(--el-color-primary);
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    min-width: 0;
  }

  &__body {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
  }

  &__customer {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 4px;
    min-width: 0;
    font-size: 13px;
    line-height: 1.4;

    .label {
      color: var(--el-text-color-secondary);
      flex-shrink: 0;
    }

    .value {
      color: var(--el-text-color-primary);
      font-weight: 500;
      min-width: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .code {
      color: var(--el-text-color-regular);
      flex-shrink: 0;
    }
  }

  &__row {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;

    .label {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      color: var(--el-text-color-secondary);
      line-height: 1.2;
    }

    .value {
      font-size: 13px;
      color: var(--el-text-color-primary);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }

  &__times {
    display: block;
  }

  &__time {
    display: inline-block;
    max-width: 100%;
    cursor: help;
    vertical-align: bottom;
  }

  .deadline-status-icon {
    font-size: 14px;
    cursor: default;
    vertical-align: middle;

    &.is-ok {
      color: var(--el-color-success);
    }

    &.is-late {
      color: var(--el-color-danger);
    }
  }

  &__files {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    padding-top: 2px;
  }

  &__file {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
    min-width: 0;

    .label {
      font-size: 11px;
      color: var(--el-text-color-secondary);
      line-height: 1.2;
    }
  }

  &__file-actions {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    min-height: 24px;
  }

  .link-open {
    font-size: 13px;
    font-weight: 400;
    color: var(--el-color-primary);
    text-decoration: none;
    line-height: 24px;

    &:hover {
      text-decoration: underline;
    }
  }

  &__roles {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding-top: 4px;

    .label {
      font-size: 11px;
      color: var(--el-text-color-secondary);
      line-height: 1.2;
    }
  }

  &__roles-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
  }

  &__footer {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid var(--el-border-color-lighter);
  }

  &__footer-hint {
    padding-bottom: 2px;
  }

  &__footer-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
  }

  .status-hint {
    display: block;
    width: 100%;
    font-size: 12px;
    line-height: 1.4;
    color: var(--el-text-color-secondary);
    text-align: center;
  }

  &__footer-btn-wrap {
    display: inline-flex;
  }

  .file-empty {
    font-size: 13px;
    color: var(--el-text-color-placeholder);
    line-height: 24px;
  }
}

.y-kien-view {
  &__meta {
    margin: 0 0 10px;
    font-size: 13px;
    color: var(--el-text-color-secondary);
  }

  &__content {
    margin: 0;
    padding: 12px;
    border-radius: 6px;
    background: var(--el-fill-color-light);
    color: var(--el-text-color-primary);
    font-size: 14px;
    line-height: 1.55;
    white-space: pre-wrap;
    word-break: break-word;
  }
}
</style>
