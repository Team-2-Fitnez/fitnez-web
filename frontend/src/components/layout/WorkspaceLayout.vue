<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { http as api } from '../../api/http'
import { RouterLink } from 'vue-router'
import { connectSocket, getSocket } from '../../services/socket'
import { useAuthStore } from '../../stores/authStore'
import { usePushNotifications } from '../../composables/usePushNotifications'
import WorkspaceSidebar from './WorkspaceSidebar.vue'

type MenuItem = {
  label: string
  to?: string
  icon?: string
  submenu?: Array<{ label: string; to: string; icon?: string }>
}

type ToastState = {
  message: string
  type: FitnezToastType
} | null

type NotificationItem = {
  id?: number
  title?: string
  body?: string
  is_read?: boolean
}

type NotificationListResponse = {
  data?: NotificationItem[]
}

const props = defineProps<{
  role: 'admin' | 'member' | 'trainer'
  title: string
  subtitle?: string
  sidebarTitle: string
  sidebarItems: MenuItem[]
  hideHeader?: boolean
}>()

const notificationLink = computed(() => {
  switch (props.role) {
    case 'admin': return '/admin/notifications'
    case 'trainer': return '/trainer/notifications'
    default: return '/member/notifications'
  }
})

const roleLabel = computed(() => {
  switch (props.role) {
    case 'admin': return 'Administrator'
    case 'trainer': return 'Fitness Trainer'
    default: return 'Gym Member'
  }
})

const mobileOpen = ref(false)
const isCollapsed = ref(false)
const showPermissionPrompt = ref(false)

const activeToast = ref<ToastState>(null)

window.showFitnezToast = (message: string, type: FitnezToastType = 'success') => {
  activeToast.value = { message, type }

  setTimeout(() => {
    activeToast.value = null
  }, 4000)
}

const toggleSidebar = () => {
  if (window.innerWidth < 1024) {
    mobileOpen.value = !mobileOpen.value
  } else {
    isCollapsed.value = !isCollapsed.value
  }
}

const requestPermission = async () => {
  if ('Notification' in window) {
    const permission = await Notification.requestPermission()
    localStorage.setItem('fitnez_notif_asked', 'true')
    showPermissionPrompt.value = false
    
    if (permission === 'granted') {
      try {
        await push.subscribe()
      } catch {
        // push subscription failed silently
      }
    }
  }
}

const dismissPrompt = () => {
  localStorage.setItem('fitnez_notif_asked', 'true')
  showPermissionPrompt.value = false
}

const push = usePushNotifications()

function loadSeenNotifIds(): Set<number> {
  try {
    const raw = localStorage.getItem('fitnez_seen_notif_ids')
    return new Set<number>(raw ? JSON.parse(raw) : [])
  } catch {
    return new Set<number>()
  }
}
function saveSeenNotifIds(ids: Set<number>) {
  localStorage.setItem('fitnez_seen_notif_ids', JSON.stringify([...ids]))
}

const hasUnread = ref(false)
let seenNotifIds = loadSeenNotifIds()
const pollNotifications = async () => {
  try {
    const resp = await api.get<NotificationListResponse | NotificationItem[]>('/notifications?perPage=5')
    const items = Array.isArray(resp.data) ? resp.data : (resp.data.data ?? [])

    const unreadExists = items.some(item => !item.is_read)
    hasUnread.value = unreadExists

    for (const item of items) {
      if (item.id == null) continue
      if (item.is_read) continue
      if (seenNotifIds.has(item.id)) continue

      seenNotifIds = new Set([...seenNotifIds, item.id])
      saveSeenNotifIds(seenNotifIds)
      window.showFitnezToast(`🔔 ${item.title || 'Notification'}: ${item.body || ''}`, 'info')
    }
  } catch {
    window.showFitnezToast('Failed to load notifications.', 'error')
  }
}

let pollInterval: ReturnType<typeof setInterval> | null = null
let socketIoCleanup: (() => void) | null = null

onMounted(() => {
  pollNotifications()
  pollInterval = setInterval(pollNotifications, 15000)

  if (typeof window !== 'undefined' && 'Notification' in window) {
    const hasAsked = localStorage.getItem('fitnez_notif_asked')
    if (Notification.permission === 'default' && !hasAsked) {
      setTimeout(() => {
        showPermissionPrompt.value = true
      }, 2000)
    }
  }

  push.init()

  const authStore = useAuthStore()
  const userId = authStore.user?.id
  const token = localStorage.getItem('fitnez_access_token')

  if (userId && token) {
    try {
      const socket = getSocket() || connectSocket(token)

      const handler = (e: { id: number; title: string; body: string }) => {
        if (seenNotifIds.has(e.id)) return
        seenNotifIds = new Set([...seenNotifIds, e.id])
        saveSeenNotifIds(seenNotifIds)
        hasUnread.value = true
        window.showFitnezToast(`🔔 ${e.title}: ${e.body}`, 'info')
      }

      socket.on(`notifications-${userId}-new-notification`, handler)
      socketIoCleanup = () => {
        socket.off(`notifications-${userId}-new-notification`, handler)
      }
    } catch {
      // Socket.io not available; polling will catch notifications
    }
  }
})

onUnmounted(() => {
  if (pollInterval !== null) clearInterval(pollInterval)
  if (socketIoCleanup) socketIoCleanup()
})

</script>

<template>
  <div class="flex h-screen overflow-hidden text-gray-800 admin-workspace w-full md:gap-4 xl:gap-5" :class="{ 'sidebar-collapsed': isCollapsed }">
    <!-- Desktop Sidebar (Unified Floating Style) -->
    <aside
      :class="['text-gray-300 flex flex-col justify-between hidden md:flex flex-shrink-0 z-20 shadow-xl my-4 ml-4 transition-all duration-300', isCollapsed ? 'w-0 ml-0 overflow-hidden' : 'w-[280px]']">
      <WorkspaceSidebar :role="role" :title="sidebarTitle" :items="sidebarItems" :collapsed="isCollapsed" @close="mobileOpen = false" />
    </aside>

    <!-- Mobile Drawer -->
    <div v-if="mobileOpen" class="mobile-drawer">
      <button class="mobile-overlay" type="button" aria-label="Close menu" @click="mobileOpen = false" />
      <aside class="mobile-sidebar bg-[#0B1120] p-4 h-full">
        <WorkspaceSidebar :role="role" :title="sidebarTitle" :items="sidebarItems" @close="mobileOpen = false" />
      </aside>
    </div>

    <!-- Main Workspace Container (Unified Style) -->
    <main class="workspace-main flex-1 flex flex-col h-screen overflow-y-auto p-4 md:py-6 md:pl-0 md:pr-6 xl:py-7 xl:pr-7">
      <!-- Toast Notification -->
      <transition name="fade">
        <div v-if="activeToast" :class="['toast-popup', activeToast.type]" style="z-index: 9999;">
          <div class="toast-content">
            <span class="toast-icon">{{ activeToast.type === 'success' ? '✅' : '❌' }}</span>
            <p>{{ activeToast.message }}</p>
          </div>
        </div>
      </transition>

      <div class="workspace-content w-full max-w-[1280px] mx-auto flex-1 flex flex-col">
        <!-- Header Section -->
        <header v-if="!hideHeader" class="workspace-header bg-white rounded-3xl p-6 md:px-8 md:py-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8 border border-gray-100 flex-shrink-0">
          <div class="flex items-center gap-4">
            <!-- Hamburger Menu for Mobile -->
            <button class="bg-[#111827] text-white p-2.5 rounded-full flex-shrink-0 md:hidden" @click="mobileOpen = true">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
            </button>
            <div>
              <div class="text-xs font-bold tracking-[0.15em] text-blue-600/70 uppercase mb-1">{{ roleLabel }}</div>
              <h1 class="text-3xl md:text-4xl font-black text-[#111827] tracking-tight mb-1.5">{{ title }}</h1>
              <p v-if="subtitle" class="text-sm font-medium text-gray-500">{{ subtitle }}</p>
            </div>
          </div>
          <div class="flex items-center gap-5 w-full md:w-auto justify-end">
            <!-- Notification Bell -->
            <RouterLink :to="notificationLink" class="relative p-3 bg-white border border-gray-200 rounded-full hover:bg-gray-50 transition-colors shadow-sm flex items-center justify-center">
              <span v-if="hasUnread" class="absolute top-2.5 right-2.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white animate-ping"></span>
              <span v-if="hasUnread" class="absolute top-2.5 right-2.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
              <svg class="w-5 h-5" :class="hasUnread ? 'text-red-500 animate-pulse' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
            </RouterLink>
          </div>
        </header>

        <!-- Slot Content -->
        <slot />
      </div>
    </main>

    <!-- Notification Permission Prompt -->
    <transition name="fade">
      <div v-if="showPermissionPrompt" class="notif-permission-toast card">
        <div class="flex items-start gap-4">
          <div class="notif-prompt-icon">🔔</div>
          <div class="flex-1">
            <h4 class="font-bold text-sm">Enable Notifications?</h4>
            <p class="text-xs text-muted mt-1">Don't miss workout reminders and updates from your trainer.</p>
            <div class="flex gap-2 mt-3">
              <button @click="requestPermission" class="button button-small">Allow</button>
              <button @click="dismissPrompt" class="button button-small button-ghost">Later</button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap');

.admin-workspace {
  font-family: 'Outfit', sans-serif !important;
  background-color: #f0f4f8 !important;
  background-image: radial-gradient(circle at top right, #fdfbf7 0%, #f0f4f8 100%) !important;
}

/* Global premium overrides for all workspace pages (admin, member, trainer) */
.admin-workspace .card {
  background: #ffffff !important;
  border: 1px solid #f1f5f9 !important;
  border-radius: 1.5rem !important; /* rounded-3xl */
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05) !important; /* shadow-sm */
  padding: 1.5rem !important;
}

.admin-workspace .panel {
  background: #ffffff !important;
  border: 1px solid #f1f5f9 !important;
  border-radius: 1.5rem !important; /* rounded-3xl */
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05) !important; /* shadow-sm */
  padding: 1.5rem !important;
}

/* Primary buttons in all workspaces */
.admin-workspace .button-primary,
.admin-workspace .button:not(.button-ghost):not(.button-danger):not(.button-danger-ghost):not(.cookie-settings-button) {
  background: #2563eb !important; /* blue-600 */
  color: #ffffff !important;
  border-radius: 0.75rem !important; /* rounded-xl */
  font-weight: 700 !important;
  transition: all 0.2s ease !important;
  border: none !important;
}

.admin-workspace .button-primary:hover,
.admin-workspace .button:not(.button-ghost):not(.button-danger):not(.button-danger-ghost):not(.cookie-settings-button):hover {
  background: #1d4ed8 !important; /* blue-700 */
}

/* Secondary / Ghost buttons */
.admin-workspace .button-ghost,
.admin-workspace .button-secondary {
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important; /* border-gray-200 */
  color: #334155 !important; /* text-gray-700 */
  border-radius: 0.75rem !important; /* rounded-xl */
  font-weight: 700 !important;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important; /* shadow-sm */
  transition: all 0.2s ease !important;
}

.admin-workspace .button-ghost:hover,
.admin-workspace .button-secondary:hover {
  background: #f8fafc !important; /* bg-gray-50 */
  border-color: #cbd5e1 !important;
}

/* Form fields & inputs */
.admin-workspace .form-input,
.admin-workspace input[type="text"],
.admin-workspace input[type="email"],
.admin-workspace input[type="password"],
.admin-workspace input[type="number"],
.admin-workspace select,
.admin-workspace textarea {
  background-color: #f8fafc !important; /* bg-gray-50 */
  border: 1px solid #e2e8f0 !important; /* border-gray-200 */
  border-radius: 0.75rem !important; /* rounded-xl */
  padding: 0.5rem 1rem !important; /* py-2 px-4 */
  font-size: 0.875rem !important; /* text-sm */
  font-weight: 700 !important;
  color: #475569 !important; /* text-gray-600 */
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important; /* shadow-sm */
  transition: all 0.2s ease !important;
}

.admin-workspace .form-input:focus,
.admin-workspace input:focus,
.admin-workspace select:focus,
.admin-workspace textarea:focus {
  border-color: #3b82f6 !important; /* blue-500 */
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
  outline: none !important;
}

.admin-workspace .form-label {
  color: #1e293b !important; /* text-gray-800 */
  font-weight: 700 !important;
  font-size: 0.875rem !important;
  margin-bottom: 0.375rem !important;
}

/* Data tables in all workspaces */
.admin-workspace .data-table-wrapper {
  background: #ffffff !important;
  border: 1px solid #f1f5f9 !important;
  border-radius: 1.5rem !important; /* rounded-3xl */
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05) !important;
  padding: 0.5rem !important;
}

.admin-workspace .data-table {
  min-width: 100% !important;
  border-collapse: collapse !important;
}

.admin-workspace .data-table th {
  background: #f8fafc !important; /* bg-gray-50 */
  color: #475569 !important; /* text-gray-600 */
  font-size: 0.75rem !important;
  font-weight: 700 !important;
  letter-spacing: 0.05em !important;
  padding: 0.75rem 1rem !important;
  border-bottom: 1px solid #f1f5f9 !important;
  border-radius: 0 !important; /* clear old border-radius */
}

.admin-workspace .data-table td {
  border-bottom: 1px solid #f1f5f9 !important;
  color: #334155 !important;
  font-size: 0.875rem !important;
  padding: 1rem !important;
  vertical-align: middle !important;
}

.admin-workspace .data-table tr:hover td {
  background-color: #f8fafc !important;
}

/* Alert notifications */
.admin-workspace .alert {
  border-radius: 0.75rem !important;
  font-weight: 700 !important;
}

.admin-workspace .alert-info {
  background-color: #eff6ff !important;
  color: #1e40af !important;
  border: 1px solid #bfdbfe !important;
}

.admin-workspace .alert-success {
  background-color: #f0fdf4 !important;
  color: #166534 !important;
  border: 1px solid #bbf7d0 !important;
}

.admin-workspace .alert-error {
  background-color: #fef2f2 !important;
  color: #991b1b !important;
  border: 1px solid #fecaca !important;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.admin-workspace,
.admin-workspace * {
  box-sizing: border-box;
}

.admin-workspace .workspace-main {
  margin-left: 0;
  min-width: 0;
}

.admin-workspace .workspace-content {
  min-width: 0;
}

.admin-workspace:not(.sidebar-collapsed) .workspace-main {
  padding-left: 0;
  padding-right: clamp(1rem, 2vw, 1.75rem);
}

.admin-workspace .workspace-header {
  overflow: hidden;
}

.admin-workspace .workspace-header h1 {
  overflow-wrap: anywhere;
}

.admin-workspace img,
.admin-workspace video,
.admin-workspace canvas,
.admin-workspace svg {
  max-width: 100%;
}

.admin-workspace table {
  min-width: 720px;
}

.admin-workspace [class*="grid-cols-4"],
.admin-workspace [class*="grid-cols-5"] {
  min-width: 0;
}

.mobile-drawer {
  position: fixed;
  inset: 0;
  z-index: 80;
}

.mobile-overlay {
  background: rgba(15, 23, 42, 0.54);
  border: 0;
  inset: 0;
  position: absolute;
}

.mobile-sidebar {
  left: 0;
  max-width: min(22rem, calc(100vw - 2rem));
  overflow-y: auto;
  position: absolute;
  top: 0;
  width: 86vw;
}

@media (max-width: 1024px) {
  .admin-workspace:not(.sidebar-collapsed) .workspace-main {
    padding-left: 0;
    padding-right: 1.25rem;
  }

  .admin-workspace .workspace-main {
    padding: 1.25rem !important;
  }

  .admin-workspace .workspace-header {
    border-radius: 1.25rem !important;
    gap: 1rem !important;
    margin-bottom: 1.25rem !important;
    padding: 1.25rem !important;
  }

  .admin-workspace .workspace-header h1 {
    font-size: clamp(1.65rem, 5vw, 2.35rem) !important;
    line-height: 1.1 !important;
  }
}

@media (max-width: 768px) {
  .admin-workspace {
    height: auto !important;
    min-height: 100vh !important;
    overflow: visible !important;
  }

  .admin-workspace .workspace-main {
    height: auto !important;
    min-height: 100vh !important;
    overflow-x: hidden !important;
    padding: 0.9rem !important;
  }

  .admin-workspace .card,
  .admin-workspace .panel {
    border-radius: 1rem !important;
    padding: 1rem !important;
  }

  .admin-workspace .workspace-header {
    align-items: stretch !important;
  }

  .admin-workspace .workspace-header > div {
    width: 100%;
  }

  .admin-workspace .workspace-header h1 {
    font-size: 1.7rem !important;
  }

  .admin-workspace .workspace-header p {
    overflow-wrap: anywhere;
  }

  .admin-workspace input,
  .admin-workspace select,
  .admin-workspace textarea,
  .admin-workspace button,
  .admin-workspace a {
    max-width: 100%;
  }

  .admin-workspace .data-table-wrapper,
  .admin-workspace [class*="table"],
  .admin-workspace [class*="overflow-x-auto"] {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
  }
}
</style>
