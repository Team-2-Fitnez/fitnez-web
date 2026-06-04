<script setup lang="ts">
import { ref } from 'vue'
import RoleLayout from '../../components/layout/RoleLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import ExcelImportModal from '../../components/shared/ExcelImportModal.vue'
import FitnezButton from '../../components/ui/FitnezButton.vue'

const showImportModal = ref(false)
</script>

<template>
  <RoleLayout role="admin" sidebar-title="Admin" title="Schedules" subtitle="Manage gym classes, trainer sessions, and bookings." :sidebar-items="adminSidebarItems">
    <div class="grid gap-4 md:grid-cols-3">
      <StatCard label="Status" value="UI Ready" hint="Prepared page" />
      <StatCard label="Next Step" value="API" hint="Connect backend later" />
      <StatCard label="Access" value="Admin" hint="Protected route" />
    </div>

    <FitnezCard class="mt-5">
      <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
        <div>
          <h2 class="text-2xl font-black">Schedules Module</h2>
          <p class="mt-3 max-w-3xl text-sm font-semibold leading-6 text-black/55">
            Import training schedules, member data, or workout plans from Excel/CSV files.
          </p>
        </div>
        <FitnezButton @click="showImportModal = true">
          Import Excel
        </FitnezButton>
      </div>
    </FitnezCard>

    <ExcelImportModal v-if="showImportModal" @done="showImportModal = false" />
  </RoleLayout>
</template>
