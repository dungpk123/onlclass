<template>
  <div class="login-page d-flex align-items-center justify-content-center min-vh-100">
    <div class="login-container">
      <!-- Background Decorations -->
      <div class="bg-decoration bg-decoration-1"></div>
      <div class="bg-decoration bg-decoration-2"></div>
      <div class="bg-decoration bg-decoration-3"></div>
      
      <div class="glass-card">
        <!-- Header -->
        <div class="card-header text-center">
          <div class="logo-container">
            <div class="logo-circle">
              <i class="bi bi-mortarboard"></i>
            </div>
          </div>
          <h2 class="title">Chào mừng trở lại</h2>
          <p class="subtitle">Đăng nhập vào hệ thống quản lý giáo dục</p>
        </div>

        <!-- Form -->
        <div class="card-body">
          <form @submit.prevent="handleLogin" class="login-form">
            <!-- Email Field -->
            <div class="form-group">
              <label class="form-label">Địa chỉ Email</label>
              <div class="input-container">
                <i class="bi bi-envelope input-icon"></i>
                <input
                  v-model="form.email"
                  type="email"
                  class="modern-input"
                  :class="{ 'error': errors.email }"
                  placeholder="example@gmail.com"
                  required
                />
              </div>
              <div class="error-message" v-if="errors.email">{{ errors.email }}</div>
            </div>

            <!-- Password Field -->
            <div class="form-group">
              <label class="form-label">Mật khẩu</label>
              <div class="input-container">
                <i class="bi bi-lock input-icon"></i>
                <input
                  v-model="form.password"
                  type="password"
                  class="modern-input"
                  :class="{ 'error': errors.password }"
                  placeholder="••••••••"
                  required
                />
              </div>
              <div class="error-message" v-if="errors.password">{{ errors.password }}</div>
            </div>

            <!-- Remember & Forgot -->
            <div class="form-options">
              <label class="checkbox-container">
                <input type="checkbox" id="remember" />
                <span class="checkmark"></span>
                <span class="checkbox-label">Ghi nhớ đăng nhập</span>
              </label>
              <router-link to="/forgot" class="forgot-link">Quên mật khẩu?</router-link>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="login-btn" :disabled="loading">
              <span v-if="loading" class="loading-spinner"></span>
              <span v-else>Đăng nhập</span>
            </button>

            <!-- Register Link -->
            <div class="register-link">
              <span>Chưa có tài khoản?</span>
              <router-link to="/register">Đăng ký ngay</router-link>
            </div>
          </form>

          <!-- Error Alert -->
          <div class="error-alert" v-if="authStore.error">
            <i class="bi bi-exclamation-circle"></i>
            <span>{{ authStore.error }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from "vue"
import { useRouter } from "vue-router"
import { useAuthStore } from "@/stores/auth"

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: "",
  password: "",
})

const errors = ref({})
const loading = ref(false)

const handleLogin = async () => {
  loading.value = true
  errors.value = {}
  authStore.clearError()

  try {
    await authStore.login(form)
    router.push("/dashboard")
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* Main Background */
.login-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
  overflow: hidden;
}

/* Background Decorations */
.bg-decoration {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  animation: float 6s ease-in-out infinite;
}

.bg-decoration-1 {
  width: 200px;
  height: 200px;
  top: 10%;
  left: 10%;
  animation-delay: -2s;
}

.bg-decoration-2 {
  width: 150px;
  height: 150px;
  top: 60%;
  right: 15%;
  animation-delay: -4s;
}

.bg-decoration-3 {
  width: 100px;
  height: 100px;
  bottom: 20%;
  left: 20%;
  animation-delay: -1s;
}

@keyframes float {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(180deg); }
}

/* Container */
.login-container {
  position: relative;
  z-index: 1;
  max-width: 420px;
  width: 100%;
  margin: 0 20px;
}

/* Glass Card */
.glass-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  animation: slideIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(50px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Header */
.card-header {
  padding: 40px 40px 20px;
  background: transparent;
}

.logo-container {
  margin-bottom: 20px;
}

.logo-circle {
  width: 70px;
  height: 70px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.logo-circle i {
  font-size: 28px;
  color: white;
}

.title {
  font-size: 28px;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 8px;
  letter-spacing: -0.5px;
}

.subtitle {
  color: #718096;
  font-size: 16px;
  margin: 0;
}

/* Form Body */
.card-body {
  padding: 0 40px 40px;
}

.login-form {
  margin-top: 20px;
}

/* Form Groups */
.form-group {
  margin-bottom: 24px;
}

.form-label {
  display: block;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 8px;
  font-size: 14px;
}

/* Input Container */
.input-container {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 18px;
  z-index: 2;
  transition: color 0.3s ease;
}

.modern-input {
  width: 100%;
  padding: 16px 16px 16px 48px;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  background: #f7fafc;
  font-size: 16px;
  color: #2d3748;
  transition: all 0.3s ease;
  outline: none;
}

.modern-input::placeholder {
  color: #a0aec0;
}

.modern-input:focus {
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.modern-input:focus + .input-icon,
.modern-input:focus ~ .input-icon {
  color: #667eea;
}

.modern-input.error {
  border-color: #e53e3e;
  background: #fed7d7;
}

/* Error Message */
.error-message {
  color: #e53e3e;
  font-size: 14px;
  margin-top: 6px;
  display: flex;
  align-items: center;
}

/* Form Options */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

/* Custom Checkbox */
.checkbox-container {
  display: flex;
  align-items: center;
  cursor: pointer;
  user-select: none;
}

.checkbox-container input {
  display: none;
}

.checkmark {
  width: 20px;
  height: 20px;
  background: #f7fafc;
  border: 2px solid #e2e8f0;
  border-radius: 6px;
  margin-right: 8px;
  position: relative;
  transition: all 0.3s ease;
}

.checkbox-container:hover .checkmark {
  border-color: #667eea;
}

.checkbox-container input:checked ~ .checkmark {
  background: #667eea;
  border-color: #667eea;
}

.checkmark:after {
  content: "";
  position: absolute;
  display: none;
  left: 6px;
  top: 2px;
  width: 6px;
  height: 10px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.checkbox-container input:checked ~ .checkmark:after {
  display: block;
}

.checkbox-label {
  color: #4a5568;
  font-size: 14px;
}

/* Forgot Link */
.forgot-link {
  color: #667eea;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: color 0.3s ease;
}

.forgot-link:hover {
  color: #764ba2;
}

/* Login Button */
.login-btn {
  width: 100%;
  padding: 16px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 16px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  margin-bottom: 24px;
}

.login-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
}

.login-btn:active {
  transform: translateY(0);
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Loading Spinner */
.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Register Link */
.register-link {
  text-align: center;
  color: #718096;
  font-size: 14px;
}

.register-link span {
  margin-right: 4px;
}

.register-link a {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s ease;
}

.register-link a:hover {
  color: #764ba2;
}

/* Error Alert */
.error-alert {
  background: rgba(229, 62, 62, 0.1);
  border: 1px solid rgba(229, 62, 62, 0.2);
  border-radius: 12px;
  padding: 12px 16px;
  margin-top: 20px;
  display: flex;
  align-items: center;
  color: #e53e3e;
}

.error-alert i {
  margin-right: 8px;
  font-size: 18px;
}

/* Responsive */
@media (max-width: 768px) {
  .login-container {
    margin: 0 16px;
  }
  
  .card-header,
  .card-body {
    padding-left: 24px;
    padding-right: 24px;
  }
  
  .title {
    font-size: 24px;
  }
}
</style>
