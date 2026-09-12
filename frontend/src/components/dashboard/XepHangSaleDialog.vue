<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="820"
    class="xep-hang-sale-dialog"
    @closed="onClosed"
  >
    <p class="hint">{{ hintText }}</p>

    <CustomTable
      v-loading="loading"
      :data="rows"
      stripe
      border
      row-key="id"
      style="width: 100%"
      :empty-text="loading ? 'Đang tải...' : 'Chưa có dữ liệu trong kỳ'"
    >
      <CustomTableColumn label="Hạng" width="72" align="center">
        <template #default="{ $index }">
          <span class="rank-no" :class="rankClass(rankOf($index))">
            {{ rankOf($index) }}
          </span>
        </template>
      </CustomTableColumn>
      <CustomTableColumn prop="name" label="Nhân viên" min-width="180" show-overflow-tooltip />

      <template v-if="tieuChi === 'so_hd'">
        <CustomTableColumn prop="so_hd" label="Số HĐ" width="90" align="right">
          <template #default="{ row }">
            <strong>{{ formatCount(row.so_hd) }}</strong>
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="so_hd_sddv" label="HĐ SDDV" width="96" align="right">
          <template #default="{ row }">
            {{ formatCount(row.so_hd_sddv) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="so_hd_tp" label="HĐ TP" width="88" align="right">
          <template #default="{ row }">
            {{ formatCount(row.so_hd_tp) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="doanh_thu" label="Doanh thu" min-width="140" align="right">
          <template #default="{ row }">
            {{ formatMoney(row.doanh_thu) }}
          </template>
        </CustomTableColumn>
      </template>

      <template v-else>
        <CustomTableColumn prop="doanh_thu" label="Doanh thu" min-width="140" align="right">
          <template #default="{ row }">
            <strong>{{ formatMoney(row.doanh_thu) }}</strong>
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="so_hd" label="Số HĐ" width="90" align="right">
          <template #default="{ row }">
            {{ formatCount(row.so_hd) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="doanh_thu_sddv" label="DT SDDV" min-width="130" align="right">
          <template #default="{ row }">
            {{ formatMoney(row.doanh_thu_sddv) }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="doanh_thu_tp" label="DT TP" min-width="130" align="right">
          <template #default="{ row }">
            {{ formatMoney(row.doanh_thu_tp) }}
          </template>
        </CustomTableColumn>
      </template>
    </CustomTable>

    <div v-if="total > 0" class="pager">
      <el-pagination
        v-model:current-page="page"
        :page-size="perPage"
        :total="total"
        layout="total, prev, pager, next"
        background
        @current-change="loadItems"
      />
    </div>
  </CustomDialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { fetchXepHangSaleKinhDoanh } from '@/api/tongQuanKinhDoanh'

defineOptions({ name: 'XepHangSaleDialog' })

const visible = defineModel({ type: Boolean, default: false })

const props = defineProps({
  tieuChi: {
    type: String,
    default: 'so_hd',
    validator: (value) => ['so_hd', 'doanh_thu'].includes(value),
  },
  tuNgay: { type: String, default: '' },
  denNgay: { type: String, default: '' },
})

const loading = ref(false)
const rows = ref([])
const page = ref(1)
const perPage = 15
const total = ref(0)

const dialogTitle = computed(() =>
  props.tieuChi === 'doanh_thu'
    ? 'Xếp hạng sale — doanh thu HĐ ký'
    : 'Xếp hạng sale — số HĐ ký',
)

const hintText = computed(() => {
  const range = formatRange(props.tuNgay, props.denNgay)
  return props.tieuChi === 'doanh_thu'
    ? `SUM tong_tien HĐ ký trong kỳ (SDDV + TP)${range}`
    : `SDDV theo người tạo · TP theo người cho thuê${range}`
})

watch(visible, (open) => {
  if (!open) return
  page.value = 1
  loadItems()
})

async function loadItems() {
  if (!props.tuNgay || !props.denNgay) {
    rows.value = []
    total.value = 0
    return
  }

  loading.value = true
  try {
    const { data } = await fetchXepHangSaleKinhDoanh(
      {
        tu_ngay: props.tuNgay,
        den_ngay: props.denNgay,
        tieu_chi: props.tieuChi,
        page: page.value,
        per_page: perPage,
      },
      { skipLoading: true },
    )
    rows.value = data.data || []
    total.value = Number(data.total) || 0
    page.value = Number(data.current_page) || page.value
  } catch {
    rows.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

function onClosed() {
  rows.value = []
  total.value = 0
  page.value = 1
}

function rankOf(index) {
  return (page.value - 1) * perPage + index + 1
}

function rankClass(rank) {
  if (rank === 1) return 'is-1'
  if (rank === 2) return 'is-2'
  if (rank === 3) return 'is-3'
  return ''
}

function formatRange(from, to) {
  if (!from || !to) return ''
  return ` · ${formatDateLabel(from)} — ${formatDateLabel(to)}`
}

function formatDateLabel(value) {
  if (!value || !String(value).includes('-')) return '—'
  const [y, m, d] = String(value).split('-')
  return `${d}/${m}/${y}`
}

function formatMoney(val) {
  return `${Number(val || 0).toLocaleString('vi-VN')} ₫`
}

function formatCount(val) {
  return Number(val || 0).toLocaleString('vi-VN')
}
</script>

<style scoped lang="scss">
.hint {
  margin: 0 0 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.rank-no {
  font-weight: 600;

  &.is-1 {
    color: #e6a23c;
  }
  &.is-2 {
    color: #909399;
  }
  &.is-3 {
    color: #c47a3a;
  }
}

.pager {
  display: flex;
  justify-content: flex-end;
  margin-top: 12px;
}
</style>
