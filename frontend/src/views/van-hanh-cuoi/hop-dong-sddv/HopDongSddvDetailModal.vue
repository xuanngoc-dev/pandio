<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1280"
    class="hop-dong-sddv-detail-modal"
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
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Kênh tiếp cận">
                  <CustomInput :model-value="display(hopDong.kenh_tiep_can)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Người tạo">
                  <CustomInput :model-value="display(hopDong.nguoi_tao?.name)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Người tham gia">
                  <CustomInput :model-value="nguoiThamGiaLabel" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Ngày tạo">
                  <CustomInput :model-value="formatDate(hopDong.created_at)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Lượt giới thiệu">
                  <CustomInput :model-value="display(hopDong.luot_gioi_thieu)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-if='false' v-bind="wideColProps">
                <CustomFormItem label="Ghi chú sale">
                  <CustomInput
                    :model-value="display(hopDong.ghi_chu_sale)"
                    type="textarea"
                    :rows="3"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
            </CustomRow>
          </CustomForm>
        </section>

        <section v-if="thongTinHopDongItems.length" class="detail-section">
          <div class="detail-section__title">Thông tin hợp đồng</div>
          <CustomForm label-position="top">
            <CustomRow :gutter="16">
              <CustomCol
                v-for="item in thongTinHopDongItems"
                :key="item.key"
                v-bind="item.wide ? wideColProps : fieldColProps"
              >
                <CustomFormItem :label="item.label">
                  <CustomInput
                    :model-value="item.value"
                    :type="item.wide ? 'textarea' : 'text'"
                    :rows="item.wide ? 3 : undefined"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
            </CustomRow>
          </CustomForm>
        </section>

        <section class="detail-section">
          <div class="detail-section__title">Combo dịch vụ</div>
          <CustomTable v-if="comboRows.length" :data="comboRows" stripe style="width: 100%">
            <CustomTableColumn label="STT" width="64" align="center">
              <template #default="{ $index }">{{ $index + 1 }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="ten" label="Tên combo" min-width="200" />
            <CustomTableColumn prop="so_luong" label="Số lượng" width="100" align="center" />
            <CustomTableColumn label="Thành tiền" width="140" align="right">
              <template #default="{ row }">{{ formatMoney(row.thanh_tien) }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="ghi_chu" label="Ghi chú" min-width="160">
              <template #default="{ row }">{{ display(row.ghi_chu) }}</template>
            </CustomTableColumn>
          </CustomTable>
          <div v-else class="detail-empty">Chưa có combo.</div>
        </section>

        <section class="detail-section">
          <div class="detail-section__title">Dịch vụ lẻ</div>
          <CustomTable v-if="dichVuRows.length" :data="dichVuRows" stripe style="width: 100%">
            <CustomTableColumn label="STT" width="64" align="center">
              <template #default="{ $index }">{{ $index + 1 }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="ten" label="Tên dịch vụ" min-width="200" />
            <CustomTableColumn prop="so_luong" label="Số lượng" width="100" align="center" />
            <CustomTableColumn label="Thành tiền" width="140" align="right">
              <template #default="{ row }">{{ formatMoney(row.thanh_tien) }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="ghi_chu" label="Ghi chú" min-width="160">
              <template #default="{ row }">{{ display(row.ghi_chu) }}</template>
            </CustomTableColumn>
          </CustomTable>
          <div v-else class="detail-empty">Chưa có dịch vụ lẻ.</div>
        </section>

        <section class="detail-section">
          <div class="detail-section__title">Concept</div>
          <CustomTable v-if="conceptRows.length" :data="conceptRows" stripe style="width: 100%">
            <CustomTableColumn label="STT" width="64" align="center">
              <template #default="{ $index }">{{ $index + 1 }}</template>
            </CustomTableColumn>
            <CustomTableColumn prop="ten" label="Tên concept" min-width="200" />
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
            <CustomTableColumn label="Giá thuê" width="130" align="right">
              <template #default="{ row }">{{ formatMoney(row.gia_cho_thue) }}</template>
            </CustomTableColumn>
            <CustomTableColumn label="Ngày bắt đầu" width="130" align="center">
              <template #default="{ row }">{{ formatDate(row.ngay_bat_dau) }}</template>
            </CustomTableColumn>
            <CustomTableColumn label="Ngày kết thúc" width="130" align="center">
              <template #default="{ row }">{{ formatDate(row.ngay_ket_thuc) }}</template>
            </CustomTableColumn>
          </CustomTable>
          <div v-else class="detail-empty">Chưa có trang phục.</div>
        </section>

        <section class="detail-section">
          <div class="detail-section__title">Thanh toán</div>
          <CustomForm label-position="top">
            <CustomRow :gutter="16">
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Tổng tiền">
                  <CustomInput :model-value="formatMoney(hopDong.tong_tien)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Phát sinh">
                  <CustomInput :model-value="formatMoney(hopDong.phat_sinh)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Chiết khấu">
                  <CustomInput :model-value="formatMoney(hopDong.chiet_khau)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Mã giảm giá">
                  <CustomInput :model-value="display(hopDong.ma_giam_gia)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Số tiền giảm giá">
                  <CustomInput
                    :model-value="formatMoney(hopDong.khuyen_mai_theo_ma_giam_gia)"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Khách phải thanh toán">
                  <CustomInput :model-value="formatMoney(khachPhaiThanhToan)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Tiền cọc">
                  <CustomInput :model-value="formatMoney(hopDong.tien_coc)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Hình thức cọc">
                  <CustomInput
                    :model-value="hinhThucCocLabel(hopDong.hinh_thuc_coc)"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Thanh toán lần 1">
                  <CustomInput
                    :model-value="formatMoney(hopDong.so_tien_thanh_toan_lan_1)"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Thời gian TT lần 1">
                  <CustomInput
                    :model-value="formatPaymentDateTime(hopDong.thoi_gian_thanh_toan_lan_1)"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Thanh toán lần 2">
                  <CustomInput
                    :model-value="formatMoney(hopDong.so_tien_thanh_toan_lan_2)"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Thời gian TT lần 2">
                  <CustomInput
                    :model-value="formatPaymentDateTime(hopDong.thoi_gian_thanh_toan_lan_2)"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Hạn TT lần 2">
                  <CustomInput
                    :model-value="formatDate(hopDong.han_thanh_toan_lan_2)"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Thanh toán lần 3">
                  <CustomInput
                    :model-value="formatMoney(hopDong.so_tien_thanh_toan_lan_3)"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Thời gian TT lần 3">
                  <CustomInput
                    :model-value="formatPaymentDateTime(hopDong.thoi_gian_thanh_toan_lan_3)"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="fieldColProps">
                <CustomFormItem label="Hạn TT lần 3">
                  <CustomInput
                    :model-value="formatDate(hopDong.han_thanh_toan_lan_3)"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
              <CustomCol v-bind="wideColProps">
                <CustomFormItem label="Quà tặng kèm">
                  <CustomInput :model-value="display(hopDong.qua_tang_kem)" readonly />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :span="24">
                <CustomFormItem label="Yêu cầu đặc biệt">
                  <CustomInput
                    :model-value="display(hopDong.yeu_cau_dac_biet)"
                    type="textarea"
                    :rows="3"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
            </CustomRow>
          </CustomForm>
        </section>

        <section v-if="dieuPhoiItems.length" class="detail-section">
          <div class="detail-section__title">Thông tin điều phối</div>
          <CustomForm label-position="top">
            <CustomRow v-if="dieuPhoiNormalItems.length" :gutter="16">
              <CustomCol
                v-for="item in dieuPhoiNormalItems"
                :key="item.key"
                v-bind="fieldColProps"
              >
                <CustomFormItem :label="item.label">
                  <CustomInput :model-value="item.value" readonly />
                </CustomFormItem>
              </CustomCol>
            </CustomRow>

            <CustomRow v-if="dieuPhoiTextareaItems.length" :gutter="16">
              <CustomCol
                v-for="item in dieuPhoiTextareaItems"
                :key="item.key"
                v-bind="textareaColProps"
              >
                <CustomFormItem :label="item.label">
                  <CustomInput
                    :model-value="item.value"
                    type="textarea"
                    :rows="3"
                    readonly
                  />
                </CustomFormItem>
              </CustomCol>
            </CustomRow>
          </CustomForm>
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
import { getLoaiHopDong } from '@/api/loaiHopDong'
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

const STAFF_FIELD_KEYS = new Set(['tho_chup', 'tho_make', 'tho_edit', 'quay_phim'])

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
const textareaColProps = { xs: 24, sm: 12, md: 12, lg: 12, xl: 12 }

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
const loaiHopDong = ref(null)
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

const nguoiThamGiaLabel = computed(() => {
  const ids = Array.isArray(hopDong.value?.nguoi_tham_gia_ids)
    ? hopDong.value.nguoi_tham_gia_ids
    : []
  if (!ids.length) return '—'
  return ids
    .map((id) => userNameMap.value.get(id) || `#${id}`)
    .join(', ')
})

const thongTinHopDongItems = computed(() => {
  const data =
    hopDong.value?.thong_tin_hop_dong &&
    typeof hopDong.value.thong_tin_hop_dong === 'object' &&
    !Array.isArray(hopDong.value.thong_tin_hop_dong)
      ? hopDong.value.thong_tin_hop_dong
      : {}

  const fields = Array.isArray(loaiHopDong.value?.noi_dung?.truong)
    ? loaiHopDong.value.noi_dung.truong
    : []

  if (fields.length) {
    return fields
      .filter((field) => field?.key)
      .map((field) => ({
        key: field.key,
        label: field.ten_truong || field.key,
        wide: field.kieu === 'textarea',
        value: formatDynamicValue(data[field.key], field.kieu),
      }))
  }

  return Object.entries(data).map(([key, value]) => ({
    key,
    label: key,
    wide: false,
    value: formatDynamicValue(value),
  }))
})

const comboRows = computed(() => {
  const rows = Array.isArray(hopDong.value?.combos) ? hopDong.value.combos : []
  return rows.map((row) => ({
    ten: row.combo?.ten_nhom || `Combo #${row.combo_id}`,
    so_luong: Number(row.so_luong) || 0,
    thanh_tien: Number(row.thanh_tien) || 0,
    ghi_chu: row.ghi_chu || '',
  }))
})

const dichVuRows = computed(() => {
  const rows = Array.isArray(hopDong.value?.dich_vu) ? hopDong.value.dich_vu : []
  return rows.map((row) => ({
    ten: row.dich_vu?.ten_dich_vu || `Dịch vụ #${row.dich_vu_id}`,
    so_luong: Number(row.so_luong) || 0,
    thanh_tien: Number(row.thanh_tien) || 0,
    ghi_chu: row.ghi_chu || '',
  }))
})

const conceptRows = computed(() => {
  const rows = Array.isArray(hopDong.value?.concepts) ? hopDong.value.concepts : []
  return rows.map((row) => ({
    ten: row.concept?.ten_concept || `Concept #${row.concept_id}`,
    dia_diem: row.concept?.dia_diem || '',
  }))
})

const trangPhucRows = computed(() => {
  const rows = Array.isArray(hopDong.value?.trang_phucs) ? hopDong.value.trang_phucs : []
  return rows.map((row) => ({
    ten: row.trang_phuc?.ten_san_pham || `Trang phục #${row.trang_phuc_id}`,
    gia_cho_thue: Number(row.trang_phuc?.gia_cho_thue) || 0,
    ngay_bat_dau: row.ngay_bat_dau || null,
    ngay_ket_thuc: row.ngay_ket_thuc || null,
  }))
})

const khachPhaiThanhToan = computed(() => {
  const tong = Number(hopDong.value?.tong_tien) || 0
  const phatSinh = Number(hopDong.value?.phat_sinh) || 0
  const chietKhau = Number(hopDong.value?.chiet_khau) || 0
  const giamGia = Number(hopDong.value?.khuyen_mai_theo_ma_giam_gia) || 0
  return Math.max(0, tong + phatSinh - chietKhau - giamGia)
})

const dieuPhoiItems = computed(() => {
  const saved =
    hopDong.value?.thong_tin_dieu_phoi &&
    typeof hopDong.value.thong_tin_dieu_phoi === 'object' &&
    !Array.isArray(hopDong.value.thong_tin_dieu_phoi)
      ? hopDong.value.thong_tin_dieu_phoi
      : {}

  const schema =
    loaiHopDong.value?.thong_tin_dieu_phoi &&
    typeof loaiHopDong.value.thong_tin_dieu_phoi === 'object' &&
    !Array.isArray(loaiHopDong.value.thong_tin_dieu_phoi)
      ? loaiHopDong.value.thong_tin_dieu_phoi
      : {}

  const keys = Object.keys(schema).length ? Object.keys(schema) : Object.keys(saved)
  const items = []

  for (const key of keys) {
    const schemaItem = schema[key] && typeof schema[key] === 'object' ? schema[key] : null
    const savedItem = saved[key] && typeof saved[key] === 'object' ? saved[key] : null
    if (schemaItem?.su_dung === false && !savedItem) continue

    const loai = savedItem?.loai_du_lieu || schemaItem?.loai_du_lieu || 'string'
    const label = savedItem?.ten_thong_tin || schemaItem?.ten_thong_tin || key
    const raw =
      savedItem?.gia_tri !== undefined
        ? savedItem.gia_tri
        : schemaItem?.gia_tri !== undefined
          ? schemaItem.gia_tri
          : null

    items.push({
      key,
      label,
      wide: loai === 'textarea',
      value: formatDieuPhoiValue(key, loai, raw),
    })
  }

  return items
})

const dieuPhoiNormalItems = computed(() =>
  dieuPhoiItems.value.filter((item) => !item.wide),
)

const dieuPhoiTextareaItems = computed(() =>
  dieuPhoiItems.value.filter((item) => item.wide),
)

function display(value) {
  if (value == null || value === '') return '—'
  return value
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
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString('vi-VN')
}

/** Hiển thị thời gian thanh toán: hh:mm:ss dd/mm/yyyy */
function formatPaymentDateTime(value) {
  if (!value) return '—'
  if (typeof value === 'string') {
    const trimmed = value.trim()
    if (/^\d{2}:\d{2}:\d{2} \d{2}\/\d{2}\/\d{4}$/.test(trimmed)) return trimmed
  }
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  const pad = (n) => String(n).padStart(2, '0')
  return `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())} ${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()}`
}

function trangThaiLabel(value) {
  return TRANG_THAI_OPTIONS.find((opt) => opt.value === value)?.label || value || '—'
}

function hinhThucCocLabel(value) {
  if (value === 'online') return 'Online'
  if (value === 'offline') return 'Offline'
  return display(value)
}

function formatDynamicValue(value, kieu = '') {
  if (value == null || value === '') return '—'
  if (typeof value === 'boolean' || kieu === 'checkbox' || kieu === 'switch') {
    return value ? 'Có' : 'Không'
  }
  if (Array.isArray(value) || kieu === 'checkbox_group') {
    return value.length ? value.join(', ') : '—'
  }
  if (kieu === 'money') return formatMoney(value)
  if (kieu === 'date' || kieu === 'datetime' || kieu === 'daterange') {
    if (Array.isArray(value)) {
      return value.map((item) => formatDate(item)).join(' → ')
    }
    return formatDate(value)
  }
  if (kieu === 'time') return String(value)
  return String(value)
}

function formatDieuPhoiValue(key, loai, value) {
  if (value == null || value === '') return '—'
  if (loai === 'date') return formatDate(value)
  if (loai === 'time') return String(value)
  if (loai === 'array') {
    const list = Array.isArray(value) ? value : [value]
    if (!list.length) return '—'
    if (STAFF_FIELD_KEYS.has(key)) {
      return list
        .map((id) => userNameMap.value.get(Number(id)) || userNameMap.value.get(id) || `#${id}`)
        .join(', ')
    }
    return list.map((item) => String(item)).join(', ')
  }
  return String(value)
}

async function loadData() {
  if (!props.hopDongId) return

  loading.value = true
  hopDong.value = null
  loaiHopDong.value = null

  try {
    const [hopDongRes, usersRes] = await Promise.all([
      getHopDongSuDungDichVu(props.hopDongId),
      fetchUsers({ per_page: 100, status: 'active' }),
    ])
    hopDong.value = hopDongRes.data
    userOptions.value = usersRes.data.data || []

    const loaiId = hopDong.value?.loai_hop_dong_id
    if (loaiId) {
      try {
        const { data } = await getLoaiHopDong(loaiId)
        loaiHopDong.value = data
      } catch {
        loaiHopDong.value = hopDong.value?.loai_hop_dong || null
      }
    }
  } catch {
    hopDong.value = null
    loaiHopDong.value = null
    userOptions.value = []
  } finally {
    loading.value = false
  }
}

function onClosed() {
  hopDong.value = null
  loaiHopDong.value = null
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
