<template>
  <div class="trung-tam-phe-duyet">
    <h2 class="page-title">Trung tâm Phê duyệt</h2>
    <p class="page-desc">Duyệt các đơn đang chờ trên cùng một màn hình.</p>

    <div class="approval-sections">
      <DuyetNghiPhep />
      <DuyetDatMuaTrangPhuc />
      <DuyetPhieuThuChi />
    </div>
  </div>
</template>

<script setup>
import DuyetNghiPhep from './trung-tam-phe-duyet/DuyetNghiPhep.vue'
import DuyetDatMuaTrangPhuc from './trung-tam-phe-duyet/DuyetDatMuaTrangPhuc.vue'
import DuyetPhieuThuChi from './trung-tam-phe-duyet/DuyetPhieuThuChi.vue'
</script>

<style scoped lang="scss">
.trung-tam-phe-duyet {
  .page-title {
    margin: 0 0 4px;
    font-size: 20px;
    font-weight: 600;
    color: var(--el-text-color-primary);
  }

  .page-desc {
    margin: 0 0 16px;
    font-size: 14px;
    color: var(--el-text-color-secondary);
  }

  .approval-sections {
    display: flex;
    flex-direction: column;
    gap: 24px;
  }
}
</style>
