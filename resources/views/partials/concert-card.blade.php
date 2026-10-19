{{--
 * concert-card.blade.php
 * การ์ดคอนเสิร์ต ใช้ในหน้า Home และหน้าคอนเสิร์ตทั้งหมด
 * @include('partials.concert-card', ['concert' => $concert, 'index' => $loop->index, 'detailed' => true])
 * detailed = แสดงสถานที่ + ราคาเริ่มต้น (ต้อง query ด้วย withMin('zones', 'price'))
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 09-10-2569
--}}
@php
  $detailed = $detailed ?? false;
  $colorClass = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6'][($index ?? 0) % 6];
  $concertDate = $concert->event_datetime ? \Carbon\Carbon::parse($concert->event_datetime) : null;

  // ปุ่มตาม concert.status: ON_SALE = ซื้อได้, SOLD_OUT = ขายหมด, อื่น ๆ = Coming Soon
  [$btnClass, $btnText] = match (strtolower((string) $concert->status)) {
      'on_sale'  => ['buy', 'Buy Now'],
      'sold_out' => ['sold-out', 'Sold out'],
      default    => ['soon', 'Coming Soon'],
  };

  $minPrice = $concert->zones_min_price ?? null;
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
    <div class="date">{{ $concertDate?->thDate() ?? '-' }}</div>
    @if($detailed)
      @if($concert->venue)
        <div class="card-venue" title="{{ $concert->venue }}">{{ $concert->venue }}</div>
      @endif
      @if($minPrice !== null)
        <div class="card-price">เริ่มต้น <strong>฿{{ number_format((float) $minPrice) }}</strong></div>
      @endif
    @endif
    @if($btnClass === 'buy')
      <a href="{{ url('/concert-detail?concert_id=' . $concert->concert_id) }}" class="card-btn buy">{{ $btnText }}</a>
    @else
      <span class="card-btn {{ $btnClass }}">{{ $btnText }}</span>
    @endif
  </div>
</div>
