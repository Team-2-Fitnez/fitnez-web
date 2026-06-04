<script setup lang="ts">
import { onMounted, computed } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import { useBookingStore } from '../../stores/bookingStore'

const store = useBookingStore()

function formatTime(iso: string) {
  try {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(iso))
  } catch {
    return iso
  }
}

function formatPrice(n: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(n)
}

async function updateStatus(id: number, status: string) {
  await store.updateStatus(id, status)
}

const activeBookings = computed(() => {
  return store.bookings.filter((b) => ['pending', 'confirmed'].includes(b.status ?? ''))
})

const pastBookings = computed(() => {
  return store.bookings.filter((b) => !['pending', 'confirmed'].includes(b.status ?? ''))
})

onMounted(() => store.loadBookings())
</script>

<template>
  <WorkspaceLayout
    role="trainer"
    sidebar-title="Trainer"
    title="Jadwal Melatih"
    subtitle="Kelola sesi training Anda."
    :sidebar-items="trainerSidebarItems"
  >
    <!-- Loading -->
    <FitnezCard v-if="store.loading && store.bookings.length === 0" style="padding: 3rem; text-align: center;">
      <p class="text-muted">Memuat jadwal...</p>
    </FitnezCard>

    <div v-else style="display: grid; gap: 2rem;">
      <!-- Sesi Aktif -->
      <section>
        <h3 class="title-md" style="margin-bottom: 1rem; font-size: 1.1rem;">Permintaan & Jadwal Aktif</h3>
        <div v-if="activeBookings.length === 0" class="card" style="padding: 2rem; text-align: center;">
          <p class="text-muted">Belum ada sesi aktif.</p>
        </div>
        <div v-else class="feature-grid">
          <FitnezCard v-for="b in activeBookings" :key="b.id">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
              <div>
                <p style="font-weight: 900; font-size: 1.1rem;">{{ b.member?.full_name ?? 'Member' }}</p>
                <p class="text-muted" style="font-size: 0.8rem; text-transform: capitalize;">{{ b.session_type }} - {{ b.location }}</p>
              </div>
              <span
                class="status"
                :class="b.status === 'pending' ? 'status-warning' : 'status-success'"
              >
                {{ b.status === 'pending' ? 'Menunggu Konfirmasi' : 'Dikonfirmasi' }}
              </span>
            </div>

            <div style="background: var(--color-cream); padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem;">
              <p style="font-weight: 800; font-size: 0.9rem;">{{ formatTime(b.booking_date) }}</p>
              <p class="text-muted" style="font-size: 0.8rem;">Pukul {{ b.start_time.substring(0, 5) }} - {{ b.end_time.substring(0, 5) }}</p>
            </div>

            <div v-if="b.member_notes" style="margin-bottom: 1rem;">
              <p class="stat-label" style="font-size: 0.7rem;">Catatan Member:</p>
              <p style="font-size: 0.85rem; font-style: italic; color: var(--color-text);">"{{ b.member_notes }}"</p>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: auto;">
              <p style="font-weight: 900; color: var(--color-orange);">{{ formatPrice(Number(b.total_price)) }}</p>
              
              <div v-if="b.status === 'pending'" style="display: flex; gap: 0.5rem;">
                <button class="button button-ghost button-small" @click="updateStatus(b.id, 'rejected')">Tolak</button>
                <button class="button button-primary button-small" @click="updateStatus(b.id, 'confirmed')">Terima</button>
              </div>
              <div v-else-if="b.status === 'confirmed'" style="display: flex; gap: 0.5rem;">
                <button class="button button-primary button-small" @click="updateStatus(b.id, 'completed')">Selesai</button>
              </div>
            </div>
          </FitnezCard>
        </div>
      </section>

      <!-- Riwayat -->
      <section>
        <h3 class="title-md" style="margin-bottom: 1rem; font-size: 1.1rem;">Riwayat Sesi</h3>
        <div v-if="pastBookings.length === 0" class="card" style="padding: 2rem; text-align: center;">
          <p class="text-muted">Belum ada riwayat sesi.</p>
        </div>
        <div v-else style="display: grid; gap: 0.75rem;">
          <FitnezCard v-for="b in pastBookings" :key="b.id" style="padding: 1rem;">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
              <div>
                <p style="font-weight: 900; font-size: 0.95rem;">{{ b.member?.full_name ?? 'Member' }}</p>
                <p class="text-muted" style="font-size: 0.8rem;">{{ formatTime(b.booking_date) }}</p>
              </div>
              <span
                class="status"
                :class="{
                  'status-success': b.status === 'completed',
                  'status-error': b.status === 'rejected' || b.status === 'cancelled',
                }"
              >
                {{ b.status }}
              </span>
            </div>
          </FitnezCard>
        </div>
      </section>
    </div>
  </WorkspaceLayout>
</template>
