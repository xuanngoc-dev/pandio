<template>
  <div class="lich-khach-hang-danh-sach page-list">
    <CustomCard shadow="hover">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách khách hàng</span>
        </div>
      </template>

      <el-empty description="Chưa có dữ liệu danh sách">
        <p class="hint">Tab danh sách đang chờ triển khai.</p>
      </el-empty>
    </CustomCard>
  </div>
</template>

<script setup>
</script>

<style scoped lang="scss">
.hint {
  margin: 8px 0 0;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}
</style>
