/**
 * site.js
 * ไฟล์รวม JS ของทั้งเว็บ (layouts/main ใช้ @vite) แต่ละไฟล์ตรวจเองว่าหน้านั้นมี element ที่ต้องใช้ไหม
 * Author : ธีรเมธ พลสาย
 * Created : 09/10/2569
 */

import './navbar.js';          // เมนูโปรไฟล์, ตัวอักษรในวงกลมโปรไฟล์, ปุ่มเข้าสู่ระบบ
import './pagination.js';      // ปุ่ม "…" ในตัวแบ่งหน้า
import './dropdown.js';        // เมนู dropdown (ตัวกรองสถานะหน้าคอนเสิร์ตทั้งหมด)
import './concert-search.js';  // ช่องค้นหาที่มีรายการแนะนำ (หน้า Home / คอนเสิร์ตทั้งหมด)
import './my-ticket.js';       // popup แสดง e-ticket ในหน้าตั๋วของฉัน
