<template>
  <div class="modal fade" :id="modalId" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content modern-modal">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-cloud-upload me-2"></i>
            {{ title || 'Tải lên tài liệu' }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="uploadDocument">
            <div class="mb-3">
              <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
              <input v-model="form.title" type="text" class="form-control" required placeholder="Nhập tiêu đề" />
            </div>
            <div class="mb-3">
              <label class="form-label">Mô tả</label>
              <textarea v-model="form.description" class="form-control" rows="3" placeholder="Mô tả (tuỳ chọn)"></textarea>
            </div>
            <div class="row g-3">
              <div class="col-md-6" v-if="allowClass">
                <label class="form-label">Lớp học</label>
                <select v-model="form.class_id" class="form-select">
                  <option value="">Không chọn</option>
                  <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="isPublicSwitch" v-model="form.is_public">
                  <label class="form-check-label" for="isPublicSwitch">Công khai</label>
                </div>
              </div>
            </div>
            <div class="mt-3">
              <label class="form-label">Chọn tệp tin <span class="text-danger">*</span></label>
              <div class="file-upload-area" :class="{ 'drag-over': dragOver, 'has-file': form.file }"
                   @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false" @drop.prevent="onDrop" @click="triggerFile">
                <input ref="fileInput" type="file" class="d-none" @change="onFileChange"
                       accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.jpg,.jpeg,.png" />
                <div v-if="!form.file" class="upload-placeholder">
                  <i class="bi bi-cloud-upload upload-icon"></i>
                  <h6>Kéo thả hoặc nhấp để chọn</h6>
                  <p class="text-muted">PDF / DOC / PPT / XLS / TXT / JPG / PNG (≤ 10MB)</p>
                </div>
                <div v-else class="file-preview">
                  <div class="file-info">
                    <i :class="getFileIcon(form.file.name)" class="file-icon"></i>
                    <div class="file-details">
                      <h6 class="file-name">{{ form.file.name }}</h6>
                      <p class="file-size">{{ formatSize(form.file.size) }}</p>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" type="button" @click.stop="removeFile">
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-3" v-if="uploading && progress > 0">
              <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Đang tải lên...</span><span>{{ progress }}%</span>
              </div>
              <div class="progress">
                <div class="progress-bar bg-primary" :style="{ width: progress + '%' }"></div>
              </div>
            </div>
            <div class="d-flex gap-2 mt-4">
              <button class="btn btn-primary flex-fill" type="submit" :disabled="uploading || !form.title || !form.file">
                <span v-if="uploading" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-cloud-upload me-2"></i>
                {{ uploading ? 'Đang tải...' : 'Tải lên' }}
              </button>
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" :disabled="uploading">Hủy</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'
import { Modal } from 'bootstrap'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modalId: { type: String, required: true },
  title: { type: String, default: '' },
  presetClassId: { type: [Number, String], default: null },
  allowClass: { type: Boolean, default: true },
  classes: { type: Array, default: () => [] }
})
const emits = defineEmits(['uploaded'])

const { success, error } = useToast()

// Form data
const form = ref({
  title: '',
  description: '',
  class_id: '',
  is_public: false,
  file: null
})

// Upload states
const uploading = ref(false)
const progress = ref(0)
const dragOver = ref(false)
const fileInput = ref(null)

// Watch for preset class ID
watch(() => props.presetClassId, (val) => {
  if (val) form.value.class_id = val
})

// File handling
const triggerFile = () => fileInput.value?.click()

const onDrop = (e) => {
  dragOver.value = false
  const file = e.dataTransfer.files[0]
  if (file) handleFile(file)
}

const onFileChange = (e) => {
  const file = e.target.files[0]
  if (file) handleFile(file)
}

const handleFile = (file) => {
  // Check file type
  const allowedTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'image/jpeg',
    'image/jpg',
    'image/png'
  ]
  
  if (!allowedTypes.includes(file.type)) {
    error('Định dạng file không được hỗ trợ!')
    return
  }
  
  // Check file size (10MB)
  if (file.size > 10 * 1024 * 1024) {
    error('File vượt quá 10MB!')
    return
  }
  
  form.value.file = file
  
  // Auto fill title if empty
  if (!form.value.title) {
    form.value.title = file.name.replace(/\.[^/.]+$/, '')
  }
}

const removeFile = () => {
  form.value.file = null
  if (fileInput.value) fileInput.value.value = ''
}

// Upload function
const uploadDocument = async () => {
  if (!form.value.title || !form.value.file) return
  
  uploading.value = true
  progress.value = 0
  
  const formData = new FormData()
  formData.append('title', form.value.title)
  if (form.value.description) formData.append('description', form.value.description)
  if (form.value.class_id) formData.append('class_id', form.value.class_id)
  formData.append('is_public', form.value.is_public ? '1' : '0')
  formData.append('file', form.value.file)
  
  try {
    const response = await axios.post('/documents', formData, {
      headers: {
        // Let browser set Content-Type with boundary for multipart/form-data
        'Content-Type': undefined
      },
      onUploadProgress: (progressEvent) => {
        if (progressEvent.total) {
          progress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
        }
      }
    })
    
    // Check if response is valid
    if (response.status !== 200 && response.status !== 201) {
      throw new Error('Unexpected response status: ' + response.status)
    }
    
    if (!response.data || !response.data.document) {
      throw new Error('Invalid response: missing document data')
    }
    
    // Success
    success(response.data?.message || 'Tải lên thành công!')
    
    // Emit to parent
    emits('uploaded', response.data.document)
    
    // Reset form
    resetForm()
    
    // Close modal
    closeModal()
    
  } catch (uploadError) {
    
    let errorMessage = 'Có lỗi xảy ra khi tải lên tài liệu'
    
    if (uploadError.response) {
      // Server responded with error
      const data = uploadError.response.data
      if (typeof data === 'string') {
        errorMessage = data
      } else if (data?.error) {
        errorMessage = data.error
      } else if (data?.message) {
        errorMessage = data.message
      } else if (data && typeof data === 'object') {
        // Handle validation errors
        const firstError = Object.values(data)[0]
        if (Array.isArray(firstError)) {
          errorMessage = firstError[0]
        } else {
          errorMessage = firstError || errorMessage
        }
      }
    } else if (uploadError.request) {
      // Network error
      errorMessage = 'Không thể kết nối tới server. Vui lòng kiểm tra mạng.'
    } else {
      // Other error
      errorMessage = uploadError.message || errorMessage
    }
    
    error(errorMessage)
  } finally {
    uploading.value = false
  }
}

// Helper functions
const resetForm = () => {
  form.value = {
    title: '',
    description: '',
    class_id: props.presetClassId || '',
    is_public: false,
    file: null
  }
  progress.value = 0
  if (fileInput.value) fileInput.value.value = ''
}

const closeModal = () => {
  const modal = document.getElementById(props.modalId)
  if (modal) {
    const bsModal = Modal.getInstance(modal) || new Modal(modal)
    bsModal.hide()
  }
}

const formatSize = (bytes) => {
  if (!bytes) return '0 B'
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i]
}

const getFileIcon = (fileName = '') => {
  const ext = fileName.split('.').pop()?.toLowerCase()
  const iconMap = {
    pdf: 'bi bi-file-earmark-pdf text-danger',
    doc: 'bi bi-file-earmark-word text-primary',
    docx: 'bi bi-file-earmark-word text-primary',
    ppt: 'bi bi-file-earmark-slides text-warning',
    pptx: 'bi bi-file-earmark-slides text-warning',
    xls: 'bi bi-file-earmark-excel text-success',
    xlsx: 'bi bi-file-earmark-excel text-success',
    txt: 'bi bi-file-earmark-text text-secondary',
    jpg: 'bi bi-file-earmark-image text-success',
    jpeg: 'bi bi-file-earmark-image text-success',
    png: 'bi bi-file-earmark-image text-success'
  }
  return iconMap[ext] || 'bi bi-file-earmark'
}

// Initialize
onMounted(() => {
  if (props.presetClassId) {
    form.value.class_id = props.presetClassId
  }
})
</script>

<style scoped>
.file-upload-area {
  border: 2px dashed rgba(102, 126, 234, 0.4);
  padding: 1.25rem;
  text-align: center;
  border-radius: 14px;
  cursor: pointer;
  transition: 0.25s;
}

.file-upload-area.drag-over {
  background: rgba(102, 126, 234, 0.06);
  border-color: #667eea;
}

.file-upload-area.has-file {
  border-style: solid;
}

.upload-placeholder .upload-icon {
  font-size: 2.2rem;
  color: #667eea;
}

.file-preview .file-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.file-icon {
  font-size: 1.8rem;
  color: #667eea;
}

.file-name {
  font-size: 0.85rem;
  font-weight: 600;
}

.file-size {
  font-size: 0.7rem;
  color: #6b7280;
}
</style>
