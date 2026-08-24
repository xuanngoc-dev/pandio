<template>
  <div class="khach-hang page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="12" :sm="12" :md="8" :lg="8">
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
        <CustomCol :xs="12" :sm="12" :md="8" :lg="8">
          <CustomSelect
            v-model="filterLoaiHopDong"
            placeholder="Loại hợp đồng đã ký"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="opt in loaiHopDongOptions"
              :key="opt.value"
              :label="opt.label"
              :value="opt.value"
            />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="12" :sm="12" :md="8" :lg="4">
          <CustomButton type="primary" plain @click="onSearch">
            Tìm kiếm
          </CustomButton>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách khách hàng</span>
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
        row-key="id"
        style="width: 100%"
      >
        <CustomTableColumn label="STT" width="60" align="center">
          <template #default="{ $index }">
            {{ (page - 1) * perPage + $index + 1 }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ten_khach')"
          label="Tên khách"
          min-width="220"
        >
          <template #default="{ row }">
            <div v-if="!row.ten_khach?.length">—</div>
            <div v-else class="cell-names">
              <div v-for="ten in row.ten_khach" :key="ten">{{ ten }}</div>
            </div>
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('sdt')"
          label="Số điện thoại"
          min-width="140"
        >
          <template #default="{ row }">
            {{ row.sdt || '—' }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguon_khach')"
          label="Nguồn khách"
          min-width="180"
        >
          <template #default="{ row }">
            <div v-if="!row.nguon_khach?.length">—</div>
            <div v-else class="name-tags">
              <CustomTag
                v-for="nguon in row.nguon_khach"
                :key="nguon"
                size="small"
                effect="plain"
              >
                {{ nguon }}
              </CustomTag>
            </div>
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('loai_hop_dong')"
          label="Loại hợp đồng đã ký"
          min-width="240"
        >
          <template #default="{ row }">
            <div v-if="!row.loai_hop_dong?.length">—</div>
            <div v-else class="name-tags">
              <CustomTag
                v-for="loai in row.loai_hop_dong"
                :key="loai"
                size="small"
                :type="loaiHopDongTagType(loai)"
                effect="light"
              >
                {{ loaiHopDongLabel(loai) }}
              </CustomTag>
            </div>
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('so_note')"
          label="Note"
          width="80"
          align="center"
        >
          <template #default="{ row }">
            {{ row.so_note || 0 }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('so_hop_dong_sddv')"
          label="HĐ dịch vụ"
          width="110"
          align="center"
        >
          <template #default="{ row }">
            {{ row.so_hop_dong_sddv || 0 }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('so_hop_dong_cho_thue')"
          label="HĐ thuê"
          width="90"
          align="center"
        >
          <template #default="{ row }">
            {{ row.so_hop_dong_cho_thue || 0 }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('tong_gia_tri_hop_dong')"
          label="Tổng giá trị HĐ"
          min-width="150"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.tong_gia_tri_hop_dong) }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('cap_nhat_gan_nhat')"
          label="Cập nhật gần nhất"
          width="160"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDateTime(row.cap_nhat_gan_nhat) }}
          </template>
        </CustomTableColumn>

        <CustomTableColumn label="Thao tác" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip content="Xem chi tiết" placement="top">
                <CustomButton type="primary" link :icon="View" @click="openChiTiet(row)" />
              </CustomTooltip>
              <CustomTooltip content="Xem lịch sử khách" placement="top">
                <CustomButton type="primary" link :icon="Clock" @click="openLichSu(row)" />
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

    <CustomDialog v-model="chiTietVisible" :title="chiTietTitle" :width="920">
      <el-empty
        v-if="!selectedHopDongSddv.length && !selectedHopDongChoThue.length"
        description="Khách chưa có hợp đồng"
      />
      <div v-else class="detail-sections">
        <section v-if="selectedHopDongSddv.length" class="detail-block">
          <h4 class="detail-title">Hợp đồng dịch vụ ({{ selectedHopDongSddv.length }})</h4>
          <CustomTable :data="selectedHopDongSddv" stripe row-key="id" style="width: 100%">
            <CustomTableColumn label="Mã HĐ" min-width="160">
              <template #default="{ row }">
                <router-link
                  class="detail-link"
                  :to="{ name: 'hop-dong-sddv', query: { keyword: row.ma_hop_dong || selectedSdt } }"
                >
                  {{ row.ma_hop_dong || '—' }}
                </router-link>
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Tên trên HĐ" min-width="150" show-overflow-tooltip>
              <template #default="{ row }">
                {{ row.ten_khach_hang || '—' }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Ngày tạo" width="120" align="center">
              <template #default="{ row }">
                {{ formatDate(row.created_at) }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Tổng tiền" width="140" align="right">
              <template #default="{ row }">
                {{ formatMoney(row.tong_tien) }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Trạng thái" width="140" align="center">
              <template #default="{ row }">
                <CustomTag :type="sddvTrangThaiTagType(row.trang_thai)" size="small">
                  {{ sddvTrangThaiLabel(row.trang_thai) }}
                </CustomTag>
              </template>
            </CustomTableColumn>
          </CustomTable>
        </section>

        <section v-if="selectedHopDongChoThue.length" class="detail-block">
          <h4 class="detail-title">Hợp đồng thuê trang phục ({{ selectedHopDongChoThue.length }})</h4>
          <CustomTable :data="selectedHopDongChoThue" stripe row-key="id" style="width: 100%">
            <CustomTableColumn label="Mã HĐ" min-width="160">
              <template #default="{ row }">
                <router-link
                  class="detail-link"
                  :to="{ name: 'hop-dong-cho-thue', query: { keyword: row.ma_hop_dong || selectedSdt } }"
                >
                  {{ row.ma_hop_dong || '—' }}
                </router-link>
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Tên trên HĐ" min-width="150" show-overflow-tooltip>
              <template #default="{ row }">
                {{ row.ten_khach_hang || '—' }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Ngày thuê" width="120" align="center">
              <template #default="{ row }">
                {{ formatDate(row.ngay_thue || row.created_at) }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Tổng tiền" width="140" align="right">
              <template #default="{ row }">
                {{ formatMoney(row.tong_tien) }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Trạng thái" width="140" align="center">
              <template #default="{ row }">
                <CustomTag :type="choThueTrangThaiTagType(row.trang_thai)" size="small">
                  {{ choThueTrangThaiLabel(row.trang_thai) }}
                </CustomTag>
              </template>
            </CustomTableColumn>
          </CustomTable>
        </section>
      </div>
      <template #footer>
        <CustomButton @click="chiTietVisible = false">Đóng</CustomButton>
      </template>
    </CustomDialog>

    <CustomDialog v-model="lichSuVisible" :title="lichSuTitle" :width="640">
      <el-empty v-if="!lichSuEvents.length" description="Chưa có lịch sử khách" />
      <el-timeline v-else class="lich-su-timeline">
        <el-timeline-item
          v-for="event in lichSuEvents"
          :key="event.key"
          :timestamp="formatDate(event.date)"
          :type="event.tagType"
          placement="top"
        >
          <div class="timeline-title">{{ event.title }}</div>
          <div v-if="event.moTa" class="timeline-desc">{{ event.moTa }}</div>
        </el-timeline-item>
      </el-timeline>
      <template #footer>
        <CustomButton @click="lichSuVisible = false">Đóng</CustomButton>
      </template>
    </CustomDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { Clock, Search, View } from '@element-plus/icons-vue'
import { fetchKhachHang } from '@/api/khachHang'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { useTableColumns } from '@/composables/useTableColumns'
import {
  CustomButton,
  CustomCard,
  CustomCol,
  CustomDialog,
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

const tableColumns = [
  { key: 'ten_khach', label: 'Tên khách' },
  { key: 'sdt', label: 'Số điện thoại' },
  { key: 'nguon_khach', label: 'Nguồn khách' },
  { key: 'loai_hop_dong', label: 'Loại hợp đồng đã ký' },
  { key: 'so_note', label: 'Note' },
  { key: 'so_hop_dong_sddv', label: 'HĐ dịch vụ' },
  { key: 'so_hop_dong_cho_thue', label: 'HĐ thuê' },
  { key: 'tong_gia_tri_hop_dong', label: 'Tổng giá trị HĐ' },
  { key: 'cap_nhat_gan_nhat', label: 'Cập nhật gần nhất' },
]
const columnSettings = useTableColumns('khach-hang.danh-sach', tableColumns, {
  pin: { selection: false },
})

const loaiHopDongOptions = [
  { value: 'note_khach_moi', label: 'Note khách mới' },
  { value: 'hop_dong_sddv', label: 'Hợp đồng dịch vụ' },
  { value: 'hop_dong_cho_thue', label: 'Hợp đồng thuê trang phục' },
]

const noteTrangThaiOptions = [
  { value: 'cho_hen', label: 'Chờ hẹn' },
  { value: 'da_den', label: 'Đã đến' },
  { value: 'khong_den', label: 'Không đến' },
  { value: 'da_ky_hd', label: 'Đã ký HĐ' },
  { value: 'da_huy', label: 'Đã hủy' },
]

const sddvTrangThaiOptions = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
  { value: 'da_coc', label: 'Đã cọc' },
  { value: 'dang_thuc_hien', label: 'Đang thực hiện' },
  { value: 'da_huy', label: 'Đã hủy' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
]

const choThueTrangThaiOptions = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
  { value: 'cho_xac_nhan', label: 'Chờ xác nhận' },
  { value: 'dang_thue', label: 'Đang thuê' },
  { value: 'da_tra', label: 'Đã trả' },
  { value: 'qua_han', label: 'Quá hạn' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
  { value: 'da_huy', label: 'Đã hủy' },
]

const items = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const route = useRoute()
const keyword = ref(String(route.query.keyword || ''))
const filterLoaiHopDong = ref('')

const selectedRow = ref(null)
const chiTietVisible = ref(false)
const lichSuVisible = ref(false)

const selectedHopDongSddv = computed(() => selectedRow.value?.hop_dong_sddv || [])
const selectedHopDongChoThue = computed(() => selectedRow.value?.hop_dong_cho_thue || [])
const selectedSdt = computed(() => selectedRow.value?.sdt || '')

const chiTietTitle = computed(() => `Chi tiết hợp đồng · ${customerLabel(selectedRow.value)}`)
const lichSuTitle = computed(() => `Lịch sử khách · ${customerLabel(selectedRow.value)}`)

const lichSuEvents = computed(() => buildLichSu(selectedRow.value))

function customerLabel(row) {
  if (!row) return ''
  const ten = Array.isArray(row.ten_khach) && row.ten_khach.length ? row.ten_khach[0] : ''
  if (ten && row.sdt) return `${ten} · ${row.sdt}`
  return ten || row.sdt || 'Khách hàng'
}

function loaiHopDongLabel(value) {
  return loaiHopDongOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function loaiHopDongTagType(value) {
  const map = {
    note_khach_moi: 'info',
    hop_dong_sddv: '',
    hop_dong_cho_thue: 'warning',
  }
  return map[value] || 'info'
}

function optionLabel(options, value) {
  return options.find((opt) => opt.value === value)?.label || value || '—'
}

function noteTrangThaiLabel(value) {
  return optionLabel(noteTrangThaiOptions, value)
}

function sddvTrangThaiLabel(value) {
  return optionLabel(sddvTrangThaiOptions, value)
}

function choThueTrangThaiLabel(value) {
  return optionLabel(choThueTrangThaiOptions, value)
}

function sddvTrangThaiTagType(value) {
  const map = {
    moi_tao: 'info',
    nhap: 'info',
    da_coc: 'warning',
    dang_thuc_hien: '',
    da_huy: 'danger',
    hoan_thanh: 'success',
  }
  return map[value] || 'info'
}

function choThueTrangThaiTagType(value) {
  const map = {
    moi_tao: 'info',
    nhap: 'info',
    cho_xac_nhan: 'warning',
    dang_thue: '',
    da_tra: 'success',
    qua_han: 'danger',
    hoan_thanh: 'success',
    da_huy: 'danger',
  }
  return map[value] || 'info'
}

function isDraft(trangThai) {
  return ['moi_tao', 'nhap'].includes(trangThai)
}

function formatMoney(value) {
  if (value == null || value === '') return '—'
  const num = Number(value)
  if (Number.isNaN(num)) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function formatDate(value) {
  if (!value) return '—'
  const str = String(value).slice(0, 10)
  const [y, m, d] = str.split('-')
  if (!y || !m || !d) return str
  return `${d}/${m}/${y}`
}

function formatDateTime(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return formatDate(value)
  return date.toLocaleString('vi-VN')
}

function sortKeyFromDate(value) {
  if (!value) return '9999-12-31'
  const str = String(value)
  if (/^\d{4}-\d{2}-\d{2}/.test(str)) return str.slice(0, 19)
  return str
}

function buildLichSu(row) {
  if (!row) return []

  const events = []

  for (const note of row.note_khach_moi || []) {
    events.push({
      key: `note-${note.id}`,
      date: note.ngay_hen_lich || note.created_at,
      tagType: 'info',
      title: 'Note khách mới',
      moTa: [note.ten_khach, noteTrangThaiLabel(note.trang_thai)].filter(Boolean).join(' · '),
    })
    if (note.ngay_den_thuc_te) {
      events.push({
        key: `note-den-${note.id}`,
        date: note.ngay_den_thuc_te,
        tagType: 'success',
        title: 'Đến trung tâm',
        moTa: note.ten_khach || '',
      })
    }
  }

  for (const hd of row.hop_dong_sddv || []) {
    events.push({
      key: `sddv-${hd.id}`,
      date: hd.created_at,
      tagType: isDraft(hd.trang_thai) ? 'info' : 'primary',
      title: isDraft(hd.trang_thai) ? 'Tạo nháp hợp đồng dịch vụ' : 'Ký hợp đồng dịch vụ',
      moTa: [hd.ma_hop_dong, sddvTrangThaiLabel(hd.trang_thai), formatMoney(hd.tong_tien)]
        .filter((part) => part && part !== '—')
        .join(' · '),
    })
  }

  for (const hd of row.hop_dong_cho_thue || []) {
    events.push({
      key: `thue-${hd.id}`,
      date: hd.ngay_thue || hd.created_at,
      tagType: isDraft(hd.trang_thai) ? 'info' : 'warning',
      title: isDraft(hd.trang_thai) ? 'Tạo nháp hợp đồng thuê trang phục' : 'Ký hợp đồng thuê trang phục',
      moTa: [hd.ma_hop_dong, choThueTrangThaiLabel(hd.trang_thai), formatMoney(hd.tong_tien)]
        .filter((part) => part && part !== '—')
        .join(' · '),
    })
  }

  return events.sort((a, b) => sortKeyFromDate(a.date).localeCompare(sortKeyFromDate(b.date)))
}

function openChiTiet(row) {
  selectedRow.value = row
  chiTietVisible.value = true
}

function openLichSu(row) {
  selectedRow.value = row
  lichSuVisible.value = true
}

async function loadItems() {
  loading.value = true
  try {
    const { data } = await fetchKhachHang({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      loai_hop_dong: filterLoaiHopDong.value || undefined,
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
.cell-names {
  font-size: 13px;
  font-weight: 400;
  color: var(--el-text-color-primary);
  line-height: 1.45;
}

.cell-secondary {
  font-size: 12px;
  color: var(--el-text-color-regular);
}

.name-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.detail-sections {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-title {
  margin: 0 0 10px;
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.detail-link {
  font-weight: 600;
  color: var(--el-color-primary);
  text-decoration: none;
}

.detail-link:hover {
  text-decoration: underline;
}

.lich-su-timeline {
  padding-left: 4px;
}

.timeline-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  line-height: 1.4;
}

.timeline-desc {
  margin-top: 2px;
  font-size: 13px;
  color: var(--el-text-color-secondary);
  line-height: 1.4;
}
</style>
