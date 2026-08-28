# Folder Structure & Team Assignment (12 คน)
### Concert Ticketing System — Laravel Modular Architecture

---

## 1. โครงสร้าง Folder (Laravel Modular)

```
concert-ticketing-system/
│
├── app/
│   ├── Console/                     # Artisan commands (เช่น cleanup expired lock/booking)
│   │
│   ├── Events/                      # Domain events เช่น BookingConfirmed, PaymentFailed
│   │
│   ├── Exceptions/                  # Custom exception handlers
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                # Login, Register, Logout
│   │   │   ├── Customer/            # Concert browse, Booking, Payment, Ticket (ฝั่งลูกค้า)
│   │   │   ├── Organizer/           # Concert, Zone, Seat, Sales report
│   │   │   ├── Staff/               # Ticket scan/validate
│   │   │   ├── Admin/               # User mgmt, Anomaly, Audit log
│   │   │   └── Api/                 # AJAX/Fetch endpoints (seat status, booking status ฯลฯ)
│   │   │
│   │   ├── Middleware/              # RBAC role check, rate limiting
│   │   │
│   │   └── Requests/                # Form Request validation
│   │       ├── Auth/
│   │       ├── Concert/
│   │       ├── Booking/
│   │       └── Payment/
│   │
│   ├── Jobs/                        # Queue jobs: GenerateTicket, SendEmail, CleanupExpiredSeatLock
│   │
│   ├── Listeners/                   # Event listeners
│   │
│   ├── Models/                      # Eloquent Models (User, Company, Concert, Zone, Seat,
│   │                                #   Booking, Payment, Ticket, SeatLockLog, Chargeback,
│   │                                #   SystemAnomaly, AuditLog ฯลฯ)
│   │
│   ├── Policies/                    # Authorization policies ต่อ role
│   │
│   ├── Providers/
│   │
│   ├── Services/                    # Business logic หลัก (ตาม module)
│   │   ├── Auth/
│   │   ├── Concert/
│   │   ├── Zone/
│   │   ├── Seat/                    # SeatLockService (Redis)
│   │   ├── Booking/                 # BookingTransactionService
│   │   ├── Payment/                 # PaymentService, ChargebackService
│   │   ├── Ticket/                  # TicketService, QrCodeService
│   │   ├── Notification/
│   │   ├── Organizer/
│   │   ├── Staff/
│   │   ├── Admin/                   # AnomalyDetectionService
│   │   └── AuditLog/
│   │
│   └── Helpers/                     # formatTime.php ฯลฯ
│
├── bootstrap/
│
├── config/                          # database.php, redis.php, cloudflare.php ฯลฯ
│
├── database/
│   ├── migrations/                  # ตาม naming standard (snake_case, พหูพจน์)
│   ├── seeders/
│   └── factories/
│
├── public/
│
├── resources/
│   ├── views/
│   │   ├── layouts/                 # Blade layout กลาง
│   │   ├── components/              # Reusable Blade components
│   │   ├── auth/
│   │   ├── customer/                # concert list, seat map, booking, payment, e-ticket
│   │   ├── organizer/                # dashboard, concert/zone/seat management
│   │   ├── staff/                   # scan ticket UI
│   │   ├── admin/                   # user mgmt, anomaly, audit log dashboard
│   │   └── partials/
│   │
│   ├── js/
│   │   ├── seatmap/                 # Interactive seat map logic
│   │   ├── booking/                 # AJAX booking flow
│   │   └── dashboard/               # Organizer/Admin dashboard charts
│   │
│   └── css/
│
├── routes/
│   ├── web.php
│   ├── api.php
│   └── channels.php
│
├── storage/
│
├── tests/
│   ├── Feature/                     # ทดสอบ booking flow, payment flow, seat lock race condition
│   └── Unit/
│
├── docker/
│   ├── nginx/
│   ├── php/
│   └── redis/
│
├── docker-compose.yml
├── .env.example
└── spec.md                          # เอกสาร spec ที่ทำไว้
```

---

## 2. ตารางแบ่งงานทีม 12 คน (Full-stack Squad ตาม Domain)

> แนวคิด: แบ่งเป็น 6 squad ตาม module/entity ที่สัมพันธ์กัน ให้แต่ละคนดูแลทั้ง backend service + view/controller ที่เกี่ยวข้องในโดเมนของตัวเอง เพื่อลด handoff และให้ทุกคนเข้าใจ business logic ของ module ตัวเองจบในตัว

| # | สมาชิก | Squad | โมดูลที่รับผิดชอบ | งานหลัก | เกี่ยวข้องกับ |
|---|---|---|---|---|---|
| 1 | Member 1 | A — Auth & Access | Authentication, User, RBAC | Login/Register/Logout, Middleware ตรวจ role, Policy/Gate, Password hashing, Session security | Laravel Auth, Middleware, Policies |
| 2 | Member 2 | A — Auth & Access | User (Blade UI), Layout กลาง | หน้า Login/Register UI, Base Blade layout + reusable components, CSRF บน form | Blade, Bootstrap/Tailwind |
| 3 | Member 3 | B — Company/Concert | Company, Concert, Organizer backend | CRUD Company/Concert, Organizer dashboard (backend), sales report query | Eloquent, Service layer |
| 4 | Member 4 | B — Company/Concert | Zone, Seat (data layer) | Model/Migration Zone-Seat, API ส่งสถานะที่นั่งให้ frontend, seat status validation | Eloquent, API Controller |
| 5 | Member 5 | B — Company/Concert | Interactive Seat Map (Frontend) | Seat map UI (HTML/CSS/JS), color-coded status, real-time update ผ่าน AJAX, ป้องกันเลือกที่นั่งไม่ว่าง | JS, Fetch API |
| 6 | Member 6 | C — Booking & Lock | Booking Service, Redis Seat Lock | Redis lock (`SET NX EX`), TTL/auto-expire, verify lock owner, release lock logic | Redis, BookingService |
| 7 | Member 7 | C — Booking & Lock | Booking Controller/Transaction | Booking transaction flow เต็ม (13 ขั้นตอนใน spec), DB transaction + row locking, race condition handling | MySQL Transaction |
| 8 | Member 8 | D — Payment | Payment Service | Payment processing, verify payment status, gateway integration, prevent duplicate processing | Payment gateway API |
| 9 | Member 9 | D — Payment | Payment UI & Chargeback | หน้า payment flow ฝั่งลูกค้า, Chargeback module, notification เมื่อ payment fail/success | Blade, Notification |
| 10 | Member 10 | E — Ticket & Validation | Ticket, QR Code, Validation Log | Generate E-ticket, QR code generation (secure token), ticket validation logic (ใช้ได้ครั้งเดียว) | Queue Job, QR library |
| 11 | Member 11 | E — Ticket & Validation | Staff scan UI, Ticket view | หน้า E-ticket ของลูกค้า, Staff scanning dashboard, validation log display | Blade, JS |
| 12 | Member 12 | F — Admin/Infra/QA | Admin, Anomaly, Audit Log, Notification, DevOps | Admin dashboard (manage user/ban), SystemAnomaly + AuditLog, Notification module, Docker/NGINX/Cloudflare deploy, Rate limiting, coordinate code review & testing | Docker, NGINX, Cloudflare, Laravel Queue |

---

## 3. งานที่ทุกคนต้องรับผิดชอบร่วมกัน (Cross-cutting)

| งาน | รายละเอียด |
|---|---|
| Coding Standard | ทุกคนต้อง follow naming convention + comment format ตาม `spec.md` หัวข้อ 9 อย่างเคร่งครัด |
| Code Review | ทุก PR ต้องมีคน review อย่างน้อย 1 คนก่อน merge (Member 12 เป็นผู้ประสาน) |
| Testing | แต่ละ squad เขียน Feature test ของ module ตัวเอง (โดยเฉพาะ squad C ต้องมี test race condition ของ seat lock) |
| Security | ทุก squad ต้องเช็ค input validation, ห้าม trust frontend, CSRF/XSS ตาม spec หัวข้อ 6 |
| Git/GitHub | Branch แยกตาม squad/feature, ห้าม commit `.env`/secret, PR ต้องอ้างอิง module ที่แก้ |

---

## 4. Squad Dependency (ลำดับงานคร่าว ๆ)

```
Squad A (Auth)  ─┐
                  ├─▶ Squad B (Concert/Zone/Seat) ─▶ Squad C (Booking/Lock) ─▶ Squad D (Payment) ─▶ Squad E (Ticket)
Squad F (Infra) ─┘         (เตรียม DB/Docker ให้ทุก squad ใช้ตั้งแต่ต้น)
```

- **Squad F ต้องเริ่มก่อนทุกทีม** เพื่อเตรียม Docker/DB/Redis environment ให้ squad อื่นพัฒนาได้
- **Squad A** ควรเสร็จ auth/RBAC พื้นฐานก่อน เพราะทุก module ต้องพึ่ง middleware/role
- **Squad B → C → D → E** ไหลตาม business flow ของการจองตั๋วจริง (ดู concert → เลือกที่นั่ง → จอง → จ่ายเงิน → ได้ตั๋ว)
