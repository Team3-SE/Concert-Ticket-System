{{--
 * sidebar.blade.php
 * เมนูซ้ายของหน้า account (เรียกใช้ด้วย @include('partials.sidebar'))
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 08-10-2569
--}}
<aside class="sidebar">
  {{-- sidebar-logo start --}}
  <a href="{{ url('/') }}" class="sidebar-logo">
    <img src="{{ asset('images/logo.png') }}" alt="TEAM 3 Ticket">
    <span class="logo-text">TEAM <span>3</span> Ticket</span>
  </a>
  {{-- sidebar-logo end --}}

  {{-- sidebar-menu start --}}
  <nav>
    <a href="{{ route('tickets.index') }}" class="{{ request()->routeIs('tickets.*') ? 'active' : '' }}">ตั๋วของฉัน</a>
    <a href="{{ route('orders.history') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">ประวัติการสั่งซื้อ</a>
    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">แก้ไขข้อมูลส่วนตัว</a>
    <form method="POST" action="{{ route('auth.logout') }}">
      @csrf
      <button type="submit" class="nav-logout">ออกจากระบบ</button>
    </form>
  </nav>
  {{-- sidebar-menu end --}}
</aside>
