<template>
  <div class="tong-quan">
    <el-empty v-if="!visibleTabs.length" description="Bạn chưa được phân quyền tab nào trên màn này." />

    <el-tabs v-else v-model="activeTab" class="page-tabs">
      <el-tab-pane v-if="hasTab('ceo-admin')" label="CEO & Admin" name="ceo-admin">
        <CeoAdmin />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('kinh-doanh')" label="Kinh doanh" name="kinh-doanh">
        <KinhDoanh />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('marketing')" label="Marketing" name="marketing">
        <Marketing />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('san-xuat-dieu-phoi')" label="Sản xuất & điều phối" name="san-xuat-dieu-phoi">
        <SanXuatDieuPhoi />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('trang-phuc')" label="Trang phục" name="trang-phuc">
        <TrangPhuc />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('tai-chinh-nhan-su')" label="Tài chính & nhân sự" name="tai-chinh-nhan-su">
        <TaiChinhNhanSu />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import CeoAdmin from './tabs/CeoAdmin.vue'
import KinhDoanh from './tabs/KinhDoanh.vue'
import Marketing from './tabs/Marketing.vue'
import SanXuatDieuPhoi from './tabs/SanXuatDieuPhoi.vue'
import TrangPhuc from './tabs/TrangPhuc.vue'
import TaiChinhNhanSu from './tabs/TaiChinhNhanSu.vue'
import { usePageTabs } from '@/composables/usePageTabs'

const { activeTab, visibleTabs, hasTab } = usePageTabs('/tong-quan')
</script>

<style scoped lang="scss">
.tong-quan {
  display: flex;
  flex-direction: column;
  gap: 12px;
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
