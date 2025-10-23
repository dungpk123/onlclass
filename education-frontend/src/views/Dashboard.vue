<template>
  <div class="dashboard-container">
    <div class="container-fluid px-4 py-4">
      <!-- Welcome Header -->
      <div class="welcome-section m-5">
        <div class="row align-items-center">
          <div class="col">
            <div class="welcome-content">
              <h1 class="welcome-title">
                Xin chào, {{ authStore.user?.name }}!
              </h1>
              <p class="welcome-subtitle">
                {{ getWelcomeMessage() }}
              </p>
            </div>
          </div>
          <div class="col-auto">
            <div class="date-badge">
              {{ getCurrentDate() }}
            </div>
          </div>
        </div>
        <!-- Dashboard Toolbar -->
        <div class="dashboard-toolbar mt-4">
          <div class="toolbar-left">
            <label class="range-label" for="rangeSelect">Khoảng thời gian:</label>
            <select id="rangeSelect" v-model="range" @change="handleRangeChange" class="range-select" aria-label="Chọn khoảng thời gian">
              <option value="7">7 ngày</option>
              <option value="30">30 ngày</option>
              <option value="90">90 ngày</option>
              <option value="365">1 năm</option>
              <option value="all">Tất cả</option>
            </select>
            <span class="last-updated" v-if="lastUpdated" :title="formatDateTime(lastUpdated)"><i class="bi bi-clock-history me-1"></i>{{ formatTimeAgo(lastUpdated) }}</span>
          </div>
          <div class="toolbar-actions">
            <button class="btn-refresh" type="button" @click="refreshData" :disabled="loading" aria-label="Làm mới dữ liệu">
              <i class="bi" :class="loading ? 'bi-arrow-repeat spinner' : 'bi-arrow-clockwise'"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-grid m-5" aria-live="polite">
        <template v-if="loading">
          <div v-for="n in 4" :key="'skeleton-'+n" class="stat-card stat-card-skeleton" aria-hidden="true">
            <div class="stat-card-content">
              <div class="stat-icon skeleton-block"></div>
              <div class="stat-info w-100">
                <div class="skeleton-line w-50 mb-2"></div>
                <div class="skeleton-line w-75 mb-2"></div>
                <div class="skeleton-line w-40"></div>
              </div>
            </div>
          </div>
        </template>
      <template v-else-if="statistics">
        <!-- Admin Statistics -->
        <template v-if="authStore.isAdmin">
          <div class="stat-card stat-card-primary">
            <div class="stat-card-content">
              <div class="stat-icon">
                <i class="bi bi-people"></i>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ statistics.users?.total || 0 }}</div>
                <div class="stat-label">Tổng người dùng</div>
                <div class="stat-change positive">
                  <i class="bi bi-arrow-up"></i>
                  +{{ statistics.users?.new || 0 }} mới
                </div>
              </div>
            </div>
          </div>
          
          <div class="stat-card stat-card-success">
            <div class="stat-card-content">
              <div class="stat-icon">
                <i class="bi bi-book"></i>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ statistics.classes?.total || 0 }}</div>
                <div class="stat-label">Tổng lớp học</div>
                
                <!-- Active vs Total Classes Info -->
                <div class="stat-change" style="color:#38a169" v-if="statistics.classes?.active !== undefined">
                  <i class="bi bi-play-circle-fill"></i>
                  {{ statistics.classes.active }} đang hoạt động
                </div>
                
                <!-- Show total enrollments (more accurate for capacity calculation) -->
                <div class="stat-change positive" v-if="statistics.classes?.total_enrollments !== undefined">
                  <i class="bi bi-people-fill"></i>
                  {{ statistics.classes.total_enrollments }} lượt đăng ký
                </div>
                
                <!-- Show unique students for better understanding -->
                <div class="stat-change" style="color:#2b6cb0" v-if="statistics.classes?.enrolled_students !== undefined">
                  <i class="bi bi-person-check"></i>
                  {{ statistics.classes.enrolled_students }} học viên duy nhất
                </div>
                
                <!-- Students in active classes -->
                <div class="stat-change" style="color:#38a169" v-if="statistics.classes?.active_enrolled_students !== undefined">
                  <i class="bi bi-play-circle"></i>
                  {{ statistics.classes.active_enrolled_students }} học lớp đang mở
                </div>
                
                <!-- Capacity usage percentage -->
                <div class="stat-change" style="color:#4a5568" v-if="statistics.classes?.capacity_used_percent !== undefined && statistics.classes?.capacity_used_percent > 0">
                  <i class="bi bi-graph-up"></i>
                  {{ Number(statistics.classes.capacity_used_percent).toFixed(1) }}% lấp đầy sĩ số
                </div>
                
                <!-- Total capacity info -->
                <div class="stat-change" style="color:#718096; font-size: 0.8rem;" v-if="statistics.classes?.total_capacity !== undefined && statistics.classes?.total_capacity > 0">
                  <i class="bi bi-grid"></i>
                  {{ statistics.classes.used_capacity || 0 }}/{{ statistics.classes.total_capacity }} chỗ
                </div>
              </div>
            </div>
          </div>

          <div class="stat-card stat-card-warning">
            <div class="stat-card-content">
              <div class="stat-icon">
                <i class="bi bi-star"></i>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ statistics.evaluations?.total || 0 }}</div>
                <div class="stat-label">Tổng đánh giá</div>
                <div class="stat-change positive">
                  <i class="bi bi-trending-up"></i>
                  {{ (statistics.evaluations?.average_rating || 0).toFixed(1) }}/5
                </div>
              </div>
            </div>
          </div>

          <div class="stat-card stat-card-info">
            <div class="stat-card-content">
              <div class="stat-icon">
                <i class="bi bi-file-earmark"></i>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ statistics.documents?.total || 0 }}</div>
                <div class="stat-label">Tổng tài liệu</div>
                <div class="stat-change positive">
                  <i class="bi bi-cloud-arrow-up"></i>
                  {{ statistics.documents?.new || 0 }} mới
                </div>
              </div>
            </div>
          </div>
          <!-- Admin student growth mini cards (optional summary) -->
          <div class="stat-card stat-card-secondary" v-if="statistics.student_stats">
            <div class="stat-card-content">
              <div class="stat-icon">
                <i class="bi bi-bar-chart"></i>
              </div>
              <div class="stat-info">
                <div class="stat-number">
                  {{ statistics.student_stats.yearly?.reduce((a,c)=> a + (c.count||0), 0) || 0 }}
                </div>
                <div class="stat-label">Tổng Học Viên (5 năm)</div>
                <div class="stat-change positive" v-if="statistics.student_stats.monthly?.length">
                  <i class="bi bi-graph-up"></i>
                  {{ statistics.student_stats.monthly[statistics.student_stats.monthly.length -1].count || 0 }} tháng này
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- Teacher Statistics -->
        <template v-else-if="authStore.isTeacher">
          <div class="stat-card stat-card-primary">
            <div class="stat-card-content">
              <div class="stat-icon">
                <i class="bi bi-book"></i>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ statistics.classes?.total || 0 }}</div>
                <div class="stat-label">Lớp của tôi</div>
                <div class="stat-change positive">
                  <i class="bi bi-users"></i>
                  {{ statistics.students || 0 }} học viên
                </div>
                <div class="stat-change" style="color:#2b6cb0" v-if="statistics.classes?.completed !== undefined">
                  <i class="bi bi-journal-check"></i>
                  {{ statistics.classes.completed }} đã kết thúc
                </div>
              </div>
            </div>
          </div>

          <div class="stat-card stat-card-success">
            <div class="stat-card-content">
              <div class="stat-icon">
                <i class="bi bi-star"></i>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ statistics.evaluations?.total || 0 }}</div>
                <div class="stat-label">Đánh giá nhận</div>
                <div class="stat-change positive">
                  <i class="bi bi-award"></i>
                  {{ (statistics.evaluations?.average_rating || 0).toFixed(1) }}/5
                </div>
              </div>
            </div>
          </div>

          <div class="stat-card stat-card-warning">
            <div class="stat-card-content">
              <div class="stat-icon">
                <i class="bi bi-file-earmark"></i>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ statistics.documents?.uploaded || 0 }}</div>
                <div class="stat-label">Tài liệu đã upload</div>
                <div class="stat-change positive">
                  <i class="bi bi-cloud-arrow-up"></i>
                  Đã upload
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- Student Statistics -->
        <template v-else-if="authStore.isStudent">
          <div class="stat-card stat-card-primary">
            <div class="stat-card-content">
              <div class="stat-icon">
                <i class="bi bi-book"></i>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ statistics.classes?.enrolled || 0 }}</div>
                <div class="stat-label">Lớp đã đăng ký</div>
                <div class="stat-change positive">
                  <i class="bi bi-check-circle"></i>
                  {{ statistics.classes?.active || 0 }} đang học
                </div>
                <div class="stat-change" style="color:#2b6cb0" v-if="statistics.classes?.completed !== undefined">
                  <i class="bi bi-journal-check"></i>
                  {{ statistics.classes.completed }} đã học xong
                </div>
              </div>
            </div>
          </div>

          <div class="stat-card stat-card-success">
            <div class="stat-card-content">
              <div class="stat-icon">
                <i class="bi bi-star"></i>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ statistics.evaluations_given || 0 }}</div>
                <div class="stat-label">Đánh giá đã cho</div>
                <div class="stat-change positive">
                  <i class="bi bi-heart"></i>
                  Cảm ơn góp ý
                </div>
              </div>
            </div>
          </div>

          <div class="stat-card stat-card-info">
            <div class="stat-card-content">
              <div class="stat-icon">
                <i class="bi bi-file-earmark"></i>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ statistics.available_documents || 0 }}</div>
                <div class="stat-label">Tài liệu có thể xem</div>
                <div class="stat-change positive">
                  <i class="bi bi-download"></i>
                  Sẵn sàng tải
                </div>
              </div>
            </div>
          </div>
        </template>
      </template>
      </div>

      <!-- Content based on role -->
      <div class="row" v-if="authStore.isAdmin">
        <div class="col-12 mb-4" v-if="statistics?.student_stats">
          <div class="content-card">
            <div class="content-header"><h3 class="content-title"><i class="bi bi-bar-chart me-2"></i>Tăng trưởng Học viên</h3></div>
            <div class="content-body">
              <div class="row">
                <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                  <h6 class="mb-2">12 tháng gần đây</h6>
                  <ChartBar
                    :labels="monthlyStudentData.map(m=>m.label)"
                    :values="monthlyStudentData.map(m=>m.value)"
                    dataset-label="Học viên"
                    color="#2563eb"
                    :height="260"
                  />
                </div>
                <div class="col-12 col-xl-4">
                  <h6 class="mb-2">5 năm</h6>
                  <ChartBar
                    :labels="yearlyStudentData.map(y=>y.label)"
                    :values="yearlyStudentData.map(y=>y.value)"
                    dataset-label="Học viên"
                    color="#7c3aed"
                    :height="260"
                    :show-legend="false"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-8">
            <div class="content-card">
              <div class="content-header">
                <h3 class="content-title"><i class="bi bi-activity me-2"></i>Hoạt động gần đây</h3>
              </div>
              <div class="content-body">
                <div v-if="(recentClasses?.length || recentEnrollments?.length)" class="recent-inline mb-4">
                  <template v-for="item in [...recentClasses, ...recentEnrollments].slice(0,10)" :key="(item.id)+'-mix'">
                    <div class="inline-class-item">
                      <div class="activity-icon small-icon" :style="item.student_name ? 'background:linear-gradient(135deg,#38ef7d,#11998e)' : ''">
                        <i :class="item.student_name ? 'bi bi-person-plus' : 'bi bi-book'" />
                      </div>
                      <div class="inline-texts">
                        <span class="title" :title="item.class_name || item.name">{{ item.class_name || item.name }}</span>
                        <span class="meta" v-if="item.student_name">+ {{ item.student_name }}</span>
                        <span class="meta" v-else>{{ formatDate(item.created_at) }}</span>
                      </div>
                    </div>
                  </template>
                </div>
                <div v-else class="empty-state">
                  <i class="bi bi-inbox display-4 text-muted"></i>
                  <p class="text-muted">Chưa có hoạt động nào gần đây</p>
                </div>
                <h5 class="mb-3 mt-2" v-if="recentEvaluations?.length"><i class="bi bi-star-half me-2"></i>Đánh giá mới</h5>
                <div v-if="recentEvaluations?.length" class="activity-list">
                  <div v-for="ev in recentEvaluations" :key="ev.id" class="activity-item">
                    <div class="activity-icon" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><i class="bi bi-star"></i></div>
                    <div class="activity-content">
                      <h6 class="activity-title">{{ ev.class_room?.name || ev.classRoom?.name }}</h6>
                      <p class="activity-description mb-1">Giảng viên: {{ ev.teacher?.name }}</p>
                      <div class="small" style="color:#d69e2e"><i class="bi bi-star-fill me-1"></i>{{ Number(ev.rating).toFixed(1) }}/5</div>
                      <small class="activity-time">{{ formatDate(ev.created_at) }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <div class="col-lg-4">
            <div class="content-card">
              <div class="content-header"><h3 class="content-title"><i class="bi bi-gear me-2"></i>Quản lý nhanh</h3></div>
              <div class="content-body">
                <div class="action-buttons">
                  <router-link to="/users" class="btn btn-outline-primary"><i class="bi bi-people me-2"></i>Quản lý người dùng</router-link>
                  <router-link to="/classes" class="btn btn-outline-success"><i class="bi bi-book me-2"></i>Quản lý lớp học</router-link>
                  <router-link to="/documents" class="btn btn-outline-info"><i class="bi bi-file-earmark me-2"></i>Quản lý tài liệu</router-link>
                  <router-link to="/evaluations" class="btn btn-outline-warning"><i class="bi bi-star me-2"></i>Xem đánh giá</router-link>
                </div>
              </div>
            </div>
            <div class="content-card" v-if="topTeachers?.length">
              <div class="content-header"><h3 class="content-title"><i class="bi bi-trophy me-2"></i>Top Giảng viên</h3></div>
              <div class="content-body compact-list">
                <div v-for="(t,i) in topTeachers" :key="t.id" class="mini-item">
                  <div class="avatar-rank">
                    <span class="avatar-letter">{{ (t.name||'?').charAt(0).toUpperCase() }}</span>
                    <span class="rank-badge">#{{ i+1 }}</span>
                  </div>
                  <div class="mini-texts">
                    <span class="name" :title="t.name">{{ t.name }}</span>
                    <span class="meta">{{ Number(t.received_evaluations_avg_rating || 0).toFixed(1) }}/5 · {{ t.received_evaluations_count }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="content-card" v-if="recentUsers?.length">
              <div class="content-header"><h3 class="content-title"><i class="bi bi-person-plus me-2"></i>Người dùng mới</h3></div>
              <div class="content-body compact-list">
                <div v-for="u in recentUsers" :key="u.id" class="mini-item">
                  <div class="avatar-small">{{ (u.name||'?').charAt(0).toUpperCase() }}</div>
                  <div class="mini-texts">
                    <span class="name" :title="u.name">{{ u.name }}</span>
                    <span class="meta">{{ getRoleDisplayName(u.role?.name) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
      </div>
      <div class="row" v-else>
          <div class="col-lg-8">
            <div class="content-card">
              <div class="content-header"><h3 class="content-title"><i class="bi bi-book me-2"></i>{{ authStore.isTeacher ? 'Lớp học của tôi' : 'Lớp đã tham gia' }}</h3></div>
              <div class="content-body">
                <div v-if="myClasses?.length" class="class-grid">
                  <div v-for="classItem in myClasses" :key="classItem.id" class="class-card">
                    <div class="class-header">
                      <div class="class-title-section">
                        <h6 class="class-title">{{ classItem.name }}</h6>
                        <div class="class-badges">
                          <span class="class-badge">{{ classItem.subject }}</span>
                          <span class="status-badge" :class="getClassStatusClass(classItem)"><i :class="getClassStatusIcon(classItem)"></i>{{ getClassStatusText(classItem) }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="class-body">
                      <p class="class-description">{{ classItem.description || 'Không có mô tả' }}</p>
                      <div class="class-stats">
                        <div class="stat-item"><i class="bi bi-people"></i><span>{{ classItem.students?.length || 0 }} học viên</span></div>
                        <div class="stat-item"><i class="bi bi-file-earmark"></i><span>{{ classItem.documents?.length || 0 }} tài liệu</span></div>
                      </div>
                    </div>
                    <div class="class-footer">
                      <router-link :to="`/classes/${classItem.id}`" class="btn btn-sm btn-primary">Xem chi tiết</router-link>
                    </div>
                  </div>
                </div>
                <div v-else class="empty-state">
                  <i class="bi bi-book display-4 text-muted"></i>
                  <p class="text-muted">{{ authStore.isTeacher ? 'Chưa có lớp học nào' : 'Chưa tham gia lớp học nào' }}</p>
                  <router-link to="/classes" class="btn btn-primary">{{ authStore.isTeacher ? 'Tạo lớp học' : 'Tham gia lớp học' }}</router-link>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="content-card">
              <div class="content-header"><h3 class="content-title"><i class="bi bi-lightning me-2"></i>Thao tác nhanh</h3></div>
              <div class="content-body">
                <div class="action-buttons">
                  <template v-if="authStore.isTeacher">
                    <router-link to="/classes/create" class="btn btn-outline-primary"><i class="bi bi-plus-circle me-2"></i>Tạo lớp học</router-link>
                    <router-link to="/documents" class="btn btn-outline-success"><i class="bi bi-upload me-2"></i>Tải lên tài liệu</router-link>
                    <router-link to="/evaluations" class="btn btn-outline-info"><i class="bi bi-star me-2"></i>Xem đánh giá</router-link>
                  </template>
                  <template v-else>
                    <router-link to="/classes" class="btn btn-outline-primary"><i class="bi bi-search me-2"></i>Tìm lớp học</router-link>
                    <router-link to="/documents" class="btn btn-outline-success"><i class="bi bi-file-earmark me-2"></i>Xem tài liệu</router-link>
                    <router-link to="/evaluations" class="btn btn-outline-warning"><i class="bi bi-star me-2"></i>Đánh giá giảng viên</router-link>
                  </template>
                </div>
              </div>
            </div>
          </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import BarChart from '@/components/charts/BarChart.vue'
import ChartBar from '@/components/charts/ChartBar.vue'

const authStore = useAuthStore()

const statistics = ref(null)
const recentClasses = ref([])
const recentEnrollments = ref([])
const recentEvaluations = ref([])
const topTeachers = ref([])
const recentUsers = ref([])
const myClasses = ref([])
const loading = ref(false)
const range = ref('30')
const lastUpdated = ref(null)

const getWelcomeMessage = () => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Chúc bạn một ngày làm việc hiệu quả!'
  if (hour < 18) return 'Chúc bạn buổi chiều tốt lành!'
  return 'Chúc bạn buổi tối vui vẻ!'
}

const getCurrentDate = () => {
  return new Date().toLocaleDateString('vi-VN', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('vi-VN')
}

const getClassStatusClass = (classItem) => {
  if (!classItem?.start_date || !classItem?.end_date) return 'status-upcoming'
  
  const today = new Date()
  today.setHours(0, 0, 0, 0) // Reset time to compare dates only
  
  const startDate = new Date(classItem.start_date)
  startDate.setHours(0, 0, 0, 0)
  
  const endDate = new Date(classItem.end_date)
  endDate.setHours(23, 59, 59, 999) // End of day
  
  if (today < startDate) return 'status-upcoming'
  if (today > endDate) return 'status-completed'
  return 'status-ongoing'
}

const getClassStatusIcon = (classItem) => {
  if (!classItem?.start_date || !classItem?.end_date) return 'bi bi-clock-history'
  
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  const startDate = new Date(classItem.start_date)
  startDate.setHours(0, 0, 0, 0)
  
  const endDate = new Date(classItem.end_date)
  endDate.setHours(23, 59, 59, 999)
  
  if (today < startDate) return 'bi bi-clock-history'
  if (today > endDate) return 'bi bi-check-circle-fill'
  return 'bi bi-play-circle-fill'
}

const getClassStatusText = (classItem) => {
  if (!classItem?.start_date || !classItem?.end_date) return 'Sắp diễn ra'
  
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  const startDate = new Date(classItem.start_date)
  startDate.setHours(0, 0, 0, 0)
  
  const endDate = new Date(classItem.end_date)
  endDate.setHours(23, 59, 59, 999)
  
  if (today < startDate) return 'Sắp diễn ra'
  if (today > endDate) return 'Đã hoàn thành'
  return 'Đang diễn ra'
}

const calculateDuration = (classItem) => {
  if (!classItem?.start_date || !classItem?.end_date) return 0
  const startDate = new Date(classItem.start_date)
  const endDate = new Date(classItem.end_date)
  const diffTime = Math.abs(endDate - startDate)
  const diffWeeks = Math.ceil(diffTime / (1000 * 60 * 60 * 24 * 7))
  return diffWeeks
}

const fetchDashboardData = async () => {
  loading.value = true
  try {
    const params = range.value && range.value !== 'all' ? { params: { range: range.value } } : {}
    const response = await axios.get('/dashboard', params)
    const data = response.data
    
    console.log('Dashboard data received:', data)
    console.log('Statistics:', data.statistics)
    console.log('Classes statistics:', data.statistics?.classes)
    console.log('User role:', authStore.user?.role?.name)
    
    // Debug: Log specific class statistics
    if (data.statistics?.classes) {
      console.log('Classes breakdown:', {
        total: data.statistics.classes.total,
        active: data.statistics.classes.active,
        enrolled_students: data.statistics.classes.enrolled_students,
        active_enrolled_students: data.statistics.classes.active_enrolled_students,
        capacity_used_percent: data.statistics.classes.capacity_used_percent,
        total_capacity: data.statistics.classes.total_capacity,
        used_capacity: data.statistics.classes.used_capacity
      })
    }
    
    statistics.value = data.statistics
    
    if (authStore.isAdmin) {
  recentClasses.value = data.recent_classes || []
  recentEnrollments.value = data.recent_enrollments || []
      recentEvaluations.value = data.recent_evaluations || []
      topTeachers.value = data.top_teachers || []
      recentUsers.value = data.recent_users || []
    } else if (authStore.isTeacher || authStore.isStudent) {
      myClasses.value = data.my_classes || []
    }
    lastUpdated.value = new Date()
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
  } finally {
    loading.value = false
  }
}

const refreshData = () => {
  fetchDashboardData()
}

const handleRangeChange = () => {
  fetchDashboardData()
}

const formatDateTime = (dt) => new Date(dt).toLocaleString('vi-VN')
const formatTimeAgo = (dt) => {
  if (!dt) return ''
  const diff = (Date.now() - dt.getTime()) / 1000
  if (diff < 60) return 'vừa xong'
  if (diff < 3600) return Math.floor(diff/60) + ' phút trước'
  if (diff < 86400) return Math.floor(diff/3600) + ' giờ trước'
  return Math.floor(diff/86400) + ' ngày trước'
}

// Convert role names from English to Vietnamese
const getRoleDisplayName = (roleName) => {
  const roleMap = {
    'admin': 'Quản trị viên',
    'teacher': 'Giảng viên',
    'student': 'Học viên'
  }
  return roleMap[roleName] || roleName || 'Chưa xác định'
}

onMounted(() => {
  fetchDashboardData()
})

// Chart datasets (reactive)
const monthlyStudentData = computed(() => {
  const arr = statistics.value?.student_stats?.monthly || []
  return arr.map(m => ({ label: m.month, value: m.count }))
})
const yearlyStudentData = computed(() => {
  const arr = statistics.value?.student_stats?.yearly || []
  return arr.map(y => ({ label: y.year, value: y.count }))
})
</script>

<style scoped>
/* Modern Dashboard Styles */
.dashboard-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  background-attachment: fixed;
}

.welcome-section {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 24px;
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.welcome-title { font-size:1.6rem; font-weight:700; color: var(--text-primary,#2d3748); margin-bottom:.5rem; text-shadow:0 2px 4px rgba(0,0,0,0.1); }
.welcome-subtitle { font-size:0.8rem; color: var(--text-muted,#718096); margin:0; }

.date-badge {
  background: rgba(255, 255, 255, 0.2);
  color: #718096;
  padding: 0.75rem 1.5rem;
  border-radius: 50px;
  font-weight: 600;
  border: 1px solid rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(10px);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); /* narrower cards */
  gap: 1rem; /* tighter spacing */
  margin-top: .5rem;
}
/* Skeleton */
.skeleton-block, .skeleton-line { background:linear-gradient(90deg,#e2e8f0 25%,#f1f5f9 50%,#e2e8f0 75%); background-size:400% 100%; animation:skl 1.4s ease-in-out infinite; }
.skeleton-block { width:64px; height:64px; border-radius:16px; }
.skeleton-line { height:12px; border-radius:6px; }
.skeleton-line.mb-2 { margin-bottom:8px; }
.w-50 { width:50%; }
.w-75 { width:75%; }
.w-40 { width:40%; }
@keyframes skl { 0% { background-position:100% 50%; } 100% { background-position:0 50%; } }

.stat-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  padding: 0;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.2);
}

.stat-card-content {
  padding: 1.15rem 1.1rem; /* reduced padding */
  display: flex;
  align-items: center;
  gap: 1rem; /* tighter inner gap */
}

.stat-icon {
  width: 54px; /* was 64 */
  height: 54px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.45rem; /* smaller icon */
  color: white;
  flex-shrink: 0;
}

.stat-card-primary .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-card-success .stat-icon { background: linear-gradient(135deg, #11998e, #38ef7d); }
.stat-card-warning .stat-icon { background: linear-gradient(135deg, #f093fb, #f5576c); }
.stat-card-info .stat-icon { background: linear-gradient(135deg, #4facfe, #00f2fe); }

.stat-info {
  flex: 1;
}

.stat-number {
  font-size: 1.3rem; /* smaller */
  font-weight: 700;
  color: #2d3748;
  line-height: 1;
  margin-bottom: 0.2rem;
}

.stat-label {
  font-size: 0.75rem; /* smaller label */
  color: #718096;
  margin-bottom: 0.35rem;
  font-weight: 600;
  letter-spacing: .5px;
  text-transform: uppercase;
}

.stat-change {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.7rem; /* smaller */
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 12px;
  background: rgba(0,0,0,0.04);
}

.stat-change.positive {
  color: #38a169;
}

/* Content Cards */
.content-card {
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(0, 0, 0, 0.05);
  border-radius: 18px;
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  margin-bottom: 1.5rem; /* tighter */
}

.content-header {
  padding: 0.9rem 1.25rem; /* reduced */
  border-bottom: 1px solid rgba(0, 0, 0, 0.04);
  background: rgba(255, 255, 255, 0.55);
}

.content-title {
  font-size: 1.05rem; /* smaller */
  font-weight: 600;
  color: #2d3748;
  margin: 0;
  display: flex;
  align-items: center;
  letter-spacing: .3px;
}

.content-body {
  padding: 1.15rem 1.25rem; /* reduced */
}

/* Activity List */
.activity-list {
  display: flex;
  flex-direction: column;
  gap: 0.65rem; /* tighter */
}

.activity-item {
  display: flex;
  gap: 0.75rem; /* tighter */
  padding: 0.65rem 0.75rem; /* reduced */
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.55);
  transition: all 0.25s ease;
}

.activity-item:hover {
  background: rgba(255, 255, 255, 0.85);
  transform: translateX(3px);
}

.activity-icon {
  width: 34px; /* smaller */
  height: 34px;
  border-radius: 8px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
  font-size: 0.9rem;
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.15rem;
  font-size: 0.8rem; /* smaller */
}

.activity-description { color: var(--text-muted,#718096); margin-bottom:0.2rem; font-size:0.7rem; line-height:1.2; }

.activity-time {
  color: #a0aec0;
  font-size: 0.6rem; /* smaller */
  letter-spacing:.5px;
  text-transform: uppercase;
  font-weight:500;
}

/* Inline recent classes */
.recent-inline {
  display: flex;
  gap: .85rem;
  flex-wrap: nowrap;
  overflow-x: auto;
  padding-bottom: .25rem;
  scrollbar-width: thin;
}
.recent-inline::-webkit-scrollbar { height:6px; }
.recent-inline::-webkit-scrollbar-track { background: transparent; }
.recent-inline::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 10px; }
.inline-class-item {
  display: flex;
  align-items: center;
  gap: .55rem;
  background: rgba(255,255,255,0.55);
  border:1px solid rgba(0,0,0,0.05);
  padding: .5rem .7rem;
  border-radius: 12px;
  flex-shrink: 0;
  max-width: 220px;
  transition:.25s;
}
.inline-class-item:hover { background: rgba(255,255,255,0.85); }
.small-icon { width:30px; height:30px; font-size:.85rem; }
.inline-texts { display:flex; flex-direction:column; min-width:0; }
.inline-texts .title { font-size:.72rem; font-weight:600; color:#2d3748; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.inline-texts .meta { font-size:.55rem; color:#718096; letter-spacing:.5px; text-transform:uppercase; }

/* Compact lists (top teachers & recent users) */
.compact-list { display:flex; flex-direction:column; gap:.55rem; padding:.2rem 0; }
.mini-item { display:flex; align-items:center; gap:.6rem; background:rgba(255,255,255,0.55); border:1px solid rgba(0,0,0,0.05); padding:.45rem .6rem; border-radius:12px; transition:.25s; }
.mini-item:hover { background:rgba(255,255,255,0.85); }
.avatar-rank { position:relative; width:38px; height:38px; border-radius:14px; background:linear-gradient(135deg,#667eea,#764ba2); display:flex; align-items:center; justify-content:center; font-weight:600; color:#fff; font-size:.8rem; }
.avatar-rank .rank-badge { position:absolute; bottom:-6px; right:-6px; background:#f6ad55; color:#2d3748; font-size:.55rem; padding:2px 5px; border-radius:8px; font-weight:600; box-shadow:0 2px 6px rgba(0,0,0,0.15); }
.avatar-small { width:32px; height:32px; border-radius:10px; background:#eef2ff; display:flex; align-items:center; justify-content:center; font-weight:600; color:#4c51bf; font-size:.75rem; }
.mini-texts { display:flex; flex-direction:column; min-width:0; }
.mini-texts .name { font-size:.72rem; font-weight:600; color:#2d3748; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mini-texts .meta { font-size:.6rem; color:#718096; letter-spacing:.5px; text-transform:uppercase; font-weight:500; }

/* Class Grid */
.class-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.class-card { background: rgba(255,255,255,0.9); border-radius:16px; padding:1.5rem; transition:.3s; border:1px solid rgba(0,0,0,0.05); box-shadow:0 4px 14px rgba(0,0,0,0.08); }
.class-card:hover { background:#ffffff; transform:translateY(-4px); box-shadow:0 10px 28px rgba(0,0,0,0.15); }

.class-header {
  margin-bottom: 1rem;
}

.class-title-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.class-title {
  font-weight: 600;
  color: #2d3748;
  margin: 0;
  flex-grow: 1;
  margin-right: 1rem;
}

.class-badges {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.class-badge {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.status-badge.status-upcoming {
  background: rgba(251, 191, 36, 0.2);
  color: #fbbf24;
}

.status-badge.status-ongoing {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
}

.status-badge.status-completed {
  background: rgba(156, 163, 175, 0.2);
  color: rgba(12, 12, 12, 0.8);
}

.class-description { color: var(--text-muted,#718096); font-size:0.8rem; margin-bottom:1rem; line-height:1.4; }

.class-stats {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  color: #718096;
}

.class-footer {
  padding-top: 1rem;
  border-top: 1px solid rgba(0, 0, 0, 0.05);
}

/* Action Buttons */
.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  border-radius: 12px;
  transition: all 0.3s ease;
  text-decoration: none;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  border: 2px solid transparent;
}

.btn-outline-primary {
  color: #667eea;
  border-color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.btn-outline-primary:hover {
  background: #667eea;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.btn-outline-success {
  color: #38a169;
  border-color: #38a169;
  background: rgba(56, 161, 105, 0.05);
}

.btn-outline-success:hover {
  background: #38a169;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(56, 161, 105, 0.3);
}

.btn-outline-info {
  color: #3182ce;
  border-color: #3182ce;
  background: rgba(49, 130, 206, 0.05);
}

.btn-outline-info:hover {
  background: #3182ce;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
}

.btn-outline-warning {
  color: #d69e2e;
  border-color: #d69e2e;
  background: rgba(214, 158, 46, 0.05);
}

.btn-outline-warning:hover {
  background: #d69e2e;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(214, 158, 46, 0.3);
}

.btn-primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
}

.empty-state i {
  margin-bottom: 1rem;
}

.empty-state p {
  font-size: 1.1rem;
  margin-bottom: 1.5rem;
}

/* Toolbar */
.dashboard-toolbar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; padding:0.75rem 1rem; background:rgba(255,255,255,0.6); border:1px solid rgba(0,0,0,0.05); border-radius:16px; }
.range-select { padding:0.45rem 0.75rem; border-radius:8px; border:1px solid #cbd5e0; font-size:0.85rem; background:white; }
.range-select:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,0.25); }
.range-label { font-size:0.7rem; text-transform:uppercase; letter-spacing:.05em; font-weight:600; color:var(--text-muted,#718096); }
.last-updated { font-size:0.65rem; color:var(--text-muted,#718096); background:rgba(0,0,0,0.04); padding:0.35rem 0.6rem; border-radius:50px; display:inline-flex; align-items:center; }
.btn-refresh { background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; border:none; width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 6px 18px rgba(102,126,234,0.4); transition:.3s; }
.btn-refresh:hover { transform:translateY(-3px); }
.btn-refresh:disabled { opacity:.6; cursor:not-allowed; }
.btn-refresh .spinner { animation:spin 1s linear infinite; }
@keyframes spin { from { transform:rotate(0deg);} to { transform:rotate(360deg);} }

/* Responsive Design */
@media (max-width: 768px) {
  .welcome-title {
    font-size: 2rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
    justify-content: center;
  }
  
  .class-grid {
    grid-template-columns: 1fr;
  }
  
  .class-title-section {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .class-badges {
    flex-direction: row;
    align-items: flex-start;
    margin-top: 0.5rem;
  }
  
  .class-stats {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>