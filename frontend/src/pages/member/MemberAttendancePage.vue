<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import { http } from '../../api/http'

const loading = ref(false)
const history = ref<any[]>([])
const checkInStatus = ref<'none' | 'checked_in' | 'checked_out'>('none')

<<<<<<< HEAD
type AttendanceHistory = { data: any[] }

async function loadHistory() {
  try {
    const res = await http.get<AttendanceHistory>('/attendance/history?per_page=10')
=======
async function loadHistory() {
  try {
    const res = await http.get<{ data: any[] }>('/attendance/history?per_page=10')
>>>>>>> origin/Trainer-Booking-Chat
    history.value = res.data.data || []
    const active = history.value.find((a: any) => !a.check_out_time)
    checkInStatus.value = active ? 'checked_in' : 'checked_out'
  } catch {
<<<<<<< HEAD
    window.showFitnezToast('Failed to load history.', 'error')
=======
    window.showFitnezToast('Gagal memuat riwayat.', 'error')
>>>>>>> origin/Trainer-Booking-Chat
  }
}

async function doCheckIn() {
  loading.value = true
  try {
    await http.post('/attendance/check-in', {})
<<<<<<< HEAD
    window.showFitnezToast('Check-in successful!', 'success')
    await loadHistory()
  } catch (e: any) {
    window.showFitnezToast(e?.message || 'Failed to check in.', 'error')
=======
    window.showFitnezToast('Check-in berhasil!', 'success')
    await loadHistory()
  } catch (e: any) {
    window.showFitnezToast(e?.message || 'Gagal check-in.', 'error')
>>>>>>> origin/Trainer-Booking-Chat
  } finally {
    loading.value = false
  }
}

async function doCheckOut() {
  loading.value = true
  try {
    await http.post('/attendance/check-out', {})
<<<<<<< HEAD
    window.showFitnezToast('Check-out successful!', 'success')
    await loadHistory()
  } catch (e: any) {
    window.showFitnezToast(e?.message || 'Failed to check out.', 'error')
=======
    window.showFitnezToast('Check-out berhasil!', 'success')
    await loadHistory()
  } catch (e: any) {
    window.showFitnezToast(e?.message || 'Gagal check-out.', 'error')
>>>>>>> origin/Trainer-Booking-Chat
  } finally {
    loading.value = false
  }
}

function formatDateTime(val?: string) {
  if (!val) return '-'
<<<<<<< HEAD
  return new Date(val).toLocaleString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
=======
  return new Date(val).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
>>>>>>> origin/Trainer-Booking-Chat
}

onMounted(loadHistory)
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Attendance"
<<<<<<< HEAD
    subtitle="Check-in and check-out your training attendance."
=======
    subtitle="Check-in dan check-out kehadiran latihan Anda."
>>>>>>> origin/Trainer-Booking-Chat
    :sidebar-items="memberSidebarItems"
  >
    <div style="display: grid; gap: 1.25rem;">
      <FitnezCard>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
          <div>
<<<<<<< HEAD
            <p class="eyebrow">Today's Attendance</p>
            <h2 class="title-md">
              {{ checkInStatus === 'checked_in' ? 'You are checked in' : checkInStatus === 'checked_out' ? 'Ready to check in' : '--' }}
=======
            <p class="eyebrow">Presensi Hari Ini</p>
            <h2 class="title-md">
              {{ checkInStatus === 'checked_in' ? 'Anda sedang check-in' : checkInStatus === 'checked_out' ? 'Siap check-in' : '--' }}
>>>>>>> origin/Trainer-Booking-Chat
            </h2>
          </div>
          <div style="display: flex; gap: 0.75rem;">
            <button
              v-if="checkInStatus !== 'checked_in'"
              class="button button-primary"
              :disabled="loading"
              @click="doCheckIn"
<<<<<<< HEAD
            >{{ loading ? 'Processing...' : 'Check-In' }}</button>
=======
            >{{ loading ? 'Memproses...' : 'Check-In' }}</button>
>>>>>>> origin/Trainer-Booking-Chat
            <button
              v-if="checkInStatus === 'checked_in'"
              class="button button-danger"
              :disabled="loading"
              @click="doCheckOut"
<<<<<<< HEAD
            >{{ loading ? 'Processing...' : 'Check-Out' }}</button>
=======
            >{{ loading ? 'Memproses...' : 'Check-Out' }}</button>
>>>>>>> origin/Trainer-Booking-Chat
          </div>
        </div>
      </FitnezCard>

      <FitnezCard>
<<<<<<< HEAD
        <p class="eyebrow">Attendance History</p>
=======
        <p class="eyebrow">Riwayat Kehadiran</p>
>>>>>>> origin/Trainer-Booking-Chat
        <div class="responsive-table" style="margin-top: 1rem;">
          <table class="data-table">
            <thead>
              <tr>
<<<<<<< HEAD
                <th>Date</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Type</th>
=======
                <th>Tanggal</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Tipe</th>
>>>>>>> origin/Trainer-Booking-Chat
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in history" :key="item.id">
                <td>{{ formatDateTime(item.check_in_time) }}</td>
<<<<<<< HEAD
                <td>{{ item.check_in_time ? new Date(item.check_in_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-' }}</td>
                <td>{{ item.check_out_time ? new Date(item.check_out_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-' }}</td>
                <td>{{ item.attendance_type || '-' }}</td>
                <td><span :class="['status', item.check_out_time ? 'status-success' : 'status-warning']">{{ item.check_out_time ? 'Completed' : 'Active' }}</span></td>
              </tr>
              <tr v-if="!history.length">
                <td colspan="5" class="empty-cell">No attendance history yet.</td>
=======
                <td>{{ item.check_in_time ? new Date(item.check_in_time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-' }}</td>
                <td>{{ item.check_out_time ? new Date(item.check_out_time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-' }}</td>
                <td>{{ item.attendance_type || '-' }}</td>
                <td><span :class="['status', item.check_out_time ? 'status-success' : 'status-warning']">{{ item.check_out_time ? 'Selesai' : 'Aktif' }}</span></td>
              </tr>
              <tr v-if="!history.length">
                <td colspan="5" class="empty-cell">Belum ada riwayat kehadiran.</td>
>>>>>>> origin/Trainer-Booking-Chat
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
