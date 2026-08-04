<template>
  <CustomDialog
    v-model="visible"
    :width="1400"
    class="hop-dong-cho-thue-thanh-toan-modal"
    @closed="onClosed"
  >
    <template #header>
      <div class="modal-header">
        <div class="modal-header__title">Thanh toán hợp đồng cho thuê</div>
        <div v-if="headerMeta" class="modal-header__meta">{{ headerMeta }}</div>
      </div>
    </template>

    <div v-loading="loading" class="thanh-toan-body">
      <section class="overview-section">
        <div class="section-title">Tổng quan hợp đồng</div>
        <CustomForm label-position="top">
          <CustomRow :gutter="16">
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Ngày thuê">
                <CustomInput :model-value="formatDate(hopDongData?.ngay_thue)" readonly />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Ngày trả dự kiến">
                <CustomInput :model-value="formatDate(hopDongData?.ngay_tra_du_kien)" readonly />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Ngày trả chính thức">
                <CustomInput :model-value="formatDate(ngayTraChinhThuc)" readonly />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Số ngày thuê">
                <CustomInput
                  :model-value="hopDongData?.so_ngay_thue != null ? String(hopDongData.so_ngay_thue) : ''"
                  readonly
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Số ngày thuê thực tế">
                <CustomInput
                  :model-value="soNgayThueThucTe != null ? String(soNgayThueThucTe) : ''"
                  readonly
                />
              </CustomFormItem>
            </CustomCol>
          </CustomRow>
        </CustomForm>
      </section>

      <section class="products-section" :class="{ 'is-collapsed': !productsExpanded }">
        <button
          type="button"
          class="products-section__header"
          @click="productsExpanded = !productsExpanded"
        >
          <div class="products-section__title-wrap">
            <span class="products-section__title">Danh sách sản phẩm</span>
            <span v-if="sanPhamRows.length" class="products-section__count">
              {{ sanPhamRows.length }} sản phẩm
            </span>
          </div>
          <CustomIcon
            class="products-section__arrow"
            :class="{ 'is-expanded': productsExpanded }"
          >
            <ArrowDown />
          </CustomIcon>
        </button>
        <div v-show="productsExpanded" class="products-section__body">
          <CustomTable
            v-if="sanPhamRows.length"
            :data="sanPhamRows"
            stripe
            style="width: 100%"
          >
            <CustomTableColumn label="STT" width="60" align="center">
              <template #default="{ $index }">{{ $index + 1 }}</template>
            </CustomTableColumn>
            <CustomTableColumn label="Hình ảnh" width="80" align="center">
              <template #default="{ row }">
                <el-avatar
                  v-if="row.hinh_anh"
                  :size="40"
                  :src="mediaUrl(row.hinh_anh)"
                  shape="square"
                />
                <span v-else class="no-image">—</span>
              </template>
            </CustomTableColumn>
            <CustomTableColumn prop="ma_san_pham" label="Mã SP" width="120" />
            <CustomTableColumn prop="ten_san_pham" label="Tên sản phẩm" min-width="180" />
            <CustomTableColumn label="Giá trị" width="130" align="right">
              <template #default="{ row }">
                {{ formatMoney(row.gia_tri) }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Giá thuê/ngày" width="130" align="right">
              <template #default="{ row }">
                {{ formatMoney(row.gia_cho_thue) }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Ghi chú" min-width="200">
              <template #default="{ row }">
                <CustomInput
                  v-model="row.ghi_chu"
                  placeholder="Nhập ghi chú"
                  clearable
                />
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Đã hoàn trả" width="140" align="center">
              <template #default="{ row }">
                <el-checkbox :model-value="row.trang_thai_hoan_tra === 'da_hoan_tra'"
                  @update:model-value="(checked) => onHoanTraChange(row, checked)" />
              </template>
            </CustomTableColumn>
          </CustomTable>
          <div v-else class="empty-products">Chưa có sản phẩm nào trong hợp đồng.</div>
        </div>
      </section>

      <section class="payment-section">
        <div class="section-title">Thanh toán</div>
        <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
          <CustomRow :gutter="16">
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Giá trị hợp đồng">
                <MoneyInput :model-value="thanhTien" readonly style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Đã thanh toán">
                <MoneyInput :model-value="daThanhToan" readonly style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Còn phải thanh toán">
                <CustomInput
                  :model-value="formatMoney(conLai)"
                  readonly
                  class="con-lai-input"
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Phí trả muộn">
                <MoneyInput :model-value="phiTraMuon" readonly style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Số tiền đền bù">
                <MoneyInput :model-value="tienDenBu" readonly style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Phí phụ thu" prop="phi_phu_thu">
                <MoneyInput v-model="form.phi_phu_thu" style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Ưu đãi tất toán" prop="uu_dai_tat_toan">
                <MoneyInput v-model="form.uu_dai_tat_toan" style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Tổng tiền thanh toán">
                <MoneyInput :model-value="tongTienThanhToan" readonly style="width: 100%" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6" :lg="4">
              <CustomFormItem label="Hình thức thanh toán" prop="hinh_thuc_thanh_toan">
                <CustomSelect
                  v-model="form.hinh_thuc_thanh_toan"
                  placeholder="Chọn hình thức"
                  style="width: 100%"
                >
                  <CustomOption
                    v-for="opt in hinhThucOptions"
                    :key="opt.value"
                    :label="opt.label"
                    :value="opt.value"
                  />
                </CustomSelect>
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
      </section>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Đóng</CustomButton>
        <CustomButton type="primary" :loading="saving" :disabled="!canSubmit" @click="submit">
          Xác nhận thanh toán
        </CustomButton>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { ArrowDown } from '@element-plus/icons-vue'
import {
  getHopDongChoThueTrangPhuc,
  thanhToanHopDongChoThueTrangPhuc,
} from '@/api/hopDongChoThueTrangPhuc'
import {
  CustomButton,
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
  MoneyInput,
} from '@/components/element'
import { mediaUrl } from '@/utils/media'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  hopDong: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved', 'closed'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const hinhThucOptions = [
  { value: 'tien_mat', label: 'Tiền mặt' },
  { value: 'chuyen_khoan', label: 'Chuyển khoản' },
]

const loading = ref(false)
const saving = ref(false)
const formRef = ref(null)
const hopDongData = ref(null)
const sanPhamRows = ref([])
const productsExpanded = ref(true)

const form = reactive({
  hinh_thuc_thanh_toan: 'tien_mat',
  phi_phu_thu: 0,
  uu_dai_tat_toan: 0,
  ghi_chu_sale: '',
  ghi_chu_khach: '',
})

const headerMeta = computed(() => {
  const parts = [
    hopDongData.value?.ma_hop_dong,
    hopDongData.value?.ten_khach_hang,
    hopDongData.value?.sdt_khach_hang,
  ].filter(Boolean)
  return parts.join(' · ')
})

const tongTien = computed(() => Number(hopDongData.value?.tong_tien) || 0)
const giamGia = computed(() => Number(hopDongData.value?.giam_gia) || 0)
const thanhTien = computed(() => {
  const value = Number(hopDongData.value?.thanh_tien)
  if (Number.isFinite(value) && value > 0) return value
  return Math.max(0, tongTien.value - giamGia.value)
})
const daThanhToan = computed(() => Number(hopDongData.value?.tien_coc) || 0)
const conLai = computed(() => Math.max(0, thanhTien.value - daThanhToan.value))

const ngayTraChinhThuc = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return today
})

const soNgayThueThucTe = computed(() => {
  const thueRaw = hopDongData.value?.ngay_thue
  if (!thueRaw) return null
  const ngayThue = new Date(thueRaw)
  if (Number.isNaN(ngayThue.getTime())) return null
  ngayThue.setHours(0, 0, 0, 0)
  return Math.max(
    0,
    Math.floor(
      (ngayTraChinhThuc.value.getTime() - ngayThue.getTime()) / (24 * 60 * 60 * 1000),
    ),
  )
})

const soNgayTraMuon = computed(() => {
  const duKienRaw = hopDongData.value?.ngay_tra_du_kien
  if (!duKienRaw) return 0
  const duKien = new Date(duKienRaw)
  if (Number.isNaN(duKien.getTime())) return 0
  duKien.setHours(0, 0, 0, 0)
  const diffDays = Math.floor(
    (ngayTraChinhThuc.value.getTime() - duKien.getTime()) / (24 * 60 * 60 * 1000),
  )
  return diffDays > 0 ? diffDays : 0
})

const tongGiaChoThue = computed(() =>
  sanPhamRows.value.reduce((sum, row) => sum + (Number(row.gia_cho_thue) || 0), 0),
)

const phiTraMuon = computed(() => tongGiaChoThue.value * soNgayTraMuon.value)

const tienDenBu = computed(() =>
  sanPhamRows.value.reduce((sum, row) => {
    if (row.trang_thai_hoan_tra === 'da_hoan_tra') return sum
    return sum + (Number(row.gia_tri) || 0)
  }, 0),
)

const tongTienThanhToan = computed(() =>
  Math.max(
    0,
    conLai.value
      + phiTraMuon.value
      + tienDenBu.value
      + (Number(form.phi_phu_thu) || 0)
      - (Number(form.uu_dai_tat_toan) || 0),
  ),
)

const rules = {
  hinh_thuc_thanh_toan: [
    {
      validator: (_rule, value, callback) => {
        if (tongTienThanhToan.value > 0 && !value) {
          callback(new Error('Chọn hình thức thanh toán'))
          return
        }
        callback()
      },
      trigger: 'change',
    },
  ],
}

const canSubmit = computed(() => Boolean(hopDongData.value?.id))

function formatMoney(value) {
  if (value == null || value === '') return '—'
  const num = Number(value)
  if (Number.isNaN(num)) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function formatDate(value) {
  if (!value) return ''
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString('vi-VN')
}

function formatDateIso(value) {
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return null
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function onHoanTraChange(row, checked) {
  row.trang_thai_hoan_tra = checked ? 'da_hoan_tra' : 'chua_hoan_tra'
}

function mapSanPhamRows(items = []) {
  return items.map((item) => ({
    id: item.id,
    san_pham_id: item.san_pham_id,
    ma_san_pham: item.san_pham?.ma_san_pham || '—',
    ten_san_pham: item.san_pham?.ten_san_pham || '—',
    gia_tri: item.san_pham?.gia_tri ?? null,
    gia_cho_thue: item.san_pham?.gia_cho_thue ?? null,
    hinh_anh: item.san_pham?.hinh_anh || null,
    trang_thai_hoan_tra: item.trang_thai_hoan_tra || 'chua_hoan_tra',
    ghi_chu: item.ghi_chu || '',
  }))
}

function fillFromHopDong(row) {
  hopDongData.value = row || null
  sanPhamRows.value = mapSanPhamRows(row?.san_pham_cho_thue || [])
  form.hinh_thuc_thanh_toan = row?.hinh_thuc_thanh_toan || 'tien_mat'
  form.phi_phu_thu = Number(row?.phi_phu_thu) || 0
  form.uu_dai_tat_toan = Number(row?.uu_dai_tat_toan) || 0
  form.ghi_chu_sale = row?.ghi_chu_sale || ''
  form.ghi_chu_khach = row?.ghi_chu_khach || ''
}

async function loadDetail(id) {
  if (!id) return
  loading.value = true
  try {
    const { data } = await getHopDongChoThueTrangPhuc(id)
    fillFromHopDong(data)
  } catch {
    fillFromHopDong(props.hopDong)
  } finally {
    loading.value = false
  }
}

async function submit() {
  if (!hopDongData.value?.id) return

  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  const soTien = conLai.value
  if (tongTienThanhToan.value > 0 && !form.hinh_thuc_thanh_toan) {
    ElMessage.warning('Vui lòng chọn hình thức thanh toán.')
    return
  }

  saving.value = true
  try {
    const { data } = await thanhToanHopDongChoThueTrangPhuc(hopDongData.value.id, {
      so_tien_thanh_toan: soTien,
      hinh_thuc_thanh_toan: tongTienThanhToan.value > 0 ? form.hinh_thuc_thanh_toan : null,
      phi_tra_muon: phiTraMuon.value,
      tien_den_bu: tienDenBu.value,
      phi_phu_thu: Number(form.phi_phu_thu) || 0,
      uu_dai_tat_toan: Number(form.uu_dai_tat_toan) || 0,
      tong_tien_thanh_toan: tongTienThanhToan.value,
      ngay_tra_chinh_thuc: formatDateIso(ngayTraChinhThuc.value),
      ghi_chu_sale: form.ghi_chu_sale?.trim() || null,
      ghi_chu_khach: form.ghi_chu_khach?.trim() || null,
      san_pham_cho_thue: sanPhamRows.value.map((row) => ({
        id: row.id,
        trang_thai_hoan_tra: row.trang_thai_hoan_tra,
        ghi_chu: row.ghi_chu || null,
      })),
    })
    ElMessage.success(
      tongTienThanhToan.value > 0
        ? 'Đã ghi nhận thanh toán thành công.'
        : 'Đã cập nhật trạng thái hoàn trả.',
    )
    visible.value = false
    emit('saved', data)
  } catch {
    // interceptor
  } finally {
    saving.value = false
  }
}

function resetLocalState() {
  hopDongData.value = null
  sanPhamRows.value = []
  productsExpanded.value = true
  form.hinh_thuc_thanh_toan = 'tien_mat'
  form.phi_phu_thu = 0
  form.uu_dai_tat_toan = 0
  form.ghi_chu_sale = ''
  form.ghi_chu_khach = ''
  formRef.value?.clearValidate?.()
}

function onClosed() {
  resetLocalState()
  emit('closed')
}

watch(
  () => [props.modelValue, props.hopDong?.id],
  ([isOpen, id]) => {
    if (!isOpen) return
    if (id) {
      fillFromHopDong(props.hopDong)
      loadDetail(id)
    } else {
      fillFromHopDong(null)
    }
  },
)
</script>

<style scoped lang="scss">
.thanh-toan-body {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-height: 200px;
}

.modal-header {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding-right: 28px;
}

.modal-header__title {
  font-size: 16px;
  font-weight: 600;
  line-height: 1.4;
  color: var(--el-text-color-primary);
}

.modal-header__meta {
  font-size: 13px;
  line-height: 1.4;
  color: var(--el-text-color-secondary);
  word-break: break-word;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.products-section {
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
  background: var(--el-bg-color);
  overflow: hidden;
}

.products-section__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  padding: 12px 14px;
  border: 0;
  background: var(--el-fill-color-lighter);
  cursor: pointer;
  text-align: left;
  transition: background 0.2s ease;

  &:hover {
    background: var(--el-fill-color);
  }
}

.products-section__title-wrap {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  min-width: 0;
}

.products-section__title {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.products-section__count {
  font-size: 12px;
  font-weight: 500;
  color: var(--el-color-primary);
  background: var(--el-color-primary-light-9);
  border-radius: 999px;
  padding: 2px 8px;
}

.products-section__arrow {
  flex-shrink: 0;
  color: var(--el-text-color-secondary);
  transition: transform 0.2s ease;

  &.is-expanded {
    transform: rotate(180deg);
  }
}

.products-section__body {
  padding: 12px 14px 14px;
  border-top: 1px solid var(--el-border-color-lighter);
}

.no-image,
.empty-products {
  color: var(--el-text-color-secondary);
}

.empty-products {
  padding: 16px;
  text-align: center;
  background: var(--el-fill-color-lighter);
  border-radius: 8px;
}

.con-lai-input :deep(.el-input__inner) {
  font-weight: 600;
  color: var(--el-color-danger);
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
