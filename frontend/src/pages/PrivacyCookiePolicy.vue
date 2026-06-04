<template>
  <main class="privacy-page">
    <h1>Kebijakan Privasi & Cookie FitNez</h1>

    <section>
      <h2>Apa itu cookie?</h2>
      <p>
        Cookie adalah file kecil yang disimpan di browser pengguna untuk menjalankan
        fitur website, mengingat preferensi, menganalisis performa, atau mendukung
        pemasaran.
      </p>
    </section>

    <section>
      <h2>Kategori Cookie</h2>

      <table>
        <thead>
          <tr>
            <th>Kategori</th>
            <th>Tujuan</th>
            <th>Status</th>
            <th>Contoh Durasi</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Esensial / Sangat Diperlukan</td>
            <td>Login, session, CSRF, keamanan, navigasi dasar.</td>
            <td>Selalu aktif</td>
            <td>Session sampai 12 bulan</td>
          </tr>
          <tr>
            <td>Analitik / Statistik</td>
            <td>Mengukur performa halaman dan memahami penggunaan website.</td>
            <td>Opsional</td>
            <td>1–24 bulan</td>
          </tr>
          <tr>
            <td>Pemasaran / Iklan</td>
            <td>Mengukur efektivitas promosi dan iklan.</td>
            <td>Opsional</td>
            <td>1–24 bulan</td>
          </tr>
          <tr>
            <td>Preferensi / Fungsional</td>
            <td>Menyimpan pilihan bahasa, tema, filter, atau preferensi tampilan.</td>
            <td>Opsional</td>
            <td>1–12 bulan</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section>
      <h2>Pihak Ketiga</h2>
      <p>
        Jika FitNez memakai layanan pihak ketiga seperti Google Analytics, Meta Pixel,
        payment gateway, atau layanan chat, daftar nama penyedia, tujuan penggunaan,
        dan durasi cookie harus dicantumkan di halaman ini.
      </p>
    </section>

    <section>
      <h2>Pencabutan Izin</h2>
      <p>
        Pengguna dapat mengubah atau mencabut persetujuan kapan saja melalui tautan
        <strong>Pengaturan Cookie</strong> di footer website.
      </p>
    </section>

    <section>
      <h2>Keamanan Pengiriman Data</h2>
      <p>
        Penyimpanan dan pengiriman data cookie wajib menggunakan HTTPS di environment
        production.
      </p>
    </section>
  </main>
</template>

<style scoped>
.privacy-page {
  max-width: 980px;
  margin: 0 auto;
  padding: 48px 24px;
  color: #141414;
}

.privacy-page h1,
.privacy-page h2 {
  color: #141414;
}

.privacy-page p {
  line-height: 1.75;
}

.privacy-page table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 16px;
  background: #fff4df;
}

.privacy-page th,
.privacy-page td {
  padding: 14px;
  border: 1px solid rgba(20, 20, 20, 0.18);
  text-align: left;
  vertical-align: top;
}

.privacy-page th {
  background: #f6a21a;
  color: #141414;
}
</style>
