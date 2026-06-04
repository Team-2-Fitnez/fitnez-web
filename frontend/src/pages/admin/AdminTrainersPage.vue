<script setup lang="ts">
import RoleLayout from '../../components/layout/RoleLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
</script>

<template>
  <RoleLayout role="admin" sidebar-title="Admin" title="Trainers" subtitle="Manage trainer profiles, specialization, and availability." :sidebar-items="adminSidebarItems">
    <div class="grid gap-4 md:grid-cols-3">
      <StatCard label="Status" value="UI Ready" hint="Prepared page" />
      <StatCard label="Next Step" value="API" hint="Connect backend later" />
      <StatCard label="Access" value="Admin" hint="Protected route" />
    </div>

    <FitnezCard class="mt-5">
      <h2 class="text-2xl font-black">Trainers Module</h2>
      <p class="mt-3 max-w-3xl text-sm font-semibold leading-6 text-black/55">
        This page is intentionally prepared as a clean UI placeholder. Connect tables, filters, forms, and API logic when the feature backend is finalized.
      </p>
    </FitnezCard>
  </RoleLayout>
</template>
