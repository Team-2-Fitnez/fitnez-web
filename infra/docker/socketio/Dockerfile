FROM node:22-alpine

WORKDIR /app

COPY socketio/package*.json ./
RUN npm install

COPY socketio/server.js ./server.js

EXPOSE 6001

CMD ["node", "server.js"]
