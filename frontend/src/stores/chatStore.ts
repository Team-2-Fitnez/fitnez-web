import { defineStore } from 'pinia'
import { chatApi, type ChatContact, type ChatMsg, type ChatMessagesResponse } from '../api/chatApi'
import { connectSocket, getSocket } from '../services/socket'
import { useAuthStore } from './authStore'

type SocketMessage = {
  id: number
  sender_id: number
  receiver_id: number
  message: string
  created_at: string
  sender_name: string
  is_read: boolean
}

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
    activeContact: (state) => state.contacts.find(contact => contact.id === state.activeContactId) ?? null,
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
      this.startContactsRefresh()
    },

    async loadMessages(contactId: number) {
      this.activeContactId = contactId
      this.messagesLoading = true
      this.hasMoreMessages = false
      this.oldestMessageId = null

      try {
        const response = await chatApi.messages(contactId)
        this.applyMessagePage(response.data, false)
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
        this.applyMessagePage(response.data, true)
      } finally {
        this.messagesLoading = false
      }
    },

    applyMessagePage(result: ChatMessagesResponse, prepend: boolean) {
      this.messages = prepend ? [...result.data, ...this.messages] : result.data
      this.hasMoreMessages = result.has_more
      this.oldestMessageId = result.oldest_id
    },

    appendSocketMessage(event: SocketMessage) {
      if (!this.activeContactId) return
      if (event.sender_id !== this.activeContactId && event.receiver_id !== this.activeContactId) return
      if (this.messages.some(message => message.id === event.id)) return

      this.messages.push({
        id: event.id,
        sender_id: event.sender_id,
        receiver_id: event.receiver_id,
        message: event.message,
        created_at: event.created_at,
        sender_name: event.sender_name,
        is_read: event.is_read,
        isMe: event.sender_id === useAuthStore().user?.id,
      })
    },

    connectSocketIo(contactId: number) {
      const token = localStorage.getItem('fitnez_access_token')
      if (!token) return

      try {
        const socket = getSocket() || connectSocket(token)
        this.socketConnected = true

        socket.off('new-message')
        socket.off(`chat-${contactId}-new-message`)
        socket.on('new-message', (event: SocketMessage) => this.appendSocketMessage(event))
        socket.on(`chat-${contactId}-new-message`, (event: SocketMessage) => this.appendSocketMessage(event))
      } catch {
        this.socketConnected = false
      }
    },

    stopSocket() {
      const socket = getSocket()
      if (!socket) return

      socket.off('new-message')
      if (this.activeContactId) socket.off(`chat-${this.activeContactId}-new-message`)
      this.socketConnected = false
    },

    startPolling(contactId: number) {
      this.stopPolling()
      this.pollingInterval = setInterval(async () => {
        try {
          const response = await chatApi.messages(contactId)
          const existingIds = new Set(this.messages.map(message => message.id))
          const newMessages = response.data.data.filter(message => !existingIds.has(message.id))
          if (newMessages.length) this.messages = [...this.messages, ...newMessages]
          this.hasMoreMessages = response.data.has_more
          this.oldestMessageId = response.data.oldest_id
        } catch {
          // Polling is a fallback; ignore transient refresh failures.
        }
      }, 3000)
    },

    stopPolling() {
      if (!this.pollingInterval) return
      clearInterval(this.pollingInterval)
      this.pollingInterval = null
    },

    startContactsRefresh() {
      this.stopContactsRefresh()
      this.contactsRefreshInterval = setInterval(async () => {
        try {
          const response = await chatApi.contacts()
          this.contacts = response.data
        } catch {
          // Ignore transient refresh failures.
        }
      }, 4000)
    },

    stopContactsRefresh() {
      if (!this.contactsRefreshInterval) return
      clearInterval(this.contactsRefreshInterval)
      this.contactsRefreshInterval = null
    },

    async sendMessage(message: string) {
      if (!this.activeContactId) return

      try {
        const response = await chatApi.send(this.activeContactId, message)
        this.messages.push(response.data)
      } catch {
        window.showFitnezToast('Failed to send message. Please try again.', 'error')
      }
    },

    resetChat() {
      this.stopPolling()
      this.stopSocket()
      this.stopContactsRefresh()
      this.messages = []
      this.activeContactId = null
      this.hasMoreMessages = false
      this.oldestMessageId = null
    },
  },
})
