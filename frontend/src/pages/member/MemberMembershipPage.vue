<script setup lang="ts">
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Memberships"
    subtitle="Check active package, renewal, and class benefits."
    :sidebar-items="memberSidebarItems"
  >
    <div class="feature-grid">
      <StatCard label="Status" value="Active" hint="After admin approval" />
      <StatCard label="Package" value="Membership" hint="Based on selected plan" />
      <StatCard label="Class Access" value="Package Based" hint="6M/12M include classes" />
    </div>

    <FitnezCard style="margin-top: 1.25rem;">
      <h2 class="title-md">Membership Information</h2>
      <p class="text-muted">
        This page is prepared for membership details, package duration, expiry date, renewal status, and class benefit information.
      </p>
    </FitnezCard>
  </WorkspaceLayout>
</template>
