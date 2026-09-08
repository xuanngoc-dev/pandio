<template>
  <div class="tong-quan">
    <el-tabs v-model="activeTab" class="page-tabs">
      <el-tab-pane label="CEO & Admin" name="ceo-admin">
        <CeoAdmin />
      </el-tab-pane>
      <el-tab-pane label="Kinh doanh" name="kinh-doanh">
        <KinhDoanh />
      </el-tab-pane>
      <el-tab-pane label="Marketing" name="marketing">
        <Marketing />
      </el-tab-pane>
      <el-tab-pane label="Sản xuất & điều phối" name="san-xuat-dieu-phoi">
        <SanXuatDieuPhoi />
      </el-tab-pane>
      <el-tab-pane label="Trang phục" name="trang-phuc">
        <TrangPhuc />
      </el-tab-pane>
      <el-tab-pane label="Tài chính & nhân sự" name="tai-chinh-nhan-su">
        <TaiChinhNhanSu />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import CeoAdmin from './tabs/CeoAdmin.vue'
import KinhDoanh from './tabs/KinhDoanh.vue'
import Marketing from './tabs/Marketing.vue'
import SanXuatDieuPhoi from './tabs/SanXuatDieuPhoi.vue'
import TrangPhuc from './tabs/TrangPhuc.vue'
import TaiChinhNhanSu from './tabs/TaiChinhNhanSu.vue'

const activeTab = ref('ceo-admin')
</script>

<style scoped lang="scss">
.tong-quan {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.page-head__title {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  line-height: 1.3;
  color: var(--el-text-color-primary);
}

.page-head__sub {
  margin: 2px 0 0;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.page-tabs {
  :deep(.el-tabs__header) {
    margin-bottom: 16px;
  }

  :deep(.el-tabs__content) {
    overflow: visible;
  }
}
</style>
