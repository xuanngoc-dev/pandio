<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1220"
  >
    <div class="san-xuat-chi-tiet">
      <p class="hint">
        Các buổi chụp được gán vai trò {{ roleLabel.toLowerCase() }} trong hợp đồng
        hoàn tất sản xuất ngày {{ formatFullDate(ngay) }}.
        Thành tiền = đơn giá theo loại quay chụp và số điểm trong hồ sơ lương.
      </p>

      <CustomTable
        :data="rows"
        stripe
        border
        row-key="row_key"
        show-summary
        :summary-method="getSummaries"
        style="width: 100%"
        class="detail-table"
        empty-text="Không có buổi chụp nào cho khoản này"
      >
        <CustomTableColumn label="STT" width="56" align="center">
          <template #default="{ $index }">
            {{ $index + 1 }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="ma_hop_dong" label="Mã HĐ" min-width="150" />
        <CustomTableColumn prop="ten_khach_hang" label="Khách hàng" min-width="140" show-overflow-tooltip />
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
}

const visible = ref(false)
const ngay = ref('')
const role = ref('make')
const items = ref([])

const roleLabel = computed(() => ROLE_LABELS[role.value] || role.value)

const dialogTitle = computed(() => {
  const datePart = formatFullDate(ngay.value)
  return `Chi tiết lương ${roleLabel.value} · ${datePart}`
})

const rows = computed(() =>
  (items.value || []).map((item, index) => ({
    ...item,
    row_key: `${item.hop_dong_id}-${item.buoi_index}-${index}`,
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

function getSummaries({ columns }) {
  const total = rows.value.reduce((sum, row) => sum + (Number(row.thanh_tien) || 0), 0)
  return columns.map((column, index) => {
    if (index === 0) return ''
    if (column.property === 'ma_hop_dong') return 'Tổng'
    if (column.property === 'thanh_tien') return formatMoney(total)
    return ''
  })
}

/**
 * @param {{ ngay: string, role: 'make'|'chup'|'quay_phim', items?: array }} payload
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
