<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1200"
    class="lich-hau-ky-chi-tiet-modal"
    @closed="onClosed"
  >
    <el-tabs
      v-if="visibleTabs.length"
      v-model="activeLoai"
      class="loai-tabs"
      @tab-change="onTabChange"
    >
      <el-tab-pane
        v-for="tab in visibleTabs"
        :key="tab.key"
        :name="tab.key"
        :label="`${tab.label} (${tab.count})`"
      />
    </el-tabs>

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
      <CustomTableColumn label="Mã HĐ" prop="ma_hop_dong" min-width="150" />
      <CustomTableColumn label="Loại hợp đồng" min-width="140">
        <template #default="{ row }">
          {{ row.loai_hop_dong?.ten_hop_dong || '—' }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Khách hàng" min-width="180">
        <template #default="{ row }">
          <div>{{ row.ten_khach_hang || '—' }}</div>
          <div v-if="row.sdt_khach_hang" class="sub-text">{{ row.sdt_khach_hang }}</div>
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Trả file lẻ" width="120" align="center">
        <template #default="{ row }">
          {{ formatSharedDate(row, 'ngay_tra_file_le') }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Trả file in" width="120" align="center">
        <template #default="{ row }">
          {{ formatSharedDate(row, 'ngay_tra_file_in') }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Khách hẹn qua" width="130" align="center">
        <template #default="{ row }">
          {{ formatSharedDate(row, 'ngay_khach_hen_qua') }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Trạng thái" width="130" align="center">
        <template #default="{ row }">
          <CustomTag :type="trangThaiTagType(row.trang_thai)" size="small">
            {{ trangThaiLabel(row.trang_thai) }}
          </CustomTag>
        </template>
      </CustomTableColumn>
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
import { fetchLichHauKyChiTiet } from '@/api/hopDongSuDungDichVu'
import HopDongSddvDieuPhoiModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvDieuPhoiModal.vue'
import { firstDieuPhoiGiaTri } from '@/utils/thongTinDieuPhoi'

const LOAI_TABS = [
  { key: 'ngay_tra_file_le', countKey: 'tra_file_le', label: 'Trả file lẻ' },
  { key: 'ngay_tra_file_in', countKey: 'tra_file_in', label: 'Trả file in' },
  { key: 'ngay_khach_hen_qua', countKey: 'khach_qua', label: 'Khách hẹn qua' },
]

const visible = defineModel({ type: Boolean, default: false })

const props = defineProps({
  ngay: { type: String, default: '' },
  loai: { type: String, default: 'ngay_tra_file_le' },
  counts: {
    type: Object,
    default: () => ({ tra_file_le: 0, tra_file_in: 0, khach_qua: 0 }),
  },
})

const emit = defineEmits(['saved'])

const loading = ref(false)
const items = ref([])
const page = ref(1)
const perPage = 20
const total = ref(0)
const activeLoai = ref(props.loai)
const dieuPhoiModalVisible = ref(false)
const dieuPhoiHopDongId = ref(null)

const visibleTabs = computed(() =>
  LOAI_TABS.map((tab) => ({
    ...tab,
    count: Number(props.counts?.[tab.countKey]) || 0,
  })).filter((tab) => tab.count > 0),
)

const activeTabMeta = computed(
  () => LOAI_TABS.find((tab) => tab.key === activeLoai.value) || LOAI_TABS[0],
)

const dialogTitle = computed(() => {
  const ngay = formatDateVi(props.ngay)
  return `${activeTabMeta.value.label} ngày ${ngay}`
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

function formatDateVi(value) {
  if (!value) return '—'
  const raw = String(value).slice(0, 10)
  const [y, m, d] = raw.split('-')
  if (!y || !m || !d) return raw
  return `${d}/${m}/${y}`
}

function formatSharedDate(row, key) {
  const raw = firstDieuPhoiGiaTri(row?.thong_tin_dieu_phoi, key)
  return formatDateVi(raw)
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

function onTabChange() {
  page.value = 1
  loadItems()
}

async function loadItems() {
  if (!props.ngay || !activeLoai.value) {
    items.value = []
    total.value = 0
    return
  }

  loading.value = true
  try {
    const { data } = await fetchLichHauKyChiTiet({
      ngay: props.ngay,
      loai: activeLoai.value,
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
  () => [visible.value, props.ngay, props.loai],
  ([isOpen, , loai]) => {
    if (!isOpen) return
    activeLoai.value = loai || visibleTabs.value[0]?.key || LOAI_TABS[0].key
    page.value = 1
    loadItems()
  },
)
</script>

<style scoped lang="scss">
.loai-tabs {
  margin-bottom: 12px;
}

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
