<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1100"
    class="lich-khach-hang-chi-tiet-modal"
    @closed="onClosed"
  >
    <CustomTable
      v-loading="loading"
      :data="items"
      stripe
      row-key="event_key"
      style="width: 100%"
      empty-text="Không có khách hàng"
    >
      <CustomTableColumn label="STT" width="60" align="center">
        <template #default="{ $index }">
          {{ $index + 1 }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Trạng thái ngày" width="130" align="center">
        <template #default="{ row }">
          <CustomTag :type="loaiTagType(row.loai)" size="small">
            {{ loaiLabel(row.loai) }}
          </CustomTag>
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Khách hàng" min-width="180">
        <template #default="{ row }">
          <div>{{ row.ten_khach || '—' }}</div>
          <div v-if="row.sdt" class="sub-text">{{ row.sdt }}</div>
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Phụ trách sale" min-width="160" show-overflow-tooltip>
        <template #default="{ row }">
          {{ formatSaleNames(row) }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Trạng thái note" width="130" align="center">
        <template #default="{ row }">
          <CustomTag :type="trangThaiTagType(row.trang_thai)" size="small">
            {{ trangThaiLabel(row.trang_thai) }}
          </CustomTag>
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Nguồn khách" min-width="120" show-overflow-tooltip>
        <template #default="{ row }">
          {{ row.nguon_khach || '—' }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Ghi chú" min-width="160" show-overflow-tooltip>
        <template #default="{ row }">
          {{ row.ghi_chu || '—' }}
        </template>
      </CustomTableColumn>
    </CustomTable>
  </CustomDialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { fetchLichKhachHangChiTiet } from '@/api/noteKhachMoi'

const visible = defineModel({ type: Boolean, default: false })

const props = defineProps({
  ngay: { type: String, default: '' },
})

const loading = ref(false)
const items = ref([])

const dialogTitle = computed(() => `Lịch khách hàng ngày ${formatDateVi(props.ngay)}`)

const LOAI_LABEL = {
  hen_lich: 'Hẹn lịch',
  den: 'Đến',
}

const TRANG_THAI_LABEL = {
  cho_hen: 'Chờ hẹn',
  da_den: 'Đã đến',
  khong_den: 'Không đến',
  da_ky_hd: 'Đã ký HĐ',
  da_huy: 'Đã hủy',
}

function loaiLabel(value) {
  return LOAI_LABEL[value] || value || '—'
}

function loaiTagType(value) {
  return value === 'den' ? 'success' : 'primary'
}

function trangThaiLabel(value) {
  return TRANG_THAI_LABEL[value] || value || '—'
}

function trangThaiTagType(value) {
  const map = {
    cho_hen: 'info',
    da_den: 'success',
    khong_den: 'warning',
    da_ky_hd: 'success',
    da_huy: 'danger',
  }
  return map[value] || 'info'
}

function formatDateVi(value) {
  if (!value) return '—'
  const raw = String(value).slice(0, 10)
  const [y, m, d] = raw.split('-')
  if (!y || !m || !d) return raw
  return `${d}/${m}/${y}`
}

function formatSaleNames(row) {
  const users = row?.phu_trach_sale_users
  if (!Array.isArray(users) || !users.length) return '—'
  return users.map((u) => u.name).filter(Boolean).join(', ') || '—'
}

async function loadItems() {
  if (!props.ngay) {
    items.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await fetchLichKhachHangChiTiet({ ngay: props.ngay })
    items.value = data.items || []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

function onClosed() {
  items.value = []
}

watch(
  () => [visible.value, props.ngay],
  ([isOpen]) => {
    if (!isOpen) return
    loadItems()
  },
)
</script>

<style scoped lang="scss">
.sub-text {
  font-size: 12px;
  color: var(--el-text-color-secondary);
  margin-top: 2px;
}
</style>
