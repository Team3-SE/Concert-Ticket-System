{{--
 * pagination.blade.php
 * ตัวแบ่งหน้า: ข้อความสรุปจำนวน และปุ่มเปลี่ยนหน้า
 * @include('partials.pagination', ['paginator' => $items, 'unit' => 'คำสั่งซื้อ', 'framed' => true])
 *
 * - framed = มีกรอบครอบ (ใช้เมื่อไม่ได้อยู่ท้ายการ์ด/ตารางที่มีกรอบอยู่แล้ว)
 *
 * - ฝั่งซ้าย: "แสดง 1 - 10 จากทั้งหมด 50 {unit}"
 * - ฝั่งขวา: « ก่อนหน้า | เลขหน้า 3 หน้าที่เลื่อนตามหน้าปัจจุบัน | … | หน้าสุดท้าย | ถัดไป »
 * - กด "…" เพื่อพิมพ์เลขหน้าแล้วกระโดดไปได้ (resources/js/pagination.js)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 09-10-2569
--}}
@php
  $unit = $unit ?? 'รายการ';
  $framed = $framed ?? false;
  $current = $paginator->currentPage();
  $last = $paginator->lastPage();

  // หน้าต่างเลขหน้า: ไล่ย้อนหลังจากหน้าปัจจุบัน 3 หน้า แล้วเลื่อนตามหน้าที่อยู่
  $lead = 3;
  $windowStart = max(1, $current - $lead + 1);
  $windowEnd = $current;
  if ($windowEnd - $windowStart + 1 < $lead) {
      $windowEnd = min($last, $windowStart + $lead - 1);
  }

  $items = [];
  for ($i = $windowStart; $i <= $windowEnd; $i++) {
      $items[] = $i;
  }

  $gap = $last - $windowEnd - 1;
  if ($gap === 1) {
      // เหลือหน้าเดียวที่ถูกซ่อน แสดงเลขนั้นตรง ๆ ดีกว่าใช้ "…" แทนแค่หนึ่งหน้า
      $items[] = $windowEnd + 1;
      $items[] = $last;
  } elseif ($gap >= 2) {
      $items[] = 'dots';
      $items[] = $last;
  } elseif ($windowEnd < $last) {
      $items[] = $last;
  }
@endphp

<div class="pager-bar {{ $framed ? 'pager-bar--framed' : '' }}">
  <div class="pager-summary">
    @if($paginator->total() > 0)
      แสดง {{ number_format($paginator->firstItem()) }} - {{ number_format($paginator->lastItem()) }}
      จากทั้งหมด {{ number_format($paginator->total()) }} {{ $unit }}
    @else
      ไม่มี{{ $unit }}
    @endif
  </div>

  @if($paginator->hasPages())
    <nav class="pager" aria-label="เปลี่ยนหน้า"
         data-pager data-last-page="{{ $last }}"
         data-page-name="{{ $paginator->getPageName() }}" data-url="{{ $paginator->url(1) }}">

      @if($paginator->onFirstPage())
        <span class="pg-btn" aria-disabled="true">« ก่อนหน้า</span>
      @else
        <a class="pg-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">« ก่อนหน้า</a>
      @endif

      @foreach($items as $item)
        @if($item === 'dots')
          <div class="pg-dots-container">
            <button type="button" class="pg-dots" aria-label="เลือกหน้า" aria-haspopup="true" aria-expanded="false">…</button>
          </div>
        @elseif($item === $current)
          <span class="pg-btn num active" aria-current="page">{{ $item }}</span>
        @else
          <a class="pg-btn num" href="{{ $paginator->url($item) }}">{{ $item }}</a>
        @endif
      @endforeach

      @if($paginator->hasMorePages())
        <a class="pg-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">ถัดไป »</a>
      @else
        <span class="pg-btn" aria-disabled="true">ถัดไป »</span>
      @endif
    </nav>
  @endif
</div>
