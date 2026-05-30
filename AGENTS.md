# Fitnez

**Stack:** Laravel 13 (PHP 8.3+) / Vue 3 + Pinia + TailwindCSS + TS 6 / PostgreSQL 16 / Socket.io / Docker Compose

## 1. Repo layout

```
├── backend/          Laravel API (PHP 8.4-fpm, Nginx on :8080)
├── frontend/         Vue 3 + Vite 8 (dev on :5173)
│   ├── index.html    Public pages (landing, login, register, faq, forgot-pw)
│   ├── admin.html    Admin workspace entry
│   ├── member.html   Member workspace entry
│   └── trainer.html  Trainer workspace entry
├── docker/
│   ├── php/          PHP-FPM Dockerfile + entrypoint
│   ├── nginx/        Nginx config
│   ├── socketio/     Node.js Socket.io server (port 6001)
│   └── postgres/     Init scripts
└── docker-compose.yml All 5 services orchestrated
```

## 2. MPA architecture

4 Vite entry points → separate bundles in production build. In dev mode Vite serves everything from `index.html` (SPA-like), but production generates `admin.html`, `member.html`, `trainer.html`.

Role redirects use **`window.location.href`** (full reload across entry points) — see `frontend/src/router-guard.ts`.

Trainer access uses `can_access_trainer_workspace` flag on member `User` model (no separate trainer login). Admin-created trainers stay `member` role with a trainer profile.

## 3. Commands

**Backend:**
```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan storage:link
docker compose exec app php artisan route:list
docker compose exec app php artisan test
```

**Frontend (all in container):**
```bash
docker compose exec frontend npm run dev          # Vite dev server
docker compose exec frontend npm run build        # type-check + build
docker compose exec frontend npm run type-check   # vue-tsc --build
docker compose exec frontend npm run lint         # oxlint + eslint
docker compose exec frontend npm run format       # prettier
docker compose exec frontend npm run test:unit    # vitest
```

**Full setup from clean:**
```bash
docker compose down --remove-orphans
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan migrate --seed
docker compose exec -u www-data app sh -lc "echo test >> storage/logs/laravel.log"
```

**Quick rebuild after code changes:**
```bash
docker compose up -d --build app frontend    # rebuild PHP/Vite containers
docker compose exec app php artisan migrate   # if new migrations
```

**Clean slate (nuke volumes):**
```bash
docker compose down -v --remove-orphans && docker compose up -d --build
```

## 4. Auth & session

- Login: **email + password direct** (OTP removed from login flow)
- OTP only for forgot-password via `/auth/password/forgot` and `/auth/password/reset`
- `/verify-otp` redirects to `/forgot-password`
- Token: JWT stored in localStorage key `fitnez_access_token`
- Auth middleware: `JwtAuthenticate` + `EnsureRole` (admin/member/trainer)
- Test accounts (pw: `FitnezTeam2@2026`): `admin@fitnez.test`, `trainer@fitnez.test`, `member@fitnez.test`

## 5. Real-time (Socket.io)

Backend broadcasts via HTTP POST to the socket.io server. Frontend uses `socket.io-client` (service at `frontend/src/services/socket.ts`).

- Chat: room `chat-{userId}`, event `.new-message`
- Notifications: room `notifications-{userId}`, event `.new-notification`
- Fallback polling: 3s for chat, 15s for notifications (see `chatStore.ts`, `WorkspaceLayout.vue`)
- Socket.io JWT secret must match `JWT_SECRET` in `backend/.env` (default: `fitnez-local-jwt-secret`)

Env vars: `VITE_SOCKET_HOST`, `VITE_SOCKET_PORT` (frontend); `PUSHER_*` vars in backend `.env` are **stale** (leftover from Soketi era — the actual broadcasting helper is `SocketioBroadcast.php`).

## 6. Key architecture rules

- **Do NOT change UI or logic** when adding features. If adding a menu item, keep UI identical.
- **60% library usage cap** per feature — wrap external libraries with your own abstraction.
- **Form validation**: use `vee-validate` + `zod` everywhere. Pattern: `toTypedSchema(z.object({...}).refine(...))` then `useForm({ validationSchema })`.
- **Error handling**: use `getApiErrorMessage()` from `frontend/src/utils/apiError.ts`. Show errors to users via `window.showFitnezToast(msg, 'error')` — never `console.error`.
- **Laravel routes**: notification routes order matters — `markAllRead` (PATCH `/notifications/read-all`) must be registered **before** `markAsReadIndividual` (PATCH `/notifications/{notification}/read`) to avoid route collision.
- **Admin monitoring** of member data is **read-only** — must not modify workout/meal/nutrition data.

## 7. Frontend architecture

| Layer | Location |
|-------|----------|
| Entry points | `frontend/src/main.ts`, `main-admin.ts`, `main-member.ts`, `main-trainer.ts` |
| Route guard | `frontend/src/router-guard.ts` (shared, `installGuard()`) |
| Router | `frontend/src/router/index.ts` (single file, all roles) |
| State | Pinia stores in `frontend/src/stores/` |
| API | Axios wrappers in `frontend/src/api/` |
| Types | `frontend/src/types/` |
| Utils | `frontend/src/utils/` (apiError, cookieConsent, deviceId, storage) |

## 8. Backend architecture

| Layer | Location |
|-------|----------|
| Controllers | `backend/app/Http/Controllers/` (subdirs: Admin/, Trainer/, Api/, Analytics/) |
| Models | `backend/app/Models/` (32 models, Eloquent ORM) |
| Middleware | `JwtAuthenticate`, `EnsureRole`, `EnsureTrainerWorkspaceAccess` |
| Events | `NewChatMessage`, `NewNotification` (broadcast via `SocketioBroadcast.php`) |
| Excel import | `ExcelImportController.php` — supports `members`, `workouts`, `schedules` CSV types |

## 9. Dummy accounts & testing

All use password `FitnezTeam2@2026`:
- `admin@fitnez.test` — admin workspace
- `trainer@fitnez.test` — member login with approved trainer application
- `member@fitnez.test` — member workspace

Cookie consent testing: clear localStorage keys `fitnez_cookie_consent` and `fitnez_cookie_anon_id`.
OTP (forgot-password) appears in `storage/logs/laravel.log` when `MAIL_MAILER=log`.

## 10. Known quirks / pitfalls

- **`.env` files must never be committed.** Only `.env.example` goes to git.
- **DB host must be `db`** inside Docker (not `localhost`).
- **Vite proxy** for Docker must target `http://nginx` (not `localhost:8080`). Only change to `localhost:8080` when running frontend outside Docker.
- **Permission fixes** when Laravel can't write logs/uploads:
  ```bash
  docker compose exec app mkdir -p storage/logs bootstrap/cache
  docker compose exec app chmod -R 775 storage bootstrap/cache
  docker compose exec app chown -R www-data:www-data storage bootstrap/cache
  ```
- **Payment is simulated** — no real payment gateway. Use `POST /member/payments/simulate-create` and `POST /member/payments/simulate-pay`.
- **TanStack Query / React Query is NOT used** — polling + Pinia stores only.
- **FCM (Firebase Cloud Messaging)** for mobile push cannot be implemented in this repo (Android native repo only). Web Push API is already implemented.
- **No real cloud DB yet** — PostgreSQL runs in Docker locally.
