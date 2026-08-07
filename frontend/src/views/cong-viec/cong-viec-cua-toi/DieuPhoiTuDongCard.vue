<template>
  <CustomCard shadow="hover" class="cong-viec-card">
    <div class="cong-viec-card__header">
      <span class="cong-viec-card__code" :title="item.ma_hop_dong">
        {{ item.ma_hop_dong || '—' }}
      </span>
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
      <div class="cong-viec-card__row">
        <span class="label">Khách hàng</span>
        <span class="value" :title="khachHang">{{ khachHang }}</span>
      </div>
      <div class="cong-viec-card__row">
        <span class="label">Loại HĐ</span>
        <span class="value" :title="loaiHopDong">{{ loaiHopDong }}</span>
      </div>
      <div v-if="ngayChup" class="cong-viec-card__row">
        <span class="label">Ngày chụp</span>
        <span class="value">{{ formatDate(ngayChup) }}</span>
      </div>
      <div v-if="vaiTroLabels.length" class="cong-viec-card__roles">
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
  </CustomCard>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Select } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { nhanCongViecDieuPhoi } from '@/api/hopDongSuDungDichVu'
import { CustomButton, CustomCard, CustomTag, CustomTooltip } from '@/components/element'
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

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  showNhan: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['accepted'])

const authStore = useAuthStore()
const accepting = ref(false)

const ketQuaTrangThai = computed(() => {
  const giaTri = props.item?.ket_qua_hop_dong?.trang_thai?.gia_tri
  if (giaTri == null || giaTri === '') return null
  return String(giaTri)
})

const canNhan = computed(() => props.showNhan && !ketQuaTrangThai.value)

const khachHang = computed(() => formatKhachHang(props.item))
const loaiHopDong = computed(
  () => props.item?.loai_hop_dong?.ten_hop_dong || '—',
)
const ngayChup = computed(() => {
  const raw = props.item?.thong_tin_dieu_phoi?.ngay_chup?.gia_tri
  return raw || null
})

const vaiTroLabels = computed(() => {
  const userId = authStore.user?.id
  if (userId == null) return []

  const dieuPhoi =
    props.item?.thong_tin_dieu_phoi &&
    typeof props.item.thong_tin_dieu_phoi === 'object' &&
    !Array.isArray(props.item.thong_tin_dieu_phoi)
      ? props.item.thong_tin_dieu_phoi
      : {}

  const roles = []
  for (const [key, label] of Object.entries(STAFF_ROLE_LABELS)) {
    const giaTri = dieuPhoi[key]?.gia_tri
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
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString('vi-VN')
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
</script>

<style scoped lang="scss">
.cong-viec-card {
  height: 100%;

  :deep(.el-card__body) {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px;
    height: 100%;
  }

  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
  }

  &__header-actions {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
  }

  &__code {
    font-weight: 600;
    font-size: 13px;
    color: var(--el-color-primary);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
  }

  &__body {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
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

  &__roles {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: auto;
    padding-top: 4px;
  }
}
</style>
