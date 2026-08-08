<template>
  <CustomCard shadow="hover" class="cong-viec-card">
    <div class="cong-viec-card__header">
      <h3 class="cong-viec-card__title" :title="loaiHopDong">
        {{ loaiHopDong }}
      </h3>
      <div class="cong-viec-card__header-actions">
        <CustomTooltip content="Xem chi tiết hợp đồng" placement="top">
          <CustomButton
            type="info"
            link
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
            link
            :icon="ChatDotRound"
            @click.stop="openYKienView"
          />
        </CustomTooltip>
        <!-- <CustomTag
          v-if="ketQuaTrangThaiTag"
          :type="ketQuaTrangThaiTag.type"
          size="small"
        >
          {{ ketQuaTrangThaiTag.label }}
        </CustomTag>
        <CustomTag v-else :type="trangThaiTagType(item.trang_thai)" size="small">
          {{ trangThaiLabel(item.trang_thai) }}
        </CustomTag> -->
        <CustomTooltip v-if="canNhan" content="Nhận việc" placement="top">
          <CustomButton
            type="primary"
            link
            :icon="Select"
            :loading="accepting"
            @click.stop="onNhan"
          />
        </CustomTooltip>
      </div>
    </div>

    <div class="cong-viec-card__body">
      <div class="cong-viec-card__customer" :title="customerLine">
        <span class="value">{{ khachHang }}</span>
        <span class="code">- [{{ maHopDong }}]</span>
      </div>

      <div v-if="thoiGianChup" class="cong-viec-card__row">
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
        <span class="value">{{ thoiGianChup }}</span>
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

      <div class="cong-viec-card__files">
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
                  link
                  :icon="Edit"
                  @click.stop="openLinkModal(field)"
                />
              </CustomTooltip>
            </template>
            <template v-else-if="canEditFile(field)">
              <CustomTooltip content="Thêm link" placement="top">
                <CustomButton
                  type="primary"
                  link
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

      <div v-if="showGuiKhachFooter" class="cong-viec-card__footer">
        <CustomTooltip
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
              size="small"
              :disabled="!canGuiKhachKiemTra"
              :loading="sendingKhach"
              @click.stop="onGuiKhachKiemTra"
            >
              Gửi khách kiểm tra
            </CustomButton>
          </span>
        </CustomTooltip>
      </div>

      <div v-else-if="showKhachKiemTraFooter" class="cong-viec-card__footer cong-viec-card__footer--split">
        <div class="cong-viec-card__footer-col">
          <CustomTooltip content="Khách đồng ý" placement="top">
            <CustomButton
              type="success"
              size="small"
              :icon="Check"
              :loading="processingKhach === 'dong_y'"
              :disabled="!!processingKhach"
              class="btn-khach-response"
              @click.stop="onXuLyKhachKiemTra('dong_y')"
            >
              <span class="btn-khach-response__label">Khách đồng ý</span>
            </CustomButton>
          </CustomTooltip>
        </div>
        <div class="cong-viec-card__footer-col">
          <CustomTooltip content="Khách không đồng ý" placement="top">
            <CustomButton
              type="danger"
              plain
              size="small"
              :icon="Close"
              :loading="processingKhach === 'khong_dong_y'"
              :disabled="!!processingKhach"
              class="btn-khach-response"
              @click.stop="openYKienModal('khong_dong_y')"
            >
              <span class="btn-khach-response__label">Khách không đồng ý</span>
            </CustomButton>
          </CustomTooltip>
        </div>
      </div>

      <div v-else-if="showBanGiaoFooter" class="cong-viec-card__footer">
        <CustomTooltip
          :content="
            canBanGiao
              ? 'Bàn giao sản phẩm'
              : 'Cần có File in trước khi bàn giao'
          "
          placement="top"
        >
          <span class="cong-viec-card__footer-btn-wrap">
            <CustomButton
              type="primary"
              size="small"
              :disabled="!canBanGiao"
              :loading="banningGiao"
              @click.stop="onBanGiao"
            >
              Bàn giao
            </CustomButton>
          </span>
        </CustomTooltip>
      </div>

      <div
        v-else-if="showNghiemThuFooter"
        class="cong-viec-card__footer cong-viec-card__footer--split"
      >
        <div class="cong-viec-card__footer-col">
          <CustomTooltip content="Hoàn thành" placement="top">
            <CustomButton
              type="success"
              size="small"
              :icon="Check"
              :loading="processingNghiemThu === 'hoan_thanh'"
              :disabled="!!processingNghiemThu"
              class="btn-khach-response"
              @click.stop="onHoanThanh"
            >
              <span class="btn-khach-response__label">Hoàn thành</span>
            </CustomButton>
          </CustomTooltip>
        </div>
        <div class="cong-viec-card__footer-col">
          <CustomTooltip content="Làm lại" placement="top">
            <CustomButton
              type="warning"
              plain
              size="small"
              :icon="RefreshRight"
              :loading="processingNghiemThu === 'lam_lai'"
              :disabled="!!processingNghiemThu"
              class="btn-khach-response"
              @click.stop="openYKienModal('lam_lai')"
            >
              <span class="btn-khach-response__label">Làm lại</span>
            </CustomButton>
          </CustomTooltip>
        </div>
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
      v-model="yKienModalVisible"
      :title="yKienModalTitle"
      :width="520"
    >
      <el-form label-position="top" @submit.prevent="submitYKienModal">
        <el-form-item :label="yKienModalLabel" required>
          <el-input
            v-model="yKienInput"
            type="textarea"
            :rows="5"
            maxlength="5000"
            show-word-limit
            :placeholder="yKienModalPlaceholder"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <CustomButton @click="yKienModalVisible = false">Hủy</CustomButton>
        <CustomButton
          :type="yKienModalMode === 'lam_lai' ? 'warning' : 'danger'"
          :loading="
            processingKhach === 'khong_dong_y' ||
            processingNghiemThu === 'lam_lai'
          "
          @click="submitYKienModal"
        >
          {{ yKienModalConfirmText }}
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
import { ChatDotRound, Check, CircleCheckFilled, Close, Edit, Plus, RefreshRight, Select, View, WarningFilled } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  banGiaoCongViec,
  capNhatKetQuaHopDong,
  guiKhachKiemTra,
  nhanCongViecDieuPhoi,
  xuLyKhachKiemTra,
  xuLyNghiemThu,
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
const processingKhach = ref('')
const processingNghiemThu = ref('')
const linkModalVisible = ref(false)
const linkModalField = ref(null)
const linkInput = ref('')
const savingLink = ref(false)
const yKienModalVisible = ref(false)
const yKienModalMode = ref('khong_dong_y')
const yKienInput = ref('')
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

const ketQuaTrangThai = computed(() => {
  const giaTri = ketQua.value?.trang_thai?.gia_tri
  if (giaTri == null || giaTri === '') return null
  return String(giaTri)
})

const ketQuaTrangThaiTag = computed(() => {
  const map = {
    cho_nghiem_thu: { label: 'Chờ nghiệm thu', type: 'warning' },
    hoan_thanh: { label: 'Hoàn thành', type: 'success' },
  }
  if (!ketQuaTrangThai.value) return null
  return map[ketQuaTrangThai.value] || null
})

const canNhan = computed(() => props.step === 'cho_nhan' && !ketQuaTrangThai.value)
const showGuiKhachFooter = computed(() => props.step === 'dang_xu_ly')
const showKhachKiemTraFooter = computed(() => props.step === 'gui_khach_kiem_tra')
const showBanGiaoFooter = computed(() => props.step === 'san_xuat_in_an')
const showNghiemThuFooter = computed(
  () => props.step === 'cho_nghiem_thu' && ketQuaTrangThai.value === 'cho_nghiem_thu',
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

const yKienModalTitle = computed(() =>
  yKienModalMode.value === 'lam_lai'
    ? 'Yêu cầu làm lại'
    : 'Ý kiến khách hàng',
)
const yKienModalLabel = computed(() =>
  yKienModalMode.value === 'lam_lai'
    ? 'Yêu cầu của khách hàng'
    : 'Ý kiến khách hàng',
)
const yKienModalPlaceholder = computed(() =>
  yKienModalMode.value === 'lam_lai'
    ? 'Nhập yêu cầu làm lại của khách hàng...'
    : 'Nhập ý kiến / lý do khách không đồng ý...',
)
const yKienModalConfirmText = computed(() =>
  yKienModalMode.value === 'lam_lai'
    ? 'Xác nhận làm lại'
    : 'Xác nhận không đồng ý',
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

const thongTinDieuPhoi = computed(() => {
  const raw = props.item?.thong_tin_dieu_phoi
  return raw && typeof raw === 'object' && !Array.isArray(raw) ? raw : {}
})

const thoiGianChup = computed(() => {
  const gio = formatTime(thongTinDieuPhoi.value.gio_chup?.gia_tri)
  const buoi = formatBuoi(thongTinDieuPhoi.value.buoi_chup?.gia_tri)
  const ngay = formatDate(thongTinDieuPhoi.value.ngay_chup?.gia_tri)
  const parts = [gio, buoi, ngay].filter(Boolean)
  return parts.length ? parts.join(' ') : ''
})

const ngayTraDemo = computed(() =>
  formatDate(thongTinDieuPhoi.value.ngay_tra_demo?.gia_tri) || '',
)
const ngayTraChinhThuc = computed(() =>
  formatDate(thongTinDieuPhoi.value.ngay_tra_chinh_thuc?.gia_tri) || '',
)

const trangThaiChup = computed(() =>
  buildDeadlineStatus({
    dateValue: thongTinDieuPhoi.value.ngay_chup?.gia_tri,
    hasFile: hasLinkFileGoc.value,
    lateLabel: 'Trễ chụp',
    okLabel: 'Đúng hạn chụp',
  }),
)

const trangThaiGiaoDemo = computed(() =>
  buildDeadlineStatus({
    dateValue: thongTinDieuPhoi.value.ngay_tra_demo?.gia_tri,
    hasFile: hasLinkFileDemo.value,
    lateLabel: 'Trễ giao demo',
    okLabel: 'Đúng hạn giao demo',
  }),
)

const trangThaiBanGiao = computed(() =>
  buildDeadlineStatus({
    dateValue: thongTinDieuPhoi.value.ngay_tra_chinh_thuc?.gia_tri,
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
    const giaTri = thongTinDieuPhoi.value[key]?.gia_tri
    const list = Array.isArray(giaTri) ? giaTri : []
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

async function onXuLyKhachKiemTra(ketQua) {
  if (ketQua !== 'dong_y') return

  try {
    await ElMessageBox.confirm(
      `Khách đồng ý với hợp đồng ${props.item.ma_hop_dong || ''}? Chuyển sang Sản xuất & in ấn.`,
      'Khách đồng ý',
      {
        type: 'success',
        confirmButtonText: 'Xác nhận',
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  processingKhach.value = 'dong_y'
  try {
    await xuLyKhachKiemTra(props.item.id, { ket_qua: 'dong_y' })
    ElMessage.success('Đã chuyển sang Sản xuất & in ấn')
    emit('status-changed')
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      'Không thể cập nhật trạng thái'
    ElMessage.error(msg)
  } finally {
    processingKhach.value = ''
  }
}

function openYKienModal(mode) {
  yKienModalMode.value = mode
  yKienInput.value = yKienKhachHang.value || ''
  yKienModalVisible.value = true
}

function openYKienView() {
  yKienViewVisible.value = true
}

async function submitYKienModal() {
  const yKien = String(yKienInput.value || '').trim()
  if (!yKien) {
    ElMessage.warning(
      yKienModalMode.value === 'lam_lai'
        ? 'Vui lòng nhập yêu cầu của khách hàng'
        : 'Vui lòng nhập ý kiến khách hàng',
    )
    return
  }

  if (yKienModalMode.value === 'lam_lai') {
    processingNghiemThu.value = 'lam_lai'
    try {
      await xuLyNghiemThu(props.item.id, {
        hanh_dong: 'lam_lai',
        y_kien_khach_hang: yKien,
      })
      ElMessage.success('Đã chuyển lại Sản xuất & in ấn')
      yKienModalVisible.value = false
      emit('status-changed')
    } catch (error) {
      const msg =
        error?.response?.data?.message ||
        error?.message ||
        'Không thể cập nhật trạng thái'
      ElMessage.error(msg)
    } finally {
      processingNghiemThu.value = ''
    }
    return
  }

  processingKhach.value = 'khong_dong_y'
  try {
    await xuLyKhachKiemTra(props.item.id, {
      ket_qua: 'khong_dong_y',
      y_kien_khach_hang: yKien,
    })
    ElMessage.success('Đã chuyển lại Đang xử lý')
    yKienModalVisible.value = false
    emit('status-changed')
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      'Không thể cập nhật trạng thái'
    ElMessage.error(msg)
  } finally {
    processingKhach.value = ''
  }
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

async function onHoanThanh() {
  try {
    await ElMessageBox.confirm(
      `Hoàn thành hợp đồng ${props.item.ma_hop_dong || ''}?`,
      'Hoàn thành',
      {
        type: 'success',
        confirmButtonText: 'Hoàn thành',
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  processingNghiemThu.value = 'hoan_thanh'
  try {
    await xuLyNghiemThu(props.item.id, { hanh_dong: 'hoan_thanh' })
    ElMessage.success('Đã hoàn thành hợp đồng')
    emit('status-changed')
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      'Không thể hoàn thành'
    ElMessage.error(msg)
  } finally {
    processingNghiemThu.value = ''
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

  &__header-actions {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
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
    margin-top: auto;
    padding-top: 8px;

    &--split {
      display: flex;
      align-items: stretch;
      gap: 12px;
      width: 100%;
      box-sizing: border-box;
    }
  }

  &__footer-col {
    flex: 1 1 0;
    min-width: 0;

    :deep(.btn-khach-response) {
      width: 100%;
      justify-content: center;
    }
  }

  &__footer-btn-wrap {
    display: block;
    width: 100%;

    :deep(.el-button) {
      width: 100%;
    }
  }

  .btn-khach-response {
    &__label {
      margin-left: 4px;
    }
  }

  @media (max-width: 767px) {
    .btn-khach-response {
      :deep(.el-icon) {
        margin-right: 0;
      }

      &__label {
        display: none;
      }
    }
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
