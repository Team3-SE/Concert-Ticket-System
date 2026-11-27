{{--
 * reset-steps.blade.php
 * แถบ 3 ขั้นตอนของหน้าต่างลืมรหัสผ่าน (กรอกอีเมล → ใส่รหัสยืนยัน → ตั้งรหัสใหม่)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 29/10/2569
 * @Update Date 27/11/2569 67160292 ธีรเมธ พลสาย
--}}
{{--
 * ใช้กับ : user/auth/partials/forgot-modal (ทั้ง 3 ขั้น)  สไตล์ : resources/css/user/auth/password-reset.css
 * ข้อมูล : $current (ขั้นที่กำลังทำ 1–3)
--}}
<ol class="pr-steps" aria-label="ขั้นตอนการตั้งรหัสผ่านใหม่">
  @foreach(['กรอกอีเมล', 'ใส่รหัสยืนยัน', 'ตั้งรหัสใหม่'] as $i => $label)
    @php($step = $i + 1)
    <li @class(['is-done' => $step < $current, 'is-current' => $step === $current]) @if($step === $current) aria-current="step" @endif>
      {{-- ทุกขั้นแสดงเป็นตัวเลข (ตามแบบ Figma) แยกสถานะด้วยสี: ทำแล้ว / กำลังทำ / ยังไม่ถึง --}}
      <b>{{ $step }}</b>
      <span>{{ $label }}</span>
    </li>
  @endforeach
</ol>
