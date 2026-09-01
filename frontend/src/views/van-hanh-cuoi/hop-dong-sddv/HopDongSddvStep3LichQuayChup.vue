<template>
  <div class="step-panel" v-loading="loading">
    <CustomForm ref="formRef" :model="formModel" :rules="formRules" label-position="top">
      <template v-if="dieuPhoiFields.length">
        <CustomRow :gutter="16" class="shared-date-row">
          <CustomCol
            v-for="key in SHARED_LICH_QUAY_CHUP_KEYS"
            :key="key"
            v-bind="sharedDateColProps"
          >
            <CustomFormItem :label="sharedLichQuayChupLabel(key)" :prop="key">
              <el-date-picker
                v-model="formModel[key]"
                type="date"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                :placeholder="`Chọn ${sharedLichQuayChupLabel(key).toLowerCase()}`"
                :disabled-date="disabledPastDate"
                style="width: 100%"
                clearable
              />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>

        <el-tabs
          v-model="activeSessionName"
          type="border-card"
          editable
          class="session-tabs"
          :class="{
            'is-slide-left': slideDirection === 'left',
            'is-slide-right': slideDirection !== 'left',
            'is-max-sessions': !canAddSession,
          }"
          @edit="handleTabsEdit"
        >
        <el-tab-pane
          v-for="(session, index) in formModel.sessions"
          :key="session._uid"
          :name="String(session._uid)"
          :closable="formModel.sessions.length > 1"
          lazy
        >
          <template #label>
            <span
              class="session-tab-label"
              :title="editingUid === session._uid ? '' : 'Nhấp đúp hoặc bấm icon để đổi tên'"
              @dblclick.stop="startRename(session, index)"
            >
              <input
                v-if="editingUid === session._uid"
                :ref="setTitleInputRef"
                v-model="editingTitle"
                class="session-tab-input"
                :maxlength="TEN_LICH_MAX_LENGTH"
                @mousedown.stop
                @click.stop
                @keydown="onTitleKeydown($event, session, index)"
                @keyup.stop
                @blur="commitRename(session, index)"
              />
              <template v-else>
                <span class="session-tab-text">{{ displayTenLich(session, index) }}</span>
                <CustomIcon
                  class="session-tab-edit"
                  @mousedown.stop
                  @click.stop="startRename(session, index)"
                >
                  <EditPen />
                </CustomIcon>
              </template>
            </span>
          </template>
          <HopDongSddvDieuPhoiSessionFields
            v-model="formModel.sessions[index]"
            :fields="sessionFields"
            :loai-quay-chup-options="loaiQuayChupOptions"
            :prop-prefix="`sessions.${index}`"
            require-ngay-chup
          >
            <HopDongSddvConceptTrangPhucPicker
              :ref="(el) => setPickerRef(session._uid, el)"
              v-model:concepts="formModel.sessions[index].concepts"
              v-model:trang-phucs="formModel.sessions[index].trang_phucs"
              :ngay-chup="formModel.sessions[index].ngay_chup"
            />
          </HopDongSddvDieuPhoiSessionFields>
        </el-tab-pane>
      </el-tabs>
      </template>

      <CustomCard v-else shadow="never" class="info-card">
        <template #header>
          <span class="info-card-title">Thông tin điều phối</span>
        </template>
        <p class="info-card-empty">
          {{
            loaiHopDongId
              ? 'Loại hợp đồng này chưa cấu hình thông tin điều phối.'
              : 'Chọn loại hợp đồng ở bước Thông tin chung để hiển thị lịch quay chụp.'
          }}
        </p>
      </CustomCard>
    </CustomForm>
  </div>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { EditPen } from '@element-plus/icons-vue'
import { fetchDanhMucLoaiQuayChup } from '@/api/danhMucLoaiQuayChup'
import { getLoaiHopDong } from '@/api/loaiHopDong'
import { CustomCard, CustomCol, CustomForm, CustomFormItem, CustomIcon, CustomRow } from '@/components/element'
import {
  CONCEPT_FIELD_KEY,
  LICH_QUAY_CHUP_KEYS,
  LOAI_QUAY_CHUP_KEY,
  MAX_LICH_QUAY_CHUP,
  SO_DIEM_CHUP_DEFAULT,
  SO_DIEM_CHUP_KEY,
  SO_DIEM_CHUP_MAX,
  SO_DIEM_CHUP_MIN,
  TEN_LICH_KEY,
  TEN_LICH_MAX_LENGTH,
  TRANG_PHUC_FIELD_KEY,
  buildConceptField,
  buildLoaiQuayChupField,
  buildTenLichField,
  buildTrangPhucField,
  defaultTenLichQuayChup,
  buildDieuPhoiEnvelope,
  firstDieuPhoiGiaTri,
  getTenLichQuayChup,
  isSharedLichQuayChupKey,
  mapHopDongConceptRows,
  mapHopDongTrangPhucRows,
  normalizeDieuPhoiSessions,
  normalizeTenLichQuayChup,
  parseSessionConceptItems,
  parseSessionLoaiQuayChup,
  parseSessionTrangPhucItems,
  loaiQuayChupRequiredRule,
  SAP_XEP_TRANG_PHUC_KEY,
  defaultSapXepTrangPhucGiaTri,
  insertDieuPhoiSchemaFields,
  normalizeSapXepTrangPhucValue,
  SHARED_LICH_QUAY_CHUP_KEYS,
  emptySharedLichQuayChupDates,
  sharedLichQuayChupLabel,
} from '@/utils/thongTinDieuPhoi'
import HopDongSddvConceptTrangPhucPicker from './HopDongSddvConceptTrangPhucPicker.vue'
import HopDongSddvDieuPhoiSessionFields from './HopDongSddvDieuPhoiSessionFields.vue'

const props = defineProps({
  form: { type: Object, required: true },
  /** Tổng số điểm chụp từ combo đã chọn ở bước 2 */
  tongSoDiemChupCombo: { type: Number, default: 0 },
})

const formRef = ref(null)
const loading = ref(false)
const dieuPhoiFields = ref([])
const dieuPhoiMeta = ref({})
const loaiQuayChupOptions = ref([])
const activeSessionName = ref('')
const slideDirection = ref('right')
const editingUid = ref(null)
const editingTitle = ref('')
const titleInputRef = ref(null)
const pickerRefs = new Map()
let schemaLoadToken = 0
let sessionUid = 0

const formModel = reactive({
  sessions: [],
  ...emptySharedLichQuayChupDates(),
})

const loaiHopDongId = computed(() => props.form?.loai_hop_dong_id)
const canAddSession = computed(() => formModel.sessions.length < MAX_LICH_QUAY_CHUP)
const sessionFields = computed(() =>
  dieuPhoiFields.value.filter((field) => !isSharedLichQuayChupKey(field.key)),
)
const sharedDateColProps = {
  xs: 24,
  sm: 12,
  md: 8,
  lg: 8,
  xl: 8,
}

const formRules = computed(() => {
  const rules = {}
  if (!dieuPhoiFields.value.length) return rules

  rules.sessions = [
    {
      type: 'array',
      required: true,
      min: 1,
      message: 'Cần ít nhất 1 lịch quay chụp',
      trigger: 'change',
    },
  ]

  formModel.sessions.forEach((_, index) => {
    rules[`sessions.${index}.${LOAI_QUAY_CHUP_KEY}`] = [loaiQuayChupRequiredRule()]
  })

  const hasNgayChup = dieuPhoiFields.value.some((field) => field.key === 'ngay_chup')
  if (!hasNgayChup) return rules

  formModel.sessions.forEach((_, index) => {
    rules[`sessions.${index}.ngay_chup`] = [
      {
        required: true,
        message: 'Vui lòng chọn ngày chụp',
        trigger: 'change',
      },
    ]
  })

  return rules
})

function nextUid() {
  sessionUid += 1
  return sessionUid
}

function clampSoDiemChupForSchema(value) {
  const field = dieuPhoiFields.value.find((item) => item.key === SO_DIEM_CHUP_KEY)
  const n = Math.round(Number(value))
  if (!Number.isFinite(n)) return SO_DIEM_CHUP_DEFAULT
  const min = Number(field?.gia_tri_toi_thieu ?? SO_DIEM_CHUP_MIN)
  const max = Number(field?.gia_tri_toi_da ?? SO_DIEM_CHUP_MAX)
  let result = n
  if (Number.isFinite(min)) result = Math.max(min, result)
  if (Number.isFinite(max)) result = Math.min(max, result)
  return result
}

function defaultSoDiemChup(sessionIndex = 0) {
  if (sessionIndex !== 0) return SO_DIEM_CHUP_DEFAULT
  const total = Math.max(0, Math.round(Number(props.tongSoDiemChupCombo) || 0))
  if (total <= 0) return SO_DIEM_CHUP_DEFAULT
  return clampSoDiemChupForSchema(total)
}

function hasSavedDieuPhoiSessions() {
  return normalizeDieuPhoiSessions(props.form?.thong_tin_dieu_phoi).length > 0
}

function defaultValue(loai, key, sessionIndex = 0) {
  if (key === SO_DIEM_CHUP_KEY) return defaultSoDiemChup(sessionIndex)
  if (key === SAP_XEP_TRANG_PHUC_KEY) return defaultSapXepTrangPhucGiaTri()
  return loai === 'array' ? [] : null
}

function normalizeArray(value) {
  if (Array.isArray(value)) return [...value]
  if (value == null || value === '') return []
  return [value]
}

function normalizeScalar(value) {
  if (Array.isArray(value)) {
    const text = value
      .map((item) => String(item).trim())
      .filter(Boolean)
      .join(', ')
    return text || null
  }
  if (value == null || value === '') return null
  return value
}

function resolveLoai(key, item) {
  if (key === 'dia_diem_chup') return 'string'
  return item?.loai_du_lieu || 'string'
}

function createEmptySessionValues(index = formModel.sessions.length) {
  const values = {
    _uid: nextUid(),
    _ten_lich: defaultTenLichQuayChup(index),
    concepts: [],
    trang_phucs: [],
    [LOAI_QUAY_CHUP_KEY]: null,
  }
  for (const field of dieuPhoiFields.value) {
    if (isSharedLichQuayChupKey(field.key)) continue
    values[field.key] = defaultValue(field.loai_du_lieu, field.key, index)
  }
  return values
}

function sessionValuesFromSaved(savedMap, index = 0) {
  const values = {
    _uid: nextUid(),
    _ten_lich: getTenLichQuayChup(savedMap, index),
    concepts: [],
    trang_phucs: [],
    [LOAI_QUAY_CHUP_KEY]: parseSessionLoaiQuayChup(savedMap),
  }
  const source = savedMap && typeof savedMap === 'object' && !Array.isArray(savedMap) ? savedMap : {}

  for (const field of dieuPhoiFields.value) {
    if (isSharedLichQuayChupKey(field.key)) continue
    const savedItem = source[field.key] && typeof source[field.key] === 'object' ? source[field.key] : null
    const rawValue = savedItem?.gia_tri !== undefined ? savedItem.gia_tri : defaultValue(field.loai_du_lieu, field.key)
    if (field.key === SO_DIEM_CHUP_KEY) {
      values[field.key] = clampSoDiemChupForSchema(rawValue)
    } else if (field.key === SAP_XEP_TRANG_PHUC_KEY) {
      values[field.key] = normalizeSapXepTrangPhucValue(rawValue)
    } else {
      values[field.key] =
        field.loai_du_lieu === 'array' ? normalizeArray(rawValue) : normalizeScalar(rawValue)
    }
  }

  const ngayChup = values.ngay_chup || null
  const fromDieuPhoiConcepts = parseSessionConceptItems(source)
  const fromDieuPhoiTrangPhucs = parseSessionTrangPhucItems(source)
  values.concepts = fromDieuPhoiConcepts.length
    ? fromDieuPhoiConcepts
    : mapHopDongConceptRows(props.form?.concepts, ngayChup)
  values.trang_phucs = fromDieuPhoiTrangPhucs.length
    ? fromDieuPhoiTrangPhucs
    : mapHopDongTrangPhucRows(props.form?.trang_phucs, ngayChup)

  if (index === 0) {
    if (!values.concepts.length) {
      values.concepts = mapHopDongConceptRows(props.form?.concepts, null)
    }
    if (!values.trang_phucs.length) {
      values.trang_phucs = mapHopDongTrangPhucRows(props.form?.trang_phucs, null)
    }
  }

  return values
}

function buildFieldsFromSchema(schema) {
  const source = insertDieuPhoiSchemaFields(
    schema && typeof schema === 'object' && !Array.isArray(schema) ? schema : {},
    [
      {
        key: SAP_XEP_TRANG_PHUC_KEY,
        ten_thong_tin: 'Sắp xếp trang phục',
        loai_du_lieu: 'string',
      },
    ],
    'so_diem_chup',
  )
  const nextFields = []
  const nextMeta = {}

  for (const key of LICH_QUAY_CHUP_KEYS) {
    const item = source[key]
    if (!item || typeof item !== 'object') continue
    if (item.su_dung !== true) continue

    const loai = resolveLoai(key, item)
    nextFields.push({
      key,
      ten_thong_tin: item.ten_thong_tin || key,
      loai_du_lieu: loai,
      gia_tri_toi_thieu: item.gia_tri_toi_thieu,
      gia_tri_toi_da: item.gia_tri_toi_da,
    })
    nextMeta[key] = {
      su_dung: true,
      ten_thong_tin: item.ten_thong_tin || key,
      loai_du_lieu: loai,
    }
  }

  dieuPhoiFields.value = nextFields
  dieuPhoiMeta.value = nextMeta
}

function sessionName(session) {
  return String(session?._uid ?? '')
}

function syncActiveSession(preferredName = '') {
  const names = formModel.sessions.map(sessionName).filter(Boolean)
  if (!names.length) {
    activeSessionName.value = ''
    return
  }
  if (preferredName && names.includes(String(preferredName))) {
    activeSessionName.value = String(preferredName)
    return
  }
  if (!names.includes(String(activeSessionName.value))) {
    activeSessionName.value = names[0]
  }
}

function displayTenLich(session, index) {
  const name = String(session?._ten_lich || '').trim()
  return name || defaultTenLichQuayChup(index)
}

function setTitleInputRef(el) {
  if (el) titleInputRef.value = el
}

function setPickerRef(uid, el) {
  if (el) {
    pickerRefs.set(uid, el)
    return
  }
  pickerRefs.delete(uid)
}

function loadActivePickerOptions() {
  const uid = Number(activeSessionName.value)
  const picker = pickerRefs.get(uid)
  picker?.loadOptions?.()
}

function startRename(session, index) {
  if (!session) return
  activeSessionName.value = sessionName(session)
  editingUid.value = session._uid
  editingTitle.value = displayTenLich(session, index)
  nextTick(() => {
    titleInputRef.value?.focus?.()
    titleInputRef.value?.select?.()
  })
}

function onTitleKeydown(event, session, index) {
  event.stopPropagation()
  if (event.key === 'Enter') {
    event.preventDefault()
    commitRename(session, index)
    return
  }
  if (event.key === 'Escape') {
    event.preventDefault()
    cancelRename()
  }
}

function commitRename(session, index) {
  if (!session || editingUid.value !== session._uid) return
  session._ten_lich = normalizeTenLichQuayChup(editingTitle.value, index)
  editingUid.value = null
  editingTitle.value = ''
}

function cancelRename() {
  editingUid.value = null
  editingTitle.value = ''
}

function hydrateSharedDatesFromForm() {
  const raw = props.form?.thong_tin_dieu_phoi
  for (const key of SHARED_LICH_QUAY_CHUP_KEYS) {
    formModel[key] = firstDieuPhoiGiaTri(raw, key)
  }
}

function resetSharedDates() {
  Object.assign(formModel, emptySharedLichQuayChupDates())
}

function startOfToday() {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return today
}

function disabledPastDate(date) {
  return date.getTime() < startOfToday().getTime()
}

function hydrateSessionsFromForm() {
  const savedSessions = normalizeDieuPhoiSessions(props.form?.thong_tin_dieu_phoi).slice(
    0,
    MAX_LICH_QUAY_CHUP,
  )
  if (savedSessions.length) {
    formModel.sessions = savedSessions.map((item, index) => sessionValuesFromSaved(item, index))
  } else {
    formModel.sessions = dieuPhoiFields.value.length ? [createEmptySessionValues(0)] : []
    applyComboSoDiemChupDefault()
  }
  hydrateSharedDatesFromForm()
  cancelRename()
  syncActiveSession()
}

function applyComboSoDiemChupDefault() {
  if (!dieuPhoiFields.value.some((field) => field.key === SO_DIEM_CHUP_KEY)) return
  if (hasSavedDieuPhoiSessions()) return
  const value = defaultSoDiemChup(0)
  const firstSession = formModel.sessions[0]
  if (firstSession) {
    firstSession[SO_DIEM_CHUP_KEY] = value
  }
}

function addSession() {
  if (!canAddSession.value) {
    ElMessage.warning(`Tối đa ${MAX_LICH_QUAY_CHUP} lịch quay chụp.`)
    return
  }
  const session = createEmptySessionValues(formModel.sessions.length)
  formModel.sessions.push(session)
  activeSessionName.value = sessionName(session)
}

function removeSessionByName(targetName) {
  if (formModel.sessions.length <= 1) {
    ElMessage.warning('Cần ít nhất 1 lịch quay chụp.')
    return
  }

  const name = String(targetName ?? '')
  const index = formModel.sessions.findIndex((session) => sessionName(session) === name)
  if (index === -1) return

  let nextActive = activeSessionName.value
  if (sessionName(formModel.sessions[index]) === String(activeSessionName.value)) {
    const nextSession = formModel.sessions[index + 1] || formModel.sessions[index - 1]
    nextActive = sessionName(nextSession)
  }

  if (String(editingUid.value) === name) {
    cancelRename()
  }

  formModel.sessions.splice(index, 1)
  syncActiveSession(nextActive)
}

function handleTabsEdit(targetName, action) {
  if (action === 'add') {
    addSession()
    return
  }
  if (action === 'remove') {
    removeSessionByName(targetName)
  }
}

async function loadDieuPhoiSchema() {
  const loaiId = loaiHopDongId.value
  schemaLoadToken += 1
  const token = schemaLoadToken

  if (!loaiId) {
    dieuPhoiFields.value = []
    dieuPhoiMeta.value = {}
    loaiQuayChupOptions.value = []
    formModel.sessions = []
    activeSessionName.value = ''
    resetSharedDates()
    cancelRename()
    return
  }

  loading.value = true
  try {
    const [{ data: loaiHopDong }, loaiQuayChupRes] = await Promise.all([
      getLoaiHopDong(loaiId),
      fetchDanhMucLoaiQuayChup({ per_page: 100, trang_thai: 'active' }).catch(() => ({ data: { data: [] } })),
    ])
    if (token !== schemaLoadToken) return
    loaiQuayChupOptions.value = (loaiQuayChupRes.data?.data || []).slice().sort((a, b) =>
      String(a.ten_dich_vu || '').localeCompare(String(b.ten_dich_vu || ''), 'vi'),
    )
    buildFieldsFromSchema(loaiHopDong?.thong_tin_dieu_phoi)
    hydrateSessionsFromForm()
    nextTick(() => loadActivePickerOptions())
  } catch {
    if (token !== schemaLoadToken) return
    dieuPhoiFields.value = []
    dieuPhoiMeta.value = {}
    loaiQuayChupOptions.value = []
    formModel.sessions = []
    activeSessionName.value = ''
    resetSharedDates()
    cancelRename()
  } finally {
    if (token === schemaLoadToken) loading.value = false
  }
}

function getDieuPhoiPayload(existing = null) {
  const source =
    existing !== null && existing !== undefined
      ? existing
      : props.form?.thong_tin_dieu_phoi

  const sharedDates = Object.fromEntries(
    SHARED_LICH_QUAY_CHUP_KEYS.map((key) => [key, formModel[key]]),
  )

  if (!dieuPhoiFields.value.length) {
    return buildDieuPhoiEnvelope(source, normalizeDieuPhoiSessions(source), sharedDates)
  }

  const built = formModel.sessions.map((session, index) => {
    const result = {
      [TEN_LICH_KEY]: buildTenLichField(session._ten_lich, index),
    }
    for (const field of dieuPhoiFields.value) {
      if (isSharedLichQuayChupKey(field.key)) continue
      const meta = dieuPhoiMeta.value[field.key] || {}
      const loai = field.loai_du_lieu || 'string'
      let giaTri = session[field.key]

      if (field.key === SO_DIEM_CHUP_KEY) {
        giaTri = clampSoDiemChupForSchema(giaTri)
      } else if (field.key === SAP_XEP_TRANG_PHUC_KEY) {
        giaTri = normalizeSapXepTrangPhucValue(giaTri)
      } else if (loai === 'array') {
        giaTri = normalizeArray(giaTri)
      } else if (giaTri === '' || giaTri === undefined) {
        giaTri = null
      }

      result[field.key] = {
        su_dung: true,
        ten_thong_tin: meta.ten_thong_tin || field.ten_thong_tin,
        loai_du_lieu: loai,
        gia_tri: giaTri,
      }
    }
    result[LOAI_QUAY_CHUP_KEY] = buildLoaiQuayChupField(session[LOAI_QUAY_CHUP_KEY])
    result[CONCEPT_FIELD_KEY] = buildConceptField(session.concepts)
    result[TRANG_PHUC_FIELD_KEY] = buildTrangPhucField(session.trang_phucs)
    return result
  })

  return buildDieuPhoiEnvelope(source, built, sharedDates)
}

function getConceptTrangPhucPayload() {
  const concepts = []
  const trangPhucs = []

  for (const session of formModel.sessions) {
    const ngaySuDung = session?.ngay_chup || null
    for (const item of session.concepts || []) {
      if (!item?.id) continue
      concepts.push({
        concept_id: item.id,
        ngay_su_dung: ngaySuDung,
      })
    }
    for (const item of session.trang_phucs || []) {
      if (!item?.id) continue
      trangPhucs.push({
        trang_phuc_id: item.id,
        ngay_su_dung: ngaySuDung,
        ngay_bat_dau: item.ngay_bat_dau || ngaySuDung,
        ngay_ket_thuc: item.ngay_ket_thuc || ngaySuDung,
      })
    }
  }

  return {
    concepts: uniqueByKey(concepts, (item) => `${item.concept_id}|${item.ngay_su_dung || ''}`),
    trang_phucs: uniqueByKey(
      trangPhucs,
      (item) => `${item.trang_phuc_id}|${item.ngay_su_dung || ''}`,
    ),
  }
}

function uniqueByKey(items, getKey) {
  const seen = new Set()
  const result = []
  for (const item of items) {
    const key = getKey(item)
    if (seen.has(key)) continue
    seen.add(key)
    result.push(item)
  }
  return result
}

function focusFirstInvalidSession(invalidFields) {
  if (!invalidFields || typeof invalidFields !== 'object') return
  for (const key of Object.keys(invalidFields)) {
    const match = key.match(/^sessions\.(\d+)/)
    if (!match) continue
    const session = formModel.sessions[Number(match[1])]
    if (session) {
      activeSessionName.value = sessionName(session)
      return
    }
  }
}

async function validate() {
  if (!dieuPhoiFields.value.length) return true
  if (!formModel.sessions.length) {
    ElMessage.warning('Cần ít nhất 1 lịch quay chụp.')
    return false
  }
  const valid = await formRef.value?.validate().catch((invalidFields) => {
    focusFirstInvalidSession(invalidFields)
    return false
  })
  if (!valid) {
    ElMessage.warning('Vui lòng nhập đầy đủ thông tin lịch quay chụp.')
  }
  return Boolean(valid)
}

function hydrate() {
  if (dieuPhoiFields.value.length) {
    hydrateSessionsFromForm()
    nextTick(() => loadActivePickerOptions())
    return
  }
  return loadDieuPhoiSchema()
}

function reset() {
  schemaLoadToken += 1
  dieuPhoiFields.value = []
  dieuPhoiMeta.value = {}
  loaiQuayChupOptions.value = []
  formModel.sessions = []
  activeSessionName.value = ''
  resetSharedDates()
  pickerRefs.clear()
  cancelRename()
  formRef.value?.clearValidate?.()
}

watch(activeSessionName, (next, prev) => {
  const names = formModel.sessions.map(sessionName)
  const nextIndex = names.indexOf(String(next))
  const prevIndex = names.indexOf(String(prev))
  if (nextIndex === -1 || prevIndex === -1) {
    slideDirection.value = 'right'
  } else {
    slideDirection.value = nextIndex < prevIndex ? 'left' : 'right'
  }
  nextTick(() => loadActivePickerOptions())
})

watch(
  () => props.tongSoDiemChupCombo,
  () => {
    applyComboSoDiemChupDefault()
  },
)

defineExpose({
  validate,
  getDieuPhoiPayload,
  getConceptTrangPhucPayload,
  loadDieuPhoiSchema,
  loadActivePickerOptions,
  applyComboSoDiemChupDefault,
  hydrate,
  reset,
})
</script>

<style scoped lang="scss">
.step-panel {
  min-height: 220px;

  :deep(.el-form) {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
}

.session-tabs {
  :deep(.el-tabs__header) {
    margin-bottom: 0;
  }

  :deep(.el-tabs__content) {
    overflow: hidden;
    padding: 16px 16px 4px;
  }

  :deep(.el-tabs__new-tab) {
    width: 22px;
    height: 22px;
    margin: 10px 12px 10px auto;
    border-radius: 4px;
  }

  &.is-max-sessions :deep(.el-tabs__new-tab) {
    display: none;
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
  gap: 6px;
  max-width: 180px;
}

.session-tab-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.session-tab-edit {
  flex-shrink: 0;
  font-size: 13px;
  color: var(--el-text-color-secondary);
  opacity: 0;
  transition: opacity 0.15s ease, color 0.15s ease;
  cursor: pointer;
}

.session-tabs :deep(.el-tabs__item:hover) .session-tab-edit,
.session-tabs :deep(.el-tabs__item.is-active) .session-tab-edit {
  opacity: 1;
}

.session-tab-edit:hover {
  color: var(--el-color-primary);
}

.session-tab-input {
  width: 148px;
  height: 24px;
  padding: 0 8px;
  border: 1px solid var(--el-color-primary);
  border-radius: 4px;
  background: var(--el-bg-color);
  color: var(--el-text-color-primary);
  font: inherit;
  font-size: 13px;
  font-weight: 600;
  line-height: 22px;
  outline: none;
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

.info-card {
  border: 1px solid var(--el-border-color-lighter);
}

.info-card :deep(.el-card__header) {
  padding: 10px 16px;
  background: var(--el-fill-color-light);
}

.info-card :deep(.el-card__body) {
  padding: 12px 16px 4px;
}

.info-card-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.info-card-empty {
  margin: 0;
  padding: 4px 0 12px;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.shared-date-row {
  :deep(.el-form-item) {
    margin-bottom: 12px;
  }
}
</style>
