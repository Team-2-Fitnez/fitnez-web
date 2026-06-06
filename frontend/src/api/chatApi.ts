import { http } from './http'

export type ChatContact = {
  id: number
  name: string
  img: string | null
  role: string
  unread_count?: number
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

export type ChatMessagesResponse = {
  data: ChatMsg[]
  has_more: boolean
  oldest_id: number | null
}

export const chatApi = {
  contacts() {
    return http.get<ChatContact[]>('/chat/contacts')
  },

  messages(contactId: number, before?: number) {
    let url = `/chat/messages?contact_id=${contactId}`
    if (before) url += `&before=${before}`
    return http.get<ChatMessagesResponse>(url)
  },

  send(receiverId: number, message: string) {
    return http.post<ChatMsg>('/chat/messages', { receiver_id: receiverId, message })
  },
}
