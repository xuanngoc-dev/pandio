<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="isEditRole ? 1100 : 1220"
  >
    <div class="san-xuat-chi-tiet">
      <p class="hint">{{ hintText }}</p>

      <CustomTable
        :data="rows"
        stripe
        border
        row-key="row_key"
        show-summary
        :summary-method="getSummaries"
        style="width: 100%"
        class="detail-table"
        :empty-text="emptyText"
      >
        <CustomTableColumn label="STT" width="56" align="center">
          <template #default="{ $index }">
            {{ $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="ma_hop_dong" label="Mã HĐ" min-width="150" />
        <CustomTableColumn prop="ten_khach_hang" label="Khách hàng" min-width="140" show-overflow-tooltip />

        <template v-if="isEditRole">
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
          <CustomTableColumn label="Combo" min-width="220" show-overflow-tooltip>
            <template #default="{ row }">
              {{ formatComboSummary(row.combos) }}
            </template>
          </CustomTableColumn>
        </template>

        <template v-else>
          <CustomTableColumn prop="ten_buoi" label="Buổi chụp" min-width="130" show-overflow-tooltip />
          <CustomTableColumn prop="ngay_chup" label="Ngày chụp" width="110" align="center">
            <template #default="{ row }">
              {{ formatFullDate(row.ngay_chup) }}
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
          <CustomTableColumn prop="don_gia" label="Đơn giá" min-width="120" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.don_gia) }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="thanh_tien" label="Thành tiền" min-width="120" align="right">
            <template #default="{ row }">
              {{ formatMoney(row.thanh_tien) }}
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
import {
  CustomButton,
  CustomDialog,
  CustomTable,
  CustomTableColumn,
} from '@/components/element'

const ROLE_LABELS = {
  make: 'Make',
  chup: 'Chụp',
  quay_phim: 'Quay phim',
  edit: 'Edit',
}

const visible = ref(false)
const ngay = ref('')
const role = ref('make')
const items = ref([])

const roleLabel = computed(() => ROLE_LABELS[role.value] || role.value)

const isEditRole = computed(() => role.value === 'edit')

const dialogTitle = computed(() => {
  const datePart = formatFullDate(ngay.value)
  return `Chi tiết lương ${roleLabel.value} · ${datePart}`
})

const hintText = computed(() => {
  const datePart = formatFullDate(ngay.value)
  if (isEditRole.value) {
    return (
      `Các hợp đồng hoàn tất sản xuất ngày ${datePart} mà bạn được gán thợ edit. ` +
      'Thành tiền = tổng số ảnh chỉnh sửa (từ combo HĐ) × lương chỉnh sửa ảnh trong hồ sơ.'
    )
  }
  return (
    `Các buổi chụp được gán vai trò ${roleLabel.value.toLowerCase()} trong hợp đồng ` +
    `hoàn tất sản xuất ngày ${datePart}. ` +
    'Thành tiền = đơn giá theo loại quay chụp và số điểm trong hồ sơ lương.'
  )
})

const emptyText = computed(() =>
  isEditRole.value
    ? 'Không có hợp đồng nào cho khoản edit'
    : 'Không có buổi chụp nào cho khoản này',
)

const rows = computed(() =>
  (items.value || []).map((item, index) => ({
    ...item,
    row_key: isEditRole.value
      ? `edit-${item.hop_dong_id}-${index}`
      : `${item.hop_dong_id}-${item.buoi_index}-${index}`,
  })),
)

function formatFullDate(value) {
  if (!value) return '—'
  const [y, m, d] = String(value).slice(0, 10).split('-')
  if (!y || !m || !d) return '—'
  return `${d}/${m}/${y}`
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

function formatComboSummary(combos) {
  if (!Array.isArray(combos) || combos.length === 0) return '—'
  return combos
    .map((c) => {
      const name = c.ten_nhom || c.ma_nhom || `#${c.combo_id || '?'}`
      const qty = Number(c.so_luong) || 1
      const anh = Number(c.so_anh_chinh_sua) || 0
      return `${name} (×${qty}, ${anh} ảnh)`
    })
    .join('; ')
}

function getSummaries({ columns }) {
  const total = rows.value.reduce((sum, row) => sum + (Number(row.thanh_tien) || 0), 0)
  const totalAnh = isEditRole.value
    ? rows.value.reduce((sum, row) => sum + (Number(row.so_anh_chinh_sua) || 0), 0)
    : 0

  return columns.map((column, index) => {
    if (index === 0) return ''
    if (column.property === 'ma_hop_dong') return 'Tổng'
    if (column.property === 'so_anh_chinh_sua') return formatNumber(totalAnh)
    if (column.property === 'thanh_tien') return formatMoney(total)
    return ''
  })
}

/**
 * @param {{ ngay: string, role: 'make'|'chup'|'quay_phim'|'edit', items?: array }} payload
 */
function open(payload) {
  ngay.value = payload?.ngay || ''
  role.value = payload?.role || 'make'
  items.value = Array.isArray(payload?.items) ? payload.items : []
  visible.value = true
}

defineExpose({ open })
</script>

<style scoped lang="scss">
.san-xuat-chi-tiet {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.hint {
  margin: 0;
  font-size: 13px;
  line-height: 1.45;
  color: var(--el-text-color-secondary);
}

.detail-table {
  :deep(.el-table__footer-wrapper td) {
    font-weight: 700;
    background: var(--el-fill-color-light);
  }
}
</style>
