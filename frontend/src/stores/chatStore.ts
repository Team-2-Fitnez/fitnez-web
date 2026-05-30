import { defineStore } from 'pinia'
import { chatApi, type ChatContact, type ChatMsg, type ChatMessagesResponse } from '../api/chatApi'
import { connectSocket, getSocket } from '../services/socket'
import { useAuthStore } from './authStore'

export const useChatStore = defineStore('chat', {
  state: () => ({
    contacts: [] as ChatContact[],
    messages: [] as ChatMsg[],
    activeContactId: null as number | null,
    contactsLoading: false,
    messagesLoading: false,
    hasMoreMessages: false,
    oldestMessageId: null as number | null,
    pollingInterval: null as ReturnType<typeof setInterval> | null,
    contactsRefreshInterval: null as ReturnType<typeof setInterval> | null,
    socketConnected: false,
  }),

  getters: {
    activeContact: (state) =>
      state.contacts.find((c) => c.id === state.activeContactId) ?? null,
  },

  actions: {
    async loadContacts() {
      this.contactsLoading = true
      try {
        const response = await chatApi.contacts()
        this.contacts = response.data
      } finally {
        this.contactsLoading = false
      }
    },

    async loadMessages(contactId: number) {
      this.activeContactId = contactId
      this.messagesLoading = true
      this.hasMoreMessages = false
      this.oldestMessageId = null
      try {
        const response = await chatApi.messages(contactId)
        const result = response.data as ChatMessagesResponse
        this.messages = result.data
        this.hasMoreMessages = result.has_more
        this.oldestMessageId = result.oldest_id
      } finally {
        this.messagesLoading = false
      }
      this.connectSocketIo(contactId)
      this.startPolling(contactId)
      this.startContactsRefresh()
    },

    async loadMoreMessages() {
      if (!this.activeContactId || !this.hasMoreMessages || !this.oldestMessageId) return
      this.messagesLoading = true
      try {
        const response = await chatApi.messages(this.activeContactId, this.oldestMessageId)
        const result = response.data as ChatMessagesResponse
        this.messages = [...result.data, ...this.messages]
        this.hasMoreMessages = result.has_more
        this.oldestMessageId = result.oldest_id
      } finally {
        this.messagesLoading = false
      }
    },

    connectSocketIo(contactId: number) {
      const token = localStorage.getItem('fitnez_access_token')
      if (!token) return

      try {
        const socket = getSocket() || connectSocket()
        this.socketConnected = true

        socket.off('new-message')
        socket.on('new-message', (e: { id: number; sender_id: number; receiver_id: number; message: string; created_at: string; sender_name: string; is_read: boolean }) => {
          if (e.sender_id !== contactId && e.receiver_id !== contactId) return
          const exists = this.messages.some((m) => m.id === e.id)
          if (!exists) {
            this.messages.push({
              id: e.id,
              sender_id: e.sender_id,
              receiver_id: e.receiver_id,
              message: e.message,
              created_at: e.created_at,
              sender_name: e.sender_name,
              is_read: e.is_read,
              isMe: e.sender_id === useAuthStore().user?.id,
            })
          }
        })
      } catch {
        // Socket.io not available; polling will handle updates
      }
    },

    stopSocket() {
      const socket = getSocket()
      if (socket) {
        socket.off('new-message')
      }
    },

    startPolling(contactId: number) {
      this.stopPolling()
      this.pollingInterval = setInterval(async () => {
        try {
          const response = await chatApi.messages(contactId)
          const result = response.data as ChatMessagesResponse
          const existingIds = new Set(this.messages.map((m) => m.id))
          const newMessages = result.data.filter((m) => !existingIds.has(m.id))
          if (newMessages.length > 0) {
            this.messages = [...this.messages, ...newMessages]
          }
        } catch {
          // Ignore polling errors
        }
      }, 3000)
    },

    stopPolling() {
      if (this.pollingInterval) {
        clearInterval(this.pollingInterval)
        this.pollingInterval = null
      }
    },

    startContactsRefresh() {
      this.stopContactsRefresh()
      this.contactsRefreshInterval = setInterval(async () => {
        try {
          const response = await chatApi.contacts()
          const currentIds = new Set(this.contacts.map((c) => c.id))
          const hasNew = response.data.some((c) => !currentIds.has(c.id))
          if (hasNew) {
            this.contacts = response.data
          }
        } catch {
          // Ignore refresh errors
        }
      }, 30000)
    },

    stopContactsRefresh() {
      if (this.contactsRefreshInterval) {
        clearInterval(this.contactsRefreshInterval)
        this.contactsRefreshInterval = null
      }
    },

    async sendMessage(message: string) {
      if (!this.activeContactId) return
      try {
        const response = await chatApi.send(this.activeContactId, message)
        this.messages.push(response.data)
      } catch {
        window.showFitnezToast('Gagal mengirim pesan. Coba lagi.', 'error')
      }
    },

    resetChat() {
      this.stopPolling()
      this.stopSocket()
      this.stopContactsRefresh()
      this.messages = []
      this.activeContactId = null
    },
  },
})
