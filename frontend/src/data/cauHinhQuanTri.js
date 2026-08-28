/**
 * Danh mục cấu hình quản trị — dùng cho lưới card và đăng ký route.
 * Tabs phân quyền được lấy tự động từ menu.json (field `tabs`).
 */
import menuGroups from '@/data/menu.json'

export const cauHinhSections = [
  {
    key: 'studio',
    title: 'Studio',
    icon: 'Box',
    items: [
      {
        label: 'Thông tin studio',
        routeName: 'cau-hinh-thong-tin-studio',
        path: 'thong-tin-studio',
        component: 'ThongTinStudio',
      },
      {
        label: 'Chi nhánh',
        routeName: 'cau-hinh-chi-nhanh',
        path: 'chi-nhanh',
        component: 'ChiNhanh',
      },
      {
        label: 'Kỳ nghỉ & ngày lễ',
        routeName: 'cau-hinh-gio-lam-viec',
        path: 'gio-lam-viec-ngay-nghi',
        component: 'GioLamViecNgayNghi',
      },
    ],
  },
  {
    key: 'hop-dong',
    title: 'Hợp đồng',
    icon: 'Document',
    items: [
      {
        label: 'Hợp đồng & Đặt cọc',
        routeName: 'cau-hinh-hop-dong-dat-coc',
        path: 'hop-dong-dat-coc',
        component: 'HopDongDatCoc',
      },
      {
        label: 'Cấu hình thông tin hợp đồng',
        routeName: 'cau-hinh-tuy-chinh-truong-hop-dong',
        path: 'tuy-chinh-truong-hop-dong',
        component: 'TuyChinhTruongHopDong',
      },
      {
        label: 'Mẫu in',
        routeName: 'cau-hinh-mau-in',
        path: 'mau-in',
        component: 'MauIn',
      },
      {
        label: 'Tuỳ chỉnh mẫu in',
        routeName: 'cau-hinh-tuy-chinh-mau-in',
        path: 'tuy-chinh-mau-in',
        component: 'TuyChinhMauIn',
      },
      {
        label: 'Cấu hình mã giảm giá',
        routeName: 'cau-hinh-ma-giam-gia',
        path: 'ma-giam-gia',
        component: 'MaGiamGia',
      },
    ],
  },
  {
    key: 'van-hanh',
    title: 'Vận hành & Sản xuất',
    icon: 'Refresh',
    items: [
      {
        label: 'Điều phối & Sản xuất',
        routeName: 'cau-hinh-dieu-phoi-san-xuat',
        path: 'dieu-phoi-san-xuat',
        component: 'DieuPhoiSanXuat',
      },
      {
        label: 'Danh mục dịch vụ',
        routeName: 'cau-hinh-danh-muc-dich-vu',
        path: 'danh-muc-dich-vu',
        component: 'DanhMucDichVu',
      },
    ],
  },
  {
    key: 'nhan-su',
    title: 'Nhân sự',
    icon: 'User',
    items: [
      {
        label: 'Chấm công & Tăng ca',
        routeName: 'cau-hinh-cham-cong-tang-ca',
        path: 'cham-cong-tang-ca',
        component: 'ChamCongTangCa',
      },
      {
        label: 'Lương & Hoa hồng',
        routeName: 'cau-hinh-luong-hoa-hong',
        path: 'luong-hoa-hong',
        component: 'LuongHoaHong',
      },
      {
        label: 'Vai trò (chức danh nhân sự)',
        routeName: 'cau-hinh-vai-tro',
        path: 'vai-tro',
        component: 'VaiTro',
      },
      {
        label: 'Phòng ban',
        routeName: 'cau-hinh-phong-ban',
        path: 'phong-ban',
        component: 'PhongBan',
      },
      {
        label: 'Ca làm việc',
        routeName: 'cau-hinh-ca-lam-viec',
        path: 'ca-lam-viec',
        component: 'CaLamViec',
      },
    ],
  },
  {
    key: 'khach-hang',
    title: 'Khách hàng',
    icon: 'Star',
    items: [
      {
        label: 'Mục tiêu KPI',
        routeName: 'cau-hinh-muc-tieu-kpi',
        path: 'muc-tieu-kpi',
        component: 'MucTieuKpi',
      },
      {
        label: 'Form đánh giá',
        routeName: 'cau-hinh-form-danh-gia',
        path: 'form-danh-gia',
        component: 'FormDanhGia',
      },
      {
        label: 'Nguồn khách',
        routeName: 'cau-hinh-nguon-khach',
        path: 'nguon-khach',
        component: 'NguonKhach',
      },
    ],
  },
  {
    key: 'ke-toan',
    title: 'Kế toán & Tài chính',
    icon: 'Coin',
    items: [
      {
        label: 'Thuế · Hạng mục chi phí · Biên LN gộp',
        routeName: 'cau-hinh-ke-toan-tai-chinh',
        path: 'ke-toan-tai-chinh',
        component: 'KeToanTaiChinh',
      },
    ],
  },
  {
    key: 'bao-mat',
    title: 'Bảo mật',
    icon: 'Lock',
    items: [
      {
        label: 'IP điểm danh',
        routeName: 'cau-hinh-ip-diem-danh',
        path: 'ip-diem-danh',
        component: 'IpDiemDanh',
      },
    ],
  },
]

/** Flatten tất cả mục cấu hình con (phục vụ router). */
export const cauHinhItems = cauHinhSections.flatMap((section) =>
  section.items.map((item) => ({
    ...item,
    sectionTitle: section.title,
  })),
)

/**
 * Tabs theo menu.json — chỉ item có `tabs` mới đưa vào phân quyền.
 * Key lưu trong vai_tro.cau_hinh: `${menuIndex}#${tab.key}`
 *
 * Thêm tab mới: khai báo trong menu.json → VaiTro tự cập nhật cây "Tabs được phép".
 */
function collectMenuItems(groups) {
  const items = []
  for (const group of groups) {
    for (const item of group.items || []) {
      items.push(item)
      for (const child of item.children || []) {
        items.push(child)
      }
    }
  }
  return items
}

/** @returns {{ menuIndex: string, title: string, tabs: Array<{ key: string, label: string }> }[]} */
export function buildMenuTabConfigs(groups = menuGroups) {
  return collectMenuItems(groups)
    .filter((item) => Array.isArray(item.tabs) && item.tabs.length > 0)
    .map((item) => ({
      menuIndex: item.index,
      title: item.title,
      tabs: item.tabs.map((tab) => ({
        key: tab.key,
        label: tab.label,
      })),
    }))
}

export const menuTabConfigs = buildMenuTabConfigs()

/** Key lưu trong vai_tro.cau_hinh */
export function tabPermissionKey(menuIndex, tabKey) {
  return `${menuIndex}#${tabKey}`
}

/** Tabs của 1 menu (dạng { name, label } cho usePageTabs). */
export function getMenuPageTabs(menuIndex, groups = menuGroups) {
  const item = collectMenuItems(groups).find((m) => m.index === menuIndex)
  if (!item?.tabs?.length) return []
  return item.tabs.map((tab) => ({
    name: tab.key,
    label: tab.label,
  }))
}

/** Toàn bộ leaf id tabs (phục vụ form Vai trò). */
export function getAllMenuTabLeafIds(groups = menuGroups) {
  return buildMenuTabConfigs(groups).flatMap((menu) =>
    (menu.tabs || []).map((tab) => tabPermissionKey(menu.menuIndex, tab.key)),
  )
}

export const allMenuTabLeafIds = getAllMenuTabLeafIds()

/** Tree data cho checkbox phân quyền tabs trong Vai trò. */
export function buildMenuTabTreeData(groups = menuGroups) {
  return buildMenuTabConfigs(groups).map((menu) => ({
    id: `menu:${menu.menuIndex}`,
    label: menu.title,
    children: (menu.tabs || []).map((tab) => ({
      id: tabPermissionKey(menu.menuIndex, tab.key),
      label: tab.label,
    })),
  }))
}
