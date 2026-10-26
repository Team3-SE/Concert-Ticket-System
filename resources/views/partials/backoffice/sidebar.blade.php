{{--
 * sidebar.blade.php (backoffice)
 * เมนูซ้ายของหลังบ้าน เลือกรายการตาม role ของผู้ใช้ (ORGANIZER / ADMIN) ตามแบบ Figma
 * เมนูไหนที่ยังไม่มี route จะแสดงเป็นสีจาง กดไม่ได้ (เพิ่ม route ชื่อตามที่ระบุ แล้วเมนูจะกดได้เอง)
 * ใช้กับ : layouts/backoffice.blade.php
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 27/10/2569
--}}
@php
  $boUser = auth()->user();
  $boMenus = [
    'ORGANIZER' => [
      ['label' => 'แผงควบคุม (Dashboard)', 'route' => 'organizer.dashboard',      'active' => 'organizer.dashboard'],
      ['label' => 'จัดการคอนเสิร์ต',        'route' => 'organizer.concerts.index', 'active' => 'organizer.concerts.*'],
      ['label' => 'รายการผู้ซื้อตั๋ว',        'route' => 'organizer.buyers.index',   'active' => 'organizer.buyers.*'],
      ['label' => 'จัดการเจ้าหน้าที่',        'route' => 'organizer.staff.index',    'active' => 'organizer.staff.*'],
      ['label' => 'ตรวจสอบสิทธิ์เข้างาน',     'route' => 'organizer.check-in',       'active' => 'organizer.check-in'],
    ],
    'ADMIN' => [
      ['label' => 'จัดการผู้ใช้',            'route' => 'admin.users.index',         'active' => 'admin.users.*'],
      ['label' => 'รายการความผิดปกติ',       'route' => 'admin.incidents.index',     'active' => 'admin.incidents.*'],
      ['label' => 'ตรวจสอบการจอง',          'route' => 'admin.bookings.index',      'active' => 'admin.bookings.*'],
      ['label' => 'ประวัติการใช้งาน',         'route' => 'admin.audit-logs.index',    'active' => 'admin.audit-logs.*'],
      ['label' => 'จัดการองค์กร',            'route' => 'admin.organizations.index', 'active' => 'admin.organizations.*'],
    ],
  ];
  $boItems = $boMenus[strtoupper((string) $boUser->role)] ?? [];
@endphp
<aside class="bo-sidebar" id="boSidebar">
  <a href="{{ $boUser->homeUrl() }}" class="bo-logo">
    <img src="{{ asset('images/logo.png') }}" alt="" width="38" height="38">
    <span>TEAM <b>3</b> Ticket</span>
  </a>

  <nav class="bo-nav" aria-label="เมนูหลังบ้าน">
    @foreach($boItems as $item)
      @if(Route::has($item['route']))
        <a href="{{ route($item['route']) }}"
           @class(['bo-nav__item', 'is-active' => request()->routeIs($item['active'])])
           @if(request()->routeIs($item['active'])) aria-current="page" @endif>{{ $item['label'] }}</a>
      @else
        <span class="bo-nav__item is-soon" title="ยังไม่เปิดใช้งาน (route {{ $item['route'] }})">{{ $item['label'] }}</span>
      @endif
    @endforeach

    <form method="POST" action="{{ route('auth.logout') }}">
      @csrf
      <button type="submit" class="bo-nav__item bo-nav__logout">ออกจากระบบ</button>
    </form>
  </nav>
</aside>
