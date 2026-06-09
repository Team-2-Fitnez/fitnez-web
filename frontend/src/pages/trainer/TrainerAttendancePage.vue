<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import SkeletonList from '../../components/ui/SkeletonList.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'
import { http } from '../../api/http'

const loading = ref(false)
const actionLoading = ref(false)
const loaded = ref(false)
const history = ref<any[]>([])
const checkInStatus = ref<'none' | 'checked_in' | 'checked_out'>('none')
const { loading: initialLoading, run, shimmerStyle } = useDeferredLoading()

async function loadHistory() {
  loading.value = true
  try {
    const res = await http.get<{ data: any[] }>('/attendance/history?per_page=10')
    history.value = res.data.data || []
    const active = history.value.find((a: any) => !a.check_out_time)
    checkInStatus.value = active ? 'checked_in' : 'checked_out'
  } catch {
    window.showFitnezToast('Failed to load history.', 'error')
  } finally {
    loading.value = false
    loaded.value = true
  }
}

async function doCheckIn() {
  actionLoading.value = true
  try {
    const res = await http.post<{ id: number; check_in_time: string }>('/attendance/check-in', { attendance_type: 'trainer_checkin' })
    window.showFitnezToast('Check-in successful!', 'success')
    history.value.unshift({
      id: res.data.id,
      check_in_time: res.data.check_in_time,
      check_out_time: null,
      attendance_type: 'trainer_checkin',
    })
    checkInStatus.value = 'checked_in'
  } catch (e: any) {
    window.showFitnezToast(e?.message || 'Failed to check in.', 'error')
  } finally {
    actionLoading.value = false
  }
}

async function doCheckOut() {
  actionLoading.value = true
  try {
    await http.post('/attendance/check-out', {})
    window.showFitnezToast('Check-out successful!', 'success')
    const active = history.value.find((a: any) => !a.check_out_time)
    if (active) active.check_out_time = new Date().toISOString()
    checkInStatus.value = 'checked_out'
  } catch (e: any) {
    window.showFitnezToast(e?.message || 'Failed to check out.', 'error')
  } finally {
    actionLoading.value = false
  }
}

function formatDateTime(val?: string) {
  if (!val) return '-'
  return new Date(val).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => run(loadHistory))
</script>

<template>
  <WorkspaceLayout
    role="trainer"
    sidebar-title="Trainer"
    title="Attendance"
    subtitle="Check in and check out for your trainer attendance sessions."
    :sidebar-items="trainerSidebarItems"
  >
    <div style="display: grid; gap: 1.25rem;">
      <FitnezCard>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
          <div>
            <p class="eyebrow">Today's Attendance</p>
            <h2 class="title-md">
              {{ checkInStatus === 'checked_in' ? 'You are currently checked in' : checkInStatus === 'checked_out' ? 'Ready to check in' : '--' }}
            </h2>
          </div>
          <div style="display: flex; gap: 0.75rem;">
            <button
              v-if="checkInStatus !== 'checked_in'"
              class="button button-primary"
              :disabled="actionLoading"
              @click="doCheckIn"
            >{{ actionLoading ? 'Processing...' : 'Check-In' }}</button>
            <button
              v-if="checkInStatus === 'checked_in'"
              class="button button-danger"
              :disabled="actionLoading"
              @click="doCheckOut"
            >{{ actionLoading ? 'Processing...' : 'Check-Out' }}</button>
          </div>
        </div>
      </FitnezCard>

      <SkeletonList v-if="initialLoading" :rows="5" :style="shimmerStyle" />
      <SkeletonList v-else-if="!loaded && loading" :rows="5" />

      <FitnezCard v-else>
        <p class="eyebrow">Attendance History</p>
        <div class="responsive-table" style="margin-top: 1rem;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Type</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in history" :key="item.id">
                <td>{{ formatDateTime(item.check_in_time) }}</td>
                <td>{{ item.check_in_time ? new Date(item.check_in_time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-' }}</td>
                <td>{{ item.check_out_time ? new Date(item.check_out_time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-' }}</td>
                <td>{{ item.attendance_type || '-' }}</td>
                <td><span :class="['status', item.check_out_time ? 'status-success' : 'status-warning']">{{ item.check_out_time ? 'Completed' : 'Active' }}</span></td>
              </tr>
              <tr v-if="!history.length">
                <td colspan="5" class="empty-cell">No attendance history yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </FitnezCard>
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
.responsive-table { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--color-border); }
.data-table th { font-weight: 700; font-size: 0.8rem; color: var(--color-muted); }
.empty-cell { color: var(--color-muted); font-weight: 800; padding: 2rem; text-align: center; }
.button-danger { background: #e53e3e; color: white; border: none; }
.button-danger:hover { background: #c53030; }
</style>
