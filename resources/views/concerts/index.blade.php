{{--
 * index.blade.php
 * หน้าคอนเสิร์ตทั้งหมด (Show All): ค้นหา กรองสถานะ เรียงลำดับ และแบ่งหน้า
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 09-10-2569
--}}
@extends('layouts.main')

@section('title', 'คอนเสิร์ตทั้งหมด - TEAM 3 Ticket')
{{-- page-home: ใช้สไตล์การ์ด/grid ร่วมกับหน้า Home, page-concerts: สไตล์เฉพาะหน้านี้ --}}
@section('body_class', 'page-home page-concerts')

@section('content')
<div class="concerts-page">

  {{-- breadcrumb start --}}
  <nav class="crumbs" aria-label="breadcrumb">
    <a href="{{ url('/') }}">หน้าแรก</a>
    <span aria-hidden="true">›</span>
    <span>คอนเสิร์ตทั้งหมด</span>
  </nav>
  {{-- breadcrumb end --}}

  {{-- page-heading start --}}
  <div class="concerts-head">
    <h1 class="concerts-title">คอนเสิร์ตทั้งหมด</h1>
    <p class="concerts-count">
      @if($search !== '')
        ผลการค้นหา “<strong>{{ $search }}</strong>” ·
      @endif
      พบ <strong>{{ number_format($concerts->total()) }}</strong> คอนเสิร์ต
    </p>
  </div>
  {{-- page-heading end --}}

  {{-- search-toolbar start: ค้นหา + เรียงลำดับ --}}
  <div class="concerts-toolbar">
    <div class="search-box">
      <form class="search-bar" id="concertFilters" method="GET" action="{{ route('concerts.index') }}" role="search"
            data-concert-search data-suggest-url="{{ route('concerts.suggest') }}">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" name="search" value="{{ $search }}"
               placeholder="ค้นหาชื่อคอนเสิร์ต ศิลปิน สถานที่ หรือผู้จัด"
               aria-label="ค้นหาคอนเสิร์ต" autocomplete="off" spellcheck="false"
               role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="searchSuggest">
        @if($status !== '')
          <input type="hidden" name="status" value="{{ $status }}">
        @endif
      </form>
      <div class="search-suggest" id="searchSuggest" role="listbox" aria-label="คอนเสิร์ตที่ใกล้เคียง" hidden></div>
    </div>

    <label class="sort-select">
      <span>เรียงตาม</span>
      <select name="sort" form="concertFilters" data-auto-submit>
        @foreach($sorts as $value => $label)
          <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
  </div>
  {{-- search-toolbar end --}}

  {{-- status-tabs start: แท็บสถานะ --}}
  <div class="status-tabs">
    @foreach(['' => 'ทั้งหมด'] + $statusFilters as $value => $label)
      @php
        $tabUrl = route('concerts.index', array_filter([
            'search' => $search,
            'status' => $value,
            'sort'   => $sort !== 'date' ? $sort : null,
        ]));
        $tabCount = $value === '' ? $allCount : (int) ($statusCounts[$value] ?? 0);
      @endphp
      <a href="{{ $tabUrl }}" class="status-tab {{ $status === $value ? 'active' : '' }}" @if($status === $value) aria-current="true" @endif>
        {{ $label }} <span class="count">{{ $tabCount }}</span>
      </a>
    @endforeach
  </div>
  {{-- status-tabs end --}}

  {{-- concert-list start --}}
  @if($concerts->isNotEmpty())
    <div class="grid concerts-grid">
      @foreach($concerts as $concert)
        @include('partials.concert-card', ['concert' => $concert, 'index' => $loop->index, 'detailed' => true])
      @endforeach
    </div>

    <div class="concerts-pager">
      @include('partials.pagination', ['paginator' => $concerts, 'unit' => 'คอนเสิร์ต', 'framed' => true])
    </div>
  @else
    <div class="concerts-empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M8.5 11h5"/></svg>
      <p class="concerts-empty-title">
        @if($search !== '')
          ไม่พบคอนเสิร์ตที่ตรงกับ “{{ $search }}”
        @else
          ยังไม่มีคอนเสิร์ตในหมวดนี้
        @endif
      </p>
      <p>ลองค้นหาด้วยชื่อศิลปิน สถานที่ หรือเลือกสถานะอื่น</p>
      <a href="{{ route('concerts.index') }}" class="concerts-reset">ดูคอนเสิร์ตทั้งหมด</a>
    </div>
  @endif
  {{-- concert-list end --}}

</div>
@endsection

@push('scripts')
<script>
  // เปลี่ยนการเรียงลำดับแล้วโหลดผลใหม่ทันที
  document.querySelectorAll('[data-auto-submit]').forEach((el) => {
    el.addEventListener('change', () => el.form.submit());
  });
</script>
@endpush
