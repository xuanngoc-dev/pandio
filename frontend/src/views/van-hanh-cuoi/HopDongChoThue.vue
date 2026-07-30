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
            <CustomButton type="primary" @click="openCreate">
              <CustomIcon><Plus /></CustomIcon>
              Thêm hợp đồng
            </CustomButton>
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
        <CustomTableColumn label="Mã HĐ" prop="ma_hop_dong" min-width="140" />
        <CustomTableColumn label="Khách hàng" min-width="160">
          <template #default="{ row }">
            <div>{{ row.ten_khach_hang }}</div>
            <div class="sub-text">{{ row.sdt_khach_hang }}</div>
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
        <CustomTableColumn label="Số ngày" width="90" align="center" prop="so_ngay_thue" />
        <CustomTableColumn label="Tổng tiền" width="130" align="right">
          <template #default="{ row }">
            {{ formatMoney(row.tong_tien) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Người cho thuê" min-width="140">
          <template #default="{ row }">
            {{ row.nguoi_cho_thue_user?.name || '—' }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Sản phẩm" width="90" align="center">
          <template #default="{ row }">
            {{ row.san_pham_cho_thue?.length || 0 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Trạng thái" width="130" align="center">
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
      :title="editingId ? 'Sửa hợp đồng cho thuê' : 'Thêm hợp đồng cho thuê'"
      :width="1400"
    >
      <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Mã hợp đồng" prop="ma_hop_dong">
              <CustomInput v-model="form.ma_hop_dong" placeholder="VD: HDCT-20260725-001" />
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
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="form.trang_thai" placeholder="Chọn trạng thái" style="width: 100%">
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

        <CustomRow :gutter="16">
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
            <CustomFormItem label="Ngày trả chính thức" prop="ngay_tra_chinh_thuc">
              <el-date-picker
                v-model="form.ngay_tra_chinh_thuc"
                type="date"
                placeholder="Chọn ngày trả chính thức"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                clearable
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="6">
            <CustomFormItem label="Số ngày thuê">
              <CustomInput :model-value="String(soNgayThue)" readonly />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>

        <CustomRow :gutter="16">
          <CustomCol :xs="24" :sm="12" :md="12">
            <CustomFormItem label="Người tạo đơn" prop="nguoi_cho_thue">
              <CustomSelect
                v-model="form.nguoi_cho_thue"
                placeholder="Người tạo đơn"
                filterable
                disabled
                style="width: 100%"
              >
                <CustomOption
                  v-for="user in userOptions"
                  :key="user.id"
                  :label="user.name"
                  :value="user.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12" :md="12">
            <CustomFormItem label="Người tham gia" prop="nguoi_tham_gia">
              <CustomSelect
                v-model="form.nguoi_tham_gia"
                placeholder="Chọn người tham gia"
                filterable
                multiple
                collapse-tags
                collapse-tags-tooltip
                style="width: 100%"
              >
                <CustomOption
                  v-for="user in userOptions"
                  :key="user.id"
                  :label="user.name"
                  :value="user.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
        </CustomRow>

        <div class="san-pham-section">
          <div class="section-header">
            <span class="section-title">Sản phẩm cho thuê</span>
            <span v-if="form.san_pham_cho_thue.length" class="section-count">
              Đã chọn {{ form.san_pham_cho_thue.length }} sản phẩm
            </span>
          </div>

          <CustomInput
            v-model="sanPhamKeyword"
            placeholder="Tìm nhanh theo mã, tên trang phục..."
            clearable
            class="san-pham-search"
            @input="onSanPhamSearch"
            @clear="onSanPhamSearch"
          >
            <template #prefix>
              <CustomIcon><Search /></CustomIcon>
            </template>
          </CustomInput>

          <div v-loading="trangPhucLoading" class="san-pham-card-grid">
            <button
              v-for="tp in trangPhucOptions"
              :key="tp.id"
              type="button"
              class="san-pham-card"
              :class="{ 'is-selected': isSanPhamSelected(tp.id) }"
              @click="toggleSanPham(tp)"
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
            </button>
            <div v-if="!trangPhucLoading && !trangPhucOptions.length" class="san-pham-empty">
              Không tìm thấy trang phục phù hợp.
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
              <CustomTableColumn prop="ten_san_pham" label="Tên sản phẩm" min-width="180" />
              <CustomTableColumn prop="ma_san_pham" label="Mã SP" width="120" />
              <CustomTableColumn label="Giá cho thuê" width="140" align="right">
                <template #default="{ row }">
                  {{ formatMoney(row.gia_cho_thue) }}
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
        <CustomButton @click="dialogVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="saving" @click="save">Lưu</CustomButton>
      </template>
    </CustomDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Check, Delete, Edit, Picture, Plus, Search } from '@element-plus/icons-vue'
import {
  createHopDongChoThueTrangPhuc,
  deleteHopDongChoThueTrangPhuc,
  fetchHopDongChoThueTrangPhuc,
  updateHopDongChoThueTrangPhuc,
} from '@/api/hopDongChoThueTrangPhuc'
import { fetchTrangPhuc } from '@/api/trangPhuc'
import { fetchUsers } from '@/api/users'
import BulkActionBar from '@/components/BulkActionBar.vue'
import { runBulk, useBulkSelection } from '@/composables/useBulkSelection'
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

const trangThaiOptions = [
  { value: 'cho_xac_nhan', label: 'Chờ xác nhận' },
  { value: 'dang_thue', label: 'Đang thuê' },
  { value: 'da_tra', label: 'Đã trả' },
  { value: 'qua_han', label: 'Quá hạn' },
  { value: 'da_huy', label: 'Đã hủy' },
]

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const filterTrangThai = ref('')

const userOptions = ref([])
const trangPhucOptions = ref([])
const trangPhucCache = ref({})
const sanPhamKeyword = ref('')
const trangPhucLoading = ref(false)
let sanPhamSearchTimer = null

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
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
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
      if (cached) return { ...cached, id: cached.id ?? item.san_pham_id }
      return null
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
  return trangThaiOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function trangThaiTagType(value) {
  const map = {
    cho_xac_nhan: 'info',
    dang_thue: 'warning',
    da_tra: 'success',
    qua_han: 'danger',
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

function recalcTongTien() {
  form.tong_tien = tongTienTuTinh.value
}

function applyTongTienTuTinh() {
  form.tong_tien = tongTienTuTinh.value
}

function toggleSanPham(tp) {
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
    const { data } = await fetchTrangPhuc({
      per_page: 100,
      trang_thai: 1,
      keyword: sanPhamKeyword.value.trim() || undefined,
    })
    trangPhucOptions.value = data.data || []
    cacheTrangPhucItems(trangPhucOptions.value)
  } catch {
    trangPhucOptions.value = []
  } finally {
    trangPhucLoading.value = false
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

function openCreate() {
  editingId.value = null
  sanPhamKeyword.value = ''
  Object.assign(form, emptyForm())
  ensureUserInOptions(authStore.user)
  form.nguoi_cho_thue = currentUserId()
  dialogVisible.value = true
  loadTrangPhucOptions()
}

function openEdit(row) {
  editingId.value = row.id
  sanPhamKeyword.value = ''
  ensureUserInOptions(row.nguoi_cho_thue_user)

  const sanPhamItems = (row.san_pham_cho_thue || []).map((item) => ({
    san_pham_id: item.san_pham_id,
    ghi_chu: item.ghi_chu || '',
  }))
  cacheTrangPhucItems((row.san_pham_cho_thue || []).map((item) => item.san_pham).filter(Boolean))

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
    trang_thai: row.trang_thai || 'cho_xac_nhan',
    nguoi_cho_thue: row.nguoi_cho_thue || null,
    nguoi_tham_gia: Array.isArray(row.nguoi_tham_gia) ? [...row.nguoi_tham_gia] : [],
    ghi_chu_sale: row.ghi_chu_sale || '',
    ghi_chu_khach: row.ghi_chu_khach || '',
    san_pham_cho_thue: sanPhamItems,
  })

  dialogVisible.value = true
  loadTrangPhucOptions()
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  if (!form.san_pham_cho_thue.length) {
    ElMessage.warning('Vui lòng chọn ít nhất một sản phẩm cho thuê.')
    return
  }

  saving.value = true
  const payload = {
    ma_hop_dong: form.ma_hop_dong.trim(),
    ten_khach_hang: form.ten_khach_hang.trim(),
    sdt_khach_hang: form.sdt_khach_hang.trim(),
    ngay_thue: form.ngay_thue,
    ngay_tra_du_kien: form.ngay_tra_du_kien,
    ngay_tra_chinh_thuc: form.ngay_tra_chinh_thuc || null,
    tong_tien: Number(form.tong_tien) || 0,
    giam_gia: Number(form.giam_gia) || 0,
    tien_coc: Number(form.tien_coc) || 0,
    trang_thai: form.trang_thai,
    nguoi_tham_gia: form.nguoi_tham_gia || [],
    ghi_chu_sale: form.ghi_chu_sale?.trim() || null,
    ghi_chu_khach: form.ghi_chu_khach?.trim() || null,
    san_pham_cho_thue: form.san_pham_cho_thue.map((item) => ({
      san_pham_id: item.san_pham_id,
      ghi_chu: item.ghi_chu?.trim() || null,
    })),
  }

  if (!editingId.value) {
    payload.nguoi_cho_thue = form.nguoi_cho_thue || currentUserId()
  }

  try {
    if (editingId.value) {
      await updateHopDongChoThueTrangPhuc(editingId.value, payload)
      ElMessage.success('Đã cập nhật hợp đồng cho thuê.')
    } else {
      await createHopDongChoThueTrangPhuc(payload)
      ElMessage.success('Đã thêm hợp đồng cho thuê.')
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
  padding: 16px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  background: var(--el-fill-color-blank);
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.section-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.section-count {
  font-size: 13px;
  color: var(--el-color-primary);
}

.san-pham-search {
  margin-bottom: 12px;
}

.san-pham-card-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px;
  max-height: 360px;
  overflow-y: auto;
  padding: 4px;
  margin-bottom: 16px;
}

.san-pham-card {
  display: flex;
  flex-direction: row;
  align-items: stretch;
  width: 100%;
  min-height: 64px;
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
}

.san-pham-card__image {
  position: relative;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  min-height: 64px;
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
</style>
