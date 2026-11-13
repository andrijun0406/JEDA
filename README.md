# 🌟 JEDA (Smart Parenting & Financial Literacy Monitor)

**JEDA** adalah aplikasi web interaktif berbasis PHP dan MySQL yang dirancang khusus untuk membantu anak dan orang tua dalam memantau literasi keuangan, manajemen pengeluaran harian, target tabungan (*saving goals*), serta prediksi kebutuhan konsumsi rutin berbasis AI (*Recurring Baseline Analysis*).

---

## 🚀 Fitur Utama

1. **Dashboard Siswa & Analitik Bulanan**
   * Pemantauan total pengeluaran harian dan bulanan secara *real-time*.
   * Filter periode bulanan interaktif (melihat riwayat transaksi hingga beberapa bulan ke belakang).
   * Grafik visualisasi proporsi pengeluaran menggunakan **Chart.js (Donut Chart)**[cite: 1].
2. **AI Recurring Baseline & Restock Prediction**
   * Sistem cerdas yang mendeteksi pola konsumsi berulang (seperti sabun, pasta gigi, atau paket data) berdasarkan siklus 30 hari[cite: 1].
   * Estimasi tanggal barang habis dan pengingat *restock* otomatis bagi siswa[cite: 1].
3. **Manajemen Target Tabungan (Saving Goals)**
   * Membantu siswa menetapkan target finansial (misal: membeli buku, sepatu, dll.) lengkap dengan progress bar dan status pencapaian (`ACTIVE` / `COMPLETED`)[cite: 1].
4. **Leaderboard & Gamifikasi**
   * Sistem peringkat antar siswa untuk memotivasi pengelolaan keuangan yang lebih bijak dan disiplin menabung.
5. **Profil & Kartu Pintar (Virtual Wallet)**
   * Integrasi saldo nyata, batas pengeluaran harian (*daily hard limit*), serta rekam jejak streak menabung[cite: 1].

---

## 🛠️ Teknologi yang Digunakan

* **Backend:** PHP (Native / OOP-style prepare statements dengan MySQLi)
* **Frontend:** Tailwind CSS (Styling modern & responsif ala mobile app)
* **Database:** MySQL / MariaDB (Standar Encoding: `utf8mb4_unicode_ci`)
* **Library Grafik:** Chart.js[cite: 1]
* **Server Environment:** Laragon (Local Development) & Hostinger (Production Deployment)

---

## 📂 Struktur Database & Data Dummy

Aplikasi ini menggunakan beberapa tabel utama yang saling berelasi:
* **`parents`**: Menyimpan data akun orang tua pembimbing[cite: 1].
* **`users`**: Menyimpan data akun siswa (terhubung ke `parents` melalui `parent_id`)[cite: 1].
* **`dummy_transactions`**: Mencatat riwayat transaksi keuangan harian siswa (kategori, nominal, dan waktu transaksi)[cite: 1].
* **`item_baselines`**: Data referensi siklus barang rutin (misal: sabun muka, pasta gigi, buku tulis)[cite: 1].
* **`saving_goals`**: Daftar target tabungan dan jumlah yang telah terkumpul[cite: 1].
* **`virtual_wallets`**: Pengelolaan saldo, batas limit, dan streak harian siswa[cite: 1].

---

## 💻 Panduan Instalasi & Menjalankan Lokal

1. **Clone Repository** ke direktori web server lokal Anda (misal: `laragon/www/JEDA` atau `htdocs/JEDA`):
   ```bash
   git clone [https://github.com/username/JEDA.git](https://github.com/username/JEDA.git)

2. **Konfigurasi Database:**

Buat database baru di MySQL dengan nama jeda_db (pastikan collation diset ke utf8mb4_unicode_ci).

Import file .sql yang tersedia ke dalam database tersebut melalui phpMyAdmin atau HeidiSQL.

Pengaturan Koneksi:

Sesuaikan kredensial database Anda pada file koneksi.php:
 ```php
   $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "jeda_db";
```
3. **Aplikasi Siap Digunakan:**

Buka browser dan akses URL: http://localhost/JEDA/login.php

🌐 Deployment (Hostinger)
Upload file project melalui Git integration atau File Manager Hostinger.

Buat database MySQL di panel Hostinger, lalu import struktur tabel yang sama.

Pastikan file koneksi.php sudah dikonfigurasi menggunakan konfigurasi database production dan penambahan charset:
 ```php
  $conn->set_charset("utf8mb4");
```
---