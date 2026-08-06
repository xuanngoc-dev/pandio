/**
 * Origin của API (bỏ /api), dùng để ghép URL file public storage.
 */
export function apiOrigin() {
  const base = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'
  return String(base).replace(/\/api\/?$/, '')
}

/**
 * Đường dẫn tương đối trong storage → URL xem được.
 * VD: nhan-vien/abc.jpg → https://api.pandio.vn/storage/nhan-vien/abc.jpg
 *
 * Nếu backend trả URL tuyệt đối (có thể sai domain do APP_URL),
 * vẫn tách /storage/... rồi ghép lại với origin từ VITE_API_BASE_URL.
 */
export function mediaUrl(path) {
  if (!path) return ''
  if (/^(blob:|data:)/i.test(path)) return path

  let cleaned = String(path).trim()

  // Absolute / protocol-relative URL → lấy phần sau /storage/
  if (/^(https?:)?\/\//i.test(cleaned)) {
    const match = cleaned.match(/\/storage\/(.+)$/i)
    if (!match) return cleaned // URL ngoài (CDN, v.v.) giữ nguyên
    cleaned = match[1]
  } else {
    cleaned = cleaned.replace(/^\/?(storage\/)?/, '')
  }

  if (!cleaned) return ''
  return `${apiOrigin()}/storage/${cleaned}`
}
