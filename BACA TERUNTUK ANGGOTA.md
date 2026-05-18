# Fitnez cleanup and trainer-flow fixes

Silahkan nanti kalian pull, lalu setelah kalian buka atau tes hasil kodingan, silahkan di gabung, inget ambil projectnya dari branch develop.

## Main fixes

1. Removed shareable-package dependency on local runtime files. The final ZIP excludes `.env`, `node_modules`, `vendor`, logs, sessions, cache, local SQLite databases, frontend `dist`, and uploaded payment proof files.
2. Fixed the frontend production build TypeScript errors by completing the landing-visit types and guarding browser UUID generation.
3. Changed login behavior so users login directly with email and password. OTP is now exposed for password reset through `/auth/password/forgot` and `/auth/password/reset`.
4. Added a member-to-trainer application flow:
   - Member opens Profile.
   - Member uploads CV PDF and certificate PDF.
   - Admin reviews the trainer application.
   - If approved, the member gets access to the Trainer Workspace without needing a separate trainer login.
   - Trainer can switch back to Member Workspace from Trainer Profile.
5. Added admin trainer-application review routes and frontend page.
6. Exposed existing admin pages in the router/sidebar: users, trainers, schedules, trainer applications, payment review, and landing visitors.
7. Changed sidebar logout buttons so they call the real logout API action before redirecting.
8. Hardened `NotificationController::markAsRead` so a user can only mark their own notification or a global notification as read.
9. Added `Payment.php` model and updated dashboard logic so payment counts no longer depend on a missing model class.
10. Added dummy accounts for group testing.

## Dummy accounts

All dummy accounts use this password:

```txt
FitnezTeam2@2026
```

Accounts:

```txt
admin@fitnez.test
trainer@fitnez.test
member@fitnez.test
```

`trainer@fitnez.test` is still a member login account, but it already has an approved trainer application and can enter the Trainer Workspace.

## Important commands

Frontend:

```bash
cd frontend
npm install
npm run build
npm run dev
```

Backend:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Docker:

```bash
docker compose up --build
```

## Cara jalankan projectt

- Pertama, masuk ke tempat kalian simpan project fitnez (saya sarankan simpan di WSL kalian masing masing).
- Buka terminal WSL Debian kalian, arahkan ke folder project ulang, lalu silahkan jalankan perintah ini buat matikan container lama: docker compose down --remove-orphans 
- Kalau misalkan pernah konflik di nama container, jalankan ini: docker rm -f fitnez-app fitnez-nginx fitnez-frontend fitnez-db
- Kalau container emang gaada, silahkan jalankan ini untuk buat file .envnya: (cp backend/.env.example.patch backend/.env) & (cp frontend/.env.example frontend/.env).
- Silahkan jalankan ini kalau sudah: docker compose up -d --build
- Kalau prosesnya udah selesai, cek statusnya: docker compose ps
- Setelah container hidup, jalankan ini: docker compose exec app composer install
- generate laravel key nya: docker compose exec app php artisan key:generate
- jalanin migration databasenya: docker compose exec app php artisan migrate
- *INI PENTING BUAT TESTING BTW, Silahkan masukkan akun dummy yang ada diatas, Udah 20 tahun, silahkan dibaca mana email mana password: docker compose exec app php artisan db:seed
- ntar kalau laravelnya tanya, jawab aja yes.
- ***Kalau muncul pesan bahwa environment dianggap production dan command ditolak, baru gunakan versi --force: (docker compose exec app php artisan migrate --force) & (docker compose exec app php artisan db:seed --force).
- Setelah itu, karena versi v5 memakai named volume untuk storage, sebaiknya cek permission upload/log terlebih dahulu: docker compose exec -u www-data app sh -lc "echo test >> storage/logs/laravel.log && mkdir -p storage/app/private/trainer-applications/test && rmdir storage/app/private/trainer-applications/test"
- Kalau command itu (diatas) tidak mengeluarkan error, berarti Laravel sudah bisa menulis log dan membuat folder upload. Artinya upload CV/Sertifikat seharusnya sudah aman.
- silahkan jalankan ini kalau udah semua, terus masuk ke link local dulu karena harus kalian test di local dan kalian wajib tahu struktur webnya gimana sekarang: docker-compose logs -f frontend
- Untuk cek OTP Kalian bisa pakai ini di terminal: docker compose exec app sh -lc "tail -n 100 storage/logs/laravel.log"

## 2026-05-10 hardening pass

- Removed the visible dummy-account card from the login UI so demo credentials are no longer exposed in the application screen.
- Replaced stale dashboard labels that described login as OTP-based; login is now shown as direct email/password, with OTP only for forgot-password reset.
- Removed the frontend registration-OTP page from routing; `/verify-otp` redirects to `/forgot-password`.
- Added request validation classes for forgot password, reset password, registration status lookup, notification list pagination, public limited lists, and trainer application uploads/reviews.
- Added Laravel trim/empty-string normalization middleware and stricter email normalization in login/OTP requests.
- Kept database access through Eloquent ORM models/relationships/scopes and sanitized search terms before applying LIKE/ILIKE filters.
- Added explicit limits/pagination to public package/payment-method fetches, notification fetches, landing visit summaries, admin tables, and role lists.
- Changed admin-created trainers to remain `member` role users with trainer profile/access, instead of creating a separate trainer login role.
