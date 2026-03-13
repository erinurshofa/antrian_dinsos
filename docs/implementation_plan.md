# Queue Management System — Dinas Sosial Kota Semarang

Complete production-ready queue management system built with **Yii2 Basic Template**, **MySQL**, **Bootstrap 5**, and **SSE** (Server-Sent Events) for real-time updates.

## User Review Required

> [!IMPORTANT]
> **Real-time Technology**: I will use **SSE (Server-Sent Events)** instead of WebSocket. SSE is simpler to deploy, works natively with PHP/Apache/Nginx without requiring a separate WebSocket daemon (like Ratchet), and is sufficient for one-directional server-to-client updates. This greatly simplifies deployment for government IT teams.

> [!IMPORTANT]
> **Thermal Printer**: Will use **ESC/POS via JavaScript** (`WebUSB` or browser `window.print()` with a thermal-printer-optimized CSS). No server-side printer driver needed. The kiosk machine must be configured with the thermal printer as default printer.

> [!IMPORTANT]
> **Voice Announcement**: Will use the **Web Speech Synthesis API** (built into modern browsers, no external dependency). Indonesian language (`id-ID`) is supported.

> [!WARNING]
> **Yii2 Basic Template**: Since the workspace is empty, I will create the project structure manually (not via Composer) to keep it self-contained. You will need to run `composer install` after to fetch Yii2 core. Alternatively, I can run `composer create-project` if you prefer.

---

## Proposed Changes

### Component 1: Project Skeleton & Configuration

#### [NEW] [composer.json](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/composer.json)
Yii2 basic template dependencies with PHP 8+ requirement.

#### [NEW] [config/db.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/config/db.php)
MySQL database connection configuration.

#### [NEW] [config/web.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/config/web.php)
Main application config: RBAC, URL rules, modules, components.

#### [NEW] [config/console.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/config/console.php)
Console config for migrations.

#### [NEW] [web/index.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/web/index.php)
Application entry point.

---

### Component 2: Database Migrations (8 tables, 3NF)

Schema overview:

| Table | Purpose |
|---|---|
| `offices` | Multi-office support (province, city, office_id) |
| `users` | System users with RBAC role |
| `loket` | Service counters (Loket 1, 2, etc.) |
| `queue_types` | Queue categories (Umum, Rentan) |
| `service_types` | Service categories (Rekomendasi PBI, etc.) |
| `queues` | Queue tickets (U001, R001) |
| `services` | Service records with visitor data |
| `audit_logs` | Security audit trail |

#### [NEW] [migrations/m000001_create_offices_table.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/migrations/m000001_create_offices_table.php)
#### [NEW] [migrations/m000002_create_users_table.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/migrations/m000002_create_users_table.php)
#### [NEW] [migrations/m000003_create_loket_table.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/migrations/m000003_create_loket_table.php)
#### [NEW] [migrations/m000004_create_queue_and_service_types.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/migrations/m000004_create_queue_and_service_types.php)
#### [NEW] [migrations/m000005_create_queues_table.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/migrations/m000005_create_queues_table.php)
#### [NEW] [migrations/m000006_create_services_table.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/migrations/m000006_create_services_table.php)
#### [NEW] [migrations/m000007_create_audit_logs_table.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/migrations/m000007_create_audit_logs_table.php)
#### [NEW] [migrations/m000008_seed_data.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/migrations/m000008_seed_data.php)
Seed default office, admin user, queue types, service types, lokets.

---

### Component 3: Yii2 Models

#### [NEW] [models/Office.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/models/Office.php)
#### [NEW] [models/User.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/models/User.php)
User model with authentication (IdentityInterface), password hashing, RBAC role field.
#### [NEW] [models/Loket.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/models/Loket.php)
#### [NEW] [models/QueueType.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/models/QueueType.php)
#### [NEW] [models/ServiceType.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/models/ServiceType.php)
#### [NEW] [models/Queue.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/models/Queue.php)
Queue model with auto-generation logic, daily reset, prefix formatting.
#### [NEW] [models/Service.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/models/Service.php)
#### [NEW] [models/AuditLog.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/models/AuditLog.php)
#### [NEW] [models/LoginForm.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/models/LoginForm.php)

---

### Component 4: Controllers

#### [NEW] [controllers/SiteController.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/controllers/SiteController.php)
Login, logout, index redirect.

#### [NEW] [controllers/QueueController.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/controllers/QueueController.php)
- `actionKiosk` — Visitor touch screen
- `actionTake` — Generate queue ticket (AJAX)
- `actionDisplay` — Public TV display
- `actionStream` — SSE endpoint for real-time updates

#### [NEW] [controllers/OfficerController.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/controllers/OfficerController.php)
- `actionPanel` — Service officer panel
- `actionCall` — Call next queue (AJAX)
- `actionComplete` — Complete service (AJAX)
- `actionServiceForm` — Service data entry

#### [NEW] [controllers/SecurityController.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/controllers/SecurityController.php)
- `actionPanel` — Security officer panel
- `actionTake` — Generate queue ticket (AJAX)

#### [NEW] [controllers/DashboardController.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/controllers/DashboardController.php)
- `actionIndex` — Leadership dashboard
- `actionStats` — AJAX endpoint for chart data
- `actionExportPdf` — PDF export
- `actionExportExcel` — Excel export

#### [NEW] [controllers/ApiController.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/controllers/ApiController.php)
REST API for queue status, current serving.

---

### Component 5: Views

#### [NEW] [views/layouts/main.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/layouts/main.php)
Bootstrap 5 layout with sidebar navigation, role-based menu.

#### [NEW] [views/layouts/kiosk.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/layouts/kiosk.php)
Fullscreen layout for kiosk/display screens (no nav).

#### [NEW] [views/site/login.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/site/login.php)
Government-themed login page.

#### [NEW] [views/queue/kiosk.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/queue/kiosk.php)
Two large touch buttons (Antrian Umum / Antrian Rentan) + ticket preview.

#### [NEW] [views/queue/display.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/queue/display.php)
TV display with current serving numbers, SSE-driven auto-update.

#### [NEW] [views/security/panel.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/security/panel.php)
Security officer touch panel.

#### [NEW] [views/officer/panel.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/officer/panel.php)
Service officer panel (PANGGIL / SELESAI buttons + service form).

#### [NEW] [views/dashboard/index.php](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/views/dashboard/index.php)
Leadership dashboard with Chart.js charts, filters, export buttons.

---

### Component 6: Assets & Frontend

#### [NEW] [web/css/app.css](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/web/css/app.css)
Custom government-themed styles, kiosk fullscreen styles, display styles.

#### [NEW] [web/js/queue-display.js](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/web/js/queue-display.js)
SSE client for real-time display updates.

#### [NEW] [web/js/officer-panel.js](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/web/js/officer-panel.js)
AJAX calls for PANGGIL/SELESAI + Web Speech API for voice announcement.

#### [NEW] [web/js/kiosk.js](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/web/js/kiosk.js)
Kiosk touch handling + auto-print trigger.

#### [NEW] [web/js/dashboard.js](file:///D:/_PEKERJAAN/ANTRIAN%20-%20DINSOS/web/js/dashboard.js)
Chart.js initialization and filter logic.

---

## Verification Plan

### Manual Verification

Since this is a new standalone Yii2 project, verification will be manual:

1. **Database**: After `composer install` and migration, verify tables exist:
   ```
   php yii migrate
   ```
2. **Login**: Navigate to `/site/login`, login with seeded admin credentials
3. **Kiosk**: Open `/queue/kiosk` — click each button, verify queue number generated
4. **Display**: Open `/queue/display` — verify SSE updates when queue is called
5. **Officer Panel**: Open `/officer/panel` — click PANGGIL, verify voice + display update
6. **Security Panel**: Open `/security/panel` — take queue tickets
7. **Dashboard**: Open `/dashboard/index` — verify charts render with data
8. **Print**: Verify `window.print()` triggers on kiosk (requires connected printer)

> [!NOTE]
> Full end-to-end testing requires the PHP dev server running (`php yii serve`) and a MySQL database. I will provide exact setup commands in the deployment instructions.
