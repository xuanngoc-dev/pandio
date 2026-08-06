<template>
  <div class="concept-page">
    <h2 class="page-title">Concept</h2>

    <el-tabs v-model="activeTab" class="page-tabs">
      <el-tab-pane label="Concept" name="concept">
        <ConceptList :active="activeTab === 'concept'" />
      </el-tab-pane>
      <el-tab-pane label="Danh mục concept" name="danh-muc">
        <DanhMucConcept :active="activeTab === 'danh-muc'" />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import ConceptList from './concept/ConceptList.vue'
import DanhMucConcept from './concept/DanhMucConcept.vue'

const route = useRoute()
const allowedTabs = ['concept', 'danh-muc']
const initialTab = String(route.query.tab || 'concept')
const activeTab = ref(allowedTabs.includes(initialTab) ? initialTab : 'concept')
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
