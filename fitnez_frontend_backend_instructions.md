# FitNez Development Guide: Frontend & Backend Implementation

Dokumen panduan teknis ini disusun untuk memberikan instruksi kerja yang jelas dalam memfinalisasi aplikasi FitNez. Implementasi harus dibagi secara terstruktur ke dalam lapisan Frontend, Backend, dan Database.

---

## 📌 Ketentuan Umum & Wajib
1. **Konsistensi Bahasa:** Seluruh teks pada antarmuka pengguna (UI), notifikasi, pesan kesalahan, nama kolom database, hingga data dummy wajib menggunakan **Bahasa Inggris**.
2. **Design Pattern:** Semua rekomendasi kode dari GitHub Copilot atau kecerdasan buatan (AI) harus direfaktorkan agar sesuai dengan *design pattern* kelompok (misalnya: *Repository Pattern* atau *Service Layer*).

---

## 🖥️ 1. PANDUAN IMPLEMENTASI FRONTEND

### A. Komponen Cookie Consent Pop-Up
* **Desain & Posisi:** Buat komponen pop-up sederhana yang muncul di sudut kanan bawah layar (`bottom-right`), menyerupai notifikasi melayang agar tidak menghalangi konten utama.
* **Branding:** **TIDAK BOLEH** menampilkan Logo atau Brand dari "FitNez" di dalam pop-up cookie ini.
* **Fungsionalitas:** Sediakan tombol **"Accept all cookies"**. Terapkan state management atau penyimpanan lokal (`localStorage`) agar pop-up ini hilang sepenuhnya dan tidak muncul kembali setelah tombol ditekan.

### B. Pembersihan UI & Komponen Halaman
* **Dashboard Member:** Hapus seluruh views, komponen, dan konfigurasi routing terkait Dashboard Member.
* **Dashboard Trainer:** Cari komponen halaman dashboard trainer dan **hapus button "Refresh"** dari layout tersebut.
* **Landing Page:** Buat tampilan landing page informatif untuk calon pengguna online yang menjelaskan fitur-fitur aplikasi serta tahapan melangkah/mendaftar di FitNez.
* **Payment Success Screen:** Pastikan rute setelah transaksi berhasil mengarah ke tampilan khusus yang mengonfirmasi kesuksesan pembayaran.

### C. Optimalisasi Performa & Real-Time (Lighthouse Tuning)
* **Code Splitting & Lazy Loading:** Terapkan pemisahan kode dinamis dan pemuatan malas (lazy loading) pada routing komponen framework untuk mempercepat loading awal demi mencapai skor **Google Lighthouse maksimal**.
* **Library Optimization:** Lakukan audit komponen dan minimalisasi ukuran library pihak ketiga yang digunakan.
* **Socket.io Client:** Integrasikan `socket.io-client` untuk menangani interaksi langsung dua arah (Live Chat dan pembaruan notifikasi pembayaran real-time).
* **Server-Sent Events (SSE) Listener:** Gunakan objek `EventSource` bawaan browser untuk menangkap stream data dari server dan menampilkan progress bar pelacakan langsung (*live-tracking*) pengolahan data besar.

### D. Visualisasi Data Pengunjung
* **Tampilan Admin:** Integrasikan library chart (seperti Chart.js atau ApexCharts) untuk menampilkan grafik tren pengunjung berdasarkan waktu dan lokasi (Negara & Kota).
* **Tampilan Non-Admin:** Sediakan komponen tabel data pengunjung biasa yang dilengkapi dengan tombol aksi untuk mengunduh data tersebut dalam format **CSV**.

---

## ⚙️ 2. PANDUAN IMPLEMENTASI BACKEND

### A. Keamanan, CORS, dan Autentikasi
* **Penerapan CORS Terbatas:** Konfigurasikan aturan CORS pada server untuk membatasi hak akses ke API inti jika pengguna belum melakukan login/autentikasi melalui landing page.
* **CORS & Tokenisasi:** Gabungkan kebijakan CORS dengan sistem token (seperti JWT atau Laravel Sanctum) untuk memastikan setiap pertukaran data pada aplikasi web aman dan terverifikasi.

### B. Proteksi Direktori Server
* **Virtual Directory & Path Aliasing:** Konfigurasikan mekanisme *URL masking* atau *path aliasing* pada level server. Tujuannya adalah menyembunyikan struktur folder penyimpanan file asli di server agar berkas penting/sensitif tidak terekspos secara bebas melalui URL langsung.

### C. Arsitektur Service API
* **Pemisahan API Endpoint:** Rancang API service untuk operasi CRUD secara terpisah antara kebutuhan Aplikasi Web dan Aplikasi Mobile. **DILARANG** melakukan *override* metode; pastikan kedua jalur distribusi data ini terisolasi dengan baik di dalam kode pengontrol (Controller).

### D. Infrastruktur Real-Time & Geolocation
* **Socket.io Server Engine:** Jalankan server Socket.io untuk memanajemeni koneksi komunikasi dua arah secara langsung (live chat dan trigger instan status pembayaran).
* **SSE Stream Endpoint:** Sediakan endpoint khusus dengan header `text/event-stream` untuk mengalirkan status progres pemrosesan data bervolume besar secara berkala (real-time stream).
* **Fungsi Lokasi Terakhir:** Integrasikan modul deteksi IP (misalnya GeoIP) untuk mengekstrak koordinat nama **Negara** dan **Kota** dari akses terakhir pengguna.

### E. Optimalisasi Query Database
Refaktorkan query standar database Anda menjadi struktur query tingkat lanjut yang dioptimalkan melalui:
* **Subquery:** Digunakan untuk melakukan penyaringan (filter) data yang berlapis.
* **Window Function:** Digunakan untuk keperluan pemeringkatan data (misalnya: `ROW_NUMBER()`, `RANK()`).
* **Common Table Expression (CTE):** Digunakan untuk mengisolasi proses perhitungan awal data mentah sebelum digabungkan ke query final.

---

## 🗄️ 3. PANDUAN SEEDING DATABASE

Buat berkas seeder database untuk menghasilkan data dummy murni dengan rincian persis sebagai berikut:
1. **60 Data Member Murni:** Akun dengan peran member standar tanpa riwayat pendaftaran lain.
2. **20 Data Trainer Aktif:** Akun trainer yang statusnya sudah valid dan aktif beroperasi.
3. **20 Data Akun Member Siap Jadi Trainer:** Akun member khusus yang sudah memiliki data lampiran dummy berupa berkas Curriculum Vitae (CV) dan Sertifikat pendukung di dalam skema database, sehingga tinggal menunggu verifikasi untuk migrasi peran.
