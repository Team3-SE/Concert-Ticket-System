/**
 * backoffice.js
 * ไฟล์รวม JS ของหลังบ้าน (Organizer / Admin / Staff) ใช้กับ layouts/backoffice และ layouts/staff (@vite)
 * แต่ละไฟล์ตรวจเองว่าหน้านั้นมี element ที่ต้องใช้ไหม (ฝั่ง User ใช้ user.js)
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 27/10/2569
 * Updated : 26/11/2569 67160279 กนกพล แซ่ตั๊น
 */

import './backoffice/layout.js';      // เปิด/ปิด sidebar บนมือถือ/แท็บเล็ต
import './shared/dropdown.js';        // เมนู dropdown (เมนูออกจากระบบของ Staff, ตัวกรองต่าง ๆ)
import './shared/pagination.js';      // ปุ่ม "…" ในตัวแบ่งหน้า
import './shared/password-toggle.js'; // ปุ่มรูปตาแสดง/ซ่อนรหัสผ่าน
import './shared/check-in.js';       // ตรวจสอบสิทธิ์เข้างาน (Organizer + Staff)
import './user/concert-search.js';   // ช่องค้นหาและรายการแนะนำคอนเสิร์ต

// organizer/   views/organizer/*   (เพิ่ม import ของหน้า Organizer ตรงนี้)
import './organizer/dashboard.js';   // แผงควบคุม
import './organizer/concert-management.js';

// admin/       views/admin/*       (เพิ่ม import ของหน้า Admin ตรงนี้)
import './admin/users.js';           // จัดการผู้ใช้
