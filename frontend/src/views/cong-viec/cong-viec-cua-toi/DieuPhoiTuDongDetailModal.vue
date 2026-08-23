<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1100"
    class="dieu-phoi-tu-dong-detail-modal"
    @closed="onClosed"
  >
    <div v-loading="loading" class="detail-body">
      <el-empty
        v-if="!loading && !hopDong"
        description="Không tìm thấy thông tin hợp đồng."
      />

      <template v-else-if="hopDong">
        <section class="detail-section">
          <div class="detail-section__title">Thông tin chung</div>
          <CustomForm label-position="top">
            <CustomRow :gutter="16">
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Mã hợp đồng">
                  <CustomInput :model-value="display(hopDong.ma_hop_dong)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Loại hợp đồng">
                  <CustomInput
                    :model-value="display(hopDong.loai_hop_dong?.ten_hop_dong)"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Trạng thái">
                  <CustomInput
                    :model-value="trangThaiLabel(hopDong.trang_thai)"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Tên khách hàng">
                  <CustomInput :model-value="display(hopDong.ten_khach_hang)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="SĐT khách hàng">
                  <CustomInput :model-value="display(hopDong.sdt_khach_hang)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Địa chỉ">
                  <CustomInput :model-value="display(hopDong.dia_chi)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="wideColProps">
                <CustomFormItem label="Ghi chú sale">
                  <CustomInput
                    :model-value="display(hopDong.ghi_chu_sale)"
                    type="textarea"
                    :rows="3"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="wideColProps">
                <CustomFormItem label="Yêu cầu đặc biệt">
                  <CustomInput
                    :model-value="display(hopDong.yeu_cau_dac_biet)"
                    type="textarea"
                    :rows="3"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-if="yeuCauKhachHang" v-bind="wideColProps">
                <CustomFormItem label="Yêu cầu khách hàng">
                  <CustomInput
                    :model-value="yeuCauKhachHang"
                    type="textarea"
                    :rows="3"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
            </CustomRow>
          </CustomForm>
        </section>

        <section class="detail-section">
          <div class="detail-section__title">Concept</div>
          <CustomTable v-if="conceptRows.length" :data="conceptRows" stripe style="width: 100%">
            <CustomTableColumn label="STT" width="64" align="center">
              <template #default="{ $index }">{{ $index + 1 }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="ten" label="Tên concept" min-width="200" />
            <CustomTableColumn label="Ngày sử dụng" width="140" align="center">
              <template #default="{ row }">{{ formatDate(row.ngay_su_dung) }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="dia_diem" label="Địa điểm" min-width="180">
              <template #default="{ row }">{{ display(row.dia_diem) }}</template>
            </CustomTableColumn>
          </CustomTable>
          <div v-else class="detail-empty">Chưa có concept.</div>
        </section>

        <section class="detail-section">
          <div class="detail-section__title">Trang phục</div>
          <CustomTable v-if="trangPhucRows.length" :data="trangPhucRows" stripe style="width: 100%">
            <CustomTableColumn label="STT" width="64" align="center">
              <template #default="{ $index }">{{ $index + 1 }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="ten" label="Tên trang phục" min-width="180" />
            <CustomTableColumn label="Ngày sử dụng" width="140" align="center">
              <template #default="{ row }">{{ formatDate(row.ngay_su_dung) }}</template>
            </CustomTableColumn>
            <!-- <CustomTableColumn label="Ngày bắt đầu" width="130" align="center">
              <template #default="{ row }">{{ formatDate(row.ngay_bat_dau) }}</template>
            </CustomTableColumn>
            <CustomTableColumn label="Ngày kết thúc" width="130" align="center">
              <template #default="{ row }">{{ formatDate(row.ngay_ket_thuc) }}</template>
            </CustomTableColumn> -->
          </CustomTable>
          <div v-else class="detail-empty">Chưa có trang phục.</div>
        </section>

        <section class="detail-section">
          <div class="detail-section__title">Thông tin điều phối</div>
          <CustomTable
            v-if="dieuPhoiRows.length"
            :data="dieuPhoiRows"
            stripe
            style="width: 100%"
          >
            <CustomTableColumn prop="ten" label="Đầu mục" width="140" />
            <CustomTableColumn prop="thoi_gian" label="Thời gian" min-width="200">
              <template #default="{ row }">{{ display(row.thoi_gian) }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="dia_diem" label="Địa điểm" min-width="200">
              <template #default="{ row }">{{ display(row.dia_diem) }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="nguoi_thuc_hien" label="Người thực hiện" min-width="200">
              <template #default="{ row }">{{ display(row.nguoi_thuc_hien) }}</template>
            </CustomTableColumn>
          </CustomTable>
          <div v-else class="detail-empty">Chưa có thông tin điều phối.</div>
        </section>
      </template>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Đóng</CustomButton>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { getHopDongSuDungDichVu } from '@/api/hopDongSuDungDichVu'
import { fetchUsers } from '@/api/users'
import {
  CustomButton,
  CustomCol,
  CustomDialog,
  CustomForm,
  CustomFormItem,
  CustomInput,
  CustomRow,
  CustomTable,
  CustomTableColumn,
} from '@/components/element'
import {
  DIEU_PHOI_STAFF_KEYS,
  collectDieuPhoiGiaTri,
  getDieuPhoiGiaTriFromSession,
  normalizeDieuPhoiSessions,
} from '@/utils/thongTinDieuPhoi'

const STAFF_FIELD_KEYS = DIEU_PHOI_STAFF_KEYS

const DIEU_PHOI_ROLE_ROWS = [
  {
    key: 'chup',
    ten: 'Chụp',
    staffKey: 'tho_chup',
    staffNgoaiKey: 'tho_chup_ngoai',
    hasSchedule: true,
  },
  {
    key: 'make',
    ten: 'Make',
    staffKey: 'tho_make',
    staffNgoaiKey: 'tho_make_ngoai',
    hasSchedule: false,
  },
  {
    key: 'edit',
    ten: 'Edit',
    staffKey: 'tho_edit',
    staffNgoaiKey: 'tho_edit_ngoai',
    hasSchedule: false,
  },
  {
    key: 'quay_phim',
    ten: 'Quay phim',
    staffKey: 'quay_phim',
    staffNgoaiKey: 'quay_phim_ngoai',
    hasSchedule: false,
  },
  {
    key: 'dung_video',
    ten: 'Thợ dựng video',
    staffKey: 'tho_dung_video',
    staffNgoaiKey: 'tho_dung_video_ngoai',
    hasSchedule: false,
  },
]

const TRANG_THAI_OPTIONS = [
  { value: 'moi_tao', label: 'Mới tạo' },
  { value: 'nhap', label: 'Nháp' },
  { value: 'da_coc', label: 'Đã cọc' },
  { value: 'dang_thuc_hien', label: 'Đang thực hiện' },
  { value: 'da_huy', label: 'Đã hủy' },
  { value: 'hoan_thanh', label: 'Hoàn thành' },
]

const fieldColProps = { xs: 12, sm: 8, md: 6, lg: 4, xl: 4 }
const wideColProps = { xs: 24, sm: 24, md: 12, lg: 12, xl: 12 }

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  hopDongId: { type: [Number, String], default: null },
})

const emit = defineEmits(['update:modelValue', 'closed'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const loading = ref(false)
const hopDong = ref(null)
const userOptions = ref([])

const dialogTitle = computed(() => {
  const ma = hopDong.value?.ma_hop_dong
  return ma ? `Chi tiết hợp đồng — ${ma}` : 'Chi tiết hợp đồng'
})

const userNameMap = computed(() => {
  const map = new Map()
  for (const user of userOptions.value) {
    map.set(user.id, user.name)
  }
  return map
})

const dieuPhoiSessions = computed(() =>
  normalizeDieuPhoiSessions(hopDong.value?.thong_tin_dieu_phoi),
)

const yeuCauKhachHang = computed(() => {
  const values = collectDieuPhoiGiaTri(
    hopDong.value?.thong_tin_dieu_phoi,
    'ghi_chu_trang_phuc_phu_kien',
  )
  const text = values.map((item) => String(item).trim()).filter(Boolean).join('; ')
  return text
})

const conceptRows = computed(() => {
  const rows = Array.isArray(hopDong.value?.concepts) ? hopDong.value.concepts : []
  return rows.map((row) => ({
    ten: row.concept?.ten_concept || `Concept #${row.concept_id}`,
    dia_diem: row.concept?.dia_diem || '',
    ngay_su_dung: row.ngay_su_dung || null,
  }))
})

const trangPhucRows = computed(() => {
  const rows = Array.isArray(hopDong.value?.trang_phucs) ? hopDong.value.trang_phucs : []
  return rows.map((row) => ({
    ten: row.trang_phuc?.ten_san_pham || `Trang phục #${row.trang_phuc_id}`,
    ngay_su_dung: row.ngay_su_dung || null,
    ngay_bat_dau: row.ngay_bat_dau || null,
    ngay_ket_thuc: row.ngay_ket_thuc || null,
  }))
})

const thoiGianChup = computed(() => {
  return dieuPhoiSessions.value
    .map((session) => {
      const gio = formatTime(getDieuPhoiGiaTriFromSession(session, 'gio_chup'))
      const buoi = formatBuoi(getDieuPhoiGiaTriFromSession(session, 'buoi_chup'))
      const ngay = formatDateValue(getDieuPhoiGiaTriFromSession(session, 'ngay_chup'))
      return [gio, buoi, ngay].filter(Boolean).join(' ')
    })
    .filter(Boolean)
    .join('; ')
})

const diaDiemChup = computed(() => {
  const values = collectDieuPhoiGiaTri(hopDong.value?.thong_tin_dieu_phoi, 'dia_diem_chup')
  return values
    .flatMap((raw) => (Array.isArray(raw) ? raw : [raw]))
    .map((item) => String(item).trim())
    .filter(Boolean)
    .join(', ')
})

const dieuPhoiRows = computed(() => {
  const rows = []
  for (const role of DIEU_PHOI_ROLE_ROWS) {
    const nguoiThucHien = formatStaffValue(
      getDieuPhoiRaw(role.staffKey),
      getDieuPhoiRaw(role.staffNgoaiKey),
      role.staffKey,
    )
    const thoiGian = role.hasSchedule ? thoiGianChup.value : ''
    const diaDiem = role.hasSchedule ? diaDiemChup.value : ''

    if (!thoiGian && !diaDiem && !nguoiThucHien) continue

    rows.push({
      key: role.key,
      ten: role.ten,
      thoi_gian: thoiGian,
      dia_diem: diaDiem,
      nguoi_thuc_hien: nguoiThucHien,
    })
  }
  return rows
})

function getDieuPhoiRaw(key) {
  const values = collectDieuPhoiGiaTri(hopDong.value?.thong_tin_dieu_phoi, key)
  if (!values.length) return null
  if (values.length === 1) return values[0]
  return values
}

function display(value) {
  if (value == null || value === '') return '—'
  return value
}

function formatDate(value) {
  if (!value) return '—'
  return formatDateValue(value) || String(value)
}

function formatDateValue(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString('vi-VN')
}

function formatTime(value) {
  if (value == null || value === '') return ''
  return String(value).trim()
}

function formatBuoi(value) {
  if (value == null || value === '') return ''
  const map = {
    sang: 'Sáng',
    chieu: 'Chiều',
    toi: 'Tối',
  }
  const key = String(value).trim().toLowerCase()
  return map[key] || String(value).trim()
}

function trangThaiLabel(value) {
  return TRANG_THAI_OPTIONS.find((opt) => opt.value === value)?.label || value || '—'
}

function formatStaffValue(ids, ngoai, key) {
  const names = []
  if (ids != null && ids !== '') {
    const list = Array.isArray(ids) ? ids : [ids]
    for (const id of list) {
      if (id == null || id === '') continue
      if (STAFF_FIELD_KEYS.has(key)) {
        names.push(
          userNameMap.value.get(Number(id)) ||
            userNameMap.value.get(id) ||
            `#${id}`,
        )
      } else {
        names.push(String(id))
      }
    }
  }
  if (ngoai != null && ngoai !== '') {
    const extra = Array.isArray(ngoai) ? ngoai : [ngoai]
    for (const item of extra) {
      if (item == null || String(item).trim() === '') continue
      names.push(String(item).trim())
    }
  }
  return names.length ? names.join(', ') : ''
}

async function loadData() {
  if (!props.hopDongId) return

  loading.value = true
  hopDong.value = null

  try {
    const [hopDongRes, usersRes] = await Promise.all([
      getHopDongSuDungDichVu(props.hopDongId),
      fetchUsers({ per_page: 100, status: 'active' }),
    ])
    hopDong.value = hopDongRes.data
    userOptions.value = usersRes.data.data || []
  } catch {
    hopDong.value = null
    userOptions.value = []
  } finally {
    loading.value = false
  }
}

function onClosed() {
  hopDong.value = null
  userOptions.value = []
  emit('closed')
}

watch(
  () => props.modelValue,
  (isOpen) => {
    if (!isOpen) return
    loadData()
  },
)
</script>

<style scoped lang="scss">
.detail-body {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.detail-section__title {
  margin-bottom: 10px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.detail-empty {
  padding: 14px 12px;
  border-radius: 8px;
  background: var(--el-fill-color-lighter);
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
