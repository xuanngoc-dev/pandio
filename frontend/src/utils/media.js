/**
 * Origin của API (bỏ /api), dùng để ghép URL file public storage.
 */
export function apiOrigin() {
  const base = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'
  return String(base).replace(/\/api\/?$/, '')
}

/**
 * Đường dẫn tương đối trong storage → URL xem được.
 * VD: nhan-vien/abc.jpg → http://127.0.0.1:8000/storage/nhan-vien/abc.jpg
 */
export function mediaUrl(path) {
  if (!path) return ''
  if (/^(https?:|blob:|data:)/i.test(path)) return path
  const cleaned = String(path).replace(/^\/?(storage\/)?/, '')
  return `${apiOrigin()}/storage/${cleaned}`
}
