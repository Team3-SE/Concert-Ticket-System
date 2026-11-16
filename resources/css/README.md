# CSS ของเว็บ (resources/css)

เว็บแบ่งเป็น 4 ส่วนตาม role และโฟลเดอร์จัดตาม `resources/views` — แก้หน้าในโฟลเดอร์ไหน ให้ไปแก้ CSS ในโฟลเดอร์ชื่อเดียวกัน

| ส่วน | views | CSS / JS | layout | URL |
|---|---|---|---|---|
| User (ลูกค้า + ยังไม่ login) | `views/user/*` | `user.css` / `user.js` | `layouts/user`, `layouts/user-account` | `/`, `/concerts`, `/booking` … |
| Organizer | `views/organizer/*` | `backoffice.css` / `backoffice.js` | `layouts/backoffice` | `/organizer/*` |
| Admin | `views/admin/*` | `backoffice.css` / `backoffice.js` | `layouts/backoffice` | `/admin/*` |
| Staff | `views/staff/*` | `backoffice.css` / `backoffice.js` | `layouts/staff` (มือถือก่อน) | `/staff/*` |

ทุก role ใช้หน้าต่างเข้าสู่ระบบเดียวกัน (ฝั่ง User) แล้วระบบพาไปหน้าแรกของ role นั้น (`AppUser::homeUrl()`)
และ Organizer/Admin/Staff เปิดหน้าฝั่ง User ไม่ได้ (middleware `role` ใน `app/Http/Middleware/EnsureRole.php`)

## ทำงานยังไง

1. เขียน CSS แยกไฟล์ในโฟลเดอร์ย่อยด้านล่าง
2. ไฟล์รวม 2 ไฟล์ `@import` ไฟล์ย่อยตามลำดับ: `user.css` (ฝั่ง User) และ `backoffice.css` (หลังบ้าน)
3. Vite build ไฟล์รวมแต่ละไฟล์เป็น CSS ไฟล์เดียวไปไว้ที่ `public/build/` (มีเลขเวอร์ชันกัน browser cache ให้เอง)
4. layout โหลดด้วย `@vite` — `layouts/user` โหลด `user.css` + `user.js`, `layouts/backoffice` และ `layouts/staff` โหลด `backoffice.css` + `backoffice.js`

**แก้ไฟล์แล้วต้อง build ใหม่** — `docker-compose run --rm node npm run build`
(หรือถ้ามี Node.js ในเครื่อง: `npm run dev` ระหว่างแก้)

**เพิ่มไฟล์ CSS ใหม่** — สร้างไฟล์ในโฟลเดอร์ที่ตรงกับ view แล้วเพิ่มบรรทัด `@import` ในไฟล์รวมของส่วนนั้น
(หน้า Organizer/Admin มีที่เว้นไว้ใน `backoffice.css` แล้ว)

> ห้ามแก้ไฟล์ใน `public/build/` ด้วยมือ — ถูกสร้างใหม่ทุกครั้งที่ build (และไม่ขึ้น git)

## ไฟล์ไหนใช้กับอะไร

| ไฟล์ | ใช้กับ (resources/views) | หน้าที่ |
|---|---|---|
| `shared/base.css` | ทุกหน้า (ทั้งสองไฟล์รวม) | สีหลัก ฟอนต์ footer อยู่ล่างสุด |
| `shared/pagination.css` | `partials/pagination` | ตัวแบ่งหน้า (ใช้ได้ทุกส่วน) |
| `shared/password-toggle.css` | `user/partials/auth-modals`, `user/account/edit-profile` | ปุ่มรูปตาในช่องรหัสผ่าน (ใช้ได้ทุกส่วน) |
| `user/partials/navbar.css` | `user/partials/navbar` | แถบด้านบน ชื่อ/รูปโปรไฟล์ |
| `user/partials/sidebar.css` | `user/partials/sidebar` | เมนูซ้าย (หน้า account) |
| `user/partials/auth-modal.css` | `user/partials/auth-modals` | หน้าต่างเข้าสู่ระบบ/สมัครสมาชิก (ทุกหน้า ตอนยังไม่ login) |
| `user/auth/verify-notice.css` | `user/auth/partials/verify-email-modal`, `forgot-modal` | หน้าต่างแจ้งให้ตรวจอีเมลหลังสมัครสมาชิก + กล่อง/ปุ่มที่หน้าต่างลืมรหัสผ่านใช้ร่วม (ครอบด้วย `.auth-flow`) |
| `user/auth/password-reset.css` | `user/auth/partials/forgot-modal`, `reset-steps` | หน้าต่างลืมรหัสผ่าน 3 ขั้น (ครอบด้วย `.auth-flow`) |
| `user/concerts/home.css` | `user/concerts/home`, `user/partials/concert-card` | หน้า Home + การ์ดคอนเสิร์ต |
| `user/concerts/index.css` | `user/concerts/index` | หน้าคอนเสิร์ตทั้งหมด (Show All) |
| `user/concerts/search.css` | `user/concerts/home`, `user/concerts/index` | ช่องค้นหา + รายการแนะนำ |
| `user/booking/booking.css` | `user/booking/booking` | หน้าเลือกโซน/ที่นั่ง |
| `user/booking/payment.css` | `user/booking/payment` | หน้าชำระเงิน |
| `user/booking/qr-modal.css` | `user/booking/qr-modal` | popup ชำระเงินด้วย QR |
| `user/booking/payment-popups.css` | `user/booking/payment-popups` | popup ยืนยัน/ผลการชำระเงิน |
| `user/account/account.css` | `layouts/user-account`, `user/account/*` | หน้า account (มี sidebar) |
| `organizer/concerts/manage-images.css` | `organizer/concerts/manage-images` | หน้าจัดการรูปคอนเสิร์ต (ยังใช้ `layouts/user` จึงรวมอยู่ใน `user.css`) |
| `organizer/dashboard.css` | `organizer/dashboard` | แผงควบคุมของ Organizer (ไฟล์ตั้งต้น) |
| `organizer/check-in.css` | `organizer/check-in` | ตรวจสอบสิทธิ์เข้างานของ Organizer (ไฟล์ตั้งต้น) |
| `admin/users.css` | `admin/users/index` | จัดการผู้ใช้ของ Admin (ไฟล์ตั้งต้น) |
| `backoffice/layout.css` | `layouts/backoffice`, `partials/backoffice/*` | โครงหลังบ้าน Organizer + Admin: sidebar, แถบด้านบน, `.bo-title`, `.bo-card`, `.bo-empty` |
| `staff/layout.css` | `layouts/staff` | โครงหน้า Staff (โทนมืด มือถือก่อน) |
| `staff/check-in.css` | `staff/check-in` | หน้าตรวจสอบสิทธิ์เข้างานของ Staff |
| `app.css` | `welcome` | หน้า welcome ของ Laravel (Tailwind) — ไม่เกี่ยวกับหน้าอื่น |

## class บน `<body>` (กันสไตล์ชนกันระหว่างหน้า)

บางหน้าใช้ชื่อ class เดียวกันแต่หน้าตาต่างกัน (เช่น `.card`, `.field`) ไฟล์ของหน้านั้นจึงครอบทุก rule ด้วย class ของ `<body>`
ตั้งค่าใน view ด้วย `@section('body_class', '...')`

| body class | ไฟล์ |
|---|---|
| `page-home` | `user/concerts/home.css` |
| `page-concerts` | `user/concerts/index.css` |
| `page-payment` | `user/booking/payment.css` |
| `page-account` | `user/account/account.css` |
| `page-organizer-dashboard` | `organizer/dashboard.css` |
| `page-organizer-check-in` | `organizer/check-in.css` |
| `page-admin-users` | `admin/users.css` |
| `bo` (ใส่ให้อัตโนมัติ) | `backoffice/layout.css` — หน้า Organizer/Admin เพิ่ม class ของหน้าเองต่อท้ายได้ |
| `st` (ใส่ให้อัตโนมัติ) | `staff/layout.css`, `staff/check-in.css` |

## JS ที่คู่กัน (resources/js)

| ไฟล์ | รวมใน | ใช้กับ |
|---|---|---|
| `user/navbar.js` | `user.js` | เมนูโปรไฟล์, จัดตัวอักษรในวงกลมโปรไฟล์, ปุ่มเข้าสู่ระบบ |
| `user/auth-modal.js` | `user.js` | หน้าต่างเข้าสู่ระบบ/สมัครสมาชิก, ลิงก์ที่มี `data-login-required` (ต้อง login ก่อน) |
| `user/concert-search.js` | `user.js` | ช่องค้นหาที่มีรายการแนะนำ |
| `user/my-ticket.js` | `user.js` | popup แสดง e-ticket ในหน้าตั๋วของฉัน |
| `backoffice/layout.js` | `backoffice.js` | เปิด/ปิด sidebar หลังบ้านบนมือถือ/แท็บเล็ต |
| `organizer/dashboard.js` | `backoffice.js` | แผงควบคุมของ Organizer (ไฟล์ตั้งต้น, `[data-organizer-dashboard]`) |
| `admin/users.js` | `backoffice.js` | จัดการผู้ใช้ของ Admin (ไฟล์ตั้งต้น, `[data-admin-users]`) |
| `shared/check-in.js` | `backoffice.js` | สแกน/ตรวจตั๋ว ใช้ร่วมหน้า Organizer + Staff (ไฟล์ตั้งต้น, `[data-check-in]`) |
| `shared/dropdown.js` | ทั้งสองไฟล์ | เมนู dropdown ทั่วไป (`data-dropdown`) |
| `shared/pagination.js` | ทั้งสองไฟล์ | ปุ่ม "…" ในตัวแบ่งหน้า |
| `shared/password-toggle.js` | ทั้งสองไฟล์ | ปุ่มรูปตาแสดง/ซ่อนรหัสผ่าน |
