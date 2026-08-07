import axios from 'axios'

const OWM_BASE = 'https://api.openweathermap.org'
const apiKey = () => import.meta.env.VITE_OPENWEATHERMAP_API_KEY || ''

const owm = axios.create({
  baseURL: OWM_BASE,
  timeout: 15000,
})

/**
 * Geocode tên thành phố → lat/lon
 */
export async function geocodeCity(city) {
  const { data } = await owm.get('/geo/1.0/direct', {
    params: {
      q: city,
      limit: 1,
      appid: apiKey(),
    },
  })
  if (!Array.isArray(data) || !data.length) {
    throw new Error(`Không tìm thấy địa điểm: ${city}`)
  }
  const place = data[0]
  return {
    name: place.local_names?.vi || place.name,
    country: place.country,
    state: place.state || '',
    lat: place.lat,
    lon: place.lon,
  }
}

/**
 * One Call 3.0 — dự báo ngày (tối đa 8 ngày).
 * Cần đăng ký gói "One Call by Call" (1000 gọi/ngày miễn phí).
 */
export async function fetchOneCallDaily(lat, lon) {
  const { data } = await owm.get('/data/3.0/onecall', {
    params: {
      lat,
      lon,
      exclude: 'minutely,hourly,alerts',
      units: 'metric',
      lang: 'vi',
      appid: apiKey(),
    },
  })
  return data
}

/**
 * Forecast 5 ngày / 3 giờ (gói Free) — dùng làm fallback.
 */
export async function fetchForecast5Day(lat, lon) {
  const { data } = await owm.get('/data/2.5/forecast', {
    params: {
      lat,
      lon,
      units: 'metric',
      lang: 'vi',
      appid: apiKey(),
    },
  })
  return data
}

/**
 * Thời tiết hiện tại (gói Free).
 */
export async function fetchCurrentWeather(lat, lon) {
  const { data } = await owm.get('/data/2.5/weather', {
    params: {
      lat,
      lon,
      units: 'metric',
      lang: 'vi',
      appid: apiKey(),
    },
  })
  return data
}

function iconUrl(code) {
  return code ? `https://openweathermap.org/img/wn/${code}@2x.png` : ''
}

function dayKeyFromUnix(dt, offsetSec = 0) {
  const d = new Date((dt + offsetSec) * 1000)
  return d.toISOString().slice(0, 10)
}

/**
 * Chuẩn hoá dữ liệu One Call → { current, days[] }
 */
export function normalizeOneCall(data, place) {
  const timezoneOffset = data.timezone_offset || 0
  const current = data.current
    ? {
        temp: Math.round(current.temp),
        feelsLike: Math.round(current.feels_like),
        humidity: current.humidity,
        windSpeed: current.wind_speed,
        description: current.weather?.[0]?.description || '',
        icon: iconUrl(current.weather?.[0]?.icon),
        dt: current.dt,
      }
    : null

  const days = (data.daily || []).slice(0, 7).map((d) => ({
    dateKey: dayKeyFromUnix(d.dt, timezoneOffset),
    dt: d.dt,
    tempMin: Math.round(d.temp.min),
    tempMax: Math.round(d.temp.max),
    tempDay: Math.round(d.temp.day),
    humidity: d.humidity,
    windSpeed: d.wind_speed,
    pop: Math.round((d.pop || 0) * 100),
    description: d.weather?.[0]?.description || '',
    icon: iconUrl(d.weather?.[0]?.icon),
  }))

  return { place, current, days, source: 'onecall' }
}

/**
 * Gom forecast 3h thành các ngày (tối đa 7 nếu API trả đủ).
 */
export function normalizeForecast5Day(forecast, current, place) {
  const timezoneOffset = forecast.city?.timezone || 0
  const buckets = new Map()

  for (const item of forecast.list || []) {
    const key = dayKeyFromUnix(item.dt, timezoneOffset)
    if (!buckets.has(key)) {
      buckets.set(key, {
        dateKey: key,
        dt: item.dt,
        temps: [],
        humidity: [],
        wind: [],
        pops: [],
        weatherSlots: [],
      })
    }
    const b = buckets.get(key)
    b.temps.push(item.main.temp)
    b.humidity.push(item.main.humidity)
    b.wind.push(item.wind.speed)
    b.pops.push(item.pop || 0)
    b.weatherSlots.push({
      hour: new Date((item.dt + timezoneOffset) * 1000).getUTCHours(),
      description: item.weather?.[0]?.description || '',
      icon: item.weather?.[0]?.icon || '',
    })
  }

  const days = [...buckets.values()].slice(0, 7).map((b) => {
    const noonish =
      b.weatherSlots.find((s) => s.hour >= 11 && s.hour <= 14) ||
      b.weatherSlots[Math.floor(b.weatherSlots.length / 2)] ||
      b.weatherSlots[0]

    return {
      dateKey: b.dateKey,
      dt: b.dt,
      tempMin: Math.round(Math.min(...b.temps)),
      tempMax: Math.round(Math.max(...b.temps)),
      tempDay: Math.round(b.temps.reduce((a, n) => a + n, 0) / b.temps.length),
      humidity: Math.round(b.humidity.reduce((a, n) => a + n, 0) / b.humidity.length),
      windSpeed: Number(
        (b.wind.reduce((a, n) => a + n, 0) / b.wind.length).toFixed(1),
      ),
      pop: Math.round(Math.max(...b.pops) * 100),
      description: noonish?.description || '',
      icon: iconUrl(noonish?.icon),
    }
  })

  const normalizedCurrent = current
    ? {
        temp: Math.round(current.main.temp),
        feelsLike: Math.round(current.main.feels_like),
        humidity: current.main.humidity,
        windSpeed: current.wind?.speed ?? 0,
        description: current.weather?.[0]?.description || '',
        icon: iconUrl(current.weather?.[0]?.icon),
        dt: current.dt,
      }
    : days[0]
      ? {
          temp: days[0].tempDay,
          feelsLike: days[0].tempDay,
          humidity: days[0].humidity,
          windSpeed: days[0].windSpeed,
          description: days[0].description,
          icon: days[0].icon,
          dt: days[0].dt,
        }
      : null

  return { place, current: normalizedCurrent, days, source: 'forecast5' }
}

/**
 * Lấy thời tiết: ưu tiên One Call 3.0 (7–8 ngày), fallback Free 5 ngày.
 */
export async function fetchWeatherByCity(city) {
  if (!apiKey()) {
    throw new Error('Chưa cấu hình VITE_OPENWEATHERMAP_API_KEY trong .env')
  }

  const place = await geocodeCity(city)

  try {
    const oneCall = await fetchOneCallDaily(place.lat, place.lon)
    return normalizeOneCall(oneCall, place)
  } catch (err) {
    const status = err?.response?.status
    // 401 One Call chưa subscribe / key chưa kích hoạt — thử Free API
    if (status === 401 || status === 403) {
      const [forecast, current] = await Promise.all([
        fetchForecast5Day(place.lat, place.lon),
        fetchCurrentWeather(place.lat, place.lon),
      ])
      return normalizeForecast5Day(forecast, current, place)
    }
    throw err
  }
}
