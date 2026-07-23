<template>
  <el-menu
    :default-active="activeMenu"
    :collapse="collapsed"
    router
    class="side-menu"
  >
    <template v-for="item in menuItems" :key="item.index">
      <el-sub-menu v-if="item.children?.length" :index="item.index">
        <template #title>
          <el-icon>
            <component :is="resolveIcon(item.icon)" />
          </el-icon>
          <span>{{ item.title }}</span>
        </template>
        <el-menu-item
          v-for="child in item.children"
          :key="child.index"
          :index="child.index"
        >
          <el-icon v-if="child.icon">
            <component :is="resolveIcon(child.icon)" />
          </el-icon>
          <span>{{ child.title }}</span>
        </el-menu-item>
      </el-sub-menu>

      <el-menu-item v-else :index="item.index">
        <el-icon>
          <component :is="resolveIcon(item.icon)" />
        </el-icon>
        <span>{{ item.title }}</span>
      </el-menu-item>
    </template>
  </el-menu>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import * as Icons from '@element-plus/icons-vue'
import menuItems from '@/data/menu.json'

defineProps({
  collapsed: {
    type: Boolean,
    default: false,
  },
})

const route = useRoute()
const activeMenu = computed(() => route.path)

function resolveIcon(name) {
  return Icons[name] || Icons.Menu
}
</script>

<style scoped lang="scss">
.side-menu {
  border-right: none;

  :deep(.el-menu-item),
  :deep(.el-sub-menu__title) {
    height: 40px;
    line-height: 40px;
  }

  :deep(.el-sub-menu .el-menu-item) {
    height: 36px;
    line-height: 36px;
    padding-left: 48px !important;
  }
}
</style>
