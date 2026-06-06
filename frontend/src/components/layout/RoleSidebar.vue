<script setup lang="ts">
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'

type MenuItem = {
  label: string
  to?: string
  icon?: string
  submenu?: Array<{ label: string; to: string; icon?: string }>
}

defineProps<{
  role: 'admin' | 'member' | 'trainer'
  title: string
  items: MenuItem[]
}>()

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

async function logout() {
  await auth.logout()
  await router.push('/login/member')
}

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
  <aside class="fitnez-sidebar fixed inset-y-0 left-0 z-30 hidden w-72 border-r border-white/10 lg:block bg-[#0B1120] text-gray-300">
    <div class="flex h-full flex-col">
      <div class="p-6">
        <div class="rounded-[1.4rem] bg-white/8 p-4">
          <p class="text-xs font-black uppercase tracking-[0.35em] text-[#DB854F]">Fitnez</p>
          <h1 class="mt-2 text-2xl font-black text-white">{{ title }}</h1>
          <p class="mt-1 text-sm font-semibold capitalize text-white/45">{{ role }} workspace</p>
        </div>
      </div>

      <nav class="fitnez-scrollbar flex-1 space-y-1 overflow-y-auto px-4 pb-4">
        <template v-for="item in items" :key="item.label">
          <RouterLink
            v-if="item.to"
            :to="item.to"
            :class="[
              'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold transition',
              route.path === item.to
              ? 'bg-[#F4E8E3] text-black'
              : 'text-white/70 hover:bg-white/10 hover:text-white',
          ]"
          >
            <span class="text-lg flex items-center justify-center w-5 h-5" v-html="getAdminIcon(item.label, item.icon)" />
            <span>{{ item.label }}</span>
          </RouterLink>

          <div v-else-if="item.submenu" class="space-y-1">
            <p class="px-4 py-2 text-xs font-black uppercase tracking-[0.25em] text-white/40">
              {{ item.label }}
            </p>

            <RouterLink
              v-for="child in item.submenu"
              :key="child.to"
              :to="child.to"
              :class="[
                'ml-3 flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold transition',
                route.path === child.to
                  ? 'bg-[#F4E8E3] text-black'
                  : 'text-white/70 hover:bg-white/10 hover:text-white',
              ]"
           >
              <span class="text-lg flex items-center justify-center w-5 h-5" v-html="getAdminIcon(child.label, child.icon)" />
              <span>{{ child.label }}</span>
            </RouterLink>
          </div>
        </template>
      </nav>

      <div class="p-4">
        <button type="button" class="block w-full rounded-2xl bg-white/10 px-4 py-3 text-left text-sm font-bold text-white/80 hover:bg-white/15" @click="logout">
          Logout / Switch Account
        </button>
      </div>
    </div>
  </aside>
</template>
