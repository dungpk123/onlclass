<template>
  <div class="min-vh-100 d-flex align-items-center bg-light">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
          <div class="card shadow">
            <div class="card-body p-4">
              <div class="text-center mb-4">
                <i class="bi bi-person-plus fs-1 text-primary"></i>
                <h2 class="mt-2">Đăng ký</h2>
                <p class="text-muted">Tạo tài khoản mới</p>
              </div>

              <form @submit.prevent="handleRegister">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Họ tên *</label>
                    <input 
                      v-model="form.name"
                      type="text" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.name }"
                      placeholder="Nhập họ tên"
                      required
                    >
                    <div class="invalid-feedback" v-if="errors.name">
                      {{ errors.name[0] }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input 
                      v-model="form.email"
                      type="email" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.email }"
                      placeholder="Nhập email"
                      required
                    >
                    <div class="invalid-feedback" v-if="errors.email">
                      {{ errors.email[0] }}
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Mật khẩu *</label>
                    <input 
                      v-model="form.password"
                      type="password" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.password }"
                      placeholder="Nhập mật khẩu"
                      required
                    >
                    <div class="invalid-feedback" v-if="errors.password">
                      {{ errors.password[0] }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Xác nhận mật khẩu *</label>
                    <input 
                      v-model="form.password_confirmation"
                      type="password" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.password_confirmation }"
                      placeholder="Nhập lại mật khẩu"
                      required
                    >
                    <div class="invalid-feedback" v-if="errors.password_confirmation">
                      {{ errors.password_confirmation[0] }}
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Vai trò *</label>
                    <select 
                      v-model="form.role"
                      class="form-select"
                      :class="{ 'is-invalid': errors.role }"
                      required
                    >
                      <option value="">Chọn vai trò</option>
                      <option value="student">Học viên</option>
                      <option value="teacher">Giảng viên</option>
                    </select>
                    <div class="invalid-feedback" v-if="errors.role">
                      {{ errors.role[0] }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input 
                      v-model="form.phone"
                      type="tel" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.phone }"
                      placeholder="Nhập số điện thoại"
                    >
                    <div class="invalid-feedback" v-if="errors.phone">
                      {{ errors.phone[0] }}
                    </div>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Địa chỉ</label>
                  <textarea 
                    v-model="form.address"
                    class="form-control"
                    :class="{ 'is-invalid': errors.address }"
                    placeholder="Nhập địa chỉ"
                    rows="3"
                  ></textarea>
                  <div class="invalid-feedback" v-if="errors.address">
                    {{ errors.address[0] }}
                  </div>
                </div>

                <div class="d-grid mb-3">
                  <button 
                    type="submit" 
                    class="btn btn-primary"
                    :disabled="loading"
                  >
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                    Đăng ký
                  </button>
                </div>

                <div class="text-center">
                  <span class="text-muted">Đã có tài khoản?</span>
                  <router-link to="/login" class="text-decoration-none ms-1">
                    Đăng nhập
                  </router-link>
                </div>
              </form>

              <!-- Success Alert -->
              <div class="alert alert-success mt-3" v-if="success">
                Đăng ký thành công! Vui lòng đăng nhập.
              </div>

              <!-- Error Alert -->
              <div class="alert alert-danger mt-3" v-if="authStore.error">
                {{ authStore.error }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
  phone: '',
  address: ''
})

const errors = ref({})
const loading = ref(false)
const success = ref(false)

const handleRegister = async () => {
  loading.value = true
  errors.value = {}
  success.value = false
  authStore.clearError()

  try {
    await authStore.register(form)
    success.value = true
    
    // Redirect to login after 2 seconds
    setTimeout(() => {
      router.push('/login')
    }, 2000)
  } catch (error) {
    if (error.response?.status === 400) {
      const errorData = error.response.data
      if (typeof errorData === 'string') {
        errors.value = JSON.parse(errorData)
      } else {
        errors.value = errorData
      }
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.card {
  border: none;
  border-radius: 10px;
}

.form-control, .form-select {
  border-radius: 8px;
}

.btn {
  border-radius: 8px;
  padding: 12px;
}
</style>