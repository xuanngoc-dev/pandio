<template>
  <div class="hop-dong-cho-thue">
    <CustomCard shadow="hover" class="filter-card">
      <div class="toolbar">
        <CustomInput
          v-model="keyword"
          placeholder="Tìm theo mã HĐ, tên khách hàng, SĐT..."
          clearable
          style="max-width: 360px"
          @clear="onSearch"
          @keyup.enter="onSearch"
        >
          <template #prefix>
            <CustomIcon><Search /></CustomIcon>
          </template>
        </CustomInput>
        <CustomSelect
          v-model="filterTrangThai"
          placeholder="Trạng thái"
          clearable
          style="width: 180px"
          @change="onSearch"
        >
          <CustomOption
            v-for="opt in trangThaiOptions"
            :key="opt.value"
            :label="opt.label"
            :value="opt.value"
          />
        </CustomSelect>
        <CustomButton type="primary" plain @click="onSearch">
          <CustomIcon><Search /></CustomIcon>
          Tìm kiếm
        </CustomButton>
      </div>
    </CustomCard>

    <CustomCard shadow="hover" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách hợp đồng cho thuê trang phục</span>
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
          min-width="140"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('khach_hang')"
          label="Khách hàng"
          min-width="160"
        >
          <template #default="{ row }">
            <div>{{ row.ten_khach_hang }}</div>
            <div class="sub-text">{{ row.sdt_khach_hang }}</div>
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ngay_thue')"
          label="Ngày thuê"
          width="120"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDate(row.ngay_thue) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('ngay_tra_du_kien')"
          label="Ngày trả DK"
          width="120"
          align="center"
        >
          <template #default="{ row }">
            {{ formatDate(row.ngay_tra_du_kien) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('so_ngay_thue')"
          label="Số ngày"
          width="90"
          align="center"
          prop="so_ngay_thue"
        />
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('tong_tien')"
          label="Tổng tiền"
          width="130"
          align="right"
        >
          <template #default="{ row }">
            {{ formatMoney(row.tong_tien) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('nguoi_cho_thue')"
          label="Người cho thuê"
          min-width="140"
        >
          <template #default="{ row }">
            {{ row.nguoi_cho_thue_user?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('san_pham')"
          label="Sản phẩm"
          width="90"
          align="center"
        >
          <template #default="{ row }">
            {{ row.san_pham_cho_thue?.length || 0 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn
          v-if="columnSettings.isColumnVisible('trang_thai')"
          label="Trạng thái"
          width="130"
          align="center"
        >
          <template #default="{ row }">
            <el-tag :type="trangThaiTagType(row.trang_thai)" size="small">
              {{ trangThaiLabel(row.trang_thai) }}
            </el-tag>
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
      :title="isDraftFlow ? 'Thêm hợp đồng cho thuê' : 'Sửa hợp đồng cho thuê'"
      :width="1400"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Mã hợp đồng" prop="ma_hop_dong">
              <CustomInput v-model="form.ma_hop_dong" readonly placeholder="Mã tự sinh" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Tên khách hàng" prop="ten_khach_hang">
              <CustomInput v-model="form.ten_khach_hang" placeholder="Nhập tên khách hàng" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="SĐT khách hàng" prop="sdt_khach_hang">
              <CustomInput v-model="form.sdt_khach_hang" placeholder="Nhập số điện thoại" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Người tham gia" prop="nguoi_tham_gia">
              <CustomSelect v-model="form.nguoi_tham_gia" placeholder="Chọn người tham gia" filterable multiple
                collapse-tags collapse-tags-tooltip style="width: 100%">
                <CustomOption v-for="user in userOptions" :key="user.id" :label="user.name" :value="user.id" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Ngày thuê" prop="ngay_thue">
              <el-date-picker
                v-model="form.ngay_thue"
                type="date"
                placeholder="Chọn ngày thuê"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                @change="updateSoNgayThue"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Ngày trả dự kiến" prop="ngay_tra_du_kien">
              <el-date-picker
                v-model="form.ngay_tra_du_kien"
                type="date"
                placeholder="Chọn ngày trả dự kiến"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                @change="updateSoNgayThue"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Số ngày thuê">
              <CustomInput :model-value="String(soNgayThue)" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol v-if="!isDraftFlow" :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect
                v-model="form.trang_thai"
                placeholder="Chọn trạng thái"
                style="width: 100%"
              >
                <CustomOption
                  v-for="opt in trangThaiOptions"
                  :key="opt.value"
                  :label="opt.label"
                  :value="opt.value"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
        </CustomRow>

        <div class="san-pham-section">
          <div class="section-header">
            <div class="section-header__title-wrap">
              <span class="section-title">Sản phẩm cho thuê</span>
              <span v-if="form.san_pham_cho_thue.length" class="section-count">
                Đã chọn {{ form.san_pham_cho_thue.length }} sản phẩm
              </span>
            </div>
          </div>

          <div class="san-pham-section__body">
            <div class="san-pham-picker" :class="{ 'is-collapsed': !sanPhamExpanded }">
              <button
                type="button"
                class="san-pham-picker__toggle"
                @click="sanPhamExpanded = !sanPhamExpanded"
              >
                <span>Danh sách trang phục</span>
                <CustomIcon class="section-header__arrow" :class="{ 'is-expanded': sanPhamExpanded }">
                  <ArrowDown />
                </CustomIcon>
              </button>

              <div v-show="sanPhamExpanded" class="san-pham-picker__body">
                <div class="san-pham-filter">
                  <CustomInput
                    v-model="sanPhamKeyword"
                    placeholder="Tìm nhanh theo mã, tên trang phục..."
                    clearable
                    class="san-pham-filter__keyword"
                    @input="onSanPhamSearch"
                    @clear="onSanPhamSearch"
                  >
                    <template #prefix>
                      <CustomIcon><Search /></CustomIcon>
                    </template>
                  </CustomInput>
                  <MoneyInput
                    v-model="sanPhamGiaTu"
                    placeholder="Giá từ"
                    clearable
                    class="san-pham-filter__price"
                    @update:model-value="onSanPhamSearch"
                    @clear="onSanPhamSearch"
                  />
                  <MoneyInput
                    v-model="sanPhamGiaDen"
                    placeholder="Giá đến"
                    clearable
                    class="san-pham-filter__price"
                    @update:model-value="onSanPhamSearch"
                    @clear="onSanPhamSearch"
                  />
                </div>

                <div v-loading="trangPhucLoading" class="san-pham-card-grid">
                  <CustomTooltip
                    v-for="tp in trangPhucOptions"
                    :key="tp.id"
                    :disabled="!isSanPhamBusy(tp)"
                    content="Sản phẩm đã được sử dụng trong khoảng thời gian bạn chọn"
                    placement="top"
                  >
                    <button
                      type="button"
                      class="san-pham-card"
                      :class="{
                        'is-selected': isSanPhamSelected(tp.id),
                        'is-disabled': isSanPhamBusy(tp),
                      }"
                      @click="onSanPhamCardClick(tp)"
                    >
                      <div class="san-pham-card__image">
                        <el-avatar
                          v-if="tp.hinh_anh"
                          :size="40"
                          :src="mediaUrl(tp.hinh_anh)"
                          shape="square"
                          class="san-pham-card__avatar"
                        />
                        <div v-else class="san-pham-card__placeholder">
                          <CustomIcon :size="24"><Picture /></CustomIcon>
                        </div>
                        <span v-if="isSanPhamSelected(tp.id)" class="san-pham-card__check">
                          <CustomIcon><Check /></CustomIcon>
                        </span>
                      </div>
                      <div class="san-pham-card__body">
                        <div class="san-pham-card__code">{{ tp.ma_san_pham }}</div>
                        <div class="san-pham-card__name" :title="tp.ten_san_pham">{{ tp.ten_san_pham }}</div>
                        <div class="san-pham-card__price">{{ formatMoney(tp.gia_cho_thue) }}</div>
                      </div>
                      <CustomTooltip
                        v-if="tp.co_lich_cho_thue"
                        content="Xem lịch sử dụng"
                        placement="top"
                      >
                        <span
                          class="san-pham-card__calendar"
                          @click.stop="openLichChoThue(tp)"
                        >
                          <CustomIcon :size="14"><Calendar /></CustomIcon>
                        </span>
                      </CustomTooltip>
                    </button>
                  </CustomTooltip>
                  <div v-if="!trangPhucLoading && !trangPhucOptions.length" class="san-pham-empty">
                    Không tìm thấy trang phục phù hợp.
                  </div>
                </div>
              </div>
            </div>

            <div v-if="selectedSanPhamList.length" class="selected-san-pham">
              <div class="selected-san-pham__title">Danh sách đã chọn</div>
              <CustomTable :data="selectedSanPhamList" stripe style="width: 100%">
                <CustomTableColumn label="Hình ảnh" width="90" align="center">
                  <template #default="{ row }">
                    <el-avatar
                      v-if="row.hinh_anh"
                      :size="48"
                      :src="mediaUrl(row.hinh_anh)"
                      shape="square"
                      class="product-thumb"
                    />
                    <span v-else class="no-image">—</span>
                  </template>
                </CustomTableColumn>
                <CustomTableColumn prop="ten_san_pham" label="Tên sản phẩm" min-width="160" />
                <CustomTableColumn prop="ma_san_pham" label="Mã SP" width="120" />
                <CustomTableColumn label="Giá cho thuê" width="140" align="right">
                  <template #default="{ row }">
                    {{ formatMoney(row.gia_cho_thue) }}
                  </template>
                </CustomTableColumn>
                <CustomTableColumn label="Ghi chú" min-width="200">
                  <template #default="{ row }">
                    <CustomInput
                      v-model="row.formItem.ghi_chu"
                      placeholder="Ghi chú sản phẩm"
                      clearable
                    />
                  </template>
                </CustomTableColumn>
                <CustomTableColumn label="Thao tác" width="80" align="center">
                  <template #default="{ row }">
                    <CustomButton type="danger" link :icon="Delete" @click="removeSanPham(row.id)" />
                  </template>
                </CustomTableColumn>
              </CustomTable>
              <div class="selected-san-pham__summary">
                <span>Tổng giá cho thuê/ngày: <strong>{{ formatMoney(tongGiaChoThue) }}</strong></span>
                <span>× {{ soNgayThue }} ngày = <strong>{{ formatMoney(tongTienTuTinh) }}</strong></span>
              </div>
            </div>
            <div v-else class="selected-san-pham-empty">
              Chưa chọn sản phẩm nào. Nhấn vào thẻ trang phục phía trên để thêm.
            </div>
          </div>
        </div>

        <CustomRow :gutter="16" class="summary-row">
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Tổng tiền" prop="tong_tien">
              <MoneyInput v-model="form.tong_tien" style="width: 100%" />
              <div v-if="tongTienTuTinh !== form.tong_tien" class="tong-tien-hint">
                Gợi ý: {{ formatMoney(tongTienTuTinh) }}
                <CustomButton type="primary" link size="small" @click="applyTongTienTuTinh">
                  Áp dụng
                </CustomButton>
              </div>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Giảm giá" prop="giam_gia">
              <MoneyInput v-model="form.giam_gia" style="width: 100%" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Tiền cọc" prop="tien_coc">
              <MoneyInput v-model="form.tien_coc" style="width: 100%" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Còn lại">
              <CustomInput :model-value="formatMoney(conLai)" readonly class="con-lai-input" />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>

        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Ghi chú sale" prop="ghi_chu_sale">
              <CustomInput
                v-model="form.ghi_chu_sale"
                type="textarea"
                :rows="3"
                placeholder="Ghi chú nội bộ của sale"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Ghi chú khách" prop="ghi_chu_khach">
              <CustomInput
                v-model="form.ghi_chu_khach"
                type="textarea"
                :rows="3"
                placeholder="Ghi chú dành cho khách hàng"
              />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
      <template #footer>
        <div class="footer-actions">
          <CustomButton @click="dialogVisible = false">Đóng</CustomButton>
          <CustomButton
            v-if="isDraftFlow"
            :loading="savingDraft"
            @click="saveDraft"
          >
            Lưu nháp
          </CustomButton>
          <CustomButton
            v-if="canCancelHopDong"
            type="danger"
            plain
            :loading="cancelling"
            @click="cancelHopDong"
          >
            Huỷ hợp đồng
          </CustomButton>
          <CustomButton type="primary" :loading="saving" @click="save">Lưu</CustomButton>
        </div>
      </template>
    </CustomDialog>

    <HopDongChoThueDraftModal
      ref="draftModalRef"
      v-model="draftModalVisible"
      @continue="onContinueDraft"
      @changed="loadItems"
    />

    <CustomDialog
      v-model="lichModalVisible"
      :title="lichModalTitle"
      :width="1100"
      @closed="onLichModalClosed"
    >
      <CustomTable v-loading="lichLoading" :data="lichItems" stripe style="width: 100%">
        <CustomTableColumn label="Mã HĐ" prop="hop_dong.ma_hop_dong" min-width="140" />
        <CustomTableColumn label="Khách hàng" min-width="160">
          <template #default="{ row }">
            <div>{{ row.hop_dong?.ten_khach_hang || '—' }}</div>
            <div v-if="row.hop_dong?.sdt_khach_hang" class="sub-text">
              {{ row.hop_dong.sdt_khach_hang }}
            </div>
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Ngày bắt đầu" width="120" align="center">
          <template #default="{ row }">
            {{ formatDate(row.ngay_bat_dau) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Ngày KT dự kiến" width="130" align="center">
          <template #default="{ row }">
            {{ formatDate(row.ngay_ket_thuc_du_kien) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Ngày KT thực tế" width="130" align="center">
          <template #default="{ row }">
            {{ formatDate(row.ngay_ket_thuc_thuc_te) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Trạng thái" width="120" align="center">
          <template #default="{ row }">
            <el-tag :type="trangThaiTagType(row.hop_dong?.trang_thai)" size="small">
              {{ trangThaiLabel(row.hop_dong?.trang_thai) }}
            </el-tag>
          </template>
        </CustomTableColumn>
      </CustomTable>
      <div v-if="!lichLoading && !lichItems.length" class="lich-empty">
        Chưa có lịch sử dụng sản phẩm.
      </div>
      <template #footer>
        <CustomButton @click="lichModalVisible = false">Đóng</CustomButton>
      </template>
    </CustomDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ArrowDown, Calendar, Check, Delete, Document, Edit, Picture, Plus, Search } from '@element-plus/icons-vue'
import {
  deleteHopDongChoThueTrangPhuc,
  fetchHopDongChoThueTrangPhuc,
  getHopDongChoThueTrangPhuc,
  khoiTaoHopDongChoThueTrangPhuc,
  updateHopDongChoThueTrangPhuc,
} from '@/api/hopDongChoThueTrangPhuc'
import HopDongChoThueDraftModal from '@/views/van-hanh-cuoi/hop-dong-cho-thue/HopDongChoThueDraftModal.vue'
import { fetchTrangPhuc, fetchTrangPhucLichChoThue } from '@/api/trangPhuc'
import { fetchUsers } from '@/api/users'
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
  CustomOption,
  CustomRow,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
  MoneyInput,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'
import { mediaUrl } from '@/utils/media'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const tableColumns = [
  { key: 'ma_hop_dong', label: 'Mã HĐ' },
  { key: 'khach_hang', label: 'Khách hàng' },
  { key: 'ngay_thue', label: 'Ngày thuê' },
  { key: 'ngay_tra_du_kien', label: 'Ngày trả DK' },
  { key: 'so_ngay_thue', label: 'Số ngày' },
  { key: 'tong_tien', label: 'Tổng tiền' },
  { key: 'nguoi_cho_thue', label: 'Người cho thuê' },
  { key: 'san_pham', label: 'Sản phẩm' },
  { key: 'trang_thai', label: 'Trạng thái' },
]
const columnSettings = useTableColumns('van-hanh-cuoi.hop-dong-cho-thue', tableColumns)

const trangThaiOptions = [
  { value: 'cho_xac_nhan', label: 'Chờ xác nhận' },
  { value: 'dang_thue', label: 'Đang thuê' },
  { value: 'da_tra', label: 'Đã trả' },
  { value: 'qua_han', label: 'Quá hạn' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
  { value: 'da_huy', label: 'Đã hủy' },
]

const trangThaiAllOptions = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
  ...trangThaiOptions,
]

const items = ref([])
const loading = ref(false)
const creating = ref(false)
const saving = ref(false)
const savingDraft = ref(false)
const cancelling = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const filterTrangThai = ref('')

const userOptions = ref([])
const trangPhucOptions = ref([])
const trangPhucCache = ref({})
const sanPhamKeyword = ref('')
const sanPhamGiaTu = ref(null)
const sanPhamGiaDen = ref(null)
const sanPhamExpanded = ref(true)
const trangPhucLoading = ref(false)
let sanPhamSearchTimer = null

const dialogVisible = ref(false)
const editingId = ref(null)
const isDraftFlow = ref(false)
const formRef = ref(null)
const bulkDeleting = ref(false)
const draftModalVisible = ref(false)
const draftModalRef = ref(null)
const lichModalVisible = ref(false)
const lichLoading = ref(false)
const lichItems = ref([])
const lichCurrentProduct = ref(null)

const lichModalTitle = computed(() => {
  const product = lichCurrentProduct.value
  if (!product) return 'Lịch sử dụng sản phẩm'
  return `Lịch sử dụng — ${product.ma_san_pham || product.ten_san_pham || ''}`
})

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
      ? `Xóa ${selectedCount.value} hợp đồng đã chọn`
      : 'Chọn hợp đồng để xóa',
  },
])

const emptySanPhamItem = (sanPhamId) => ({
  san_pham_id: sanPhamId,
  ghi_chu: '',
})

const emptyForm = () => ({
  ma_hop_dong: '',
  ten_khach_hang: '',
  sdt_khach_hang: '',
  ngay_thue: '',
  ngay_tra_du_kien: '',
  ngay_tra_chinh_thuc: '',
  tong_tien: 0,
  giam_gia: 0,
  tien_coc: 0,
  trang_thai: 'cho_xac_nhan',
  nguoi_cho_thue: null,
  nguoi_tham_gia: [],
  ghi_chu_sale: '',
  ghi_chu_khach: '',
  san_pham_cho_thue: [],
})

const form = reactive(emptyForm())

const rules = {
  ma_hop_dong: [{ required: true, message: 'Vui lòng nhập mã hợp đồng', trigger: 'blur' }],
  ten_khach_hang: [{ required: true, message: 'Vui lòng nhập tên khách hàng', trigger: 'blur' }],
  sdt_khach_hang: [{ required: true, message: 'Vui lòng nhập SĐT khách hàng', trigger: 'blur' }],
  ngay_thue: [{ required: true, message: 'Vui lòng chọn ngày thuê', trigger: 'change' }],
  ngay_tra_du_kien: [{ required: true, message: 'Vui lòng chọn ngày trả dự kiến', trigger: 'change' }],
}

const soNgayThue = computed(() => {
  if (!form.ngay_thue || !form.ngay_tra_du_kien) return 0
  const start = new Date(form.ngay_thue)
  const end = new Date(form.ngay_tra_du_kien)
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) return 0
  const diff = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1
  return Math.max(1, diff)
})

const selectedSanPhamList = computed(() =>
  form.san_pham_cho_thue
    .map((item) => {
      const cached = trangPhucCache.value[item.san_pham_id]
      if (!cached) return null
      return {
        ...cached,
        id: cached.id ?? item.san_pham_id,
        formItem: item,
      }
    })
    .filter(Boolean),
)

const tongGiaChoThue = computed(() =>
  selectedSanPhamList.value.reduce((sum, item) => sum + (Number(item.gia_cho_thue) || 0), 0),
)

const tongTienTuTinh = computed(() => soNgayThue.value * tongGiaChoThue.value)

const conLai = computed(() => {
  const tong = Number(form.tong_tien) || 0
  const giam = Number(form.giam_gia) || 0
  const coc = Number(form.tien_coc) || 0
  return tong - giam - coc
})

function currentUserId() {
  return authStore.user?.id ?? null
}

function ensureUserInOptions(user) {
  if (!user?.id) return
  const exists = userOptions.value.some((item) => item.id === user.id)
  if (!exists) {
    userOptions.value = [{ id: user.id, name: user.name }, ...userOptions.value]
  }
}

function trangThaiLabel(value) {
  return trangThaiAllOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function trangThaiTagType(value) {
  const map = {
    moi_tao: 'info',
    nhap: '',
    cho_xac_nhan: 'info',
    dang_thue: 'warning',
    da_tra: 'success',
    qua_han: 'danger',
    hoan_thanh: 'success',
    da_huy: '',
  }
  return map[value] || 'info'
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

function cacheTrangPhucItems(items = []) {
  const next = { ...trangPhucCache.value }
  items.forEach((item) => {
    if (item?.id) next[item.id] = item
  })
  trangPhucCache.value = next
}

function isSanPhamSelected(sanPhamId) {
  return form.san_pham_cho_thue.some((item) => item.san_pham_id === sanPhamId)
}

function isSanPhamBusy(tp) {
  return Boolean(tp?.dang_su_dung)
}

function onSanPhamCardClick(tp) {
  if (isSanPhamBusy(tp)) return
  toggleSanPham(tp)
}

function recalcTongTien() {
  form.tong_tien = tongTienTuTinh.value
}

function applyTongTienTuTinh() {
  form.tong_tien = tongTienTuTinh.value
}

function toggleSanPham(tp) {
  if (isSanPhamBusy(tp)) return
  const index = form.san_pham_cho_thue.findIndex((item) => item.san_pham_id === tp.id)
  if (index >= 0) {
    form.san_pham_cho_thue.splice(index, 1)
  } else {
    cacheTrangPhucItems([tp])
    form.san_pham_cho_thue.push(emptySanPhamItem(tp.id))
  }
  recalcTongTien()
}

function removeSanPham(sanPhamId) {
  const index = form.san_pham_cho_thue.findIndex((item) => item.san_pham_id === sanPhamId)
  if (index >= 0) {
    form.san_pham_cho_thue.splice(index, 1)
    recalcTongTien()
  }
}

function updateSoNgayThue() {
  recalcTongTien()
  loadTrangPhucOptions()
}

function onSanPhamSearch() {
  clearTimeout(sanPhamSearchTimer)
  sanPhamSearchTimer = setTimeout(() => {
    loadTrangPhucOptions()
  }, 300)
}

async function loadUserOptions() {
  try {
    const { data } = await fetchUsers({ per_page: 100, status: 'active' })
    userOptions.value = data.data || []
  } catch {
    userOptions.value = []
  }
}

async function loadTrangPhucOptions() {
  trangPhucLoading.value = true
  try {
    const giaTu = sanPhamGiaTu.value === null || sanPhamGiaTu.value === ''
      ? undefined
      : Number(sanPhamGiaTu.value)
    const giaDen = sanPhamGiaDen.value === null || sanPhamGiaDen.value === ''
      ? undefined
      : Number(sanPhamGiaDen.value)

    const { data } = await fetchTrangPhuc({
      per_page: 100,
      trang_thai: 1,
      keyword: sanPhamKeyword.value.trim() || undefined,
      gia_tu: Number.isFinite(giaTu) ? giaTu : undefined,
      gia_den: Number.isFinite(giaDen) ? giaDen : undefined,
      ngay_thue: form.ngay_thue || undefined,
      ngay_tra_du_kien: form.ngay_tra_du_kien || undefined,
      exclude_hop_dong_id: editingId.value || undefined,
    })
    trangPhucOptions.value = data.data || []
    cacheTrangPhucItems(trangPhucOptions.value)
    removeBusySelectedSanPham()
  } catch {
    trangPhucOptions.value = []
  } finally {
    trangPhucLoading.value = false
  }
}

function removeBusySelectedSanPham() {
  if (!form.ngay_thue || !form.ngay_tra_du_kien) return
  const busyIds = new Set(
    trangPhucOptions.value.filter((tp) => isSanPhamBusy(tp)).map((tp) => tp.id),
  )
  if (!busyIds.size) return

  const before = form.san_pham_cho_thue.length
  form.san_pham_cho_thue = form.san_pham_cho_thue.filter(
    (item) => !busyIds.has(item.san_pham_id),
  )
  if (form.san_pham_cho_thue.length !== before) {
    recalcTongTien()
  }
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchHopDongChoThueTrangPhuc({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      trang_thai: filterTrangThai.value || undefined,
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

function fillFormFromRow(row, { draftFlow = false } = {}) {
  sanPhamKeyword.value = ''
  sanPhamGiaTu.value = null
  sanPhamGiaDen.value = null
  sanPhamExpanded.value = true
  ensureUserInOptions(row.nguoi_cho_thue_user || authStore.user)

  const sanPhamItems = (row.san_pham_cho_thue || []).map((item) => ({
    san_pham_id: item.san_pham_id,
    ghi_chu: item.ghi_chu || '',
  }))
  cacheTrangPhucItems((row.san_pham_cho_thue || []).map((item) => item.san_pham).filter(Boolean))

  const draftStatuses = ['moi_tao', 'nhap']
  const trangThai = draftFlow && draftStatuses.includes(row.trang_thai)
    ? 'cho_xac_nhan'
    : (row.trang_thai || 'cho_xac_nhan')

  Object.assign(form, {
    ma_hop_dong: row.ma_hop_dong || '',
    ten_khach_hang: row.ten_khach_hang || '',
    sdt_khach_hang: row.sdt_khach_hang || '',
    ngay_thue: row.ngay_thue?.slice?.(0, 10) || row.ngay_thue || '',
    ngay_tra_du_kien: row.ngay_tra_du_kien?.slice?.(0, 10) || row.ngay_tra_du_kien || '',
    ngay_tra_chinh_thuc: row.ngay_tra_chinh_thuc?.slice?.(0, 10) || row.ngay_tra_chinh_thuc || '',
    tong_tien: Number(row.tong_tien) || 0,
    giam_gia: Number(row.giam_gia) || 0,
    tien_coc: Number(row.tien_coc) || 0,
    trang_thai: trangThai,
    nguoi_cho_thue: row.nguoi_cho_thue || currentUserId(),
    nguoi_tham_gia: Array.isArray(row.nguoi_tham_gia) ? [...row.nguoi_tham_gia] : [],
    ghi_chu_sale: row.ghi_chu_sale || '',
    ghi_chu_khach: row.ghi_chu_khach || '',
    san_pham_cho_thue: sanPhamItems,
  })
}

async function openCreate() {
  creating.value = true
  try {
    const { data } = await khoiTaoHopDongChoThueTrangPhuc()
    editingId.value = data.id
    isDraftFlow.value = true
    fillFormFromRow(data, { draftFlow: true })
    dialogVisible.value = true
    loadTrangPhucOptions()
  } catch {
    // interceptor
  } finally {
    creating.value = false
  }
}

function openDrafts() {
  draftModalVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  isDraftFlow.value = ['moi_tao', 'nhap'].includes(row.trang_thai)
  fillFormFromRow(row, { draftFlow: isDraftFlow.value })
  dialogVisible.value = true
  loadTrangPhucOptions()
}

async function onContinueDraft(row) {
  draftModalVisible.value = false
  try {
    const { data } = await getHopDongChoThueTrangPhuc(row.id)
    editingId.value = data.id
    isDraftFlow.value = true
    fillFormFromRow(data, { draftFlow: true })
  } catch {
    editingId.value = row.id
    isDraftFlow.value = true
    fillFormFromRow(row, { draftFlow: true })
  }
  dialogVisible.value = true
  loadTrangPhucOptions()
}

const canCancelHopDong = computed(() => {
  if (isDraftFlow.value || !editingId.value) return false
  return !['hoan_thanh', 'da_huy'].includes(form.trang_thai)
})

function buildPayload(trangThai) {
  return {
    ten_khach_hang: form.ten_khach_hang?.trim() || null,
    sdt_khach_hang: form.sdt_khach_hang?.trim() || null,
    ngay_thue: form.ngay_thue || null,
    ngay_tra_du_kien: form.ngay_tra_du_kien || null,
    tong_tien: Number(form.tong_tien) || 0,
    giam_gia: Number(form.giam_gia) || 0,
    tien_coc: Number(form.tien_coc) || 0,
    trang_thai: trangThai,
    nguoi_tham_gia: form.nguoi_tham_gia || [],
    ghi_chu_sale: form.ghi_chu_sale?.trim() || null,
    ghi_chu_khach: form.ghi_chu_khach?.trim() || null,
    san_pham_cho_thue: form.san_pham_cho_thue.map((item) => ({
      san_pham_id: item.san_pham_id,
      ghi_chu: item.ghi_chu?.trim() || null,
    })),
  }
}

async function openLichChoThue(tp) {
  lichCurrentProduct.value = tp
  lichModalVisible.value = true
  lichLoading.value = true
  lichItems.value = []
  try {
    const { data } = await fetchTrangPhucLichChoThue(tp.id)
    lichItems.value = Array.isArray(data) ? data : []
  } catch {
    lichItems.value = []
  } finally {
    lichLoading.value = false
  }
}

function onLichModalClosed() {
  lichCurrentProduct.value = null
  lichItems.value = []
}

async function saveDraft() {
  if (!editingId.value) {
    ElMessage.error('Không tìm thấy hợp đồng để lưu nháp.')
    return
  }

  savingDraft.value = true
  try {
    await updateHopDongChoThueTrangPhuc(editingId.value, buildPayload('nhap'))
    ElMessage.success('Đã lưu nháp hợp đồng cho thuê.')
    dialogVisible.value = false
    isDraftFlow.value = false
    if (draftModalVisible.value) {
      draftModalRef.value?.reload?.()
    }
  } catch {
    // interceptor
  } finally {
    savingDraft.value = false
  }
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  if (!form.san_pham_cho_thue.length) {
    ElMessage.warning('Vui lòng chọn ít nhất một sản phẩm cho thuê.')
    return
  }

  if (!editingId.value) {
    ElMessage.error('Không tìm thấy hợp đồng để lưu.')
    return
  }

  saving.value = true
  try {
    const trangThai = isDraftFlow.value ? 'dang_thue' : form.trang_thai
    await updateHopDongChoThueTrangPhuc(editingId.value, buildPayload(trangThai))
    ElMessage.success(isDraftFlow.value ? 'Đã thêm hợp đồng cho thuê.' : 'Đã cập nhật hợp đồng cho thuê.')
    dialogVisible.value = false
    isDraftFlow.value = false
    await loadItems()
    if (draftModalVisible.value) {
      draftModalRef.value?.reload?.()
    }
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
}

async function cancelHopDong() {
  if (!editingId.value || !canCancelHopDong.value) return

  await ElMessageBox.confirm(
    `Huỷ hợp đồng "${form.ma_hop_dong}"? Trạng thái sẽ chuyển sang Đã hủy.`,
    'Xác nhận',
    {
      type: 'warning',
      confirmButtonText: 'Huỷ hợp đồng',
      cancelButtonText: 'Đóng',
    },
  )

  cancelling.value = true
  try {
    await updateHopDongChoThueTrangPhuc(editingId.value, buildPayload('da_huy'))
    ElMessage.success('Đã huỷ hợp đồng cho thuê.')
    dialogVisible.value = false
    await loadItems()
  } catch {
    // interceptor
  } finally {
    cancelling.value = false
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
    await runBulk(ids, (id) => deleteHopDongChoThueTrangPhuc(id))
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
    await deleteHopDongChoThueTrangPhuc(row.id)
    ElMessage.success('Đã xóa hợp đồng cho thuê.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(() => {
  loadUserOptions()
  loadItems()
})
</script>

<style scoped lang="scss">
.hop-dong-cho-thue {
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
}

.sub-text {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.action-btns {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.san-pham-section {
  margin: 8px 0 16px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  background: var(--el-fill-color-blank);
  overflow: hidden;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  padding: 12px 16px;
  border-bottom: 1px solid var(--el-border-color-lighter);
  background: var(--el-fill-color-lighter);
}

.section-header__title-wrap {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  min-width: 0;
}

.section-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.section-count {
  font-size: 13px;
  color: var(--el-color-primary);
}

.section-header__arrow {
  flex-shrink: 0;
  color: var(--el-text-color-secondary);
  transition: transform 0.2s ease;

  &.is-expanded {
    transform: rotate(180deg);
  }
}

.san-pham-section__body {
  padding: 16px;
}

.san-pham-picker {
  margin-bottom: 16px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  overflow: hidden;
}

.san-pham-picker__toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  padding: 10px 12px;
  border: 0;
  background: var(--el-fill-color-blank);
  cursor: pointer;
  text-align: left;
  font-size: 13px;
  font-weight: 500;
  color: var(--el-text-color-primary);
  transition: background 0.2s ease;

  &:hover {
    background: var(--el-fill-color-light);
  }
}

.san-pham-picker__body {
  padding: 0 12px 12px;
  border-top: 1px solid var(--el-border-color-lighter);
}

.san-pham-picker.is-collapsed {
  .san-pham-picker__toggle {
    border-bottom: 0;
  }
}

.san-pham-filter {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 12px;
}

.san-pham-filter__keyword {
  flex: 1 1 240px;
  min-width: 200px;
}

.san-pham-filter__price {
  flex: 0 1 160px;
  width: 160px;
}

.san-pham-card-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  grid-auto-rows: 68px;
  gap: 10px;
  /* Hiển thị đúng 2 hàng; thừa thì cuộn dọc */
  max-height: calc(2 * 68px + 10px + 8px);
  overflow-y: auto;
  padding: 4px;
  margin-bottom: 16px;
}

.san-pham-card {
  position: relative;
  display: flex;
  flex-direction: row;
  align-items: stretch;
  width: 100%;
  height: 68px;
  min-height: 68px;
  padding: 0;
  border: 2px solid var(--el-border-color-lighter);
  border-radius: 8px;
  background: var(--el-fill-color-blank);
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
  text-align: left;
  overflow: hidden;

  &:hover {
    border-color: var(--el-color-primary-light-5);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  }

  &.is-selected {
    border-color: var(--el-color-primary);
    box-shadow: 0 0 0 1px var(--el-color-primary-light-7);
    background: var(--el-color-primary-light-9);
  }

  &.is-disabled {
    cursor: not-allowed;
    opacity: 0.55;
    background: var(--el-fill-color-light);

    &:hover {
      border-color: var(--el-border-color-lighter);
      box-shadow: none;
    }

    &.is-selected {
      border-color: var(--el-border-color);
      box-shadow: none;
      background: var(--el-fill-color-light);
    }
  }
}

.san-pham-card__image {
  position: relative;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  min-height: 100%;
  background: var(--el-fill-color-light);
  border-right: 1px solid var(--el-border-color-lighter);
}

.san-pham-card__avatar {
  flex-shrink: 0;
}

.san-pham-card__placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  color: var(--el-text-color-placeholder);
}

.san-pham-card__check {
  position: absolute;
  top: 4px;
  left: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--el-color-primary);
  color: #fff;
  font-size: 10px;
}

.san-pham-card__body {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 2px;
  min-width: 0;
  padding: 6px 8px;
}

.san-pham-card__code {
  font-size: 10px;
  color: var(--el-text-color-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.san-pham-card__name {
  font-size: 11px;
  font-weight: 500;
  color: var(--el-text-color-primary);
  line-height: 1.25;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.san-pham-card__price {
  font-size: 10px;
  font-weight: 600;
  color: var(--el-color-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.san-pham-card__calendar {
  position: absolute;
  right: 6px;
  bottom: 6px;
  z-index: 2;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 6px;
  background: var(--el-color-warning-light-8);
  color: var(--el-color-warning-dark-2);
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;

  &:hover {
    background: var(--el-color-warning);
    color: #fff;
  }
}

.lich-empty {
  padding: 24px 12px;
  text-align: center;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.san-pham-empty,
.selected-san-pham-empty {
  grid-column: 1 / -1;
  padding: 24px 12px;
  text-align: center;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.selected-san-pham {
  border-top: 1px solid var(--el-border-color-lighter);
  padding-top: 16px;
}

.selected-san-pham__title {
  margin-bottom: 12px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.selected-san-pham__summary {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  justify-content: flex-end;
  margin-top: 12px;
  font-size: 13px;
  color: var(--el-text-color-regular);

  strong {
    color: var(--el-color-primary);
  }
}

.product-thumb {
  flex-shrink: 0;
}

.no-image {
  color: var(--el-text-color-placeholder);
}

.tong-tien-hint {
  margin-top: 4px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.summary-row {
  margin-top: 8px;
}

.con-lai-input :deep(.el-input__inner) {
  font-weight: 600;
  color: var(--el-color-primary);
}

.footer-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 8px;
  width: 100%;
}
</style>
