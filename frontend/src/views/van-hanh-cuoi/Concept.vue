<template>
  <div class="concept-page">
    <h2 class="page-title">Concept</h2>

    <el-empty v-if="!visibleTabs.length" description="Bạn chưa được phân quyền tab nào trên màn này." />

    <el-tabs v-else v-model="activeTab" class="page-tabs">
      <el-tab-pane v-if="hasTab('concept')" label="Concept" name="concept">
        <ConceptList :active="activeTab === 'concept'" />
      </el-tab-pane>
      <el-tab-pane v-if="hasTab('danh-muc')" label="Danh mục concept" name="danh-muc">
        <DanhMucConcept :active="activeTab === 'danh-muc'" />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import ConceptList from './concept/ConceptList.vue'
import DanhMucConcept from './concept/DanhMucConcept.vue'
import { usePageTabs } from '@/composables/usePageTabs'

const { activeTab, visibleTabs, hasTab } = usePageTabs('/van-hanh-cuoi/concept', {
  useQuery: true,
})
</script>

<style scoped lang="scss">
.concept-page {
  .page-title {
    margin: 0 0 16px;
    font-size: 20px;
    font-weight: 600;
    color: var(--el-text-color-primary);
  }

  .page-tabs {
    :deep(.el-tabs__header) {
      margin-bottom: 16px;
    }
  }
}
</style>
