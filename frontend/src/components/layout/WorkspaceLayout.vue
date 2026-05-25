<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { http as api } from '../../api/http'
import { RouterLink } from 'vue-router'
import { connectSocket, getSocket, disconnectSocket } from '../../services/socket'
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

let seenNotifIds = loadSeenNotifIds()
const pollNotifications = async () => {
  try {
    const resp = await api.get<NotificationListResponse | NotificationItem[]>('/notifications?perPage=5')
    const items = Array.isArray(resp.data) ? resp.data : (resp.data.data ?? [])

    for (const item of items) {
      if (item.id == null) continue
      if (item.is_read) continue
      if (seenNotifIds.has(item.id)) continue

      seenNotifIds = new Set([...seenNotifIds, item.id])
      saveSeenNotifIds(seenNotifIds)
      window.showFitnezToast(`🔔 ${item.title || 'Notifikasi'}: ${item.body || ''}`, 'info')
    }
  } catch {
    window.showFitnezToast('Gagal memuat notifikasi.', 'error')
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
  const token = authStore.token || localStorage.getItem('fitnez_access_token')

  if (userId && token) {
    try {
      const socket = getSocket() || connectSocket(token)

      const handler = (e: { id: number; title: string; body: string }) => {
        if (seenNotifIds.has(e.id)) return
        seenNotifIds = new Set([...seenNotifIds, e.id])
        saveSeenNotifIds(seenNotifIds)
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
