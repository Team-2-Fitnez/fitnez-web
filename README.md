# Fitnez Web

Aplikasi manajemen gym berbasis web dengan 3 workspace role: **Admin**, **Member**, dan **Trainer**.

## Tech Stack

| Layer | Teknologi |
|---|---|
| Frontend | Vue 3, TypeScript, Pinia, Vue Router 5, Tailwind CSS, Chart.js, Vite 8 |
| Backend | Laravel 13, PHP 8.4, PostgreSQL 16 |
| Real-time | Socket.io (Node 22, port 6001) |
| Infra | Docker Compose (app, nginx, frontend, socketio, db) |

## Workspace Access

Setiap role memiliki entrypoint SPA terpisah:

| Role | URL |
|---|---|
| Public (Landing) | `http://localhost:5173` |
| Admin | `http://localhost:5173/entries/admin.html` |
| Member | `http://localhost:5173/entries/member.html` |
| Trainer | `http://localhost:5173/entries/trainer.html` |

### Fitur Per Role

| Fitur | Admin | Member | Trainer |
|---|---|---|---|
| Dashboard & Statistik | ✅ | ✅ (Attendance) | ✅ |
| Manajemen User | ✅ | — | — |
| Konfirmasi Pendaftaran | ✅ | — | — |
| Konfirmasi Pembayaran | ✅ | ✅ (Riwayat) | — |
| Laporan Check-in | ✅ | — | — |
| Laporan Kehadiran & Pembayaran | ✅ | — | — |
| Landing Visit Analytics | ✅ | — | — |
| Auth Activity Logs | ✅ | — | — |
| Manajemen Paket Membership | ✅ | ✅ (Renewal) | — |
| Manajemen Jadwal & Kelas | ✅ | ✅ | ✅ |
| Workout Plan & Tracking | — | ✅ | — |
| Meal Plan & Nutrition | — | ✅ | ✅ (Monitoring) |
| Attendance (Check-in/out) | — | ✅ | ✅ |
| Hire Trainer & Booking | — | ✅ | — |
| Chat Real-time | — | ✅ | ✅ |
| Notifications | ✅ | ✅ | ✅ |
| Profile & Settings | ✅ | ✅ | ✅ |
| Member Progress Monitoring | — | — | ✅ |
| Rent History / Komisi | — | — | ✅ |
| Trainer Application | — | ✅ | — |

## Dummy Accounts

Semua akun menggunakan password: `FitnezTeam2@2026`

| Email | Role |
|---|---|
| `admin@fitnez.test` | Admin |
| `trainer@fitnez.test` | Member + Trainer (dual role) |
| `member@fitnez.test` | Member |

`trainer@fitnez.test` adalah akun member yang juga memiliki akses ke trainer workspace.

## Cara Install File
Saran utama: jalankan project ini lewat **WSL Debian/Ubuntu**, bukan langsung dari folder Windows, supaya permission Laravel, storage upload, log, dan volume Docker lebih stabil.

---

## 1. Masuk ke Folder Project

Pertama, buka terminal WSL kalian, lalu masuk ke folder utama project FitNez.
Folder yang benar adalah folder yang berisi file:

```txt
docker-compose.yml
backend/
frontend/
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

## 15. Cara Cek OTP Local
Untuk development local, OTP sebaiknya tidak dikirim ke email asli. OTP akan ditulis ke Laravel log
Cek OTP dengan:

```bash
docker compose exec app tail -n 80 storage/logs/laravel.log
```

Kalau OTP tidak muncul, cek `backend/.env` dan pastikan:

```env
MAIL_MAILER=log
```

---

## 18. Command Ringkas dari Awal
Kalau mau setup dari awal, jalankan urutan ini:

```bash
docker compose up -d --build
docker compose ps

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed

docker compose exec frontend npm run build
```

Lalu buka:

```txt
http://localhost:5173
```
---

## Fitur tersedia.

Fitur authentication-attendance-profile
```
Fitur Authentication:
1. Cara kerjanya user bisa register atau login terlebih dahulu, jika register cukup mengisi data dan password, lalu langsung pilih paket membership, setelah itu nanti akan admin approve dan user bisa login menggunakan akun yang sudah terdaftar.
2. User bisa cek status registrasinya di halaman login
3. Jika user langsung login dan user lupa password, maka user bisa input email yang digunakan untuk di kirimkan otp oleh admin (otp diakses oleh admin dan admin akan mengirim lewat email untuk dimasukkan otpnya). Jika user sudah memasukkan otp dengan benar, dan memasukkan password dengan benar, user bisa langsung melakukan login ulang dengan password yang baru.

Fitur Attendance:
1. User bisa checkin untuk melakukan absensi yang artinya ia masuk untuk berolahraga hari ini di dashboard dan checkout jika aktivitas di gym sudah selesai.

Fitur Profile:
1. User bisa edit profil dan data dirinya (belum tersedia ubah email) di menu profil.
2. User bisa renewal subscriptionnya dari profil.
3. Jika user adalah member sekaligus trainer, bisa menggunakan fitur switch profile di dalam menu profile. Dan bisa berganti workspace.
```

Fitur workoutplan-notification
```
Fitur Workout Plan:
1. User menambahkan daftar latihan
2. Daftar latihan akan masuk ke dalam jadwal latihan
3. Setelah menambahkan jadwal latihan, maka reminder latihan tersebut akan masuk ke notifikasi member
4. Jika user salah mengisi data latihan maka di bagian jadwal latihan data nya bisa di edit
5. Jika user ingin menghapus latihan tersebut maka, user bisa menghapus data yang ada di jadwal latihan. Ada hapus untuk jadwal latihan tertentu dan ada hapus untuk menghapus seluruh data latihan
6. Jika user ingin melakukan latihan mandiri (tidak di lokasi gym), dan tidak tahu langkah untuk melakukan latihan tersebut, maka bisa memutar video tutorial yang di sediakan.

Fitur notifikasi:
1. Halaman Member: Setiap ada informasi langsung seperti (menambahkan jadwal latihan, meal plan, sewa trainer, dan konfirmasi baik dari trainer dan admin), maka akan masuk ke log informasi. Dan reminder jadwal latihan akan di ingatkan lewat notifikasi
2. Halaman Trainer: Setiap ada informasi langsung seperti (pembayaran sewa masuk, jadwal kelas, dan trainer di sewa), maka akan masuk ke log informasi
3. Halaman Admin: Setiap ada informasi langsung (orang yang mendaftarkan akun sebagai member, pembayaran member, pengajuan daftar trainer, jumlah akun yang aktif pada hari itu), maka akan masuk ke log informasi
```

Fitur trainer-admin-monitoring
```
a. Fitur 1:
- Member harus sudah melakukan booking trainer.
- Di workstation trainer, terdapat halaman Members yang trainer bisa melihat data-data dari Member yang membooking nya. Data yang dimaksud adalah: Workout Plans, Nutrition & Meals, dan Progress dari Latihan yang sudah dilakukan selama ini.
- Trainer bisa memilih salah satu member untuk melihat datanya, jika memang trainer tersebut memiliki banyak bookingan dari berbagai member.
- Terdapat ringkasan total members, Active Plans, Completed Logs, dan Meal Plans.

b. Fitur 2:
- Di workstation trainer, terdapat halaman Trainer Reports, yang berfungsi sebagai melacak riwayat komisi, status pencairan, dan catatan transaksi klien.
- Terdapat ringkasan Total transaksi, pendapatan, pencairan yang tertunda, dan yang dicairkan.
- Trainer bisa menfilter Status Pencairan, serta tanggal mulai dan akhir yang mempermudah melacak dalam kurung Waktu tertentu yang di inginkan trainer.

c. Fitur 3:
- Calon member mengisi semua form registrasi dan melakukan pembayaran.
- Di workstation admin, teradapat halaman Payments Review, yang berfungsi sebagai tempat konfirmasi semua pembayaran apakah pembayaran berhasil atau tidak.
- Di workstation admin, teradapat halaman Payments, yang berfungsi sebagai pelacak semua pembayaran yang dilakukan member. Baik itu daftar menjadi member, atau perpanjangan membership.

d. Fitur 4:
- Di dashboard member, terdapat tombol untuk Check-in dan Check-out. Check-in dan Check-out akan tercatat oleh admin.
- Di workstation admin, terdapat halaman Check-in logs & Reports, yang berfungsi sebagai tempat pemantauan log operasional gym, pembayaran, riwayat kehadiran, dan metrik bisnis Utama.
- Admin dapat mengecetak laporan bulanan dalam bentuk format .xlsx
- Admin dapat mencari member, email, maupun invoice dengan pencarian yang tersedia. Serta menfilter Classification, Payment Status, serta kurung Waktu tertentu yang di inginkan.
- Terdapat ringkasan Total pendapatan, total transaksi, Rasio kehadiran, Total Checkin, dan yang datang hari ini.
```

Fitur trainer-booking-chat-payment
```
Hire Trainer - Halaman untuk mencari dan memilih trainer berdasarkan spesialisasi, lalu membuat booking dengan mengisi start date, sesi per minggu, hari sesi, dan jam sesi. Booking dimulai dari status pending, setelah upload bukti bayar jadi pending_payment, lalu admin konfirmasi jadi confirmed - setelah itu chat baru bisa digunakan.

Schedule - Menampilkan semua jadwal sesi booking yang sudah dikonfirmasi. Sesi di-generate otomatis oleh sistem berdasarkan data booking (hari, jam, tanggal mulai-akhir). Dari sini user bisa melihat jadwal latihan harian mereka lengkap dengan informasi trainer.

Chat - Fitur pesan real-time antara member dan trainer yang hanya aktif setelah booking dikonfirmasi. Menggunakan koneksi WebSocket (Socket.io) dengan fallback polling, mendukung lampiran file (gambar/PDF), dan mengirim notifikasi otomatis ke penerima saat ada pesan baru.

Payments - Riwayat pembayaran yang menampilkan invoice, status, jumlah, dan metode. Setiap booking menghasilkan tagihan total_member_price (perhitungan berdasarkan base_price trainer x sesi per minggu x 4 minggu). Admin bisa mengelola konfirmasi atau penolakan pembayaran dari halaman ini.

Penghasilan (Rent History) - Halaman yang menampilkan total penghasilan trainer, rincian disbursed (sudah dicairkan), pending (belum dicairkan), serta breakdown income dari mentoring dan sesi. Data bersumber dari tabel trainer_earnings yang terisi otomatis saat admin mengkonfirmasi pembayaran booking.
```

Fitur mealplan-nutritioncalculator-nutritionmonitoring
```
FITUR MEALPLAN DAN KALKULATOR GIZI
1. Hitung daily limit dengan menginput data diri (TB, BB, Umur, dll)
2. Jadikan hasil kalkulasi daily limit menjadi set daily limit hari ini
3. User dapat mengetahui kalori limit  hari ini dari set daily limit
4. User bisa menginput makanan apa saja yang dikonsumsi dan menyertakan kalori dari makanan tersebut
5. Set daily limit otomatis akan menghitung limit kalori hari ini dengan hasil input makanan yang telah dikonsumsi
6. User tidak akan bisa menginput makanan yang dikonsumsi hari ini sebelum set daily limit (No 2)

Fitur Monitoring Nutrisi
1. Progress nutrisi user bisa di perhatikan oleh user sendiri maupun oleh trainernya dengan syarat user sudah booking trainer tersebut.
```
