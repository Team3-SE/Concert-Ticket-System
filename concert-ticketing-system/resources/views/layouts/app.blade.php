<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'TEAM 3 Ticket')</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --navy-900:#0a1a4a;
    --navy-800:#0e2166;
    --navy-700:#12297d;
    --orange:#f7941d;
    --orange-dark:#e8820a;
    --bg:#f4f5f8;
    --gray-100:#f1f2f5;
    --gray-300:#dfe1e7;
    --gray-500:#8b8fa3;
    --gray-700:#4b4f63;
    --text:#1c2138;
  }
  *{box-sizing:border-box;margin:0;padding:0;}
  body{
    font-family:'Noto Sans Thai',sans-serif;
    background:var(--bg);
    color:var(--text);
  }

  /* ===== Layout shell ===== */
  .shell{display:flex;min-height:100vh;}

  /* ===== Sidebar ===== */
  .sidebar{
    width:230px;flex-shrink:0;
    background:linear-gradient(180deg,var(--navy-900),var(--navy-800));
    display:flex;flex-direction:column;
  }
  .sidebar .logo{
    display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;
    font-size:16px;padding:22px 22px 20px;
  }
  .logo-badge{
    width:28px;height:28px;border-radius:8px;background:var(--orange);
    display:flex;align-items:center;justify-content:center;font-size:14px;
  }
  .sidebar nav{margin-top:6px;flex:1;}
  .sidebar nav a{
    display:block;padding:13px 22px;font-size:13px;color:#b6bce0;
    text-decoration:none;border-left:4px solid transparent;
    transition:background .15s ease,color .15s ease;
  }
  .sidebar nav a:hover{background:rgba(255,255,255,.06);color:#fff;}
  .sidebar nav a.active{
    background:rgba(255,255,255,.08);color:#fff;font-weight:600;
    border-left-color:var(--orange);
  }

  /* ===== Main column ===== */
  .main{flex:1;display:flex;flex-direction:column;min-width:0;}

  header.topbar{
    background:linear-gradient(90deg,var(--navy-900),var(--navy-700));
    border-bottom:3px solid var(--orange);
    display:flex;align-items:center;justify-content:flex-end;
    padding:14px 32px;
  }
  .user-chip{display:flex;align-items:center;gap:10px;color:#fff;font-size:13px;}
  .avatar{width:30px;height:30px;border-radius:50%;background:var(--gray-300);}

  main{padding:34px 32px 60px;}
  .page-title{
    font-size:15px;font-weight:700;color:var(--text);
    border-left:4px solid var(--orange);padding-left:10px;
    margin-bottom:18px;
  }

  .status-msg{
    max-width:1080px;margin:0 0 18px;padding:10px 16px;
    background:#e6f6ec;border:1px solid #b9e6c8;color:#1c7a41;
    border-radius:8px;font-size:12px;
  }

  /* ===== Shared card / form styles (available to every page) ===== */
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:22px;max-width:1080px;}

  .card{
    background:#fff;border-radius:12px;border:1px solid var(--gray-300);
    padding:28px 30px;
  }
  .card h2{
    display:flex;align-items:center;gap:8px;
    font-size:15px;font-weight:700;color:var(--text);margin-bottom:22px;
  }
  .card h2 .bar{width:5px;height:18px;background:var(--orange);border-radius:2px;display:inline-block;}

  .field{margin-bottom:16px;}
  .field label{display:block;font-size:12px;font-weight:600;color:var(--gray-700);margin-bottom:6px;}
  .field input{
    width:100%;padding:10px 12px;border:1px solid var(--gray-300);
    border-radius:8px;font-size:13px;font-family:inherit;color:var(--text);
    outline:none;transition:border-color .15s;
  }
  .field input:focus{border-color:var(--navy-700);}
  .field input::placeholder{color:#b7bad0;}
  .row-2{display:flex;gap:14px;}
  .row-2 .field{flex:1;}

  .phone-field{
    display:flex;align-items:stretch;border:1px solid var(--gray-300);border-radius:8px;overflow:hidden;
  }
  .phone-prefix{
    background:var(--gray-100);display:flex;align-items:center;gap:6px;padding:0 12px;
    font-size:13px;color:var(--gray-700);border-right:1px solid var(--gray-300);
  }
  .phone-field input{border:none;border-radius:0;}
  .phone-field input:focus{border:none;}

  .actions{display:flex;justify-content:center;margin-top:26px;}

  .btn{
    border:none;font-family:inherit;font-size:13px;font-weight:600;
    padding:11px 30px;border-radius:22px;cursor:pointer;
    transition:background .15s ease, transform .1s ease;
  }
  .btn:hover{transform:translateY(-1px);}
  .btn-orange{background:var(--orange);color:#fff;}
  .btn-orange:hover{background:var(--orange-dark);}
  .btn-navy{background:var(--navy-800);color:#fff;}
  .btn-navy:hover{background:var(--navy-700);}

  @media(max-width:900px){
    .grid-2{grid-template-columns:1fr;}
  }
  @media(max-width:640px){
    .sidebar{width:76px;}
    .sidebar .logo span:last-child, .sidebar nav a{display:none;}
  }

  /* ===== Extra CSS a page can inject (optional) ===== */
  @yield('styles')
</style>
</head>
<body>

<div class="shell">

  <!-- ===== Sidebar  ===== -->
  <aside class="sidebar">
    <div class="logo"><span class="logo-badge">🎫</span> <span>TEAM 3 Ticket</span></div>
    <nav>
      <a href="{{ route('tickets.index') }}" class="{{ request()->routeIs('tickets.*') ? 'active' : '' }}">
        ตั๋วของฉัน
      </a>
      <a href="{{ route('orders.history') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">
        ประวัติการสั่งซื้อ
      </a>
      <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
        แก้ไขข้อมูลส่วนตัว
      </a>
      <a href="{{ route('logout') }}">ออกจากระบบ</a>
    </nav>
  </aside>

  <!-- ===== Main ===== -->
  <div class="main">
    <header class="topbar">
      <div class="user-chip">
        <span>{{ $user->first_name ?? 'อนิกุน' }} {{ $user->last_name ?? 'ชุนวิรกาล' }}</span>
        <div class="avatar"></div>
      </div>
    </header>

    <main>
      @if(session('status'))
        <div class="status-msg">{{ session('status') }}</div>
      @endif

      @yield('content')
    </main>
  </div>

</div>

@yield('scripts')

</body>
</html>
