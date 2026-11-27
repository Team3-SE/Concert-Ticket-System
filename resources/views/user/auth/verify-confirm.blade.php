{{--
 * verify-confirm.blade.php
 * ตัวกลางของลิงก์ยืนยันอีเมล (เปิดจากอีเมลสมัครสมาชิก): หน้าว่างที่ส่งฟอร์มยืนยันเองทันที แล้วระบบเข้าสู่ระบบและพาไปหน้า Home (POST verification.confirm)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 16/11/2569
 * @Update Date 27/11/2569 67160292 ธีรเมธ พลสาย
--}}
{{--
 * ไม่ยืนยันตอน GET เพราะโปรแกรมสแกนลิงก์ของอีเมลมักเปิดลิงก์ล่วงหน้า (ส่วนใหญ่ไม่รัน JavaScript) ถ้าปิด JavaScript จะมีปุ่มให้กดเอง
--}}
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>กำลังยืนยันอีเมล - TEAM 3 Ticket</title>
  <style>
    body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #eaf0f8; font-family: system-ui, sans-serif; color: #4b5563; text-align: center; }
    .box { padding: 1rem; }
    button { margin-top: 1rem; width: 100%; padding: .75rem 1rem; border: 0; border-radius: .5rem; background: #1a2b8f; color: #fff; font-weight: 600; font-size: 1rem; cursor: pointer; }
  </style>
</head>
<body>
  <form id="verifyForm" method="POST" action="{{ $action }}" class="box">
    @csrf
    <p>กำลังยืนยันอีเมล...</p>
    <noscript>
      <p>กดปุ่มด้านล่างเพื่อยืนยันอีเมล <strong>{{ $email }}</strong></p>
      <button type="submit">ยืนยันอีเมล</button>
    </noscript>
  </form>
  <script>
    // ส่งครั้งเดียว กันซ้ำตอนโหลดหน้าจาก cache
    document.getElementById('verifyForm').requestSubmit();
  </script>
</body>
</html>
