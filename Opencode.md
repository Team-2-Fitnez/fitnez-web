# Proyek: [Fitnez]
**Role:** Anda adalah AI Agent OpenCode yang bertindak sebagai Senior Full-Stack Developer yang ahli dalam dunia Programming.


**Who gives the command**: Muhammad Iqbal Rizanta (Anggota 4)
---

## 1. Ringkasan Proyek (Context & Tech Stack)
**Deskripsi:** 

Latar Belakang:

  Sistem ini dibuat untuk membantu dalam pengelolaan aktivitas gym yang masih banyak dilakukan secara manual, seperti pencatatan absensi, penyusunan workout plan, dan laporan pembayaran member, sehingga kurang kurang efisien dan sulit dipantau. Oleh karena itu, perlu dikembangkan Sistem Manajemen Fitness berbasis web dan mobile sebagai platform terpusat untuk member, admin, dan trainer. Aplikasi ini menyediakan login terpisah, workout plan, tracking gizi & meal plan, reminder latihan, absensi, laporan pembayaran, serta fitur pendukung untuk meningkatkan konsistensi dan motivasi latihan pengguna.


**Functional Requirements (Kebutuhan Fungsional)**

  Sistem harus mampu melakukan fungsi-fungsi berikut:

  FR-01: Sistem menyediakan login/ daftar member yang terpisah dengan admin dan trainer

  FR-02: Sistem menyediakan tampilan profil pengguna/ member/ admin/ trainer

  FR-03: Admin bisa melihat laporan siapa saja yang melakukan login/ daftar

  FR-04: Sistem menyediakan fitur pembuatan workout plan disertai tracking latihan dan dengan tutorial gerakan fitness (foto/ video dengan penjelasan)

  FR-05:Sistem menyediakan Kalkulator gizi dan meal plan yang sesuai dengan workout plan.

  FR-06: Sistem menyediakan notifikasi

  FR-07: Sistem menyediakan sewa personal trainer yang bisa pengguna pilih

  FR-08: Sistem pendaftaran Trainer bekerja dengan mewajibkan member yang ingin menjadi trainer mengunggah dokumen pengalaman melalui laman “Jadi Trainer”, yang kemudian diverifikasi admin secara manual dalam waktu maksimal 2×24 jam.

  FR-09: Admin dapat memantau workout plan, kalkulator gizi, dan meal plan pengguna. Serta tidak berhak mengubah data (workout plan, kalkulator gizi, meal plan) yang telah di input oleh pengguna.

  FR-10: Sistem pencatatan laporan absen, transaksi pembayaran member, dan transaksi sewa personal trainer.

  FR-11: Sistem pencatatan laporan absen trainer, transaksi pembayaran member, dan penghasilan mentoring.

  FR-12: Trainer memiliki halaman untuk memantau workout plan, tracking latihan, kalkulator gizi, dan meal plan pengguna. Serta tidak berhak mengubah data (workout plan, kalkulator gizi, meal plan) yang telah di input oleh pengguna.

  FR-13: Trainer memiliki halaman untuk laporan riwayat uang sewa yang masuk.

  FR-14: Trainer memiliki halaman notifikasi pengingat jadwal latihan dan pembayaran masuk.

  FR-15: Admin bisa melihat semua laporan pembayaran member & absen kehadiran.

  FR-16: Sistem menyediakan FAQ tentang jadwal, harga sewa trainer, pengaduan, informasi aplikasi.

  FR-17:   Sistem menyediakan fitur chat untuk Member dan Trainer.



**Non-Fungsional Requirement (Kebutuhan Non-Fungsional)**

Kualitas sistem didefinisikan sebagai berikut:

Usability: Aplikasi dirancang dengan tampilan yang sederhana dan intuitif sehingga mudah dipahami oleh pengguna yang baru

Performance: Sistem memiliki performa yang baik dengan loading 3 - 5 detik

Security: Data pengguna dilindungi melalui mekanisme autentikasi ganda

Portability: Aplikasi dapat digunakan dalam platform web atau perangkat mobile

Scalability: Sistem mampu menyesuaikan diri terhadap peningkatan jumlah pengguna tanpa mengurangi performa sistem

Maintainability: Sistem memiliki struktur yang terorganisir sehingga memudahkan proses pemeliharaan maupun pengembangan fitur lanjutan.

**Fitur & Pembagian Tugas (Integrasi Web & Mobile)**

Anggota (Nama Anggota)

Deskripsi Fitur 1 (Mobile & Web) (Angka)

Deskripsi Fitur 2 (Mobile & Web) (Angka)

Deskripsi Integrasi (Integrasi)

Fernando Seroy
1. Fitur Menu login/daftar member yang terpisah dengan admin dan trainer
2. Tampilan profil pengguna/ member/ admin/ trainer
3. FAQ tentang jadwal, harga sewa trainer, pengaduan, informasi aplikasi.

Integrasi: Fitur login/daftar meng autentikasi pengguna berdasarkan peran sebagai member, trainer, atau admin, lalu menampilkan profil pengguna sesuai data yang diinput saat pendaftaran. Sistem juga menyediakan FAQ berisi informasi jadwal, harga sewa trainer, pengaduan, dan informasi aplikasi yang dapat diakses oleh seluruh pengguna.


Nadiya Putri Intan Nur Ramadhani:
1. Fitur pembuatan workout plan disertai tracking latihan yang dilengkapi dengan tutorial gerakan fitness (foto/ video dengan penjelasan)
2. Fitur Notifikasi

Integrasi: Fitur pembuatan workout plan memungkinkan pengguna menyusun jadwal latihan yang dilengkapi tracking latihan dan tutorial gerakan fitness berupa foto atau video beserta penjelasan. Sistem kemudian mengirimkan notifikasi pengingat latihan secara otomatis berdasarkan jadwal yang telah ditentukan pada workout plan tersebut.

Luthfion Hamit Ailan:
1. Halaman trainer untuk memantau workout plan, tracking latihan, kalkulator gizi, dan meal plan pengguna
2. Halaman Trainer untuk laporan riwayat uang sewa yang masuk.
3. Halaman admin untuk melihat laporan siapa saja yang melakukan login/ daftar
4. Halaman admin untuk melihat  semua laporan pembayaran member & absen kehadiran.

Integrasi: Halaman trainer memungkinkan trainer memantau workout plan, tracking latihan, kalkulator gizi, dan meal plan pengguna serta melihat riwayat uang sewa yang masuk. Sementara itu, halaman admin menyediakan akses untuk melihat laporan login/daftar pengguna serta seluruh laporan pembayaran member dan absen kehadiran secara terpusat.

Muhammad Iqbal Rizanta:
1. Fitur sewa personal trainer yang bisa pengguna pilih.
2. Halaman pendaftaran menjadi trainner.
3. Halaman notifikasi untuk trainner sebagai pengingat jadwal latihan dan pembayaran masuk.
4. Fitur chat untuk Member dan Trainer yang berfungsi untuk konsultasi dan komunikasi antar keduanya.

Integrasi: Fitur sewa personal trainer memungkinkan pengguna memilih trainer yang telah terdaftar melalui halaman pendaftaran trainer, sehingga sistem menghubungkan member dan trainer secara langsung. Setelah penyewaan berhasil, sistem menyediakan fitur chat untuk konsultasi serta mengirimkan notifikasi kepada trainer sebagai pengingat jadwal latihan dan informasi pembayaran yang masuk.


Eva Amilia:
1. Monitoring Admin yaitu fitur pemantauan admin terhadap  kalkulator gizi, dan meal plan pengguna.
2. Fitur kalkulator gizi dan meal plan yang sesuai dengan workout plan.

Integrasi: Fitur kalkulator gizi dan meal plan menyesuaikan rekomendasi asupan nutrisi berdasarkan workout plan yang dibuat oleh pengguna. Sistem Monitoring Admin memungkinkan admin memantau workout plan, kalkulator gizi, dan meal plan pengguna secara read-only tanpa mengubah data yang telah diinput.

Faysal Abdullahi Yusuf:
1. Fitur pencatatan laporan absen, transaksi pembayaran member, dan transaksi sewa personal trainer.
2. Fitur pencatatan laporan absen trainer, transaksi pembayaran member, dan penghasilan mentoring.

Integrasi: Fitur pencatatan laporan absen dan transaksi pembayaran member terintegrasi dengan transaksi sewa personal trainer untuk merekam aktivitas layanan yang berlangsung. Sistem juga mencatat absen trainer serta menghitung penghasilan mentoring berdasarkan transaksi pembayaran yang diterima.


**Tech Stack:**

Backend & API: Laravel - PHP

Web Frontend: VueJS - Javascript

Mobile App: Android Studio-Kotlin

Database: PostgreSQL

Software Architecture: Presentation layer(Client)-Communication Layer (API)-Logic Layer (Server)-Data Layer (Database)

Web Server: Nginx

## Penugasan dari Dosen ##

Daftar Penugasan Mandiri dan Kelompok

**Mandiri**

**Pertemuan 1 - 3: (Done)**

Mahasiswa mencari studi kasus yang disesuaikan dengan kemampuan kelompok

Mahasiswa menentukan fungsional dan non fungsional

Mahasiswa menentukan spesifikasi aplikasi mobile dan web yang dikembangkan dan teknologi pendukung proyek (library & framework)

Mahasiswa mengisi template software requirement specification (SRS)

Mahasiswa memahami konsep dasar pemrograman web dan mobile untuk pengembangan aplikasi web dan mobile

Mahasiswa memahami manajemen pengerjaan dan penyimpanan proyek ke github dengan akses privasi

**Pertemuan 4: (Done)**

Mahasiswa mengembangkan aplikasi web berbasis single page application (SPA)

Mahasiswa menerapkan penamaan proyek dengan nama panggil anggota dan struktur sederhana dalam folder yaitu index.html, style.css dan script.js

Mahasiswa menerapkan standarisasi HTML minimal 300 baris, CSS maksimal 100 baris dan vanilla javascript maksimal 300 baris, tanpa penjelasan koding pada setiap baris kode

Mahasiswa menerapkan konsep modular pada semua fungsi di javascript; semua komponen html dan css

Mahasiswa menerapkan penyimpanan dengan local storage ke browser

**Pertemuan 5: (Done)**

Mahasiswa menerapkan aplikasi web ke aplikasi mobile berbasis single activity application (SAA) yang di dalam folder diberikan penamaan seperti web. Tetapi untuk android harus diberi nama seperti domain web yaitu id.tokonya, kemudian di dalam terdapat home_activity.kt dan home_layout.xml. Setelah itu, bagian yang didefinisikan otomatis oleh android studio.

Mahasiswa mengerjakan koding dengan kualitas bukan kuantitas yaitu tidak terpengaruhi oleh jumlah.

Mahasiswa menerapkan penyimpan data di aplikasi mobile dengan sharedpreferences dan database skala kecil dengan sqlite.

Mahasiswa melanjutkan pengembangan aplikasi web dari sederhana ke sedang yaitu single page application (SPA).

Mahasiswa menerapkan penyimpanan aplikasi web dari skala kecil ke besar yaitu variable array, json & csv, cookies, local storage, indexedDB, postgresql, dan vector database dengan CRUD sederhana (kecuali postgresql dan vector database opsional).

**Pertemuan 6 - 7: (Done)**

Mahasiswa menerapkan koneksi ke server dengan penerapan authentication sederhana yaitu session, cookies dan verifikasi akun dan sandi dengan salt dan tokenisasi.

Mahasiswa menerapkan kedua fitur pada aplikasi web dan mobile. Pada aplikasi web wajib terkoneksi dengan server dari login ke kedua fitur.

Mahasiswa mengembangkan SPA dan SAA ke Multi-Page Application dan Multi-Activities Application (kombinasi Activity & Frame) yang disesuaikan dengan fitur.

Mahasiswa menerapkan penyimpanan yang disinkronisasikan dari server ke client dengan response dan request sederhana (PUT, POST, GET dan DELETE) dengan protocol HTTP dan HTTPS.

Mahasiswa menerapkan koneksi lanjutan dengan websocket, polling dan server sent event pada aplikasi web.

Mahasiswa menerapkan kustomisasi struktur proyek mandiri

Mahasiswa memperbarui semua koding ke github dan memperbaiki semua koding dalam branch develop dan melakukan sinkronisasi ke branch main.

**Pertemuan 8: (Done)**

Mahasiswa harus menyelesaikan semua tugas dari pertemuan ke 4 - 7 untuk bisa mengikuti UTS, kelompok yang anggotanya belum lengkap tugasnya tidak diperkenankan untuk UTS.

Mahasiswa mendemonstrasikan aplikasi web dan mobile mandiri satu persatu bersama kelompok.

Mahasiswa sudah mempresentasikan bisa mengikuti ujian teknis (ujian tulis dan mock interview coding) untuk menguji dan mengevaluasi konsep dasar dan teknis singkat.

# Kelompok #

**Pertemuan 9: (Butuh Validasi)**

Mahasiswa memfokuskan transisi dari Vanilla ke Framework dan persiapan integrasi API yang lebih kompleks.

Mahasiswa melakukan migrasi dari Vanilla JS ke Framework (Rekomendasi: React.js atau Vue.js) dan menerapkan Component-based Architecture pada aplikasi web.

Mahasiswa melakukan Migrasi ke pustaka UI modern (Rekomendasi: Jetpack Compose) untuk mempercepat pengembangan UI deklaratif di Android pada aplikasi mobile.

Mahasiswa menerapkan HTML & CSS: Mengganti CSS murni dengan minimalis framework/library seperti Tailwind CSS atau Pico.css untuk efisiensi styling.

Mahasiswa menyelaraskan struktur model data antara Web Framework dan Mobile App agar siap mengonsumsi API yang sama sebagai penerapan integrasi web dan mobile.

P**ertemuan 10 - 11: (Butuh Validasi)**

Mahasiswa memfokuskan sinkronisasi data yang kompleks dan pengelolaan status aplikasi secara global.

Mahasiswa menerapkan Global State Management (seperti Redux Toolkit, Zustand, atau Pinia) dan mengintegrasikan library form handling (seperti React Hook Form) untuk validasi data input pada aplikasi web.

Mahasiswa menerapkan arsitektur MVVM (Model-View-ViewModel) dan State di Android menggunakan ViewModel dan LiveData/StateFlow pada aplikasi mobile.

Mahasiswa mengembangkan data dengan konsep CRUD dari skala sedang ke besar dan menerapkan ORM (Object-Relational Mapping) seperti Prisma atau Drizzle untuk interaksi ke PostgreSQL di sisi server.

Mahasiswa mengimplementasikan Skeleton Loading dan Error Handling yang seragam antara Web dan Mobile saat melakukan request data.

**Pertemuan 12 - 13: (Butuh Validasi)**

Mahasiswa menerapkan fitur interaktif lanjutan dan pengelolaan file multimedia (Upload Excel/Gambar).

Mahasiswa mengimplementasikan library Socket.io atau pustaka TanStack Query untuk sinkronisasi data real-time dan server-state caching pada aplikasi web.

Mahasiswa mengimplementasikan library networking (seperti Retrofit atau Ktor Client) untuk konsumsi API yang lebih stabil dan terstruktur pada aplikasi mobile.

Mahasiswa menerapkan fitur khusus atau sub fitur sebagai contoh fitur Upload dan Processing file (Excel/CSV) yang sebelumnya diuji di Vanilla, kini menggunakan library khusus (seperti ExcelJS atau SheetJS) di dalam framework.

Mahasiswa melakukan sinkronisasi notifikasi antara Web (Web Push API) dan Mobile (Firebase Cloud Messaging - FCM) untuk fitur pemberitahuan status karir/pembayaran atau disesuaikan dengan fitur masing - masing kelompok.

**Pertemuan 14 - 15: (Belum)**

Mahasiswa mengimplementasikan fitur interaksi langsung dan melakukan optimalisasi performa akhir.

Mahasiswa mengimplementasikan Websocket dengan menggunakan library Socket.io untuk fitur yang membutuhkan komunikasi dua arah seperti live chat, notifikasi pembayaran real-time atau kondisi lainnya.

Mahasiswa mengimplementasikan Server Sent Events (SSE) sebagai berfungsi untuk live-tracking progres pengolahan data besar di server.

Mahasiswa menghitung Performance Tuning dengan melakukan code splitting dan lazy loading pada framework untuk mencapai skor Lighthouse maksimal.

Mahasiswa menerapkan sinkronisasi GitHub antara branch develop dan main tanpa konflik dengan laptop anggota lain.

**Pertemuan 16: (Belum)**

Mahasiswa melakukan evaluasi total terhadap integrasi aplikasi Web dan Mobile.

Mahasiswa melakukan demonstrasi Akhir oleh Kelompok mempresentasikan aplikasi yang sudah menggunakan Framework, terkoneksi database Cloud (PostgreSQL), dan memiliki fitur pembayaran sukses.

Mahasiswa melakukan Uji Teknis (Ujian Tulis dan Mock Interview Coding)yang terdiri dari evaluasi terhadap kecepatan akses (Latency), keamanan token (JWT), dan sinkronisasi data client-server.

Mahasiswa mengumpulkan dokumen SRS yang sudah diperbarui sesuai dengan library dan framework yang digunakan.

## Sesuaikan Pembuatan nama File ##

1. Anggota 1:  Login dan Autentikasi ada pada modul verifyOtp (dan requestOtp) di file AuthViewModel.kt yang juga terhubung dengan AuthFacade.kt, dan Profil ada pada modul currentUser di file AuthFacade.kt

2. Anggota 2: Workout Plan ada pada modul MemberWorkoutPlanScreen di file MemberWorkoutPlan.kt dan Notifikasi (Member, Trainer, Admin) ada pada modul SystemNotificationManager di file SystemNotificationManager.kt

3. Anggota 3: Monitoring dari Trainer ada pada modul getTrainerMembers di file MemberFitnessMonitoringViewModel.kt (dengan tampilan di TrainerMonitoringScreen.kt), dan Laporan Sewa Trainer Masuk ada pada modul getIncomingRentSummary di file IncomingRentHistoryViewModel.kt

4. Anggota 4: Sewa Trainer dan Booking Trainer ada pada modul createBooking di file BookingViewModel.kt, Pendaftaran Trainer ada pada modul submitTrainerApplication di file TrainerApplicationViewModel.kt, dan Chat dengan Member ada pada modul sendMessage (serta observeChatMessages) di file ChatViewModel.kt

5. Anggota 5: Monitoring Admin ada pada modul getAdminMonitoringData di file MealPlanViewModel.kt (dengan antarmuka pada AdminMonitoringScreen.kt), dan Kalkulator Gizi dan Meal Plan ada pada modul saveMealPlan dan fungsi calculateNutrition difile MealPlanViewModel.kt

6. Anggota 6: Laporan Absen dan Pencatatan Absen Trainer ada pada modul recordAttendance serta getAttendanceReport di file PaymentAttendanceReportViewModel.kt, Transaksi Pembayaran dan Transaksi Pembayaran Member ada pada modul getMemberPayments di file PaymentAttendanceReportViewModel.kt, Penghasilan Mentoring dan Sewa Personal Trainer ada pada modul getIncomeSummary di file PaymentAttendanceReportViewModel.kt

## Refrensi Penerapan Design Pattern ##

The Catalog of Design Patterns

Creational patterns:
These patterns provide various object creation mechanisms, which increase flexibility and reuse of existing code.

Structural patterns:
These patterns explain how to assemble objects and classes into larger structures while keeping these structures flexible and efficient.

Behavioral patterns
These patterns are concerned with algorithms and the assignment of responsibilities between objects.

- Factory Method
- Abstract Factory
- Builder
- Prototype
- Singleton
- Adapter
- Bridge
- Chain of Responsibility
- Command
- Iterator
- Mediator
- Composite
- Decorator
- Memento
- Observer
- State
- Strategy
- Facade
- Flyweigth
- Template Method
- Visitor
- Proxy

**Saran Terbaru dari dosen saya**

salah satu contoh penerapan factory method untuk api service https://github.com/rednafi/flask-factory dengan flask

minimal setiap fitur yang dikerjakan kelompok seperti referensi ini

abstract factory bagian dasar dari semua framework, sehingga mas dan mbak harus extend dari framework seperti php (laravel) dan python (django), sedangkan javascript bisa menerapkan factory pattern karena terdiri dari beberapa fungsi (functional) sedangkan python bisa menerapkan abstract factory dan factory method seperti flask

Saat search di web muncul directory di search browser

## Noted ##

Jangan Mengubah UI dan Logic saat melakukan pengerjaan fitur dan jika ada tambahan Menu pastikan UI tetap sama.

Jika kamu menemukan di internet Library Algoritma fitur tertentu segera terapkan algoritma tersebut pada fitur itu tetapi hanya 60% penggunaan library pada project ini dan PASTIKAN KAMU MENCARI PADA SITUS RESMI DAN TERVALIDASI.