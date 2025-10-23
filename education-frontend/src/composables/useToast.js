import { ref } from 'vue'

const toasts = ref([])
let toastId = 0

export const useToast = () => {
  const showToast = (message, type = 'success') => {
    const toast = {
      id: ++toastId,
      message,
      type,
      timestamp: Date.now()
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

  const success = (message) => showToast(message, 'success')
  const error = (message) => showToast(message, 'danger')
  const warning = (message) => showToast(message, 'warning')
  const info = (message) => showToast(message, 'info')

  return {
    toasts,
    showToast,
    removeToast,
    success,
    error,
    warning,
    info
  }
}