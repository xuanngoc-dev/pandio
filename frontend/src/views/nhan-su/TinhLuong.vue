<template>
  <div class="tinh-luong page-list">
    <BangLuongChiTiet
      :payload="payload"
      :loading="loading"
      :month="selectedMonth"
      daily-title="Bảng lương của tôi"
    >
      <template #actions>
        <CustomDatePicker
          v-model="selectedMonth"
          type="month"
          placeholder="Chọn tháng"
          format="MM/YYYY"
          value-format="YYYY-MM"
          :clearable="false"
          style="width: 160px"
          @change="loadData"
        />
        <CustomButton type="primary" plain :loading="loading" @click="loadData">
          <CustomIcon><Search /></CustomIcon>
          Xem bảng lương
        </CustomButton>
      </template>
    </BangLuongChiTiet>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { Search } from '@element-plus/icons-vue'
import { fetchBangLuongChiTietTheoNgay } from '@/api/tinhLuong'
import {
  CustomButton,
  CustomDatePicker,
  CustomIcon,
} from '@/components/element'
import BangLuongChiTiet from './BangLuongChiTiet.vue'

const loading = ref(false)
const selectedMonth = ref(currentMonthValue())
const payload = ref(null)

function currentMonthValue() {
  const now = new Date()
  const y = now.getFullYear()
  const m = String(now.getMonth() + 1).padStart(2, '0')
  return `${y}-${m}`
}

async function loadData() {
  if (!selectedMonth.value) {
    payload.value = null
    return
  }

  loading.value = true
  try {
    const { data } = await fetchBangLuongChiTietTheoNgay(
      { thang: selectedMonth.value },
      { skipLoading: true },
    )
    payload.value = data
  } catch {
    payload.value = null
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>
