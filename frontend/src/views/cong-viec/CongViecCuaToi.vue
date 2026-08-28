<template>
  <div class="cong-viec-cua-toi-page">
    <el-empty v-if="!visibleTabs.length" description="Bạn chưa được phân quyền tab nào trên màn này." />

    <el-tabs v-else v-model="activeTab" class="page-tabs">
      <el-tab-pane v-if="hasTab('dieu-phoi-tu-dong')" label="Điều phối tự động" name="dieu-phoi-tu-dong">
        <DieuPhoiTuDong />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('cong-viec-ca-nhan')" label="Công việc cá nhân" name="cong-viec-ca-nhan">
        <CongViecCaNhan />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import DieuPhoiTuDong from './cong-viec-cua-toi/DieuPhoiTuDong.vue'
import CongViecCaNhan from './cong-viec-cua-toi/CongViecCaNhan.vue'
import { usePageTabs } from '@/composables/usePageTabs'

const { activeTab, visibleTabs, hasTab } = usePageTabs('/cong-viec-cua-toi')
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
