<template>
  <CustomCard shadow="hover" class="cong-viec-card">
    <div class="cong-viec-card__header">
      <h3 class="cong-viec-card__title" :title="loaiHopDong">
        {{ loaiHopDong }}
      </h3>
      <div class="cong-viec-card__header-actions">
        <CustomTag :type="trangThaiTagType(item.trang_thai)" size="small">
          {{ trangThaiLabel(item.trang_thai) }}
        </CustomTag>
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
        <span class="label">Thời gian chụp</span>
        <span class="value">{{ thoiGianChup }}</span>
      </div>
      <div v-if="ngayTraDemo" class="cong-viec-card__row">
        <span class="label">Ngày trả demo</span>
        <span class="value">{{ ngayTraDemo }}</span>
      </div>
      <div v-if="ngayTraChinhThuc" class="cong-viec-card__row">
        <span class="label">Ngày trả chính thức</span>
        <span class="value">{{ ngayTraChinhThuc }}</span>
      </div>

      <div v-if="showFileLinks" class="cong-viec-card__files">
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
              <CustomTooltip content="Sửa link" placement="top">
                <CustomButton
                  type="primary"
                  link
                  :icon="Edit"
                  @click.stop="openLinkModal(field)"
                />
              </CustomTooltip>
            </template>
            <CustomTooltip v-else content="Thêm link" placement="top">
              <CustomButton
                type="primary"
                link
                :icon="Plus"
                @click.stop="openLinkModal(field)"
              />
            </CustomTooltip>
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

      <div v-if="showFileLinks" class="cong-viec-card__footer">
        <CustomTooltip
          :content="
            canGuiKhachKiemTra
              ? 'Gửi khách kiểm tra'
              : 'Cần có đủ File gốc và File demo'
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
  </CustomCard>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Edit, Plus, Select } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  capNhatKetQuaHopDong,
  guiKhachKiemTra,
  nhanCongViecDieuPhoi,
} from '@/api/hopDongSuDungDichVu'
import {
  CustomButton,
  CustomCard,
  CustomDialog,
  CustomTag,
  CustomTooltip,
} from '@/components/element'
import { useAuthStore } from '@/stores/auth'

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
  { key: 'link_file_demo', label: 'File demo' },
  { key: 'link_giao_san_pham', label: 'File chính thức' },
]

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  showNhan: {
    type: Boolean,
    default: false,
  },
  showFileLinks: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['accepted', 'updated', 'status-changed'])

const authStore = useAuthStore()
const accepting = ref(false)
const sendingKhach = ref(false)
const linkModalVisible = ref(false)
const linkModalField = ref(null)
const linkInput = ref('')
const savingLink = ref(false)

const ketQua = computed(() => {
  const raw = props.item?.ket_qua_hop_dong
  return raw && typeof raw === 'object' && !Array.isArray(raw) ? raw : {}
})

const ketQuaTrangThai = computed(() => {
  const giaTri = ketQua.value?.trang_thai?.gia_tri
  if (giaTri == null || giaTri === '') return null
  return String(giaTri)
})

const canNhan = computed(() => props.showNhan && !ketQuaTrangThai.value)

const hasLinkFileGoc = computed(() => {
  const giaTri = ketQua.value?.link_file_goc?.gia_tri
  return giaTri != null && String(giaTri).trim() !== ''
})

const hasLinkFileDemo = computed(() => {
  const giaTri = ketQua.value?.link_file_demo?.gia_tri
  return giaTri != null && String(giaTri).trim() !== ''
})

const canGuiKhachKiemTra = computed(
  () => props.showFileLinks && hasLinkFileGoc.value && hasLinkFileDemo.value,
)

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
    font-weight: 500;
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
  }

  &__footer-btn-wrap {
    display: block;
    width: 100%;

    :deep(.el-button) {
      width: 100%;
    }
  }
}
</style>
