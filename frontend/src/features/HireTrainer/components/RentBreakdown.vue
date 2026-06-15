<script setup lang="ts">
import type { TrainerIncomeBreakdown } from '@/features/RentHistory/types/trainerRentHistory'

defineProps<{
  breakdown: TrainerIncomeBreakdown
}>()

function currency(value?: string | number | null) {
  const numberValue = Number(value || 0)
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(numberValue)
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
    <h3 class="table-title mb-4">Income Breakdown</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="mentoring-stat">
        <span class="mentoring-stat-label">Mentoring Income</span>
        <span class="mentoring-stat-value accent-purple">{{ currency(breakdown.mentoring_income) }}</span>
      </div>
      <div class="mentoring-stat">
        <span class="mentoring-stat-label">Session Income</span>
        <span class="mentoring-stat-value accent-blue">{{ currency(breakdown.session_income) }}</span>
      </div>
      <div class="mentoring-stat">
        <span class="mentoring-stat-label">This Month</span>
        <span class="mentoring-stat-value accent-green">{{ currency(breakdown.this_month_income) }}</span>
      </div>
      <div class="mentoring-stat">
        <span class="mentoring-stat-label">Total Income</span>
        <span class="mentoring-stat-value accent-dark">{{ currency(breakdown.total_income) }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.table-title {
  font-size: 1.15rem;
  font-weight: 900;
  color: #111827;
  margin: 0;
  letter-spacing: -0.02em;
}
.mentoring-stat {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.mentoring-stat-label {
  font-size: 0.7rem;
  font-weight: 800;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.mentoring-stat-value {
  font-size: 1.35rem;
  font-weight: 900;
}
.accent-purple { color: #9333ea; }
.accent-blue { color: #0284c7; }
.accent-green { color: #16a34a; }
.accent-dark { color: #111827; }
</style>
