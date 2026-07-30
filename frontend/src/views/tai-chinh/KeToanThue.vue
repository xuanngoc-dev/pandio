<template>
  <div class="ke-toan-thue-page">
    <el-tabs v-model="activeTab" class="page-tabs">
      <el-tab-pane label="Phiếu thu chi" name="phieu-thu-chi">
        <PhieuThuChi />
      </el-tab-pane>
      <el-tab-pane label="Hạng mục thu chi" name="hang-muc">
        <HangMucThuChi />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import HangMucThuChi from './ke-toan-thue/HangMucThuChi.vue'
import PhieuThuChi from './ke-toan-thue/PhieuThuChi.vue'

const activeTab = ref('phieu-thu-chi')
</script>

<style scoped lang="scss">
.ke-toan-thue-page {
  .page-tabs {
    :deep(.el-tabs__header) {
      margin-bottom: 16px;
    }
  }
}
</style>
