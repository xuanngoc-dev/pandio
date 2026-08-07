<template>
  <div class="thoi-tiet-page">
    <div class="page-header">
      <div>
        <h2 class="page-title">Thời tiết</h2>
        <p class="page-desc">Dự báo {{ daysLabel }} tại địa điểm bạn chọn</p>
      </div>

      <div class="page-actions">
        <CustomSelect
          v-model="selectedCity"
          filterable
          allow-create
          default-first-option
          placeholder="Chọn hoặc nhập thành phố"
          style="width: 240px"
          @change="loadWeather"
        >
          <CustomOption
            v-for="city in cityOptions"
            :key="city"
            :label="city"
            :value="city"
          />
        </CustomSelect>
        <CustomButton :loading="loading" @click="loadWeather">
          Làm mới
        </CustomButton>
      </div>
    </div>

    <div v-loading="loading" class="weather-body">
      <CustomCard v-if="current" shadow="hover" class="current-card">
        <div class="current-card__body">
          <div class="current-card__main">
            <div class="current-card__place">
              <span class="current-card__city">{{ placeLabel }}</span>
              <span class="current-card__updated">{{ updatedLabel }}</span>
            </div>
            <div class="current-card__temp-row">
              <img
                v-if="current.icon"
                :src="current.icon"
                :alt="current.description"
                class="current-card__icon"
              />
              <div>
                <div class="current-card__temp">{{ current.temp }}°</div>
                <div class="current-card__desc">{{ capitalize(current.description) }}</div>
              </div>
            </div>
          </div>

          <div class="current-card__meta">
            <div class="meta-item">
              <span class="meta-item__label">Cảm giác như</span>
              <span class="meta-item__value">{{ current.feelsLike }}°</span>
            </div>
            <div class="meta-item">
              <span class="meta-item__label">Độ ẩm</span>
              <span class="meta-item__value">{{ current.humidity }}%</span>
            </div>
            <div class="meta-item">
              <span class="meta-item__label">Gió</span>
              <span class="meta-item__value">{{ formatWind(current.windSpeed) }}</span>
            </div>
          </div>
        </div>
      </CustomCard>

      <div v-if="days.length" class="forecast-section">
        <h3 class="section-title">{{ days.length }} ngày tới</h3>
        <div class="forecast-grid">
          <CustomCard
            v-for="(day, index) in days"
            :key="day.dateKey"
            shadow="hover"
            class="day-card"
            :class="{ 'day-card--today': index === 0 }"
          >
            <div class="day-card__weekday">{{ formatWeekday(day.dt, index) }}</div>
            <div class="day-card__date">{{ formatDate(day.dt) }}</div>
            <img
              v-if="day.icon"
              :src="day.icon"
              :alt="day.description"
              class="day-card__icon"
            />
            <div class="day-card__temps">
              <span class="day-card__max">{{ day.tempMax }}°</span>
              <span class="day-card__min">{{ day.tempMin }}°</span>
            </div>
            <div class="day-card__desc">{{ capitalize(day.description) }}</div>
            <div class="day-card__extra">
              <span>Mưa {{ day.pop }}%</span>
              <span>Gió {{ formatWind(day.windSpeed) }}</span>
            </div>
          </CustomCard>
        </div>
      </div>

      <el-empty
        v-if="!loading && !days.length && errorMessage"
        :description="errorMessage"
      />
      <el-empty
        v-else-if="!loading && !days.length"
        description="Chưa có dữ liệu thời tiết"
      />

      <p v-if="sourceNote" class="source-note">{{ sourceNote }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import {
  CustomButton,
  CustomCard,
  CustomOption,
  CustomSelect,
} from '@/components/element'
import { fetchWeatherByCity } from '@/api/thoiTiet'

const DEFAULT_CITY =
  import.meta.env.VITE_WEATHER_DEFAULT_CITY || 'Ho Chi Minh'

const cityOptions = [
  'Ho Chi Minh',
  'Ha Noi',
  'Da Nang',
  'Can Tho',
  'Hue',
  'Nha Trang',
  'Hai Phong',
  'Vung Tau',
  'Da Lat',
]

const selectedCity = ref(DEFAULT_CITY)
const loading = ref(false)
const place = ref(null)
const current = ref(null)
const days = ref([])
const source = ref('')
const errorMessage = ref('')

const placeLabel = computed(() => {
  if (!place.value) return selectedCity.value
  const parts = [place.value.name]
  if (place.value.state) parts.push(place.value.state)
  if (place.value.country) parts.push(place.value.country)
  return parts.join(', ')
})

const daysLabel = computed(() => {
  if (!days.value.length) return '7 ngày'
  return `${days.value.length} ngày`
})

const updatedLabel = computed(() => {
  if (!current.value?.dt) return ''
  return `Cập nhật ${formatDateTime(current.value.dt)}`
})

const sourceNote = computed(() => {
  if (source.value === 'forecast5') {
    return ''
    return 'Đang dùng API Free (dự báo ~5 ngày / 3 giờ). Để đủ 7–8 ngày, đăng ký One Call API 3.0 trên OpenWeatherMap.'
  }
  if (source.value === 'onecall') {
    return 'Nguồn: OpenWeatherMap One Call API 3.0'
  }
  return ''
})

function capitalize(text) {
  if (!text) return ''
  return text.charAt(0).toUpperCase() + text.slice(1)
}

function formatWind(speed) {
  if (speed == null) return '—'
  return `${Number(speed).toFixed(1)} m/s`
}

function formatWeekday(dt, index) {
  if (index === 0) return 'Hôm nay'
  return new Date(dt * 1000).toLocaleDateString('vi-VN', { weekday: 'long' })
}

function formatDate(dt) {
  return new Date(dt * 1000).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
  })
}

function formatDateTime(dt) {
  return new Date(dt * 1000).toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
  })
}

function mapOwmError(err) {
  const status = err?.response?.status
  const msg = err?.response?.data?.message || err?.message || ''

  if (!import.meta.env.VITE_OPENWEATHERMAP_API_KEY) {
    return 'Chưa cấu hình VITE_OPENWEATHERMAP_API_KEY trong file .env'
  }
  if (status === 401) {
    return 'API key không hợp lệ hoặc chưa kích hoạt (OpenWeatherMap có thể mất đến ~2 giờ sau khi tạo key).'
  }
  if (status === 429) {
    return 'Đã vượt giới hạn số lần gọi API. Vui lòng thử lại sau.'
  }
  if (msg) return msg
  return 'Không tải được dữ liệu thời tiết'
}

async function loadWeather() {
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await fetchWeatherByCity(selectedCity.value.trim() || DEFAULT_CITY)
    place.value = data.place
    current.value = data.current
    days.value = data.days
    source.value = data.source
  } catch (err) {
    place.value = null
    current.value = null
    days.value = []
    source.value = ''
    errorMessage.value = mapOwmError(err)
    ElMessage.error(errorMessage.value)
  } finally {
    loading.value = false
  }
}

onMounted(loadWeather)
</script>

<style scoped lang="scss">
.thoi-tiet-page {
  .page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
    flex-wrap: wrap;
  }

  .page-title {
    margin: 0 0 4px;
    font-size: 20px;
    font-weight: 600;
    color: var(--el-text-color-primary);
  }

  .page-desc {
    margin: 0;
    color: var(--el-text-color-secondary);
    font-size: 13px;
  }

  .page-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .weather-body {
    min-height: 240px;
  }

  .current-card {
    margin-bottom: 20px;
    background: linear-gradient(
      135deg,
      color-mix(in srgb, var(--el-color-primary) 12%, var(--el-bg-color)),
      var(--el-bg-color)
    );

    &__body {
      display: flex;
      justify-content: space-between;
      gap: 24px;
      flex-wrap: wrap;
    }

    &__place {
      display: flex;
      flex-direction: column;
      gap: 4px;
      margin-bottom: 8px;
    }

    &__city {
      font-size: 18px;
      font-weight: 600;
      color: var(--el-text-color-primary);
    }

    &__updated {
      font-size: 12px;
      color: var(--el-text-color-secondary);
    }

    &__temp-row {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    &__icon {
      width: 88px;
      height: 88px;
    }

    &__temp {
      font-size: 48px;
      font-weight: 700;
      line-height: 1.1;
      color: var(--el-text-color-primary);
    }

    &__desc {
      margin-top: 4px;
      font-size: 15px;
      color: var(--el-text-color-regular);
      text-transform: capitalize;
    }

    &__meta {
      display: flex;
      gap: 28px;
      align-items: center;
      flex-wrap: wrap;
    }
  }

  .meta-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 88px;

    &__label {
      font-size: 12px;
      color: var(--el-text-color-secondary);
    }

    &__value {
      font-size: 18px;
      font-weight: 600;
      color: var(--el-text-color-primary);
    }
  }

  .section-title {
    margin: 0 0 12px;
    font-size: 16px;
    font-weight: 600;
    color: var(--el-text-color-primary);
  }

  .forecast-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 12px;
  }

  .day-card {
    text-align: center;
    height: 100%;

    &--today {
      border-color: var(--el-color-primary);
    }

    &__weekday {
      font-weight: 600;
      font-size: 14px;
      color: var(--el-text-color-primary);
      text-transform: capitalize;
    }

    &__date {
      margin-top: 2px;
      font-size: 12px;
      color: var(--el-text-color-secondary);
    }

    &__icon {
      width: 64px;
      height: 64px;
      margin: 4px auto;
      display: block;
    }

    &__temps {
      display: flex;
      justify-content: center;
      gap: 10px;
      align-items: baseline;
      margin-bottom: 6px;
    }

    &__max {
      font-size: 20px;
      font-weight: 700;
      color: var(--el-text-color-primary);
    }

    &__min {
      font-size: 14px;
      color: var(--el-text-color-secondary);
    }

    &__desc {
      font-size: 13px;
      color: var(--el-text-color-regular);
      min-height: 36px;
      text-transform: capitalize;
    }

    &__extra {
      margin-top: 8px;
      display: flex;
      justify-content: space-between;
      gap: 8px;
      font-size: 12px;
      color: var(--el-text-color-secondary);
    }
  }

  .source-note {
    margin: 8px 0 0;
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }
}

@media (max-width: 1200px) {
  .thoi-tiet-page .forecast-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .thoi-tiet-page {
    .forecast-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .current-card__temp {
      font-size: 40px;
    }

    .current-card__meta {
      width: 100%;
      justify-content: space-between;
    }
  }
}
</style>
