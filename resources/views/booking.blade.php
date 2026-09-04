<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TEAM 3 Ticket - Concert Booking</title>

  <!-- Tailwind CSS & Alpine.js -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- IBM Plex Sans Thai Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- External Pure CSS Style -->
  @vite('resources/css/booking.css')
  <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen flex flex-col bg-[#EBF1F9]" x-data="bookingApp()">

  <!-- NAVBAR INCLUDE -->
  @include('navbar')

  <div class="flex-1">
    <!-- HERO BANNER WRAPPER -->
    <div class="hero-banner">
      <img src="{{ asset('images/Gradient.png') }}" alt="Background Gradient" class="hero-bg-img">
    </div>

    <!-- MAIN HERO CARD OVERLAY -->
    <div class="hero-overlay-wrap">
      <div class="hero-flex-container">

        <!-- Concert Poster -->
        <div class="poster-card">
          <img src="{{ $concert && $concert->poster_path ? asset($concert->poster_path) : asset('images/poster.png') }}" alt="Concert Poster">
        </div>

        <!-- Main Dark Info Card -->
        <div class="main-hero-card">
          <div class="hero-content-left">
            <div>
              <h1 class="hero-title">{{ $concert ? strtoupper($concert->name) : 'ไม่พบคอนเสิร์ต' }}</h1>

              <div class="badge-group">
                <span class="badge-item">
                  <svg class="badge-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  {{ $concert ? $concert->event_datetime->locale('th')->isoFormat('D MMMM YYYY') : '-' }}
                </span>
                <span class="badge-item">
                  <svg class="badge-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 16 14"/></svg>
                  {{ $concert ? $concert->event_datetime->format('H:i') : '-' }} น.
                </span>
                <span class="badge-item">
                  <svg class="badge-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  {{ $concert ? $concert->venue : '-' }}
                </span>
              </div>
            </div>

            <div class="contact-info">
              <p>ติดต่อผู้จัดงาน</p>
              <div class="contact-grid">
                <div>โทร: <span>{{ $contacts->get('PHONE') ? $contacts->get('PHONE')->value : '-' }}</span></div>
                <div>Facebook: <span>{{ $contacts->get('FACEBOOK') ? $contacts->get('FACEBOOK')->value : '-' }}</span></div>
                <div>LINE: <span>{{ $contacts->get('LINE') ? $contacts->get('LINE')->value : '-' }}</span></div>
                <div>Email: <span>{{ $contacts->get('EMAIL') ? $contacts->get('EMAIL')->value : '-' }}</span></div>
              </div>
            </div>
          </div>

          <div class="price-panel">
            <span class="price-title">TICKET PRICES</span>
            <div>
              @foreach($zones as $zone)
              <div class="price-row">
                <span class="num">{{ number_format($zone['price']) }}</span>
                <span class="unit">THB</span>
              </div>
              @endforeach
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- MAIN BODY CONTENT -->
    <main class="main-body space-y-6">

      <!-- DATE SELECTOR -->
      <div class="space-y-2">
        <h3 class="section-label">วันเวลาการแสดง</h3>
        <div class="date-pill">
          <span class="dot-orange"></span>
          วันที่ {{ $concert ? $concert->event_datetime->locale('th')->isoFormat('D MMMM YYYY') : '-' }}
          เวลา {{ $concert ? $concert->event_datetime->format('H:i') : '-' }} น.
        </div>
      </div>

      <!-- ZONE SELECTOR + MAP -->
      <div class="space-y-2.5">
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="section-label mr-1">เลือกโซนที่นั่ง</h3>
          <div class="flex flex-wrap gap-1">
            <template x-for="(tag, idx) in zoneTags" :key="idx">
              <button @click="selectZoneByName(tag.name)"
                      :style="{ backgroundColor: tag.color }"
                      class="text-white text-[10px] px-2 py-0.5 rounded-[2px] font-semibold hover:opacity-90 transition"
                      x-text="tag.name">
              </button>
            </template>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
          <div class="md:col-span-5 bg-white p-3 rounded-xl shadow-sm border border-gray-100 flex items-center justify-center">
            <img src="{{ asset('images/zone-map.png') }}" alt="Venue Zone Map" class="w-full h-auto object-contain rounded-lg">
          </div>

          <div class="md:col-span-7 bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-center">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="text-gray-400 border-b border-gray-100">
                  <th class="pb-2.5 font-medium">โซน</th>
                  <th class="pb-2.5 font-medium text-center">ราคา</th>
                  <th class="pb-2.5 text-right font-medium">ที่นั่งว่าง</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <template x-for="zone in zones" :key="zone.name">
                  <tr class="hover:bg-blue-50/50 cursor-pointer transition-colors" @click="selectZone(zone)">
                    <td class="py-3">
                      <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-sm inline-block flex-shrink-0" :style="{ backgroundColor: zone.color }"></span>
                        <span class="font-semibold text-gray-700 text-xs" x-text="zone.name"></span>
                      </div>
                    </td>
                    <td class="text-gray-600 text-xs text-center" x-text="zone.price + ' บาท'"></td>
                    <td class="text-right">
                      <span class="px-3 py-0.5 text-[10px] text-white font-bold rounded-full inline-block min-w-[60px] text-center shadow-sm"
                            :class="zone.seats === 0 ? 'bg-[#E6483C]' : (zone.seats < 50 ? 'bg-[#E79B1F]' : 'bg-[#1FA855]')"
                            x-text="zone.seats === 0 ? 'Sold out' : zone.seats"></span>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ========================================================= -->
      <!-- OPTION 1: SEAT MAP <= 20 SEATS                            -->
      <!-- ========================================================= -->
      <template x-if="totalSeats <= 20">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">

          <!-- ฝั่งซ้าย: ผังที่นั่งขนาดกระชับ -->
          <div class="seat-map-transparent-box seat-map-box-20 p-1">
            <h3 class="section-label mb-4">แผนผังที่นั่ง</h3>
            <div class="bg-[#061838] text-white text-center py-1.5 text-[10px] tracking-[0.2em] font-bold rounded max-w-[240px] mx-auto mb-4">STAGE</div>

            <div class="overflow-x-auto seat-scroll pb-2">
              <div class="flex flex-col gap-1 items-center min-w-fit mx-auto">
                <!-- Header Numbers -->
                <div class="flex gap-1 items-center mb-1">
                  <span class="w-[14px] inline-block"></span>
                  <template x-for="col in totalSeats" :key="'hdr-'+col">
                    <span class="w-[14px] text-[9px] seat-label-text text-center inline-block" x-text="col"></span>
                  </template>
                  <span class="w-[14px] inline-block"></span>
                </div>
                <!-- Rows -->
                <template x-for="row in rows" :key="'r20-'+row">
                  <div class="flex gap-1 items-center">
                    <span class="w-[14px] text-[9px] seat-label-text text-center select-none" x-text="row"></span>
                    <template x-for="col in totalSeats" :key="'s20-'+row+col">
                      <button @click="toggleSeat(row + col)"
                              :disabled="isOccupied(row + col)"
                              :class="{
                                'bg-[#F47B1F] scale-110 shadow-md z-10': selectedSeats.includes(row + col),
                                'seat-btn-occupied': isOccupied(row + col),
                                'cursor-pointer': !isOccupied(row + col) && !selectedSeats.includes(row + col)
                              }"
                              :style="!isOccupied(row + col) && !selectedSeats.includes(row + col) ? { backgroundColor: getZoneColorForRow(row) } : {}"
                              class="seat-icon-shape transition-all duration-150 flex-shrink-0 border-0 outline-none">
                      </button>
                    </template>
                    <span class="w-[14px] text-[9px] seat-label-text text-center select-none" x-text="row"></span>
                  </div>
                </template>
              </div>
            </div>

            <!-- Legend -->
            <div class="flex justify-center gap-5 mt-4 text-[10px] text-gray-500">
              <div class="flex items-center gap-1.5">
                <span class="seat-legend-icon" :style="{ backgroundColor: selectedZone ? selectedZone.color : '#4D7EE0' }"></span> ว่าง
              </div>
              <div class="flex items-center gap-1.5">
                <span class="seat-legend-icon seat-btn-occupied"></span> ขายแล้ว
              </div>
              <div class="flex items-center gap-1.5">
                <span class="seat-legend-icon bg-[#F47B1F]"></span> ที่นั่งที่เลือก
              </div>
            </div>
          </div>

          <!-- ฝั่งขวา: ข้อมูลบัตร -->
          <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
            <div>
              <h3 class="section-label mb-4">ข้อมูลบัตร</h3>
              <div class="text-xs space-y-3.5">
                <div class="flex justify-between border-b border-gray-100 pb-2.5">
                  <span class="font-semibold text-gray-700">รอบการแสดง</span>
                  <span class="text-gray-700 font-medium">{{ $concert ? $concert->event_datetime->locale('th')->isoFormat('D MMMM YYYY') . ', ' . $concert->event_datetime->format('H:i') . ' น.' : '-' }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2.5">
                  <span class="font-semibold text-gray-700">โซน</span>
                  <span class="text-gray-600" x-text="selectedZone ? selectedZone.name : '-'"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2.5">
                  <span class="font-semibold text-gray-700">เลขที่นั่ง</span>
                  <span class="text-gray-600" x-text="selectedSeats.length > 0 ? selectedSeats.join(', ') : '-'"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2.5">
                  <span class="font-semibold text-gray-700">จำนวน</span>
                  <span class="text-gray-600" x-text="selectedSeats.length > 0 ? selectedSeats.length : '0'"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2.5">
                  <span class="font-semibold text-gray-700">ราคาต่อบัตร</span>
                  <span class="text-gray-600" x-text="selectedZone ? selectedZone.price + ' บาท' : '0 บาท'"></span>
                </div>
                <div class="flex justify-between pt-1">
                  <span class="font-bold text-[#F47B1F]">ราคารวม</span>
                  <span class="font-bold text-[#F47B1F]" x-text="calculateTotalPrice()"></span>
                </div>
              </div>
            </div>

            <div class="flex gap-3 mt-6">
              <button class="flex-1 py-2.5 rounded-lg bg-[#E8EEF7] text-gray-600 text-xs font-semibold hover:bg-gray-200 transition" @click="window.location.href='/'">ย้อนกลับ</button>
              <button :disabled="selectedSeats.length === 0"
                      :class="selectedSeats.length > 0 ? 'bg-[#F47B1F] hover:bg-orange-600 text-white shadow-md cursor-pointer' : 'bg-[#FBC48E] text-white cursor-not-allowed'"
                      class="flex-1 py-2.5 rounded-lg text-xs font-semibold transition"
                      @click="nextPage()">จองบัตร</button>
            </div>
          </div>

        </div>
      </template>

      <!-- ========================================================= -->
      <!-- OPTION 2: SEAT MAP > 20 SEATS                             -->
      <!-- ========================================================= -->
      <template x-if="totalSeats > 20">
        <div class="space-y-6">

          <!-- 1. FULL WIDTH SEAT MAP TRANSPARENT SECTION -->
          <div class="seat-map-transparent-box space-y-4">
            <h3 class="section-label">แผนผังที่นั่ง</h3>

            <div class="bg-[#061838] text-white text-center py-1.5 text-[10px] tracking-[0.2em] font-bold rounded max-w-[280px] mx-auto">STAGE</div>

            <!-- Wide Seat Grid Area -->
            <div class="overflow-x-auto seat-scroll py-2">
              <div class="flex flex-col gap-1 items-center min-w-fit mx-auto">
                <!-- Header Numbers -->
                <div class="flex gap-1 items-center mb-1">
                  <span class="w-[14px] inline-block"></span>
                  <template x-for="col in totalSeats" :key="'hdr40-'+col">
                    <span class="w-[14px] text-[9px] seat-label-text text-center inline-block" x-text="col"></span>
                  </template>
                  <span class="w-[14px] inline-block"></span>
                </div>

                <!-- Seat Rows -->
                <template x-for="row in rows" :key="'r40-'+row">
                  <div class="flex gap-1 items-center">
                    <span class="w-[14px] text-[9px] seat-label-text text-center select-none" x-text="row"></span>
                    <template x-for="col in totalSeats" :key="'s40-'+row+col">
                      <button @click="toggleSeat(row + col)"
                              :disabled="isOccupied(row + col)"
                              :class="{
                                'bg-[#F47B1F] scale-110 shadow-md z-10': selectedSeats.includes(row + col),
                                'seat-btn-occupied': isOccupied(row + col),
                                'cursor-pointer': !isOccupied(row + col) && !selectedSeats.includes(row + col)
                              }"
                              :style="!isOccupied(row + col) && !selectedSeats.includes(row + col) ? { backgroundColor: getZoneColorForRow(row) } : {}"
                              class="seat-icon-shape transition-all duration-150 flex-shrink-0 border-0 outline-none">
                      </button>
                    </template>
                    <span class="w-[14px] text-[9px] seat-label-text text-center select-none" x-text="row"></span>
                  </div>
                </template>
              </div>
            </div>

            <!-- Seat Legend -->
            <div class="flex justify-center gap-6 pt-2 text-[10px] text-gray-500">
              <div class="flex items-center gap-1.5">
                <span class="seat-legend-icon" :style="{ backgroundColor: selectedZone ? selectedZone.color : '#4D7EE0' }"></span> ว่าง
              </div>
              <div class="flex items-center gap-1.5">
                <span class="seat-legend-icon seat-btn-occupied"></span> ขายแล้ว
              </div>
              <div class="flex items-center gap-1.5">
                <span class="seat-legend-icon bg-[#F47B1F]"></span> ที่นั่งที่เลือก
              </div>
            </div>
          </div>

          <!-- 2. FULL WIDTH TICKET INFO CARD -->
          <div class="ticket-info-card-full space-y-6">
            <h3 class="section-label">ข้อมูลบัตร</h3>

            <div class="ticket-summary-grid">
              <!-- Left Column Specs -->
              <div class="space-y-3">
                <div class="ticket-summary-row">
                  <span class="font-semibold text-gray-700">รอบการแสดง</span>
                  <span class="text-gray-700 font-medium">{{ $concert ? $concert->event_datetime->locale('th')->isoFormat('D MMMM YYYY') . ', ' . $concert->event_datetime->format('H:i') . ' น.' : '-' }}</span>
                </div>
                <div class="ticket-summary-row">
                  <span class="font-semibold text-gray-700">โซน</span>
                  <span class="text-gray-600" x-text="selectedZone ? selectedZone.name : '-'"></span>
                </div>
                <div class="ticket-summary-row">
                  <span class="font-semibold text-gray-700">เลขที่นั่ง</span>
                  <span class="text-gray-600" x-text="selectedSeats.length > 0 ? selectedSeats.join(', ') : '-'"></span>
                </div>
              </div>

              <!-- Right Column Specs -->
              <div class="space-y-3">
                <div class="ticket-summary-row">
                  <span class="font-semibold text-gray-700">จำนวน</span>
                  <span class="text-gray-600" x-text="selectedSeats.length > 0 ? selectedSeats.length : '0'"></span>
                </div>
                <div class="ticket-summary-row">
                  <span class="font-semibold text-gray-700">ราคาต่อบัตร</span>
                  <span class="text-gray-600" x-text="selectedZone ? selectedZone.price + ' บาท' : '0 บาท'"></span>
                </div>
                <div class="ticket-summary-row">
                  <span class="font-bold text-[#F47B1F]">ราคารวม</span>
                  <span class="font-bold text-[#F47B1F]" x-text="calculateTotalPrice()"></span>
                </div>
              </div>
            </div>

            <!-- Action Buttons Centered at Bottom -->
            <div class="action-buttons-center">
              <button class="btn-clean-back" @click="window.location.href='/'">
                ย้อนกลับ
              </button>
              <button class="btn-clean-submit"
                      :disabled="selectedSeats.length === 0"
                      @click="nextPage()">
                จองบัตร
              </button>
            </div>
          </div>

        </div>
      </template>

    </main>
  </div>

  <!-- FOOTER INCLUDE -->
  @include('footer')

  <!-- ALPINE LOGIC -->
  <script>
    function bookingApp() {
      // ข้อมูลจากฐานข้อมูล (inject โดย PHP/Blade)
      const dbZones = @json($zones);
      const dbOccupied = @json($occupiedSeats);
      const concertId = @json($concert ? $concert->concert_id : null);
      const maxTickets = @json($concert ? $concert->max_ticket_per_order : 4);

      return {
        totalSeats: dbZones.length > 0 ? dbZones[0].totalCols : 20,
        selectedSeats: [],
        selectedZone: null,
        rows: dbZones.length > 0 ? dbZones[0].rows : ['A','B','C','D','E'],
        zones: dbZones,
        zoneTags: dbZones,
        occupiedSeats: dbOccupied,

        selectZone(zone) {
          this.selectedZone = zone;
          this.totalSeats = zone.totalCols || 20;
          this.rows = zone.rows || ['A','B','C','D','E'];
          this.selectedSeats = [];
        },
        selectZoneByName(name) {
          const zone = this.zones.find(z => z.name === name);
          if (zone) this.selectZone(zone);
        },

        toggleSeat(seatId) {
            if (this.selectedSeats.includes(seatId)) {
                this.selectedSeats = this.selectedSeats.filter(s => s !== seatId);
            } else {
                if (this.selectedSeats.length < maxTickets) {
                    this.selectedSeats.push(seatId);
                    if (!this.selectedZone && this.zones.length > 0) {
                        this.selectedZone = this.zones[0];
                    }
                }
            }
        },

        getZoneColorForRow(row) {
          if (this.selectedZone && this.selectedZone.color) {
            return this.selectedZone.color;
          }
          // fallback: find zone that contains this row
          for (const z of this.zones) {
            if (z.rows && z.rows.includes(row)) {
              return z.color || '#4D7EE0';
            }
          }
          return '#4D7EE0';
        },

        isOccupied(seatId) { return this.occupiedSeats.includes(seatId); },

        calculateTotalPrice() {
          if (!this.selectedZone || this.selectedSeats.length === 0) return '0 บาท';
          const price = parseInt(this.selectedZone.price) || 0;
          return (price * this.selectedSeats.length).toLocaleString() + ' บาท';
        },

        nextPage() {
          if (this.selectedSeats.length > 0 && this.selectedZone) {
            const params = new URLSearchParams({
              zone: this.selectedZone.name,
              seat: this.selectedSeats.join(','),
              price: this.selectedZone.price,
              quantity: this.selectedSeats.length,
            });
            if (concertId) params.set('concert_id', concertId);

            window.location.href = `/payment?${params.toString()}`;
          }
        }
      }
    }
  </script>
</body>
</html>
