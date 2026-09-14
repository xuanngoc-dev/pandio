import { ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  completeHinhAnhTrangPhucUpload,
  giaiNenHinhAnhTrangPhuc,
  uploadHinhAnhTrangPhucChunk,
} from '@/api/trangPhuc'

export const IMAGE_MAX_BYTES = 5 * 1024 * 1024
export const ZIP_MAX_BYTES = 1024 * 1024 * 1024
export const CHUNK_SIZE = 1024 * 1024
export const IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg']

export function fileExtension(filename) {
  const name = String(filename || '')
  const idx = name.lastIndexOf('.')
  return idx > 0 ? name.slice(idx + 1).toLowerCase() : ''
}

export function isImageName(filename) {
  return IMAGE_EXTS.includes(fileExtension(filename))
}

export function isZipName(filename) {
  return fileExtension(filename) === 'zip'
}

export function useHinhAnhFolderUpload({
  uploadChunk,
  completeUpload,
  extractZip: extractZipApi,
}) {
  const progressVisible = ref(false)
  const progressPercent = ref(0)
  const progressText = ref('')
  const progressBusy = ref(false)

  function setProgress(percent, text) {
    progressPercent.value = Math.max(0, Math.min(100, Math.round(percent)))
    if (text) progressText.value = text
  }

  async function uploadFileInChunks(file, onChunkProgress) {
    const totalChunks = Math.max(1, Math.ceil(file.size / CHUNK_SIZE))
    let uploadId = null

    for (let index = 0; index < totalChunks; index++) {
      const start = index * CHUNK_SIZE
      const blob = file.slice(start, Math.min(start + CHUNK_SIZE, file.size))
      const formData = new FormData()
      if (uploadId) formData.append('upload_id', uploadId)
      formData.append('filename', file.name)
      formData.append('chunk_index', String(index))
      formData.append('total_chunks', String(totalChunks))
      formData.append('total_size', String(file.size))
      formData.append('chunk', blob, `${file.name}.part${index}`)

      const { data } = await uploadChunk(formData)
      uploadId = data.upload_id
      onChunkProgress?.((index + 1) / totalChunks)
    }

    const { data } = await completeUpload({ upload_id: uploadId })
    return data
  }

  async function extractZip(path, onProgress) {
    let cursor = 0
    let extractedTotal = 0
    let skippedTotal = 0
    let total = 0

    do {
      const { data } = await extractZipApi({
        path,
        cursor,
        limit: 40,
      })
      cursor = data.cursor
      total = data.total || 0
      extractedTotal += data.extracted || 0
      skippedTotal += data.skipped || 0
      const ratio = total > 0 ? cursor / total : 1
      onProgress?.(ratio, extractedTotal, total)
      if (data.done) break
    } while (true)

    return { extracted: extractedTotal, skipped: skippedTotal, total }
  }

  function validateSelection(files) {
    const list = [...files]
    if (!list.length) return { error: 'Vui lòng chọn file.' }

    const zips = list.filter((file) => isZipName(file.name))
    const images = list.filter((file) => isImageName(file.name))
    const others = list.filter((file) => !isZipName(file.name) && !isImageName(file.name))

    if (others.length) {
      return { error: 'Chỉ chấp nhận ảnh hoặc 1 file zip.' }
    }

    if (zips.length && images.length) {
      return { error: 'Chọn danh sách ảnh hoặc 1 file zip, không chọn lẫn.' }
    }

    if (zips.length > 1) {
      return { error: 'Chỉ chọn 1 file zip mỗi lần tải lên.' }
    }

    if (zips.length === 1) {
      if (zips[0].size > ZIP_MAX_BYTES) {
        return { error: 'File zip tối đa 1GB.' }
      }
      return { zip: zips[0] }
    }

    const oversized = images.find((file) => file.size > IMAGE_MAX_BYTES)
    if (oversized) {
      return { error: `Ảnh "${oversized.name}" vượt 5MB.` }
    }

    return { images }
  }

  async function confirmImport(parsed) {
    if (parsed.zip) {
      await ElMessageBox.confirm(
        `Tải lên zip "${parsed.zip.name}"?\nZip trùng tên sẽ được đổi thành dạng fileName(1).zip. Giải nén sau từ danh sách nếu cần.`,
        'Xác nhận tải lên',
        {
          type: 'warning',
          confirmButtonText: 'Tải lên',
          cancelButtonText: 'Hủy',
        },
      )
      return
    }

    await ElMessageBox.confirm(
      `Tải lên ${parsed.images.length} ảnh?\nẢnh trùng tên sẽ bị ghi đè.`,
      'Xác nhận tải lên',
      {
        type: 'warning',
        confirmButtonText: 'Tải lên',
        cancelButtonText: 'Hủy',
      },
    )
  }

  async function uploadSelection(files) {
    const parsed = validateSelection(files)
    if (parsed.error) {
      ElMessage.error(parsed.error)
      return null
    }

    try {
      await confirmImport(parsed)
    } catch {
      return null
    }

    progressVisible.value = true
    progressBusy.value = true
    setProgress(0, 'Đang tải lên...')

    try {
      if (parsed.zip) {
        setProgress(0, `Đang tải ${parsed.zip.name}...`)
        const saved = await uploadFileInChunks(parsed.zip, (ratio) => {
          setProgress(ratio * 100, `Đang tải zip... ${Math.round(ratio * 100)}%`)
        })
        setProgress(100, 'Hoàn tất')
        if (saved.renamed) {
          ElMessage.success(`Zip trùng tên, đã lưu thành ${saved.name}. Giải nén khi cần từ danh sách.`)
        } else {
          ElMessage.success(`Đã tải zip ${saved.name}. Giải nén khi cần từ danh sách.`)
        }
        return { type: 'zip', saved }
      }

      const images = parsed.images
      let uploaded = 0
      for (const file of images) {
        setProgress((uploaded / images.length) * 100, `Đang tải ${file.name}...`)
        await uploadFileInChunks(file, (ratio) => {
          const overall = ((uploaded + ratio) / images.length) * 100
          setProgress(overall, `Đang tải ${uploaded + 1}/${images.length}: ${file.name}`)
        })
        uploaded += 1
      }
      setProgress(100, 'Hoàn tất')
      ElMessage.success(`Đã tải lên ${uploaded} ảnh.`)
      return { type: 'images', uploaded }
    } finally {
      progressBusy.value = false
    }
  }

  async function extractExisting(path, name) {
    try {
      await ElMessageBox.confirm(
        `Giải nén "${name || 'file zip'}"?\nẢnh trùng tên sẽ bị ghi đè.`,
        'Xác nhận giải nén',
        {
          type: 'warning',
          confirmButtonText: 'Giải nén',
          cancelButtonText: 'Hủy',
        },
      )
    } catch {
      return null
    }

    progressVisible.value = true
    progressBusy.value = true
    setProgress(0, `Đang giải nén ${name || 'zip'}...`)

    try {
      const result = await extractZip(path, (ratio, extracted, total) => {
        setProgress(ratio * 100, `Đang giải nén ${extracted}/${total} ảnh...`)
      })
      setProgress(100, 'Hoàn tất')
      if (result.total === 0) {
        ElMessage.warning('Zip không chứa hình ảnh hợp lệ.')
      } else {
        ElMessage.success(`Đã giải nén ${result.extracted} ảnh.`)
      }
      return result
    } finally {
      progressBusy.value = false
    }
  }

  return {
    progressVisible,
    progressPercent,
    progressText,
    progressBusy,
    uploadSelection,
    extractExisting,
  }
}

export function useHinhAnhTrangPhucUpload() {
  return useHinhAnhFolderUpload({
    uploadChunk: uploadHinhAnhTrangPhucChunk,
    completeUpload: completeHinhAnhTrangPhucUpload,
    extractZip: giaiNenHinhAnhTrangPhuc,
  })
}
