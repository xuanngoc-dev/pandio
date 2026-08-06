<template>
  <div class="cham-cong-nghi-phep">
    <h2 class="page-title">Chấm công · Nghỉ phép</h2>

    <el-tabs v-model="activeTab" class="page-tabs">
      <el-tab-pane label="Điểm danh" name="diem-danh">
        <DiemDanh :active="activeTab === 'diem-danh'" />
      </el-tab-pane>
      <el-tab-pane label="Chấm công tháng" name="cham-cong-thang">
        <ChamCongThang :active="activeTab === 'cham-cong-thang'" />
      </el-tab-pane>
      <el-tab-pane label="Đăng ký ca" name="dang-ky-ca">
        <DangKyCa :active="activeTab === 'dang-ky-ca'" />
      </el-tab-pane>
      <el-tab-pane label="Nghỉ phép" name="nghi-phep">
        <NghiPhep :active="activeTab === 'nghi-phep'" />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import DiemDanh from './cham-cong-nghi-phep/DiemDanh.vue'
import ChamCongThang from './cham-cong-nghi-phep/ChamCongThang.vue'
import DangKyCa from './cham-cong-nghi-phep/DangKyCa.vue'
import NghiPhep from './cham-cong-nghi-phep/NghiPhep.vue'

const activeTab = ref('diem-danh')
</script>

<style scoped lang="scss">
.cham-cong-nghi-phep {
  .page-title {
    margin: 0 0 16px;
    font-size: 20px;
    font-weight: 600;
    color: var(--el-text-color-primary);
  }

  .page-tabs {
    :deep(.el-tabs__header) {
      margin-bottom: 16px;
    }
  }
}
</style>
