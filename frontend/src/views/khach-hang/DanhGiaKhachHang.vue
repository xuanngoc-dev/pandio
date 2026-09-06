<template>
  <div class="danh-gia-page">
    <div class="danh-gia-shell">
      <div v-if="loading" class="state-box" v-loading="true">Đang tải form...</div>

      <div v-else-if="loadError" class="state-box state-box--error">
        <h2>Không tìm thấy form</h2>
        <p>{{ loadError }}</p>
      </div>

      <div v-else-if="submitted" class="state-box state-box--success">
        <h2>Cảm ơn bạn!</h2>
        <p>Đánh giá của bạn đã được ghi nhận. Chúng tôi rất trân trọng phản hồi này.</p>
      </div>

      <template v-else>
        <header class="danh-gia-header">
          <p class="eyebrow">Đánh giá dịch vụ</p>
          <h1>{{ formDef.ten_form }}</h1>
          <p v-if="alreadySubmitted" class="subtitle subtitle--done">
            Bạn đã gửi đánh giá. Dưới đây là kết quả đã ghi nhận.
          </p>
          <p v-else class="subtitle">Vui lòng dành ít phút chia sẻ trải nghiệm của bạn.</p>
        </header>

        <CustomForm
          ref="formRef"
          :model="answers"
          label-position="top"
          class="danh-gia-form"
          @submit.prevent="onSubmit"
        >
          <div
            v-for="(question, index) in questions"
            :key="`${index}-${question.cau_hoi}`"
            class="question-block"
          >
            <div class="question-meta">
              <span class="question-no">Câu {{ index + 1 }}</span>
              <CustomTag v-if="question.thong_tin_danh_gia" type="info" effect="plain" size="small">
                {{ question.thong_tin_danh_gia }}
              </CustomTag>
              <CustomTag
                v-if="question.required && !alreadySubmitted"
                type="danger"
                effect="plain"
                size="small"
              >
                Bắt buộc
              </CustomTag>
            </div>

            <CustomFormItem
              :label="question.cau_hoi"
              :prop="`q_${index}`"
              :rules="alreadySubmitted ? [] : rulesFor(question)"
            >
              <el-rate
                v-if="question.loai_danh_gia === 'diem'"
                v-model="answers[`q_${index}`]"
                :colors="['#f7ba2a', '#f7ba2a', '#f7ba2a']"
                allow-half
                show-score
                score-template="{value} điểm"
                :disabled="alreadySubmitted"
              />
              <CustomInput
                v-else
                v-model="answers[`q_${index}`]"
                type="textarea"
                :rows="3"
                placeholder="Nhập phản hồi của bạn..."
                :disabled="alreadySubmitted"
              />
            </CustomFormItem>
          </div>

          <CustomButton
            v-if="!alreadySubmitted"
            type="primary"
            size="large"
            class="submit-btn"
            :loading="submitting"
            @click="onSubmit"
          >
            Gửi đánh giá
          </CustomButton>
        </CustomForm>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getFormDanhGiaBySlug, nopFormDanhGia } from '@/api/formDanhGia'
import {
  CustomButton,
  CustomForm,
  CustomFormItem,
  CustomInput,
  CustomTag,
} from '@/components/element'

const route = useRoute()

const loading = ref(true)
const loadError = ref('')
const submitting = ref(false)
const submitted = ref(false)
const alreadySubmitted = ref(false)
const formRef = ref(null)

const formDef = reactive({
  id: null,
  ten_form: '',
  slug: '',
  cau_hoi: [],
})

const answers = reactive({})

const questions = computed(() =>
  Array.isArray(formDef.cau_hoi) ? formDef.cau_hoi : []
)

const hopDongDanhGiaId = computed(() => {
  const raw = route.query.hop_dong_danh_gia_id
  const id = Number(Array.isArray(raw) ? raw[0] : raw)
  return Number.isFinite(id) && id > 0 ? id : null
})

function rulesFor(question) {
  if (!question.required) return []
  if (question.loai_danh_gia === 'diem') {
    return [
      {
        required: true,
        validator: (_rule, value, callback) => {
          if (!value || Number(value) <= 0) {
            callback(new Error('Vui lòng chọn điểm đánh giá'))
          } else {
            callback()
          }
        },
        trigger: 'change',
      },
    ]
  }
  return [{ required: true, message: 'Vui lòng nhập phản hồi', trigger: 'blur' }]
}

function questionKey(item) {
  return [
    String(item?.cau_hoi || '').trim().toLowerCase(),
    String(item?.thong_tin_danh_gia || '').trim().toLowerCase(),
    String(item?.loai_danh_gia || '').trim(),
  ].join('||')
}

function initAnswers(noiDung = null) {
  Object.keys(answers).forEach((key) => delete answers[key])

  const submittedList = Array.isArray(noiDung) ? noiDung : []
  const byKey = new Map(submittedList.map((item) => [questionKey(item), item]))

  questions.value.forEach((q, index) => {
    const matched = byKey.get(questionKey(q)) || submittedList[index] || null
    if (q.loai_danh_gia === 'diem') {
      const raw = matched?.gia_tri
      const num = Number(raw)
      answers[`q_${index}`] = Number.isFinite(num) ? num : 0
    } else {
      answers[`q_${index}`] = matched?.gia_tri != null ? String(matched.gia_tri) : ''
    }
  })
}

async function loadForm() {
  loading.value = true
  loadError.value = ''
  submitted.value = false
  alreadySubmitted.value = false

  const slug = String(route.params.slug || '').trim()
  if (!slug) {
    loadError.value = 'Đường dẫn form không hợp lệ.'
    loading.value = false
    return
  }

  try {
    const { data } = await getFormDanhGiaBySlug(slug, {
      hop_dong_danh_gia_id: hopDongDanhGiaId.value || undefined,
    })
    Object.assign(formDef, {
      id: data.id,
      ten_form: data.ten_form || '',
      slug: data.slug || slug,
      cau_hoi: Array.isArray(data.cau_hoi) ? data.cau_hoi : [],
    })

    if (!formDef.cau_hoi.length) {
      loadError.value = 'Form này chưa có câu hỏi để đánh giá.'
    } else {
      const daNop = !!data.da_nop && Array.isArray(data.noi_dung_danh_gia)
      alreadySubmitted.value = daNop
      initAnswers(daNop ? data.noi_dung_danh_gia : null)
    }
  } catch (error) {
    const status = error?.response?.status
    loadError.value =
      status === 404
        ? 'Form đánh giá không tồn tại hoặc đã bị xoá.'
        : 'Không thể tải form đánh giá. Vui lòng thử lại sau.'
  } finally {
    loading.value = false
  }
}

async function onSubmit() {
  if (alreadySubmitted.value) return

  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  if (!hopDongDanhGiaId.value) {
    ElMessage.error('Link đánh giá không hợp lệ (thiếu thông tin hợp đồng).')
    return
  }

  if (!formDef.slug) {
    ElMessage.error('Form đánh giá không hợp lệ.')
    return
  }

  submitting.value = true
  try {
    await nopFormDanhGia(formDef.slug, {
      hop_dong_danh_gia_id: hopDongDanhGiaId.value,
      noi_dung_danh_gia: questions.value.map((q, index) => ({
        cau_hoi: q.cau_hoi,
        loai_danh_gia: q.loai_danh_gia,
        thong_tin_danh_gia: q.thong_tin_danh_gia || null,
        gia_tri: answers[`q_${index}`],
      })),
    })

    submitted.value = true
    ElMessage.success('Cảm ơn bạn đã gửi đánh giá!')
  } catch {
    // Axios interceptor đã hiển thị lỗi
  } finally {
    submitting.value = false
  }
}

watch(
  () => [route.params.slug, route.query.hop_dong_danh_gia_id],
  () => {
    loadForm()
  }
)

onMounted(loadForm)
</script>

<style scoped>
.danh-gia-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  padding: 32px 16px 48px;
  background:
    radial-gradient(circle at top left, rgba(64, 158, 255, 0.12), transparent 42%),
    linear-gradient(180deg, #f7f9fc 0%, #eef2f7 100%);
}

.danh-gia-shell {
  width: 100%;
  max-width: 640px;
  background: #fff;
  border-radius: 16px;
  padding: 28px 24px 32px;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.08);
}

.danh-gia-header {
  margin-bottom: 24px;
}

.eyebrow {
  margin: 0 0 6px;
  font-size: 12px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--el-color-primary);
  font-weight: 600;
}

.danh-gia-header h1 {
  margin: 0 0 8px;
  font-size: 26px;
  line-height: 1.3;
  color: var(--el-text-color-primary);
}

.subtitle {
  margin: 0;
  color: var(--el-text-color-secondary);
  font-size: 14px;
}

.subtitle--done {
  color: var(--el-color-success);
}

.question-block {
  padding: 16px 0;
  border-bottom: 1px solid var(--el-border-color-extra-light);
}

.question-block:last-of-type {
  border-bottom: none;
}

.question-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}

.question-no {
  font-size: 12px;
  font-weight: 600;
  color: var(--el-text-color-secondary);
}

.submit-btn {
  width: 100%;
  margin-top: 20px;
}

.state-box {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 8px;
  color: var(--el-text-color-secondary);
}

.state-box h2 {
  margin: 0;
  color: var(--el-text-color-primary);
}

.state-box p {
  margin: 0;
  max-width: 360px;
}

.state-box--error h2 {
  color: var(--el-color-danger);
}

.state-box--success h2 {
  color: var(--el-color-success);
}
</style>
