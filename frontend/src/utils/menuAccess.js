import allMenuGroups from '@/data/menu.json'

/**
 * Lấy danh sách path menu được phân quyền từ user.
 * - admin → null (full access)
 * - còn lại → mảng path từ vai_tro.danh_sach_menu
 *
 * @param {object|null|undefined} user
 * @returns {string[]|null}
 */
export function getAllowedMenuPaths(user) {
  if (!user) return []
  if (user.role === 'admin') return null

  const vaiTro =
    user?.nhan_vien?.vai_tro ||
    user?.nhanVien?.vaiTro ||
    user?.nhan_vien?.vaiTro ||
    user?.nhanVien?.vai_tro ||
    null

  const list = vaiTro?.danh_sach_menu
  if (!Array.isArray(list)) return []

  return list.map((p) => String(p).trim()).filter(Boolean)
}

/**
 * Lọc menu.json theo danh_sach_menu (giữ group/submenu nếu còn item con).
 *
 * @param {Array} menuGroups
 * @param {string[]|null} allowedPaths null = hiện tất cả
 */
export function filterMenuGroups(menuGroups, allowedPaths) {
  if (allowedPaths === null) {
    return menuGroups
  }

  const allowed = new Set(allowedPaths)

  return menuGroups
    .map((group) => {
      const items = (group.items || [])
        .map((item) => {
          if (item.children?.length) {
            const children = item.children.filter((child) => allowed.has(child.index))
            if (!children.length) return null
            return { ...item, children }
          }
          return allowed.has(item.index) ? item : null
        })
        .filter(Boolean)

      if (!items.length) return null
      return { ...group, items }
    })
    .filter(Boolean)
}

/**
 * Path menu đầu tiên user được vào (sau khi lọc).
 */
export function firstAllowedMenuPath(allowedPaths, menuGroups = allMenuGroups) {
  const filtered = filterMenuGroups(menuGroups, allowedPaths)
  for (const group of filtered) {
    for (const item of group.items || []) {
      if (item.children?.length) {
        return item.children[0].index
      }
      return item.index
    }
  }
  return null
}

/**
 * Thu thập mọi path trong menu.json (kể cả parent có children).
 */
export function collectMenuPaths(menuGroups = allMenuGroups) {
  const paths = []
  for (const group of menuGroups) {
    for (const item of group.items || []) {
      paths.push(item.index)
      for (const child of item.children || []) {
        paths.push(child.index)
      }
    }
  }
  return paths
}

/**
 * Kiểm tra path hiện tại có nằm trong phạm vi menu được phép không.
 * Cho phép path con của menu cha (VD: /he-thong/cau-hinh-quan-tri/...).
 */
export function isMenuPathAllowed(path, allowedPaths, menuGroups = allMenuGroups) {
  if (allowedPaths === null) return true

  const normalized = String(path || '').split('?')[0]
  if (!normalized) return true

  // Không phải route menu → không chặn (trang public/khác)
  const menuPaths = collectMenuPaths(menuGroups)
  const isMenuRelated = menuPaths.some(
    (p) => normalized === p || normalized.startsWith(`${p}/`),
  )
  if (!isMenuRelated) return true

  return allowedPaths.some(
    (p) => normalized === p || normalized.startsWith(`${p}/`),
  )
}

export { allMenuGroups }
