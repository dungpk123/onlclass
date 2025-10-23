<template>
  <div class="modern-class-detail-container">
    <div v-if="classDetail">
      <!-- Hero Section -->
      <div class="hero-section">
        <div class="hero-background">
          <div class="hero-gradient"></div>
          <div class="hero-pattern"></div>
        </div>
        <div class="container">
          <div class="row align-items-start">
            <div class="col-lg-7">
              <div class="hero-content animate-fade-in-up">
                <div class="breadcrumb-nav mb-4">
                  <router-link to="/classes" class="breadcrumb-link">
                    <i class="bi bi-arrow-left me-2"></i>
                    Quay lại danh sách lớp học
                  </router-link>
                </div>
                <div class="class-meta-header mb-4">
                  <span class="subject-badge">
                    <i class="bi bi-book me-1"></i>
                    {{ classDetail.subject }}
                  </span>
                  <span class="status-badge" :class="getClassStatusClass()">
                    <i :class="getClassStatusIcon()"></i>
                    {{ getClassStatusText() }}
                  </span>
                </div>
                <h1 class="hero-title">{{ classDetail.name }}</h1>
                <p class="hero-subtitle mb-4">
                  {{ classDetail.description || 'Khám phá kiến thức mới cùng giảng viên chuyên nghiệp' }}
                </p>
                <div class="hero-stats mb-4">
                  <div class="stat-item">
                    <i class="bi bi-people-fill"></i>
                    <span>{{ classDetail.students?.length || 0 }} học viên</span>
                  </div>
                  <div class="stat-divider"></div>
                  <div class="stat-item">
                    <i class="bi bi-person-badge"></i>
                    <span>{{ classDetail.teacher?.name }}</span>
                  </div>
                  <div class="stat-divider"></div>
                  <div class="stat-item">
                    <i class="bi bi-calendar-event"></i>
                    <span>{{ formatDate(classDetail.created_at) }}</span>
                  </div>
                </div>
                <div class="teacher-info">
                  <div class="teacher-avatar">
                    <img
                      :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(classDetail.teacher?.name || 'Unknown')"
                      :alt="classDetail.teacher?.name"
                      @error="handleImageError"
                    >
                  </div>
                  <div class="teacher-details">
                    <div class="teacher-name">{{ classDetail.teacher?.name }}</div>
                    <div class="teacher-title">Giảng viên hướng dẫn</div>
                  </div>
                </div>

                <!-- Inline Evaluation Form (moved here) -->
                <div
                  v-if="authStore.isStudent && isEnrolled"
                  class="evaluation-inline mt-4 animate-fade-in-up"
                  style="animation-delay: .1s"
                >
                  <div class="glass-card p-4" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3);">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h5 class="mb-0 text-white">
                        <i class="bi bi-star-half me-2"></i>{{ myEvaluation ? 'Chỉnh sửa đánh giá của bạn' : 'Đánh giá giảng viên' }}
                      </h5>
                      <span v-if="myEvaluation" class="badge bg-success">Đã gửi</span>
                    </div>
                    <form @submit.prevent="submitEvaluation" class="row g-3">
                      <div class="col-md-4">
                        <label class="form-label text-white-50">Đánh giá</label>
                        <div class="star-rating d-flex align-items-center">
                          <i
                            v-for="n in 5"
                            :key="n"
                            class="bi mx-1 star"
                            :class="[
                              (hoverStar && n <= hoverStar) || (!hoverStar && n <= starRating) ? 'bi-star-fill active' : 'bi-star',
                              (hoverStar && n <= hoverStar) ? 'hovered' : ''
                            ]"
                            @mouseenter="enterStar(n)"
                            @mouseleave="leaveStars"
                            @click="selectStar(n)"
                            role="button"
                            aria-label="Đánh giá {{ n }} sao"
                          ></i>
                        </div>
                        <div class="small text-light mt-2">{{ starRating }} / 5 sao</div>
                      </div>
                      <div class="col-md-8">
                        <label class="form-label text-white-50">Nhận xét</label>
                        <textarea
                          v-model="evaluationForm.comment"
                          rows="3"
                          class="form-control form-control-sm"
                          placeholder="Viết cảm nhận của bạn..."
                        ></textarea>
                      </div>
                      <div class="col-12 d-flex align-items-center">
                        <button class="btn btn-sm btn-primary me-2" type="submit">
                          <i class="bi" :class="myEvaluation ? 'bi-save' : 'bi-send'"></i>
                          <span class="ms-1">{{ myEvaluation ? 'Cập nhật' : 'Gửi đánh giá' }}</span>
                        </button>
                        <button
                          v-if="myEvaluation"
                          type="button"
                          class="btn btn-sm btn-outline-light"
                          @click="fetchMyEvaluation()"
                        >
                          <i class="bi bi-arrow-clockwise me-1"></i>
                          Tải lại
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
                <!-- End Inline Evaluation Form -->
                <!-- Quick Stats Bar (Compact) -->
                <div class="quick-stats-bar mt-3">
                  <div class="qs-item">
                    <i class="bi bi-people-fill"></i>
                    <span>{{ classDetail.students?.length || 0 }}/{{ classDetail.capacity }} HV</span>
                  </div>
                  <div class="qs-sep"></div>
                  <div class="qs-item">
                    <i class="bi bi-hourglass-split" v-if="getClassStatus() === 'upcoming'"></i>
                    <i class="bi bi-play-circle" v-else-if="getClassStatus() === 'ongoing'"></i>
                    <i class="bi bi-check-circle" v-else></i>
                    <span>{{ getClassStatusText() }}</span>
                  </div>
                  <div class="qs-sep" v-if="classDetail.capacity"></div>
                  <div v-if="classDetail.capacity" class="qs-item">
                    <i class="bi bi-box"></i>
                    <span>{{ classDetail.capacity - (classDetail.students?.length || 0) }} trống</span>
                  </div>
                </div>
                <!-- End Quick Stats Bar -->
              </div>
            </div>
            <div class="col-lg-5" style="position: relative; z-index: 10;">
              <div class="enrollment-card glass-card animate-fade-in-right">
                <div class="card-header-modern">
                  <h3 class="card-title">
                    <i class="bi bi-person-lines-fill me-2"></i>
                    Thông tin đăng ký
                  </h3>
                </div>

                <div class="enrollment-stats-modern">
                  <div class="stat-card">
                    <div class="stat-icon">
                      <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-content">
                      <div class="stat-number">{{ classDetail.students?.length || 0 }}</div>
                      <div class="stat-label">Học viên</div>
                    </div>
                  </div>
                  <div class="stat-card">
                    <div class="stat-icon">
                      <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div class="stat-content">
                      <div class="stat-number">{{ classDetail.capacity }}</div>
                      <div class="stat-label">Sĩ số tối đa</div>
                    </div>
                  </div>
                </div>

                <div class="progress-section-modern">
                  <div class="progress-header">
                    <span class="progress-label">Tiến độ đăng ký</span>
                    <span class="progress-percentage">{{ getEnrollmentPercentage() }}%</span>
                  </div>
                  <div class="progress-container">
                    <div class="progress modern-progress">
                      <div
                        class="progress-bar"
                        :class="getProgressBarClass()"
                        :style="{ width: getEnrollmentPercentage() + '%' }"
                      ></div>
                    </div>
                  </div>
                  <div class="progress-text">
                    {{ classDetail.capacity - (classDetail.students?.length || 0) }} chỗ trống còn lại
                  </div>
                </div>

                <div class="enrollment-actions-modern">
                  <template v-if="authStore.isStudent">
                    <div v-if="!isEnrolled" class="action-section">
                      <button
                        @click="enrollClass"
                        class="btn btn-primary btn-lg action-btn-primary"
                        :disabled="classDetail.students?.length >= classDetail.capacity"
                      >
                        <i class="bi bi-person-plus-fill me-2"></i>
                        {{ classDetail.students?.length >= classDetail.capacity ? 'Lớp đã đầy' : 'Tham gia ngay' }}
                        <i class="bi bi-arrow-right ms-2"></i>
                      </button>
                      <p class="action-note">
                        <i class="bi bi-info-circle me-1"></i>
                        Tham gia để xem danh sách học viên và tài liệu
                      </p>
                    </div>
                    <div v-else class="action-section">
                      <button
                        @click="leaveClass"
                        class="btn btn-outline-warning btn-lg action-btn-secondary"
                      >
                        <i class="bi bi-box-arrow-left me-2"></i>
                        Rời lớp học
                      </button>
                    </div>
                  </template>

                  <template v-else-if="canManageClass">
                    <div class="action-section">
                      <div class="btn-group-vertical w-100">
                        <router-link
                          :to="`/classes/${classDetail.id}/edit`"
                          class="btn btn-outline-primary action-btn-outline"
                        >
                          <i class="bi bi-pencil-square me-2"></i>
                          Chỉnh sửa lớp học
                        </router-link>
                        <button
                          @click="deleteClass"
                          class="btn btn-outline-danger action-btn-outline"
                        >
                          <i class="bi bi-trash me-2"></i>
                          Xóa lớp học
                        </button>
                      </div>
                    </div>
                  </template>

                  <template v-else>
                    <div class="login-prompt">
                      <i class="bi bi-person-circle"></i>
                      <h5>Đăng nhập để tham gia</h5>
                      <p>Đăng nhập tài khoản học viên để tham gia lớp học này</p>
                    </div>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="container-fluid px-4 pb-5 main-content-section">
        <!-- Class Information -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="glass-card animate-fade-in-up" style="animation-delay: 0.1s">
              <div class="card-header-modern">
                <h5 class="section-title">
                  <i class="bi me-2"></i>
                  Thông tin chi tiết lớp học
                </h5>
                <p class="section-subtitle">Thông tin chi tiết</p>
              </div>
              <div class="card-body">
                <div class="info-grid-modern">
                  <div class="info-card">
                    <div class="info-card-icon">
                      <i class="bi bi-calendar-event-fill"></i>
                    </div>
                    <div class="info-card-content">
                      <div class="info-card-label">Ngày bắt đầu</div>
                      <div class="info-card-value">{{ formatDate(classDetail.start_date) }}</div>
                    </div>
                  </div>

                  <div class="info-card">
                    <div class="info-card-icon">
                      <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <div class="info-card-content">
                      <div class="info-card-label">Ngày kết thúc</div>
                      <div class="info-card-value">{{ formatDate(classDetail.end_date) }}</div>
                    </div>
                  </div>

                  <div class="info-card">
                    <div class="info-card-icon">
                      <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="info-card-content">
                      <div class="info-card-label">Sĩ số lớp học</div>
                      <div class="info-card-value">{{ classDetail.students?.length || 0 }}/{{ classDetail.capacity }}</div>
                      <div class="info-card-subtext">
                        {{ classDetail.capacity - (classDetail.students?.length || 0) }} chỗ trống
                      </div>
                    </div>
                  </div>

                  <div class="info-card">
                    <div class="info-card-icon">
                      <i class="bi bi-clock-fill"></i>
                    </div>
                    <div class="info-card-content">
                      <div class="info-card-label">Trạng thái lớp học</div>
                      <div class="info-card-value">
                        <span class="status-pill" :class="getClassStatusClass()">
                          <i :class="getClassStatusIcon()"></i>
                          {{ getClassStatusText() }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="info-card" v-if="classDetail.schedule">
                    <div class="info-card-icon">
                      <i class="bi bi-calendar-week-fill"></i>
                    </div>
                    <div class="info-card-content">
                      <div class="info-card-label">Lịch học</div>
                      <div class="info-card-value">{{ classDetail.schedule }}</div>
                    </div>
                  </div>

                  <div class="info-card" v-if="classDetail.location">
                    <div class="info-card-icon">
                      <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div class="info-card-content">
                      <div class="info-card-label">Địa điểm</div>
                      <div class="info-card-value">{{ classDetail.location }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Access Notice for Non-Enrolled Users -->
        <div class="row mb-4" v-if="!isEnrolled && !canManageClass">
          <div class="col-12">
            <div class="glass-card animate-fade-in-up" style="animation-delay: 0.2s">
              <div class="card-body text-center py-5">
                <div class="access-notice">
                  <i class="bi bi-lock-fill text-primary mb-3" style="font-size: 3rem;"></i>
                  <h5 class="mb-3">Bạn cần tham gia lớp học để xem nội dung</h5>
                  <p class="text-muted mb-4">
                    Danh sách học viên và tài liệu lớp học chỉ dành cho những người đã tham gia lớp.
                    Hãy nhấn nút "Tham gia lớp học" ở trên để truy cập đầy đủ nội dung.
                  </p>
                  <div class="access-features">
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <div class="feature-item">
                          <i class="bi bi-people text-primary me-2"></i>
                          <span>Xem danh sách bạn học</span>
                        </div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <div class="feature-item">
                          <i class="bi bi-folder2-open text-primary me-2"></i>
                          <span>Tải xuống tài liệu học tập</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Students List -->
        <div class="row mb-4" v-if="isEnrolled || canManageClass">
          <div class="col-12">
            <div class="glass-card animate-fade-in-up" style="animation-delay: 0.2s">
              <div class="card-header-modern">
                <h5 class="section-title">
                  <i class="bi bi-people-fill me-2"></i>
                  Danh sách học viên
                  <span class="section-count">{{ classDetail.students?.length || 0 }}</span>
                </h5>
                <p class="section-subtitle">Những người bạn học cùng lớp</p>
              </div>
              <div class="card-body">
                <div v-if="classDetail.students?.length" class="students-grid-modern">
                  <div
                    v-for="(student, index) in classDetail.students"
                    :key="student.id"
                    class="student-card-modern animate-fade-in-up"
                    :style="{ animationDelay: `${0.1 * index}s` }"
                  >
                    <div class="student-card-header">
                      <div class="student-avatar-modern">
                        <img
                          :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(student.name || 'Unknown')"
                          :alt="student.name"
                          @error="handleImageError"
                        >
                        <div class="student-status">
                          <i class="bi bi-circle-fill"></i>
                        </div>
                      </div>
                      <div class="student-actions">
                        <button class="btn-icon" title="Liên hệ">
                          <i class="bi bi-envelope"></i>
                        </button>
                      </div>
                    </div>
                    <div class="student-card-body">
                      <div class="student-name-modern">{{ student.name }}</div>
                      <div class="student-email-modern">{{ student.email }}</div>
                      <div class="student-join-date">
                        <i class="bi bi-calendar-check"></i>
                        <span>Tham gia {{ formatJoinDate(student.pivot?.created_at) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="empty-state-modern">
                  <div class="empty-icon">
                    <i class="bi bi-person-plus-fill"></i>
                  </div>
                  <h5>Chưa có học viên nào</h5>
                  <p>Chưa có học viên nào đăng ký tham gia lớp học này. Hãy chia sẻ đường link để thu hút học viên!</p>
                  <button class="btn btn-primary btn-sm">
                    <i class="bi bi-share me-1"></i>
                    Chia sẻ lớp học
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Documents -->
        <div class="row mb-4" v-if="isEnrolled || canManageClass">
          <div class="col-12">
            <div class="glass-card animate-fade-in-up" style="animation-delay: 0.3s">
              <div class="card-header-modern">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="section-title">
                      <i class="bi bi-folder2-open-fill me-2"></i>
                      Tài liệu học tập
                      <span class="section-count">{{ classDetail.documents?.length || 0 }}</span>
                    </h6>
                  </div>
                  <button
                    v-if="canManageClass"
                    class="btn btn-sm btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#uploadDocumentModal"
                  >
                    <i class="bi bi-cloud-upload-fill me-1"></i>
                    Tải lên
                  </button>
                </div>
              </div>
              <div class="card-body p-3">
                <div v-if="classDetail.documents?.length" class="documents-list">
                  <div
                    v-for="(doc, index) in classDetail.documents"
                    :key="doc.id"
                    class="document-item"
                  >
                    <div class="d-flex align-items-center gap-3">
                      <div class="document-icon-small">
                        <i :class="getFileIcon(doc.file_name)"></i>
                      </div>
                      <div class="flex-grow-1">
                        <div class="document-title-small">{{ doc.title || 'Không có tiêu đề' }}</div>
                        <div class="document-meta-small">
                          <span v-if="doc.file_size" class="me-2">
                            <i class="bi bi-hdd"></i> {{ formatFileSize(doc.file_size) }}
                          </span>
                          <span>
                            <i class="bi bi-calendar"></i> {{ formatDate(doc.created_at) }}
                          </span>
                        </div>
                      </div>
                      <div class="document-actions-small">
                        <button
                          class="btn btn-sm btn-outline-primary me-1"
                          @click="downloadDocument(doc)"
                          :disabled="downloading === doc.id"
                          title="Tải xuống"
                        >
                          <i class="bi bi-download" v-if="downloading !== doc.id"></i>
                          <span class="spinner-border spinner-border-sm" v-else></span>
                        </button>
                        <button
                          v-if="canManageClass"
                          class="btn btn-sm btn-outline-danger"
                          @click="deleteDocument(doc)"
                          :disabled="downloading === doc.id || deleting === doc.id"
                          title="Xóa"
                        >
                          <i class="bi bi-trash" v-if="deleting !== doc.id"></i>
                          <span class="spinner-border spinner-border-sm" v-else></span>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="empty-state-documents text-center py-3">
                  <i class="bi bi-folder-plus text-muted" style="font-size: 2rem;"></i>
                  <p class="text-muted small mt-2 mb-2">Chưa có tài liệu nào</p>
                  <button
                    v-if="canManageClass"
                    class="btn btn-sm btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#uploadDocumentModal"
                  >
                    <i class="bi bi-cloud-upload-fill me-1"></i>
                    Tải lên tài liệu
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading & Error States (paired with classDetail v-if) -->
    <div v-else>
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner">
          <div class="spinner-border text-primary"></div>
        </div>
        <p class="loading-text">Đang tải thông tin lớp học...</p>
      </div>
      <div v-else class="error-state">
        <div class="error-illustration">
          <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h3 class="error-title">Không thể tải thông tin lớp học</h3>
        <p class="error-message">Vui lòng thử lại sau hoặc liên hệ quản trị viên</p>
        <button @click="fetchClassDetail" class="btn btn-primary">
          <i class="bi bi-arrow-clockwise me-2"></i>
          Thử lại
        </button>
      </div>
    </div>
  </div>

  <!-- Reusable Upload Document Modal Component -->
  <UploadDocumentModal
    modal-id="uploadDocumentModal"
    :preset-class-id="classDetail?.id"
    :allow-class="false"
    :classes="[]"
    @uploaded="handleClassDocUploaded"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import UploadDocumentModal from '@/components/documents/UploadDocumentModal.vue'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { success, error, warning } = useToast()

const classDetail = ref(null)
const loading = ref(false)
const downloading = ref(null)
const deleting = ref(null)

// Upload handled by UploadDocumentModal component
const handleClassDocUploaded = (doc) => {
  if (doc && doc.id) {
    // Thêm ngay tài liệu mới vào danh sách để người dùng thấy tức thì
    if (classDetail.value) {
      classDetail.value.documents = [doc, ...(classDetail.value.documents || [])]
    }
    // Làm mới nền để đồng bộ (nếu có thay đổi metadata trên server)
    setTimeout(() => fetchClassDetail(), 500)
  } else {
    fetchClassDetail()
  }
}

const isEnrolled = computed(() => {
  return classDetail.value?.students?.some(student => student.id === authStore.user?.id)
})

const canManageClass = computed(() => {
  return authStore.isAdmin || (authStore.isTeacher && classDetail.value?.teacher_id === authStore.user?.id)
})

const fetchClassDetail = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/classes/${route.params.id}`)
    classDetail.value = response.data
  } catch (error) {
    error('Không thể tải thông tin lớp học')
  } finally {
    loading.value = false
  }
}

const enrollClass = async () => {
  try {
    const response = await axios.post(`/classes/${route.params.id}/join`)
    success(response.data.message || 'Đăng ký lớp học thành công!')
    await fetchClassDetail()
  } catch (err) {
    error(err.response?.data?.error || 'Có lỗi xảy ra khi đăng ký lớp học')
  }
}

const leaveClass = async () => {
  if (!confirm('Bạn có chắc muốn rời lớp học này?')) return
  
  try {
    const response = await axios.delete(`/classes/${route.params.id}/leave`)
    success(response.data.message || 'Rời lớp học thành công!')
    await fetchClassDetail()
  } catch (err) {
    error(err.response?.data?.error || 'Có lỗi xảy ra khi rời lớp học')
  }
}

const deleteClass = async () => {
  if (!confirm('Bạn có chắc muốn xóa lớp học này? Hành động này không thể hoàn tác.')) return
  
  try {
    await axios.delete(`/classes/${route.params.id}`)
    success('Xóa lớp học thành công!')
    router.push('/classes')
  } catch (err) {
    error(err.response?.data?.error || 'Có lỗi xảy ra khi xóa lớp học')
  }
}

const downloadDocument = async (doc) => {
  downloading.value = doc.id
  try {
    const response = await axios.get(`/documents/${doc.id}/download`, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = window.document.createElement('a')
    link.href = url
    link.setAttribute('download', doc.file_name)
    window.document.body.appendChild(link)
    link.click()
    
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    error('Không thể tải xuống tài liệu. Vui lòng thử lại.')
  } finally {
    downloading.value = null
  }
}

const viewDocument = (doc) => {
  window.open(`/api/documents/${doc.id}/download`, '_blank')
}

const deleteDocument = async (doc) => {
  // Ngăn chặn xóa nếu đang trong quá trình xóa
  if (deleting.value === doc.id) {
    return
  }
  
  if (!confirm(`Bạn có chắc muốn xóa tài liệu "${doc.title}"?`)) {
    return
  }
  
  deleting.value = doc.id
  try {
    await axios.delete(`/documents/${doc.id}`)
    success('Xóa tài liệu thành công!')
    
    // Remove document from local list
    classDetail.value.documents = classDetail.value.documents.filter(d => d.id !== doc.id)
  } catch (err) {
    const errorMessage = err.response?.data?.error || 
                        err.response?.data?.message || 
                        'Có lỗi xảy ra khi xóa tài liệu'
    error(errorMessage)
  } finally {
    deleting.value = null
  }
}

// (Upload logic removed - now encapsulated in UploadDocumentModal component which emits 'uploaded')

const myEvaluation = ref(null)
const evaluationLoading = ref(false)
const evaluationForm = ref({
  rating: 4, // 1-5 scale now matches backend
  comment: '',
  criteria: {
    teaching_quality: 5,
    interaction: 5,
    clarity: 5,
    punctuality: 5,
    support: 5
  }
})

// Star rating (1-5) identical to backend rating now
const starRating = ref(4)
const hoverStar = ref(0)

const syncStarFromRating = () => {
  starRating.value = Math.max(1, Math.min(5, Math.round(evaluationForm.value.rating)))
}

const enterStar = (n) => { hoverStar.value = n }
const leaveStars = () => { hoverStar.value = 0 }
const selectStar = (n) => {
  starRating.value = n
  evaluationForm.value.rating = n
}

const fetchMyEvaluation = async () => {
  if (!authStore.isStudent || !isEnrolled.value) return
  evaluationLoading.value = true
  try {
    const { data } = await axios.get(`/classes/${route.params.id}/my-evaluation`)
    myEvaluation.value = data.evaluation
    if (myEvaluation.value) {
      evaluationForm.value.rating = Number(myEvaluation.value.rating)
      evaluationForm.value.comment = myEvaluation.value.comment || ''
      evaluationForm.value.criteria = myEvaluation.value.criteria || evaluationForm.value.criteria
      syncStarFromRating()
    }
  } catch (e) {
    error('Không thể tải thông tin đánh giá')
  } finally {
    evaluationLoading.value = false
  }
}

const submitEvaluation = async () => {
  if (!authStore.isStudent || !isEnrolled.value) return
  try {
    // Ensure star selection
    if (!starRating.value || starRating.value < 1) {
      warning('Vui lòng chọn số sao trước khi gửi')
      return
    }
  // Use star rating directly (1-5)
  evaluationForm.value.rating = Math.min(5, Math.max(1, starRating.value))

    const payload = {
      rating: evaluationForm.value.rating,
      comment: evaluationForm.value.comment,
      criteria: evaluationForm.value.criteria
    }

    if (myEvaluation.value) {
      await axios.put(`/evaluations/${myEvaluation.value.id}`, payload)
      success('Cập nhật đánh giá thành công')
    } else {
      await axios.post('/evaluations', {
        class_id: classDetail.value.id,
        teacher_id: classDetail.value.teacher_id,
        ...payload
      })
      success('Gửi đánh giá thành công')
    }
    await fetchMyEvaluation()
  } catch (e) {
    const msg = e.response?.data?.error || e.response?.data?.message || 'Không thể gửi đánh giá'
    error(msg)
  }
}

onMounted(() => {
  fetchClassDetail().then(fetchMyEvaluation)
})

// =========================
// Helper Functions (UI/Data)
// =========================

const getClassStatus = () => {
  if (!classDetail.value) return 'upcoming'
  const now = new Date()
  const start = classDetail.value.start_date ? new Date(classDetail.value.start_date) : null
  const end = classDetail.value.end_date ? new Date(classDetail.value.end_date) : null
  if (start && now < start) return 'upcoming'
  if (start && end && now >= start && now <= end) return 'ongoing'
  if (end && now > end) return 'completed'
  // Fallbacks
  if (start && !end && now >= start) return 'ongoing'
  return 'upcoming'
}

const getClassStatusClass = () => {
  const status = getClassStatus()
  return {
    'status-upcoming': status === 'upcoming',
    'status-ongoing': status === 'ongoing',
    'status-completed': status === 'completed'
  }
}

const getClassStatusIcon = () => {
  const status = getClassStatus()
  switch (status) {
    case 'upcoming': return 'bi bi-hourglass-split'
    case 'ongoing': return 'bi bi-play-circle'
    case 'completed': return 'bi bi-check-circle'
    default: return 'bi bi-question-circle'
  }
}

const getClassStatusText = () => {
  const status = getClassStatus()
  switch (status) {
    case 'upcoming': return 'Sắp diễn ra'
    case 'ongoing': return 'Đang diễn ra'
    case 'completed': return 'Đã kết thúc'
    default: return 'Không xác định'
  }
}

const getEnrollmentPercentage = () => {
  if (!classDetail.value) return 0
  const total = classDetail.value.capacity || 0
  if (!total) return 0
  const current = classDetail.value.students?.length || 0
  return Math.min(100, Math.round((current / total) * 100))
}

const getProgressBarClass = () => {
  const pct = getEnrollmentPercentage()
  if (pct >= 80) return 'bg-success'
  if (pct >= 50) return 'bg-warning'
  return 'bg-info'
}

const formatDate = (value) => {
  if (!value) return '—'
  try {
    const d = new Date(value)
    if (isNaN(d.getTime())) return value
    return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch (_) {
    return value
  }
}

const formatJoinDate = (value) => {
  if (!value) return '—'
  try {
    const d = new Date(value)
    const now = new Date()
    const diffMs = now - d
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
    if (diffDays <= 0) return 'hôm nay'
    if (diffDays === 1) return '1 ngày trước'
    if (diffDays < 30) return `${diffDays} ngày trước`
    const diffMonths = Math.floor(diffDays / 30)
    if (diffMonths === 1) return '1 tháng trước'
    if (diffMonths < 12) return `${diffMonths} tháng trước`
    const diffYears = Math.floor(diffMonths / 12)
    return diffYears === 1 ? '1 năm trước' : `${diffYears} năm trước`
  } catch (_) {
    return value
  }
}

const formatFileSize = (bytes) => {
  if (!bytes && bytes !== 0) return '—'
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  let i = 0
  let num = bytes
  while (num >= 1024 && i < sizes.length - 1) {
    num /= 1024
    i++
  }
  return `${num.toFixed(num >= 10 || i === 0 ? 0 : 1)} ${sizes[i]}`
}

const getFileIcon = (fileName = '') => {
  const ext = fileName.split('.').pop()?.toLowerCase() || ''
  switch (ext) {
    case 'pdf': return 'bi bi-filetype-pdf text-danger'
    case 'doc':
    case 'docx': return 'bi bi-filetype-doc text-primary'
    case 'ppt':
    case 'pptx': return 'bi bi-filetype-ppt text-warning'
    case 'xls':
    case 'xlsx': return 'bi bi-filetype-xls text-success'
    case 'txt': return 'bi bi-filetype-txt text-secondary'
    case 'jpg':
    case 'jpeg':
    case 'png': return 'bi bi-file-image text-info'
    default: return 'bi bi-file-earmark text-muted'
  }
}

const handleImageError = (e) => {
  e.target.src = '/default-avatar.png'
}
</script>

<style scoped>

.hero-section {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2.25rem 0 2rem; /* reduced */
  margin-bottom: 1.75rem; /* tighter */
  z-index: 20;
}

/* Quick Stats Bar */
.quick-stats-bar { display:flex; align-items:center; gap:.65rem; background:rgba(255,255,255,0.12); padding:.45rem .75rem; border-radius:14px; backdrop-filter:blur(8px); width:fit-content; }
.qs-item { display:flex; align-items:center; gap:.35rem; font-size:.6rem; font-weight:600; letter-spacing:.4px; text-transform:uppercase; color:rgba(255,255,255,0.9); }
.qs-item i { font-size:.75rem; opacity:.9; }
.qs-sep { width:1px; height:14px; background:rgba(255,255,255,0.25); }

.hero-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
}

.hero-gradient {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
}

.hero-pattern {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                    radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
}

.hero-content {
  position: relative;
  z-index: 5;
}

.hero-title {
  font-size: 1.9rem; /* smaller */
  font-weight: 700;
  margin-bottom: .65rem;
  line-height: 1.15;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.25);
}

.hero-subtitle {
  font-size: .9rem;
  opacity: 0.85;
  line-height: 1.45;
  margin-bottom: 1.1rem;
  text-shadow: 0 1px 2px rgba(0,0,0,0.25);
  max-width: 720px;
}

.hero-stats {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  margin-bottom: 1.1rem;
  flex-wrap: wrap;
}

.hero-stats .stat-item {
  display: flex;
  align-items: center;
  gap: .4rem;
  font-size: .75rem;
  font-weight: 600;
  opacity: .9;
  padding: .35rem .7rem;
  background: rgba(255,255,255,0.15);
  border-radius: 24px;
  backdrop-filter: blur(8px);
}

.hero-stats .stat-item i { font-size:.9rem; }

.stat-divider { display:none; }

.breadcrumb-nav {
  margin-bottom: 2rem;
}

.breadcrumb-link {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50px;
  backdrop-filter: blur(10px);
  transition: all var(--transition-normal);
}

.breadcrumb-link:hover {
  color: white;
  background: rgba(255, 255, 255, 0.2);
  transform: translateX(-2px);
}

.class-meta-header {
  display:flex;
  gap:.5rem;
  margin-bottom:1rem;
  flex-wrap:wrap;
  align-items:center;
}

.subject-badge {
  background: rgba(255,255,255,0.18);
  color:#fff;
  padding:.3rem .75rem;
  border-radius: 18px;
  font-weight:600;
  font-size:.65rem;
  letter-spacing:.5px;
  display:inline-flex;
  align-items:center;
  backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,0.25);
}

.status-badge {
  padding:.3rem .65rem;
  border-radius:18px;
  font-weight:600;
  font-size:.6rem;
  display:inline-flex;
  align-items:center;
  gap:.4rem;
  backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,0.25);
  letter-spacing:.5px;
  text-transform:uppercase;
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
  color: rgba(255, 255, 255, 0.9);
}

.teacher-info { display:flex; align-items:center; gap:.65rem; margin-top:.25rem; }

.teacher-avatar { width:3rem; height:3rem; border-radius:50%; overflow:hidden; border:2px solid rgba(255,255,255,0.25); }

.teacher-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.teacher-name { font-size:.8rem; font-weight:600; margin-bottom:0; letter-spacing:.3px; }

.teacher-title { opacity:.7; font-size:.55rem; text-transform:uppercase; letter-spacing:.8px; }

/* Enrollment Card */
.enrollment-card {
  padding: 2.5rem;
  background: white;
  backdrop-filter: blur(25px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: var(--radius-xl);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  position: relative;
  z-index: 10;
  margin-top: 1rem;
}

.card-header-modern {
  text-align: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.card-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.enrollment-stats-modern {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  background: rgba(102, 126, 234, 0.05);
  border-radius: var(--radius-lg);
  border: 1px solid rgba(102, 126, 234, 0.1);
  transition: all var(--transition-normal);
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 3rem;
  height: 3rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
}

.stat-content {
  flex: 1;
}

.stat-number {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--primary-color);
  display: block;
  line-height: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

.progress-section-modern {
  margin-bottom: 2rem;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.progress-label {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.progress-percentage {
  font-weight: 700;
  color: var(--primary-color);
  font-size: 0.9rem;
}

.progress-container {
  margin-bottom: 0.5rem;
}

.modern-progress {
  height: 12px;
  border-radius: 6px;
  background: rgba(0,0,0,0.1);
  overflow: hidden;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

.modern-progress .progress-bar {
  height: 100%;
  border-radius: 6px;
  transition: width 0.8s ease;
  position: relative;
}

.modern-progress .progress-bar::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.progress-text {
  font-size: 0.8rem;
  color: var(--text-secondary);
  text-align: center;
  font-weight: 500;
}

.enrollment-actions-modern {
  margin-top: 2rem;
}

.action-section {
  text-align: center;
}

.action-btn-primary {
  width: 100%;
  border-radius: var(--radius-lg);
  font-weight: 600;
  padding: 1rem 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  font-size: 1.1rem;
  transition: all var(--transition-normal);
  box-shadow: var(--shadow-md);
}

.action-btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.action-btn-primary:disabled {
  background: #9ca3af;
  transform: none;
  box-shadow: none;
}

.action-note {
  margin-top: 1rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.action-btn-secondary {
  width: 100%;
  border-radius: var(--radius-lg);
  font-weight: 600;
  padding: 1rem 2rem;
  border: 2px solid #fbbf24;
  background: transparent;
  color: #fbbf24;
  font-size: 1.1rem;
  transition: all var(--transition-normal);
}

.action-btn-secondary:hover {
  background: #fbbf24;
  color: white;
}

.enrollment-status-modern {
  margin-top: 1.5rem;
}

.status-badge-modern {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.9rem;
  backdrop-filter: blur(10px);
}

.status-badge-modern.enrolled {
  background: rgba(34, 197, 94, 0.15);
  color: #22c55e;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.btn-group-vertical .action-btn-outline {
  border-radius: var(--radius-lg) !important;
  font-weight: 600;
  padding: 0.75rem 1rem;
  margin-bottom: 0.5rem;
  transition: all var(--transition-normal);
}

.btn-group-vertical .action-btn-outline:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}

.login-prompt {
  text-align: center;
  padding: 2rem;
  background: rgba(251, 191, 36, 0.05);
  border-radius: var(--radius-lg);
  border: 1px solid rgba(251, 191, 36, 0.2);
}

.login-prompt i {
  font-size: 3rem;
  color: #fbbf24;
  margin-bottom: 1rem;
  display: block;
}

.login-prompt h5 {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.login-prompt p {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin: 0;
}

/* Section Title */
.section-title {
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

/* Info Grid */
.info-grid-modern {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.info-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
  backdrop-filter: blur(15px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: var(--radius-lg);
  transition: all var(--transition-normal);
  box-shadow: var(--shadow-sm);
}

.info-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
  border-color: rgba(102, 126, 234, 0.3);
}

.info-card-icon {
  width: 3.5rem;
  height: 3.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
  box-shadow: var(--shadow-md);
}

.info-card-content {
  flex: 1;
  min-width: 0;
}

.info-card-label {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-bottom: 0.25rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-card-value {
  font-weight: 700;
  color: var(--text-primary);
  font-size: 1rem;
  line-height: 1.3;
}

.info-card-subtext {
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
  font-weight: 500;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  backdrop-filter: blur(10px);
}

.status-pill.status-upcoming {
  background: rgba(251, 191, 36, 0.15);
  color: #d97706;
}

.status-pill.status-ongoing {
  background: rgba(34, 197, 94, 0.15);
  color: #16a34a;
}

.status-pill.status-completed {
  background: rgba(156, 163, 175, 0.15);
  color: #6b7280;
}

.card-header-modern {
  text-align: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.section-subtitle {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin: 0.5rem 0 0 0;
  font-weight: 400;
}

.section-count {
  background: rgba(102, 126, 234, 0.1);
  color: var(--primary-color);
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  margin-left: 0.5rem;
}

/* Star Rating */
.star-rating .star {
  font-size: 1.75rem;
  cursor: pointer;
  transition: transform 0.15s ease, color 0.2s ease;
  color: #d1d5db;
}
.star-rating .star.active { color: #fbbf24; }
.star-rating .star.hovered { color: #fcd34d; transform: scale(1.1); }
.star-rating .star:active { transform: scale(0.9); }

/* Students Grid */
.students-grid-modern {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); /* narrower */
  gap: .9rem; /* tighter */
}

.student-card-modern {
  background: rgba(255,255,255,0.65);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(0,0,0,0.05);
  border-radius: 14px;
  transition: background .25s ease, box-shadow .25s ease;
  box-shadow: 0 4px 10px rgba(0,0,0,0.04);
  overflow: hidden;
}

.student-card-modern:hover { background: rgba(255,255,255,0.9); box-shadow:0 6px 16px rgba(0,0,0,0.08); }

.student-card-header { display:flex; align-items:center; justify-content:space-between; padding:.8rem .85rem .2rem; }

.student-avatar-modern { position:relative; width:2.75rem; height:2.75rem; border-radius:50%; overflow:hidden; border:2px solid rgba(102,126,234,0.25); box-shadow: var(--shadow-sm); }

.student-avatar-modern img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.student-status {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 1rem;
  height: 1rem;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.student-status i {
  font-size: 0.6rem;
  color: #22c55e;
}

.student-actions {
  opacity: 0;
  transition: opacity var(--transition-normal);
}

.student-card-modern:hover .student-actions {
  opacity: 1;
}

.btn-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  background: rgba(102, 126, 234, 0.1);
  border: none;
  color: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-normal);
}

.btn-icon:hover {
  background: var(--primary-color);
  color: white;
  transform: scale(1.1);
}

.student-card-body { padding:.55rem .85rem .8rem; }

.student-name-modern { font-weight:600; color:var(--text-primary); font-size:.8rem; margin-bottom:.15rem; letter-spacing:.2px; }

.student-email-modern { color:var(--text-secondary); font-size:.65rem; margin-bottom:.4rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

.student-join-date { display:flex; align-items:center; gap:.3rem; font-size:.55rem; color:var(--text-secondary); font-weight:500; text-transform:uppercase; letter-spacing:.5px; }

.student-join-date i {
  color: var(--primary-color);
}

.empty-state-modern {
  text-align: center;
  padding: 4rem 2rem;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.5) 0%, rgba(255, 255, 255, 0.3) 100%);
  border-radius: var(--radius-lg);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.empty-icon {
  width: 5rem;
  height: 5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
  margin: 0 auto 1.5rem;
  box-shadow: var(--shadow-lg);
}

.empty-state-modern h5 {
  color: var(--text-primary);
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.empty-state-modern p {
  color: var(--text-secondary);
  margin-bottom: 2rem;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}

.student-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.student-info {
  flex: 1;
}

.student-name {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.student-email {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.student-stats {
  text-align: right;
}

.stat-badge {
  background: rgba(102, 126, 234, 0.1);
  color: var(--primary-color);
  padding: 0.25rem 0.75rem;
  border-radius: var(--radius-md);
  font-size: 0.75rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* Documents Grid */
.documents-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.document-card {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
  backdrop-filter: blur(15px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: var(--radius-lg);
  transition: all var(--transition-normal);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.document-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-lg);
  border-color: rgba(102, 126, 234, 0.3);
}

.document-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem 1.5rem 0 1.5rem;
}

.document-icon-modern {
  width: 4rem;
  height: 4rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
  box-shadow: var(--shadow-md);
}

.document-actions-modern {
  display: flex;
  gap: 0.5rem;
  opacity: 0;
  transition: opacity var(--transition-normal);
}

.document-card:hover .document-actions-modern {
  opacity: 1;
}

.btn-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-normal);
  font-size: 0.9rem;
}

.btn-download {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.btn-download:hover {
  background: #22c55e;
  color: white;
  transform: scale(1.1);
}

.btn-view {
  background: rgba(102, 126, 234, 0.1);
  color: var(--primary-color);
}

.btn-view:hover {
  background: var(--primary-color);
  color: white;
  transform: scale(1.1);
}

.btn-delete {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.btn-delete:hover {
  background: #ef4444;
  color: white;
  transform: scale(1.1);
}

.document-card-body {
  padding: 1rem 1.5rem 1.5rem 1.5rem;
}

.document-title-modern {
  font-weight: 700;
  color: var(--text-primary);
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
  line-height: 1.3;
}

.document-description-modern {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin-bottom: 1rem;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  line-clamp: 2;
  overflow: hidden;
}

.document-meta-modern {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.meta-item-modern {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.meta-item-modern i {
  color: var(--primary-color);
  font-size: 0.85rem;
}

.btn-upload {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 50px;
  font-weight: 600;
  color: white;
  transition: all var(--transition-normal);
  box-shadow: var(--shadow-md);
}

.btn-upload:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.empty-state-documents {
  text-align: center;
  padding: 4rem 2rem;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.5) 0%, rgba(255, 255, 255, 0.3) 100%);
  border-radius: var(--radius-lg);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.empty-state-documents .empty-icon {
  width: 5rem;
  height: 5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
  margin: 0 auto 1.5rem;
  box-shadow: var(--shadow-lg);
}

.empty-state-documents h5 {
  color: var(--text-primary);
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.empty-state-documents p {
  color: var(--text-secondary);
  margin-bottom: 2rem;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}

/* Compact Documents List */
.documents-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.document-item { padding:.55rem .6rem; background:rgba(255,255,255,0.55); border-radius:12px; border:1px solid rgba(0,0,0,0.05); transition:background .25s ease, box-shadow .25s ease; }

.document-item:hover { background:rgba(255,255,255,0.9); box-shadow:0 4px 12px rgba(0,0,0,0.05); }

.document-icon-small { width:2rem; height:2rem; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:.8rem; flex-shrink:0; }

.document-title-small { font-weight:600; color:var(--text-primary); font-size:.7rem; margin-bottom:.15rem; letter-spacing:.3px; }

.document-meta-small { font-size:.6rem; color:var(--text-secondary); letter-spacing:.4px; }

.document-meta-small i {
  color: var(--primary-color);
  margin-right: 0.25rem;
}

.document-actions-small {
  display: flex;
  gap: 0.25rem;
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

/* Error State */
.error-state {
  text-align: center;
  padding: 5rem 2rem;
}

.error-illustration i {
  font-size: 6rem;
  color: var(--danger-color);
  margin-bottom: 2rem;
  display: block;
}

.error-title {
  color: var(--text-primary);
  margin-bottom: 1rem;
}

.error-message {
  color: var(--text-secondary);
  margin-bottom: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
  .hero-title {
    font-size: 1.8rem;
  }

  .hero-section {
    padding: 2rem 0;
  }

  .hero-stats {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .stat-divider {
    display: none;
  }

  .enrollment-card {
    padding: 1.5rem;
    margin-top: 2rem;
  }

  .enrollment-stats-modern {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .info-grid-modern {
    grid-template-columns: 1fr;
  }

  .students-grid-modern {
    grid-template-columns: 1fr;
  }

  .documents-grid {
    grid-template-columns: 1fr;
  }

  .document-card-modern {
    flex-direction: column;
    text-align: center;
  }

  .document-actions-modern {
    flex-direction: row;
    justify-content: center;
  }

  .class-meta-header {
    flex-direction: column;
    gap: 0.5rem;
  }
}

/* Compact Layout Styles */
.compact-header {
  background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.glass-card-compact {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.status-badge-small {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.status-badge-small.status-upcoming {
  background: rgba(251, 191, 36, 0.2);
  color: #fbbf24;
}

.status-badge-small.status-ongoing {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
}

.status-badge-small.status-completed {
  background: rgba(156, 163, 175, 0.2);
  color: rgba(12, 12, 12, 0.8);
}

.subject-badge-small {
  background: rgba(79, 70, 229, 0.2);
  color: #4f46e5;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}

.class-title-compact {
  font-size: 1.5rem;
  font-weight: 600;
  color: rgba(12, 12, 12, 0.9);
}

.class-description-compact {
  color: rgba(12, 12, 12, 0.7);
  font-size: 0.9rem;
  line-height: 1.5;
}

.teacher-avatar-small {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.teacher-avatar-small img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.teacher-info-compact {
  font-size: 0.85rem;
  color: rgba(12, 12, 12, 0.6);
}

.teacher-name-small {
  font-weight: 600;
  color: rgba(12, 12, 12, 0.8);
}

.teacher-separator {
  margin: 0 0.5rem;
}

.quick-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.stat-compact {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: rgba(12, 12, 12, 0.7);
}

.stat-compact i {
  color: #4f46e5;
}

.progress-compact .progress-info-small {
  font-size: 0.75rem;
  color: rgba(12, 12, 12, 0.6);
  margin-bottom: 0.5rem;
}

.progress-sm {
  height: 6px;
  border-radius: 3px;
}

.actions-compact .management-notice-small {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
  padding: 0.5rem;
  border-radius: 8px;
  font-size: 0.8rem;
  text-align: center;
}

.actions-compact .login-notice-small {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
  padding: 0.5rem;
  border-radius: 8px;
  font-size: 0.8rem;
  text-align: center;
}

/* Upload Document Styles */
.file-upload-area {
  border: 2px dashed #e5e7eb;
  border-radius: var(--radius-lg);
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all var(--transition-normal);
  background: var(--bg-light);
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.file-upload-area:hover {
  border-color: #6366f1;
  background: rgba(99, 102, 241, 0.05);
}

.file-upload-area.drag-over {
  border-color: #6366f1;
  background: rgba(99, 102, 241, 0.1);
  transform: scale(1.02);
}

.file-upload-area.has-file {
  border-color: #10b981;
  background: rgba(16, 185, 129, 0.05);
}

.upload-placeholder {
  color: var(--text-muted);
}

.upload-icon {
  font-size: 3rem;
  color: #6366f1;
  margin-bottom: 1rem;
}

.upload-placeholder h6 {
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: var(--text-primary);
}

.file-preview {
  width: 100%;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: white;
  border-radius: var(--radius-md);
  border: 1px solid #e5e7eb;
}

.file-icon {
  font-size: 2rem;
  color: #6366f1;
}

.file-details {
  flex: 1;
  text-align: left;
}

.file-name {
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: var(--text-primary);
}



.file-size {
  color: var(--text-muted);
  font-size: 0.875rem;
  margin: 0;
}

/* Main Content Section */
.main-content-section {
  position: relative;
  z-index: 5;
}

</style>