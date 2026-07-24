import api from '@/api/axios'

const CLIENT_IP_URL = 'https://ipv4.geojs.io/v1/ip.json'

/**
 * Lấy địa chỉ IPv4 public của client từ geojs.io.
 * @returns {Promise<string>}
 */
export async function fetchClientIp() {
  const res = await fetch(CLIENT_IP_URL)
  if (!res.ok) {
    throw new Error('Không thể lấy địa chỉ IP.')
  }
  const data = await res.json()
  if (!data?.ip) {
    throw new Error('Không thể lấy địa chỉ IP.')
  }
  return data.ip
}

/**
 * Danh sách điểm danh — phân trang.
 * @param {{
 *   page?: number,
 *   per_page?: number,
 *   keyword?: string,
 *   user_id?: number|string,
 *   ngay_lam?: string,
 *   tu_ngay?: string,
 *   den_ngay?: string,
 * }} params
 * @param {{ skipLoading?: boolean }} [config]
 */
export function fetchDiemDanh(params = {}, config = {}) {
  return api.get('/diem-danh', { params, ...config })
}

/**
 * Trạng thái điểm danh hôm nay của user đang đăng nhập.
 * @param {{ skipLoading?: boolean }} [config]
 */
export function getDiemDanhToday(config = {}) {
  return api.get('/diem-danh/today', config)
}

/**
 * Checkin điểm danh.
 * @param {{ ip?: string }} payload — ip bắt buộc khi bật kiểm soát IP
 */
export function checkinDiemDanh(payload = {}) {
  return api.post('/diem-danh/checkin', payload)
}

/**
 * Checkout điểm danh.
 * @param {{ ip?: string }} payload — ip bắt buộc khi bật kiểm soát IP
 */
export function checkoutDiemDanh(payload = {}) {
  return api.post('/diem-danh/checkout', payload)
}
