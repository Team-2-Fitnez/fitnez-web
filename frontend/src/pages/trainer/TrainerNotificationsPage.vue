<script setup lang="ts">
import { onMounted } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import { useNotificationStore } from '../../stores/notificationStore'

const store = useNotificationStore()

function formatTime(iso: string) {
  try {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(
      new Date(iso),
    )
  } catch {
    return iso
  }
}

async function onItem(id: number) {
  await store.markAsRead(id)
}

async function onMarkAll() {
  await store.markAllRead()
}

onMounted(async () => {
  await store.load()
  await store.loadUnreadCount()
})
</script>

<template>
  <WorkspaceLayout
    role="trainer"
    sidebar-title="Trainer"
    title="Notifications"
    subtitle="Pengingat jadwal latihan dan pembayaran masuk."
    :sidebar-items="trainerSidebarItems"
  >
    <div class="card" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.25rem; margin-bottom: 1.5rem; background: linear-gradient(135deg, #fff 0%, var(--color-cream) 100%); border: 1px solid var(--color-orange-light);">
      <div style="display: flex; align-items: center; gap: 1rem;">
        <div style="width: 3rem; height: 3rem; border-radius: 1rem; background: var(--color-orange); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; box-shadow: 0 4px 12px rgba(235, 110, 67, 0.2);">
          🔔
        </div>
        <div>
          <p class="stat-label" style="margin: 0;">Unread Notifications</p>
          <p class="title-md" style="margin: 0; color: var(--color-orange);">{{ store.unreadCount }} Pesan Baru</p>
        </div>
      </div>
      <button
        type="button"
        class="button"
        :class="store.unreadCount > 0 ? 'button-primary' : 'button-ghost'"
        :disabled="store.loading || store.unreadCount === 0"
        style="font-weight: 800; letter-spacing: 0.02em;"
        @click="onMarkAll"
      >
        {{ store.loading ? 'Memproses...' : 'TANDAI DIBACA SEMUA' }}
      </button>
    </div>

    <!-- Loading -->
    <FitnezCard v-if="store.loading && store.items.length === 0" style="padding: 3rem; text-align: center;">
      <p class="text-muted">Memuat notifikasi...</p>
    </FitnezCard>

    <!-- Empty -->
    <FitnezCard v-else-if="store.items.length === 0" style="padding: 3rem; text-align: center;">
      <p class="text-muted">Belum ada notifikasi. Notifikasi muncul saat ada booking baru atau sesi dikonfirmasi.</p>
    </FitnezCard>

    <!-- Notification List -->
    <div v-else style="display: grid; gap: 0.75rem;">
      <FitnezCard
        v-for="n in store.items"
        :key="n.id"
        style="cursor: pointer; transition: border-color 160ms ease;"
        :style="!n.is_read ? 'border-color: var(--color-orange); background: rgba(244, 232, 227, 0.5);' : ''"
        @click="!n.is_read && onItem(n.id)"
      >
        <div style="display: flex; gap: 0.75rem; align-items: center;">
          <div
            style="width: 0.5rem; min-height: 1rem; border-radius: 999px; flex-shrink: 0;"
            :style="n.is_read ? 'background: rgba(0,0,0,0.1);' : 'background: var(--color-orange);'"
          />
          <div style="flex: 1; min-width: 0;">
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
              <p style="font-weight: 900; font-size: 0.9rem;">{{ n.title }}</p>
              <span
                v-if="n.notification_type === 'payment_in'"
                class="status status-success"
                style="font-size: 0.65rem;"
              >Pembayaran</span>
              <span
                v-else-if="n.notification_type === 'booking_request'"
                class="status status-warning"
                style="font-size: 0.65rem;"
              >Jadwal</span>
            </div>
            <p class="text-muted" style="font-size: 0.85rem; margin-top: 0.25rem;">{{ n.body }}</p>
            <p style="font-size: 0.75rem; opacity: 0.4; margin-top: 0.25rem;">{{ formatTime(n.created_at) }}</p>
          </div>

          <div style="flex-shrink: 0; padding-left: 0.5rem; border-left: 1px solid rgba(0,0,0,0.05);">
            <button
              v-if="!n.is_read"
              type="button"
              class="button button-primary button-small"
              style="padding: 0.5rem 0.75rem; font-size: 0.7rem; letter-spacing: 0.05em;"
              @click.stop="onItem(n.id)"
            >
              TANDAI DIBACA
            </button>
            <div v-else style="display: flex; align-items: center; gap: 0.35rem; color: var(--color-blue); opacity: 0.6;">
              <span style="font-size: 0.8rem;">✓</span>
              <span style="font-size: 0.65rem; font-weight: 900; letter-spacing: 0.1em;">DIBACA</span>
            </div>
          </div>
        </div>
      </FitnezCard>
    </div>
  </WorkspaceLayout>
</template>
