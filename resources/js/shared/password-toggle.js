/**
 * password-toggle.js
 * ปุ่มรูปตาในช่องรหัสผ่าน (ปุ่มที่มี data-toggle-password อยู่ใน .pw-wrap เดียวกับช่อง input): กดแล้วแสดง/ซ่อนรหัสผ่าน
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 24/10/2569
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

/*
 * ใช้ได้ทุกหน้า: หน้าต่างเข้าสู่ระบบ/สมัครสมาชิก และหน้าเปลี่ยนรหัสผ่านในแก้ไขข้อมูลส่วนตัว
 */

document.addEventListener('click', (event) => {
  const btn = event.target.closest('[data-toggle-password]');
  if (!btn) return;

  const input = btn.closest('.pw-wrap')?.querySelector('input');
  if (!input) return;

  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  btn.classList.toggle('is-on', show);
});
