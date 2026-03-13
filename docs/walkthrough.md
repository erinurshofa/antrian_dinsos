# Walkthrough — Queue Management System

## Overview
Built a complete **production-ready Queue Management System** for Dinas Sosial Kota Semarang using **Yii2 Basic Template**, **MySQL**, **Bootstrap 5**, and **SSE** for real-time updates.

## What Was Built

### ✅ Database (8 migrations, 3NF normalized)
- `offices` — multi-office national scalability
- `users` — RBAC roles (admin, satpam, petugas, pimpinan)
- `loket` — service counters
- `queue_types` — Umum (U) / Rentan (R)
- `queues` — daily auto-reset, status lifecycle
- `services` — visitor data + timing
- `service_types` — 6 service categories
- `audit_logs` — security trail

### ✅ Models (9 files)
Core engine in [Queue.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/models/Queue.php):
- `generateTicket()` — auto-increment **U001/R001** with daily reset
- `callNext()` — FIFO with loket-type filtering
- `getCurrentServing()` — real-time display data

### ✅ Controllers (5 files)
| Controller | Features |
|---|---|
| [QueueController](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/controllers/QueueController.php) | Kiosk, Display, SSE stream, REST API |
| [OfficerController](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/controllers/OfficerController.php) | Call, Complete, Save service data |
| [SecurityController](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/controllers/SecurityController.php) | Queue ticket generation |
| [DashboardController](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/controllers/DashboardController.php) | Stats API, Excel/PDF export |
| [SiteController](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/controllers/SiteController.php) | Login, Logout, role redirect |

### ✅ Views (7 screens) — Premium UI/UX

| Screen | File | Highlights |
|---|---|---|
| Login | [login.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/site/login.php) | Glassmorphism, gradient bg |
| Kiosk | [kiosk.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/queue/kiosk.php) | Touch-friendly, auto-print, ticket modal |
| Display | [display.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/queue/display.php) | SSE real-time, voice, ding-dong sound |
| Officer | [panel.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/officer/panel.php) | PANGGIL/SELESAI, service form, timer |
| Security | [panel.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/security/panel.php) | Queue buttons, recent table, auto-print |
| Dashboard | [index.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/dashboard/index.php) | 5 Chart.js charts, filters, export |

### ✅ Design System
[app.css](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/web/css/app.css) — 650+ lines:
- CSS variables, Inter + JetBrains Mono fonts
- Glassmorphism, gradient cards, micro-animations
- Responsive for mobile, tablet, and TV displays

### ✅ Real-time & Advanced Features
- **SSE** — Server-Sent Events polling every 2s with auto-reconnect
- **Web Speech API** — Indonesian voice: *"Nomor antrian umum satu dua tiga, silakan menuju Loket 1"*
- **Thermal Print** — `window.print()` with 80mm CSS layout, Chrome `--kiosk-printing`
- **Ding-dong sound** — AudioContext sine wave chime
- **Audit logging** — All actions tracked in `audit_logs`

## Quick Start
```bash
composer install
# Create database: antrian_dinsos
php yii migrate --migrationPath=@app/migrations
php yii serve --port=8080
```
Login: `admin` / `admin123`
