<template>
  <div class="modern-class-container">
    <!-- Hero Section -->
    <div class="hero-section">
      <div class="container py-5">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <div class="hero-content animate-fade-in-up">
              <h1 class="hero-title">
                <i class="bi bi-mortarboard me-3"></i>
                Danh sách lớp học
              </h1>
              <p class="hero-subtitle">
                Khám phá và quản lý các lớp học trong hệ thống
              </p>
              <div class="stats-row mt-4">
                <div class="stat-item">
                  <div class="stat-number">{{ totalClasses }}</div>
                  <div class="stat-label">Lớp học</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number">{{ totalStudents }}</div>
                  <div class="stat-label">Học viên</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 text-end">
            <div v-if="authStore.isTeacher || authStore.isAdmin" class="animate-fade-in-right">
              <router-link to="/classes/create" class="btn btn-primary btn-lg create-btn hover-lift">
                <i class="bi bi-plus-circle me-2"></i>
                Tạo lớp học mới
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
  <div class="container-fluid px-3 pb-4 compact-classes">
      <!-- Search & Filter Section -->
  <div v-if="!authStore.isTeacher" class="glass-card mx-3 mt-3 mb-2 animate-fade-in-up compact-filters" style="animation-delay: 0.03s">
        <div class="card-body p-2 py-3">
          <div class="row g-2 align-items-end small-row class-filter-row">
            <div class="col-md-4 col-lg-3">
              <label class="form-label filter-label">
                <i class="bi bi-search me-1"></i>
                Tìm kiếm
              </label>
              <div class="search-wrapper compact">
                <input 
                  v-model="filters.search"
                  @input="fetchClasses"
                  type="text" 
                  class="form-control modern-input input-sm"
                  placeholder="Tên lớp / môn..."
                >
                <div class="search-icon">
                  <i class="bi bi-search"></i>
                </div>
              </div>
            </div>
            
            <div class="col-md-4 col-lg-3" v-if="authStore.isAdmin">
              <label class="form-label filter-label d-block">
                <i class="bi bi-person-workspace me-1"></i>
                Giảng viên
              </label>
              <div class="dropdown w-100 dropdown-sm">
                <button class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center btn-sm" type="button" data-bs-toggle="dropdown">
                  <span class="text-truncate" style="max-width:82%">
                    <i class="bi bi-person-badge me-1 small"></i>
                    {{ selectedTeacherLabel }}
                  </span>
                  <i class="bi bi-caret-down-fill small"></i>
                </button>
                <ul class="dropdown-menu w-100 teacher-dropdown-menu">
                  <li>
                    <a class="dropdown-item d-flex align-items-center" href="#" @click.prevent="selectTeacher(null)">
                      <i class="bi bi-people me-2"></i> Tất cả
                    </a>
                  </li>
                  <li v-for="t in teachers" :key="t.id">
                    <a class="dropdown-item d-flex align-items-center" href="#" @click.prevent="selectTeacher(t.id)">
                      <i class="bi bi-person me-2"></i>
                      <span class="text-truncate">{{ t.name }}</span>
                      <i v-if="filters.teacher_id === t.id" class="bi bi-check ms-auto text-primary"></i>
                    </a>
                  </li>
                </ul>
              </div>
            </div>
            
            <div class="col-md-4 col-lg-2">
              <label class="form-label filter-label">
                <i class="bi bi-layout-three-columns me-1"></i>
                Hiển thị
              </label>
              <select 
                v-model.number="filters.per_page"
                @change="fetchClasses"
                class="form-select modern-select select-sm"
              >
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="40">40</option>
              </select>
            </div>
          </div>
          <div class="filter-tabs compact-tabs mt-2 mb-1">
            <button 
              v-for="status in classStatuses" 
              :key="status.value"
              @click="filters.status = status.value; fetchClasses()"
              :class="['filter-tab tab-sm', { active: filters.status === status.value }]"
            >
              <i :class="status.icon"></i>
              <span class="d-none d-sm-inline">{{ status.label }}</span>
            </button>
          </div>
        </div>
      </div>

  <!-- Classes Grid -->
  <div v-if="classes.data?.length && !loading" class="classes-grid mx-3 mb-4">
        <div 
          v-for="(cls, index) in classes.data" 
          :key="cls.id"
          class="class-card animate-fade-in-up"
          :style="{ animationDelay: `${0.1 * (index % 12)}s` }"
        >
          <div class="class-card-inner glass-card hover-lift">
            <!-- Class Status Badge -->
            <div class="class-status-badge" :class="getClassStatusColor(cls)">
              <i :class="getClassStatusIcon(cls)"></i>
              <span class="status-text">{{ getClassStatusText(cls) }}</span>
            </div>

            <!-- Class Header -->
            <div class="class-header">
              <div class="class-badges">
                <div class="class-subject-badge">
                  {{ cls.subject }}
                </div>
              </div>
              <h5 class="class-title">{{ cls.name }}</h5>
              <p class="class-description">
                {{ cls.description || 'Chưa có mô tả cho lớp học này' }}
              </p>
            </div>

            <!-- Teacher Info -->
            <div class="teacher-info">
              <div class="teacher-avatar">
                <img 
                  :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(cls.teacher?.name || 'Unknown')}&background=random&color=fff`"
                  :alt="cls.teacher?.name"
                  @error="handleImageError"
                >
              </div>
              <div class="teacher-details">
                <div class="teacher-name">{{ cls.teacher?.name }}</div>
                <div class="teacher-title">Giảng viên</div>
              </div>
            </div>

            <!-- Class Meta -->
            <div class="class-meta">
              <div class="meta-item">
                <i class="bi bi-calendar-event"></i>
                <span>{{ formatDate(cls.start_date) }}</span>
              </div>
              <div class="meta-item">
                <i class="bi bi-arrow-right"></i>
                <span>{{ formatDate(cls.end_date) }}</span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="class-actions">
              <router-link 
                :to="`/classes/${cls.id}`"
                class="btn btn-primary btn-sm action-btn"
              >
                <i class="bi bi-eye me-1"></i>
                Xem chi tiết
              </router-link>
              
              <!-- Student Actions -->
              <template v-if="authStore.isStudent">
                <button 
                  v-if="!isEnrolled(cls)"
                  @click="enrollClass(cls.id)"
                  class="btn btn-success btn-sm action-btn"
                  :disabled="cls.students?.length >= cls.capacity"
                >
                  <i class="bi bi-person-plus me-1"></i>
                  {{ cls.students?.length >= cls.capacity ? 'Đã đầy' : 'Đăng ký' }}
                </button>
                <button 
                  v-else
                  @click="leaveClass(cls.id)"
                  class="btn btn-warning btn-sm action-btn"
                >
                  <i class="bi bi-box-arrow-left me-1"></i>
                  Rút khỏi lớp
                </button>
              </template>

              <!-- Teacher/Admin Actions -->
              <template v-if="authStore.isAdmin || (authStore.isTeacher && cls.teacher_id === authStore.user?.id)">
                <router-link 
                  :to="`/classes/${cls.id}/edit`"
                  class="btn btn-outline-warning btn-sm action-btn"
                >
                  <i class="bi bi-pencil-square"></i>
                </router-link>
                <button 
                  @click="deleteClass(cls.id)"
                  class="btn btn-outline-danger btn-sm action-btn"
                >
                  <i class="bi bi-trash"></i>
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div class="empty-state animate-fade-in-up" v-else-if="!loading">
        <div class="empty-illustration">
          <i class="bi bi-mortarboard"></i>
        </div>
        <h3 class="empty-title">Không tìm thấy lớp học</h3>
        <p class="empty-message" v-if="authStore.isStudent">
          Chưa có lớp học nào phù hợp với bạn.
          <br><small>Hãy tìm kiếm hoặc đăng ký các lớp học mới.</small>
        </p>
        <p class="empty-message" v-else-if="authStore.isTeacher">
          Bạn chưa tạo lớp học nào.
          <br><small>Tạo lớp học đầu tiên để bắt đầu giảng dạy.</small>
        </p>
        <p class="empty-message" v-else>
          Hệ thống chưa có lớp học nào được tạo.
        </p>
        <router-link 
          v-if="authStore.isTeacher || authStore.isAdmin"
          to="/classes/create"
          class="btn btn-primary mt-3"
        >
          <i class="bi bi-plus-circle me-2"></i>
          Tạo lớp học đầu tiên
        </router-link>
      </div>

      <!-- Loading State -->
      <div class="loading-state" v-if="loading">
        <div class="loading-spinner">
          <div class="spinner-border text-primary"></div>
        </div>
        <p class="loading-text">Đang tải danh sách lớp học...</p>
      </div>

      <!-- Pagination -->
      <div v-if="classes.last_page > 1" class="pagination-wrapper mt-5">
        <nav class="pagination-nav">
          <ul class="pagination justify-content-center">
            <li class="page-item" :class="{ disabled: classes.current_page === 1 }">
              <button 
                @click="goToPage(1)"
                class="page-link"
                :disabled="classes.current_page === 1"
              >
                <i class="bi bi-chevron-double-left"></i>
              </button>
            </li>
            <li class="page-item" :class="{ disabled: classes.current_page === 1 }">
              <button 
                @click="goToPage(classes.current_page - 1)"
                class="page-link"
                :disabled="classes.current_page === 1"
              >
                <i class="bi bi-chevron-left"></i>
              </button>
            </li>
            <li 
              v-for="page in visiblePages" 
              :key="page" 
              class="page-item" 
              :class="{ active: page === classes.current_page }"
            >
              <button 
                v-if="typeof page === 'number'"
                @click="goToPage(page)" 
                class="page-link"
              >
                {{ page }}
              </button>
              <span v-else class="page-link">{{ page }}</span>
            </li>
            <li class="page-item" :class="{ disabled: classes.current_page === classes.last_page }">
              <button 
                @click="goToPage(classes.current_page + 1)"
                class="page-link"
                :disabled="classes.current_page === classes.last_page"
              >
                <i class="bi bi-chevron-right"></i>
              </button>
            </li>
            <li class="page-item" :class="{ disabled: classes.current_page === classes.last_page }">
              <button 
                @click="goToPage(classes.last_page)"
                class="page-link"
                :disabled="classes.current_page === classes.last_page"
              >
                <i class="bi bi-chevron-double-right"></i>
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const authStore = useAuthStore()

const classes = ref({ data: [], current_page: 1, last_page: 1, total: 0 })
const teachers = ref([])
const loading = ref(false)

const filters = reactive({
  search: '',
  teacher_id: '',
  status: '',
  per_page: 10 // default updated from 9 to 10
})

const classStatuses = [
  { value: '', label: 'Tất cả', icon: 'bi bi-grid-3x3-gap' },
  { value: 'upcoming', label: 'Sắp diễn ra', icon: 'bi bi-clock-history' },
  { value: 'ongoing', label: 'Đang diễn ra', icon: 'bi bi-play-circle' },
]

// Computed
const totalClasses = computed(() => classes.value.total || 0)
const totalStudents = computed(() => {
  return classes.value.data?.reduce((total, cls) => total + (cls.students?.length || 0), 0) || 0
})
const totalTeachers = computed(() => teachers.value.length)
const selectedTeacherLabel = computed(() => {
  if (!filters.teacher_id) return 'Tất cả giảng viên'
  const t = teachers.value.find(t => t.id === filters.teacher_id)
  return t ? t.name : 'Tất cả giảng viên'
})

const visiblePages = computed(() => {
  const pages = []
  const current = classes.value.current_page
  const last = classes.value.last_page
  
  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    } else if (current >= last - 3) {
      pages.push(1)
      pages.push('...')
      for (let i = last - 4; i <= last; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    }
  }
  
  return pages
})

// Methods
const fetchClasses = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page,
      per_page: filters.per_page
    })
    
    if (filters.search) params.append('search', filters.search)
    if (filters.teacher_id) params.append('teacher_id', filters.teacher_id)
    if (filters.status) params.append('status', filters.status)
    
    const response = await axios.get(`/classes?${params}`)
    classes.value = response.data
  } catch (error) {
    console.error('Failed to fetch classes:', error)
  } finally {
    loading.value = false
  }
}

const fetchTeachers = async () => {
  try {
    const response = await axios.get('/teachers')
    teachers.value = response.data
  } catch (error) {
    console.error('Failed to fetch teachers:', error)
  }
}

const selectTeacher = (id) => {
  filters.teacher_id = id || ''
  fetchClasses(1)
}

const enrollClass = async (classId) => {
  try {
    await axios.post(`/classes/${classId}/enroll`, {
      student_id: authStore.user.id
    })
    await fetchClasses(classes.value.current_page)
  } catch (error) {
    alert(error.response?.data?.error || 'Có lỗi xảy ra')
  }
}

const leaveClass = async (classId) => {
  if (!confirm('Bạn có chắc muốn rút khỏi lớp học này?')) return
  
  try {
    await axios.delete(`/classes/${classId}/students`, {
      data: { student_id: authStore.user.id }
    })
    await fetchClasses(classes.value.current_page)
  } catch (error) {
    alert(error.response?.data?.error || 'Có lỗi xảy ra')
  }
}

const deleteClass = async (classId) => {
  if (!confirm('Bạn có chắc muốn xóa lớp học này?')) return
  
  try {
    await axios.delete(`/classes/${classId}`)
    await fetchClasses(classes.value.current_page)
  } catch (error) {
    alert(error.response?.data?.error || 'Có lỗi xảy ra')
  }
}

const isEnrolled = (cls) => {
  return cls.students?.some(student => student.id === authStore.user?.id)
}

const getClassProgress = (cls) => {
  const today = new Date()
  const startDate = new Date(cls.start_date)
  const endDate = new Date(cls.end_date)
  
  if (today < startDate) return 0
  if (today > endDate) return 100
  
  const totalDuration = endDate - startDate
  const elapsed = today - startDate
  
  return Math.round((elapsed / totalDuration) * 100)
}

const getClassStatusColor = (cls) => {
  if (!cls?.start_date || !cls?.end_date) return 'status-upcoming'
  
  const today = new Date()
  today.setHours(0, 0, 0, 0) // Reset time to compare dates only
  
  const startDate = new Date(cls.start_date)
  startDate.setHours(0, 0, 0, 0)
  
  const endDate = new Date(cls.end_date)
  endDate.setHours(23, 59, 59, 999) // End of day
  
  if (today < startDate) return 'status-upcoming'
  if (today > endDate) return 'status-completed'
  return 'status-ongoing'
}

const getClassStatusIcon = (cls) => {
  if (!cls?.start_date || !cls?.end_date) return 'bi bi-clock-history'
  
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  const startDate = new Date(cls.start_date)
  startDate.setHours(0, 0, 0, 0)
  
  const endDate = new Date(cls.end_date)
  endDate.setHours(23, 59, 59, 999)
  
  if (today < startDate) return 'bi bi-clock-history'
  if (today > endDate) return 'bi bi-check-circle-fill'
  return 'bi bi-play-circle-fill'
}

const getClassStatusText = (cls) => {
  if (!cls?.start_date || !cls?.end_date) return 'Sắp diễn ra'
  
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  const startDate = new Date(cls.start_date)
  startDate.setHours(0, 0, 0, 0)
  
  const endDate = new Date(cls.end_date)
  endDate.setHours(23, 59, 59, 999)
  
  if (today < startDate) return 'Sắp diễn ra'
  if (today > endDate) return 'Đã hoàn thành'
  return 'Đang diễn ra'
}

const getProgressBarColor = (cls) => {
  const progress = getClassProgress(cls)
  if (progress === 0) return 'bg-secondary'
  if (progress === 100) return 'bg-success'
  return 'bg-primary'
}

const calculateDuration = (cls) => {
  const startDate = new Date(cls.start_date)
  const endDate = new Date(cls.end_date)
  const diffTime = Math.abs(endDate - startDate)
  const diffWeeks = Math.ceil(diffTime / (1000 * 60 * 60 * 24 * 7))
  return diffWeeks
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const goToPage = (page) => {
  fetchClasses(page)
}


const handleImageError = (event) => {
  event.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiByeD0iMjAiIGZpbGw9IiM2NjdFRUEiLz4KPHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiBzdHlsZT0icG9zaXRpb246IGFic29sdXRlOyB0b3A6IDUwJTsgbGVmdDogNTAlOyB0cmFuc2Zvcm06IHRyYW5zbGF0ZSgtNTAlLCAtNTAlKSI+CjxwYXRoIGQ9Ik0xMiAxMkM5Ljc5IDEyIDggMTMuNzkgOCAxNlYxOEMxMCAxOCAxNCAxOCAxNiAxOFYxNkMxNiAxMy43OSAxNC4yMSAxMiAxMiAxMloiIGZpbGw9IndoaXRlIi8+CjxjaXJjbGUgY3g9IjEyIiBjeT0iOCIgcj0iMyIgZmlsbD0id2hpdGUiLz4KPC9zdmc+Cjwvc3ZnPgo='
}

onMounted(() => {
  if (authStore.isTeacher) {
    // Force teacher_id to own id (backend already restricts but helps pagination/search consistency)
    filters.teacher_id = authStore.user?.id || ''
  }
  fetchClasses()
  if (authStore.isAdmin) {
    fetchTeachers()
  }
})
</script>

<style scoped>
/* Hero Section */
.hero-section {
  background: #101735;
  color: white;
  position: relative;
  overflow: hidden;
}

.hero-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="white" opacity="0.1"><polygon points="0,0 1000,100 1000,0"/></svg>');
  background-size: cover;
}

.hero-content {
  position: relative;
  z-index: 2;
}

.hero-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
  text-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.hero-subtitle {
  font-size: 1.25rem;
  opacity: 0.9;
  margin-bottom: 0;
}

.stats-row {
  display: flex;
  gap: 2rem;
}

.stat-item {
  text-align: center;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  display: block;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.8;
}

.create-btn {
  padding: 1rem 2rem;
  border-radius: var(--radius-xl);
  font-weight: 600;
  box-shadow: 0 8px 25px rgba(255,255,255,0.2);
}

/* Filter Tabs */
.filter-tabs {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 2rem;
}

.filter-tab {
  padding: 0.75rem 1.5rem;
  border: 2px solid transparent;
  border-radius: var(--radius-xl);
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(10px);
  color: var(--text-primary);
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-normal);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-tab:hover {
  background: rgba(102, 126, 234, 0.1);
  border-color: var(--primary-color);
  transform: translateY(-2px);
}

.filter-tab.active {
  background: var(--primary-color);
  color: white;
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

/* Compact Tabs inside filter card */
.compact-tabs { display:flex; flex-wrap:wrap; gap:.4rem; position:relative; z-index:4; }
.filter-tab.tab-sm { padding:.4rem .75rem; font-size:.65rem; border-radius:14px; box-shadow:none; background:rgba(255,255,255,0.6); }
.filter-tab.tab-sm i { font-size:.8rem; }
.filter-tab.tab-sm.active { 
  background: var(--primary-color); 
  color: #fff; 
  box-shadow:0 4px 14px rgba(102,126,234,.35); 
}
.filter-tab.tab-sm.active i { color: currentColor; }
.compact-filters { position:relative; z-index:5; }
.compact-filters .dropdown-menu { z-index:20; }
.compact-filters .dropdown-sm .btn-sm { line-height:1.1; }

/* Classes Grid */
.classes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.25rem; /* tighter */
  margin-bottom: 2rem;
}

.class-card-inner {
  position: relative;
  padding: .85rem .9rem; /* reduced */
  height: 100%;
  display: flex;
  flex-direction: column;
  border-radius: var(--radius-xl);
  transition: all var(--transition-normal);
}

.class-card-inner:hover {
  transform: translateY(-5px);
  box-shadow: 0 18px 36px rgba(0,0,0,0.12);
}

/* Status Badge */
.class-status-badge {
  position: absolute;
  top: .6rem;
  right: .6rem;
  padding: 0.35rem 0.55rem;
  border-radius: 14px;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.6rem;
  font-weight: 600;
  box-shadow: var(--shadow-sm);
}

.class-status-badge .status-text {
  white-space: nowrap;
}

.status-upcoming {
  background: rgba(251, 191, 36, 0.2);
  color: #fbbf24;
}

.status-ongoing {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
}

.status-completed {
  background: rgba(156, 163, 175, 0.2);
  color: rgba(12, 12, 12, 0.8);
}

/* Class Header */
.class-header {
  margin-bottom: 0.5rem;
}

.class-badges {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
  margin-bottom: .6rem;
}

.class-subject-badge {
  padding: 0.3rem 0.55rem;
  background: rgba(102, 126, 234, 0.1);
  color: var(--primary-color);
  border-radius: var(--radius-md);
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: .3px;
}

.class-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: .55rem;
  line-height: 1.3;
}

.class-description {
  color: var(--text-secondary);
  line-height: 1.4;
  margin-bottom: 0;
  font-size: .72rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Progress Section */
.progress-section {
  margin-bottom: 2rem;
}

.progress-info {
  display: flex;
  justify-content: between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.progress-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
}

.progress-percentage {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--primary-color);
}

.modern-progress {
  height: 8px;
  border-radius: 4px;
  background: rgba(0,0,0,0.1);
  overflow: hidden;
}

.modern-progress .progress-bar {
  height: 100%;
  border-radius: 4px;
  transition: width 0.6s ease;
}

/* Class Stats */
.class-stats {
  margin-bottom: 0.5rem;
}

.stat-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.stat-item {
  text-align: center;
  padding: 1rem;
  background: rgba(102, 126, 234, 0.05);
  border-radius: var(--radius-md);
  border: 1px solid rgba(102, 126, 234, 0.1);
}

.stat-item i {
  font-size: 1.25rem;
  margin-bottom: 0.5rem;
  display: block;
}

.stat-item span {
  display: block;
  font-weight: 700;
  font-size: 1.1rem;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.stat-item small {
  color: var(--text-secondary);
  font-size: 0.75rem;
  font-weight: 500;
}

/* Teacher Info */
.teacher-info {
  display: flex;
  align-items: center;
  gap: .65rem;
  margin-bottom: 1rem;
  padding: .55rem .6rem;
  background: rgba(102, 126, 234, 0.05);
  border-radius: var(--radius-md);
  border: 1px solid rgba(102, 126, 234, 0.1);
}

.teacher-avatar {
  width: 2.4rem;
  height: 2.4rem;
  border-radius: 50%;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.teacher-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.teacher-name {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.15rem;
  font-size: .74rem;
}

.teacher-title {
  font-size: 0.6rem;
  color: var(--text-secondary);
  letter-spacing: .5px;
  text-transform: uppercase;
}

/* Class Meta */
.class-meta {
  display: flex;
  gap: .75rem;
  margin-bottom: 1rem;
  padding-top: .6rem;
  border-top: 1px solid rgba(0,0,0,0.08);
}

.meta-item {
  display: flex;
  align-items: center;
  gap: .4rem;
  font-size: .65rem;
  color: var(--text-secondary);
  font-weight:500;
}

.meta-item i {
  color: var(--primary-color);
}

/* Class Actions */
.class-actions {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: .55rem;
  margin-top: auto;
}

.action-btn {
  border-radius: var(--radius-md);
  font-weight: 600;
  padding: .55rem .7rem;
  font-size:.7rem;
  transition: all var(--transition-normal);
}

.action-btn:hover {
  transform: translateY(-2px);
}

/* Search Wrapper */
.search-wrapper {
  position: relative;
}

.search-icon {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
}

/* Modern Form Elements */
.modern-input, .modern-select {
  border: 2px solid rgba(102, 126, 234, 0.1);
  border-radius: var(--radius-lg);
  padding: 0.875rem 1rem;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  transition: all var(--transition-normal);
}

.modern-input:focus, .modern-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
  background: white;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 5rem 2rem;
}

.empty-illustration i {
  font-size: 6rem;
  color: var(--text-light);
  margin-bottom: 2rem;
  display: block;
}

.empty-title {
  color: var(--text-primary);
  margin-bottom: 1rem;
}

.empty-message {
  color: var(--text-secondary);
  max-width: 400px;
  margin: 0 auto 2rem;
  line-height: 1.6;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 5rem 2rem;
}

.loading-spinner {
  margin-bottom: 2rem;
}

.loading-text {
  color: var(--text-secondary);
  font-size: 1.1rem;
}

/* Pagination */
.pagination-wrapper {
  display: flex;
  justify-content: center;
}

.pagination .page-link {
  border: 2px solid transparent;
  color: var(--text-primary);
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(10px);
  margin: 0 0.25rem;
  border-radius: var(--radius-md);
  padding: 0.75rem 1rem;
  font-weight: 600;
  transition: all var(--transition-normal);
}

.pagination .page-link:hover {
  background: var(--primary-color);
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.pagination .page-item.active .page-link {
  background: var(--primary-color);
  color: white;
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.pagination .page-item.disabled .page-link {
  opacity: 0.5;
  pointer-events: none;
}

/* Compact Filters */
.compact-filters { border-radius: 18px; }
.class-filter-row .form-label { margin-bottom:.25rem; }
.filter-label { font-size:.65rem; font-weight:600; text-transform:uppercase; letter-spacing:.6px; color:var(--text-secondary); }
.search-wrapper.compact input { padding:.55rem .75rem .55rem .85rem; font-size:.75rem; }
.input-sm { padding:.55rem .75rem !important; font-size:.75rem !important; }
.select-sm { padding:.55rem .75rem !important; font-size:.75rem !important; }
.dropdown-sm .btn-sm { padding:.45rem .6rem; font-size:.7rem; }
.dropdown-sm .dropdown-item { font-size:.7rem; padding:.4rem .75rem; }
.dropdown-sm .dropdown-item i { font-size:.75rem; }
.compact-classes .modern-input, .compact-classes .modern-select { padding:.6rem .75rem; }
.compact-classes .modern-input:focus, .compact-classes .modern-select:focus { box-shadow:0 0 0 3px rgba(102,126,234,0.15); }
.class-filter-row { --bs-gutter-x: .75rem; }

/* Responsive */
@media (max-width: 768px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .stats-row {
    justify-content: center;
    gap: 1rem;
  }
  
  .classes-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .filter-tabs {
    justify-content: center;
    flex-wrap: wrap;
  }
  
  .class-actions {
    grid-template-columns: 1fr;
  }
  
  .class-badges {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .class-status-badge {
    position: relative;
    top: auto;
    right: auto;
    margin-bottom: 1rem;
    align-self: flex-end;
  }
  
  .stat-row {
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
  }
}

@media (max-width: 480px) {
  .stat-row {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .class-meta {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>