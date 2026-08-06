<template>
  <div class="trung-tam-phe-duyet">
    <h2 class="page-title">Trung tâm Phê duyệt</h2>
    <p class="page-desc">Duyệt các đơn đang chờ trên cùng một màn hình.</p>

    <CustomRow :gutter="16" class="stat-row">
      <CustomCol
        v-for="card in summaryCards"
        :key="card.key"
        :xs="12"
        :sm="12"
        :md="6"
        :lg="6"
      >
        <CustomCard
          shadow="hover"
          class="stat-card"
          :class="[`stat-card--${card.tone}`, { 'is-active': activeSection === card.key }]"
          @click="scrollToSection(card.key)"
        >
          <div class="stat-card__body">
            <div class="stat-card__icon" :class="`stat-card__icon--${card.tone}`">
              <CustomIcon :size="22">
                <component :is="card.icon" />
              </CustomIcon>
            </div>
            <div class="stat-card__content">
              <div class="stat-card__label">{{ card.label }}</div>
              <div class="stat-card__value">{{ card.count }}</div>
              <div class="stat-card__hint">{{ card.hint }}</div>
            </div>
          </div>
        </CustomCard>
      </CustomCol>
    </CustomRow>

    <div class="approval-sections">
      <div id="section-nghi-phep" class="approval-section">
        <DuyetNghiPhep @count-change="(n) => (counts.nghiPhep = n)" />
      </div>
      <div id="section-dat-mua" class="approval-section">
        <DuyetDatMuaTrangPhuc @count-change="(n) => (counts.datMua = n)" />
      </div>
      <div id="section-phieu-thu-chi" class="approval-section">
        <DuyetPhieuThuChi @count-change="(n) => (counts.phieuThuChi = n)" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import {
  Calendar,
  CircleCheck,
  Goods,
  Wallet,
} from '@element-plus/icons-vue'
import {
  CustomCard,
  CustomCol,
  CustomIcon,
  CustomRow,
} from '@/components/element'
import DuyetNghiPhep from './trung-tam-phe-duyet/DuyetNghiPhep.vue'
import DuyetDatMuaTrangPhuc from './trung-tam-phe-duyet/DuyetDatMuaTrangPhuc.vue'
import DuyetPhieuThuChi from './trung-tam-phe-duyet/DuyetPhieuThuChi.vue'

const counts = ref({
  nghiPhep: 0,
  datMua: 0,
  phieuThuChi: 0,
})

const activeSection = ref('tong')

const totalCount = computed(
  () => counts.value.nghiPhep + counts.value.datMua + counts.value.phieuThuChi,
)

const summaryCards = computed(() => [
  {
    key: 'tong',
    label: 'Tổng chờ duyệt',
    count: totalCount.value,
    hint: 'Tất cả hạng mục',
    tone: 'primary',
    icon: CircleCheck,
  },
  {
    key: 'nghi-phep',
    label: 'Nghỉ phép',
    count: counts.value.nghiPhep,
    hint: 'Đơn xin nghỉ phép',
    tone: 'warning',
    icon: Calendar,
  },
  {
    key: 'dat-mua',
    label: 'Đặt mua trang phục',
    count: counts.value.datMua,
    hint: 'Đơn đặt mua',
    tone: 'success',
    icon: Goods,
  },
  {
    key: 'phieu-thu-chi',
    label: 'Phiếu thu chi',
    count: counts.value.phieuThuChi,
    hint: 'Phiếu thu / chi',
    tone: 'danger',
    icon: Wallet,
  },
])

function scrollToSection(key) {
  activeSection.value = key
  if (key === 'tong') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }
  const el = document.getElementById(`section-${key}`)
  el?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
</script>

<style scoped lang="scss">
.trung-tam-phe-duyet {
  .page-title {
    margin: 0 0 4px;
    font-size: 20px;
    font-weight: 600;
    color: var(--el-text-color-primary);
  }

  .page-desc {
    margin: 0 0 16px;
    font-size: 14px;
    color: var(--el-text-color-secondary);
  }

  .stat-row {
    margin-bottom: 20px;
  }

  .stat-card {
    cursor: pointer;
    margin-bottom: 12px;
    border: 1px solid transparent;
    transition: border-color 0.2s ease, transform 0.2s ease;

    &:hover {
      transform: translateY(-2px);
    }

    &.is-active {
      border-color: var(--el-color-primary);
    }

    :deep(.el-card__body) {
      padding: 16px;
    }
  }

  .stat-card__body {
    display: flex;
    align-items: flex-start;
    gap: 12px;
  }

  .stat-card__icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;

    &--primary {
      color: var(--el-color-primary);
      background: var(--el-color-primary-light-9);
    }

    &--warning {
      color: var(--el-color-warning);
      background: var(--el-color-warning-light-9);
    }

    &--success {
      color: var(--el-color-success);
      background: var(--el-color-success-light-9);
    }

    &--danger {
      color: var(--el-color-danger);
      background: var(--el-color-danger-light-9);
    }
  }

  .stat-card__content {
    min-width: 0;
  }

  .stat-card__label {
    font-size: 13px;
    color: var(--el-text-color-secondary);
    line-height: 1.3;
  }

  .stat-card__value {
    margin-top: 4px;
    font-size: 28px;
    font-weight: 700;
    line-height: 1.2;
    color: var(--el-text-color-primary);
  }

  .stat-card__hint {
    margin-top: 2px;
    font-size: 12px;
    color: var(--el-text-color-placeholder);
  }

  .approval-sections {
    display: flex;
    flex-direction: column;
    gap: 24px;
  }

  .approval-section {
    scroll-margin-top: 16px;
  }
}
</style>
