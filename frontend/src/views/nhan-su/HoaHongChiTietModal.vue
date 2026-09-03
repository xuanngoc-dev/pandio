<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1220"
  >
    <div class="hoa-hong-chi-tiet">
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
        <CustomTableColumn prop="vai_tro" label="Vai trò" width="130" align="center" />
        <CustomTableColumn prop="ngay_tao" label="Ngày tạo" width="110" align="center">
          <template #default="{ row }">
            {{ formatFullDate(row.ngay_tao) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="gia_tri_hop_dong" :label="giaTriLabel" min-width="130" align="right">
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

const TYPE_META = {
  hd_tp: {
    title: 'Hoa hồng HĐ trang phục',
    giaTriLabel: 'Thành tiền HĐ',
    emptyText: 'Không có hợp đồng trang phục nào trong ngày',
    hint: (date) =>
      `Các HĐ trang phục tạo ngày ${date} mà bạn là người cho thuê hoặc người tham gia. ` +
      'Hoa hồng = thành tiền × tỷ lệ % trong hồ sơ lương.',
  },
  hd_sddv: {
    title: 'Hoa hồng HĐ sử dụng dịch vụ',
    giaTriLabel: 'Tổng tiền HĐ',
    emptyText: 'Không có hợp đồng SDDV nào trong ngày',
    hint: (date) =>
      `Các HĐ sử dụng dịch vụ tạo ngày ${date} mà bạn là người tạo hoặc người tham gia. ` +
      'Hoa hồng = tổng tiền × tỷ lệ % trong hồ sơ lương.',
  },
}

const visible = ref(false)
const ngay = ref('')
const type = ref('hd_tp')
const items = ref([])

const meta = computed(() => TYPE_META[type.value] || TYPE_META.hd_tp)

const dialogTitle = computed(() => `${meta.value.title} · ${formatFullDate(ngay.value)}`)

const giaTriLabel = computed(() => meta.value.giaTriLabel)

const emptyText = computed(() => meta.value.emptyText)

const hintText = computed(() => meta.value.hint(formatFullDate(ngay.value)))

const rows = computed(() =>
  (items.value || []).map((item, index) => ({
    ...item,
    row_key: `${item.hop_dong_id}-${index}`,
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

function formatPercent(value) {
  const num = Number(value)
  if (!Number.isFinite(num)) return '—'
  return `${num.toLocaleString('vi-VN', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })}%`
}

function getSummaries({ columns }) {
  const total = rows.value.reduce((sum, row) => sum + (Number(row.hoa_hong) || 0), 0)
  return columns.map((column, index) => {
    if (index === 0) return ''
    if (column.property === 'ma_hop_dong') return 'Tổng'
    if (column.property === 'hoa_hong') return formatMoney(total)
    return ''
  })
}

/**
 * @param {{ ngay: string, type: 'hd_tp'|'hd_sddv', items?: array }} payload
 */
function open(payload) {
  ngay.value = payload?.ngay || ''
  type.value = payload?.type === 'hd_sddv' ? 'hd_sddv' : 'hd_tp'
  items.value = Array.isArray(payload?.items) ? payload.items : []
  visible.value = true
}

defineExpose({ open })
</script>

<style scoped lang="scss">
.hoa-hong-chi-tiet {
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
