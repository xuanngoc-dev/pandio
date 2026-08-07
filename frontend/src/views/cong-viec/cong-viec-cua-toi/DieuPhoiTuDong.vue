<template>
  <div class="dieu-phoi-tu-dong">
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
      <el-empty
        v-if="!loading && !items.length"
        :description="emptyDescription"
      />

      <template v-else-if="items.length">
        <CustomRow :gutter="16">
          <CustomCol
            v-for="item in items"
            :key="item.id"
            :xs="24"
            :sm="12"
            :md="8"
            :lg="6"
            :xl="6"
            class="card-col"
          >
            <DieuPhoiTuDongCard
              :item="item"
              :show-nhan="activeTab === 'cho_nhan'"
              @accepted="onAccepted"
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
import { fetchCongViecDieuPhoiCuaToi } from '@/api/hopDongSuDungDichVu'
import Pagination from '@/components/Pagination.vue'
import { CustomCol, CustomRow } from '@/components/element'
import DieuPhoiTuDongCard from './DieuPhoiTuDongCard.vue'

const tabs = [
  { name: 'cho_nhan', label: 'Chờ nhận' },
  { name: 'dang_xu_ly', label: 'Đang xử lý' },
  { name: 'gui_khach_kiem_tra', label: 'Gửi khách kiểm tra' },
  { name: 'san_xuat_in_an', label: 'Sản xuất & in ấn' },
  { name: 'hoan_thanh', label: 'Hoàn thành' },
]

const activeTab = ref('cho_nhan')

const tabCounts = reactive({
  cho_nhan: 0,
  dang_xu_ly: 0,
  gui_khach_kiem_tra: 0,
  san_xuat_in_an: 0,
  hoan_thanh: 0,
})

const items = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(24)
const total = ref(0)

const emptyDescription = computed(() => {
  const label = tabs.find((t) => t.name === activeTab.value)?.label || ''
  return label
    ? `Chưa có công việc — ${label}`
    : 'Chưa có công việc điều phối nào được gán cho bạn'
})

function applyTabCounts(counts) {
  if (!counts || typeof counts !== 'object') return
  for (const key of Object.keys(tabCounts)) {
    tabCounts[key] = Number(counts[key]) || 0
  }
}

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchCongViecDieuPhoiCuaToi({
      page: page.value,
      per_page: perPage.value,
      ket_qua_trang_thai: activeTab.value,
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
  loadItems()
}

function onAccepted() {
  loadItems()
}

onMounted(() => {
  loadItems()
})
</script>

<style scoped lang="scss">
.dieu-phoi-tu-dong {
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

  .card-col {
    margin-bottom: 16px;
  }
}
</style>
