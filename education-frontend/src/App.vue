<template>
  <div id="app">
    <!-- Modern Glass Navigation Bar -->
    <nav class="glass-navbar" v-if="authStore.isAuthenticated">
      <div class="navbar-wrapper">
        <!-- Brand Section -->
        <div class="navbar-brand-section">
          <router-link to="/dashboard" class="brand-link">
            <div class="brand-logo">
              <div class="logo-icon">
                <i class="bi bi-mortarboard-fill"></i>
              </div>
              <div class="logo-glow"></div>
            </div>
            <div class="brand-content">
              <span class="brand-title">OnlClass</span>
              <span class="brand-subtitle">Quản lý Giáo Dục</span>
            </div>
          </router-link>
        </div>

        <!-- Navigation Links -->
        <div class="navbar-navigation" :class="{ 'mobile-open': mobileMenuOpen }">
          <div class="nav-links-container">
            <router-link to="/dashboard" class="nav-item" @click="closeMobileMenu">
              <div class="nav-icon">
                <i class="bi bi-house-door"></i>
              </div>
              <span class="nav-text">Trang chủ</span>
              <div class="nav-indicator"></div>
            </router-link>

            <router-link to="/classes" class="nav-item" @click="closeMobileMenu">
              <div class="nav-icon">
                <i class="bi bi-journal-bookmark"></i>
              </div>
              <span class="nav-text">Lớp học</span>
              <div class="nav-indicator"></div>
            </router-link>

            <router-link to="/documents" class="nav-item" @click="closeMobileMenu">
              <div class="nav-icon">
                <i class="bi bi-folder2-open"></i>
              </div>
              <span class="nav-text">Tài liệu</span>
              <div class="nav-indicator"></div>
            </router-link>

            <router-link to="/evaluations" class="nav-item" @click="closeMobileMenu">
              <div class="nav-icon">
                <i class="bi bi-star-fill"></i>
              </div>
              <span class="nav-text">Đánh giá</span>
              <div class="nav-indicator"></div>
            </router-link>

            <router-link to="/users" class="nav-item" v-if="authStore.isAdmin" @click="closeMobileMenu">
              <div class="nav-icon">
                <i class="bi bi-people-fill"></i>
              </div>
              <span class="nav-text">Người dùng</span>
              <div class="nav-indicator"></div>
            </router-link>
          </div>
        </div>

        <!-- User Profile Section -->
        <div class="navbar-user-section">
          <div
            class="user-profile"
            @click="toggleUserDropdown"
            :class="{ 'active': userDropdownOpen }"
            @keydown="handleUserProfileKeydown"
            role="button"
            tabindex="0"
            aria-haspopup="true"
            :aria-expanded="userDropdownOpen.toString()"
            aria-controls="user-dropdown-menu"
          >
            <div class="user-avatar-container">
              <div class="user-avatar">
                <div class="avatar-bg"></div>
                <span class="avatar-text">{{ getAvatarInitials() }}</span>
                <div class="avatar-status"></div>
              </div>
            </div>
            
            <div class="user-details">
              <div class="user-name">{{ authStore.user?.name }}</div>
              <div class="user-role-badge">
                <i class="bi bi-shield-check"></i>
                {{ getRoleText() }}
              </div>
            </div>

            <div class="dropdown-toggle-icon">
              <i class="bi bi-chevron-down" :class="{ 'rotated': userDropdownOpen }" aria-hidden="true"></i>
            </div>

            <!-- User Dropdown -->
            <div
              id="user-dropdown-menu"
              class="user-dropdown-menu"
              :class="{ 'show': userDropdownOpen }"
              ref="userDropdownRef"
              role="menu"
              aria-label="Tùy chọn người dùng"
              @keydown.stop.prevent="handleMenuKeydown"
            >

              <div class="dropdown-divider" role="separator"></div>

              <!-- Role based quick links -->
              <router-link
                v-if="authStore.isTeacher"
                to="/classes"
                class="dropdown-option"
                @click="closeUserDropdown"
                role="menuitem"
                tabindex="-1"
              >
                <div class="option-icon"><i class="bi bi-journal-bookmark"></i></div>
                <div class="option-content">
                  <span class="option-title">Lớp của tôi</span>
                  <span class="option-desc">Quản lý lớp giảng dạy</span>
                </div>
              </router-link>

              <router-link
                v-if="authStore.isAdmin"
                to="/users"
                class="dropdown-option"
                @click="closeUserDropdown"
                role="menuitem"
                tabindex="-1"
              >
                <div class="option-icon"><i class="bi bi-people-fill"></i></div>
                <div class="option-content">
                  <span class="option-title">Người dùng</span>
                  <span class="option-desc">Quản trị hệ thống</span>
                </div>
              </router-link>

              <router-link
                to="/documents"
                class="dropdown-option"
                @click="closeUserDropdown"
                role="menuitem"
                tabindex="-1"
              >
                <div class="option-icon"><i class="bi bi-folder2-open"></i></div>
                <div class="option-content">
                  <span class="option-title">Tài liệu</span>
                  <span class="option-desc">Tài nguyên học tập</span>
                </div>
              </router-link>

              <div class="dropdown-divider" role="separator"></div>

              <router-link
                to="/profile"
                class="dropdown-option"
                @click="closeUserDropdown"
                role="menuitem"
                tabindex="-1"
              >
                <div class="option-icon">
                  <i class="bi bi-person-gear"></i>
                </div>
                <div class="option-content">
                  <span class="option-title">Hồ sơ cá nhân</span>
                  <span class="option-desc">Quản lý tài khoản</span>
                </div>
              </router-link>

              <div class="dropdown-divider" role="separator"></div>

              <a
                href="#"
                class="dropdown-option logout"
                @click.prevent="handleLogout"
                role="menuitem"
                tabindex="-1"
              >
                <div class="option-icon">
                  <i class="bi bi-box-arrow-right"></i>
                </div>
                <div class="option-content">
                  <span class="option-title">Đăng xuất</span>
                  <span class="option-desc">Thoát khỏi hệ thống</span>
                </div>
              </a>
            </div>
          </div>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" @click="toggleMobileMenu" :class="{ 'active': mobileMenuOpen }">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </button>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid p-0" v-if="authStore.initialized">
      <router-view />
    </div>

    <!-- Initialization Loading -->
    <div v-if="!authStore.initialized" class="loading-overlay">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Đang khởi tạo...</span>
      </div>
    </div>

    <!-- General Loading Spinner -->
    <div v-if="authStore.loading && authStore.initialized" class="loading-overlay">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
  </div>

  <!-- Global Toast Notifications -->
  <GlobalToast />
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch, nextTick } from 'vue'
import { useAuthStore } from '@/stores/auth'
import GlobalToast from '@/components/common/GlobalToast.vue'

const authStore = useAuthStore()
const mobileMenuOpen = ref(false)
const userDropdownOpen = ref(false)
const userDropdownRef = ref(null)

// Accessibility: capture focusable items
const getMenuItems = () => {
  if (!userDropdownRef.value) return []
  return Array.from(userDropdownRef.value.querySelectorAll('.dropdown-option'))
}

const focusFirstItem = () => {
  const items = getMenuItems()
  if (items.length) items[0].focus()
}

const handleMenuKeydown = (e) => {
  const items = getMenuItems()
  const currentIndex = items.indexOf(document.activeElement)
  switch (e.key) {
    case 'ArrowDown':
      e.preventDefault()
      if (currentIndex === -1 || currentIndex === items.length - 1) {
        items[0]?.focus()
      } else {
        items[currentIndex + 1]?.focus()
      }
      break
    case 'ArrowUp':
      e.preventDefault()
      if (currentIndex <= 0) {
        items[items.length - 1]?.focus()
      } else {
        items[currentIndex - 1]?.focus()
      }
      break
    case 'Home':
      e.preventDefault(); items[0]?.focus(); break
    case 'End':
      e.preventDefault(); items[items.length - 1]?.focus(); break
    case 'Escape':
      closeUserDropdown(); break
    case 'Tab':
      closeUserDropdown(); break
  }
}

const handleUserProfileKeydown = (e) => {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault(); toggleUserDropdown();
  } else if (e.key === 'ArrowDown') {
    if (!userDropdownOpen.value) {
      toggleUserDropdown(() => focusFirstItem())
    } else {
      focusFirstItem()
    }
  }
}

watch(userDropdownOpen, async (open) => {
  if (open) {
    await nextTick()
    focusFirstItem()
  }
})

onMounted(async () => {
  await authStore.init()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

const handleClickOutside = (event) => {
  const userSection = event.target.closest('.navbar-user-section')
  if (!userSection && userDropdownOpen.value) {
    userDropdownOpen.value = false
  }
  const mobileMenu = event.target.closest('.navbar-navigation')
  const mobileToggle = event.target.closest('.mobile-menu-toggle')
  if (!mobileMenu && !mobileToggle && mobileMenuOpen.value) {
    mobileMenuOpen.value = false
  }
}

const handleLogout = () => {
  authStore.logout()
  closeUserDropdown()
}

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
  if (mobileMenuOpen.value) {
    userDropdownOpen.value = false
  }
}

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
}

const toggleUserDropdown = async (cb) => {
  userDropdownOpen.value = !userDropdownOpen.value
  if (userDropdownOpen.value && typeof cb === 'function') {
    await nextTick(); cb()
  }
}

const closeUserDropdown = () => {
  userDropdownOpen.value = false
  closeMobileMenu()
}

const getRoleText = () => {
  const role = authStore.user?.role?.name
  switch(role) {
    case 'admin': return 'Quản trị viên'
    case 'teacher': return 'Giảng viên'
    case 'student': return 'Học viên'
    default: return 'Người dùng'
  }
}

const getAvatarInitials = () => {
  const name = authStore.user?.name || 'User'
  return name.split(' ')
    .map(word => word.charAt(0))
    .join('')
    .substring(0, 2)
    .toUpperCase()
}
</script>

<style>
#app {
  min-height: 100vh;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Glass Navigation Bar */
.glass-navbar {
  z-index: 1000;
}

.navbar-wrapper {
  max-width: 1400px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: auto 1fr auto auto;
  align-items: center;
  gap: 2rem;
  padding: 0 2rem;
  height: 100%;
}

/* Brand Section */
.navbar-brand-section {
  display: flex;
  align-items: center;
}

.brand-link {
  display: flex;
  align-items: center;
  gap: 1rem;
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.brand-link:hover {
  transform: translateY(-2px);
}

.brand-logo {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
}

.logo-icon {
  position: relative;
  z-index: 2;
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  font-weight: 600;
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.logo-glow {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  filter: blur(10px);
  opacity: 0.3;
  transition: all 0.3s ease;
  z-index: 1;
}

.brand-link:hover .logo-glow {
  opacity: 0.6;
  transform: scale(1.1);
}

.brand-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.brand-title {
  font-size: 1.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
}

.brand-subtitle {
  font-size: 0.75rem;
  color: rgba(55, 65, 81, 0.7);
  font-weight: 500;
  line-height: 1;
}

/* Navigation Links */
.navbar-navigation {
  display: flex;
  justify-content: center;
}

.nav-links-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 0.5rem;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.nav-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  text-decoration: none;
  color: rgba(55, 65, 81, 0.8);
  font-weight: 600;
  font-size: 0.9rem;
  border-radius: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.nav-item::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1;
}

.nav-item:hover::before,
.nav-item.router-link-active::before {
  opacity: 1;
}

.nav-item:hover {
  transform: translateY(-2px);
  color: white;
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.nav-item.router-link-active {
  color: white;
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.nav-icon {
  position: relative;
  z-index: 2;
  font-size: 1.1rem;
  transition: all 0.3s ease;
}

.nav-text {
  position: relative;
  z-index: 2;
  white-space: nowrap;
}

.nav-indicator {
  position: absolute;
  bottom: -2px;
  left: 50%;
  transform: translateX(-50%) scaleX(0);
  width: 30px;
  height: 3px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 2px;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-item.router-link-active .nav-indicator {
  transform: translateX(-50%) scaleX(1);
}

/* User Profile Section */
.navbar-user-section {
  position: relative;
  z-index: 2000;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  min-width: 200px;
}

.user-profile:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.user-profile.active {
  background: rgba(255, 255, 255, 0.2);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.user-avatar-container {
  position: relative;
}

.user-avatar {
  position: relative;
  width: 45px;
  height: 45px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  opacity: 0.9;
}

.avatar-text {
  position: relative;
  z-index: 2;
  color: white;
  font-weight: 700;
  font-size: 1rem;
}

.avatar-status {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 12px;
  height: 12px;
  background: #10b981;
  border: 2px solid white;
  border-radius: 50%;
}

.user-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}

.user-name {
  font-weight: 700;
  font-size: 0.95rem;
  color: rgba(17, 24, 39, 0.9);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: rgba(55, 65, 81, 0.7);
  font-weight: 500;
}

.user-role-badge i {
  font-size: 0.8rem;
  color: #10b981;
}

.dropdown-toggle-icon {
  color: rgba(55, 65, 81, 0.6);
  font-size: 0.9rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dropdown-toggle-icon .rotated {
  transform: rotate(180deg);
}

/* User Dropdown Menu */
.user-dropdown-menu {
  position: fixed;
  top: calc(80px + 12px);
  right: 2rem;
  min-width: 280px;
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(25px);
  -webkit-backdrop-filter: blur(25px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 20px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px) scale(0.95);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 10000;
  overflow: hidden;
}

.user-dropdown-menu.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(0) scale(1);
}

.dropdown-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.dropdown-avatar {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 1.1rem;
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.dropdown-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.dropdown-name {
  font-weight: 700;
  font-size: 1.1rem;
  color: rgba(17, 24, 39, 0.9);
}

.dropdown-email {
  font-size: 0.85rem;
  color: rgba(55, 65, 81, 0.7);
}

.dropdown-option {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  text-decoration: none;
  color: rgba(55, 65, 81, 0.8);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.dropdown-option::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  width: 4px;
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  transform: scaleY(0);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dropdown-option:hover::before {
  transform: scaleY(1);
}

.dropdown-option:hover {
  background: rgba(102, 126, 234, 0.05);
  color: rgba(17, 24, 39, 0.9);
  transform: translateX(8px);
}

.dropdown-option.logout:hover {
  background: rgba(239, 68, 68, 0.05);
  color: #dc2626;
}

.option-icon {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  color: rgba(102, 126, 234, 0.7);
  transition: all 0.3s ease;
}

.dropdown-option:hover .option-icon {
  color: #667eea;
  transform: scale(1.1);
}

.dropdown-option.logout:hover .option-icon {
  color: #dc2626;
}

.option-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.option-title {
  font-weight: 600;
  font-size: 0.95rem;
}

.option-desc {
  font-size: 0.8rem;
  color: rgba(107, 114, 128, 0.8);
}

.dropdown-divider {
  height: 1px;
  background: rgba(229, 231, 235, 0.6);
  margin: 0.5rem 0;
}

/* Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.spinner-border {
  width: 3rem;
  height: 3rem;
  color: #667eea !important;
}

/* Mobile Menu Toggle */
.mobile-menu-toggle {
  display: none;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  width: 44px;
  height: 44px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mobile-menu-toggle:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.05);
}

.hamburger-line {
  width: 20px;
  height: 2px;
  background: rgba(55, 65, 81, 0.8);
  border-radius: 2px;
  margin: 2px 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mobile-menu-toggle.active .hamburger-line:nth-child(1) {
  transform: rotate(45deg) translate(5px, 5px);
}

.mobile-menu-toggle.active .hamburger-line:nth-child(2) {
  opacity: 0;
  transform: scale(0);
}

.mobile-menu-toggle.active .hamburger-line:nth-child(3) {
  transform: rotate(-45deg) translate(7px, -6px);
}

/* Content Padding */
.container-fluid {
  padding-top: 80px;
  overflow-x: hidden;
}

/* Prevent horizontal overflow globally */
* {
  box-sizing: border-box;
}

.row {
  margin-left: 0;
  margin-right: 0;
}

.container-fluid {
  padding-left: 0;
  padding-right: 0;
}

/* Mobile Responsive */
@media (max-width: 1024px) {
  .navbar-wrapper {
    grid-template-columns: auto 1fr auto;
    gap: 1rem;
    padding: 0 1.5rem;
  }
  
  .navbar-navigation {
    display: none;
  }
  
  .mobile-menu-toggle {
    display: flex;
  }
  
  .navbar-navigation.mobile-open {
    display: block;
    position: fixed;
    top: 80px;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(25px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    z-index: 999;
  }
  
  .nav-links-container {
    flex-direction: column;
    gap: 0.75rem;
    background: transparent;
    border: none;
    padding: 0;
  }
  
  .nav-item {
    width: 100%;
    justify-content: flex-start;
    padding: 1rem 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
  }
}

@media (max-width: 768px) {
  .glass-navbar {
    height: 70px;
  }
  
  .navbar-wrapper {
    padding: 0 1rem;
    gap: 0.75rem;
  }
  
  .brand-content {
    display: none;
  }
  
  .user-profile {
    min-width: auto;
    padding: 0.5rem;
  }
  
  .user-details {
    display: none;
  }
  
  .dropdown-toggle-icon {
    display: none;
  }
  
  .user-dropdown-menu {
    position: absolute;
    top: calc(100% + 12px);
    right: -1rem;
    min-width: 250px;
  }
  
  .container-fluid {
    padding-top: 70px;
  }
}

@media (max-width: 480px) {
  .navbar-wrapper {
    padding: 0 0.75rem;
  }
  
  .brand-logo {
    width: 40px;
    height: 40px;
  }
  
  .logo-icon {
    width: 40px;
    height: 40px;
    font-size: 1.2rem;
  }
}

/* Smooth scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #5a6fd8, #6a42a0);
}

/* Accessibility & dropdown refinements */
.user-dropdown-menu { will-change: transform, opacity; }
.dropdown-option { outline: none; }
.dropdown-option:focus-visible { background: rgba(102,126,234,0.12); transform: translateX(8px); }
.dropdown-option.logout:focus-visible { background: rgba(239,68,68,0.08); }
.user-profile:focus { box-shadow: 0 0 0 3px rgba(102,126,234,0.35); }
@media (prefers-reduced-motion: reduce) { .user-dropdown-menu { transition: none; } .dropdown-option { transition: none; } }
</style>
