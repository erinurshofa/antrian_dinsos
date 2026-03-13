# Panduan Deployment & Setup

## Sistem Antrian Layanan — Dinas Sosial Kota Semarang

---

## 1. System Requirements

| Component | Requirement |
|---|---|
| PHP | >= 8.0 dengan ext: mbstring, pdo_mysql, json, openssl |
| MySQL | >= 5.7 / MariaDB >= 10.3 |
| Composer | >= 2.0 |
| Web Server | Apache (mod_rewrite) atau Nginx |
| Browser | Chrome/Edge (untuk Web Speech API & thermal printing) |

---

## 2. Installation Steps

### 2.1 Clone & Install Dependencies
```bash
cd D:\_PEKERJAAN\ANTRIAN - DINSOS
composer install
```

### 2.2 Create Database
```sql
CREATE DATABASE antrian_dinsos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2.3 Configure Database
Edit `config/db.php`:
```php
'dsn' => 'mysql:host=localhost;dbname=antrian_dinsos',
'username' => 'root',
'password' => 'your_password',
```

### 2.4 Run Migrations
```bash
php yii migrate --migrationPath=@app/migrations
```
Ketik `yes` saat diminta konfirmasi.

### 2.5 Run Development Server
```bash
php yii serve --port=8080
```

Buka browser: **http://localhost:8080**

---

## 3. Default User Accounts

| Username | Password | Role | Loket |
|---|---|---|---|
| admin | admin123 | Administrator | - |
| satpam | satpam123 | Satpam | - |
| petugas1 | petugas123 | Petugas | Loket 1 |
| petugas2 | petugas123 | Petugas | Loket 2 |
| pimpinan | pimpinan123 | Pimpinan | - |

> ⚠️ **PENTING**: Ganti password sebelum deploy ke production!

---

## 4. URL Routes

| URL | Fungsi | Akses |
|---|---|---|
| `/login` | Halaman login | Public |
| `/kiosk` | Touch screen ambil antrian | Public |
| `/display` | TV display antrian real-time | Public |
| `/security/panel` | Panel satpam | Satpam |
| `/officer/panel` | Panel petugas | Petugas |
| `/dashboard/index` | Dashboard pimpinan | Pimpinan/Admin |

---

## 5. Thermal Printer Setup

1. Pasang thermal printer (58mm/80mm) di PC kiosk
2. Set sebagai **Default Printer** di Windows
3. Buka Chrome kiosk dalam mode kiosk:
```bash
chrome.exe --kiosk --kiosk-printing http://localhost:8080/kiosk
```
Flag `--kiosk-printing` akan auto-print tanpa dialog.

---

## 6. TV Display Setup

1. Buka Chrome di TV PC:
```bash
chrome.exe --kiosk http://localhost:8080/display
```
2. Display akan auto-update via SSE (real-time)
3. Voice announcement otomatis saat antrian dipanggil
4. Pastikan volume speaker aktif

---

## 7. Production Deployment (Apache)

### VirtualHost Configuration
```apache
<VirtualHost *:80>
    ServerName antrian.dinsos-semarang.go.id
    DocumentRoot "D:/_PEKERJAAN/ANTRIAN - DINSOS/web"
    
    <Directory "D:/_PEKERJAAN/ANTRIAN - DINSOS/web">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Security Checklist
- [ ] Set `YII_DEBUG` ke `false` di `web/index.php`
- [ ] Set `YII_ENV` ke `'prod'`
- [ ] Ganti `cookieValidationKey` dengan random string unik
- [ ] Ganti semua default password
- [ ] Aktifkan HTTPS (SSL certificate)
- [ ] Nonaktifkan Gii dan Debug module

---

## 8. Scalability (Multi-Office)

System sudah dirancang untuk mendukung multi-kantor:

1. Tambah kantor baru di tabel `offices` (province, city)
2. Assign user ke `office_id` yang sesuai
3. Semua query sudah di-filter per `office_id`
4. Siap deploy untuk seluruh Dinas Sosial se-Indonesia

---

## 9. Folder Structure
```
ANTRIAN - DINSOS/
├── config/          # Konfigurasi aplikasi
│   ├── db.php
│   ├── web.php
│   ├── console.php
│   └── params.php
├── controllers/     # Controller (MVC)
├── models/          # Model (MVC)
├── views/           # View templates
│   ├── layouts/     # Layout master
│   ├── site/        # Login, error
│   ├── queue/       # Kiosk, display
│   ├── officer/     # Panel petugas
│   ├── security/    # Panel satpam
│   └── dashboard/   # Dashboard pimpinan
├── migrations/      # Database migrations
├── web/             # Document root
│   ├── css/         # Stylesheets
│   ├── index.php    # Entry point
│   └── .htaccess    # URL rewrite
├── docs/            # Documentation
├── composer.json
└── yii              # Console entry
```
