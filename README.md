# logkaryawan
pencatatan kegiatan harian karyawan
# 📋 Log Karyawan
 
Aplikasi web untuk pencatatan dan manajemen logbook kerja harian karyawan. Karyawan dapat mencatat aktivitas harian mereka, yang kemudian diverifikasi oleh PIC (Person In Charge) dan dipantau oleh Direktur maupun Admin.
 
---
 
## 🗂️ Struktur Database
 
Database: `log_karyawan`
 
### Tabel `users`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | ID unik karyawan (format: `25XXXXX`) |
| `name` | VARCHAR | Nama lengkap |
| `email` | VARCHAR | Email perusahaan |
| `password` | VARCHAR | Password di-hash dengan bcrypt (salt=10) |
| `role` | ENUM | Role user: `admin`, `direktur`, `pic`, `karyawan` |
 
### Tabel `logbooks`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | ID unik logbook (format: `25XXXXX`) |
| `user_id` | INT | FK → `users.id` (karyawan yang mencatat) |
| `pic_id` | INT | FK → `users.id` (PIC yang bertanggung jawab) |
| `tanggal` | DATE | Tanggal aktivitas |
| `jam` | DECIMAL | Jumlah jam kerja (contoh: `1.5`, `4`) |
| `aktivitas` | TEXT | Deskripsi aktivitas yang dikerjakan |
| `status` | ENUM | Status logbook: `pending`, `approved`, `declined` |
| `catatan_pic` | TEXT | Catatan/feedback dari PIC (nullable) |
 
---
 
## 👥 Role & Hak Akses
 
| Role | Deskripsi |
|---|---|
| `admin` | Akses penuh ke seluruh sistem dan manajemen data |
| `direktur` | Memantau seluruh logbook dan rekap kinerja karyawan |
| `pic` | Mereview dan menyetujui/menolak logbook karyawan yang dipegangnya |
| `karyawan` | Mencatat aktivitas harian dan melihat status logbooknya sendiri |
 
---
 
## 🌱 Data Dummy
 
File SQL dummy tersedia untuk keperluan development dan testing.
 
### Komposisi user
- **1** Direktur
- **5** PIC
- **20** Karyawan
- **1** Admin
### Logbook
- Setiap karyawan memiliki **35–40 entri logbook** dalam rentang **7 hari** (18–24 Juni 2026)
- **3 karyawan tanpa logbook**: Chandra Putra, Julia Anggraini, Putri Handayani
- Status logbook bervariasi: `approved`, `pending`, `declined`
- Logbook yang `declined` disertai `catatan_pic`
### Format ID
Semua ID (user & logbook) menggunakan format **7 digit dengan prefix `25`** (contoh: `2566392`).
 
---
 
## 🔐 Credential Default (Development)
 
> ⚠️ Hanya untuk environment **development/testing**. Jangan digunakan di production.
 
Password setiap user adalah **ID mereka sendiri**, di-hash dengan bcrypt.
 
Contoh login:
 
| Role | Email | Password |
|---|---|---|
| Admin | `budi.admin@company.com` | `2576673` |
| Direktur | `hendra.dir@company.com` | `2566392` |
| PIC | `agus@company.com` | `2533214` |
| Karyawan | `alfian@company.com` | `2581756` |
 
---
 
## 🚀 Setup
 
### 1. Import database
 
```bash
mysql -u root -p < dummy_full.sql
```
 
### 2. Update password (jika diperlukan)
 
```bash
# Reset semua password menjadi ID masing-masing user
mysql -u root -p < update_passwords.sql
 
# Tambah user admin
mysql -u root -p < add_admin.sql
```
 
### 3. Konfigurasi koneksi
 
Sesuaikan konfigurasi koneksi database di file environment aplikasi:
 
```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=log_karyawan
DB_USER=root
DB_PASSWORD=your_password
```
 
---
 
## 📁 Daftar File SQL
 
| File | Keterangan |
|---|---|
| `db.sql` | Data dummy lengkap (users + logbooks) |

