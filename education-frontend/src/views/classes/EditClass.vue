<template>
  <div class="main-content bg-light min-vh-100">
    <div class="container py-4">
      <!-- Header -->
      <div class="row mb-4 align-items-center">
        <div class="col">
          <h1 class="h4 mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-pencil-square"></i>
            Chỉnh sửa lớp học
            <span v-if="loadingFetch" class="spinner-border spinner-border-sm text-secondary ms-2"></span>
          </h1>
          <div v-if="classData" class="text-muted small mt-1">
            ID: {{ classData.id }} · Tạo bởi: {{ classData.teacher?.name || 'N/A' }}
          </div>
        </div>
        <div class="col-auto d-flex gap-2">
          <router-link :to="`/classes/${route.params.id}`" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Quay lại
          </router-link>
          <router-link to="/classes" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list me-1"></i> Danh sách
          </router-link>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <form @submit.prevent="handleUpdate" v-if="formReady">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Tên lớp học *</label>
                    <input v-model="form.name" type="text" class="form-control" :class="{ 'is-invalid': errors.name }" required>
                    <div class="invalid-feedback" v-if="errors.name">{{ errors.name[0] }}</div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Môn học *</label>
                    <input v-model="form.subject" type="text" class="form-control" :class="{ 'is-invalid': errors.subject }" required>
                    <div class="invalid-feedback" v-if="errors.subject">{{ errors.subject[0] }}</div>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Mô tả</label>
                  <textarea v-model="form.description" class="form-control" rows="3" :class="{ 'is-invalid': errors.description }"></textarea>
                  <div class="invalid-feedback" v-if="errors.description">{{ errors.description[0] }}</div>
                </div>

                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Chỉ tiêu sĩ số *</label>
                    <input v-model.number="form.capacity" type="number" min="1" max="100" class="form-control" :class="{ 'is-invalid': errors.capacity }" required>
                    <div class="invalid-feedback" v-if="errors.capacity">{{ errors.capacity[0] }}</div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Ngày bắt đầu *</label>
                    <input v-model="form.start_date" type="datetime-local" class="form-control" :class="{ 'is-invalid': errors.start_date }" required>
                    <div class="invalid-feedback" v-if="errors.start_date">{{ errors.start_date[0] }}</div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Ngày kết thúc *</label>
                    <input v-model="form.end_date" type="datetime-local" class="form-control" :class="{ 'is-invalid': errors.end_date }" required>
                    <div class="invalid-feedback" v-if="errors.end_date">{{ errors.end_date[0] }}</div>
                  </div>
                </div>

                <div class="row" v-if="authStore.isAdmin">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Giảng viên *</label>
                    <select v-model="form.teacher_id" class="form-select" :class="{ 'is-invalid': errors.teacher_id }" required>
                      <option value="">Chọn giảng viên</option>
                      <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                    <div class="invalid-feedback" v-if="errors.teacher_id">{{ errors.teacher_id[0] }}</div>
                  </div>
                  <div class="col-md-6 mb-3 d-flex align-items-end">
                    <div class="alert alert-warning py-2 px-3 w-100 mb-0 small" v-if="classData && classData.teacher_id !== form.teacher_id">
                      <i class="bi bi-exclamation-triangle me-1"></i>
                      Đổi giảng viên sẽ ảnh hưởng phân quyền.
                    </div>
                  </div>
                </div>

                <div class="border-top pt-3 mt-2 d-flex gap-2 flex-wrap">
                  <button type="submit" class="btn btn-primary" :disabled="loadingSubmit">
                    <span v-if="loadingSubmit" class="spinner-border spinner-border-sm me-2"></span>
                    Lưu thay đổi
                  </button>
                  <router-link :to="`/classes/${route.params.id}`" class="btn btn-outline-secondary">Hủy</router-link>
                  <button type="button" class="btn btn-outline-danger ms-auto" @click="confirmDelete" :disabled="loadingDelete">
                    <span v-if="loadingDelete" class="spinner-border spinner-border-sm me-2"></span>
                    Xóa lớp học
                  </button>
                </div>

                <div v-if="formError" class="alert alert-danger mt-3 small mb-0">{{ formError }}</div>
                <div v-if="formSuccess" class="alert alert-success mt-3 small mb-0">{{ formSuccess }}</div>
              </form>

              <div v-else class="py-5 text-center text-muted">
                <div class="spinner-border text-primary mb-3"></div>
                <div>Đang tải dữ liệu lớp học...</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Side meta -->
        <div class="col-lg-3 d-none d-lg-block">
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">
              <h6 class="text-uppercase small fw-bold mb-3">Thông tin nhanh</h6>
              <ul class="list-unstyled small mb-0" v-if="classData">
                <li class="mb-2"><i class="bi bi-hash text-primary me-1"></i> ID: {{ classData.id }}</li>
                <li class="mb-2"><i class="bi bi-people text-primary me-1"></i> Sĩ số: {{ classData.students?.length }} / {{ form.capacity }}</li>
                <li class="mb-2"><i class="bi bi-calendar-event text-primary me-1"></i> Bắt đầu: {{ formatDateDisplay(form.start_date) }}</li>
                <li class="mb-2"><i class="bi bi-flag text-primary me-1"></i> Kết thúc: {{ formatDateDisplay(form.end_date) }}</li>
              </ul>
              <div v-else class="small text-muted">Chưa có dữ liệu.</div>
            </div>
          </div>
          <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
              <h6 class="text-uppercase small fw-bold mb-3">Hướng dẫn</h6>
              <p class="small mb-2">• Cập nhật đúng ngày bắt đầu / kết thúc để trạng thái hiển thị chính xác.</p>
              <p class="small mb-2">• Không giảm sĩ số xuống thấp hơn số học viên hiện tại.</p>
              <p class="small mb-0">• Đổi giảng viên chỉ khi thật sự cần.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const classData = ref(null)
const teachers = ref([])
const loadingFetch = ref(false)
const loadingSubmit = ref(false)
const loadingDelete = ref(false)
const formReady = ref(false)
const formError = ref('')
const formSuccess = ref('')

const form = reactive({
  name: '',
  subject: '',
  description: '',
  teacher_id: '',
  capacity: 30,
  start_date: '',
  end_date: ''
})

const errors = ref({})

const fetchClass = async () => {
  loadingFetch.value = true
  try {
    const res = await axios.get(`/classes/${route.params.id}`)
    classData.value = res.data
    form.name = res.data.name
    form.subject = res.data.subject
    form.description = res.data.description || ''
    form.teacher_id = res.data.teacher_id
    form.capacity = res.data.capacity
    // Convert to datetime-local friendly value
    form.start_date = toLocalInput(res.data.start_date)
    form.end_date = toLocalInput(res.data.end_date)
    formReady.value = true
  } catch (e) {
    formError.value = e.response?.data?.error || 'Không tải được dữ liệu lớp học'
  } finally {
    loadingFetch.value = false
  }
}

const fetchTeachers = async () => {
  if (!authStore.isAdmin) return
  try {
    const res = await axios.get('/teachers')
    teachers.value = res.data
  } catch (e) {
    console.error(e)
  }
}

const toLocalInput = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const off = d.getTimezoneOffset()
  const local = new Date(d.getTime() - off * 60000)
  return local.toISOString().slice(0,16)
}

const formatDateDisplay = (dateStr) => {
  if (!dateStr) return ''
  try {
    return new Date(dateStr).toLocaleString('vi-VN')
  } catch { return dateStr }
}

const handleUpdate = async () => {
  loadingSubmit.value = true
  errors.value = {}
  formError.value = ''
  formSuccess.value = ''
  try {
    if (authStore.isTeacher && !authStore.isAdmin) {
      // Ensure teacher_id locked to current teacher
      form.teacher_id = classData.value.teacher_id
    }
    const payload = { ...form }
    const res = await axios.put(`/classes/${route.params.id}`, payload)
    formSuccess.value = 'Cập nhật thành công'
    // Optionally refresh classData
    await fetchClass()
  } catch (e) {
    if (e.response?.status === 400) {
      errors.value = e.response.data
    } else {
      formError.value = e.response?.data?.error || 'Cập nhật thất bại'
    }
  } finally {
    loadingSubmit.value = false
  }
}

const confirmDelete = () => {
  if (!confirm('Bạn chắc chắn muốn xóa lớp học này?')) return
  deleteClass()
}

const deleteClass = async () => {
  loadingDelete.value = true
  formError.value = ''
  try {
    await axios.delete(`/classes/${route.params.id}`)
    router.push('/classes')
  } catch (e) {
    formError.value = e.response?.data?.error || 'Xóa lớp học thất bại'
  } finally {
    loadingDelete.value = false
  }
}

onMounted(async () => {
  await fetchClass()
  await fetchTeachers()
})
</script>

<style scoped>
.card { border-radius: 14px; }
.form-control, .form-select { border-radius: 10px; }
textarea { resize: vertical; }
</style>
