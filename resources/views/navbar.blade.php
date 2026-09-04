<header class="bg-[#0B2F61] text-white px-8 md:px-16 py-3.5 flex justify-between items-center shadow-md relative z-30">

  <!-- LOGO SECTION -->
  <div class="flex items-center space-x-2.5">
    <img src="{{ asset('images/logo.png') }}" alt="TEAM 3 Ticket Logo" class="w-7 h-7 object-contain" style="mix-blend-mode:screen;">
    <span class="font-bold text-base tracking-wide">TEAM <span class="text-[#F47B1F]">3</span> Ticket</span>
  </div>

  <!-- PROFILE & DROPDOWN SECTION -->
  <div class="relative" x-data="{ open: false }">

    <!-- Profile Trigger Button -->
    <button @click="open = !open"
            @click.outside="open = false"
            class="flex items-center space-x-3 focus:outline-none cursor-pointer group">
      <span class="text-xs text-gray-200 font-medium hidden sm:inline group-hover:text-white transition">อนิรุธ สุขวิรกาล</span>
      <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden border border-white/20 group-hover:border-white transition">
        <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
        </svg>
      </div>
    </button>

    <!-- Profile Dropdown Popup (Matching Mockup) -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
         x-cloak
         class="absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 text-gray-800 z-50 overflow-hidden">

      <!-- Top Arrow Indicator -->
      <div class="absolute -top-2 right-3 w-4 h-4 bg-white rotate-45 border-t border-l border-gray-100"></div>

      <!-- User Header Info -->
      <div class="p-5 border-b border-gray-100 relative bg-white z-10">
        <h3 class="font-bold text-base text-[#1E293B]">อนิรุธ สุขวิรกาล</h3>
        <p class="text-xs text-gray-400 font-normal mt-0.5">ข้อมูลส่วนตัว</p>
      </div>

      <!-- Menu Items -->
      <div class="py-1 text-xs font-medium text-[#334155] relative bg-white z-10">

        <a href="#" class="block px-5 py-3 hover:bg-gray-50 hover:text-[#F47B1F] transition">
          ตั๋วของฉัน
        </a>

        <a href="#" class="block px-5 py-3 hover:bg-gray-50 hover:text-[#F47B1F] transition">
          ประวัติการสั่งซื้อ
        </a>

        <a href="#" class="block px-5 py-3 border-b border-gray-100 hover:bg-gray-50 hover:text-[#F47B1F] transition">
          แก้ไขข้อมูลส่วนตัว
        </a>

        <!-- Logout Item -->
        <a href="#" class="block px-5 py-3.5 text-gray-600 hover:bg-red-50 hover:text-red-500 transition">
          ออกจากระบบ
        </a>

      </div>

    </div>

  </div>

</header>
