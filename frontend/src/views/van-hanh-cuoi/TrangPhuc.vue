<template>
  <div class="trang-phuc-page">
    <h2 class="page-title">Trang phục</h2>

    <el-tabs v-model="activeTab" class="page-tabs">
      <el-tab-pane label="Danh mục trang phục" name="danh-muc">
        <DanhMucTrangPhuc />
      </el-tab-pane>
      <el-tab-pane label="Nhà cung cấp trang phục" name="nha-cung-cap">
        <NhaCungCapTrangPhuc />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import DanhMucTrangPhuc from './trang-phuc/DanhMucTrangPhuc.vue'
import NhaCungCapTrangPhuc from './trang-phuc/NhaCungCapTrangPhuc.vue'

const activeTab = ref('danh-muc')
</script>

<style scoped lang="scss">
.trang-phuc-page {
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
