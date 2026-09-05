<template>
  <div class="dich-vu-page">
    <el-empty v-if="!visibleTabs.length" description="Bạn chưa được phân quyền tab nào trên màn này." />

    <el-tabs v-else v-model="activeTab" class="page-tabs">
      <el-tab-pane v-if="hasTab('dich-vu')" label="Dịch vụ" name="dich-vu">
        <DanhSachDichVuLe />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('nhom-dich-vu')" label="Nhóm dịch vụ (combo)" name="nhom-dich-vu">
        <NhomDichVu />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('loai-dich-vu')" label="Loại dịch vụ" name="loai-dich-vu">
        <LoaiDichVu />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import DanhSachDichVuLe from './dich-vu/DanhSachDichVuLe.vue'
import NhomDichVu from './dich-vu/NhomDichVu.vue'
import LoaiDichVu from './dich-vu/LoaiDichVu.vue'
import { usePageTabs } from '@/composables/usePageTabs'

const { activeTab, visibleTabs, hasTab } = usePageTabs('/van-hanh-cuoi/dich-vu', {
  useQuery: true,
})
</script>

<style scoped lang="scss">
.dich-vu-page {
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
