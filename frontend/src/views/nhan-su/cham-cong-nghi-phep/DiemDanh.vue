<template>
  <div class="diem-danh">
    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Điểm danh hôm nay</span>
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
      </template>

      <CustomTable
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
        <CustomTableColumn label="Họ tên" min-width="150" fixed="left" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.user?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Ca làm" min-width="160" show-overflow-tooltip>
          <template #default="{ row }">
            {{ formatCaLam(row.ca_lam) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Giờ vào" width="110" align="center">
          <template #default="{ row }">
            {{ formatTime(row.gio_vao) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Giờ ra" width="110" align="center">
          <template #default="{ row }">
            {{ formatTime(row.gio_ra) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Đi muộn" width="100" align="center">
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
        <CustomTableColumn label="Về sớm" width="100" align="center">
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
        <CustomTableColumn label="Phạt đi muộn" min-width="120" align="right">
          <template #default="{ row }">
            {{ formatMoney(row.tien_phat_di_muon) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Phạt về sớm" min-width="120" align="right">
          <template #default="{ row }">
            {{ formatMoney(row.tien_phat_ve_som) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Lý do" min-width="160" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.ly_do || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Giờ làm cơ bản" width="130" align="center">
          <template #default="{ row }">
            {{ formatHours(row.gio_lam_co_ban) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Giờ làm tăng ca" width="130" align="center">
          <template #default="{ row }">
            {{ formatHours(row.gio_lam_tang_ca) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Lương cơ bản" min-width="130" align="right">
          <template #default="{ row }">
            {{ formatMoney(row.luong_co_ban) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Lương tăng ca" min-width="130" align="right">
          <template #default="{ row }">
            {{ formatMoney(row.luong_tang_ca) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Ghi chú" min-width="160" show-overflow-tooltip>
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
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { CircleCheck, SwitchButton } from '@element-plus/icons-vue'
import {
  checkinDiemDanh,
  checkoutDiemDanh,
  fetchClientIp,
  fetchDiemDanh,
  getDiemDanhToday,
} from '@/api/diemDanh'
import {
  CustomButton,
  CustomCard,
  CustomIcon,
  CustomTable,
  CustomTableColumn,
  CustomTag,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'

const items = ref([])
const loading = ref(false)
const checking = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)

const canCheckin = ref(true)
const canCheckout = ref(false)
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
    const clientIp = await fetchClientIp()
    if (canCheckout.value) {
      await checkoutDiemDanh({ ip: clientIp })
      ElMessage.success('Checkout thành công')
    } else {
      await checkinDiemDanh({ ip: clientIp })
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

onMounted(async () => {
  await Promise.all([loadTodayStatus(), loadItems()])
})
</script>

<style scoped>
.diem-danh {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.card-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}
</style>
