<template>
  <div class="thong-bao-page">
    <h2 class="page-title">Thông báo</h2>

    <el-empty v-if="!visibleTabs.length" description="Bạn chưa được phân quyền tab nào trên màn này." />

    <el-tabs v-else v-model="activeTab" class="page-tabs">
      <el-tab-pane v-if="hasTab('thong-bao')" label="Thông báo" name="thong-bao">
        <ThongBaoList />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('loai-thong-bao')" label="Loại thông báo" name="loai-thong-bao">
        <LoaiThongBao />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import ThongBaoList from './thong-bao/ThongBaoList.vue'
import LoaiThongBao from './thong-bao/LoaiThongBao.vue'
import { usePageTabs } from '@/composables/usePageTabs'

const { activeTab, visibleTabs, hasTab } = usePageTabs('/he-thong/thong-bao', {
  useQuery: true,
})
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
