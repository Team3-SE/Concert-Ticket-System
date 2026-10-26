/**
 * user.js
 * ไฟล์รวม JS ของฝั่ง User (layouts/user ใช้ @vite) แต่ละไฟล์ตรวจเองว่าหน้านั้นมี element ที่ต้องใช้ไหม
 * หลังบ้าน Organizer/Admin/Staff ใช้ backoffice.js
 * Author : ธีรเมธ พลสาย
 * Created : 09/10/2569
 * Updated : 27/10/2569 67160292 ธีรเมธ พลสาย
 */

import './user/navbar.js';          // เมนูโปรไฟล์, ตัวอักษรในวงกลมโปรไฟล์, ปุ่มเข้าสู่ระบบ
import './user/auth-modal.js';      // หน้าต่างเข้าสู่ระบบ/สมัครสมาชิก (ทุกหน้า) + ปุ่มที่ต้อง login ก่อน
import './shared/password-toggle.js'; // ปุ่มรูปตาแสดง/ซ่อนรหัสผ่าน (หน้าต่าง login/สมัครสมาชิก และหน้าเปลี่ยนรหัสผ่าน)
import './shared/pagination.js';    // ปุ่ม "…" ในตัวแบ่งหน้า
import './shared/dropdown.js';      // เมนู dropdown (ตัวกรองสถานะหน้าคอนเสิร์ตทั้งหมด)
import './user/concert-search.js';  // ช่องค้นหาที่มีรายการแนะนำ (หน้า Home / คอนเสิร์ตทั้งหมด)
import './user/my-ticket.js';       // popup แสดง e-ticket ในหน้าตั๋วของฉัน
