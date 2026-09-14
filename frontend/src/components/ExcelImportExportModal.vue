<template>
  <CustomDialog
    v-model="visible"
    :title="dialogTitle"
    :width="1160"
    class="excel-import-export-dialog"
    @closed="onClosed"
  >
    <div class="excel-modal">
      <el-tabs v-model="activeTab" class="excel-tabs">
        <el-tab-pane label="Xuất Excel" name="xuat">
          <section class="excel-panel">
            <p class="excel-panel__desc">Tải toàn bộ dữ liệu hiện có ra file Excel (.xlsx).</p>
            <CustomButton type="primary" :loading="exporting" @click="onExport">
              Tải file Excel
            </CustomButton>
          </section>
        </el-tab-pane>

        <el-tab-pane label="Nhập Excel" name="nhap">

          <div class="excel-panel__toolbar">
            <p class="excel-panel__desc">
              {{ importDesc }}
            </p>
            <CustomButton plain type="primary" :loading="downloadingTemplate" @click="onDownloadTemplate">
              Tải file mẫu
            </CustomButton>
          </div>
          <section class="excel-panel">
            <el-upload
              :key="uploadKey"
              v-loading="validating"
              class="excel-uploader"
              drag
              :auto-upload="false"
              :limit="1"
              accept=".xlsx,.xls,.csv"
              :disabled="validating"
              :file-list="fileList"
              :on-change="onFileChange"
              :on-exceed="onExceed"
              :on-remove="onFileRemove"
            >
              <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
              <div class="el-upload__text">
                Kéo file vào đây hoặc <em>bấm để chọn</em>
              </div>
              <template #tip>
                <div class="el-upload__tip">
                  Chấp nhận .xlsx, .xls, .csv — tối đa 5MB. Hàng tiêu đề phải giống file mẫu.
                </div>
              </template>
            </el-upload>


            <div v-if="previewItems.length" class="excel-preview">
              <div class="excel-preview__title">
                Dữ liệu sẽ nhập ({{ previewItems.length }} dòng)
              </div>
              <CustomTable
                :data="previewItems"
                stripe
                size="small"
                max-height="280"
                style="width: 100%"
              >
                <CustomTableColumn
                  v-for="col in previewColumns"
                  :key="col.key"
                  :prop="col.key"
                  :label="col.label"
                  :width="col.key === 'hang' ? 70 : undefined"
                  :min-width="col.key === 'hang' ? undefined : 140"
                  :align="col.key === 'hang' ? 'center' : 'left'"
                  show-overflow-tooltip
                />
              </CustomTable>
            </div>

            <CustomButton
              type="success"
              :loading="importing || validating"
              :disabled="!canImport"
              @click="onImport"
            >
              Nhập dữ liệu
            </CustomButton>
          </section>
        </el-tab-pane>
      </el-tabs>
    </div>

    <template #footer>
      <CustomButton @click="visible = false">Đóng</CustomButton>
    </template>
  </CustomDialog>

  <CustomDialog
    v-model="resultVisible"
    :title="resultTitle"
    :width="760"
    class="excel-import-result-dialog"
  >
    <el-tabs v-model="resultTab" class="excel-tabs">
      <el-tab-pane :label="`Thành công (${thanhCong.length})`" name="thanh_cong">
        <CustomTable
          :data="thanhCong"
          stripe
          size="small"
          max-height="360"
          style="width: 100%"
        >
          <CustomTableColumn prop="hang" label="Hàng" width="70" align="center" />
          <CustomTableColumn
            v-for="col in resultColumns"
            :key="col.key"
            :prop="col.key"
            :label="col.label"
            :min-width="col.minWidth || 140"
            show-overflow-tooltip
          >
            <template #default="{ row }">
              {{ row[col.key] || '—' }}
            </template>
          </CustomTableColumn>
        </CustomTable>
      </el-tab-pane>
      <el-tab-pane :label="`Thất bại (${thatBai.length})`" name="that_bai">
        <CustomTable
          :data="thatBaiRows"
          stripe
          size="small"
          max-height="360"
          style="width: 100%"
        >
          <CustomTableColumn prop="hang" label="Hàng" width="70" align="center" />
          <CustomTableColumn v-if="loaiConfig?.maKey" prop="ma" label="Mã" min-width="120">
            <template #default="{ row }">
              {{ row.ma || '—' }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="ten" label="Tên" min-width="140">
            <template #default="{ row }">
              {{ row.ten || '—' }}
            </template>
          </CustomTableColumn>
          <CustomTableColumn prop="mo_ta" label="Mô tả" min-width="220" show-overflow-tooltip />
        </CustomTable>
      </el-tab-pane>
    </el-tabs>
    <template #footer>
      <CustomButton type="primary" @click="resultVisible = false">Đóng</CustomButton>
    </template>
  </CustomDialog>
</template>

<script setup>
import { computed, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { UploadFilled } from '@element-plus/icons-vue'
import {
  downloadExcelTemplate,
  exportExcel,
  filenameFromDisposition,
  importExcel,
  saveBlob,
  validateExcelTemplate,
} from '@/api/excel'
import {
  CustomButton,
  CustomDialog,
  CustomTable,
  CustomTableColumn,
} from '@/components/element'

const LOAI_CONFIG = {
  danh_muc_trang_phuc: {
    label: 'Danh mục trang phục',
    maKey: 'ma_danh_muc',
    tenKey: 'ten_danh_muc',
    resultColumns: [
      { key: 'ma_danh_muc', label: 'Mã', minWidth: 120 },
      { key: 'ten_danh_muc', label: 'Tên', minWidth: 160 },
      { key: 'mo_ta', label: 'Mô tả', minWidth: 180 },
    ],
  },
  nha_cung_cap_trang_phuc: {
    label: 'Nhà cung cấp trang phục',
    maKey: 'ma_nha_cung_cap',
    tenKey: 'ten_nha_cung_cap',
    resultColumns: [
      { key: 'ma_nha_cung_cap', label: 'Mã', minWidth: 120 },
      { key: 'ten_nha_cung_cap', label: 'Tên', minWidth: 160 },
      { key: 'so_dien_thoai', label: 'SĐT', minWidth: 120 },
      { key: 'email', label: 'Email', minWidth: 160 },
    ],
  },
  danh_muc_concept: {
    label: 'Danh mục concept',
    tenKey: 'ten_danh_muc',
    duplicateHint: 'Tên danh mục đã tồn tại sẽ không được thêm.',
    resultColumns: [
      { key: 'ten_danh_muc', label: 'Tên', minWidth: 160 },
      { key: 'mo_ta', label: 'Mô tả', minWidth: 180 },
    ],
  },
  concept: {
    label: 'Concept',
    maKey: 'ma_concept',
    tenKey: 'ten_concept',
    importHint:
      'Nhập từ file Excel. Cột "ID danh mục" điền id của danh mục concept — xem sheet "Danh sách danh mục". Cột "Trạng thái" điền dang_su_dung (Đang sử dụng) hoặc ngung_su_dung (Ngừng sử dụng). Cột "Hình ảnh" điền tên file (vd: concept-studio.jpg).',
    resultColumns: [
      { key: 'ma_concept', label: 'Mã', minWidth: 120 },
      { key: 'ten_concept', label: 'Tên', minWidth: 160 },
      { key: 'hinh_anh', label: 'Hình ảnh', minWidth: 140 },
      { key: 'loai_concept', label: 'ID DM', minWidth: 90 },
      { key: 'ten_danh_muc', label: 'Danh mục', minWidth: 140 },
    ],
  },
  trang_phuc: {
    label: 'Trang phục',
    maKey: 'ma_san_pham',
    tenKey: 'ten_san_pham',
    importHint:
      'Nhập từ file Excel. Cột "Trạng thái" điền 1 (Hoạt động) hoặc 0 (Ngừng hoạt động) — xem sheet "Trạng thái". Cột "Hình ảnh" điền tên file (vd: ao-cuoi.jpg). File mẫu còn sheet danh mục và nhà cung cấp.',
    resultColumns: [
      { key: 'ma_san_pham', label: 'Mã SP', minWidth: 120 },
      { key: 'ten_san_pham', label: 'Tên', minWidth: 160 },
      { key: 'hinh_anh', label: 'Hình ảnh', minWidth: 140 },
      { key: 'ma_danh_muc', label: 'Mã DM', minWidth: 120 },
      { key: 'ma_nha_cung_cap', label: 'Mã NCC', minWidth: 120 },
    ],
  },
}

const visible = defineModel({ type: Boolean, default: false })

const props = defineProps({
  loaiDuLieu: {
    type: String,
    required: true,
  },
  tenLoai: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['imported'])

const activeTab = ref('xuat')
const exporting = ref(false)
const downloadingTemplate = ref(false)
const importing = ref(false)
const validating = ref(false)
const selectedFile = ref(null)
const fileList = ref([])
const uploadKey = ref(0)
const previewItems = ref([])
const previewColumns = ref([])
const result = ref(null)
const resultVisible = ref(false)
const resultTab = ref('thanh_cong')

const loaiConfig = computed(() => LOAI_CONFIG[props.loaiDuLieu] || null)

const resolvedTenLoai = computed(
  () => props.tenLoai || loaiConfig.value?.label || props.loaiDuLieu
)

const resultColumns = computed(() => loaiConfig.value?.resultColumns || [])

const importDesc = computed(
  () =>
    loaiConfig.value?.importHint ||
    'Nhập dữ liệu từ file Excel (.xlsx, .xls, .csv) vào hệ thống.'
)

const duplicateHint = computed(
  () => loaiConfig.value?.duplicateHint || 'Mã đã tồn tại sẽ không được thêm.'
)

const dialogTitle = computed(() => `Xuất / Nhập Excel + ${resolvedTenLoai.value}`)
const resultTitle = computed(() => `Kết quả nhập Excel + ${resolvedTenLoai.value}`)

const canImport = computed(
  () => !!selectedFile.value && previewItems.value.length > 0 && !validating.value
)

const thanhCong = computed(() => result.value?.thanh_cong || [])
const thatBai = computed(() => result.value?.that_bai || [])

const thatBaiRows = computed(() => {
  const maKey = loaiConfig.value?.maKey
  const tenKey = loaiConfig.value?.tenKey

  return thatBai.value.map((item) => ({
    hang: item.hang,
    mo_ta: item.mo_ta,
    ma: (maKey && (item.du_lieu?.[maKey] || item[maKey])) || '',
    ten: (tenKey && (item.du_lieu?.[tenKey] || item[tenKey])) || '',
  }))
})

function isAllowedExcel(file) {
  const name = (file?.name || '').toLowerCase()
  return name.endsWith('.xlsx') || name.endsWith('.xls') || name.endsWith('.csv')
}

function clearSelectedFile() {
  selectedFile.value = null
  fileList.value = []
  previewItems.value = []
  previewColumns.value = []
  uploadKey.value += 1
}

async function onFileChange(uploadFile) {
  const raw = uploadFile?.raw
  if (!raw) {
    clearSelectedFile()
    return
  }

  if (!isAllowedExcel(raw)) {
    ElMessage.error('Chỉ chấp nhận file .xlsx, .xls hoặc .csv.')
    clearSelectedFile()
    return
  }

  validating.value = true
  try {
    const { data } = await validateExcelTemplate(props.loaiDuLieu, raw)
    if (!data.hop_le) {
      ElMessage.error(
        data.message || 'File không đúng định dạng file mẫu. Vui lòng tải file mẫu và chọn lại.'
      )
      clearSelectedFile()
      return
    }

    const items = data.items || []
    if (!items.length) {
      ElMessage.warning('File đúng mẫu nhưng không có dữ liệu để nhập.')
      clearSelectedFile()
      return
    }

    selectedFile.value = raw
    fileList.value = [
      {
        name: raw.name,
        status: 'success',
        uid: uploadFile.uid,
      },
    ]
    previewItems.value = items
    previewColumns.value = data.cot || []
  } catch {
    clearSelectedFile()
  } finally {
    validating.value = false
  }
}

function onExceed() {
  ElMessage.warning('Chỉ chọn 1 file. Hãy xóa file hiện tại trước.')
}

function onFileRemove() {
  clearSelectedFile()
}

async function downloadFromResponse(response, fallbackName) {
  const blob = response.data
  if (blob instanceof Blob && (blob.type || '').includes('application/json')) {
    const text = await blob.text()
    let message = 'Không thể tải file Excel.'
    try {
      message = JSON.parse(text).message || message
    } catch {
      // giữ message mặc định
    }
    ElMessage.error(message)
    throw new Error(message)
  }
  const filename = filenameFromDisposition(
    response.headers['content-disposition'],
    fallbackName
  )
  saveBlob(blob, filename)
}

async function onExport() {
  exporting.value = true
  try {
    const response = await exportExcel(props.loaiDuLieu)
    await downloadFromResponse(response, `${props.loaiDuLieu}.xlsx`)
    ElMessage.success('Đã tải file Excel.')
  } catch {
    // interceptor / downloadFromResponse đã thông báo
  } finally {
    exporting.value = false
  }
}

async function onDownloadTemplate() {
  downloadingTemplate.value = true
  try {
    const response = await downloadExcelTemplate(props.loaiDuLieu)
    await downloadFromResponse(response, `${props.loaiDuLieu}-mau.xlsx`)
    ElMessage.success('Đã tải file mẫu.')
  } catch {
    // interceptor
  } finally {
    downloadingTemplate.value = false
  }
}

async function onImport() {
  if (!canImport.value) {
    ElMessage.warning('Vui lòng chọn file Excel hợp lệ.')
    return
  }

  const tong = previewItems.value.length
  try {
    await ElMessageBox.confirm(
      `Nhập ${tong} dòng ${resolvedTenLoai.value}? ${duplicateHint.value}`,
      'Xác nhận nhập Excel',
      {
        type: 'warning',
        confirmButtonText: 'Nhập',
        cancelButtonText: 'Hủy',
      }
    )
  } catch {
    return
  }

  importing.value = true
  result.value = null
  try {
    const { data } = await importExcel(props.loaiDuLieu, previewItems.value)
    result.value = data
    const soThanhCong = (data.thanh_cong || []).length
    const soThatBai = (data.that_bai || []).length
    const tongKet = soThanhCong + soThatBai || tong
    const message = data.message || `Import thành công ${soThanhCong}/${tongKet} bản ghi.`

    resultTab.value = soThanhCong > 0 || soThatBai === 0 ? 'thanh_cong' : 'that_bai'
    resultVisible.value = true

    if (soThanhCong > 0) {
      emit('imported', data)
    }

    if (soThanhCong === tongKet) {
      ElMessage.success(message)
    } else if (soThanhCong > 0) {
      ElMessage.warning(message)
    } else {
      ElMessage.error(message)
    }
  } catch {
    // interceptor
  } finally {
    importing.value = false
  }
}

function onClosed() {
  activeTab.value = 'xuat'
  exporting.value = false
  downloadingTemplate.value = false
  importing.value = false
  validating.value = false
  result.value = null
  resultVisible.value = false
  resultTab.value = 'thanh_cong'
  clearSelectedFile()
}
</script>

<style scoped lang="scss">
.excel-modal {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.excel-tabs {
  width: 100%;

  :deep(.el-tabs__header) {
    margin-bottom: 12px;
  }

  :deep(.el-tabs__content) {
    overflow: visible;
  }
}

.excel-panel {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 10px;
  width: 100%;
  padding-top: 12px
}

.excel-panel__toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  width: 100%;
}

.excel-panel__desc {
  margin: 0;
  flex: 1;
  min-width: 0;
  font-size: 13px;
  line-height: 1.5;
  color: var(--el-text-color-secondary);
}

.excel-uploader {
  width: 100%;

  :deep(.el-upload) {
    width: 100%;
  }

  :deep(.el-upload-dragger) {
    width: 100%;
    padding: 20px 16px;
    border-width: 2px;
    border-color: var(--el-border-color);
  }

  :deep(.el-upload-dragger:hover) {
    border-color: var(--el-color-primary);
  }
}

.excel-preview {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.excel-preview__title {
  font-size: 13px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}
</style>
