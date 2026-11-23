{{--
 * zone-overview.blade.php
 * ส่วน "โซนทั้งหมด": รูปผังที่นั่ง (กรอบ 1:1) + ตารางโซน ราคา และที่นั่งว่าง ใช้ร่วมกันในหน้า booking และ concert-detail
 * @include('user.partials.zone-overview', ['concert' => $concert, 'zones' => $zones, 'selectable' => true])
 * zones      = [['name', 'price', 'color', 'seats', 'capacity'], ...] จาก BookingController
 * selectable = กดแถวเพื่อเลือกโซนได้ (ใช้ในหน้า booking ที่อยู่ใน Alpine bookingApp)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 21/10/2569
--}}
@php
  $selectable = $selectable ?? false;
@endphp
<div class="space-y-4">
  <h3 class="section-label">โซนทั้งหมด</h3>

  <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">

    {{-- ฝั่งซ้าย: รูปผังที่นั่ง กรอบใหญ่กว่ารูป 12px รูปแสดงสัดส่วน 1:1 (รูปที่ไม่ใช่ 1:1 จะถูกตัดขอบ) --}}
    <div class="md:col-span-4 bg-white p-3 rounded-xl shadow-sm border border-[#DFE6F2]">
      @if($concert?->seat_map_image_path)
        <img
          src="{{ asset('storage/' . $concert->seat_map_image_path) }}"
          alt="ผังที่นั่ง {{ $concert->name }}"
          class="block w-full aspect-square object-cover rounded-lg"
          onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'aspect-square flex items-center justify-center text-xs text-gray-400',textContent:'ไม่พบรูปผังที่นั่ง'}))">
      @else
        <div class="aspect-square flex items-center justify-center text-xs text-gray-400">ยังไม่มีรูปผังโซนที่นั่ง</div>
      @endif
    </div>

    {{-- ฝั่งขวา: ตารางโซน (Figma: ตัวอักษร 16px จัดกึ่งกลาง เส้นคั่นแถวเส้นประ) --}}
    <div class="md:col-span-8 self-stretch bg-white p-[25px] rounded-xl border border-[#DFE6F2] overflow-x-auto">
      <table class="w-full min-w-[360px] text-center text-[16px]">
        <thead>
          <tr class="text-[#5B6580] border-b border-[#DFE6F2]">
            <th class="pb-4 pt-0.5 font-semibold tracking-[0.5px] uppercase w-1/3">โซน</th>
            <th class="pb-4 pt-0.5 font-semibold tracking-[0.5px] uppercase w-1/3">ราคา</th>
            <th class="pb-4 pt-0.5 font-semibold tracking-[0.5px] uppercase w-1/3">ที่นั่งว่าง</th>
          </tr>
        </thead>
        <tbody>
          @forelse($zones as $zone)
            @php
              $seatsLeft = (int) ($zone['seats'] ?? 0);
              $capacity  = (int) ($zone['capacity'] ?? 0);
              // Figma: แดง = เหลือ 0, เหลือง = เหลือ <= 30% ของโซน, เขียว = เหลือ > 30%
              $badgeClass = $seatsLeft === 0
                  ? 'bg-[#E6483C]'
                  : (($capacity > 0 && $seatsLeft / $capacity <= 0.3) ? 'bg-[#E79B1F]' : 'bg-[#1FA855]');
            @endphp
            <tr class="border-b border-dashed border-[#DFE6F2] {{ $selectable ? 'hover:bg-blue-50/50 cursor-pointer transition-colors' : '' }}"
                @if($selectable) @click="selectZoneByName(@js($zone['name']))" @endif>
              {{-- Figma: จุดสี + ชื่อโซน ชิดซ้ายที่ระยะ 37% ของคอลัมน์ (120 / 319px) จุดสีจึงเรียงตรงกันทุกแถว --}}
              <td class="py-[17px] text-left">
                <div class="flex items-center gap-2.5 ml-[37%]">
                  <span class="w-3 h-3 rounded-[3px] inline-block flex-shrink-0" style="background-color: {{ $zone['color'] ?: '#4D7EE0' }}"></span>
                  <span class="font-medium text-[#152040]">{{ $zone['name'] }}</span>
                </div>
              </td>
              <td class="py-[17px] font-medium text-[#152040]">{{ number_format((float) $zone['price']) }} บาท</td>
              <td class="py-[15px]">
                <span class="px-3 py-1 text-[16px] text-white font-bold rounded-xl inline-block text-center {{ $badgeClass }}">
                  {{ $seatsLeft === 0 ? 'Sold out' : number_format($seatsLeft) }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="py-8 text-center text-gray-400">ยังไม่มีข้อมูลโซนบัตร</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
</div>
