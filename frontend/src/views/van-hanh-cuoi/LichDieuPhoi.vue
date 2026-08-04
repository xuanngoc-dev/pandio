<template>
  <div class="lich-dieu-phoi-page">
    <el-tabs v-model="activeTab" class="page-tabs">
      <el-tab-pane label="Lịch chụp - make" name="chup-make">
        <LichChupMake />
      </el-tab-pane>
      <el-tab-pane label="Lịch cưới" name="cuoi">
        <LichCuoi />
      </el-tab-pane>
      <el-tab-pane label="Lịch hậu kỳ" name="hau-ky">
        <LichHauKy />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import LichChupMake from './lich-dieu-phoi/LichChupMake.vue'
import LichCuoi from './lich-dieu-phoi/LichCuoi.vue'
import LichHauKy from './lich-dieu-phoi/LichHauKy.vue'

const activeTab = ref('chup-make')
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
