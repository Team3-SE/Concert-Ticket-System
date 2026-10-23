# CSS ของเว็บ (resources/css)

โฟลเดอร์จัดตาม `resources/views` — แก้หน้าในโฟลเดอร์ไหน ให้ไปแก้ CSS ในโฟลเดอร์ชื่อเดียวกัน

## ทำงานยังไง

1. เขียน CSS แยกไฟล์ในโฟลเดอร์ย่อยด้านล่าง
2. `site.css` รวมทุกไฟล์ด้วย `@import` (ตามลำดับ)
3. Vite build `site.css` เป็น CSS ไฟล์เดียวไปไว้ที่ `public/build/` (มีเลขเวอร์ชันกัน browser cache ให้เอง)
4. `layouts/main.blade.php` โหลดด้วย `@vite(['resources/css/site.css', 'resources/js/site.js'])`

**แก้ไฟล์แล้วต้อง build ใหม่** — `docker-compose run --rm node npm run build`
(หรือถ้ามี Node.js ในเครื่อง: `npm run dev` ระหว่างแก้)

**เพิ่มไฟล์ CSS ใหม่** — สร้างไฟล์ในโฟลเดอร์ที่ตรงกับ view แล้วเพิ่มบรรทัด `@import` ใน `site.css`

> ห้ามแก้ไฟล์ใน `public/build/` ด้วยมือ — ถูกสร้างใหม่ทุกครั้งที่ build (และไม่ขึ้น git)

## ไฟล์ไหนใช้กับอะไร

| ไฟล์ | ใช้กับ (resources/views) | หน้าที่ |
|---|---|---|
| `shared/base.css` | ทุกหน้า | สีหลัก ฟอนต์ footer อยู่ล่างสุด |
| `shared/navbar.css` | `partials/navbar` | แถบด้านบน ชื่อ/รูปโปรไฟล์ |
| `shared/sidebar.css` | `partials/sidebar` | เมนูซ้าย (หน้า account) |
| `shared/pagination.css` | `partials/pagination` | ตัวแบ่งหน้า (คอนเสิร์ตทั้งหมด, ประวัติการสั่งซื้อ) |
| `shared/auth-modal.css` | `partials/auth-modals` | หน้าต่างเข้าสู่ระบบ/สมัครสมาชิก (ทุกหน้า ตอนยังไม่ login) |
| `concerts/home.css` | `concerts/home`, `partials/concert-card` | หน้า Home + การ์ดคอนเสิร์ต |
| `concerts/index.css` | `concerts/index` | หน้าคอนเสิร์ตทั้งหมด (Show All) |
| `concerts/search.css` | `concerts/home`, `concerts/index` | ช่องค้นหา + รายการแนะนำ |
| `concerts/manage-images.css` | `concerts/manage-images` | หน้าจัดการรูปคอนเสิร์ต |
| `booking/booking.css` | `booking/booking` | หน้าเลือกโซน/ที่นั่ง |
| `booking/payment.css` | `booking/payment` | หน้าชำระเงิน |
| `booking/qr-modal.css` | `booking/qr-modal` | popup ชำระเงินด้วย QR |
| `account/account.css` | `layouts/app`, `account/order-history`, `account/edit-profile`, `account/my-ticket` | หน้า account (มี sidebar) |
| `app.css` | `welcome` | หน้า welcome ของ Laravel (Tailwind) — ไม่เกี่ยวกับหน้าอื่น |

## class บน `<body>` (กันสไตล์ชนกันระหว่างหน้า)

บางหน้าใช้ชื่อ class เดียวกันแต่หน้าตาต่างกัน (เช่น `.card`, `.field`) ไฟล์ของหน้านั้นจึงครอบทุก rule ด้วย class ของ `<body>`
ตั้งค่าใน view ด้วย `@section('body_class', '...')`

| body class | ไฟล์ |
|---|---|
| `page-home` | `concerts/home.css` |
| `page-concerts` | `concerts/index.css` |
| `page-payment` | `booking/payment.css` |
| `page-account` | `account/account.css` |

## JS ที่คู่กัน (resources/js — รวมใน `site.js`)

| ไฟล์ | ใช้กับ |
|---|---|
| `navbar.js` | เมนูโปรไฟล์, จัดตัวอักษรในวงกลมโปรไฟล์, ปุ่มเข้าสู่ระบบ |
| `auth-modal.js` | หน้าต่างเข้าสู่ระบบ/สมัครสมาชิก, ลิงก์ที่มี `data-login-required` (ต้อง login ก่อน) |
| `pagination.js` | ปุ่ม "…" ในตัวแบ่งหน้า |
| `concert-search.js` | ช่องค้นหาที่มีรายการแนะนำ |
