import { createServer } from 'http'
import { Server } from 'socket.io'
import jwt from 'jsonwebtoken'

const PORT = process.env.PORT || 6001

function jwtSecret() {
  const secret = process.env.JWT_SECRET || process.env.APP_KEY || 'fitnez-local-jwt-secret'

  if (secret.startsWith('base64:')) {
    return Buffer.from(secret.slice(7), 'base64')
  }

  return secret
}

const httpServer = createServer((req, res) => {
  if (req.method === 'POST') {
    let body = ''
    req.on('data', chunk => body += chunk)
    req.on('end', () => {
      try {
        const { event, channel, payload } = JSON.parse(body)
        const roomName = channel.replace('.', '-')
        io.to(roomName).emit(event, payload)
        res.writeHead(200, { 'Content-Type': 'application/json' })
        res.end(JSON.stringify({ ok: true }))
      } catch (e) {
        res.writeHead(400, { 'Content-Type': 'application/json' })
        res.end(JSON.stringify({ ok: false, error: e.message }))
      }
    })
  } else {
    res.writeHead(200, { 'Content-Type': 'text/plain' })
    res.end('Fitnez Socket.io Server')
  }
})

const io = new Server(httpServer, {
  cors: {
    origin: '*',
    methods: ['GET', 'POST'],
  },
})

io.use((socket, next) => {
  const token = socket.handshake.auth?.token || socket.handshake.query?.token
  if (!token) {
    return next(new Error('Authentication required'))
  }
  try {
    const decoded = jwt.verify(token, jwtSecret())
    socket.data.userId = decoded.sub || decoded.id
    next()
  } catch {
    next(new Error('Invalid token'))
  }
})

io.on('connection', (socket) => {
  const userId = socket.data.userId

  socket.join(`chat-${userId}`)
  socket.join(`notifications-${userId}`)

  socket.on('disconnect', () => {
    socket.leave(`chat-${userId}`)
    socket.leave(`notifications-${userId}`)
  })
})

httpServer.listen(PORT, '0.0.0.0', () => {
  console.log(`Fitnez Socket.io server running on port ${PORT}`)
})
