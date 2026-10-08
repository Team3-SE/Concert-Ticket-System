{{--
 * manage-images.blade.php
 * หน้าจัดการรูปภาพคอนเสิร์ต (โปสเตอร์ แบนเนอร์ ผังที่นั่ง)
 * @Author
 * @Create Date
--}}
@extends('layouts.main')

@section('title', 'จัดการรูปภาพคอนเสิร์ต - TEAM 3 Ticket')
@section('body_class', 'min-h-screen bg-[#EBF1F9]')

@section('content')
  {{-- HEADER SECTION --}}
  <div class="max-w-7xl mx-auto px-6 pt-8 pb-4">
    <div class="flex items-center justify-between mb-2">
      <div>
        <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
          <span class="text-2xl">🖼️</span>
          จัดการรูปภาพคอนเสิร์ต
        </h1>
        <p class="text-sm text-gray-500 mt-1">อัปโหลดและแก้ไขรูปโปสเตอร์ แบนเนอร์ และผังที่นั่งของคอนเสิร์ตทั้งหมด</p>
      </div>
      <a href="{{ url('/') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
        ← กลับหน้าหลัก
      </a>
    </div>

    {{-- ขนาดแนะนำ (ปรับค่าที่ Controller) --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-6 text-xs text-blue-700">
      <strong>📐 ขนาดรูปที่แนะนำ:</strong>
      {{-- ค่าเหล่านี้มาจาก Controller (ConcertImageController) — แก้ที่ const ใน Controller --}}
      โปสเตอร์ <strong>{{ $imageSizes['poster']['w'] }}×{{ $imageSizes['poster']['h'] }}px</strong> (สูงสุด {{ round($imageSizes['poster']['maxKB']/1024, 1) }}MB)
      &nbsp;·&nbsp;
      แบนเนอร์ <strong>{{ $imageSizes['banner']['w'] }}×{{ $imageSizes['banner']['h'] }}px</strong> (สูงสุด {{ round($imageSizes['banner']['maxKB']/1024, 1) }}MB)
      &nbsp;·&nbsp;
      ผังที่นั่ง <strong>{{ $imageSizes['seat_map']['w'] }}×{{ $imageSizes['seat_map']['h'] }}px</strong> (สูงสุด {{ round($imageSizes['seat_map']['maxKB']/1024, 1) }}MB)
    </div>

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-2 rounded-lg mb-4 text-sm font-medium" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
      ✅ {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-2 rounded-lg mb-4 text-sm font-medium" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition>
      ❌ {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-2 rounded-lg mb-4 text-sm">
      @foreach($errors->all() as $error)
        <p>❌ {{ $error }}</p>
      @endforeach
    </div>
    @endif
  </div>

  {{-- CONCERT LIST --}}
  <div class="max-w-7xl mx-auto px-6 pb-12 space-y-8">

    @forelse($concerts as $concert)
    @php $idx = $loop->index; @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
         x-data="imageManager_{{ $idx }}()">

      {{-- Concert Header --}}
      <div class="bg-gradient-to-r from-[#0B2F61] to-[#1B4297] text-white px-6 py-4 flex items-center justify-between">
        <div>
          <h2 class="font-bold text-base">{{ $concert->name }}</h2>
          <p class="text-blue-200 text-xs mt-0.5">{{ $concert->artist }} · {{ $concert->venue }} · {{ $concert->event_datetime->format('d/m/Y H:i') }}</p>
        </div>
        <span class="text-xs px-3 py-1 rounded-full font-semibold
          {{ $concert->status === 'ON_SALE' ? 'bg-green-500' : ($concert->status === 'SCHEDULED' ? 'bg-yellow-500' : 'bg-gray-400') }}">
          {{ $concert->status }}
        </span>
      </div>

      {{-- Image Grid: 3 columns --}}
      <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- โปสเตอร์ --}}
        <div class="space-y-3">
          <h3 class="text-sm font-bold text-gray-700 border-l-4 border-[#F47B1F] pl-2">🎨 โปสเตอร์</h3>
          <p class="text-[10px] text-gray-400">
            ขนาดแนะนำ: {{ $imageSizes['poster']['w'] }}×{{ $imageSizes['poster']['h'] }}px · สูงสุด {{ round($imageSizes['poster']['maxKB']/1024, 1) }}MB
          </p>

          {{-- Preview + Drag & Drop --}}
          <div class="preview-poster img-placeholder mx-auto"
               :class="{ 'drag-over': posterDragOver }"
               @click="$refs.posterInput_{{ $idx }}.click()"
               @dragover.prevent="posterDragOver = true"
               @dragleave.prevent="posterDragOver = false"
               @drop.prevent="handleDrop($event, 'poster', {{ $idx }})">
            @if($concert->poster_path)
              <template x-if="!posterPreview">
                <img src="{{ asset('storage/' . $concert->poster_path) }}" alt="Poster">
              </template>
              <template x-if="posterPreview">
                <img :src="posterPreview" alt="Preview">
              </template>
              <div class="upload-overlay">
                <span class="text-white text-xs font-semibold bg-black/60 px-3 py-1.5 rounded-lg">📷 เปลี่ยนรูป / ลากไฟล์มาวาง</span>
              </div>
            @else
              <template x-if="!posterPreview">
                <div class="text-center p-4">
                  <div class="text-3xl mb-1">🖼️</div>
                  <div>คลิกหรือลากไฟล์มาวาง</div>
                </div>
              </template>
              <template x-if="posterPreview">
                <img :src="posterPreview" alt="Preview">
              </template>
            @endif
          </div>

          {{-- Upload Form --}}
          <form method="POST" action="{{ url('/manage/images/' . $concert->concert_id . '/poster') }}" enctype="multipart/form-data" class="space-y-2"
                x-ref="posterForm_{{ $idx }}">
            @csrf
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                   class="hidden" x-ref="posterInput_{{ $idx }}"
                   @change="handleFileSelect($event, 'poster')">

            <div class="flex gap-2 justify-center">
              <button type="button" @click="$refs.posterInput_{{ $idx }}.click()"
                      class="text-xs px-3 py-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-medium">
                📤 เลือกไฟล์
              </button>
              <button type="submit" x-ref="posterSubmit_{{ $idx }}"
                      class="text-xs px-3 py-1.5 bg-[#F47B1F] text-white rounded-lg hover:bg-orange-600 transition font-medium"
                      :class="{ 'hidden': !posterReady }"
                      x-cloak>
                💾 บันทึก
              </button>
            </div>
          </form>

          {{-- Delete --}}
          @if($concert->poster_path)
          <form method="POST" action="{{ url('/manage/images/' . $concert->concert_id . '/poster') }}" class="text-center">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs text-red-400 hover:text-red-600 transition" onclick="return confirm('ลบรูปโปสเตอร์?')">
              🗑️ ลบรูป
            </button>
          </form>
          @endif
        </div>

        {{-- แบนเนอร์ --}}
        <div class="space-y-3 md:col-span-2">
          <h3 class="text-sm font-bold text-gray-700 border-l-4 border-[#F47B1F] pl-2">🏞️ แบนเนอร์แนวนอน</h3>
          <p class="text-[10px] text-gray-400">
            ขนาดแนะนำ: {{ $imageSizes['banner']['w'] }}×{{ $imageSizes['banner']['h'] }}px · สูงสุด {{ round($imageSizes['banner']['maxKB']/1024, 1) }}MB
          </p>

          {{-- Preview + Drag & Drop --}}
          <div class="preview-banner img-placeholder"
               :class="{ 'drag-over': bannerDragOver }"
               @click="$refs.bannerInput_{{ $idx }}.click()"
               @dragover.prevent="bannerDragOver = true"
               @dragleave.prevent="bannerDragOver = false"
               @drop.prevent="handleDrop($event, 'banner', {{ $idx }})">
            @if($concert->banner_path)
              <template x-if="!bannerPreview">
                <img src="{{ asset('storage/' . $concert->banner_path) }}" alt="Banner">
              </template>
              <template x-if="bannerPreview">
                <img :src="bannerPreview" alt="Preview">
              </template>
              <div class="upload-overlay">
                <span class="text-white text-xs font-semibold bg-black/60 px-3 py-1.5 rounded-lg">📷 เปลี่ยนรูป / ลากไฟล์มาวาง</span>
              </div>
            @else
              <template x-if="!bannerPreview">
                <div class="text-center p-4">
                  <div class="text-3xl mb-1">🏞️</div>
                  <div>คลิกหรือลากไฟล์มาวาง</div>
                </div>
              </template>
              <template x-if="bannerPreview">
                <img :src="bannerPreview" alt="Preview">
              </template>
            @endif
          </div>

          {{-- Upload Form --}}
          <form method="POST" action="{{ url('/manage/images/' . $concert->concert_id . '/banner') }}" enctype="multipart/form-data" class="space-y-2"
                x-ref="bannerForm_{{ $idx }}">
            @csrf
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                   class="hidden" x-ref="bannerInput_{{ $idx }}"
                   @change="handleFileSelect($event, 'banner')">

            <div class="flex gap-2">
              <button type="button" @click="$refs.bannerInput_{{ $idx }}.click()"
                      class="text-xs px-3 py-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-medium">
                📤 เลือกไฟล์
              </button>
              <button type="submit" x-ref="bannerSubmit_{{ $idx }}"
                      class="text-xs px-3 py-1.5 bg-[#F47B1F] text-white rounded-lg hover:bg-orange-600 transition font-medium"
                      :class="{ 'hidden': !bannerReady }"
                      x-cloak>
                💾 บันทึก
              </button>
            </div>
          </form>

          {{-- Delete --}}
          @if($concert->banner_path)
          <form method="POST" action="{{ url('/manage/images/' . $concert->concert_id . '/banner') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs text-red-400 hover:text-red-600 transition" onclick="return confirm('ลบรูปแบนเนอร์?')">
              🗑️ ลบรูป
            </button>
          </form>
          @endif
        </div>
      </div>

      {{-- Seat Map (Full Width Row) --}}
      <div class="px-6 pb-6">
        <div class="space-y-3">
          <h3 class="text-sm font-bold text-gray-700 border-l-4 border-[#F47B1F] pl-2">🗺️ ผังที่นั่ง</h3>
          <p class="text-[10px] text-gray-400">
            ขนาดแนะนำ: {{ $imageSizes['seat_map']['w'] }}×{{ $imageSizes['seat_map']['h'] }}px · สูงสุด {{ round($imageSizes['seat_map']['maxKB']/1024, 1) }}MB
          </p>

          {{-- Preview + Drag & Drop --}}
          <div class="preview-seat-map img-placeholder"
               :class="{ 'drag-over': seatMapDragOver }"
               @click="$refs.seatMapInput_{{ $idx }}.click()"
               @dragover.prevent="seatMapDragOver = true"
               @dragleave.prevent="seatMapDragOver = false"
               @drop.prevent="handleDrop($event, 'seatMap', {{ $idx }})">
            @if($concert->seat_map_image_path)
              <template x-if="!seatMapPreview">
                <img src="{{ asset('storage/' . $concert->seat_map_image_path) }}" alt="Seat Map">
              </template>
              <template x-if="seatMapPreview">
                <img :src="seatMapPreview" alt="Preview">
              </template>
              <div class="upload-overlay">
                <span class="text-white text-xs font-semibold bg-black/60 px-3 py-1.5 rounded-lg">📷 เปลี่ยนรูป / ลากไฟล์มาวาง</span>
              </div>
            @else
              <template x-if="!seatMapPreview">
                <div class="text-center p-4">
                  <div class="text-3xl mb-1">🗺️</div>
                  <div>คลิกหรือลากไฟล์มาวาง</div>
                </div>
              </template>
              <template x-if="seatMapPreview">
                <img :src="seatMapPreview" alt="Preview">
              </template>
            @endif
          </div>

          {{-- Upload Form --}}
          <form method="POST" action="{{ url('/manage/images/' . $concert->concert_id . '/seat_map') }}" enctype="multipart/form-data" class="space-y-2"
                x-ref="seatMapForm_{{ $idx }}">
            @csrf
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                   class="hidden" x-ref="seatMapInput_{{ $idx }}"
                   @change="handleFileSelect($event, 'seatMap')">

            <div class="flex gap-2">
              <button type="button" @click="$refs.seatMapInput_{{ $idx }}.click()"
                      class="text-xs px-3 py-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-medium">
                📤 เลือกไฟล์
              </button>
              <button type="submit" x-ref="seatMapSubmit_{{ $idx }}"
                      class="text-xs px-3 py-1.5 bg-[#F47B1F] text-white rounded-lg hover:bg-orange-600 transition font-medium"
                      :class="{ 'hidden': !seatMapReady }"
                      x-cloak>
                💾 บันทึก
              </button>
            </div>
          </form>

          {{-- Delete --}}
          @if($concert->seat_map_image_path)
          <form method="POST" action="{{ url('/manage/images/' . $concert->concert_id . '/seat_map') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs text-red-400 hover:text-red-600 transition" onclick="return confirm('ลบรูปผังที่นั่ง?')">
              🗑️ ลบรูป
            </button>
          </form>
          @endif
        </div>
      </div>

    </div>

    {{-- Alpine.js state for this concert card --}}
    <script>
      function imageManager_{{ $idx }}() {
        return {
          // Preview URLs
          posterPreview: null,
          bannerPreview: null,
          seatMapPreview: null,

          // Drag-over states
          posterDragOver: false,
          bannerDragOver: false,
          seatMapDragOver: false,

          // Show/hide save button
          posterReady: {{ $concert->poster_path ? 'false' : 'false' }},
          bannerReady: {{ $concert->banner_path ? 'false' : 'false' }},
          seatMapReady: {{ $concert->seat_map_image_path ? 'false' : 'false' }},

          /**
           * handleFileSelect()
           * เมื่อเลือกไฟล์จาก input ให้แสดง preview และเปิดปุ่มบันทึก
           */
          handleFileSelect(event, type) {
            const file = event.target.files[0];
            if (!file) return;

            // ตรวจสอบประเภทไฟล์
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
              alert('รองรับเฉพาะ JPEG, PNG, WEBP เท่านั้น');
              event.target.value = '';
              return;
            }

            this[type + 'Preview'] = URL.createObjectURL(file);
            this[type + 'Ready'] = true;
          },

          /**
           * handleDrop()
           * เมื่อ drag & drop ไฟล์ลงมาที่กรอบรูป ให้ใส่ไฟล์ลงใน input แล้ว preview
           */
          handleDrop(event, type, idx) {
            this[type + 'DragOver'] = false;

            const files = event.dataTransfer.files;
            if (!files || files.length === 0) return;

            const file = files[0];

            // ตรวจสอบประเภทไฟล์
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
              alert('รองรับเฉพาะ JPEG, PNG, WEBP เท่านั้น');
              return;
            }

            // ใส่ไฟล์ลงใน file input ผ่าน DataTransfer
            const inputRef = type + 'Input_' + idx;
            const input = this.$refs[inputRef];
            if (input) {
              const dt = new DataTransfer();
              dt.items.add(file);
              input.files = dt.files;
            }

            this[type + 'Preview'] = URL.createObjectURL(file);
            this[type + 'Ready'] = true;
          }
        }
      }
    </script>
    @empty
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
      <div class="text-4xl mb-3">📭</div>
      <p class="font-medium">ยังไม่มีคอนเสิร์ตในระบบ</p>
    </div>
    @endforelse
  </div>
@endsection
