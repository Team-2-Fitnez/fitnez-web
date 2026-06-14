<script setup lang="ts">
import { onMounted, onUnmounted, ref, nextTick, watch } from 'vue'
import { useRoute } from 'vue-router'
import WorkspaceLayout from '@/shared/components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '@/shared/components/layout/sidebarItems'
import FitnezCard from '@/shared/components/ui/FitnezCard.vue'
import SkeletonList from '@/shared/components/ui/SkeletonList.vue'
import SkeletonCard from '@/shared/components/ui/SkeletonCard.vue'
import SkeletonChatLayout from '@/shared/components/ui/skeleton/SkeletonChatLayout.vue'
import { useDeferredLoading } from '@/shared/composables/useDeferredLoading'
import { chatAttachmentUrl } from '@/features/Chat/api/chatApi'
import { useChatStore } from '@/features/Chat/stores/chatStore'

const chat = useChatStore()
const route = useRoute()
const { loading: initialLoading, run } = useDeferredLoading()
const newMessage = ref('')
const messagesEnd = ref<HTMLElement | null>(null)
const pendingFile = ref<File | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

function scrollToBottom() {
  nextTick(() => {
    messagesEnd.value?.scrollIntoView({ behavior: 'smooth' })
  })
}

async function selectContact(id: number) {
  await chat.loadMessages(id)
  scrollToBottom()
}

function pickFile() {
  fileInput.value?.click()
}

function onFileSelected(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    pendingFile.value = target.files[0]
  }
}

function clearFile() {
  pendingFile.value = null
  if (fileInput.value) fileInput.value.value = ''
}

async function sendMsg() {
  const text = newMessage.value.trim()
  if (!text && !pendingFile.value) return
  const file = pendingFile.value
  newMessage.value = ''
  clearFile()
  await chat.sendMessage(text, file || undefined)
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
                <p v-if="msg.message">{{ msg.message }}</p>
                <div v-if="msg.file_url" style="margin-top: 0.25rem;">
                  <a :href="chatAttachmentUrl(msg.file_url)" target="_blank" rel="noopener noreferrer"
                     :style="{ color: msg.isMe ? 'white' : 'var(--color-blue)', textDecoration: 'underline', fontSize: '0.8rem', display: 'inline-flex', alignItems: 'center', gap: '0.25rem' }">
                    <span class="material-symbols-outlined" style="font-size: 1rem;">attach_file</span>
                    {{ msg.file_name || 'Attachment' }}
                  </a>
                </div>
                <p :style="{ fontSize: '0.65rem', opacity: 0.5, marginTop: '0.25rem', textAlign: 'right' }">
                  {{ formatTime(msg.created_at) }}
                </p>
              </div>
            </template>
            <div ref="messagesEnd" />
          </div>

          <!-- Input -->
          <div class="chat-composer" style="padding: 0.75rem 1rem; border-top: 1px solid var(--color-border); display: flex; gap: 0.5rem; align-items: center;">
            <button type="button" class="button button-ghost" style="padding: 0.375rem; font-size: 1.25rem; line-height: 1;" @click="pickFile" title="Attach file">
              <span class="material-symbols-outlined">attach_file</span>
            </button>
            <input ref="fileInput" type="file" style="display: none;" @change="onFileSelected" />
            <div v-if="pendingFile" style="display: flex; align-items: center; gap: 0.25rem; background: var(--color-cream); border-radius: 0.5rem; padding: 0.25rem 0.5rem; font-size: 0.75rem; white-space: nowrap; overflow: hidden; max-width: 8rem;">
              <span class="material-symbols-outlined" style="font-size: 0.9rem;">attach_file</span>
              <span style="overflow: hidden; text-overflow: ellipsis;">{{ pendingFile.name }}</span>
              <button type="button" style="background: none; border: none; cursor: pointer; font-size: 0.8rem; padding: 0;" @click="clearFile">&times;</button>
            </div>
            <input
              v-model="newMessage"
              class="form-input"
              style="flex: 1;"
              placeholder="Type a message..."
              @keydown.enter.prevent="sendMsg"
            />
            <button class="button button-primary" :disabled="!newMessage.trim() && !pendingFile" @click="sendMsg">
              Send
            </button>
          </div>
        </template>
      </FitnezCard>
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

.material-symbols-outlined {
  direction: ltr;
  display: inline-block;
  flex: 0 0 auto;
  font-family: 'Material Symbols Outlined';
  font-feature-settings: 'liga';
  font-size: 1.25rem;
  font-style: normal;
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  font-weight: normal;
  letter-spacing: normal;
  line-height: 1;
  text-transform: none;
  white-space: nowrap;
  width: 1em;
  word-wrap: normal;
  -webkit-font-feature-settings: 'liga';
  -webkit-font-smoothing: antialiased;
}

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
