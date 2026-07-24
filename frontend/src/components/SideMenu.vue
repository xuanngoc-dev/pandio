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
      :class="{ 'is-collapsible': isGroupCollapsible(group) }"
    >
      <button
        v-if="group.header && !collapsed && layoutStore.menuGroupCollapsible"
        type="button"
        class="menu-group__toggle"
        @click="toggleGroup(groupIndex)"
      >
        <span class="menu-group__header">{{ group.header }}</span>
        <el-icon class="menu-group__arrow" :class="{ 'is-open': isGroupOpen(groupIndex) }">
          <ArrowDown />
        </el-icon>
      </button>

      <div
        v-else-if="group.header && !collapsed"
        class="menu-group__header menu-group__header--static"
      >
        {{ group.header }}
      </div>

      <div
        v-show="!layoutStore.menuGroupCollapsible || !group.header || isGroupOpen(groupIndex) || collapsed"
        class="menu-group__items"
      >
        <template v-for="item in group.items" :key="item.index">
          <el-sub-menu v-if="item.children?.length" :index="item.index">
            <template #title>
              <el-icon>
                <component :is="resolveIcon(item.icon)" />
              </el-icon>
              <span v-show="!collapsed">{{ item.title }}</span>
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

          <el-menu-item
            v-else
            :index="item.index"
            :title="collapsed ? item.title : undefined"
          >
            <el-icon>
              <component :is="resolveIcon(item.icon)" />
            </el-icon>
            <span v-show="!collapsed">{{ item.title }}</span>
          </el-menu-item>
        </template>
      </div>
    </div>
  </el-menu>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import * as Icons from '@element-plus/icons-vue'
import { ArrowDown } from '@element-plus/icons-vue'
import menuGroups from '@/data/menu.json'
import { useLayoutStore } from '@/stores/layout'

const props = defineProps({
  collapsed: {
    type: Boolean,
    default: false,
  },
})

const route = useRoute()
const layoutStore = useLayoutStore()
/** Giữ menu cha active khi đang ở trang cấu hình con. */
const activeMenu = computed(() => {
  if (route.path.startsWith('/he-thong/cau-hinh-quan-tri')) {
    return '/he-thong/cau-hinh-quan-tri'
  }
  return route.path
})
const collapsed = computed(() => props.collapsed)

/** Các nhóm đang mở (theo index) */
const openGroups = ref(new Set())

function initOpenGroups() {
  const next = new Set()
  menuGroups.forEach((group, index) => {
    if (!group.header) return
    // Mở sẵn nhóm chứa route hiện tại
    const hasActive = group.items?.some(
      (item) =>
        item.index === route.path ||
        item.children?.some((child) => child.index === route.path)
    )
    if (hasActive || !layoutStore.menuGroupCollapsible) {
      next.add(index)
    }
  })
  // Nếu chưa có nhóm nào mở và đang collapsible → mở nhóm đầu có header
  if (layoutStore.menuGroupCollapsible && next.size === 0) {
    const first = menuGroups.findIndex((g) => g.header)
    if (first >= 0) next.add(first)
  }
  openGroups.value = next
}

initOpenGroups()

watch(
  () => layoutStore.menuGroupCollapsible,
  (enabled) => {
    if (!enabled) {
      openGroups.value = new Set(menuGroups.map((_, i) => i))
    } else {
      initOpenGroups()
    }
  }
)

watch(
  () => route.path,
  () => {
    if (!layoutStore.menuGroupCollapsible) return
    const activeIndex = menuGroups.findIndex((group) =>
      group.items?.some(
        (item) =>
          item.index === route.path ||
          item.children?.some((child) => child.index === route.path)
      )
    )
    if (activeIndex < 0) return
    if (layoutStore.menuUniqueOpened) {
      openGroups.value = new Set([activeIndex])
    } else {
      const next = new Set(openGroups.value)
      next.add(activeIndex)
      openGroups.value = next
    }
  }
)

function isGroupCollapsible(group) {
  return !!(group.header && layoutStore.menuGroupCollapsible)
}

function isGroupOpen(groupIndex) {
  return openGroups.value.has(groupIndex)
}

function toggleGroup(groupIndex) {
  const next = new Set(
    layoutStore.menuUniqueOpened ? [] : openGroups.value
  )
  if (openGroups.value.has(groupIndex) && !layoutStore.menuUniqueOpened) {
    next.delete(groupIndex)
  } else if (openGroups.value.has(groupIndex) && layoutStore.menuUniqueOpened) {
    // Cho phép đóng nhóm đang mở
    // next đã rỗng
  } else {
    next.add(groupIndex)
  }
  openGroups.value = next
}

function resolveIcon(name) {
  return Icons[name] || Icons.Menu
}
</script>

<style scoped lang="scss">
.side-menu {
  border-right: none;
  width: 100%;

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

  /* Collapse mode: chỉ icon — wrapper div làm hỏng CSS mặc định của Element Plus */
  &.el-menu--collapse {
    width: 64px;

    :deep(.el-menu-item),
    :deep(.el-sub-menu__title) {
      padding: 0 !important;
      justify-content: center;
    }

    :deep(.el-menu-item .el-icon),
    :deep(.el-sub-menu__title .el-icon) {
      margin: 0;
      width: 24px;
      text-align: center;
    }

    :deep(.el-menu-item span),
    :deep(.el-sub-menu__title span),
    :deep(.el-sub-menu__icon-arrow) {
      display: none !important;
      width: 0;
      height: 0;
      overflow: hidden;
      visibility: hidden;
    }
  }
}

.menu-group {
  & + & {
    margin-top: 4px;
  }

  &__toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 14px 16px 6px 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    color: inherit;
    text-align: left;

    &:hover .menu-group__header {
      color: var(--el-color-primary);
    }
  }

  &__header {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.04em;
    color: var(--el-text-color-secondary);
    line-height: 1.2;
    user-select: none;
    transition: color 0.15s ease;

    &--static {
      display: block;
      padding: 16px 20px 6px;
    }
  }

  &__arrow {
    font-size: 12px;
    color: var(--el-text-color-secondary);
    transition: transform 0.2s ease;
    flex-shrink: 0;

    &.is-open {
      transform: rotate(180deg);
    }
  }
}
</style>
