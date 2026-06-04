import { defineStore } from 'pinia'
import { chatApi, type ChatContact, type ChatMsg } from '../api/chatApi'

export const useChatStore = defineStore('chat', {
  state: () => ({
    contacts: [] as ChatContact[],
    messages: [] as ChatMsg[],
    activeContactId: null as number | null,
    contactsLoading: false,
    messagesLoading: false,
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
    },

    async sendMessage(message: string) {
      if (!this.activeContactId) return
      const response = await chatApi.send(this.activeContactId, message)
      this.messages.push(response.data)
    },
  },
})
