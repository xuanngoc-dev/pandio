<template>
  <div class="cau-hinh-quan-tri">
    <div class="config-grid">
      <article
        v-for="section in cauHinhSections"
        :key="section.key"
        class="config-card"
      >
        <header class="config-card__header">
          <el-icon class="config-card__icon" :size="18">
            <component :is="resolveIcon(section.icon)" />
          </el-icon>
          <h3 class="config-card__title">{{ section.title }}</h3>
        </header>

        <ul class="config-card__list">
          <li v-for="item in section.items" :key="item.routeName">
            <router-link
              :to="{ name: item.routeName }"
              class="config-card__link"
            >
              <el-icon class="config-card__chevron" :size="12">
                <ArrowRight />
              </el-icon>
              <span>{{ item.label }}</span>
            </router-link>
          </li>
        </ul>
      </article>
    </div>
  </div>
</template>

<script setup>
import * as Icons from '@element-plus/icons-vue'
import { ArrowRight } from '@element-plus/icons-vue'
import { cauHinhSections } from '@/data/cauHinhQuanTri'

function resolveIcon(name) {
  return Icons[name] || Icons.Setting
}
</script>

<style scoped lang="scss">
.cau-hinh-quan-tri {
  width: 100%;
}

.config-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  column-gap: 20px;
  row-gap: 24px;
  align-items: stretch;
}

.config-card {
  min-height: 100%;
  display: flex;
  flex-direction: column;
  background: var(--el-bg-color);
  border-radius: 10px;
  padding: 20px 22px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06), 0 4px 14px rgba(15, 23, 42, 0.04);
  border: 1px solid var(--el-border-color-lighter);
  box-sizing: border-box;

  &__header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
  }

  &__icon {
    color: var(--el-color-primary);
    flex-shrink: 0;
  }

  &__title {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: var(--el-text-color-primary);
    line-height: 1.3;
  }

  &__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  &__link {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    padding: 6px 4px;
    border-radius: 6px;
    color: var(--el-text-color-regular);
    font-size: 13.5px;
    line-height: 1.45;
    text-decoration: none;
    transition: color 0.15s ease, background-color 0.15s ease;

    &:hover {
      color: var(--el-color-primary);
      background: var(--el-color-primary-light-9);
    }
  }

  &__chevron {
    margin-top: 3px;
    color: var(--el-text-color-placeholder);
    flex-shrink: 0;
  }

  &__link:hover &__chevron {
    color: var(--el-color-primary);
  }
}

@media (max-width: 1100px) {
  .config-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .config-grid {
    grid-template-columns: 1fr;
  }
}
</style>
