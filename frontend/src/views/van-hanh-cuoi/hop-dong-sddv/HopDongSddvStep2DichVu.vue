<template>
  <div class="step-panel step-panel--dich-vu" v-loading="loading">
    <section class="dich-vu-section" :class="{ 'is-collapsed': !comboExpanded }">
      <button type="button" class="dich-vu-section__header" @click="comboExpanded = !comboExpanded">
        <div class="dich-vu-section__title-wrap">
          <span class="dich-vu-section__title">Chọn combo</span>
          <span v-if="selectedCombos.length" class="dich-vu-section__count">
            Đã chọn {{ selectedCombos.length }}
          </span>
        </div>
        <CustomIcon class="dich-vu-section__arrow" :class="{ 'is-expanded': comboExpanded }">
          <ArrowDown />
        </CustomIcon>
      </button>
      <div v-show="comboExpanded" class="dich-vu-section__body">
        <div class="service-filter">
          <CustomInput
            v-model="comboFilter.keyword"
            placeholder="Tìm theo tên combo..."
            clearable
            class="service-filter__keyword"
          >
            <template #prefix>
              <CustomIcon><Search /></CustomIcon>
            </template>
          </CustomInput>
          <MoneyInput
            v-model="comboFilter.gia_tu"
            placeholder="Giá từ"
            clearable
            class="service-filter__price"
          />
          <MoneyInput
            v-model="comboFilter.gia_den"
            placeholder="Giá đến"
            clearable
            class="service-filter__price"
          />
        </div>

        <div class="service-card-grid">
          <button
            v-for="item in filteredComboOptions"
            :key="item.id"
            type="button"
            class="service-card"
            :class="{ 'is-selected': isComboSelected(item.id) }"
            @click="toggleCombo(item)"
          >
            <span class="service-card__check" :class="{ 'is-checked': isComboSelected(item.id) }">
              <CustomIcon v-if="isComboSelected(item.id)"><Check /></CustomIcon>
            </span>
            <div class="service-card__body">
              <div class="service-card__name" :title="item.ten_nhom">{{ item.ten_nhom }}</div>
              <div class="service-card__meta">
                <span v-if="item.so_diem_chup">{{ item.so_diem_chup }} điểm</span>
                <span v-if="item.so_anh_chinh_sua">{{ item.so_anh_chinh_sua }} ảnh</span>
              </div>
              <div class="service-card__price">
                <span v-if="hasPromotion(item)" class="service-card__price-origin">
                  {{ formatMoney(item.gia_goc) }}
                </span>
                <span>{{ formatMoney(displayPrice(item)) }}</span>
              </div>
            </div>
          </button>
          <div v-if="!loading && !filteredComboOptions.length" class="service-card-empty">
            {{ emptyComboMessage }}
          </div>
        </div>

        <div class="selected-table-wrap">
          <div class="selected-table-title">Danh sách combo đã chọn</div>
          <CustomTable
            v-if="selectedCombos.length"
            :data="selectedCombos"
            stripe
            show-summary
            :summary-method="getComboSummaries"
            class="selected-combo-table"
            style="width: 100%"
          >
            <CustomTableColumn label="STT" width="64" align="center">
              <template #default="{ $index }">{{ $index + 1 }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="ten" label="Tên dịch vụ" min-width="180" />
            <CustomTableColumn prop="so_diem_chup" label="Số điểm chụp" width="120" align="center">
              <template #default="{ row }">
                {{ formatComboMetric(row, 'so_diem_chup') }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn prop="so_anh_chinh_sua" label="Số ảnh chỉnh sửa" width="140" align="center">
              <template #default="{ row }">
                {{ formatComboMetric(row, 'so_anh_chinh_sua') }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Số lượng" width="140" align="center">
              <template #default="{ row }">
                <el-input-number
                  v-model="row.so_luong"
                  :min="1"
                  :max="999"
                  :step="1"
                  controls-position="right"
                  style="width: 110px"
                />
              </template>
            </CustomTableColumn>
            <CustomTableColumn prop="thanh_tien" label="Thành tiền" width="140" align="right">
              <template #default="{ row }">
                {{ formatMoney(rowThanhTien(row)) }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Ghi chú" min-width="180">
              <template #default="{ row }">
                <CustomInput
                  v-model="row.ghi_chu"
                  placeholder="Nhập ghi chú"
                  maxlength="100"
                  show-word-limit
                  clearable
                />
              </template>
            </CustomTableColumn>
          </CustomTable>
          <div v-else class="selected-table-empty">
            Chưa chọn combo nào.
          </div>
        </div>
      </div>
    </section>

    <section class="dich-vu-section" :class="{ 'is-collapsed': !dichVuLeExpanded }">
      <button type="button" class="dich-vu-section__header" @click="dichVuLeExpanded = !dichVuLeExpanded">
        <div class="dich-vu-section__title-wrap">
          <span class="dich-vu-section__title">Chọn dịch vụ lẻ</span>
          <span v-if="selectedDichVuLes.length" class="dich-vu-section__count">
            Đã chọn {{ selectedDichVuLes.length }}
          </span>
        </div>
        <CustomIcon class="dich-vu-section__arrow" :class="{ 'is-expanded': dichVuLeExpanded }">
          <ArrowDown />
        </CustomIcon>
      </button>
      <div v-show="dichVuLeExpanded" class="dich-vu-section__body">
        <div class="service-filter">
          <CustomInput
            v-model="dichVuLeFilter.keyword"
            placeholder="Tìm theo tên dịch vụ..."
            clearable
            class="service-filter__keyword"
          >
            <template #prefix>
              <CustomIcon><Search /></CustomIcon>
            </template>
          </CustomInput>
          <MoneyInput
            v-model="dichVuLeFilter.gia_tu"
            placeholder="Giá từ"
            clearable
            class="service-filter__price"
          />
          <MoneyInput
            v-model="dichVuLeFilter.gia_den"
            placeholder="Giá đến"
            clearable
            class="service-filter__price"
          />
        </div>

        <div class="service-card-grid">
          <button
            v-for="item in filteredDichVuLeOptions"
            :key="item.id"
            type="button"
            class="service-card"
            :class="{ 'is-selected': isDichVuLeSelected(item.id) }"
            @click="toggleDichVuLe(item)"
          >
            <span class="service-card__check" :class="{ 'is-checked': isDichVuLeSelected(item.id) }">
              <CustomIcon v-if="isDichVuLeSelected(item.id)"><Check /></CustomIcon>
            </span>
            <div class="service-card__body">
              <div class="service-card__name" :title="item.ten_dich_vu">{{ item.ten_dich_vu }}</div>
              <div v-if="item.loai_dich_vu?.ten_dich_vu" class="service-card__meta">
                <span>{{ item.loai_dich_vu.ten_dich_vu }}</span>
              </div>
              <div class="service-card__price">
                <span v-if="hasPromotion(item)" class="service-card__price-origin">
                  {{ formatMoney(item.gia_goc) }}
                </span>
                <span>{{ formatMoney(displayPrice(item)) }}</span>
              </div>
            </div>
          </button>
          <div v-if="!loading && !filteredDichVuLeOptions.length" class="service-card-empty">
            {{ emptyDichVuLeMessage }}
          </div>
        </div>

        <div class="selected-table-wrap">
          <div class="selected-table-title">Danh sách dịch vụ lẻ đã chọn</div>
          <CustomTable v-if="selectedDichVuLes.length" :data="selectedDichVuLes" stripe style="width: 100%">
            <CustomTableColumn label="STT" width="64" align="center">
              <template #default="{ $index }">{{ $index + 1 }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="ten" label="Tên dịch vụ" min-width="180" />
            <CustomTableColumn label="Số lượng" width="140" align="center">
              <template #default="{ row }">
                <el-input-number
                  v-model="row.so_luong"
                  :min="1"
                  :max="999"
                  :step="1"
                  controls-position="right"
                  style="width: 110px"
                />
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Thành tiền" width="140" align="right">
              <template #default="{ row }">
                {{ formatMoney(rowThanhTien(row)) }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Ghi chú" min-width="180">
              <template #default="{ row }">
                <CustomInput
                  v-model="row.ghi_chu"
                  placeholder="Nhập ghi chú"
                  maxlength="100"
                  show-word-limit
                  clearable
                />
              </template>
            </CustomTableColumn>
          </CustomTable>
          <div v-else class="selected-table-empty">
            Chưa chọn dịch vụ lẻ nào.
          </div>
        </div>
      </div>
    </section>

    <CustomForm class="step2-summary" label-position="top">
      <CustomRow :gutter="16">
        <CustomCol :xs="24" :sm="8">
          <CustomFormItem label="Tiền combo">
            <MoneyInput :model-value="tienCombo" readonly style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
        <CustomCol :xs="24" :sm="8">
          <CustomFormItem label="Tiền dịch vụ">
            <MoneyInput :model-value="tienDichVu" readonly style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
        <CustomCol :xs="24" :sm="8">
          <CustomFormItem label="Tổng tiền">
            <MoneyInput :model-value="tongTienDichVu" readonly class="tong-tien-input" style="width: 100%" />
          </CustomFormItem>
        </CustomCol>
      </CustomRow>
    </CustomForm>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { ArrowDown, Check, Search } from '@element-plus/icons-vue'
import { fetchDichVuDanhSachDichNhomDichVu } from '@/api/dichVuDanhSachDichNhomDichVu'
import { fetchDichVuDanhSachDichVuLe } from '@/api/dichVuDanhSachDichVuLe'
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
  loaiHopDongId: { type: Number, default: null },
})

const emit = defineEmits(['tong-tien-change'])

const loading = ref(false)
const comboOptions = ref([])
const dichVuLeOptions = ref([])
const selectedCombos = ref([])
const selectedDichVuLes = ref([])
const comboExpanded = ref(true)
const dichVuLeExpanded = ref(true)
const loadedForLoaiId = ref(null)

const comboFilter = reactive({
  keyword: '',
  gia_tu: null,
  gia_den: null,
})

const dichVuLeFilter = reactive({
  keyword: '',
  gia_tu: null,
  gia_den: null,
})

const emptyComboMessage = computed(() => {
  if (!props.loaiHopDongId) return 'Vui lòng chọn loại hợp đồng ở bước 1 để tải combo.'
  if (!comboOptions.value.length) return 'Không có combo phù hợp với loại hợp đồng này.'
  return 'Không tìm thấy combo khớp bộ lọc.'
})

const emptyDichVuLeMessage = computed(() => {
  if (!props.loaiHopDongId) return 'Vui lòng chọn loại hợp đồng ở bước 1 để tải dịch vụ lẻ.'
  if (!dichVuLeOptions.value.length) return 'Không có dịch vụ lẻ phù hợp với loại hợp đồng này.'
  return 'Không tìm thấy dịch vụ lẻ khớp bộ lọc.'
})

const filteredComboOptions = computed(() =>
  filterServiceOptions(comboOptions.value, comboFilter, (item) => item.ten_nhom),
)

const filteredDichVuLeOptions = computed(() =>
  filterServiceOptions(dichVuLeOptions.value, dichVuLeFilter, (item) => item.ten_dich_vu),
)

const tienCombo = computed(() =>
  selectedCombos.value.reduce((sum, row) => sum + rowThanhTien(row), 0),
)

const tienDichVu = computed(() =>
  selectedDichVuLes.value.reduce((sum, row) => sum + rowThanhTien(row), 0),
)

const tongTienDichVu = computed(() => tienCombo.value + tienDichVu.value)

const tongSoDiemChup = computed(() =>
  selectedCombos.value.reduce((sum, row) => sum + comboMetricTotal(row, 'so_diem_chup'), 0),
)

function filterServiceOptions(items, filter, getName, getPrice = displayPrice) {
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

function hasPromotion(item) {
  const promo = Number(item?.gia_khuyen_mai)
  return Number.isFinite(promo) && promo > 0 && promo < Number(item?.gia_goc)
}

function displayPrice(item) {
  if (hasPromotion(item)) return Number(item.gia_khuyen_mai)
  return Number(item?.gia_goc) || 0
}

function rowThanhTien(row) {
  const donGia = Number(row?.don_gia) || 0
  const soLuong = Math.max(1, Number(row?.so_luong) || 1)
  return donGia * soLuong
}

function comboMetricTotal(row, key) {
  const unit = Math.max(0, Number(row?.[key]) || 0)
  const soLuong = Math.max(1, Number(row?.so_luong) || 1)
  return unit * soLuong
}

function formatComboMetric(row, key) {
  const total = comboMetricTotal(row, key)
  return total > 0 ? total : '—'
}

function formatComboMetricSum(value) {
  const total = Math.max(0, Number(value) || 0)
  return total > 0 ? total : '—'
}

function getComboSummaries({ columns }) {
  const tongDiemChup = selectedCombos.value.reduce(
    (sum, row) => sum + comboMetricTotal(row, 'so_diem_chup'),
    0,
  )
  const tongAnhChinhSua = selectedCombos.value.reduce(
    (sum, row) => sum + comboMetricTotal(row, 'so_anh_chinh_sua'),
    0,
  )

  return columns.map((column, index) => {
    if (index === 0) return 'Tổng'
    if (column.property === 'so_diem_chup') return formatComboMetricSum(tongDiemChup)
    if (column.property === 'so_anh_chinh_sua') return formatComboMetricSum(tongAnhChinhSua)
    if (column.property === 'thanh_tien') return formatMoney(tienCombo.value)
    return ''
  })
}

function isComboSelected(id) {
  return selectedCombos.value.some((row) => row.id === id)
}

function isDichVuLeSelected(id) {
  return selectedDichVuLes.value.some((row) => row.id === id)
}

function toggleCombo(item) {
  const index = selectedCombos.value.findIndex((row) => row.id === item.id)
  if (index >= 0) {
    selectedCombos.value.splice(index, 1)
    return
  }
  selectedCombos.value.push({
    id: item.id,
    ten: item.ten_nhom,
    don_gia: displayPrice(item),
    so_diem_chup: Number(item.so_diem_chup) || 0,
    so_anh_chinh_sua: Number(item.so_anh_chinh_sua) || 0,
    so_luong: 1,
    ghi_chu: '',
  })
}

function toggleDichVuLe(item) {
  const index = selectedDichVuLes.value.findIndex((row) => row.id === item.id)
  if (index >= 0) {
    selectedDichVuLes.value.splice(index, 1)
    return
  }
  selectedDichVuLes.value.push({
    id: item.id,
    ten: item.ten_dich_vu,
    don_gia: displayPrice(item),
    so_luong: 1,
    ghi_chu: '',
  })
}

function resetServiceFilters() {
  comboFilter.keyword = ''
  comboFilter.gia_tu = null
  comboFilter.gia_den = null
  dichVuLeFilter.keyword = ''
  dichVuLeFilter.gia_tu = null
  dichVuLeFilter.gia_den = null
}

async function loadOptions(force = false) {
  const loaiId = props.loaiHopDongId
  if (!loaiId) {
    comboOptions.value = []
    dichVuLeOptions.value = []
    loadedForLoaiId.value = null
    return
  }
  if (!force && loadedForLoaiId.value === loaiId) return

  loading.value = true
  try {
    const params = {
      per_page: 100,
      trang_thai: 'dang_su_dung',
      loai_hop_dong_id: loaiId,
    }
    const [comboRes, dichVuRes] = await Promise.all([
      fetchDichVuDanhSachDichNhomDichVu(params),
      fetchDichVuDanhSachDichVuLe(params),
    ])
    comboOptions.value = comboRes.data.data || []
    dichVuLeOptions.value = dichVuRes.data.data || []
    loadedForLoaiId.value = loaiId

    const validComboIds = new Set(comboOptions.value.map((item) => item.id))
    selectedCombos.value = selectedCombos.value.filter((row) => validComboIds.has(row.id))
    const validDichVuIds = new Set(dichVuLeOptions.value.map((item) => item.id))
    selectedDichVuLes.value = selectedDichVuLes.value.filter((row) => validDichVuIds.has(row.id))
  } catch {
    comboOptions.value = []
    dichVuLeOptions.value = []
    loadedForLoaiId.value = null
  } finally {
    loading.value = false
  }
}

function hydrate(hopDong) {
  const combos = Array.isArray(hopDong?.combos) ? hopDong.combos : []
  selectedCombos.value = combos.map((row) => {
    const catalog = row.combo || {}
    const donGia = displayPrice(catalog)
    const soLuong = Math.max(1, Number(row.so_luong) || 1)
    return {
      id: row.combo_id,
      ten: catalog.ten_nhom || `Combo #${row.combo_id}`,
      don_gia: donGia || Math.round(Number(row.thanh_tien || 0) / soLuong),
      so_diem_chup: Number(catalog.so_diem_chup) || 0,
      so_anh_chinh_sua: Number(catalog.so_anh_chinh_sua) || 0,
      so_luong: soLuong,
      ghi_chu: row.ghi_chu || '',
    }
  })

  const dichVuRows = Array.isArray(hopDong?.dich_vu) ? hopDong.dich_vu : []
  selectedDichVuLes.value = dichVuRows.map((row) => {
    const catalog = row.dich_vu || {}
    const donGia = displayPrice(catalog)
    const soLuong = Math.max(1, Number(row.so_luong) || 1)
    return {
      id: row.dich_vu_id,
      ten: catalog.ten_dich_vu || `Dịch vụ #${row.dich_vu_id}`,
      don_gia: donGia || Math.round(Number(row.thanh_tien || 0) / soLuong),
      so_luong: soLuong,
      ghi_chu: row.ghi_chu || '',
    }
  })
}

function reset() {
  selectedCombos.value = []
  selectedDichVuLes.value = []
  comboExpanded.value = true
  dichVuLeExpanded.value = true
  comboOptions.value = []
  dichVuLeOptions.value = []
  loadedForLoaiId.value = null
  resetServiceFilters()
}

function getTongTien() {
  return tongTienDichVu.value
}

function getTongSoDiemChup() {
  return tongSoDiemChup.value
}

function getPayload() {
  return {
    combos: selectedCombos.value.map((row) => ({
      combo_id: row.id,
      so_luong: Math.max(1, Number(row.so_luong) || 1),
      thanh_tien: rowThanhTien(row),
      ghi_chu: String(row.ghi_chu || '').trim().slice(0, 100) || null,
    })),
    dich_vu: selectedDichVuLes.value.map((row) => ({
      dich_vu_id: row.id,
      so_luong: Math.max(1, Number(row.so_luong) || 1),
      thanh_tien: rowThanhTien(row),
      ghi_chu: String(row.ghi_chu || '').trim().slice(0, 100) || null,
    })),
    tong_tien: tongTienDichVu.value,
  }
}

watch(
  () => props.loaiHopDongId,
  () => {
    loadedForLoaiId.value = null
  },
)

watch(
  [tongTienDichVu, tongSoDiemChup, selectedCombos, selectedDichVuLes],
  () => {
    emit('tong-tien-change', {
      total: tongTienDichVu.value,
      tongSoDiemChup: tongSoDiemChup.value,
      hasSelection: selectedCombos.value.length > 0 || selectedDichVuLes.value.length > 0,
    })
  },
  { immediate: true, deep: true },
)

defineExpose({
  loadOptions,
  hydrate,
  reset,
  getPayload,
  getTongTien,
  getTongSoDiemChup,
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
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 12px;
}

.service-filter__keyword {
  flex: 1 1 240px;
  min-width: 200px;
}

.service-filter__price {
  flex: 0 1 160px;
  width: 160px;
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

.service-card__price-origin {
  font-size: 11px;
  font-weight: 500;
  color: var(--el-text-color-secondary);
  text-decoration: line-through;
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

.selected-combo-table :deep(.el-table__footer-wrapper td) {
  font-weight: 600;
  color: var(--el-text-color-primary);
  background: var(--el-fill-color-lighter);
}

.selected-combo-table :deep(.el-table__footer-wrapper td:nth-child(6)) {
  color: var(--el-color-primary);
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
