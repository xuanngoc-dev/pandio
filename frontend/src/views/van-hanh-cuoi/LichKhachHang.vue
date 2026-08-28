<template>
  <div class="lich-khach-hang-page">
    <el-empty v-if="!visibleTabs.length" description="Bạn chưa được phân quyền tab nào trên màn này." />

    <el-tabs v-else v-model="activeTab" class="page-tabs">
      <el-tab-pane v-if="hasTab('lich')" label="Lịch" name="lich">
        <LichKhachHangCalendar v-model:date-range="dateRange" />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('danh-sach')" label="Danh sách" name="danh-sach" lazy>
        <LichKhachHangDanhSach v-model:date-range="dateRange" />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import LichKhachHangCalendar from './lich-khach-hang/LichKhachHangCalendar.vue'
import LichKhachHangDanhSach from './lich-khach-hang/LichKhachHangDanhSach.vue'
import { getPresetRange } from './lich-khach-hang/lichKhachHangDate'
import { usePageTabs } from '@/composables/usePageTabs'

const { activeTab, visibleTabs, hasTab } = usePageTabs('/van-hanh-cuoi/lich-khach-hang')

const dateRange = ref(getPresetRange('this_month'))
</script>

<style scoped lang="scss">
.lich-khach-hang-page {
  .page-tabs {
    :deep(.el-tabs__header) {
      margin-bottom: 16px;
    }
  }
}
</style>
