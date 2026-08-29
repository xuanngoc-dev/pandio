<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="activeStep === 0 ? '92%' : '96%'"
    class="lich-chup-make-them-modal"
    @closed="onClosed"
  >
    <div class="steps-wrap">
      <el-steps :active="activeStep" finish-status="success" align-center>
        <el-step title="Chọn hợp đồng" />
        <el-step title="Điều phối" />
      </el-steps>
    </div>

    <div v-show="activeStep === 0" class="step-select">
      <div class="step-select__toolbar">
        <CustomInput
          v-model="keyword"
          placeholder="Tìm mã HĐ, tên KH, SĐT..."
          clearable
          style="max-width: 360px"
          @keyup.enter="onSearch"
          @clear="onSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </CustomInput>
        <CustomButton type="primary" @click="onSearch">Tìm</CustomButton>
        <span class="step-select__count">{{ total }} hợp đồng</span>
      </div>

      <div v-loading="loadingList" class="hop-dong-card-grid">
        <el-empty
          v-if="!loadingList && !items.length"
          description="Không có hợp đồng phù hợp"
        />
        <button
          v-for="row in items"
          :key="row.id"
          type="button"
          class="hop-dong-card"
          @click="onSelectCard(row)"
        >
          <div class="hop-dong-card__top">
            <span class="hop-dong-card__ma" :title="row.ma_hop_dong">
              {{ row.ma_hop_dong || '—' }}
            </span>
          </div>
          <div class="hop-dong-card__name" :title="row.ten_khach_hang">
            {{ row.ten_khach_hang || '—' }}
          </div>
          <div class="hop-dong-card__meta">
            <span v-if="row.sdt_khach_hang">SĐT: {{ row.sdt_khach_hang }}</span>
            <span v-if="row.loai_hop_dong?.ten_hop_dong">
              {{ row.loai_hop_dong.ten_hop_dong }}
            </span>
          </div>
          <CustomTooltip :content="sessionCountTooltip(row)" placement="top">
            <span
              class="hop-dong-card__sessions"
              :class="{ 'is-empty': sessionCount(row) === 0 }"
              @click.stop
            >
              <el-icon :size="13"><Calendar /></el-icon>
              <span>{{ sessionCount(row) }}</span>
            </span>
          </CustomTooltip>
        </button>
      </div>

      <div v-if="total > perPage" class="pager">
        <el-pagination
          v-model:current-page="page"
          :page-size="perPage"
          :total="total"
          layout="total, prev, pager, next"
          background
          @current-change="applyClientPage"
        />
      </div>
    </div>

    <div v-if="activeStep === 1 && selectedId" class="step-form">
      <p class="step-form__hint">
        Ngày chụp: <strong>{{ formatDateVi(ngayChup) }}</strong>
        <span v-if="selectedLabel"> · {{ selectedLabel }}</span>
      </p>
      <HopDongSddvDieuPhoiModal
        ref="dieuPhoiRef"
        embedded
        allow-edit-sessions
        :hop-dong-id="selectedId"
        :default-ngay-chup="ngayChup"
        @saved="onDieuPhoiSaved"
      />
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Hủy</CustomButton>
        <CustomButton v-if="activeStep === 1" @click="goBack">Quay lại</CustomButton>
        <CustomButton
          v-if="activeStep === 1"
          type="primary"
          :loading="saving"
          @click="onSave"
        >
          Lưu
        </CustomButton>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Calendar } from '@element-plus/icons-vue'
import { fetchHopDongSuDungDichVu } from '@/api/hopDongSuDungDichVu'
import {
  CustomButton,
  CustomDialog,
  CustomInput,
  CustomTooltip,
} from '@/components/element'
import { normalizeDieuPhoiSessions } from '@/utils/thongTinDieuPhoi'
import HopDongSddvDieuPhoiModal from '@/views/van-hanh-cuoi/hop-dong-sddv/HopDongSddvDieuPhoiModal.vue'

/** Chỉ hiện các trạng thái này */
const ALLOWED_TRANG_THAI = ['da_coc', 'dang_thuc_hien', 'moi_tao']

const visible = defineModel({ type: Boolean, default: false })

const props = defineProps({
  ngayChup: { type: String, default: '' },
})

const emit = defineEmits(['saved'])

const activeStep = ref(0)
const keyword = ref('')
const loadingList = ref(false)
const allFiltered = ref([])
const items = ref([])
const page = ref(1)
const perPage = 12
const total = ref(0)
const selectedId = ref(null)
const selectedLabel = ref('')
const dieuPhoiRef = ref(null)
const saving = ref(false)

const dialogTitle = computed(() => {
  const ngay = formatDateVi(props.ngayChup)
  return ngay !== '—' ? `Thêm lịch điều phối — ${ngay}` : 'Thêm lịch điều phối'
})

function formatDateVi(value) {
  if (!value) return '—'
  const raw = String(value).slice(0, 10)
  const [y, m, d] = raw.split('-')
  if (!y || !m || !d) return raw
  return `${d}/${m}/${y}`
}

function sessionCount(row) {
  return normalizeDieuPhoiSessions(row?.thong_tin_dieu_phoi).length
}

function sessionCountTooltip(row) {
  const n = sessionCount(row)
  if (n <= 0) return 'Chưa có buổi chụp'
  return n === 1 ? 'Đã có 1 buổi chụp' : `Đã có ${n} buổi chụp`
}

function onSearch() {
  page.value = 1
  loadItems()
}

function applyClientPage() {
  total.value = allFiltered.value.length
  const start = (page.value - 1) * perPage
  items.value = allFiltered.value.slice(start, start + perPage)
}

async function fetchAllPages(extraParams = {}) {
  const rows = []
  let currentPage = 1
  let lastPage = 1

  do {
    const { data } = await fetchHopDongSuDungDichVu({
      page: currentPage,
      per_page: 100,
      keyword: keyword.value || undefined,
      ...extraParams,
    })
    rows.push(...(data.data || []))
    lastPage = data.last_page || 1
    currentPage += 1
  } while (currentPage <= lastPage && currentPage <= 5)

  return rows
}

async function loadItems() {
  loadingList.value = true
  try {
    // API mặc định ẩn moi_tao → fetch từng trạng thái được phép
    const batches = await Promise.all(
      ALLOWED_TRANG_THAI.map((trang_thai) => fetchAllPages({ trang_thai })),
    )

    const byId = new Map()
    for (const row of batches.flat()) {
      if (!row?.id || !row?.loai_hop_dong_id) continue
      if (!ALLOWED_TRANG_THAI.includes(String(row.trang_thai || ''))) continue
      byId.set(row.id, row)
    }

    allFiltered.value = [...byId.values()].sort((a, b) => Number(b.id) - Number(a.id))
    applyClientPage()

    if (selectedId.value && !allFiltered.value.some((row) => row.id === selectedId.value)) {
      selectedId.value = null
      selectedLabel.value = ''
    }
  } catch {
    allFiltered.value = []
    items.value = []
    total.value = 0
  } finally {
    loadingList.value = false
  }
}

async function onSelectCard(row) {
  if (!row?.id) return
  if (!row.loai_hop_dong_id) {
    ElMessage.warning('Hợp đồng chưa chọn loại hợp đồng.')
    return
  }

  const label = [row.ma_hop_dong, row.ten_khach_hang].filter(Boolean).join(' — ')
  try {
    await ElMessageBox.confirm(
      `Chọn hợp đồng ${label || `#${row.id}`} để thêm lịch điều phối ngày ${formatDateVi(props.ngayChup)}?`,
      'Xác nhận chọn hợp đồng',
      {
        type: 'info',
        confirmButtonText: 'Tiếp tục',
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  selectedId.value = row.id
  selectedLabel.value = [row.ma_hop_dong, row.ten_khach_hang].filter(Boolean).join(' · ')
  activeStep.value = 1
}

function goBack() {
  dieuPhoiRef.value?.resetState?.()
  activeStep.value = 0
  selectedId.value = null
  selectedLabel.value = ''
}

async function onSave() {
  saving.value = true
  try {
    const ok = await dieuPhoiRef.value?.save?.()
    if (ok) visible.value = false
  } finally {
    saving.value = false
  }
}

function onDieuPhoiSaved() {
  emit('saved')
}

function resetAll() {
  activeStep.value = 0
  keyword.value = ''
  allFiltered.value = []
  items.value = []
  page.value = 1
  total.value = 0
  selectedId.value = null
  selectedLabel.value = ''
  saving.value = false
  dieuPhoiRef.value?.resetState?.()
}

function onClosed() {
  resetAll()
}

watch(visible, (isOpen) => {
  if (!isOpen) return
  resetAll()
  loadItems()
})
</script>

<style scoped lang="scss">
.steps-wrap {
  margin-bottom: 16px;
}

.step-select__toolbar {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.step-select__count {
  margin-left: auto;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.hop-dong-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 10px;
  min-height: 92px;
  max-height: min(58vh, 560px);
  overflow-y: auto;
  padding: 4px 6px 10px;
}

.hop-dong-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 10px 12px 22px;
  border: 1.5px solid var(--el-border-color);
  border-radius: 8px;
  background: var(--el-fill-color-blank);
  text-align: left;
  cursor: pointer;
  font: inherit;
  color: inherit;
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--el-border-color) 55%, transparent),
    0 2px 6px rgba(0, 0, 0, 0.07);
  transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;

  &:hover {
    border-color: var(--el-color-primary);
    box-shadow:
      0 0 0 1px color-mix(in srgb, var(--el-color-primary) 35%, transparent),
      0 3px 10px rgba(0, 0, 0, 0.1);
    background: var(--el-color-primary-light-9);
  }
}

.hop-dong-card__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.hop-dong-card__ma {
  display: block;
  width: 100%;
  font-size: 12px;
  font-weight: 700;
  color: var(--el-color-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.hop-dong-card__name {
  font-size: 13px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.hop-dong-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  line-height: 1.3;
  padding-right: 36px;
}

.hop-dong-card__sessions {
  position: absolute;
  right: 8px;
  bottom: 6px;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 1px 6px;
  border-radius: 999px;
  background: var(--el-color-primary-light-9);
  color: var(--el-color-primary);
  font-size: 11px;
  font-weight: 700;
  line-height: 1.4;
  cursor: default;

  &.is-empty {
    background: var(--el-fill-color);
    color: var(--el-text-color-secondary);
  }
}

.pager {
  display: flex;
  justify-content: flex-end;
  margin-top: 12px;
}

.step-form__hint {
  margin: 0 0 12px;
  font-size: 13px;
  color: var(--el-text-color-regular);
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
