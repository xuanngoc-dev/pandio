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
        path: 'nhan-su/luong-tong-hop',
        name: 'luong-tong-hop',
        component: () => import('@/views/nhan-su/LuongTongHop.vue'),
        meta: { title: 'Lương tổng hợp', requiresAuth: true },
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
        path: 'he-thong/cau-hinh-quan-tri/thong-tin-studio',
        name: 'cau-hinh-thong-tin-studio',
        component: () => import('@/views/he-thong/cau-hinh/ThongTinStudio.vue'),
        meta: { title: 'Thông tin studio', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/chi-nhanh',
        name: 'cau-hinh-chi-nhanh',
        component: () => import('@/views/he-thong/cau-hinh/ChiNhanh.vue'),
        meta: { title: 'Chi nhánh', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/gio-lam-viec-ngay-nghi',
        name: 'cau-hinh-gio-lam-viec',
        component: () => import('@/views/he-thong/cau-hinh/GioLamViecNgayNghi.vue'),
        meta: { title: 'Kỳ nghỉ & ngày lễ', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/hop-dong-dat-coc',
        name: 'cau-hinh-hop-dong-dat-coc',
        component: () => import('@/views/he-thong/cau-hinh/HopDongDatCoc.vue'),
        meta: { title: 'Hợp đồng & Đặt cọc', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/tuy-chinh-truong-hop-dong',
        name: 'cau-hinh-tuy-chinh-truong-hop-dong',
        component: () => import('@/views/he-thong/cau-hinh/TuyChinhTruongHopDong.vue'),
        meta: { title: 'Tuỳ chỉnh trường theo loại hợp đồng', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/mau-in',
        name: 'cau-hinh-mau-in',
        component: () => import('@/views/he-thong/cau-hinh/MauIn.vue'),
        meta: { title: 'Mẫu in', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/tuy-chinh-mau-in',
        name: 'cau-hinh-tuy-chinh-mau-in',
        component: () => import('@/views/he-thong/cau-hinh/TuyChinhMauIn.vue'),
        meta: { title: 'Tuỳ chỉnh mẫu in', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/ma-giam-gia',
        name: 'cau-hinh-ma-giam-gia',
        component: () => import('@/views/he-thong/cau-hinh/MaGiamGia.vue'),
        meta: { title: 'Cấu hình mã giảm giá', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/dieu-phoi-san-xuat',
        name: 'cau-hinh-dieu-phoi-san-xuat',
        component: () => import('@/views/he-thong/cau-hinh/DieuPhoiSanXuat.vue'),
        meta: { title: 'Điều phối & Sản xuất', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/danh-muc-dich-vu',
        name: 'cau-hinh-danh-muc-dich-vu',
        component: () => import('@/views/he-thong/cau-hinh/DanhMucDichVu.vue'),
        meta: { title: 'Danh mục dịch vụ', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/cham-cong-tang-ca',
        name: 'cau-hinh-cham-cong-tang-ca',
        component: () => import('@/views/he-thong/cau-hinh/ChamCongTangCa.vue'),
        meta: { title: 'Chấm công & Tăng ca', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/luong-hoa-hong',
        name: 'cau-hinh-luong-hoa-hong',
        component: () => import('@/views/he-thong/cau-hinh/LuongHoaHong.vue'),
        meta: { title: 'Lương & Hoa hồng', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/vai-tro',
        name: 'cau-hinh-vai-tro',
        component: () => import('@/views/he-thong/cau-hinh/VaiTro.vue'),
        meta: { title: 'Vai trò (chức danh nhân sự)', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/phong-ban',
        name: 'cau-hinh-phong-ban',
        component: () => import('@/views/he-thong/cau-hinh/PhongBan.vue'),
        meta: { title: 'Phòng ban', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/ca-lam-viec',
        name: 'cau-hinh-ca-lam-viec',
        component: () => import('@/views/he-thong/cau-hinh/CaLamViec.vue'),
        meta: { title: 'Ca làm việc', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/muc-tieu-kpi',
        name: 'cau-hinh-muc-tieu-kpi',
        component: () => import('@/views/he-thong/cau-hinh/MucTieuKpi.vue'),
        meta: { title: 'Mục tiêu KPI', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/form-danh-gia',
        name: 'cau-hinh-form-danh-gia',
        component: () => import('@/views/he-thong/cau-hinh/FormDanhGia.vue'),
        meta: { title: 'Form đánh giá', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/nguon-khach',
        name: 'cau-hinh-nguon-khach',
        component: () => import('@/views/he-thong/cau-hinh/NguonKhach.vue'),
        meta: { title: 'Nguồn khách', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/ke-toan-tai-chinh',
        name: 'cau-hinh-ke-toan-tai-chinh',
        component: () => import('@/views/he-thong/cau-hinh/KeToanTaiChinh.vue'),
        meta: { title: 'Thuế · Hạng mục chi phí · Biên LN gộp', requiresAuth: true },
      },
      {
        path: 'he-thong/cau-hinh-quan-tri/ip-diem-danh',
        name: 'cau-hinh-ip-diem-danh',
        component: () => import('@/views/he-thong/cau-hinh/IpDiemDanh.vue'),
        meta: { title: 'IP điểm danh', requiresAuth: true },
      },
      {
        path: 'he-thong/trung-tam-phe-duyet',
        name: 'trung-tam-phe-duyet',
        component: () => import('@/views/he-thong/TrungTamPheDuyet.vue'),
        meta: { title: 'Trung tâm Phê duyệt', requiresAuth: true },
      },

      // Đào tạo
      {
        path: 'he-thong/dao-tao',
        redirect: { name: 'dao-tao-hoc-vien' },
      },
      {
        path: 'he-thong/dao-tao/hoc-vien',
        name: 'dao-tao-hoc-vien',
        component: () => import('@/views/he-thong/dao-tao/QuanLyHocVien.vue'),
        meta: { title: 'Quản lý học viên', requiresAuth: true },
      },
      {
        path: 'he-thong/dao-tao/lich-dao-tao',
        name: 'dao-tao-lich-dao-tao',
        component: () => import('@/views/he-thong/dao-tao/LichDaoTao.vue'),
        meta: { title: 'Lịch đào tạo', requiresAuth: true },
      },
      {
        path: 'he-thong/dao-tao/khoa-hoc',
        name: 'dao-tao-khoa-hoc',
        component: () => import('@/views/he-thong/dao-tao/KhoaHoc.vue'),
        meta: { title: 'Khóa học', requiresAuth: true },
      },
      {
        path: 'he-thong/dao-tao/diem-danh',
        name: 'dao-tao-diem-danh',
        component: () => import('@/views/he-thong/dao-tao/DiemDanhDaoTao.vue'),
        meta: { title: 'Điểm danh đào tạo', requiresAuth: true },
      },
      {
        path: 'he-thong/dao-tao/bao-cao',
        name: 'dao-tao-bao-cao',
        component: () => import('@/views/he-thong/dao-tao/BaoCaoDaoTao.vue'),
        meta: { title: 'Báo cáo đào tạo', requiresAuth: true },
      },

      {
        path: 'he-thong/thong-bao',
        name: 'thong-bao',
        component: () => import('@/views/he-thong/ThongBao.vue'),
        meta: { title: 'Thông báo', requiresAuth: true },
      },
      {
        path: 'he-thong/thoi-tiet',
        name: 'thoi-tiet',
        component: () => import('@/views/he-thong/ThoiTiet.vue'),
        meta: { title: 'Thời tiết', requiresAuth: true },
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
    path: '/danh-gia/:slug',
    name: 'danh-gia-khach',
    component: () => import('@/views/khach-hang/DanhGiaKhachHang.vue'),
    meta: { title: 'Đánh giá dịch vụ', public: true },
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
 * - public: không cần đăng nhập (form đánh giá khách hàng)
 * - requiresAuth: bắt buộc đã login
 * - guest: chỉ dành cho khách (đã login thì chuyển Tổng quan)
 */
router.beforeEach(async (to, from, next) => {
  document.title = `${to.meta.title || 'Pandio'} | Pandio`

  if (to.meta.public) {
    return next()
  }

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
