<template>
  <div class="modern-document-container">
    <!-- Compact Header -->
    <div class="compact-header">
      <div class="container py-4">
        <div class="row align-items-center">
          <div class="col-md-8">
            <div class="header-content">
              <h2 class="page-title">
                <i class="bi bi-folder2-open me-2"></i>
                Thư viện tài liệu
              </h2>
              <p class="page-subtitle">{{ documents.length }} tài liệu có sẵn</p>
            </div>
          </div>
          <div class="col-md-4 text-end">
            <div v-if="authStore.isTeacher || authStore.isAdmin">
              <button 
                class="btn btn-primary upload-btn"
                data-bs-toggle="modal" 
                data-bs-target="#uploadModal"
              >
                <i class="bi bi-plus-circle me-1"></i>
                Tải lên
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="container py-3">
      <!-- Compact Filter Bar -->
      <div class="filter-bar mb-4">
        <div class="row g-3 align-items-center">
          <div class="col-md-4">
            <div class="search-box">
              <i class="bi bi-search search-icon"></i>
              <input 
                v-model="filters.search" 
                type="text" 
                class="form-control" 
                placeholder="Tìm kiếm tài liệu..."
              >
            </div>
          </div>
          <div class="col-md-4">
            <select v-model="filters.class_id" class="form-select">
              <option value="">Tất cả lớp học</option>
              <option 
                v-for="classItem in classes" 
                :key="classItem.id" 
                :value="classItem.id"
              >
                {{ classItem.name }}
              </option>
            </select>
          </div>
          <div class="col-md-4 text-end">
            <button @click="fetchDocuments" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-arrow-clockwise me-1"></i>
              Làm mới
            </button>
          </div>
        </div>
      </div>



      <!-- Compact Documents List -->
      <div v-if="documents.length && !loading" class="documents-list">
        <div 
          v-for="doc in paginatedDocuments" 
          :key="doc.id"
          class="document-item"
        >
          <!-- Document Icon -->
          <div class="doc-icon">
            <i :class="getFileIcon(doc.file_name)"></i>
          </div>
          
          <!-- Document Info -->
          <div class="doc-info">
            <h6 class="doc-title">{{ doc.title }}</h6>
            <p class="doc-meta">
              <span class="doc-uploader">{{ doc.uploader?.name }}</span>
              <span class="doc-separator">•</span>
              <span class="doc-size">{{ getFileSize(doc.file_size) }}</span>
              <span v-if="doc.class" class="doc-separator">•</span>
              <span v-if="doc.class" class="doc-class">{{ doc.class.name }}</span>
            </p>
          </div>
          
          <!-- Quick Actions -->
          <div class="doc-actions">
            <button 
              @click="downloadDocument(doc)"
              class="btn btn-outline-primary btn-sm"
              title="Tải về"
            >
              <i class="bi bi-download"></i>
            </button>
            <button 
              v-if="canManageDocument(doc)"
              @click="deleteDocument(doc.id)"
              class="btn btn-outline-danger btn-sm"
              title="Xóa"
              :disabled="deleting === doc.id"
            >
              <i class="bi bi-trash" v-if="deleting !== doc.id"></i>
              <span class="spinner-border spinner-border-sm" v-else></span>
            </button>
            <div v-if="canManageDocument(doc)" class="dropdown">
              <button 
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="dropdown"
                title="Thêm"
              >
                <i class="bi bi-three-dots"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <a 
                    class="dropdown-item"
                    href="#"
                    @click.prevent="editDocument(doc)"
                    data-bs-toggle="modal" 
                    data-bs-target="#editModal"
                  >
                    <i class="bi bi-pencil-square me-2"></i>
                    Chỉnh sửa
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div class="empty-state animate-fade-in-up" v-else-if="!loading">
        <div class="empty-illustration">
          <i class="bi bi-folder2-open"></i>
        </div>
        <h3 class="empty-title">Không tìm thấy tài liệu</h3>
        <p class="empty-message" v-if="authStore.isStudent">
          Chưa có tài liệu nào trong các lớp học bạn tham gia.
          <br><small>Tham gia thêm lớp học để truy cập tài liệu.</small>
        </p>
        <p class="empty-message" v-else-if="authStore.isTeacher">
          Chưa có tài liệu nào trong các lớp bạn giảng dạy.
          <br><small>Tải lên tài liệu đầu tiên cho học viên của bạn.</small>
        </p>
        <p class="empty-message" v-else>
          Hệ thống chưa có tài liệu nào được tải lên.
        </p>
        <button 
          v-if="authStore.isTeacher || authStore.isAdmin"
          class="btn btn-primary mt-3"
          data-bs-toggle="modal" 
          data-bs-target="#uploadModal"
        >
          <i class="bi bi-plus-circle me-2"></i>
          Tải lên tài liệu đầu tiên
        </button>
      </div>

      <!-- Loading State -->
      <div class="loading-state" v-if="loading">
        <div class="loading-spinner">
          <div class="spinner-border text-primary"></div>
        </div>
        <p class="loading-text">Đang tải tài liệu...</p>
      </div>

      <!-- Pagination -->
      <div v-if="documents.length > itemsPerPage" class="pagination-wrapper mt-5">
        <nav class="pagination-nav">
          <ul class="pagination justify-content-center">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button class="page-link" @click="currentPage = 1" :disabled="currentPage === 1">
                <i class="bi bi-chevron-double-left"></i>
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button class="page-link" @click="currentPage--" :disabled="currentPage === 1">
                <i class="bi bi-chevron-left"></i>
              </button>
            </li>
            <li 
              v-for="page in visiblePages" 
              :key="page" 
              class="page-item" 
              :class="{ active: page === currentPage }"
            >
              <button class="page-link" @click="currentPage = page">
                {{ page }}
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button class="page-link" @click="currentPage++" :disabled="currentPage === totalPages">
                <i class="bi bi-chevron-right"></i>
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button class="page-link" @click="currentPage = totalPages" :disabled="currentPage === totalPages">
                <i class="bi bi-chevron-double-right"></i>
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Reusable Upload Modal Component -->
    <UploadDocumentModal
      modal-id="uploadModal"
      :classes="classes"
      :allow-class="true"
      @uploaded="handleUploaded"
    />

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content modern-modal">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi bi-pencil-square me-2"></i>
              Chỉnh sửa tài liệu
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="updateDocument" class="modern-form">
              <div class="mb-3">
                <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                <input 
                  v-model="editForm.title" 
                  type="text" 
                  class="form-control modern-input" 
                  required
                >
              </div>
              
              <div class="mb-3">
                <label class="form-label">Mô tả</label>
                <textarea 
                  v-model="editForm.description" 
                  class="form-control modern-input" 
                  rows="3"
                ></textarea>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Lớp học</label>
                <select v-model="editForm.class_id" class="form-select modern-select">
                  <option value="">Không chọn lớp</option>
                  <option 
                    v-for="classItem in classes" 
                    :key="classItem.id" 
                    :value="classItem.id"
                  >
                    {{ classItem.name }}
                  </option>
                </select>
              </div>
              
              <div class="modal-actions">
                <button type="submit" class="btn btn-primary me-3">
                  <i class="bi bi-check-circle me-2"></i>
                  Cập nhật
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                  Hủy bỏ
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import UploadDocumentModal from '@/components/documents/UploadDocumentModal.vue'
import { useToast } from '@/composables/useToast'

const authStore = useAuthStore()
const { success, error } = useToast()

const documents = ref([])
const classes = ref([])
const loading = ref(false)
const deleting = ref(null)
// uploadLoading removed (handled inside component)
const currentPage = ref(1)
const itemsPerPage = ref(12)

const filters = ref({
  class_id: '',
  search: '',
  category: ''
})

// Inline upload form state removed - managed by UploadDocumentModal

const editForm = ref({
  id: null,
  title: '',
  description: '',
  class_id: ''
})

const documentCategories = [
  { value: '', label: 'Tất cả', icon: 'bi bi-grid-3x3-gap' },
  { value: 'lecture', label: 'Bài giảng', icon: 'bi bi-book' },
  { value: 'assignment', label: 'Bài tập', icon: 'bi bi-pencil-square' },
  { value: 'reference', label: 'Tham khảo', icon: 'bi bi-bookmark' },
  { value: 'other', label: 'Khác', icon: 'bi bi-folder' }
]

// Computed
const totalDownloads = computed(() => {
  return documents.value.reduce((total, doc) => total + (doc.downloads || 0), 0)
})

const filteredDocuments = computed(() => {
  let filtered = documents.value

  if (filters.value.class_id) {
    filtered = filtered.filter(doc => doc.class_id == filters.value.class_id)
  }

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(doc => 
      doc.title.toLowerCase().includes(search) ||
      (doc.description && doc.description.toLowerCase().includes(search))
    )
  }

  if (filters.value.category) {
    filtered = filtered.filter(doc => doc.category === filters.value.category)
  }

  return filtered
})

const totalPages = computed(() => {
  return Math.ceil(filteredDocuments.value.length / itemsPerPage.value)
})

const paginatedDocuments = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredDocuments.value.slice(start, end)
})

const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pages.push(i)
      pages.push('...')
      pages.push(total)
    } else if (current >= total - 3) {
      pages.push(1)
      pages.push('...')
      for (let i = total - 4; i <= total; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(total)
    }
  }
  
  return pages
})

// Methods
const canManageDocument = (doc) => {
  return authStore.isAdmin || doc.uploaded_by === authStore.user?.id
}

const fetchDocuments = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.class_id) params.append('class_id', filters.value.class_id)
    if (filters.value.search) params.append('search', filters.value.search)
    
    const response = await axios.get(`/documents?${params.toString()}`)
    
    if (response.data.data) {
      documents.value = response.data.data
    } else {
      documents.value = response.data
    }
  } catch (error) {
    console.error('Failed to fetch documents:', error)
  } finally {
    loading.value = false
  }
}

const fetchClasses = async () => {
  try {
    let url = '/classes'
    
    if (authStore.isTeacher) {
      url += `?teacher_id=${authStore.user.id}`
    } else if (authStore.isStudent) {
      url += `?enrolled=true`
    }
    
    const response = await axios.get(url)
    
    if (response.data.data) {
      classes.value = response.data.data
    } else {
      classes.value = response.data
    }
  } catch (error) {
    console.error('Failed to fetch classes:', error)
  }
}

// Upload handled by UploadDocumentModal; optimistic update sau đó đồng bộ lại nền
const handleUploaded = (doc) => {
  if (doc && doc.id) {
    // Đưa lên đầu danh sách ngay để cảm giác nhanh
    documents.value.unshift(doc)
    // Nếu đang có filter class khác không khớp thì vẫn refresh đầy đủ
    setTimeout(() => { fetchDocuments() }, 400) // đồng bộ nền, tránh flicker
  } else {
    fetchDocuments()
  }
}

const editDocument = (doc) => {
  editForm.value = {
    id: doc.id,
    title: doc.title,
    description: doc.description || '',
    class_id: doc.class_id || ''
  }
}

const updateDocument = async () => {
  try {
    await axios.put(`/documents/${editForm.value.id}`, {
      title: editForm.value.title,
      description: editForm.value.description,
      class_id: editForm.value.class_id
    })
    
    const modal = document.getElementById('editModal')
    const bsModal = bootstrap.Modal.getInstance(modal)
    bsModal.hide()
    
    await fetchDocuments()
  } catch (err) {
    error(err.response?.data?.error || 'Có lỗi xảy ra')
  }
}

const deleteDocument = async (id) => {
  // Ngăn chặn xóa nếu đang trong quá trình xóa
  if (deleting.value === id) {
    return
  }
  
  if (!confirm('Bạn có chắc muốn xóa tài liệu này?')) return
  
  deleting.value = id
  try {
    await axios.delete(`/documents/${id}`)
    success('Xóa tài liệu thành công!')
    
    // Remove document from local list
    documents.value = documents.value.filter(d => d.id !== id)
  } catch (err) {
    const errorMessage = err.response?.data?.error || 
                        err.response?.data?.message || 
                        'Có lỗi xảy ra khi xóa tài liệu'
    error(errorMessage)
  } finally {
    deleting.value = null
  }
}

const downloadDocument = async (doc) => {
  try {
    const response = await axios.get(`/documents/${doc.id}/download`, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', doc.file_name || doc.title)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (err) {
    error('Không thể tải về tài liệu')
  }
}



const shareDocument = (doc) => {
  if (navigator.share) {
    navigator.share({
      title: doc.title,
      text: doc.description,
      url: window.location.href + '?doc=' + doc.id
    })
  } else {
    // Copy link to clipboard
    const url = window.location.href + '?doc=' + doc.id
    navigator.clipboard.writeText(url).then(() => {
      success('Đã sao chép liên kết vào clipboard!')
    })
  }
}

const getFileIcon = (fileName) => {
  if (!fileName) return 'bi bi-file-earmark'
  
  const ext = fileName.split('.').pop().toLowerCase()
  const iconMap = {
    'pdf': 'bi bi-file-earmark-pdf text-danger',
    'doc': 'bi bi-file-earmark-word text-primary',
    'docx': 'bi bi-file-earmark-word text-primary',
    'ppt': 'bi bi-file-earmark-slides text-warning',
    'pptx': 'bi bi-file-earmark-slides text-warning',
    'txt': 'bi bi-file-earmark-text text-secondary',
    'jpg': 'bi bi-file-earmark-image text-success',
    'jpeg': 'bi bi-file-earmark-image text-success',
    'png': 'bi bi-file-earmark-image text-success',
    'gif': 'bi bi-file-earmark-image text-success'
  }
  
  return iconMap[ext] || 'bi bi-file-earmark'
}

const getFileSize = (bytes) => {
  if (!bytes) return '0 B'
  
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Watchers
watch(filters, () => {
  currentPage.value = 1
  fetchDocuments()
}, { deep: true })

onMounted(() => {
  fetchDocuments()
  fetchClasses()
})
</script>

<style scoped>
/* Compact Header */
.compact-header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.page-title {
  font-size: 1.75rem;
  font-weight: 600;
  margin: 0;
  color: #2d3748;
}

.page-subtitle {
  color: #718096;
  margin: 0;
  font-size: 0.9rem;
}

.upload-btn {
  border-radius: 8px;
  font-weight: 500;
}

/* Filter Bar */
.filter-bar {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  padding: 1rem;
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.search-box {
  position: relative;
}

.search-box .search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
}

.search-box input {
  padding-left: 2.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.search-box input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Documents List */
.documents-list {
  background: white;
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.document-item {
  display: flex;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #f7fafc;
  transition: background-color 0.2s;
}

.document-item:last-child {
  border-bottom: none;
}

.document-item:hover {
  background-color: #f8fafc;
}

.doc-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #edf2f7;
  border-radius: 8px;
  margin-right: 1rem;
  flex-shrink: 0;
}

.doc-icon i {
  font-size: 1.2rem;
  color: #667eea;
}

.doc-info {
  flex: 1;
  min-width: 0;
}

.doc-title {
  font-size: 0.95rem;
  font-weight: 600;
  margin: 0 0 0.25rem 0;
  color: #2d3748;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.doc-meta {
  margin: 0;
  font-size: 0.8rem;
  color: #718096;
}

.doc-separator {
  margin: 0 0.5rem;
}

.doc-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.doc-actions .btn {
  width: 36px;
  height: 36px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-title {
    font-size: 1.5rem;
  }
  
  .doc-title {
    font-size: 0.9rem;
  }
  
  .doc-meta {
    font-size: 0.75rem;
  }
  
  .doc-actions {
    gap: 0.25rem;
  }
  
  .doc-actions .btn {
    width: 32px;
    height: 32px;
  }
  
  .document-item {
    padding: 0.75rem;
  }
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
}

.empty-state i {
  font-size: 4rem;
  color: #cbd5e0;
  margin-bottom: 1rem;
}

.empty-state h3 {
  color: #2d3748;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #718096;
}

/* Upload Modal */
.file-upload-area { border:2px dashed rgba(102,126,234,.4); padding:1.25rem; text-align:center; border-radius:14px; cursor:pointer; transition:.25s; position:relative; }
.file-upload-area.drag-over { background:rgba(102,126,234,.06); border-color:#667eea; }
.file-upload-area.has-file { border-style:solid; }
.upload-placeholder .upload-icon { font-size:2.2rem; color:#667eea; }
.file-preview .file-info { display:flex; align-items:center; gap:.75rem; }
.file-icon { font-size:1.8rem; color:#667eea; }
.file-name { font-size:.85rem; font-weight:600; }
.file-size { font-size:.7rem; color:#6b7280; }
</style>