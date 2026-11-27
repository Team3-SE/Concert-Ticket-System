{{--
 * concert-card.blade.php
 * การ์ดคอนเสิร์ต ใช้ในหน้า Home และหน้าคอนเสิร์ตทั้งหมด
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 09-10-2569
 * @Update Date 27/11/2569 67160292 ธีรเมธ พลสาย
--}}
{{--
 * @include('user.partials.concert-card', ['concert' => $concert, 'index' => $loop->index, 'detailed' => true])
 * detailed = แสดงสถานที่
--}}
@php
  $detailed = $detailed ?? false;
  $colorClass = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6'][($index ?? 0) % 6];
  $concertDate = $concert->event_datetime ? \Carbon\Carbon::parse($concert->event_datetime) : null;

  // ปุ่มตามสถานะที่ระบบคำนวณ (Concert::displayStatus): ซื้อได้เฉพาะ ON_SALE ที่ยังอยู่ในช่วงขาย ยังไม่ถึงเวลาแสดง และบัตรยังไม่หมด
  [$btnClass, $btnText] = match ($concert->displayStatus()) {
      \App\Models\Concert::STATUS_ON_SALE       => ['buy', 'Buy Now'],
      \App\Models\Concert::STATUS_SOLD_OUT      => ['sold-out', 'Sold out'],
      \App\Models\Concert::STATUS_CANCELLED     => ['sold-out', 'Cancelled'],
      \App\Models\Concert::DISPLAY_COMPLETED    => ['sold-out', 'Ended'],
      \App\Models\Concert::DISPLAY_SALE_CLOSED  => ['sold-out', 'Sales closed'],
      default                                    => ['soon', 'Coming Soon'],
  };
@endphp
<div class="card">
  <div class="card-img {{ $colorClass }}">
    <span class="card-artist">{{ $concert->artist }}</span>
    @if($concert->poster_path)
      {{-- ถ้ารูปโหลดไม่ได้ ซ่อนรูปแล้วแสดงชื่อศิลปินแทน --}}
      <img class="card-poster" src="{{ asset('storage/' . $concert->poster_path) }}" alt="{{ $concert->name }}" loading="lazy" onerror="this.remove()">
    @endif
  </div>
  <div class="card-body">
    <div class="title">{{ $concert->name }}</div>
    <div class="date">{{ $concertDate?->thDateLong() ?? '-' }}</div>
    @if($detailed)
      @if($concert->venue)
        <div class="card-venue" title="{{ $concert->venue }}">{{ $concert->venue }}</div>
      @endif
    @endif
    @if($btnClass === 'buy')
      <a href="{{ url('/concert-detail?concert_id=' . $concert->c_id) }}" class="card-btn buy">{{ $btnText }}</a>
    @else
      <span class="card-btn {{ $btnClass }}">{{ $btnText }}</span>
    @endif
  </div>
</div>
