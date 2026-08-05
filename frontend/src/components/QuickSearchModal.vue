<template>
  <CustomDialog
    v-model="visible"
    :width="680"
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
          placeholder="Tìm kiếm chức năng, nhân viên..."
          autocomplete="off"
          @keydown.esc="visible = false"
        />
        <kbd class="quick-search__kbd">ESC</kbd>
      </div>

      <el-tabs v-model="activeTab" class="quick-search__tabs">
        <el-tab-pane label="Tất cả" name="all" />
        <el-tab-pane label="Chức năng" name="functions" />
        <el-tab-pane label="Nhân viên" name="employees" />
      </el-tabs>

      <div v-loading="loading && needsEmployeeSearch" class="quick-search__results">
        <template v-if="activeTab === 'all'">
          <section v-if="functionResults.length" class="quick-search__section">
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
            v-if="needsEmployeeSearch && (loading || employeeResults.length)"
            class="quick-search__section"
          >
            <h4 class="quick-search__section-title">Nhân viên</h4>
            <button
              v-for="employee in employeeResults"
              :key="`emp-${employee.id}`"
              type="button"
              class="quick-search__item"
              @click="goToEmployee(employee)"
            >
              <el-avatar :size="36" class="quick-search__avatar">
                {{ avatarInitial(employee.name) }}
              </el-avatar>
              <span class="quick-search__item-body">
                <span class="quick-search__item-title">{{ employee.name }}</span>
                <span class="quick-search__item-meta">
                  {{ [employee.email, employee.phone, deptName(employee)].filter(Boolean).join(' · ') }}
                </span>
              </span>
              <el-icon class="quick-search__item-arrow"><ArrowRight /></el-icon>
            </button>
          </section>

          <el-empty
            v-if="showAllEmpty"
            :description="keyword.trim() ? 'Không tìm thấy kết quả phù hợp' : 'Nhập từ khóa để tìm kiếm toàn bộ'"
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
            v-for="employee in employeeResults"
            :key="employee.id"
            type="button"
            class="quick-search__item"
            @click="goToEmployee(employee)"
          >
            <el-avatar :size="36" class="quick-search__avatar">
              {{ avatarInitial(employee.name) }}
            </el-avatar>
            <span class="quick-search__item-body">
              <span class="quick-search__item-title">{{ employee.name }}</span>
              <span class="quick-search__item-meta">
                {{ [employee.email, employee.phone, deptName(employee)].filter(Boolean).join(' · ') }}
              </span>
            </span>
            <el-icon class="quick-search__item-arrow"><ArrowRight /></el-icon>
          </button>

          <el-empty
            v-if="!loading && !employeeResults.length"
            :description="keyword.trim() ? 'Không tìm thấy nhân viên phù hợp' : 'Nhập tên, email hoặc SĐT để tìm nhân viên'"
            :image-size="72"
          />
        </template>
      </div>
    </div>
  </CustomDialog>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import * as Icons from '@element-plus/icons-vue'
import { ArrowRight, Search, Odometer } from '@element-plus/icons-vue'
import { fetchUsers } from '@/api/users'
import { searchFunctions } from '@/utils/quickSearch'
import CustomDialog from '@/components/element/CustomDialog.vue'
import CustomIcon from '@/components/element/CustomIcon.vue'

const visible = defineModel({ type: Boolean, default: false })

const router = useRouter()
const inputRef = ref(null)
const keyword = ref('')
const activeTab = ref('all')
const loading = ref(false)
const employeeResults = ref([])

let searchTimer = null
let searchRequestId = 0

const functionResults = computed(() => searchFunctions(keyword.value))

const needsEmployeeSearch = computed(
  () =>
    keyword.value.trim() &&
    (activeTab.value === 'employees' || activeTab.value === 'all')
)

const showAllEmpty = computed(() => {
  if (activeTab.value !== 'all' || loading.value) return false
  const hasFunctions = functionResults.value.length > 0
  const hasEmployees = needsEmployeeSearch.value && employeeResults.value.length > 0
  return !hasFunctions && !hasEmployees
})

function resolveIcon(name) {
  return Icons[name] || Odometer
}

function avatarInitial(name) {
  const text = String(name || '').trim()
  return text ? text.charAt(0).toUpperCase() : '?'
}

function deptName(row) {
  const list = row?.nhan_vien?.phong_bans
  if (Array.isArray(list) && list.length) {
    return list.map((pb) => pb.ten_phong_ban).filter(Boolean).join(', ')
  }
  return ''
}

function resetState() {
  keyword.value = ''
  activeTab.value = 'all'
  employeeResults.value = []
  loading.value = false
  clearTimeout(searchTimer)
}

function onOpened() {
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

function goToEmployee(employee) {
  closeAndNavigate({
    name: 'nhan-su-danh-sach',
    query: { keyword: employee.name },
  })
}

async function searchEmployees(value) {
  const trimmed = value.trim()
  if (!trimmed) {
    employeeResults.value = []
    loading.value = false
    return
  }

  const requestId = ++searchRequestId
  loading.value = true

  try {
    const { data } = await fetchUsers({
      page: 1,
      per_page: 10,
      keyword: trimmed,
    })

    if (requestId !== searchRequestId) return
    employeeResults.value = data.data || []
  } catch {
    if (requestId !== searchRequestId) return
    employeeResults.value = []
  } finally {
    if (requestId === searchRequestId) {
      loading.value = false
    }
  }
}

function scheduleEmployeeSearch(value) {
  if (!value.trim()) {
    clearTimeout(searchTimer)
    employeeResults.value = []
    loading.value = false
    return
  }

  if (activeTab.value !== 'employees' && activeTab.value !== 'all') return

  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    searchEmployees(value)
  }, 300)
}

watch(keyword, scheduleEmployeeSearch)

watch(activeTab, (tab) => {
  if ((tab === 'employees' || tab === 'all') && keyword.value.trim()) {
    searchEmployees(keyword.value)
  }
})

watch(visible, (open) => {
  if (
    open &&
    (activeTab.value === 'employees' || activeTab.value === 'all') &&
    keyword.value.trim()
  ) {
    searchEmployees(keyword.value)
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
</style>

<style>
.quick-search-dialog .el-dialog__header {
  display: none;
}

.quick-search-dialog .el-dialog__body {
  padding-top: 16px;
}
</style>
