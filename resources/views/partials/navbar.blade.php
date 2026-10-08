{{--
 * navbar.blade.php
 * แถบด้านบน: โลโก้ ชื่อและรูปโปรไฟล์ของผู้ใช้ เมนูโปรไฟล์ หรือปุ่มเข้าสู่ระบบ
 * @Author
 * @Create Date
--}}
@php
  $showLogo = $showLogo ?? true;
  $profileUser = auth()->user();
  $profileName = $profileUser
      ? (trim(($profileUser->first_name ?? '') . ' ' . ($profileUser->last_name ?? '')) ?: $profileUser->email)
      : null;
  // อักษรตัวแรกของชื่อ + สีพื้นหลังที่คงที่ต่อผู้ใช้ (คำนวณจาก user_id จึงได้สีเดิมทุกครั้งที่กลับเข้ามา)
  $profileInitial = $profileUser
      ? mb_strtoupper(mb_substr($profileUser->first_name ?: ($profileUser->email ?? '?'), 0, 1))
      : '';
  $profileColors = ['#2563eb', '#0f766e', '#c2410c', '#7c3aed', '#be123c', '#0369a1'];
  $profileColor = $profileUser ? $profileColors[crc32((string) $profileUser->getKey()) % count($profileColors)] : $profileColors[0];
@endphp

<header class="team-header {{ $showLogo ? '' : 'no-logo' }}">

  {{-- LOGO SECTION --}}
  @if($showLogo)
  <a href="{{ url('/') }}" class="team-logo">
    <img src="{{ asset('images/logo.png') }}" alt="TEAM 3 Ticket Logo" class="w-7 h-7 object-contain" style="mix-blend-mode:screen;">
    <span class="font-bold text-base tracking-wide">TEAM <span class="text-[#F47B1F]">3</span> Ticket</span>
  </a>
  @endif

  {{-- PROFILE & DROPDOWN SECTION --}}
  <div class="profile-area">

    {{-- Profile Trigger Button --}}
    @auth
    <button type="button" class="profile-trigger" data-profile-toggle aria-expanded="false">
      <span class="profile-name">{{ $profileName }}</span>
      <span class="profile-avatar" style="background-color:{{ $profileColor }};"><span class="profile-initial">{{ $profileInitial }}</span></span>
    </button>
    @else
    <button class="profile-login" id="openLogin">เข้าสู่ระบบ</button>
    @endauth

    {{-- Profile Dropdown Popup (Matching Mockup) --}}
    @auth
    <div data-profile-menu hidden class="profile-popup">

      {{-- User Header Info --}}
      <div class="profile-popup-head">
        <strong>{{ $profileName }}</strong>
        <span>ข้อมูลส่วนตัว</span>
      </div>

      {{-- Menu Items --}}
      <div class="profile-popup-menu">

        <a href="#" aria-disabled="true">
          ตั๋วของฉัน
        </a>

        <a href="{{ route('orders.history') }}">
          ประวัติการสั่งซื้อ
        </a>

        <a href="{{ route('profile.edit') }}">
          แก้ไขข้อมูลส่วนตัว
        </a>

        {{-- Logout Item --}}
        <form method="POST" action="{{ route('auth.logout') }}">
          @csrf
          <button type="submit" class="profile-logout">ออกจากระบบ</button>
        </form>

      </div>

    </div>
    @endauth

  </div>

</header>
