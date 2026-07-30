import { computed, ref } from 'vue'

/**
 * Selection + đếm theo trạng thái cho bảng multi-select.
 *
 * @param {(row: object) => boolean} [canSelect] — mặc định chọn được tất cả
 * @param {(row: object) => string|null|undefined} [getStatus] — lấy field trạng thái
 */
export function useBulkSelection(canSelect = () => true, getStatus = null) {
  const selectedRows = ref([])

  const selectedCount = computed(() => selectedRows.value.length)

  const selectedIds = computed(() =>
    selectedRows.value.map((row) => row.id).filter((id) => id != null),
  )

  function onSelectionChange(rows) {
    selectedRows.value = (rows || []).filter((row) => canSelect(row))
  }

  function clearSelection() {
    selectedRows.value = []
  }

  function countByStatus(status) {
    if (!getStatus) return 0
    return selectedRows.value.filter((row) => getStatus(row) === status).length
  }

  function idsByStatus(status) {
    if (!getStatus) return []
    return selectedRows.value
      .filter((row) => getStatus(row) === status)
      .map((row) => row.id)
      .filter((id) => id != null)
  }

  return {
    selectedRows,
    selectedCount,
    selectedIds,
    onSelectionChange,
    clearSelection,
    countByStatus,
    idsByStatus,
    canSelect,
  }
}

/**
 * Chạy hàng loạt trên danh sách id (tuần tự, dừng khi lỗi nếu stopOnError).
 * @param {Array<number|string>} ids
 * @param {(id: number|string) => Promise<unknown>} worker
 */
export async function runBulk(ids, worker) {
  let ok = 0
  for (const id of ids) {
    await worker(id)
    ok += 1
  }
  return ok
}
