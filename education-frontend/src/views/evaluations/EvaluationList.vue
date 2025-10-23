<template>
  <div class="main-content bg-light min-vh-100">
    <div class="container py-4">
      <!-- Header -->
      <div class="row mb-4">
        <div class="col">
          <h1 class="h3 mb-0">
            <i class="bi bi-star me-2"></i>
            Đánh giá giảng viên
          </h1>
        </div>
        <div class="col-auto" v-if="authStore.isStudent">
          <button 
            class="btn btn-primary"
            data-bs-toggle="modal" 
            data-bs-target="#createModal"
          >
            <i class="bi bi-plus-circle me-1"></i>
            Tạo đánh giá
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <select v-model="filters.teacher_id" class="form-select">
                <option value="">Tất cả giảng viên</option>
                <option 
                  v-for="teacher in teachers" 
                  :key="teacher.id" 
                  :value="teacher.id"
                >
                  {{ teacher.name }}
                </option>
              </select>
            </div>
            <div class="col-md-4">
              <select v-model="filters.rating" class="form-select">
                <option value="">Tất cả đánh giá</option>
                <option value="5">⭐⭐⭐⭐⭐ (5 sao)</option>
                <option value="4">⭐⭐⭐⭐☆ (4 sao)</option>
                <option value="3">⭐⭐⭐☆☆ (3 sao)</option>
                <option value="2">⭐⭐☆☆☆ (2 sao)</option>
                <option value="1">⭐☆☆☆☆ (1 sao)</option>
              </select>
            </div>
            <div class="col-md-4">
              <button @click="fetchEvaluations" class="btn btn-outline-primary w-100">
                <i class="bi bi-search me-1"></i>
                Tìm kiếm
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics Cards (for Admin/Teacher) -->
      <div class="row mb-4" v-if="authStore.isAdmin || authStore.isTeacher">
        <div class="col-md-3">
          <div class="card text-center bg-primary text-white">
            <div class="card-body">
              <h4 class="card-title">{{ stats.total }}</h4>
              <p class="card-text">Tổng đánh giá</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center bg-success text-white">
            <div class="card-body">
              <h4 class="card-title">
                {{ stats.average.toFixed(1) }}/5
                <div class="mt-1">
                  <template v-for="i in 5" :key="i">
                    <i 
                      class="bi text-warning" 
                      :class="i <= Math.round(stats.average) ? 'bi-star-fill' : 'bi-star'"
                      style="font-size: 0.8rem;"
                    ></i>
                  </template>
                </div>
              </h4>
              <p class="card-text">Điểm trung bình</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center bg-info text-white">
            <div class="card-body">
              <h4 class="card-title">{{ stats.excellent }}</h4>
              <p class="card-text">Xuất sắc (5 sao)</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center bg-warning text-white">
            <div class="card-body">
              <h4 class="card-title">{{ stats.poor }}</h4>
              <p class="card-text">Kém (1-2 sao)</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Evaluations List -->
      <div class="row" v-if="evaluations.length">
        <div 
          v-for="evaluation in evaluations" 
          :key="evaluation.id"
          class="col-lg-6 mb-4"
        >
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h5 class="card-title mb-1">{{ getTeacherDisplayName(evaluation) }}</h5>
                  <div class="rating-display mb-2">
                    <div class="d-flex align-items-center">
                      <div class="text-warning me-2">
                        <template v-for="i in 5" :key="i">
                          <i 
                            class="bi" 
                            :class="i <= evaluation.rating ? 'bi-star-fill' : 'bi-star'"
                          ></i>
                        </template>
                      </div>
                      <span class="fw-bold text-primary">{{ evaluation.rating }}/5</span>
                      <span class="ms-2 small text-muted">{{ getRatingText(evaluation.rating) }}</span>
                    </div>
                  </div>
                </div>
                <div class="dropdown" v-if="canManageEvaluation(evaluation)">
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
                        @click.prevent="editEvaluation(evaluation)"
                        data-bs-toggle="modal" 
                        data-bs-target="#editModal"
                      >
                        <i class="bi bi-pencil me-1"></i>
                        Chỉnh sửa
                      </a>
                    </li>
                    <li>
                      <a 
                        class="dropdown-item text-danger" 
                        href="#"
                        @click.prevent="deleteEvaluation(evaluation.id)"
                      >
                        <i class="bi bi-trash me-1"></i>
                        Xóa
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
              
              <p class="card-text">{{ evaluation.comment || 'Không có nhận xét' }}</p>
              
              <div class="d-flex justify-content-between text-muted">
                <small>
                  <i class="bi bi-person me-1"></i>
                  {{ evaluation.student?.name }}
                </small>
                <small>
                  <i class="bi bi-calendar me-1"></i>
                  {{ formatDate(evaluation.created_at) }}
                </small>
              </div>
              
              <!-- Anonymous label -->
              <h6 class="mb-1 text-muted" v-if="isAnonymous(evaluation)">
                Ẩn danh
              </h6>
            </div>
          </div>
        </div>
      </div>

      <!-- No Evaluations -->
      <div class="text-center py-5" v-else-if="!loading">
        <i class="bi bi-star display-1 text-muted"></i>
        <h4 class="text-muted mt-3">Chưa có đánh giá nào</h4>
        <p class="text-muted" v-if="authStore.isStudent">
          Chưa có đánh giá nào trong các lớp học bạn đã tham gia.<br>
          <small>Hãy tham gia vào các lớp học để đánh giá giảng viên.</small>
        </p>
        <p class="text-muted" v-else-if="authStore.isTeacher">
          Chưa có học viên nào đánh giá bạn.<br>
          <small>Các đánh giá từ học viên sẽ hiển thị tại đây.</small>
        </p>
        <p class="text-muted" v-else>
          Chưa có đánh giá giảng viên nào được tạo
        </p>
      </div>

      <!-- Loading -->
      <div class="text-center py-5" v-if="loading">
        <div class="spinner-border text-primary"></div>
      </div>

      <!-- Create Modal -->
      <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Tạo đánh giá mới</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="createEvaluation">
                <div class="mb-3">
                  <label class="form-label">Lớp học <span class="text-danger">*</span></label>
                  <select v-model="form.class_id" @change="onClassChange" class="form-select" required>
                    <option value="">Chọn lớp học</option>
                    <option 
                      v-for="classItem in enrolledClasses" 
                      :key="classItem.id" 
                      :value="classItem.id"
                    >
                      {{ classItem.name }} - {{ classItem.teacher?.name }}
                    </option>
                  </select>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Giảng viên <span class="text-danger">*</span></label>
                  <input 
                    :value="getSelectedTeacherName()" 
                    type="text" 
                    class="form-control" 
                    readonly
                    placeholder="Chọn lớp học trước để xác định giảng viên"
                    style="background-color: #f8f9fa;"
                  >
                  <div class="form-text">
                    Giảng viên sẽ được tự động chọn dựa trên lớp học bạn chọn
                  </div>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Đánh giá <span class="text-danger">*</span></label>
                  <div class="rating-input-section">
                    <div class="d-flex gap-1 align-items-center mb-2">
                      <template v-for="i in 5" :key="i">
                        <button
                          type="button"
                          class="btn btn-link p-1 text-warning"
                          @click="form.rating = i"
                          :title="`${i} sao - ${getRatingText(i)}`"
                        >
                          <i 
                            class="bi fs-4" 
                            :class="i <= form.rating ? 'bi-star-fill' : 'bi-star'"
                          ></i>
                        </button>
                      </template>
                    </div>
                    <div class="text-center">
                      <span class="fw-bold text-primary">{{ form.rating }}/5</span>
                      <span class="ms-2 text-muted">- {{ getRatingText(form.rating) }}</span>
                    </div>
                  </div>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Nhận xét</label>
                  <textarea 
                    v-model="form.comment" 
                    class="form-control" 
                    rows="4"
                    placeholder="Chia sẻ nhận xét của bạn về giảng viên..."
                  ></textarea>
                </div>
                
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary flex-fill">
                    Tạo đánh giá
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

      <!-- Edit Modal -->
      <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Chỉnh sửa đánh giá</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="updateEvaluation">
                <div class="mb-3">
                  <label class="form-label">Giảng viên</label>
                  <input 
                    :value="editForm.teacher_name" 
                    type="text" 
                    class="form-control" 
                    readonly
                  >
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Đánh giá <span class="text-danger">*</span></label>
                  <div class="rating-input-section">
                    <div class="d-flex gap-1 align-items-center mb-2">
                      <template v-for="i in 5" :key="i">
                        <button
                          type="button"
                          class="btn btn-link p-1 text-warning"
                          @click="editForm.rating = i"
                          :title="`${i} sao - ${getRatingText(i)}`"
                        >
                          <i 
                            class="bi fs-4" 
                            :class="i <= editForm.rating ? 'bi-star-fill' : 'bi-star'"
                          ></i>
                        </button>
                      </template>
                    </div>
                    <div class="text-center">
                      <span class="fw-bold text-primary">{{ editForm.rating }}/5</span>
                      <span class="ms-2 text-muted">- {{ getRatingText(editForm.rating) }}</span>
                    </div>
                  </div>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Nhận xét</label>
                  <textarea 
                    v-model="editForm.comment" 
                    class="form-control" 
                    rows="4"
                  ></textarea>
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

const evaluations = ref([])
const teachers = ref([])
const enrolledClasses = ref([])
const loading = ref(false)

const stats = ref({
  total: 0,
  average: 0,
  excellent: 0,
  poor: 0
})

const filters = ref({
  teacher_id: '',
  rating: ''
})

const form = ref({
  class_id: '',
  teacher_id: '',
  rating: 5,
  comment: ''
})

const editForm = ref({
  id: null,
  teacher_id: '',
  teacher_name: '',
  rating: 5,
  comment: ''
})

const canManageEvaluation = (evaluation) => {
  return authStore.isAdmin || evaluation.student_id === authStore.user?.id
}

const fetchEvaluations = async (throwOnError = false) => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.teacher_id) params.append('teacher_id', filters.value.teacher_id)
    if (filters.value.rating) params.append('rating', filters.value.rating)
    
    console.log('Fetching evaluations with params:', params.toString())
    console.log('User role:', authStore.user?.role?.name)
    
    const response = await axios.get(`/evaluations?${params.toString()}`)
    
    // Handle pagination
    let evaluationData = []
    if (response.data.data) {
      evaluationData = response.data.data
      console.log('Evaluations loaded (paginated):', evaluationData.length)
    } else {
      evaluationData = response.data
      console.log('Evaluations loaded (direct):', evaluationData.length)
    }
    
    evaluations.value = evaluationData
    
    // Calculate stats - wrap in try-catch to prevent errors from affecting the fetch
    try {
      const total = evaluations.value.length
      const average = total > 0 ? evaluations.value.reduce((sum, e) => sum + (Number(e.rating) || 0), 0) / total : 0
      const excellent = evaluations.value.filter(e => Number(e.rating) === 5).length
      const poor = evaluations.value.filter(e => Number(e.rating) <= 2).length
      
      stats.value = { total, average, excellent, poor }
    } catch (statsError) {
      console.warn('Stats calculation error:', statsError)
      stats.value = { total: 0, average: 0, excellent: 0, poor: 0 }
    }
    
  } catch (error) {
    console.error('Failed to fetch evaluations:', error)
    if (throwOnError) {
      throw error
    }
  } finally {
    loading.value = false
  }
}

const fetchTeachers = async () => {
  try {
    let url = '/teachers'
    
    // Học viên chỉ xem giảng viên của các lớp đã tham gia
    if (authStore.isStudent) {
      url += '?enrolled=true'
    }
    
    const response = await axios.get(url)
    teachers.value = response.data
    console.log('Teachers loaded:', teachers.value.length)
  } catch (error) {
    console.error('Failed to fetch teachers:', error)
  }
}

const fetchEnrolledClasses = async () => {
  if (!authStore.isStudent) return
  
  try {
    const response = await axios.get('/classes?enrolled=true')
    
    // Handle pagination
    if (response.data.data) {
      enrolledClasses.value = response.data.data
    } else {
      enrolledClasses.value = response.data
    }
    console.log('Enrolled classes loaded:', enrolledClasses.value.length)
  } catch (error) {
    console.error('Failed to fetch enrolled classes:', error)
  }
}

const createEvaluation = async () => {
  try {
    const response = await axios.post('/evaluations', form.value)
    console.log('Create response:', response.status, response.data)
    
    // Show success message first
    alert('Tạo đánh giá thành công!')
    
    // Reset form
    form.value = { class_id: '', teacher_id: '', rating: 5, comment: '' }
    
    // Close modal
    const modal = document.getElementById('createModal')
    if (modal) {
      try {
        const bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal)
        bsModal.hide()
      } catch (modalError) {
        console.warn('Modal close error:', modalError)
        modal.querySelector('[data-bs-dismiss="modal"]')?.click()
      }
    }
    
    // Refresh data with error handling
    try {
      await fetchEvaluations()
    } catch (fetchError) {
      console.warn('Fetch evaluations error after create:', fetchError)
      // Don't show error to user since create was successful
    }
    
  } catch (error) {
    console.error('Create error:', error.response?.status, error.response?.data)
    alert(error.response?.data?.error || 'Có lỗi xảy ra khi tạo đánh giá')
  }
}

const editEvaluation = (evaluation) => {
  console.log('Editing evaluation:', evaluation)
  editForm.value = {
    id: evaluation.id,
    teacher_id: evaluation.teacher_id,
    teacher_name: evaluation.teacher?.name || '',
    rating: evaluation.rating,
    comment: evaluation.comment || ''
  }
  console.log('Edit form populated:', editForm.value)
}

const updateEvaluation = async () => {
  try {
    console.log('Updating evaluation:', editForm.value.id, {
      rating: editForm.value.rating,
      comment: editForm.value.comment
    })
    
    const response = await axios.put(`/evaluations/${editForm.value.id}`, {
      rating: editForm.value.rating,
      comment: editForm.value.comment
    })
    
    console.log('Update response:', response.status, response.data)
    
    // Show success message first
    alert('Cập nhật đánh giá thành công!')
    
    // Close modal
    const modal = document.getElementById('editModal')
    if (modal) {
      try {
        const bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal)
        bsModal.hide()
      } catch (modalError) {
        console.warn('Modal close error:', modalError)
        // Fallback: use data-bs-dismiss
        modal.querySelector('[data-bs-dismiss="modal"]')?.click()
      }
    }
    
    // Refresh data with error handling
    try {
      await fetchEvaluations()
    } catch (fetchError) {
      console.warn('Fetch evaluations error after update:', fetchError)
      // Don't show error to user since update was successful
    }
    
  } catch (error) {
    console.error('Update error:', error.response?.status, error.response?.data)
    const errorMessage = error.response?.data?.error || error.response?.data?.message || 'Có lỗi xảy ra khi cập nhật đánh giá'
    alert(errorMessage)
  }
}

const deleteEvaluation = async (id) => {
  if (!confirm('Bạn có chắc muốn xóa đánh giá này?')) return
  
  try {
    console.log('Deleting evaluation:', id)
    const response = await axios.delete(`/evaluations/${id}`)
    console.log('Delete response:', response.status, response.data)
    
    // Show success message first
    alert('Xóa đánh giá thành công!')
    
    // Refresh data with error handling
    try {
      await fetchEvaluations()
    } catch (fetchError) {
      console.warn('Fetch evaluations error after delete:', fetchError)
      // Don't show error to user since delete was successful
    }
    
  } catch (error) {
    console.error('Delete error:', error.response?.status, error.response?.data)
    const errorMessage = error.response?.data?.error || error.response?.data?.message || 'Có lỗi xảy ra khi xóa đánh giá'
    alert(errorMessage)
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('vi-VN')
}

const getRatingText = (rating) => {
  const ratingTexts = {
    5: 'Xuất sắc',
    4: 'Tốt', 
    3: 'Trung bình',
    2: 'Yếu',
    1: 'Kém'
  }
  return ratingTexts[rating] || ''
}

const onClassChange = () => {
  // Automatically set teacher_id based on selected class
  const selectedClass = enrolledClasses.value.find(cls => cls.id == form.value.class_id)
  if (selectedClass && selectedClass.teacher) {
    form.value.teacher_id = selectedClass.teacher.id
  } else {
    form.value.teacher_id = ''
  }
}

const getSelectedTeacherName = () => {
  const selectedClass = enrolledClasses.value.find(cls => cls.id == form.value.class_id)
  return selectedClass?.teacher?.name || ''
}

const getTeacherDisplayName = (evaluation) => {
  return evaluation?.teacher?.name || evaluation?.classRoom?.teacher?.name || 'Giảng viên';
}

// Check if evaluation is anonymous
const isAnonymous = (evaluation) => {
  return !!(evaluation.anonymous || (!evaluation.student_id && !evaluation.student));
};

// Watch for filter changes
watch(filters, () => {
  fetchEvaluations()
}, { deep: true })

onMounted(() => {
  fetchEvaluations()
  fetchTeachers()
  fetchEnrolledClasses()
})
</script>

<style scoped>
.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border-radius: 10px;
  transition: transform 0.2s;
}

.card:hover {
  transform: translateY(-2px);
}

.btn-link:focus {
  box-shadow: none;
}
</style>