# Concert Ticket System — Project Specification

> รวม **Technology Stack Specification (v1.0.1)** และ **Coding Standard (v1.0.1)**
> ของโปรเจกต์ระบบจองตั๋วคอนเสิร์ต (Team 3, Software Engineering, Burapha University)
> ใช้เป็นเอกสารอ้างอิงหลัก (single source of truth) สำหรับการพัฒนา รวมถึงให้ AI agent ใช้ประกอบการเขียนโค้ด

---

## สารบัญ

1. [Objective](#1-objective)
2. [Technology Stack](#2-technology-stack)
3. [Application Architecture & Modules](#3-application-architecture--modules)
4. [Seat Locking & Booking Rules (Redis + MySQL)](#4-seat-locking--booking-rules-redis--mysql)
5. [Payment Rules](#5-payment-rules)
6. [Security Requirements](#6-security-requirements)
7. [Logging & Error Handling](#7-logging--error-handling)
8. [Deployment](#8-deployment)
9. [Coding Standard](#9-coding-standard)
   - [9.1 การตั้งชื่อไฟล์และคลาส](#91-การตั้งชื่อไฟล์และคลาส)
   - [9.2 การตั้งชื่อ Component](#92-การตั้งชื่อ-component)
   - [9.3 การตั้งชื่อฟังก์ชัน](#93-การตั้งชื่อฟังก์ชัน)
   - [9.4 การตั้งชื่อตัวแปร](#94-การตั้งชื่อตัวแปร)
   - [9.5 มาตรฐานเกี่ยวกับฐานข้อมูล](#95-มาตรฐานเกี่ยวกับฐานข้อมูล)
   - [9.6 การเขียนคอมเมนต์](#96-การเขียนคอมเมนต์)
10. [Development Constraints (AI Agent Checklist)](#10-development-constraints-ai-agent-checklist)

---

## 1. Objective

พัฒนา **Concert Ticket System** ตาม business requirement ที่กำหนด โดย **ต้องใช้ tech stack ตามที่ระบุไว้เท่านั้น** ห้ามเปลี่ยนเทคโนโลยีใด ๆ เว้นแต่จะได้รับคำสั่งชัดเจน

ต้องให้ความสำคัญกับ:

- High Performance
- Security
- Reliability
- Maintainability
- Scalability
- Clean Architecture
- Production-ready code

---

## 2. Technology Stack

| ชั้น (Layer) | เทคโนโลยี |
|---|---|
| Frontend / Templating | Laravel Blade, HTML5, CSS3, JavaScript, Bootstrap, Tailwind CSS |
| Backend | Laravel (PHP) |
| ORM | Eloquent ORM + Laravel Migrations |
| Database (persistent) | MySQL |
| Cache / Temporary data / Queue | Redis |
| File & Asset Storage | Cloudflare |
| Background Jobs | Laravel Queue / Jobs (Redis เป็น queue backend ได้) |
| Source Control | GitHub |
| Deployment | Docker, NGINX, Cloudflare |

**หลักการสำคัญ:**
- Laravel Blade เป็น templating engine หลัก, ใช้ JavaScript เฉพาะจุดที่ต้อง interactive
- Bootstrap + Tailwind ใช้ร่วมกันได้ แต่ห้าม style ซ้ำซ้อน/ชนกัน
- **Frontend ห้ามเป็น source of truth ของสถานะที่นั่ง** — ต้อง validate ที่ backend เสมอ
- **MySQL = permanent business data เท่านั้น**, **Redis = temporary/high-speed data เท่านั้น** ห้ามใช้ Redis เป็น primary database

---

## 3. Application Architecture & Modules

### Backend Architecture (Modular Laravel)

ใช้:
- Controllers (บาง, ไม่ใส่ business logic)
- Services (ที่เก็บ business logic หลัก)
- Models (Eloquent)
- Middleware
- Form Requests (validation)
- Policies / Gates (authorization)
- Jobs / Events / Listeners

### Main Modules

Authentication, Users, Companies, Concerts, Zones, Seats, Bookings, Payments, Tickets, Notifications, Organizer, Staff, Admin, Audit Logs

### Communication

- Standard page → Blade server-side rendering
- Async operation (seat selection, seat status check, booking, payment status, ticket validation, dashboard data) → JS Fetch/AJAX
- ห้าม poll server โดยไม่จำเป็น

### Roles (RBAC)

| Role | สิทธิ์หลัก |
|---|---|
| Guest | เข้าดูข้อมูลทั่วไป |
| Customer | browse concert, select seat, create booking, payment, view own tickets |
| Event Organizer | จัดการ concert/zone/seat ของบริษัทตัวเอง, ดู booking & sales |
| Staff | ฟังก์ชันที่ได้รับมอบหมาย, validate ticket ที่มีสิทธิ์ |
| Admin | manage users, review audit logs, review anomalies, suspend/ban user |

---

## 4. Seat Locking & Booking Rules (Redis + MySQL)

### Seat Lock (Redis)

```
SET seat:{seatId} {userId} NX EX 600
```

- TTL = 10 นาที, auto expire
- Atomic lock ป้องกัน 2 คนล็อกที่นั่งเดียวกัน
- ผูก lock กับ user, ปล่อย lock อัตโนมัติเมื่อหมดอายุ หรือเมื่อ cancel

### Seat Status Flow

```
Available → Locked → Booked
Locked → Expired/Cancelled → Available
```

**กฎเหล็ก:** ห้ามมี booking สำเร็จซ้ำ 2 ครั้งสำหรับที่นั่งเดียวกันในคอนเสิร์ตเดียวกันเด็ดขาด

### Booking Transaction Flow

1. Verify authenticated user
2. Verify booking information
3. Verify seat exists
4. Verify Redis seat lock
5. Verify user เป็นเจ้าของ lock
6. Verify seat ยัง available ใน MySQL
7. Start MySQL transaction
8. Lock/check seat records (row locking)
9. Create/confirm booking
10. Associate seats กับ booking
11. Update status ที่เกี่ยวข้อง
12. Commit transaction
13. ลบ Redis lock เมื่อเหมาะสม

หากขั้นตอนวิกฤตล้มเหลว → **rollback ทั้งหมด**, ห้ามสร้าง partial booking

---

## 5. Payment Rules

**Payment Success:** verify result → confirm payment → confirm booking → mark seat booked → generate ticket + QR → send confirmation → persist ข้อมูล payment

**Payment Failed:** mark failed → update booking ตาม business rule → release seat lock (ถ้าเหมาะสม) → notify client

- ป้องกัน duplicate payment processing
- **ห้าม trust payment success จาก frontend เด็ดขาด** ต้อง verify ที่ backend เสมอ

---

## 6. Security Requirements

- Laravel Authentication + Session-based auth
- RBAC ผ่าน Policies/Gates
- CSRF protection ทุก state-changing request (POST/PUT/PATCH/DELETE) — ห้ามปิด CSRF ทั้งระบบ, AJAX/Fetch ต้องแนบ CSRF token
- Input validation ผ่าน Form Request, Output escaping, SQL Injection / XSS protection
- Password hashing (ห้ามเก็บ plaintext เด็ดขาด)
- Rate limiting endpoint สำคัญ: login, register, booking, payment, ticket validation, password reset
- Secure cookies, session security, HTTPS in production
- ห้าม expose stack trace / DB credential / internal path / env variable ใน production

### QR Code / Ticket Validation

- QR ต้องอ้างอิง secure ticket identifier/token เท่านั้น ห้ามฝังข้อมูล sensitive
- Validate: ticket มีอยู่จริง, concert ตรงกัน, ยังไม่ถูกใช้, ยังไม่ถูกยกเลิก
- Validate ได้ **ครั้งเดียวเท่านั้น**, ผลลัพธ์ persist ลง MySQL และต้อง log ทุกครั้ง

---

## 7. Logging & Error Handling

**ต้อง log:** login, booking, payment, ticket validation, admin activity, organizer activity, security event, system error สำคัญ
Audit log ต้องระบุ: Actor, Action, Target, Timestamp, Result

**ห้าม log:** password, session secret, credential, full payment data, ข้อมูลส่วนบุคคลที่ไม่จำเป็น

**Error handling:** ต้องจัดการ validation/authn/authz error, resource not found, seat unavailable/lock expired, booking conflict, payment failure, DB failure, unexpected error — โดยไม่ expose รายละเอียด internal ใน production

---

## 8. Deployment

```
User → Cloudflare → NGINX → Laravel/PHP → MySQL + Redis
```

- Docker: ให้ environment สม่ำเสมอ (Laravel/PHP, NGINX, Redis เป็น container ได้; MySQL จะ containerize หรือแยก host ก็ได้ แต่ข้อมูลถาวรต้องใช้ persistent storage เสมอ — ห้ามพึ่ง container-local storage)
- NGINX: web server / reverse proxy / static file server / routing
- Cloudflare: DNS, HTTPS, CDN, cache, DDoS protection

---

## 9. Coding Standard

> อ้างอิงจาก Coding Standard v1.0.1 (11 ก.ค. 2569, แก้ไข 31 ก.ค. 2569) — บริบท Laravel MVC (Controllers, Models, Views, Config, Helpers)

### 9.1 การตั้งชื่อไฟล์และคลาส

| รายการ | Convention | ตัวอย่าง |
|---|---|---|
| Controller (ไฟล์ + คลาส) | PascalCase + ต่อท้ายด้วย `Controller` | `UserController.php` / `UserController` |
| Model (ไฟล์) | ชื่อ Entity แบบ PascalCase | `CustomerDetail.php` |

### 9.2 การตั้งชื่อ Component

| รายการ | Convention | ตัวอย่าง |
|---|---|---|
| Component | PascalCase | `MyComponent` |
| Middleware/Hooks | ขึ้นต้นพิมพ์เล็ก ตามด้วย PascalCase | `UseCustomHook.ts` |
| Utility Function (ไฟล์) | camelCase + ปิดท้ายด้วย `util` | `formatTime.php` |
| Folder | ขึ้นต้นพิมพ์ใหญ่, ถ้า >2 คำเป็น snake_case, เติม `s` ถ้ามีมากกว่า 1 ไฟล์ | `components` |
| Service (ไฟล์) | PascalCase + ต่อท้ายด้วย `Service` | `FormatTimeService.php` |
| Route (ไฟล์) | camelCase + ปิดท้ายด้วย `Routes` | `web.php` |
| Config (ไฟล์) | camelCase + ปิดท้ายด้วย `Config` | `database.php` |

### 9.3 การตั้งชื่อฟังก์ชัน

- ใช้ **camelCase** เสมอ (ทั้งใน Service และ Component)
- ถ้ามากกว่า 1 คำ ให้ตัวอักษรตัวถัดไปขึ้นต้นด้วยพิมพ์ใหญ่ (เช่น `getUserById`, `getUserByEmail`, `energyChart`)
- ชื่อฟังก์ชันต้องสื่อความหมายชัดเจน

### 9.4 การตั้งชื่อตัวแปร

- ตัวแปรทั่วไป/Array: **camelCase**
- ชื่อต้องสอดคล้องกับข้อมูลที่เก็บ

### 9.5 มาตรฐานเกี่ยวกับฐานข้อมูล

| รายการ | กฎ | ตัวอย่าง |
|---|---|---|
| ชื่อฐานข้อมูล | พิมพ์เล็กทั้งหมด + `_` | `user_db` |
| ชื่อตาราง | พิมพ์เล็กทั้งหมด, ขึ้นต้นด้วยตัวย่อระบบ, คั่นด้วย `_`, ใช้พหูพจน์ | `ic_monitors` |
| ชื่อฟิลด์ | snake_case | `id`, `can_login` |
| Comment ตาราง/ฟิลด์ | **บังคับทุกตาราง ทุกฟิลด์ ไม่มีข้อยกเว้น** — อธิบายว่าเก็บอะไร/ใช้ทำอะไร; ฟิลด์ FK ต้องระบุที่มา (ตาราง/DB ต้นทาง) | `monitor_id` = รหัสประจำ monitor (ตาราง histories) |
| Config .env | พิมพ์ใหญ่ทั้งหมด, snake_case, ใช้ชื่อย่อหัวข้อหลัก + `_` + ชื่อข้อมูล, ห้ามตั้งซ้ำ, ห้ามแก้/ลบโดยพลการ (ต้องปรึกษาหัวหน้าทีมก่อน) | `DB_USERNAME`, `DB_PASSWORD` |

### 9.6 การเขียนคอมเมนต์

**หลักทั่วไป:** ทุกบรรทัดใน comment block ให้ขึ้นต้นด้วย `*` + เว้นวรรค 1 ครั้ง (ยกเว้นบรรทัดเปิด `/**` และปิด `*/`)

**หัวไฟล์คลาส (Controller / Service / Model)** — บังคับทุกไฟล์:
```
/**
 * UserController
 * manage user-related operations such as registration, and login
 * Author : Pimonphan Mamak
 * Created : 09-03-2569
 */
```
บรรทัด: (1) เปิด `/**` → (2) ชื่อคลาส → (3) หน้าที่คร่าว ๆ → (4) `Author :` → (5) `Created : DD/MM/YYYY` → (6) ปิด `*/`

**หัวฟังก์ชัน (Controller / Service / Model / Component)** — บังคับทุกฟังก์ชัน:
```
/**
 * getUserById(userId)
 * retrieve user details based on the given user ID.
 * param : userId
 * return : User details
 */
```
บรรทัด: (1) เปิด `/**` → (2) ชื่อฟังก์ชัน+พารามิเตอร์ → (3) หน้าที่คร่าว ๆ → (4) `param :` → (5) `return :` → (6) ปิด `*/`

**หัวไฟล์ Component:**
```
/**
 * sendVerificationEmail(userEmail)
 * send a verification email to the specified user.
 * Author : Pimonphan Mamak
 * Created : 09/03/2569
 */
```

**Style / CSS:**
- บรรทัดเดียว: `/* Set the background color for the main container */`
- หลายบรรทัด: เปิด `/*`, แต่ละบรรทัดขึ้นด้วย `*`, ปิด `*/`

**JavaScript:**
- บรรทัดเดียว: `// เนื้อหา` เช่น `// when hide button get click`
- หลายบรรทัด: ใช้หลักเดียวกับ Style/CSS (`/* ... */`)

---

## 10. Development Constraints (AI Agent Checklist)

เมื่อ AI agent เขียนโค้ดให้โปรเจกต์นี้ ต้อง:

- [ ] ใช้ Laravel + PHP เป็น backend เท่านั้น, Blade เป็น server-rendered frontend
- [ ] ใช้ MySQL สำหรับ persistent data, Redis เฉพาะ cache/lock/queue/session/ephemeral data
- [ ] ใช้ Eloquent + Migration, หลีกเลี่ยง raw SQL เว้นแต่จำเป็นจริง ๆ
- [ ] Controller ต้องบาง — business logic อยู่ใน Service class
- [ ] ใช้ Form Request validation, ห้าม trust client-side validation
- [ ] ใช้ database transaction + row locking สำหรับ operation ที่กระทบ seat/payment
- [ ] ปฏิบัติตาม naming convention ในหัวข้อ 9 ทุกจุด (ไฟล์/คลาส/ฟังก์ชัน/ตัวแปร/DB)
- [ ] เขียน comment กำกับหัวไฟล์และหัวฟังก์ชันตามรูปแบบหัวข้อ 9.6 **ทุกไฟล์ ทุกฟังก์ชัน ไม่มีข้อยกเว้น**
- [ ] ทุกตาราง/ฟิลด์ใน migration ต้องมี comment กำกับความหมาย
- [ ] ห้าม hardcode ค่า config — ใช้ environment variable (.env) ตามมาตรฐานข้อ 9.5
- [ ] ห้าม commit `.env`, credential, secret ใด ๆ ขึ้น GitHub
- [ ] ห้ามเก็บ password เป็น plaintext
- [ ] ป้องกัน race condition ในการจองที่นั่งเสมอ (Redis lock + MySQL transaction)
- [ ] ไม่ expose sensitive error/stack trace ใน production
- [ ] เขียนโค้ดให้ขยาย (extend) ต่อได้โดยไม่ต้องปรับสถาปัตยกรรมใหญ่
