import axios from 'axios'

// Cấu hình base URL cho Laravel API
axios.defaults.baseURL = 'http://localhost:8000/api'
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.withCredentials = true
// KHÔNG ép Content-Type chung là application/json để tránh phá multipart/form-data (FormData sẽ tự set)
// axios.defaults.headers.common['Content-Type'] = 'application/json'

// Response interceptor để xử lý lỗi
axios.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token && !config.headers.Authorization) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token hết hạn hoặc không hợp lệ
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default axios