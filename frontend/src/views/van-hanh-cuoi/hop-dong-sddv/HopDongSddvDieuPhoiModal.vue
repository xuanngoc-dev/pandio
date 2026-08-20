<template>
  <CustomDialog
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

      <CustomForm
        v-else-if="fields.length"
        ref="formRef"
        :model="formModel"
        :rules="formRules"
        label-position="top"
      >
        <CustomCard
          v-for="(session, index) in formModel.sessions"
          :key="session._uid"
          shadow="never"
          class="session-card"
        >
          <template #header>
            <div class="session-header">
              <span class="session-title">
                {{ formModel.sessions.length > 1 ? `Lịch quay chụp ${index + 1}` : 'Lịch quay chụp' }}
              </span>
              <CustomButton
                type="danger"
                link
                :disabled="formModel.sessions.length <= 1"
                @click="removeSession(index)"
              >
                Xóa
              </CustomButton>
            </div>
          </template>
          <HopDongSddvDieuPhoiSessionFields
            v-model="formModel.sessions[index]"
            :fields="fields"
            :user-options="userOptions"
            :prop-prefix="`sessions.${index}`"
            require-dates
          />
        </CustomCard>

        <div v-if="formModel.sessions.length < MAX_LICH_QUAY_CHUP" class="add-session-wrap">
          <CustomButton type="primary" plain @click="addSession">
            Thêm lịch quay chụp
          </CustomButton>
        </div>
      </CustomForm>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Hủy</CustomButton>
        <CustomButton
          type="primary"
          :loading="saving"
          :disabled="loading || !fields.length"
          @click="save"
        >
          Lưu
        </CustomButton>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import {
  getHopDongSuDungDichVu,
  updateHopDongSuDungDichVu,
} from '@/api/hopDongSuDungDichVu'
import { getLoaiHopDong } from '@/api/loaiHopDong'
import { fetchUsers } from '@/api/users'
import {
  CustomButton,
  CustomCard,
  CustomDialog,
  CustomForm,
} from '@/components/element'
import { MAX_LICH_QUAY_CHUP, normalizeDieuPhoiSessions } from '@/utils/thongTinDieuPhoi'
import HopDongSddvDieuPhoiSessionFields from './HopDongSddvDieuPhoiSessionFields.vue'

const REQUIRED_DATE_KEYS = new Set([
  'ngay_chup',
  'ngay_tra_demo',
  'ngay_tra_chinh_thuc',
])

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  hopDongId: { type: [Number, String], default: null },
})

const emit = defineEmits(['update:modelValue', 'saved', 'closed'])

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
let sessionUid = 0

const formModel = reactive({
  sessions: [],
})

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
    for (const field of fields.value) {
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

function defaultValueByLoai(loai) {
  return loai === 'array' ? [] : null
}

function normalizeArrayValue(value) {
  if (Array.isArray(value)) return [...value]
  if (value == null || value === '') return []
  return [value]
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

function createEmptySession() {
  const values = { _uid: nextUid() }
  for (const field of fields.value) {
    values[field.key] = defaultValueByLoai(field.loai_du_lieu)
  }
  return values
}

function sessionFromSaved(savedMap) {
  const values = { _uid: nextUid() }
  const source = savedMap && typeof savedMap === 'object' && !Array.isArray(savedMap) ? savedMap : {}

  for (const field of fields.value) {
    const savedItem = source[field.key] && typeof source[field.key] === 'object' ? source[field.key] : null
    const meta = fieldMeta.value[field.key] || {}
    const rawValue =
      savedItem?.gia_tri !== undefined
        ? savedItem.gia_tri
        : defaultValueByLoai(field.loai_du_lieu || meta.loai_du_lieu)

    values[field.key] =
      field.loai_du_lieu === 'array'
        ? normalizeArrayValue(rawValue)
        : normalizeScalarValue(rawValue)
  }

  return values
}

function buildFieldsFromSchema(schema) {
  const source =
    schema && typeof schema === 'object' && !Array.isArray(schema) ? schema : {}

  const nextFields = []
  const nextMeta = {}

  for (const [key, item] of Object.entries(source)) {
    if (!item || typeof item !== 'object') continue
    if (item.su_dung === false) continue

    const loai = resolveLoaiDuLieu(key, item)
    nextFields.push({
      key,
      ten_thong_tin: item.ten_thong_tin || key,
      loai_du_lieu: loai,
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

function addSession() {
  if (formModel.sessions.length >= MAX_LICH_QUAY_CHUP) {
    ElMessage.warning(`Tối đa ${MAX_LICH_QUAY_CHUP} lịch quay chụp.`)
    return
  }
  formModel.sessions.push(createEmptySession())
}

function removeSession(index) {
  if (formModel.sessions.length <= 1) {
    ElMessage.warning('Cần ít nhất 1 lịch quay chụp.')
    return
  }
  formModel.sessions.splice(index, 1)
}

async function loadData() {
  if (!props.hopDongId) return

  loading.value = true
  fields.value = []
  fieldMeta.value = {}
  formModel.sessions = []
  hopDong.value = null

  try {
    const [hopDongRes, usersRes] = await Promise.all([
      getHopDongSuDungDichVu(props.hopDongId),
      fetchUsers({ per_page: 100, status: 'active' }),
    ])

    hopDong.value = hopDongRes.data
    userOptions.value = usersRes.data.data || []

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
    formModel.sessions = savedSessions.length
      ? savedSessions.map((item) => sessionFromSaved(item))
      : fields.value.length
        ? [createEmptySession()]
        : []
  } catch {
    fields.value = []
    userOptions.value = []
    formModel.sessions = []
  } finally {
    loading.value = false
  }
}

function buildPayload() {
  return formModel.sessions.map((session) => {
    const result = {}
    for (const field of fields.value) {
      const meta = fieldMeta.value[field.key] || {}
      const loai = field.loai_du_lieu || 'string'
      let giaTri = session[field.key]

      if (loai === 'array') {
        giaTri = normalizeArrayValue(giaTri)
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
    return result
  })
}

async function save() {
  if (!props.hopDongId || !fields.value.length) return

  if (!formModel.sessions.length) {
    ElMessage.warning('Cần ít nhất 1 lịch quay chụp.')
    return
  }

  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) {
    ElMessage.warning('Vui lòng điền đầy đủ các trường ngày bắt buộc.')
    return
  }

  saving.value = true
  try {
    const { data } = await updateHopDongSuDungDichVu(props.hopDongId, {
      thong_tin_dieu_phoi: buildPayload(),
    })
    ElMessage.success('Đã lưu thông tin điều phối.')
    emit('saved', data)
    visible.value = false
  } catch {
    // interceptor
  } finally {
    saving.value = false
  }
}

function onClosed() {
  formRef.value?.clearValidate?.()
  fields.value = []
  fieldMeta.value = {}
  formModel.sessions = []
  hopDong.value = null
  userOptions.value = []
  saving.value = false
  emit('closed')
}

watch(
  () => props.modelValue,
  (isOpen) => {
    if (!isOpen) return
    loadData()
  },
)
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

.session-card {
  border: 1px solid var(--el-border-color-lighter);
}

.session-card :deep(.el-card__header) {
  padding: 10px 16px;
  background: var(--el-fill-color-light);
}

.session-card :deep(.el-card__body) {
  padding: 12px 16px 4px;
}

.session-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.session-title {
  font-size: 14px;
  font-weight: 600;
}

.add-session-wrap {
  display: flex;
  justify-content: flex-start;
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
