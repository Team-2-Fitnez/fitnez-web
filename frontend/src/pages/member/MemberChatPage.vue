<script setup lang="ts">
import { onMounted, ref, nextTick, watch } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import { useChatStore } from '../../stores/chatStore'

const chat = useChatStore()
const newMessage = ref('')
const messagesEnd = ref<HTMLElement | null>(null)

function scrollToBottom() {
  nextTick(() => {
    messagesEnd.value?.scrollIntoView({ behavior: 'smooth' })
  })
}

async function selectContact(id: number) {
  await chat.loadMessages(id)
  scrollToBottom()
}

async function sendMsg() {
  const text = newMessage.value.trim()
  if (!text) return
  newMessage.value = ''
  await chat.sendMessage(text)
  scrollToBottom()
}

function formatTime(iso: string) {
  try {
    return new Intl.DateTimeFormat('id-ID', { timeStyle: 'short' }).format(new Date(iso))
  } catch {
    return ''
  }
}

watch(() => chat.messages.length, scrollToBottom)

onMounted(() => chat.loadContacts())
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Chat"
    subtitle="Konsultasi dengan trainer Anda."
    :sidebar-items="memberSidebarItems"
  >
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 1rem; min-height: 500px;"
         class="chat-layout">
      <!-- Contact List -->
      <FitnezCard style="padding: 0; overflow: hidden;">
        <div style="padding: 1rem; border-bottom: 1px solid var(--color-border);">
          <p class="stat-label">Kontak</p>
        </div>

        <div v-if="chat.contactsLoading" style="padding: 2rem; text-align: center;">
          <p class="text-muted" style="font-size: 0.8rem;">Memuat...</p>
        </div>
        <div v-else-if="chat.contacts.length === 0" style="padding: 2rem; text-align: center;">
          <p class="text-muted" style="font-size: 0.8rem;">Belum ada kontak. Kontak muncul setelah Anda booking trainer.</p>
        </div>

        <div v-else style="max-height: 400px; overflow-y: auto;">
          <button
            v-for="c in chat.contacts"
            :key="c.id"
            type="button"
            style="display: flex; align-items: center; gap: 0.75rem; padding: 0.875rem 1rem; width: 100%; text-align: left; border: 0; border-bottom: 1px solid rgba(0,0,0,0.06); cursor: pointer; transition: background 160ms ease;"
            :style="chat.activeContactId === c.id ? 'background: var(--color-cream);' : 'background: transparent;'"
            @click="selectContact(c.id)"
          >
            <div style="width: 2.25rem; height: 2.25rem; border-radius: 50%; background: var(--color-blue); display: flex; align-items: center; justify-content: center; color: white; font-weight: 900; font-size: 0.8rem; flex-shrink: 0;">
              {{ c.name?.[0]?.toUpperCase() ?? '?' }}
            </div>
            <div style="flex: 1; min-width: 0;">
              <p style="font-weight: 800; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ c.name }}</p>
              <p class="text-muted" style="font-size: 0.7rem; text-transform: capitalize;">{{ c.role }}</p>
            </div>
          </button>
        </div>
      </FitnezCard>

      <!-- Messages -->
      <FitnezCard style="padding: 0; display: flex; flex-direction: column; overflow: hidden;">
        <div v-if="!chat.activeContact" style="flex: 1; display: grid; place-items: center; padding: 2rem;">
          <p class="text-muted">Pilih kontak untuk mulai chat.</p>
        </div>

        <template v-else>
          <!-- Header -->
          <div style="padding: 1rem; border-bottom: 1px solid var(--color-border); display: flex; align-items: center; gap: 0.75rem;">
            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: var(--color-blue); display: flex; align-items: center; justify-content: center; color: white; font-weight: 900; font-size: 0.75rem;">
              {{ chat.activeContact.name?.[0]?.toUpperCase() ?? '?' }}
            </div>
            <div>
              <p style="font-weight: 900; font-size: 0.9rem;">{{ chat.activeContact.name }}</p>
              <p class="text-muted" style="font-size: 0.7rem; text-transform: capitalize;">{{ chat.activeContact.role }}</p>
            </div>
          </div>

          <!-- Messages area -->
          <div style="flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem; min-height: 300px; max-height: 400px;">
            <div v-if="chat.messagesLoading" style="text-align: center; padding: 2rem;">
              <p class="text-muted" style="font-size: 0.8rem;">Memuat pesan...</p>
            </div>
            <div v-else-if="chat.messages.length === 0" style="text-align: center; padding: 2rem;">
              <p class="text-muted" style="font-size: 0.8rem;">Belum ada pesan. Mulai chat!</p>
            </div>
            <template v-else>
              <div
                v-for="msg in chat.messages"
                :key="msg.id"
                :style="{
                  alignSelf: msg.isMe ? 'flex-end' : 'flex-start',
                  background: msg.isMe ? 'var(--color-blue)' : 'var(--color-cream)',
                  color: msg.isMe ? 'white' : 'var(--color-text)',
                  borderRadius: '1rem',
                  padding: '0.625rem 0.875rem',
                  maxWidth: '75%',
                  fontSize: '0.85rem',
                  lineHeight: '1.5',
                }"
              >
                <p>{{ msg.message }}</p>
                <p :style="{ fontSize: '0.65rem', opacity: 0.5, marginTop: '0.25rem', textAlign: 'right' }">
                  {{ formatTime(msg.created_at) }}
                </p>
              </div>
            </template>
            <div ref="messagesEnd" />
          </div>

          <!-- Input -->
          <div style="padding: 0.75rem 1rem; border-top: 1px solid var(--color-border); display: flex; gap: 0.5rem;">
            <input
              v-model="newMessage"
              class="form-input"
              style="flex: 1;"
              placeholder="Tulis pesan..."
              @keydown.enter.prevent="sendMsg"
            />
            <button class="button button-primary" :disabled="!newMessage.trim()" @click="sendMsg">
              Kirim
            </button>
          </div>
        </template>
      </FitnezCard>
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
@media (max-width: 768px) {
  .chat-layout {
    grid-template-columns: 1fr !important;
  }
}
</style>
