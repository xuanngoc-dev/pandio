<template>
  <CustomDialog
    v-if="!embedded"
    v-model="visible"
    :title="dialogTitle"
    :width="1280"
    class="hop-dong-sddv-dieu-phoi-modal"
    @closed="onClosed"
  >
    <div v-loading="loading" class="dieu-phoi-body">
      <el-empty
        v-if="!loading && !fields.length"
        description="Loại hợp đồng chưa cấu hình thông tin điều phối."
      />

      <el-empty
        v-else-if="!loading && !formModel.sessions.length"
        description="Chưa có lịch quay chụp. Vui lòng thêm lịch ở form hợp đồng."
      />

      <CustomForm
        v-else-if="fields.length && formModel.sessions.length"
        ref="formRef"
        :model="formModel"
        :rules="formRules"
        label-position="top"
      >
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
                :disabled="!canEditDieuPhoi"
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
          class="session-tabs"
          :editable="canEditSessions"
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
            :closable="canEditSessions && formModel.sessions.length > 1"
            lazy
          >
            <template #label>
              <span
                class="session-tab-label"
                :title="
                  canEditSessions && editingUid !== session._uid
                    ? 'Nhấp đúp hoặc bấm icon để đổi tên'
                    : displayTenLich(session, index)
                "
                @dblclick.stop="canEditSessions && startRename(session, index)"
              >
                <input
                  v-if="canEditSessions && editingUid === session._uid"
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
                    v-if="canEditSessions"
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
              :user-options="userOptions"
              :loai-quay-chup-options="loaiQuayChupOptions"
              :prop-prefix="`sessions.${index}`"
              :disabled="!canEditDieuPhoi"
              :lock-ngay-chup="Boolean(session._lockNgayChup)"
              require-dates
            />
          </el-tab-pane>
        </el-tabs>
      </CustomForm>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Hủy</CustomButton>
        <CustomButton
          type="primary"
          :loading="saving"
          :disabled="!canSave"
          @click="save"
        >
          Lưu
        </CustomButton>
      </div>
    </template>
  </CustomDialog>

  <div v-else v-loading="loading" class="dieu-phoi-body">
    <el-empty
      v-if="!loading && !fields.length"
      description="Loại hợp đồng chưa cấu hình thông tin điều phối."
    />

    <el-empty
      v-else-if="!loading && !formModel.sessions.length"
      description="Chưa có lịch quay chụp. Vui lòng thêm lịch ở form hợp đồng."
    />

    <CustomForm
      v-else-if="fields.length && formModel.sessions.length"
      ref="formRef"
      :model="formModel"
      :rules="formRules"
      label-position="top"
    >
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
              :disabled="!canEditDieuPhoi"
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
        class="session-tabs"
        :editable="canEditSessions"
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
          :closable="canEditSessions && formModel.sessions.length > 1"
          lazy
        >
          <template #label>
            <span
              class="session-tab-label"
              :title="
                canEditSessions && editingUid !== session._uid
                  ? 'Nhấp đúp hoặc bấm icon để đổi tên'
                  : displayTenLich(session, index)
              "
              @dblclick.stop="canEditSessions && startRename(session, index)"
            >
              <input
                v-if="canEditSessions && editingUid === session._uid"
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
                  v-if="canEditSessions"
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
            :user-options="userOptions"
            :loai-quay-chup-options="loaiQuayChupOptions"
            :prop-prefix="`sessions.${index}`"
            :disabled="!canEditDieuPhoi"
            :lock-ngay-chup="Boolean(session._lockNgayChup)"
            require-dates
          />
        </el-tab-pane>
      </el-tabs>
    </CustomForm>
  </div>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { EditPen } from '@element-plus/icons-vue'
import { fetchDanhMucLoaiQuayChup } from '@/api/danhMucLoaiQuayChup'
import { capNhatThongTinDieuPhoi, getHopDongSuDungDichVu } from '@/api/hopDongSuDungDichVu'
import { getLoaiHopDong } from '@/api/loaiHopDong'
import { fetchUsers } from '@/api/users'
import {
  CustomButton,
  CustomCol,
  CustomDialog,
  CustomForm,
  CustomFormItem,
  CustomIcon,
  CustomRow,
} from '@/components/element'
import { useAuthStore } from '@/stores/auth'
import {
  LOAI_QUAY_CHUP_KEY,
  MAX_LICH_QUAY_CHUP,
  SO_DIEM_CHUP_DEFAULT,
  SO_DIEM_CHUP_KEY,
  TEN_LICH_KEY,
  TEN_LICH_MAX_LENGTH,
  THO_DUNG_VIDEO_KEY,
  THO_DUNG_VIDEO_NGOAI_KEY,
  buildLoaiQuayChupField,
  buildTenLichField,
  clampSoDiemChup,
  clampStaffArrayValue,
  defaultTenLichQuayChup,
  insertDieuPhoiSchemaFields,
  buildDieuPhoiEnvelope,
  firstDieuPhoiGiaTri,
  getTenLichQuayChup,
  isDieuPhoiExtraSessionKey,
  isSharedLichQuayChupKey,
  normalizeDieuPhoiSessions,
  normalizeTenLichQuayChup,
  parseSessionLoaiQuayChup,
  loaiQuayChupRequiredRule,
  SAP_XEP_TRANG_PHUC_KEY,
  defaultSapXepTrangPhucGiaTri,
  normalizeSapXepTrangPhucValue,
  withTienKyIfStaffAssigned,
  SHARED_LICH_QUAY_CHUP_KEYS,
  emptySharedLichQuayChupDates,
  sharedLichQuayChupLabel,
} from '@/utils/thongTinDieuPhoi'
import HopDongSddvDieuPhoiSessionFields from './HopDongSddvDieuPhoiSessionFields.vue'

const REQUIRED_DATE_KEYS = new Set(['ngay_chup', ...SHARED_LICH_QUAY_CHUP_KEYS])

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  hopDongId: { type: [Number, String], default: null },
  /** Prefill / ghi đè ngày chụp trên mọi buổi khi mở form */
  defaultNgayChup: { type: String, default: '' },
  /** Render body only (dùng trong wizard / dialog cha) */
  embedded: { type: Boolean, default: false },
  /**
   * @deprecated Luôn bật thêm/xoá/đổi tên khi có quyền điều phối.
   * Giữ prop để tương thích nơi đang truyền (ThemModal).
   */
  allowEditSessions: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue', 'saved', 'closed'])

const authStore = useAuthStore()

const canEditDieuPhoi = computed(() => {
  const role = String(authStore.user?.role || '').toLowerCase()
  return role === 'admin' || role === 'coordinator'
})

/** Thêm / xoá / đổi tên buổi — giống bước lịch quay chụp */
const canEditSessions = computed(() => canEditDieuPhoi.value)

const canAddSession = computed(
  () => canEditSessions.value && formModel.sessions.length < MAX_LICH_QUAY_CHUP,
)

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const loading = ref(false)
const saving = ref(false)
const formRef = ref(null)
const hopDong = ref(null)
const fields = ref([])
const fieldMeta = ref({})
const userOptions = ref([])
const loaiQuayChupOptions = ref([])
const activeSessionName = ref('')
const slideDirection = ref('right')
const editingUid = ref(null)
const editingTitle = ref('')
const titleInputRef = ref(null)
let sessionUid = 0

const formModel = reactive({
  sessions: [],
  ...emptySharedLichQuayChupDates(),
})

const sessionFields = computed(() =>
  fields.value.filter((field) => !isSharedLichQuayChupKey(field.key)),
)
const sharedDateColProps = {
  xs: 24,
  sm: 12,
  md: 8,
  lg: 8,
  xl: 8,
}

const canSave = computed(
  () =>
    canEditDieuPhoi.value &&
    !loading.value &&
    fields.value.length > 0 &&
    formModel.sessions.length > 0,
)

const dialogTitle = computed(() => {
  const ma = hopDong.value?.ma_hop_dong
  return ma ? `Điều phối — ${ma}` : 'Điều phối'
})

const formRules = computed(() => {
  const rules = {
    sessions: [
      {
        type: 'array',
        required: true,
        min: 1,
        message: 'Cần ít nhất 1 lịch quay chụp',
        trigger: 'change',
      },
    ],
  }

  for (const [index] of formModel.sessions.entries()) {
    rules[`sessions.${index}.${LOAI_QUAY_CHUP_KEY}`] = [loaiQuayChupRequiredRule()]
    for (const field of sessionFields.value) {
      if (!isDateField(field)) continue
      rules[`sessions.${index}.${field.key}`] = [
        {
          required: true,
          message: `Vui lòng chọn ${String(field.ten_thong_tin || field.key).toLowerCase()}`,
          trigger: 'change',
        },
      ]
    }
  }

  return rules
})

function nextUid() {
  sessionUid += 1
  return sessionUid
}

function isDateField(field) {
  if (!field) return false
  if (field.loai_du_lieu === 'date') return true
  return REQUIRED_DATE_KEYS.has(field.key)
}

function startOfToday() {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return today
}

function disabledPastDate(date) {
  return date.getTime() < startOfToday().getTime()
}

function defaultValueByLoai(loai, key) {
  if (key === SO_DIEM_CHUP_KEY) return SO_DIEM_CHUP_DEFAULT
  if (key === SAP_XEP_TRANG_PHUC_KEY) return defaultSapXepTrangPhucGiaTri()
  return loai === 'array' ? [] : null
}

function normalizeScalarValue(value) {
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

function resolveLoaiDuLieu(key, item) {
  if (key === 'dia_diem_chup') return 'string'
  if (REQUIRED_DATE_KEYS.has(key)) return 'date'
  return item?.loai_du_lieu || 'string'
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

function startRename(session, index) {
  if (!canEditSessions.value || !session) return
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

function resolvedDefaultNgayChup() {
  const ngay = String(props.defaultNgayChup || '').slice(0, 10)
  return /^\d{4}-\d{2}-\d{2}$/.test(ngay) ? ngay : ''
}

function createEmptySession(index = formModel.sessions.length) {
  const values = {
    _uid: nextUid(),
    _ten_lich: defaultTenLichQuayChup(index),
    _lockNgayChup: false,
    [LOAI_QUAY_CHUP_KEY]: null,
  }
  for (const field of fields.value) {
    if (isSharedLichQuayChupKey(field.key)) continue
    values[field.key] = defaultValueByLoai(field.loai_du_lieu, field.key)
  }

  // Buổi mới / HĐ chưa có buổi: gán + khóa ngày từ lịch
  const ngay = resolvedDefaultNgayChup()
  if (ngay && fields.value.some((f) => f.key === 'ngay_chup')) {
    values.ngay_chup = ngay
    values._lockNgayChup = true
  }
  return values
}

function addSession() {
  if (!canEditSessions.value) return
  if (!canAddSession.value) {
    ElMessage.warning(`Tối đa ${MAX_LICH_QUAY_CHUP} lịch quay chụp.`)
    return
  }
  const session = createEmptySession(formModel.sessions.length)
  formModel.sessions.push(session)
  activeSessionName.value = sessionName(session)
}

function removeSessionByName(targetName) {
  if (!canEditSessions.value) return
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
  if (!canEditSessions.value) return
  if (action === 'add') {
    addSession()
    return
  }
  if (action === 'remove') {
    removeSessionByName(targetName)
  }
}

function sessionFromSaved(savedMap, index = 0) {
  const values = {
    _uid: nextUid(),
    _ten_lich: getTenLichQuayChup(savedMap, index),
    _lockNgayChup: false,
    [LOAI_QUAY_CHUP_KEY]: parseSessionLoaiQuayChup(savedMap),
  }
  const source = savedMap && typeof savedMap === 'object' && !Array.isArray(savedMap) ? savedMap : {}

  for (const field of fields.value) {
    if (isSharedLichQuayChupKey(field.key)) continue
    const savedItem = source[field.key] && typeof source[field.key] === 'object' ? source[field.key] : null
    const meta = fieldMeta.value[field.key] || {}
    const rawValue =
      savedItem?.gia_tri !== undefined
        ? savedItem.gia_tri
        : defaultValueByLoai(field.loai_du_lieu || meta.loai_du_lieu, field.key)

    if (field.key === SO_DIEM_CHUP_KEY) {
      values[field.key] = clampSoDiemChup(rawValue)
    } else if (field.key === SAP_XEP_TRANG_PHUC_KEY) {
      values[field.key] = normalizeSapXepTrangPhucValue(rawValue)
    } else if (field.loai_du_lieu === 'array') {
      values[field.key] = clampStaffArrayValue(field, rawValue)
    } else {
      values[field.key] = normalizeScalarValue(rawValue)
    }
  }

  // Buổi đã có: giữ nguyên ngày chụp, cho phép sửa
  return values
}

function buildFieldsFromSchema(schema) {
  const withVideoFields = insertDieuPhoiSchemaFields(
    schema && typeof schema === 'object' && !Array.isArray(schema) ? schema : {},
    [
      {
        key: THO_DUNG_VIDEO_KEY,
        ten_thong_tin: 'Thợ dựng video',
        loai_du_lieu: 'array',
        gia_tri_toi_da: 1,
      },
      {
        key: THO_DUNG_VIDEO_NGOAI_KEY,
        ten_thong_tin: 'Thợ dựng video ngoài',
        loai_du_lieu: 'string',
      },
    ],
    'quay_phim_ngoai',
  )
  const source = insertDieuPhoiSchemaFields(
    withVideoFields,
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

  for (const [key, item] of Object.entries(source)) {
    if (!item || typeof item !== 'object') continue
    if (item.su_dung === false) continue
    if (isDieuPhoiExtraSessionKey(key)) continue
    if (key === 'ngay_tra_chinh_thuc') continue

    const loai = resolveLoaiDuLieu(key, item)
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

  fields.value = nextFields
  fieldMeta.value = nextMeta
}

async function loadData() {
  if (!props.hopDongId) return

  loading.value = true
  fields.value = []
  fieldMeta.value = {}
  formModel.sessions = []
  Object.assign(formModel, emptySharedLichQuayChupDates())
  hopDong.value = null
  activeSessionName.value = ''

  try {
    const [hopDongRes, usersRes, loaiQuayChupRes] = await Promise.all([
      getHopDongSuDungDichVu(props.hopDongId),
      fetchUsers({ per_page: 100, status: 'active' }),
      fetchDanhMucLoaiQuayChup({ per_page: 100, trang_thai: 'active' }).catch(() => ({ data: { data: [] } })),
    ])

    hopDong.value = hopDongRes.data
    userOptions.value = usersRes.data.data || []
    loaiQuayChupOptions.value = (loaiQuayChupRes.data?.data || []).slice().sort((a, b) =>
      String(a.ten_dich_vu || '').localeCompare(String(b.ten_dich_vu || ''), 'vi'),
    )

    const loaiId = hopDong.value?.loai_hop_dong_id
    if (!loaiId) {
      ElMessage.warning('Hợp đồng chưa chọn loại hợp đồng.')
      return
    }

    const { data: loaiHopDong } = await getLoaiHopDong(loaiId)
    buildFieldsFromSchema(loaiHopDong?.thong_tin_dieu_phoi)

    const savedSessions = normalizeDieuPhoiSessions(hopDong.value?.thong_tin_dieu_phoi).slice(
      0,
      MAX_LICH_QUAY_CHUP,
    )
    if (savedSessions.length) {
      formModel.sessions = savedSessions.map((item, index) => sessionFromSaved(item, index))
    } else if (canEditDieuPhoi.value && fields.value.length) {
      formModel.sessions = [createEmptySession(0)]
    } else {
      formModel.sessions = []
    }
    for (const key of SHARED_LICH_QUAY_CHUP_KEYS) {
      formModel[key] = firstDieuPhoiGiaTri(hopDong.value?.thong_tin_dieu_phoi, key)
    }
    cancelRename()
    syncActiveSession()
  } catch {
    fields.value = []
    userOptions.value = []
    loaiQuayChupOptions.value = []
    formModel.sessions = []
    Object.assign(formModel, emptySharedLichQuayChupDates())
    activeSessionName.value = ''
  } finally {
    loading.value = false
  }
}

function buildPayload() {
  const built = formModel.sessions.map((session, index) => {
    const result = {
      [TEN_LICH_KEY]: buildTenLichField(session._ten_lich, index),
    }
    for (const field of fields.value) {
      if (isSharedLichQuayChupKey(field.key)) continue
      const meta = fieldMeta.value[field.key] || {}
      const loai = field.loai_du_lieu || 'string'
      let giaTri = session[field.key]

      if (field.key === SO_DIEM_CHUP_KEY) {
        giaTri = clampSoDiemChup(giaTri)
      } else if (field.key === SAP_XEP_TRANG_PHUC_KEY) {
        giaTri = normalizeSapXepTrangPhucValue(giaTri)
      } else if (loai === 'array') {
        giaTri = clampStaffArrayValue(field, giaTri)
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
    return result
  })

  return withTienKyIfStaffAssigned(
    buildDieuPhoiEnvelope(hopDong.value?.thong_tin_dieu_phoi, built, {
      ...Object.fromEntries(SHARED_LICH_QUAY_CHUP_KEYS.map((key) => [key, formModel[key]])),
    }),
  )
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

async function save() {
  if (!canEditDieuPhoi.value) return false
  if (!props.hopDongId || !fields.value.length) return false

  if (!formModel.sessions.length) {
    ElMessage.warning('Chưa có lịch quay chụp để điều phối.')
    return false
  }

  const valid = await formRef.value?.validate().catch((invalidFields) => {
    focusFirstInvalidSession(invalidFields)
    return false
  })
  if (!valid) {
    ElMessage.warning('Vui lòng điền đầy đủ các trường bắt buộc.')
    return false
  }

  saving.value = true
  try {
    const { data } = await capNhatThongTinDieuPhoi(props.hopDongId, {
      thong_tin_dieu_phoi: buildPayload(),
    })
    ElMessage.success('Đã lưu thông tin điều phối.')
    emit('saved', data)
    if (!props.embedded) {
      visible.value = false
    }
    return true
  } catch {
    // interceptor
    return false
  } finally {
    saving.value = false
  }
}

function resetState() {
  formRef.value?.clearValidate?.()
  fields.value = []
  fieldMeta.value = {}
  formModel.sessions = []
  Object.assign(formModel, emptySharedLichQuayChupDates())
  hopDong.value = null
  userOptions.value = []
  loaiQuayChupOptions.value = []
  activeSessionName.value = ''
  cancelRename()
  saving.value = false
}

function onClosed() {
  resetState()
  emit('closed')
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
})

watch(
  () => ({
    open: props.embedded ? Boolean(props.hopDongId) : props.modelValue,
    hopDongId: props.hopDongId,
    defaultNgayChup: props.defaultNgayChup,
  }),
  (state, prev) => {
    if (!state.open) {
      if (props.embedded && prev?.open) resetState()
      return
    }
    if (
      state.hopDongId &&
      (!prev ||
        !prev.open ||
        prev.hopDongId !== state.hopDongId ||
        prev.defaultNgayChup !== state.defaultNgayChup)
    ) {
      loadData()
    }
  },
  { immediate: true },
)

defineExpose({
  save,
  resetState,
  loading,
  saving,
  canSave,
})
</script>

<style scoped lang="scss">
.dieu-phoi-body {
  min-height: 180px;

  :deep(.el-form) {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
}

.shared-date-row {
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

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
