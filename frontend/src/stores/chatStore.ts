import { defineStore } from 'pinia'
import { chatApi, type ChatContact, type ChatMsg } from '../api/chatApi'
import { connectSocket, getSocket } from '../services/socket'
import { useAuthStore } from './authStore'

export const useChatStore = defineStore('chat', {
  state: () => ({
    contacts: [] as ChatContact[],
    messages: [] as ChatMsg[],
    activeContactId: null as number | null,
    contactsLoading: false,
    messagesLoading: false,
    pollingInterval: null as ReturnType<typeof setInterval> | null,
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
      try {
        const response = await chatApi.messages(contactId)
        this.messages = response.data
      } finally {
        this.messagesLoading = false
      }
      this.connectSocketIo(contactId)
      this.startPolling(contactId)
    },

    connectSocketIo(contactId: number) {
      const token = localStorage.getItem('fitnez_access_token')
      if (!token) return

      try {
        const socket = getSocket() || connectSocket(token)
        this.socketConnected = true

        socket.off(`chat-${contactId}-new-message`)
        socket.on(`chat-${contactId}-new-message`, (e: { id: number; sender_id: number; receiver_id: number; message: string; created_at: string; sender_name: string; is_read: boolean }) => {
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
        socket.off(`chat-${this.activeContactId}-new-message`)
      }
    },

    startPolling(contactId: number) {
      this.stopPolling()
      this.pollingInterval = setInterval(async () => {
        try {
          const response = await chatApi.messages(contactId)
          this.messages = response.data
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

    async sendMessage(message: string) {
      if (!this.activeContactId) return
      const response = await chatApi.send(this.activeContactId, message)
      this.messages.push(response.data)
    },

    resetChat() {
      this.stopPolling()
      this.stopSocket()
      this.messages = []
      this.activeContactId = null
    },
  },
})
