<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1060"
    class="lich-chup-make-chi-tiet-modal"
    @closed="onClosed"
  >
    <CustomTable
      v-loading="loading"
      :data="items"
      stripe
      row-key="id"
      style="width: 100%"
      empty-text="Không có hợp đồng"
    >
      <CustomTableColumn label="STT" width="60" align="center">
        <template #default="{ $index }">
          {{ (page - 1) * perPage + $index + 1 }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Mã HĐ" prop="ma_hop_dong" min-width="140" />
      <!-- <CustomTableColumn label="Loại hợp đồng" min-width="150">
        <template #default="{ row }">
          {{ row.loai_hop_dong?.ten_hop_dong || meta.tenHopDong || '—' }}
        </template>
      </CustomTableColumn> -->
      <CustomTableColumn label="Khách hàng" min-width="180">
        <template #default="{ row }">
          <div>{{ formatKhachHang(row) }}</div>
          <div v-if="row.sdt_khach_hang" class="sub-text">{{ row.sdt_khach_hang }}</div>
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Người tạo" min-width="130">
        <template #default="{ row }">
          {{ row.nguoi_tao?.name || '—' }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Trạng thái" width="130" align="center">
        <template #default="{ row }">
          <CustomTag :type="trangThaiTagType(row.trang_thai)" size="small">
            {{ trangThaiLabel(row.trang_thai) }}
          </CustomTag>
        </template>
      </CustomTableColumn>
      <!-- <CustomTableColumn label="Tổng tiền" min-width="120" align="right">
        <template #default="{ row }">
          {{ formatMoney(row.tong_tien) }}
        </template>
      </CustomTableColumn> -->
      <CustomTableColumn label="Thao tác" width="100" align="center" fixed="right">
        <template #default="{ row }">
          <CustomTooltip
            v-if="row.trang_thai === 'dang_thuc_hien'"
            content="Điều phối"
            placement="top"
          >
            <CustomButton type="warning" link :icon="Position" @click="openDieuPhoi(row)" />
          </CustomTooltip>
          <span v-else>—</span>
        </template>
      </CustomTableColumn>
    </CustomTable>

    <div v-if="total > perPage" class="pager">
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

  <HopDongSddvDieuPhoiModal
    v-model="dieuPhoiModalVisible"
    :hop-dong-id="dieuPhoiHopDongId"
    @saved="onDieuPhoiSaved"
  />
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Position } from '@element-plus/icons-vue'
import { fetchLichChupMakeChiTiet } from '@/api/hopDongSuDungDichVu'
import HopDongSddvDieuPhoiModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvDieuPhoiModal.vue'

const visible = defineModel({ type: Boolean, default: false })

const props = defineProps({
  ngayChup: { type: String, default: '' },
  loaiHopDongId: { type: [Number, String], default: null },
  tenHopDong: { type: String, default: '' },
})

const emit = defineEmits(['saved'])

const loading = ref(false)
const items = ref([])
const page = ref(1)
const perPage = 20
const total = ref(0)
const dieuPhoiModalVisible = ref(false)
const dieuPhoiHopDongId = ref(null)

const meta = computed(() => ({
  ngayChup: props.ngayChup,
  loaiHopDongId: props.loaiHopDongId,
  tenHopDong: props.tenHopDong,
}))

const dialogTitle = computed(() => {
  const ngay = formatDateVi(props.ngayChup)
  const loai = props.tenHopDong || 'Loại hợp đồng'
  return `Lịch chụp ${loai} ngày ${ngay}`
})

const TRANG_THAI_LABEL = {
  moi_tao: 'Mới tạo',
  nhap: 'Nháp',
  da_coc: 'Đã cọc',
  dang_thuc_hien: 'Đang thực hiện',
  da_huy: 'Đã hủy',
  hoan_thanh: 'Hoàn thành',
}

function trangThaiLabel(value) {
  return TRANG_THAI_LABEL[value] || value || '—'
}

function trangThaiTagType(value) {
  const map = {
    moi_tao: 'info',
    nhap: '',
    da_coc: 'warning',
    dang_thuc_hien: 'primary',
    da_huy: 'danger',
    hoan_thanh: 'success',
  }
  return map[value] || 'info'
}

function formatMoney(value) {
  if (value == null || value === '') return '—'
  const num = Number(value)
  if (Number.isNaN(num)) return '—'
  return `${num.toLocaleString('vi-VN')} ₫`
}

function formatDateVi(value) {
  if (!value) return '—'
  const raw = String(value).slice(0, 10)
  const [y, m, d] = raw.split('-')
  if (!y || !m || !d) return raw
  return `${d}/${m}/${y}`
}

function formatKhachHang(row) {
  return row?.ten_khach_hang || '—'
}

function openDieuPhoi(row) {
  if (!row?.loai_hop_dong_id) {
    ElMessage.warning('Hợp đồng chưa chọn loại hợp đồng.')
    return
  }
  dieuPhoiHopDongId.value = row.id
  dieuPhoiModalVisible.value = true
}

function onDieuPhoiSaved() {
  loadItems()
  emit('saved')
}

async function loadItems() {
  if (!props.ngayChup) {
    items.value = []
    total.value = 0
    return
  }

  loading.value = true
  try {
    const { data } = await fetchLichChupMakeChiTiet({
      ngay_chup: props.ngayChup,
      loai_hop_dong_id: props.loaiHopDongId || undefined,
      page: page.value,
      per_page: perPage,
    })
    items.value = data.data || []
    total.value = data.total || 0
  } catch {
    items.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

function onClosed() {
  items.value = []
  total.value = 0
  page.value = 1
}

watch(
  () => [visible.value, props.ngayChup, props.loaiHopDongId],
  ([isOpen]) => {
    if (!isOpen) return
    page.value = 1
    loadItems()
  },
)
</script>

<style scoped lang="scss">
.sub-text {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.pager {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}
</style>
