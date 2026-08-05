import { computed, onMounted, onUnmounted, ref } from 'vue'

/** Offline nếu ping vượt quá (ms) hoặc lỗi */
const PING_TIMEOUT_MS = 4000
/** Coi là không ổn định nếu RTT ping > ngưỡng này */
const UNSTABLE_RTT_MS = 1200
/** Chu kỳ kiểm tra lại khi đang online */
const POLL_INTERVAL_MS = 15000

/**
 * Theo dõi trạng thái đường truyền: online | unstable | offline
 */
export function useNetworkStatus() {
  const status = ref(navigator.onLine ? 'online' : 'offline')

  let pollTimer = null
  let connection = null
  let checking = false

  const tooltip = computed(() => {
    if (status.value === 'offline') return 'Không có kết nối internet'
    if (status.value === 'unstable') return 'Kết nối mạng không ổn định'
    return 'Kết nối mạng ổn định'
  })

  const toneClass = computed(() => {
    if (status.value === 'offline') return 'is-offline'
    if (status.value === 'unstable') return 'is-unstable'
    return 'is-online'
  })

  function getConnection() {
    return navigator.connection || navigator.mozConnection || navigator.webkitConnection || null
  }

  function inferFromConnectionApi() {
    const conn = getConnection()
    if (!conn) return null

    const type = String(conn.effectiveType || '').toLowerCase()
    if (type === 'slow-2g' || type === '2g') return 'unstable'
    if (typeof conn.rtt === 'number' && conn.rtt > 500) return 'unstable'
    if (typeof conn.downlink === 'number' && conn.downlink > 0 && conn.downlink < 0.5) {
      return 'unstable'
    }
    return 'online'
  }

  async function pingOnce() {
    const controller = new AbortController()
    const timer = window.setTimeout(() => controller.abort(), PING_TIMEOUT_MS)
    const started = performance.now()

    try {
      // HEAD tới origin hiện tại — nhẹ, không phụ thuộc CDN ngoài
      await fetch(`${window.location.origin}/favicon.ico?_=${Date.now()}`, {
        method: 'HEAD',
        cache: 'no-store',
        signal: controller.signal,
      })
      return performance.now() - started
    } catch {
      // Fallback: thử GET nhỏ nếu HEAD bị chặn
      try {
        await fetch(`${window.location.origin}/?_netcheck=${Date.now()}`, {
          method: 'GET',
          cache: 'no-store',
          signal: controller.signal,
          headers: { Range: 'bytes=0-0' },
        })
        return performance.now() - started
      } catch {
        return null
      }
    } finally {
      window.clearTimeout(timer)
    }
  }

  async function refreshStatus() {
    if (checking) return
    checking = true

    try {
      if (!navigator.onLine) {
        status.value = 'offline'
        return
      }

      const rtt = await pingOnce()
      if (rtt == null) {
        status.value = 'offline'
        return
      }

      if (rtt > UNSTABLE_RTT_MS) {
        status.value = 'unstable'
        return
      }

      status.value = inferFromConnectionApi() || 'online'
    } finally {
      checking = false
    }
  }

  function onOnline() {
    status.value = 'unstable'
    refreshStatus()
  }

  function onOffline() {
    status.value = 'offline'
  }

  function onConnectionChange() {
    refreshStatus()
  }

  function startPolling() {
    stopPolling()
    pollTimer = window.setInterval(refreshStatus, POLL_INTERVAL_MS)
  }

  function stopPolling() {
    if (pollTimer != null) {
      window.clearInterval(pollTimer)
      pollTimer = null
    }
  }

  onMounted(() => {
    window.addEventListener('online', onOnline)
    window.addEventListener('offline', onOffline)

    connection = getConnection()
    connection?.addEventListener?.('change', onConnectionChange)

    refreshStatus()
    startPolling()
  })

  onUnmounted(() => {
    window.removeEventListener('online', onOnline)
    window.removeEventListener('offline', onOffline)
    connection?.removeEventListener?.('change', onConnectionChange)
    stopPolling()
  })

  return {
    status,
    tooltip,
    toneClass,
    refreshStatus,
  }
}
