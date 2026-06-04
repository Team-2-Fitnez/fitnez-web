<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'

type MenuItem = {
  label: string
  to?: string
  icon?: string
  external?: boolean
  submenu?: Array<{ label: string; to: string; icon?: string }>
}

const props = defineProps<{
  role: 'admin' | 'member' | 'trainer'
  title: string
  items: MenuItem[]
  collapsed?: boolean
}>()

const emit = defineEmits(['close'])
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const openSubmenu = ref<string | null>(null)

function close() {
  emit('close')
}

function toggleSubmenu(label: string) {
  openSubmenu.value = openSubmenu.value === label ? null : label
}

function handleSubmenuClick(item: MenuItem) {
  if (!item.submenu) return

  const isCurrentlyInSubmenu = item.submenu.some(sub => route.path === sub.to)
  
  if (isCurrentlyInSubmenu) {
    toggleSubmenu(item.label)
  } else {
    openSubmenu.value = item.label
    const targetPath = item.submenu[0].to
    if (targetPath) {
      router.push(targetPath)
    }
  }
}

async function logout() {
  await auth.logout()
  close()
  window.location.href = '/'
}

// Compute professional subtitle for workspace profile card
const workspaceSubtitle = computed(() => {
  switch (props.role) {
    case 'admin':
      return 'Workspace Administrator'
    case 'trainer':
      return 'Certified Fitness Trainer'
    default:
      return 'Fitnez Premium Member'
  }
})

// Auto open parent submenu if child is currently active
function checkActiveSubmenu() {
  for (const item of props.items) {
    if (item.submenu) {
      const hasActiveChild = item.submenu.some(sub => route.path === sub.to)
      if (hasActiveChild) {
        openSubmenu.value = item.label
        break
      }
    }
  }
}

onMounted(() => {
  checkActiveSubmenu()
})

watch(() => route.path, () => {
  checkActiveSubmenu()
})

function getAdminIcon(label: string, fallback: string = '•') {
  const cleanLabel = label.toLowerCase().trim()
  switch (cleanLabel) {
    case 'home':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>`
    case 'dashboard':
    case 'dasbor':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>`
    case 'payment review':
    case 'tinjauan pembayaran':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>`
    case 'users':
    case 'pengguna':
    case 'members':
    case 'anggota':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>`
    case 'payments':
    case 'pembayaran':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>`
    case 'trainer applications':
    case 'aplikasi pelatih':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>`
    case 'landing visitors':
    case 'pengunjung':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>`
    case 'auth activity':
    case 'aktivitas autentikasi':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>`
    case 'admin reports':
    case 'laporan admin':
    case 'trainer reports':
    case 'laporan pelatih':
    case 'reports':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>`
    case 'nutrition monitoring':
    case 'pemantauan nutrisi':
    case 'meal plan':
    case 'rencana makanan':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>`
    case 'workout plan':
    case 'rencana latihan':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 7.5v9M17.5 7.5v9M2 9v6a1 1 0 001 1h2.5a1 1 0 001-1V9a1 1 0 00-1-1H3a1 1 0 00-1 1zm16.5 0v6a1 1 0 001 1H22a1 1 0 002-2V9a1 1 0 00-1-1h-2.5a1 1 0 00-1 1zM6.5 12h11" /></svg>`
    case 'hire a trainer':
    case 'hire trainer':
    case 'register as trainer':
    case 'rekrut pelatih':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`
    case 'schedule':
    case 'jadwal':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>`
    case 'attendance':
    case 'kehadiran':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>`
    case 'classes':
    case 'kelas':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>`
    case 'chat':
    case 'obrolan':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>`
    case 'memberships':
    case 'keanggotaan':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>`
    case 'profile':
    case 'profil':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>`
    case 'notifications':
    case 'notifikasi':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>`
    default:
      return `<span class="sidebar-icon">${fallback}</span>`
  }
}
</script>

<template>
  <div class="h-full bg-[#0B1120] text-gray-300 flex flex-col justify-between rounded-2xl border border-gray-800">
    <div class="p-6 overflow-y-auto scrollbar-hide flex-1">
      <!-- Profile Section (Unified & Dynamic) -->
      <div class="flex items-center gap-4 bg-[#1F2937] p-4 rounded-xl mb-8 border border-gray-700 shadow-sm">
        <div class="w-12 h-12 bg-[#fdf4e3] rounded-full flex items-center justify-center text-black font-extrabold text-xl flex-shrink-0 capitalize">
          {{ auth.user?.full_name ? auth.user.full_name.charAt(0) : role.charAt(0) }}
        </div>
        <div v-if="!collapsed" class="flex flex-col justify-center overflow-hidden">
          <div class="text-[10px] font-bold tracking-[0.15em] text-gray-400 uppercase mb-0.5">FITNEZ</div>
          <div class="font-extrabold text-white text-base truncate leading-none mb-1 capitalize">
            {{ auth.user?.full_name || role }}
          </div>
          <div class="text-[11px] text-gray-400 font-medium">
            {{ workspaceSubtitle }}
          </div>
        </div>
      </div>

      <nav class="space-y-1.5">
        <template v-for="item in items" :key="item.label">
          <!-- Menu with submenu -->
          <div v-if="item.submenu">
            <button
              type="button"
              :class="[
                'w-full flex items-center justify-between gap-3 px-4 py-3 hover:bg-[#1F2937] hover:text-white rounded-xl transition-colors font-medium text-sm',
                item.submenu.some(sub => route.path === sub.to)
                  ? 'text-white bg-[#1F2937]'
                  : 'text-gray-400'
              ]"
              style="text-align: left;"
              @click="handleSubmenuClick(item)"
            >
              <span style="display: flex; align-items: center; gap: 0.75rem;">
                <span v-html="getAdminIcon(item.label, item.icon)"></span>
                <span v-if="!collapsed">{{ item.label }}</span>
              </span>
              <span v-if="!collapsed" style="transition: transform 0.2s;" :style="{ transform: openSubmenu === item.label ? 'rotate(180deg)' : 'rotate(0deg)' }">▼</span>
            </button>
            <div v-if="openSubmenu === item.label && !collapsed" style="padding-left: 1rem; margin-top: 0.25rem;">
              <RouterLink
                v-for="sub in item.submenu"
                :key="sub.to"
                :to="sub.to"
                :class="[
                  route.path === sub.to
                    ? 'flex items-center gap-3 px-4 py-2.5 bg-[#fdf4e3] text-black rounded-lg font-semibold shadow-sm transition-colors text-xs'
                    : 'flex items-center gap-3 px-4 py-2.5 hover:bg-[#1F2937] hover:text-white rounded-lg transition-colors font-medium text-xs text-gray-400'
                ]"
                @click="close"
              >
                <span v-html="getAdminIcon(sub.label, sub.icon)"></span>
                <span>{{ sub.label }}</span>
              </RouterLink>
            </div>
          </div>

          <!-- Regular menu without submenu -->
          <a
            v-else-if="item.external"
            :href="item.to!"
            class="flex items-center gap-3 px-4 py-3 hover:bg-[#1F2937] hover:text-white rounded-xl transition-colors font-medium text-sm text-gray-400"
            @click="close"
          >
            <span v-html="getAdminIcon(item.label, item.icon)"></span>
            <span v-if="!collapsed">{{ item.label }}</span>
          </a>
          <RouterLink
            v-else
            :to="item.to!"
            :class="[
              route.path === item.to
                ? 'flex items-center gap-3 px-4 py-3 bg-[#fdf4e3] text-black rounded-xl font-semibold shadow-sm transition-colors text-sm'
                : 'flex items-center gap-3 px-4 py-3 hover:bg-[#1F2937] hover:text-white rounded-xl transition-colors font-medium text-sm text-gray-400'
            ]"
            @click="close"
          >
            <span v-html="getAdminIcon(item.label, item.icon)"></span>
            <span v-if="!collapsed">{{ item.label }}</span>
          </RouterLink>
        </template>
      </nav>
    </div>

    <div class="p-6 pt-0">
      <button
        type="button"
        class="w-full flex items-center gap-3 px-4 py-3 hover:bg-red-500/10 hover:text-red-400 rounded-xl transition-colors font-medium text-sm text-gray-500"
        @click="logout"
      >
        <svg class="w-5 h-5 text-red-500/70 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
        <span v-if="!collapsed">Logout</span>
      </button>
    </div>
  </div>
</template>
