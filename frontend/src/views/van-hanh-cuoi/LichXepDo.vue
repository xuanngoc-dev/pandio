<template>
  <div class="lich-xep-do page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="24" :sm="12" :md="8" :lg="4">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo tên, SĐT, mã HĐ..."
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
        <CustomCol :xs="24" :sm="12" :md="8" :lg="4">
          <div class="filter-datepicker">
            <CustomDatePicker
              v-model="dateRange"
              type="daterange"
              range-separator="—"
              start-placeholder="Ngày chụp từ"
              end-placeholder="Ngày chụp đến"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              unlink-panels
              clearable
              @change="onSearch"
            />
          </div>
        </CustomCol>
        <CustomCol :xs="24" :sm="12" :md="8" :lg="4">
          <CustomSelect
            v-model="filterSapXepTrangPhuc"
            placeholder="Trạng thái xếp đồ"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="opt in sapXepDoOptions"
              :key="opt.value"
              :label="opt.label"
              :value="opt.value"
            />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="24" :sm="24" :md="24" :lg="12">
          <div class="toolbar-actions">
            <CustomButton type="primary" plain @click="onSearch">
              Tìm kiếm
            </CustomButton>
            <CustomButton
              v-for="preset in datePresets"
              :key="preset.key"
              :type="activeDatePreset === preset.key ? 'primary' : 'default'"
              plain
              @click="applyDatePreset(preset.key)"
            >
              {{ preset.label }}
            </CustomButton>
          </div>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách lịch xếp đồ</span>
          <div class="card-header-actions">
            <TableColumnConfig :settings="columnSettings" />
          </div>
        </div>
      </template>

      <CustomTable
        :column-settings="columnSettings"
        v-loading="loading"
        :data="items"
        stripe
        row-key="row_key"
        style="width: 100%"
      >
        <CustomTableColumn label="STT" width="60" align="center">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn v-if="columnSettings.isColumnVisible('ngay_chup')" label="Ngày chụp" width="120"
          align="center">
          <template #default="{ row }">
            {{ formatDateVi(row.ngay_chup) }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ten_khach_hang')"
          label="Tên khách"
          min-width="180"
        >
          <template #default="{ row }">
            {{ row.ten_khach_hang || '—' }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('sdt_khach_hang')"
          label="Số điện thoại"
          min-width="130"
        >
          <template #default="{ row }">
            {{ row.sdt_khach_hang || '—' }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ma_hop_dong')"
          label="Mã HĐ"
          min-width="200"
        >
          <template #default="{ row }">
            <router-link
              class="detail-link"
              :to="{ name: 'hop-dong-sddv', query: { keyword: row.ma_hop_dong || row.sdt_khach_hang } }"
            >
              {{ row.ma_hop_dong || '—' }}
            </router-link>
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ten_hop_dong')"
          label="Loại HĐ"
          min-width="140"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.ten_hop_dong || '—' }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ten_loai_quay_chup')"
          label="Loại quay chụp"
          min-width="140"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.ten_loai_quay_chup || '—' }}
          </template>
        </CustomTableColumn>

        
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('gio_chup')"
          label="Giờ chụp"
          width="100"
          align="center"
        >
          <template #default="{ row }">
            {{ formatGioChup(row.gio_chup) }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('sap_xep_trang_phuc')"
          label="Sắp xếp đồ"
          width="180"
          align="center"
        >
          <template #default="{ row }">
            <div class="sap-xep-do-cell">
              <CustomTag :type="sapXepTrangPhucTagType(row.sap_xep_trang_phuc)" size="small">
                {{ formatSapXepTrangPhucLabel(row.sap_xep_trang_phuc) || '—' }}
              </CustomTag>
              <CustomTooltip
                :content="
                  canEditSapXepDo(row)
                    ? 'Đổi trạng thái sắp xếp đồ'
                    : sapXepDoDisabledReason(row)
                "
                placement="top"
              >
                <span class="sap-xep-do-edit-wrap">
                  <el-dropdown
                    trigger="click"
                    :disabled="!canEditSapXepDo(row) || isUpdatingSapXepDo(row)"
                    @command="(value) => updateSapXepDo(row, value)"
                  >
                    <CustomButton
                      type="primary"
                      link
                      :icon="EditPen"
                      :loading="isUpdatingSapXepDo(row)"
                      :disabled="!canEditSapXepDo(row)"
                    />
                    <template #dropdown>
                      <el-dropdown-menu>
                        <el-dropdown-item
                          v-for="opt in sapXepDoOptions"
                          :key="opt.value"
                          :command="opt.value"
                          :disabled="resolveSapXepTrangPhucValue(row.sap_xep_trang_phuc) === opt.value"
                        >
                          {{ opt.label }}
                        </el-dropdown-item>
                      </el-dropdown-menu>
                    </template>
                  </el-dropdown>
                </span>
              </CustomTooltip>
            </div>
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ghi_chu')"
          label="Ghi chú"
          min-width="180"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.ghi_chu || '—' }}
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
import { ElMessage } from 'element-plus'
import { EditPen, Search } from '@element-plus/icons-vue'
import {
  capNhatThongTinDieuPhoi,
  fetchLichXepDo,
} from '@/api/hopDongSuDungDichVu'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { useTableColumns } from '@/composables/useTableColumns'
import {
  CustomButton,
  CustomCard,
  CustomCol,
  CustomDatePicker,
  CustomIcon,
  CustomInput,
  CustomOption,
  CustomRow,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
  CustomTag,
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'
import { toYmd } from '@/views/van-hanh-cuoi/lich-khach-hang/lichKhachHangDate'
import {
  DANH_SACH_BUOI_CHUP_KEY,
  formatSapXepTrangPhucLabel,
  normalizeSapXepTrangPhucValue,
  resolveSapXepTrangPhucValue,
  resolveTrangThaiDieuPhoi,
  SAP_XEP_TRANG_PHUC_KEY,
  SAP_XEP_TRANG_PHUC_OPTIONS,
  sapXepTrangPhucTagType,
} from '@/utils/thongTinDieuPhoi'

const tableColumns = [
  { key: 'ngay_chup', label: 'Ngày chụp' },
  { key: 'ten_khach_hang', label: 'Tên khách' },
  { key: 'sdt_khach_hang', label: 'Số điện thoại' },
  { key: 'ma_hop_dong', label: 'Mã HĐ' },
  { key: 'ten_hop_dong', label: 'Loại HĐ' },
  { key: 'ten_loai_quay_chup', label: 'Loại quay chụp' },
  { key: 'gio_chup', label: 'Giờ chụp' },
  { key: 'sap_xep_trang_phuc', label: 'Sắp xếp đồ' },
  { key: 'ghi_chu', label: 'Ghi chú' },
]
const columnSettings = useTableColumns('lich-xep-do.danh-sach', tableColumns, {
  pin: { selection: false },
})

const sapXepDoOptions = SAP_XEP_TRANG_PHUC_OPTIONS

const datePresets = [
  { key: 'today', label: 'Hôm nay' },
  { key: 'tomorrow', label: 'Ngày mai' },
  { key: 'this_week', label: 'Tuần này' },
]

const items = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(20)
const total = ref(0)
const keyword = ref('')
const dateRange = ref(getDatePresetRange('today'))
const filterSapXepTrangPhuc = ref('')
const updatingSapXepDoKey = ref(null)

const activeDatePreset = computed(() => {
  const range = dateRange.value
  if (!range?.[0] || !range?.[1]) return null
  for (const preset of datePresets) {
    const expected = getDatePresetRange(preset.key)
    if (expected[0] === range[0] && expected[1] === range[1]) {
      return preset.key
    }
  }
  return null
})

function startOfToday() {
  const now = new Date()
  return new Date(now.getFullYear(), now.getMonth(), now.getDate())
}

function addDays(date, days) {
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  d.setDate(d.getDate() + days)
  return d
}

/** Thứ 2 trong tuần chứa `date` (tuần bắt đầu từ Thứ 2) */
function getMonday(date) {
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const day = d.getDay()
  const diff = day === 0 ? -6 : 1 - day
  d.setDate(d.getDate() + diff)
  return d
}

/**
 * @param {'today'|'tomorrow'|'this_week'} key
 * @returns {[string, string]}
 */
function getDatePresetRange(key) {
  const today = startOfToday()
  if (key === 'tomorrow') {
    const tomorrow = addDays(today, 1)
    return [toYmd(tomorrow), toYmd(tomorrow)]
  }
  if (key === 'this_week') {
    const monday = getMonday(today)
    return [toYmd(monday), toYmd(addDays(monday, 6))]
  }
  return [toYmd(today), toYmd(today)]
}

function applyDatePreset(key) {
  dateRange.value = getDatePresetRange(key)
  onSearch()
}

function formatDateVi(value) {
  const raw = String(value || '').slice(0, 10)
  const [y, m, d] = raw.split('-')
  if (!y || !m || !d) return '—'
  return `${d}/${m}/${y}`
}

function formatGioChup(value) {
  if (value == null || value === '') return '—'
  const text = String(value).trim()
  const match = text.match(/^(\d{1,2}):(\d{2})/)
  if (!match) return text.slice(0, 5)
  return `${match[1].padStart(2, '0')}:${match[2]}`
}

function canEditSapXepDo(row) {
  if (!row?.id || row.session_index == null || !row.thong_tin_dieu_phoi) return false
  return resolveTrangThaiDieuPhoi(row) !== 'hoan_tat_san_xuat'
}

function sapXepDoDisabledReason(row) {
  if (!row?.thong_tin_dieu_phoi || row.session_index == null) {
    return 'Không tìm thấy buổi chụp để cập nhật'
  }
  if (resolveTrangThaiDieuPhoi(row) === 'hoan_tat_san_xuat') {
    return 'Đã hoàn tất sản xuất — không thể đổi trạng thái sắp xếp đồ'
  }
  return 'Không thể đổi trạng thái sắp xếp đồ'
}

function isUpdatingSapXepDo(row) {
  return updatingSapXepDoKey.value === row?.row_key
}

function buildSapXepDoPayload(row, value) {
  const envelope =
    row?.thong_tin_dieu_phoi && typeof row.thong_tin_dieu_phoi === 'object'
      ? JSON.parse(JSON.stringify(row.thong_tin_dieu_phoi))
      : {}
  const sessions = Array.isArray(envelope[DANH_SACH_BUOI_CHUP_KEY])
    ? envelope[DANH_SACH_BUOI_CHUP_KEY]
    : null
  const session = sessions?.[row.session_index]
  if (!session || typeof session !== 'object') return null

  const normalized = normalizeSapXepTrangPhucValue(value)
  const existing = session[SAP_XEP_TRANG_PHUC_KEY]
  if (existing && typeof existing === 'object' && !Array.isArray(existing)) {
    existing.gia_tri = normalized
  } else {
    session[SAP_XEP_TRANG_PHUC_KEY] = {
      su_dung: true,
      ten_thong_tin: 'Sắp xếp trang phục',
      loai_du_lieu: 'string',
      gia_tri: normalized,
    }
  }
  return envelope
}

async function updateSapXepDo(row, value) {
  if (!canEditSapXepDo(row)) {
    ElMessage.warning(sapXepDoDisabledReason(row))
    return
  }
  if (resolveSapXepTrangPhucValue(row.sap_xep_trang_phuc) === value) return

  const payload = buildSapXepDoPayload(row, value)
  if (!payload) {
    ElMessage.warning('Không tìm thấy buổi chụp để cập nhật')
    return
  }

  updatingSapXepDoKey.value = row.row_key
  try {
    await capNhatThongTinDieuPhoi(row.id, { thong_tin_dieu_phoi: payload })
    ElMessage.success('Đã cập nhật trạng thái sắp xếp đồ')
    await loadItems()
  } catch {
    // interceptor
  } finally {
    updatingSapXepDoKey.value = null
  }
}

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchLichXepDo({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      tu_ngay: dateRange.value?.[0] || undefined,
      den_ngay: dateRange.value?.[1] || undefined,
      sap_xep_trang_phuc: filterSapXepTrangPhuc.value || undefined,
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

onMounted(() => {
  loadItems()
})
</script>

<style scoped lang="scss">
.toolbar {
  :deep(.el-col) {
    min-width: 0;
  }

  :deep(.el-select),
  :deep(.el-date-editor) {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
  }
}

.filter-datepicker {
  width: 100%;
  max-width: 100%;
  min-width: 0;

  :deep(.el-date-editor) {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
  }
}

.toolbar-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.detail-link {
  color: var(--el-color-primary);
  text-decoration: none;
}

.detail-link:hover {
  text-decoration: underline;
}

.sap-xep-do-cell {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.sap-xep-do-edit-wrap {
  display: inline-flex;
  align-items: center;
}
</style>
