<template>
  <div class="cong-viec-cua-toi-page">
    <el-tabs v-model="activeTab" class="page-tabs">
      <el-tab-pane label="Điều phối tự động" name="dieu-phoi-tu-dong">
        <DieuPhoiTuDong />
      </el-tab-pane>
      <el-tab-pane label="Công việc cá nhân" name="cong-viec-ca-nhan">
        <CongViecCaNhan />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import DieuPhoiTuDong from './cong-viec-cua-toi/DieuPhoiTuDong.vue'
import CongViecCaNhan from './cong-viec-cua-toi/CongViecCaNhan.vue'

const activeTab = ref('dieu-phoi-tu-dong')
</script>

<style scoped lang="scss">
.cong-viec-cua-toi-page {
  .page-tabs {
    :deep(.el-tabs__header) {
      margin-bottom: 16px;
    }
  }
}
</style>
