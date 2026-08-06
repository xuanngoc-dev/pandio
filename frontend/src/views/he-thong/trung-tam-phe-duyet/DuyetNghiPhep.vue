<template>
  <div class="duyet-nghi-phep page-list">
    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="section-header">
          <span class="card-title">
            Đơn xin nghỉ phép chờ duyệt
            <el-badge
              v-if="total > 0"
              :value="total"
              :max="99"
              class="title-badge"
            />
          </span>
          <div class="filter-toolbar">
            <CustomInput
              v-model="keyword"
              class="filter-toolbar__search"
              placeholder="Tìm nhân viên, lý do..."
              clearable
              @clear="onSearch"
              @keyup.enter="onSearch"
            >
              <template #prefix>
                <CustomIcon><Search /></CustomIcon>
              </template>
            </CustomInput>
            <CustomSelect
              v-model="loaiFilter"
              class="filter-toolbar__select filter-toolbar__select--loai"
              placeholder="Loại nghỉ"
              clearable
              @change="onSearch"
            >
              <CustomOption
                v-for="item in LOAI_NGHI_PHEP_OPTIONS"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </CustomSelect>
            <BulkActionBar class="filter-toolbar__actions" :actions="bulkActions" @action="onBulkAction" />
          </div>
        </div>
      </template>

      <CustomTable
        v-loading="loading"
        :data="items"
        stripe
        row-key="id"
        style="width: 100%"
        @selection-change="onSelectionChange"
      >
        <CustomTableColumn type="selection" width="48" align="center" />
        <CustomTableColumn label="STT" width="60" align="center">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Nhân viên" min-width="160" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.user?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Loại nghỉ" min-width="140">
          <template #default="{ row }">
            {{ loaiLabel(row.loai_nghi_phep) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Buổi nghỉ" width="110" align="center">
          <template #default="{ row }">
            {{ buoiLabel(row.buoi_nghi) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Thời gian" min-width="160" align="center">
          <template #default="{ row }">
            {{ formatThoiGian(row) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Lý do" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.ly_do || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Thao tác" width="120" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip content="Duyệt" placement="top">
                <CustomButton
                  type="success"
                  link
                  :icon="CircleCheck"
                  :loading="processingId === row.id"
                  @click="confirmAction('da_duyet', [row.id])"
                />
              </CustomTooltip>
              <CustomTooltip content="Từ chối" placement="top">
                <CustomButton
                  type="warning"
                  link
                  :icon="CircleClose"
                  :loading="processingId === row.id"
                  @click="confirmAction('tu_choi', [row.id])"
                />
              </CustomTooltip>
            </div>
          </template>
        </CustomTableColumn>
      </CustomTable>

      <Pagination
        v-model="page"
        v-model:page-size="perPage"
        :total="total"
        :disabled="loading"
        @change="loadItems"
      />
    </CustomCard>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { CircleCheck, CircleClose, Search } from '@element-plus/icons-vue'
import {
  bulkDuyetXinNghiPhep,
  bulkTuChoiXinNghiPhep,
  duyetXinNghiPhep,
  fetchXinNghiPhep,
  tuChoiXinNghiPhep,
} from '@/api/xinNghiPhep'
import BulkActionBar from '@/components/BulkActionBar.vue'
import {
  CustomButton,
  CustomCard,
  CustomIcon,
  CustomInput,
  CustomOption,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'

const emit = defineEmits(['changed', 'count-change'])

const LOAI_NGHI_PHEP_OPTIONS = [
  { value: 'di_muon', label: 'Đi muộn' },
  { value: 've_som', label: 'Về sớm' },
  { value: 'nghi_nua_ngay', label: 'Nghỉ nửa ngày' },
  { value: 'nghi_1_ngay', label: 'Nghỉ 1 ngày' },
  { value: 'nghi_nhieu_ngay', label: 'Nghỉ nhiều ngày' },
]

const BUOI_NGHI_OPTIONS = [
  { value: 'sang', label: 'Buổi sáng' },
  { value: 'chieu', label: 'Buổi chiều' },
]

const items = ref([])
const loading = ref(false)
const processingId = ref(null)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const loaiFilter = ref('')
const selectedRows = ref([])
const bulkApproving = ref(false)
const bulkRejecting = ref(false)

const selectedCount = computed(() => selectedRows.value.length)

const bulkActions = computed(() => [
  {
    key: 'tu_choi',
    label: 'Từ chối',
    type: 'warning',
    badge: selectedCount.value,
    badgeType: 'warning',
    loading: bulkRejecting.value,
    tooltip: selectedCount.value
      ? `Từ chối ${selectedCount.value} đơn đã chọn`
      : 'Chọn đơn để từ chối',
  },
  {
    key: 'da_duyet',
    label: 'Duyệt',
    type: 'success',
    badge: selectedCount.value,
    badgeType: 'success',
    loading: bulkApproving.value,
    tooltip: selectedCount.value
      ? `Duyệt ${selectedCount.value} đơn đã chọn`
      : 'Chọn đơn để duyệt',
  },
])

function loaiLabel(value) {
  return LOAI_NGHI_PHEP_OPTIONS.find((item) => item.value === value)?.label || value || '—'
}

function buoiLabel(value) {
  return BUOI_NGHI_OPTIONS.find((item) => item.value === value)?.label || '—'
}

function formatDate(value) {
  if (!value) return '—'
  const raw = String(value).slice(0, 10)
  const [year, month, day] = raw.split('-')
  if (!year || !month || !day) return raw
  return `${day}/${month}/${year}`
}

function formatThoiGian(row) {
  const batDau = formatDate(row.ngay_bat_dau)
  if (row.loai_nghi_phep !== 'nghi_nhieu_ngay') return batDau
  const ketThuc = formatDate(row.ngay_ket_thuc)
  if (batDau === '—' || ketThuc === '—' || batDau === ketThuc) return batDau
  return `${batDau} - ${ketThuc}`
}

function onSelectionChange(rows) {
  selectedRows.value = rows || []
}

function selectedIds() {
  return selectedRows.value.map((row) => row.id).filter(Boolean)
}

function onBulkAction(key) {
  if (key === 'da_duyet' || key === 'tu_choi') {
    confirmAction(key, selectedIds())
  }
}

async function confirmAction(trangThai, ids = []) {
  const targetIds = (ids || []).filter(Boolean)
  if (!targetIds.length) {
    ElMessage.warning('Vui lòng chọn ít nhất một đơn.')
    return
  }

  const isApprove = trangThai === 'da_duyet'
  const count = targetIds.length
  try {
    await ElMessageBox.confirm(
      isApprove
        ? `Duyệt ${count} đơn xin nghỉ phép đã chọn?`
        : `Từ chối ${count} đơn xin nghỉ phép đã chọn?`,
      isApprove ? 'Xác nhận duyệt' : 'Xác nhận từ chối',
      {
        type: isApprove ? 'success' : 'warning',
        confirmButtonText: isApprove ? 'Duyệt' : 'Từ chối',
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  await runAction(trangThai, targetIds)
}

async function runAction(trangThai, ids) {
  const isApprove = trangThai === 'da_duyet'
  const loadingRef = isApprove ? bulkApproving : bulkRejecting
  loadingRef.value = true
  if (ids.length === 1) processingId.value = ids[0]

  try {
    if (ids.length === 1) {
      if (isApprove) await duyetXinNghiPhep(ids[0])
      else await tuChoiXinNghiPhep(ids[0])
    } else if (isApprove) {
      await bulkDuyetXinNghiPhep(ids)
    } else {
      await bulkTuChoiXinNghiPhep(ids)
    }
    ElMessage.success(
      isApprove ? `Đã duyệt ${ids.length} đơn.` : `Đã từ chối ${ids.length} đơn.`,
    )
    await loadItems()
    emit('changed')
  } catch {
    // interceptor
  } finally {
    loadingRef.value = false
    processingId.value = null
  }
}

async function loadItems() {
  loading.value = true
  selectedRows.value = []
  try {
    const { data } = await fetchXinNghiPhep({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      loai_nghi_phep: loaiFilter.value || undefined,
      trang_thai: 'cho_duyet',
    })
    items.value = data.data || []
    total.value = data.total || 0
    page.value = data.current_page || page.value
    emit('count-change', total.value)
  } catch {
    items.value = []
    total.value = 0
    emit('count-change', 0)
  } finally {
    loading.value = false
  }
}

function onSearch() {
  page.value = 1
  loadItems()
}

onMounted(loadItems)
</script>

<style scoped lang="scss">
.section-header {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
}

.card-title {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.title-badge {
  :deep(.el-badge__content) {
    position: static;
    transform: none;
  }
}

.filter-toolbar {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 8px;
  width: 100%;
  min-width: 0;

  &__search {
    flex: 1 1 auto;
    min-width: 0;
    width: auto !important;
  }

  &__select {
    flex: 0 0 auto;
    width: 140px !important;

    &--loai {
      width: 130px !important;
    }
  }

  &__actions {
    flex: 0 0 auto;
    flex-wrap: nowrap !important;
  }

  @media (min-width: 768px) {
    justify-content: flex-end;

    &__search {
      flex: 0 0 240px;
      width: 240px !important;
      max-width: 240px;
    }
  }
}
</style>
