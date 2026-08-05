<template>
  <CustomDialog
    v-model="visible"
    :width="720"
    :show-close="false"
    class="quick-search-dialog"
    @opened="onOpened"
    @closed="resetState"
  >
    <div class="quick-search">
      <div class="quick-search__input-wrap">
        <CustomIcon class="quick-search__input-icon"><Search /></CustomIcon>
        <input
          ref="inputRef"
          v-model="keyword"
          type="text"
          class="quick-search__input"
          placeholder="Tìm hợp đồng, trang phục, concept, dịch vụ, nhân viên..."
          autocomplete="off"
          @keydown.esc="visible = false"
        />
        <CustomTooltip content="Cấu hình mục tìm kiếm" placement="top">
          <button
            type="button"
            class="quick-search__config-btn"
            aria-label="Cấu hình mục tìm kiếm"
            @click="sourceSettings.openConfig()"
          >
            <el-icon :size="18"><Setting /></el-icon>
          </button>
        </CustomTooltip>
        <kbd class="quick-search__kbd">ESC</kbd>
      </div>

      <el-tabs v-model="activeTab" class="quick-search__tabs">
        <el-tab-pane label="Tất cả" name="all" />
        <el-tab-pane
          v-if="sourceSettings.isEnabled('functions')"
          label="Chức năng"
          name="functions"
        />
        <el-tab-pane
          v-for="source in enabledSources"
          :key="source.key"
          :label="source.label"
          :name="source.key"
        />
      </el-tabs>

      <div v-loading="loading && needsEntitySearch" class="quick-search__results">
        <template v-if="activeTab === 'all'">
          <section
            v-if="sourceSettings.isEnabled('functions') && functionResults.length"
            class="quick-search__section"
          >
            <h4 class="quick-search__section-title">Chức năng</h4>
            <button
              v-for="item in functionResults"
              :key="`fn-${item.path}`"
              type="button"
              class="quick-search__item"
              @click="goToFunction(item)"
            >
              <span class="quick-search__item-icon">
                <el-icon :size="18">
                  <component :is="resolveIcon(item.icon)" />
                </el-icon>
              </span>
              <span class="quick-search__item-body">
                <span class="quick-search__item-title">{{ item.title }}</span>
                <span v-if="item.group || item.parent" class="quick-search__item-meta">
                  {{ [item.group, item.parent].filter(Boolean).join(' · ') }}
                </span>
              </span>
              <el-icon class="quick-search__item-arrow"><ArrowRight /></el-icon>
            </button>
          </section>

          <section
            v-for="source in enabledSources"
            v-show="needsEntitySearch && (loading || entityResults[source.key]?.length)"
            :key="source.key"
            class="quick-search__section"
          >
            <h4 class="quick-search__section-title">{{ source.label }}</h4>
            <button
              v-for="item in entityResults[source.key] || []"
              :key="`${source.key}-${item.id}`"
              type="button"
              class="quick-search__item"
              @click="goToEntity(item)"
            >
              <el-avatar v-if="item.avatar" :size="36" class="quick-search__avatar">
                {{ avatarInitial(item.title) }}
              </el-avatar>
              <span v-else class="quick-search__item-icon">
                <el-icon :size="18">
                  <component :is="resolveIcon(source.icon)" />
                </el-icon>
              </span>
              <span class="quick-search__item-body">
                <span class="quick-search__item-title">{{ item.title }}</span>
                <span v-if="item.meta" class="quick-search__item-meta">{{ item.meta }}</span>
              </span>
              <el-icon class="quick-search__item-arrow"><ArrowRight /></el-icon>
            </button>
          </section>

          <el-empty
            v-if="showAllEmpty"
            :description="emptyAllDescription"
            :image-size="72"
          />
        </template>

        <template v-else-if="activeTab === 'functions'">
          <button
            v-for="item in functionResults"
            :key="item.path"
            type="button"
            class="quick-search__item"
            @click="goToFunction(item)"
          >
            <span class="quick-search__item-icon">
              <el-icon :size="18">
                <component :is="resolveIcon(item.icon)" />
              </el-icon>
            </span>
            <span class="quick-search__item-body">
              <span class="quick-search__item-title">{{ item.title }}</span>
              <span v-if="item.group || item.parent" class="quick-search__item-meta">
                {{ [item.group, item.parent].filter(Boolean).join(' · ') }}
              </span>
            </span>
            <el-icon class="quick-search__item-arrow"><ArrowRight /></el-icon>
          </button>

          <el-empty
            v-if="!functionResults.length"
            :description="keyword.trim() ? 'Không tìm thấy chức năng phù hợp' : 'Nhập từ khóa để tìm chức năng'"
            :image-size="72"
          />
        </template>

        <template v-else>
          <button
            v-for="item in activeEntityResults"
            :key="item.id"
            type="button"
            class="quick-search__item"
            @click="goToEntity(item)"
          >
            <el-avatar v-if="item.avatar" :size="36" class="quick-search__avatar">
              {{ avatarInitial(item.title) }}
            </el-avatar>
            <span v-else class="quick-search__item-icon">
              <el-icon :size="18">
                <component :is="resolveIcon(activeSource?.icon)" />
              </el-icon>
            </span>
            <span class="quick-search__item-body">
              <span class="quick-search__item-title">{{ item.title }}</span>
              <span v-if="item.meta" class="quick-search__item-meta">{{ item.meta }}</span>
            </span>
            <el-icon class="quick-search__item-arrow"><ArrowRight /></el-icon>
          </button>

          <el-empty
            v-if="!loading && !activeEntityResults.length"
            :description="
              keyword.trim()
                ? `Không tìm thấy ${activeSource?.label?.toLowerCase() || 'kết quả'} phù hợp`
                : `Nhập từ khóa để tìm ${activeSource?.label?.toLowerCase() || 'dữ liệu'}`
            "
            :image-size="72"
          />
        </template>
      </div>
    </div>
  </CustomDialog>

  <CustomDialog
    v-model="sourceSettings.dialogVisible"
    title="Cấu hình mục tìm kiếm"
    :width="560"
  >
    <p class="source-config-hint">
      Chọn các mục muốn tìm trong tìm kiếm nhanh. Cấu hình được lưu trên trình duyệt này.
    </p>
    <div class="source-config-list">
      <label
        v-for="opt in sourceSettings.options"
        :key="opt.key"
        class="source-config-item"
      >
        <el-checkbox v-model="sourceSettings.draft[opt.key]">
          <span class="source-config-item__label">
            <el-icon :size="16"><component :is="resolveIcon(opt.icon)" /></el-icon>
            {{ opt.label }}
          </span>
        </el-checkbox>
      </label>
    </div>
    <template #footer>
      <div class="source-config-footer">
        <div class="source-config-footer-left">
          <CustomButton @click="sourceSettings.selectAllDraft()">Chọn tất cả</CustomButton>
          <CustomButton @click="sourceSettings.clearDraft()">Bỏ chọn tất cả</CustomButton>
        </div>
        <div class="source-config-footer-right">
          <CustomButton @click="sourceSettings.dialogVisible = false">Hủy</CustomButton>
          <CustomButton type="primary" @click="saveSourceConfig">Lưu</CustomButton>
        </div>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import * as Icons from '@element-plus/icons-vue'
import { ArrowRight, Odometer, Search, Setting } from '@element-plus/icons-vue'
import { searchFunctions } from '@/utils/quickSearch'
import {
  ENTITY_TAB_KEYS,
  SEARCH_SOURCE_MAP,
  searchSources,
} from '@/utils/quickSearchEntities'
import { useQuickSearchSources } from '@/composables/useQuickSearchSources'
import CustomButton from '@/components/element/CustomButton.vue'
import CustomDialog from '@/components/element/CustomDialog.vue'
import CustomIcon from '@/components/element/CustomIcon.vue'
import CustomTooltip from '@/components/element/CustomTooltip.vue'

const visible = defineModel({ type: Boolean, default: false })

const router = useRouter()
const sourceSettings = useQuickSearchSources()
const inputRef = ref(null)
const keyword = ref('')
const activeTab = ref('all')
const loading = ref(false)
const entityResults = reactive(
  Object.fromEntries(ENTITY_TAB_KEYS.map((key) => [key, []]))
)

let searchTimer = null
let searchRequestId = 0

const enabledSources = computed(() => sourceSettings.enabledSources())

const enabledEntityKeys = computed(() => sourceSettings.enabledEntityKeys())

const functionResults = computed(() => {
  if (!sourceSettings.isEnabled('functions')) return []
  return searchFunctions(keyword.value)
})

const activeSource = computed(() => SEARCH_SOURCE_MAP[activeTab.value] || null)

const activeEntityResults = computed(() => entityResults[activeTab.value] || [])

const needsEntitySearch = computed(() => {
  const trimmed = keyword.value.trim()
  if (!trimmed) return false
  if (activeTab.value === 'all') return enabledEntityKeys.value.length > 0
  return enabledEntityKeys.value.includes(activeTab.value)
})

const emptyAllDescription = computed(() => {
  if (!keyword.value.trim()) {
    return sourceSettings.isEnabled('functions')
      ? 'Nhập từ khóa để tìm kiếm toàn bộ'
      : 'Nhập từ khóa để tìm kiếm'
  }
  return 'Không tìm thấy kết quả phù hợp'
})

const showAllEmpty = computed(() => {
  if (activeTab.value !== 'all' || loading.value) return false
  const hasFunctions =
    sourceSettings.isEnabled('functions') && functionResults.value.length > 0
  const hasEntities = enabledEntityKeys.value.some((key) => entityResults[key]?.length)
  if (!keyword.value.trim()) return !hasFunctions
  return !hasFunctions && !hasEntities
})

function resolveIcon(name) {
  return Icons[name] || Odometer
}

function avatarInitial(name) {
  const text = String(name || '').trim()
  return text ? text.charAt(0).toUpperCase() : '?'
}

function clearEntityResults() {
  for (const key of ENTITY_TAB_KEYS) {
    entityResults[key] = []
  }
}

function isTabAvailable(tab) {
  if (tab === 'all') return true
  if (tab === 'functions') return sourceSettings.isEnabled('functions')
  return enabledEntityKeys.value.includes(tab)
}

function ensureActiveTabAvailable() {
  if (!isTabAvailable(activeTab.value)) {
    activeTab.value = 'all'
  }
}

function resetState() {
  keyword.value = ''
  activeTab.value = 'all'
  clearEntityResults()
  loading.value = false
  clearTimeout(searchTimer)
}

function onOpened() {
  ensureActiveTabAvailable()
  nextTick(() => {
    inputRef.value?.focus()
    inputRef.value?.select()
  })
}

function closeAndNavigate(location) {
  visible.value = false
  router.push(location)
}

function goToFunction(item) {
  closeAndNavigate(item.path)
}

function goToEntity(item) {
  if (!item?.route) return
  closeAndNavigate(item.route)
}

function keysForTab(tab) {
  if (tab === 'all') return enabledEntityKeys.value
  if (enabledEntityKeys.value.includes(tab)) return [tab]
  return []
}

async function runEntitySearch(value, tab = activeTab.value) {
  const trimmed = value.trim()
  const keys = keysForTab(tab)

  if (!trimmed || !keys.length) {
    clearEntityResults()
    loading.value = false
    return
  }

  const requestId = ++searchRequestId
  loading.value = true

  const limit = tab === 'all' ? 5 : 10
  const results = await searchSources(keys, trimmed, limit)

  if (requestId !== searchRequestId) return

  for (const key of ENTITY_TAB_KEYS) {
    entityResults[key] = results[key] || []
  }
  loading.value = false
}

function scheduleEntitySearch(value) {
  const trimmed = value.trim()
  const keys = keysForTab(activeTab.value)

  if (!trimmed || !keys.length) {
    clearTimeout(searchTimer)
    clearEntityResults()
    loading.value = false
    return
  }

  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    runEntitySearch(value)
  }, 300)
}

function saveSourceConfig() {
  if (!sourceSettings.saveConfig()) return
  ensureActiveTabAvailable()
  if (keysForTab(activeTab.value).length && keyword.value.trim()) {
    runEntitySearch(keyword.value)
  } else {
    clearEntityResults()
  }
}

watch(keyword, scheduleEntitySearch)

watch(activeTab, (tab) => {
  if (keysForTab(tab).length && keyword.value.trim()) {
    runEntitySearch(keyword.value, tab)
  }
})

watch(visible, (open) => {
  if (open && keysForTab(activeTab.value).length && keyword.value.trim()) {
    runEntitySearch(keyword.value)
  }
})
</script>

<style scoped lang="scss">
.quick-search {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.quick-search__input-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 2px 12px;
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.quick-search__input-icon {
  color: var(--el-text-color-secondary);
  flex-shrink: 0;
}

.quick-search__input {
  flex: 1;
  min-width: 0;
  border: none;
  outline: none;
  background: transparent;
  font-size: 16px;
  color: var(--el-text-color-primary);

  &::placeholder {
    color: var(--el-text-color-placeholder);
  }
}

.quick-search__config-btn {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  background: transparent;
  color: var(--el-text-color-secondary);
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;

  &:hover {
    background: var(--el-fill-color-light);
    color: var(--el-color-primary);
  }
}

.quick-search__kbd {
  flex-shrink: 0;
  padding: 2px 6px;
  border-radius: 4px;
  border: 1px solid var(--el-border-color);
  background: var(--el-fill-color-light);
  font-size: 11px;
  line-height: 1.4;
  color: var(--el-text-color-secondary);
  font-family: inherit;
}

.quick-search__tabs {
  :deep(.el-tabs__header) {
    margin-bottom: 0;
  }

  :deep(.el-tabs__nav-wrap::after) {
    height: 1px;
  }

  :deep(.el-tabs__item) {
    padding: 0 12px;
    height: 36px;
    line-height: 36px;
    font-size: 13px;
  }
}

.quick-search__results {
  min-height: 280px;
  max-height: min(420px, 50vh);
  overflow-y: auto;
  padding: 8px 0 4px;
}

.quick-search__section + .quick-search__section {
  margin-top: 8px;
  padding-top: 4px;
  border-top: 1px solid var(--el-border-color-lighter);
}

.quick-search__section-title {
  margin: 0 0 4px;
  padding: 0 12px;
  font-size: 12px;
  font-weight: 600;
  color: var(--el-text-color-secondary);
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.quick-search__item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border: none;
  border-radius: 8px;
  background: transparent;
  text-align: left;
  cursor: pointer;
  transition: background 0.15s ease;

  &:hover {
    background: var(--el-fill-color-light);
  }
}

.quick-search__item-icon {
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: var(--el-color-primary-light-9);
  color: var(--el-color-primary);
  flex-shrink: 0;
}

.quick-search__avatar {
  flex-shrink: 0;
  background: var(--el-color-primary-light-7);
  color: var(--el-color-primary);
  font-weight: 600;
}

.quick-search__item-body {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.quick-search__item-title {
  font-size: 14px;
  font-weight: 500;
  color: var(--el-text-color-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.quick-search__item-meta {
  font-size: 12px;
  color: var(--el-text-color-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.quick-search__item-arrow {
  flex-shrink: 0;
  color: var(--el-text-color-placeholder);
}

.source-config-hint {
  margin: 0 0 12px;
  font-size: 13px;
  color: var(--el-text-color-secondary);
  line-height: 1.45;
}

.source-config-list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
  max-height: min(55vh, 360px);
  overflow: auto;
  padding-right: 4px;

  @media (max-width: 520px) {
    grid-template-columns: 1fr;
  }
}

.source-config-item {
  display: flex;
  align-items: flex-start;
  min-width: 0;
  padding: 8px 10px;
  border-radius: 6px;
  cursor: pointer;

  &:hover {
    background: var(--el-fill-color-light);
  }

  :deep(.el-checkbox) {
    width: 100%;
    height: auto;
    align-items: center;
    white-space: normal;
  }

  :deep(.el-checkbox__label) {
    white-space: normal;
    word-break: break-word;
    line-height: 1.35;
  }
}

.source-config-item__label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.source-config-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  flex-wrap: wrap;
}

.source-config-footer-left,
.source-config-footer-right {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
</style>

<style>
.quick-search-dialog .el-dialog__header {
  display: none;
}

.quick-search-dialog .el-dialog__body {
  padding-top: 16px;
}
</style>
