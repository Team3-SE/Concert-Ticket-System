{{--
 * reset-steps.blade.php
 * แถบ 3 ขั้นตอนของหน้าต่างลืมรหัสผ่าน (กรอกอีเมล → ใส่รหัสยืนยัน → ตั้งรหัสใหม่)
 * ใช้กับ : user/auth/partials/forgot-modal (ทั้ง 3 ขั้น)  สไตล์ : resources/css/user/auth/password-reset.css
 * ข้อมูล : $current (ขั้นที่กำลังทำ 1–3)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 29/10/2569
 * @Update Date 15/11/2569 67160292 ธีรเมธ พลสาย
--}}
<ol class="pr-steps" aria-label="ขั้นตอนการตั้งรหัสผ่านใหม่">
  @foreach(['กรอกอีเมล', 'ใส่รหัสยืนยัน', 'ตั้งรหัสใหม่'] as $i => $label)
    @php($step = $i + 1)
    <li @class(['is-done' => $step < $current, 'is-current' => $step === $current]) @if($step === $current) aria-current="step" @endif>
      <b>
        @if($step < $current)
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
        @else
          {{ $step }}
        @endif
      </b>
      <span>{{ $label }}</span>
    </li>
  @endforeach
</ol>
