<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { http as api } from '../../api/http'
import { RouterLink } from 'vue-router'
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

type UnreadCountResponse = {
  count?: number
}

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
}>()

const notificationLink = computed(() => {
  switch (props.role) {
    case 'admin': return '/admin/notifications'
    case 'trainer': return '/trainer/notifications'
    default: return '/member/notifications'
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

onMounted(() => {

  if (typeof window !== 'undefined' && 'Notification' in window) {
    const hasAsked = localStorage.getItem('fitnez_notif_asked')
    if (Notification.permission === 'default' && !hasAsked) {

      setTimeout(() => {
        showPermissionPrompt.value = true
      }, 2000)
    }
  }
})

const requestPermission = async () => {
  if ('Notification' in window) {
    const permission = await Notification.requestPermission()
    localStorage.setItem('fitnez_notif_asked', 'true')
    showPermissionPrompt.value = false
    
    if (permission === 'granted') {
      console.log('Notification permission granted.')

    }
  }
}

const dismissPrompt = () => {
  localStorage.setItem('fitnez_notif_asked', 'true')
  showPermissionPrompt.value = false
}


const lastNotifCount = ref(0)
const pollNotifications = async () => {
  try {
    const { data } = await api.get<UnreadCountResponse>('/notifications/unread-count')
    const currentCount = data.count || 0

    if (currentCount > lastNotifCount.value) {
      const resp = await api.get<NotificationListResponse | NotificationItem[]>('/notifications?perPage=1')

      const latest = Array.isArray(resp.data)
        ? resp.data[0]
        : resp.data.data?.[0]

      if (latest && !latest.is_read) {
        window.showFitnezToast(`🔔 ${latest.title || 'Notifikasi'}: ${latest.body || ''}`, 'info')
      }
    }

    lastNotifCount.value = currentCount
  } catch (error) {
    console.error('Failed to poll notifications:', error)
  }
}

let pollInterval: ReturnType<typeof setInterval> | null = null

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
})

onUnmounted(() => {
  if (pollInterval !== null) clearInterval(pollInterval)
})

</script>

<template>
  <div class="page workspace-layout" :class="{ 'sidebar-collapsed': isCollapsed }">
    <aside class="sidebar-shell" :class="{ 'collapsed': isCollapsed }">
      <WorkspaceSidebar :role="role" :title="sidebarTitle" :items="sidebarItems" :collapsed="isCollapsed" @close="mobileOpen = false" />
    </aside>

    <div v-if="mobileOpen" class="mobile-drawer">
      <button class="mobile-overlay" type="button" aria-label="Close menu" @click="mobileOpen = false" />
      <aside class="mobile-sidebar">
        <WorkspaceSidebar :role="role" :title="sidebarTitle" :items="sidebarItems" @close="mobileOpen = false" />
      </aside>
    </div>

    <main class="workspace-main">
      <div class="workspace-container">
        <!-- Toast Notification -->
        <transition name="fade">
          <div v-if="activeToast" :class="['toast-popup', activeToast.type]">
            <div class="toast-content">
              <span class="toast-icon">{{ activeToast.type === 'success' ? '✅' : '❌' }}</span>
              <p>{{ activeToast.message }}</p>
            </div>
          </div>
        </transition>

        <header class="workspace-header">
          <button type="button" class="menu-button" @click="toggleSidebar" aria-label="Toggle Menu">
            <div class="hamburger-lines">
              <span class="line line1"></span>
              <span class="line line2"></span>
              <span class="line line3"></span>
            </div>
          </button>

          <div>
            <p class="eyebrow">{{ role }}</p>
            <h1 class="title-lg">{{ title }}</h1>
            <p v-if="subtitle" class="text-muted">{{ subtitle }}</p>
          </div>

          <div class="header-right-panel">
            <RouterLink :to="notificationLink" class="notification-trigger" title="Notifications">
              <span class="bell-icon">🔔</span>
              <span class="notification-badge"></span>
            </RouterLink>
            
            <div class="panel">
              <p class="stat-label">Workspace</p>
              <p class="title-md">{{ role }}</p>
            </div>
          </div>
        </header>

        <slot />
      </div>
    </main>

    <!-- Notification Permission Prompt -->
    <transition name="fade">
      <div v-if="showPermissionPrompt" class="notif-permission-toast card">
        <div class="flex items-start gap-4">
          <div class="notif-prompt-icon">🔔</div>
          <div class="flex-1">
            <h4 class="font-bold text-sm">Aktifkan Notifikasi?</h4>
            <p class="text-xs text-muted mt-1">Jangan lewatkan pengingat latihan dan update dari trainer Anda.</p>
            <div class="flex gap-2 mt-3">
              <button @click="requestPermission" class="button button-small">Ijinkan</button>
              <button @click="dismissPrompt" class="button button-small button-ghost">Nanti</button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>
