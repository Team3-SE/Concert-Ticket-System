{{--
 * header.blade.php (backoffice)
 * แถบด้านบนของหลังบ้าน: ปุ่มเปิดเมนู (มือถือ/แท็บเล็ต), ชื่อผู้ใช้ และรูปโปรไฟล์ (ตัวอักษรแรกของชื่อ)
 * ใช้กับ : layouts/backoffice.blade.php
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 27/10/2569
--}}
@php($boName = auth()->user()->displayName())
<header class="bo-header">
  <button type="button" class="bo-menu-btn" data-bo-open aria-controls="boSidebar" aria-label="เปิดเมนู">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
  <div class="bo-user">
    <span class="bo-user__name">{{ $boName }}</span>
    <span class="bo-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($boName, 0, 1)) }}</span>
  </div>
</header>
