<template>
  <div class="tong-quan page-list">
    <div class="page-head">
      <div>
        <h1 class="page-head__title">Tổng quan</h1>
        <p class="page-head__sub">Theo dõi vận hành studio cưới — dữ liệu minh họa, chưa ghép API</p>
      </div>
      <CustomSelect v-model="period" :clearable="false" placeholder="Kỳ báo cáo" style="width: 148px">
        <CustomOption
          v-for="item in periodOptions"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        />
      </CustomSelect>
    </div>

    <CustomRow :gutter="12" class="dash-row">
      <CustomCol
        v-for="card in statCards"
        :key="card.key"
        :xs="12"
        :sm="8"
        :md="6"
        :lg="4"
      >
        <StatCard
          :title="card.title"
          :value="card.value"
          :change="card.change"
          :tone="card.tone"
        >
          <template #icon>
            <component :is="card.icon" />
          </template>
          <template #chart>
            <ChartSparkline :series="card.spark" :colors="[card.sparkColor]" :height="28" />
          </template>
        </StatCard>
      </CustomCol>
    </CustomRow>

    <CustomRow :gutter="12" class="dash-row">
      <CustomCol :xs="24" :lg="16">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Doanh thu</div>
                <p class="card-sub">Tổng thu theo kỳ đang chọn</p>
              </div>
            </div>
          </template>
          <ChartArea
            :series="revenueSeries"
            :categories="revenueCategories"
            :height="chartH"
            :y-formatter="formatCompactMoney"
            :tooltip-formatter="formatMoney"
          />
        </CustomCard>
      </CustomCol>
      <CustomCol :xs="24" :md="12" :lg="8">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Trạng thái hợp đồng</div>
                <p class="card-sub">Phân bổ HĐ SDDV đang xử lý</p>
              </div>
            </div>
          </template>
          <ChartDonut
            :series="contractStatusSeries"
            :labels="contractStatusLabels"
            :colors="contractStatusColors"
            :height="chartH"
            total-label="Hợp đồng"
            :options="donutCompactOptions"
          />
        </CustomCard>
      </CustomCol>
    </CustomRow>

    <CustomRow :gutter="12" class="dash-row">
      <CustomCol :xs="24" :md="12" :lg="8">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Hợp đồng mới</div>
                <p class="card-sub">SDDV và cho thuê trang phục</p>
              </div>
            </div>
          </template>
          <ChartColumn
            :series="contractSeries"
            :categories="contractCategories"
            :height="chartH"
            stacked
          />
        </CustomCard>
      </CustomCol>
      <CustomCol :xs="24" :md="12" :lg="8">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Khách hàng & lead</div>
                <p class="card-sub">Khách mới so với note khách mới</p>
              </div>
            </div>
          </template>
          <ChartLine
            :series="customerSeries"
            :categories="customerCategories"
            :height="chartH"
            :colors="['#409eff', '#e6a23c']"
          />
        </CustomCard>
      </CustomCol>
      <CustomCol :xs="24" :md="12" :lg="8">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Nguồn khách hàng</div>
                <p class="card-sub">Tỷ lệ lead theo kênh</p>
              </div>
            </div>
          </template>
          <ChartPie
            :series="channelSeries"
            :labels="channelLabels"
            :colors="channelColors"
            :height="chartH"
            :tooltip-formatter="(val) => `${val} lead`"
          />
        </CustomCard>
      </CustomCol>
    </CustomRow>

    <CustomRow :gutter="12" class="dash-row">
      <CustomCol :xs="24" :md="12" :lg="8">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Thực thu & công nợ</div>
                <p class="card-sub">Số đã thu so với còn phải thu</p>
              </div>
            </div>
          </template>
          <ChartColumn
            :series="cashSeries"
            :categories="cashCategories"
            :height="chartH"
            stacked
            :colors="['#67c23a', '#e6a23c']"
            :y-formatter="formatCompactMoney"
            :tooltip-formatter="formatMoney"
          />
        </CustomCard>
      </CustomCol>
      <CustomCol :xs="24" :md="12" :lg="8">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Lịch theo thứ</div>
                <p class="card-sub">Số buổi chụp / makeup trong kỳ</p>
              </div>
            </div>
          </template>
          <ChartColumn
            :series="weekdaySeries"
            :categories="weekdayCategories"
            :height="chartH"
            distributed
            :colors="weekdayColors"
          />
        </CustomCard>
      </CustomCol>
      <CustomCol :xs="24" :md="12" :lg="8">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Tiến độ mục tiêu</div>
                <p class="card-sub">So với chỉ tiêu đã đặt</p>
              </div>
            </div>
          </template>
          <ChartRadialBar
            :series="goalSeries"
            :labels="goalLabels"
            :height="chartH"
            :colors="['#409eff', '#67c23a', '#e6a23c']"
          />
        </CustomCard>
      </CustomCol>
    </CustomRow>

    <CustomRow :gutter="12" class="dash-row">
      <CustomCol :xs="24" :sm="12" :lg="6">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Top nhân viên sale</div>
                <p class="card-sub">Nhiều hợp đồng nhất</p>
              </div>
            </div>
          </template>
          <RankList :items="topSales" />
        </CustomCard>
      </CustomCol>
      <CustomCol :xs="24" :sm="12" :lg="6">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Dịch vụ dùng nhiều</div>
                <p class="card-sub">Theo số hợp đồng</p>
              </div>
            </div>
          </template>
          <RankList :items="topServices" />
        </CustomCard>
      </CustomCol>
      <CustomCol :xs="24" :sm="12" :lg="6">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Trang phục đang hot</div>
                <p class="card-sub">Lượt chọn trong hợp đồng</p>
              </div>
            </div>
          </template>
          <RankList :items="topCostumes" />
        </CustomCard>
      </CustomCol>
      <CustomCol :xs="24" :sm="12" :lg="6">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <div>
                <div class="card-title">Concept đang hot</div>
                <p class="card-sub">Lượt book nhiều nhất</p>
              </div>
            </div>
          </template>
          <RankList :items="topConcepts" />
        </CustomCard>
      </CustomCol>
    </CustomRow>

    <CustomRow :gutter="12" class="dash-row">
      <CustomCol :xs="24" :lg="14">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <span class="card-title">Hợp đồng gần đây</span>
            </div>
          </template>
          <CustomTable :data="recentContracts" stripe style="width: 100%">
            <CustomTableColumn prop="ma" label="Mã HĐ" min-width="110" />
            <CustomTableColumn prop="khachHang" label="Khách hàng" min-width="140" />
            <CustomTableColumn prop="dichVu" label="Dịch vụ" min-width="120" />
            <CustomTableColumn prop="giaTri" label="Giá trị" min-width="120" align="right">
              <template #default="{ row }">
                {{ formatMoney(row.giaTri) }}
              </template>
            </CustomTableColumn>
            <CustomTableColumn label="Trạng thái" width="130" align="center">
              <template #default="{ row }">
                <CustomTag :type="row.tagType" size="small">{{ row.trangThai }}</CustomTag>
              </template>
            </CustomTableColumn>
          </CustomTable>
        </CustomCard>
      </CustomCol>
      <CustomCol :xs="24" :lg="10">
        <CustomCard shadow="hover" class="chart-card">
          <template #header>
            <div class="card-header">
              <span class="card-title">Lịch sắp tới</span>
            </div>
          </template>
          <ul class="schedule-list">
            <li v-for="item in upcomingSchedules" :key="item.id" class="schedule-item">
              <span class="schedule-item__dot" :style="{ background: item.color }" />
              <div class="schedule-item__body">
                <div class="schedule-item__title">{{ item.title }}</div>
                <div class="schedule-item__meta">{{ item.customer }} · {{ item.time }}</div>
              </div>
              <CustomTag :type="item.tagType" size="small">{{ item.type }}</CustomTag>
            </li>
          </ul>
        </CustomCard>
      </CustomCol>
    </CustomRow>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import {
  Calendar,
  Document,
  Finished,
  Star,
  User,
  Wallet,
} from '@element-plus/icons-vue'
import {
  ChartArea,
  ChartColumn,
  ChartDonut,
  ChartLine,
  ChartPie,
  ChartRadialBar,
  ChartSparkline,
} from '@/components/charts'
import RankList from '@/components/dashboard/RankList.vue'
import StatCard from '@/components/dashboard/StatCard.vue'

const chartH = 210
const donutCompactOptions = {
  plotOptions: {
    pie: {
      donut: {
        labels: {
          name: { fontSize: '12px' },
          value: { fontSize: '18px', fontWeight: 600 },
        },
      },
    },
  },
  legend: { fontSize: '12px' },
}

const period = ref('month')
const periodOptions = [
  { value: 'week', label: 'Tuần này' },
  { value: 'month', label: 'Tháng này' },
  { value: 'quarter', label: 'Quý này' },
  { value: 'year', label: 'Năm nay' },
]

/** Mock — thay bằng API sau */
const MOCK = {
  week: {
    stats: {
      revenue: 128_000_000,
      revenueChange: 6.2,
      contracts: 9,
      contractsChange: 12.5,
      customers: 14,
      customersChange: 7.1,
      schedules: 21,
      schedulesChange: -4.3,
      tasks: 17,
      tasksChange: 0,
      rating: 4.8,
      ratingChange: 2.1,
    },
    spark: {
      revenue: [18, 22, 19, 28, 24, 31, 35],
      contracts: [1, 0, 2, 1, 2, 1, 2],
      customers: [2, 1, 3, 2, 1, 3, 2],
      schedules: [4, 3, 2, 5, 3, 2, 2],
      tasks: [6, 5, 7, 4, 5, 3, 4],
      rating: [4.6, 4.7, 4.7, 4.8, 4.8, 4.9, 4.8],
    },
    revenueCategories: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
    revenue: [12, 16, 14, 22, 18, 24, 22].map((n) => n * 1_000_000),
    contractsCategories: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
    contractsSddv: [1, 0, 2, 1, 1, 2, 1],
    contractsRent: [0, 1, 0, 1, 0, 1, 0],
    customerCategories: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
    customers: [2, 1, 3, 2, 1, 3, 2],
    leads: [4, 3, 5, 4, 6, 5, 3],
    collected: [9, 12, 10, 16, 14, 18, 15].map((n) => n * 1_000_000),
    debt: [3, 4, 4, 6, 4, 6, 7].map((n) => n * 1_000_000),
    weekdays: [2, 4, 3, 5, 4, 2, 1],
  },
  month: {
    stats: {
      revenue: 486_000_000,
      revenueChange: 12.4,
      contracts: 38,
      contractsChange: 8.1,
      customers: 54,
      customersChange: 15.2,
      schedules: 86,
      schedulesChange: 3.6,
      tasks: 17,
      tasksChange: -11.5,
      rating: 4.8,
      ratingChange: 2.1,
    },
    spark: {
      revenue: [32, 28, 41, 36, 48, 44, 52, 49],
      contracts: [3, 4, 5, 4, 6, 5, 7, 4],
      customers: [5, 6, 4, 8, 7, 9, 8, 7],
      schedules: [9, 11, 8, 12, 10, 14, 11, 11],
      tasks: [8, 7, 9, 6, 5, 7, 4, 5],
      rating: [4.6, 4.7, 4.7, 4.8, 4.8, 4.8, 4.9, 4.8],
    },
    revenueCategories: ['Tuần 1', 'Tuần 2', 'Tuần 3', 'Tuần 4'],
    revenue: [98, 112, 128, 148].map((n) => n * 1_000_000),
    contractsCategories: ['Tuần 1', 'Tuần 2', 'Tuần 3', 'Tuần 4'],
    contractsSddv: [6, 8, 7, 9],
    contractsRent: [2, 3, 1, 2],
    customerCategories: ['Tuần 1', 'Tuần 2', 'Tuần 3', 'Tuần 4'],
    customers: [11, 13, 14, 16],
    leads: [18, 22, 19, 24],
    collected: [72, 84, 96, 110].map((n) => n * 1_000_000),
    debt: [26, 28, 32, 38].map((n) => n * 1_000_000),
    weekdays: [9, 14, 12, 16, 15, 12, 8],
  },
  quarter: {
    stats: {
      revenue: 1_320_000_000,
      revenueChange: 9.8,
      contracts: 102,
      contractsChange: 5.4,
      customers: 148,
      customersChange: 11.0,
      schedules: 240,
      schedulesChange: 6.2,
      tasks: 29,
      tasksChange: 4.0,
      rating: 4.7,
      ratingChange: 1.1,
    },
    spark: {
      revenue: [280, 310, 295, 340, 360, 390],
      contracts: [14, 16, 15, 18, 19, 20],
      customers: [20, 22, 24, 26, 28, 28],
      schedules: [34, 38, 36, 42, 44, 46],
      tasks: [12, 10, 11, 9, 8, 10],
      rating: [4.6, 4.6, 4.7, 4.7, 4.8, 4.7],
    },
    revenueCategories: ['Tháng 6', 'Tháng 7', 'Tháng 8'],
    revenue: [390, 444, 486].map((n) => n * 1_000_000),
    contractsCategories: ['Tháng 6', 'Tháng 7', 'Tháng 8'],
    contractsSddv: [22, 26, 30],
    contractsRent: [8, 7, 9],
    customerCategories: ['Tháng 6', 'Tháng 7', 'Tháng 8'],
    customers: [42, 52, 54],
    leads: [61, 70, 83],
    collected: [290, 330, 362].map((n) => n * 1_000_000),
    debt: [100, 114, 124].map((n) => n * 1_000_000),
    weekdays: [28, 38, 34, 42, 40, 32, 26],
  },
  year: {
    stats: {
      revenue: 4_860_000_000,
      revenueChange: 18.6,
      contracts: 412,
      contractsChange: 14.2,
      customers: 590,
      customersChange: 21.3,
      schedules: 980,
      schedulesChange: 9.4,
      tasks: 41,
      tasksChange: -6.8,
      rating: 4.8,
      ratingChange: 3.2,
    },
    spark: {
      revenue: [280, 310, 340, 360, 390, 420, 410, 460, 440, 480, 500, 470],
      contracts: [24, 28, 30, 32, 34, 36, 33, 40, 38, 42, 39, 36],
      customers: [38, 42, 44, 48, 50, 52, 49, 56, 54, 58, 55, 44],
      schedules: [70, 74, 78, 80, 84, 88, 82, 92, 90, 96, 78, 68],
      tasks: [18, 16, 20, 14, 12, 15, 11, 10, 13, 12, 9, 8],
      rating: [4.5, 4.6, 4.6, 4.7, 4.7, 4.7, 4.8, 4.8, 4.8, 4.8, 4.9, 4.8],
    },
    revenueCategories: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
    revenue: [280, 310, 340, 360, 390, 420, 410, 486, 440, 480, 500, 444].map((n) => n * 1_000_000),
    contractsCategories: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
    contractsSddv: [18, 22, 24, 26, 28, 30, 27, 32, 30, 34, 31, 28],
    contractsRent: [6, 6, 6, 6, 6, 6, 6, 6, 8, 8, 8, 8],
    customerCategories: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
    customers: [38, 42, 44, 48, 50, 52, 49, 54, 54, 58, 55, 46],
    leads: [50, 55, 58, 62, 64, 70, 66, 83, 72, 78, 74, 60],
    collected: [210, 230, 255, 270, 292, 315, 308, 362, 330, 360, 375, 333].map((n) => n * 1_000_000),
    debt: [70, 80, 85, 90, 98, 105, 102, 124, 110, 120, 125, 111].map((n) => n * 1_000_000),
    weekdays: [110, 148, 132, 164, 156, 140, 130],
  },
}

const current = computed(() => MOCK[period.value] || MOCK.month)

function formatMoney(val) {
  return `${Number(val || 0).toLocaleString('vi-VN')} ₫`
}

function formatCompactMoney(val) {
  const n = Number(val) || 0
  if (Math.abs(n) >= 1_000_000_000) return `${(n / 1_000_000_000).toFixed(1)} tỷ`
  if (Math.abs(n) >= 1_000_000) return `${Math.round(n / 1_000_000)} tr`
  return n.toLocaleString('vi-VN')
}

const statCards = computed(() => {
  const { stats, spark } = current.value
  return [
    {
      key: 'revenue',
      title: 'Doanh thu',
      value: `${formatCompactMoney(stats.revenue)} ₫`,
      change: stats.revenueChange,
      tone: 'primary',
      icon: Wallet,
      spark: spark.revenue,
      sparkColor: '#409eff',
    },
    {
      key: 'contracts',
      title: 'Hợp đồng mới',
      value: stats.contracts,
      change: stats.contractsChange,
      tone: 'success',
      icon: Document,
      spark: spark.contracts,
      sparkColor: '#67c23a',
    },
    {
      key: 'customers',
      title: 'Khách hàng mới',
      value: stats.customers,
      change: stats.customersChange,
      tone: 'warning',
      icon: User,
      spark: spark.customers,
      sparkColor: '#e6a23c',
    },
    {
      key: 'schedules',
      title: 'Lịch điều phối',
      value: stats.schedules,
      change: stats.schedulesChange,
      tone: 'info',
      icon: Calendar,
      spark: spark.schedules,
      sparkColor: '#909399',
    },
    {
      key: 'tasks',
      title: 'Việc chờ xử lý',
      value: stats.tasks,
      change: stats.tasksChange,
      tone: 'danger',
      icon: Finished,
      spark: spark.tasks,
      sparkColor: '#f56c6c',
    },
    {
      key: 'rating',
      title: 'Đánh giá TB',
      value: `${stats.rating}/5`,
      change: stats.ratingChange,
      tone: 'success',
      icon: Star,
      spark: spark.rating,
      sparkColor: '#67c23a',
    },
  ]
})

const revenueSeries = computed(() => [
  { name: 'Doanh thu', data: current.value.revenue },
])
const revenueCategories = computed(() => current.value.revenueCategories)

const contractSeries = computed(() => [
  { name: 'SDDV', data: current.value.contractsSddv },
  { name: 'Thuê TP', data: current.value.contractsRent },
])
const contractCategories = computed(() => current.value.contractsCategories)

const customerSeries = computed(() => [
  { name: 'Khách hàng mới', data: current.value.customers },
  { name: 'Note khách mới', data: current.value.leads },
])
const customerCategories = computed(() => current.value.customerCategories)

const cashSeries = computed(() => [
  { name: 'Đã thu', data: current.value.collected },
  { name: 'Công nợ', data: current.value.debt },
])
const cashCategories = computed(() => current.value.revenueCategories)

const weekdayCategories = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']
const weekdaySeries = computed(() => [
  { name: 'Buổi lịch', data: current.value.weekdays },
])
const weekdayColors = ['#409eff', '#67c23a', '#e6a23c', '#9b59b6', '#f56c6c', '#36cfc9', '#909399']

const contractStatusLabels = ['Đang thực hiện', 'Chờ duyệt', 'Hoàn thành', 'Tạm dừng']
const contractStatusSeries = [46, 18, 28, 8]
const contractStatusColors = ['#409eff', '#e6a23c', '#67c23a', '#909399']

const channelLabels = ['Facebook', 'TikTok', 'Zalo', 'Giới thiệu', 'Walk-in']
const channelSeries = [38, 24, 16, 14, 8]
const channelColors = ['#409eff', '#303133', '#67c23a', '#e6a23c', '#909399']

const goalLabels = ['Doanh thu', 'Hợp đồng', 'Khách mới']
const goalSeries = [78, 64, 91]

const topSales = [
  { name: 'Nguyễn Thị Lan', meta: 'Sale · CN Cầu Giấy', value: 18, valueLabel: '18 HĐ', color: '#409eff' },
  { name: 'Trần Minh Quân', meta: 'Sale · CN Hà Đông', value: 15, valueLabel: '15 HĐ', color: '#67c23a' },
  { name: 'Lê Thu Trang', meta: 'Sale · CN Cầu Giấy', value: 14, valueLabel: '14 HĐ', color: '#e6a23c' },
  { name: 'Phạm Đức Anh', meta: 'Sale · CN Thanh Xuân', value: 11, valueLabel: '11 HĐ', color: '#9b59b6' },
  { name: 'Võ Ngọc Hà', meta: 'Sale · CN Hà Đông', value: 9, valueLabel: '9 HĐ', color: '#f56c6c' },
]

const topServices = [
  { name: 'Album cưới', meta: 'Chụp + retouch', value: 42, valueLabel: '42 HĐ', color: '#409eff' },
  { name: 'Quay phim ngày cưới', meta: 'Highlight + full', value: 31, valueLabel: '31 HĐ', color: '#67c23a' },
  { name: 'Makeup cô dâu', meta: 'Lễ + tiệc', value: 24, valueLabel: '24 HĐ', color: '#e6a23c' },
  { name: 'Thuê trang phục', meta: 'Váy · vest · áo dài', value: 19, valueLabel: '19 HĐ', color: '#9b59b6' },
  { name: 'In ấn album', meta: 'Photobook', value: 14, valueLabel: '14 HĐ', color: '#f56c6c' },
]

const topCostumes = [
  { name: 'Váy A-line Champagne', meta: 'Cô dâu · size S–L', value: 28, valueLabel: '28 lượt', color: '#e6a23c' },
  { name: 'Áo dài đỏ gấm', meta: 'Lễ vu quy', value: 22, valueLabel: '22 lượt', color: '#f56c6c' },
  { name: 'Vest nam Navy', meta: 'Chú rể', value: 19, valueLabel: '19 lượt', color: '#409eff' },
  { name: 'Váy mermaid Ivory', meta: 'Studio + tiệc', value: 16, valueLabel: '16 lượt', color: '#9b59b6' },
  { name: 'Áo dài trắng thêu', meta: 'Ngoại cảnh', value: 13, valueLabel: '13 lượt', color: '#67c23a' },
]

const topConcepts = [
  { name: 'Hàn Quốc nhẹ nhàng', meta: 'Studio + park', value: 26, valueLabel: '26 book', color: '#409eff' },
  { name: 'Hoàng hôn biển', meta: 'Ngoại cảnh', value: 21, valueLabel: '21 book', color: '#e6a23c' },
  { name: 'Vintage film', meta: 'Tone film cũ', value: 18, valueLabel: '18 book', color: '#9b59b6' },
  { name: 'Phố cổ Hà Nội', meta: 'Áo dài + phố', value: 15, valueLabel: '15 book', color: '#f56c6c' },
  { name: 'Garden fairy', meta: 'Vườn + đèn', value: 12, valueLabel: '12 book', color: '#67c23a' },
]

const recentContracts = [
  { ma: 'SDDV-0826', khachHang: 'Nguyễn Minh Anh', dichVu: 'Album cưới', giaTri: 48_000_000, trangThai: 'Đang thực hiện', tagType: 'primary' },
  { ma: 'SDDV-0824', khachHang: 'Trần Gia Hân', dichVu: 'Quay phim', giaTri: 32_000_000, trangThai: 'Chờ duyệt', tagType: 'warning' },
  { ma: 'CTTP-0819', khachHang: 'Lê Hoàng Nam', dichVu: 'Thuê áo dài', giaTri: 6_500_000, trangThai: 'Hoàn thành', tagType: 'success' },
  { ma: 'SDDV-0815', khachHang: 'Phạm Thu Hà', dichVu: 'Makeup + album', giaTri: 55_000_000, trangThai: 'Đang thực hiện', tagType: 'primary' },
  { ma: 'SDDV-0812', khachHang: 'Vũ Đức Thành', dichVu: 'In ấn album', giaTri: 12_000_000, trangThai: 'Tạm dừng', tagType: 'info' },
]

const upcomingSchedules = [
  { id: 1, title: 'Chụp album ngoại cảnh', customer: 'Nguyễn Minh Anh', time: '26/08 · 07:30', type: 'Chụp', tagType: 'primary', color: '#409eff' },
  { id: 2, title: 'Makeup cô dâu', customer: 'Trần Gia Hân', time: '27/08 · 09:00', type: 'Makeup', tagType: 'warning', color: '#e6a23c' },
  { id: 3, title: 'Quay phim lễ vu quy', customer: 'Lê Hoàng Nam', time: '28/08 · 06:00', type: 'Quay', tagType: 'success', color: '#67c23a' },
  { id: 4, title: 'Thử váy cưới', customer: 'Phạm Thu Hà', time: '29/08 · 14:00', type: 'Trang phục', tagType: 'info', color: '#9b59b6' },
  { id: 5, title: 'Giao file in', customer: 'Vũ Đức Thành', time: '30/08 · 10:30', type: 'In ấn', tagType: 'info', color: '#909399' },
]
</script>

<style scoped lang="scss">
.tong-quan.page-list {
  gap: 12px;
}

.page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.page-head__title {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  line-height: 1.3;
  color: var(--el-text-color-primary);
}

.page-head__sub {
  margin: 2px 0 0;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.dash-row {
  row-gap: 12px;

  :deep(> .el-col) {
    display: flex;
    min-width: 0;
  }

  :deep(> .el-col > .el-card) {
    width: 100%;
    flex: 1;
    min-width: 0;
  }
}

.chart-card {
  min-width: 0;

  :deep(.el-card__header) {
    padding: 10px 12px 4px;
    border-bottom: none;
  }

  :deep(.el-card__body) {
    padding: 4px 12px 10px;
  }
}

.card-title {
  font-size: 13px;
}

.card-sub {
  margin: 1px 0 0;
  font-size: 11px;
  font-weight: 400;
  color: var(--el-text-color-placeholder);
}

.schedule-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
}

.schedule-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px solid var(--el-border-color-lighter);

  &:last-child {
    border-bottom: none;
    padding-bottom: 2px;
  }

  &:first-child {
    padding-top: 2px;
  }
}

.schedule-item__dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.schedule-item__body {
  min-width: 0;
  flex: 1;
}

.schedule-item__title {
  font-size: 13px;
  font-weight: 500;
  color: var(--el-text-color-primary);
}

.schedule-item__meta {
  margin-top: 1px;
  font-size: 11px;
  color: var(--el-text-color-secondary);
}
</style>
