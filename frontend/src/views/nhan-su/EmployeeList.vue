<template>
  <div class="employee-list">
    <el-card shadow="hover">
      <template #header>
        <div class="card-header">
          <span>Danh sách nhân sự</span>
          <el-button type="primary" @click="openCreate">
            <el-icon><Plus /></el-icon>
            Thêm nhân sự
          </el-button>
        </div>
      </template>

      <div class="toolbar">
        <el-input
          v-model="keyword"
          placeholder="Tìm theo tên, email, SĐT..."
          clearable
          style="max-width: 280px"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select v-model="statusFilter" placeholder="Trạng thái" clearable style="width: 180px">
          <el-option label="Đang làm việc" value="dang_lam" />
          <el-option label="Đã nghỉ việc" value="da_nghi" />
          <el-option label="Hạn chế quyền" value="han_che" />
        </el-select>
      </div>

      <el-table :data="filteredEmployees" stripe style="width: 100%">
        <el-table-column prop="ma" label="Mã" width="90" />
        <el-table-column prop="ho_ten" label="Họ tên" min-width="160" />
        <el-table-column prop="email" label="Email" min-width="180" />
        <el-table-column prop="sdt" label="SĐT" width="130" />
        <el-table-column prop="phong_ban" label="Phòng ban" min-width="140" />
        <el-table-column prop="loai_nhan_vien" label="Loại NV" width="120" />
        <el-table-column prop="trang_thai" label="Trạng thái" width="140">
          <template #default="{ row }">
            <el-tag :type="statusType(row.trang_thai)" size="small">
              {{ statusLabel(row.trang_thai) }}
            </el-tag>
          </template>
        </el-table-column>
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
      :title="editingId ? 'Sửa nhân sự' : 'Thêm nhân sự'"
      width="520px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="120px">
        <el-form-item label="Họ tên" prop="ho_ten">
          <el-input v-model="form.ho_ten" />
        </el-form-item>
        <el-form-item label="Email" prop="email">
          <el-input v-model="form.email" />
        </el-form-item>
        <el-form-item label="SĐT" prop="sdt">
          <el-input v-model="form.sdt" />
        </el-form-item>
        <el-form-item label="Phòng ban" prop="phong_ban">
          <el-select v-model="form.phong_ban" style="width: 100%">
            <el-option v-for="d in departments" :key="d" :label="d" :value="d" />
          </el-select>
        </el-form-item>
        <el-form-item label="Loại NV" prop="loai_nhan_vien">
          <el-select v-model="form.loai_nhan_vien" style="width: 100%">
            <el-option label="Full-time" value="Full-time" />
            <el-option label="Part-time" value="Part-time" />
          </el-select>
        </el-form-item>
        <el-form-item label="Trạng thái" prop="trang_thai">
          <el-select v-model="form.trang_thai" style="width: 100%">
            <el-option label="Đang làm việc" value="dang_lam" />
            <el-option label="Đã nghỉ việc" value="da_nghi" />
            <el-option label="Hạn chế quyền" value="han_che" />
          </el-select>
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
import { computed, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Search } from '@element-plus/icons-vue'

const departments = ['Sale', 'Sản xuất', 'Tài chính-NS', 'Trang phục', 'Marketing', 'Kho']

const employees = ref([
  {
    id: 1,
    ma: 'NV001',
    ho_ten: 'Nguyễn Văn An',
    email: 'an.nguyen@sorabridal.vn',
    sdt: '0901234567',
    phong_ban: 'Tài chính-NS',
    loai_nhan_vien: 'Full-time',
    trang_thai: 'dang_lam',
  },
  {
    id: 2,
    ma: 'NV002',
    ho_ten: 'Trần Thị Bình',
    email: 'binh.tran@sorabridal.vn',
    sdt: '0912345678',
    phong_ban: 'Sale',
    loai_nhan_vien: 'Full-time',
    trang_thai: 'dang_lam',
  },
  {
    id: 3,
    ma: 'NV003',
    ho_ten: 'Lê Minh Cường',
    email: 'cuong.le@sorabridal.vn',
    sdt: '0923456789',
    phong_ban: 'Sản xuất',
    loai_nhan_vien: 'Full-time',
    trang_thai: 'dang_lam',
  },
  {
    id: 4,
    ma: 'NV004',
    ho_ten: 'Phạm Thu Dung',
    email: 'dung.pham@sorabridal.vn',
    sdt: '0934567890',
    phong_ban: 'Trang phục',
    loai_nhan_vien: 'Part-time',
    trang_thai: 'han_che',
  },
])

const keyword = ref('')
const statusFilter = ref('')
const dialogVisible = ref(false)
const editingId = ref(null)
const formRef = ref(null)

const emptyForm = () => ({
  ho_ten: '',
  email: '',
  sdt: '',
  phong_ban: '',
  loai_nhan_vien: 'Full-time',
  trang_thai: 'dang_lam',
})

const form = reactive(emptyForm())

const rules = {
  ho_ten: [{ required: true, message: 'Vui lòng nhập họ tên', trigger: 'blur' }],
  email: [
    { required: true, message: 'Vui lòng nhập email', trigger: 'blur' },
    { type: 'email', message: 'Email không hợp lệ', trigger: 'blur' },
  ],
  loai_nhan_vien: [{ required: true, message: 'Vui lòng chọn loại NV', trigger: 'change' }],
}

const filteredEmployees = computed(() => {
  const q = keyword.value.trim().toLowerCase()
  return employees.value.filter((e) => {
    const matchStatus = !statusFilter.value || e.trang_thai === statusFilter.value
    const matchKeyword =
      !q ||
      e.ho_ten.toLowerCase().includes(q) ||
      e.email.toLowerCase().includes(q) ||
      (e.sdt || '').includes(q)
    return matchStatus && matchKeyword
  })
})

function statusLabel(status) {
  return (
    {
      dang_lam: 'Đang làm việc',
      da_nghi: 'Đã nghỉ việc',
      han_che: 'Hạn chế quyền',
    }[status] || status
  )
}

function statusType(status) {
  return { dang_lam: 'success', da_nghi: 'info', han_che: 'warning' }[status] || 'info'
}

function openCreate() {
  editingId.value = null
  Object.assign(form, emptyForm())
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  Object.assign(form, {
    ho_ten: row.ho_ten,
    email: row.email,
    sdt: row.sdt,
    phong_ban: row.phong_ban,
    loai_nhan_vien: row.loai_nhan_vien,
    trang_thai: row.trang_thai,
  })
  dialogVisible.value = true
}

async function save() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  if (editingId.value) {
    const idx = employees.value.findIndex((e) => e.id === editingId.value)
    if (idx !== -1) {
      employees.value[idx] = { ...employees.value[idx], ...form }
    }
    ElMessage.success('Đã cập nhật nhân sự.')
  } else {
    const nextId = Math.max(0, ...employees.value.map((e) => e.id)) + 1
    employees.value.push({
      id: nextId,
      ma: `NV${String(nextId).padStart(3, '0')}`,
      ...form,
    })
    ElMessage.success('Đã thêm nhân sự.')
  }
  dialogVisible.value = false
}

async function remove(row) {
  await ElMessageBox.confirm(`Xóa nhân sự "${row.ho_ten}"?`, 'Xác nhận', {
    type: 'warning',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
  })
  employees.value = employees.value.filter((e) => e.id !== row.id)
  ElMessage.success('Đã xóa nhân sự.')
}
</script>

<style scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
}
</style>
