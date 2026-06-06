<script setup lang="ts">
import { onMounted, onUnmounted, ref, nextTick, watch } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import { useChatStore } from '../../stores/chatStore'

import { useRoute } from 'vue-router'
import { useTrainerMemberMonitoringStore } from '../../stores/trainerMemberMonitoringStore'

const chat = useChatStore()
const route = useRoute()
const monitoringStore = useTrainerMemberMonitoringStore()
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
    return new Intl.DateTimeFormat('en-US', { timeStyle: 'short' }).format(new Date(iso))
  } catch {
    return ''
  }
}

watch(() => chat.messages.length, scrollToBottom)

onMounted(async () => {
  await chat.loadContacts()
  const userIdParam = route.query.userId
  if (userIdParam) {
    const contactId = Number(userIdParam)
    if (!chat.contacts.some((c) => c.id === contactId)) {
      const selectedMember = monitoringStore.selected?.member
      if (selectedMember && selectedMember.id === contactId) {
        chat.contacts.push({
          id: selectedMember.id,
          name: selectedMember.full_name,
          img: null,
          role: 'member',
        })
      } else {
        chat.contacts.push({
          id: contactId,
          name: 'Member',
          img: null,
          role: 'member',
        })
      }
    }
    await selectContact(contactId)
  }
})
onUnmounted(() => chat.stopPolling())
</script>

<template>
  <WorkspaceLayout
    role="trainer"
    sidebar-title="Trainer"
    title="Chat"
    subtitle="Communicate with your members."
    :sidebar-items="trainerSidebarItems"
  >
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 1rem; min-height: 500px;"
         class="chat-layout">
      <!-- Contact List -->
      <FitnezCard style="padding: 0; overflow: hidden;">
        <div style="padding: 1rem; border-bottom: 1px solid var(--color-border);">
          <p class="stat-label">Member Contacts</p>
        </div>

        <div v-if="chat.contactsLoading" style="padding: 2rem; text-align: center;">
          <p class="text-muted" style="font-size: 0.8rem;">Loading...</p>
        </div>
        <div v-else-if="chat.contacts.length === 0" style="padding: 2rem; text-align: center;">
          <p class="text-muted" style="font-size: 0.8rem;">No contacts yet. Members who book you will appear here.</p>
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
              <div style="display: flex; align-items: center; gap: 0.5rem;">
                <p style="font-weight: 800; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ c.name }}</p>
                <span v-if="c.unread_count && c.unread_count > 0 && chat.activeContactId !== c.id" style="background: var(--color-blue); color: white; font-size: 0.6rem; font-weight: 900; border-radius: 999px; padding: 0.1rem 0.4rem; min-width: 1rem; text-align: center;">{{ c.unread_count }}</span>
              </div>
              <p class="text-muted" style="font-size: 0.7rem; text-transform: capitalize;">{{ c.role }}</p>
            </div>
          </button>
        </div>
      </FitnezCard>

      <!-- Messages -->
      <FitnezCard style="padding: 0; display: flex; flex-direction: column; overflow: hidden;">
        <div v-if="!chat.activeContact" style="flex: 1; display: grid; place-items: center; padding: 2rem;">
          <p class="text-muted">Select a contact to start chatting.</p>
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
              <p class="text-muted" style="font-size: 0.8rem;">Loading messages...</p>
            </div>
            <div v-else-if="chat.messages.length === 0" style="text-align: center; padding: 2rem;">
              <p class="text-muted" style="font-size: 0.8rem;">No messages yet. Start chatting!</p>
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
              placeholder="Type a message..."
              @keydown.enter.prevent="sendMsg"
            />
            <button class="button button-primary" :disabled="!newMessage.trim()" @click="sendMsg">
              Send
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
