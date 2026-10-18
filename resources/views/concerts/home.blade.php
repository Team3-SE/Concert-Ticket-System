{{--
 * home.blade.php
 * หน้า Home: ช่องค้นหา แบนเนอร์ไฮไลต์ คอนเสิร์ตแนะนำ และหน้าต่างเข้าสู่ระบบ/สมัครสมาชิก
 * @Author
 * @Create Date
--}}
@extends('layouts.main')

@section('title', 'TEAM 3 Ticket')
@section('body_class', 'page-home')

@section('content')
{{-- Search Bar --}}
<div class="search-wrap">
  <div class="search-box">
    <form class="search-bar" method="GET" action="{{ url('/') }}" role="search"
          data-concert-search data-suggest-url="{{ route('concerts.suggest') }}">
      <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" name="search" value="{{ $search ?? request('search') }}"
             placeholder="ค้นหาชื่อคอนเสิร์ต ศิลปิน สถานที่ หรือผู้จัด"
             aria-label="ค้นหาคอนเสิร์ต" autocomplete="off" spellcheck="false"
             role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="searchSuggest">
    </form>
    <div class="search-suggest" id="searchSuggest" role="listbox" aria-label="คอนเสิร์ตที่ใกล้เคียง" hidden></div>
  </div>
</div>

{{-- Hero Banner --}}
<div class="hero-wrap">
  <div class="hero">
    @if($highlights->isNotEmpty())
      @foreach($highlights as $index => $highlight)
      @php
        // ถ้าคอนเสิร์ตมีแบนเนอร์ (banner_path) ใช้รูปเป็นพื้นหลังสไลด์ ถ้าไม่มีใช้พื้นไล่สีเดิม
        $bannerUrl = $highlight->banner_path ? asset('storage/' . $highlight->banner_path) : null;
      @endphp
      <div class="hero-slide {{ $index === 0 ? 'active' : '' }} {{ $bannerUrl ? 'has-banner' : '' }}" data-slide="{{ $index }}">
        @if($bannerUrl)
          {{-- รูปโหลดไม่ได้: ลบรูปแล้วกลับไปแสดงสไลด์แบบข้อความ --}}
          <a class="hero-banner-link" href="{{ url('/booking?concert_id=' . $highlight->concert_id) }}" aria-label="{{ $highlight->name }}">
            <img class="hero-banner-img" src="{{ $bannerUrl }}" alt="{{ $highlight->name }}"
                 onerror="this.closest('.hero-slide').classList.remove('has-banner'); this.closest('a').remove();">
          </a>
        @endif
        <h1>{{ $highlight->name }}</h1>
        <p>{{ $highlight->artist }} — {{ $highlight->venue }}</p>
      </div>
      @endforeach
    @else
      {{-- แสดง layout เหมือนในรูป แต่ยังไม่มีข้อมูลจริง --}}
      <div class="hero-slide active">
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

{{-- Recommend Concerts --}}
<div class="section">
  <div class="section-head">
    <div class="section-title">{{ ($search ?? '') !== '' ? 'ผลการค้นหา' : 'Recommend Concerts' }}</div>
    <a href="{{ route('concerts.index', array_filter(['search' => $search ?? null])) }}" class="show-all">Show All</a>
  </div>

  <div class="grid">
    @php
      $colorClasses = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6'];
    @endphp

    @if($concerts->isNotEmpty())
      @foreach($concerts as $index => $concert)
        @include('partials.concert-card', ['concert' => $concert, 'index' => $index])
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

{{-- Success Modal: ยืนยันอีเมลสำเร็จ --}}
<div class="overlay" id="verifiedOverlay">
  <div class="modal verified-modal" role="dialog" aria-modal="true" aria-labelledby="verifiedTitle">
    <div class="verified-icon">
      <span class="verified-ring"></span>
      <span class="verified-check">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="5 12.5 10 17.5 19 7.5"/></svg>
      </span>
    </div>
    <h2 id="verifiedTitle">ยืนยันอีเมลสำเร็จ</h2>
    <p class="verified-text">บัญชีของคุณพร้อมใช้งานแล้ว<br>ยินดีต้อนรับสู่ TEAM 3 Ticket</p>
    <a class="verified-cta" href="{{ route('concerts.index') }}">เลือกคอนเสิร์ตเลย</a>
    <button type="button" class="verified-close" data-close>ปิดหน้าต่างนี้</button>
  </div>
</div>

{{-- Login Modal --}}
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

{{-- Register Modal --}}
<div class="overlay" id="registerOverlay">
  <div class="modal">
    <button class="modal-close" data-close>✕</button>
    <h2>สมัครสมาชิก</h2>
    <p class="sub">เป็นสมาชิกอยู่แล้ว? <a href="#" id="toLoginFromSub" style="color:var(--orange-dark);font-weight:700;text-decoration:none;">เข้าสู่ระบบ</a></p>

    {{-- แสดง error --}}
    @if(session('open_modal') === 'register' && $errors->any())
      <div class="modal-error">
        {{ $errors->first() }}
        @if(session('pending_verify'))
          <a href="{{ route('verification.notice') }}">ส่งลิงก์ยืนยันอีกครั้ง</a>
        @endif
      </div>
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
@endsection

@push('scripts')
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
  const verifiedOverlay = document.getElementById('verifiedOverlay');

  function openModal(el)  { el.classList.add('open'); }
  function closeModal(el) { el.classList.remove('open'); }
  function closeAll()     { closeModal(loginOverlay); closeModal(registerOverlay); closeModal(verifiedOverlay); }

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

  document.addEventListener('keydown', (e) => { if(e.key==='Escape') closeAll(); });

  // ----- เปิด modal อัตโนมัติเมื่อ server ส่ง error กลับมา -----
  // มาจากปุ่ม "เข้าสู่ระบบ" ของหน้าอื่น (/?login=1)
  if (new URLSearchParams(window.location.search).get('login') === '1') openModal(loginOverlay);

  @if(session('verified'))
    openModal(verifiedOverlay);
  @endif

  @if(session('open_modal') === 'login')
    openModal(loginOverlay);
  @elseif(session('open_modal') === 'register')
    openModal(registerOverlay);
  @endif
</script>
@endpush
