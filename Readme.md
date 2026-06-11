# Fitnez cleanup and trainer-flow fixes

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

## Cara jalankan project
Saran utama: jalankan project ini lewat **WSL Debian/Ubuntu**, bukan langsung dari folder Windows, supaya permission Laravel, storage upload, log, dan volume Docker lebih stabil.

---

## 1. Masuk ke Folder Project

Pertama, buka terminal WSL kalian, lalu masuk ke folder utama project FitNez.
Folder yang benar adalah folder yang berisi file:

```txt
docker-compose.yml
backend/
frontend/
docker/
```

Contoh:

```bash
cd ~/Fitnez/fitnez-web
```

Kalau folder kalian beda, gak masalah. Yang penting kalian berada di folder yang ada file `docker-compose.yml`.

---

## 2. Matikan Container Lama
Sebelum menjalankan ulang project, matikan dulu container lama supaya gak bentrok.

```bash
docker compose down --remove-orphans
```

Kalau sebelumnya pernah muncul error konflik nama container seperti `fitnez-frontend already in use`, jalankan command ini:

```bash
docker rm -f fitnez-app fitnez-nginx fitnez-frontend fitnez-db
```

Kalau muncul pesan bahwa container tidak ditemukan, itu tidak masalah. Artinya container lama memang sudah tidak ada.

---

## 3. Siapkan File `.env`
Project ini butuh file `.env` di backend Laravel.

Letakkan file `.env` yang dibagikan bersama panduan ini ke:

```txt
backend/.env
```

Jadi hasil akhirnya harus seperti ini:

```txt
backend/.env
frontend/
docker-compose.yml
```

Kalau kalian sudah punya `backend/.env`, jangan langsung ditimpa. Lebih aman backup dulu:

```bash
cp backend/.env backend/.env.backup
```

Lalu baru copy file `.env` yang baru:

```bash
cp .env backend/.env
```

Catatan penting: file `.env` asli jangan di-push ke GitHub. File ini dipakai untuk konfigurasi local masing-masing anggota.

---

## 4. Jalankan Docker
Setelah file `.env` siap, jalankan Docker dari root project:

```bash
docker compose up -d --build
```

Tunggu sampai proses build selesai.
Setelah itu cek status container:

```bash
docker compose ps
```

---

## 5. Install Dependency Laravel
Setelah container hidup, install dependency backend Laravel:

```bash
docker compose exec app composer install
```

Kalau `composer install` sudah pernah dijalankan dan tidak ada perubahan dependency, step ini biasanya cepat.

---

## 6. Generate Laravel Key
Jalankan:

```bash
docker compose exec app php artisan key:generate
```

Laravel membutuhkan `APP_KEY` untuk enkripsi session, token, dan data sensitif lain.
Kalau `APP_KEY` kosong, aplikasi bisa error.

---

## 7. Bersihkan Cache Laravel
Setelah ada perubahan file route, controller, `.env`, migration, atau config, jalankan:

```bash
docker compose exec app php artisan optimize:clear
```

Ini penting supaya Laravel membaca konfigurasi terbaru.

---

## 8. Jalankan Migration Database
Jalankan migration:

```bash
docker compose exec app php artisan migrate
```

Step ini akan membuat tabel-tabel database, termasuk tabel tambahan untuk fitur baru seperti cookie consent jika migration-nya sudah ada.
Kalau Laravel bertanya konfirmasi, jawab:

```txt
yes
```

Kalau muncul pesan bahwa environment dianggap production dan command ditolak, baru gunakan versi force:

```bash
docker compose exec app php artisan migrate --force
```

Tapi untuk local development, seharusnya `APP_ENV` di `backend/.env` tetap:

```env
APP_ENV=local
```

---

## 9. Masukkan Data Dummy untuk Testing
Untuk memasukkan akun dummy dan data awal:

```bash
docker compose exec app php artisan db:seed
```

Kalau Laravel bertanya konfirmasi, jawab:

```txt
yes
```

Kalau environment dianggap production dan command ditolak, gunakan:

```bash
docker compose exec app php artisan db:seed --force
```

Akun dummy yang ada di seeder dipakai untuk testing login admin, trainer, dan member. Silahkan baca data seedernya untuk melihat mana email dan mana password.

---

## 10. Jika Project Memakai JWT
Kalau project memakai JWT dan muncul error terkait `JWT_SECRET`, jalankan:

```bash
docker compose exec app php artisan jwt:secret
```

Kalau command tersebut tidak dikenali, berarti package JWT tidak memakai command itu atau project memakai mekanisme auth lain. Kalau tidak ada error JWT, step ini boleh dilewati.

---

## 11. Cek Permission Laravel Storage dan Log
Karena project ini memakai upload file seperti CV/Sertifikat trainer dan Laravel log, cek permission terlebih dahulu:

```bash
docker compose exec -u www-data app sh -lc "echo test >> storage/logs/laravel.log && mkdir -p storage/app/private/trainer-applications/test && rmdir storage/app/private/trainer-applications/test"
```

Kalau command di atas tidak mengeluarkan error, berarti Laravel sudah bisa menulis log dan membuat folder upload. Artinya upload CV/Sertifikat seharusnya sudah aman.
Kalau muncul error permission seperti:

```txt
laravel.log could not be opened
Permission denied
```

jalankan:

```bash
docker compose exec app mkdir -p storage/logs bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

Setelah itu ulangi lagi command pengecekan permission.

---

## 12. Jalankan Storage Link Jika Dibutuhkan
Kalau project memakai file yang perlu diakses lewat `/storage`, jalankan:

```bash
docker compose exec app php artisan storage:link
```

Kalau muncul pesan link sudah ada, itu tidak masalah.

---
## 13. Cek Build Frontend
Jalankan build frontend:

```bash
docker compose exec frontend npm run build
```

Kalau build berhasil, berarti file Vue, router, component, dan TypeScript aman.
Kalau build gagal setelah menambahkan cookie consent, cek beberapa hal ini:

```txt
1. CookieConsent.vue sudah berada di frontend/src/components/
2. cookieConsent.ts sudah berada di frontend/src/utils/
3. PrivacyCookiePolicy.vue sudah berada di frontend/src/pages/
4. Import dengan alias @ sudah didukung di vite.config.js
5. CookieConsent sudah dipasang di App.vue
```

---

## 14. Buka Website Local
Frontend Vue dibuka dari:

```txt
http://localhost:5173
```

Backend Laravel lewat Nginx biasanya bisa dicek dari:

```txt
http://localhost:8080
```

Kalau frontend berjalan tapi API gagal, cek `vite.config.js`.
Untuk Docker, bagian proxy harus mengarah ke service Nginx:

```js
proxy: {
  '/api': {
    target: 'http://nginx',
    changeOrigin: true,
    secure: false,
  },
  '/storage': {
    target: 'http://nginx',
    changeOrigin: true,
    secure: false,
  },
}
```

Kalau frontend dijalankan di luar Docker langsung dari laptop, barulah target proxy biasanya diganti ke:

```js
target: 'http://localhost:8080'
```

Tapi untuk setup Docker kelompok ini, gunakan:

```js
target: 'http://nginx'
```

---

## 15. Melihat Log
Untuk melihat log frontend:

```bash
docker compose logs -f frontend
```

Untuk melihat log backend Laravel container:

```bash
docker compose logs -f app
```

Untuk melihat log Laravel langsung dari file log:

```bash
docker compose exec app sh -lc "tail -n 100 storage/logs/laravel.log"
```

---

## 16. Cara Cek OTP Local
Untuk development local, OTP sebaiknya tidak dikirim ke email asli. OTP akan ditulis ke Laravel log jika `MAIL_MAILER=log`.
Cek OTP dengan:

```bash
docker compose exec app tail -n 80 storage/logs/laravel.log
```

Kalau OTP tidak muncul, cek `backend/.env` dan pastikan:

```env
MAIL_MAILER=log
```

---
## 17. Masalah yang Paling Sering Terjadi
### Database tidak terhubung

Cek `backend/.env`.
Untuk Docker, database host harus:

```env
DB_HOST=db
```

Jangan pakai:

```env
DB_HOST=localhost
```

Kenapa? Karena di dalam Docker, `localhost` berarti container Laravel itu sendiri, bukan container database. Database ada di service Docker bernama `db`.

### Frontend tidak bisa akses API
Cek `frontend/vite.config.js`.
Kalau frontend berjalan di Docker, proxy `/api` harus:

```js
target: 'http://nginx'
```

### Cookie consent muncul tapi tidak masuk database

Cek tiga hal:

```txt
1. Route POST /api/cookie-consents sudah ada
2. Migration cookie_consents sudah dijalankan
3. Proxy /api di Vite sudah mengarah ke http://nginx
```

### Laravel tidak bisa menulis log atau upload file
Cek permission:

```bash
docker compose exec app mkdir -p storage/logs bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Container conflict
Kalau muncul container name already in use:

```bash
docker rm -f fitnez-app fitnez-nginx fitnez-frontend fitnez-db
docker compose up -d --build
```

---

## 18. Command Ringkas dari Awal
Kalau mau setup dari awal, jalankan urutan ini:

```bash
cd ~/Fitnez/fitnez-web

docker compose down --remove-orphans
docker rm -f fitnez-app fitnez-nginx fitnez-frontend fitnez-db

cp .env backend/.env

docker compose up -d --build
docker compose ps

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed

docker compose exec -u www-data app sh -lc "echo test >> storage/logs/laravel.log && mkdir -p storage/app/private/trainer-applications/test && rmdir storage/app/private/trainer-applications/test"

docker compose exec frontend npm run build
```

Lalu buka:

```txt
http://localhost:5173
```

---

## 19. Catatan untuk Kalian.
Jangan commit file:

```txt
backend/.env
frontend/.env
```

Yang boleh di-commit adalah file contoh:

```txt
backend/.env.example
backend/.env.example.patch
frontend/.env.example
```

File `.env` berisi konfigurasi local dan bisa berbeda antar laptop anggota. Kalau file `.env` salah, error yang paling sering muncul adalah database tidak terhubung, API gagal dipanggil, atau OTP tidak muncul di log.

## 2026-05-10 hardening pass

- Removed the visible dummy-account card from the login UI so demo credentials are no longer exposed in the application screen.
- Replaced stale dashboard labels that described login as OTP-based; login is now shown as direct email/password, with OTP only for forgot-password reset.
- Removed the frontend registration-OTP page from routing; `/verify-otp` redirects to `/forgot-password`.
- Added request validation classes for forgot password, reset password, registration status lookup, notification list pagination, public limited lists, and trainer application uploads/reviews.
- Added Laravel trim/empty-string normalization middleware and stricter email normalization in login/OTP requests.
- Kept database access through Eloquent ORM models/relationships/scopes and sanitized search terms before applying LIKE/ILIKE filters.
- Added explicit limits/pagination to public package/payment-method fetches, notification fetches, landing visit summaries, admin tables, and role lists.
- Changed admin-created trainers to remain `member` role users with trainer profile/access, instead of creating a separate trainer login role.
