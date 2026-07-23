<template>
  <div class="department-list">
    <el-card shadow="hover">
      <template #header>
        <div class="card-header">
          <span>Phòng ban</span>
          <el-button type="primary" @click="openCreate">
            <el-icon><Plus /></el-icon>
            Thêm phòng ban
          </el-button>
        </div>
      </template>

      <el-table :data="departments" stripe style="width: 100%">
        <el-table-column prop="ma" label="Mã" width="100" />
        <el-table-column prop="ten" label="Tên phòng ban" min-width="180" />
        <el-table-column prop="chi_nhanh" label="Chi nhánh" min-width="180" />
        <el-table-column prop="truong_phong" label="Trưởng phòng" min-width="160" />
        <el-table-column prop="so_nhan_su" label="Số nhân sự" width="120" align="center" />
        <el-table-column label="Thao tác" width="140" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="openEdit(row)">Sửa</el-button>
            <el-button type="danger" link @click="remove(row)">Xóa</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog
      v-model="dialogVisible"
      :title="editingId ? 'Sửa phòng ban' : 'Thêm phòng ban'"
      width="480px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="120px">
        <el-form-item label="Mã" prop="ma">
          <el-input v-model="form.ma" :disabled="!!editingId" />
        </el-form-item>
        <el-form-item label="Tên" prop="ten">
          <el-input v-model="form.ten" />
        </el-form-item>
        <el-form-item label="Chi nhánh" prop="chi_nhanh">
          <el-select v-model="form.chi_nhanh" style="width: 100%">
            <el-option label="Sora Rooftop – Bạch Mai" value="Sora Rooftop – Bạch Mai" />
            <el-option label="Biệt Thự Colonia – Tân Mai" value="Biệt Thự Colonia – Tân Mai" />
          </el-select>
        </el-form-item>
        <el-form-item label="Trưởng phòng" prop="truong_phong">
          <el-input v-model="form.truong_phong" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">Hủy</el-button>
        <el-button type="primary" @click="save">Lưu</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'

const departments = ref([
  {
    id: 1,
    ma: 'PB01',
    ten: 'Sale',
    chi_nhanh: 'Sora Rooftop – Bạch Mai',
    truong_phong: 'Trần Thị Bình',
    so_nhan_su: 8,
  },
  {
    id: 2,
    ma: 'PB02',
    ten: 'Sản xuất',
    chi_nhanh: 'Sora Rooftop – Bạch Mai',
    truong_phong: 'Lê Minh Cường',
    so_nhan_su: 10,
  },
  {
    id: 3,
    ma: 'PB03',
    ten: 'Tài chính-NS',
    chi_nhanh: 'Sora Rooftop – Bạch Mai',
    truong_phong: 'Nguyễn Văn An',
    so_nhan_su: 4,
  },
  {
    id: 4,
    ma: 'PB04',
    ten: 'Trang phục',
    chi_nhanh: 'Biệt Thự Colonia – Tân Mai',
    truong_phong: 'Phạm Thu Dung',
    so_nhan_su: 5,
  },
  {
    id: 5,
    ma: 'PB05',
    ten: 'Marketing',
    chi_nhanh: 'Sora Rooftop – Bạch Mai',
    truong_phong: '—',
    so_nhan_su: 2,
  },
  {
    id: 6,
    ma: 'PB06',
    ten: 'Kho',
    chi_nhanh: 'Biệt Thự Colonia – Tân Mai',
    truong_phong: '—',
    so_nhan_su: 2,
  },
])

const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)

const emptyForm = () => ({
  ma: '',
  ten: '',
  chi_nhanh: '',
  truong_phong: '',
})

const form = reactive(emptyForm())

const rules = {
  ma: [{ required: true, message: 'Vui lòng nhập mã', trigger: 'blur' }],
  ten: [{ required: true, message: 'Vui lòng nhập tên phòng ban', trigger: 'blur' }],
  chi_nhanh: [{ required: true, message: 'Vui lòng chọn chi nhánh', trigger: 'change' }],
}

function openCreate() {
  editingId.value = null
  Object.assign(form, emptyForm())
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  Object.assign(form, {
    ma: row.ma,
    ten: row.ten,
    chi_nhanh: row.chi_nhanh,
    truong_phong: row.truong_phong,
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  if (editingId.value) {
    const idx = departments.value.findIndex((d) => d.id === editingId.value)
    if (idx !== -1) {
      departments.value[idx] = {
        ...departments.value[idx],
        ten: form.ten,
        chi_nhanh: form.chi_nhanh,
        truong_phong: form.truong_phong || '—',
      }
    }
    ElMessage.success('Đã cập nhật phòng ban.')
  } else {
    if (departments.value.some((d) => d.ma === form.ma)) {
      ElMessage.error('Mã phòng ban đã tồn tại.')
      return
    }
    const nextId = Math.max(0, ...departments.value.map((d) => d.id)) + 1
    departments.value.push({
      id: nextId,
      ma: form.ma,
      ten: form.ten,
      chi_nhanh: form.chi_nhanh,
      truong_phong: form.truong_phong || '—',
      so_nhan_su: 0,
    })
    ElMessage.success('Đã thêm phòng ban.')
  }
  dialogVisible.value = false
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa phòng ban "${row.ten}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })
  departments.value = departments.value.filter((d) => d.id !== row.id)
  ElMessage.success('Đã xóa phòng ban.')
}
</script>

<style scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
