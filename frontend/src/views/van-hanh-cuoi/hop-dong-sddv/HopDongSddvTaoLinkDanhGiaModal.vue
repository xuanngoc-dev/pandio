<template>
  <CustomDialog
    v-model="visible"
    :width="760"
    class="hop-dong-sddv-tao-link-danh-gia-modal"
    @closed="onClosed"
  >
    <template #header>
      <div class="modal-header">
        <div class="modal-header__title">
          Tạo link đánh giá
          <span v-if="maHopDong" class="modal-header__ma">· {{ maHopDong }}</span>
        </div>
      </div>
    </template>

    <div v-loading="loading" class="tao-link-body">
      <section class="info-section">
        <div class="info-row">
          <span class="info-row__label">Khách hàng</span>
          <span class="info-row__value">{{ display(tenKhachHang) }}</span>
        </div>
      </section>

      <section class="form-section">
        <div class="section-title">Chọn form đánh giá</div>
        <div v-if="formOptions.length" class="form-card-grid">
          <button
            v-for="item in formOptions"
            :key="item.id"
            type="button"
            class="form-card"
            :class="{ 'is-selected': form.form_danh_gia_id === item.id }"
            @click="selectForm(item.id)"
          >
            <span
              class="form-card__check"
              :class="{ 'is-checked': form.form_danh_gia_id === item.id }"
            >
              <CustomIcon v-if="form.form_danh_gia_id === item.id"><Check /></CustomIcon>
            </span>
            <div class="form-card__name" :title="item.ten_form">{{ item.ten_form }}</div>
          </button>
        </div>
        <div v-else-if="!loadingForms && !loading" class="form-card-empty">
          Chưa có form đánh giá mẫu.
        </div>
        <p v-if="formError" class="form-error">{{ formError }}</p>
      </section>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="visible = false">Đóng</CustomButton>
        <CustomButton
          type="primary"
          :loading="creating"
          :disabled="!form.form_danh_gia_id"
          @click="createLink"
        >
          Tạo link
        </CustomButton>
      </div>
    </template>
  </CustomDialog>

  <CustomDialog
    v-model="showLinkVisible"
    :width="560"
    class="hop-dong-sddv-show-link-danh-gia-modal"
    @closed="onShowLinkClosed"
  >
    <template #header>
      <div class="modal-header">
        <div class="modal-header__title">Link đánh giá</div>
      </div>
    </template>

    <div class="show-link-body">
      <p v-if="showLinkFormName" class="show-link-form-name">{{ showLinkFormName }}</p>
      <CustomInput :model-value="generatedLink" readonly>
        <template #append>
          <CustomButton :icon="CopyDocument" @click="copyLink">Sao chép</CustomButton>
        </template>
      </CustomInput>
      <p class="link-hint">Gửi link này cho khách hàng để họ điền đánh giá.</p>
    </div>

    <template #footer>
      <div class="footer-actions">
        <CustomButton @click="showLinkVisible = false">Đóng</CustomButton>
        <CustomButton type="primary" plain @click="openLink">Mở link</CustomButton>
      </div>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Check, CopyDocument } from '@element-plus/icons-vue'
import { fetchFormDanhGia } from '@/api/formDanhGia'
import { createHopDongSuDungDichVuFormDanhGia } from '@/api/hopDongSuDungDichVuFormDanhGia'
import {
  CustomButton,
  CustomDialog,
  CustomIcon,
  CustomInput,
} from '@/components/element'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  hopDong: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'created'])

const router = useRouter()

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const loading = ref(false)
const loadingForms = ref(false)
const creating = ref(false)
const formOptions = ref([])
const formError = ref('')
const generatedLink = ref('')
const showLinkVisible = ref(false)
const showLinkFormName = ref('')

const form = reactive({
  form_danh_gia_id: null,
})

const maHopDong = computed(() => props.hopDong?.ma_hop_dong || '')

const tenKhachHang = computed(() => {
  const hd = props.hopDong
  if (!hd) return ''
  return hd.ten_khach_hang || hd.thong_tin_hop_dong?.ten_khach_hang || ''
})

const selectedForm = computed(() =>
  formOptions.value.find((item) => item.id === form.form_danh_gia_id) || null
)

function display(value) {
  if (value == null || value === '') return '—'
  return String(value)
}

function selectForm(id) {
  form.form_danh_gia_id = id
  formError.value = ''
}

function buildLink(slug, hopDongId) {
  if (!slug || !hopDongId) return ''
  const resolved = router.resolve({
    name: 'danh-gia-khach',
    params: { slug },
    query: {
      hop_dong_danh_gia_id: hopDongId,
    },
  })
  return `${window.location.origin}${resolved.href}`
}

async function loadForms() {
  loadingForms.value = true
  try {
    const { data } = await fetchFormDanhGia({ per_page: 100 })
    formOptions.value = data.data || data || []
  } catch {
    formOptions.value = []
  } finally {
    loadingForms.value = false
  }
}

function resetState() {
  form.form_danh_gia_id = null
  formError.value = ''
}

async function createLink() {
  if (!form.form_danh_gia_id) {
    formError.value = 'Vui lòng chọn form đánh giá'
    return
  }

  const selected = selectedForm.value
  if (!selected?.slug) {
    ElMessage.warning('Form đánh giá chưa có slug hợp lệ')
    return
  }

  const hopDongId = props.hopDong?.id
  if (!hopDongId) {
    ElMessage.warning('Không tìm thấy hợp đồng')
    return
  }

  creating.value = true
  try {
    const { data } = await createHopDongSuDungDichVuFormDanhGia({
      hop_dong_danh_gia_id: hopDongId,
      form_danh_gia_id: selected.id,
    })

    const formSlug = data?.form_danh_gia?.slug || selected.slug
    const formName = data?.form_danh_gia?.ten_form || selected.ten_form
    const link = buildLink(formSlug, hopDongId)

    generatedLink.value = link
    showLinkFormName.value = formName || ''
    showLinkVisible.value = true
    visible.value = false

    ElMessage.success('Đã tạo link đánh giá')
    emit('created', {
      id: data?.id,
      hop_dong_danh_gia_id: hopDongId,
      form_danh_gia_id: selected.id,
      link,
      form: selected,
      record: data,
    })
  } catch {
    // Axios interceptor đã hiển thị lỗi (kể cả trùng hợp đồng + form)
  } finally {
    creating.value = false
  }
}

async function copyLink() {
  if (!generatedLink.value) return
  try {
    await navigator.clipboard.writeText(generatedLink.value)
    ElMessage.success('Đã sao chép link')
  } catch {
    ElMessage.error('Không thể sao chép link')
  }
}

function openLink() {
  if (!generatedLink.value) return
  window.open(generatedLink.value, '_blank', 'noopener,noreferrer')
}

function onClosed() {
  resetState()
}

function onShowLinkClosed() {
  generatedLink.value = ''
  showLinkFormName.value = ''
}

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return
    resetState()
    loading.value = true
    try {
      await loadForms()
    } finally {
      loading.value = false
    }
  }
)
</script>

<style scoped>
.modal-header__title {
  font-size: 16px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.modal-header__ma {
  margin-left: 4px;
  font-weight: 500;
  color: var(--el-text-color-secondary);
}

.tao-link-body {
  min-height: 160px;
}

.info-section {
  margin-bottom: 16px;
}

.info-row {
  display: flex;
  gap: 12px;
  align-items: baseline;
  font-size: 13px;
}

.info-row__label {
  flex: 0 0 auto;
  color: var(--el-text-color-secondary);
}

.info-row__value {
  font-weight: 500;
  color: var(--el-text-color-primary);
}

.section-title {
  margin-bottom: 10px;
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.form-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 10px;
  max-height: 320px;
  overflow-y: auto;
  padding: 2px;
}

.form-card {
  position: relative;
  display: flex;
  align-items: center;
  min-height: 64px;
  padding: 12px 36px 12px 12px;
  border: 1.5px solid var(--el-border-color-lighter);
  border-radius: 8px;
  background: var(--el-fill-color-blank);
  cursor: pointer;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
  text-align: left;
}

.form-card:hover {
  border-color: var(--el-color-primary-light-5);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.form-card.is-selected {
  border-color: var(--el-color-primary);
  background: var(--el-color-primary-light-9);
  box-shadow: 0 0 0 1px var(--el-color-primary-light-7);
}

.form-card__check {
  position: absolute;
  top: 8px;
  right: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border: 1.5px solid var(--el-border-color);
  border-radius: 4px;
  background: #fff;
  color: #fff;
  font-size: 12px;
}

.form-card__check.is-checked {
  border-color: var(--el-color-primary);
  background: var(--el-color-primary);
}

.form-card__name {
  font-size: 13px;
  font-weight: 500;
  color: var(--el-text-color-primary);
  line-height: 1.4;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.form-card-empty {
  padding: 24px 12px;
  text-align: center;
  font-size: 13px;
  color: var(--el-text-color-secondary);
  border: 1px dashed var(--el-border-color);
  border-radius: 8px;
}

.form-error {
  margin: 8px 0 0;
  font-size: 12px;
  color: var(--el-color-danger);
}

.show-link-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.show-link-form-name {
  margin: 0;
  font-size: 13px;
  font-weight: 500;
  color: var(--el-text-color-primary);
}

.link-hint {
  margin: 0;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
