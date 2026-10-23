{{--
 * home.blade.php
 * หน้า Home: ช่องค้นหา แบนเนอร์ไฮไลต์ และคอนเสิร์ตแนะนำ (หน้าต่างเข้าสู่ระบบ/สมัครสมาชิกย้ายไป partials/auth-modals)
 * @Author
 * @Create Date
 * Updated : 23/10/2569 67160292 ธีรเมธ พลสาย
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
          <a class="hero-banner-link" href="{{ url('/concert-detail?concert_id=' . $highlight->concert_id) }}" aria-label="{{ $highlight->name }}">
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

  // หน้าต่างเข้าสู่ระบบ/สมัครสมาชิก: ดู resources/js/auth-modal.js
</script>
@endpush
