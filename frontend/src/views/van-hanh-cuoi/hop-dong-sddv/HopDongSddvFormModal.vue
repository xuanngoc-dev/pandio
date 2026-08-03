<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1380"
    class="hop-dong-sddv-form-modal"
    @closed="onClosed"
  >
    <div class="steps-wrap">
      <el-steps
        class="sddv-steps"
        :active="activeStep"
        finish-status="success"
        process-status="process"
        align-center
      >
        <el-step
          v-for="(step, index) in steps"
          :key="step.key"
          :title="step.title"
          :class="{ 'is-clickable': index <= activeStep }"
          @click="goToStep(index)"
        />
      </el-steps>
    </div>

    <div v-show="activeStep === 0" class="step-panel">
      <CustomForm ref="step1FormRef" :model="form" :rules="step1Rules" label-position="top">
        <CustomRow :gutter="16">
          <CustomCol v-bind="fieldColProps">
            <CustomFormItem label="Mã hợp đồng" prop="ma_hop_dong">
              <CustomInput v-model="form.ma_hop_dong" readonly />
            </CustomFormItem>
          </CustomCol>
          <CustomCol v-bind="fieldColProps">
            <CustomFormItem label="Loại hợp đồng" prop="loai_hop_dong_id">
              <CustomSelect
                v-model="form.loai_hop_dong_id"
                placeholder="Chọn loại hợp đồng"
                filterable
                style="width: 100%"
                @change="onLoaiHopDongChange"
              >
                <CustomOption
                  v-for="item in loaiHopDongOptions"
                  :key="item.id"
                  :label="item.ten_hop_dong"
                  :value="item.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol v-bind="fieldColProps">
            <CustomFormItem label="Tên khách hàng" prop="ten_khach_hang">
              <CustomInput v-model="form.ten_khach_hang" placeholder="Nhập tên khách hàng" clearable />
            </CustomFormItem>
          </CustomCol>
          <CustomCol v-bind="fieldColProps">
            <CustomFormItem label="SĐT khách hàng" prop="sdt_khach_hang">
              <CustomInput v-model="form.sdt_khach_hang" placeholder="Nhập số điện thoại" clearable />
            </CustomFormItem>
          </CustomCol>
          <CustomCol v-bind="fieldColProps">
            <CustomFormItem label="Địa chỉ" prop="dia_chi">
              <CustomInput v-model="form.dia_chi" placeholder="Nhập địa chỉ" clearable />
            </CustomFormItem>
          </CustomCol>
          <CustomCol v-bind="fieldColProps">
            <CustomFormItem label="Kênh tiếp cận" prop="kenh_tiep_can">
              <CustomSelect
                v-model="form.kenh_tiep_can"
                placeholder="Chọn kênh tiếp cận"
                clearable
                filterable
                allow-create
                default-first-option
                style="width: 100%"
              >
                <CustomOption
                  v-for="opt in kenhTiepCanOptions"
                  :key="opt"
                  :label="opt"
                  :value="opt"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>

          <CustomCol
            v-for="field in dynamicFields"
            :key="field.key"
            v-bind="getFieldColProps(field)"
          >
            <CustomFormItem
              :label="field.ten_truong"
              :prop="`thong_tin_hop_dong.${field.key}`"
              :rules="getDynamicFieldRules(field)"
            >
              <template v-if="isTextarea(field.kieu)">
                <CustomInput
                  v-model="form.thong_tin_hop_dong[field.key]"
                  type="textarea"
                  :rows="3"
                  :placeholder="`Nhập ${field.ten_truong.toLowerCase()}`"
                />
              </template>
              <template v-else-if="isMoney(field.kieu)">
                <MoneyInput
                  v-model="form.thong_tin_hop_dong[field.key]"
                  :placeholder="`Nhập ${field.ten_truong.toLowerCase()}`"
                  style="width: 100%"
                />
              </template>
              <template v-else-if="isNumberLike(field.kieu)">
                <CustomInput
                  v-model="form.thong_tin_hop_dong[field.key]"
                  type="number"
                  :placeholder="`Nhập ${field.ten_truong.toLowerCase()}`"
                />
              </template>
              <template v-else-if="field.kieu === 'select'">
                <CustomSelect
                  v-model="form.thong_tin_hop_dong[field.key]"
                  :placeholder="`Chọn ${field.ten_truong.toLowerCase()}`"
                  clearable
                  filterable
                  style="width: 100%"
                >
                  <CustomOption
                    v-for="opt in field.options || []"
                    :key="opt.value"
                    :label="opt.label"
                    :value="opt.value"
                  />
                </CustomSelect>
              </template>
              <template v-else-if="field.kieu === 'radio'">
                <el-radio-group v-model="form.thong_tin_hop_dong[field.key]">
                  <el-radio
                    v-for="opt in field.options || []"
                    :key="opt.value"
                    :value="opt.value"
                  >
                    {{ opt.label }}
                  </el-radio>
                </el-radio-group>
              </template>
              <template v-else-if="field.kieu === 'checkbox'">
                <el-checkbox v-model="form.thong_tin_hop_dong[field.key]">
                  {{ field.ten_truong }}
                </el-checkbox>
              </template>
              <template v-else-if="field.kieu === 'checkbox_group'">
                <el-checkbox-group v-model="form.thong_tin_hop_dong[field.key]">
                  <el-checkbox
                    v-for="opt in field.options || []"
                    :key="opt.value"
                    :value="opt.value"
                  >
                    {{ opt.label }}
                  </el-checkbox>
                </el-checkbox-group>
              </template>
              <template v-else-if="field.kieu === 'switch'">
                <el-switch v-model="form.thong_tin_hop_dong[field.key]" />
              </template>
              <template v-else-if="isDateLike(field.kieu)">
                <el-date-picker
                  v-model="form.thong_tin_hop_dong[field.key]"
                  :type="datePickerType(field.kieu)"
                  :format="datePickerFormat(field.kieu)"
                  :value-format="datePickerValueFormat(field.kieu)"
                  :placeholder="`Chọn ${field.ten_truong.toLowerCase()}`"
                  style="width: 100%"
                />
              </template>
              <template v-else-if="field.kieu === 'time'">
                <el-time-picker
                  v-model="form.thong_tin_hop_dong[field.key]"
                  format="HH:mm"
                  value-format="HH:mm"
                  :placeholder="`Chọn ${field.ten_truong.toLowerCase()}`"
                  style="width: 100%"
                />
              </template>
              <template v-else>
                <CustomInput
                  v-model="form.thong_tin_hop_dong[field.key]"
                  :type="textInputType(field.kieu)"
                  :placeholder="`Nhập ${field.ten_truong.toLowerCase()}`"
                  clearable
                />
              </template>
            </CustomFormItem>
          </CustomCol>

          <CustomCol v-bind="fieldColProps">
            <CustomFormItem label="Người tham gia" prop="nguoi_tham_gia_ids">
              <CustomSelect
                v-model="form.nguoi_tham_gia_ids"
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

        <CustomRow :gutter="16">
          <CustomCol :span="24">
            <CustomFormItem label="Ghi chú sale" prop="ghi_chu_sale">
              <CustomInput
                v-model="form.ghi_chu_sale"
                type="textarea"
                :rows="3"
                placeholder="Ghi chú nội bộ của sale"
              />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
    </div>

    <div v-show="activeStep === 1" class="step-panel step-panel--dich-vu" v-loading="step2Loading">
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
            <div v-if="!step2Loading && !filteredComboOptions.length" class="service-card-empty">
              {{ emptyComboMessage }}
            </div>
          </div>

          <div class="selected-table-wrap">
            <div class="selected-table-title">Danh sách combo đã chọn</div>
            <CustomTable v-if="selectedCombos.length" :data="selectedCombos" stripe style="width: 100%">
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
            <div v-if="!step2Loading && !filteredDichVuLeOptions.length" class="service-card-empty">
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

    <div v-show="activeStep > 1" class="step-panel step-panel--placeholder">
      <p>Nội dung bước "{{ steps[activeStep]?.title }}" sẽ được bổ sung ở bước tiếp theo.</p>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Đóng</CustomButton>
        <CustomButton v-if="activeStep > 0" @click="activeStep -= 1">Quay lại</CustomButton>
        <CustomButton
          v-if="activeStep === 0"
          type="primary"
          plain
          :loading="saving"
          @click="saveStep1(false)"
        >
          Lưu
        </CustomButton>
        <CustomButton
          v-if="activeStep < steps.length - 1"
          type="primary"
          :loading="saving"
          @click="onNext"
        >
          Tiếp tục
        </CustomButton>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { ArrowDown, Check, Search } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { fetchDichVuDanhSachDichNhomDichVu } from '@/api/dichVuDanhSachDichNhomDichVu'
import { fetchDichVuDanhSachDichVuLe } from '@/api/dichVuDanhSachDichVuLe'
import { updateHopDongSuDungDichVu } from '@/api/hopDongSuDungDichVu'
import { fetchLoaiHopDong } from '@/api/loaiHopDong'
import { fetchUsers } from '@/api/users'
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

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  hopDong: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved', 'closed'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

/** xl/lg: 6/hàng · md: 3/hàng · sm/xs (mobile): 2/hàng */
const fieldColProps = {
  xs: 12,
  sm: 12,
  md: 8,
  lg: 4,
  xl: 4,
}

const wideFieldColProps = {
  xs: 24,
  sm: 24,
  md: 16,
  lg: 12,
  xl: 12,
}

const steps = [
  { key: 'thong-tin-chung', title: 'Thông tin chung', description: 'Mã HĐ & sale' },
  { key: 'dich-vu', title: 'Dịch vụ', description: 'Combo & dịch vụ lẻ' },
  { key: 'thanh-toan', title: 'Thanh toán', description: 'Sắp có' },
]

const kenhTiepCanOptions = [
  'Facebook',
  'Zalo',
  'TikTok',
  'Instagram',
  'Hotline',
  'Website',
  'Giới thiệu',
  'Walk-in',
  'Khác',
]

const activeStep = ref(0)
const saving = ref(false)
const step1FormRef = ref(null)
const loaiHopDongOptions = ref([])
const userOptions = ref([])
const optionsLoaded = ref(false)

const step2Loading = ref(false)
const comboOptions = ref([])
const dichVuLeOptions = ref([])
const selectedCombos = ref([])
const selectedDichVuLes = ref([])
const comboExpanded = ref(true)
const dichVuLeExpanded = ref(true)
const step2LoadedForLoaiId = ref(null)

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

const form = reactive({
  id: null,
  ma_hop_dong: '',
  loai_hop_dong_id: null,
  ten_khach_hang: '',
  sdt_khach_hang: '',
  dia_chi: '',
  kenh_tiep_can: '',
  thong_tin_hop_dong: {},
  nguoi_tham_gia_ids: [],
  ghi_chu_sale: '',
})

const step1Rules = {
  loai_hop_dong_id: [{ required: true, message: 'Vui lòng chọn loại hợp đồng', trigger: 'change' }],
  ten_khach_hang: [{ required: true, message: 'Vui lòng nhập tên khách hàng', trigger: 'blur' }],
  sdt_khach_hang: [{ required: true, message: 'Vui lòng nhập SĐT khách hàng', trigger: 'blur' }],
}

const dialogTitle = computed(() => {
  const ma = form.ma_hop_dong || props.hopDong?.ma_hop_dong
  return ma ? `Hợp đồng ${ma}` : 'Thêm hợp đồng sử dụng dịch vụ'
})

const selectedLoaiHopDong = computed(() => {
  if (!form.loai_hop_dong_id) return null
  return loaiHopDongOptions.value.find((item) => item.id === form.loai_hop_dong_id) || null
})

const dynamicFields = computed(() => {
  const truong = selectedLoaiHopDong.value?.noi_dung?.truong
  if (!Array.isArray(truong)) return []
  return truong.filter((item) => item?.key && item?.ten_truong)
})

const emptyComboMessage = computed(() => {
  if (!form.loai_hop_dong_id) return 'Vui lòng chọn loại hợp đồng ở bước 1 để tải combo.'
  if (!comboOptions.value.length) return 'Không có combo phù hợp với loại hợp đồng này.'
  return 'Không tìm thấy combo khớp bộ lọc.'
})

const emptyDichVuLeMessage = computed(() => {
  if (!form.loai_hop_dong_id) return 'Vui lòng chọn loại hợp đồng ở bước 1 để tải dịch vụ lẻ.'
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

function filterServiceOptions(items, filter, getName) {
  const keyword = String(filter.keyword || '').trim().toLowerCase()
  const giaTu = filter.gia_tu === null || filter.gia_tu === '' ? null : Number(filter.gia_tu)
  const giaDen = filter.gia_den === null || filter.gia_den === '' ? null : Number(filter.gia_den)

  return items.filter((item) => {
    if (keyword) {
      const name = String(getName(item) || '').toLowerCase()
      if (!name.includes(keyword)) return false
    }

    const price = displayPrice(item)
    if (Number.isFinite(giaTu) && price < giaTu) return false
    if (Number.isFinite(giaDen) && price > giaDen) return false
    return true
  })
}

function defaultValueForKieu(kieu) {
  if (kieu === 'checkbox_group') return []
  if (kieu === 'checkbox' || kieu === 'switch') return false
  if (kieu === 'number' || kieu === 'money' || kieu === 'percent') return null
  return ''
}

function isTextarea(kieu) {
  return kieu === 'textarea'
}

function isMoney(kieu) {
  return kieu === 'money'
}

function isNumberLike(kieu) {
  return kieu === 'number' || kieu === 'percent'
}

function isDateLike(kieu) {
  return ['date', 'datetime', 'month', 'year'].includes(kieu)
}

function getFieldColProps(field) {
  if (isTextarea(field.kieu) || field.kieu === 'checkbox_group' || field.kieu === 'radio') {
    return wideFieldColProps
  }
  return fieldColProps
}

function textInputType(kieu) {
  if (kieu === 'email') return 'email'
  if (kieu === 'phone') return 'tel'
  if (kieu === 'url') return 'url'
  return 'text'
}

function datePickerType(kieu) {
  if (kieu === 'datetime') return 'datetime'
  if (kieu === 'month') return 'month'
  if (kieu === 'year') return 'year'
  return 'date'
}

function datePickerFormat(kieu) {
  if (kieu === 'datetime') return 'DD/MM/YYYY HH:mm'
  if (kieu === 'month') return 'MM/YYYY'
  if (kieu === 'year') return 'YYYY'
  return 'DD/MM/YYYY'
}

function datePickerValueFormat(kieu) {
  if (kieu === 'datetime') return 'YYYY-MM-DD HH:mm:ss'
  if (kieu === 'month') return 'YYYY-MM'
  if (kieu === 'year') return 'YYYY'
  return 'YYYY-MM-DD'
}

function getDynamicFieldRules(field) {
  if (!field.bat_buoc) return []
  return [
    {
      required: true,
      message: `Vui lòng nhập ${field.ten_truong.toLowerCase()}`,
      trigger: ['blur', 'change'],
      validator: (_rule, value, callback) => {
        if (field.kieu === 'checkbox_group') {
          if (!Array.isArray(value) || value.length === 0) {
            callback(new Error(`Vui lòng chọn ${field.ten_truong.toLowerCase()}`))
            return
          }
        } else if (field.kieu === 'checkbox' || field.kieu === 'switch') {
          // optional boolean — required only means must be present
        } else if (value === null || value === undefined || value === '') {
          callback(new Error(`Vui lòng nhập ${field.ten_truong.toLowerCase()}`))
          return
        }
        callback()
      },
    },
  ]
}

function buildThongTinHopDong(fields, existing = {}) {
  const next = {}
  for (const field of fields) {
    if (Object.prototype.hasOwnProperty.call(existing, field.key)) {
      next[field.key] = existing[field.key]
    } else {
      next[field.key] = defaultValueForKieu(field.kieu)
    }
  }
  return next
}

function syncDynamicFields(preserveExisting = true) {
  const existing = preserveExisting ? { ...(form.thong_tin_hop_dong || {}) } : {}
  form.thong_tin_hop_dong = buildThongTinHopDong(dynamicFields.value, existing)
}

function onLoaiHopDongChange() {
  syncDynamicFields(false)
  selectedCombos.value = []
  selectedDichVuLes.value = []
  step2LoadedForLoaiId.value = null
  resetServiceFilters()
}

function syncFormFromHopDong(hopDong) {
  if (!hopDong) return
  form.id = hopDong.id ?? null
  form.ma_hop_dong = hopDong.ma_hop_dong || ''
  form.loai_hop_dong_id = hopDong.loai_hop_dong_id ?? null
  form.ten_khach_hang = hopDong.ten_khach_hang || ''
  form.sdt_khach_hang = hopDong.sdt_khach_hang || ''
  form.dia_chi = hopDong.dia_chi || ''
  form.kenh_tiep_can = hopDong.kenh_tiep_can || ''
  form.nguoi_tham_gia_ids = Array.isArray(hopDong.nguoi_tham_gia_ids)
    ? [...hopDong.nguoi_tham_gia_ids]
    : []
  form.ghi_chu_sale = hopDong.ghi_chu_sale || ''
  form.thong_tin_hop_dong =
    hopDong.thong_tin_hop_dong && typeof hopDong.thong_tin_hop_dong === 'object'
      ? { ...hopDong.thong_tin_hop_dong }
      : {}
  syncDynamicFields(true)
}

async function loadOptions() {
  if (optionsLoaded.value) return
  try {
    const [loaiRes, userRes] = await Promise.all([
      fetchLoaiHopDong({ per_page: 100, trang_thai: 'hoat_dong' }),
      fetchUsers({ per_page: 100, status: 'active' }),
    ])
    loaiHopDongOptions.value = loaiRes.data.data || []
    userOptions.value = userRes.data.data || []
    optionsLoaded.value = true
  } catch {
    loaiHopDongOptions.value = []
    userOptions.value = []
  }
}

async function loadStep2Options(force = false) {
  const loaiId = form.loai_hop_dong_id
  if (!loaiId) {
    comboOptions.value = []
    dichVuLeOptions.value = []
    step2LoadedForLoaiId.value = null
    return
  }
  if (!force && step2LoadedForLoaiId.value === loaiId) return

  step2Loading.value = true
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
    step2LoadedForLoaiId.value = loaiId

    const validComboIds = new Set(comboOptions.value.map((item) => item.id))
    selectedCombos.value = selectedCombos.value.filter((row) => validComboIds.has(row.id))
    const validDichVuIds = new Set(dichVuLeOptions.value.map((item) => item.id))
    selectedDichVuLes.value = selectedDichVuLes.value.filter((row) => validDichVuIds.has(row.id))
  } catch {
    comboOptions.value = []
    dichVuLeOptions.value = []
    step2LoadedForLoaiId.value = null
  } finally {
    step2Loading.value = false
  }
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

function resetStep2Selection() {
  selectedCombos.value = []
  selectedDichVuLes.value = []
  comboExpanded.value = true
  dichVuLeExpanded.value = true
  comboOptions.value = []
  dichVuLeOptions.value = []
  step2LoadedForLoaiId.value = null
  resetServiceFilters()
}

function goToStep(index) {
  if (index <= activeStep.value) {
    activeStep.value = index
  }
}

function buildThongTinHopDongPayload() {
  const source = form.thong_tin_hop_dong || {}
  const payload = {}
  for (const field of dynamicFields.value) {
    const value = source[field.key]
    payload[field.key] = value === undefined ? defaultValueForKieu(field.kieu) : value
  }
  return payload
}

async function saveStep1(silent = false) {
  const valid = await step1FormRef.value?.validate().catch(() => false)
  if (!valid) return false
  if (!form.id) {
    ElMessage.error('Không tìm thấy hợp đồng để cập nhật.')
    return false
  }

  saving.value = true
  try {
    const thongTinHopDong = buildThongTinHopDongPayload()
    const { data } = await updateHopDongSuDungDichVu(form.id, {
      loai_hop_dong_id: form.loai_hop_dong_id,
      ten_khach_hang: form.ten_khach_hang?.trim() || null,
      sdt_khach_hang: form.sdt_khach_hang?.trim() || null,
      dia_chi: form.dia_chi?.trim() || null,
      kenh_tiep_can: form.kenh_tiep_can?.trim() || null,
      thong_tin_hop_dong: thongTinHopDong,
      nguoi_tham_gia_ids: form.nguoi_tham_gia_ids || [],
      ghi_chu_sale: form.ghi_chu_sale?.trim() || null,
      trang_thai: 'nhap',
    })
    syncFormFromHopDong(data)
    emit('saved', data)
    if (!silent) ElMessage.success('Đã lưu thông tin chung.')
    return true
  } catch {
    return false
  } finally {
    saving.value = false
  }
}

async function onNext() {
  // Bước 1: lưu dynamicFields vào thong_tin_hop_dong trước khi sang bước 2
  if (activeStep.value === 0) {
    const ok = await saveStep1(true)
    if (!ok) return
    await loadStep2Options()
  }
  if (activeStep.value < steps.length - 1) {
    activeStep.value += 1
  }
}

function onClosed() {
  activeStep.value = 0
  resetStep2Selection()
  emit('closed')
}

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return
    activeStep.value = 0
    resetStep2Selection()
    syncFormFromHopDong(props.hopDong)
    await loadOptions()
    syncDynamicFields(true)
  },
)

watch(
  () => props.hopDong,
  (hopDong) => {
    if (props.modelValue) syncFormFromHopDong(hopDong)
  },
)

watch(dynamicFields, () => {
  if (props.modelValue) syncDynamicFields(true)
})

watch(
  () => form.loai_hop_dong_id,
  () => {
    if (!props.modelValue) return
    step2LoadedForLoaiId.value = null
    if (activeStep.value >= 1) {
      loadStep2Options(true)
    }
  },
)

watch(activeStep, (step) => {
  if (step === 1) loadStep2Options()
})
</script>

<style scoped lang="scss">
.steps-wrap {
  margin-bottom: 28px;
  padding: 8px 8px 20px;
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.sddv-steps {
  :deep(.el-step__title) {
    font-size: 14px;
    font-weight: 500;
    line-height: 1.35;
    transition: color 0.25s ease, transform 0.25s ease;
  }

  :deep(.el-step__head) {
    .el-step__icon {
      width: 36px;
      height: 36px;
      font-size: 15px;
      font-weight: 600;
      border-width: 2px;
      transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }
  }

  :deep(.el-step__line) {
    top: 18px;
    background-color: var(--el-border-color-lighter);
  }

  :deep(.el-step.is-success) {
    .el-step__head {
      color: var(--el-color-success);
      border-color: var(--el-color-success);
    }

    .el-step__icon {
      background: var(--el-color-success);
      border-color: var(--el-color-success);
      color: #fff;
      box-shadow: 0 4px 12px color-mix(in srgb, var(--el-color-success) 35%, transparent);
    }

    .el-step__line {
      background-color: var(--el-color-success-light-5);
    }

    .el-step__title {
      color: var(--el-color-success);
      font-weight: 600;
    }
  }

  :deep(.el-step.is-process) {
    .el-step__head {
      color: var(--el-color-primary);
      border-color: var(--el-color-primary);
    }

    .el-step__icon {
      position: relative;
      z-index: 1;
      background: var(--el-color-primary);
      border-color: var(--el-color-primary);
      color: #fff;
      transform: scale(1.08);
      box-shadow: 0 6px 16px color-mix(in srgb, var(--el-color-primary) 35%, transparent);

      &::before,
      &::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 2px solid var(--el-color-primary);
        opacity: 0.65;
        animation: sddv-step-wave 2s ease-out infinite;
        pointer-events: none;
      }

      &::after {
        animation-delay: 1s;
      }
    }

    .el-step__title {
      color: var(--el-color-primary);
      font-weight: 700;
      transform: translateY(1px);
    }
  }

  :deep(.el-step.is-wait) {
    .el-step__icon {
      background: var(--el-fill-color-blank);
      border-color: var(--el-border-color);
      color: var(--el-text-color-secondary);
    }

    .el-step__title {
      color: var(--el-text-color-secondary);
    }
  }

  :deep(.el-step.is-clickable) {
    cursor: pointer;

    &:hover .el-step__title {
      color: var(--el-color-primary);
    }
  }
}

@keyframes sddv-step-wave {
  0% {
    transform: scale(1);
    opacity: 0.55;
  }
  70% {
    opacity: 0.15;
  }
  100% {
    transform: scale(2.15);
    opacity: 0;
  }
}

.step-panel {
  min-height: 220px;
}

.step-panel--dich-vu {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding-bottom: 8px;
}

.step-panel--placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--el-text-color-secondary);
  font-size: 14px;
  text-align: center;
  padding: 48px 16px;
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
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px;
  max-height: 320px;
  overflow-y: auto;
  padding: 2px;
}

.service-card {
  position: relative;
  display: flex;
  align-items: stretch;
  width: 100%;
  min-height: 88px;
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

.footer-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 8px;
}

@media (max-width: 1200px) {
  .service-card-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .service-card-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    max-height: 280px;
  }
}
</style>
