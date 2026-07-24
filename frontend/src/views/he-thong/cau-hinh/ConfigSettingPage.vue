<template>
  <div class="config-setting-page">
    <div class="config-setting-page__header">
      <el-button text class="back-btn" @click="goBack">
        <el-icon><ArrowLeft /></el-icon>
        Quay lại cấu hình
      </el-button>
      <h2 class="config-setting-page__title">{{ title }}</h2>
    </div>

    <div class="config-setting-page__body">
      <slot>
        <PagePlaceholder :title="title" />
      </slot>
    </div>
  </div>
</template>

<script setup>
import { ArrowLeft } from '@element-plus/icons-vue'
import { useRouter } from 'vue-router'
import PagePlaceholder from '@/components/PagePlaceholder.vue'

defineProps({
  title: {
    type: String,
    required: true,
  },
})

const router = useRouter()

function goBack() {
  router.push({ name: 'cau-hinh-quan-tri' })
}
</script>

<style scoped lang="scss">
.config-setting-page {
  &__header {
    margin-bottom: 16px;
  }

  &__title {
    margin: 8px 0 0;
    font-size: 20px;
    font-weight: 600;
    color: var(--el-text-color-primary);
  }
}

.back-btn {
  padding-left: 0;
  color: var(--el-color-primary);
}
</style>
