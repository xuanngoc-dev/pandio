<template>
  <div class="san-xuat-dieu-phoi page-list" v-loading="loading">
    <CustomCard shadow="hover" class="filter-card">
      <CustomRow :gutter="12" class="toolbar" align="middle">
        <CustomCol :xs="12" :sm="12" :md="8" :lg="6">
          <CustomDatePicker
            v-model="dateRange"
            type="daterange"
            range-separator="—"
            start-placeholder="Từ ngày"
            end-placeholder="Đến ngày"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            unlink-panels
            :clearable="false"
            style="width: 100%"
          />
        </CustomCol>
        <CustomCol :xs="24" :sm="24" :md="16" :lg="18">
          <div class="toolbar-actions">
            <CustomButton type="primary" :loading="loading" @click="applyFilter">
              Áp dụng
            </CustomButton>
            <CustomButton
              v-for="preset in datePresets"
              :key="preset.key"
              :type="activePreset === preset.key ? 'primary' : 'default'"
              plain
              @click="applyDatePreset(preset.key)"
            >
              {{ preset.label }}
            </CustomButton>
          </div>
        </CustomCol>
      </CustomRow>
    </CustomCard>

    <CustomRow :gutter="12" class="dash-row">
      <CustomCol
        v-for="card in statCards"
        :key="card.key"
        :xs="12"
        :sm="12"
        :md="8"
        :lg="4"
      >
        <StatCard
          :title="card.title"
          :value="card.value"
          :hint="card.hint"
          :tone="card.tone"
        >
          <template #icon>
            <component :is="card.icon" />
          </template>
        </StatCard>
      </CustomCol>
    </CustomRow>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import {
  Camera,
  CircleCheck,
  Clock,
  Document,
  Finished,
  Picture,
  Printer,
} from '@element-plus/icons-vue'
import { fetchTongQuanSanXuatDieuPhoi } from '@/api/tongQuanSanXuatDieuPhoi'
import StatCard from '@/components/dashboard/StatCard.vue'

defineOptions({ name: 'SanXuatDieuPhoi' })

const datePresets = [
  { key: 'this_week', label: 'Tuần này' },
  { key: 'last_week', label: 'Tuần trước' },
  { key: 'this_month', label: 'Tháng này' },
  { key: 'last_month', label: 'Tháng trước' },
]

const dateRange = ref(getPresetRange('this_month'))
const appliedRange = ref(getPresetRange('this_month'))
const loading = ref(false)
const stats = ref(null)

const activePreset = computed(() => {
  const range = dateRange.value
  if (!range?.[0] || !range?.[1]) return null
  for (const preset of datePresets) {
    const expected = getPresetRange(preset.key)
    if (expected && expected[0] === range[0] && expected[1] === range[1]) {
      return preset.key
    }
  }
  return null
})

const statCards = computed(() => {
  const s = stats.value || emptyStats()

  return [
    {
      key: 'so_hop_dong_sddv_ky',
      title: 'Số hợp đồng SDDV ký',
      value: formatCount(s.so_hop_dong_sddv_ky),
      hint: 'HĐ SDDV tạo trong kỳ (loại nháp / hủy)',
      tone: 'primary',
      icon: Document,
    },
    {
      key: 'so_buoi_chup',
      title: 'Số buổi chụp',
      value: formatCount(s.so_buoi_chup),
      hint: 'Tổng buổi chụp trong thông tin điều phối của HĐ ký trong kỳ',
      tone: 'info',
      icon: Camera,
    },
    {
      key: 'so_hd_cho_dieu_phoi',
      title: 'HĐ chờ điều phối',
      value: formatCount(s.so_hd_cho_dieu_phoi),
      hint: 'HĐ ký trong kỳ chưa có trạng thái điều phối',
      tone: 'warning',
      icon: Clock,
    },
    {
      key: 'so_hd_tien_ky',
      title: 'HĐ Tiền kỳ',
      value: formatCount(s.so_hd_tien_ky),
      hint: 'HĐ ký trong kỳ đang ở bước tiền kỳ',
      tone: 'info',
      icon: Picture,
    },
    {
      key: 'so_hd_hau_ky',
      title: 'HĐ Hậu kỳ',
      value: formatCount(s.so_hd_hau_ky),
      hint: 'HĐ ký trong kỳ đang ở bước hậu kỳ',
      tone: 'primary',
      icon: Finished,
    },
    {
      key: 'so_hd_gui_in',
      title: 'HĐ gửi in',
      value: formatCount(s.so_hd_gui_in),
      hint: 'HĐ ký trong kỳ đang ở bước gửi in',
      tone: 'warning',
      icon: Printer,
    },
    {
      key: 'so_hd_hoan_tat_san_xuat',
      title: 'HĐ Hoàn tất sản xuất',
      value: formatCount(s.so_hd_hoan_tat_san_xuat),
      hint: 'HĐ ký trong kỳ đã hoàn tất sản xuất',
      tone: 'success',
      icon: CircleCheck,
    },
  ]
})

onMounted(() => {
  loadStats()
})

function applyFilter() {
  if (!dateRange.value?.[0] || !dateRange.value?.[1]) {
    ElMessage.warning('Vui lòng chọn khoảng thời gian')
    return
  }
  appliedRange.value = [...dateRange.value]
  loadStats()
}

function applyDatePreset(key) {
  dateRange.value = getPresetRange(key)
  applyFilter()
}

async function loadStats() {
  const range = appliedRange.value
  if (!range?.[0] || !range?.[1]) return

  loading.value = true
  try {
    const { data } = await fetchTongQuanSanXuatDieuPhoi(
      {
        tu_ngay: range[0],
        den_ngay: range[1],
      },
      { skipLoading: true },
    )
    stats.value = data
  } catch {
    stats.value = emptyStats()
  } finally {
    loading.value = false
  }
}

function emptyStats() {
  return {
    so_hop_dong_sddv_ky: 0,
    so_buoi_chup: 0,
    so_hd_cho_dieu_phoi: 0,
    so_hd_tien_ky: 0,
    so_hd_hau_ky: 0,
    so_hd_gui_in: 0,
    so_hd_hoan_tat_san_xuat: 0,
  }
}

function toYmd(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

/** Thứ 2 trong tuần chứa `date` (tuần bắt đầu từ Thứ 2) */
function getMonday(date) {
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const day = d.getDay()
  const diff = day === 0 ? -6 : 1 - day
  d.setDate(d.getDate() + diff)
  return d
}

function addDays(date, days) {
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  d.setDate(d.getDate() + days)
  return d
}

/** Khoảng ngày cho preset — tuần: T2→CN */
function getPresetRange(key) {
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const monday = getMonday(today)

  if (key === 'this_week') {
    return [toYmd(monday), toYmd(addDays(monday, 6))]
  }
  if (key === 'last_week') {
    const start = addDays(monday, -7)
    return [toYmd(start), toYmd(addDays(start, 6))]
  }
  if (key === 'this_month') {
    const start = new Date(now.getFullYear(), now.getMonth(), 1)
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
    return [toYmd(start), toYmd(end)]
  }
  if (key === 'last_month') {
    const start = new Date(now.getFullYear(), now.getMonth() - 1, 1)
    const end = new Date(now.getFullYear(), now.getMonth(), 0)
    return [toYmd(start), toYmd(end)]
  }
  return null
}

function formatCount(val) {
  return Number(val || 0).toLocaleString('vi-VN')
}
</script>

<style scoped lang="scss">
.san-xuat-dieu-phoi.page-list {
  gap: 14px;
}

.filter-card {
  .toolbar {
    :deep(> .el-col) {
      min-width: 0;
    }

    :deep(.el-date-editor),
    :deep(.el-date-editor.el-date-editor--daterange) {
      width: 100% !important;
      max-width: 100%;
      min-width: 0 !important;
      box-sizing: border-box;
    }
  }
}

.dash-row {
  row-gap: 12px;

  :deep(> .el-col) {
    display: flex;
    min-width: 0;
  }

  :deep(> .el-col > .stat-card) {
    width: 100%;
    flex: 1;
    min-width: 0;
  }
}
</style>
