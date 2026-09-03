<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1680"
    class="luong-nhan-vien-chi-tiet-dialog"
    @closed="onClosed"
  >
    <BangLuongChiTiet
      :payload="payload"
      :loading="loading"
      :month="month"
      :daily-title="dailyTitle"
      embedded
      daily-table-max-height="min(40vh, 420px)"
    />
  </CustomDialog>
</template>

<script setup>
import { computed, ref } from 'vue'
import { fetchBangLuongChiTietTheoNgayNhanVien } from '@/api/tinhLuong'
import { CustomDialog } from '@/components/element'
import BangLuongChiTiet from './BangLuongChiTiet.vue'

defineOptions({ name: 'LuongNhanVienChiTietModal' })

const visible = ref(false)
const loading = ref(false)
const payload = ref(null)
const month = ref('')
const employeeName = ref('')

const dialogTitle = computed(() => {
  const monthLabel = formatMonthLabel(month.value)
  const name = employeeName.value || payload.value?.nhan_vien?.name
  if (name) return `Chi tiết lương · ${name} · ${monthLabel}`
  return `Chi tiết lương · ${monthLabel}`
})

const dailyTitle = computed(() => {
  const name = employeeName.value || payload.value?.nhan_vien?.name
  return name ? `Bảng lương · ${name}` : 'Bảng lương nhân viên'
})

function formatMonthLabel(value) {
  if (!value || !String(value).includes('-')) return '—'
  const [y, m] = String(value).split('-')
  return `${m}/${y}`
}

async function open({ userId, thang, name } = {}) {
  if (!userId || !thang) return

  visible.value = true
  month.value = String(thang)
  employeeName.value = name || ''
  payload.value = null
  loading.value = true

  try {
    const { data } = await fetchBangLuongChiTietTheoNgayNhanVien(
      {
        user_id: userId,
        thang: month.value,
      },
      { skipLoading: true },
    )
    payload.value = data
    if (!employeeName.value && data?.nhan_vien?.name) {
      employeeName.value = data.nhan_vien.name
    }
  } catch {
    payload.value = null
  } finally {
    loading.value = false
  }
}

function onClosed() {
  payload.value = null
  month.value = ''
  employeeName.value = ''
  loading.value = false
}

defineExpose({ open })
</script>

<style scoped lang="scss">
:global(.luong-nhan-vien-chi-tiet-dialog.custom-dialog .el-dialog__body) {
  padding-top: 8px;
}
</style>
