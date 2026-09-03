<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1500"
    class="lich-chup-make-chi-tiet-modal"
    @closed="onClosed"
  >
    <CustomTable
      v-loading="loading"
      :data="items"
      stripe
      row-key="_rowKey"
      style="width: 100%"
      empty-text="Không có hợp đồng"
    >
      <CustomTableColumn label="STT" width="60" align="center">
        <template #default="{ $index }">
          {{ (page - 1) * perPage + $index + 1 }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Giờ chụp" width="100" align="center">
        <template #default="{ row }">
          {{ formatGioChup(row) }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Mã HĐ" prop="ma_hop_dong" min-width="160" />
      <CustomTableColumn label="Loại hợp đồng" min-width="140">
        <template #default="{ row }">
          {{ row.loai_hop_dong?.ten_hop_dong || props.tenHopDong || '—' }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Loại dịch vụ" min-width="150">
        <template #default="{ row }">
          {{ formatLoaiDichVu(row) }}
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Khách hàng" min-width="180">
        <template #default="{ row }">
          <div>{{ formatKhachHang(row) }}</div>
          <div v-if="row.sdt_khach_hang" class="sub-text">{{ row.sdt_khach_hang }}</div>
        </template>
      </CustomTableColumn>
      <!-- <CustomTableColumn label="Người tạo" min-width="130">
        <template #default="{ row }">
          {{ row.nguoi_tao?.name || '—' }}
        </template>
      </CustomTableColumn> -->
      <CustomTableColumn label="Trạng thái" width="130" align="center">
        <template #default="{ row }">
          <CustomTag :type="trangThaiTagType(row.trang_thai)" size="small">
            {{ trangThaiLabel(row.trang_thai) }}
          </CustomTag>
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Trạng thái điều phối" width="150" align="center">
        <template #default="{ row }">
          <CustomTag :type="trangThaiDieuPhoiTagType(getTrangThaiDieuPhoi(row))" size="small">
            {{ trangThaiDieuPhoiLabel(getTrangThaiDieuPhoi(row)) }}
          </CustomTag>
        </template>
      </CustomTableColumn>
      <CustomTableColumn label="Sắp xếp đồ" width="170" align="center">
        <template #default="{ row }">
          <div class="sap-xep-do-cell">
            <CustomTag :type="sapXepDoTagType(row)" size="small">
              {{ sapXepDoLabel(row) }}
            </CustomTag>
            <CustomTooltip
              :content="
                canEditSapXepDo(row)
                  ? 'Đổi trạng thái sắp xếp đồ'
                  : sapXepDoDisabledReason(row)
              "
              placement="top"
            >
              <span class="sap-xep-do-edit-wrap">
                <el-dropdown
                  trigger="click"
                  :disabled="!canEditSapXepDo(row) || isUpdatingSapXepDo(row)"
                  @command="(value) => updateSapXepDo(row, value)"
                >
                  <CustomButton
                    type="primary"
                    link
                    :icon="EditPen"
                    :loading="isUpdatingSapXepDo(row)"
                    :disabled="!canEditSapXepDo(row)"
                  />
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item
                        v-for="opt in sapXepDoOptions"
                        :key="opt.value"
                        :command="opt.value"
                        :disabled="resolveSapXepDoValue(row) === opt.value"
                      >
                        {{ opt.label }}
                      </el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
              </span>
            </CustomTooltip>
          </div>
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
            :content="canDieuPhoi(row) ? 'Điều phối' : dieuPhoiDisabledReason(row)"
            placement="top"
          >
            <CustomButton
              type="warning"
              link
              :icon="Position"
              :disabled="!canDieuPhoi(row)"
              @click="openDieuPhoi(row)"
            />
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
import { EditPen, Position } from '@element-plus/icons-vue'
import {
  capNhatThongTinDieuPhoi,
  fetchLichChupMakeChiTiet,
} from '@/api/hopDongSuDungDichVu'
import HopDongSddvDieuPhoiModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvDieuPhoiModal.vue'
import {
  DANH_SACH_BUOI_CHUP_KEY,
  formatLoaiQuayChupLabel,
  formatSapXepTrangPhucLabel,
  getDieuPhoiGiaTriFromSession,
  normalizeDieuPhoiSessions,
  normalizeSapXepTrangPhucValue,
  parseSessionLoaiQuayChup,
  resolveSapXepTrangPhucValue,
  resolveTrangThaiDieuPhoi,
  SAP_XEP_TRANG_PHUC_KEY,
  SAP_XEP_TRANG_PHUC_OPTIONS,
  sapXepTrangPhucTagType,
} from '@/utils/thongTinDieuPhoi'

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
const updatingSapXepDoKey = ref(null)
const sapXepDoOptions = SAP_XEP_TRANG_PHUC_OPTIONS

const dialogTitle = computed(() => {
  const ngay = formatDateVi(props.ngayChup)
  if (props.tenHopDong) {
    return `Lịch chụp ${props.tenHopDong} ngày ${ngay}`
  }
  return `Lịch chụp ngày ${ngay}`
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

const TRANG_THAI_DIEU_PHOI_LABEL = {
  tien_ky: 'Tiền kỳ',
  hau_ky: 'Hậu kỳ',
  gui_in: 'Gửi in',
  hoan_tat_san_xuat: 'Hoàn tất sản xuất',
  cho_nhan: 'Chờ nhận',
  dang_xu_ly: 'Đang xử lý',
  gui_khach_kiem_tra: 'Gửi khách kiểm tra',
  san_xuat_in_an: 'Sản xuất & in ấn',
  cho_nghiem_thu: 'Chờ nghiệm thu',
  hoan_thanh: 'Hoàn thành',
}

function getTrangThaiDieuPhoi(row) {
  return resolveTrangThaiDieuPhoi(row) || null
}

function trangThaiDieuPhoiLabel(value) {
  if (!value) return '—'
  return TRANG_THAI_DIEU_PHOI_LABEL[value] || value
}

function trangThaiDieuPhoiTagType(value) {
  const map = {
    tien_ky: 'info',
    hau_ky: 'warning',
    gui_in: 'primary',
    hoan_tat_san_xuat: 'success',
    cho_nhan: 'info',
    dang_xu_ly: 'warning',
    gui_khach_kiem_tra: 'primary',
    san_xuat_in_an: 'warning',
    cho_nghiem_thu: 'primary',
    hoan_thanh: 'success',
  }
  return map[value] || 'info'
}

function canDieuPhoi(row) {
  if (!row?.id || !row?.loai_hop_dong_id) return false
  return getTrangThaiDieuPhoi(row) !== 'hoan_tat_san_xuat'
}

function dieuPhoiDisabledReason(row) {
  if (!row?.loai_hop_dong_id) return 'Hợp đồng chưa chọn loại hợp đồng'
  if (getTrangThaiDieuPhoi(row) === 'hoan_tat_san_xuat') {
    return 'Đã hoàn tất sản xuất — không thể điều phối thêm'
  }
  return 'Không thể điều phối'
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

function sessionsOnNgayChup(row) {
  const sessions = normalizeDieuPhoiSessions(row?.thong_tin_dieu_phoi)
  const targetDate = String(props.ngayChup || '').slice(0, 10)
  return sessions
    .map((session, index) => ({ session, index }))
    .filter(({ session }) => {
      const ngay = String(getDieuPhoiGiaTriFromSession(session, 'ngay_chup') || '').slice(0, 10)
      return ngay === targetDate
    })
}

function expandHopDongRows(rows) {
  const result = []
  for (const row of rows) {
    const matched = sessionsOnNgayChup(row)
    const list = matched.length ? matched : [{ session: null, index: 0 }]
    for (const { session, index } of list) {
      result.push({
        ...row,
        _session: session,
        _sessionIndex: index,
        _rowKey: `${row.id}-${index}`,
      })
    }
  }
  return result
}

function formatGioChup(row) {
  const raw = getDieuPhoiGiaTriFromSession(row?._session, 'gio_chup')
  if (raw == null || raw === '') return '—'
  const text = String(raw).trim()
  const match = text.match(/^(\d{1,2}):(\d{2})/)
  if (!match) return text.slice(0, 5)
  return `${match[1].padStart(2, '0')}:${match[2]}`
}

function formatLoaiDichVu(row) {
  const loaiQuayChup = parseSessionLoaiQuayChup(row?._session)
  return formatLoaiQuayChupLabel(loaiQuayChup) || '—'
}

function sapXepDoValue(row) {
  return getDieuPhoiGiaTriFromSession(row?._session, SAP_XEP_TRANG_PHUC_KEY)
}

function resolveSapXepDoValue(row) {
  return resolveSapXepTrangPhucValue(sapXepDoValue(row))
}

function sapXepDoLabel(row) {
  return formatSapXepTrangPhucLabel(sapXepDoValue(row)) || '—'
}

function sapXepDoTagType(row) {
  return sapXepTrangPhucTagType(sapXepDoValue(row))
}

function canEditSapXepDo(row) {
  if (!row?.id || !row?._session) return false
  return getTrangThaiDieuPhoi(row) !== 'hoan_tat_san_xuat'
}

function sapXepDoDisabledReason(row) {
  if (!row?._session) return 'Không tìm thấy buổi chụp để cập nhật'
  if (getTrangThaiDieuPhoi(row) === 'hoan_tat_san_xuat') {
    return 'Đã hoàn tất sản xuất — không thể đổi trạng thái sắp xếp đồ'
  }
  return 'Không thể đổi trạng thái sắp xếp đồ'
}

function isUpdatingSapXepDo(row) {
  return updatingSapXepDoKey.value === row?._rowKey
}

function buildSapXepDoPayload(row, value) {
  const envelope =
    row?.thong_tin_dieu_phoi && typeof row.thong_tin_dieu_phoi === 'object'
      ? JSON.parse(JSON.stringify(row.thong_tin_dieu_phoi))
      : {}
  const sessions = Array.isArray(envelope[DANH_SACH_BUOI_CHUP_KEY])
    ? envelope[DANH_SACH_BUOI_CHUP_KEY]
    : null
  const session = sessions?.[row._sessionIndex]
  if (!session || typeof session !== 'object') return null

  const normalized = normalizeSapXepTrangPhucValue(value)
  const existing = session[SAP_XEP_TRANG_PHUC_KEY]
  if (existing && typeof existing === 'object' && !Array.isArray(existing)) {
    existing.gia_tri = normalized
  } else {
    session[SAP_XEP_TRANG_PHUC_KEY] = {
      su_dung: true,
      ten_thong_tin: 'Sắp xếp trang phục',
      loai_du_lieu: 'string',
      gia_tri: normalized,
    }
  }
  return envelope
}

async function updateSapXepDo(row, value) {
  if (!canEditSapXepDo(row)) {
    ElMessage.warning(sapXepDoDisabledReason(row))
    return
  }
  if (resolveSapXepDoValue(row) === value) return

  const payload = buildSapXepDoPayload(row, value)
  if (!payload) {
    ElMessage.warning('Không tìm thấy buổi chụp để cập nhật')
    return
  }

  updatingSapXepDoKey.value = row._rowKey
  try {
    await capNhatThongTinDieuPhoi(row.id, { thong_tin_dieu_phoi: payload })
    ElMessage.success('Đã cập nhật trạng thái sắp xếp đồ')
    await loadItems()
    emit('saved')
  } catch {
    // interceptor
  } finally {
    updatingSapXepDoKey.value = null
  }
}

function openDieuPhoi(row) {
  if (!canDieuPhoi(row)) {
    ElMessage.warning(dieuPhoiDisabledReason(row))
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
    items.value = expandHopDongRows(data.data || [])
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

.sap-xep-do-cell {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.sap-xep-do-edit-wrap {
  display: inline-flex;
  align-items: center;
}

.pager {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}
</style>
