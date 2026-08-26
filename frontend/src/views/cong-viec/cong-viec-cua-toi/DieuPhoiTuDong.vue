<template>
  <div class="dieu-phoi-tu-dong">
    <div class="filter-bar">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="8" :lg="5">
          <CustomInput
            v-model="filters.keyword"
            placeholder="Tìm theo mã HĐ, tên, SĐT khách hàng..."
            clearable
            style="width: 100%"
            @clear="onSearch"
            @keyup.enter="onSearch"
          >
            <template #prefix>
              <CustomIcon><Search /></CustomIcon>
            </template>
          </CustomInput>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="8" :lg="4">
          <CustomSelect
            v-model="filters.loai_hop_dong_id"
            placeholder="Loại hợp đồng"
            clearable
            filterable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="item in loaiHopDongOptions"
              :key="item.id"
              :label="item.ten_hop_dong"
              :value="item.id"
            />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="8" :lg="4">
          <el-date-picker
            v-model="filters.ngay_chup"
            type="date"
            placeholder="Ngày chụp"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            style="width: 100%"
            clearable
          />
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="8" :lg="4">
          <el-date-picker
            v-model="filters.ngay_tra_file_in"
            type="date"
            placeholder="Ngày trả file in"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            style="width: 100%"
            clearable
          />
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="8" :lg="4">
          <el-date-picker
            v-model="filters.ngay_tra_chinh_thuc"
            type="date"
            placeholder="Ngày trả chính thức"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            style="width: 100%"
            clearable
          />
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="8" :lg="3">
          <CustomButton type="primary" plain :loading="loading" @click="onSearch">
            Tìm kiếm
          </CustomButton>
        </CustomCol>
      </CustomRow>
    </div>

    <el-tabs v-model="activeTab" type="border-card" class="status-tabs" @tab-change="onTabChange">
      <el-tab-pane
        v-for="tab in tabs"
        :key="tab.name"
        :name="tab.name"
      >
        <template #label>
          <span class="tab-label">
            {{ tab.label }}
            <el-badge
              :value="tabCounts[tab.name]"
              :max="99"
              :hidden="!tabCounts[tab.name]"
              class="tab-badge"
            />
          </span>
        </template>
      </el-tab-pane>
    </el-tabs>

    <div v-loading="loading" class="tab-content tab-content--list">
      <div v-if="fileFilterOptions.length" class="step-file-filters">
        <el-checkbox-group
          :model-value="stepFileFilters"
          @change="onStepFileFilterChange"
        >
          <el-checkbox
            v-for="opt in fileFilterOptions"
            :key="opt.value"
            :value="opt.value"
          >
            {{ opt.label }}
          </el-checkbox>
        </el-checkbox-group>
      </div>

      <el-empty
        v-if="!loading && !items.length"
        :description="emptyDescription"
      />

      <template v-else-if="items.length">
        <CustomRow :gutter="16">
          <CustomCol
            v-for="item in items"
            :key="item.id"
            :xs="12"
            :sm="12"
            :md="8"
            :lg="6"
            :xl="6"
            class="card-col"
          >
            <DieuPhoiTuDongCard
              :item="item"
              :step="activeTab"
              @accepted="onAccepted"
              @updated="onItemUpdated"
              @status-changed="onAccepted"
            />
          </CustomCol>
        </CustomRow>

        <Pagination
          v-model="page"
          v-model:page-size="perPage"
          :total="total"
          :page-sizes="[12, 24, 48]"
          :disabled="loading"
          @change="loadItems"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { Search } from '@element-plus/icons-vue'
import { fetchCongViecDieuPhoiCuaToi } from '@/api/hopDongSuDungDichVu'
import { fetchLoaiHopDong } from '@/api/loaiHopDong'
import Pagination from '@/components/Pagination.vue'
import {
  CustomButton,
  CustomCol,
  CustomIcon,
  CustomInput,
  CustomOption,
  CustomRow,
  CustomSelect,
} from '@/components/element'
import DieuPhoiTuDongCard from './DieuPhoiTuDongCard.vue'

const tabs = [
  { name: 'tien_ky', label: 'Tiền kỳ' },
  { name: 'hau_ky', label: 'Hậu kỳ' },
  { name: 'gui_in', label: 'Gửi in' },
  { name: 'hoan_tat_san_xuat', label: 'Hoàn tất sản xuất' },
]

const STEP_FILE_FILTER_OPTIONS = {
  tien_ky: [
    { value: 'all', label: 'Tất cả' },
    { value: 'da_co_file_goc', label: 'Đã có file gốc' },
    { value: 'chua_co_file_goc', label: 'Chưa có file gốc' },
  ],
  hau_ky: [
    { value: 'all', label: 'Tất cả' },
    { value: 'da_co_file_goc', label: 'Đã có file gốc' },
    { value: 'chua_co_file_goc', label: 'Chưa có file gốc' },
    { value: 'da_co_file_le', label: 'Đã có file lẻ' },
    { value: 'chua_co_file_le', label: 'Chưa có file lẻ' },
    { value: 'da_co_file_in', label: 'Đã có file in' },
    { value: 'chua_co_file_in', label: 'Chưa có file in' },
  ],
}

const FILE_FILTER_PAIRS = {
  da_co_file_goc: 'chua_co_file_goc',
  chua_co_file_goc: 'da_co_file_goc',
  da_co_file_le: 'chua_co_file_le',
  chua_co_file_le: 'da_co_file_le',
  da_co_file_in: 'chua_co_file_in',
  chua_co_file_in: 'da_co_file_in',
}

const activeTab = ref('tien_ky')

const tabCounts = reactive({
  tien_ky: 0,
  hau_ky: 0,
  gui_in: 0,
  hoan_tat_san_xuat: 0,
})

const filters = reactive({
  keyword: '',
  loai_hop_dong_id: null,
  ngay_chup: '',
  ngay_tra_file_in: '',
  ngay_tra_chinh_thuc: '',
})

const loaiHopDongOptions = ref([])
const items = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(24)
const total = ref(0)
const stepFileFilters = ref(['all'])

const fileFilterOptions = computed(
  () => STEP_FILE_FILTER_OPTIONS[activeTab.value] || [],
)

const stepFileFilterActive = computed(
  () =>
    fileFilterOptions.value.length > 0 &&
    stepFileFilters.value.length > 0 &&
    !stepFileFilters.value.includes('all'),
)

const emptyDescription = computed(() => {
  if (stepFileFilterActive.value) {
    return 'Không có hợp đồng khớp bộ lọc'
  }
  const label = tabs.find((t) => t.name === activeTab.value)?.label || ''
  return label
    ? `Chưa có công việc — ${label}`
    : 'Chưa có công việc điều phối nào được gán cho bạn'
})

function stepFileQueryParams() {
  if (!stepFileFilterActive.value) return {}

  const selected = new Set(stepFileFilters.value)
  const params = {}
  if (selected.has('da_co_file_goc')) params.co_file_goc = 1
  else if (selected.has('chua_co_file_goc')) params.co_file_goc = 0
  if (activeTab.value === 'hau_ky') {
    if (selected.has('da_co_file_le')) params.co_file_le = 1
    else if (selected.has('chua_co_file_le')) params.co_file_le = 0
    if (selected.has('da_co_file_in')) params.co_file_in = 1
    else if (selected.has('chua_co_file_in')) params.co_file_in = 0
  }
  return params
}

function onStepFileFilterChange(values) {
  const prev = stepFileFilters.value
  let next = Array.isArray(values) ? [...values] : []
  const added = next.find((value) => !prev.includes(value))

  if (added === 'all' || next.length === 0) {
    next = ['all']
  } else {
    next = next.filter((value) => value !== 'all')
    if (added && FILE_FILTER_PAIRS[added]) {
      next = next.filter((value) => value !== FILE_FILTER_PAIRS[added])
    }
    if (!next.length) next = ['all']
  }

  stepFileFilters.value = next
  page.value = 1
  loadItems()
}

function applyTabCounts(counts) {
  if (!counts || typeof counts !== 'object') return
  for (const key of Object.keys(tabCounts)) {
    tabCounts[key] = Number(counts[key]) || 0
  }
}

async function loadLoaiHopDongOptions() {
  try {
    const { data } = await fetchLoaiHopDong({ per_page: 100, trang_thai: 'hoat_dong' })
    loaiHopDongOptions.value = data.data || []
  } catch {
    loaiHopDongOptions.value = []
  }
}

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchCongViecDieuPhoiCuaToi({
      page: page.value,
      per_page: perPage.value,
      ket_qua_trang_thai: activeTab.value,
      keyword: filters.keyword.trim() || undefined,
      loai_hop_dong_id: filters.loai_hop_dong_id || undefined,
      ngay_chup: filters.ngay_chup || undefined,
      ngay_tra_file_in: filters.ngay_tra_file_in || undefined,
      ngay_tra_chinh_thuc: filters.ngay_tra_chinh_thuc || undefined,
      ...stepFileQueryParams(),
    })
    items.value = data.data || []
    total.value = data.total || 0
    applyTabCounts(data.tab_counts)
  } catch {
    items.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

function onTabChange() {
  page.value = 1
  stepFileFilters.value = ['all']
  loadItems()
}

function onSearch() {
  page.value = 1
  loadItems()
}

function onAccepted() {
  loadItems()
}

function onItemUpdated(updated) {
  if (stepFileFilterActive.value) {
    loadItems()
    return
  }
  if (!updated?.id) {
    loadItems()
    return
  }
  const index = items.value.findIndex((row) => row.id === updated.id)
  if (index === -1) {
    loadItems()
    return
  }
  items.value[index] = { ...items.value[index], ...updated }
}

onMounted(() => {
  loadLoaiHopDongOptions()
  loadItems()
})
</script>

<style scoped lang="scss">
.dieu-phoi-tu-dong {
  .filter-bar {
    margin-bottom: 12px;

    :deep(.el-row) {
      row-gap: 12px;
    }
  }

  .status-tabs {
    :deep(.el-tabs__header) {
      margin-bottom: 0;
    }

    :deep(.el-tabs__content) {
      display: none;
    }

    :deep(.el-tabs__item) {
      height: auto;
      padding: 10px 16px;
      line-height: 1.4;
    }

    :deep(.el-tabs--border-card) {
      box-shadow: none;
    }
  }

  .tab-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .tab-badge {
    :deep(.el-badge__content) {
      position: static;
      transform: none;
    }
  }

  .tab-content {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 320px;
    padding: 16px;
    border: 1px solid var(--el-border-color-light);
    border-top: none;
    background: var(--el-bg-color);
    border-radius: 0 0 4px 4px;

    &--list {
      display: block;
      align-items: stretch;
      justify-content: flex-start;
    }
  }

  .step-file-filters {
    margin-bottom: 16px;
    padding: 10px 12px;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 6px;
    background: var(--el-fill-color-blank);

    :deep(.el-checkbox-group) {
      display: flex;
      flex-wrap: wrap;
      gap: 4px 16px;
    }

    :deep(.el-checkbox) {
      margin-right: 0;
      height: auto;
    }

    :deep(.el-checkbox__label) {
      font-size: 13px;
      line-height: 1.4;
    }
  }

  .card-col {
    margin-bottom: 16px;
  }
}
</style>
