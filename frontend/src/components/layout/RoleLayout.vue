<script setup lang="ts">
import RoleSidebar from './RoleSidebar.vue'

type MenuItem = {
  label: string
  to?: string
  icon?: string
  submenu?: Array<{ label: string; to: string; icon?: string }>
}

defineProps<{
  role: 'admin' | 'member' | 'trainer'
  title: string
  subtitle?: string
  sidebarTitle: string
  sidebarItems: MenuItem[]
}>()

</script>

<template>
  <div class="fitnez-page min-h-screen">
    <RoleSidebar :role="role" :title="sidebarTitle" :items="sidebarItems" />

    <main class="min-h-screen p-4 lg:ml-72 lg:p-8">
      <div class="mx-auto max-w-7xl">
        <header class="mb-6 rounded-[1.75rem] bg-white/80 p-6 shadow-sm ring-1 ring-black/10 backdrop-blur">
          <p class="text-xs font-black uppercase tracking-[0.3em] text-[#365A82]">{{ role }}</p>
          <h1 class="mt-2 text-3xl font-black tracking-[-0.03em] md:text-5xl">{{ title }}</h1>
          <p v-if="subtitle" class="mt-3 max-w-3xl text-sm font-semibold leading-6 text-black/50 md:text-base">
            {{ subtitle }}
          </p>
        </header>

        <slot />
      </div>
    </main>
  </div>
</template>
