# JEDA

**Smart Parenting & Financial Literacy Monitor**

JEDA adalah aplikasi web interaktif berbasis PHP dan MySQL untuk membantu anak dan orang tua memantau literasi keuangan, pengeluaran harian, target tabungan (*saving goals*), serta prediksi kebutuhan rutin (*Recurring Baseline Analysis*).

## Fitur Utama

1. **Dashboard siswa dan analitik bulanan**
   - Memantau total pengeluaran harian dan bulanan.
   - Memfilter riwayat transaksi berdasarkan bulan.
   - Menampilkan proporsi pengeluaran menggunakan grafik donat Chart.js.
2. **Recurring baseline dan prediksi restock**
   - Mendeteksi pola konsumsi berulang, seperti sabun, pasta gigi, atau paket data.
   - Memperkirakan waktu barang perlu diisi ulang.
3. **Manajemen target tabungan**
   - Menetapkan target finansial dan memantau progresnya.
   - Menggunakan status `ACTIVE` dan `COMPLETED`.
4. **Leaderboard dan gamifikasi**
   - Memotivasi siswa untuk mengelola uang dan menabung dengan lebih bijak.
5. **Profil dan virtual wallet**
   - Menampilkan saldo, batas pengeluaran harian (*daily hard limit*), dan streak menabung.

## Teknologi

- **Backend:** PHP native dan MySQLi.
- **Frontend:** Tailwind CSS.
- **Database:** MySQL atau MariaDB dengan encoding `utf8mb4`.
- **Grafik:** Chart.js.
- **Lingkungan server:** Laragon untuk pengembangan lokal; Hostinger untuk deployment.

## Struktur Database

Tabel utama yang digunakan aplikasi:

- `parents`: akun orang tua.
- `users`: akun siswa yang terhubung ke orang tua melalui `parent_id`.
- `dummy_transactions`: riwayat transaksi siswa.
- `item_baselines`: referensi siklus kebutuhan rutin.
- `saving_goals`: target tabungan dan progresnya.
- `virtual_wallets`: saldo, batas, dan streak menabung siswa.

## Skenario Demo Berdasarkan Data Seed

Semua akun contoh menggunakan kata sandi **`123`**. Pada halaman login, pilih portal **Siswa** atau **Orang Tua** sesuai jenis akun.

### Skenario siswa

| Username | Contoh perilaku |
| --- | --- |
| `nisa` atau `aldi` | Contoh siswa rajin menabung. Keduanya memiliki streak menabung 21 hari pada data seed. |
| `budi` atau `aldo` | Contoh siswa dengan pengelolaan keuangan yang kurang disiplin. Aldo memiliki transaksi belanja bernilai besar, sedangkan Budi memiliki saldo rendah dan utang virtual. |

### Skenario orang tua

| Username | Anak yang dapat dipantau |
| --- | --- |
| `ortu_hendra` | Aldo (contoh boros) dan Aldi (contoh rajin menabung), sehingga perilaku dua anak dapat dibandingkan dari satu akun orang tua. |
| `ortu_dika` | Dika (satu anak). |
| `ortu_nisa` | Nisa (satu anak, contoh rajin menabung). |

Pada data seed saat ini, akun `ortu_dika` hanya terhubung ke Dika; akun tersebut tidak menampilkan Aldo atau Aldi. Gunakan `ortu_hendra` untuk melihat contoh anak boros dan hemat sekaligus.

## Instalasi Lokal

1. Clone repository ke direktori web server lokal, misalnya `laragon/www/JEDA` atau `htdocs/JEDA`:

   ```bash
   git clone https://github.com/username/JEDA.git
   ```

2. Buat database MySQL bernama `jeda_db` dengan collation `utf8mb4_unicode_ci`.
3. Import file SQL yang tersedia melalui phpMyAdmin atau HeidiSQL.
4. Sesuaikan kredensial database pada [koneksi.php](./koneksi.php):

   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";
   $db = "jeda_db";
   ```

5. Buka aplikasi melalui `http://localhost/JEDA/login.php`.

## Deployment ke Hostinger

1. Unggah file proyek menggunakan Git integration atau File Manager Hostinger.
2. Buat database MySQL di panel Hostinger, lalu import file SQL.
3. Perbarui kredensial pada [koneksi.php](./koneksi.php) sesuai konfigurasi database production. Koneksi menggunakan charset `utf8mb4`.
