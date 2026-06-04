import { http } from './http'

export type ChatContact = {
  id: number
  name: string
  img: string | null
  role: string
}

export type ChatMsg = {
  id: number
  sender_id: number
  receiver_id: number
  message: string
  created_at: string
  sender_name: string
  is_read: boolean
  isMe: boolean
}

export const chatApi = {
  contacts() {
    return http.get<ChatContact[]>('/chat/contacts')
  },

  messages(contactId: number) {
    return http.get<ChatMsg[]>(`/chat/messages?contact_id=${contactId}`)
  },

  send(receiverId: number, message: string) {
    return http.post<ChatMsg>('/chat/messages', { receiver_id: receiverId, message })
  },
}
