<template>
  <div class="diem-danh page-list">
    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Điểm danh hôm nay</span>
          <div class="card-header-actions">
            <TableColumnConfig :settings="columnSettings" />
            <CustomButton
              v-if="!alreadyDone"
              :type="canCheckout ? 'warning' : 'primary'"
              :loading="checking"
              :disabled="!canCheckin && !canCheckout"
              @click="onCheckInOut"
            >
              <CustomIcon>
                <component :is="canCheckout ? SwitchButton : CircleCheck" />
              </CustomIcon>
              {{ canCheckout ? 'Checkout' : 'Checkin' }}
            </CustomButton>
            <CustomTag v-else type="success" effect="light">Đã checkout</CustomTag>
          </div>
        </div>
      </template>

      <CustomTable
        :column-settings="columnSettings"
        v-loading="loading"
        :data="items"
        stripe
        border
        style="width: 100%"
        :empty-text="loading ? 'Đang tải...' : 'Chưa có dữ liệu điểm danh'"
      >
        <CustomTableColumn label="STT" width="60" align="center" fixed="left">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ho_ten')"
          label="Họ tên"
          min-width="150"
          fixed="left"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.user?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ca_lam')"
          label="Ca làm"
          min-width="160"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ formatCaLam(row.ca_lam) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('gio_vao')"
          label="Giờ vào"
          width="110"
          align="center"
        >
          <template #default="{ row }">
            {{ formatTime(row.gio_vao) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('gio_ra')"
          label="Giờ ra"
          width="110"
          align="center"
        >
          <template #default="{ row }">
            {{ formatTime(row.gio_ra) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('di_muon')"
          label="Đi muộn"
          width="100"
          align="center"
        >
          <template #default="{ row }">
            <CustomTag
              v-if="row.di_muon === 'co'"
              type="danger"
              effect="light"
              size="small"
            >
              {{ row.thoi_gian_di_muon ? `${row.thoi_gian_di_muon} phút` : 'Có' }}
            </CustomTag>
            <span v-else>—</span>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ve_som')"
          label="Về sớm"
          width="100"
          align="center"
        >
          <template #default="{ row }">
            <CustomTag
              v-if="row.ve_som === 'co'"
              type="warning"
              effect="light"
              size="small"
            >
              {{ row.thoi_gian_ve_som ? `${row.thoi_gian_ve_som} phút` : 'Có' }}
            </CustomTag>
            <span v-else>—</span>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('phat_di_muon')"
          label="Phạt đi muộn"
          min-width="120"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.tien_phat_di_muon) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('phat_ve_som')"
          label="Phạt về sớm"
          min-width="120"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.tien_phat_ve_som) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ly_do')"
          label="Lý do"
          min-width="160"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.ly_do || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('gio_lam_co_ban')"
          label="Giờ làm cơ bản"
          width="130"
          align="center"
        >
          <template #default="{ row }">
            {{ formatHours(row.gio_lam_co_ban) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('gio_lam_tang_ca')"
          label="Giờ làm tăng ca"
          width="130"
          align="center"
        >
          <template #default="{ row }">
            {{ formatHours(row.gio_lam_tang_ca) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('luong_co_ban')"
          label="Lương cơ bản"
          min-width="130"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.luong_co_ban) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('luong_tang_ca')"
          label="Lương tăng ca"
          min-width="130"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.luong_tang_ca) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ghi_chu')"
          label="Ghi chú"
          min-width="160"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.ghi_chu || '—' }}
          </template>
        </CustomTableColumn>
      </CustomTable>

      <Pagination
        v-model="page"
        v-model:page-size="perPage"
        :total="total"
        :disabled="loading"
        @change="loadItems"
      />
    </CustomCard>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { CircleCheck, SwitchButton } from '@element-plus/icons-vue'
import {
  checkinDiemDanh,
  checkoutDiemDanh,
  fetchClientIp,
  fetchDiemDanh,
  getDiemDanhToday,
} from '@/api/diemDanh'
import { getCauHinhJson } from '@/api/cauHinhJson'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { useTableColumns } from '@/composables/useTableColumns'
import {
  CustomButton,
  CustomCard,
  CustomIcon,
  CustomTable,
  CustomTableColumn,
  CustomTag,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'

const tableColumns = [
  { key: 'ho_ten', label: 'Họ tên' },
  { key: 'ca_lam', label: 'Ca làm' },
  { key: 'gio_vao', label: 'Giờ vào' },
  { key: 'gio_ra', label: 'Giờ ra' },
  { key: 'di_muon', label: 'Đi muộn' },
  { key: 've_som', label: 'Về sớm' },
  { key: 'phat_di_muon', label: 'Phạt đi muộn' },
  { key: 'phat_ve_som', label: 'Phạt về sớm' },
  { key: 'ly_do', label: 'Lý do' },
  { key: 'gio_lam_co_ban', label: 'Giờ làm cơ bản' },
  { key: 'gio_lam_tang_ca', label: 'Giờ làm tăng ca' },
  { key: 'luong_co_ban', label: 'Lương cơ bản' },
  { key: 'luong_tang_ca', label: 'Lương tăng ca' },
  { key: 'ghi_chu', label: 'Ghi chú' },
]
const columnSettings = useTableColumns('nhan-su.diem-danh', tableColumns, {
  pin: {
    selection: false,
    actions: false,
    defaultLeft: ['__stt__', 'ho_ten'],
  },
})

const items = ref([])
const loading = ref(false)
const checking = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)

const canCheckin = ref(true)
const canCheckout = ref(false)
const kiemSoatIpDiemDanh = ref(false)
const alreadyDone = computed(() => !canCheckin.value && !canCheckout.value)

function formatTime(value) {
  if (!value) return '—'
  const str = String(value)
  if (str.includes('T')) {
    return str.slice(11, 16)
  }
  if (str.includes(' ')) {
    return str.slice(11, 16)
  }
  return str.slice(0, 5)
}

function formatCaLam(caLam) {
  if (!caLam) return '—'
  const start = formatTime(caLam.gio_bat_dau)
  const end = formatTime(caLam.gio_ket_thuc)
  if (start !== '—' && end !== '—') {
    return `${caLam.ten_ca} (${start}–${end})`
  }
  return caLam.ten_ca || '—'
}

function formatHours(value) {
  if (value == null || value === '') return '—'
  const num = Number(value)
  if (Number.isNaN(num) || num === 0) return '—'
  return `${num}h`
}

function formatMoney(value) {
  if (value == null || value === '') return '—'
  const num = Number(value)
  if (Number.isNaN(num) || num === 0) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

async function loadChamCongConfig() {
  try {
    const { data } = await getCauHinhJson({ skipLoading: true })
    const group = data?.thong_tin_cau_hinh?.cham_cong_tang_ca || {}
    kiemSoatIpDiemDanh.value = Boolean(group.kiem_soat_ip_diem_danh?.gia_tri)
  } catch {
    kiemSoatIpDiemDanh.value = false
  }
}

async function loadTodayStatus() {
  try {
    const { data } = await getDiemDanhToday({ skipLoading: true })
    canCheckin.value = !!data.can_checkin
    canCheckout.value = !!data.can_checkout
  } catch {
    canCheckin.value = true
    canCheckout.value = false
  }
}

async function loadItems() {
  loading.value = true
  try {
    const today = new Intl.DateTimeFormat('en-CA', {
      timeZone: 'Asia/Ho_Chi_Minh',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    }).format(new Date())

    const { data } = await fetchDiemDanh(
      {
        page: page.value,
        per_page: perPage.value,
        ngay_lam: today,
      },
      { skipLoading: true },
    )
    items.value = data.data || []
    total.value = data.total || 0
  } catch {
    items.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

async function onCheckInOut() {
  checking.value = true
  try {
    const payload = {}
    if (kiemSoatIpDiemDanh.value) {
      payload.ip = await fetchClientIp()
    }
    if (canCheckout.value) {
      await checkoutDiemDanh(payload)
      ElMessage.success('Checkout thành công')
    } else {
      await checkinDiemDanh(payload)
      ElMessage.success('Checkin thành công')
    }
    await Promise.all([loadTodayStatus(), loadItems()])
  } catch (error) {
    if (!error?.response) {
      ElMessage.error(error?.message || 'Không thể lấy địa chỉ IP để điểm danh.')
    }
    // Lỗi 422 từ API đã được axios interceptor hiển thị
  } finally {
    checking.value = false
  }
}

const props = defineProps({
  active: { type: Boolean, default: false },
})

async function refresh() {
  await Promise.all([loadTodayStatus(), loadItems()])
}

watch(
  () => props.active,
  (isActive) => {
    if (isActive) refresh()
  },
  { immediate: true },
)

onMounted(loadChamCongConfig)
</script>
