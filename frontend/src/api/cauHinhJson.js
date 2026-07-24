import api from '@/api/axios'

/**
 * Lấy cấu hình JSON động hiện tại.
 */
export function getCauHinhJson() {
  return api.get('/cau-hinh-json')
}

/**
 * Cập nhật cấu hình JSON động.
 * @param {{ thong_tin_cau_hinh: Record<string, unknown> }} payload
 */
export function updateCauHinhJson(payload) {
  return api.put('/cau-hinh-json', payload)
}
