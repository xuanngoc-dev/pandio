<template>
  <ConfigSettingPage title="Cấu hình mã giảm giá">
    <div v-loading="loading" class="ma-giam-gia">
      <CustomCard shadow="hover" class="form-card">
        <template #header>
          <div class="card-header">
            <span class="card-title">Cấu hình mã giảm giá</span>
            <CustomButton type="primary" :loading="saving" @click="save">
              Lưu cấu hình
            </CustomButton>
          </div>
        </template>

        <CustomForm ref="formRef" :model="form" :rules="rules" label-position="top">
          <CustomRow :gutter="20">
            <CustomCol :xs="24" :sm="8">
              <CustomFormItem label="Mã giảm giá mặc định" prop="ma_giam_gia_mac_dinh">
                <CustomInput v-model="form.ma_giam_gia_mac_dinh" placeholder="10 ký tự" maxlength="10" show-word-limit
                  clearable />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="8">
              <CustomFormItem label="Phần trăm giảm giá (%)" prop="phan_tram_giam_gia">
                <el-input-number
                  v-model="form.phan_tram_giam_gia"
                  :min="0"
                  :max="100"
                  :step="1"
                  controls-position="right"
                  style="width: 100%"
                />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="24" :sm="8">
              <CustomFormItem label="Số tiền giảm tối đa (VND)" prop="so_tien_giam_toi_da">
                <MoneyInput
                  v-model="form.so_tien_giam_toi_da"
                  placeholder="0"
                  style="width: 100%"
                />
              </CustomFormItem>
            </CustomCol>
          </CustomRow>
        </CustomForm>
      </CustomCard>
    </div>
  </ConfigSettingPage>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { getCauHinhJson, updateCauHinhJson } from '@/api/cauHinhJson'
import { MoneyInput } from '@/components/element'
import ConfigSettingPage from './ConfigSettingPage.vue'

const CONFIG_GROUP_KEY = 'ma_giam_gia'

const MA_GIAM_GIA_MAC_DINH_LENGTH = 10

const DEFAULTS = {
  phan_tram_giam_gia: 0,
  so_tien_giam_toi_da: 0,
  ma_giam_gia_mac_dinh: '',
}

const loading = ref(false)
const saving = ref(false)
const formRef = ref(null)

const form = reactive({
  phan_tram_giam_gia: DEFAULTS.phan_tram_giam_gia,
  so_tien_giam_toi_da: DEFAULTS.so_tien_giam_toi_da,
  ma_giam_gia_mac_dinh: DEFAULTS.ma_giam_gia_mac_dinh,
})

const rules = {
  phan_tram_giam_gia: [
    { required: true, message: 'Vui lòng nhập phần trăm giảm giá', trigger: 'change' },
  ],
  so_tien_giam_toi_da: [
    { required: true, message: 'Vui lòng nhập số tiền giảm tối đa', trigger: 'change' },
  ],
  ma_giam_gia_mac_dinh: [
    { required: true, message: 'Vui lòng nhập mã giảm giá mặc định', trigger: 'blur' },
    {
      len: MA_GIAM_GIA_MAC_DINH_LENGTH,
      message: `Mã giảm giá mặc định phải gồm ${MA_GIAM_GIA_MAC_DINH_LENGTH} ký tự`,
      trigger: 'blur',
    },
  ],
}

function toNumber(value, fallback = 0) {
  const n = Number(value)
  return Number.isFinite(n) ? n : fallback
}

function applyFromServer(group = {}) {
  form.phan_tram_giam_gia = toNumber(group.phan_tram_giam_gia, DEFAULTS.phan_tram_giam_gia)
  form.so_tien_giam_toi_da = toNumber(group.so_tien_giam_toi_da, DEFAULTS.so_tien_giam_toi_da)
  form.ma_giam_gia_mac_dinh = typeof group.ma_giam_gia_mac_dinh === 'string'
    ? group.ma_giam_gia_mac_dinh
    : DEFAULTS.ma_giam_gia_mac_dinh
}

function buildGroupPayload() {
  return {
    phan_tram_giam_gia: toNumber(form.phan_tram_giam_gia),
    so_tien_giam_toi_da: toNumber(form.so_tien_giam_toi_da),
    ma_giam_gia_mac_dinh: form.ma_giam_gia_mac_dinh?.trim() || '',
  }
}

async function loadConfig() {
  loading.value = true
  try {
    const { data } = await getCauHinhJson()
    applyFromServer(data?.thong_tin_cau_hinh?.[CONFIG_GROUP_KEY] || {})
  } catch {
    applyFromServer({})
  } finally {
    loading.value = false
  }
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  try {
    const payload = {
      thong_tin_cau_hinh: {
        [CONFIG_GROUP_KEY]: buildGroupPayload(),
      },
    }

    const { data } = await updateCauHinhJson(payload)
    applyFromServer(data?.thong_tin_cau_hinh?.[CONFIG_GROUP_KEY] || {})
    ElMessage.success('Đã lưu cấu hình mã giảm giá.')
  } finally {
    saving.value = false
  }
}

onMounted(loadConfig)
</script>
