<template>
  <div class="main-content bg-light min-vh-100">
    <div class="container py-4">
      <!-- Header -->
      <div class="row mb-4">
        <div class="col">
          <h1 class="h3 mb-0">
            <i class="bi bi-person-circle me-2"></i>
            Thông tin cá nhân
          </h1>
        </div>
      </div>

      <!-- Profile Form -->
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card">
            <div class="card-body p-4">
              <form @submit.prevent="handleUpdateProfile">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Họ tên</label>
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
                    <label class="form-label">Email</label>
                    <input 
                      v-model="form.email"
                      type="email" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.email }"
                      required
                    >
                    <div class="invalid-feedback" v-if="errors.email">
                      {{ errors.email[0] }}
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Vai trò</label>
                    <input 
                      :value="authStore.user?.role?.description || authStore.user?.role?.name"
                      type="text" 
                      class="form-control"
                      disabled
                    >
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input 
                      v-model="form.phone"
                      type="tel" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.phone }"
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
                    rows="3"
                  ></textarea>
                  <div class="invalid-feedback" v-if="errors.address">
                    {{ errors.address[0] }}
                  </div>
                </div>

                <hr>

                <h5>Đổi mật khẩu</h5>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Mật khẩu hiện tại</label>
                    <input 
                      v-model="form.current_password"
                      type="password" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.current_password }"
                    >
                    <div class="invalid-feedback" v-if="errors.current_password">
                      {{ errors.current_password[0] }}
                    </div>
                  </div>

                  <div class="col-md-4 mb-3">
                    <label class="form-label">Mật khẩu mới</label>
                    <input 
                      v-model="form.password"
                      type="password" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.password }"
                    >
                    <div class="invalid-feedback" v-if="errors.password">
                      {{ errors.password[0] }}
                    </div>
                  </div>

                  <div class="col-md-4 mb-3">
                    <label class="form-label">Xác nhận mật khẩu mới</label>
                    <input 
                      v-model="form.password_confirmation"
                      type="password" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.password_confirmation }"
                    >
                    <div class="invalid-feedback" v-if="errors.password_confirmation">
                      {{ errors.password_confirmation[0] }}
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
                    Cập nhật
                  </button>
                  <router-link to="/dashboard" class="btn btn-secondary">
                    Quay lại
                  </router-link>
                </div>
              </form>

              <!-- Success Alert -->
              <div class="alert alert-success mt-3" v-if="success">
                Cập nhật thành công!
              </div>

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
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const authStore = useAuthStore()

const form = reactive({
  name: '',
  email: '',
  phone: '',
  address: '',
  current_password: '',
  password: '',
  password_confirmation: ''
})

const errors = ref({})
const loading = ref(false)
const success = ref(false)
const error = ref('')

const loadUserData = () => {
  if (authStore.user) {
    form.name = authStore.user.name || ''
    form.email = authStore.user.email || ''
    form.phone = authStore.user.phone || ''
    form.address = authStore.user.address || ''
  }
}

const handleUpdateProfile = async () => {
  loading.value = true
  errors.value = {}
  success.value = false
  error.value = ''

  try {
    const updateData = {
      name: form.name,
      email: form.email,
      phone: form.phone,
      address: form.address
    }

    // Add password fields if provided
    if (form.password) {
      updateData.current_password = form.current_password
      updateData.password = form.password
      updateData.password_confirmation = form.password_confirmation
    }

    const response = await axios.put('/profile', updateData)
    
    // Update user in store
    await authStore.getProfile()
    
    success.value = true
    
    // Clear password fields
    form.current_password = ''
    form.password = ''
    form.password_confirmation = ''
    
  } catch (err) {
    if (err.response?.status === 400) {
      errors.value = err.response.data
    } else {
      error.value = 'Có lỗi xảy ra khi cập nhật thông tin'
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadUserData()
})
</script>

<style scoped>
.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border-radius: 10px;
}

.form-control {
  border-radius: 8px;
}

.btn {
  border-radius: 8px;
}
</style>