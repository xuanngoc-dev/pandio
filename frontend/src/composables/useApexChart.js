import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const DEFAULT_COLORS = ['#409eff', '#67c23a', '#e6a23c', '#f56c6c', '#9b59b6', '#36cfc9']

function mergeDeep(target, ...sources) {
  const output = { ...target }
  for (const source of sources) {
    if (!source || typeof source !== 'object') continue
    for (const key of Object.keys(source)) {
      const next = source[key]
      const current = output[key]
      if (
        next &&
        typeof next === 'object' &&
        !Array.isArray(next) &&
        current &&
        typeof current === 'object' &&
        !Array.isArray(current)
      ) {
        output[key] = mergeDeep(current, next)
      } else if (next !== undefined) {
        output[key] = next
      }
    }
  }
  return output
}

/**
 * Theme ApexCharts theo dark/light của html.dark + helper merge options.
 */
export function useApexChart() {
  const isDark = ref(
    typeof document !== 'undefined' && document.documentElement.classList.contains('dark'),
  )

  let observer

  onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark')
    observer = new MutationObserver(() => {
      isDark.value = document.documentElement.classList.contains('dark')
    })
    observer.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['class'],
    })
  })

  onBeforeUnmount(() => observer?.disconnect())

  const chartKey = computed(() => (isDark.value ? 'dark' : 'light'))

  const themeOptions = computed(() => {
    const muted = isDark.value ? '#a3a6ad' : '#909399'
    const text = isDark.value ? '#cfd3dc' : '#303133'
    const grid = isDark.value ? '#414243' : '#ebeef5'

    return {
      colors: DEFAULT_COLORS,
      chart: {
        background: 'transparent',
        fontFamily:
          'Helvetica Neue, Helvetica, PingFang SC, Hiragino Sans GB, Microsoft YaHei, Arial, sans-serif',
        toolbar: { show: false },
        zoom: { enabled: false },
        foreColor: text,
        parentHeightOffset: 0,
        animations: { enabled: true, speed: 500 },
      },
      theme: { mode: isDark.value ? 'dark' : 'light' },
      grid: {
        borderColor: grid,
        strokeDashArray: 4,
        padding: { left: 6, right: 8, top: 8, bottom: 0 },
      },
      legend: {
        position: 'bottom',
        fontSize: '13px',
        fontWeight: 400,
        labels: { colors: text },
        markers: { size: 5 },
      },
      tooltip: { theme: isDark.value ? 'dark' : 'light' },
      xaxis: {
        labels: { style: { colors: muted, fontSize: '12px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
        tooltip: { enabled: false },
      },
      yaxis: {
        labels: { style: { colors: muted, fontSize: '12px' } },
      },
      dataLabels: { enabled: false },
      stroke: { width: 2 },
    }
  })

  function buildOptions(...layers) {
    return mergeDeep(themeOptions.value, ...layers)
  }

  return { isDark, chartKey, themeOptions, buildOptions }
}

export function seriesColors(colors) {
  return Array.isArray(colors) && colors.length ? { colors } : {}
}

export function axisFormatters(yFormatter, tooltipFormatter) {
  const tooltipFn = tooltipFormatter || yFormatter
  const extra = {}
  if (typeof yFormatter === 'function') {
    extra.yaxis = { labels: { formatter: yFormatter } }
  }
  if (typeof tooltipFn === 'function') {
    extra.tooltip = { y: { formatter: tooltipFn } }
  }
  return extra
}

function formatDataLabel(val, formatter) {
  if (val == null || val === '' || Number.isNaN(Number(val))) return ''
  if (Number(val) === 0) return ''
  if (typeof formatter === 'function') return formatter(val)
  return String(val)
}

/** Bật dataLabels, dùng chung formatter với trục Y khi có. */
export function dataLabelLayer(formatter, extra = {}) {
  return mergeDeep(
    {
      dataLabels: {
        enabled: true,
        formatter: (val) => formatDataLabel(val, formatter),
        style: {
          fontSize: '11px',
          fontWeight: 600,
        },
        dropShadow: { enabled: false },
      },
    },
    extra,
  )
}
