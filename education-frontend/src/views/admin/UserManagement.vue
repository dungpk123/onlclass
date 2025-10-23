<template>
  <div class="main-content bg-light min-vh-100">
    <div class="container py-4">
      <!-- Header -->
      <div class="row mb-4">
        <div class="col">
          <h1 class="h3 mb-0">
            <i class="bi bi-people me-2"></i>
            Quản lý người dùng
          </h1>
        </div>
        <div class="col-auto">
          <button 
            class="btn btn-primary"
            data-bs-toggle="modal" 
            data-bs-target="#createModal"
          >
            <i class="bi bi-plus-circle me-1"></i>
            Thêm người dùng
          </button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card text-center bg-primary text-white">
            <div class="card-body">
              <h4 class="card-title">{{ stats.total }}</h4>
              <p class="card-text">Tổng người dùng</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center bg-success text-white">
            <div class="card-body">
              <h4 class="card-title">{{ stats.students }}</h4>
              <p class="card-text">Học viên</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center bg-info text-white">
            <div class="card-body">
              <h4 class="card-title">{{ stats.teachers }}</h4>
              <p class="card-text">Giảng viên</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center bg-warning text-white">
            <div class="card-body">
              <h4 class="card-title">{{ stats.admins }}</h4>
              <p class="card-text">Quản trị viên</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <select v-model="filters.role" class="form-select">
                <option value="">Tất cả vai trò</option>
                <option value="admin">Admin</option>
                <option value="teacher">Giảng viên</option>
                <option value="student">Học viên</option>
              </select>
            </div>
            <div class="col-md-6">
              <input 
                v-model="filters.search" 
                type="text" 
                class="form-control" 
                placeholder="Tìm kiếm theo tên hoặc email..."
              >
            </div>
            <div class="col-md-3">
              <button @click="fetchUsers" class="btn btn-outline-primary w-100">
                <i class="bi bi-search me-1"></i>
                Tìm kiếm
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Users Table -->
      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Tên</th>
                  <th>Email</th>
                  <th>Vai trò</th>
                  <th>Ngày tạo</th>
                  <th>Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in users" :key="user.id">
                  <td>{{ user.id }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      <i class="bi bi-person-circle me-2 fs-5"></i>
                      <div>
                        <div class="fw-medium">{{ user.name }}</div>
                        <small class="text-muted" v-if="user.phone">{{ user.phone }}</small>
                      </div>
                    </div>
                  </td>
                  <td>{{ user.email }}</td>
                  <td>
                    <span 
                      class="badge"
                      :class="getRoleBadgeClass(user.role?.name)"
                    >
                      {{ getRoleLabel(user.role?.name) }}
                    </span>
                  </td>
                  <td>{{ formatDate(user.created_at) }}</td>
                  <td>
                    <div class="dropdown">
                      <button 
                        class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="dropdown"
                      >
                        <i class="bi bi-three-dots-vertical"></i>
                      </button>
                      <ul class="dropdown-menu">
                        <li>
                          <a 
                            class="dropdown-item" 
                            href="#"
                            @click.prevent="viewUser(user)"
                            data-bs-toggle="modal" 
                            data-bs-target="#viewModal"
                          >
                            <i class="bi bi-eye me-1"></i>
                            Xem chi tiết
                          </a>
                        </li>
                        <li>
                          <a 
                            class="dropdown-item" 
                            href="#"
                            @click.prevent="editUser(user)"
                            data-bs-toggle="modal" 
                            data-bs-target="#editModal"
                          >
                            <i class="bi bi-pencil me-1"></i>
                            Chỉnh sửa
                          </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                          <a 
                            class="dropdown-item text-danger" 
                            href="#"
                            @click.prevent="deleteUser(user.id)"
                          >
                            <i class="bi bi-trash me-1"></i>
                            Xóa
                          </a>
                        </li>
                      </ul>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- No Users -->
          <div class="text-center py-4" v-if="!users.length && !loading">
            <i class="bi bi-people display-1 text-muted"></i>
            <h4 class="text-muted mt-3">Không có người dùng nào</h4>
          </div>

          <!-- Loading -->
          <div class="text-center py-4" v-if="loading">
            <div class="spinner-border text-primary"></div>
          </div>
        </div>
      </div>

      <!-- Create Modal -->
      <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Thêm người dùng mới</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="createUser">
                <div class="mb-3">
                  <label class="form-label">Tên <span class="text-danger">*</span></label>
                  <input 
                    v-model="form.name" 
                    type="text" 
                    class="form-control" 
                    required
                  >
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Email <span class="text-danger">*</span></label>
                  <input 
                    v-model="form.email" 
                    type="email" 
                    class="form-control" 
                    required
                  >
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Số điện thoại</label>
                  <input 
                    v-model="form.phone" 
                    type="text" 
                    class="form-control"
                  >
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Vai trò <span class="text-danger">*</span></label>
                  <select v-model="form.role" class="form-select" required>
                    <option value="">Chọn vai trò</option>
                    <option value="admin">Admin</option>
                    <option value="teacher">Giảng viên</option>
                    <option value="student">Học viên</option>
                  </select>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                  <input 
                    v-model="form.password" 
                    type="password" 
                    class="form-control" 
                    required
                  >
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                  <input 
                    v-model="form.password_confirmation" 
                    type="password" 
                    class="form-control" 
                    required
                  >
                </div>
                
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary flex-fill">
                    Tạo người dùng
                  </button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Hủy
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- View Modal -->
      <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Chi tiết người dùng</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div v-if="selectedUser">
                <div class="text-center mb-4">
                  <i class="bi bi-person-circle display-1 text-muted"></i>
                  <h4>{{ selectedUser.name }}</h4>
                  <span 
                    class="badge fs-6"
                    :class="getRoleBadgeClass(selectedUser.role?.name)"
                  >
                    {{ getRoleLabel(selectedUser.role?.name) }}
                  </span>
                </div>
                
                <div class="row">
                  <div class="col-sm-4"><strong>ID:</strong></div>
                  <div class="col-sm-8">{{ selectedUser.id }}</div>
                </div>
                <div class="row mt-2">
                  <div class="col-sm-4"><strong>Email:</strong></div>
                  <div class="col-sm-8">{{ selectedUser.email }}</div>
                </div>
                <div class="row mt-2" v-if="selectedUser.phone">
                  <div class="col-sm-4"><strong>Điện thoại:</strong></div>
                  <div class="col-sm-8">{{ selectedUser.phone }}</div>
                </div>
                <div class="row mt-2">
                  <div class="col-sm-4"><strong>Ngày tạo:</strong></div>
                  <div class="col-sm-8">{{ formatDate(selectedUser.created_at) }}</div>
                </div>
                
                <!-- Teacher Stats -->
                <div v-if="selectedUser.role?.name === 'teacher'" class="mt-4">
                  <h6>Thống kê giảng viên:</h6>
                  <div class="row">
                    <div class="col-6 text-center">
                      <div class="card bg-light">
                        <div class="card-body py-2">
                          <h5>{{ selectedUser.classes_count || 0 }}</h5>
                          <small>Lớp học</small>
                        </div>
                      </div>
                    </div>
                    <div class="col-6 text-center">
                      <div class="card bg-light">
                        <div class="card-body py-2">
                          <h5>{{ selectedUser.avg_rating || '0.0' }}</h5>
                          <small>Đánh giá TB</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Student Stats -->
                <div v-if="selectedUser.role?.name === 'student'" class="mt-4">
                  <h6>Thống kê học viên:</h6>
                  <div class="row">
                    <div class="col-12 text-center">
                      <div class="card bg-light">
                        <div class="card-body py-2">
                          <h5>{{ selectedUser.enrollments_count || 0 }}</h5>
                          <small>Lớp đã đăng ký</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Modal -->
      <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Chỉnh sửa người dùng</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="updateUser">
                <div class="mb-3">
                  <label class="form-label">Tên <span class="text-danger">*</span></label>
                  <input 
                    v-model="editForm.name" 
                    type="text" 
                    class="form-control" 
                    required
                  >
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Email <span class="text-danger">*</span></label>
                  <input 
                    v-model="editForm.email" 
                    type="email" 
                    class="form-control" 
                    required
                  >
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Số điện thoại</label>
                  <input 
                    v-model="editForm.phone" 
                    type="text" 
                    class="form-control"
                  >
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Vai trò <span class="text-danger">*</span></label>
                  <select v-model="editForm.role" class="form-select" required>
                    <option value="admin">Admin</option>
                    <option value="teacher">Giảng viên</option>
                    <option value="student">Học viên</option>
                  </select>
                </div>
                
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary flex-fill">
                    Cập nhật
                  </button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Hủy
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const authStore = useAuthStore()

const users = ref([])
const pagination = ref({ current_page:1, last_page:1, total:0, per_page:15 })
const loading = ref(false)
const selectedUser = ref(null)

const stats = ref({
  total: 0,
  students: 0,
  teachers: 0,
  admins: 0
})

const filters = ref({
  role: '',
  search: ''
})

const form = ref({
  name: '',
  email: '',
  phone: '',
  role: '',
  password: '',
  password_confirmation: ''
})

const editForm = ref({
  id: null,
  name: '',
  email: '',
  phone: '',
  role: ''
})

const fetchUsers = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.role) params.append('role', filters.value.role)
    if (filters.value.search) params.append('search', filters.value.search)
    
    const response = await axios.get(`/users?${params.toString()}`)
    const body = response.data
    if (Array.isArray(body)) {
      users.value = body
      pagination.value = { current_page:1, last_page:1, total: body.length, per_page: body.length }
    } else {
      users.value = body.data || []
      pagination.value = {
        current_page: body.current_page || 1,
        last_page: body.last_page || 1,
        total: body.total || (body.data ? body.data.length : 0),
        per_page: body.per_page || (body.data ? body.data.length : 0)
      }
    }
    const students = users.value.filter(u => u.role?.name === 'student').length
    const teachers = users.value.filter(u => u.role?.name === 'teacher').length
    const admins = users.value.filter(u => u.role?.name === 'admin').length
    stats.value = { total: pagination.value.total, students, teachers, admins }
  } catch (error) {
    console.error('Failed to fetch users:', error)
  } finally {
    loading.value = false
  }
}

const createUser = async () => {
  try {
    await axios.post('/users', form.value)
    
    // Reset form
    form.value = {
      name: '',
      email: '',
      phone: '',
      role: '',
      password: '',
      password_confirmation: ''
    }
    
    // Close modal and refresh
    const modal = document.getElementById('createModal')
    const bsModal = bootstrap.Modal.getInstance(modal)
    bsModal.hide()
    
    await fetchUsers()
  } catch (error) {
    alert(error.response?.data?.message || 'Có lỗi xảy ra')
  }
}

const viewUser = (user) => {
  selectedUser.value = user
}

const editUser = (user) => {
  editForm.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    phone: user.phone || '',
    role: user.role?.name || ''
  }
}

const updateUser = async () => {
  try {
    await axios.put(`/users/${editForm.value.id}`, {
      name: editForm.value.name,
      email: editForm.value.email,
      phone: editForm.value.phone,
      role: editForm.value.role
    })
    
    // Close modal and refresh
    const modal = document.getElementById('editModal')
    const bsModal = bootstrap.Modal.getInstance(modal)
    bsModal.hide()
    
    await fetchUsers()
  } catch (error) {
    alert(error.response?.data?.message || 'Có lỗi xảy ra')
  }
}

const deleteUser = async (id) => {
  if (!confirm('Bạn có chắc muốn xóa người dùng này?')) return
  
  try {
    await axios.delete(`/users/${id}`)
    await fetchUsers()
  } catch (error) {
    alert(error.response?.data?.message || 'Có lỗi xảy ra')
  }
}

const getRoleLabel = (role) => {
  const labels = {
    admin: 'Quản trị viên',
    teacher: 'Giảng viên',
    student: 'Học viên'
  }
  return labels[role] || role
}

const getRoleBadgeClass = (role) => {
  const classes = {
    admin: 'bg-danger',
    teacher: 'bg-info',
    student: 'bg-success'
  }
  return classes[role] || 'bg-secondary'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('vi-VN')
}

// Watch for filter changes
watch(filters, () => {
  fetchUsers()
}, { deep: true })

onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border-radius: 10px;
}

.table th {
  border-bottom: 2px solid #dee2e6;
  font-weight: 600;
}
</style>