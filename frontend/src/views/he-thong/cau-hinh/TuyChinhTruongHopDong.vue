<template>
  <ConfigSettingPage title="Cấu hình thông tin hợp đồng">
    <div class="loai-hop-dong">
      <CustomCard shadow="hover" class="filter-card">
        <div class="toolbar">
          <CustomInput
            v-model="keyword"
            placeholder="Tìm theo tên, mã hợp đồng..."
            clearable
            style="max-width: 300px"
            @clear="onSearch"
            @keyup.enter="onSearch"
          >
            <template #prefix>
              <CustomIcon><Search /></CustomIcon>
            </template>
          </CustomInput>
          <CustomSelect
            v-model="trangThaiFilter"
            placeholder="Trạng thái"
            clearable
            style="width: 180px"
            @change="onSearch"
          >
            <CustomOption label="Đang hoạt động" value="hoat_dong" />
            <CustomOption label="Ngừng hoạt động" value="ngung_hoat_dong" />
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
            <span class="card-title">Danh sách loại hợp đồng khách hàng</span>
            <BulkActionBar :actions="bulkActions" @action="onBulkAction">
              <TableColumnConfig :settings="columnSettings" />
              <CustomTooltip content="Thêm mới" placement="top">
                <CustomButton type="primary" @click="openCreate">
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
            prop="ma_hop_dong"
            label="Mã"
            width="140"
          />
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('ten_hop_dong')"
            prop="ten_hop_dong"
            label="Tên hợp đồng"
            min-width="200"
            show-overflow-tooltip
          />
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('so_truong')"
            label="Số thông tin thông tin"
            align="center"
          >
            <template #default="{ row }">
              {{ countFields(row.noi_dung) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('so_dieu_phoi')"
            label="Số thông tin điều phối"
            align="center"
          >
            <template #default="{ row }">
              {{ countDieuPhoi(row.thong_tin_dieu_phoi) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="columnSettings.isColumnVisible('trang_thai')"
            prop="trang_thai"
            label="Trạng thái"
            width="190"
            align="center"
          >
            <template #default="{ row }">
              <div class="status-cell">
                <el-switch
                  :model-value="row.trang_thai"
                  active-value="hoat_dong"
                  inactive-value="ngung_hoat_dong"
                  :loading="togglingId === row.id"
                  :disabled="togglingId === row.id"
                  :before-change="() => toggleStatus(row)"
                />
                <span
                  class="status-label"
                  :class="row.trang_thai === 'hoat_dong' ? 'is-active' : 'is-inactive'"
                >
                  {{ row.trang_thai === 'hoat_dong' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}
                </span>
              </div>
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
        :title="editingId ? 'Sửa thông tin hợp đồng' : 'Thêm thông tin hợp đồng'"
        :width="1400"
      >
        <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
          <CustomRow :gutter="16">
            <CustomCol :xs="24" :sm="8">
              <CustomFormItem label="Mã hợp đồng" prop="ma_hop_dong">
                <CustomInput
                  v-model="form.ma_hop_dong"
                  :disabled="!!editingId"
                  placeholder="VD: SDDV"
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="8">
              <CustomFormItem label="Tên hợp đồng" prop="ten_hop_dong">
                <CustomInput
                  v-model="form.ten_hop_dong"
                  placeholder="VD: Hợp đồng SDDV"
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="8">
              <CustomFormItem label="Trạng thái" prop="trang_thai">
                <CustomSelect v-model="form.trang_thai" style="width: 100%">
                  <CustomOption label="Đang hoạt động" value="hoat_dong" />
                  <CustomOption label="Ngừng hoạt động" value="ngung_hoat_dong" />
                </CustomSelect>
              </CustomFormItem>
            </CustomCol>
          </CustomRow>

          <!-- Nội dung trường hợp đồng -->
          <div class="section-block">
            <div class="section-header" @click="fieldsExpanded = !fieldsExpanded">
              <div class="section-header__left">
                <CustomIcon class="section-chevron" :class="{ 'is-expanded': fieldsExpanded }">
                  <ArrowRight />
                </CustomIcon>
                <span class="fields-title">Nội dung trường hợp đồng</span>
                <span v-if="form.truong.length" class="section-count">
                  ({{ form.truong.length }})
                </span>
              </div>
              <CustomButton
                v-if="form.truong.length && fieldsExpanded"
                type="primary"
                plain
                @click.stop="addField"
              >
                <CustomIcon><Plus /></CustomIcon>
                Thêm trường
              </CustomButton>
            </div>

            <div v-show="fieldsExpanded" class="section-body">
              <div v-if="!form.truong.length" class="fields-empty">
                <p>Chưa có trường nào.</p>
                <CustomButton type="primary" @click="addField">
                  <CustomIcon><Plus /></CustomIcon>
                  Thêm trường
                </CustomButton>
              </div>

              <div
                v-for="(item, index) in form.truong"
                :key="item._key"
                class="field-card"
              >
                <CustomRow :gutter="12">
                  <CustomCol :xs="24" :sm="6">
                    <CustomFormItem
                      label="Tên trường"
                      :prop="`truong.${index}.ten_truong`"
                      :rules="fieldRules.ten_truong"
                    >
                      <CustomInput
                        v-model="item.ten_truong"
                        placeholder="VD: Tên chú rể"
                        @input="onFieldNameInput(item)"
                      />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="24" :sm="6">
                    <CustomFormItem
                      label="Key"
                      :prop="`truong.${index}.key`"
                      :rules="getFieldKeyRules(index)"
                    >
                      <CustomInput
                        v-model="item.key"
                        placeholder="VD: tenChuRe"
                        @input="onFieldKeyInput(index)"
                      />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="24" :sm="6">
                    <CustomFormItem
                      label="Kiểu dữ liệu"
                      :prop="`truong.${index}.kieu`"
                      :rules="fieldRules.kieu"
                    >
                      <CustomSelect
                        v-model="item.kieu"
                        placeholder="Chọn kiểu"
                        style="width: 100%"
                        @change="(val) => onKieuChange(item, val)"
                      >
                        <template v-for="group in getKieuOptionGroups(item.kieu)" :key="group.label">
                          <el-option-group :label="group.label">
                            <CustomOption
                              v-for="opt in group.options"
                              :key="opt.value"
                              :label="opt.label"
                              :value="opt.value"
                            />
                          </el-option-group>
                        </template>
                      </CustomSelect>
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="24" :sm="6">
                    <CustomFormItem label="Bắt buộc">
                      <div class="field-required-row">
                        <el-switch
                          v-model="item.bat_buoc"
                          inline-prompt
                          active-text="Có"
                          inactive-text="Không"
                        />
                        <CustomButton type="danger" link :icon="Delete" @click="removeField(index)">
                          Xóa
                        </CustomButton>
                      </div>
                    </CustomFormItem>
                  </CustomCol>

                  <CustomCol v-if="hasFieldOptions(item.kieu)" :span="24">
                    <div class="options-block">
                      <div class="options-block__header">
                        <span class="options-block__title">Tùy chọn (options)</span>
                        <CustomButton link type="primary" @click="addOption(item)">
                          <CustomIcon><Plus /></CustomIcon>
                          Thêm option
                        </CustomButton>
                      </div>

                      <div v-if="!item.options.length" class="options-empty">
                        Chưa có option. Nhấn "Thêm option" để thêm.
                      </div>

                      <div
                        v-for="(opt, optIndex) in item.options"
                        :key="opt._key"
                        class="option-row"
                      >
                        <CustomFormItem
                          class="option-row__item"
                          label="Nhãn"
                          :prop="`truong.${index}.options.${optIndex}.label`"
                          :rules="fieldRules.optionLabel"
                        >
                          <CustomInput v-model="opt.label" placeholder="VD: Nam" />
                        </CustomFormItem>
                        <CustomFormItem
                          class="option-row__item"
                          label="Giá trị"
                          :prop="`truong.${index}.options.${optIndex}.value`"
                          :rules="fieldRules.optionValue"
                        >
                          <CustomInput v-model="opt.value" placeholder="VD: nam" />
                        </CustomFormItem>
                        <CustomButton
                          type="danger"
                          link
                          :icon="Delete"
                          class="option-row__remove"
                          @click="removeOption(item, optIndex)"
                        />
                      </div>
                    </div>
                  </CustomCol>
                </CustomRow>
              </div>
            </div>
          </div>

          <!-- Thông tin điều phối -->
          <div class="section-block">
            <div class="section-header" @click="dieuPhoiExpanded = !dieuPhoiExpanded">
              <div class="section-header__left">
                <CustomIcon class="section-chevron" :class="{ 'is-expanded': dieuPhoiExpanded }">
                  <ArrowRight />
                </CustomIcon>
                <span class="fields-title">Thông tin điều phối</span>
                <span v-if="form.dieu_phoi.length" class="section-count">
                  ({{ form.dieu_phoi.length }})
                </span>
              </div>
              <CustomButton
                v-if="form.dieu_phoi.length && dieuPhoiExpanded"
                type="primary"
                plain
                @click.stop="addDieuPhoi"
              >
                <CustomIcon><Plus /></CustomIcon>
                Thêm thông tin
              </CustomButton>
            </div>

            <div v-show="dieuPhoiExpanded" class="section-body">
              <div v-if="!form.dieu_phoi.length" class="fields-empty">
                <p>Chưa có thông tin điều phối nào.</p>
                <CustomButton type="primary" @click="addDieuPhoi">
                  <CustomIcon><Plus /></CustomIcon>
                  Thêm thông tin
                </CustomButton>
              </div>

              <div
                v-for="(item, index) in form.dieu_phoi"
                :key="item._key"
                class="field-card"
              >
                <CustomRow :gutter="12">
                  <CustomCol :xs="24" :sm="5">
                    <CustomFormItem
                      label="Tên (name)"
                      :prop="`dieu_phoi.${index}.name`"
                      :rules="dieuPhoiRules.name"
                    >
                      <CustomInput
                        v-model="item.name"
                        placeholder="VD: Ngày cưới chính thức"
                        @input="onDieuPhoiNameInput(item)"
                      />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="24" :sm="4">
                    <CustomFormItem
                      label="Key"
                      :prop="`dieu_phoi.${index}.key`"
                      :rules="getDieuPhoiKeyRules(index)"
                    >
                      <CustomInput
                        v-model="item.key"
                        placeholder="VD: ngayCuoiChinhThuc"
                        @input="onDieuPhoiKeyInput(index)"
                      />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="24" :sm="5">
                    <CustomFormItem
                      label="Giá trị mặc định (value)"
                      :prop="`dieu_phoi.${index}.value`"
                    >
                      <CustomInput
                        v-model="item.value"
                        placeholder="Giá trị mặc định"
                      />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="24" :sm="5">
                    <CustomFormItem
                      label="Ghi chú (note)"
                      :prop="`dieu_phoi.${index}.note`"
                    >
                      <CustomInput
                        v-model="item.note"
                        placeholder="Ghi chú (tuỳ chọn)"
                      />
                    </CustomFormItem>
                  </CustomCol>
                  <CustomCol :xs="24" :sm="5">
                    <CustomFormItem label="Bắt buộc (required)">
                      <div class="field-required-row">
                        <el-switch
                          v-model="item.required"
                          inline-prompt
                          active-text="Có"
                          inactive-text="Không"
                        />
                        <CustomButton
                          type="danger"
                          link
                          :icon="Delete"
                          @click="removeDieuPhoi(index)"
                        >
                          Xóa
                        </CustomButton>
                      </div>
                    </CustomFormItem>
                  </CustomCol>
                </CustomRow>
              </div>
            </div>
          </div>
        </CustomForm>

        <template #footer>
          <CustomButton @click="dialogVisible = false">Hủy</CustomButton>
          <CustomButton type="primary" :loading="saving" @click="save">Lưu</CustomButton>
        </template>
      </CustomDialog>
    </div>
  </ConfigSettingPage>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search, ArrowRight } from '@element-plus/icons-vue'
import {
  createLoaiHopDong,
  deleteLoaiHopDong,
  fetchLoaiHopDong,
  updateLoaiHopDong,
} from '@/api/loaiHopDong'
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
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'
import ConfigSettingPage from './ConfigSettingPage.vue'

const ACTIVE = 'hoat_dong'
const INACTIVE = 'ngung_hoat_dong'

const KIEU_CO_TUY_CHON = ['select', 'radio', 'checkbox_group']
const KIEU_LEGACY = [
  { label: 'Tệp đính kèm (cũ)', value: 'file' },
  { label: 'Hình ảnh (cũ)', value: 'image' },
]

const kieuDuLieuOptionGroups = [
  {
    label: 'Văn bản',
    options: [
      { label: 'Văn bản ngắn', value: 'input' },
      { label: 'Văn bản dài', value: 'textarea' },
      { label: 'Email', value: 'email' },
      { label: 'Số điện thoại', value: 'phone' },
      { label: 'Liên kết (URL)', value: 'url' },
    ],
  },
  {
    label: 'Số liệu',
    options: [
      { label: 'Số', value: 'number' },
      { label: 'Tiền tệ', value: 'money' },
      { label: 'Phần trăm', value: 'percent' },
    ],
  },
  {
    label: 'Lựa chọn',
    options: [
      { label: 'Danh sách chọn', value: 'select' },
      { label: 'Lựa chọn đơn (radio)', value: 'radio' },
      { label: 'Checkbox', value: 'checkbox' },
      { label: 'Nhiều lựa chọn', value: 'checkbox_group' },
      { label: 'Bật/Tắt', value: 'switch' },
    ],
  },
  {
    label: 'Thời gian',
    options: [
      { label: 'Ngày', value: 'date' },
      { label: 'Ngày giờ', value: 'datetime' },
      { label: 'Giờ', value: 'time' },
      { label: 'Tháng', value: 'month' },
      { label: 'Năm', value: 'year' },
    ],
  },
]

function getKieuOptionGroups(currentKieu) {
  if (!KIEU_LEGACY.some((opt) => opt.value === currentKieu)) {
    return kieuDuLieuOptionGroups
  }

  return [
    ...kieuDuLieuOptionGroups,
    {
      label: 'Tệp tin (dữ liệu cũ)',
      options: KIEU_LEGACY,
    },
  ]
}

function hasFieldOptions(kieu) {
  return KIEU_CO_TUY_CHON.includes(kieu)
}

let fieldKeySeed = 0
let optionKeySeed = 0
let dieuPhoiKeySeed = 0

const tableColumns = [
  { key: 'ma_hop_dong', label: 'Mã' },
  { key: 'ten_hop_dong', label: 'Tên hợp đồng' },
  { key: 'so_truong', label: 'Số thông tin thông tin' },
  { key: 'so_dieu_phoi', label: 'Số thông tin điều phối' },
  { key: 'trang_thai', label: 'Trạng thái' },
]
const columnSettings = useTableColumns('he-thong.tuy-chinh-truong-hop-dong', tableColumns)

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const togglingId = ref(null)
const bulkActivating = ref(false)
const bulkDeactivating = ref(false)
const bulkDeleting = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const keyword = ref('')
const trangThaiFilter = ref('')

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)
const fieldsExpanded = ref(true)
const dieuPhoiExpanded = ref(true)

const { selectedCount, onSelectionChange, clearSelection, countByStatus, idsByStatus, selectedIds } =
  useBulkSelection(
    () => true,
    (row) => row.trang_thai,
  )

const bulkActions = computed(() => {
  const activeCount = countByStatus(INACTIVE)
  const inactiveCount = countByStatus(ACTIVE)
  return [
    {
      key: 'activate',
      label: 'Bật',
      type: 'success',
      badge: activeCount,
      badgeType: 'success',
      loading: bulkActivating.value,
      tooltip: activeCount
        ? `Bật ${activeCount} loại hợp đồng đang ngừng`
        : 'Chọn loại hợp đồng ngừng hoạt động để bật',
    },
    {
      key: 'deactivate',
      label: 'Tắt',
      type: 'warning',
      badge: inactiveCount,
      badgeType: 'warning',
      loading: bulkDeactivating.value,
      tooltip: inactiveCount
        ? `Tắt ${inactiveCount} loại hợp đồng đang hoạt động`
        : 'Chọn loại hợp đồng đang hoạt động để tắt',
    },
    {
      key: 'delete',
      label: 'Xóa',
      type: 'danger',
      badge: selectedCount.value,
      badgeType: 'danger',
      loading: bulkDeleting.value,
      tooltip: selectedCount.value
        ? `Xóa ${selectedCount.value} loại hợp đồng đã chọn`
        : 'Chọn loại hợp đồng để xóa',
    },
  ]
})

const emptyForm = () => ({
  ten_hop_dong: '',
  ma_hop_dong: '',
  trang_thai: ACTIVE,
  truong: [],
  dieu_phoi: [],
})

const form = reactive(emptyForm())

const rules = {
  ma_hop_dong: [{ required: true, message: 'Vui lòng nhập mã hợp đồng', trigger: 'blur' }],
  ten_hop_dong: [{ required: true, message: 'Vui lòng nhập tên hợp đồng', trigger: 'blur' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

const fieldRules = {
  ten_truong: [{ required: true, message: 'Vui lòng nhập tên trường', trigger: 'blur' }],
  kieu: [{ required: true, message: 'Vui lòng chọn kiểu dữ liệu', trigger: 'change' }],
  optionLabel: [{ required: true, message: 'Vui lòng nhập nhãn', trigger: 'blur' }],
  optionValue: [{ required: true, message: 'Vui lòng nhập giá trị', trigger: 'blur' }],
}

const dieuPhoiRules = {
  name: [{ required: true, message: 'Vui lòng nhập tên', trigger: 'blur' }],
}

function isDuplicateKey(items, currentIndex, value) {
  const normalized = (value || '').trim()
  if (!normalized) return false
  return items.some(
    (item, index) => index !== currentIndex && item.key.trim() === normalized,
  )
}

function getFieldKeyRules(index) {
  return [
    { required: true, message: 'Vui lòng nhập key', trigger: ['blur', 'change'] },
    {
      pattern: /^[a-zA-Z][a-zA-Z0-9_]*$/,
      message: 'Key chỉ gồm chữ, số, _ và bắt đầu bằng chữ',
      trigger: ['blur', 'change'],
    },
    {
      validator: (_rule, value, callback) => {
        if (isDuplicateKey(form.truong, index, value)) {
          callback(new Error('Key bị trùng'))
          return
        }
        callback()
      },
      trigger: ['blur', 'change'],
    },
  ]
}

function getDieuPhoiKeyRules(index) {
  return [
    { required: true, message: 'Vui lòng nhập key', trigger: ['blur', 'change'] },
    {
      pattern: /^[a-zA-Z][a-zA-Z0-9_]*$/,
      message: 'Key chỉ gồm chữ, số, _ và bắt đầu bằng chữ',
      trigger: ['blur', 'change'],
    },
    {
      validator: (_rule, value, callback) => {
        if (isDuplicateKey(form.dieu_phoi, index, value)) {
          callback(new Error('Key bị trùng'))
          return
        }
        callback()
      },
      trigger: ['blur', 'change'],
    },
  ]
}

function nextFieldKey() {
  fieldKeySeed += 1
  return `field-${fieldKeySeed}`
}

function nextOptionKey() {
  optionKeySeed += 1
  return `option-${optionKeySeed}`
}

function nextDieuPhoiKey() {
  dieuPhoiKeySeed += 1
  return `dieu-phoi-${dieuPhoiKeySeed}`
}

function createOption(data = {}) {
  return {
    _key: nextOptionKey(),
    label: data.label || '',
    value: data.value || '',
  }
}

function createField(data = {}) {
  return {
    _key: nextFieldKey(),
    ten_truong: data.ten_truong || '',
    key: data.key || '',
    kieu: data.kieu || 'input',
    bat_buoc: !!data.bat_buoc,
    options: Array.isArray(data.options)
      ? data.options.map((opt) => createOption(opt))
      : [],
  }
}

function createDieuPhoi(data = {}) {
  return {
    _key: nextDieuPhoiKey(),
    key: data.key || '',
    name: data.name || '',
    value: data.value ?? '',
    required: !!data.required,
    note: data.note || '',
  }
}

function parseNoiDung(noiDung) {
  const truong = Array.isArray(noiDung?.truong) ? noiDung.truong : []
  return truong.map((item) => createField(item))
}

function parseThongTinDieuPhoi(thongTin) {
  if (!thongTin || typeof thongTin !== 'object' || Array.isArray(thongTin)) return []

  return Object.entries(thongTin).map(([key, item]) =>
    createDieuPhoi({
      key,
      name: item?.name,
      value: item?.value,
      required: item?.required,
      note: item?.note,
    }),
  )
}

function buildNoiDungPayload() {
  return {
    truong: form.truong.map((item) => {
      const field = {
        ten_truong: item.ten_truong.trim(),
        key: item.key.trim(),
        kieu: item.kieu,
        bat_buoc: !!item.bat_buoc,
      }

      if (hasFieldOptions(item.kieu)) {
        field.options = item.options.map((opt) => ({
          label: opt.label.trim(),
          value: opt.value.trim(),
        }))
      }

      return field
    }),
  }
}

function buildThongTinDieuPhoiPayload() {
  const result = {}
  for (const item of form.dieu_phoi) {
    const key = item.key.trim()
    if (!key) continue
    result[key] = {
      name: item.name.trim(),
      value: typeof item.value === 'string' ? item.value.trim() : item.value ?? '',
      required: !!item.required,
      note: (item.note || '').trim(),
    }
  }
  return result
}

function countFields(noiDung) {
  return Array.isArray(noiDung?.truong) ? noiDung.truong.length : 0
}

function countDieuPhoi(thongTin) {
  if (!thongTin || typeof thongTin !== 'object' || Array.isArray(thongTin)) return 0
  return Object.keys(thongTin).length
}

function addField() {
  form.truong.push(createField())
  fieldsExpanded.value = true
}

function removeField(index) {
  form.truong.splice(index, 1)
}

function addDieuPhoi() {
  form.dieu_phoi.push(createDieuPhoi())
  dieuPhoiExpanded.value = true
}

function removeDieuPhoi(index) {
  form.dieu_phoi.splice(index, 1)
}

function addOption(field) {
  field.options.push(createOption())
}

function removeOption(field, index) {
  field.options.splice(index, 1)
}

function onKieuChange(field, kieu) {
  if (hasFieldOptions(kieu) && !field.options.length) {
    field.options.push(createOption())
  }
  if (!hasFieldOptions(kieu)) {
    field.options = []
  }
}

function removeVietnameseTones(str) {
  return String(str || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/đ/g, 'd')
    .replace(/Đ/g, 'D')
}

function toCamelCaseKey(text) {
  const words = removeVietnameseTones(text)
    .replace(/[^a-zA-Z0-9\s_-]/g, ' ')
    .trim()
    .split(/[\s_-]+/)
    .filter(Boolean)

  if (!words.length) return ''

  return words
    .map((word, index) => {
      const lower = word.toLowerCase()
      if (index === 0) return lower
      return lower.charAt(0).toUpperCase() + lower.slice(1)
    })
    .join('')
    .replace(/^[0-9]+/, '')
}

function revalidateFieldKeys() {
  nextTick(() => {
    form.truong.forEach((_, index) => {
      formRef.value?.validateField(`truong.${index}.key`, () => {})
    })
  })
}

function revalidateDieuPhoiKeys() {
  nextTick(() => {
    form.dieu_phoi.forEach((_, index) => {
      formRef.value?.validateField(`dieu_phoi.${index}.key`, () => {})
    })
  })
}

function onFieldNameInput(item) {
  item.key = toCamelCaseKey(item.ten_truong)
  revalidateFieldKeys()
}

function onFieldKeyInput() {
  revalidateFieldKeys()
}

function onDieuPhoiNameInput(item) {
  item.key = toCamelCaseKey(item.name)
  revalidateDieuPhoiKeys()
}

function onDieuPhoiKeyInput() {
  revalidateDieuPhoiKeys()
}

function validateSelectOptions() {
  for (const item of form.truong) {
    if (hasFieldOptions(item.kieu) && !item.options.length) {
      ElMessage.warning(`Trường "${item.ten_truong || item.key || 'chưa đặt tên'}" cần ít nhất 1 tùy chọn.`)
      return false
    }
  }
  return true
}

async function toggleStatus(row) {
  if (!row?.id) return false

  const value = row.trang_thai === ACTIVE ? INACTIVE : ACTIVE
  togglingId.value = row.id

  try {
    await updateLoaiHopDong(row.id, {
      ten_hop_dong: row.ten_hop_dong,
      ma_hop_dong: row.ma_hop_dong,
      noi_dung: row.noi_dung || { truong: [] },
      thong_tin_dieu_phoi: row.thong_tin_dieu_phoi || {},
      trang_thai: value,
    })
    row.trang_thai = value
    ElMessage.success(
      value === ACTIVE ? 'Đã bật loại hợp đồng.' : 'Đã ngừng loại hợp đồng.',
    )
    return true
  } catch {
    return false
  } finally {
    togglingId.value = null
  }
}

async function loadItems() {
  loading.value = true
  clearSelection()
  try {
    const { data } = await fetchLoaiHopDong({
      page: page.value,
      per_page: perPage.value,
      keyword: keyword.value.trim() || undefined,
      trang_thai: trangThaiFilter.value || undefined,
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

async function onBulkAction(key) {
  if (key === 'activate') await bulkSetStatus(ACTIVE)
  else if (key === 'deactivate') await bulkSetStatus(INACTIVE)
  else if (key === 'delete') await bulkRemove()
}

async function bulkSetStatus(target) {
  const fromStatus = target === ACTIVE ? INACTIVE : ACTIVE
  const ids = idsByStatus(fromStatus)
  if (!ids.length) return

  const label = target === ACTIVE ? 'Bật' : 'Tắt'
  await ElMessageBox.confirm(`${label} ${ids.length} loại hợp đồng đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: label,
    cancelButtonText: 'Hủy',
  })

  const loadingRef = target === ACTIVE ? bulkActivating : bulkDeactivating
  loadingRef.value = true
  try {
    const rows = items.value.filter((item) => ids.includes(item.id))
    await runBulk(ids, async (id) => {
      const row = rows.find((item) => item.id === id)
      await updateLoaiHopDong(id, {
        ten_hop_dong: row?.ten_hop_dong,
        ma_hop_dong: row?.ma_hop_dong,
        noi_dung: row?.noi_dung || { truong: [] },
        thong_tin_dieu_phoi: row?.thong_tin_dieu_phoi || {},
        trang_thai: target,
      })
    })
    ElMessage.success(`Đã ${label.toLowerCase()} ${ids.length} loại hợp đồng.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    loadingRef.value = false
  }
}

async function bulkRemove() {
  const ids = selectedIds.value
  if (!ids.length) return

  await ElMessageBox.confirm(`Xóa ${ids.length} loại hợp đồng đã chọn?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  bulkDeleting.value = true
  try {
    await runBulk(ids, (id) => deleteLoaiHopDong(id))
    ElMessage.success(`Đã xóa ${ids.length} loại hợp đồng.`)
    await loadItems()
  } catch {
    // interceptor
  } finally {
    bulkDeleting.value = false
  }
}

function onSearch() {
  page.value = 1
  loadItems()
}

function openCreate() {
  editingId.value = null
  Object.assign(form, emptyForm())
  fieldsExpanded.value = true
  dieuPhoiExpanded.value = true
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  Object.assign(form, {
    ten_hop_dong: row.ten_hop_dong,
    ma_hop_dong: row.ma_hop_dong,
    trang_thai: row.trang_thai || ACTIVE,
    truong: parseNoiDung(row.noi_dung),
    dieu_phoi: parseThongTinDieuPhoi(row.thong_tin_dieu_phoi),
  })
  fieldsExpanded.value = true
  dieuPhoiExpanded.value = true
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) {
    // Mở section nếu có lỗi key bên trong
    fieldsExpanded.value = true
    dieuPhoiExpanded.value = true
    return
  }
  if (!validateSelectOptions()) return

  saving.value = true
  const payload = {
    ten_hop_dong: form.ten_hop_dong.trim(),
    ma_hop_dong: form.ma_hop_dong.trim(),
    noi_dung: buildNoiDungPayload(),
    thong_tin_dieu_phoi: buildThongTinDieuPhoiPayload(),
    trang_thai: form.trang_thai,
  }

  try {
    if (editingId.value) {
      await updateLoaiHopDong(editingId.value, payload)
      ElMessage.success('Đã cập nhật loại hợp đồng.')
    } else {
      await createLoaiHopDong(payload)
      ElMessage.success('Đã thêm loại hợp đồng.')
    }
    dialogVisible.value = false
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    saving.value = false
  }
}

async function remove(row) {
  await ElMessageBox.confirm(
    `Xóa loại hợp đồng "${row.ten_hop_dong}" (${row.ma_hop_dong})?`,
    'Xác nhận',
    {
      type: 'warning',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
    },
  )

  try {
    await deleteLoaiHopDong(row.id)
    ElMessage.success('Đã xóa loại hợp đồng.')
    await loadItems()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

onMounted(loadItems)
</script>

<style scoped>
.loai-hop-dong {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
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

.action-btns {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.status-cell {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.status-label {
  font-size: 13px;
  line-height: 1.2;
  white-space: nowrap;
}

.status-label.is-active {
  color: var(--el-color-success);
}

.status-label.is-inactive {
  color: var(--el-text-color-secondary);
}

.fields-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 8px 0 12px;
}

.fields-title {
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.section-block {
  margin-top: 8px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  margin: 8px 0 0;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  background: var(--el-fill-color-light);
  cursor: pointer;
  user-select: none;
}

.section-header:hover {
  background: var(--el-fill-color);
}

.section-header__left {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.section-chevron {
  transition: transform 0.2s ease;
  color: var(--el-text-color-secondary);
}

.section-chevron.is-expanded {
  transform: rotate(90deg);
}

.section-count {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.section-body {
  margin-top: 12px;
}

.fields-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 28px 16px;
  border: 1px dashed var(--el-border-color);
  border-radius: 8px;
  color: var(--el-text-color-secondary);
  text-align: center;
}

.fields-empty p {
  margin: 0;
}

.field-card {
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  padding: 12px 14px 4px;
  margin-bottom: 12px;
  background: var(--el-fill-color-blank);
}

.field-required-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.options-block {
  width: 100%;
  padding: 10px 12px;
  border: 1px dashed var(--el-border-color-lighter);
  border-radius: 8px;
  margin-bottom: 8px;
  background: var(--el-fill-color-light);
}

.options-block__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.options-block__title {
  font-size: 13px;
  font-weight: 600;
  color: var(--el-text-color-regular);
}

.options-empty {
  font-size: 13px;
  color: var(--el-text-color-secondary);
  margin-bottom: 8px;
}

.option-row {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  margin-bottom: 4px;
}

.option-row__item {
  flex: 1;
  margin-bottom: 0;
}

.option-row__remove {
  margin-bottom: 18px;
}
</style>
