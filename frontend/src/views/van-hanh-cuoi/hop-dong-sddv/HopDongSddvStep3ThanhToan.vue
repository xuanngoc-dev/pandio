<template>
  <div class="step-panel step-panel--dich-vu" v-loading="loading">
    <section class="dich-vu-section" :class="{ 'is-collapsed': !conceptExpanded }">
      <button type="button" class="dich-vu-section__header" @click="conceptExpanded = !conceptExpanded">
        <div class="dich-vu-section__title-wrap">
          <span class="dich-vu-section__title">Chọn concept</span>
          <span v-if="selectedConcepts.length" class="dich-vu-section__count">
            Đã chọn {{ selectedConcepts.length }}
          </span>
        </div>
        <CustomIcon class="dich-vu-section__arrow" :class="{ 'is-expanded': conceptExpanded }">
          <ArrowDown />
        </CustomIcon>
      </button>
      <div v-show="conceptExpanded" class="dich-vu-section__body">
        <div class="service-filter">
          <CustomRow :gutter="12">
            <CustomCol :xs="12" :sm="16" :md="18">
              <CustomInput
                v-model="conceptFilter.keyword"
                placeholder="Tìm theo tên concept..."
                clearable
                class="service-filter__keyword"
              >
                <template #prefix>
                  <CustomIcon><Search /></CustomIcon>
                </template>
              </CustomInput>
            </CustomCol>
            <CustomCol :xs="12" :sm="8" :md="6">
              <div class="service-filter__switch">
                <el-switch v-model="conceptFilter.chi_da_chon" size="small" />
                <span class="service-filter__switch-label">Đã chọn</span>
              </div>
            </CustomCol>
          </CustomRow>
        </div>

        <div class="service-card-grid">
          <button
            v-for="item in filteredConceptOptions"
            :key="item.id"
            type="button"
            class="service-card"
            :class="{ 'is-selected': isConceptSelected(item.id) }"
            @click="toggleConcept(item)"
          >
            <span class="service-card__check" :class="{ 'is-checked': isConceptSelected(item.id) }">
              <CustomIcon v-if="isConceptSelected(item.id)"><Check /></CustomIcon>
            </span>
            <div class="service-card__body">
              <div class="service-card__name" :title="item.ten_concept">{{ item.ten_concept }}</div>
              <div v-if="item.dia_diem || item.danh_muc?.ten_danh_muc" class="service-card__meta">
                <span v-if="item.danh_muc?.ten_danh_muc">{{ item.danh_muc.ten_danh_muc }}</span>
                <span v-if="item.dia_diem">{{ item.dia_diem }}</span>
              </div>
            </div>
          </button>
          <div v-if="!loading && !filteredConceptOptions.length" class="service-card-empty">
            {{ emptyConceptMessage }}
          </div>
        </div>

        <div class="selected-table-wrap">
          <div class="selected-table-title">Danh sách concept đã chọn</div>
          <CustomTable v-if="selectedConcepts.length" :data="selectedConcepts" stripe style="width: 100%">
            <CustomTableColumn label="STT" width="64" align="center">
              <template #default="{ $index }">{{ $index + 1 }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="ten" label="Tên concept" min-width="200" />
            <CustomTableColumn prop="dia_diem" label="Địa điểm" min-width="160">
              <template #default="{ row }">{{ row.dia_diem || '—' }}</template>
            </CustomTableColumn>
          </CustomTable>
          <div v-else class="selected-table-empty">Chưa chọn concept nào.</div>
        </div>
      </div>
    </section>

    <section class="dich-vu-section" :class="{ 'is-collapsed': !trangPhucExpanded }">
      <button type="button" class="dich-vu-section__header" @click="trangPhucExpanded = !trangPhucExpanded">
        <div class="dich-vu-section__title-wrap">
          <span class="dich-vu-section__title">Chọn trang phục</span>
          <span v-if="selectedTrangPhucs.length" class="dich-vu-section__count">
            Đã chọn {{ selectedTrangPhucs.length }}
          </span>
        </div>
        <CustomIcon class="dich-vu-section__arrow" :class="{ 'is-expanded': trangPhucExpanded }">
          <ArrowDown />
        </CustomIcon>
      </button>
      <div v-show="trangPhucExpanded" class="dich-vu-section__body">
        <div class="service-filter">
          <CustomRow :gutter="12">
            <CustomCol :xs="12" :sm="12" :md="10">
              <CustomInput
                v-model="trangPhucFilter.keyword"
                placeholder="Tìm theo tên trang phục..."
                clearable
                class="service-filter__keyword"
              >
                <template #prefix>
                  <CustomIcon><Search /></CustomIcon>
                </template>
              </CustomInput>
            </CustomCol>
            <CustomCol :xs="12" :sm="6" :md="4">
              <MoneyInput
                v-model="trangPhucFilter.gia_tu"
                placeholder="Giá từ"
                clearable
                class="service-filter__price"
              />
            </CustomCol>
            <CustomCol :xs="12" :sm="6" :md="4">
              <MoneyInput
                v-model="trangPhucFilter.gia_den"
                placeholder="Giá đến"
                clearable
                class="service-filter__price"
              />
            </CustomCol>
            <CustomCol :xs="12" :sm="12" :md="6">
              <div class="service-filter__switch">
                <el-switch v-model="trangPhucFilter.chi_da_chon" size="small" />
                <span class="service-filter__switch-label">Đã chọn</span>
              </div>
            </CustomCol>
          </CustomRow>
        </div>

        <div class="service-card-grid">
          <button
            v-for="item in filteredTrangPhucOptions"
            :key="item.id"
            type="button"
            class="service-card"
            :class="{ 'is-selected': isTrangPhucSelected(item.id) }"
            @click="toggleTrangPhuc(item)"
          >
            <span class="service-card__check" :class="{ 'is-checked': isTrangPhucSelected(item.id) }">
              <CustomIcon v-if="isTrangPhucSelected(item.id)"><Check /></CustomIcon>
            </span>
            <div class="service-card__body">
              <div class="service-card__name" :title="item.ten_san_pham">{{ item.ten_san_pham }}</div>
              <div v-if="item.danh_muc_trang_phuc?.ten_danh_muc" class="service-card__meta">
                <span>{{ item.danh_muc_trang_phuc.ten_danh_muc }}</span>
              </div>
              <div class="service-card__price">{{ formatMoney(item.gia_cho_thue) }}</div>
            </div>
          </button>
          <div v-if="!loading && !filteredTrangPhucOptions.length" class="service-card-empty">
            {{ emptyTrangPhucMessage }}
          </div>
        </div>

        <div class="selected-table-wrap">
          <div class="selected-table-title">Danh sách trang phục đã chọn</div>
          <CustomTable v-if="selectedTrangPhucs.length" :data="selectedTrangPhucs" stripe style="width: 100%">
            <CustomTableColumn label="STT" width="64" align="center">
              <template #default="{ $index }">{{ $index + 1 }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="ten" label="Tên trang phục" min-width="180" />
            <CustomTableColumn label="Giá thuê" width="130" align="right">
              <template #default="{ row }">{{ formatMoney(row.gia_cho_thue) }}</template>
            </CustomTableColumn>
            <CustomTableColumn label="Ngày bắt đầu" width="170" align="center">
              <template #default="{ row }">
                <el-date-picker
                  v-model="row.ngay_bat_dau"
                  type="date"
                  format="DD/MM/YYYY"
                  value-format="YYYY-MM-DD"
                  placeholder="Chọn ngày"
                  style="width: 150px"
                />
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Ngày kết thúc" width="170" align="center">
              <template #default="{ row }">
                <el-date-picker
                  v-model="row.ngay_ket_thuc"
                  type="date"
                  format="DD/MM/YYYY"
                  value-format="YYYY-MM-DD"
                  placeholder="Chọn ngày"
                  style="width: 150px"
                />
              </template>
            </CustomTableColumn>
          </CustomTable>
          <div v-else class="selected-table-empty">Chưa chọn trang phục nào.</div>
        </div>
      </div>
    </section>

    <CustomForm class="step2-summary" label-position="top">
      <CustomRow :gutter="16">
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Tổng tiền dịch vụ">
            <MoneyInput :model-value="tongTienDichVuHienThi" readonly style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Phát sinh">
            <MoneyInput v-model="form.phat_sinh" style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Chiết khấu">
            <MoneyInput v-model="form.chiet_khau" style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Mã giảm giá">
            <CustomInput v-model="form.ma_giam_gia" placeholder="Nhập mã giảm giá" clearable />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Số tiền giảm giá">
            <MoneyInput v-model="form.khuyen_mai_theo_ma_giam_gia" style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Khách hàng phải thanh toán">
            <MoneyInput
              :model-value="khachHangPhaiThanhToan"
              readonly
              class="tong-tien-input"
              style="width: 100%"
            />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryFieldColProps">
          <CustomFormItem label="Số tiền thanh toán lần 1">
            <MoneyInput v-model="form.so_tien_thanh_toan_lan_1" style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
        <CustomCol v-bind="summaryWideColProps">
          <CustomFormItem label="Quà tặng kèm">
            <CustomInput v-model="form.qua_tang_kem" placeholder="Nhập quà tặng kèm" clearable />
          </CustomFormItem>
        </CustomCol>
        <CustomCol :span="24">
          <CustomFormItem label="Yêu cầu đặc biệt">
            <CustomInput
              v-model="form.yeu_cau_dac_biet"
              type="textarea"
              :rows="3"
              placeholder="Nhập yêu cầu đặc biệt"
            />
          </CustomFormItem>
        </CustomCol>
      </CustomRow>
    </CustomForm>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { ArrowDown, Check, Search } from '@element-plus/icons-vue'
import { fetchConcept } from '@/api/concept'
import { fetchTrangPhuc } from '@/api/trangPhuc'
import {
  CustomCol,
  CustomForm,
  CustomFormItem,
  CustomIcon,
  CustomInput,
  CustomRow,
  CustomTable,
  CustomTableColumn,
  MoneyInput,
} from '@/components/element'

const props = defineProps({
  form: { type: Object, required: true },
  tongTienDichVu: { type: Number, default: 0 },
})

/** xl/lg: 6/hàng · md: 3/hàng · sm/xs (mobile): 2/hàng */
const summaryFieldColProps = {
  xs: 12,
  sm: 12,
  md: 8,
  lg: 4,
  xl: 4,
}

const summaryWideColProps = {
  xs: 24,
  sm: 24,
  md: 16,
  lg: 12,
  xl: 12,
}

const loading = ref(false)
const optionsLoaded = ref(false)
const conceptOptions = ref([])
const trangPhucOptions = ref([])
const selectedConcepts = ref([])
const selectedTrangPhucs = ref([])
const conceptExpanded = ref(true)
const trangPhucExpanded = ref(true)

const conceptFilter = reactive({
  keyword: '',
  chi_da_chon: false,
})

const trangPhucFilter = reactive({
  keyword: '',
  gia_tu: null,
  gia_den: null,
  chi_da_chon: false,
})

const emptyConceptMessage = computed(() => {
  if (!conceptOptions.value.length) return 'Không có concept đang sử dụng.'
  if (conceptFilter.chi_da_chon && !selectedConcepts.value.length) {
    return 'Chưa chọn concept nào.'
  }
  return 'Không tìm thấy concept khớp bộ lọc.'
})

const emptyTrangPhucMessage = computed(() => {
  if (!trangPhucOptions.value.length) return 'Không có trang phục đang hoạt động.'
  if (trangPhucFilter.chi_da_chon && !selectedTrangPhucs.value.length) {
    return 'Chưa chọn trang phục nào.'
  }
  return 'Không tìm thấy trang phục khớp bộ lọc.'
})

const filteredConceptOptions = computed(() => {
  const keyword = String(conceptFilter.keyword || '').trim().toLowerCase()
  return conceptOptions.value.filter((item) => {
    if (conceptFilter.chi_da_chon && !isConceptSelected(item.id)) return false
    if (!keyword) return true
    const name = String(item.ten_concept || '').toLowerCase()
    const place = String(item.dia_diem || '').toLowerCase()
    return name.includes(keyword) || place.includes(keyword)
  })
})

const filteredTrangPhucOptions = computed(() => {
  const filtered = filterServiceOptions(
    trangPhucOptions.value,
    trangPhucFilter,
    (item) => item.ten_san_pham,
    (item) => Number(item.gia_cho_thue) || 0,
  )
  if (!trangPhucFilter.chi_da_chon) return filtered
  return filtered.filter((item) => isTrangPhucSelected(item.id))
})

const tongTienDichVuHienThi = computed(() => Number(props.tongTienDichVu) || 0)

const khachHangPhaiThanhToan = computed(() => {
  const tongDichVu = tongTienDichVuHienThi.value
  const phatSinh = Number(props.form.phat_sinh) || 0
  const giamGia = Number(props.form.khuyen_mai_theo_ma_giam_gia) || 0
  const chietKhau = Number(props.form.chiet_khau) || 0
  return Math.max(0, tongDichVu + phatSinh - giamGia - chietKhau)
})

function filterServiceOptions(items, filter, getName, getPrice) {
  const keyword = String(filter.keyword || '').trim().toLowerCase()
  const giaTu = filter.gia_tu === null || filter.gia_tu === '' ? null : Number(filter.gia_tu)
  const giaDen = filter.gia_den === null || filter.gia_den === '' ? null : Number(filter.gia_den)

  return items.filter((item) => {
    if (keyword) {
      const name = String(getName(item) || '').toLowerCase()
      if (!name.includes(keyword)) return false
    }

    const price = getPrice(item)
    if (Number.isFinite(giaTu) && price < giaTu) return false
    if (Number.isFinite(giaDen) && price > giaDen) return false
    return true
  })
}

function formatMoney(value) {
  const num = Number(value)
  if (!Number.isFinite(num)) return '—'
  return `${num.toLocaleString('vi-VN')} đ`
}

function resetFilters() {
  conceptFilter.keyword = ''
  conceptFilter.chi_da_chon = false
  trangPhucFilter.keyword = ''
  trangPhucFilter.gia_tu = null
  trangPhucFilter.gia_den = null
  trangPhucFilter.chi_da_chon = false
}

function isConceptSelected(id) {
  return selectedConcepts.value.some((row) => row.id === id)
}

function isTrangPhucSelected(id) {
  return selectedTrangPhucs.value.some((row) => row.id === id)
}

function toggleConcept(item) {
  const index = selectedConcepts.value.findIndex((row) => row.id === item.id)
  if (index >= 0) {
    selectedConcepts.value.splice(index, 1)
    return
  }
  selectedConcepts.value.push({
    id: item.id,
    ten: item.ten_concept,
    dia_diem: item.dia_diem || '',
  })
}

function toggleTrangPhuc(item) {
  const index = selectedTrangPhucs.value.findIndex((row) => row.id === item.id)
  if (index >= 0) {
    selectedTrangPhucs.value.splice(index, 1)
    return
  }
  selectedTrangPhucs.value.push({
    id: item.id,
    ten: item.ten_san_pham,
    gia_cho_thue: Number(item.gia_cho_thue) || 0,
    ngay_bat_dau: null,
    ngay_ket_thuc: null,
  })
}

async function loadOptions(force = false) {
  if (!force && optionsLoaded.value) return

  loading.value = true
  try {
    const [conceptRes, trangPhucRes] = await Promise.all([
      fetchConcept({ per_page: 100, trang_thai: 'dang_su_dung' }),
      fetchTrangPhuc({ per_page: 100, trang_thai: 1 }),
    ])
    conceptOptions.value = conceptRes.data.data || []
    trangPhucOptions.value = trangPhucRes.data.data || []
    optionsLoaded.value = true

    const validConceptIds = new Set(conceptOptions.value.map((item) => item.id))
    selectedConcepts.value = selectedConcepts.value.filter((row) => validConceptIds.has(row.id))
    const validTrangPhucIds = new Set(trangPhucOptions.value.map((item) => item.id))
    selectedTrangPhucs.value = selectedTrangPhucs.value.filter((row) =>
      validTrangPhucIds.has(row.id),
    )
  } catch {
    conceptOptions.value = []
    trangPhucOptions.value = []
    optionsLoaded.value = false
  } finally {
    loading.value = false
  }
}

function hydrate(hopDong) {
  const concepts = Array.isArray(hopDong?.concepts) ? hopDong.concepts : []
  selectedConcepts.value = concepts.map((row) => {
    const catalog = row.concept || {}
    return {
      id: row.concept_id,
      ten: catalog.ten_concept || `Concept #${row.concept_id}`,
      dia_diem: catalog.dia_diem || '',
    }
  })

  const trangPhucs = Array.isArray(hopDong?.trang_phucs) ? hopDong.trang_phucs : []
  selectedTrangPhucs.value = trangPhucs.map((row) => {
    const catalog = row.trang_phuc || {}
    return {
      id: row.trang_phuc_id,
      ten: catalog.ten_san_pham || `Trang phục #${row.trang_phuc_id}`,
      gia_cho_thue: Number(catalog.gia_cho_thue) || 0,
      ngay_bat_dau: row.ngay_bat_dau || null,
      ngay_ket_thuc: row.ngay_ket_thuc || null,
    }
  })
}

function reset() {
  selectedConcepts.value = []
  selectedTrangPhucs.value = []
  conceptExpanded.value = true
  trangPhucExpanded.value = true
  conceptOptions.value = []
  trangPhucOptions.value = []
  optionsLoaded.value = false
  resetFilters()
}

function getPayload() {
  return {
    concepts: selectedConcepts.value.map((row) => ({
      concept_id: row.id,
    })),
    trang_phucs: selectedTrangPhucs.value.map((row) => ({
      trang_phuc_id: row.id,
      ngay_bat_dau: row.ngay_bat_dau || null,
      ngay_ket_thuc: row.ngay_ket_thuc || null,
    })),
    phat_sinh: Number(props.form.phat_sinh) || 0,
    chiet_khau: Number(props.form.chiet_khau) || 0,
    ma_giam_gia: props.form.ma_giam_gia?.trim() || null,
    khuyen_mai_theo_ma_giam_gia: Number(props.form.khuyen_mai_theo_ma_giam_gia) || 0,
    so_tien_thanh_toan_lan_1: Number(props.form.so_tien_thanh_toan_lan_1) || 0,
    so_tien_thanh_toan_lan_2: Number(props.form.so_tien_thanh_toan_lan_2) || 0,
    so_tien_thanh_toan_lan_3: Number(props.form.so_tien_thanh_toan_lan_3) || 0,
    thoi_gian_thanh_toan_lan_1: resolveThoiGianThanhToan(
      props.form.so_tien_thanh_toan_lan_1,
      props.form.thoi_gian_thanh_toan_lan_1,
    ),
    thoi_gian_thanh_toan_lan_2: resolveThoiGianThanhToan(
      props.form.so_tien_thanh_toan_lan_2,
      props.form.thoi_gian_thanh_toan_lan_2,
    ),
    thoi_gian_thanh_toan_lan_3: resolveThoiGianThanhToan(
      props.form.so_tien_thanh_toan_lan_3,
      props.form.thoi_gian_thanh_toan_lan_3,
    ),
    qua_tang_kem: props.form.qua_tang_kem?.trim() || null,
    yeu_cau_dac_biet: props.form.yeu_cau_dac_biet?.trim() || null,
  }
}

/**
 * Có số tiền → giữ thời gian cũ hoặc ghi nhận thời điểm hiện tại (Y-m-d H:i:s).
 * Không có số tiền → xóa thời gian thanh toán.
 */
function resolveThoiGianThanhToan(amount, existing) {
  if (!(Number(amount) > 0)) return null
  if (existing) return existing
  return formatNowPaymentDateTimeStorage()
}

/** Lưu DB datetime: Y-m-d H:i:s — hiển thị hh:mm:ss dd/mm/yyyy */
function formatNowPaymentDateTimeStorage(date = new Date()) {
  const pad = (n) => String(n).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`
}

defineExpose({
  loadOptions,
  hydrate,
  reset,
  getPayload,
})
</script>

<style scoped lang="scss">
.step-panel {
  min-height: 220px;
}

.step-panel--dich-vu {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding-bottom: 8px;
}

.dich-vu-section {
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
  background: var(--el-bg-color);
  overflow: hidden;
}

.dich-vu-section__header {
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

.dich-vu-section__title-wrap {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  min-width: 0;
}

.dich-vu-section__title {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.dich-vu-section__count {
  font-size: 12px;
  font-weight: 500;
  color: var(--el-color-primary);
  background: var(--el-color-primary-light-9);
  border-radius: 999px;
  padding: 2px 8px;
}

.dich-vu-section__arrow {
  flex-shrink: 0;
  color: var(--el-text-color-secondary);
  transition: transform 0.2s ease;

  &.is-expanded {
    transform: rotate(180deg);
  }
}

.dich-vu-section__body {
  padding: 12px 14px 14px;
  border-top: 1px solid var(--el-border-color-lighter);
}

.service-filter {
  margin-bottom: 12px;

  :deep(.el-row) {
    row-gap: 10px;
  }
}

.service-filter__keyword,
.service-filter__price {
  width: 100%;
}

.service-filter__switch {
  display: flex;
  align-items: center;
  gap: 8px;
  min-height: 32px;
}

.service-filter__switch-label {
  font-size: 13px;
  color: var(--el-text-color-regular);
  white-space: nowrap;
}

.service-card-grid {
  --service-card-row-height: 88px;
  --service-card-gap: 10px;
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  grid-auto-rows: var(--service-card-row-height);
  gap: var(--service-card-gap);
  // Hiển thị đúng 2 hàng; vượt quá thì cuộn dọc
  max-height: calc(2 * var(--service-card-row-height) + var(--service-card-gap) + 4px);
  overflow-y: auto;
  padding: 2px;
}

.service-card {
  position: relative;
  display: flex;
  align-items: stretch;
  width: 100%;
  height: 100%;
  min-height: 0;
  padding: 0;
  border: 1.5px solid var(--el-border-color-lighter);
  border-radius: 8px;
  background: var(--el-fill-color-blank);
  cursor: pointer;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
  text-align: left;
  overflow: hidden;

  &:hover {
    border-color: var(--el-color-primary-light-5);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  }

  &.is-selected {
    border-color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
    box-shadow: 0 0 0 1px var(--el-color-primary-light-7);
  }
}

.service-card__check {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border: 1.5px solid var(--el-border-color);
  border-radius: 4px;
  background: #fff;
  color: #fff;
  font-size: 12px;
  transition: border-color 0.2s ease, background 0.2s ease;

  &.is-checked {
    border-color: var(--el-color-primary);
    background: var(--el-color-primary);
  }
}

.service-card__body {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 3px;
  min-width: 0;
  padding: 10px 30px 10px 12px;
}

.service-card__name {
  font-size: 13px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.service-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  font-size: 11px;
  color: var(--el-text-color-secondary);
}

.service-card__price {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 2px;
  font-size: 12px;
  font-weight: 700;
  color: var(--el-color-primary);
}

.service-card-empty {
  grid-column: 1 / -1;
  padding: 28px 12px;
  text-align: center;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.selected-table-wrap {
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px dashed var(--el-border-color-lighter);
}

.selected-table-title {
  margin-bottom: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.selected-table-empty {
  margin-top: 8px;
  padding: 12px;
  text-align: center;
  color: var(--el-text-color-secondary);
  font-size: 13px;
  background: var(--el-fill-color-lighter);
  border-radius: 6px;
}

.step2-summary {
  margin-top: 4px;
  padding: 14px 16px 4px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
  background: var(--el-fill-color-blank);
}

.step2-summary :deep(.el-form-item) {
  margin-bottom: 12px;
}

.tong-tien-input {
  :deep(.el-input__inner) {
    font-weight: 700;
    color: var(--el-color-primary);
  }
}

@media (max-width: 1200px) {
  .service-card-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .service-card-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
