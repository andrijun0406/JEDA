# JEDA Smart Saving Card 💳✨
**"Pause the Impulse. Protect Your Future."**

JEDA adalah platform *companion web app* berbasis gamifikasi dan literasi finansial. Prototipe ini dirancang khusus untuk ekosistem *boarding school* (SMA Asy-Syahid) sebagai pelengkap sistem BSI Smart Card dan kasir Syahid Mart yang sudah ada. JEDA tidak menyimpan uang asli, melainkan menggunakan konsep **Virtual Soft-Limit** untuk memanipulasi batas jajan harian siswa secara psikologis agar mereka disiplin menabung.

---

## 🎯 Nilai Inovasi Utama
1. **Pemisahan Hard Limit & Soft Limit:** Memotong batas jajan harian BSI (Rp25.000) dengan target tabungan harian siswa, sehingga memunculkan "Jatah Jajan Aman" virtual.
2. **Gamifikasi Angel vs Zombie:** Avatar siswa akan bersinar (Angel 👼) jika disiplin, dan berubah menjadi zombi (Zombie 🧟) jika belanja melebihi batas (berutang pada tabungan sendiri).
3. **AI Financial Coach:** Menganalisis riwayat transaksi Syahid Mart (misal: pengeluaran *"Camilan Malam"*) dan memberikan saran penghematan kontekstual.
4. **Smart Parental Dashboard:** Dasbor orang tua yang secara cerdas **mengunci** fitur hadiah (Top-Up Apresiasi) jika anak sedang dalam fase boros/zombi.

---

## 💻 Tech Stack Prototipe
*   **Backend:** PHP (Native/Vanilla) & MySQL.
*   **Frontend:** HTML5, CSS3, JavaScript (Bootstrap 5 via CDN).
*   **Environment:** Laragon / XAMPP (Local) & Hostinger (Live Deployment).

---

## 🚀 Panduan Instalasi Lokal (Laragon/XAMPP)
1. Pindahkan folder `jeda` ke dalam direktori `www` (Laragon) atau `htdocs` (XAMPP).
2. Buka **HeidiSQL** atau **phpMyAdmin**.
3. Buat database baru dengan nama `jeda_db`.
4. Jalankan (Import/Eksekusi Query) kumpulan *script* SQL yang telah disediakan sebelumnya untuk membentuk tabel dan data *dummy*.
5. Sesuaikan kredensial database di file `koneksi.php` (default user: `root`, password: kosong).
6. Buka browser dan akses: `http://localhost/jeda/login.php` (atau `http://jeda.test` di Laragon).

---

## 🎬 Skenario Presentasi (The "Wow" Moments)

Berikut adalah panduan mendemonstrasikan sistem ini di depan juri. Sistem memiliki dua peran utama: **Siswa** dan **Orang Tua**. 

Gunakan akun-akun *dummy* di bawah ini untuk menunjukkan kecerdasan dinamis JEDA:

### Skenario 1: Teguran Cerdas AI Financial Coach (Fokus: Literasi Keuangan)
Sistem JEDA tidak memberikan respons pasif, melainkan membaca apa yang dibeli siswa.
*   **Login Siswa:** `dika` | **Password:** `123`
*   **Aksi:** Tunjukkan Dasbor Dika. Juri akan melihat AI menyala (💡) dan memberikan teguran spesifik: *"Jajan camilan malammu naik nih. Kurangi jajan malam agar target beli Jaket Angkatan cepat tercapai!"* Ini terjadi karena *database* mencatat Dika sering jajan di kategori "Camilan Malam".
*   **Kontras:** Coba login sebagai `siti` (Password: `123`). Siti hanya membeli buku dan air mineral, sehingga AI akan memberikan pujian, bukan teguran.

### Skenario 2: Gamifikasi Sanksi Sosial (Angel vs Zombie)
Tunjukkan bahwa aplikasi ini menghukum keborosan dengan sanksi visual.
*   **Login Siswa:** `budi` | **Password:** `123`
*   **Aksi:** Budi saat ini berstatus boros (utang tabungan Rp5.000). Sorot avatarnya yang berubah menjadi **Zombie (🧟‍♂️)** dan pita peringatan utang berwarna merah.
*   **Aksi Tambahan:** Klik "Custom Avatar" dan ubah menjadi Kucing Emas. Karena masih berutang, avatarnya menjadi **Kucing Zombie (😿🧟)**.

### Skenario 3: Smart Parenting & Conditional Top-Up (Puncak Presentasi)
Gunakan skenario ini untuk memukau juri terkait fitur edukasi karakter di tingkat keluarga.
*   **Login Orang Tua:** `ortu_hendra` | **Password:** `123`
*   **Aksi:** Jelaskan bahwa Bapak Hendra sedang memantau anak kembarnya: **Aldo** (Si Boros) dan **Aldi** (Si Hemat).
*   **Sorot Kartu Aldo:** Tunjukkan bahwa Aldo berutang (Zombie). Buktikan bahwa tombol apresiasi berubah menjadi abu-abu bertuliskan **"🔒 Apresiasi Terkunci (Anak Berutang)"**. Sistem mendidik orang tua agar *tidak memanjakan* anak yang boros.
*   **Sorot Kartu Aldi:** Tunjukkan bahwa Aldi disiplin (Angel) dengan *streak* 21 hari. Tombol apresiasi berwarna hijau menyala.
*   **Eksekusi:** Klik tombol apresiasi di kartu Aldi, berikan nominal Rp 50.000. Sistem akan mensimulasikan penambahan saldo langsung ke akun PSP Mobile milik Aldi.

---

## 📁 Struktur Direktori
*   `login.php` : Pintu masuk aplikasi (deteksi otomatis peran siswa/ortu).
*   `index.php` : Dasbor utama siswa (Soft Limit, Gamifikasi, AI Coach).
*   `parent_dashboard.php` : Dasbor orang tua (Multi-child tracking, Conditional Top Up).
*   `koneksi.php` : Jembatan koneksi sistem dengan MySQL.
*   `update_goal.php` & `update_avatar.php` : Pemroses form kustomisasi profil siswa.
*   `topup_action.php` : Pemroses transfer uang virtual dari orang tua ke anak.
*   `logout.php` : Penghancur sesi (*session destroyer*).

---
*Dikembangkan untuk presentasi inovasi teknologi finansial pelajar (2026).*