<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1280"
    class="hop-dong-sddv-lich-quay-chup-modal"
    @closed="onClosed"
  >
    <div v-loading="loading" class="lich-body">
      <el-empty
        v-if="!loading && !sessions.length"
        description="Chưa có lịch quay chụp."
      />

      <template v-else-if="sessions.length">
        <CustomForm v-if="sharedDateItems.length" label-position="top" class="shared-date-form">
          <CustomRow :gutter="16">
            <CustomCol
              v-for="item in sharedDateItems"
              :key="item.key"
              v-bind="fieldColProps"
            >
              <CustomFormItem :label="item.label">
                <CustomInput :model-value="item.value" readonly />
              </CustomFormItem>
            </CustomCol>
          </CustomRow>
        </CustomForm>

        <el-tabs
          v-model="activeSessionName"
          type="border-card"
          class="session-tabs"
          :class="{
            'is-slide-left': slideDirection === 'left',
            'is-slide-right': slideDirection !== 'left',
          }"
        >
        <el-tab-pane
          v-for="(session, index) in sessions"
          :key="session.key"
          :name="session.key"
          lazy
        >
          <template #label>
            <span class="session-tab-label" :title="session.title">
              <span class="session-tab-text">{{ session.title }}</span>
            </span>
          </template>

          <CustomForm label-position="top">
            <CustomRow v-if="session.normalItems.length" :gutter="16">
              <CustomCol
                v-for="item in session.normalItems"
                :key="item.key"
                v-bind="item.wide ? wideColProps : fieldColProps"
              >
                <CustomFormItem :label="item.label">
                  <CustomInput :model-value="item.value" readonly />
                </CustomFormItem>
              </CustomCol>
            </CustomRow>

            <section class="lich-block">
              <div class="lich-block__title">Concept</div>
              <CustomTable
                v-if="session.concepts.length"
                :data="session.concepts"
                stripe
                style="width: 100%"
              >
                <CustomTableColumn label="STT" width="64" align="center">
                  <template #default="{ $index }">{{ $index + 1 }}</template>
                </CustomTableColumn>
                <CustomTableColumn prop="ten" label="Tên concept" min-width="200" />
                <CustomTableColumn label="Địa điểm" min-width="160">
                  <template #default="{ row }">{{ display(row.dia_diem) }}</template>
                </CustomTableColumn>
              </CustomTable>
              <div v-else class="lich-empty">Chưa có concept.</div>
            </section>

            <section class="lich-block">
              <div class="lich-block__title">Trang phục</div>
              <CustomTable
                v-if="session.trangPhucs.length"
                :data="session.trangPhucs"
                stripe
                style="width: 100%"
              >
                <CustomTableColumn label="STT" width="64" align="center">
                  <template #default="{ $index }">{{ $index + 1 }}</template>
                </CustomTableColumn>
                <CustomTableColumn label="Tên trang phục" min-width="200">
                  <template #default="{ row }">{{ formatTrangPhucLabel(row) }}</template>
                </CustomTableColumn>
                <CustomTableColumn label="Giá thuê" width="140" align="right">
                  <template #default="{ row }">{{ formatMoney(row.gia_cho_thue) }}</template>
                </CustomTableColumn>
                <CustomTableColumn label="Ngày bắt đầu" width="130" align="center">
                  <template #default="{ row }">{{ formatDate(row.ngay_bat_dau) }}</template>
                </CustomTableColumn>
                <CustomTableColumn label="Ngày kết thúc" width="130" align="center">
                  <template #default="{ row }">{{ formatDate(row.ngay_ket_thuc) }}</template>
                </CustomTableColumn>
              </CustomTable>
              <div v-else class="lich-empty">Chưa có trang phục.</div>
            </section>

            <CustomRow v-if="session.textareaItems.length" :gutter="16">
              <CustomCol
                v-for="item in session.textareaItems"
                :key="item.key"
                v-bind="wideColProps"
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
        </el-tab-pane>
      </el-tabs>
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
import {
  BUOI_CHUP_OPTIONS,
  DIEU_PHOI_STAFF_KEYS,
  LICH_QUAY_CHUP_KEYS,
  LOAI_QUAY_CHUP_KEY,
  SHARED_LICH_QUAY_CHUP_KEYS,
  firstDieuPhoiGiaTri,
  formatLoaiQuayChupLabel,
  formatTrangPhucDieuPhoiLabel,
  getDieuPhoiGiaTriFromSession,
  getTenLichQuayChup,
  isDieuPhoiExtraSessionKey,
  isSharedLichQuayChupKey,
  mapHopDongConceptRows,
  mapHopDongTrangPhucRows,
  normalizeDieuPhoiSessions,
  parseSessionConceptItems,
  parseSessionLoaiQuayChup,
  parseSessionTrangPhucItems,
} from '@/utils/thongTinDieuPhoi'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  hopDongId: { type: [Number, String], default: null },
})

const emit = defineEmits(['update:modelValue', 'closed'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const fieldColProps = { xs: 24, sm: 12, md: 8, lg: 6, xl: 6 }
const wideColProps = { xs: 24, sm: 24, md: 12, lg: 12, xl: 12 }

const loading = ref(false)
const hopDong = ref(null)
const loaiHopDong = ref(null)
const userOptions = ref([])
const activeSessionName = ref('')
const slideDirection = ref('right')

const dialogTitle = computed(() => {
  const ma = hopDong.value?.ma_hop_dong
  return ma ? `Lịch quay chụp — ${ma}` : 'Lịch quay chụp'
})

const userNameMap = computed(() => {
  const map = new Map()
  for (const user of userOptions.value) {
    map.set(Number(user.id), user.name)
  }
  return map
})

const sessions = computed(() => {
  const schema =
    loaiHopDong.value?.thong_tin_dieu_phoi &&
    typeof loaiHopDong.value.thong_tin_dieu_phoi === 'object' &&
    !Array.isArray(loaiHopDong.value.thong_tin_dieu_phoi)
      ? loaiHopDong.value.thong_tin_dieu_phoi
      : {}

  const savedSessions = normalizeDieuPhoiSessions(hopDong.value?.thong_tin_dieu_phoi)
  if (!savedSessions.length) return []

  const schemaKeys = orderedFieldKeys(schema, savedSessions)

  return savedSessions.map((saved, index) => {
    const items = []
    const loaiQuayChup = parseSessionLoaiQuayChup(saved)
    items.push({
      key: LOAI_QUAY_CHUP_KEY,
      label: saved?.[LOAI_QUAY_CHUP_KEY]?.ten_thong_tin || 'Loại quay chụp',
      wide: false,
      value: formatLoaiQuayChupLabel(loaiQuayChup) || '—',
    })

    for (const key of schemaKeys) {
      if (isDieuPhoiExtraSessionKey(key) || isSharedLichQuayChupKey(key) || String(key).startsWith('_')) continue
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

    const ngayChup = getDieuPhoiGiaTriFromSession(saved, 'ngay_chup')
    const fromDieuPhoiConcepts = parseSessionConceptItems(saved)
    const fromDieuPhoiTrangPhucs = parseSessionTrangPhucItems(saved)
    let concepts = fromDieuPhoiConcepts.length
      ? fromDieuPhoiConcepts
      : mapHopDongConceptRows(hopDong.value?.concepts, ngayChup)
    let trangPhucs = fromDieuPhoiTrangPhucs.length
      ? fromDieuPhoiTrangPhucs
      : mapHopDongTrangPhucRows(hopDong.value?.trang_phucs, ngayChup)

    if (index === 0) {
      if (!concepts.length) {
        concepts = mapHopDongConceptRows(hopDong.value?.concepts, null)
      }
      if (!trangPhucs.length) {
        trangPhucs = mapHopDongTrangPhucRows(hopDong.value?.trang_phucs, null)
      }
    }

    return {
      key: `session-${index}`,
      title: getTenLichQuayChup(saved, index),
      normalItems: items.filter((item) => !item.wide),
      textareaItems: items.filter((item) => item.wide),
      concepts,
      trangPhucs,
    }
  })
})

const sharedDateItems = computed(() => {
  const schema =
    loaiHopDong.value?.thong_tin_dieu_phoi &&
    typeof loaiHopDong.value.thong_tin_dieu_phoi === 'object' &&
    !Array.isArray(loaiHopDong.value.thong_tin_dieu_phoi)
      ? loaiHopDong.value.thong_tin_dieu_phoi
      : {}
  const raw = hopDong.value?.thong_tin_dieu_phoi
  const items = []

  for (const key of SHARED_LICH_QUAY_CHUP_KEYS) {
    const schemaItem = schema[key] && typeof schema[key] === 'object' ? schema[key] : null
    const value = firstDieuPhoiGiaTri(raw, key)
    if (schemaItem?.su_dung === false && value == null) continue
    if (!schemaItem && value == null) continue

    items.push({
      key,
      label: schemaItem?.ten_thong_tin || (
        key === 'ngay_tra_demo' ? 'Ngày trả demo' : 'Ngày trả chính thức'
      ),
      value: formatDate(value),
    })
  }

  return items
})

function orderedFieldKeys(schema, savedSessions) {
  const schemaKeys = Object.keys(schema)
  const savedKeys = [...new Set(savedSessions.flatMap((item) => Object.keys(item || {})))]
  const all = [...new Set([...schemaKeys, ...savedKeys])]
  const preferred = LICH_QUAY_CHUP_KEYS.filter((key) => all.includes(key))
  const rest = all.filter((key) => !preferred.includes(key) && !isDieuPhoiExtraSessionKey(key))
  return [...preferred, ...rest]
}

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

function formatTrangPhucLabel(row) {
  return formatTrangPhucDieuPhoiLabel(row) || row?.ten || '—'
}

function formatDieuPhoiValue(key, loai, value) {
  if (value == null || value === '') return '—'
  if (loai === 'date' || String(key).startsWith('ngay_')) return formatDate(value)
  if (loai === 'time') return String(value)
  if (key === 'buoi_chup') {
    const map = Object.fromEntries(BUOI_CHUP_OPTIONS.map((opt) => [opt.value, opt.label]))
    const list = Array.isArray(value) ? value : [value]
    return list.map((item) => map[String(item).toLowerCase()] || String(item)).join(', ')
  }
  if (loai === 'array') {
    const list = Array.isArray(value) ? value : [value]
    if (!list.length) return '—'
    if (DIEU_PHOI_STAFF_KEYS.has(key)) {
      return list
        .map((id) => userNameMap.value.get(Number(id)) || `#${id}`)
        .join(', ')
    }
    return list.map((item) => String(item)).join(', ')
  }
  return String(value)
}

function syncActiveSession() {
  const names = sessions.value.map((item) => item.key)
  if (!names.length) {
    activeSessionName.value = ''
    return
  }
  if (!names.includes(activeSessionName.value)) {
    activeSessionName.value = names[0]
  }
}

async function loadData() {
  if (!props.hopDongId) return

  loading.value = true
  hopDong.value = null
  loaiHopDong.value = null
  userOptions.value = []
  activeSessionName.value = ''

  try {
    const [hopDongRes, usersRes] = await Promise.all([
      getHopDongSuDungDichVu(props.hopDongId),
      fetchUsers({ per_page: 100, status: 'active' }),
    ])
    hopDong.value = hopDongRes.data
    userOptions.value = usersRes.data?.data || []

    const loaiId = hopDong.value?.loai_hop_dong_id
    if (loaiId) {
      try {
        const { data } = await getLoaiHopDong(loaiId)
        loaiHopDong.value = data
      } catch {
        loaiHopDong.value = hopDong.value?.loai_hop_dong || null
      }
    }
    syncActiveSession()
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
  activeSessionName.value = ''
  emit('closed')
}

watch(
  () => [props.modelValue, props.hopDongId],
  ([isOpen]) => {
    if (!isOpen) return
    loadData()
  },
)

watch(activeSessionName, (next, prev) => {
  const names = sessions.value.map((item) => item.key)
  const nextIndex = names.indexOf(String(next))
  const prevIndex = names.indexOf(String(prev))
  if (nextIndex === -1 || prevIndex === -1) {
    slideDirection.value = 'right'
    return
  }
  slideDirection.value = nextIndex < prevIndex ? 'left' : 'right'
})
</script>

<style scoped lang="scss">
.lich-body {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.shared-date-form {
  :deep(.el-form-item) {
    margin-bottom: 0;
  }
}

.session-tabs {
  :deep(.el-tabs__header) {
    margin-bottom: 0;
  }

  :deep(.el-tabs__content) {
    overflow: hidden;
    padding: 16px 16px 8px;
  }

  :deep(.el-tabs__item) {
    height: 40px;
    font-weight: 600;
  }

  :deep(.el-tab-pane) {
    animation: session-tab-in-right 0.32s cubic-bezier(0.22, 1, 0.36, 1);
  }

  &.is-slide-left :deep(.el-tab-pane) {
    animation-name: session-tab-in-left;
  }
}

.session-tab-label {
  display: inline-flex;
  align-items: center;
  max-width: 180px;
}

.session-tab-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.lich-block {
  margin: 4px 0 16px;
}

.lich-block__title {
  margin-bottom: 10px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.lich-empty {
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

@keyframes session-tab-in-right {
  from {
    opacity: 0;
    transform: translateX(28px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes session-tab-in-left {
  from {
    opacity: 0;
    transform: translateX(-28px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .session-tabs :deep(.el-tab-pane) {
    animation: none;
  }
}
</style>
