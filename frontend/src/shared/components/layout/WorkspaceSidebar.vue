<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/features/Auth/stores/authStore'

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

const emit = defineEmits<{
  close: []
}>()

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const manualToggle = ref<string | null>(null)

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

const openSubmenu = computed(() => {
  const parent = props.items.find(item => item.submenu?.some(sub => route.path === sub.to))
  return parent?.label ?? manualToggle.value
})

function close() {
  emit('close')
}

function toggleSubmenu(label: string) {
  manualToggle.value = manualToggle.value === label ? null : label
}

function handleSubmenuClick(item: MenuItem) {
  if (!item.submenu?.length) return

  const isActive = item.submenu.some(sub => route.path === sub.to)
  if (isActive) {
    toggleSubmenu(item.label)
    return
  }

  manualToggle.value = item.label
  router.push(item.submenu[0].to)
}

async function logout() {
  await auth.logout()
  close()
  window.location.href = '/'
}

watch(
  () => route.path,
  () => {
    const activeParent = props.items.find(item => item.submenu?.some(sub => route.path === sub.to))
    if (activeParent) manualToggle.value = activeParent.label
  },
  { immediate: true },
)

function getAdminIcon(label: string, fallback = '.') {
  const cleanLabel = label.toLowerCase().trim()
  switch (cleanLabel) {
    case 'home':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9M5 10v10h14V10M9 21v-6h6v6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'dashboard':
    case 'dasbor':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 5h6v6H4V5zm10 0h6v6h-6V5zM4 15h6v4H4v-4zm10 0h6v4h-6v-4z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'payment review':
    case 'tinjauan pembayaran':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'users':
    case 'pengguna':
    case 'members':
    case 'anggota':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2m11-10a4 4 0 10-8 0 4 4 0 008 0zm7 10v-2a4 4 0 00-3-3.87m-2-8.26a4 4 0 010 7.75" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'payments':
    case 'pembayaran':
    case 'trainer reports':
    case 'laporan pelatih':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.66 0-3 .9-3 2s1.34 2 3 2 3 .9 3 2-1.34 2-3 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'trainer applications':
    case 'aplikasi pelatih':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 7h6m-8 4h10M7 15h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'check-in logs & reports':
    case 'check-in logs':
    case 'access logs':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5h6m-6 4h6m-7 4l2 2 4-4M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'visitor analytics':
    case 'visitor trends':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'visitor insights':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 11a4 4 0 10-8 0 4 4 0 008 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/><path d="M4 21a8 8 0 0116 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/><path d="M18 5v4m3-2h-6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'landing visitors':
    case 'pengunjung':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>`
    case 'auth activity':
    case 'aktivitas autentikasi':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 11V7a5 5 0 0110 0v4m-9 0h8a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2v-6a2 2 0 012-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'admin reports':
    case 'laporan admin':
    case 'reports':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 19V9m7 10V5m7 14v-7M3 19h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'nutrition monitoring':
    case 'pemantauan nutrisi':
    case 'meal plan':
    case 'rencana makanan':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 21s-7-4.35-7-10a4 4 0 017-2.65A4 4 0 0119 11c0 5.65-7 10-7 10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'workout plan':
    case 'rencana latihan':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 7v10m12-10v10M3 10v4m18-4v4M6 12h12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'hire a trainer':
    case 'hire trainer':
    case 'rekrut pelatih':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M16 11h6"/></svg>`
    case 'register as trainer':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>`
    case 'schedule':
    case 'jadwal':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'attendance':
    case 'kehadiran':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 11l2 2 4-4M5 3h14v18H5V3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'classes':
    case 'kelas':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 3L4 14h7l-1 7 10-12h-7l0-6z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'chat':
    case 'obrolan':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`
    case 'memberships':
    case 'keanggotaan':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8h18M7 15h1m4 0h1M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'profile':
    case 'profil':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 12a4 4 0 100-8 4 4 0 000 8zm7 9a7 7 0 00-14 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    case 'notifications':
    case 'notifikasi':
      return `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`
    default:
      return `<span class="sidebar-icon">${fallback}</span>`
  }
}
</script>

<template>
  <div class="h-full bg-[#0B1120] text-gray-300 flex flex-col justify-between rounded-2xl border border-gray-800">
    <div class="p-6 overflow-y-auto scrollbar-hide flex-1">
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
          <div v-if="item.submenu">
            <button
              type="button"
              :class="[
                'w-full flex items-center justify-between gap-3 px-4 py-3 hover:bg-[#1F2937] hover:text-white rounded-xl transition-colors font-medium text-sm',
                item.submenu.some(sub => route.path === sub.to) ? 'text-white bg-[#1F2937]' : 'text-gray-400',
              ]"
              @click="handleSubmenuClick(item)"
            >
              <span class="flex items-center gap-3">
                <span v-html="getAdminIcon(item.label, item.icon)" />
                <span v-if="!collapsed">{{ item.label }}</span>
              </span>
              <span v-if="!collapsed" class="text-[10px] transition-transform" :class="{ 'rotate-180': openSubmenu === item.label }">v</span>
            </button>
            <div v-if="openSubmenu === item.label && !collapsed" class="pl-4 mt-1 space-y-1">
              <RouterLink
                v-for="sub in item.submenu"
                :key="sub.to"
                :to="sub.to"
                :class="[
                  route.path === sub.to
                    ? 'flex items-center gap-3 px-4 py-2.5 bg-[#fdf4e3] text-black rounded-lg font-semibold shadow-sm transition-colors text-xs'
                    : 'flex items-center gap-3 px-4 py-2.5 hover:bg-[#1F2937] hover:text-white rounded-lg transition-colors font-medium text-xs text-gray-400',
                ]"
                @click="close"
              >
                <span v-html="getAdminIcon(sub.label, sub.icon)" />
                <span>{{ sub.label }}</span>
              </RouterLink>
            </div>
          </div>

          <a
            v-else-if="item.external"
            :href="item.to!"
            class="flex items-center gap-3 px-4 py-3 hover:bg-[#1F2937] hover:text-white rounded-xl transition-colors font-medium text-sm text-gray-400"
            @click="close"
          >
            <span v-html="getAdminIcon(item.label, item.icon)" />
            <span v-if="!collapsed">{{ item.label }}</span>
          </a>
          <RouterLink
            v-else
            :to="item.to!"
            :class="[
              route.path === item.to
                ? 'flex items-center gap-3 px-4 py-3 bg-[#fdf4e3] text-black rounded-xl font-semibold shadow-sm transition-colors text-sm'
                : 'flex items-center gap-3 px-4 py-3 hover:bg-[#1F2937] hover:text-white rounded-xl transition-colors font-medium text-sm text-gray-400',
            ]"
            @click="close"
          >
            <span v-html="getAdminIcon(item.label, item.icon)" />
            <span v-if="!collapsed">{{ item.label }}</span>
          </RouterLink>
        </template>
      </nav>
    </div>

    <div class="p-6 pt-0">
      <button
        type="button"
        class="w-full flex items-center gap-3 px-4 py-3 hover:bg-red-500/10 hover:text-red-400 rounded-xl transition-colors font-medium text-sm text-gray-500"
        :title="collapsed ? 'Logout' : undefined"
        @click="logout"
      >
        <svg class="w-5 h-5 text-red-500/70 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        <span v-if="!collapsed">Logout</span>
      </button>
    </div>
  </div>
</template>
