<template>
  <div class="thong-bao-page">
    <h2 class="page-title">Thông báo</h2>

    <el-tabs v-model="activeTab" class="page-tabs">
      <el-tab-pane label="Thông báo" name="thong-bao">
        <ThongBaoList />
      </el-tab-pane>
      <el-tab-pane label="Loại thông báo" name="loai-thong-bao">
        <LoaiThongBao />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import ThongBaoList from './thong-bao/ThongBaoList.vue'
import LoaiThongBao from './thong-bao/LoaiThongBao.vue'

const route = useRoute()
const allowedTabs = ['thong-bao', 'loai-thong-bao']
const initialTab = String(route.query.tab || 'thong-bao')
const activeTab = ref(allowedTabs.includes(initialTab) ? initialTab : 'thong-bao')
</script>

<style scoped lang="scss">
.thong-bao-page {
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
