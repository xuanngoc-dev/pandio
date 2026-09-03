<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="dialogWidth"
    class="luong-khoan-muc-dialog"
    @closed="onClosed"
  >
    <div v-loading="loading" class="khoan-muc-chi-tiet">
      <p class="hint">{{ hintText }}</p>

      <CustomTable
        :key="`${kind}-${itemKey}`"
        :data="rows"
        stripe
        border
        row-key="row_key"
        show-summary
        :summary-method="getSummaries"
        style="width: 100%"
        class="detail-table"
        :empty-text="loading ? 'Đang tải...' : emptyText"
      >
        <CustomTableColumn label="STT" width="56" align="center">
          <template #default="{ $index }">
            {{ $index + 1 }}
          </template>
        </CustomTableColumn>

        <template v-if="kind === 'fixed' || kind === 'group_total' || kind === 'extra_penalty'">
          <CustomTableColumn prop="label" label="Khoản mục" min-width="220" />
          <CustomTableColumn prop="nguon" label="Nguồn" min-width="180" show-overflow-tooltip />
          <CustomTableColumn prop="so_tien" label="Số tiền" min-width="140" align="right">
            <template #default="{ row }">
              <span :class="{ 'money-danger': danger }">{{ formatMoney(row.so_tien) }}</span>
            </template>
          </CustomTableColumn>
        </template>

        <template v-else-if="kind === 'hoa_hong'">
          <CustomTableColumn prop="ma_hop_dong" label="Mã HĐ" min-width="150" />
          <CustomTableColumn prop="ten_khach_hang" label="Khách hàng" min-width="140" show-overflow-tooltip />
          <CustomTableColumn prop="vai_tro" label="Vai trò" width="130" align="center" />
          <CustomTableColumn prop="ngay_tao" label="Ngày tạo" width="110" align="center">
            <template #default="{ row }">
              {{ formatFullDate(row.ngay_tao) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="gia_tri_hop_dong" :label="hoaHongGiaTriLabel" min-width="130" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.gia_tri_hop_dong) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="ty_le" label="Tỷ lệ %" width="90" align="center">
            <template #default="{ row }">
              {{ formatPercent(row.ty_le) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="hoa_hong" label="Hoa hồng" min-width="120" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.hoa_hong) }}
            </template>
          </CustomTableColumn>
        </template>

        <template v-else-if="kind === 'san_xuat_edit'">
          <CustomTableColumn prop="ma_hop_dong" label="Mã HĐ" min-width="150" />
          <CustomTableColumn prop="ten_khach_hang" label="Khách hàng" min-width="140" show-overflow-tooltip />
          <CustomTableColumn prop="ngay" label="Ngày HT SX" width="120" align="center">
            <template #default="{ row }">
              {{ formatFullDate(row.ngay) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="so_anh_chinh_sua" label="Số ảnh CS" width="110" align="center">
            <template #default="{ row }">
              {{ formatNumber(row.so_anh_chinh_sua) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="don_gia" label="Đơn giá / ảnh" min-width="130" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.don_gia) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="thanh_tien" label="Thành tiền" min-width="130" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.thanh_tien) }}
            </template>
          </CustomTableColumn>
        </template>

        <template v-else-if="kind === 'san_xuat'">
          <CustomTableColumn prop="ma_hop_dong" label="Mã HĐ" min-width="150" />
          <CustomTableColumn prop="ten_khach_hang" label="Khách hàng" min-width="140" show-overflow-tooltip />
          <CustomTableColumn prop="ten_buoi" label="Buổi chụp" min-width="130" show-overflow-tooltip />
          <CustomTableColumn prop="ngay_chup" label="Ngày chụp" width="110" align="center">
            <template #default="{ row }">
              {{ formatFullDate(row.ngay_chup) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="ngay" label="Ngày HT SX" width="120" align="center">
            <template #default="{ row }">
              {{ formatFullDate(row.ngay) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="loai_quay_chup_ten" label="Loại quay chụp" min-width="130" show-overflow-tooltip>
            <template #default="{ row }">
              {{ row.loai_quay_chup_ten || (row.loai_quay_chup_id ? `#${row.loai_quay_chup_id}` : '—') }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="so_diem_chup" label="Điểm" width="72" align="center">
            <template #default="{ row }">
              {{ row.so_diem_chup > 0 ? row.so_diem_chup : '—' }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="thanh_tien" label="Thành tiền" min-width="120" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.thanh_tien) }}
            </template>
          </CustomTableColumn>
        </template>

        <template v-else-if="kind === 'chuyen_can'">
          <CustomTableColumn prop="ngay" label="Ngày nghỉ" width="140" align="center">
            <template #default="{ row }">
              {{ formatFullDate(row.ngay) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="thu" label="Thứ" width="80" align="center" />
          <CustomTableColumn prop="ghi_chu" label="Ghi chú" min-width="220" show-overflow-tooltip />
          <CustomTableColumn prop="so_tien" label="Thưởng" min-width="140" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.so_tien) }}
            </template>
          </CustomTableColumn>
        </template>

        <template v-else>
          <CustomTableColumn prop="ngay" label="Ngày" width="140" align="center">
            <template #default="{ row }">
              <span :class="{ 'day-weekend': row.is_weekend }">
                {{ row.thu }}-{{ formatDateLabel(row.ngay) }}
              </span>
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="gio_vao" label="Giờ vào" width="100" align="center">
            <template #default="{ row }">
              {{ formatTime(row.gio_vao) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="gio_ra" label="Giờ ra" width="100" align="center">
            <template #default="{ row }">
              {{ formatTime(row.gio_ra) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-if="kind === 'hourly'"
            prop="gio_lam_co_ban"
            label="Giờ làm"
            width="100"
            align="center"
          >
            <template #default="{ row }">
              {{ formatHours(row.gio_lam_co_ban) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-else-if="kind === 'overtime'"
            prop="gio_lam_tang_ca"
            label="Giờ tăng ca"
            width="110"
            align="center"
          >
            <template #default="{ row }">
              {{ formatHours(row.gio_lam_tang_ca) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-else-if="kind === 'late'"
            prop="thoi_gian_di_muon"
            label="Đi muộn"
            width="110"
            align="center"
          >
            <template #default="{ row }">
              {{ formatMinutes(row.thoi_gian_di_muon) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-else-if="kind === 'early'"
            prop="thoi_gian_ve_som"
            label="Về sớm"
            width="110"
            align="center"
          >
            <template #default="{ row }">
              {{ formatMinutes(row.thoi_gian_ve_som) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn
            v-else-if="kind === 'weekend'"
            prop="don_gia"
            label="Đơn giá / ngày"
            min-width="130"
            align="right"
          >
            <template #default="{ row }">
              {{ formatMoney(row.don_gia) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="so_tien" :label="amountColumnLabel" min-width="140" align="right">
            <template #default="{ row }">
              <span :class="{ 'money-danger': danger }">{{ formatMoney(row.so_tien) }}</span>
            </template>
          </CustomTableColumn>
        </template>
      </CustomTable>
    </div>

    <template #footer>
      <CustomButton @click="visible = false">Đóng</CustomButton>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, ref } from 'vue'
import { fetchBangLuongChiTietTheoNgayNhanVien } from '@/api/tinhLuong'
import {
  CustomButton,
  CustomDialog,
  CustomTable,
  CustomTableColumn,
} from '@/components/element'

defineOptions({ name: 'LuongKhoanMucChiTietModal' })

const DOW_LABELS = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']

const GROUP_A_ITEMS = [
  { key: 'luong_cung', label: 'Lương cứng', source: 'luong_co_dinh' },
  { key: 'luong_mem', label: 'Lương mềm', source: 'luong_co_dinh' },
  { key: 'phu_cap', label: 'Phụ cấp', source: 'phu_cap' },
  { key: 'phu_cap_xang', label: 'Phụ cấp xăng', source: 'phu_cap' },
  { key: 'phu_cap_an_trua', label: 'Phụ cấp ăn trưa', source: 'phu_cap' },
  { key: 'phu_cap_dien_thoai', label: 'Phụ cấp điện thoại', source: 'phu_cap' },
  { key: 'phu_cap_nha_o', label: 'Phụ cấp nhà ở', source: 'phu_cap' },
]

const GROUP_B_ITEMS = [
  { key: 'tong_luong_theo_gio', label: 'Tổng lương theo giờ' },
  { key: 'tong_tang_ca', label: 'Tổng tăng ca' },
  { key: 'hoa_hong_hd_tp', label: 'Hoa hồng HĐ TP' },
  { key: 'hoa_hong_hd_sddv', label: 'Hoa hồng HĐ SDDV' },
  { key: 'san_xuat_make', label: 'Lương make' },
  { key: 'san_xuat_chup', label: 'Lương chụp' },
  { key: 'san_xuat_quay_phim', label: 'Lương quay phim' },
  { key: 'san_xuat_edit', label: 'Lương edit' },
  { key: 'phu_cap_thu_bay_va_chu_nhat', label: 'Phụ cấp thứ 7 và chủ nhật' },
  { key: 'thuong_chuyen_can', label: 'Thưởng chuyên cần' },
]

const GROUP_C_ITEMS = [
  { key: 'tien_phat_di_muon', label: 'Tiền đi muộn' },
  { key: 'tien_phat_ve_som', label: 'Tiền về sớm' },
  { key: 'phat_phat_sinh', label: 'Phạt phát sinh' },
]

const KIND_BY_KEY = {
  luong_cung: 'fixed',
  luong_mem: 'fixed',
  phu_cap: 'fixed',
  phu_cap_xang: 'fixed',
  phu_cap_an_trua: 'fixed',
  phu_cap_dien_thoai: 'fixed',
  phu_cap_nha_o: 'fixed',
  tong: 'group_total',
  tong_luong_theo_gio: 'hourly',
  tong_tang_ca: 'overtime',
  hoa_hong_hd_tp: 'hoa_hong',
  hoa_hong_hd_sddv: 'hoa_hong',
  san_xuat_make: 'san_xuat',
  san_xuat_chup: 'san_xuat',
  san_xuat_quay_phim: 'san_xuat',
  san_xuat_edit: 'san_xuat_edit',
  phu_cap_thu_bay_va_chu_nhat: 'weekend',
  thuong_chuyen_can: 'chuyen_can',
  tien_phat_di_muon: 'late',
  tien_phat_ve_som: 'early',
  phat_phat_sinh: 'extra_penalty',
}

const payloadCache = new Map()

const visible = ref(false)
const loading = ref(false)
const payload = ref(null)
const month = ref('')
const employeeName = ref('')
const groupCode = ref('A')
const groupLabel = ref('')
const itemKey = ref('')
const itemLabel = ref('')
const danger = ref(false)

const kind = computed(() => KIND_BY_KEY[itemKey.value] || 'hourly')

const dialogWidth = computed(() => {
  if (kind.value === 'hoa_hong' || kind.value === 'san_xuat' || kind.value === 'san_xuat_edit') return 1220
  if (kind.value === 'fixed' || kind.value === 'group_total') return 720
  return 920
})

const dialogTitle = computed(() => {
  const name = employeeName.value || payload.value?.nhan_vien?.name || ''
  const monthLabel = formatMonthLabel(month.value)
  const parts = [itemLabel.value || 'Chi tiết khoản', name, monthLabel].filter(Boolean)
  return parts.join(' · ')
})

const hoaHongGiaTriLabel = computed(() =>
  itemKey.value === 'hoa_hong_hd_sddv' ? 'Tổng tiền HĐ' : 'Thành tiền HĐ',
)

const amountColumnLabel = computed(() => {
  if (kind.value === 'late' || kind.value === 'early' || kind.value === 'extra_penalty') return 'Số tiền phạt'
  return 'Thành tiền'
})

const emptyText = computed(() => {
  if (kind.value === 'late') return 'Không có ngày đi muộn trong tháng'
  if (kind.value === 'early') return 'Không có ngày về sớm trong tháng'
  if (kind.value === 'extra_penalty') return 'Chưa ghi nhận phạt phát sinh theo ngày'
  if (kind.value === 'hourly') return 'Không có ngày tính lương theo giờ'
  if (kind.value === 'overtime') return 'Không có ngày tăng ca'
  if (kind.value === 'weekend') return 'Không có ngày làm thứ 7 / chủ nhật'
  if (kind.value === 'chuyen_can') return 'Không có ngày nghỉ tính chuyên cần'
  if (kind.value === 'hoa_hong') return 'Không có hợp đồng hoa hồng trong tháng'
  if (kind.value === 'san_xuat' || kind.value === 'san_xuat_edit') return 'Không có khoản sản xuất trong tháng'
  return 'Chưa có dữ liệu'
})

const hintText = computed(() => {
  const monthLabel = formatMonthLabel(month.value)
  const group = groupLabel.value ? `Nhóm ${groupCode.value} · ${groupLabel.value}. ` : ''
  if (kind.value === 'late') {
    return `${group}Các ngày đi muộn trong tháng ${monthLabel}: giờ vào/ra, số phút đi muộn và tiền phạt.`
  }
  if (kind.value === 'early') {
    return `${group}Các ngày về sớm trong tháng ${monthLabel}: giờ vào/ra, số phút về sớm và tiền phạt.`
  }
  if (kind.value === 'hourly') {
    return `${group}Các ngày có lương theo giờ trong tháng ${monthLabel}.`
  }
  if (kind.value === 'overtime') {
    return `${group}Các ngày có tăng ca trong tháng ${monthLabel}.`
  }
  if (kind.value === 'weekend') {
    return `${group}Các ngày làm thứ 7 / chủ nhật trong tháng ${monthLabel}. Thành tiền = đơn giá phụ cấp × số ngày làm.`
  }
  if (kind.value === 'chuyen_can') {
    return `${group}Ngày nghỉ (trừ T7/CN và ngày lễ) dùng để tính thưởng chuyên cần tháng ${monthLabel}.`
  }
  if (kind.value === 'hoa_hong') {
    return `${group}Danh sách hợp đồng phát sinh hoa hồng trong tháng ${monthLabel}.`
  }
  if (kind.value === 'san_xuat' || kind.value === 'san_xuat_edit') {
    return `${group}Các buổi / hợp đồng tính lương sản xuất trong tháng ${monthLabel}.`
  }
  if (kind.value === 'fixed') {
    return `${group}Khoản cố định theo hồ sơ lương, áp dụng cả tháng ${monthLabel}.`
  }
  if (kind.value === 'group_total') {
    return `${group}Tổng các khoản trong nhóm tháng ${monthLabel}.`
  }
  if (kind.value === 'extra_penalty') {
    return `${group}Phạt phát sinh tháng ${monthLabel}. Hiện chưa gắn theo từng ngày điểm danh.`
  }
  return group
})

const rows = computed(() => buildRows(payload.value, itemKey.value, kind.value, groupCode.value))

function groupAValue(nv, item) {
  return Number(nv?.[item.source]?.[item.key] || 0)
}

function buildRows(data, key, rowKind, group) {
  if (!data) return []

  const nv = data.nhan_vien || {}
  const tong = data.tong_ket || {}
  const days = data.days || []

  if (rowKind === 'fixed') {
    const item = GROUP_A_ITEMS.find((row) => row.key === key)
    if (!item) return []
    return [
      {
        row_key: item.key,
        label: item.label,
        nguon: 'Hồ sơ lương nhân viên',
        so_tien: groupAValue(nv, item),
      },
    ]
  }

  if (rowKind === 'group_total') {
    if (group === 'A') {
      return GROUP_A_ITEMS.map((item) => ({
        row_key: item.key,
        label: item.label,
        nguon: 'Hồ sơ lương nhân viên',
        so_tien: groupAValue(nv, item),
      }))
    }
    if (group === 'B') {
      return GROUP_B_ITEMS.map((item) => ({
        row_key: item.key,
        label: item.label,
        nguon: 'Phát sinh trong tháng',
        so_tien: Number(tong[item.key] || 0),
      }))
    }
    return GROUP_C_ITEMS.map((item) => ({
      row_key: item.key,
      label: item.label,
      nguon: 'Khấu trừ trong tháng',
      so_tien: Number(tong[item.key] || 0),
    }))
  }

  if (rowKind === 'hoa_hong') {
    const type = key === 'hoa_hong_hd_sddv' ? 'hd_sddv' : 'hd_tp'
    return days.flatMap((day, dayIndex) =>
      (day.hoa_hong?.chi_tiet?.[type] || []).map((item, index) => ({
        ...item,
        row_key: `${type}-${day.ngay}-${item.hop_dong_id}-${dayIndex}-${index}`,
      })),
    )
  }

  if (rowKind === 'san_xuat' || rowKind === 'san_xuat_edit') {
    const role = key.replace('san_xuat_', '')
    return days.flatMap((day, dayIndex) =>
      (day.san_xuat?.chi_tiet?.[role] || []).map((item, index) => ({
        ...item,
        ngay: day.ngay,
        row_key: `${role}-${day.ngay}-${item.hop_dong_id}-${item.buoi_index ?? index}-${dayIndex}`,
      })),
    )
  }

  if (rowKind === 'chuyen_can') {
    const leaveDates = tong.danh_sach_ngay_nghi || []
    const bonus = Number(tong.thuong_chuyen_can || 0)
    if (nv.loai_nhan_vien !== 'full_time') {
      return [
        {
          row_key: 'chuyen-can-part-time',
          ngay: '',
          thu: '—',
          ghi_chu: 'Thưởng chuyên cần theo hồ sơ lương (part time)',
          so_tien: bonus,
        },
      ]
    }
    if (!leaveDates.length) {
      return [
        {
          row_key: 'chuyen-can-khong-nghi',
          ngay: '',
          thu: '—',
          ghi_chu: 'Không nghỉ ngày làm trong tháng (mức không nghỉ)',
          so_tien: bonus,
        },
      ]
    }
    return leaveDates.map((ngay) => ({
      row_key: `nghi-${ngay}`,
      ngay,
      thu: weekdayLabel(ngay),
      ghi_chu: 'Không điểm danh (ngày làm)',
      so_tien: 0,
    }))
  }

  if (rowKind === 'extra_penalty') {
    const amount = Number(tong.phat_phat_sinh || 0)
    if (!amount) return []
    return [
      {
        row_key: 'phat-phat-sinh',
        label: 'Phạt phát sinh',
        nguon: 'Chưa gắn theo ngày điểm danh',
        so_tien: amount,
      },
    ]
  }

  const weekendDonGia = weekendUnitAmount(tong)

  return days
    .filter((day) => {
      if (rowKind === 'hourly') return Number(day.luong_co_ban) > 0
      if (rowKind === 'overtime') return Number(day.luong_tang_ca) > 0
      if (rowKind === 'late') return Number(day.tien_phat_di_muon) > 0
      if (rowKind === 'early') return Number(day.tien_phat_ve_som) > 0
      if (rowKind === 'weekend') return day.is_weekend && day.co_diem_danh
      return false
    })
    .map((day) => ({
      row_key: `${rowKind}-${day.ngay}`,
      ngay: day.ngay,
      thu: day.thu,
      is_weekend: day.is_weekend,
      gio_vao: day.gio_vao,
      gio_ra: day.gio_ra,
      gio_lam_co_ban: day.gio_lam_co_ban,
      gio_lam_tang_ca: day.gio_lam_tang_ca,
      thoi_gian_di_muon: day.thoi_gian_di_muon,
      thoi_gian_ve_som: day.thoi_gian_ve_som,
      don_gia: weekendDonGia,
      so_tien: amountOfDay(day, rowKind, weekendDonGia),
    }))
}

function amountOfDay(day, rowKind, weekendDonGia) {
  if (rowKind === 'hourly') return Number(day.luong_co_ban) || 0
  if (rowKind === 'overtime') return Number(day.luong_tang_ca) || 0
  if (rowKind === 'late') return Number(day.tien_phat_di_muon) || 0
  if (rowKind === 'early') return Number(day.tien_phat_ve_som) || 0
  if (rowKind === 'weekend') return weekendDonGia
  return 0
}

function weekendUnitAmount(tong) {
  const count = Number(tong.so_ngay_lam_thu_bay_chu_nhat) || 0
  const total = Number(tong.phu_cap_thu_bay_va_chu_nhat) || 0
  if (count <= 0) return 0
  return total / count
}

function weekdayLabel(value) {
  if (!value) return '—'
  const date = new Date(`${String(value).slice(0, 10)}T00:00:00`)
  if (Number.isNaN(date.getTime())) return '—'
  return DOW_LABELS[date.getDay()] || '—'
}

function moneyTotal() {
  if (kind.value === 'chuyen_can') {
    return Number(payload.value?.tong_ket?.thuong_chuyen_can || 0)
  }

  return rows.value.reduce((sum, row) => {
    if (kind.value === 'hoa_hong') return sum + (Number(row.hoa_hong) || 0)
    if (kind.value === 'san_xuat' || kind.value === 'san_xuat_edit') {
      return sum + (Number(row.thanh_tien) || 0)
    }
    return sum + (Number(row.so_tien) || 0)
  }, 0)
}

function getSummaries({ columns }) {
  const total = moneyTotal()
  return columns.map((column, index) => {
    if (index === 0) return ''
    if (
      column.property === 'label'
      || column.property === 'ma_hop_dong'
      || column.property === 'ngay'
    ) {
      return 'Tổng'
    }
    if (
      column.property === 'so_tien'
      || column.property === 'hoa_hong'
      || column.property === 'thanh_tien'
    ) {
      return formatMoney(total)
    }
    if (column.property === 'so_anh_chinh_sua') {
      const totalAnh = rows.value.reduce((sum, row) => sum + (Number(row.so_anh_chinh_sua) || 0), 0)
      return formatNumber(totalAnh)
    }
    return ''
  })
}

function formatMonthLabel(value) {
  if (!value || !String(value).includes('-')) return '—'
  const [y, m] = String(value).split('-')
  return `${m}/${y}`
}

function formatFullDate(value) {
  if (!value) return '—'
  const [y, m, d] = String(value).slice(0, 10).split('-')
  if (!y || !m || !d) return '—'
  return `${d}/${m}/${y}`
}

function formatDateLabel(value) {
  if (!value) return '—'
  const [, m, d] = String(value).slice(0, 10).split('-')
  if (!m || !d) return '—'
  return `${d}/${m}`
}

function formatTime(value) {
  if (!value) return '—'
  const str = String(value)
  if (str.includes('T')) return str.slice(11, 16)
  if (str.includes(' ')) return str.slice(11, 16)
  return str.slice(0, 5)
}

function formatHours(value) {
  const num = Number(value)
  if (!Number.isFinite(num) || num <= 0) return '—'
  return num.toLocaleString('vi-VN', { maximumFractionDigits: 2 })
}

function formatMinutes(value) {
  const num = Number(value)
  if (!Number.isFinite(num) || num <= 0) return '—'
  return `${num.toLocaleString('vi-VN')} phút`
}

function formatMoney(value) {
  const num = Number(value)
  if (!Number.isFinite(num)) return '0 ₫'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function formatNumber(value) {
  const num = Number(value)
  if (!Number.isFinite(num)) return '0'
  return num.toLocaleString('vi-VN')
}

function formatPercent(value) {
  const num = Number(value)
  if (!Number.isFinite(num)) return '—'
  return `${num.toLocaleString('vi-VN', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })}%`
}

async function open({
  userId,
  thang,
  name,
  groupCode: nextGroup,
  groupLabel: nextGroupLabel,
  itemKey: nextKey,
  itemLabel: nextLabel,
  danger: nextDanger,
} = {}) {
  if (!userId || !thang || !nextKey) return

  visible.value = true
  month.value = String(thang)
  employeeName.value = name || ''
  groupCode.value = nextGroup || 'A'
  groupLabel.value = nextGroupLabel || ''
  itemKey.value = nextKey
  itemLabel.value = nextLabel || ''
  danger.value = Boolean(nextDanger)
  loading.value = true

  try {
    payload.value = await loadPayload(userId, month.value)
    if (!employeeName.value && payload.value?.nhan_vien?.name) {
      employeeName.value = payload.value.nhan_vien.name
    }
  } catch {
    payload.value = null
  } finally {
    loading.value = false
  }
}

async function loadPayload(userId, thang) {
  const cacheKey = `${userId}:${thang}`
  if (payloadCache.has(cacheKey)) return payloadCache.get(cacheKey)

  const { data } = await fetchBangLuongChiTietTheoNgayNhanVien(
    { user_id: userId, thang },
    { skipLoading: true },
  )
  payloadCache.set(cacheKey, data)
  return data
}

function onClosed() {
  payload.value = null
  month.value = ''
  employeeName.value = ''
  groupCode.value = 'A'
  groupLabel.value = ''
  itemKey.value = ''
  itemLabel.value = ''
  danger.value = false
  loading.value = false
}

defineExpose({ open })
</script>

<style scoped lang="scss">
.khoan-muc-chi-tiet {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-height: 160px;
}

.hint {
  margin: 0;
  font-size: 13px;
  line-height: 1.45;
  color: var(--el-text-color-secondary);
}

.money-danger {
  color: var(--el-color-danger);
}

.day-weekend {
  color: var(--el-color-danger);
  font-weight: 600;
}

.detail-table {
  :deep(.el-table__footer-wrapper td) {
    font-weight: 700;
    background: var(--el-fill-color-light);
  }
}
</style>
