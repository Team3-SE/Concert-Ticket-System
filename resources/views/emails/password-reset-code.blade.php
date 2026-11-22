{{--
 * password-reset-code.blade.php
 * อีเมลรหัสยืนยัน 6 หลักสำหรับตั้งรหัสผ่านใหม่ (HTML) แบบเดียวกับอีเมลยืนยันการสมัคร (หัวไล่สี + ไอคอน)
 * เขียนด้วยตาราง + สไตล์ในบรรทัด ให้ Gmail / Outlook / แอปมือถือแสดงได้ (Outlook บนคอมเห็นหัวเป็นสีกรมท่าเรียบ)
 * ใช้กับ : App\Notifications\PasswordResetCode (คู่กับ password-reset-code-text สำหรับอีเมลแบบข้อความล้วน)
 * ข้อมูล : $code (รหัส 6 หลัก), $firstName (ชื่อผู้ใช้ ว่างได้), $ttl (อายุรหัส นาที), $maxAttempts (กรอกผิดได้กี่ครั้ง)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 29/10/2569
--}}
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>รหัสยืนยันสำหรับตั้งรหัสผ่านใหม่ - TEAM 3 Ticket</title>
</head>
<body style="margin:0;padding:0;background:#f2f4f8;-webkit-text-size-adjust:100%;">
  {{-- ข้อความตัวอย่างที่โปรแกรมอีเมลแสดงต่อจากหัวเรื่องในกล่องจดหมาย (ซ่อนในตัวอีเมล) --}}
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;font-size:1px;line-height:1px;color:#f2f4f8;">
    รหัสยืนยันของคุณคือ {{ $code }} ใช้ได้ {{ $ttl }} นาที อย่าบอกรหัสนี้กับใคร
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f2f4f8;">
    <tr><td align="center" style="padding:32px 12px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;">

        {{-- หัวอีเมล: ไล่สีกรมท่า (ถ้าโปรแกรมไม่รองรับจะเป็นสีกรมท่าเรียบ) --}}
        <tr><td align="center" style="background:#0d1757;background-image:linear-gradient(135deg,#0d1757 0%,#24399e 70%,#3a3f9e 100%);padding:34px 24px 30px;font-family:Tahoma,'Leelawadee UI',sans-serif;">
          <p style="margin:0 0 22px;font-size:15px;font-weight:bold;color:#ffffff;">TEAM <span style="color:#f7941d;">3</span> Ticket</p>
          <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
            <td align="center" valign="middle" width="72" height="72" style="width:72px;height:72px;border-radius:50%;background:#f7941d;font-size:32px;line-height:72px;">&#128274;</td>
          </tr></table>
          <h1 style="margin:20px 0 6px;font-size:24px;line-height:1.35;font-weight:bold;color:#ffffff;">รหัสยืนยันตั้งรหัสผ่านใหม่</h1>
          <p style="margin:0;font-size:14px;line-height:1.6;color:#c9d1f2;">
            {{ $firstName !== '' ? "สวัสดีคุณ{$firstName} " : '' }}นำรหัสด้านล่างไปกรอกในหน้าเว็บ
          </p>
        </td></tr>

        {{-- รหัส 6 หลัก + เวลาหมดอายุ --}}
        <tr><td align="center" style="padding:30px 32px 8px;font-family:Tahoma,'Leelawadee UI',sans-serif;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
            <td align="center" style="background:#f5f7fc;border:2px dashed #f7941d;border-radius:16px;padding:16px 26px;font-family:'Courier New',Consolas,monospace;font-size:38px;line-height:1.2;font-weight:bold;letter-spacing:10px;color:#0d1757;">{{ $code }}</td>
          </tr></table>
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;"><tr>
            <td style="background:#fff4e6;border-radius:999px;padding:6px 14px;font-family:Tahoma,'Leelawadee UI',sans-serif;font-size:12px;color:#b4610a;">&#9201; รหัสหมดอายุใน {{ $ttl }} นาที</td>
          </tr></table>
        </td></tr>

        {{-- 3 ขั้นตอน --}}
        <tr><td style="padding:22px 32px 6px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family:Tahoma,'Leelawadee UI',sans-serif;">
            <tr>
              <td width="28" valign="top" style="padding:8px 0;"><div style="width:22px;height:22px;border-radius:50%;background:#0d1757;color:#ffffff;font-size:12px;line-height:22px;text-align:center;font-weight:bold;">1</div></td>
              <td style="padding:8px 0 8px 8px;font-size:14px;line-height:1.6;color:#4b4f63;">กรอกรหัส <b style="color:#1c2138;">6 หลัก</b> ในหน้า “กรอกรหัสยืนยัน” (กรอกผิดได้ไม่เกิน {{ $maxAttempts }} ครั้ง)</td>
            </tr>
            <tr>
              <td width="28" valign="top" style="padding:8px 0;"><div style="width:22px;height:22px;border-radius:50%;background:#0d1757;color:#ffffff;font-size:12px;line-height:22px;text-align:center;font-weight:bold;">2</div></td>
              <td style="padding:8px 0 8px 8px;font-size:14px;line-height:1.6;color:#4b4f63;">ตั้งรหัสผ่านใหม่</td>
            </tr>
            <tr>
              <td width="28" valign="top" style="padding:8px 0;"><div style="width:22px;height:22px;border-radius:50%;background:#16a36a;color:#ffffff;font-size:12px;line-height:22px;text-align:center;font-weight:bold;">&#10003;</div></td>
              <td style="padding:8px 0 8px 8px;font-size:14px;line-height:1.6;color:#4b4f63;">เข้าสู่ระบบด้วยรหัสผ่านใหม่ได้เลย</td>
            </tr>
          </table>
        </td></tr>

        {{-- คำเตือนความปลอดภัย --}}
        <tr><td style="padding:18px 32px 30px;font-family:Tahoma,'Leelawadee UI',sans-serif;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
            <td style="background:#f5f7fc;border-radius:12px;padding:14px 16px;font-family:Tahoma,'Leelawadee UI',sans-serif;font-size:12px;line-height:1.7;color:#8b8fa3;">
              &#128737;&#65039; อย่าบอกรหัสนี้กับใคร TEAM 3 Ticket จะไม่ขอรหัสนี้ทางโทรศัพท์ แชท หรืออีเมล
            </td>
          </tr></table>
        </td></tr>
      </table>

      <p style="margin:18px 0 0;font-family:Tahoma,'Leelawadee UI',sans-serif;font-size:12px;line-height:1.7;color:#8b8fa3;text-align:center;">
        ถ้าคุณไม่ได้ขอตั้งรหัสผ่านใหม่ ไม่ต้องทำอะไร รหัสผ่านเดิมยังใช้ได้ตามปกติ<br>
        &copy; {{ now()->year + 543 }} TEAM 3 Ticket
      </p>
    </td></tr>
  </table>
</body>
</html>
