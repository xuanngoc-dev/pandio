<template>
  <div class="tab-panel">
    <CustomCard shadow="hover">
      <div class="placeholder">
        <h3 class="placeholder__title">Đăng ký ca</h3>
        <p class="placeholder__desc">Giao diện đăng ký ca sẽ được bổ sung sau.</p>
      </div>
    </CustomCard>
  </div>
</template>

<script setup>
import { CustomCard } from '@/components/element'
</script>

<style scoped>
.placeholder {
  padding: 32px 16px;
  text-align: center;
}

.placeholder__title {
  margin: 0 0 8px;
  font-size: 16px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.placeholder__desc {
  margin: 0;
  color: var(--el-text-color-secondary);
}
</style>
