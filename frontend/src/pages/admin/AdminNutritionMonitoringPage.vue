<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import { mealPlanApi } from '../../api/mealPlanApi'

interface MemberNutrition {
    id: number
    name: string
    email: string
    daily_limit: number
    total_calories: number
}

const members = ref<MemberNutrition[]>([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
    try {
        const res = await mealPlanApi.getAllMemberNutrition()
        members.value = (res as any).members ?? []
    } catch (e) {
        error.value = 'Gagal memuat data monitoring nutrisi'
    } finally {
        loading.value = false
    }
})

function getPercentage(total: number, limit: number) {
    if (limit <= 0) return 0
    return Math.min(100, Math.round((total / limit) * 100))
}

function getStatus(total: number, limit: number) {
    if (limit <= 0) return { label: 'Belum set limit', color: '#888' }
    const pct = (total / limit) * 100
    if (pct >= 100) return { label: 'Melebihi limit', color: '#e74c3c' }
    if (pct >= 80) return { label: 'Hampir penuh', color: '#f39c12' }
    return { label: 'Normal', color: '#2ecc71' }
}
</script>

<template>
    <WorkspaceLayout
        role="admin"
        sidebar-title="Admin"
        title="Monitoring Nutrisi"
        subtitle="Pantau konsumsi kalori harian seluruh member."
        :sidebar-items="adminSidebarItems"
    >
        <!-- Summary Cards -->
        <div class="feature-grid">
            <FitnezCard>
                <p class="eyebrow">Total Member</p>
                <h2 class="title-md">{{ members.length }}</h2>
                <p class="text-muted">Member dengan meal plan aktif</p>
            </FitnezCard>
            <FitnezCard>
                <p class="eyebrow">Melebihi Limit</p>
                <h2 class="title-md" style="color: #e74c3c;">
                    {{ members.filter(m => m.total_calories >= m.daily_limit && m.daily_limit > 0).length }}
                </h2>
                <p class="text-muted">Member yang melebihi kalori hari ini</p>
            </FitnezCard>
            <FitnezCard>
                <p class="eyebrow">Belum Set Limit</p>
                <h2 class="title-md" style="color: #f39c12;">
                    {{ members.filter(m => m.daily_limit <= 0).length }}
                </h2>
                <p class="text-muted">Member belum mengatur daily limit</p>
            </FitnezCard>
        </div>

        <!-- Tabel Monitoring -->
        <FitnezCard style="margin-top: 1.25rem;">
            <h2 class="title-md">📊 Data Konsumsi Kalori Member</h2>

            <div v-if="loading" style="margin-top: 1rem; color: var(--muted);">
                Memuat data...
            </div>

            <div v-else-if="error" style="margin-top: 1rem; color: red;">
                {{ error }}
            </div>

            <div v-else-if="members.length === 0" style="margin-top: 1rem; color: var(--muted);">
                Belum ada member yang mengatur meal plan.
            </div>

            <div v-else style="margin-top: 1rem; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #eee; text-align: left;">
                            <th style="padding: 0.75rem; font-size: 0.85rem;">Member</th>
                            <th style="padding: 0.75rem; font-size: 0.85rem;">Daily Limit</th>
                            <th style="padding: 0.75rem; font-size: 0.85rem;">Dikonsumsi</th>
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
                                {{ m.daily_limit > 0 ? m.daily_limit.toLocaleString() + ' kkal' : '-' }}
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ m.total_calories.toLocaleString() }} kkal
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
    </WorkspaceLayout>
</template>