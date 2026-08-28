<template>
  <div class="ke-toan-thue-page">
    <el-empty v-if="!visibleTabs.length" description="Bạn chưa được phân quyền tab nào trên màn này." />

    <el-tabs v-else v-model="activeTab" class="page-tabs">
      <el-tab-pane v-if="hasTab('phieu-thu-chi')" label="Phiếu thu chi" name="phieu-thu-chi">
        <PhieuThuChi />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('hang-muc')" label="Hạng mục thu chi" name="hang-muc">
        <HangMucThuChi />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import HangMucThuChi from './ke-toan-thue/HangMucThuChi.vue'
import PhieuThuChi from './ke-toan-thue/PhieuThuChi.vue'
import { usePageTabs } from '@/composables/usePageTabs'

const { activeTab, visibleTabs, hasTab } = usePageTabs('/tai-chinh/ke-toan-thue')
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
