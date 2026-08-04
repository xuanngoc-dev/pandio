<template>
  <div class="lich-khach-hang-danh-sach">
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
.lich-khach-hang-danh-sach {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.card-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.hint {
  margin: 8px 0 0;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}
</style>
