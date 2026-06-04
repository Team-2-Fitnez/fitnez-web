<script setup>
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
</script>

<template>
  <WorkspaceLayout role="trainer" sidebar-title="Trainer" title="Classes" subtitle="Prepare and manage assigned class programs." :sidebar-items="trainerSidebarItems">
    <div class="grid gap-4 md:grid-cols-3">
      <StatCard label="Today Sessions" value="4" hint="Placeholder" />
      <StatCard label="Assigned Members" value="18" hint="Placeholder" />
      <StatCard label="Class Rating" value="4.8" hint="Placeholder" />
    </div>

    <FitnezCard class="mt-5">
      <h2 class="title-md">Classes Module</h2>
      <p class="text-muted mt-3">
        This trainer page is prepared for UI/UX. Connect backend functions later.
      </p>
    </FitnezCard>
  </WorkspaceLayout>
</template>
