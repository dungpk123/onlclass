<template>
  <div class="main-content bg-light min-vh-100">
    <div class="container py-4">
      <!-- Header -->
      <div class="row mb-4">
        <div class="col">
          <h1 class="h3 mb-0">
            <i class="bi bi-star me-2"></i>
            Tạo Đánh giá
          </h1>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item">
                <router-link to="/dashboard">Dashboard</router-link>
              </li>
              <li class="breadcrumb-item">
                <router-link to="/evaluations">Đánh giá</router-link>
              </li>
              <li class="breadcrumb-item active">Tạo đánh giá</li>
            </ol>
          </nav>
        </div>
      </div>

      <!-- Evaluation Form -->
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Đánh giá giảng viên</h5>
            </div>
            <div class="card-body">
              <form @submit.prevent="handleSubmit">
                <!-- Class Selection -->
                <div class="mb-3">
                  <label class="form-label">Lớp học *</label>
                  <select 
                    v-model="form.class_id"
                    class="form-select"
                    :class="{ 'is-invalid': errors.class_id }"
                    @change="onClassChange"
                    required
                  >
                    <option value="">Chọn lớp học</option>
                    <option 
                      v-for="cls in myClasses" 
                      :key="cls.id" 
                      :value="cls.id"
                    >
                      {{ cls.name }} - {{ cls.subject }}
                    </option>
                  </select>
                  <div class="invalid-feedback" v-if="errors.class_id">
                    {{ errors.class_id[0] }}
                  </div>
                </div>

                <!-- Teacher (Auto-filled based on class) -->
                <div class="mb-3" v-if="selectedClass">
                  <label class="form-label">Giảng viên</label>
                  <input 
                    type="text" 
                    class="form-control"
                    :value="selectedClass.teacher?.name"
                    readonly
                  >
                  <input type="hidden" v-model="form.teacher_id">
                </div>

                <!-- Rating -->
                <div class="mb-3">
                  <label class="form-label">Điểm đánh giá * (0-10)</label>
                  <input 
                    v-model="form.rating"
                    type="number"
                    min="0"
                    max="10"
                    step="0.1"
                    class="form-control"
                    :class="{ 'is-invalid': errors.rating }"
                    placeholder="Nhập điểm từ 0 đến 10"
                    required
                  >
                  <div class="invalid-feedback" v-if="errors.rating">
                    {{ errors.rating[0] }}
                  </div>
                </div>

                <!-- Rating Display -->
                <div class="mb-3" v-if="form.rating">
                  <div class="d-flex align-items-center">
                    <span class="me-2">Điểm:</span>
                    <div class="rating-stars">
                      <i 
                        v-for="n in 5" 
                        :key="n"
                        class="bi"
                        :class="n <= Math.ceil(form.rating / 2) ? 'bi-star-fill text-warning' : 'bi-star text-muted'"
                      ></i>
                    </div>
                    <span class="ms-2 fw-bold">{{ form.rating }}/10</span>
                  </div>
                </div>

                <!-- Evaluation Criteria -->
                <div class="mb-3">
                  <label class="form-label">Tiêu chí đánh giá</label>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-2">
                        <label class="form-label">Chất lượng giảng dạy</label>
                        <select v-model="criteria.teaching_quality" class="form-select">
                          <option value="1">Kém</option>
                          <option value="2">Trung bình</option>
                          <option value="3">Khá</option>
                          <option value="4">Tốt</option>
                          <option value="5">Xuất sắc</option>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label">Tương tác với học viên</label>
                        <select v-model="criteria.interaction" class="form-select">
                          <option value="1">Kém</option>
                          <option value="2">Trung bình</option>
                          <option value="3">Khá</option>
                          <option value="4">Tốt</option>
                          <option value="5">Xuất sắc</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-2">
                        <label class="form-label">Kiến thức chuyên môn</label>
                        <select v-model="criteria.expertise" class="form-select">
                          <option value="1">Kém</option>
                          <option value="2">Trung bình</option>
                          <option value="3">Khá</option>
                          <option value="4">Tốt</option>
                          <option value="5">Xuất sắc</option>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label">Tổ chức bài học</label>
                        <select v-model="criteria.organization" class="form-select">
                          <option value="1">Kém</option>
                          <option value="2">Trung bình</option>
                          <option value="3">Khá</option>
                          <option value="4">Tốt</option>
                          <option value="5">Xuất sắc</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Comment -->
                <div class="mb-3">
                  <label class="form-label">Nhận xét</label>
                  <textarea 
                    v-model="form.comment"
                    class="form-control"
                    :class="{ 'is-invalid': errors.comment }"
                    placeholder="Nhập nhận xét chi tiết về giảng viên..."
                    rows="4"
                  ></textarea>
                  <div class="invalid-feedback" v-if="errors.comment">
                    {{ errors.comment[0] }}
                  </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex gap-2">
                  <button 
                    type="submit" 
                    class="btn btn-primary"
                    :disabled="loading || !form.class_id"
                  >
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                    Gửi đánh giá
                  </button>
                  <router-link to="/evaluations" class="btn btn-secondary">
                    Hủy
                  </router-link>
                </div>
              </form>

              <!-- Success Alert -->
              <div class="alert alert-success mt-3" v-if="success">
                <i class="bi bi-check-circle me-2"></i>
                Đánh giá đã được gửi thành công!
              </div>

              <!-- Error Alert -->
              <div class="alert alert-danger mt-3" v-if="error">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ error }}
              </div>
            </div>
          </div>
        </div>

        <!-- Guidelines -->
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0">Hướng dẫn đánh giá</h6>
            </div>
            <div class="card-body">
              <h6>Thang điểm:</h6>
              <ul class="list-unstyled">
                <li><strong>9-10:</strong> Xuất sắc</li>
                <li><strong>7-8:</strong> Tốt</li>
                <li><strong>5-6:</strong> Khá</li>
                <li><strong>3-4:</strong> Trung bình</li>
                <li><strong>0-2:</strong> Kém</li>
              </ul>

              <h6 class="mt-3">Lưu ý:</h6>
              <ul class="small">
                <li>Chỉ có thể đánh giá giảng viên trong lớp mà bạn đã đăng ký</li>
                <li>Mỗi giảng viên chỉ được đánh giá 1 lần trong 1 lớp</li>
                <li>Đánh giá một cách khách quan và công bằng</li>
                <li>Nhận xét chi tiết sẽ giúp giảng viên cải thiện</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  class_id: '',
  teacher_id: '',
  rating: '',
  comment: ''
})

const criteria = reactive({
  teaching_quality: 3,
  interaction: 3,
  expertise: 3,
  organization: 3
})

const myClasses = ref([])
const errors = ref({})
const loading = ref(false)
const success = ref(false)
const error = ref('')

const selectedClass = computed(() => {
  return myClasses.value.find(cls => cls.id == form.class_id)
})

const fetchMyClasses = async () => {
  try {
    const response = await axios.get('/classes', {
      params: { enrolled: true }
    })
    myClasses.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to fetch classes:', error)
  }
}

const onClassChange = () => {
  if (selectedClass.value) {
    form.teacher_id = selectedClass.value.teacher_id
  }
}

const handleSubmit = async () => {
  loading.value = true
  errors.value = {}
  success.value = false
  error.value = ''

  try {
    const payload = {
      ...form,
      criteria: criteria
    }

    await axios.post('/evaluations', payload)
    success.value = true
    
    // Reset form
    Object.assign(form, {
      class_id: '',
      teacher_id: '',
      rating: '',
      comment: ''
    })
    
    // Redirect after success
    setTimeout(() => {
      router.push('/evaluations')
    }, 2000)
  } catch (err) {
    if (err.response?.status === 400) {
      errors.value = err.response.data
    } else if (err.response?.data?.error) {
      error.value = err.response.data.error
    } else {
      error.value = 'Có lỗi xảy ra khi gửi đánh giá'
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchMyClasses()
})
</script>

<style scoped>
.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border-radius: 10px;
}

.rating-stars i {
  font-size: 1.2rem;
}

.breadcrumb {
  background: transparent;
  padding: 0;
}

.breadcrumb-item a {
  text-decoration: none;
}
</style>