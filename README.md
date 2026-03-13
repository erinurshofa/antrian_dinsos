# 🏛️ Sistem Antrian — Dinas Sosial Kota Semarang

Aplikasi **sistem antrian pelayanan publik** berbasis web untuk Dinas Sosial Kota Semarang. Dibangun dengan framework **Yii2 Basic** (PHP) + MySQL.

---

## 📋 Daftar Isi

- [Gambaran Umum](#-gambaran-umum)
- [Alur Aplikasi](#-alur-aplikasi)
- [Halaman & URL](#-halaman--url)
- [Peran Pengguna (Roles)](#-peran-pengguna-roles)
- [Akun Default](#-akun-default)
- [Arsitektur Kode](#-arsitektur-kode)
- [Database](#-database)
- [Fitur-Fitur](#-fitur-fitur)
- [Instalasi & Setup](#-instalasi--setup)
- [Konfigurasi](#-konfigurasi)
- [Troubleshooting](#-troubleshooting)

---

## 🎯 Gambaran Umum

Aplikasi ini mengatur **alur antrian pengunjung** dari awal mengambil nomor sampai selesai dilayani:

```
Pengunjung → Ambil Tiket (Kiosk) → Menunggu → Dipanggil Petugas → Dilayani → Selesai
```

### Komponen Sistem

| Komponen | Fungsi | Pengguna |
|----------|--------|----------|
| **Kiosk** | Layar sentuh untuk ambil nomor antrian | Pengunjung |
| **Display** | Layar TV menampilkan antrian aktif + suara | Semua (publik) |
| **Panel Petugas** | Memanggil & melayani antrian | Petugas loket |
| **Panel Satpam** | Monitoring keamanan & antrian | Satpam |
| **Dashboard** | Statistik & laporan | Admin / Pimpinan |
| **Riwayat Layanan** | Lihat & export data selesai | Petugas / Admin |

---

## 🔄 Alur Aplikasi

### Alur Lengkap (End-to-End)

```
┌─────────────────────────────────────────────────────────────────┐
│                        ALUR ANTRIAN                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. PENGUNJUNG datang ke Dinas Sosial                           │
│     │                                                           │
│  2. Ambil nomor di KIOSK (/kiosk)                               │
│     ├── Pilih: "Antrian Umum" (U001, U002, ...)                 │
│     └── Pilih: "Antrian Rentan" (R001, R002, ...)               │
│          (lansia, disabilitas, ibu hamil)                        │
│     │                                                           │
│  3. Status: WAITING (menunggu)                                  │
│     Nomor tampil di DISPLAY (/display) sebagai "menunggu"       │
│     │                                                           │
│  4. PETUGAS klik "PANGGIL" di Panel (/officer/panel)            │
│     │  → Status berubah: CALLED → SERVING                       │
│     │  → Display menampilkan nomor + loket                      │
│     │  → Suara pengumuman berbunyi 3x                           │
│     │  → "Nomor antrian umum lima belas                         │
│     │     silakan menuju ke loket satu"                          │
│     │                                                           │
│  5. PETUGAS mengisi Data Pelayanan:                             │
│     ├── Nama, Jenis Kelamin, No. HP, NIK                       │
│     ├── Jenis Layanan (PBI/UHC/Bansos/dll)                     │
│     └── Keperluan & Keterangan                                  │
│     │                                                           │
│  6. PETUGAS klik "SELESAI"                                      │
│     │  → Status: COMPLETED                                      │
│     │  → Durasi dihitung otomatis                                │
│     │  → Otomatis bisa panggil antrian berikutnya                │
│     │                                                           │
│  7. Data masuk ke RIWAYAT (/officer/history)                    │
│     └── Bisa di-export CSV kapan saja                           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Status Lifecycle Antrian

```
waiting → called → serving → completed
                          └→ cancelled
         └→ skipped
```

| Status | Arti |
|--------|------|
| `waiting` | Menunggu dipanggil |
| `called` | Sudah dipanggil, pengunjung menuju loket |
| `serving` | Sedang dilayani petugas |
| `completed` | Selesai dilayani |
| `cancelled` | Dibatalkan |
| `skipped` | Dilewati (tidak hadir) |

---

## 🌐 Halaman & URL

### Halaman Publik (tanpa login)

| URL | Halaman | Keterangan |
|-----|---------|------------|
| `/kiosk` | Kiosk Ambil Antrian | Layar sentuh untuk pengunjung |
| `/display` | Display Antrian | Layar TV, auto-refresh via SSE + polling |

### Halaman Internal (login required)

| URL | Halaman | Role |
|-----|---------|------|
| `/login` | Login | Semua |
| `/officer/panel` | Panel Petugas | petugas, admin |
| `/officer/history` | Riwayat Layanan | petugas, admin |
| `/officer/export` | Export CSV | petugas, admin |
| `/security/panel` | Panel Satpam | satpam, admin |
| `/dashboard/index` | Dashboard Statistik | pimpinan, admin |

### API Endpoint

| URL | Method | Fungsi |
|-----|--------|--------|
| `/api/queue/stream` | GET (SSE) | Real-time stream data antrian |
| `/api/queue/status` | GET | Status antrian saat ini (JSON) |
| `/api/queue/take` | POST | Ambil tiket antrian baru |
| `/api/officer/call` | POST | Panggil antrian berikutnya |
| `/api/officer/complete` | POST | Selesaikan antrian |
| `/api/dashboard/stats` | GET | Data statistik dashboard |

---

## 👥 Peran Pengguna (Roles)

| Role | Akses | Tugas |
|------|-------|-------|
| **admin** | Semua halaman | Kelola sistem, user, konfigurasi |
| **petugas** | Panel Petugas + Riwayat | Memanggil & melayani antrian di loket |
| **satpam** | Panel Satpam | Monitoring antrian & keamanan |
| **pimpinan** | Dashboard | Melihat statistik & laporan |

### Hubungan Petugas ↔ Loket

- Setiap **petugas** di-assign ke satu **loket** (via `loket_id` di tabel `users`)
- Setiap **loket** bisa di-assign ke satu **tipe antrian** (via `queue_type_id` di tabel `loket`)
  - Loket 1 → Antrian Umum (hanya tarik antrian U)
  - Loket 2 → Antrian Rentan (hanya tarik antrian R)
  - Loket 4 → `null` (bisa tarik semua tipe)

---

## 🔑 Akun Default

Akun dibuat oleh migration seed (`m260313_010008_seed_data.php`):

| Username | Password | Role | Loket |
|----------|----------|------|-------|
| `admin` | `admin123` | admin | - |
| `satpam` | `satpam123` | satpam | - |
| `petugas1` | `petugas123` | petugas | Loket 1 (Umum) |
| `petugas2` | `petugas123` | petugas | Loket 2 (Rentan) |
| `pimpinan` | `pimpinan123` | pimpinan | - |

> ⚠️ **Ganti password di production!**

---

## 🏗️ Arsitektur Kode

```
ANTRIAN - DINSOS/
├── config/
│   ├── db.php              # Koneksi database MySQL
│   ├── web.php             # Konfigurasi aplikasi (URL rules, components)
│   ├── params.php          # Parameter aplikasi
│   └── console.php         # Konfigurasi console (migration)
│
├── controllers/
│   ├── SiteController.php       # Login, logout, error
│   ├── QueueController.php      # Kiosk, display, SSE stream, status API
│   ├── OfficerController.php    # Panel petugas, call, complete, history, export
│   ├── SecurityController.php   # Panel satpam
│   └── DashboardController.php  # Dashboard pimpinan, statistik, export
│
├── models/
│   ├── User.php            # User + role (admin/satpam/petugas/pimpinan)
│   ├── Queue.php           # Antrian (logic: generate, callNext, status)
│   ├── QueueType.php       # Tipe antrian (UMUM, RENTAN)
│   ├── Service.php         # Data pelayanan (nama, JK, HP, NIK, dll)
│   ├── ServiceType.php     # Jenis layanan (PBI, UHC, Bansos, dll)
│   ├── Loket.php           # Loket pelayanan
│   ├── Office.php          # Kantor/instansi
│   ├── AuditLog.php        # Log aktivitas
│   └── LoginForm.php       # Form login
│
├── views/
│   ├── layouts/
│   │   ├── main.php        # Layout utama (sidebar + header)
│   │   ├── kiosk.php       # Layout fullscreen (kiosk & display)
│   │   └── blank.php       # Layout kosong (login)
│   ├── queue/
│   │   ├── kiosk.php       # Halaman kiosk ambil antrian
│   │   └── display.php     # Halaman display TV (+ voice announcement)
│   ├── officer/
│   │   ├── panel.php       # Panel petugas (call/complete/form data)
│   │   ├── history.php     # Riwayat layanan + filter + pagination
│   │   └── no-loket.php    # Error: petugas belum assign loket
│   ├── security/
│   │   └── panel.php       # Panel satpam
│   ├── dashboard/
│   │   └── index.php       # Dashboard statistik (chart)
│   └── site/
│       ├── login.php       # Halaman login
│       └── error.php       # Halaman error
│
├── migrations/             # 8 migration files (schema + seed data)
├── web/
│   ├── index.php           # Entry point
│   ├── .htaccess           # Apache rewrite rules
│   └── css/app.css         # Stylesheet utama (dark theme)
│
├── composer.json           # Dependencies (yiisoft/yii2)
└── README.md               # 📄 File ini
```

### Diagram Relasi Model

```
Office (1) ──→ (*) User
Office (1) ──→ (*) Loket
Office (1) ──→ (*) Queue
Office (1) ──→ (*) Service

QueueType (1) ──→ (*) Queue
QueueType (1) ──→ (*) Loket

Loket (1) ──→ (*) Queue
Loket (1) ──→ (1) User (petugas)

Queue (1) ──→ (1) Service
Queue (*) ──→ (1) User (called_by)

Service (*) ──→ (1) ServiceType
Service (*) ──→ (1) User (officer)
```

---

## 🗄️ Database

### Tabel

| Tabel | Deskripsi |
|-------|-----------|
| `offices` | Data kantor/instansi |
| `users` | Akun pengguna (admin, petugas, satpam, pimpinan) |
| `loket` | Loket pelayanan (terkait queue_type) |
| `queue_types` | Tipe antrian: UMUM (prefix U) & RENTAN (prefix R) |
| `queues` | Data antrian per hari (queue_number, status, timestamps) |
| `service_types` | Jenis layanan: PBI, UHC, Tanda Daftar, Bansos, Konsultasi, Lainnya |
| `services` | Data pelayanan (nama, JK, HP, NIK, keperluan, durasi) |
| `audit_logs` | Log aktivitas sistem |

### Nomor Antrian

Format: `{prefix}{3-digit-number}` — contoh: `U001`, `U002`, `R001`, `R015`

- Prefix `U` = Antrian Umum
- Prefix `R` = Antrian Rentan (prioritas)
- Nomor reset otomatis setiap hari baru

---

## ✨ Fitur-Fitur

### 🔊 Voice Announcement (Suara Panggilan)
- Pengumuman suara otomatis saat antrian dipanggil
- Angka dibaca dalam Bahasa Indonesia natural: `123` → "seratus dua puluh tiga"
- Support angka sampai ratusan juta
- Preferensi voice perempuan (Microsoft Gadis di Windows)
- Diulang **3 kali** dengan jeda 800ms
- Nada suara: pitch 1.4, rate 1.05 (semangat & antusias)

### 📊 Dashboard Pimpinan
- Total antrian hari ini, selesai, menunggu, dilayani
- Rata-rata waktu pelayanan
- Chart: tren harian, distribusi layanan, jam sibuk, performa loket
- Export CSV dan PDF

### 📋 Riwayat Layanan (Petugas)
- Filter: tanggal, tipe antrian, pencarian (nama/HP/NIK/no.antrian)
- Pagination 20 data/halaman
- Export CSV (UTF-8, Excel-compatible)

### 🔄 Real-time Update
- **SSE (Server-Sent Events)** untuk push real-time
- **Polling** backup setiap 5 detik
- Display & panel auto-update tanpa refresh

---

## 🚀 Instalasi & Setup

### Prasyarat

- **PHP** ≥ 7.4 (disarankan PHP 8.x)
- **MySQL** ≥ 5.7
- **Composer** (dependency manager)
- **Web server**: Apache/Nginx atau PHP built-in server

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/erinurshofa/antrian_dinsos.git
cd antrian_dinsos

# 2. Install dependencies
composer install

# 3. Buat database MySQL
mysql -u root -p -e "CREATE DATABASE antrian_dinsos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Sesuaikan config database (lihat bagian Konfigurasi)
# Edit file: config/db.php

# 5. Jalankan migration (buat tabel + data awal)
php yii migrate --interactive=0

# 6. Jalankan server
php yii serve --port=8080

# Buka: http://localhost:8080
```

### Setup untuk Production (Apache)

1. Arahkan DocumentRoot ke folder `web/`
2. Pastikan `mod_rewrite` aktif
3. File `.htaccess` sudah tersedia di `web/`
4. Set permission: `runtime/` dan `web/assets/` writable oleh web server

---

## ⚙️ Konfigurasi

### Database (`config/db.php`)

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;port=3306;dbname=antrian_dinsos',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

> Sesuaikan `host`, `port`, `username`, `password` dengan server MySQL Anda.

### URL Routes (`config/web.php`)

Pretty URL sudah dikonfigurasi. Route penting:

```php
'urlManager' => [
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
        'kiosk' => 'queue/kiosk',
        'display' => 'queue/display',
        'api/queue/stream' => 'queue/stream',
        'api/queue/status' => 'queue/status',
        // ... dll
    ],
],
```

---

## 🔧 Troubleshooting

### SSE Error: MIME type "text/html" not "text/event-stream"
- Pastikan URL SSE menggunakan `/api/queue/stream` (ada prefix `api/`)
- Cek tidak ada error PHP yang mengirim output HTML sebelum header SSE

### Suara masih laki-laki
- Buka Console browser (F12) → cek log `🔊 Available voices:`
- Install voice perempuan Indonesia di Windows:
  **Settings → Time & Language → Speech → Manage voices → Add → Indonesian**
- Restart browser setelah install voice baru

### Migration error
```bash
# Reset migration (HAPUS SEMUA DATA!)
php yii migrate/down --interactive=0
php yii migrate --interactive=0
```

### Halaman blank / error 500
- Cek `runtime/logs/app.log` untuk detail error
- Pastikan folder `runtime/` writable: `chmod -R 777 runtime/` (Linux/Mac)

### Display tidak update real-time
- SSE auto-fallback ke polling setelah 3x gagal
- Polling backup berjalan setiap 5 detik
- Cek Console browser untuk error

---

## 📝 Catatan Teknis

### Bagaimana Nomor Antrian Di-generate
1. Pengunjung pilih tipe antrian di Kiosk
2. Sistem cek nomor terakhir hari ini untuk tipe tersebut
3. Nomor berikutnya = `{prefix}{last + 1}`, padding 3 digit
4. Contoh: antrian UMUM ke-15 hari ini = `U015`
5. Reset ke 1 setiap hari baru (cek `queue_date`)

### Bagaimana Petugas Memanggil Antrian
1. Petugas klik "PANGGIL" → `POST /api/officer/call`
2. Sistem cari antrian `waiting` paling awal sesuai tipe loket
3. Status diubah: `called` → `serving`
4. Record `Service` dibuat otomatis
5. SSE push update ke Display → suara berbunyi

### Bagaimana Real-time Bekerja
```
Display ←──SSE──── QueueController::actionStream()
   │                      │
   │                      ├── Check Queue::getLastUpdate()
   │                      ├── If changed → send JSON event
   │                      └── Heartbeat setiap 15 detik
   │
   └──Polling──── QueueController::actionStatus()  (backup tiap 5s)
```

---

*Dibuat untuk Dinas Sosial Kota Semarang © 2026*
