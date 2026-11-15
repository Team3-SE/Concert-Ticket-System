# โครงสร้างไฟล์ตาม Role (User / Organizer / Admin / Staff)
### Concert Ticket System — TEAM 3 Ticket

> จัดทำโดย 67160292 ธีรเมธ พลสาย · 27/10/2569
> โครงสร้างนี้คือของจริงในโค้ดตอนนี้ (สาขา `refactor/views`) — ไฟล์แผนเดิม `folder-structure-team-assignment.md` ใช้ชื่อโฟลเดอร์ `customer/` แต่ของจริงใช้ `user/`

---

## 1. ภาพรวม 4 ส่วน

| ส่วน | ใครใช้ | URL | views | layout | CSS / JS | หน้าแรกหลัง login |
|---|---|---|---|---|---|---|
| **User** | ลูกค้า + คนที่ยังไม่ login | `/`, `/concerts`, `/booking` … | `views/user/` | `layouts/user`, `layouts/user-account` | `user.css` / `user.js` | หน้าที่ค้างไว้ หรือ `/` |
| **Organizer** | ผู้จัดงาน | `/organizer/*` | `views/organizer/` | `layouts/backoffice` | `backoffice.css` / `backoffice.js` | `/organizer/dashboard` |
| **Admin** | ผู้ดูแลระบบ | `/admin/*` | `views/admin/` | `layouts/backoffice` | `backoffice.css` / `backoffice.js` | `/admin/users` |
| **Staff** | เจ้าหน้าที่หน้างาน | `/staff/*` | `views/staff/` | `layouts/staff` | `backoffice.css` / `backoffice.js` | `/staff/check-in` |

กติกา

- ทุก role ใช้ **หน้าต่างเข้าสู่ระบบเดียวกัน** (ฝั่ง User) กรอกได้ทั้ง อีเมล / เบอร์โทร / ชื่อผู้ใช้ (บัญชี Staff ไม่มีอีเมล ใช้ชื่อผู้ใช้)
- **Organizer / Admin / Staff เปิดหน้าฝั่ง User ไม่ได้** (รวมหน้า Home) — ถ้าเปิดจะถูกพากลับหน้าแรกของ role ตัวเอง
- เปิดหน้าของ role อื่น (เช่น Organizer เปิด `/admin/users`) ก็ถูกพากลับหน้าแรกของ role ตัวเองเหมือนกัน
- ยังไม่ login แล้วเปิดหน้าหลังบ้าน → กลับหน้าแรกพร้อมเปิดหน้าต่าง login → login เสร็จกลับมาหน้าที่จะเข้า
- **Staff ทำได้อย่างเดียวคือตรวจสอบสิทธิ์เข้างาน** หน้าออกแบบให้มือถือก่อน แต่เปิดได้ทุกขนาดจอ
- **Organizer ก็ตรวจสอบสิทธิ์เข้างานได้** (เมนูใน sidebar) ใช้ระบบตรวจตั๋วตัวเดียวกับ Staff (`CheckInController`)

---

## 2. resources/views

```
views/
├── layouts/                         โครงหน้า
│   ├── user.blade.php               ฝั่ง User (navbar + footer)
│   ├── user-account.blade.php       ฝั่ง User แบบมี sidebar (หน้า account)
│   ├── backoffice.blade.php         Organizer + Admin (sidebar ซ้าย + แถบด้านบน ตาม Figma)
│   └── staff.blade.php              Staff (มือถือก่อน ไม่มี sidebar, ออกจากระบบที่รูปโปรไฟล์)
│
├── partials/                        ใช้ร่วมทุกส่วน
│   ├── pagination.blade.php
│   └── backoffice/
│       ├── header.blade.php         ชื่อผู้ใช้ + รูปโปรไฟล์ + ปุ่มเมนู (มือถือ)
│       └── sidebar.blade.php        เมนูซ้าย เปลี่ยนตาม role อัตโนมัติ
│
├── user/                            ── ลูกค้า + คนที่ยังไม่ login ──
│   ├── concerts/    home · index
│   ├── booking/     concert-detail · booking · payment · payment-popups · qr-modal
│   ├── account/     edit-profile · my-ticket · order-history
│   ├── auth/        partials/ (forgot-modal · verify-email-modal · reset-steps — หน้าต่างใน auth-modals)
│   └── partials/    navbar · footer · sidebar · auth-modals · concert-card · zone-overview
│
├── organizer/                       ── Organizer ──
│   ├── dashboard.blade.php          แผงควบคุม (หน้าแรก) — ยังเป็นหน้าเปล่า
│   ├── check-in.blade.php           ตรวจสอบสิทธิ์เข้างาน — ยังเป็นหน้าเปล่า
│   └── concerts/
│       └── manage-images.blade.php  หน้าจัดการรูปคอนเสิร์ต (หน้าเดิม ดูข้อ 8)
│
├── admin/                           ── Admin ──
│   └── users/
│       └── index.blade.php          จัดการผู้ใช้ (หน้าแรก) — ยังเป็นหน้าเปล่า
│
├── staff/                           ── Staff ──
│   └── check-in.blade.php           ตรวจสอบสิทธิ์เข้างาน (หน้าเดียว) — มีหน้าตาแล้ว ยังไม่เชื่อมระบบ
│
├── emails/                          อีเมล (ใช้ร่วม)
│   ├── registration-verification.blade.php
│   └── registration-verification-text.blade.php
│
└── welcome.blade.php
```

---

## 3. resources/css และ resources/js

```
css/                                         js/
├── user.css         ← ไฟล์รวมฝั่ง User       ├── user.js          ← ไฟล์รวมฝั่ง User
├── backoffice.css   ← ไฟล์รวมหลังบ้าน       ├── backoffice.js    ← ไฟล์รวมหลังบ้าน
│                      (Organizer/Admin/Staff)│                      (Organizer/Admin/Staff)
│                                            │
├── shared/          ใช้ทั้งสองไฟล์รวม        ├── shared/          ใช้ทั้งสองไฟล์รวม
│   ├── base.css                             │   ├── dropdown.js
│   ├── pagination.css                       │   ├── pagination.js
│   └── password-toggle.css                  │   ├── password-toggle.js
│                                            │   └── check-in.js      สแกน/ตรวจตั๋ว (Organizer + Staff)
├── user/                                    │
│   ├── concerts/  home · index · search     ├── user/
│   ├── booking/   booking · payment ·       │   ├── navbar.js
│   │              qr-modal · payment-popups │   ├── auth-modal.js
│   ├── account/   account                   │   ├── concert-search.js
│   ├── auth/      verify-notice             │   └── my-ticket.js
│   └── partials/  navbar · sidebar ·        │
│                  auth-modal                ├── backoffice/
│                                            │   └── layout.js        เปิด/ปิด sidebar บนมือถือ
├── backoffice/                              │
│   └── layout.css   โครงหลังบ้าน + .bo-title ├── organizer/
│                    .bo-card .bo-alert      │   └── dashboard.js
│                    .bo-empty               │
├── organizer/                               └── admin/
│   ├── dashboard.css                            └── users.js
│   ├── check-in.css
│   └── concerts/manage-images.css
├── admin/
│   └── users.css
└── staff/
    ├── layout.css
    └── check-in.css
```

- ไฟล์ของหน้า Organizer / Admin ที่มีอยู่ตอนนี้ (`dashboard`, `check-in`, `users`) เป็น**ไฟล์ตั้งต้น** มีหัวไฟล์และ import ในไฟล์รวมให้แล้ว เขียนต่อได้เลย
- CSS ของแต่ละหน้าครอบด้วย class ของ `<body>`: `page-organizer-dashboard`, `page-organizer-check-in`, `page-admin-users`
- JS ของแต่ละหน้าทำงานเฉพาะหน้าที่มี attribute ของตัวเอง: `[data-organizer-dashboard]`, `[data-admin-users]`, `[data-check-in]`
- เพิ่มหน้าใหม่: สร้างไฟล์ในโฟลเดอร์ของ role แล้ว `@import` ใน `backoffice.css` / `import` ใน `backoffice.js` (มีที่เว้นไว้แล้ว)
- รายละเอียดว่าไฟล์ไหนใช้กับหน้าไหน ดู `resources/css/README.md`

---

## 4. routes, controller, middleware

```
routes/
├── web.php          หน้าฝั่ง User + เข้าสู่ระบบ/ออกจากระบบ (require 3 ไฟล์ด้านล่าง)
├── organizer.php    prefix /organizer   ชื่อ route organizer.*   middleware auth + role:ORGANIZER
├── admin.php        prefix /admin       ชื่อ route admin.*       middleware auth + role:ADMIN
└── staff.php        prefix /staff       ชื่อ route staff.*       middleware auth + role:STAFF
```

```
app/Http/
├── Middleware/
│   └── EnsureRole.php                    ตรวจ role ทุก route (alias "role" ที่ bootstrap/app.php)
│
└── Controllers/
    ├── Controller.php                    คลาสแม่ของทุก controller
    │
    │   ── ใช้ร่วมทุก role ──
    ├── AuthController.php                สมัครสมาชิก, เข้าสู่ระบบ (พาไปหน้าแรกของ role), ออกจากระบบ
    ├── CheckInController.php             ตรวจสอบสิทธิ์เข้างาน: หน้า Organizer + หน้า Staff (+ API ตรวจตั๋วที่จะทำ)
    │
    ├── User/                             ── ฝั่ง User ──
    │   ├── HomeController.php            หน้า Home (แบนเนอร์ คอนเสิร์ตแนะนำ)
    │   ├── ConcertController.php         คอนเสิร์ตทั้งหมด + ช่องค้นหาแนะนำ
    │   ├── BookingController.php         รายละเอียดคอนเสิร์ต, เลือกโซน/ที่นั่ง, สร้างการจอง (ล็อกที่นั่ง)
    │   ├── PaymentController.php         หน้าชำระเงิน, QR, สถานะ/ยกเลิกการจอง
    │   ├── TicketController.php          ตั๋วของฉัน (e-ticket)
    │   ├── OrderController.php           ประวัติการสั่งซื้อ
    │   ├── ProfileController.php         แก้ไขข้อมูลส่วนตัว + เปลี่ยนรหัสผ่าน (ใช้ UserController ด้านล่าง)
    │   ├── UserController.php            logic ข้อมูลผู้ใช้ที่ ProfileController เรียกใช้ (ไม่ได้ผูก route ตรง)
    │   └── EmailVerificationController.php  ยืนยันอีเมลหลังสมัครสมาชิก
    │
    ├── Organizer/                        ── Organizer ──
    │   ├── DashboardController.php       แผงควบคุม
    │   └── ConcertImageController.php    จัดการรูปคอนเสิร์ต (หน้าเดิม /manage-images ยังไม่ได้จำกัด role)
    │
    ├── Admin/                            ── Admin ──
    │   └── UserController.php            จัดการผู้ใช้
    │
    │   ── Staff ── ไม่มีโฟลเดอร์ของตัวเอง เพราะมีหน้าเดียว (ใช้ CheckInController ด้านบน)
    │
    └── Dev/ModelTesterController.php     เครื่องมือ dev /dev/models (ถ้ายังไม่ถูกลบ)

app/Models/AppUser.php                    homeUrl() หน้าแรกของ role, areaPrefix(), displayName()
```

controller ใหม่ให้สร้างในโฟลเดอร์ของส่วนนั้น (`User/`, `Organizer/`, `Admin/`) โดย namespace ต้องตรงกับโฟลเดอร์ และต้อง `use App\Http\Controllers\Controller;`
เช่น `app/Http/Controllers/Organizer/ConcertManageController.php` → `namespace App\Http\Controllers\Organizer;`
(มี `UserController` สองตัว: `User\UserController` ของฝั่งลูกค้า กับ `Admin\UserController` ของหน้าจัดการผู้ใช้ — ใช้คนละ namespace จึงไม่ชนกัน)

การใช้ middleware `role`

| เขียนใน route | ความหมาย |
|---|---|
| `role:GUEST,USER` | ลูกค้า + คนที่ยังไม่ login (หน้าฝั่ง User ที่ดูได้โดยไม่ต้อง login) |
| `auth` + `role:USER` | ลูกค้าที่ login แล้วเท่านั้น (ตั๋วของฉัน, ประวัติการสั่งซื้อ, จอง/ชำระเงิน) |
| `role:ORGANIZER` | Organizer เท่านั้น |
| `role:ORGANIZER,STAFF` | Organizer หรือ Staff (เช่น API ตรวจตั๋วที่ทั้งสองหน้าใช้ร่วมกัน) |

---

## 5. เข้าสู่ระบบแล้วไปไหน

```
หน้าต่างเข้าสู่ระบบเดียวกัน (อีเมล / เบอร์โทร / ชื่อผู้ใช้)
        │
        ├── USER       →  หน้าที่ค้างไว้ (เช่นหน้าจองบัตร) หรือ /
        ├── ORGANIZER  →  /organizer/dashboard
        ├── ADMIN      →  /admin/users
        └── STAFF      →  /staff/check-in

ออกจากระบบ (ทุก role)  →  /
```

---

## 6. เมนูใน sidebar ที่รอ route อยู่

เมนูใน `partials/backoffice/sidebar.blade.php` ผูกกับชื่อ route ไว้แล้ว ตอนนี้เมนูที่ยังไม่มี route จะเป็นสีจางกดไม่ได้
**เพิ่ม route ตามชื่อในตารางแล้วเมนูจะกดได้เองทันที** ไม่ต้องแก้ sidebar

| Role | เมนู | ชื่อ route | สถานะ |
|---|---|---|---|
| Organizer | แผงควบคุม (Dashboard) | `organizer.dashboard` | มีแล้ว (หน้าเปล่า) |
| Organizer | จัดการคอนเสิร์ต | `organizer.concerts.index` | รอทำ |
| Organizer | รายการผู้ซื้อตั๋ว | `organizer.buyers.index` | รอทำ |
| Organizer | จัดการเจ้าหน้าที่ | `organizer.staff.index` | รอทำ |
| Organizer | ตรวจสอบสิทธิ์เข้างาน | `organizer.check-in` | มีแล้ว (หน้าเปล่า) |
| Admin | จัดการผู้ใช้ | `admin.users.index` | มีแล้ว (หน้าเปล่า) |
| Admin | รายการความผิดปกติ | `admin.incidents.index` | รอทำ |
| Admin | ตรวจสอบการจอง | `admin.bookings.index` | รอทำ |
| Admin | ประวัติการใช้งาน | `admin.audit-logs.index` | รอทำ |
| Admin | จัดการองค์กร | `admin.organizations.index` | รอทำ |
| Staff | ตรวจสอบสิทธิ์เข้างาน | `staff.check-in` | มีแล้ว (ยังไม่เชื่อมระบบ) |

---

## 7. วิธีเพิ่มหน้าใหม่ (ตัวอย่าง: Organizer → จัดการคอนเสิร์ต)

**1) route** — `routes/organizer.php` ในกลุ่มที่มีอยู่ (prefix/ชื่อ/role ใส่ให้แล้ว)

```php
Route::get('/concerts', [ConcertManageController::class, 'index'])->name('concerts.index');
// URL จริง = /organizer/concerts, ชื่อจริง = organizer.concerts.index
```

**2) controller** — `app/Http/Controllers/Organizer/ConcertManageController.php`

```php
namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;

class ConcertManageController extends Controller
{
    public function index()
    {
        return view('organizer.concerts.index');
    }
}
```

**3) view** — `resources/views/organizer/concerts/index.blade.php`

```blade
@extends('layouts.backoffice')

@section('title', 'จัดการคอนเสิร์ต')

@section('content')
  <h1 class="bo-title">จัดการคอนเสิร์ต</h1>
  <div class="bo-card"> ... </div>
@endsection
```

**4) CSS (ถ้ามี)** — สร้าง `resources/css/organizer/concerts.css` แล้วเพิ่ม `@import './organizer/concerts.css';` ใน `backoffice.css`

**5) build** — `docker-compose run --rm node npm run build`

---

## 8. ข้อควรรู้

- **ดึงโค้ดใหม่แล้วต้อง build ใหม่ทุกครั้ง** — ไฟล์รวมเปลี่ยนชื่อจาก `site.css/js` เป็น `user.css/js` และเพิ่ม `backoffice.css/js` ถ้าไม่ build ทุกหน้าจะขึ้น error `Unable to locate file in Vite manifest`
- **กล้องสแกน QR** — เบราว์เซอร์เปิดกล้องได้เฉพาะเว็บที่เป็น HTTPS หรือ `localhost` ถ้าเปิดจากมือถือผ่าน `http://192.168.x.x:8080` จะใช้ได้แค่ช่องกรอกรหัสตั๋ว (เดโมด้วยมือถือจริงต้องใช้ ngrok / Cloudflare Tunnel)
- **สิทธิ์ตรวจตั๋ว** (ต้องตรวจฝั่งเซิร์ฟเวอร์ตอนทำระบบ) — Staff ตรวจได้เฉพาะคอนเสิร์ตที่ถูกมอบหมาย (`staff_concert`), Organizer ตรวจได้เฉพาะคอนเสิร์ตขององค์กรตัวเอง
- **หน้า `manage-images`** — ย้าย view ไป `organizer/concerts/` และ controller ไป `Organizer/` แล้ว แต่ route `/manage-images` ยังเปิดได้โดยไม่ต้อง login และยังใช้ `layouts/user` อยู่ ควรย้ายเข้า `routes/organizer.php` + `layouts/backoffice` ตอนทำหน้าจัดการคอนเสิร์ต
- **ดีไซน์** — Figma ไฟล์ Team3: หน้า `Organizer` ส่วน `Organizer`, หน้า `Admin` ส่วน `Admin` (ส่วนที่ชื่อขึ้นต้นด้วย "กองขี้" เป็นแบบร่าง)

---

## 9. บัญชีทดสอบ (จาก `database/mock_demo_all.sql`)

ทุกบัญชีใช้รหัสผ่าน `Password123!`

| Role | เข้าสู่ระบบด้วย |
|---|---|
| User | `user1@example.com` (หรือ user2–user4) |
| Organizer | `organizer@gmm.local`, `organizer@bec.local`, `organizer@movelive.local` |
| Admin | `admin@concert.local` |
| Staff | `gmm-gate-1`, `gmm-gate-2`, `bec-gate-1`, `mvl-gate-1` (ชื่อผู้ใช้) |
