<template>
  <div class="xem-danh-gia page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="6" :lg="6">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo nội dung, mã HĐ, tên khách, SĐT..."
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
        <CustomCol :xs="12" :sm="12" :md="6" :lg="6">
          <CustomSelect
            v-model="filterFormId"
            placeholder="Form đánh giá (bắt buộc)"
            filterable
            clearable
            style="width: 100%"
            @change="onFormChange"
          >
            <CustomOption
              v-for="opt in formOptions"
              :key="opt.id"
              :label="opt.ten_form"
              :value="opt.id"
            />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="6" :lg="6">
          <CustomButton type="primary" plain @click="onSearch">
            Tìm kiếm
          </CustomButton>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách đánh giá</span>
          <TableColumnConfig :settings="columnSettings" />
        </div>
      </template>

      <CustomTable
        :column-settings="columnSettings"
        v-loading="loading"
        :data="items"
        stripe
        row-key="id"
        style="width: 100%"
      >
        <CustomTableColumn label="STT" width="60" align="center">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ma_hop_dong')"
          label="Mã HĐ"
          min-width="140"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.hop_dong?.ma_hop_dong || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('khach_hang')"
          label="Khách hàng"
          min-width="180"
        >
          <template #default="{ row }">
            <div class="cell-stack">
              <div class="cell-ellipsis" :title="formatTenKhach(row)">
                {{ formatTenKhach(row) }}
              </div>
              <div v-if="formatSdtKhach(row)" class="sub-text">
                {{ formatSdtKhach(row) }}
              </div>
            </div>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ten_form')"
          label="Tên form"
          min-width="180"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.form_danh_gia?.ten_form || '—' }}
          </template>
        </CustomTableColumn>

        <template v-for="col in questionColumns" :key="col.key">
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible(col.key)"
            :config-key="col.key"
            :label="col.label"
            min-width="160"
            show-overflow-tooltip
          >
            <template #default="{ row }">
              {{ formatAnswerAt(row, col.index) }}
            </template>
          </CustomTableColumn>
        </template>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('updated_at')"
          label="Thời gian đánh giá"
          width="170"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDateTime(row.updated_at) }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('link_danh_gia')"
          label="Link đánh giá"
          width="110"
          align="center"
        >
          <template #default="{ row }">
            <CustomButton
              type="primary"
              link
              :disabled="!buildLink(row)"
              @click="openLink(row)"
            >
              Mở
            </CustomButton>
          </template>
        </CustomTableColumn>

        <CustomTableColumn label="Thao tác" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip content="Sao chép đường link" placement="top">
                <CustomButton
                  type="primary"
                  link
                  :icon="CopyDocument"
                  :disabled="!buildLink(row)"
                  @click="copyLink(row)"
                />
              </CustomTooltip>
              <CustomTooltip content="Xóa đánh giá" placement="top">
                <CustomButton type="danger" link :icon="Delete" @click="clearNoiDung(row)" />
              </CustomTooltip>
            </div>
          </template>
        </CustomTableColumn>
      </CustomTable>

      <Pagination
        v-model="page"
        v-model:page-size="perPage"
        :total="total"
        :disabled="loading || !hasSearched"
        @change="loadItems"
      />
    </CustomCard>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { CopyDocument, Delete, Search } from '@element-plus/icons-vue'
import { fetchFormDanhGia } from '@/api/formDanhGia'
import {
  fetchHopDongSuDungDichVuFormDanhGia,
  xoaNoiDungHopDongSuDungDichVuFormDanhGia,
} from '@/api/hopDongSuDungDichVuFormDanhGia'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { useTableColumns } from '@/composables/useTableColumns'
import {
  CustomButton,
  CustomCard,
  CustomCol,
  CustomIcon,
  CustomInput,
  CustomOption,
  CustomRow,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'

const router = useRouter()

const COLUMN_STORAGE_KEY = 'khach-hang.xem-danh-gia'
const HOP_DONG_GROUP = 'Thông tin hợp đồng'
const NOI_DUNG_GROUP = 'Nội dung đánh giá'

const tableColumns = [
  { key: 'ma_hop_dong', label: 'Mã HĐ', group: HOP_DONG_GROUP },
  { key: 'khach_hang', label: 'Khách hàng', group: HOP_DONG_GROUP },
  { key: 'ten_form', label: 'Tên form' },
  { key: 'updated_at', label: 'Thời gian đánh giá' },
  { key: 'link_danh_gia', label: 'Link đánh giá' },
]

const columnSettings = useTableColumns(COLUMN_STORAGE_KEY, tableColumns)

const items = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const filterFormId = ref(null)
const formOptions = ref([])
const hasSearched = ref(false)
const questionColumns = ref([])

function truncateLabel(text, max = 48) {
  const value = String(text || '').trim()
  if (!value) return ''
  if (value.length <= max) return value
  return `${value.slice(0, max - 1)}…`
}

function buildQuestionColumns(cauHoiList) {
  if (!Array.isArray(cauHoiList)) return []
  return cauHoiList.map((q, index) => {
    const thongTin = String(q?.thong_tin_danh_gia || '').trim()
    const cauHoi = String(q?.cau_hoi || '').trim()
    const label = truncateLabel(thongTin || cauHoi) || `Câu ${index + 1}`
    return {
      key: `q_${index}`,
      index,
      label,
      group: NOI_DUNG_GROUP,
    }
  })
}

function syncQuestionColumns(formId) {
  if (!formId) {
    questionColumns.value = []
    columnSettings.setExtraColumns([])
    return
  }

  const form = formOptions.value.find((item) => item.id === formId)
  const columns = buildQuestionColumns(form?.cau_hoi)
  questionColumns.value = columns
  columnSettings.setExtraColumns(columns, {
    storageKey: `${COLUMN_STORAGE_KEY}.form.${formId}`,
  })
}

function answersOf(row) {
  return Array.isArray(row?.noi_dung_danh_gia) ? row.noi_dung_danh_gia : []
}

function formatTenKhach(row) {
  return row?.hop_dong?.ten_khach_hang || '—'
}

function formatSdtKhach(row) {
  return row?.hop_dong?.sdt_khach_hang || ''
}

function formatAnswerValue(item) {
  const value = item?.gia_tri
  if (value == null || value === '') return '—'
  if (item.loai_danh_gia === 'diem') {
    return `${value} điểm`
  }
  return String(value)
}

function formatAnswerAt(row, index) {
  return formatAnswerValue(answersOf(row)[index])
}

function formatDateTime(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleString('vi-VN')
}

function buildLink(row) {
  const slug = row?.form_danh_gia?.slug
  const hopDongId = row?.hop_dong_danh_gia_id
  if (!slug || !hopDongId) return ''
  const resolved = router.resolve({
    name: 'danh-gia-khach',
    params: { slug },
    query: { hop_dong_danh_gia_id: hopDongId },
  })
  return `${window.location.origin}${resolved.href}`
}

function openLink(row) {
  const link = buildLink(row)
  if (!link) {
    ElMessage.warning('Không tìm thấy đường link đánh giá.')
    return
  }
  window.open(link, '_blank', 'noopener,noreferrer')
}

async function copyLink(row) {
  const link = buildLink(row)
  if (!link) {
    ElMessage.warning('Không tìm thấy đường link đánh giá.')
    return
  }
  try {
    await navigator.clipboard.writeText(link)
    ElMessage.success('Đã sao chép link')
  } catch {
    ElMessage.error('Không thể sao chép link')
  }
}

async function clearNoiDung(row) {
  await ElMessageBox.confirm(
    'Xóa nội dung đánh giá này? Link đánh giá vẫn được giữ để khách gửi lại.',
    'Xác nhận',
    {
      type: 'warning',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
    }
  )

  try {
    await xoaNoiDungHopDongSuDungDichVuFormDanhGia(row.id)
    ElMessage.success('Đã xóa nội dung đánh giá.')
    await loadItems()
  } catch {
    // interceptor
  }
}

function onFormChange() {
  syncQuestionColumns(filterFormId.value)
  items.value = []
  total.value = 0
  hasSearched.value = false
}

async function loadFormOptions() {
  try {
    const { data } = await fetchFormDanhGia({ per_page: 100 })
    formOptions.value = data.data || data || []
  } catch {
    formOptions.value = []
  }
}

async function loadItems() {
  if (!filterFormId.value) {
    ElMessage.warning('Vui lòng chọn form đánh giá.')
    return
  }

  syncQuestionColumns(filterFormId.value)

  loading.value = true
  hasSearched.value = true
  try {
    const { data } = await fetchHopDongSuDungDichVuFormDanhGia({
      page: page.value,
      per_page: perPage.value,
      form_danh_gia_id: filterFormId.value,
      keyword: keyword.value.trim() || undefined,
    })
    items.value = data.data || []
    total.value = data.total || 0
    page.value = data.current_page || page.value

    if (!questionColumns.value.length && items.value.length) {
      const columns = buildQuestionColumns(answersOf(items.value[0]))
      questionColumns.value = columns
      columnSettings.setExtraColumns(columns, {
        storageKey: `${COLUMN_STORAGE_KEY}.form.${filterFormId.value}`,
      })
    }
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

onMounted(loadFormOptions)
</script>

<style scoped lang="scss">
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.card-title {
  font-weight: 600;
}

.cell-stack {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.cell-ellipsis {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sub-text {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.action-btns {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 2px;
}
</style>
