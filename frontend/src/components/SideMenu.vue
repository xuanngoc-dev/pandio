<template>
  <el-menu
    :default-active="activeMenu"
    :collapse="collapsed"
    router
    class="side-menu"
  >
    <div
      v-for="(group, groupIndex) in menuGroups"
      :key="group.header || `group-${groupIndex}`"
      class="menu-group"
    >
      <div v-if="group.header && !collapsed" class="menu-group__header">
        {{ group.header }}
      </div>

      <template v-for="item in group.items" :key="item.index">
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
    </div>
  </el-menu>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import * as Icons from '@element-plus/icons-vue'
import menuGroups from '@/data/menu.json'

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

.menu-group {
  & + & {
    margin-top: 4px;
  }

  &__header {
    padding: 16px 20px 6px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.04em;
    color: var(--el-text-color-secondary);
    line-height: 1.2;
    user-select: none;
  }
}
</style>
