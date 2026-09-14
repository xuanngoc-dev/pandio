import api from '@/api/axios'

const EXCEL_TIMEOUT = 60000

/**
 * Tải file Excel dữ liệu theo loại.
 * @param {string} loai
 */
export function exportExcel(loai) {
  return api.get('/excel/export', {
    params: { loai },
    responseType: 'blob',
    timeout: EXCEL_TIMEOUT,
  })
}

/**
 * Tải file Excel mẫu theo loại.
 * @param {string} loai
 */
export function downloadExcelTemplate(loai) {
  return api.get('/excel/template', {
    params: { loai },
    responseType: 'blob',
    timeout: EXCEL_TIMEOUT,
  })
}

/**
 * Kiểm tra file có đúng hàng tiêu đề như file mẫu không.
 * @param {string} loai
 * @param {File} file
 */
export function validateExcelTemplate(loai, file) {
  const formData = new FormData()
  formData.append('loai', loai)
  formData.append('file', file)
  return api.post('/excel/validate-template', formData, {
    skipLoading: true,
    timeout: EXCEL_TIMEOUT,
  })
}

/**
 * Nhập Excel theo mảng JSON đã xem trước.
 * @param {string} loai
 * @param {Array<object>} items
 */
export function importExcel(loai, items) {
  return api.post(
    '/excel/import',
    { loai, items },
    { timeout: EXCEL_TIMEOUT }
  )
}

/**
 * Lấy tên file từ header Content-Disposition.
 * @param {string|undefined} header
 * @param {string} fallback
 */
export function filenameFromDisposition(header, fallback) {
  if (!header) return fallback
  const utf8 = /filename\*=UTF-8''([^;]+)/i.exec(header)
  if (utf8?.[1]) {
    try {
      return decodeURIComponent(utf8[1])
    } catch {
      return utf8[1]
    }
  }
  const plain = /filename="?([^";]+)"?/i.exec(header)
  return plain?.[1] || fallback
}

/**
 * Tải blob xuống máy.
 * @param {Blob} blob
 * @param {string} filename
 */
export function saveBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}
