<template>
  <div class="dich-vu-page">
    <h2 class="page-title">Dịch vụ</h2>

    <el-tabs v-model="activeTab" class="page-tabs">
      <el-tab-pane label="Dịch vụ" name="dich-vu">
        <DanhSachDichVuLe />
      </el-tab-pane>
      <el-tab-pane label="Nhóm dịch vụ (combo)" name="nhom-dich-vu">
        <NhomDichVu />
      </el-tab-pane>
      <el-tab-pane label="Loại dịch vụ" name="loai-dich-vu">
        <LoaiDichVu />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import DanhSachDichVuLe from './dich-vu/DanhSachDichVuLe.vue'
import NhomDichVu from './dich-vu/NhomDichVu.vue'
import LoaiDichVu from './dich-vu/LoaiDichVu.vue'

const route = useRoute()
const allowedTabs = ['dich-vu', 'nhom-dich-vu', 'loai-dich-vu']
const initialTab = String(route.query.tab || 'dich-vu')
const activeTab = ref(allowedTabs.includes(initialTab) ? initialTab : 'dich-vu')
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
