<template>
  <div class="lich-dieu-phoi-page">
    <el-empty v-if="!visibleTabs.length" description="Bạn chưa được phân quyền tab nào trên màn này." />

    <el-tabs v-else v-model="activeTab" class="page-tabs">
      <el-tab-pane v-if="hasTab('chup-make')" label="Lịch chụp - make" name="chup-make">
        <LichChupMake />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('hau-ky')" label="Lịch hậu kỳ" name="hau-ky">
        <LichHauKy />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('cuoi')" label="Lịch cưới" name="cuoi">
        <LichCuoi />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import LichChupMake from './lich-dieu-phoi/LichChupMake.vue'
import LichCuoi from './lich-dieu-phoi/LichCuoi.vue'
import LichHauKy from './lich-dieu-phoi/LichHauKy.vue'
import { usePageTabs } from '@/composables/usePageTabs'

const { activeTab, visibleTabs, hasTab } = usePageTabs('/van-hanh-cuoi/lich-dieu-phoi')
</script>

<style scoped lang="scss">
.lich-dieu-phoi-page {
  .page-tabs {
    :deep(.el-tabs__header) {
      margin-bottom: 16px;
    }
  }
}
</style>
