<template>
  <ConfigSettingPage title="Lương & Hoa hồng">
    <div v-loading="loading" class="luong-hoa-hong">
      <CustomCard shadow="hover" class="form-card">
        <template #header>
          <div class="card-header">
            <span class="card-title">Cấu hình lương & hoa hồng</span>
            <CustomButton type="primary" :loading="saving" @click="save">
              Lưu cấu hình
            </CustomButton>
          </div>
        </template>

        <el-tabs v-model="activeTab" class="config-tabs">
          <!-- Tab Lương cơ bản -->
          <el-tab-pane label="Lương cơ bản" name="luong_co_ban">
            <CustomForm :model="form.luong_co_ban" label-position="top">
              <div class="setting-list">
                <div class="setting-item">
                  <label class="setting-item__title">
                    <el-checkbox v-model="form.luong_co_ban.tinh_luong_full_time_theo_cong_thuc_te.gia_tri" />
                    <span>Tính lương full-time theo công thực tế</span>
                  </label>
                  <CustomFormItem class="mo-ta-item">
                    <CustomInput
                      v-model="form.luong_co_ban.tinh_luong_full_time_theo_cong_thuc_te.mo_ta"
                      placeholder="Mô tả cấu hình"
                    />
                  </CustomFormItem>
                </div>

                <div class="setting-item">
                  <div class="setting-item__title">Công chuẩn trong tháng</div>
                  <CustomRow :gutter="12">
                    <CustomCol :xs="24" :sm="20">
                      <CustomFormItem class="mo-ta-item">
                        <CustomInput
                          v-model="form.luong_co_ban.cong_chuan_trong_thang.mo_ta"
                          placeholder="Mô tả cấu hình"
                        />
                      </CustomFormItem>
                    </CustomCol>
                    <CustomCol :xs="24" :sm="4">
                      <el-input-number
                        v-model="form.luong_co_ban.cong_chuan_trong_thang.gia_tri"
                        :min="1"
                        :max="31"
                        :step="1"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                  </CustomRow>
                </div>

                <div class="setting-item">
                  <div class="setting-item__title">Lương sản phẩm nhận theo</div>
                  <CustomFormItem class="mo-ta-item">
                    <CustomInput
                      v-model="form.luong_co_ban.luong_san_pham_nhan_theo.mo_ta"
                      placeholder="Mô tả cấu hình"
                    />
                  </CustomFormItem>
                  <el-radio-group v-model="form.luong_co_ban.luong_san_pham_nhan_theo.gia_tri" class="option-group">
                    <el-radio value="ngay_chup">Ngày chụp</el-radio>
                    <el-radio value="ngay_ban_giao">Ngày bàn giao</el-radio>
                  </el-radio-group>
                </div>

                <div class="setting-item">
                  <div class="setting-item__title">Hệ số tăng ca</div>
                  <CustomFormItem class="mo-ta-item">
                    <CustomInput
                      v-model="form.luong_co_ban.he_so_tang_ca.mo_ta"
                      placeholder="Mô tả cấu hình"
                    />
                  </CustomFormItem>
                  <CustomRow :gutter="12" class="coeff-row">
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Ngày thường</div>
                      <el-input-number
                        v-model="form.luong_co_ban.he_so_tang_ca.ngay_thuong"
                        :min="0"
                        :step="0.1"
                        :precision="2"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Ngày nghỉ</div>
                      <el-input-number
                        v-model="form.luong_co_ban.he_so_tang_ca.ngay_nghi"
                        :min="0"
                        :step="0.1"
                        :precision="2"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Ngày lễ</div>
                      <el-input-number
                        v-model="form.luong_co_ban.he_so_tang_ca.ngay_le"
                        :min="0"
                        :step="0.1"
                        :precision="2"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                  </CustomRow>
                </div>
              </div>
            </CustomForm>
          </el-tab-pane>

          <!-- Tab Đơn giá lương -->
          <el-tab-pane label="Đơn giá lương" name="don_gia_luong">
            <CustomForm :model="form.don_gia_luong" label-position="top">
              <div class="setting-list">
                <div class="setting-item">
                  <div class="setting-item__title">Sale (%)</div>
                  <CustomRow :gutter="12" class="coeff-row">
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Hoa hồng hợp đồng</div>
                      <el-input-number
                        v-model="form.don_gia_luong.sale.hoa_hong_hop_dong"
                        :min="0"
                        :max="100"
                        :step="0.1"
                        :precision="2"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Hoa hồng trang phục</div>
                      <el-input-number
                        v-model="form.don_gia_luong.sale.hoa_hong_trang_phuc"
                        :min="0"
                        :max="100"
                        :step="0.1"
                        :precision="2"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Hoa hồng upsale</div>
                      <el-input-number
                        v-model="form.don_gia_luong.sale.hoa_hong_upsale"
                        :min="0"
                        :max="100"
                        :step="0.1"
                        :precision="2"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                  </CustomRow>
                </div>

                <div class="setting-item">
                  <div class="setting-item__title">Thợ chụp (VND)</div>
                  <CustomRow :gutter="12" class="coeff-row">
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Job 1 điểm</div>
                      <el-input-number
                        v-model="form.don_gia_luong.tho_chup.job_1_diem"
                        :min="0"
                        :step="1000"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Job 2 điểm</div>
                      <el-input-number
                        v-model="form.don_gia_luong.tho_chup.job_2_diem"
                        :min="0"
                        :step="1000"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Job 3 điểm</div>
                      <el-input-number
                        v-model="form.don_gia_luong.tho_chup.job_3_diem"
                        :min="0"
                        :step="1000"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                  </CustomRow>
                </div>

                <div class="setting-item">
                  <div class="setting-item__title">Thợ make (VND)</div>
                  <CustomRow :gutter="12" class="coeff-row">
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Job 1 điểm</div>
                      <el-input-number
                        v-model="form.don_gia_luong.tho_make.job_1_diem"
                        :min="0"
                        :step="1000"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Job 2 điểm</div>
                      <el-input-number
                        v-model="form.don_gia_luong.tho_make.job_2_diem"
                        :min="0"
                        :step="1000"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Job 3 điểm</div>
                      <el-input-number
                        v-model="form.don_gia_luong.tho_make.job_3_diem"
                        :min="0"
                        :step="1000"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                  </CustomRow>
                </div>

                <div class="setting-item">
                  <div class="setting-item__title">Thợ hậu kỳ (VND)</div>
                  <CustomRow :gutter="12" class="coeff-row">
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">Đơn giá ảnh chỉnh sửa</div>
                      <el-input-number
                        v-model="form.don_gia_luong.tho_hau_ky.don_gia_anh_chinh_sua"
                        :min="0"
                        :step="1000"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                  </CustomRow>
                </div>
              </div>
            </CustomForm>
          </el-tab-pane>

          <!-- Tab Lương hoa hồng -->
          <el-tab-pane label="Lương hoa hồng" name="luong_hoa_hong">
            <CustomForm :model="form.luong_hoa_hong" label-position="top">
              <div class="setting-list">
                <div class="setting-item">
                  <div class="setting-item__title">Hoa hồng hợp đồng cuối tính theo</div>
                  <CustomFormItem class="mo-ta-item">
                    <CustomInput
                      v-model="form.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_theo.mo_ta"
                      placeholder="Mô tả cấu hình"
                    />
                  </CustomFormItem>
                  <el-radio-group
                    v-model="form.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_theo.gia_tri"
                    class="option-group"
                  >
                    <el-radio value="ngay_cuoi">Ngày cuối</el-radio>
                    <el-radio value="ngay_thu">Ngày thu</el-radio>
                  </el-radio-group>
                </div>

                <div class="setting-item">
                  <div class="setting-item__title">Hoa hồng hợp đồng cuối tính trên</div>
                  <CustomFormItem class="mo-ta-item">
                    <CustomInput
                      v-model="form.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_tren.mo_ta"
                      placeholder="Mô tả cấu hình"
                    />
                  </CustomFormItem>
                  <el-radio-group
                    v-model="form.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_tren.gia_tri"
                    class="option-group"
                  >
                    <el-radio value="tong">Tổng</el-radio>
                    <el-radio value="thuc_thu">Thực thu</el-radio>
                  </el-radio-group>
                </div>

                <div class="setting-item">
                  <div class="setting-item__title">Trọng số hoa hồng (%)</div>
                  <CustomFormItem class="mo-ta-item">
                    <CustomInput
                      v-model="form.luong_hoa_hong.trong_so_hoa_hong.mo_ta"
                      placeholder="Mô tả cấu hình"
                    />
                  </CustomFormItem>
                  <CustomRow :gutter="12" class="coeff-row">
                    <CustomCol :xs="24" :sm="12">
                      <div class="field-label">Người chốt</div>
                      <el-input-number
                        v-model="form.luong_hoa_hong.trong_so_hoa_hong.nguoi_chot"
                        :min="0"
                        :max="100"
                        :step="1"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="12">
                      <div class="field-label">Người hỗ trợ</div>
                      <el-input-number
                        v-model="form.luong_hoa_hong.trong_so_hoa_hong.nguoi_ho_tro"
                        :min="0"
                        :max="100"
                        :step="1"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                  </CustomRow>
                </div>
              </div>
            </CustomForm>
          </el-tab-pane>

          <!-- Tab Kỳ chốt lương -->
          <el-tab-pane label="Kỳ chốt lương" name="ky_chot_luong">
            <CustomForm :model="form.ky_chot_luong" label-position="top">
              <div class="setting-list">
                <div class="setting-item">
                  <div class="setting-item__title">Kỳ chốt lương tháng liền trước</div>
                  <p class="setting-hint">
                    Ngày bắt đầu và ngày kết thúc dùng để xác định khoảng chốt lương cho tháng liền trước
                    (ví dụ: từ ngày 26 tháng trước đến ngày 25 tháng hiện tại).
                  </p>
                  <CustomRow :gutter="12" class="coeff-row">
                    <CustomCol :xs="24" :sm="12">
                      <div class="field-label">Ngày bắt đầu</div>
                      <el-input-number
                        v-model="form.ky_chot_luong.ngay_bat_dau"
                        :min="1"
                        :max="31"
                        :step="1"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="12">
                      <div class="field-label">Ngày kết thúc</div>
                      <el-input-number
                        v-model="form.ky_chot_luong.ngay_ket_thuc"
                        :min="1"
                        :max="31"
                        :step="1"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                  </CustomRow>
                </div>
              </div>
            </CustomForm>
          </el-tab-pane>

          <!-- Tab Bảo hiểm & Thuế TNCN -->
          <el-tab-pane label="Bảo hiểm & Thuế TNCN" name="bao_hiem_va_thue">
            <CustomForm :model="form.bao_hiem_va_thue" label-position="top">
              <div class="setting-list">
                <div class="setting-item">
                  <label class="setting-item__title">
                    <el-checkbox v-model="form.bao_hiem_va_thue.ap_dung_khau_tru.gia_tri" />
                    <span>Áp dụng khấu trừ</span>
                  </label>
                  <CustomFormItem class="mo-ta-item">
                    <CustomInput
                      v-model="form.bao_hiem_va_thue.ap_dung_khau_tru.mo_ta"
                      placeholder="Mô tả cấu hình"
                    />
                  </CustomFormItem>
                </div>

                <div class="setting-item">
                  <div class="setting-item__title">Giảm trừ gia cảnh bản thân</div>
                  <CustomRow :gutter="12">
                    <CustomCol :xs="24" :sm="16">
                      <CustomFormItem class="mo-ta-item">
                        <CustomInput
                          v-model="form.bao_hiem_va_thue.giam_tru_gia_canh_ban_than.mo_ta"
                          placeholder="Mô tả cấu hình"
                        />
                      </CustomFormItem>
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <el-input-number
                        v-model="form.bao_hiem_va_thue.giam_tru_gia_canh_ban_than.gia_tri"
                        :min="0"
                        :step="100000"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                  </CustomRow>
                </div>

                <div class="setting-item">
                  <div class="setting-item__title">Giảm trừ gia cảnh người phụ thuộc</div>
                  <CustomRow :gutter="12">
                    <CustomCol :xs="24" :sm="16">
                      <CustomFormItem class="mo-ta-item">
                        <CustomInput
                          v-model="form.bao_hiem_va_thue.giam_tru_gia_nguoi_phu_thuoc.mo_ta"
                          placeholder="Mô tả cấu hình"
                        />
                      </CustomFormItem>
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <el-input-number
                        v-model="form.bao_hiem_va_thue.giam_tru_gia_nguoi_phu_thuoc.gia_tri"
                        :min="0"
                        :step="100000"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                  </CustomRow>
                </div>

                <div class="setting-item">
                  <div class="setting-item__title">Người lao động đóng (%)</div>
                  <CustomRow :gutter="12" class="coeff-row">
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">BHXH</div>
                      <el-input-number
                        v-model="form.bao_hiem_va_thue.nguoi_lao_dong_dong.bhxh"
                        :min="0"
                        :max="100"
                        :step="0.1"
                        :precision="2"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">BHYT</div>
                      <el-input-number
                        v-model="form.bao_hiem_va_thue.nguoi_lao_dong_dong.bhyt"
                        :min="0"
                        :max="100"
                        :step="0.1"
                        :precision="2"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">BHTN</div>
                      <el-input-number
                        v-model="form.bao_hiem_va_thue.nguoi_lao_dong_dong.bhtn"
                        :min="0"
                        :max="100"
                        :step="0.1"
                        :precision="2"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                  </CustomRow>
                </div>

                <div class="setting-item">
                  <div class="setting-item__title">Doanh nghiệp đóng (%)</div>
                  <CustomRow :gutter="12" class="coeff-row">
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">BHXH</div>
                      <el-input-number
                        v-model="form.bao_hiem_va_thue.doanh_nghiep_dong.bhxh"
                        :min="0"
                        :max="100"
                        :step="0.1"
                        :precision="2"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">BHYT</div>
                      <el-input-number
                        v-model="form.bao_hiem_va_thue.doanh_nghiep_dong.bhyt"
                        :min="0"
                        :max="100"
                        :step="0.1"
                        :precision="2"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                    <CustomCol :xs="24" :sm="8">
                      <div class="field-label">BHTN</div>
                      <el-input-number
                        v-model="form.bao_hiem_va_thue.doanh_nghiep_dong.bhtn"
                        :min="0"
                        :max="100"
                        :step="0.1"
                        :precision="2"
                        controls-position="right"
                        style="width: 100%"
                      />
                    </CustomCol>
                  </CustomRow>
                </div>
              </div>
            </CustomForm>
          </el-tab-pane>
        </el-tabs>
      </CustomCard>
    </div>
  </ConfigSettingPage>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { getCauHinhJson, updateCauHinhJson } from '@/api/cauHinhJson'
import ConfigSettingPage from './ConfigSettingPage.vue'

const CONFIG_GROUP_KEY = 'luong_va_hoa_hong'

const DEFAULTS = {
  luong_co_ban: {
    tinh_luong_full_time_theo_cong_thuc_te: {
      mo_ta: 'Tính lương nhân viên full-time dựa trên số công thực tế trong kỳ.',
      gia_tri: false,
    },
    cong_chuan_trong_thang: {
      mo_ta: 'Số ngày công chuẩn dùng để quy đổi lương tháng.',
      gia_tri: 26,
    },
    luong_san_pham_nhan_theo: {
      mo_ta: 'Mốc thời điểm ghi nhận lương sản phẩm.',
      gia_tri: 'ngay_chup',
    },
    he_so_tang_ca: {
      mo_ta: 'Hệ số nhân khi tính lương tăng ca theo loại ngày.',
      ngay_thuong: 1.5,
      ngay_nghi: 2,
      ngay_le: 3,
    },
  },
  don_gia_luong: {
    sale: {
      hoa_hong_hop_dong: 0,
      hoa_hong_trang_phuc: 0,
      hoa_hong_upsale: 0,
    },
    tho_chup: {
      job_1_diem: 0,
      job_2_diem: 0,
      job_3_diem: 0,
    },
    tho_make: {
      job_1_diem: 0,
      job_2_diem: 0,
      job_3_diem: 0,
    },
    tho_hau_ky: {
      don_gia_anh_chinh_sua: 0,
    },
  },
  luong_hoa_hong: {
    hoa_hong_hop_dong_cuoi_tinh_theo: {
      mo_ta: 'Mốc ngày dùng để ghi nhận hoa hồng hợp đồng cuối.',
      gia_tri: 'ngay_cuoi',
    },
    hoa_hong_hop_dong_cuoi_tinh_tren: {
      mo_ta: 'Cơ sở số tiền để tính hoa hồng hợp đồng cuối.',
      gia_tri: 'tong',
    },
    trong_so_hoa_hong: {
      mo_ta: 'Tỷ lệ phân bổ hoa hồng giữa người chốt và người hỗ trợ.',
      nguoi_chot: 70,
      nguoi_ho_tro: 30,
    },
  },
  ky_chot_luong: {
    ngay_bat_dau: 26,
    ngay_ket_thuc: 25,
  },
  bao_hiem_va_thue: {
    ap_dung_khau_tru: {
      mo_ta: 'Bật/tắt khấu trừ bảo hiểm và thuế TNCN khi tính lương.',
      gia_tri: true,
    },
    giam_tru_gia_canh_ban_than: {
      mo_ta: 'Mức giảm trừ gia cảnh cho bản thân (VND/tháng).',
      gia_tri: 11000000,
    },
    giam_tru_gia_nguoi_phu_thuoc: {
      mo_ta: 'Mức giảm trừ gia cảnh cho mỗi người phụ thuộc (VND/tháng).',
      gia_tri: 4400000,
    },
    nguoi_lao_dong_dong: {
      bhxh: 8,
      bhyt: 1.5,
      bhtn: 1,
    },
    doanh_nghiep_dong: {
      bhxh: 17.5,
      bhyt: 3,
      bhtn: 1,
    },
  },
}

const loading = ref(false)
const saving = ref(false)
const activeTab = ref('luong_co_ban')

const form = reactive(structuredClone(DEFAULTS))

function pickNumber(value, fallback) {
  const n = Number(value)
  return Number.isFinite(n) ? n : fallback
}

function pickString(value, fallback) {
  return typeof value === 'string' && value.length ? value : fallback
}

function applyMoTaGiaTri(target, saved, fallback) {
  target.mo_ta = pickString(saved?.mo_ta, fallback.mo_ta)
  if (typeof fallback.gia_tri === 'boolean') {
    target.gia_tri = saved?.gia_tri !== undefined && saved?.gia_tri !== null
      ? Boolean(saved.gia_tri)
      : fallback.gia_tri
    return
  }
  if (typeof fallback.gia_tri === 'number') {
    target.gia_tri = pickNumber(saved?.gia_tri ?? saved?.value, fallback.gia_tri)
    return
  }
  target.gia_tri = pickString(saved?.gia_tri, fallback.gia_tri)
}

function applyFromServer(group = {}) {
  const luongCoBan = group.luong_co_ban || {}
  applyMoTaGiaTri(
    form.luong_co_ban.tinh_luong_full_time_theo_cong_thuc_te,
    luongCoBan.tinh_luong_full_time_theo_cong_thuc_te,
    DEFAULTS.luong_co_ban.tinh_luong_full_time_theo_cong_thuc_te,
  )
  applyMoTaGiaTri(
    form.luong_co_ban.cong_chuan_trong_thang,
    luongCoBan.cong_chuan_trong_thang,
    DEFAULTS.luong_co_ban.cong_chuan_trong_thang,
  )
  applyMoTaGiaTri(
    form.luong_co_ban.luong_san_pham_nhan_theo,
    luongCoBan.luong_san_pham_nhan_theo,
    DEFAULTS.luong_co_ban.luong_san_pham_nhan_theo,
  )
  const heSo = luongCoBan.he_so_tang_ca || {}
  form.luong_co_ban.he_so_tang_ca.mo_ta = pickString(heSo.mo_ta, DEFAULTS.luong_co_ban.he_so_tang_ca.mo_ta)
  form.luong_co_ban.he_so_tang_ca.ngay_thuong = pickNumber(heSo.ngay_thuong, DEFAULTS.luong_co_ban.he_so_tang_ca.ngay_thuong)
  form.luong_co_ban.he_so_tang_ca.ngay_nghi = pickNumber(heSo.ngay_nghi, DEFAULTS.luong_co_ban.he_so_tang_ca.ngay_nghi)
  form.luong_co_ban.he_so_tang_ca.ngay_le = pickNumber(heSo.ngay_le, DEFAULTS.luong_co_ban.he_so_tang_ca.ngay_le)

  const donGia = group.don_gia_luong || {}
  const sale = donGia.sale || {}
  form.don_gia_luong.sale.hoa_hong_hop_dong = pickNumber(sale.hoa_hong_hop_dong, DEFAULTS.don_gia_luong.sale.hoa_hong_hop_dong)
  form.don_gia_luong.sale.hoa_hong_trang_phuc = pickNumber(sale.hoa_hong_trang_phuc, DEFAULTS.don_gia_luong.sale.hoa_hong_trang_phuc)
  form.don_gia_luong.sale.hoa_hong_upsale = pickNumber(sale.hoa_hong_upsale, DEFAULTS.don_gia_luong.sale.hoa_hong_upsale)

  const thoChup = donGia.tho_chup || {}
  form.don_gia_luong.tho_chup.job_1_diem = pickNumber(thoChup.job_1_diem, DEFAULTS.don_gia_luong.tho_chup.job_1_diem)
  form.don_gia_luong.tho_chup.job_2_diem = pickNumber(thoChup.job_2_diem, DEFAULTS.don_gia_luong.tho_chup.job_2_diem)
  form.don_gia_luong.tho_chup.job_3_diem = pickNumber(thoChup.job_3_diem, DEFAULTS.don_gia_luong.tho_chup.job_3_diem)

  const thoMake = donGia.tho_make || {}
  form.don_gia_luong.tho_make.job_1_diem = pickNumber(thoMake.job_1_diem, DEFAULTS.don_gia_luong.tho_make.job_1_diem)
  form.don_gia_luong.tho_make.job_2_diem = pickNumber(thoMake.job_2_diem, DEFAULTS.don_gia_luong.tho_make.job_2_diem)
  form.don_gia_luong.tho_make.job_3_diem = pickNumber(thoMake.job_3_diem, DEFAULTS.don_gia_luong.tho_make.job_3_diem)

  const thoHauKy = donGia.tho_hau_ky || {}
  form.don_gia_luong.tho_hau_ky.don_gia_anh_chinh_sua = pickNumber(
    thoHauKy.don_gia_anh_chinh_sua?.gia_tri ?? thoHauKy.don_gia_anh_chinh_sua?.value ?? thoHauKy.don_gia_anh_chinh_sua,
    DEFAULTS.don_gia_luong.tho_hau_ky.don_gia_anh_chinh_sua,
  )

  const luongHoaHong = group.luong_hoa_hong || {}
  applyMoTaGiaTri(
    form.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_theo,
    luongHoaHong.hoa_hong_hop_dong_cuoi_tinh_theo,
    DEFAULTS.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_theo,
  )
  applyMoTaGiaTri(
    form.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_tren,
    luongHoaHong.hoa_hong_hop_dong_cuoi_tinh_tren || luongHoaHong.hoa_hong_hop_dong_cuoi_tinh_theo_doanh_thu,
    DEFAULTS.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_tren,
  )
  const trongSo = luongHoaHong.trong_so_hoa_hong || {}
  form.luong_hoa_hong.trong_so_hoa_hong.mo_ta = pickString(trongSo.mo_ta, DEFAULTS.luong_hoa_hong.trong_so_hoa_hong.mo_ta)
  form.luong_hoa_hong.trong_so_hoa_hong.nguoi_chot = pickNumber(trongSo.nguoi_chot, DEFAULTS.luong_hoa_hong.trong_so_hoa_hong.nguoi_chot)
  form.luong_hoa_hong.trong_so_hoa_hong.nguoi_ho_tro = pickNumber(trongSo.nguoi_ho_tro, DEFAULTS.luong_hoa_hong.trong_so_hoa_hong.nguoi_ho_tro)

  const kyChot = group.ky_chot_luong || {}
  form.ky_chot_luong.ngay_bat_dau = pickNumber(kyChot.ngay_bat_dau, DEFAULTS.ky_chot_luong.ngay_bat_dau)
  form.ky_chot_luong.ngay_ket_thuc = pickNumber(kyChot.ngay_ket_thuc, DEFAULTS.ky_chot_luong.ngay_ket_thuc)

  const baoHiem = group.bao_hiem_va_thue || {}
  applyMoTaGiaTri(
    form.bao_hiem_va_thue.ap_dung_khau_tru,
    baoHiem.ap_dung_khau_tru,
    DEFAULTS.bao_hiem_va_thue.ap_dung_khau_tru,
  )
  applyMoTaGiaTri(
    form.bao_hiem_va_thue.giam_tru_gia_canh_ban_than,
    baoHiem.giam_tru_gia_canh_ban_than,
    DEFAULTS.bao_hiem_va_thue.giam_tru_gia_canh_ban_than,
  )
  applyMoTaGiaTri(
    form.bao_hiem_va_thue.giam_tru_gia_nguoi_phu_thuoc,
    baoHiem.giam_tru_gia_nguoi_phu_thuoc,
    DEFAULTS.bao_hiem_va_thue.giam_tru_gia_nguoi_phu_thuoc,
  )

  const nld = baoHiem.nguoi_lao_dong_dong || {}
  form.bao_hiem_va_thue.nguoi_lao_dong_dong.bhxh = pickNumber(nld.bhxh, DEFAULTS.bao_hiem_va_thue.nguoi_lao_dong_dong.bhxh)
  form.bao_hiem_va_thue.nguoi_lao_dong_dong.bhyt = pickNumber(nld.bhyt, DEFAULTS.bao_hiem_va_thue.nguoi_lao_dong_dong.bhyt)
  form.bao_hiem_va_thue.nguoi_lao_dong_dong.bhtn = pickNumber(nld.bhtn, DEFAULTS.bao_hiem_va_thue.nguoi_lao_dong_dong.bhtn)

  const dn = baoHiem.doanh_nghiep_dong || {}
  form.bao_hiem_va_thue.doanh_nghiep_dong.bhxh = pickNumber(dn.bhxh, DEFAULTS.bao_hiem_va_thue.doanh_nghiep_dong.bhxh)
  form.bao_hiem_va_thue.doanh_nghiep_dong.bhyt = pickNumber(dn.bhyt, DEFAULTS.bao_hiem_va_thue.doanh_nghiep_dong.bhyt)
  form.bao_hiem_va_thue.doanh_nghiep_dong.bhtn = pickNumber(dn.bhtn, DEFAULTS.bao_hiem_va_thue.doanh_nghiep_dong.bhtn)
}

function buildGroupPayload() {
  return {
    luong_co_ban: {
      tinh_luong_full_time_theo_cong_thuc_te: {
        mo_ta: form.luong_co_ban.tinh_luong_full_time_theo_cong_thuc_te.mo_ta?.trim()
          || DEFAULTS.luong_co_ban.tinh_luong_full_time_theo_cong_thuc_te.mo_ta,
        gia_tri: Boolean(form.luong_co_ban.tinh_luong_full_time_theo_cong_thuc_te.gia_tri),
      },
      cong_chuan_trong_thang: {
        mo_ta: form.luong_co_ban.cong_chuan_trong_thang.mo_ta?.trim()
          || DEFAULTS.luong_co_ban.cong_chuan_trong_thang.mo_ta,
        gia_tri: pickNumber(
          form.luong_co_ban.cong_chuan_trong_thang.gia_tri,
          DEFAULTS.luong_co_ban.cong_chuan_trong_thang.gia_tri,
        ),
      },
      luong_san_pham_nhan_theo: {
        mo_ta: form.luong_co_ban.luong_san_pham_nhan_theo.mo_ta?.trim()
          || DEFAULTS.luong_co_ban.luong_san_pham_nhan_theo.mo_ta,
        gia_tri: ['ngay_chup', 'ngay_ban_giao'].includes(form.luong_co_ban.luong_san_pham_nhan_theo.gia_tri)
          ? form.luong_co_ban.luong_san_pham_nhan_theo.gia_tri
          : DEFAULTS.luong_co_ban.luong_san_pham_nhan_theo.gia_tri,
      },
      he_so_tang_ca: {
        mo_ta: form.luong_co_ban.he_so_tang_ca.mo_ta?.trim() || DEFAULTS.luong_co_ban.he_so_tang_ca.mo_ta,
        ngay_thuong: pickNumber(form.luong_co_ban.he_so_tang_ca.ngay_thuong, DEFAULTS.luong_co_ban.he_so_tang_ca.ngay_thuong),
        ngay_nghi: pickNumber(form.luong_co_ban.he_so_tang_ca.ngay_nghi, DEFAULTS.luong_co_ban.he_so_tang_ca.ngay_nghi),
        ngay_le: pickNumber(form.luong_co_ban.he_so_tang_ca.ngay_le, DEFAULTS.luong_co_ban.he_so_tang_ca.ngay_le),
      },
    },
    don_gia_luong: {
      sale: {
        hoa_hong_hop_dong: pickNumber(form.don_gia_luong.sale.hoa_hong_hop_dong, DEFAULTS.don_gia_luong.sale.hoa_hong_hop_dong),
        hoa_hong_trang_phuc: pickNumber(form.don_gia_luong.sale.hoa_hong_trang_phuc, DEFAULTS.don_gia_luong.sale.hoa_hong_trang_phuc),
        hoa_hong_upsale: pickNumber(form.don_gia_luong.sale.hoa_hong_upsale, DEFAULTS.don_gia_luong.sale.hoa_hong_upsale),
      },
      tho_chup: {
        job_1_diem: pickNumber(form.don_gia_luong.tho_chup.job_1_diem, DEFAULTS.don_gia_luong.tho_chup.job_1_diem),
        job_2_diem: pickNumber(form.don_gia_luong.tho_chup.job_2_diem, DEFAULTS.don_gia_luong.tho_chup.job_2_diem),
        job_3_diem: pickNumber(form.don_gia_luong.tho_chup.job_3_diem, DEFAULTS.don_gia_luong.tho_chup.job_3_diem),
      },
      tho_make: {
        job_1_diem: pickNumber(form.don_gia_luong.tho_make.job_1_diem, DEFAULTS.don_gia_luong.tho_make.job_1_diem),
        job_2_diem: pickNumber(form.don_gia_luong.tho_make.job_2_diem, DEFAULTS.don_gia_luong.tho_make.job_2_diem),
        job_3_diem: pickNumber(form.don_gia_luong.tho_make.job_3_diem, DEFAULTS.don_gia_luong.tho_make.job_3_diem),
      },
      tho_hau_ky: {
        don_gia_anh_chinh_sua: pickNumber(
          form.don_gia_luong.tho_hau_ky.don_gia_anh_chinh_sua,
          DEFAULTS.don_gia_luong.tho_hau_ky.don_gia_anh_chinh_sua,
        ),
      },
    },
    luong_hoa_hong: {
      hoa_hong_hop_dong_cuoi_tinh_theo: {
        mo_ta: form.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_theo.mo_ta?.trim()
          || DEFAULTS.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_theo.mo_ta,
        gia_tri: ['ngay_cuoi', 'ngay_thu'].includes(form.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_theo.gia_tri)
          ? form.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_theo.gia_tri
          : DEFAULTS.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_theo.gia_tri,
      },
      hoa_hong_hop_dong_cuoi_tinh_tren: {
        mo_ta: form.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_tren.mo_ta?.trim()
          || DEFAULTS.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_tren.mo_ta,
        gia_tri: ['tong', 'thuc_thu'].includes(form.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_tren.gia_tri)
          ? form.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_tren.gia_tri
          : DEFAULTS.luong_hoa_hong.hoa_hong_hop_dong_cuoi_tinh_tren.gia_tri,
      },
      trong_so_hoa_hong: {
        mo_ta: form.luong_hoa_hong.trong_so_hoa_hong.mo_ta?.trim()
          || DEFAULTS.luong_hoa_hong.trong_so_hoa_hong.mo_ta,
        nguoi_chot: pickNumber(form.luong_hoa_hong.trong_so_hoa_hong.nguoi_chot, DEFAULTS.luong_hoa_hong.trong_so_hoa_hong.nguoi_chot),
        nguoi_ho_tro: pickNumber(form.luong_hoa_hong.trong_so_hoa_hong.nguoi_ho_tro, DEFAULTS.luong_hoa_hong.trong_so_hoa_hong.nguoi_ho_tro),
      },
    },
    ky_chot_luong: {
      ngay_bat_dau: pickNumber(form.ky_chot_luong.ngay_bat_dau, DEFAULTS.ky_chot_luong.ngay_bat_dau),
      ngay_ket_thuc: pickNumber(form.ky_chot_luong.ngay_ket_thuc, DEFAULTS.ky_chot_luong.ngay_ket_thuc),
    },
    bao_hiem_va_thue: {
      ap_dung_khau_tru: {
        mo_ta: form.bao_hiem_va_thue.ap_dung_khau_tru.mo_ta?.trim()
          || DEFAULTS.bao_hiem_va_thue.ap_dung_khau_tru.mo_ta,
        gia_tri: Boolean(form.bao_hiem_va_thue.ap_dung_khau_tru.gia_tri),
      },
      giam_tru_gia_canh_ban_than: {
        mo_ta: form.bao_hiem_va_thue.giam_tru_gia_canh_ban_than.mo_ta?.trim()
          || DEFAULTS.bao_hiem_va_thue.giam_tru_gia_canh_ban_than.mo_ta,
        gia_tri: pickNumber(
          form.bao_hiem_va_thue.giam_tru_gia_canh_ban_than.gia_tri,
          DEFAULTS.bao_hiem_va_thue.giam_tru_gia_canh_ban_than.gia_tri,
        ),
      },
      giam_tru_gia_nguoi_phu_thuoc: {
        mo_ta: form.bao_hiem_va_thue.giam_tru_gia_nguoi_phu_thuoc.mo_ta?.trim()
          || DEFAULTS.bao_hiem_va_thue.giam_tru_gia_nguoi_phu_thuoc.mo_ta,
        gia_tri: pickNumber(
          form.bao_hiem_va_thue.giam_tru_gia_nguoi_phu_thuoc.gia_tri,
          DEFAULTS.bao_hiem_va_thue.giam_tru_gia_nguoi_phu_thuoc.gia_tri,
        ),
      },
      nguoi_lao_dong_dong: {
        bhxh: pickNumber(form.bao_hiem_va_thue.nguoi_lao_dong_dong.bhxh, DEFAULTS.bao_hiem_va_thue.nguoi_lao_dong_dong.bhxh),
        bhyt: pickNumber(form.bao_hiem_va_thue.nguoi_lao_dong_dong.bhyt, DEFAULTS.bao_hiem_va_thue.nguoi_lao_dong_dong.bhyt),
        bhtn: pickNumber(form.bao_hiem_va_thue.nguoi_lao_dong_dong.bhtn, DEFAULTS.bao_hiem_va_thue.nguoi_lao_dong_dong.bhtn),
      },
      doanh_nghiep_dong: {
        bhxh: pickNumber(form.bao_hiem_va_thue.doanh_nghiep_dong.bhxh, DEFAULTS.bao_hiem_va_thue.doanh_nghiep_dong.bhxh),
        bhyt: pickNumber(form.bao_hiem_va_thue.doanh_nghiep_dong.bhyt, DEFAULTS.bao_hiem_va_thue.doanh_nghiep_dong.bhyt),
        bhtn: pickNumber(form.bao_hiem_va_thue.doanh_nghiep_dong.bhtn, DEFAULTS.bao_hiem_va_thue.doanh_nghiep_dong.bhtn),
      },
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
    ElMessage.success('Đã lưu cấu hình lương & hoa hồng.')
  } finally {
    saving.value = false
  }
}

onMounted(loadConfig)
</script>

<style scoped lang="scss">
.config-tabs :deep(.el-tabs__header) {
  margin-bottom: 8px;
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
    padding-top: 8px;
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

.setting-hint {
  margin: 0 0 12px;
  font-size: 13px;
  line-height: 1.5;
  color: var(--el-text-color-secondary);
}

.mo-ta-item {
  margin-bottom: 0;
}

.option-group {
  margin-top: 10px;
}

.coeff-row {
  margin-top: 10px;
}

.field-label {
  margin-bottom: 6px;
  font-size: 13px;
  color: var(--el-text-color-regular);
}

@media (max-width: 767px) {
  .setting-item :deep(.el-col:not(:last-child)) {
    margin-bottom: 12px;
  }
}
</style>
