<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import SkeletonList from '../../components/ui/SkeletonList.vue'
import { http } from '../../api/http'

const loading = ref(false)
const loaded = ref(false)
const history = ref<any[]>([])
const checkInStatus = ref<'none' | 'checked_in' | 'checked_out'>('none')

async function loadHistory() {
  loading.value = true
  try {
    const res = await http.get<{ data: any[] }>('/attendance/history?per_page=10')
    history.value = res.data.data || []
    const active = history.value.find((a: any) => !a.check_out_time)
    checkInStatus.value = active ? 'checked_in' : 'checked_out'
  } catch {
    window.showFitnezToast('Gagal memuat riwayat.', 'error')
  } finally {
    loading.value = false
    loaded.value = true
  }
}

async function doCheckIn() {
  loading.value = true
  try {
    await http.post('/attendance/check-in', { attendance_type: 'trainer_checkin' })
    window.showFitnezToast('Check-in berhasil!', 'success')
    await loadHistory()
  } catch (e: any) {
    window.showFitnezToast(e?.message || 'Gagal check-in.', 'error')
  } finally {
    loading.value = false
  }
}

async function doCheckOut() {
  loading.value = true
  try {
    await http.post('/attendance/check-out', {})
    window.showFitnezToast('Check-out berhasil!', 'success')
    await loadHistory()
  } catch (e: any) {
    window.showFitnezToast(e?.message || 'Gagal check-out.', 'error')
  } finally {
    loading.value = false
  }
}

function formatDateTime(val?: string) {
  if (!val) return '-'
  return new Date(val).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(loadHistory)
</script>

<template>
  <WorkspaceLayout
    role="trainer"
    sidebar-title="Trainer"
    title="Attendance"
    subtitle="Check-in dan check-out kehadiran sesi melatih Anda."
    :sidebar-items="trainerSidebarItems"
  >
    <div style="display: grid; gap: 1.25rem;">
      <FitnezCard>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
          <div>
            <p class="eyebrow">Presensi Hari Ini</p>
            <h2 class="title-md">
              {{ checkInStatus === 'checked_in' ? 'Anda sedang check-in' : checkInStatus === 'checked_out' ? 'Siap check-in' : '--' }}
            </h2>
          </div>
          <div style="display: flex; gap: 0.75rem;">
            <button
              v-if="checkInStatus !== 'checked_in'"
              class="button button-primary"
              :disabled="loading"
              @click="doCheckIn"
            >{{ loading ? 'Memproses...' : 'Check-In' }}</button>
            <button
              v-if="checkInStatus === 'checked_in'"
              class="button button-danger"
              :disabled="loading"
              @click="doCheckOut"
            >{{ loading ? 'Memproses...' : 'Check-Out' }}</button>
          </div>
        </div>
      </FitnezCard>

      <SkeletonList v-if="!loaded && loading" :rows="5" />

      <FitnezCard v-else>
        <p class="eyebrow">Riwayat Kehadiran</p>
        <div class="responsive-table" style="margin-top: 1rem;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Tipe</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in history" :key="item.id">
                <td>{{ formatDateTime(item.check_in_time) }}</td>
                <td>{{ item.check_in_time ? new Date(item.check_in_time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-' }}</td>
                <td>{{ item.check_out_time ? new Date(item.check_out_time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-' }}</td>
                <td>{{ item.attendance_type || '-' }}</td>
                <td><span :class="['status', item.check_out_time ? 'status-success' : 'status-warning']">{{ item.check_out_time ? 'Selesai' : 'Aktif' }}</span></td>
              </tr>
              <tr v-if="!history.length">
                <td colspan="5" class="empty-cell">Belum ada riwayat kehadiran.</td>
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
