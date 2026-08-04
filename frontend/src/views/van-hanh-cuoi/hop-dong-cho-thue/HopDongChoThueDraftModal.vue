<template>
  <CustomDialog
    v-model="visible"
    title="Hợp đồng nháp"
    :width="1380"
    class="hop-dong-cho-thue-draft-modal"
    @closed="onClosed"
  >
    <div class="draft-toolbar">
      <CustomInput
        v-model="keyword"
        placeholder="Tìm theo mã HĐ, khách hàng, SĐT..."
        clearable
        style="max-width: 300px"
        @clear="onSearch"
        @keyup.enter="onSearch"
      >
        <template #prefix>
          <CustomIcon><Search /></CustomIcon>
        </template>
      </CustomInput>
      <el-date-picker
        v-model="createdRange"
        type="daterange"
        range-separator="—"
        start-placeholder="Từ ngày"
        end-placeholder="Đến ngày"
        format="DD/MM/YYYY"
        value-format="YYYY-MM-DD"
        clearable
        style="width: 280px"
        @change="onSearch"
      />
      <CustomButton type="primary" plain @click="onSearch">
        <CustomIcon><Search /></CustomIcon>
        Tìm kiếm
      </CustomButton>
      <div class="draft-toolbar__spacer" />
      <BulkActionBar :actions="bulkActions" @action="onBulkAction" />
    </div>

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
      <CustomTableColumn label="Mã HĐ" prop="ma_hop_dong" min-width="160" />
      <CustomTableColumn label="Khách hàng" min-width="180">
        <template #default="{ row }">
          <div>{{ row.ten_khach_hang || '—' }}</div>
          <div v-if="row.sdt_khach_hang" class="sub-text">{{ row.sdt_khach_hang }}</div>
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Ngày thuê" width="120" align="center">
        <template #default="{ row }">
          {{ formatDate(row.ngay_thue) }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Ngày trả DK" width="120" align="center">
        <template #default="{ row }">
          {{ formatDate(row.ngay_tra_du_kien) }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Tổng tiền" width="130" align="right">
        <template #default="{ row }">
          {{ formatMoney(row.tong_tien) }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Trạng thái" width="120" align="center">
        <template #default="{ row }">
          <CustomTag :type="trangThaiTagType(row.trang_thai)" size="small">
            {{ trangThaiLabel(row.trang_thai) }}
          </CustomTag>
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Ngày tạo" width="120" align="center">
        <template #default="{ row }">
          {{ formatDate(row.created_at) }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Thao tác" width="100" fixed="right" align="center">
        <template #default="{ row }">
          <div class="action-btns">
            <CustomTooltip content="Tiếp tục" placement="top">
              <CustomButton type="primary" link :icon="Right" @click="onContinue(row)" />
            </CustomTooltip>
            <CustomTooltip content="Xóa" placement="top">
              <CustomButton type="danger" link :icon="Delete" @click="remove(row)" />
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

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Đóng</CustomButton>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Right, Search } from '@element-plus/icons-vue'
import {
  deleteHopDongChoThueTrangPhuc,
  fetchHopDongChoThueTrangPhuc,
} from '@/api/hopDongChoThueTrangPhuc'
import BulkActionBar from '@/components/BulkActionBar.vue'
import Pagination from '@/components/Pagination.vue'
import { runBulk, useBulkSelection } from '@/composables/useBulkSelection'
import {
  CustomButton,
  CustomDialog,
  CustomIcon,
  CustomInput,
  CustomTable,
  CustomTableColumn,
  CustomTag,
  CustomTooltip,
} from '@/components/element'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'continue', 'changed'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const trangThaiOptions = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
]

const items = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const createdRange = ref(null)
const bulkDeleting = ref(false)

const { selectedCount, onSelectionChange, clearSelection, selectedIds } = useBulkSelection()

const bulkActions = computed(() => [
  {
    key: 'delete',
    label: 'Xóa',
    type: 'danger',
    badge: selectedCount.value,
    badgeType: 'danger',
    loading: bulkDeleting.value,
    tooltip: selectedCount.value
      ? `Xóa ${selectedCount.value} hợp đồng nháp đã chọn`
      : 'Chọn hợp đồng để xóa',
  },
])

function trangThaiLabel(value) {
  return trangThaiOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function trangThaiTagType(value) {
  return value === 'moi_tao' ? 'info' : ''
}

function formatDate(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('vi-VN')
}

function formatMoney(value) {
  if (value == null || value === '') return '—'
  const num = Number(value)
  if (Number.isNaN(num)) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const [tuNgay, denNgay] = Array.isArray(createdRange.value) ? createdRange.value : []
    const { data } = await fetchHopDongChoThueTrangPhuc({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      tu_ngay: tuNgay || undefined,
      den_ngay: denNgay || undefined,
      chi_nhap: 1,
    })
    items.value = data.data || []
    total.value = data.total || 0
    page.value = data.current_page || page.value
  } catch {
    items.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

function onSearch() {
  page.value = 1
  loadItems()
}

function onContinue(row) {
  emit('continue', row)
}

async function onBulkAction(key) {
  if (key === 'delete') await bulkRemove()
}

async function bulkRemove() {
  const ids = selectedIds.value
  if (!ids.length) return

  await ElMessageBox.confirm(`Xóa ${ids.length} hợp đồng nháp đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteHopDongChoThueTrangPhuc(id))
    ElMessage.success(`Đã xóa ${ids.length} hợp đồng nháp.`)
    emit('changed')
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa hợp đồng nháp "${row.ma_hop_dong}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteHopDongChoThueTrangPhuc(row.id)
    ElMessage.success('Đã xóa hợp đồng nháp.')
    emit('changed')
    await loadItems()
  } catch {
    // interceptor
  }
}

function onClosed() {
  keyword.value = ''
  createdRange.value = null
  page.value = 1
  clearSelection()
}

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return
    page.value = 1
    loadItems()
  },
)

defineExpose({ reload: loadItems })
</script>

<style scoped lang="scss">
.draft-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.draft-toolbar__spacer {
  flex: 1;
  min-width: 8px;
}

.sub-text {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.action-btns {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.footer-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
