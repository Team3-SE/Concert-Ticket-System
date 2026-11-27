/**
 * check-in.js
 * ตรวจสอบสิทธิ์เข้างาน ใช้ร่วมกันระหว่างหน้า Organizer (organizer/check-in) และ Staff (staff/check-in)
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 27/10/2569
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

/*
 * เปิดกล้องสแกน QR Code / รับรหัสตั๋วจากช่องกรอก → ส่งไปตรวจที่เซิร์ฟเวอร์ (CheckInController) → แสดงผลและประวัติ
 * ทำงานเฉพาะหน้าที่มี [data-check-in]
 * หมายเหตุ: เบราว์เซอร์เปิดกล้องได้เฉพาะเว็บที่เป็น HTTPS หรือ localhost
 */

(() => {
  const root = document.querySelector('[data-check-in]');
  if (!root) return;

  // TODO: เปิดกล้อง + อ่าน QR, ส่งรหัสตั๋ว (POST) และอัปเดตสถิติ/ประวัติการสแกน
})();
