<template>
  <ConfigSettingPage title="Chấm công & Tăng ca">
    <div v-loading="loading" class="cham-cong-tang-ca">
      <CustomCard shadow="hover" class="form-card">
        <template #header>
          <div class="card-header">
            <span class="card-title">Cấu hình chấm công & tăng ca</span>
            <CustomButton type="primary" :loading="saving" @click="save">
              Lưu cấu hình
            </CustomButton>
          </div>
        </template>

        <CustomForm ref="formRef" :model="form" label-position="top">
          <div class="setting-list">
            <div class="setting-item">
              <label class="setting-item__title">
                <el-checkbox v-model="form.yeu_cau_dang_ky_ca_moi_duoc_diem_danh.gia_tri" />
                <span>Yêu cầu đăng ký ca mới được điểm danh</span>
              </label>
              <CustomFormItem prop="yeu_cau_dang_ky_ca_moi_duoc_diem_danh.mo_ta" class="mo-ta-item">
                <CustomInput
                  v-model="form.yeu_cau_dang_ky_ca_moi_duoc_diem_danh.mo_ta"
                  placeholder="Mô tả cấu hình"
                />
              </CustomFormItem>
            </div>

            <div class="setting-item">
              <label class="setting-item__title">
                <el-checkbox v-model="form.kiem_soat_ip_diem_danh.gia_tri" />
                <span>Kiểm soát IP điểm danh</span>
              </label>
              <CustomFormItem prop="kiem_soat_ip_diem_danh.mo_ta" class="mo-ta-item">
                <CustomInput
                  v-model="form.kiem_soat_ip_diem_danh.mo_ta"
                  placeholder="Mô tả cấu hình"
                />
              </CustomFormItem>
            </div>

            <div class="setting-item">
              <div class="setting-item__title">Giờ tính tăng ca</div>
              <CustomRow :gutter="12">
                <CustomCol :xs="24" :sm="20">
                  <CustomFormItem prop="gio_tinh_tang_ca.mo_ta" class="mo-ta-item">
                    <CustomInput
                      v-model="form.gio_tinh_tang_ca.mo_ta"
                      placeholder="Mô tả cấu hình"
                    />
                  </CustomFormItem>
                </CustomCol>
                <CustomCol :xs="24" :sm="4">
                  <el-time-picker
                    v-model="form.gio_tinh_tang_ca.gia_tri"
                    format="HH:mm"
                    value-format="HH:mm"
                    placeholder="Chọn giờ"
                    style="width: 100%"
                  />
                </CustomCol>
              </CustomRow>
            </div>

            <div class="setting-item">
              <div class="setting-item__title">Số phút tối thiểu để tính tăng ca</div>
              <CustomRow :gutter="12">
                <CustomCol :xs="24" :sm="20">
                  <CustomFormItem prop="so_phut_toi_thieu_de_tinh_tang_ca.mo_ta" class="mo-ta-item">
                    <CustomInput
                      v-model="form.so_phut_toi_thieu_de_tinh_tang_ca.mo_ta"
                      placeholder="Mô tả cấu hình"
                    />
                  </CustomFormItem>
                </CustomCol>
                <CustomCol :xs="24" :sm="4">
                  <el-input-number
                    v-model="form.so_phut_toi_thieu_de_tinh_tang_ca.gia_tri"
                    class="phut-input"
                    :min="0"
                    :max="1440"
                    :step="1"
                    controls-position="right"
                    style="width: 100%"
                  />
                </CustomCol>
              </CustomRow>
            </div>
          </div>
        </CustomForm>
      </CustomCard>
    </div>
  </ConfigSettingPage>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { getCauHinhJson, updateCauHinhJson } from '@/api/cauHinhJson'
import ConfigSettingPage from './ConfigSettingPage.vue'

const CONFIG_GROUP_KEY = 'cham_cong_tang_ca'

const DEFAULTS = {
  yeu_cau_dang_ky_ca_moi_duoc_diem_danh: {
    mo_ta: 'Nhân viên phải đăng ký ca làm việc trước khi được điểm danh.',
    gia_tri: false,
  },
  kiem_soat_ip_diem_danh: {
    mo_ta: 'Chỉ cho phép điểm danh từ các IP đã được cấu hình.',
    gia_tri: false,
  },
  gio_tinh_tang_ca: {
    mo_ta: 'Thời điểm bắt đầu tính giờ tăng ca trong ngày.',
    gia_tri: '18:00',
  },
  so_phut_toi_thieu_de_tinh_tang_ca: {
    mo_ta: 'Số phút làm thêm tối thiểu để được ghi nhận tăng ca.',
    gia_tri: 30,
  },
}

const CONFIG_KEYS = Object.keys(DEFAULTS)

const loading = ref(false)
const saving = ref(false)
const formRef = ref(null)

const form = reactive({
  yeu_cau_dang_ky_ca_moi_duoc_diem_danh: { ...DEFAULTS.yeu_cau_dang_ky_ca_moi_duoc_diem_danh },
  kiem_soat_ip_diem_danh: { ...DEFAULTS.kiem_soat_ip_diem_danh },
  gio_tinh_tang_ca: { ...DEFAULTS.gio_tinh_tang_ca },
  so_phut_toi_thieu_de_tinh_tang_ca: { ...DEFAULTS.so_phut_toi_thieu_de_tinh_tang_ca },
})

function applyFromServer(group = {}) {
  for (const key of CONFIG_KEYS) {
    const saved = group[key]
    const fallback = DEFAULTS[key]
    form[key].mo_ta = typeof saved?.mo_ta === 'string' ? saved.mo_ta : fallback.mo_ta
    form[key].gia_tri = saved?.gia_tri !== undefined && saved?.gia_tri !== null
      ? saved.gia_tri
      : fallback.gia_tri
  }
}

function buildGroupPayload() {
  return {
    yeu_cau_dang_ky_ca_moi_duoc_diem_danh: {
      mo_ta: form.yeu_cau_dang_ky_ca_moi_duoc_diem_danh.mo_ta?.trim() || DEFAULTS.yeu_cau_dang_ky_ca_moi_duoc_diem_danh.mo_ta,
      gia_tri: Boolean(form.yeu_cau_dang_ky_ca_moi_duoc_diem_danh.gia_tri),
    },
    kiem_soat_ip_diem_danh: {
      mo_ta: form.kiem_soat_ip_diem_danh.mo_ta?.trim() || DEFAULTS.kiem_soat_ip_diem_danh.mo_ta,
      gia_tri: Boolean(form.kiem_soat_ip_diem_danh.gia_tri),
    },
    gio_tinh_tang_ca: {
      mo_ta: form.gio_tinh_tang_ca.mo_ta?.trim() || DEFAULTS.gio_tinh_tang_ca.mo_ta,
      gia_tri: form.gio_tinh_tang_ca.gia_tri || DEFAULTS.gio_tinh_tang_ca.gia_tri,
    },
    so_phut_toi_thieu_de_tinh_tang_ca: {
      mo_ta: form.so_phut_toi_thieu_de_tinh_tang_ca.mo_ta?.trim() || DEFAULTS.so_phut_toi_thieu_de_tinh_tang_ca.mo_ta,
      gia_tri: Number(form.so_phut_toi_thieu_de_tinh_tang_ca.gia_tri ?? 0),
    },
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
  saving.value = true
  try {
    const payload = {
      thong_tin_cau_hinh: {
        [CONFIG_GROUP_KEY]: buildGroupPayload(),
      },
    }

    const { data } = await updateCauHinhJson(payload)
    applyFromServer(data?.thong_tin_cau_hinh?.[CONFIG_GROUP_KEY] || {})
    ElMessage.success('Đã lưu cấu hình chấm công & tăng ca.')
  } finally {
    saving.value = false
  }
}

onMounted(loadConfig)
</script>

<style scoped lang="scss">
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.card-title {
  font-weight: 600;
}

.setting-list {
  display: flex;
  flex-direction: column;
}

.setting-item {
  padding: 16px 0;
  border-bottom: 1px solid var(--el-border-color-lighter);

  &:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }

  &:first-child {
    padding-top: 0;
  }

  &__title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 15px;
    font-weight: 600;
    color: var(--el-text-color-primary);
    cursor: pointer;
  }
}

.mo-ta-item {
  margin-bottom: 0;
}

.phut-input :deep(.el-input__inner) {
  text-align: left;
}

@media (max-width: 767px) {
  .setting-item :deep(.el-col:not(:last-child)) {
    margin-bottom: 12px;
  }
}
</style>
