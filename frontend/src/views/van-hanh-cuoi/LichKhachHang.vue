<template>
  <div class="lich-khach-hang-page">
    <el-tabs v-model="activeTab" class="page-tabs">
      <el-tab-pane label="Lịch" name="lich">
        <LichKhachHangCalendar />
      </el-tab-pane>
      <el-tab-pane label="Danh sách" name="danh-sach">
        <LichKhachHangDanhSach />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import LichKhachHangCalendar from './lich-khach-hang/LichKhachHangCalendar.vue'
import LichKhachHangDanhSach from './lich-khach-hang/LichKhachHangDanhSach.vue'

const activeTab = ref('lich')
</script>

<style scoped lang="scss">
.lich-khach-hang-page {
  .page-tabs {
    :deep(.el-tabs__header) {
      margin-bottom: 16px;
    }
  }
}
</style>
