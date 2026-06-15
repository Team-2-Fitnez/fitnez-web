FROM node:22-alpine

WORKDIR /app

COPY backend/realtime/package*.json ./
RUN npm install

COPY backend/realtime/server.js ./server.js

EXPOSE 6001

CMD ["node", "server.js"]
