<template>
  <div class="page-placeholder">
    <el-empty :description="description">
      <template #image>
        <el-icon :size="64" color="var(--el-color-info)">
          <Document />
        </el-icon>
      </template>
      <p class="hint">Trang đang chờ triển khai giao diện.</p>
    </el-empty>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Document } from '@element-plus/icons-vue'

const props = defineProps({
  title: {
    type: String,
    default: '',
  },
})

const route = useRoute()
const description = computed(() => props.title || route.meta.title || 'Đang phát triển')
</script>

<style scoped lang="scss">
.page-placeholder {
  min-height: 360px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hint {
  margin: 8px 0 0;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}
</style>
