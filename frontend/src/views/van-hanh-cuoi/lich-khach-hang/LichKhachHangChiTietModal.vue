<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1100"
    class="lich-khach-hang-chi-tiet-modal"
    @closed="onClosed"
  >
    <el-tabs
      v-if="visibleTabs.length"
      v-model="activeTrangThai"
      class="status-tabs"
      @tab-change="onTabChange"
    >
      <el-tab-pane
        v-for="tab in visibleTabs"
        :key="tab.value"
        :name="tab.value"
        :label="`${tab.label} (${tab.count})`"
      />
    </el-tabs>

    <CustomTable
      v-loading="loading"
      :data="items"
      stripe
      row-key="id"
      style="width: 100%"
      empty-text="Không có khách hàng"
    >
      <CustomTableColumn label="STT" width="60" align="center">
        <template #default="{ $index }">
          {{ $index + 1 }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Khách hàng" min-width="180">
        <template #default="{ row }">
          <div>{{ row.ten_khach || '—' }}</div>
          <div v-if="row.sdt" class="sub-text">{{ row.sdt }}</div>
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Ngày hẹn" width="120" align="center">
        <template #default="{ row }">
          {{ formatDateVi(row.ngay_hen_lich) }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Ngày đến TT" width="120" align="center">
        <template #default="{ row }">
          {{ formatDateVi(row.ngay_den_thuc_te) }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Phụ trách sale" min-width="160" show-overflow-tooltip>
        <template #default="{ row }">
          {{ formatSaleNames(row) }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Trạng thái" width="130" align="center">
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

const TRANG_THAI_OPTIONS = [
  { value: 'cho_hen', label: 'Chờ hẹn' },
  { value: 'da_den', label: 'Đã đến' },
  { value: 'khong_den', label: 'Không đến' },
  { value: 'da_ky_hd', label: 'Đã ký HĐ' },
  { value: 'da_huy', label: 'Đã hủy' },
]

const visible = defineModel({ type: Boolean, default: false })

const props = defineProps({
  ngay: { type: String, default: '' },
  trangThai: { type: String, default: 'cho_hen' },
  counts: {
    type: Object,
    default: () => ({
      cho_hen: 0,
      da_den: 0,
      khong_den: 0,
      da_ky_hd: 0,
      da_huy: 0,
    }),
  },
})

const loading = ref(false)
const items = ref([])
const activeTrangThai = ref(props.trangThai)

const visibleTabs = computed(() =>
  TRANG_THAI_OPTIONS.map((tab) => ({
    ...tab,
    count: Number(props.counts?.[tab.value]) || 0,
  })).filter((tab) => tab.count > 0),
)

const activeTabMeta = computed(
  () => TRANG_THAI_OPTIONS.find((tab) => tab.value === activeTrangThai.value) || TRANG_THAI_OPTIONS[0],
)

const dialogTitle = computed(() => {
  const ngay = formatDateVi(props.ngay)
  return `${activeTabMeta.value.label} ngày ${ngay}`
})

const TRANG_THAI_LABEL = Object.fromEntries(
  TRANG_THAI_OPTIONS.map((opt) => [opt.value, opt.label]),
)

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

function onTabChange() {
  loadItems()
}

async function loadItems() {
  if (!props.ngay || !activeTrangThai.value) {
    items.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await fetchLichKhachHangChiTiet({
      ngay: props.ngay,
      trang_thai: activeTrangThai.value,
    })
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
  () => [visible.value, props.ngay, props.trangThai],
  ([isOpen, , trangThai]) => {
    if (!isOpen) return
    activeTrangThai.value = trangThai || visibleTabs.value[0]?.value || TRANG_THAI_OPTIONS[0].value
    loadItems()
  },
)
</script>

<style scoped lang="scss">
.status-tabs {
  margin-bottom: 12px;
}

.sub-text {
  font-size: 12px;
  color: var(--el-text-color-secondary);
  margin-top: 2px;
}
</style>
