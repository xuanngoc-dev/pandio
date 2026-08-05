<template>
  <div class="quang-cao">
    <CustomCard shadow="hover" class="filter-card">
      <div class="toolbar">
        <el-date-picker
          v-model="ngayTu"
          type="date"
          placeholder="Từ ngày"
          format="DD/MM/YYYY"
          value-format="YYYY-MM-DD"
          style="width: 160px"
          clearable
          @change="onSearch"
        />
        <el-date-picker
          v-model="ngayDen"
          type="date"
          placeholder="Đến ngày"
          format="DD/MM/YYYY"
          value-format="YYYY-MM-DD"
          style="width: 160px"
          clearable
          @change="onSearch"
        />
        <CustomInput
          v-model="keyword"
          placeholder="Tìm theo ghi chú..."
          clearable
          style="max-width: 260px"
          @clear="onSearch"
          @keyup.enter="onSearch"
        >
          <template #prefix>
            <CustomIcon><Search /></CustomIcon>
          </template>
        </CustomInput>
        <CustomButton type="primary" plain @click="onSearch">
          <CustomIcon><Search /></CustomIcon>
          Tìm kiếm
        </CustomButton>
      </div>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Report quảng cáo</span>
          <BulkActionBar :actions="bulkActions" @action="onBulkAction">
            <TableColumnConfig :settings="columnSettings" />
            <CustomTooltip content="Thêm mới" placement="top">
              <CustomButton type="primary" @click="openCreate">
                <CustomIcon><Plus /></CustomIcon>
                Thêm
              </CustomButton>
            </CustomTooltip>
          </BulkActionBar>
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
        <CustomTableColumn type="selection" width="48" align="center" fixed />
        <CustomTableColumn label="STT" width="60" align="center" fixed>
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ngay')"
          prop="ngay"
          label="Ngày"
          width="110"
          fixed
        >
          <template #default="{ row }">
            {{ formatDate(row.ngay) }}
          </template>
        </CustomTableColumn>

        <!-- CPQC -->
        <CustomTableColumn
          v-if="isGroupVisible('cpqc_tiktok', 'cpqc_fb', 'cpqc_google')"
          label="CPQC"
          align="center"
        >
          <CustomTableColumn label="Tổng" width="120" align="right">
            <template #default="{ row }">
              {{ formatNum(sumFields(row, ['cpqc_tiktok', 'cpqc_fb', 'cpqc_google'])) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('cpqc_tiktok')"
            label="TikTok"
            width="110"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.cpqc_tiktok) }}</template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('cpqc_fb')"
            label="FB"
            width="100"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.cpqc_fb) }}</template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('cpqc_google')"
            label="Google"
            width="110"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.cpqc_google) }}</template>
          </CustomTableColumn>
        </CustomTableColumn>

        <!-- Inbox -->
        <CustomTableColumn
          v-if="isGroupVisible('inbox_tiktok', 'inbox_fb')"
          label="Inbox"
          align="center"
        >
          <CustomTableColumn label="Tổng" width="100" align="right">
            <template #default="{ row }">
              {{ formatNum(sumFields(row, ['inbox_tiktok', 'inbox_fb'])) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('inbox_tiktok')"
            label="TikTok"
            width="100"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.inbox_tiktok) }}</template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('inbox_fb')"
            label="FB"
            width="90"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.inbox_fb) }}</template>
          </CustomTableColumn>
        </CustomTableColumn>

        <!-- CPI -->
        <CustomTableColumn
          v-if="isGroupVisible('cpi_tiktok', 'cpi_fb')"
          label="CPI"
          align="center"
        >
          <CustomTableColumn label="Tổng" width="100" align="right">
            <template #default="{ row }">{{ formatNum(cpiTong(row)) }}</template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('cpi_tiktok')"
            label="TikTok"
            width="100"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.cpi_tiktok) }}</template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('cpi_fb')"
            label="FB"
            width="90"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.cpi_fb) }}</template>
          </CustomTableColumn>
        </CustomTableColumn>

        <!-- KH -->
        <CustomTableColumn
          v-if="isGroupVisible('kh_tiktok', 'kh_fb', 'kh_google')"
          label="KH"
          align="center"
        >
          <CustomTableColumn label="Tổng" width="100" align="right">
            <template #default="{ row }">
              {{ formatNum(sumFields(row, ['kh_tiktok', 'kh_fb', 'kh_google'])) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('kh_tiktok')"
            label="TikTok"
            width="90"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.kh_tiktok) }}</template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('kh_fb')"
            label="FB"
            width="80"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.kh_fb) }}</template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('kh_google')"
            label="Google"
            width="90"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.kh_google) }}</template>
          </CustomTableColumn>
        </CustomTableColumn>

        <!-- CPL -->
        <CustomTableColumn
          v-if="isGroupVisible('tcpl_tiktok', 'cpl_fb', 'cpl_google')"
          label="CPL"
          align="center"
        >
          <CustomTableColumn label="Tổng" width="100" align="right">
            <template #default="{ row }">{{ formatNum(cplTong(row)) }}</template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('tcpl_tiktok')"
            label="TikTok"
            width="100"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.tcpl_tiktok) }}</template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('cpl_fb')"
            label="FB"
            width="90"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.cpl_fb) }}</template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('cpl_google')"
            label="Google"
            width="100"
            align="right"
          >
            <template #default="{ row }">{{ formatNum(row.cpl_google) }}</template>
          </CustomTableColumn>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('lich_hen')"
          label="Lịch hẹn"
          width="100"
          align="right"
        >
          <template #default="{ row }">{{ formatNum(row.lich_hen) }}</template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('khach_den_tu_hen')"
          label="Khách đến từ hẹn"
          width="140"
          align="right"
        >
          <template #default="{ row }">{{ formatNum(row.khach_den_tu_hen) }}</template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ghi_chu')"
          prop="ghi_chu"
          label="Ghi chú"
          min-width="160"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.ghi_chu || '—' }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn label="Thao tác" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip content="Sửa" placement="top">
                <CustomButton type="primary" link :icon="Edit" @click="openEdit(row)" />
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
    </CustomCard>

    <CustomDialog
      v-model="dialogVisible"
      :title="editingId ? 'Sửa report quảng cáo' : 'Thêm report quảng cáo'"
      :width="1280"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules">
        <CustomRow :gutter="16">
          <CustomCol v-bind="fieldCol">
            <CustomFormItem label="Ngày" prop="ngay">
              <el-date-picker
                v-model="form.ngay"
                type="date"
                placeholder="Chọn ngày"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                style="width: 100%"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol v-for="field in numberFields" :key="field.prop" v-bind="fieldCol">
            <CustomFormItem :label="field.label" :prop="field.prop">
              <CustomInput v-model="form[field.prop]" type="number" style="width: 100%" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :span="24">
            <CustomFormItem label="Ghi chú" prop="ghi_chu">
              <CustomInput
                v-model="form.ghi_chu"
                type="textarea"
                :rows="2"
                placeholder="Ghi chú (tuỳ chọn)"
              />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
      <template #footer>
        <CustomButton @click="dialogVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="saving" @click="save">Lưu</CustomButton>
      </template>
    </CustomDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createReportQuangCao,
  deleteReportQuangCao,
  fetchReportQuangCao,
  updateReportQuangCao,
} from '@/api/reportQuangCao'
import { formatInteger } from '@/utils/number'
import BulkActionBar from '@/components/BulkActionBar.vue'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { runBulk, useBulkSelection } from '@/composables/useBulkSelection'
import { useTableColumns } from '@/composables/useTableColumns'
import {
  CustomButton,
  CustomCard,
  CustomCol,
  CustomDialog,
  CustomForm,
  CustomFormItem,
  CustomIcon,
  CustomInput,
  CustomRow,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'

const tableColumns = [
  { key: 'ngay', label: 'Ngày' },
  { key: 'cpqc_tiktok', label: 'CPQC · TikTok' },
  { key: 'cpqc_fb', label: 'CPQC · FB' },
  { key: 'cpqc_google', label: 'CPQC · Google' },
  { key: 'inbox_tiktok', label: 'Inbox · TikTok' },
  { key: 'inbox_fb', label: 'Inbox · FB' },
  { key: 'cpi_tiktok', label: 'CPI · TikTok' },
  { key: 'cpi_fb', label: 'CPI · FB' },
  { key: 'kh_tiktok', label: 'KH · TikTok' },
  { key: 'kh_fb', label: 'KH · FB' },
  { key: 'kh_google', label: 'KH · Google' },
  { key: 'tcpl_tiktok', label: 'CPL · TikTok' },
  { key: 'cpl_fb', label: 'CPL · FB' },
  { key: 'cpl_google', label: 'CPL · Google' },
  { key: 'lich_hen', label: 'Lịch hẹn' },
  { key: 'khach_den_tu_hen', label: 'Khách đến từ hẹn' },
  { key: 'ghi_chu', label: 'Ghi chú' },
]
const columnSettings = useTableColumns('khach-hang.quang-cao', tableColumns)

function isGroupVisible(...keys) {
  return keys.some((key) => columnSettings.isColumnVisible(key))
}

const NUMBER_FIELDS = [
  'cpqc_tiktok',
  'cpqc_fb',
  'cpqc_google',
  'inbox_tiktok',
  'cpi_tiktok',
  'inbox_fb',
  'cpi_fb',
  'kh_tiktok',
  'kh_fb',
  'kh_google',
  'tcpl_tiktok',
  'cpl_fb',
  'cpl_google',
  'lich_hen',
  'khach_den_tu_hen',
]

/** lg: 6/hàng · md: 4/hàng · sm: 3/hàng · xs (mobile): 2/hàng */
const fieldCol = { xs: 12, sm: 8, md: 6, lg: 4 }

const numberFields = [
  { prop: 'cpqc_tiktok', label: 'CPQC TikTok' },
  { prop: 'cpqc_fb', label: 'CPQC FB' },
  { prop: 'cpqc_google', label: 'CPQC Google' },
  { prop: 'inbox_tiktok', label: 'Inbox TikTok' },
  { prop: 'cpi_tiktok', label: 'CPI TikTok' },
  { prop: 'inbox_fb', label: 'Inbox FB' },
  { prop: 'cpi_fb', label: 'CPI FB' },
  { prop: 'kh_tiktok', label: 'KH TikTok' },
  { prop: 'kh_fb', label: 'KH FB' },
  { prop: 'kh_google', label: 'KH Google' },
  { prop: 'tcpl_tiktok', label: 'TCPL TikTok' },
  { prop: 'cpl_fb', label: 'CPL FB' },
  { prop: 'cpl_google', label: 'CPL Google' },
  { prop: 'lich_hen', label: 'Lịch hẹn' },
  { prop: 'khach_den_tu_hen', label: 'Khách đến từ hẹn' },
]

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const route = useRoute()
const keyword = ref(String(route.query.keyword || ''))
const ngayTu = ref('')
const ngayDen = ref('')

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)
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
      ? `Xóa ${selectedCount.value} report đã chọn`
      : 'Chọn report để xóa',
  },
])

function todayStr() {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const emptyForm = () => ({
  ngay: todayStr(),
  cpqc_tiktok: 0,
  cpqc_fb: 0,
  cpqc_google: 0,
  inbox_tiktok: 0,
  cpi_tiktok: 0,
  inbox_fb: 0,
  cpi_fb: 0,
  kh_tiktok: 0,
  kh_fb: 0,
  kh_google: 0,
  tcpl_tiktok: 0,
  cpl_fb: 0,
  cpl_google: 0,
  lich_hen: 0,
  khach_den_tu_hen: 0,
  ghi_chu: '',
})

const form = reactive(emptyForm())

const rules = {
  ngay: [{ required: true, message: 'Vui lòng chọn ngày', trigger: 'change' }],
}

function formatNum(value) {
  if (value == null || value === '') return '—'
  return formatInteger(value) || '0'
}

function formatDate(value) {
  if (!value) return '—'
  const str = String(value).slice(0, 10)
  const [y, m, d] = str.split('-')
  if (!y || !m || !d) return str
  return `${d}/${m}/${y}`
}

function toNum(value) {
  if (value == null || value === '') return 0
  const n = Number(value)
  return Number.isNaN(n) ? 0 : n
}

function sumFields(row, keys) {
  return keys.reduce((sum, key) => sum + toNum(row?.[key]), 0)
}

/** CPI tổng = tổng CPQC (TikTok+FB) / tổng Inbox */
function cpiTong(row) {
  const cost = sumFields(row, ['cpqc_tiktok', 'cpqc_fb'])
  const inbox = sumFields(row, ['inbox_tiktok', 'inbox_fb'])
  return inbox ? Math.round(cost / inbox) : 0
}

/** CPL tổng = tổng CPQC / tổng KH */
function cplTong(row) {
  const cost = sumFields(row, ['cpqc_tiktok', 'cpqc_fb', 'cpqc_google'])
  const kh = sumFields(row, ['kh_tiktok', 'kh_fb', 'kh_google'])
  return kh ? Math.round(cost / kh) : 0
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchReportQuangCao({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      ngay_tu: ngayTu.value || undefined,
      ngay_den: ngayDen.value || undefined,
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

function openCreate() {
  editingId.value = null
  Object.assign(form, emptyForm())
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  const payload = {
    ngay: String(row.ngay || '').slice(0, 10),
    ghi_chu: row.ghi_chu || '',
  }
  for (const key of NUMBER_FIELDS) {
    payload[key] = toNum(row[key])
  }
  Object.assign(form, payload)
  dialogVisible.value = true
}

function buildPayload() {
  const payload = {
    ngay: form.ngay,
    ghi_chu: form.ghi_chu?.trim() || null,
  }
  for (const key of NUMBER_FIELDS) {
    payload[key] = toNum(form[key])
  }
  return payload
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = buildPayload()

  try {
    if (editingId.value) {
      await updateReportQuangCao(editingId.value, payload)
      ElMessage.success('Đã cập nhật report quảng cáo.')
    } else {
      await createReportQuangCao(payload)
      ElMessage.success('Đã thêm report quảng cáo.')
    }
    dialogVisible.value = false
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
}

async function onBulkAction(key) {
  if (key === 'delete') await bulkRemove()
}

async function bulkRemove() {
  const ids = selectedIds.value
  if (!ids.length) return

  await ElMessageBox.confirm(`Xóa ${ids.length} report đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteReportQuangCao(id))
    ElMessage.success(`Đã xóa ${ids.length} report.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(
    `Xóa report ngày ${formatDate(row.ngay)}?`,
    'Xác nhận',
    {
      type: 'warning',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
    },
  )

  try {
    await deleteReportQuangCao(row.id)
    ElMessage.success('Đã xóa report quảng cáo.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadItems)
</script>

<style scoped lang="scss">
.quang-cao {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.card-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}

.action-btns {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
</style>
