import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        redirect: { name: 'tong-quan' },
      },
      {
        path: 'tong-quan',
        name: 'tong-quan',
        component: () => import('@/views/tong-quan/TongQuan.vue'),
        meta: { title: 'Tổng quan', requiresAuth: true },
      },
      {
        path: 'cong-viec-cua-toi',
        name: 'cong-viec-cua-toi',
        component: () => import('@/views/cong-viec/CongViecCuaToi.vue'),
        meta: { title: 'Công việc của tôi', requiresAuth: true },
      },

      // Vận hành cưới
      {
        path: 'van-hanh-cuoi/hop-dong-sddv',
        name: 'hop-dong-sddv',
        component: () => import('@/views/van-hanh-cuoi/HopDongSddv.vue'),
        meta: { title: 'Hợp đồng SDDV', requiresAuth: true },
      },
      {
        path: 'van-hanh-cuoi/lich-dieu-phoi',
        name: 'lich-dieu-phoi',
        component: () => import('@/views/van-hanh-cuoi/LichDieuPhoi.vue'),
        meta: { title: 'Lịch điều phối', requiresAuth: true },
      },
      {
        path: 'van-hanh-cuoi/lich-khach-hang',
        name: 'lich-khach-hang',
        component: () => import('@/views/van-hanh-cuoi/LichKhachHang.vue'),
        meta: { title: 'Lịch khách hàng', requiresAuth: true },
      },
      {
        path: 'van-hanh-cuoi/trang-phuc',
        name: 'trang-phuc',
        component: () => import('@/views/van-hanh-cuoi/TrangPhuc.vue'),
        meta: { title: 'Trang phục', requiresAuth: true },
      },
      {
        path: 'van-hanh-cuoi/hop-dong-cho-thue',
        name: 'hop-dong-cho-thue',
        component: () => import('@/views/van-hanh-cuoi/HopDongChoThue.vue'),
        meta: { title: 'Hợp đồng cho thuê', requiresAuth: true },
      },
      {
        path: 'van-hanh-cuoi/concept',
        name: 'concept',
        component: () => import('@/views/van-hanh-cuoi/Concept.vue'),
        meta: { title: 'Concept', requiresAuth: true },
      },
      {
        path: 'van-hanh-cuoi/dich-vu',
        name: 'dich-vu',
        component: () => import('@/views/van-hanh-cuoi/DichVu.vue'),
        meta: { title: 'Dịch vụ', requiresAuth: true },
      },

      // Khách hàng
      {
        path: 'khach-hang',
        name: 'khach-hang',
        component: () => import('@/views/khach-hang/KhachHang.vue'),
        meta: { title: 'Khách hàng', requiresAuth: true },
      },
      {
        path: 'khach-hang/note-khach-moi',
        name: 'note-khach-moi',
        component: () => import('@/views/khach-hang/NoteKhachMoi.vue'),
        meta: { title: 'Note khách mới', requiresAuth: true },
      },
      {
        path: 'khach-hang/quang-cao',
        name: 'quang-cao',
        component: () => import('@/views/khach-hang/QuangCao.vue'),
        meta: { title: 'Quảng cáo', requiresAuth: true },
      },
      {
        path: 'khach-hang/xem-danh-gia',
        name: 'xem-danh-gia',
        component: () => import('@/views/khach-hang/XemDanhGia.vue'),
        meta: { title: 'Xem đánh giá', requiresAuth: true },
      },

      // Nhân sự
      {
        path: 'nhan-su/cham-cong-nghi-phep',
        name: 'cham-cong-nghi-phep',
        component: () => import('@/views/nhan-su/ChamCongNghiPhep.vue'),
        meta: { title: 'Chấm công · Nghỉ phép', requiresAuth: true },
      },
      {
        path: 'nhan-su/tinh-luong',
        name: 'tinh-luong',
        component: () => import('@/views/nhan-su/TinhLuong.vue'),
        meta: { title: 'Tính lương', requiresAuth: true },
      },
      {
        path: 'nhan-su/danh-sach',
        name: 'nhan-su-danh-sach',
        component: () => import('@/views/nhan-su/EmployeeList.vue'),
        meta: { title: 'Nhân sự', requiresAuth: true },
      },
      {
        path: 'nhan-su/phong-ban',
        name: 'nhan-su-phong-ban',
        component: () => import('@/views/nhan-su/DepartmentList.vue'),
        meta: { title: 'Phòng ban', requiresAuth: true },
      },

      // Tài chính
      {
        path: 'tai-chinh/ke-toan-thue',
        name: 'ke-toan-thue',
        component: () => import('@/views/tai-chinh/KeToanThue.vue'),
        meta: { title: 'Kế toán & Thuế', requiresAuth: true },
      },

      // Hệ thống
      {
        path: 'he-thong/cau-hinh-quan-tri',
        name: 'cau-hinh-quan-tri',
        component: () => import('@/views/he-thong/CauHinhQuanTri.vue'),
        meta: { title: 'Cấu hình & Quản trị', requiresAuth: true },
      },
      {
        path: 'he-thong/trung-tam-phe-duyet',
        name: 'trung-tam-phe-duyet',
        component: () => import('@/views/he-thong/TrungTamPheDuyet.vue'),
        meta: { title: 'Trung tâm Phê duyệt', requiresAuth: true },
      },

      // Pandio SaaS
      {
        path: 'pandio-saas/kinh-doanh',
        name: 'kinh-doanh-pandio',
        component: () => import('@/views/pandio-saas/KinhDoanhPandio.vue'),
        meta: { title: 'Kinh doanh Pandio', requiresAuth: true },
      },

      // Giữ route cũ (không còn trên menu)
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/views/Dashboard.vue'),
        meta: { title: 'Dashboard', requiresAuth: true },
      },
      {
        path: 'home',
        name: 'home',
        component: () => import('@/views/Home.vue'),
        meta: { title: 'Trang chủ' },
      },
    ],
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/Login.vue'),
    meta: { title: 'Đăng nhập', guest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/Register.vue'),
    meta: { title: 'Đăng ký', guest: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'tong-quan' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

/**
 * Navigation guard:
 * - requiresAuth: bắt buộc đã login
 * - guest: chỉ dành cho khách (đã login thì chuyển Tổng quan)
 */
router.beforeEach(async (to, from, next) => {
  document.title = `${to.meta.title || 'Pandio'} | Pandio`

  const authStore = useAuthStore()

  // Nếu có token nhưng chưa có user → gọi /api/user để khôi phục phiên
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  if (to.meta.guest && authStore.isAuthenticated) {
    return next({ name: 'tong-quan' })
  }

  return next()
})

export default router
