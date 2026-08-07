<template>
  <div class="dieu-phoi-tu-dong">
    <el-tabs v-model="activeTab" type="border-card" class="status-tabs">
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

        <div v-if="tab.name === 'tat-ca'" v-loading="loading" class="tab-content tab-content--list">
          <el-empty
            v-if="!loading && !items.length"
            description="Chưa có công việc điều phối nào được gán cho bạn"
          />

          <template v-else>
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
                <DieuPhoiTuDongCard :item="item" />
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

        <div v-else class="tab-content">
          <el-empty :description="`Chưa có dữ liệu — ${tab.label}`" />
        </div>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { fetchCongViecDieuPhoiCuaToi } from '@/api/hopDongSuDungDichVu'
import Pagination from '@/components/Pagination.vue'
import { CustomCol, CustomRow } from '@/components/element'
import DieuPhoiTuDongCard from './DieuPhoiTuDongCard.vue'

const tabs = [
  { name: 'tat-ca', label: 'Tất cả' },
  { name: 'cho-nhan', label: 'Chờ nhận' },
  { name: 'dang-xu-ly', label: 'Đang xử lý' },
  { name: 'gui-khach-kiem-tra', label: 'Gửi khách kiểm tra' },
  { name: 'san-xuat-in-an', label: 'Sản xuất & in ấn' },
  { name: 'hoan-thanh', label: 'Hoàn thành' },
]

const activeTab = ref('tat-ca')

const tabCounts = reactive({
  'tat-ca': 0,
  'cho-nhan': 0,
  'dang-xu-ly': 0,
  'gui-khach-kiem-tra': 0,
  'san-xuat-in-an': 0,
  'hoan-thanh': 0,
})

const items = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(24)
const total = ref(0)

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchCongViecDieuPhoiCuaToi({
      page: page.value,
      per_page: perPage.value,
    })
    items.value = data.data || []
    total.value = data.total || 0
    tabCounts['tat-ca'] = total.value
  } catch {
    items.value = []
    total.value = 0
    tabCounts['tat-ca'] = 0
  } finally {
    loading.value = false
  }
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
      padding: 16px;
      min-height: 320px;
    }

    :deep(.el-tabs__item) {
      height: auto;
      padding: 10px 16px;
      line-height: 1.4;
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
    min-height: 280px;

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
