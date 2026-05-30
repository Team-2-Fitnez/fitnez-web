import { io, Socket } from 'socket.io-client'

let socket: Socket | null = null

export function getSocket(): Socket | null {
  return socket
}

export function connectSocket(): Socket {
  if (socket?.connected) return socket

  const host = import.meta.env.VITE_SOCKET_HOST || 'localhost'
  const port = import.meta.env.VITE_SOCKET_PORT || '6001'

  socket = io(`ws://${host}:${port}`, {
    auth: (cb: (data: { token: string | null }) => void) => {
      cb({ token: localStorage.getItem('fitnez_access_token') })
    },
    transports: ['websocket'],
    reconnection: true,
    reconnectionAttempts: 10,
    reconnectionDelay: 2000,
  })

  socket.on('connect', () => {
    // Socket.io connected
  })

  socket.on('connect_error', () => {
    // Connection error handled by reconnection logic
  })

  socket.on('disconnect', () => {
    // Disconnect handled by reconnection logic
  })

  return socket
}

export function disconnectSocket(): void {
  if (socket) {
    socket.removeAllListeners()
    socket.disconnect()
    socket = null
  }
}
