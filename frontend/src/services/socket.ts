import { io, Socket } from 'socket.io-client'

let socket: Socket | null = null

export function getSocket(): Socket | null {
  return socket
}

export function connectSocket(token: string): Socket {
  if (socket?.connected) return socket

  const host = import.meta.env.VITE_SOCKET_HOST || 'localhost'
  const port = import.meta.env.VITE_SOCKET_PORT || '6001'

  socket = io(`ws://${host}:${port}`, {
    auth: { token },
    transports: ['websocket'],
    reconnection: true,
    reconnectionAttempts: 10,
    reconnectionDelay: 2000,
  })

  socket.on('connect', () => {
    console.log('[Socket.io] Connected')
  })

  socket.on('connect_error', (err) => {
    console.warn('[Socket.io] Connection error:', err.message)
  })

  socket.on('disconnect', (reason) => {
    console.log('[Socket.io] Disconnected:', reason)
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
