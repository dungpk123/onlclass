<template>
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 11000;">
    <div 
      v-for="toast in toasts" 
      :key="toast.id"
      class="toast show"
      :class="`text-bg-${toast.type}`"
      role="alert"
    >
      <div class="toast-header">
        <i :class="getIcon(toast.type)" class="me-2"></i>
        <strong class="me-auto">{{ getTitle(toast.type) }}</strong>
        <button 
          type="button" 
          class="btn-close" 
          @click="removeToast(toast.id)"
        ></button>
      </div>
      <div class="toast-body">
        {{ toast.message }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const toasts = ref([])
let toastId = 0

const showToast = (message, type = 'success') => {
  const toast = {
    id: ++toastId,
    message,
    type
  }
  
  toasts.value.push(toast)
  
  // Auto remove after 4 seconds
  setTimeout(() => {
    removeToast(toast.id)
  }, 4000)
}

const removeToast = (id) => {
  const index = toasts.value.findIndex(t => t.id === id)
  if (index > -1) {
    toasts.value.splice(index, 1)
  }
}

const getIcon = (type) => {
  const icons = {
    success: 'bi bi-check-circle-fill',
    danger: 'bi bi-exclamation-triangle-fill',
    warning: 'bi bi-exclamation-triangle-fill',
    info: 'bi bi-info-circle-fill'
  }
  return icons[type] || icons.info
}

const getTitle = (type) => {
  const titles = {
    success: 'Thành công',
    danger: 'Lỗi',
    warning: 'Cảnh báo',
    info: 'Thông báo'
  }
  return titles[type] || titles.info
}

// Expose methods to parent
defineExpose({
  showToast,
  success: (message) => showToast(message, 'success'),
  error: (message) => showToast(message, 'danger'),
  warning: (message) => showToast(message, 'warning'),
  info: (message) => showToast(message, 'info')
})
</script>

<style scoped>
.toast {
  min-width: 350px;
  margin-bottom: 0.5rem;
}

.toast-container {
  pointer-events: none;
}

.toast {
  pointer-events: auto;
}
</style>