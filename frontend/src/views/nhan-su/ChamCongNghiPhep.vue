<template>
  <div class="cham-cong-nghi-phep">
    <h2 class="page-title">Chấm công · Nghỉ phép</h2>

    <el-empty v-if="!visibleTabs.length" description="Bạn chưa được phân quyền tab nào trên màn này." />

    <el-tabs v-else v-model="activeTab" class="page-tabs">
      <el-tab-pane v-if="hasTab('diem-danh')" label="Điểm danh" name="diem-danh">
        <DiemDanh :active="activeTab === 'diem-danh'" />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('cham-cong-thang')" label="Chấm công tháng" name="cham-cong-thang">
        <ChamCongThang :active="activeTab === 'cham-cong-thang'" />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('dang-ky-ca')" label="Đăng ký ca" name="dang-ky-ca">
        <DangKyCa :active="activeTab === 'dang-ky-ca'" />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('nghi-phep')" label="Nghỉ phép" name="nghi-phep">
        <NghiPhep :active="activeTab === 'nghi-phep'" />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import DiemDanh from './cham-cong-nghi-phep/DiemDanh.vue'
import ChamCongThang from './cham-cong-nghi-phep/ChamCongThang.vue'
import DangKyCa from './cham-cong-nghi-phep/DangKyCa.vue'
import NghiPhep from './cham-cong-nghi-phep/NghiPhep.vue'
import { usePageTabs } from '@/composables/usePageTabs'

const { activeTab, visibleTabs, hasTab } = usePageTabs('/nhan-su/cham-cong-nghi-phep')
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
