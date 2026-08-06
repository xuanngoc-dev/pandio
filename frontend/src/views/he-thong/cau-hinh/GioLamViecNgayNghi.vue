<template>
  <ConfigSettingPage title="Kỳ nghỉ & ngày lễ">
    <el-tabs v-model="activeTab" class="gio-nghi-tabs">
      <!-- Tab giờ làm việc (ẩn tạm) -->
      <el-tab-pane v-if="showGioLamTab" label="Giờ làm việc" name="gio-lam">
        <div class="tab-panel page-list">
          <CustomCard shadow="hover" class="filter-card">
            <CustomRow :gutter="12" class="toolbar">
              <CustomCol :xs="12" :sm="12" :md="8" :lg="8">
                <CustomInput
                  v-model="gioLam.keyword"
                  placeholder="Tìm theo tên cấu hình..."
                  clearable
                  style="width: 100%"
                  @clear="onSearchGioLam"
                  @keyup.enter="onSearchGioLam"
                >
                  <template #prefix>
                    <CustomIcon><Search /></CustomIcon>
                  </template>
                </CustomInput>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="8">
                <CustomSelect
                  v-model="gioLam.suDungFilter"
                  placeholder="Sử dụng"
                  clearable
                  style="width: 100%"
                  @change="onSearchGioLam"
                >
                  <CustomOption label="Đang sử dụng" value="co" />
                  <CustomOption label="Không sử dụng" value="khong" />
                </CustomSelect>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="4">
                <CustomButton type="primary" plain @click="onSearchGioLam">
                  <CustomIcon><Search /></CustomIcon>
                  Tìm kiếm
                </CustomButton>
              </CustomCol>
            </CustomRow>
          </CustomCard>

          <CustomCard shadow="hover" class="table-card">
            <template #header>
              <div class="card-header">
                <span class="card-title">Danh sách giờ làm việc</span>
                <div class="header-actions">
                  <TableColumnConfig :settings="gioLamColumnSettings" />
                  <CustomTooltip content="Thêm mới" placement="top">
                    <CustomButton type="primary" @click="openCreateGioLam">
                      <CustomIcon><Plus /></CustomIcon>
                      Thêm
                    </CustomButton>
                  </CustomTooltip>
                </div>
              </div>
            </template>

            <el-table
              v-loading="gioLam.loading"
              :data="gioLam.items"
              border
              stripe
              style="width: 100%"
            >
              <el-table-column
                v-if="gioLamColumnSettings.isColumnVisible('ten_cau_hinh')"
                prop="ten_cau_hinh"
                label="Tên cấu hình"
                min-width="180"
                show-overflow-tooltip
              />
              <el-table-column
                v-if="gioLamColumnSettings.isColumnVisible('gio_vao_buoi_sang')"
                prop="gio_vao_buoi_sang"
                label="Giờ vào buổi sáng"
                min-width="150"
                align="center"
              >
                <template #default="{ row }">
                  {{ formatTime(row.gio_vao_buoi_sang) }}
                </template>
              </el-table-column>
              <el-table-column
                v-if="gioLamColumnSettings.isColumnVisible('gio_tan_buoi_sang')"
                prop="gio_tan_buoi_sang"
                label="Giờ tan buổi sáng"
                min-width="150"
                align="center"
              >
                <template #default="{ row }">
                  {{ formatTime(row.gio_tan_buoi_sang) }}
                </template>
              </el-table-column>
              <el-table-column
                v-if="gioLamColumnSettings.isColumnVisible('gio_vao_buoi_chieu')"
                prop="gio_vao_buoi_chieu"
                label="Giờ vào buổi chiều"
                min-width="150"
                align="center"
              >
                <template #default="{ row }">
                  {{ formatTime(row.gio_vao_buoi_chieu) }}
                </template>
              </el-table-column>
              <el-table-column
                v-if="gioLamColumnSettings.isColumnVisible('gio_tan_buoi_chieu')"
                prop="gio_tan_buoi_chieu"
                label="Giờ tan buổi chiều"
                min-width="150"
                align="center"
              >
                <template #default="{ row }">
                  {{ formatTime(row.gio_tan_buoi_chieu) }}
                </template>
              </el-table-column>
              <el-table-column
                v-if="gioLamColumnSettings.isColumnVisible('su_dung')"
                prop="su_dung"
                label="Sử dụng"
                min-width="180"
                align="center"
              >
                <template #default="{ row }">
                  <div class="status-cell">
                    <el-switch
                      :model-value="row.su_dung"
                      active-value="co"
                      inactive-value="khong"
                      :loading="gioLam.togglingId === row.id"
                      :disabled="gioLam.togglingId === row.id || row.su_dung === 'co'"
                      @change="(val) => toggleSuDung(row, val)"
                    />
                    <span
                      class="status-label"
                      :class="row.su_dung === 'co' ? 'is-active' : 'is-inactive'"
                    >
                      {{ row.su_dung === 'co' ? 'Đang sử dụng' : 'Không' }}
                    </span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column label="Thao tác" width="100" fixed="right" align="center">
                <template #default="{ row }">
                  <div class="action-btns">
                    <CustomTooltip content="Sửa" placement="top">
                      <CustomButton type="primary" link :icon="Edit" @click="openEditGioLam(row)" />
                    </CustomTooltip>
                    <CustomTooltip content="Xóa" placement="top">
                      <CustomButton type="danger" link :icon="Delete" @click="removeGioLam(row)" />
                    </CustomTooltip>
                  </div>
                </template>
              </el-table-column>
            </el-table>

            <Pagination
              v-model="gioLam.page"
              v-model:page-size="gioLam.perPage"
              :total="gioLam.total"
              :disabled="gioLam.loading"
              @change="loadGioLam"
            />
          </CustomCard>
        </div>
      </el-tab-pane>

      <!-- Tab kỳ nghỉ & ngày lễ -->
      <el-tab-pane label="Kỳ nghỉ & ngày lễ" name="ngay-nghi">
        <div class="tab-panel page-list">
          <CustomCard shadow="hover" class="filter-card">
            <CustomRow :gutter="12" class="toolbar">
              <CustomCol :xs="12" :sm="12" :md="8" :lg="8">
                <CustomInput
                  v-model="ngayNghi.keyword"
                  placeholder="Tìm theo tên kỳ nghỉ & ngày lễ..."
                  clearable
                  style="width: 100%"
                  @clear="onSearchNgayNghi"
                  @keyup.enter="onSearchNgayNghi"
                >
                  <template #prefix>
                    <CustomIcon><Search /></CustomIcon>
                  </template>
                </CustomInput>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="8">
                <CustomSelect
                  v-model="ngayNghi.trangThaiFilter"
                  placeholder="Trạng thái"
                  clearable
                  style="width: 100%"
                  @change="onSearchNgayNghi"
                >
                  <CustomOption label="Đang hoạt động" value="active" />
                  <CustomOption label="Không hoạt động" value="inactive" />
                </CustomSelect>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="8" :lg="4">
                <CustomButton type="primary" plain @click="onSearchNgayNghi">
                  <CustomIcon><Search /></CustomIcon>
                  Tìm kiếm
                </CustomButton>
              </CustomCol>
            </CustomRow>
          </CustomCard>

          <CustomCard shadow="hover" class="table-card">
            <template #header>
              <div class="card-header">
                <span class="card-title">Danh sách kỳ nghỉ & ngày lễ</span>
                <div class="header-actions">
                  <TableColumnConfig :settings="ngayNghiColumnSettings" />
                  <CustomTooltip content="Thêm mới" placement="top">
                    <CustomButton type="primary" @click="openCreateNgayNghi">
                      <CustomIcon><Plus /></CustomIcon>
                      Thêm
                    </CustomButton>
                  </CustomTooltip>
                </div>
              </div>
            </template>

            <el-table
              v-loading="ngayNghi.loading"
              :data="ngayNghi.items"
              border
              stripe
              style="width: 100%"
            >
              <el-table-column
                v-if="ngayNghiColumnSettings.isColumnVisible('ten_ngay_nghi')"
                prop="ten_ngay_nghi"
                label="Tên kỳ nghỉ & ngày lễ"
                min-width="180"
                show-overflow-tooltip
              />
              <el-table-column
                v-if="ngayNghiColumnSettings.isColumnVisible('ngay_bat_dau')"
                prop="ngay_bat_dau"
                label="Ngày bắt đầu"
                min-width="140"
                align="center"
              >
                <template #default="{ row }">
                  {{ formatDate(row.ngay_bat_dau) }}
                </template>
              </el-table-column>
              <el-table-column
                v-if="ngayNghiColumnSettings.isColumnVisible('ngay_ket_thuc')"
                prop="ngay_ket_thuc"
                label="Ngày kết thúc"
                min-width="140"
                align="center"
              >
                <template #default="{ row }">
                  {{ formatDate(row.ngay_ket_thuc) }}
                </template>
              </el-table-column>
              <el-table-column
                v-if="ngayNghiColumnSettings.isColumnVisible('so_ngay_nghi')"
                label="Số ngày"
                width="130"
                align="center"
              >
                <template #default="{ row }">
                  {{ countNgayNghi(row.ngay_bat_dau, row.ngay_ket_thuc) }}
                </template>
              </el-table-column>
              <el-table-column
                v-if="ngayNghiColumnSettings.isColumnVisible('trang_thai')"
                prop="trang_thai"
                label="Trạng thái"
                min-width="200"
                align="center"
              >
                <template #default="{ row }">
                  <div class="status-cell">
                    <el-switch
                      :model-value="row.trang_thai"
                      active-value="active"
                      inactive-value="inactive"
                      :loading="ngayNghi.togglingId === row.id"
                      :disabled="ngayNghi.togglingId === row.id"
                      @change="(val) => toggleTrangThai(row, val)"
                    />
                    <span
                      class="status-label"
                      :class="row.trang_thai === 'active' ? 'is-active' : 'is-inactive'"
                    >
                      {{ row.trang_thai === 'active' ? 'Đang hoạt động' : 'Không hoạt động' }}
                    </span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column label="Thao tác" width="100" fixed="right" align="center">
                <template #default="{ row }">
                  <div class="action-btns">
                    <CustomTooltip content="Sửa" placement="top">
                      <CustomButton
                        type="primary"
                        link
                        :icon="Edit"
                        @click="openEditNgayNghi(row)"
                      />
                    </CustomTooltip>
                    <CustomTooltip content="Xóa" placement="top">
                      <CustomButton
                        type="danger"
                        link
                        :icon="Delete"
                        @click="removeNgayNghi(row)"
                      />
                    </CustomTooltip>
                  </div>
                </template>
              </el-table-column>
            </el-table>

            <Pagination
              v-model="ngayNghi.page"
              v-model:page-size="ngayNghi.perPage"
              :total="ngayNghi.total"
              :disabled="ngayNghi.loading"
              @change="loadNgayNghi"
            />
          </CustomCard>
        </div>
      </el-tab-pane>
    </el-tabs>

    <!-- Dialog giờ làm việc -->
    <CustomDialog
      v-model="gioLam.dialogVisible"
      :title="gioLam.editingId ? 'Sửa giờ làm việc' : 'Thêm giờ làm việc'"
      :width="640"
    >
      <CustomForm ref="gioLamFormRef" :model="gioLamForm" :rules="gioLamRules">
        <CustomRow :gutter="16">
          <CustomCol :span="24">
            <CustomFormItem label="Tên cấu hình" prop="ten_cau_hinh">
              <CustomInput v-model="gioLamForm.ten_cau_hinh" placeholder="VD: Giờ hành chính" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Giờ vào buổi sáng" prop="gio_vao_buoi_sang">
              <el-time-picker
                v-model="gioLamForm.gio_vao_buoi_sang"
                format="HH:mm"
                value-format="HH:mm"
                placeholder="Chọn giờ"
                style="width: 100%"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Giờ tan buổi sáng" prop="gio_tan_buoi_sang">
              <el-time-picker
                v-model="gioLamForm.gio_tan_buoi_sang"
                format="HH:mm"
                value-format="HH:mm"
                placeholder="Chọn giờ"
                style="width: 100%"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Giờ vào buổi chiều" prop="gio_vao_buoi_chieu">
              <el-time-picker
                v-model="gioLamForm.gio_vao_buoi_chieu"
                format="HH:mm"
                value-format="HH:mm"
                placeholder="Chọn giờ"
                style="width: 100%"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Giờ tan buổi chiều" prop="gio_tan_buoi_chieu">
              <el-time-picker
                v-model="gioLamForm.gio_tan_buoi_chieu"
                format="HH:mm"
                value-format="HH:mm"
                placeholder="Chọn giờ"
                style="width: 100%"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Sử dụng" prop="su_dung">
              <CustomSelect
                v-model="gioLamForm.su_dung"
                style="width: 100%"
                :disabled="isEditingActiveGioLam"
              >
                <CustomOption label="Đang sử dụng" value="co" />
                <CustomOption label="Không sử dụng" value="khong" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
      <template #footer>
        <CustomButton @click="gioLam.dialogVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="gioLam.saving" @click="saveGioLam">Lưu</CustomButton>
      </template>
    </CustomDialog>

    <!-- Dialog kỳ nghỉ & ngày lễ -->
    <CustomDialog
      v-model="ngayNghi.dialogVisible"
      :title="ngayNghi.editingId ? 'Sửa kỳ nghỉ & ngày lễ' : 'Thêm kỳ nghỉ & ngày lễ'"
      :width="640"
    >
      <CustomForm ref="ngayNghiFormRef" :model="ngayNghiForm" :rules="ngayNghiRules">
        <CustomRow :gutter="16">
          <CustomCol :span="24">
            <CustomFormItem label="Tên kỳ nghỉ & ngày lễ" prop="ten_ngay_nghi">
              <CustomInput
                v-model="ngayNghiForm.ten_ngay_nghi"
                placeholder="VD: Nghỉ lễ Quốc khánh"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Ngày bắt đầu" prop="ngay_bat_dau">
              <el-date-picker
                v-model="ngayNghiForm.ngay_bat_dau"
                type="date"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                placeholder="dd/mm/yyyy"
                style="width: 100%"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Ngày kết thúc" prop="ngay_ket_thuc">
              <el-date-picker
                v-model="ngayNghiForm.ngay_ket_thuc"
                type="date"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                placeholder="dd/mm/yyyy"
                style="width: 100%"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="ngayNghiForm.trang_thai" style="width: 100%">
                <CustomOption label="Đang hoạt động" value="active" />
                <CustomOption label="Không hoạt động" value="inactive" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
      <template #footer>
        <CustomButton @click="ngayNghi.dialogVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="ngayNghi.saving" @click="saveNgayNghi">
          Lưu
        </CustomButton>
      </template>
    </CustomDialog>
  </ConfigSettingPage>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import {
  createGioLamViec,
  deleteGioLamViec,
  fetchGioLamViec,
  updateGioLamViec,
} from '@/api/gioLamViec'
import {
  createNgayNghi,
  deleteNgayNghi,
  fetchNgayNghi,
  updateNgayNghi,
} from '@/api/ngayNghi'
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
  CustomTooltip,
} from '@/components/element'
import Pagination from '@/components/Pagination.vue'
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { useTableColumns } from '@/composables/useTableColumns'
import ConfigSettingPage from './ConfigSettingPage.vue'

const gioLamTableColumns = [
  { key: 'ten_cau_hinh', label: 'Tên cấu hình' },
  { key: 'gio_vao_buoi_sang', label: 'Giờ vào buổi sáng' },
  { key: 'gio_tan_buoi_sang', label: 'Giờ tan buổi sáng' },
  { key: 'gio_vao_buoi_chieu', label: 'Giờ vào buổi chiều' },
  { key: 'gio_tan_buoi_chieu', label: 'Giờ tan buổi chiều' },
  { key: 'su_dung', label: 'Sử dụng' },
]
const gioLamColumnSettings = useTableColumns('he-thong.gio-lam-viec', gioLamTableColumns)

const ngayNghiTableColumns = [
  { key: 'ten_ngay_nghi', label: 'Tên kỳ nghỉ & ngày lễ' },
  { key: 'ngay_bat_dau', label: 'Ngày bắt đầu' },
  { key: 'ngay_ket_thuc', label: 'Ngày kết thúc' },
  { key: 'so_ngay_nghi', label: 'Số ngày' },
  { key: 'trang_thai', label: 'Trạng thái' },
]
const ngayNghiColumnSettings = useTableColumns('he-thong.ngay-nghi', ngayNghiTableColumns)

const activeTab = ref('ngay-nghi')
const showGioLamTab = false
const loadedTabs = ref({ 'gio-lam': false, 'ngay-nghi': false })

function formatTime(value) {
  if (!value) return '—'
  return String(value).slice(0, 5)
}

/** Hiển thị ngày dạng dd/mm/yyyy (API vẫn dùng YYYY-MM-DD). */
function formatDate(value) {
  if (!value) return '—'
  const raw = String(value).slice(0, 10)
  const [y, m, d] = raw.split('-')
  if (!y || !m || !d) return raw
  return `${d}/${m}/${y}`
}

/** Số ngày nghỉ (bao gồm cả ngày bắt đầu và kết thúc). */
function countNgayNghi(start, end) {
  if (!start || !end) return '—'
  const from = new Date(`${String(start).slice(0, 10)}T00:00:00`)
  const to = new Date(`${String(end).slice(0, 10)}T00:00:00`)
  if (Number.isNaN(from.getTime()) || Number.isNaN(to.getTime()) || to < from) return '—'
  const days = Math.floor((to - from) / 86400000) + 1
  return `${days} ngày`
}

/* ========== Giờ làm việc ========== */
const gioLam = reactive({
  items: [],
  loading: false,
  saving: false,
  togglingId: null,
  page: 1,
  perPage: 10,
  total: 0,
  keyword: '',
  suDungFilter: '',
  dialogVisible: false,
  editingId: null,
})

const gioLamFormRef = ref(null)

const emptyGioLamForm = () => ({
  ten_cau_hinh: '',
  gio_vao_buoi_sang: '',
  gio_tan_buoi_sang: '',
  gio_vao_buoi_chieu: '',
  gio_tan_buoi_chieu: '',
  su_dung: 'khong',
})

const gioLamForm = reactive(emptyGioLamForm())

const isEditingActiveGioLam = computed(
  () =>
    !!gioLam.editingId &&
    gioLam.items.some((item) => item.id === gioLam.editingId && item.su_dung === 'co'),
)

const gioLamRules = {
  ten_cau_hinh: [{ required: true, message: 'Vui lòng nhập tên cấu hình', trigger: 'blur' }],
  gio_vao_buoi_sang: [{ required: true, message: 'Vui lòng chọn giờ vào sáng', trigger: 'change' }],
  gio_tan_buoi_sang: [{ required: true, message: 'Vui lòng chọn giờ tan sáng', trigger: 'change' }],
  gio_vao_buoi_chieu: [
    { required: true, message: 'Vui lòng chọn giờ vào chiều', trigger: 'change' },
  ],
  gio_tan_buoi_chieu: [
    { required: true, message: 'Vui lòng chọn giờ tan chiều', trigger: 'change' },
  ],
  su_dung: [{ required: true, message: 'Vui lòng chọn trạng thái sử dụng', trigger: 'change' }],
}

async function loadGioLam() {
  gioLam.loading = true
  try {
    const { data } = await fetchGioLamViec({
      page: gioLam.page,
      per_page: gioLam.perPage,
      keyword: gioLam.keyword.trim() || undefined,
      su_dung: gioLam.suDungFilter || undefined,
    })
    gioLam.items = data.data || []
    gioLam.total = data.total || 0
    gioLam.page = data.current_page || gioLam.page
    loadedTabs.value['gio-lam'] = true
  } catch {
    gioLam.items = []
    gioLam.total = 0
  } finally {
    gioLam.loading = false
  }
}

function onSearchGioLam() {
  gioLam.page = 1
  loadGioLam()
}

function openCreateGioLam() {
  gioLam.editingId = null
  Object.assign(gioLamForm, emptyGioLamForm())
  // Bản ghi đầu tiên luôn là đang sử dụng
  if (gioLam.total === 0) {
    gioLamForm.su_dung = 'co'
  }
  gioLam.dialogVisible = true
}

function openEditGioLam(row) {
  gioLam.editingId = row.id
  Object.assign(gioLamForm, {
    ten_cau_hinh: row.ten_cau_hinh,
    gio_vao_buoi_sang: formatTime(row.gio_vao_buoi_sang),
    gio_tan_buoi_sang: formatTime(row.gio_tan_buoi_sang),
    gio_vao_buoi_chieu: formatTime(row.gio_vao_buoi_chieu),
    gio_tan_buoi_chieu: formatTime(row.gio_tan_buoi_chieu),
    su_dung: row.su_dung || 'khong',
  })
  gioLam.dialogVisible = true
}

async function saveGioLam() {
  const valid = await gioLamFormRef.value?.validate().catch(() => false)
  if (!valid) return

  gioLam.saving = true
  const payload = {
    ten_cau_hinh: gioLamForm.ten_cau_hinh.trim(),
    gio_vao_buoi_sang: gioLamForm.gio_vao_buoi_sang,
    gio_tan_buoi_sang: gioLamForm.gio_tan_buoi_sang,
    gio_vao_buoi_chieu: gioLamForm.gio_vao_buoi_chieu,
    gio_tan_buoi_chieu: gioLamForm.gio_tan_buoi_chieu,
    su_dung: gioLamForm.su_dung,
  }

  try {
    if (gioLam.editingId) {
      await updateGioLamViec(gioLam.editingId, payload)
      ElMessage.success('Đã cập nhật giờ làm việc.')
    } else {
      await createGioLamViec(payload)
      ElMessage.success('Đã thêm giờ làm việc.')
    }
    gioLam.dialogVisible = false
    await loadGioLam()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    gioLam.saving = false
  }
}

async function toggleSuDung(row, value) {
  if (!row?.id) {
    ElMessage.error('Không xác định được bản ghi giờ làm việc.')
    return
  }

  // Chỉ cho phép bật sử dụng; không cho tắt trực tiếp (phải chọn cấu hình khác)
  if (value !== 'co') {
    ElMessage.warning('Phải có đúng 1 cấu hình đang sử dụng. Hãy bật cấu hình khác để chuyển.')
    return
  }

  const previous = row.su_dung
  gioLam.togglingId = row.id
  row.su_dung = 'co'

  try {
    await updateGioLamViec(row.id, {
      ten_cau_hinh: row.ten_cau_hinh,
      gio_vao_buoi_sang: formatTime(row.gio_vao_buoi_sang),
      gio_tan_buoi_sang: formatTime(row.gio_tan_buoi_sang),
      gio_vao_buoi_chieu: formatTime(row.gio_vao_buoi_chieu),
      gio_tan_buoi_chieu: formatTime(row.gio_tan_buoi_chieu),
      su_dung: 'co',
    })
    ElMessage.success('Đã chuyển sang cấu hình này.')
    await loadGioLam()
  } catch {
    row.su_dung = previous
  } finally {
    gioLam.togglingId = null
  }
}

async function removeGioLam(row) {
  if (row.su_dung === 'co' && gioLam.total > 1) {
    ElMessage.warning('Không thể xóa cấu hình đang sử dụng. Hãy chọn cấu hình khác trước.')
    return
  }

  await ElMessageBox.confirm(`Xóa cấu hình "${row.ten_cau_hinh}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteGioLamViec(row.id)
    ElMessage.success('Đã xóa giờ làm việc.')
    await loadGioLam()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

/* ========== Kỳ nghỉ & ngày lễ ========== */
const ngayNghi = reactive({
  items: [],
  loading: false,
  saving: false,
  togglingId: null,
  page: 1,
  perPage: 10,
  total: 0,
  keyword: '',
  trangThaiFilter: '',
  dialogVisible: false,
  editingId: null,
})

const ngayNghiFormRef = ref(null)

const emptyNgayNghiForm = () => ({
  ten_ngay_nghi: '',
  ngay_bat_dau: '',
  ngay_ket_thuc: '',
  trang_thai: 'active',
})

const ngayNghiForm = reactive(emptyNgayNghiForm())

const ngayNghiRules = {
  ten_ngay_nghi: [{ required: true, message: 'Vui lòng nhập tên kỳ nghỉ & ngày lễ', trigger: 'blur' }],
  ngay_bat_dau: [{ required: true, message: 'Vui lòng chọn ngày bắt đầu', trigger: 'change' }],
  ngay_ket_thuc: [{ required: true, message: 'Vui lòng chọn ngày kết thúc', trigger: 'change' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

async function loadNgayNghi() {
  ngayNghi.loading = true
  try {
    const { data } = await fetchNgayNghi({
      page: ngayNghi.page,
      per_page: ngayNghi.perPage,
      keyword: ngayNghi.keyword.trim() || undefined,
      trang_thai: ngayNghi.trangThaiFilter || undefined,
    })
    ngayNghi.items = data.data || []
    ngayNghi.total = data.total || 0
    ngayNghi.page = data.current_page || ngayNghi.page
    loadedTabs.value['ngay-nghi'] = true
  } catch {
    ngayNghi.items = []
    ngayNghi.total = 0
  } finally {
    ngayNghi.loading = false
  }
}

function onSearchNgayNghi() {
  ngayNghi.page = 1
  loadNgayNghi()
}

function openCreateNgayNghi() {
  ngayNghi.editingId = null
  Object.assign(ngayNghiForm, emptyNgayNghiForm())
  ngayNghi.dialogVisible = true
}

function openEditNgayNghi(row) {
  if (!row?.id) {
    ElMessage.error('Không xác định được bản ghi kỳ nghỉ & ngày lễ.')
    return
  }
  ngayNghi.editingId = row.id
  Object.assign(ngayNghiForm, {
    ten_ngay_nghi: row.ten_ngay_nghi,
    ngay_bat_dau: row.ngay_bat_dau,
    ngay_ket_thuc: row.ngay_ket_thuc,
    trang_thai: row.trang_thai || 'active',
  })
  ngayNghi.dialogVisible = true
}

async function saveNgayNghi() {
  const valid = await ngayNghiFormRef.value?.validate().catch(() => false)
  if (!valid) return

  ngayNghi.saving = true
  const payload = {
    ten_ngay_nghi: ngayNghiForm.ten_ngay_nghi.trim(),
    ngay_bat_dau: ngayNghiForm.ngay_bat_dau,
    ngay_ket_thuc: ngayNghiForm.ngay_ket_thuc,
    trang_thai: ngayNghiForm.trang_thai,
  }

  try {
    if (ngayNghi.editingId != null) {
      await updateNgayNghi(ngayNghi.editingId, payload)
      ElMessage.success('Đã cập nhật kỳ nghỉ & ngày lễ.')
    } else {
      await createNgayNghi(payload)
      ElMessage.success('Đã thêm kỳ nghỉ & ngày lễ.')
    }
    ngayNghi.dialogVisible = false
    await loadNgayNghi()
  } catch (error) {
    if (error?.message === 'ID ngày nghỉ không hợp lệ.') {
      ElMessage.error(error.message)
    }
    // Lỗi API đã được axios interceptor xử lý
  } finally {
    ngayNghi.saving = false
  }
}

async function toggleTrangThai(row, value) {
  if (!row?.id) {
    ElMessage.error('Không xác định được bản ghi kỳ nghỉ & ngày lễ.')
    return
  }

  const previous = value === 'active' ? 'inactive' : 'active'
  const nextValue = value
  ngayNghi.togglingId = row.id
  row.trang_thai = nextValue

  try {
    await updateNgayNghi(row.id, {
      ten_ngay_nghi: row.ten_ngay_nghi,
      ngay_bat_dau: row.ngay_bat_dau,
      ngay_ket_thuc: row.ngay_ket_thuc,
      trang_thai: nextValue,
    })
    ElMessage.success(
      nextValue === 'active' ? 'Đã bật kỳ nghỉ & ngày lễ.' : 'Đã tắt kỳ nghỉ & ngày lễ.',
    )
  } catch (error) {
    row.trang_thai = previous
    if (error?.message === 'ID ngày nghỉ không hợp lệ.') {
      ElMessage.error(error.message)
    }
  } finally {
    ngayNghi.togglingId = null
  }
}

async function removeNgayNghi(row) {
  if (!row?.id) {
    ElMessage.error('Không xác định được bản ghi kỳ nghỉ & ngày lễ.')
    return
  }

  await ElMessageBox.confirm(`Xóa kỳ nghỉ & ngày lễ "${row.ten_ngay_nghi}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })

  try {
    await deleteNgayNghi(row.id)
    ElMessage.success('Đã xóa kỳ nghỉ & ngày lễ.')
    await loadNgayNghi()
  } catch (error) {
    if (error?.message === 'ID ngày nghỉ không hợp lệ.') {
      ElMessage.error(error.message)
    }
  }
}

watch(activeTab, (tab) => {
  if (tab === 'gio-lam' && !loadedTabs.value['gio-lam']) loadGioLam()
  if (tab === 'ngay-nghi' && !loadedTabs.value['ngay-nghi']) loadNgayNghi()
})

onMounted(loadNgayNghi)
</script>

<style scoped>
.gio-nghi-tabs :deep(.el-tabs__header) {
  margin-bottom: 16px;
}

.header-actions {
  display: inline-flex;
  align-items: center;
  gap: 12px;
}

.status-label {
  line-height: 1.2;
  white-space: nowrap;
}
</style>
