# TaskMate — Sistem Manajemen Tugas dan Berkas Kerja Kelompok

Aplikasi web berbasis **Laravel 12** untuk membantu kelompok mahasiswa mengatur pembagian
tugas, tenggat waktu, dan pengelolaan berkas draf dalam satu tempat. Dikembangkan oleh
**Kelompok 5 — Sarjana Terapan Sistem Informasi Kota Cerdas, Politeknik Negeri Banjarmasin (2026)**
sesuai dokumen SRS TaskMate.

![Logo TaskMate](public/images/logo.png)

---

## ✨ Fitur Utama

| ID | Fitur | Keterangan |
|----|-------|------------|
| TMT-001 | Registrasi & login multiuser | Email + kata sandi (Bcrypt) dan **Masuk dengan Google** (Laravel Socialite). Akun terkunci 15 menit setelah 5 kali gagal login. |
| TMT-002 | Workspace kelompok | CRUD workspace, peran Leader/Member, **kode undangan + link bertanda tangan + QR Code**, saklar aktif, masa berlaku, dan regenerate kode. |
| TMT-003 | Pembagian tugas & deadline | Leader membagi jobdesk; sistem menolak deadline lebih awal dari hari ini dan PJ yang bukan anggota. |
| TMT-004 | Kanban board & task tracking | Kolom **To-Do, In Progress, Review, Done** (tersembunyi sampai diklik), drag & drop, filter PJ + pencarian, warna deadline kuning/merah, riwayat perubahan status. |
| TMT-005 | Repository & file versioning | Unggahan nama sama otomatis jadi v1, v2, v3… Unduh versi tertentu, **pratinjau PDF/gambar bawaan**, hapus berkas (leader). |
| TMT-006 | Deadline notification | Scheduler pukul 07.00 WITA: pengingat H-3, H-1, hari-H + notifikasi keterlambatan (lonceng di aplikasi + email, dedupe anti ganda). |
| ➕ | Chat realtime per workspace | Polling AJAX tiap 2 detik — tanpa WebSocket/Node, aman untuk XAMPP. |
| ➕ | Profil akun | Edit data, **upload foto profil**, ubah kata sandi, keluar, dan **hapus akun permanen** (kepemimpinan workspace otomatis ditransfer). |

---

## 🧰 Persyaratan Sistem

1. **XAMPP** (PHP **8.2+** dan MySQL/MariaDB) — [apachefriends.org](https://www.apachefriends.org/)
2. **Composer** — [getcomposer.org](https://getcomposer.org/download/)

Ekstensi PHP yang dibutuhkan (semua sudah aktif bawaan XAMPP):
`openssl`, `pdo_mysql`, `mbstring`, `fileinfo`, `gd` (opsional), `zip` (untuk Composer).

---

## 🚀 Langkah Instalasi (XAMPP + phpMyAdmin)

### 1. Ekstrak & pindahkan proyek
Ekstrak ZIP ini, lalu letakkan folder `taskmate` di mana saja, misalnya `C:\xampp\htdocs\taskmate`
(tidak wajib di htdocs karena kita memakai `php artisan serve`).

### 2. Instal dependensi
Buka terminal/CMD di dalam folder proyek:
```bash
composer install
```

### 3. Siapkan file lingkungan
File `.env` **sudah disertakan** (terisi default XAMPP). Jika ingin menyalin ulang:
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Buat database di phpMyAdmin
1. Nyalakan **Apache** dan **MySQL** dari XAMPP Control Panel.
2. Buka `http://localhost/phpmyadmin` → klik **New** → buat database bernama **`taskmate`**
   (collation boleh dibiarkan default).
3. Konfigurasi `.env` (default sudah cocok untuk XAMPP):
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=taskmate
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### 5. Jalankan migrasi (membuat semua tabel)
```bash
php artisan migrate
```

### 6. Buat tautan penyimpanan (untuk foto profil)
```bash
php artisan storage:link
```

### 7. Jalankan aplikasi
```bash
php artisan serve
```
Lalu buka **http://127.0.0.1:8000** — halaman landing TaskMate akan muncul. 🎉

> ⚠️ **Penting:** pastikan `APP_URL` di `.env` adalah `http://127.0.0.1:8000`
> (sesuai cara Anda membuka aplikasi) karena nilai ini dipakai untuk link undangan & Google OAuth.

---

## 🔐 Sign in with Google (Laravel Socialite)

1. Buka [Google Cloud Console](https://console.cloud.google.com/) → buat project, misal **TaskMate**.
2. Menu **APIs & Services → OAuth consent screen**:
   - User Type: **External** → CREATE.
   - Isi App name (`TaskMate`), email dukungan, dan email developer → SAVE.
   - Tambahkan email Anda pada **Test users** (cukup untuk pengembangan).
3. Menu **APIs & Services → Credentials → Create Credentials → OAuth client ID**:
   - Application type: **Web application**.
   - **Authorized redirect URIs** tambahkan persis:
     ```
     http://127.0.0.1:8000/auth/google/callback
     ```
     (jika memakai `localhost:8000`, sesuaikan `APP_URL` di `.env` menjadi `http://localhost:8000` lalu URI-nya juga `http://localhost:8000/auth/google/callback`).
4. Salin **Client ID** dan **Client Secret** ke `.env`:
   ```env
   GOOGLE_CLIENT_ID=xxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=GOCSPX-xxxx
   GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
   ```
5. Jalankan ulang `php artisan serve` bila sedang berjalan.

> Tanpa kredensial Google, login email/kata sandi tetap berfungsi normal.

---

## ⏰ Scheduler Pengingat Deadline (TMT-006)

Sistem memeriksa deadline **setiap hari pukul 07.00 WITA**.

**Mode pengembangan (Windows/XAMPP):** buka terminal kedua di folder proyek:
```bash
php artisan schedule:work
```

**Mode produksi (Linux/cron):**
```
* * * * * cd /path/ke/taskmate && php artisan schedule:run >> /dev/null 2>&1
```

**Uji manual kapan saja:**
```bash
php artisan taskmate:check-deadlines
```

### Email pengingat
- Default `MAIL_MAILER=log` → email hanya dicatat ke `storage/logs/laravel.log` (aman untuk demo).
- Kirim email sungguhan via Gmail SMTP:
  ```env
  MAIL_MAILER=smtp
  MAIL_HOST=smtp.gmail.com
  MAIL_PORT=587
  MAIL_USERNAME=alamatgmailanda@gmail.com
  MAIL_PASSWORD=xxxxxxxxxxxxxxxx   ← App Password 16 digit (bukan sandi akun)
  MAIL_ENCRYPTION=tls
  ```
  Buat App Password di https://myaccount.google.com/apppasswords (wajib verifikasi 2 langkah).

---

## ⚠️ Atur batas unggahan (penting untuk berkas 20 MB)

Unggahan dibatasi **20 MB** oleh aplikasi, tetapi PHP juga punya batas sendiri. Edit `php.ini`
(XAMPP → Config → php.ini), lalu restart Apache:

```ini
upload_max_filesize = 25M
post_max_size = 26M
memory_limit = 256M
max_execution_time = 120
```

---

## 🔒 Keamanan Undangan (Link + QR Code)

Bergabung lewat link/QR dilengkapi beberapa lapisan:

1. **Signed URL (HMAC)** — link ditandatangani Laravel; mengubah isi link membuat tanda tangan gugur.
2. **Kode 8 karakter acak** — charset tanpa 0/O/1/I agar tidak tertukar.
3. **Wajib login** — pengunjung diarahkan ke halaman masuk dulu, lalu kembali otomatis ke undangan.
4. **Halaman konfirmasi** — pengguna melihat info workspace & leader sebelum memutuskan bergabung.
5. **Rate limiting** — maksimal 12 akses/menit per pengguna untuk endpoint undangan; 6/menit untuk mencoba kode.
6. **Saklar aktif & masa berlaku** — leader dapat menonaktifkan undangan atau membatasi masa berlaku (1/3/7/30 hari).
7. **Regenerate kode** — membuat kode baru otomatis mencabut semua link & QR lama.
8. **Isolasi workspace** — hanya anggota yang dapat membuka halaman workspace, tugas, berkas, chat (middleware + cek di setiap controller), termasuk unduhan berkas.

---

## 🗂️ Struktur & Basis Data

Tabel (sesuai ERD SRS + tambahan): `users`, `workspaces`, `workspace_members`, `tasks`,
`task_status_logs`, `file_dokumens`, `file_versions`, `notifikasis`, `workspace_chats`.

```
app/
├── Console/Commands/CheckDeadlines.php   # scheduler deadline (FR-021..023)
├── Http/Controllers/
│   ├── Auth/         # Login, Register, Google (Socialite)
│   ├── DashboardController.php
│   ├── JoinController.php          # undangan link/QR (signed)
│   ├── WorkspaceController.php     # CRUD + undangan + anggota
│   ├── KanbanController.php        # board + perpindahan status tervalidasi
│   ├── TaskController.php          # jobdesk & deadline
│   ├── RepositoryController.php    # upload versi, unduh, pratinjau
│   ├── ChatController.php          # chat realtime (polling)
│   ├── NotificationController.php  # lonceng notifikasi
│   └── ProfileController.php       # profil, foto, sandi, hapus akun
├── Mail/ReminderMail.php
├── Models/ (9 model)
├── Services/NotificationService.php
└── Support/helpers.php
resources/views/  # landing, auth split-layout, dashboard, workspace, kanban,
                  # repository, chat, notifikasi, profil, halaman gabung, email
routes/web.php    # semua rute aplikasi
routes/console.php# jadwal 07:00 WITA
```

### Catatan perilaku penting
- **Kanban kolom tersembunyi**: klik chip status (To-Do/In Progress/Review/Done) untuk
  menampilkan kolomnya; klik lagi untuk menyembunyikan. Ada juga tombol *Tampilkan Semua*.
- **Hak perpindahan status**: Member hanya bisa memajukan tugas miliknya satu tahap
  (To-Do → In Progress → Review). Hanya Project Leader yang dapat menetapkan **Done**,
  mengembalikan tugas dari Review, atau memindahkan tugas orang lain.
- **Hapus akun**: workspace yang dipimpin otomatis dialihkan ke anggota paling lama
  bergabung (atau terhapus jika kosong); tugas yang ditanggung menjadi tanpa PJ;
  pesan chat tetap tampil berlabel "Akun terhapus".
- **Batasan sesuai SRS**: format berkas PDF/DOC/DOCX/PPT/PPTX/XLS/XLSX/TXT/ZIP/PNG/JPG,
  maksimal 20 MB (dapat diubah via `TASKMATE_MAX_FILE_KB`).

---

## ⚠️ Jangan Letakkan Proyek di Google Drive / OneDrive

Jalankan proyek dari **disk lokal** (mis. `C:\xampp\htdocs\taskmate` atau `C:\dev\taskmate`),
**bukan** dari folder `G:\My Drive\...` (Google Drive) atau OneDrive.

Folder cloud membuat Laravel lambat luar biasa: setiap kali PHP membaca/menulis berkas
(kompilasi view Blade, sesi, cache, log), operasi melewati lapisan sinkronisasi cloud.
File "online-only" bahkan harus diunduh dulu saat diakses. Gejalanya:

- Halaman pertama stuck lama lalu muncul
  `PHP Fatal error: Maximum execution time of 60 seconds exceeded ... Filesystem.php`.
- Google Drive terus-menerus mengunggah ulang ribuan berkas `vendor/`.

**Solusi:** pindahkan folder proyek ke disk lokal, lalu jalankan ulang `php artisan serve` dari sana.
Sementara belum pindah, Anda dapat meringankan gejalanya:

1. Pause Google Drive, atau klik kanan folder → "Offline access" → "Available offline".
2. Naikkan batas waktu PHP: di `C:\xampp\php\php.ini` ubah `max_execution_time=300`.
3. Kurangi baca-tulis per request: `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.

---

## 🛠️ Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `Maximum execution time of 60 seconds exceeded` di `Filesystem.php` | Proyek berada di folder Google Drive/OneDrive — pindahkan ke disk lokal (lihat bagian di atas). |
| `ParseError ... unexpected token "endforeach"` pada view | Cache view terkorup (proses terpotong saat menulis). Jalankan `php artisan view:clear` lalu muat ulang halaman; bila masih error, salin ulang file view dari ZIP. |
| Parse error aneh di BANYAK view sekaligus (mis. `unexpected token "="`, `$__empty_-1`) | Vendor korup karena `composer install` pernah terputus. Perbaiki: `Remove-Item vendor -Recurse -Force` lalu `composer install` dan `php artisan view:clear`. |
| `could not find driver` saat migrate | Aktifkan `extension=pdo_mysql` di php.ini lalu restart Apache. |
| Upload 20 MB gagal | Perbesar `upload_max_filesize` & `post_max_size` (lihat bagian di atas). |
| Tombol Google error `redirect_uri_mismatch` | Samakan persis URI di Google Console dengan `APP_URL` + `/auth/google/callback`. |
| Link undangan "signature invalid" | `APP_URL` tidak sama dengan alamat yang Anda buka di browser. Samakan keduanya. |
| Foto profil tidak muncul | Jalankan `php artisan storage:link`. |
| Pengingat tidak jalan | Pastikan `php artisan schedule:work` aktif, atau uji manual `php artisan taskmate:check-deadlines`. |

---

## 👥 Tim Pengembang

- Fathur Ramadhan (C050425007)
- Muhammad Idhafi Ramadhan (C050425010)
- Nazalah Fitri (C050425043)
- Siti Sholatiah (C050425019)

**Program Studi Sarjana Terapan Sistem Informasi Kota Cerdas — Jurusan Teknik Elektro,
Politeknik Negeri Banjarmasin — 2026**
