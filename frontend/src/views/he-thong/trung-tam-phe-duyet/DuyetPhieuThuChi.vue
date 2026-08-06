<template>
  <div class="duyet-phieu-thu-chi page-list">
    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">
            Phiếu thu chi chờ duyệt
            <el-badge
              v-if="total > 0"
              :value="total"
              :max="99"
              class="title-badge"
            />
          </span>
          <div class="card-header-right">
            <CustomInput
              v-model="keyword"
              placeholder="Tìm lý do, ghi chú..."
              clearable
              style="width: 220px"
              @clear="onSearch"
              @keyup.enter="onSearch"
            >
              <template #prefix>
                <CustomIcon><Search /></CustomIcon>
              </template>
            </CustomInput>
            <CustomSelect
              v-model="filterLoai"
              placeholder="Loại"
              clearable
              style="width: 120px"
              @change="onSearch"
            >
              <CustomOption label="Thu" value="thu" />
              <CustomOption label="Chi" value="chi" />
            </CustomSelect>
            <BulkActionBar :actions="bulkActions" @action="onBulkAction" />
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
        <CustomTableColumn label="Loại" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.loai === 'thu' ? 'success' : 'danger'" size="small">
              {{ row.loai === 'thu' ? 'Thu' : 'Chi' }}
            </el-tag>
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Hạng mục" min-width="160" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.hang_muc?.ten_hang_muc || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Số tiền" width="140" align="right">
          <template #default="{ row }">
            {{ formatMoney(row.so_tien) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Lý do" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.ly_do || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Người tạo" min-width="130" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.nguoi_tao?.name || '—' }}
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
                  @click="openStatusModal('da_duyet', [row.id])"
                />
              </CustomTooltip>
              <CustomTooltip content="Từ chối" placement="top">
                <CustomButton
                  type="warning"
                  link
                  :icon="CircleClose"
                  @click="openStatusModal('tu_choi', [row.id])"
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

    <CustomDialog v-model="statusDialogVisible" :title="statusModalTitle" :width="520">
      <CustomForm ref="statusFormRef" :model="statusForm" :rules="statusRules" label-position="top">
        <CustomFormItem label="Ghi chú / Lý do" prop="ghi_chu">
          <CustomInput
            v-model="statusForm.ghi_chu"
            type="textarea"
            :rows="4"
            :placeholder="
              statusForm.trang_thai === 'da_duyet'
                ? 'Nhập ghi chú khi duyệt...'
                : 'Nhập lý do từ chối...'
            "
          />
        </CustomFormItem>
      </CustomForm>
      <template #footer>
        <CustomButton @click="statusDialogVisible = false">Hủy</CustomButton>
        <CustomButton
          :type="statusForm.trang_thai === 'da_duyet' ? 'success' : 'warning'"
          :loading="statusSaving"
          @click="confirmStatusChange"
        >
          {{ statusForm.trang_thai === 'da_duyet' ? 'Duyệt' : 'Từ chối' }}
        </CustomButton>
      </template>
    </CustomDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { CircleCheck, CircleClose, Search } from '@element-plus/icons-vue'
import {
  bulkUpdateStatusPhieuThuChi,
  fetchPhieuThuChi,
  updatePhieuThuChi,
} from '@/api/phieuThuChi'
import { formatInteger } from '@/utils/number'
import BulkActionBar from '@/components/BulkActionBar.vue'
import {
  CustomButton,
  CustomCard,
  CustomDialog,
  CustomForm,
  CustomFormItem,
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

const items = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const filterLoai = ref('')
const selectedRows = ref([])
const bulkApproving = ref(false)
const bulkRejecting = ref(false)

const statusDialogVisible = ref(false)
const statusSaving = ref(false)
const statusFormRef = ref(null)
const statusForm = reactive({
  trang_thai: 'da_duyet',
  ghi_chu: '',
  ids: [],
})
const statusRules = {
  ghi_chu: [{ required: true, message: 'Vui lòng nhập ghi chú / lý do', trigger: 'blur' }],
}

const selectedCount = computed(() => selectedRows.value.length)

const statusModalTitle = computed(() => {
  const count = statusForm.ids?.length || 0
  if (statusForm.trang_thai === 'da_duyet') {
    return count > 1 ? `Duyệt ${count} phiếu` : 'Duyệt phiếu'
  }
  return count > 1 ? `Từ chối ${count} phiếu` : 'Từ chối phiếu'
})

const bulkActions = computed(() => [
  {
    key: 'tu_choi',
    label: 'Từ chối',
    type: 'warning',
    badge: selectedCount.value,
    badgeType: 'warning',
    loading: bulkRejecting.value,
    tooltip: selectedCount.value
      ? `Từ chối ${selectedCount.value} phiếu đã chọn`
      : 'Chọn phiếu để từ chối',
  },
  {
    key: 'da_duyet',
    label: 'Duyệt',
    type: 'success',
    badge: selectedCount.value,
    badgeType: 'success',
    loading: bulkApproving.value,
    tooltip: selectedCount.value
      ? `Duyệt ${selectedCount.value} phiếu đã chọn`
      : 'Chọn phiếu để duyệt',
  },
])

function formatMoney(value) {
  if (value == null || value === '') return '—'
  return `${formatInteger(value) || '0'} ₫`
}

function onSelectionChange(rows) {
  selectedRows.value = rows || []
}

function selectedIds() {
  return selectedRows.value.map((row) => row.id).filter(Boolean)
}

function onBulkAction(key) {
  if (key === 'da_duyet' || key === 'tu_choi') {
    openStatusModal(key, selectedIds())
  }
}

function openStatusModal(trangThai, ids = []) {
  const targetIds = (ids || []).filter(Boolean)
  if (!targetIds.length || !trangThai) {
    ElMessage.warning('Vui lòng chọn ít nhất một phiếu.')
    return
  }

  statusForm.trang_thai = trangThai
  statusForm.ghi_chu = ''
  statusForm.ids = targetIds
  statusDialogVisible.value = true
}

async function confirmStatusChange() {
  const valid = await statusFormRef.value?.validate().catch(() => false)
  if (!valid) return

  const ids = statusForm.ids || []
  const trangThai = statusForm.trang_thai
  const ghiChu = statusForm.ghi_chu.trim()
  if (!ids.length || !trangThai || !ghiChu) return

  const loadingRef = trangThai === 'da_duyet' ? bulkApproving : bulkRejecting
  statusSaving.value = true
  loadingRef.value = true

  try {
    if (ids.length === 1) {
      const row = items.value.find((item) => item.id === ids[0])
      if (!row) {
        ElMessage.warning('Không tìm thấy phiếu.')
        return
      }
      await updatePhieuThuChi(row.id, {
        loai: row.loai,
        hang_muc_id: row.hang_muc_id || null,
        so_tien: Number(row.so_tien) || 0,
        ly_do: row.ly_do || null,
        trang_thai: trangThai,
        ghi_chu: ghiChu,
      })
    } else {
      await bulkUpdateStatusPhieuThuChi(ids, trangThai, ghiChu)
    }
    ElMessage.success(
      trangThai === 'da_duyet'
        ? `Đã duyệt ${ids.length} phiếu.`
        : `Đã từ chối ${ids.length} phiếu.`,
    )
    statusDialogVisible.value = false
    await loadItems()
    emit('changed')
  } catch {
    // interceptor
  } finally {
    statusSaving.value = false
    loadingRef.value = false
  }
}

async function loadItems() {
  loading.value = true
  selectedRows.value = []
  try {
    const { data } = await fetchPhieuThuChi({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      loai: filterLoai.value || undefined,
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

.card-header-right {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}
</style>
