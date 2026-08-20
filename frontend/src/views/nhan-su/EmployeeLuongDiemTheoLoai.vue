<template>
  <div class="luong-diem-theo-loai">
    <p v-if="!loaiList.length" class="luong-diem-empty">
      Chưa có loại quay chụp đang hoạt động.
    </p>
    <div v-else class="luong-diem-table-wrap">
      <table class="luong-diem-table">
        <thead>
          <tr>
            <th rowspan="2" class="col-ten">Tên dịch vụ</th>
            <th
              v-for="role in roles"
              :key="role.key"
              class="col-role"
              :colspan="levels.length"
            >
              {{ role.label }}
            </th>
          </tr>
          <tr>
            <template v-for="role in roles" :key="`${role.key}-levels`">
              <th
                v-for="level in levels"
                :key="`${role.key}-${level}`"
                class="col-diem"
              >
                {{ level }} điểm
              </th>
            </template>
          </tr>
        </thead>
        <tbody>
          <tr v-for="loai in loaiList" :key="loai.id">
            <td class="col-ten">
              <div class="loai-name">{{ loai.ten_dich_vu }}</div>
              <div v-if="loai.ghi_chu" class="loai-note">{{ loai.ghi_chu }}</div>
            </td>
            <template v-for="role in roles" :key="`${loai.id}-${role.key}`">
              <td
                v-for="level in levels"
                :key="`${loai.id}-${role.key}-${level}`"
                class="col-diem"
              >
                <MoneyInput
                  v-if="canEdit && items[idKey(loai.id)]"
                  v-model="items[idKey(loai.id)][role.key][level]"
                  style="width: 100%"
                />
                <CustomInput
                  v-else
                  :model-value="formatMoney(dichVuRate(luong, loai.id, role.key, level))"
                  readonly
                />
              </td>
            </template>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { CustomInput, MoneyInput } from '@/components/element'
import {
  LUONG_DICH_VU_ROLES,
  LUONG_THEO_DICH_VU_KEY,
  SALARY_DIEM_LEVELS,
  dichVuRate,
  ensureLuongTheoDichVu,
  formatMoney,
} from './employeeSalaryFields'

const props = defineProps({
  luong: { type: Object, required: true },
  loaiList: { type: Array, default: () => [] },
  readonly: { type: Boolean, default: false },
})

const roles = LUONG_DICH_VU_ROLES
const levels = SALARY_DIEM_LEVELS

const items = computed(() => props.luong?.[LUONG_THEO_DICH_VU_KEY]?.items || {})
const canEdit = computed(() => !props.readonly)

watch(
  () => [props.readonly, props.luong, props.loaiList],
  () => {
    if (!props.readonly && props.luong) {
      ensureLuongTheoDichVu(props.luong, props.loaiList)
    }
  },
  { immediate: true },
)

function idKey(id) {
  return String(id)
}
</script>

<style scoped>
.luong-diem-theo-loai {
  margin-top: 4px;
}

.luong-diem-empty {
  margin: 0;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.luong-diem-table-wrap {
  overflow-x: auto;
}

.luong-diem-table {
  width: 100%;
  min-width: 980px;
  border-collapse: collapse;
}

.luong-diem-table th,
.luong-diem-table td {
  border: 1px solid var(--el-border-color-lighter);
  padding: 8px;
  text-align: left;
  vertical-align: middle;
}

.luong-diem-table th {
  font-size: 12px;
  font-weight: 600;
  color: var(--el-text-color-regular);
  background: var(--el-fill-color-light);
}

.luong-diem-table .col-ten {
  min-width: 160px;
  width: 18%;
}

.luong-diem-table .col-role {
  text-align: center;
}

.luong-diem-table .col-diem {
  min-width: 110px;
  text-align: center;
}

.loai-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  line-height: 1.35;
}

.loai-note {
  margin-top: 2px;
  font-size: 11px;
  color: var(--el-text-color-secondary);
  line-height: 1.3;
}

.luong-diem-table :deep(.el-input__wrapper) {
  width: 100%;
}

.luong-diem-table td :deep(.el-input__wrapper) {
  background-color: var(--el-fill-color-blank);
}

.luong-diem-table td :deep(.el-input__inner) {
  font-size: 13px;
  font-weight: 600;
  text-align: left;
}
</style>
