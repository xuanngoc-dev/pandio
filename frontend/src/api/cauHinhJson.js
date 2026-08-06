import api from '@/api/axios'

/**
 * Lấy cấu hình JSON động hiện tại.
 * @param {{ skipLoading?: boolean }} [config]
 */
export function getCauHinhJson(config = {}) {
  return api.get('/cau-hinh-json', config)
}

/**
 * Cập nhật cấu hình JSON động.
 * @param {{ thong_tin_cau_hinh: Record<string, unknown> }} payload
 * @param {{ skipLoading?: boolean }} [config]
 */
export function updateCauHinhJson(payload, config = {}) {
  return api.put('/cau-hinh-json', payload, config)
}
