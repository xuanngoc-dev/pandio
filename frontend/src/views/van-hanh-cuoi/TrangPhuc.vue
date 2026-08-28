<template>
  <div class="trang-phuc-page">
    <el-empty v-if="!visibleTabs.length" description="Bạn chưa được phân quyền tab nào trên màn này." />

    <el-tabs v-else v-model="activeTab" class="page-tabs">
      <el-tab-pane v-if="hasTab('trang-phuc')" label="Trang phục" name="trang-phuc">
        <TrangPhucList />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('danh-muc')" label="Danh mục trang phục" name="danh-muc">
        <DanhMucTrangPhuc />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('nha-cung-cap')" label="Nhà cung cấp trang phục" name="nha-cung-cap">
        <NhaCungCapTrangPhuc />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('dat-mua')" label="Đặt mua trang phục" name="dat-mua">
        <DatMuaTrangPhuc />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import DanhMucTrangPhuc from './trang-phuc/DanhMucTrangPhuc.vue'
import DatMuaTrangPhuc from './trang-phuc/DatMuaTrangPhuc.vue'
import NhaCungCapTrangPhuc from './trang-phuc/NhaCungCapTrangPhuc.vue'
import TrangPhucList from './trang-phuc/TrangPhucList.vue'
import { usePageTabs } from '@/composables/usePageTabs'

const { activeTab, visibleTabs, hasTab } = usePageTabs('/van-hanh-cuoi/trang-phuc', {
  useQuery: true,
})
</script>

<style scoped lang="scss">
.trang-phuc-page {
  .page-title {
    margin: 0;
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
