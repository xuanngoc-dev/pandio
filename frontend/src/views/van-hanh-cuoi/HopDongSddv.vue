<template>
  <div class="hop-dong-sddv page-list">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar">
        <CustomCol :xs="24" :sm="12" :md="8" :lg="6">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo mã HĐ, thông tin khách hàng..."
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
        <CustomCol :xs="12" :sm="12" :md="8" :lg="5">
          <CustomSelect
            v-model="filterLoaiHopDongId"
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
        <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
          <CustomSelect
            v-model="filterTrangThai"
            placeholder="Trạng thái"
            clearable
            style="width: 100%"
            @change="onSearch"
          >
            <CustomOption
              v-for="opt in trangThaiOptions"
              :key="opt.value"
              :label="opt.label"
              :value="opt.value"
            />
          </CustomSelect>
        </CustomCol>
        <CustomCol :xs="24" :sm="24" :md="24" :lg="7">
          <div class="toolbar-actions">
            <CustomButton type="primary" plain @click="onSearch">
              <CustomIcon><Search /></CustomIcon>
              Tìm kiếm
            </CustomButton>
            <CustomButton
              plain
              :type="advancedFilterVisible || advancedFilterCount ? 'primary' : 'default'"
              @click="advancedFilterVisible = !advancedFilterVisible"
            >
              Lọc nâng cao
              <span
                v-if="advancedFilterCount"
                class="advanced-toggle__badge"
                :class="{ 'is-on-primary': advancedFilterVisible || advancedFilterCount }"
              >
                {{ advancedFilterCount }}
              </span>
              <CustomIcon
                class="advanced-toggle__arrow"
                :class="{ 'is-expanded': advancedFilterVisible }"
              >
                <ArrowDown />
              </CustomIcon>
            </CustomButton>
          </div>
        </CustomCol>
      </CustomRow>

      <div v-show="advancedFilterVisible" class="advanced-filter">
        <div class="advanced-filter__head">
          <span class="advanced-filter__title">Lọc theo điều phối</span>
          <CustomButton
            v-if="advancedFilterCount"
            link
            type="primary"
            @click="clearAdvancedFilters"
          >
            Xóa lọc
          </CustomButton>
        </div>
        <CustomRow :gutter="12" class="toolbar">
          <CustomCol :xs="24" :sm="12" :md="8" :lg="6">
            <CustomSelect
              v-model="filterLoaiQuayChupId"
              placeholder="Loại quay chụp"
              clearable
              filterable
              style="width: 100%"
              @change="onSearch"
            >
              <CustomOption
                v-for="item in loaiQuayChupOptions"
                :key="item.id"
                :label="item.ten_dich_vu"
                :value="item.id"
              />
            </CustomSelect>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8" :lg="6">
            <CustomDatePicker
              v-model="filterNgayChupRange"
              type="daterange"
              range-separator="—"
              start-placeholder="Ngày chụp từ"
              end-placeholder="Ngày chụp đến"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              unlink-panels
              clearable
              class="advanced-filter__datepicker"
              style="width: 100%"
              @change="onSearch"
            />
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="8" :lg="6">
            <CustomSelect
              v-model="filterSoDiemChup"
              placeholder="Số điểm chụp"
              clearable
              style="width: 100%"
              @change="onSearch"
            >
              <CustomOption
                v-for="n in soDiemChupOptions"
                :key="n"
                :label="`${n} điểm`"
                :value="n"
              />
            </CustomSelect>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6" :lg="6">
            <CustomSelect
              v-model="filterCoThoChup"
              placeholder="Thợ chụp"
              clearable
              style="width: 100%"
              @change="onSearch"
            >
              <CustomOption label="Có thợ chụp" value="1" />
              <CustomOption label="Chưa có thợ chụp" value="0" />
            </CustomSelect>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6" :lg="6">
            <CustomSelect
              v-model="filterCoThoMake"
              placeholder="Thợ make"
              clearable
              style="width: 100%"
              @change="onSearch"
            >
              <CustomOption label="Có thợ make" value="1" />
              <CustomOption label="Chưa có thợ make" value="0" />
            </CustomSelect>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6" :lg="6">
            <CustomSelect
              v-model="filterCoQuayPhim"
              placeholder="Quay phim"
              clearable
              style="width: 100%"
              @change="onSearch"
            >
              <CustomOption label="Có quay phim" value="1" />
              <CustomOption label="Chưa có quay phim" value="0" />
            </CustomSelect>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6" :lg="6">
            <CustomSelect
              v-model="filterCoThoEdit"
              placeholder="Thợ edit"
              clearable
              style="width: 100%"
              @change="onSearch"
            >
              <CustomOption label="Có thợ edit" value="1" />
              <CustomOption label="Chưa có thợ edit" value="0" />
            </CustomSelect>
          </CustomCol>
        </CustomRow>
      </div>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách hợp đồng</span>
          <BulkActionBar :actions="bulkActions" @action="onBulkAction">
            <TableColumnConfig :settings="columnSettings" />
            <CustomTooltip content="Danh sách hợp đồng nháp" placement="top">
              <CustomButton @click="openDrafts">
                <CustomIcon><Document /></CustomIcon>
                Nháp
              </CustomButton>
            </CustomTooltip>
            <CustomTooltip content="Thêm mới" placement="top">
              <CustomButton type="primary" :loading="creating" @click="openCreate">
                <CustomIcon><Plus /></CustomIcon>
                Thêm
              </CustomButton>
            </CustomTooltip>
          </BulkActionBar>
        </div>
      </template>

      <CustomTable
        :column-settings="columnSettings"
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
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ma_hop_dong')"
          label="Mã HĐ"
          prop="ma_hop_dong"
          min-width="160"
          show-overflow-tooltip
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('loai_hop_dong')"
          label="Loại hợp đồng"
          min-width="180"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.loai_hop_dong?.ten_hop_dong || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('khach_hang')"
          label="Khách hàng"
          min-width="180"
        >
          <template #default="{ row }">
            <div class="cell-stack">
              <div class="cell-ellipsis" :title="formatKhachHang(row)">{{ formatKhachHang(row) }}</div>
              <div v-if="formatSoDienThoai(row)" class="sub-text">
                {{ formatSoDienThoai(row) }}
              </div>
            </div>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('kenh_tiep_can')"
          label="Kênh tiếp cận"
          min-width="140"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.kenh_tiep_can || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('tong_tien')"
          label="Tổng tiền"
          min-width="130"
          align="right"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ formatMoney(row.tong_tien) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('tien_coc')"
          label="Tiền cọc"
          min-width="120"
          align="right"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ formatMoney(row.tien_coc) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguoi_tao')"
          label="Người tạo"
          min-width="140"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.nguoi_tao?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('trang_thai')"
          label="Trạng thái"
          min-width="130"
          align="center"
        >
          <template #default="{ row }">
            <CustomTag :type="trangThaiTagType(row.trang_thai)" size="small">
              {{ trangThaiLabel(row.trang_thai) }}
            </CustomTag>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('trang_thai_dieu_phoi')"
          label="Trạng thái điều phối"
          min-width="160"
          align="center"
        >
          <template #default="{ row }">
            <CustomTag :type="trangThaiDieuPhoiTagType(getKetQuaTrangThai(row))" size="small">
              {{ trangThaiDieuPhoiLabel(getKetQuaTrangThai(row)) }}
            </CustomTag>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ngay_tra_demo')"
          label="Ngày trả demo"
          min-width="140"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDieuPhoiSharedDate(row, 'ngay_tra_demo') }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ngay_tra_chinh_thuc')"
          label="Ngày trả chính thức"
          min-width="160"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDieuPhoiSharedDate(row, 'ngay_tra_chinh_thuc') }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('lich_quay_chup')"
          label="Lịch quay chụp"
          min-width="150"
          align="center"
        >
          <template #default="{ row }">
            <div class="lich-quay-chup-cell">
              <span>{{ lichQuayChupCountLabel(row) }}</span>
              <CustomTooltip content="Xem lịch quay chụp" placement="top">
                <CustomButton
                  type="primary"
                  link
                  :icon="Calendar"
                  @click="openLichQuayChup(row)"
                />
              </CustomTooltip>
            </div>
          </template>
        </CustomTableColumn>
        <template v-for="col in chiTietTableColumns" :key="col.key">
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible(col.key)"
            :config-key="col.key"
            :label="col.label"
            min-width="160"
            show-overflow-tooltip
          >
            <template #default="{ row }">
              {{ formatChiTietHopDongColumn(row, col) }}
            </template>
          </CustomTableColumn>
        </template>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('created_at')"
          label="Ngày tạo"
          min-width="120"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Thao tác" width="230" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip content="Xem hợp đồng" placement="top">
                <CustomButton type="info" link :icon="View" @click="openDetail(row)" />
              </CustomTooltip>
              <CustomTooltip content="Điều phối" placement="top">
                <CustomButton type="warning" link :icon="Position" @click="openDieuPhoi(row)" />
              </CustomTooltip>
              <CustomTooltip :content="thanhToanTooltip(row)" placement="top">
                <CustomButton
                  type="success"
                  link
                  :icon="Wallet"
                  :disabled="!canOpenThanhToan(row)"
                  @click="openThanhToan(row)"
                />
              </CustomTooltip>
              <CustomTooltip
                :content="canDoiTrangThai(row) ? 'Thay đổi trạng thái' : doiTrangThaiDisabledReason(row)"
                placement="top"
              >
                <CustomButton
                  link
                  :icon="Switch"
                  :disabled="!canDoiTrangThai(row)"
                  @click="openDoiTrangThai(row)"
                />
              </CustomTooltip>
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

    <HopDongSddvDraftModal
      ref="draftModalRef"
      v-model="draftModalVisible"
      @continue="onContinueDraft"
      @changed="loadItems"
    />

    <HopDongSddvFormModal
      v-model="formModalVisible"
      :hop-dong="currentHopDong"
      @saved="onFormSaved"
      @closed="onFormClosed"
    />

    <HopDongSddvDieuPhoiModal
      v-model="dieuPhoiModalVisible"
      :hop-dong-id="dieuPhoiHopDongId"
      @saved="loadItems"
    />

    <HopDongSddvDetailModal
      v-model="detailModalVisible"
      :hop-dong-id="detailHopDongId"
    />

    <HopDongSddvLichQuayChupModal
      v-model="lichQuayChupModalVisible"
      :hop-dong-id="lichQuayChupHopDongId"
    />

    <HopDongSddvThanhToanModal
      v-model="thanhToanModalVisible"
      :hop-dong="thanhToanHopDong"
      @saved="onThanhToanSaved"
    />

    <HopDongSddvDoiTrangThaiModal
      v-model="doiTrangThaiModalVisible"
      :hop-dong="doiTrangThaiHopDong"
      @saved="onDoiTrangThaiSaved"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ArrowDown, Calendar, Delete, Document, Edit, Plus, Position, Search, Switch, View, Wallet } from '@element-plus/icons-vue'
import { fetchDanhMucLoaiQuayChup } from '@/api/danhMucLoaiQuayChup'
import {
  deleteHopDongSuDungDichVu,
  fetchHopDongSuDungDichVu,
  getHopDongSuDungDichVu,
  khoiTaoHopDongSuDungDichVu,
} from '@/api/hopDongSuDungDichVu'
import { fetchLoaiHopDong, getLoaiHopDong } from '@/api/loaiHopDong'
import BulkActionBar from '@/components/BulkActionBar.vue'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { runBulk, useBulkSelection } from '@/composables/useBulkSelection'
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
import HopDongSddvDetailModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvDetailModal.vue'
import HopDongSddvDieuPhoiModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvDieuPhoiModal.vue'
import HopDongSddvDoiTrangThaiModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvDoiTrangThaiModal.vue'
import HopDongSddvDraftModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvDraftModal.vue'
import HopDongSddvFormModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvFormModal.vue'
import HopDongSddvLichQuayChupModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvLichQuayChupModal.vue'
import HopDongSddvThanhToanModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvThanhToanModal.vue'
import {
  SO_DIEM_CHUP_MAX,
  SO_DIEM_CHUP_MIN,
  firstDieuPhoiGiaTri,
  normalizeDieuPhoiSessions,
} from '@/utils/thongTinDieuPhoi'

const COLUMN_STORAGE_KEY = 'van-hanh-cuoi.hop-dong-sddv.v4'
const CHI_TIET_GROUP = 'Chi tiết hợp đồng'

const tableColumns = [
  { key: 'ma_hop_dong', label: 'Mã HĐ', group: 'Thông tin hợp đồng' },
  { key: 'loai_hop_dong', label: 'Loại hợp đồng', group: 'Thông tin hợp đồng' },
  { key: 'khach_hang', label: 'Khách hàng', group: 'Thông tin hợp đồng' },
  { key: 'kenh_tiep_can', label: 'Kênh tiếp cận', group: 'Thông tin hợp đồng' },
  { key: 'tong_tien', label: 'Tổng tiền', group: 'Thông tin hợp đồng' },
  { key: 'tien_coc', label: 'Tiền cọc', group: 'Thông tin hợp đồng' },
  { key: 'nguoi_tao', label: 'Người tạo', group: 'Thông tin hợp đồng' },
  { key: 'trang_thai', label: 'Trạng thái', group: 'Thông tin hợp đồng' },
  { key: 'created_at', label: 'Ngày tạo', group: 'Thông tin hợp đồng' },
  // Trạng thái điều phối lưu ở ket_qua_hop_dong.trang_thai (workflow sau khi gán nhân sự trong thong_tin_dieu_phoi)
  { key: 'trang_thai_dieu_phoi', label: 'Trạng thái điều phối', group: 'Thông tin điều phối' },
  { key: 'ngay_tra_demo', label: 'Ngày trả demo', group: 'Thông tin điều phối' },
  { key: 'ngay_tra_chinh_thuc', label: 'Ngày trả chính thức', group: 'Thông tin điều phối' },
  { key: 'lich_quay_chup', label: 'Lịch quay chụp', group: 'Thông tin điều phối' },
]

/** Cache noi_dung.truong theo loai_hop_dong_id */
const chiTietSchemaCache = new Map()
const chiTietTableColumns = ref([])

const columnSettings = useTableColumns(COLUMN_STORAGE_KEY, tableColumns, {
  onBeforeOpen: async () => {
    await syncChiTietColumns({ force: true })
  },
})

function buildChiTietColumnsFromTruong(truongList) {
  if (!Array.isArray(truongList)) return []
  return truongList
    .filter((field) => field?.key && field?.ten_truong)
    .map((field) => ({
      key: `hd_ct_${field.key}`,
      fieldKey: field.key,
      kieu: field.kieu || '',
      label: field.ten_truong,
      defaultVisible: false,
      group: CHI_TIET_GROUP,
    }))
}

function clearChiTietColumns() {
  chiTietTableColumns.value = []
  columnSettings.setExtraColumns([])
}

/**
 * Khi lọc theo loại HĐ: nạp schema noi_dung → cột "Chi tiết hợp đồng".
 * @param {{ force?: boolean }} [opts]
 */
async function syncChiTietColumns(opts = {}) {
  const { force = false } = opts
  const loaiId = filterLoaiHopDongId.value
  if (!loaiId) {
    clearChiTietColumns()
    return
  }

  const cacheKey = Number(loaiId)
  if (!force && chiTietSchemaCache.has(cacheKey)) {
    const cached = chiTietSchemaCache.get(cacheKey)
    chiTietTableColumns.value = cached
    columnSettings.setExtraColumns(cached, {
      storageKey: `${COLUMN_STORAGE_KEY}.detail.${cacheKey}`,
    })
    return
  }

  try {
    const { data } = await getLoaiHopDong(loaiId)
    const columns = buildChiTietColumnsFromTruong(data?.noi_dung?.truong)
    chiTietSchemaCache.set(cacheKey, columns)
    chiTietTableColumns.value = columns
    columnSettings.setExtraColumns(columns, {
      storageKey: `${COLUMN_STORAGE_KEY}.detail.${cacheKey}`,
    })
  } catch {
    clearChiTietColumns()
  }
}

const trangThaiOptions = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
  { value: 'da_coc', label: 'Đã cọc' },
  { value: 'dang_thuc_hien', label: 'Đang thực hiện' },
  { value: 'da_huy', label: 'Đã hủy' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
]

/**
 * Trạng thái điều phối (ket_qua_hop_dong.trang_thai.gia_tri).
 * null / rỗng được coi là "Chờ nhận" (nhân viên đã được gán nhưng chưa nhận việc).
 *
 * - cho_nhan: Chờ nhận — công việc đã gán nhân sự trong thong_tin_dieu_phoi, chờ nhân viên bấm nhận.
 * - dang_xu_ly: Đang xử lý — nhân viên đã nhận; đang làm (upload file gốc / file demo…).
 * - gui_khach_kiem_tra: Gửi khách kiểm tra — đã gửi khách xem; chờ phản hồi đồng ý / không đồng ý.
 * - san_xuat_in_an: Sản xuất & in ấn — khách đồng ý; đang sản xuất / chuẩn bị bàn giao.
 * - cho_nghiem_thu: Chờ nghiệm thu — đã bàn giao; chờ nghiệm thu hoàn thành hoặc làm lại.
 * - hoan_thanh: Hoàn thành — nghiệm thu xong, kết thúc quy trình điều phối.
 */
const trangThaiDieuPhoiOptions = [
  { value: 'cho_nhan', label: 'Chờ nhận' },
  { value: 'dang_xu_ly', label: 'Đang xử lý' },
  { value: 'gui_khach_kiem_tra', label: 'Gửi khách kiểm tra' },
  { value: 'san_xuat_in_an', label: 'Sản xuất & in ấn' },
  { value: 'cho_nghiem_thu', label: 'Chờ nghiệm thu' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
]

const items = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const route = useRoute()
const keyword = ref(String(route.query.keyword || ''))
const filterLoaiHopDongId = ref(null)
const filterTrangThai = ref('')
const advancedFilterVisible = ref(false)
const filterLoaiQuayChupId = ref(null)
const filterNgayChupRange = ref(null)
const filterSoDiemChup = ref(null)
const filterCoThoChup = ref(null)
const filterCoThoMake = ref(null)
const filterCoQuayPhim = ref(null)
const filterCoThoEdit = ref(null)
const loaiHopDongOptions = ref([])
const loaiQuayChupOptions = ref([])
const soDiemChupOptions = Array.from(
  { length: SO_DIEM_CHUP_MAX - SO_DIEM_CHUP_MIN + 1 },
  (_, i) => SO_DIEM_CHUP_MIN + i,
)
const bulkDeleting = ref(false)
const creating = ref(false)
const formModalVisible = ref(false)
const draftModalVisible = ref(false)
const draftModalRef = ref(null)
const currentHopDong = ref(null)
const dieuPhoiModalVisible = ref(false)
const dieuPhoiHopDongId = ref(null)
const detailModalVisible = ref(false)
const detailHopDongId = ref(null)
const lichQuayChupModalVisible = ref(false)
const lichQuayChupHopDongId = ref(null)
const thanhToanModalVisible = ref(false)
const thanhToanHopDong = ref(null)
const doiTrangThaiModalVisible = ref(false)
const doiTrangThaiHopDong = ref(null)

const { selectedCount, onSelectionChange, clearSelection, selectedIds } = useBulkSelection()

function isFilterFilled(value) {
  if (Array.isArray(value)) return value.some((item) => item != null && item !== '')
  return value != null && value !== ''
}

function presenceParam(value) {
  return value === '0' || value === '1' ? value : undefined
}

const advancedFilterCount = computed(() => {
  return [
    filterLoaiQuayChupId.value,
    filterNgayChupRange.value,
    filterSoDiemChup.value,
    filterCoThoChup.value,
    filterCoThoMake.value,
    filterCoQuayPhim.value,
    filterCoThoEdit.value,
  ].filter(isFilterFilled).length
})

const bulkActions = computed(() => [
  {
    key: 'delete',
    label: 'Xóa',
    type: 'danger',
    badge: selectedCount.value,
    badgeType: 'danger',
    loading: bulkDeleting.value,
    tooltip: selectedCount.value
      ? `Xóa ${selectedCount.value} hợp đồng đã chọn`
      : 'Chọn hợp đồng để xóa',
  },
])

function trangThaiLabel(value) {
  return trangThaiOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function trangThaiTagType(value) {
  const map = {
    moi_tao: 'info',
    nhap: '',
    da_coc: 'warning',
    dang_thuc_hien: 'primary',
    da_huy: 'danger',
    hoan_thanh: 'success',
  }
  return map[value] || 'info'
}

function trangThaiDieuPhoiLabel(value) {
  const key = value || 'cho_nhan'
  return trangThaiDieuPhoiOptions.find((opt) => opt.value === key)?.label || value || '—'
}

function trangThaiDieuPhoiTagType(value) {
  const map = {
    cho_nhan: 'info',
    dang_xu_ly: 'primary',
    gui_khach_kiem_tra: 'warning',
    san_xuat_in_an: '',
    cho_nghiem_thu: 'warning',
    hoan_thanh: 'success',
  }
  return map[value || 'cho_nhan'] || 'info'
}

function formatMoney(value) {
  if (value == null || value === '') return '—'
  const num = Number(value)
  if (Number.isNaN(num)) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function formatDate(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('vi-VN')
}

function getThongTin(row) {
  return row?.thong_tin_hop_dong && typeof row.thong_tin_hop_dong === 'object'
    ? row.thong_tin_hop_dong
    : {}
}

function lichQuayChupCount(row) {
  return normalizeDieuPhoiSessions(row?.thong_tin_dieu_phoi).length
}

function formatDieuPhoiSharedDate(row, key) {
  return formatDate(firstDieuPhoiGiaTri(row?.thong_tin_dieu_phoi, key))
}

function lichQuayChupCountLabel(row) {
  const count = lichQuayChupCount(row)
  if (!count) return '—'
  return `${count} lịch`
}

function formatDynamicValue(value, kieu = '') {
  if (value == null || value === '') return '—'
  if (typeof value === 'boolean' || kieu === 'checkbox' || kieu === 'switch') {
    return value ? 'Có' : 'Không'
  }
  if (Array.isArray(value) || kieu === 'checkbox_group') {
    return value.length ? value.map((item) => String(item)).join(', ') : '—'
  }
  if (kieu === 'money') return formatMoney(value)
  if (kieu === 'date' || kieu === 'datetime' || kieu === 'daterange') {
    if (Array.isArray(value)) {
      return value.map((item) => formatDate(item)).join(' → ')
    }
    return formatDate(value)
  }
  if (kieu === 'time') return String(value)
  return String(value)
}

function formatChiTietHopDongColumn(row, col) {
  if (!col?.fieldKey) return '—'
  const info = getThongTin(row)
  return formatDynamicValue(info[col.fieldKey], col.kieu)
}

function formatKhachHang(row) {
  if (row.ten_khach_hang) return row.ten_khach_hang
  const info = getThongTin(row)
  const tenChuRe = info.tenChuRe || info.ten_chu_re
  const tenCoDau = info.tenCoDau || info.ten_co_dau
  if (tenChuRe || tenCoDau) {
    return [tenChuRe, tenCoDau].filter(Boolean).join(' & ')
  }
  return info.hoTenKhachHang || info.ho_ten_khach_hang || info.hoTenKhach || '—'
}

function formatSoDienThoai(row) {
  if (row.sdt_khach_hang) return row.sdt_khach_hang
  const info = getThongTin(row)
  return info.soDienThoai || info.so_dien_thoai || info.sdt || ''
}

async function loadLoaiHopDongOptions() {
  try {
    const { data } = await fetchLoaiHopDong({ per_page: 100, trang_thai: 'hoat_dong' })
    loaiHopDongOptions.value = data.data || []
  } catch {
    loaiHopDongOptions.value = []
  }
}

async function loadLoaiQuayChupOptions() {
  try {
    const { data } = await fetchDanhMucLoaiQuayChup({ per_page: 100, trang_thai: 'active' })
    loaiQuayChupOptions.value = (data.data || []).slice().sort((a, b) =>
      String(a.ten_dich_vu || '').localeCompare(String(b.ten_dich_vu || ''), 'vi'),
    )
  } catch {
    loaiQuayChupOptions.value = []
  }
}

function resetAdvancedFilters() {
  filterLoaiQuayChupId.value = null
  filterNgayChupRange.value = null
  filterSoDiemChup.value = null
  filterCoThoChup.value = null
  filterCoThoMake.value = null
  filterCoQuayPhim.value = null
  filterCoThoEdit.value = null
}

function clearAdvancedFilters() {
  resetAdvancedFilters()
  onSearch()
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchHopDongSuDungDichVu({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      loai_hop_dong_id: filterLoaiHopDongId.value || undefined,
      trang_thai: filterTrangThai.value || undefined,
      loai_quay_chup_id: filterLoaiQuayChupId.value || undefined,
      ngay_chup_tu: filterNgayChupRange.value?.[0] || undefined,
      ngay_chup_den: filterNgayChupRange.value?.[1] || undefined,
      so_diem_chup: isFilterFilled(filterSoDiemChup.value) ? filterSoDiemChup.value : undefined,
      co_tho_chup: presenceParam(filterCoThoChup.value),
      co_tho_make: presenceParam(filterCoThoMake.value),
      co_quay_phim: presenceParam(filterCoQuayPhim.value),
      co_tho_edit: presenceParam(filterCoThoEdit.value),
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

async function onSearch() {
  page.value = 1
  await syncChiTietColumns()
  loadItems()
}

async function openCreate() {
  creating.value = true
  try {
    const { data } = await khoiTaoHopDongSuDungDichVu()
    currentHopDong.value = data
    formModalVisible.value = true
  } catch {
    // interceptor
  } finally {
    creating.value = false
  }
}

function openDrafts() {
  draftModalVisible.value = true
}

function openDetail(row) {
  detailHopDongId.value = row.id
  detailModalVisible.value = true
}

function openLichQuayChup(row) {
  lichQuayChupHopDongId.value = row.id
  lichQuayChupModalVisible.value = true
}

function openDieuPhoi(row) {
  if (!row?.loai_hop_dong_id) {
    ElMessage.warning('Hợp đồng chưa chọn loại hợp đồng.')
    return
  }
  dieuPhoiHopDongId.value = row.id
  dieuPhoiModalVisible.value = true
}

function getDaThanhToan(row) {
  return (
    (Number(row?.so_tien_thanh_toan_lan_1) || 0) +
    (Number(row?.so_tien_thanh_toan_lan_2) || 0) +
    (Number(row?.so_tien_thanh_toan_lan_3) || 0)
  )
}

function getKhachPhaiThanhToan(row) {
  const tong = Number(row?.tong_tien) || 0
  const phatSinh = Number(row?.phat_sinh) || 0
  const chietKhau = Number(row?.chiet_khau) || 0
  const giamGia = Number(row?.khuyen_mai_theo_ma_giam_gia) || 0
  return Math.max(0, tong + phatSinh - chietKhau - giamGia)
}

function getConLai(row) {
  return Math.max(0, getKhachPhaiThanhToan(row) - getDaThanhToan(row))
}

function hasPaymentSlot(row) {
  return (
    (Number(row?.so_tien_thanh_toan_lan_1) || 0) <= 0 ||
    (Number(row?.so_tien_thanh_toan_lan_2) || 0) <= 0 ||
    (Number(row?.so_tien_thanh_toan_lan_3) || 0) <= 0
  )
}

function canThanhToan(row) {
  return (
    ['da_coc', 'dang_thuc_hien'].includes(row?.trang_thai) &&
    getConLai(row) > 0 &&
    hasPaymentSlot(row)
  )
}

function canViewLichSuThanhToan(row) {
  return getDaThanhToan(row) > 0
}

function canOpenThanhToan(row) {
  return canThanhToan(row) || canViewLichSuThanhToan(row)
}

function thanhToanTooltip(row) {
  if (canThanhToan(row)) return 'Thanh toán'
  if (canViewLichSuThanhToan(row)) return 'Xem lịch sử thanh toán'
  return thanhToanDisabledReason(row)
}

function thanhToanDisabledReason(row) {
  if (row?.trang_thai === 'da_huy') return 'Hợp đồng đã hủy'
  if (row?.trang_thai === 'nhap' || row?.trang_thai === 'moi_tao') {
    return 'Hợp đồng chưa đủ điều kiện thanh toán'
  }
  if (!['da_coc', 'dang_thuc_hien', 'hoan_thanh'].includes(row?.trang_thai)) {
    return 'Không thể mở thanh toán'
  }
  if (getDaThanhToan(row) <= 0) return 'Chưa có lịch sử thanh toán'
  return 'Không thể thanh toán'
}

async function openThanhToan(row) {
  if (!canOpenThanhToan(row)) {
    ElMessage.warning(thanhToanDisabledReason(row))
    return
  }
  thanhToanHopDong.value = row
  thanhToanModalVisible.value = true
  try {
    const { data } = await getHopDongSuDungDichVu(row.id)
    thanhToanHopDong.value = data
  } catch {
    // keep list row as fallback; modal will also try reload
  }
}

function onThanhToanSaved() {
  loadItems()
}

function getKetQuaTrangThai(row) {
  const raw = row?.ket_qua_hop_dong
  const ketQua = raw && typeof raw === 'object' && !Array.isArray(raw) ? raw : {}
  const giaTri = ketQua?.trang_thai?.gia_tri
  return giaTri == null || giaTri === '' ? null : String(giaTri)
}

function canDoiTrangThai(row) {
  if (!row?.id) return false
  if (['nhap', 'moi_tao'].includes(row.trang_thai)) return false

  const ended = ['da_huy', 'hoan_thanh'].includes(row.trang_thai)
  const ketQua = getKetQuaTrangThai(row)
  const hasWorkflowAction =
    ketQua === 'gui_khach_kiem_tra' || ketQua === 'cho_nghiem_thu'
  const canHuyOrTatToan = !ended

  return canHuyOrTatToan || hasWorkflowAction
}

function doiTrangThaiDisabledReason(row) {
  if (['nhap', 'moi_tao'].includes(row?.trang_thai)) {
    return 'Hợp đồng nháp / mới tạo chưa đổi trạng thái tại đây'
  }
  if (['da_huy', 'hoan_thanh'].includes(row?.trang_thai)) {
    const ketQua = getKetQuaTrangThai(row)
    if (ketQua !== 'gui_khach_kiem_tra' && ketQua !== 'cho_nghiem_thu') {
      return 'Hợp đồng đã kết thúc'
    }
  }
  return 'Không thể đổi trạng thái'
}

async function openDoiTrangThai(row) {
  if (!canDoiTrangThai(row)) {
    ElMessage.warning(doiTrangThaiDisabledReason(row))
    return
  }
  doiTrangThaiHopDong.value = row
  doiTrangThaiModalVisible.value = true
  try {
    const { data } = await getHopDongSuDungDichVu(row.id)
    doiTrangThaiHopDong.value = data
  } catch {
    // keep list row
  }
}

function onDoiTrangThaiSaved() {
  loadItems()
}

async function openEdit(row) {
  try {
    const { data } = await getHopDongSuDungDichVu(row.id)
    currentHopDong.value = data
  } catch {
    currentHopDong.value = row
  }
  formModalVisible.value = true
}

async function onContinueDraft(row) {
  draftModalVisible.value = false
  try {
    const { data } = await getHopDongSuDungDichVu(row.id)
    currentHopDong.value = data
  } catch {
    currentHopDong.value = row
  }
  formModalVisible.value = true
}

async function onFormSaved(hopDong) {
  currentHopDong.value = hopDong
  // Sau khi hoàn thành bước thanh toán → tìm đúng HĐ vừa lưu trên danh sách
  if (hopDong?.trang_thai === 'dang_thuc_hien' && hopDong?.ma_hop_dong) {
    keyword.value = hopDong.ma_hop_dong
    filterLoaiHopDongId.value = null
    filterTrangThai.value = 'dang_thuc_hien'
    resetAdvancedFilters()
    page.value = 1
  }
  await syncChiTietColumns()
  loadItems()
  if (draftModalVisible.value) {
    draftModalRef.value?.reload?.()
  }
}

function onFormClosed() {
  if (draftModalVisible.value) {
    draftModalRef.value?.reload?.()
  }
}

async function onBulkAction(key) {
  if (key === 'delete') await bulkRemove()
}

async function bulkRemove() {
  const ids = selectedIds.value
  if (!ids.length) return

  await ElMessageBox.confirm(`Xóa ${ids.length} hợp đồng đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteHopDongSuDungDichVu(id))
    ElMessage.success(`Đã xóa ${ids.length} hợp đồng.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa hợp đồng "${row.ma_hop_dong}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteHopDongSuDungDichVu(row.id)
    ElMessage.success('Đã xóa hợp đồng sử dụng dịch vụ.')
    await loadItems()
  } catch {
    // interceptor
  }
}

onMounted(() => {
  loadLoaiHopDongOptions()
  loadLoaiQuayChupOptions()
  loadItems()
})
</script>

<style scoped lang="scss">
.toolbar-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.advanced-toggle__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 999px;
  background: var(--el-color-primary);
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  line-height: 1;

  &.is-on-primary {
    background: #fff;
    color: var(--el-color-primary);
  }
}

.advanced-toggle__arrow {
  margin-left: 2px;
  transition: transform 0.2s ease;

  &.is-expanded {
    transform: rotate(180deg);
  }
}

.advanced-filter {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid var(--el-border-color-lighter);

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

.advanced-filter__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.advanced-filter__title {
  font-size: 13px;
  font-weight: 600;
  color: var(--el-text-color-regular);
}

.sub-text {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.cell-stack {
  min-width: 0;
}

.cell-ellipsis {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.lich-quay-chup-cell {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}
</style>
