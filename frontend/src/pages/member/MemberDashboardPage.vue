<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonCard from '../../components/ui/SkeletonCard.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'
import { http } from '../../api/http'

const payments = ref({ total_payments: 0, total_amount: 0, paid_count: 0, pending_count: 0 })
const attendanceTotal = ref(0)
const classesTotal = ref(0)
const { loading, run, shimmerStyle } = useDeferredLoading()

async function loadDashboard() {
  const [paymentRes, attendanceRes, classesRes] = await Promise.allSettled([
    http.get<typeof payments.value>('/member/payments/summary'),
    http.get<{ data: unknown[]; total?: number }>('/attendance/history?per_page=5'),
    http.get<unknown[]>('/member/classes'),
  ])

  if (paymentRes.status === 'fulfilled') payments.value = paymentRes.value.data
  if (attendanceRes.status === 'fulfilled') attendanceTotal.value = attendanceRes.value.data?.total || attendanceRes.value.data?.data?.length || 0
  if (classesRes.status === 'fulfilled') classesTotal.value = classesRes.value.data?.length || 0
}

onMounted(() => run(loadDashboard))
</script>

<template>
  <WorkspaceLayout role="member" sidebar-title="Member" title="Member Dashboard" subtitle="Summary of member activities, membership, payments, classes, and daily progress." :sidebar-items="memberSidebarItems">
    <div v-if="loading" :style="shimmerStyle">
      <SkeletonStatGrid :count="4" />
      <div class="dashboard-grid">
        <SkeletonCard :lines="4" />
        <SkeletonCard :lines="3" />
      </div>
    </div>
    <template v-else>
    <div class="feature-grid">
      <StatCard label="Total Payments" :value="payments.total_payments" :hint="`Amount Rp ${payments.total_amount.toLocaleString('en-US')}`" />
      <StatCard label="Successful Payments" :value="payments.paid_count" hint="successful transactions" />
      <StatCard label="Active Classes" :value="classesTotal" hint="available to join" />
      <StatCard label="Login" value="Direct" hint="OTP is only for password reset" />
    </div>

    <div class="dashboard-grid">
      <FitnezCard>
        <h2 class="title-md">Next Priorities</h2>
        <div class="action-list">
          <RouterLink to="/member/workout-plan">Create or check workout plan</RouterLink>
          <RouterLink to="/member/meal-plan">Monitor meal plan and nutrition targets</RouterLink>
          <RouterLink to="/member/hire-trainer">Find a trainer if you need guidance</RouterLink>
          <RouterLink to="/member/classes">Join available classes</RouterLink>
        </div>
      </FitnezCard>

      <FitnezCard>
        <h2 class="title-md">Quick Analysis</h2>
        <p class="text-muted">
          An active account with structured payments will ensure smooth feature access. If there is a pending payment, resolve it first before scheduling new activities.
        </p>
      </FitnezCard>
    </div>
    </template>
  </WorkspaceLayout>
</template>

<style scoped>
.dashboard-grid {
  display: grid;
  gap: 1.25rem;
  grid-template-columns: 1.1fr 0.9fr;
  margin-top: 1.25rem;
}

.action-list {
  display: grid;
  gap: 0.75rem;
  margin-top: 1rem;
}

.action-list a {
  background: #f8fafc;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 0.9rem;
  color: #0f172a;
  font-weight: 900;
  padding: 0.9rem 1rem;
  text-decoration: none;
}

@media (max-width: 900px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}
</style>
