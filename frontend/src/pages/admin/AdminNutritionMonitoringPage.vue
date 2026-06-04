<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import ExcelImportModal from '../../components/ExcelImportModal.vue'
import { http } from '../../api/http'
import { mealPlanApi } from '../../api/mealPlanApi'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'

interface MemberNutrition {
    id: number
    name: string
    email: string
    daily_limit: number
    total_calories: number
}

const members = ref<MemberNutrition[]>([])
const error = ref('')
const { loading, run, shimmerStyle } = useDeferredLoading()

async function loadMembers() {
    try {
        const res = await mealPlanApi.getAllMemberNutrition()
        members.value = (res as any).members ?? []
    } catch {
        error.value = 'Failed to load nutrition monitoring data'
    }
}

onMounted(() => run(loadMembers))

function getPercentage(total: number, limit: number) {
    if (limit <= 0) return 0
    return Math.min(100, Math.round((total / limit) * 100))
}

function getStatus(total: number, limit: number) {
    if (limit <= 0) return { label: 'No limit set', color: '#888' }
    const pct = (total / limit) * 100
    if (pct >= 100) return { label: 'Exceeded limit', color: '#e74c3c' }
    if (pct >= 80) return { label: 'Near limit', color: '#f39c12' }
    return { label: 'Normal', color: '#2ecc71' }
}
</script>

<template>
    <WorkspaceLayout
        role="admin"
        sidebar-title="Admin"
        title="Nutrition Monitoring"
        subtitle="Monitor daily calorie consumption of all members."
        :sidebar-items="adminSidebarItems"
    >
        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-bottom: 0.75rem;">
            <a :href="http.url('/admin/export/nutrition-monitoring')" class="button button-ghost" style="font-size: 0.85rem; text-decoration: none;">Export Excel</a>
            <ExcelImportModal import-type="workouts" label="Import Excel" />
        </div>
        <div v-if="loading && !members.length" :style="shimmerStyle">
            <SkeletonStatGrid :count="3" />
            <div style="margin-top: 1.25rem"><SkeletonTable :columns="5" :rows="8" /></div>
        </div>
        <template v-else>
        <div class="feature-grid">
            <FitnezCard>
                <p class="eyebrow">Total Members</p>
                <h2 class="title-md">{{ members.length }}</h2>
                <p class="text-muted">Members with active meal plans</p>
            </FitnezCard>
            <FitnezCard>
                <p class="eyebrow">Exceeded Limit</p>
                <h2 class="title-md" style="color: #e74c3c;">
                    {{ members.filter(m => m.total_calories >= m.daily_limit && m.daily_limit > 0).length }}
                </h2>
                <p class="text-muted">Members exceeding calorie limit today</p>
            </FitnezCard>
            <FitnezCard>
                <p class="eyebrow">No Limit Set</p>
                <h2 class="title-md" style="color: #f39c12;">
                    {{ members.filter(m => m.daily_limit <= 0).length }}
                </h2>
                <p class="text-muted">Members who have not set a daily limit</p>
            </FitnezCard>
        </div>

        <!-- Monitoring Table -->
        <FitnezCard style="margin-top: 1.25rem;">
            <h2 class="title-md">📊 Member Calorie Consumption Data</h2>

            <div v-if="error" style="margin-top: 1rem; color: red;">
                {{ error }}
            </div>

            <div v-else-if="members.length === 0" style="margin-top: 1rem; color: var(--muted);">
                No members have set up a meal plan yet.
            </div>

            <div v-else style="margin-top: 1rem; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #eee; text-align: left;">
                            <th style="padding: 0.75rem; font-size: 0.85rem;">Member</th>
                            <th style="padding: 0.75rem; font-size: 0.85rem;">Daily Limit</th>
                            <th style="padding: 0.75rem; font-size: 0.85rem;">Consumed</th>
                            <th style="padding: 0.75rem; font-size: 0.85rem;">Progress</th>
                            <th style="padding: 0.75rem; font-size: 0.85rem;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="m in members"
                            :key="m.id"
                            style="border-bottom: 1px solid #eee;"
                        >
                            <td style="padding: 0.75rem;">
                                <p style="font-weight: 600;">{{ m.name }}</p>
                                <p style="font-size: 0.8rem; color: var(--muted);">{{ m.email }}</p>
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ m.daily_limit > 0 ? m.daily_limit.toLocaleString() + ' kcal' : '-' }}
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ m.total_calories.toLocaleString() }} kcal
                            </td>
                            <td style="padding: 0.75rem; min-width: 150px;">
                                <div style="background: #eee; border-radius: 999px; height: 8px;">
                                    <div
                                        :style="{
                                            width: getPercentage(m.total_calories, m.daily_limit) + '%',
                                            background: getPercentage(m.total_calories, m.daily_limit) >= 100 ? '#e74c3c' : '#f97316',
                                            height: '8px',
                                            borderRadius: '999px',
                                            transition: 'width 0.4s ease'
                                        }"
                                    ></div>
                                </div>
                                <p style="font-size: 0.75rem; color: var(--muted); margin-top: 4px;">
                                    {{ getPercentage(m.total_calories, m.daily_limit) }}%
                                </p>
                            </td>
                            <td style="padding: 0.75rem;">
                                <span
                                    :style="{
                                        color: getStatus(m.total_calories, m.daily_limit).color,
                                        fontWeight: '600',
                                        fontSize: '0.85rem'
                                    }"
                                >
                                    {{ getStatus(m.total_calories, m.daily_limit).label }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </FitnezCard>
        </template>
    </WorkspaceLayout>
</template>