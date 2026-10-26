{{--
 * footer.blade.php
 * ส่วนท้ายของเว็บ: ข้อมูลติดต่อและลิงก์นโยบาย
 * @Author
 * @Create Date
--}}
<footer class="bg-[#0B2F61] text-white border-t-2 border-[#F47B1F] mt-12 w-full">
  <div class="max-w-6xl mx-auto px-6 md:px-8 pt-8 pb-6">

    {{-- TOP SECTION: LOGO & CONTACT --}}
    <div class="flex flex-col md:flex-row justify-between items-start gap-8 pb-8">

      {{-- LEFT: LOGO & ABOUT --}}
      <div class="space-y-2.5 max-w-sm">
        <div class="flex items-center space-x-2">
          <img src="{{ asset('images/logo.png') }}" alt="TEAM 3 Ticket Logo" class="w-10 h-10 object-contain" style="mix-blend-mode:screen;">
          <span class="font-bold text-[22px] tracking-wide">TEAM <span class="text-[#F47B1F]">3</span> Ticket</span>
        </div>
        <p class="text-gray-300 text-[11px] leading-relaxed">
          ระบบจองตั๋วคอนเสิร์ตสะดวก รวดเร็ว ปลอดภัย<br>
          พร้อมเลือกโซนและที่นั่งได้ทันที
        </p>
      </div>

      {{-- RIGHT: CONTACT US --}}
      <div class="space-y-2.5">
        <h4 class="font-bold text-xs text-[#F47B1F]">ช่องทางติดต่อเรา</h4>
        <div class="space-y-2 text-[11px] text-gray-300">

          {{-- Address --}}
          <p class="flex items-start gap-2.5">
            <svg class="w-4 h-4 text-sky-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6-5.686-6-10A6 6 0 0 1 18 11c0 4.314-6 10-6 10z"/>
              <circle cx="12" cy="11" r="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>169 ถนนลงหาดบางแสน ตำบลแสนสุข<br>อำเภอเมือง จังหวัดชลบุรี 20131</span>
          </p>

          {{-- Phone --}}
          <p class="flex items-center gap-2.5">
            <svg class="w-4 h-4 text-sky-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            <span>098-676-6767 (เปิดบริการ 09:00 - 18:00 น.)</span>
          </p>

          {{-- Email --}}
          <p class="flex items-center gap-2.5">
            <svg class="w-4 h-4 text-sky-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span>support@team3ticket.com</span>
          </p>

        </div>
      </div>

    </div>

    {{-- BOTTOM SECTION: COPYRIGHT & POLICY LINKS --}}
    <div class="pt-4 border-t border-white/10 flex flex-col sm:flex-row justify-between items-center text-[10px] text-gray-400 gap-3">
      <div>
        © 2026 <span class="font-semibold text-gray-300">TEAM 3 Ticket</span>. All rights reserved.
      </div>
      <div class="flex items-center space-x-3 text-gray-300">
        <a href="#" class="hover:underline">เงื่อนไขการใช้งาน</a>
        <span>•</span>
        <a href="#" class="hover:underline">นโยบายความเป็นส่วนตัว</a>
        <span>•</span>
        <a href="#" class="hover:underline">ช่วยเหลือ</a>
      </div>
    </div>

  </div>
</footer>
