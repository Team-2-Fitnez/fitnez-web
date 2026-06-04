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
  <div class="fitnez-page role-layout-page min-h-screen">
    <RoleSidebar :role="role" :title="sidebarTitle" :items="sidebarItems" />

    <main class="role-layout-main min-h-screen p-4 lg:p-6 xl:p-7">
      <div class="role-layout-content mx-auto max-w-[1280px]">
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

<style scoped>
.role-layout-page,
.role-layout-page * {
  box-sizing: border-box;
}

.role-layout-main {
  min-width: 0;
  overflow-x: hidden;
}

.role-layout-content {
  min-width: 0;
  width: 100%;
}

.role-layout-main {
  padding-inline: clamp(1rem, 2vw, 1.75rem);
}

.role-layout-page :deep(table) {
  min-width: 720px;
}

.role-layout-page :deep(.overflow-x-auto),
.role-layout-page :deep([class*="table"]) {
  -webkit-overflow-scrolling: touch;
}

@media (max-width: 1024px) {
  .role-layout-main {
    padding: 1.25rem !important;
  }
}

@media (min-width: 1024px) {
  .role-layout-main {
    padding-left: clamp(19rem, 21vw, 21rem);
    padding-right: clamp(1.25rem, 2vw, 2rem);
  }
}

@media (max-width: 720px) {
  .role-layout-main {
    padding: 0.9rem !important;
  }

  header {
    border-radius: 1.1rem !important;
    padding: 1rem !important;
  }

  header h1 {
    font-size: 1.75rem !important;
    line-height: 1.1 !important;
    overflow-wrap: anywhere;
  }

  header p {
    overflow-wrap: anywhere;
  }
}
</style>
