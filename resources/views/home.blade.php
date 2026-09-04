<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>TEAM 3 Ticket</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --navy-900:#0a1a4a;
    --navy-800:#0e2166;
    --navy-700:#12297d;
    --orange:#f7941d;
    --orange-dark:#e8820a;
    --blue-buy:#2f80ed;
    --blue-buy-dark:#1e68d1;
    --bg:#f4f5f8;
    --gray-100:#f1f2f5;
    --gray-300:#dfe1e7;
    --gray-500:#8b8fa3;
    --gray-700:#4b4f63;
    --text:#1c2138;
    --card-blue:#e4e7f5;
    --card-yellow:#fbf3d9;
    --card-lav:#e6e2f7;
    --card-pink:#fbe3ee;
    --card-mint:#dff2ea;
    --card-blue-light:#e8ecf8;
  }
  *{box-sizing:border-box;margin:0;padding:0;}
  body{
    font-family:'IBM Plex Sans Thai',sans-serif;
    background:var(--bg);
    color:var(--text);
  }

  /* ===== Header ===== */
  header.site{
    background:linear-gradient(90deg,var(--navy-900),var(--navy-700));
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 32px;
  }
  .logo{display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:18px;letter-spacing:.3px;text-decoration:none;}
  .logo img{
    width:36px;height:36px;
    object-fit:contain;
    mix-blend-mode:screen;
  }
  .btn-orange{
    background:var(--orange);color:#fff;border:none;
    padding:10px 22px;border-radius:22px;font-weight:600;
    font-family:inherit;font-size:14px;cursor:pointer;
    transition:background .15s ease, transform .1s ease;
  }
  .btn-orange:hover{background:var(--orange-dark);transform:translateY(-1px);}

  /* ===== Search ===== */
  .search-wrap{max-width:1580px;margin:22px auto 0;padding:0 32px;}
  .search-bar{
    display:flex;align-items:center;gap:10px;
    background:#fff;border:1px solid var(--gray-300);
    border-radius:24px;padding:10px 18px;
    color:var(--gray-500);font-size:14px;
  }
  .search-bar input{
    border:none;outline:none;flex:1;
    font-family:inherit;font-size:14px;
    color:var(--text);background:transparent;
  }
  .search-bar button{border:0;background:transparent;color:var(--navy-700);font:600 13px inherit;cursor:pointer;padding:4px 8px;}
  .search-bar button:hover{color:var(--orange-dark);}
  .search-bar input::placeholder{color:var(--gray-500);}

  /* ===== Hero ===== */
  .hero-wrap{max-width:1580px;margin:18px auto 0;padding:0 32px;}
  .hero{
    position:relative;border-radius:14px;overflow:hidden;
    background:
      radial-gradient(circle at 80% 30%, rgba(255,255,255,.08), transparent 55%),
      linear-gradient(135deg,#0a1547 0%,#122a7d 55%,#1c3ea8 100%);
    padding:44px 48px;min-height:190px;
    display:flex;flex-direction:column;
    align-items:center;justify-content:center;
    text-align:center;
    color:#fff;
  }
  .hero-badge{
    display:inline-block;background:var(--orange);
    font-size:11px;font-weight:700;padding:4px 12px;
    border-radius:12px;margin-bottom:14px;width:fit-content;
    letter-spacing:.5px;text-transform:uppercase;
  }
  .hero h1{font-size:26px;font-weight:800;margin-bottom:10px;}
  .hero p{font-size:13px;color:#c7cdec;max-width:620px;line-height:1.6;}
  .hero-arrow{
    position:absolute;top:50%;transform:translateY(-50%);
    width:34px;height:34px;border-radius:50%;
    background:rgba(255,255,255,.12);color:#fff;
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;font-size:16px;border:none;
    transition:background .15s;
  }
  .hero-arrow:hover{background:rgba(255,255,255,.22);}
  .hero-arrow.left{left:16px;}
  .hero-arrow.right{right:16px;}
  .hero-slide{display:none;flex-direction:column;align-items:center;text-align:center;}
  .hero-slide.active{display:flex;}
  .dots{display:flex;justify-content:center;gap:6px;margin:14px 0 26px;}
  .dots span{width:6px;height:6px;border-radius:50%;background:var(--gray-300);cursor:pointer;transition:all .2s;}
  .dots span.active{background:var(--navy-800);width:16px;border-radius:4px;}

  /* ===== Recommend section ===== */
  .section{max-width:1580px;margin:0 auto;padding:0 32px 40px;}
  .section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
  .section-title{
    font-size:16px;font-weight:700;
    border-left:4px solid var(--orange);padding-left:10px;
  }
  .show-all{color:var(--navy-700);font-size:13px;font-weight:600;text-decoration:underline;}

  .grid{
    display:grid;grid-template-columns:repeat(6,1fr);gap:16px;
  }
  .card{
    background:#fff;border-radius:10px;overflow:hidden;
    border:1px solid var(--gray-300);
    display:flex;flex-direction:column;
  }
  /* card image area: artist name + tag are centered (both axes), matches reference */
  .card-img{
    height:230px;position:relative;
    display:flex;flex-direction:column;
    align-items:center;justify-content:center;
    gap:8px;
    padding:16px 10px;
    text-align:center;
  }
  .card-img .card-artist{
    font-weight:700;font-size:13px;
    color:#1c2138;
  }
  .c1{background:var(--card-blue);} .c2{background:var(--card-yellow);}
  .c3{background:var(--card-lav);} .c4{background:var(--card-pink);}
  .c5{background:var(--card-mint);} .c6{background:var(--card-blue-light);}
  .tag{
    display:inline-block;
    font-size:9px;font-weight:700;
    padding:3px 10px;border-radius:10px;
    color:#fff;width:fit-content;
    text-transform:uppercase;letter-spacing:.2px;
  }
  .tag-concert{background:var(--orange);}
  .tag-highlight{background:#e74c3c;}
  .tag-on-concert{background:#27ae60;}
  .tag-fan-meeting{background:#e84393;}
  .tag-live{background:#00b894;}
  .tag-default{background:var(--orange);}

  .card-body{padding:10px 12px 12px;display:flex;flex-direction:column;gap:6px;flex:1;}
  .card-body .title{font-size:12px;font-weight:700;line-height:1.4;min-height:32px;}
  .card-body .date{font-size:11px;color:var(--gray-500);}
  .card-btn{
    margin-top:auto;text-align:center;font-size:12px;font-weight:600;
    padding:7px;border-radius:16px;border:none;cursor:pointer;
    font-family:inherit;text-decoration:none;display:block;
  }
  /* Buy Now = bright blue (matches reference), not navy */
  .card-btn.buy{background:var(--blue-buy);color:#fff;}
  .card-btn.buy:hover{background:var(--blue-buy-dark);}
  .card-btn.soon{background:var(--gray-100);color:var(--gray-500);cursor:default;}
  .card-btn.sold-out{background:var(--gray-100);color:var(--gray-500);cursor:default;}

  /* ===== Footer ===== */
  footer.site{
    background:var(--navy-900);color:#c7cdec;
    padding:36px 32px 20px;margin-top:10px;
    border-top:3px solid var(--orange);
  }
  .footer-inner{
    max-width:1320px;margin:0 auto;
    display:flex;justify-content:space-between;flex-wrap:wrap;gap:20px;
  }
  .footer-logo{display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:16px;margin-bottom:10px;}
  .footer-inner .footer-desc{font-size:12px;line-height:1.8;color:#9aa2c6;max-width:280px;}
  .footer-contact-title{font-size:13px;font-weight:700;color:#fff;margin-bottom:8px;}
  .footer-contact p{font-size:12px;line-height:2;color:#c7cdec;}
  .footer-bottom{
    max-width:1320px;margin:24px auto 0;padding-top:16px;
    border-top:1px solid rgba(255,255,255,.1);
    display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;
    font-size:11px;color:#7d84a8;
  }
  .footer-bottom a{color:#7d84a8;text-decoration:none;margin-left:14px;}
  .footer-bottom a:hover{color:#c7cdec;}

  /* ===== Modal ===== */
  .overlay{
    position:fixed;inset:0;background:rgba(10,16,50,.55);
    display:none;align-items:center;justify-content:center;
    z-index:100;padding:20px;
    backdrop-filter:blur(2px);
  }
  .overlay.open{display:flex;}
  .modal{
    background:#fff;border-radius:14px;width:100%;max-width:380px;
    padding:28px 26px 24px;position:relative;
    box-shadow:0 20px 60px rgba(10,16,50,.35);
    animation:pop .18s ease;
  }
  @keyframes pop{from{opacity:0;transform:translateY(8px) scale(.98);}to{opacity:1;transform:none;}}
  .modal-close{
    position:absolute;top:14px;right:16px;background:none;border:none;
    font-size:16px;color:var(--gray-500);cursor:pointer;
  }
  .modal-error{
    background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;
    border-radius:8px;padding:9px 13px;font-size:12px;margin-bottom:14px;
  }

  .modal h2{
    text-align:center;color:var(--navy-800);font-size:19px;font-weight:800;margin-bottom:4px;
  }
  .modal .sub{text-align:center;font-size:12px;color:var(--gray-500);margin-bottom:20px;}
  .field{margin-bottom:14px;}
  .field label{display:block;font-size:12px;font-weight:600;color:var(--gray-700);margin-bottom:6px;}
  .field label .req{color:#e8503c;}
  .field input{
    width:100%;padding:10px 12px;border:1px solid var(--gray-300);
    border-radius:8px;font-size:13px;font-family:inherit;color:var(--text);
    outline:none;transition:border-color .15s;
  }
  .field input:focus{border-color:var(--navy-700);}
  .field input::placeholder{color:#b7bad0;}
  .row-2{display:flex;gap:10px;}
  .row-2 .field{flex:1;}
  .options-row{
    display:flex;align-items:center;justify-content:space-between;
    font-size:12px;margin-bottom:18px;color:var(--gray-700);
  }
  .options-row label{display:flex;align-items:center;gap:6px;cursor:pointer;}
  .options-row a{color:var(--orange-dark);text-decoration:none;font-weight:600;}
  .modal .btn-orange{width:100%;padding:11px;border-radius:24px;font-size:14px;}
  .switch-text{text-align:center;font-size:12px;color:var(--gray-700);margin-top:16px;}
  .switch-text a{color:var(--orange-dark);font-weight:700;text-decoration:none;}
  .phone-field{
    display:flex;align-items:stretch;border:1px solid var(--gray-300);border-radius:8px;overflow:hidden;
  }
  .phone-prefix{
    background:var(--gray-100);display:flex;align-items:center;padding:0 10px;font-size:13px;color:var(--gray-700);border-right:1px solid var(--gray-300);
  }
  .phone-field input{border:none;border-radius:0;}
  .phone-field input:focus{border:none;}

  @media(max-width:960px){
    .grid{grid-template-columns:repeat(3,1fr);}
  }
  @media(max-width:640px){
    header.site{padding:12px 16px;}
    .search-wrap,.hero-wrap,.section{padding:0 16px;}
    .grid{grid-template-columns:repeat(2,1fr);}
    .hero{padding:28px 22px;}
    .hero h1{font-size:19px;}
    .footer-inner{flex-direction:column;}
  }
</style>
</head>
<body>

{{-- ===== Header ===== --}}
@include('navbar')


{{-- ===== Search Bar ===== --}}
<div class="search-wrap">
  <form class="search-bar" method="GET" action="{{ url('/') }}">
    <span aria-hidden="true">⌕</span>
    <input type="search" name="search" value="{{ $search ?? request('search') }}" placeholder="ค้นหาชื่อคอนเสิร์ต ศิลปิน หรือองค์กร" aria-label="ค้นหาคอนเสิร์ต ศิลปิน หรือองค์กร">
    <button type="submit">ค้นหา</button>
  </form>
</div>

{{-- ===== Hero Banner ===== --}}
<div class="hero-wrap">
  <div class="hero">
    @if($highlights->isNotEmpty())
      @foreach($highlights as $index => $highlight)
      <div class="hero-slide {{ $index === 0 ? 'active' : '' }}" data-slide="{{ $index }}">
        <span class="hero-badge">HIGHLIGHT LIVE CONCERT</span>
        <h1>{{ $highlight->name }}</h1>
        <p>{{ $highlight->artist }} — {{ $highlight->venue }}</p>
      </div>
      @endforeach
    @else
      {{-- แสดง layout เหมือนในรูป แต่ยังไม่มีข้อมูลจริง --}}
      <div class="hero-slide active">
        <span class="hero-badge">HIGHLIGHT LIVE CONCERT</span>
        <h1>&nbsp;</h1>
        <p>&nbsp;</p>
      </div>
    @endif
    <button class="hero-arrow left" id="heroPrev">‹</button>
    <button class="hero-arrow right" id="heroNext">›</button>
  </div>
  <div class="dots">
    @if($highlights->count() > 1)
      @foreach($highlights as $index => $dot)
      <span class="{{ $index === 0 ? 'active' : '' }}" data-dot="{{ $index }}"></span>
      @endforeach
    @else
      <span class="active"></span><span></span><span></span>
    @endif
  </div>
</div>

{{-- ===== Recommend Concerts ===== --}}
<div class="section">
  <div class="section-head">
    <div class="section-title">{{ ($search ?? '') !== '' ? 'ผลการค้นหา' : 'Recommend Concerts' }}</div>
    <a href="#" class="show-all">Show All</a>
  </div>

  <div class="grid">
    @php
      $colorClasses = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6'];

      // ผูก "ประเภท tag" -> คลาสสี. ปรับ key ให้ตรงกับค่าจริงที่มาจาก $concert->tag_type ได้เลย
      $tagTypeToClass = [
        'concert'      => 'tag-default',     // ส้ม (ค่า default)
        'highlight'    => 'tag-highlight',    // แดง
        'on_concert'   => 'tag-on-concert',   // เขียว
        'fan_meeting'  => 'tag-fan-meeting',  // ชมพู
        'live_concert' => 'tag-live',         // เขียวมิ้นท์
      ];
    @endphp

    @if($concerts->isNotEmpty())
      @foreach($concerts as $index => $concert)
      @php
        $colorClass = $colorClasses[$index % 6];
        $concertDate = \Carbon\Carbon::parse($concert->date_time);
        $isFuture = $concertDate->isFuture();

        // สถานะปุ่ม: sold_out / coming_soon / on_sale
        // ถ้ามี field $concert->status ในโมเดลอยู่แล้วให้ใช้ค่านั้นก่อน ไม่งั้น fallback ตามวันที่
        $status = $concert->status ?? ($isFuture ? 'coming_soon' : 'on_sale');

        if ($status === 'sold_out') {
            $btnClass = 'sold-out';
            $btnText = 'Sold out';
        } elseif ($status === 'coming_soon') {
            $btnClass = 'soon';
            $btnText = 'Coming Soon';
        } else {
            $btnClass = 'buy';
            $btnText = 'Buy Now';
        }

        // ป้าย tag: ใช้ $concert->tag_type + $concert->tag_text ถ้ามีในโมเดล
        $tagType = $concert->tag_type ?? 'concert';
        $tagClass = $tagTypeToClass[$tagType] ?? 'tag-default';
        $tagText = $concert->tag_text ?? 'CONCERT';
      @endphp
      <div class="card">
        <div class="card-img {{ $colorClass }}">
          <span class="card-artist">{{ $concert->artist }}</span>
          <span class="tag {{ $tagClass }}">{{ $tagText }}</span>
        </div>
        <div class="card-body">
          <div class="title">{{ $concert->name }}</div>
          <div class="date">{{ $concertDate->translatedFormat('j F Y') }}</div>
          @if($btnClass === 'buy')
            <a href="{{ url('/booking?concert_id=' . $concert->concert_id) }}" class="card-btn buy">{{ $btnText }}</a>
          @else
            <span class="card-btn {{ $btnClass }}">{{ $btnText }}</span>
          @endif
        </div>
      </div>
      @endforeach
    @elseif(($search ?? '') !== '')
      <div style="grid-column:1/-1;background:#fff;border:1px solid var(--gray-300);border-radius:10px;padding:42px 20px;text-align:center;color:var(--gray-500);font-size:14px;">
        ไม่พบคอนเสิร์ตที่ตรงกับ “{{ $search }}”
      </div>
    @else
      {{-- แสดง card placeholder 12 ใบ (6x2) ตาม layout ในรูป แต่ยังไม่มีข้อมูลจริง --}}
      @for($i = 0; $i < 12; $i++)
      <div class="card">
        <div class="card-img {{ $colorClasses[$i % 6] }}">
          <span class="card-artist">&nbsp;</span>
          <span class="tag tag-default">&nbsp;</span>
        </div>
        <div class="card-body">
          <div class="title">&nbsp;</div>
          <div class="date">&nbsp;</div>
          <span class="card-btn soon">&nbsp;</span>
        </div>
      </div>
      @endfor
    @endif
  </div>
</div>

{{-- ===== Footer ===== --}}
<footer class="site">
  <div class="footer-inner">
    <div>
      <div class="footer-logo"><img src="{{ asset('images/logo.png') }}" alt="TEAM 3 Ticket" style="width:32px;height:32px;object-fit:contain;mix-blend-mode:screen;"> TEAM <span style="color:var(--orange);margin:0 2px;">3</span> Ticket</div>
      <p class="footer-desc">ระบบจองตั๋วคอนเสิร์ตและกิจกรรมดนตรีชั้นนำ อย่างรวดเร็ว<br>ปลอดภัย พร้อมสิทธิพิเศษสำหรับสมาชิก</p>
    </div>
    <div>
      <div class="footer-contact-title">ช่องทางติดต่อเรา</div>
      <div class="footer-contact">
        <p>169 ถนนพหลโยธิน จตุจักร</p>
        <p>กรุงเทพมหานคร รหัสไปรษณีย์ 10131</p>
        <p>096-878-6767 (จันทร์-ศุกร์ 09:00 - 18:00 น.)</p>
        <p>support@team3ticket.com</p>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© 2026 <strong>TEAM 3 Ticket</strong>. All rights reserved.</span>
    <span><a href="#">เงื่อนไขการใช้งาน</a> • <a href="#">นโยบายความเป็นส่วนตัว</a> • <a href="#">ติดต่อเรา</a></span>
  </div>
</footer>

{{-- ===== Login Modal ===== --}}
<div class="overlay" id="loginOverlay">
  <div class="modal">
    <button class="modal-close" data-close>✕</button>
    <h2>เข้าสู่ระบบ</h2>
    <p class="sub">ยินดีต้อนรับกลับมาอีกครั้ง</p>

    {{-- แสดง error --}}
    @if(session('open_modal') === 'login' && $errors->any())
      <div class="modal-error">{{ $errors->first() }}</div>
    @endif

    <form id="loginForm" method="POST" action="{{ route('auth.login') }}">
      @csrf
      <div class="field">
        <label>อีเมล</label>
        <input type="email" name="email" placeholder="example@email.com"
               value="{{ old('email') }}" required>
      </div>
      <div class="field">
        <label>รหัสผ่าน</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <div class="options-row">
        <label><input type="checkbox" name="remember" style="width:auto;"> จดจำฉัน</label>
        <a href="#">ลืมรหัสผ่าน?</a>
      </div>
      <button type="submit" class="btn-orange">เข้าสู่ระบบ</button>
    </form>
    <p class="switch-text">ยังไม่มีบัญชี? <a href="#" id="toRegister">สมัครสมาชิก</a></p>
  </div>
</div>

{{-- ===== Register Modal ===== --}}
<div class="overlay" id="registerOverlay">
  <div class="modal">
    <button class="modal-close" data-close>✕</button>
    <h2>สมัครสมาชิก</h2>
    <p class="sub">เป็นสมาชิกอยู่แล้ว? <a href="#" id="toLoginFromSub" style="color:var(--orange-dark);font-weight:700;text-decoration:none;">เข้าสู่ระบบ</a></p>

    {{-- แสดง error --}}
    @if(session('open_modal') === 'register' && $errors->any())
      <div class="modal-error">{{ $errors->first() }}</div>
    @endif

    <form id="registerForm" method="POST" action="{{ route('auth.register') }}">
      @csrf
      <div class="field">
        <label>อีเมล <span class="req">*</span></label>
        <input type="email" name="email" placeholder="example@email.com"
               value="{{ old('email') }}" required>
      </div>
      <div class="field">
        <label>รหัสผ่าน <span class="req">*</span></label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <div class="field">
        <label>ยืนยันรหัสผ่าน <span class="req">*</span></label>
        <input type="password" name="password_confirmation" placeholder="••••••••" required>
      </div>
      <div class="row-2">
        <div class="field">
          <label>ชื่อ <span class="req">*</span></label>
          <input type="text" name="first_name" placeholder="ชื่อจริงตามบัตรประชาชน"
                 value="{{ old('first_name') }}" required>
        </div>
        <div class="field">
          <label>นามสกุล <span class="req">*</span></label>
          <input type="text" name="last_name" placeholder="นามสกุลตามบัตรประชาชน"
                 value="{{ old('last_name') }}" required>
        </div>
      </div>
      <div class="field">
        <label>เบอร์โทรศัพท์ <span class="req">*</span></label>
        <div class="phone-field">
          <span class="phone-prefix">🇹🇭 +66</span>
          <input type="tel" name="phone" placeholder="0XXXXXXXX"
                 value="{{ old('phone') }}" required>
        </div>
      </div>
      <button type="submit" class="btn-orange" style="margin-top:6px;">สมัครสมาชิก</button>
    </form>
  </div>
</div>


<script>
  // ----- Hero Slider -----
  const slides = document.querySelectorAll('.hero-slide');
  const dots = document.querySelectorAll('.dots span');
  let currentSlide = 0;

  function showSlide(index) {
    if (slides.length === 0) return;
    slides.forEach(s => s.classList.remove('active'));
    dots.forEach(d => d.classList.remove('active'));
    currentSlide = (index + slides.length) % slides.length;
    slides[currentSlide].classList.add('active');
    if (dots[currentSlide]) dots[currentSlide].classList.add('active');
  }

  const prevBtn = document.getElementById('heroPrev');
  const nextBtn = document.getElementById('heroNext');
  if (prevBtn) prevBtn.addEventListener('click', () => showSlide(currentSlide - 1));
  if (nextBtn) nextBtn.addEventListener('click', () => showSlide(currentSlide + 1));
  dots.forEach((dot, i) => dot.addEventListener('click', () => showSlide(i)));

  // Auto-slide ทุก 5 วินาที
  if (slides.length > 1) {
    setInterval(() => showSlide(currentSlide + 1), 5000);
  }

  // ----- Modal logic -----
  const loginOverlay    = document.getElementById('loginOverlay');
  const registerOverlay = document.getElementById('registerOverlay');

  function openModal(el)  { el.classList.add('open'); }
  function closeModal(el) { el.classList.remove('open'); }
  function closeAll()     { closeModal(loginOverlay); closeModal(registerOverlay); }

  const openLoginBtn = document.getElementById('openLogin');
  if (openLoginBtn) {
    openLoginBtn.addEventListener('click', () => openModal(loginOverlay));
  }

  document.getElementById('toRegister').addEventListener('click', (e) => {
    e.preventDefault(); closeModal(loginOverlay); openModal(registerOverlay);
  });
  document.getElementById('toLoginFromSub').addEventListener('click', (e) => {
    e.preventDefault(); closeModal(registerOverlay); openModal(loginOverlay);
  });

  document.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', closeAll);
  });
  [loginOverlay, registerOverlay].forEach(ov => {
    ov.addEventListener('click', (e) => { if(e.target === ov) closeAll(); });
  });
  document.addEventListener('keydown', (e) => { if(e.key==='Escape') closeAll(); });

  // ----- เปิด modal อัตโนมัติเมื่อ server ส่ง error กลับมา -----
  @if(session('open_modal') === 'login')
    openModal(loginOverlay);
  @elseif(session('open_modal') === 'register')
    openModal(registerOverlay);
  @endif
</script>

</body>
</html>
