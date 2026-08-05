<template>
  <div class="trang-phuc-page">
    <!-- <h2 class="page-title">Trang phục</h2> -->

    <el-tabs v-model="activeTab" class="page-tabs">
      <el-tab-pane label="Trang phục" name="trang-phuc">
        <TrangPhucList />
      </el-tab-pane>
      <el-tab-pane label="Danh mục trang phục" name="danh-muc">
        <DanhMucTrangPhuc />
      </el-tab-pane>
      <el-tab-pane label="Nhà cung cấp trang phục" name="nha-cung-cap">
        <NhaCungCapTrangPhuc />
      </el-tab-pane>
      <el-tab-pane label="Đặt mua trang phục" name="dat-mua">
        <DatMuaTrangPhuc />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import DanhMucTrangPhuc from './trang-phuc/DanhMucTrangPhuc.vue'
import DatMuaTrangPhuc from './trang-phuc/DatMuaTrangPhuc.vue'
import NhaCungCapTrangPhuc from './trang-phuc/NhaCungCapTrangPhuc.vue'
import TrangPhucList from './trang-phuc/TrangPhucList.vue'

const route = useRoute()
const allowedTabs = ['trang-phuc', 'danh-muc', 'nha-cung-cap', 'dat-mua']
const initialTab = String(route.query.tab || 'trang-phuc')
const activeTab = ref(allowedTabs.includes(initialTab) ? initialTab : 'trang-phuc')
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
