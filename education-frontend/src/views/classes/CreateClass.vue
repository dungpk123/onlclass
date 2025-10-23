<template>
  <div class="main-content bg-light min-vh-100">
    <div class="container py-4">
      <!-- Header -->
      <div class="row mb-4">
        <div class="col">
          <h1 class="h3 mb-0">
            <i class="bi bi-plus-circle me-2"></i>
            Tạo lớp học mới
          </h1>
        </div>
        <div class="col-auto">
          <router-link to="/classes" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Quay lại
          </router-link>
        </div>
      </div>

      <!-- Form -->
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card">
            <div class="card-body p-4">
              <form @submit.prevent="handleCreate">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Tên lớp học *</label>
                    <input 
                      v-model="form.name"
                      type="text" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.name }"
                      required
                    >
                    <div class="invalid-feedback" v-if="errors.name">
                      {{ errors.name[0] }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Môn học *</label>
                    <input 
                      v-model="form.subject"
                      type="text" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.subject }"
                      required
                    >
                    <div class="invalid-feedback" v-if="errors.subject">
                      {{ errors.subject[0] }}
                    </div>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Mô tả</label>
                  <textarea 
                    v-model="form.description"
                    class="form-control"
                    :class="{ 'is-invalid': errors.description }"
                    rows="3"
                  ></textarea>
                  <div class="invalid-feedback" v-if="errors.description">
                    {{ errors.description[0] }}
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3" v-if="authStore.isAdmin">
                    <label class="form-label">Giảng viên *</label>
                    <select 
                      v-model="form.teacher_id"
                      class="form-select"
                      :class="{ 'is-invalid': errors.teacher_id }"
                      required
                    >
                      <option value="">Chọn giảng viên</option>
                      <option 
                        v-for="teacher in teachers" 
                        :key="teacher.id" 
                        :value="teacher.id"
                      >
                        {{ teacher.name }}
                      </option>
                    </select>
                    <div class="invalid-feedback" v-if="errors.teacher_id">
                      {{ errors.teacher_id[0] }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Chỉ tiêu sĩ số *</label>
                    <input 
                      v-model="form.capacity"
                      type="number" 
                      min="1"
                      max="100"
                      class="form-control"
                      :class="{ 'is-invalid': errors.capacity }"
                      required
                    >
                    <div class="invalid-feedback" v-if="errors.capacity">
                      {{ errors.capacity[0] }}
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Ngày bắt đầu *</label>
                    <input 
                      v-model="form.start_date"
                      type="datetime-local" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.start_date }"
                      required
                    >
                    <div class="invalid-feedback" v-if="errors.start_date">
                      {{ errors.start_date[0] }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Ngày kết thúc *</label>
                    <input 
                      v-model="form.end_date"
                      type="datetime-local" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.end_date }"
                      required
                    >
                    <div class="invalid-feedback" v-if="errors.end_date">
                      {{ errors.end_date[0] }}
                    </div>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <button 
                    type="submit" 
                    class="btn btn-primary"
                    :disabled="loading"
                  >
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                    Tạo lớp học
                  </button>
                  <router-link to="/classes" class="btn btn-secondary">
                    Hủy
                  </router-link>
                </div>
              </form>

              <!-- Error Alert -->
              <div class="alert alert-danger mt-3" v-if="error">
                {{ error }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  name: '',
  subject: '',
  description: '',
  teacher_id: '',
  capacity: 30,
  start_date: '',
  end_date: ''
})

const teachers = ref([])
const errors = ref({})
const loading = ref(false)
const error = ref('')

const fetchTeachers = async () => {
  try {
    const response = await axios.get('/teachers')
    teachers.value = response.data
  } catch (error) {
    console.error('Failed to fetch teachers:', error)
  }
}

const handleCreate = async () => {
  loading.value = true
  errors.value = {}
  error.value = ''

  try {
    // Set teacher_id for teachers creating their own classes
    if (authStore.isTeacher) {
      form.teacher_id = authStore.user.id
    }

    const response = await axios.post('/classes', form)
    
    // Redirect to class detail
    router.push(`/classes/${response.data.class.id}`)
    
  } catch (err) {
    if (err.response?.status === 400) {
      errors.value = err.response.data
    } else {
      error.value = err.response?.data?.error || 'Có lỗi xảy ra khi tạo lớp học'
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (authStore.isAdmin) {
    fetchTeachers()
  } else if (authStore.isTeacher) {
    form.teacher_id = authStore.user.id
  }
})
</script>

<style scoped>
.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border-radius: 10px;
}

.form-control, .form-select {
  border-radius: 8px;
}

.btn {
  border-radius: 8px;
}
</style>