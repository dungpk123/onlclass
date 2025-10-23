import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null,
    initialized: false
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    isAdmin: (state) => state.user?.role?.name === 'admin',
    isTeacher: (state) => state.user?.role?.name === 'teacher', 
    isStudent: (state) => state.user?.role?.name === 'student',
    userRole: (state) => state.user?.role?.name || null
  },

  actions: {
    // Setup axios interceptors
    setupAxios() {
    axios.defaults.baseURL = 'http://localhost:8000/api'
      
      // Request interceptor
      axios.interceptors.request.use(
        (config) => {
          if (this.token) {
            config.headers.Authorization = `Bearer ${this.token}`
          }
          return config
        },
        (error) => {
          return Promise.reject(error)
        }
      )

      // Response interceptor
      axios.interceptors.response.use(
        (response) => response,
        (error) => {
          if (error.response?.status === 401) {
            this.logout()
          }
          return Promise.reject(error)
        }
      )
    },

    // Login
    async login(credentials) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post('/auth/login', credentials)
        const { access_token, user } = response.data
        
        this.token = access_token
        this.user = user
        localStorage.setItem('token', access_token)
        
        this.setupAxios()
        
        return response.data
      } catch (error) {
        this.error = error.response?.data?.error || 'Đăng nhập thất bại'
        throw error
      } finally {
        this.loading = false
      }
    },

    // Register
    async register(userData) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post('/auth/register', userData)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Đăng ký thất bại'
        throw error
      } finally {
        this.loading = false
      }
    },

    // Get user profile
    async getProfile() {
      try {
        const response = await axios.get('/profile')
        this.user = response.data
        return response.data
      } catch (error) {
        console.error('Failed to get profile:', error)
        throw error
      }
    },

    // Logout
    async logout() {
      try {
        if (this.token) {
          await axios.post('/auth/logout')
        }
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.token = null
        this.user = null
        localStorage.removeItem('token')
        
        // Redirect to login
        window.location.href = '/login'
      }
    },

    // Initialize auth (called on app start)
    async init() {
      this.loading = true
      
      if (this.token) {
        this.setupAxios()
        try {
          await this.getProfile()
        } catch (error) {
          console.error('Failed to initialize auth:', error)
          // Token invalid, clear it
          this.token = null
          this.user = null
          localStorage.removeItem('token')
        }
      }
      
      this.initialized = true
      this.loading = false
    },

    // Clear error
    clearError() {
      this.error = null
    }
  }
})