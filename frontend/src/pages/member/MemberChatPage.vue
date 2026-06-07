<script setup lang="ts">
import { onMounted, onUnmounted, ref, nextTick, watch } from 'vue'
import { useRoute } from 'vue-router'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import SkeletonList from '../../components/ui/SkeletonList.vue'
import SkeletonCard from '../../components/ui/SkeletonCard.vue'
import SkeletonChatLayout from '../../components/ui/skeleton/SkeletonChatLayout.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'
import { useChatStore } from '../../stores/chatStore'

const chat = useChatStore()
const route = useRoute()
const { loading: initialLoading, run } = useDeferredLoading()
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

onMounted(() => run(async () => {
  await chat.loadContacts()
  const contactIdParam = route.query.contact || route.query.userId
  if (contactIdParam) {
    const contactId = Number(contactIdParam)
    if (!chat.contacts.some(c => c.id === contactId)) {
      chat.contacts.push({
        id: contactId,
        name: 'Trainer',
        img: null,
        role: 'trainer',
      })
    }
    await selectContact(contactId)
  }
}))
onUnmounted(() => chat.resetChat())
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Chat"
    subtitle="Consult with your trainer."
    :sidebar-items="memberSidebarItems"
  >
    <SkeletonChatLayout v-if="(initialLoading || chat.contactsLoading) && !chat.contacts.length" />

    <div
      v-else
      style="display: grid; grid-template-columns: 280px 1fr; gap: 1rem; min-height: 500px;"
      class="chat-layout"
    >
      <FitnezCard class="chat-contacts" style="padding: 0; overflow: hidden;">
        <div style="padding: 1rem; border-bottom: 1px solid var(--color-border);">
          <p class="stat-label">Contacts</p>
        </div>

        <div v-if="chat.contactsLoading">
          <SkeletonList :rows="6" />
        </div>
        <div v-else-if="chat.contacts.length === 0" style="padding: 2rem; text-align: center;">
          <p class="text-muted" style="font-size: 0.8rem;">No contacts yet. Contacts will appear after you book a trainer.</p>
        </div>

        <div v-else class="chat-contact-list" style="max-height: 400px; overflow-y: auto;">
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
      <FitnezCard class="chat-panel" style="padding: 0; display: flex; flex-direction: column; overflow: hidden;">
        <div v-if="!chat.activeContact && chat.contactsLoading" style="flex: 1; padding: 1rem;">
          <SkeletonCard heading :lines="2" />
        </div>
        <div v-else-if="!chat.activeContact" style="flex: 1; display: grid; place-items: center; padding: 2rem;">
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
          <div class="chat-messages" style="flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem; min-height: 300px; max-height: 400px;">
            <SkeletonList v-if="chat.messagesLoading" :rows="8" :avatar="false" />
            <div v-else-if="chat.messages.length === 0" style="text-align: center; padding: 2rem;">
              <p class="text-muted" style="font-size: 0.8rem;">No messages yet. Start chatting!</p>
            </div>
            <template v-else>
              <div
                v-for="msg in chat.messages"
                :key="msg.id"
                class="chat-bubble"
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
          <div class="chat-composer" style="padding: 0.75rem 1rem; border-top: 1px solid var(--color-border); display: flex; gap: 0.5rem;">
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
.chat-layout,
.chat-panel,
.chat-contacts {
  min-width: 0;
}

.chat-panel {
  min-height: min(620px, calc(100dvh - 11rem));
}

.chat-contact-list,
.chat-messages {
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;
}

.chat-composer {
  background: #ffffff;
  position: sticky;
  bottom: 0;
  z-index: 2;
}

.chat-composer input {
  min-width: 0;
}

@media (max-width: 768px) {
  .chat-layout {
    grid-template-columns: 1fr !important;
    min-height: 0 !important;
  }

  .chat-contact-list {
    max-height: 14rem !important;
  }

  .chat-panel {
    min-height: calc(100dvh - 12rem);
  }

  .chat-messages {
    max-height: none !important;
    min-height: 18rem !important;
  }

  .chat-bubble {
    max-width: 88% !important;
  }

  .chat-composer {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
