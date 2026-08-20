<template>
  <div class="concept-trang-phuc-picker" v-loading="loading">
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
            <CustomCol :xs="12" :sm="12" :md="8">
              <CustomInput
                v-model="trangPhucFilter.keyword"
                placeholder="Tìm theo tên hoặc mã trang phục..."
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
            <CustomCol :xs="24" :sm="12" :md="8">
              <div class="service-filter__switches">
                <div class="service-filter__switch">
                  <el-switch v-model="trangPhucFilter.hien_san_pham_ban" size="small" />
                  <span class="service-filter__switch-label">Hiện sản phẩm bận</span>
                </div>
                <div class="service-filter__switch">
                  <el-switch v-model="trangPhucFilter.chi_da_chon" size="small" />
                  <span class="service-filter__switch-label">Đã chọn</span>
                </div>
              </div>
            </CustomCol>
          </CustomRow>
        </div>

        <div class="san-pham-card-grid">
          <CustomTooltip
            v-for="item in filteredTrangPhucOptions"
            :key="item.id"
            :disabled="!isTrangPhucBusy(item)"
            content="Sản phẩm đã được sử dụng trong khoảng thời gian bạn chọn"
            placement="top"
          >
            <button
              type="button"
              class="san-pham-card"
              :class="{
                'is-selected': isTrangPhucSelected(item.id),
                'is-disabled': isTrangPhucBusy(item),
              }"
              @click="onTrangPhucCardClick(item)"
            >
              <div class="san-pham-card__image">
                <el-avatar
                  v-if="item.hinh_anh"
                  :size="40"
                  :src="mediaUrl(item.hinh_anh)"
                  shape="square"
                  class="san-pham-card__avatar"
                />
                <div v-else class="san-pham-card__placeholder">
                  <CustomIcon :size="24"><Picture /></CustomIcon>
                </div>
                <span v-if="isTrangPhucSelected(item.id)" class="san-pham-card__check">
                  <CustomIcon><Check /></CustomIcon>
                </span>
              </div>
              <div class="san-pham-card__body">
                <div class="san-pham-card__code">{{ item.ma_san_pham }}</div>
                <div class="san-pham-card__name" :title="item.ten_san_pham">{{ item.ten_san_pham }}</div>
                <div class="san-pham-card__price">{{ formatMoney(item.gia_cho_thue) }}</div>
              </div>
              <span
                v-if="item.co_lich_cho_thue"
                class="san-pham-card__calendar"
                title="Xem lịch sử dụng"
                @click.stop="openLichChoThue(item)"
              >
                <CustomIcon :size="14"><Calendar /></CustomIcon>
              </span>
            </button>
          </CustomTooltip>
          <div v-if="!loading && !filteredTrangPhucOptions.length" class="san-pham-empty">
            {{ emptyTrangPhucMessage }}
          </div>
        </div>

        <div class="selected-table-wrap">
          <div class="selected-table-title">Danh sách trang phục đã chọn</div>
          <CustomTable v-if="selectedTrangPhucs.length" :data="selectedTrangPhucs" stripe style="width: 100%">
            <CustomTableColumn label="STT" width="64" align="center">
              <template #default="{ $index }">{{ $index + 1 }}</template>
            </CustomTableColumn>
            <CustomTableColumn label="Tên trang phục" min-width="180">
              <template #default="{ row }">{{ formatTrangPhucLabel(row) }}</template>
            </CustomTableColumn>
            <CustomTableColumn label="Giá thuê" width="130" align="right">
              <template #default="{ row }">{{ formatMoney(row.gia_cho_thue) }}</template>
            </CustomTableColumn>
          </CustomTable>
          <div v-else class="selected-table-empty">Chưa chọn trang phục nào.</div>
        </div>
      </div>
    </section>

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
            <div v-if="row.hop_dong?.sdt_khach_hang" class="lich-sub-text">
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
            <el-tag :type="lichTrangThaiTagType(row.hop_dong?.trang_thai)" size="small">
              {{ lichTrangThaiLabel(row.hop_dong?.trang_thai) }}
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
import { computed, reactive, ref, watch } from 'vue'
import { ArrowDown, Calendar, Check, Picture, Search } from '@element-plus/icons-vue'
import { fetchConcept } from '@/api/concept'
import { fetchTrangPhuc, fetchTrangPhucLichChoThue } from '@/api/trangPhuc'
import {
  CustomButton,
  CustomCol,
  CustomDialog,
  CustomIcon,
  CustomInput,
  CustomRow,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
  MoneyInput,
} from '@/components/element'
import { mediaUrl } from '@/utils/media'

const props = defineProps({
  ngayChup: { type: String, default: null },
})

const selectedConcepts = defineModel('concepts', { type: Array, default: () => [] })
const selectedTrangPhucs = defineModel('trangPhucs', { type: Array, default: () => [] })

const lichTrangThaiOptions = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
  { value: 'cho_xac_nhan', label: 'Chờ xác nhận' },
  { value: 'dang_thue', label: 'Đang thuê' },
  { value: 'da_tra', label: 'Đã trả' },
  { value: 'qua_han', label: 'Quá hạn' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
  { value: 'da_huy', label: 'Đã hủy' },
]

const loading = ref(false)
const optionsLoaded = ref(false)
const conceptOptions = ref([])
const trangPhucOptions = ref([])
const conceptExpanded = ref(true)
const trangPhucExpanded = ref(true)

const lichModalVisible = ref(false)
const lichLoading = ref(false)
const lichItems = ref([])
const lichCurrentProduct = ref(null)

const conceptFilter = reactive({
  keyword: '',
  chi_da_chon: false,
})

const trangPhucFilter = reactive({
  keyword: '',
  gia_tu: null,
  gia_den: null,
  hien_san_pham_ban: true,
  chi_da_chon: false,
})

const lichModalTitle = computed(() => {
  const product = lichCurrentProduct.value
  if (!product) return 'Lịch sử dụng sản phẩm'
  return `Lịch sử dụng — ${product.ma_san_pham || product.ten_san_pham || ''}`
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
  let filtered = filterServiceOptions(
    trangPhucOptions.value,
    trangPhucFilter,
    (item) => [item.ten_san_pham, item.ma_san_pham].filter(Boolean).join(' '),
    (item) => Number(item.gia_cho_thue) || 0,
  )
  if (!trangPhucFilter.hien_san_pham_ban) {
    filtered = filtered.filter((item) => !isTrangPhucBusy(item))
  }
  if (!trangPhucFilter.chi_da_chon) return filtered
  return filtered.filter((item) => isTrangPhucSelected(item.id))
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

function formatDate(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('vi-VN')
}

function formatTrangPhucLabel(item) {
  const ten = item?.ten_san_pham || item?.ten || ''
  const ma = String(item?.ma_san_pham || '').trim()
  if (!ma) return ten || '—'
  return ten ? `${ten} - [${ma}]` : `[${ma}]`
}

function lichTrangThaiLabel(value) {
  return lichTrangThaiOptions.find((opt) => opt.value === value)?.label || value || '—'
}

function lichTrangThaiTagType(value) {
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

function isConceptSelected(id) {
  return selectedConcepts.value.some((row) => row.id === id)
}

function isTrangPhucSelected(id) {
  return selectedTrangPhucs.value.some((row) => row.id === id)
}

function isTrangPhucBusy(item) {
  return Boolean(item?.dang_su_dung)
}

function onTrangPhucCardClick(item) {
  if (isTrangPhucBusy(item)) return
  toggleTrangPhuc(item)
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
  if (isTrangPhucBusy(item)) return
  const index = selectedTrangPhucs.value.findIndex((row) => row.id === item.id)
  if (index >= 0) {
    selectedTrangPhucs.value.splice(index, 1)
    return
  }
  const ngayChup = props.ngayChup || null
  selectedTrangPhucs.value.push({
    id: item.id,
    ten: item.ten_san_pham,
    ma_san_pham: item.ma_san_pham || '',
    gia_cho_thue: Number(item.gia_cho_thue) || 0,
    ngay_bat_dau: ngayChup,
    ngay_ket_thuc: ngayChup,
  })
}

function syncSelectedTrangPhucDates(ngayChup) {
  if (!ngayChup) return
  selectedTrangPhucs.value = selectedTrangPhucs.value.map((row) => ({
    ...row,
    ngay_bat_dau: ngayChup,
    ngay_ket_thuc: ngayChup,
  }))
}

async function loadTrangPhucOptions() {
  try {
    const params = {
      per_page: 100,
      trang_thai: 1,
    }
    if (props.ngayChup) {
      params.ngay_thue = props.ngayChup
      params.ngay_tra_du_kien = props.ngayChup
    }
    const trangPhucRes = await fetchTrangPhuc(params)
    trangPhucOptions.value = trangPhucRes.data.data || []
    removeBusySelectedTrangPhuc()
  } catch {
    trangPhucOptions.value = []
  }
}

function removeBusySelectedTrangPhuc() {
  if (!props.ngayChup) return
  const busyIds = new Set(
    trangPhucOptions.value.filter((item) => isTrangPhucBusy(item)).map((item) => item.id),
  )
  if (!busyIds.size) return
  selectedTrangPhucs.value = selectedTrangPhucs.value.filter((row) => !busyIds.has(row.id))
}

async function openLichChoThue(item) {
  lichCurrentProduct.value = item
  lichModalVisible.value = true
  lichLoading.value = true
  lichItems.value = []
  try {
    const { data } = await fetchTrangPhucLichChoThue(item.id)
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

async function loadOptions(force = false) {
  if (!force && optionsLoaded.value) return

  loading.value = true
  try {
    const trangPhucParams = {
      per_page: 100,
      trang_thai: 1,
    }
    if (props.ngayChup) {
      trangPhucParams.ngay_thue = props.ngayChup
      trangPhucParams.ngay_tra_du_kien = props.ngayChup
    }

    const [conceptRes, trangPhucRes] = await Promise.all([
      fetchConcept({ per_page: 100, trang_thai: 'dang_su_dung' }),
      fetchTrangPhuc(trangPhucParams),
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
    removeBusySelectedTrangPhuc()
  } catch {
    conceptOptions.value = []
    trangPhucOptions.value = []
    optionsLoaded.value = false
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  conceptFilter.keyword = ''
  conceptFilter.chi_da_chon = false
  trangPhucFilter.keyword = ''
  trangPhucFilter.gia_tu = null
  trangPhucFilter.gia_den = null
  trangPhucFilter.hien_san_pham_ban = true
  trangPhucFilter.chi_da_chon = false
}

function reset() {
  selectedConcepts.value = []
  selectedTrangPhucs.value = []
  conceptExpanded.value = true
  trangPhucExpanded.value = true
  conceptOptions.value = []
  trangPhucOptions.value = []
  optionsLoaded.value = false
  lichModalVisible.value = false
  lichCurrentProduct.value = null
  lichItems.value = []
  resetFilters()
}

watch(
  () => props.ngayChup,
  (next, prev) => {
    if (next === prev) return
    syncSelectedTrangPhucDates(next)
    if (optionsLoaded.value) {
      loadTrangPhucOptions()
    }
  },
)

defineExpose({
  loadOptions,
  reset,
})
</script>

<style scoped lang="scss">
.concept-trang-phuc-picker {
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

.service-filter__switches {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px 16px;
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
  flex-shrink: 0;
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

.service-card-empty {
  grid-column: 1 / -1;
  padding: 28px 12px;
  text-align: center;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.san-pham-card-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  grid-auto-rows: 68px;
  gap: 10px;
  max-height: calc(2 * 68px + 10px + 8px);
  overflow-y: auto;
  padding: 4px;

  @media (max-width: 1280px) {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  @media (max-width: 900px) {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  @media (max-width: 576px) {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
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
  padding: 6px 28px 6px 8px;
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

.san-pham-empty {
  grid-column: 1 / -1;
  padding: 24px 12px;
  text-align: center;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.lich-sub-text {
  margin-top: 2px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.lich-empty {
  padding: 24px 12px;
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
