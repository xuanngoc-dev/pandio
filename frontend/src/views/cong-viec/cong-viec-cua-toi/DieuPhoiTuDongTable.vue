<template>
  <div class="dieu-phoi-table">
    <CustomTable :data="items" stripe border style="width: 100%">
      <CustomTableColumn label="STT" width="60" align="center">
        <template #default="{ $index, row }">
          {{ rowStt($index, row) }}
        </template>
      </CustomTableColumn>

      <CustomTableColumn label="Loại HĐ" min-width="160" show-overflow-tooltip>
        <template #default="{ row }">
          {{ row.loai_hop_dong?.ten_hop_dong || '—' }}
        </template>
      </CustomTableColumn>

      <CustomTableColumn label="Khách hàng" min-width="180" show-overflow-tooltip>
        <template #default="{ row }">
          {{ formatDieuPhoiKhachHang(row) }}
        </template>
      </CustomTableColumn>

      <CustomTableColumn label="Mã HĐ" prop="ma_hop_dong" min-width="120" show-overflow-tooltip />

      <CustomTableColumn
        :label="step === 'hoan_tat_san_xuat' ? 'Thời gian hoàn thành sản xuất' : 'Thời gian chụp'"
        min-width="200"
        show-overflow-tooltip
      >
        <template #default="{ row }">
          <template v-if="step === 'hoan_tat_san_xuat'">
            {{ getThoiGianHoanTatSanXuat(row) || '—' }}
          </template>
          <template v-else-if="buildDieuPhoiThoiGianChupItems(row).length">
            <template
              v-for="(item, index) in buildDieuPhoiThoiGianChupItems(row)"
              :key="index"
            >
              <span v-if="index">; </span>
              <CustomTooltip v-if="item.loaiLabel" :content="item.loaiLabel" placement="top">
                <span>{{ item.text }}</span>
              </CustomTooltip>
              <span v-else>{{ item.text }}</span>
            </template>
          </template>
          <span v-else>—</span>
        </template>
      </CustomTableColumn>

      <CustomTableColumn label="Ngày trả file lẻ" min-width="185">
        <template #default="{ row }">
          <DieuPhoiTuDongTableDateCell
            :row="row"
            field-key="ngay_tra_file_le"
            :status="getDieuPhoiDateDeadlineStatus(row, 'ngay_tra_file_le', hasLinkFileLe(row), 'Trễ giao file lẻ', 'Đúng hạn giao file lẻ')"
            :can-edit="canEditSharedDatesFlag"
            :loading="savingSharedDate && activeRow?.id === row.id && sharedDateField?.key === 'ngay_tra_file_le'"
            @edit="(field) => openSharedDateModal(row, field)"
          />
        </template>
      </CustomTableColumn>

      <CustomTableColumn label="Ngày trả file in" min-width="185">
        <template #default="{ row }">
          <DieuPhoiTuDongTableDateCell
            :row="row"
            field-key="ngay_tra_file_in"
            :status="getDieuPhoiDateDeadlineStatus(row, 'ngay_tra_file_in', hasLinkFileIn(row), 'Trễ giao file in', 'Đúng hạn giao file in')"
            :can-edit="canEditSharedDatesFlag"
            :loading="savingSharedDate && activeRow?.id === row.id && sharedDateField?.key === 'ngay_tra_file_in'"
            @edit="(field) => openSharedDateModal(row, field)"
          />
        </template>
      </CustomTableColumn>

      <CustomTableColumn label="Ngày khách hẹn qua" min-width="185">
        <template #default="{ row }">
          <DieuPhoiTuDongTableDateCell
            :row="row"
            field-key="ngay_khach_hen_qua"
            :can-edit="canEditSharedDatesFlag"
            :loading="savingSharedDate && activeRow?.id === row.id && sharedDateField?.key === 'ngay_khach_hen_qua'"
            @edit="(field) => openSharedDateModal(row, field)"
          />
        </template>
      </CustomTableColumn>

      <CustomTableColumn label="Note thợ shop" min-width="200">
        <template #default="{ row }">
          <div class="dieu-phoi-table__editable-cell">
            <template v-if="noteEditingRowId === row.id">
              <CustomSelect
                v-model="noteDraft"
                clearable
                placeholder="Chọn note"
                size="small"
                class="dieu-phoi-table__note-select"
                :loading="savingNote && noteEditingRowId === row.id"
                @change="(value) => saveNoteThoShop(row, value)"
                @visible-change="onNoteSelectVisibleChange"
              >
                <CustomOption
                  v-for="opt in NOTE_THO_SHOP_OPTIONS"
                  :key="opt.value"
                  :label="opt.label"
                  :value="opt.value"
                />
              </CustomSelect>
            </template>
            <template v-else>
              <el-tag
                v-if="getDieuPhoiNoteThoShopValue(row)"
                size="small"
                effect="plain"
                :type="noteThoShopTagType(getDieuPhoiNoteThoShopValue(row))"
              >
                {{ getDieuPhoiNoteThoShopLabel(row) }}
              </el-tag>
              <span v-else class="dieu-phoi-table__empty">—</span>
              <CustomTooltip
                v-if="canEditNoteThoShopFlag"
                :content="getDieuPhoiNoteThoShopValue(row) ? 'Sửa note thợ shop' : 'Thêm note thợ shop'"
                placement="top"
              >
                <CustomButton
                  :type="getDieuPhoiNoteThoShopValue(row) ? 'warning' : 'primary'"
                  circle
                  size="small"
                  :icon="getDieuPhoiNoteThoShopValue(row) ? Edit : Plus"
                  :loading="savingNote && noteEditingRowId === row.id"
                  @click.stop="openNoteEdit(row)"
                />
              </CustomTooltip>
            </template>
          </div>
        </template>
      </CustomTableColumn>

      <CustomTableColumn
        v-for="field in fileColumns"
        :key="field.key"
        :label="field.label"
        min-width="125"
        align="center"
      >
        <template #default="{ row }">
          <div class="dieu-phoi-table__editable-cell dieu-phoi-table__editable-cell--center">
            <a
              v-if="getFileUrl(row, field.key)"
              class="dieu-phoi-table__link"
              :href="normalizeDieuPhoiUrl(getFileUrl(row, field.key))"
              target="_blank"
              rel="noopener noreferrer"
              @click.stop
            >
              Mở
            </a>
            <span v-else class="dieu-phoi-table__empty">—</span>
            <CustomTooltip
              v-if="canEditDieuPhoiFile(row, step, authStore.user, field.key)"
              :content="getFileUrl(row, field.key) ? 'Sửa link' : 'Thêm link'"
              placement="top"
            >
              <CustomButton
                :type="getFileUrl(row, field.key) ? 'warning' : 'primary'"
                circle
                size="small"
                :icon="getFileUrl(row, field.key) ? Edit : Plus"
                @click.stop="openLinkModal(row, field)"
              />
            </CustomTooltip>
          </div>
        </template>
      </CustomTableColumn>

      <CustomTableColumn label="Vai trò" min-width="165">
        <template #default="{ row }">
          <div v-if="getDieuPhoiVaiTroLabels(row, userId).length" class="dieu-phoi-table__roles">
            <el-tag
              v-for="role in getDieuPhoiVaiTroLabels(row, userId)"
              :key="role"
              type="info"
              size="small"
              effect="plain"
            >
              {{ role }}
            </el-tag>
          </div>
          <span v-else>—</span>
        </template>
      </CustomTableColumn>

      <CustomTableColumn label="Thao tác" width="110" fixed="right" align="center">
        <template #default="{ row }">
          <div class="dieu-phoi-table__actions">
            <CustomTooltip content="Xem chi tiết hợp đồng" placement="top">
              <CustomButton
                type="info"
                circle
                size="small"
                :icon="View"
                @click.stop="openDetail(row)"
              />
            </CustomTooltip>
            <CustomTooltip
              v-if="step === 'tien_ky'"
              :content="
                canChuyenHauKy(row)
                  ? 'Chuyển sang hậu kỳ'
                  : 'Cần có File gốc trước khi chuyển sang hậu kỳ'
              "
              placement="top"
            >
              <span class="dieu-phoi-table__action-wrap">
                <CustomButton
                  type="primary"
                  circle
                  size="small"
                  :icon="Right"
                  :disabled="!canChuyenHauKy(row)"
                  :loading="movingId === row.id"
                  @click.stop="onChuyenHauKy(row)"
                />
              </span>
            </CustomTooltip>
            <CustomTooltip
              v-else-if="step === 'hau_ky'"
              :content="
                canChuyenGuiIn(row)
                  ? 'Chuyển sang gửi in'
                  : 'Cần có đủ File lẻ và File in'
              "
              placement="top"
            >
              <span class="dieu-phoi-table__action-wrap">
                <CustomButton
                  type="primary"
                  circle
                  size="small"
                  :icon="Printer"
                  :disabled="!canChuyenGuiIn(row)"
                  :loading="movingId === row.id"
                  @click.stop="onChuyenGuiIn(row)"
                />
              </span>
            </CustomTooltip>
            <CustomTooltip
              v-else-if="step === 'gui_in'"
              content="Hoàn tất sản xuất"
              placement="top"
            >
              <CustomButton
                type="success"
                circle
                size="small"
                :icon="Finished"
                :loading="movingId === row.id"
                @click.stop="onHoanTatSanXuat(row)"
              />
            </CustomTooltip>
          </div>
        </template>
      </CustomTableColumn>
    </CustomTable>

    <CustomDialog v-model="linkModalVisible" :title="linkModalTitle" :width="460">
      <el-form label-position="top" @submit.prevent="saveLink">
        <el-form-item :label="linkModalField?.label || 'Link'" required>
          <el-input
            v-model="linkInput"
            placeholder="https://..."
            clearable
            @keyup.enter="saveLink"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <CustomButton @click="linkModalVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="savingLink" @click="saveLink">
          Lưu
        </CustomButton>
      </template>
    </CustomDialog>

    <CustomDialog v-model="sharedDateModalVisible" :title="sharedDateModalTitle" :width="420">
      <el-form label-position="top" @submit.prevent="saveSharedDate">
        <el-form-item :label="sharedDateField?.label || 'Ngày'">
          <el-date-picker
            v-model="sharedDateInput"
            type="date"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            :placeholder="`Chọn ${(sharedDateField?.label || 'ngày').toLowerCase()}`"
            clearable
            style="width: 100%"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <CustomButton @click="sharedDateModalVisible = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="savingSharedDate" @click="saveSharedDate">
          Lưu
        </CustomButton>
      </template>
    </CustomDialog>

    <DieuPhoiTuDongDetailModal v-model="detailVisible" :hop-dong-id="detailId" />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import {
  Edit,
  Finished,
  Plus,
  Printer,
  Right,
  View,
} from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  capNhatKetQuaHopDong,
  capNhatNgayDieuPhoi,
  chuyenGuiInCongViec,
  chuyenHauKyCongViec,
  chuyenHoanTatSanXuatCongViec,
} from '@/api/hopDongSuDungDichVu'
import {
  CustomButton,
  CustomDialog,
  CustomOption,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import { useAuthStore } from '@/stores/auth'
import {
  buildDieuPhoiThoiGianChupItems,
  canChuyenGuiIn,
  canChuyenHauKy,
  canEditDieuPhoiFile,
  canEditNoteThoShop,
  canEditSharedDates,
  formatDieuPhoiKhachHang,
  getDieuPhoiDateDeadlineStatus,
  getDieuPhoiFileLinks,
  getDieuPhoiNoteThoShopLabel,
  getDieuPhoiNoteThoShopValue,
  getDieuPhoiVaiTroLabels,
  getThoiGianHoanTatSanXuat,
  normalizeDieuPhoiUrl,
} from '@/utils/dieuPhoiTuDongDisplay'
import {
  NOTE_THO_SHOP_KEY,
  NOTE_THO_SHOP_OPTIONS,
  normalizeNoteThoShopValue,
  noteThoShopTagType,
} from '@/utils/thongTinDieuPhoi'
import DieuPhoiTuDongDetailModal from './DieuPhoiTuDongDetailModal.vue'
import DieuPhoiTuDongTableDateCell from './DieuPhoiTuDongTableDateCell.vue'

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  step: {
    type: String,
    default: 'hau_ky',
  },
  page: {
    type: Number,
    default: 1,
  },
  perPage: {
    type: Number,
    default: 24,
  },
})

const emit = defineEmits(['status-changed', 'updated'])

function rowStt(index, row) {
  const page = Number(props.page)
  const size = Number(props.perPage)
  let i = Number(index)
  if (!Number.isFinite(i) && row) {
    i = props.items.findIndex((item) => item === row || item?.id === row?.id)
  }
  const safePage = Number.isFinite(page) && page > 0 ? page : 1
  const safeSize = Number.isFinite(size) && size > 0 ? size : 24
  const safeIndex = Number.isFinite(i) && i >= 0 ? i : 0
  return (safePage - 1) * safeSize + safeIndex + 1
}

const authStore = useAuthStore()
const userId = computed(() => authStore.user?.id)
const canEditSharedDatesFlag = computed(() => canEditSharedDates(props.step, authStore.user))
const canEditNoteThoShopFlag = computed(() => canEditNoteThoShop(props.step, authStore.user))

const movingId = ref(null)
const detailVisible = ref(false)
const detailId = ref(null)
const activeRow = ref(null)

const linkModalVisible = ref(false)
const linkModalField = ref(null)
const linkInput = ref('')
const savingLink = ref(false)

const sharedDateModalVisible = ref(false)
const sharedDateField = ref(null)
const sharedDateInput = ref('')
const savingSharedDate = ref(false)

const noteEditingRowId = ref(null)
const noteDraft = ref('')
const savingNote = ref(false)

const fileColumns = computed(() =>
  getDieuPhoiFileLinks({}, props.step).map(({ key, label }) => ({ key, label })),
)

const linkModalTitle = computed(() => {
  if (!linkModalField.value) return 'Thêm link'
  const label = linkModalField.value.label.toLowerCase()
  return linkModalField.value.url ? `Sửa ${label}` : `Thêm ${label}`
})

const sharedDateModalTitle = computed(() => {
  const field = sharedDateField.value
  if (!field) return 'Cập nhật ngày'
  const label = (field.label || 'ngày').toLowerCase()
  return field.iso ? `Sửa ${label}` : `Thêm ${label}`
})

function getFileUrl(row, key) {
  return getDieuPhoiFileLinks(row, props.step).find((item) => item.key === key)?.url || null
}

function hasLinkFileLe(row) {
  return !!getFileUrl(row, 'link_file_le')
}

function hasLinkFileIn(row) {
  return !!getFileUrl(row, 'link_file_in')
}

function openDetail(row) {
  if (!row?.id) return
  detailId.value = row.id
  detailVisible.value = true
}

function openSharedDateModal(row, field) {
  if (!canEditSharedDatesFlag.value || !field) return
  activeRow.value = row
  sharedDateField.value = field
  sharedDateInput.value = field.iso || ''
  sharedDateModalVisible.value = true
}

async function saveSharedDate() {
  const row = activeRow.value
  const field = sharedDateField.value
  if (!canEditSharedDatesFlag.value || !row?.id || !field) {
    ElMessage.error('Bạn không có quyền cập nhật ngày này.')
    return
  }

  const next = String(sharedDateInput.value || '').slice(0, 10)
  if (next === (field.iso || '')) {
    sharedDateModalVisible.value = false
    return
  }

  savingSharedDate.value = true
  try {
    const { data } = await capNhatNgayDieuPhoi(row.id, {
      key: field.key,
      gia_tri: next || null,
    })
    ElMessage.success(`Đã lưu ${field.label.toLowerCase()}`)
    sharedDateModalVisible.value = false
    emit('updated', data)
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      `Không thể lưu ${field.label.toLowerCase()}`
    ElMessage.error(msg)
  } finally {
    savingSharedDate.value = false
  }
}

function openNoteEdit(row) {
  if (!canEditNoteThoShopFlag.value || !row?.id) return
  noteEditingRowId.value = row.id
  noteDraft.value = getDieuPhoiNoteThoShopValue(row) || null
}

function onNoteSelectVisibleChange(visible) {
  if (visible || savingNote.value) return
  noteEditingRowId.value = null
  noteDraft.value = ''
}

async function saveNoteThoShop(row, value) {
  if (!canEditNoteThoShopFlag.value || !row?.id) {
    ElMessage.error('Bạn không có quyền cập nhật note thợ shop.')
    noteEditingRowId.value = null
    return
  }

  const next = normalizeNoteThoShopValue(value)
  if (next === getDieuPhoiNoteThoShopValue(row)) {
    noteEditingRowId.value = null
    return
  }

  savingNote.value = true
  try {
    const { data } = await capNhatNgayDieuPhoi(row.id, {
      key: NOTE_THO_SHOP_KEY,
      gia_tri: next || null,
    })
    ElMessage.success('Đã lưu note thợ shop')
    noteEditingRowId.value = null
    emit('updated', data)
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      'Không thể lưu note thợ shop'
    ElMessage.error(msg)
  } finally {
    savingNote.value = false
  }
}

function openLinkModal(row, fieldDef) {
  const field = getDieuPhoiFileLinks(row, props.step).find((item) => item.key === fieldDef.key)
  if (!field || !canEditDieuPhoiFile(row, props.step, authStore.user, field.key)) return
  activeRow.value = row
  linkModalField.value = field
  linkInput.value = field.url || ''
  linkModalVisible.value = true
}

async function saveLink() {
  const row = activeRow.value
  const field = linkModalField.value
  if (!row?.id || !field) return
  if (!canEditDieuPhoiFile(row, props.step, authStore.user, field.key)) {
    ElMessage.error('Bạn không có quyền cập nhật file này.')
    return
  }

  const value = String(linkInput.value || '').trim()
  if (!value) {
    ElMessage.warning('Vui lòng nhập link')
    return
  }

  savingLink.value = true
  try {
    const { data } = await capNhatKetQuaHopDong(row.id, {
      key: field.key,
      gia_tri: value,
    })
    ElMessage.success('Đã lưu link')
    linkModalVisible.value = false
    emit('updated', data)
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      'Không thể lưu link'
    ElMessage.error(msg)
  } finally {
    savingLink.value = false
  }
}

async function onChuyenHauKy(row) {
  if (!row?.id || !canChuyenHauKy(row)) return

  try {
    await ElMessageBox.confirm(
      `Chuyển hợp đồng ${row.ma_hop_dong || ''} sang hậu kỳ?`,
      'Chuyển sang hậu kỳ',
      {
        type: 'info',
        confirmButtonText: 'Chuyển',
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  movingId.value = row.id
  try {
    await chuyenHauKyCongViec(row.id)
    ElMessage.success('Đã chuyển sang hậu kỳ')
    emit('status-changed')
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      'Không thể chuyển sang hậu kỳ'
    ElMessage.error(msg)
  } finally {
    movingId.value = null
  }
}

async function onChuyenGuiIn(row) {
  if (!row?.id || !canChuyenGuiIn(row)) return

  try {
    await ElMessageBox.confirm(
      `Chuyển hợp đồng ${row.ma_hop_dong || ''} sang gửi in?`,
      'Chuyển sang gửi in',
      {
        type: 'info',
        confirmButtonText: 'Chuyển',
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  movingId.value = row.id
  try {
    await chuyenGuiInCongViec(row.id)
    ElMessage.success('Đã chuyển sang gửi in')
    emit('status-changed')
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      'Không thể chuyển sang gửi in'
    ElMessage.error(msg)
  } finally {
    movingId.value = null
  }
}

async function onHoanTatSanXuat(row) {
  if (!row?.id) return

  try {
    await ElMessageBox.confirm(
      `Hoàn tất sản xuất hợp đồng ${row.ma_hop_dong || ''}?`,
      'Hoàn tất sản xuất',
      {
        type: 'info',
        confirmButtonText: 'Đồng ý',
        cancelButtonText: 'Hủy',
      },
    )
  } catch {
    return
  }

  movingId.value = row.id
  try {
    await chuyenHoanTatSanXuatCongViec(row.id)
    ElMessage.success('Đã chuyển sang hoàn tất sản xuất')
    emit('status-changed')
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.message ||
      'Không thể hoàn tất sản xuất'
    ElMessage.error(msg)
  } finally {
    movingId.value = null
  }
}
</script>

<style scoped lang="scss">
.dieu-phoi-table {
  &__editable-cell {
    display: inline-flex;
    align-items: center;
    justify-content: flex-start;
    gap: 6px;
    min-width: 0;

    &--center {
      justify-content: center;
    }
  }

  &__note-select {
    width: 150px;
  }

  &__empty {
    color: var(--el-text-color-secondary);
  }

  &__link {
    color: var(--el-color-primary);
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }

  &__roles {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
  }

  &__actions {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }

  &__action-wrap {
    display: inline-flex;
  }
}
</style>
