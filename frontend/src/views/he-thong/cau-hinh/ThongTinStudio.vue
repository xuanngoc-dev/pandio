<template>
  <ConfigSettingPage title="Thông tin studio">
    <el-tabs v-model="activeTab" class="studio-tabs">
      <!-- Tab 1: Thông tin studio + cấu trúc tổ chức -->
      <el-tab-pane label="Thông tin studio" name="studio">
        <div class="tab-panel page-list">
          <CustomCard v-loading="studioLoading" shadow="hover" class="studio-info-card">
            <div v-if="defaultStudio" class="studio-info">
              <el-avatar
                v-if="defaultStudio.logo"
                :size="72"
                shape="square"
                :src="mediaUrl(defaultStudio.logo)"
                class="studio-info__logo"
              />
              <div v-else class="studio-info__logo studio-info__logo--empty">—</div>
              <div class="studio-info__body">
                <div class="studio-info__title-row">
                  <h3 class="studio-info__name">{{ defaultStudio.ten_studio }}</h3>
                  <el-tag v-if="defaultStudio.mac_dinh === 'co'" size="small" type="success">
                    Mặc định
                  </el-tag>
                </div>
                <p v-if="defaultStudio.khau_hieu" class="studio-info__slogan">
                  {{ defaultStudio.khau_hieu }}
                </p>
                <div class="studio-info__meta">
                  <span v-if="defaultStudio.dia_chi">
                    <strong>Địa chỉ:</strong> {{ defaultStudio.dia_chi }}
                  </span>
                  <span v-if="defaultStudio.email">
                    <strong>Email:</strong> {{ defaultStudio.email }}
                  </span>
                  <span v-if="defaultStudio.so_dien_thoai">
                    <strong>SĐT:</strong> {{ defaultStudio.so_dien_thoai }}
                  </span>
                  <span v-if="defaultStudio.ma_so_thue">
                    <strong>MST:</strong> {{ defaultStudio.ma_so_thue }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else-if="!studioLoading" class="empty-state">
              Chưa có thông tin studio.
            </div>
          </CustomCard>

          <CustomCard shadow="hover" class="org-card">
            <template #header>
              <div class="card-header">
                <span class="card-title">Cấu trúc tổ chức</span>
              </div>
            </template>

            <div v-loading="orgLoading" class="org-chart-wrap">
              <div v-if="!orgLoading && !departments.length" class="empty-state">
                Chưa có phòng ban để hiển thị cấu trúc tổ chức.
              </div>

              <ApexTreeChart
                v-else-if="!orgLoading && orgTreeData"
                :data="orgTreeData"
                :options="orgTreeOptions"
                class="org-apextree"
              />
            </div>
          </CustomCard>
        </div>
      </el-tab-pane>

      <!-- Tab 2: Tài khoản thanh toán -->
      <el-tab-pane label="Tài khoản thanh toán" name="payment">
        <div class="tab-panel page-list">
          <CustomCard shadow="hover" class="filter-card">
            <CustomRow :gutter="12" class="toolbar">
              <CustomCol :xs="12" :sm="12" :md="6" :lg="7">
                <CustomInput
                  v-model="payment.keyword"
                  placeholder="Tìm theo ngân hàng, số TK, chủ TK..."
                  clearable
                  style="width: 100%"
                  @clear="onPaymentSearch"
                  @keyup.enter="onPaymentSearch"
                >
                  <template #prefix>
                    <CustomIcon><Search /></CustomIcon>
                  </template>
                </CustomInput>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="6" :lg="5">
                <CustomSelect
                  v-model="payment.macDinhFilter"
                  placeholder="Mặc định"
                  clearable
                  style="width: 100%"
                  @change="onPaymentSearch"
                >
                  <CustomOption label="Có" value="co" />
                  <CustomOption label="Không" value="khong" />
                </CustomSelect>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="6" :lg="5">
                <CustomSelect
                  v-model="payment.trangThaiFilter"
                  placeholder="Trạng thái"
                  clearable
                  style="width: 100%"
                  @change="onPaymentSearch"
                >
                  <CustomOption label="Đang hoạt động" value="dang_hoat_dong" />
                  <CustomOption label="Ngưng hoạt động" value="ngung_hoat_dong" />
                </CustomSelect>
              </CustomCol>
              <CustomCol :xs="12" :sm="12" :md="6" :lg="4">
                <CustomButton type="primary" plain @click="onPaymentSearch">
                  <CustomIcon><Search /></CustomIcon>
                  Tìm kiếm
                </CustomButton>
              </CustomCol>
            </CustomRow>
          </CustomCard>

          <CustomCard shadow="hover" class="table-card">
            <template #header>
              <div class="card-header">
                <span class="card-title">Danh sách tài khoản thanh toán</span>
                <div class="header-actions">
                  <TableColumnConfig :settings="paymentColumnSettings" />
                  <CustomTooltip content="Thêm mới" placement="top">
                    <CustomButton type="primary" @click="openPaymentCreate">
                      <CustomIcon><Plus /></CustomIcon>
                      Thêm
                    </CustomButton>
                  </CustomTooltip>
                </div>
              </div>
            </template>

            <CustomTable v-loading="payment.loading" :data="payment.items" stripe style="width: 100%">
              <CustomTableColumn label="STT" width="60" align="center">
                <template #default="{ $index }">
                  {{ (payment.page - 1) * payment.perPage + $index + 1 }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="paymentColumnSettings.isColumnVisible('hinh_anh_logo')"
                label="Logo"
                width="80"
                align="center"
              >
                <template #default="{ row }">
                  <el-avatar
                    v-if="row.hinh_anh_logo"
                    :size="40"
                    shape="square"
                    :src="mediaUrl(row.hinh_anh_logo)"
                  />
                  <span v-else>—</span>
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="paymentColumnSettings.isColumnVisible('ngan_hang')"
                prop="ngan_hang"
                label="Ngân hàng"
                min-width="160"
              />
              <CustomTableColumn
                v-if="paymentColumnSettings.isColumnVisible('so_tai_khoan')"
                prop="so_tai_khoan"
                label="Số tài khoản"
                min-width="160"
              />
              <CustomTableColumn
                v-if="paymentColumnSettings.isColumnVisible('chu_tai_khoan')"
                prop="chu_tai_khoan"
                label="Chủ tài khoản"
                min-width="160"
              />
              <CustomTableColumn
                v-if="paymentColumnSettings.isColumnVisible('chi_nhanh')"
                prop="chi_nhanh"
                label="Chi nhánh"
                min-width="160"
                show-overflow-tooltip
              >
                <template #default="{ row }">
                  {{ row.chi_nhanh || '—' }}
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="paymentColumnSettings.isColumnVisible('mac_dinh')"
                prop="mac_dinh"
                label="Mặc định"
                width="160"
                align="center"
              >
                <template #default="{ row }">
                  <div class="status-cell">
                    <el-switch
                      :model-value="row.mac_dinh"
                      active-value="co"
                      inactive-value="khong"
                      :loading="payment.togglingId === row.id && payment.togglingField === 'mac_dinh'"
                      :disabled="payment.togglingId === row.id"
                      :before-change="() => togglePaymentMacDinh(row)"
                    />
                    <span
                      class="status-label"
                      :class="row.mac_dinh === 'co' ? 'is-active' : 'is-inactive'"
                    >
                      {{ row.mac_dinh === 'co' ? 'Có' : 'Không' }}
                    </span>
                  </div>
                </template>
              </CustomTableColumn>
              <CustomTableColumn
                v-if="paymentColumnSettings.isColumnVisible('trang_thai')"
                prop="trang_thai"
                label="Trạng thái"
                width="200"
                align="center"
              >
                <template #default="{ row }">
                  <div class="status-cell">
                    <el-switch
                      :model-value="row.trang_thai"
                      active-value="dang_hoat_dong"
                      inactive-value="ngung_hoat_dong"
                      :loading="payment.togglingId === row.id && payment.togglingField === 'trang_thai'"
                      :disabled="payment.togglingId === row.id"
                      :before-change="() => togglePaymentTrangThai(row)"
                    />
                    <span
                      class="status-label"
                      :class="row.trang_thai === 'dang_hoat_dong' ? 'is-active' : 'is-inactive'"
                    >
                      {{ row.trang_thai === 'dang_hoat_dong' ? 'Đang hoạt động' : 'Ngưng hoạt động' }}
                    </span>
                  </div>
                </template>
              </CustomTableColumn>
              <CustomTableColumn label="Thao tác" width="100" fixed="right" align="center">
                <template #default="{ row }">
                  <div class="action-btns">
                    <CustomTooltip content="Sửa" placement="top">
                      <CustomButton type="primary" link :icon="Edit" @click="openPaymentEdit(row)" />
                    </CustomTooltip>
                    <CustomTooltip content="Xóa" placement="top">
                      <CustomButton type="danger" link :icon="Delete" @click="removePayment(row)" />
                    </CustomTooltip>
                  </div>
                </template>
              </CustomTableColumn>
            </CustomTable>

            <Pagination
              v-model="payment.page"
              v-model:page-size="payment.perPage"
              :total="payment.total"
              :disabled="payment.loading"
              @change="loadPayments"
            />
          </CustomCard>
        </div>
      </el-tab-pane>
    </el-tabs>

    <!-- Dialog: Tài khoản thanh toán -->
    <CustomDialog
      v-model="payment.dialogVisible"
      :title="payment.editingId ? 'Sửa tài khoản thanh toán' : 'Thêm tài khoản thanh toán'"
      :width="640"
    >
      <CustomForm ref="paymentFormRef" :model="paymentForm" :rules="paymentRules">
        <div class="payment-logo-preview">
          <img
            v-if="paymentLogoPreviewUrl"
            :src="paymentLogoPreviewUrl"
            class="payment-logo-image"
            alt="Logo ngân hàng"
          />
          <div v-else class="payment-logo-placeholder">Chưa có logo</div>
        </div>

        <CustomRow :gutter="16">
          <CustomCol :span="24">
            <CustomFormItem label="Link hình ảnh logo" prop="hinh_anh_logo">
              <CustomInput
                v-model="paymentForm.hinh_anh_logo"
                placeholder="https://... hoặc đường dẫn logo"
                clearable
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Ngân hàng" prop="ngan_hang">
              <CustomInput v-model="paymentForm.ngan_hang" placeholder="VD: Vietcombank" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Số tài khoản" prop="so_tai_khoan">
              <CustomInput v-model="paymentForm.so_tai_khoan" placeholder="Số tài khoản" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Chủ tài khoản" prop="chu_tai_khoan">
              <CustomInput v-model="paymentForm.chu_tai_khoan" placeholder="Tên chủ tài khoản" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Chi nhánh" prop="chi_nhanh">
              <CustomInput v-model="paymentForm.chi_nhanh" placeholder="Chi nhánh (tuỳ chọn)" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Mặc định" prop="mac_dinh">
              <CustomSelect v-model="paymentForm.mac_dinh" style="width: 100%">
                <CustomOption label="Có" value="co" />
                <CustomOption label="Không" value="khong" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="24" :sm="12">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="paymentForm.trang_thai" style="width: 100%">
                <CustomOption label="Đang hoạt động" value="dang_hoat_dong" />
                <CustomOption label="Ngưng hoạt động" value="ngung_hoat_dong" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
      <template #footer>
        <CustomButton @click="payment.dialogVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="payment.saving" @click="savePayment">Lưu</CustomButton>
      </template>
    </CustomDialog>
  </ConfigSettingPage>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import { fetchThongTinStudio } from '@/api/thongTinStudio'
import {
  createTaiKhoanThanhToan,
  deleteTaiKhoanThanhToan,
  fetchTaiKhoanThanhToan,
  updateTaiKhoanThanhToan,
} from '@/api/taiKhoanThanhToan'
import { fetchPhongBan, fetchPhongBanNhanVien } from '@/api/phongBan'
import { fetchUsers } from '@/api/users'
import { fetchVaiTro } from '@/api/vaiTro'
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
import TableColumnConfig from '@/components/TableColumnConfig.vue'
import { useTableColumns } from '@/composables/useTableColumns'
import { mediaUrl } from '@/utils/media'
import { ApexTreeChart } from 'vue-apextree'
import ConfigSettingPage from './ConfigSettingPage.vue'

const DEPT_COLORS = [
  { bg: '#EFF6FF', border: '#93C5FD', borderHover: '#3B82F6', accent: '#2563EB' },
  { bg: '#FDF2F8', border: '#F9A8D4', borderHover: '#EC4899', accent: '#DB2777' },
  { bg: '#FFF7ED', border: '#FDBA74', borderHover: '#F97316', accent: '#EA580C' },
  { bg: '#F0FDF4', border: '#86EFAC', borderHover: '#22C55E', accent: '#16A34A' },
  { bg: '#EEF2FF', border: '#A5B4FC', borderHover: '#6366F1', accent: '#4F46E5' },
  { bg: '#F8FAFC', border: '#94A3B8', borderHover: '#64748B', accent: '#475569' },
]

const HQ_COLORS = {
  bg: '#F8FAFC',
  border: '#64748B',
  borderHover: '#475569',
  accent: '#475569',
}

/** ApexTree layout dùng nodeWidth/nodeHeight global cho node thường. */
const EMP_CARD_WIDTH = 230
/** Chiều cao tối thiểu cấp 1–2 (card CEO/phòng ban). */
const LAYOUT_NODE_HEIGHT = 140

function makeNodeOptions(colors) {
  return {
    nodeBGColor: colors.bg,
    nodeBGColorHover: colors.bg,
    borderColor: colors.border,
    borderColorHover: colors.borderHover,
  }
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

function avatarSrc(name, url) {
  if (url) return url
  return `https://api.dicebear.com/9.x/initials/svg?seed=${encodeURIComponent(name || '?')}`
}

function buildEmployeeListHtml(employees, accent) {
  if (!employees?.length) {
    return `<div style="padding:10px 4px; color:#94a3b8; text-align:center;">Chưa có nhân viên</div>`
  }

  return employees
    .map((emp, index) => {
      const name = escapeHtml(emp.name)
      const position = escapeHtml(emp.position || 'Nhân viên')
      return `
        <div style="
          padding:6px 4px;
          color:#1e293b;
          font-size:13px;
          line-height:1.45;
          border-top:${index === 0 ? 'none' : `1px solid ${accent}22`};
        ">
          ${index + 1}. ${name} - ${position}
        </div>`
    })
    .join('')
}

function orgNodeTemplate(content) {
  const c = typeof content === 'string' ? JSON.parse(content) : content || {}
  const accent = escapeHtml(c.accent || '#64748b')

  if (c.type === 'dept') {
    const deptName = escapeHtml(c.deptName)
    const headName = escapeHtml(c.headName)

    return `
      <div style="
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        height:100%;
        width:100%;
        padding:12px 10px;
        box-sizing:border-box;
        text-align:center;
        overflow:hidden;
      ">
        <div style="font-weight:700; font-size:14px; color:#0f172a; line-height:1.3;">
          ${deptName}
        </div>
        <div style="font-size:11px; color:#64748b; margin-top:6px; line-height:1.3;">
          TP: ${headName}
        </div>
      </div>`
  }

  if (c.type === 'employees') {
    const listHtml = buildEmployeeListHtml(c.employees, accent)

    return `
      <div style="
        display:flex;
        flex-direction:column;
        height:100%;
        width:100%;
        padding:12px 14px;
        box-sizing:border-box;
        overflow:hidden;
      ">
        <div style="font-size:12px; font-weight:600; color:${accent}; margin-bottom:8px; text-align:center; flex-shrink:0;">
          Danh sách nhân viên
        </div>
        <div style="text-align:left; width:100%;">
          ${listHtml}
        </div>
      </div>`
  }

  const name = escapeHtml(c.name)
  const title = escapeHtml(c.title)
  const dept = escapeHtml(c.dept)
  const img = escapeHtml(c.img || '')

  return `
    <div style="
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      height:100%;
      width:100%;
      padding:10px 8px;
      box-sizing:border-box;
      text-align:center;
      overflow:hidden;
    ">
      <img src="${img}" alt=""
        style="
          width: 40px; height: 40px;
          border-radius: 50%;
          object-fit: cover;
          flex-shrink: 0;
          border: 2px solid ${accent};
          margin-bottom: 6px;
        " />
      <div style="font-weight: 600; font-size: 12px; color: #1e293b; line-height: 1.3;">${name}</div>
      <div style="font-size: 10px; color: #64748b; margin-top: 2px;">${title}</div>
      <div style="font-size: 9px; color: ${accent}; margin-top: 2px; font-weight: 500;">${dept}</div>
    </div>`
}

const paymentTableColumns = [
  { key: 'hinh_anh_logo', label: 'Logo' },
  { key: 'ngan_hang', label: 'Ngân hàng' },
  { key: 'so_tai_khoan', label: 'Số tài khoản' },
  { key: 'chu_tai_khoan', label: 'Chủ tài khoản' },
  { key: 'chi_nhanh', label: 'Chi nhánh' },
  { key: 'mac_dinh', label: 'Mặc định' },
  { key: 'trang_thai', label: 'Trạng thái' },
]
const paymentColumnSettings = useTableColumns('he-thong.tai-khoan-thanh-toan', paymentTableColumns)

const activeTab = ref('studio')
const paymentLoaded = ref(false)

const studioLoading = ref(false)
const defaultStudio = ref(null)

const orgLoading = ref(false)
const ceo = ref(null)
const departments = ref([])

function normalizeText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
}

function isBgdDept(dept) {
  const ma = normalizeText(dept?.ma_phong_ban)
  const ten = normalizeText(dept?.ten_phong_ban)
  return ma === 'bgd' || ten.includes('ban giam doc')
}

/** Phòng ban cấp 2 (không gồm BGD — CEO đã đại diện cấp 1). */
const chartDepartments = computed(() => departments.value.filter((dept) => !isBgdDept(dept)))

const orgTreeData = computed(() => {
  if (!departments.value.length) return null

  const ceoName = ceo.value?.name || 'Chưa xác định CEO'
  const ceoTitle = ceo.value?.title || 'CEO'
  const ceoId = ceo.value?.id ?? null

  return {
    id: 'ceo',
    name: ceoName,
    data: {
      type: 'ceo',
      name: ceoName,
      title: ceoTitle,
      dept: 'Ban Giám đốc',
      img: avatarSrc(ceoName, ceo.value?.avatarUrl),
      accent: HQ_COLORS.accent,
    },
    options: {
      ...makeNodeOptions(HQ_COLORS),
    },
    children: chartDepartments.value.map((dept, index) => {
      const colors = DEPT_COLORS[index % DEPT_COLORS.length]
      const headName = dept.truong_phong || 'Chưa có'
      const employees = (dept.employees || [])
        .filter((emp) => (ceoId == null ? true : emp.userId !== ceoId && emp.name !== ceoName))
        .map((emp) => ({
          name: emp.name,
          position: emp.position || '',
        }))
      const empRows = Math.max(employees.length, 1)
      const empNodeHeight = Math.max(LAYOUT_NODE_HEIGHT, 28 + 28 + empRows * 32 + 8)

      return {
        id: `dept-${dept.id}`,
        name: dept.ten_phong_ban,
        data: {
          type: 'dept',
          deptName: dept.ten_phong_ban,
          headName,
          accent: colors.accent,
        },
        options: {
          ...makeNodeOptions(colors),
        },
        children: [
          {
            id: `emp-list-${dept.id}`,
            name: `Nhân viên ${dept.ten_phong_ban}`,
            data: {
              type: 'employees',
              employees,
              accent: colors.accent,
            },
            options: {
              ...makeNodeOptions(colors),
              nodeWidth: EMP_CARD_WIDTH,
              nodeHeight: empNodeHeight,
            },
            children: [],
          },
        ],
      }
    }),
  }
})

const orgMaxEmpCardHeight = computed(() => {
  if (!orgTreeData.value?.children?.length) return LAYOUT_NODE_HEIGHT
  let maxH = LAYOUT_NODE_HEIGHT
  for (const dept of orgTreeData.value.children) {
    const empNode = dept.children?.[0]
    const h = empNode?.options?.nodeHeight
    if (typeof h === 'number' && h > maxH) maxH = h
  }
  return maxH
})

const orgTreeOptions = computed(() => ({
  contentKey: 'data',
  nodeTemplate: orgNodeTemplate,
  width: '100%',
  height: Math.max(820, 280 + orgMaxEmpCardHeight.value * 2),
  viewPortWidth: Math.max(1400, EMP_CARD_WIDTH * Math.max(chartDepartments.value.length, 1) + 240),
  viewPortHeight: Math.max(1000, 320 + orgMaxEmpCardHeight.value * 2),
  nodeWidth: EMP_CARD_WIDTH,
  nodeHeight: orgMaxEmpCardHeight.value,
  // Theo demo Top to Bottom: https://apexcharts.com/apextree/demos/top-to-bottom/
  childrenSpacing: 70,
  siblingSpacing: 12,
  direction: 'top',
  edgeStyle: 'orthogonal',
  edgeWidth: 1,
  edgeColorMode: 'node',
  enableExpandCollapse: false,
  enableToolbar: true,
  groupLeafNodes: false,
  canvasStyle: 'border: 1px solid #e0e0e0; border-radius: 8px; background: #ffffff;',
}))

const payment = reactive({
  items: [],
  loading: false,
  saving: false,
  togglingId: null,
  togglingField: null,
  page: 1,
  perPage: 10,
  total: 0,
  keyword: '',
  macDinhFilter: '',
  trangThaiFilter: '',
  dialogVisible: false,
  editingId: null,
})

const paymentFormRef = ref(null)

const emptyPaymentForm = () => ({
  ngan_hang: '',
  so_tai_khoan: '',
  chu_tai_khoan: '',
  chi_nhanh: '',
  hinh_anh_logo: '',
  mac_dinh: 'khong',
  trang_thai: 'dang_hoat_dong',
})

const paymentForm = reactive(emptyPaymentForm())

const paymentRules = {
  ngan_hang: [{ required: true, message: 'Vui lòng nhập ngân hàng', trigger: 'blur' }],
  so_tai_khoan: [{ required: true, message: 'Vui lòng nhập số tài khoản', trigger: 'blur' }],
  chu_tai_khoan: [{ required: true, message: 'Vui lòng nhập chủ tài khoản', trigger: 'blur' }],
  mac_dinh: [{ required: true, message: 'Vui lòng chọn mặc định', trigger: 'change' }],
  trang_thai: [{ required: true, message: 'Vui lòng chọn trạng thái', trigger: 'change' }],
}

const paymentLogoPreviewUrl = computed(() => mediaUrl(paymentForm.hinh_anh_logo?.trim()))

const CEO_CEO_RE = /\bceo\b/i
const CEO_GIAM_DOC_RE = /gi[aá]m\s*đ?[oố]c|giam\s*doc/i

function scoreCeoCandidate(roleName, position) {
  const role = String(roleName || '')
  const pos = String(position || '')
  const combined = `${role} ${pos}`
  if (CEO_CEO_RE.test(combined)) return 3
  if (CEO_GIAM_DOC_RE.test(combined) || CEO_GIAM_DOC_RE.test(normalizeText(combined))) return 2
  return 0
}

function userInDept(user, deptId) {
  if (deptId == null) return false
  const ids = user?.nhan_vien?.phong_ban_ids
  if (!Array.isArray(ids)) return false
  return ids.map(Number).includes(Number(deptId))
}

function mapEmployee(row) {
  const name = row?.user?.name || '—'
  return {
    id: row.id,
    userId: row.user_id ?? row.user?.id ?? null,
    name,
    position: row.vi_tri_lam_viec || '',
    avatarUrl: mediaUrl(row.hinh_anh),
  }
}

async function loadDefaultStudio() {
  studioLoading.value = true
  try {
    const { data } = await fetchThongTinStudio({ mac_dinh: 'co', per_page: 1 })
    let item = (data.data || [])[0] || null
    if (!item) {
      const fallback = await fetchThongTinStudio({ per_page: 1 })
      item = (fallback.data.data || [])[0] || null
    }
    defaultStudio.value = item
  } catch {
    defaultStudio.value = null
  } finally {
    studioLoading.value = false
  }
}

async function resolveCeo(vaiTroMap, bgdDept) {
  try {
    const { data } = await fetchUsers({ per_page: 100, status: 'active' })
    const users = data.data || []
    const bgdId = bgdDept?.id ?? null
    const truongPhong = String(bgdDept?.truong_phong || '')
      .trim()
      .toLowerCase()

    let best = null
    let bestScore = -1

    for (const user of users) {
      const nv = user.nhan_vien || {}
      const roleName = vaiTroMap.get(Number(nv.vai_tro_id)) || ''
      let score = scoreCeoCandidate(roleName, nv.vi_tri_lam_viec)

      if (userInDept(user, bgdId)) score += 5
      if (truongPhong && String(user.name || '').trim().toLowerCase() === truongPhong) {
        score += 4
      }
      // Ưu tiên thành viên BGD dù chưa gắn chức danh Giám đốc/CEO
      if (score === 0 && userInDept(user, bgdId)) score = 1

      if (score > bestScore) {
        bestScore = score
        best = {
          id: user.id,
          name: user.name,
          title: roleName || nv.vi_tri_lam_viec || 'CEO',
          avatarUrl: mediaUrl(nv.hinh_anh),
        }
      }
    }

    if (!best && bgdDept?.truong_phong) {
      return {
        id: null,
        name: bgdDept.truong_phong,
        title: 'CEO',
        avatarUrl: '',
      }
    }

    return best
  } catch {
    return null
  }
}

async function loadOrgChart() {
  orgLoading.value = true
  try {
    const [vaiTroRes, phongBanRes] = await Promise.all([
      fetchVaiTro({ per_page: 100 }),
      fetchPhongBan({ per_page: 100 }),
    ])

    const vaiTroMap = new Map(
      (vaiTroRes.data.data || []).map((vt) => [Number(vt.id), vt.ten_vai_tro || '']),
    )

    const depts = phongBanRes.data.data || []
    const bgdDept = depts.find((dept) => isBgdDept(dept)) || null

    const employeeLists = await Promise.all(
      depts.map(async (dept) => {
        try {
          const { data } = await fetchPhongBanNhanVien(dept.id, { per_page: 100 })
          return (data.data || []).map(mapEmployee)
        } catch {
          return []
        }
      }),
    )

    departments.value = depts.map((dept, index) => ({
      id: dept.id,
      ten_phong_ban: dept.ten_phong_ban,
      ma_phong_ban: dept.ma_phong_ban,
      truong_phong: dept.truong_phong,
      employees: employeeLists[index] || [],
    }))

    ceo.value = await resolveCeo(vaiTroMap, bgdDept)
  } catch {
    ceo.value = null
    departments.value = []
  } finally {
    orgLoading.value = false
  }
}

async function loadPayments() {
  payment.loading = true
  try {
    const { data } = await fetchTaiKhoanThanhToan({
      page: payment.page,
      per_page: payment.perPage,
      keyword: payment.keyword.trim() || undefined,
      mac_dinh: payment.macDinhFilter || undefined,
      trang_thai: payment.trangThaiFilter || undefined,
    })
    payment.items = data.data || []
    payment.total = data.total || 0
    payment.page = data.current_page || payment.page
  } catch {
    payment.items = []
    payment.total = 0
  } finally {
    payment.loading = false
  }
}

function onPaymentSearch() {
  payment.page = 1
  loadPayments()
}

async function togglePaymentMacDinh(row) {
  if (!row?.id) return false

  const value = row.mac_dinh === 'co' ? 'khong' : 'co'
  payment.togglingId = row.id
  payment.togglingField = 'mac_dinh'

  try {
    await updateTaiKhoanThanhToan(row.id, {
      ngan_hang: row.ngan_hang,
      so_tai_khoan: row.so_tai_khoan,
      chu_tai_khoan: row.chu_tai_khoan,
      chi_nhanh: row.chi_nhanh || null,
      hinh_anh_logo: row.hinh_anh_logo || null,
      mac_dinh: value,
      trang_thai: row.trang_thai || 'dang_hoat_dong',
    })
    if (value === 'co') {
      payment.items.forEach((item) => {
        item.mac_dinh = item.id === row.id ? 'co' : 'khong'
      })
    } else {
      row.mac_dinh = 'khong'
    }
    ElMessage.success(
      value === 'co' ? 'Đã đặt làm tài khoản mặc định.' : 'Đã bỏ mặc định.',
    )
    return true
  } catch {
    return false
  } finally {
    payment.togglingId = null
    payment.togglingField = null
  }
}

async function togglePaymentTrangThai(row) {
  if (!row?.id) return false

  const value = row.trang_thai === 'dang_hoat_dong' ? 'ngung_hoat_dong' : 'dang_hoat_dong'
  payment.togglingId = row.id
  payment.togglingField = 'trang_thai'

  try {
    await updateTaiKhoanThanhToan(row.id, {
      ngan_hang: row.ngan_hang,
      so_tai_khoan: row.so_tai_khoan,
      chu_tai_khoan: row.chu_tai_khoan,
      chi_nhanh: row.chi_nhanh || null,
      hinh_anh_logo: row.hinh_anh_logo || null,
      mac_dinh: row.mac_dinh || 'khong',
      trang_thai: value,
    })
    row.trang_thai = value
    ElMessage.success(
      value === 'dang_hoat_dong' ? 'Đã bật hoạt động tài khoản.' : 'Đã ngưng hoạt động tài khoản.',
    )
    return true
  } catch {
    return false
  } finally {
    payment.togglingId = null
    payment.togglingField = null
  }
}

function openPaymentCreate() {
  payment.editingId = null
  Object.assign(paymentForm, emptyPaymentForm())
  payment.dialogVisible = true
}

function openPaymentEdit(row) {
  payment.editingId = row.id
  Object.assign(paymentForm, {
    ngan_hang: row.ngan_hang || '',
    so_tai_khoan: row.so_tai_khoan || '',
    chu_tai_khoan: row.chu_tai_khoan || '',
    chi_nhanh: row.chi_nhanh || '',
    hinh_anh_logo: row.hinh_anh_logo || '',
    mac_dinh: row.mac_dinh || 'khong',
    trang_thai: row.trang_thai || 'dang_hoat_dong',
  })
  payment.dialogVisible = true
}

async function savePayment() {
  const valid = await paymentFormRef.value?.validate().catch(() => false)
  if (!valid) return

  payment.saving = true
  const payload = {
    ngan_hang: paymentForm.ngan_hang.trim(),
    so_tai_khoan: paymentForm.so_tai_khoan.trim(),
    chu_tai_khoan: paymentForm.chu_tai_khoan.trim(),
    chi_nhanh: paymentForm.chi_nhanh?.trim() || null,
    hinh_anh_logo: paymentForm.hinh_anh_logo?.trim() || null,
    mac_dinh: paymentForm.mac_dinh,
    trang_thai: paymentForm.trang_thai || 'dang_hoat_dong',
  }

  try {
    if (payment.editingId) {
      await updateTaiKhoanThanhToan(payment.editingId, payload)
      ElMessage.success('Đã cập nhật tài khoản thanh toán.')
    } else {
      await createTaiKhoanThanhToan(payload)
      ElMessage.success('Đã thêm tài khoản thanh toán.')
    }
    payment.dialogVisible = false
    await loadPayments()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  } finally {
    payment.saving = false
  }
}

async function removePayment(row) {
  await ElMessageBox.confirm(
    `Xóa tài khoản "${row.so_tai_khoan}" (${row.ngan_hang})?`,
    'Xác nhận',
    {
      type: 'warning',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
    },
  )

  try {
    await deleteTaiKhoanThanhToan(row.id)
    ElMessage.success('Đã xóa tài khoản thanh toán.')
    await loadPayments()
  } catch {
    // Lỗi đã được axios interceptor xử lý
  }
}

watch(activeTab, (tab) => {
  if (tab === 'payment' && !paymentLoaded.value) {
    paymentLoaded.value = true
    loadPayments()
  }
})

onMounted(() => {
  loadDefaultStudio()
  loadOrgChart()
})
</script>

<style scoped>
.studio-tabs :deep(.el-tabs__header) {
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

.studio-info-card {
  margin-bottom: 16px;
}

.studio-info {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.studio-info__logo {
  flex-shrink: 0;
  border-radius: 10px;
  background: var(--el-fill-color-light);
}

.studio-info__logo--empty {
  width: 72px;
  height: 72px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px dashed var(--el-border-color);
  color: var(--el-text-color-secondary);
  font-size: 14px;
}

.studio-info__body {
  min-width: 0;
  flex: 1;
}

.studio-info__title-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.studio-info__name {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.studio-info__slogan {
  margin: 6px 0 0;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.studio-info__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  margin-top: 10px;
  font-size: 13px;
  color: var(--el-text-color-regular);
}

.org-card {
  margin-bottom: 0;
}

.org-chart-wrap {
  min-height: 240px;
}

.org-apextree {
  width: 100%;
  min-height: 820px;
}

.empty-state {
  padding: 32px 16px;
  text-align: center;
  color: var(--el-text-color-secondary);
  font-size: 14px;
}

.payment-logo-preview {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.payment-logo-image {
  width: 112px;
  height: 112px;
  object-fit: contain;
  display: block;
  border-radius: 12px;
  border: 1px solid var(--el-border-color-lighter);
  background: var(--el-fill-color-light);
  padding: 8px;
}

.payment-logo-placeholder {
  width: 112px;
  height: 112px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  border: 1px dashed var(--el-border-color);
  background: var(--el-fill-color-blank);
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

@media (max-width: 768px) {
  .studio-info {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .studio-info__title-row {
    justify-content: center;
  }

  .studio-info__meta {
    justify-content: center;
  }

  .org-apextree {
    min-height: 480px;
  }
}
</style>
