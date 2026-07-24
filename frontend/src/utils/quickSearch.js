import menuGroups from '@/data/menu.json'

/** Chuẩn hóa chuỗi để so khớp tìm kiếm (bỏ dấu, lowercase). */
export function normalizeSearchText(text) {
  return String(text || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
}

/** Kiểm tra keyword có khớp với bất kỳ field nào. */
export function matchesKeyword(keyword, ...fields) {
  const norm = normalizeSearchText(keyword)
  if (!norm) return true
  return fields.some((field) => normalizeSearchText(field).includes(norm))
}

/** Làm phẳng menu (kể cả submenu) thành danh sách chức năng. */
export function flattenMenuItems(groups = menuGroups) {
  const items = []

  for (const group of groups) {
    const groupName = group.header || ''

    for (const item of group.items || []) {
      if (item.children?.length) {
        for (const child of item.children) {
          items.push({
            title: child.title,
            path: child.index,
            icon: child.icon || item.icon,
            group: groupName,
            parent: item.title,
          })
        }
      } else {
        items.push({
          title: item.title,
          path: item.index,
          icon: item.icon,
          group: groupName,
        })
      }
    }
  }

  return items
}

/** Lọc chức năng theo từ khóa. */
export function searchFunctions(keyword, items = flattenMenuItems()) {
  if (!normalizeSearchText(keyword)) {
    return items.slice(0, 12)
  }

  return items.filter((item) =>
    matchesKeyword(keyword, item.title, item.group, item.parent)
  )
}
