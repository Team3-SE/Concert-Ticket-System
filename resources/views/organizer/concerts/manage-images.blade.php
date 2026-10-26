{{--
 * manage-images.blade.php
 * หน้าจัดการคอนเสิร์ต: แก้ข้อมูลทั่วไป ช่วงขายบัตร รูปภาพ และโซน/ราคา ได้ในฟอร์มเดียว แล้วกดบันทึกครั้งเดียว
 * - ซ้าย: รายการคอนเสิร์ต (เลือกคอนเสิร์ตที่จะแก้)
 * - ขวา: ฟอร์มแก้ไขของคอนเสิร์ตที่เลือก (ส่งไป ConcertImageController@update)
 * @Author
 * @Create Date
--}}
@extends('layouts.user')

@section('title', 'จัดการคอนเสิร์ต - TEAM 3 Ticket')
@section('body_class', 'min-h-screen bg-[#EBF1F9]')

@push('head')
  <style>[x-cloak]{display:none !important;}</style>
@endpush

@section('content')
@php
  // สีป้ายสถานะ
  $statusStyle = [
    'ON_SALE'   =>'bg-green-100 text-green-700',
    'SCHEDULED' =>'bg-yellow-100 text-yellow-700',
    'SOLD_OUT'  =>'bg-red-100 text-red-700',
    'DRAFT'     =>'bg-gray-200 text-gray-600',
  ];
  $statusShort = ['ON_SALE' =>'เปิดขาย', 'SCHEDULED' =>'เร็ว ๆ นี้', 'SOLD_OUT' =>'ขายหมด', 'DRAFT' =>'ฉบับร่าง'];

  // class ของช่องกรอก (ขอบแดงเมื่อฟิลด์นั้นมี error)
  $field = fn ($name) =>'w-full rounded-lg border px-3 py-2 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 '
        . ($errors->has($name) ? 'border-red-400' : 'border-gray-300');
  $label = 'block text-xs font-semibold text-gray-600 mb-1';
  $card  = 'bg-white rounded-xl shadow-sm border border-gray-100 p-6';
  $h2    = 'text-sm font-bold text-gray-800 border-l-4 border-[#F47B1F] pl-2 mb-4';

  // ชนิดรูป: label, ไอคอน, คอลัมน์ในตาราง, class กรอบ preview (ขนาดดู manage-images.css)
  $imageTypes = [
    'banner'   =>['label' =>'แบนเนอร์แนวนอน', 'column' =>'banner_path',         'class' =>'preview-banner',   'wide' =>true],
    'poster'   =>['label' =>'โปสเตอร์',        'column' =>'poster_path',         'class' =>'preview-poster',   'wide' =>false],
    'seat_map' =>['label' =>'ผังที่นั่ง',       'column' =>'seat_map_image_path', 'class' =>'preview-seat-map', 'wide' =>false],
  ];
@endphp

  {{-- HEADER --}}
  <div class="max-w-7xl mx-auto px-6 pt-8 pb-4">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
          จัดการคอนเสิร์ต
        </h1>
        <p class="text-sm text-gray-500 mt-1">แก้ไขข้อมูล รูปภาพ และโซนของคอนเสิร์ตได้ในหน้าเดียว แล้วกด “บันทึกทั้งหมด” ครั้งเดียว</p>
      </div>
      <a href="{{ url('/') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
        ← กลับหน้าหลัก
      </a>
    </div>

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-2 rounded-lg mb-4 text-sm font-medium" x-data="{ show: true }" x-show="show" x-init="setTimeout(() =>show = false, 4000)" x-transition>
       {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-2 rounded-lg mb-4 text-sm font-medium">
       {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
      <p class="font-semibold mb-1">บันทึกไม่สำเร็จ กรุณาแก้ไขรายการต่อไปนี้ (ข้อมูลที่กรอกไว้ยังอยู่ครบ ยกเว้นรูปที่เลือกใหม่ต้องเลือกอีกครั้ง)</p>
      <ul class="list-disc pl-5 space-y-0.5">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif
  </div>

  <div class="max-w-7xl mx-auto px-6 pb-12">
  @if(!$selected)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
      <div class="text-4xl mb-3"></div>
      <p class="font-medium">ยังไม่มีคอนเสิร์ตในระบบ</p>
    </div>
  @else
  @php
    $editorConfig = [
      'dirty'    =>$errors->any(),
      'zones'    =>$zoneRows,
      'resetUrl' =>route('manage.images', ['concert' =>$selected->concert_id]),
      'limits'   =>collect($imageSizes)->map(fn ($s) =>$s['maxKB'])->all(),
      'images'   =>collect($imageTypes)->map(fn ($m) =>[
          'url'     =>$selected->{$m['column']} ? asset('storage/' . $selected->{$m['column']}) : null,
          'preview' =>null,
          'remove'  =>false,
      ])->all(),
    ];
    $currentStatus = old('status', $selected->status);
  @endphp

  <div class="grid grid-cols-1 lg:grid-cols-[300px_minmax(0,1fr)] gap-6 items-start">

    {{-- ===== รายการคอนเสิร์ต ===== --}}
    <aside class="bg-white rounded-xl shadow-sm border border-gray-100 lg:sticky lg:top-4" x-data="{ q: '' }">
      <div class="p-3 border-b border-gray-100">
        <input type="search" x-model="q" placeholder="ค้นหาคอนเสิร์ต..."
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
        <p class="text-[11px] text-gray-400 mt-1.5">ทั้งหมด {{ $concerts->count() }} คอนเสิร์ต · เลือกเพื่อแก้ไข</p>
      </div>
      <div class="max-h-[260px] lg:max-h-[calc(100vh-190px)] overflow-y-auto divide-y divide-gray-50">
        @foreach($concerts as $c)
          @php $isCurrent = $c->concert_id === $selected->concert_id; @endphp
          <a href="{{ route('manage.images', ['concert' =>$c->concert_id]) }}"
             data-search="{{ \Illuminate\Support\Str::lower($c->name . ' ' . $c->artist . ' ' . $c->concert_code) }}"
             x-show="!q || $el.dataset.search.includes(q.toLowerCase())"
             class="flex gap-3 p-3 transition {{ $isCurrent ? 'bg-blue-50 border-l-4 border-[#F47B1F]' : 'hover:bg-gray-50 border-l-4 border-transparent' }}">
            <div class="w-10 h-14 shrink-0 rounded-md bg-gray-100 overflow-hidden flex items-center justify-center text-gray-300 text-lg">
              @if($c->poster_path)
                <img src="{{ asset('storage/' . $c->poster_path) }}" alt="" class="w-full h-full object-cover">
              @else
              @endif
            </div>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-gray-800 truncate">{{ $c->name }}</p>
              <p class="text-[11px] text-gray-400 truncate">{{ $c->concert_code }} · {{ $c->event_datetime->thDate() }}</p>
              <span class="inline-block mt-1 text-[10px] px-2 py-0.5 rounded-full font-semibold {{ $statusStyle[$c->status] ?? 'bg-gray-200 text-gray-600' }}">
                {{ $statusShort[$c->status] ?? $c->status }}
              </span>
            </div>
          </a>
        @endforeach
      </div>
    </aside>

    {{-- ===== ฟอร์มแก้ไข (ฟอร์มเดียว บันทึกทั้งหมด) ===== --}}
    <form method="POST" action="{{ route('manage.concerts.update', $selected->concert_id) }}" enctype="multipart/form-data"
          x-data="concertEditor(@js($editorConfig))"
          @input="dirty = true" @change="dirty = true" @submit="submitting = true"
          class="space-y-6 min-w-0">
      @csrf
      @method('PUT')

      {{-- หัวการ์ด --}}
      <div class="bg-gradient-to-r from-[#0B2F61] to-[#1B4297] text-white rounded-xl px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
          <h2 class="font-bold text-base truncate">{{ $selected->name }}</h2>
          <p class="text-blue-200 text-xs mt-0.5">รหัส {{ $selected->concert_code }} · แสดง {{ $selected->event_datetime->thDateTime() }}</p>
        </div>
        <a href="{{ url('/booking?concert_id=' . $selected->concert_id) }}" target="_blank" rel="noopener"
           class="text-xs px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 transition font-medium">
          ดูหน้าจอง ↗
        </a>
      </div>

      {{-- 1) ข้อมูลทั่วไป --}}
      <section class="{{ $card }}">
        <h2 class="{{ $h2 }}">ข้อมูลทั่วไป</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="{{ $label }}" for="f-name">ชื่อคอนเสิร์ต <span class="text-red-500">*</span></label>
            <input id="f-name" type="text" name="name" required maxlength="255" value="{{ old('name', $selected->name) }}" class="{{ $field('name') }}">
          </div>
          <div>
            <label class="{{ $label }}" for="f-code">รหัสคอนเสิร์ต <span class="text-red-500">*</span></label>
            <input id="f-code" type="text" name="concert_code" required maxlength="100" value="{{ old('concert_code', $selected->concert_code) }}" class="{{ $field('concert_code') }}">
          </div>
          <div>
            <label class="{{ $label }}" for="f-artist">ศิลปิน</label>
            <input id="f-artist" type="text" name="artist" maxlength="255" value="{{ old('artist', $selected->artist) }}" class="{{ $field('artist') }}">
          </div>
          <div>
            <label class="{{ $label }}" for="f-org">ผู้จัด <span class="text-red-500">*</span></label>
            <select id="f-org" name="organization_id" required class="{{ $field('organization_id') }}">
              @foreach($organizations as $org)
                <option value="{{ $org->organization_id }}" @selected((int) old('organization_id', $selected->organization_id) === (int) $org->organization_id)>{{ $org->name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="{{ $label }}" for="f-venue">สถานที่</label>
            <input id="f-venue" type="text" name="venue" maxlength="255" value="{{ old('venue', $selected->venue) }}" class="{{ $field('venue') }}">
          </div>
          <div>
            <label class="{{ $label }}" for="f-address">ที่อยู่</label>
            <input id="f-address" type="text" name="address" maxlength="500" value="{{ old('address', $selected->address) }}" class="{{ $field('address') }}">
          </div>
          <div>
            <label class="{{ $label }}" for="f-event">วัน-เวลาแสดง <span class="text-red-500">*</span></label>
            <input id="f-event" type="datetime-local" name="event_datetime" required
                   value="{{ old('event_datetime', optional($selected->event_datetime)->format('Y-m-d\TH:i')) }}" class="{{ $field('event_datetime') }}">
          </div>
          <div>
            <label class="{{ $label }}" for="f-status">สถานะ <span class="text-red-500">*</span></label>
            <select id="f-status" name="status" required class="{{ $field('status') }}">
              @foreach($statusLabels as $value =>$text)
                <option value="{{ $value }}" @selected($currentStatus === $value)>{{ $text }}</option>
              @endforeach
              @unless(array_key_exists($currentStatus, $statusLabels))
                <option value="{{ $currentStatus }}" selected>{{ $currentStatus }}</option>
              @endunless
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="{{ $label }}" for="f-desc">รายละเอียด</label>
            <textarea id="f-desc" name="description" rows="4" maxlength="5000" class="{{ $field('description') }}">{{ old('description', $selected->description) }}</textarea>
          </div>
        </div>
      </section>

      {{-- 2) การขายบัตร --}}
      <section class="{{ $card }}">
        <h2 class="{{ $h2 }}">การขายบัตร</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="{{ $label }}" for="f-sale-start">เปิดขาย</label>
            <input id="f-sale-start" type="datetime-local" name="sale_start_datetime"
                   value="{{ old('sale_start_datetime', optional($selected->sale_start_datetime)->format('Y-m-d\TH:i')) }}" class="{{ $field('sale_start_datetime') }}">
          </div>
          <div>
            <label class="{{ $label }}" for="f-sale-end">ปิดขาย</label>
            <input id="f-sale-end" type="datetime-local" name="sale_end_datetime"
                   value="{{ old('sale_end_datetime', optional($selected->sale_end_datetime)->format('Y-m-d\TH:i')) }}" class="{{ $field('sale_end_datetime') }}">
          </div>
          <div>
            <label class="{{ $label }}" for="f-max">ตั๋วสูงสุดต่อคำสั่งซื้อ</label>
            <input id="f-max" type="number" name="max_ticket_per_order" min="1" max="50" placeholder="ไม่จำกัด"
                   value="{{ old('max_ticket_per_order', $selected->max_ticket_per_order) }}" class="{{ $field('max_ticket_per_order') }}">
          </div>
        </div>
      </section>

      {{-- 3) รูปภาพ --}}
      <section class="{{ $card }}">
        <h2 class="{{ $h2 }}">รูปภาพ</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-4">เลือกหรือลากไฟล์มาวางบนกรอบ จะเห็นตัวอย่างทันที รูปจะถูกอัปโหลดเมื่อกด “บันทึกทั้งหมด” เท่านั้น</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          @foreach($imageTypes as $type =>$meta)
          <div class="{{ $meta['wide'] ? 'md:col-span-2' : '' }} space-y-2">
            <div class="flex items-center justify-between gap-2">
              <h3 class="text-sm font-bold text-gray-700">{{ $meta['label'] }}</h3>
              <span x-show="images.{{ $type }}.preview" x-cloak class="text-[11px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-semibold">รูปใหม่ · ยังไม่บันทึก</span>
              <span x-show="images.{{ $type }}.remove && !images.{{ $type }}.preview" x-cloak class="text-[11px] px-2 py-0.5 rounded-full bg-red-100 text-red-600 font-semibold">จะลบรูปเมื่อบันทึก</span>
            </div>
            <p class="text-[11px] text-gray-400">
              ขนาดแนะนำ {{ $imageSizes[$type]['w'] }}×{{ $imageSizes[$type]['h'] }}px · สูงสุด {{ round($imageSizes[$type]['maxKB'] / 1024, 1) }}MB · JPEG / PNG / WEBP
            </p>

            <div class="{{ $meta['class'] }} img-placeholder {{ $meta['wide'] ? '' : 'mx-auto' }}"
                 :class="{ 'drag-over': dragging === '{{ $type }}' }"
                 @click="pick('{{ $type }}')"
                 @dragover.prevent="dragging = '{{ $type }}'"
                 @dragleave.prevent="dragging = null"
                 @drop.prevent="drop($event, '{{ $type }}')">
              <template x-if="shown('{{ $type }}')">
                <img :src="shown('{{ $type }}')" alt="{{ $meta['label'] }}">
              </template>
              <template x-if="!shown('{{ $type }}')">
                <div class="text-center p-4">
                  <div>คลิกหรือลากไฟล์มาวาง</div>
                </div>
              </template>
              <div class="upload-overlay">
                <span class="text-white text-xs font-semibold bg-black/60 px-3 py-1.5 rounded-lg">เปลี่ยนรูป / ลากไฟล์มาวาง</span>
              </div>
            </div>

            <input type="file" name="{{ $type }}" accept="image/jpeg,image/png,image/webp" class="hidden"
                   x-ref="file_{{ $type }}" @change="onFile($event, '{{ $type }}')">
            <input type="hidden" name="remove_{{ $type }}" :value="images.{{ $type }}.remove && !images.{{ $type }}.preview ? 1 : 0">

            <div class="flex flex-wrap items-center gap-2 {{ $meta['wide'] ? '' : 'justify-center' }}">
              <button type="button" @click="pick('{{ $type }}')"
                      class="text-xs px-3 py-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-medium">เลือกไฟล์</button>
              <button type="button" x-show="images.{{ $type }}.preview" x-cloak @click="clearFile('{{ $type }}')"
                      class="text-xs px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition font-medium">ยกเลิกรูปที่เลือก</button>
              <button type="button" x-show="images.{{ $type }}.url && !images.{{ $type }}.preview && !images.{{ $type }}.remove" x-cloak @click="toggleRemove('{{ $type }}')"
                      class="text-xs px-3 py-1.5 text-red-500 hover:text-red-700 transition">ลบรูปนี้</button>
              <button type="button" x-show="images.{{ $type }}.remove && !images.{{ $type }}.preview" x-cloak @click="toggleRemove('{{ $type }}')"
                      class="text-xs px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition font-medium">ไม่ลบแล้ว</button>
            </div>
            <p x-show="imgError.{{ $type }}" x-cloak x-text="imgError.{{ $type }}" class="text-xs text-red-500 {{ $meta['wide'] ? '' : 'text-center' }}"></p>
          </div>
          @endforeach
        </div>
      </section>

      {{-- 4) โซนและราคา --}}
      <section class="{{ $card }}">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
          <h2 class="text-sm font-bold text-gray-800 border-l-4 border-[#F47B1F] pl-2">โซนและราคา</h2>
          <div class="flex gap-2">
            <button type="button" @click="addZone('SEATED')"
                    class="text-xs px-3 py-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-medium">โซนที่นั่ง</button>
            <button type="button" @click="addZone('STANDING')"
                    class="text-xs px-3 py-1.5 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition font-medium">โซนยืน</button>
          </div>
        </div>

        <p x-show="!zones.length" x-cloak class="text-sm text-gray-400 py-4 text-center">ยังไม่มีโซน กดปุ่ม  ด้านบนเพื่อเพิ่ม</p>

        <div class="space-y-3">
          <template x-for="(z, i) in zones" :key="z.key">
            <div class="rounded-lg border p-4 transition" :class="z.remove ? 'border-red-300 bg-red-50 opacity-75' : 'border-gray-200 bg-gray-50'">
              <input type="hidden" :name="'zones[' + z.key + '][zone_type]'" :value="z.zone_type">
              <input type="hidden" :name="'zones[' + z.key + '][_delete]'" :value="z.remove ? 1 : 0">

              <div class="flex flex-wrap items-end gap-3">
                <div>
                  <label class="{{ $label }}">สี</label>
                  <input type="color" :name="'zones[' + z.key + '][color]'" x-model="z.color"
                         class="h-[38px] w-12 rounded-lg border border-gray-300 bg-white p-1 cursor-pointer">
                </div>
                <div class="flex-1 min-w-[160px]">
                  <label class="{{ $label }}">ชื่อโซน <span class="text-red-500">*</span></label>
                  <input type="text" required maxlength="255" :name="'zones[' + z.key + '][name]'" x-model="z.name"
                         class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div class="w-36">
                  <label class="{{ $label }}">ราคา (บาท) <span class="text-red-500">*</span></label>
                  <input type="number" required min="0" step="any" :name="'zones[' + z.key + '][price]'" x-model="z.price"
                         class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>

                {{-- โซนยืน: จำนวนที่รองรับ --}}
                <template x-if="z.zone_type === 'STANDING'">
                  <div class="w-40">
                    <label class="{{ $label }}">จำนวนที่รองรับ <span class="text-red-500">*</span></label>
                    <input type="number" required :min="Math.max(1, z.sold)" max="100000" :name="'zones[' + z.key + '][capacity]'" x-model="z.capacity"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200">
                  </div>
                </template>

                {{-- โซนที่นั่ง: แถว × ที่นั่งต่อแถว --}}
                <template x-if="z.zone_type === 'SEATED'">
                  <div class="flex flex-wrap items-end gap-3">
                    <div class="w-24">
                      <label class="{{ $label }}">จำนวนแถว</label>
                      <input type="number" required min="1" max="26" :disabled="z.locked" :name="'zones[' + z.key + '][total_rows]'" x-model="z.total_rows"
                             class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:bg-gray-100 disabled:text-gray-400">
                    </div>
                    <div class="w-28">
                      <label class="{{ $label }}">ที่นั่ง/แถว</label>
                      <input type="number" required min="1" max="200" :disabled="z.locked" :name="'zones[' + z.key + '][seats_per_row]'" x-model="z.seats_per_row"
                             class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:bg-gray-100 disabled:text-gray-400">
                    </div>
                    <div class="w-28">
                      <label class="{{ $label }}">เลขเริ่มต้น</label>
                      <input type="number" required min="1" max="999" :disabled="z.locked" :name="'zones[' + z.key + '][seat_start_number]'" x-model="z.seat_start_number"
                             class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:bg-gray-100 disabled:text-gray-400">
                    </div>
                    <p class="text-xs text-gray-500 pb-2.5">รวม <strong class="text-gray-700" x-text="seatTotal(z).toLocaleString()"></strong>ที่นั่ง</p>
                  </div>
                </template>

                <div class="ml-auto pb-0.5">
                  <button type="button" @click="removeZone(i)" :disabled="z.locked && !z.is_new"
                          :title="z.locked && !z.is_new ? 'มีการจองหรือขายตั๋วแล้ว จึงลบโซนไม่ได้' : ''"
                          class="text-xs px-3 py-2 rounded-lg transition font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                          :class="z.remove ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' : 'text-red-500 hover:bg-red-50'"
                          x-text="z.remove ? 'ไม่ลบแล้ว' : 'ลบโซน'"></button>
                </div>
              </div>

              {{-- หมายเหตุของโซน --}}
              <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px]">
                <span class="px-2 py-0.5 rounded-full font-semibold"
                      :class="z.zone_type === 'STANDING' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'"
                      x-text="z.zone_type === 'STANDING' ? 'โซนยืน' : 'โซนที่นั่ง (ระบุเลขที่นั่ง)'"></span>
                <span x-show="z.is_new" x-cloak class="text-blue-600 font-semibold">โซนใหม่ · ยังไม่บันทึก</span>
                <span x-show="z.remove" x-cloak class="text-red-600 font-semibold">โซนนี้จะถูกลบเมื่อบันทึก</span>
                <span x-show="z.zone_type === 'STANDING' && z.sold >0" x-cloak class="text-gray-500">ขายแล้ว <span x-text="z.sold"></span>ใบ (จำนวนที่รองรับต้องไม่น้อยกว่านี้)</span>
                <span x-show="z.locked && !z.is_new" x-cloak class="text-amber-600">
                  มีการจอง/ขายตั๋วแล้ว — แก้ได้เฉพาะชื่อ ราคา สี<span x-show="z.zone_type === 'STANDING'" x-cloak>และจำนวนที่รองรับ</span>(ลบโซน/แก้โครงสร้างที่นั่งไม่ได้)
                </span>
                <span x-show="z.zone_type === 'SEATED' && !z.locked && !z.is_new" x-cloak class="text-gray-400">
                  ถ้าแก้จำนวนแถว/ที่นั่ง ระบบจะสร้างที่นั่งของโซนนี้ใหม่ทั้งหมด (แถวชื่อ A, B, C… อัตโนมัติ)
                </span>
              </div>
            </div>
          </template>
        </div>
      </section>

      {{-- แถบบันทึก (ติดขอบล่างจอ) --}}
      <div class="sticky bottom-3 z-20 rounded-xl border border-gray-200 bg-white/95 backdrop-blur shadow-lg px-4 py-3 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm">
          <span x-show="dirty" x-cloak class="text-orange-600 font-semibold">มีการแก้ไขที่ยังไม่ได้บันทึก</span>
          <span x-show="!dirty" class="text-gray-400">ยังไม่มีการเปลี่ยนแปลง</span>
        </div>
        <div class="flex gap-2">
          <button type="button" @click="resetForm()"
                  class="text-sm px-4 py-2 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition font-medium">ยกเลิกการแก้ไข</button>
          <button type="submit" :disabled="!dirty || submitting"
                  class="text-sm px-5 py-2 rounded-lg bg-[#F47B1F] text-white hover:bg-orange-600 transition font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                  x-text="submitting ? 'กำลังบันทึก…' : 'บันทึกทั้งหมด'"></button>
        </div>
      </div>
    </form>
  </div>
  @endif
  </div>
@endsection

@push('scripts')
<script>
  /**
   * concertEditor(cfg)
   * Alpine component ของฟอร์มแก้ไขคอนเสิร์ต: จัดการ preview รูป, รายการโซน และสถานะ "ยังไม่ได้บันทึก"
   * param : cfg = { dirty, zones, images, limits, resetUrl }
   */
  function concertEditor(cfg) {
    return {
      dirty: !!cfg.dirty,
      submitting: false,
      dragging: null,
      zones: cfg.zones || [],
      images: cfg.images || {},
      limits: cfg.limits || {},
      imgError: { poster: '', banner: '', seat_map: '' },
      nextNew: 1,

      init() {
        // เลขรันของโซนใหม่ต้องไม่ชนกับที่มีอยู่ (กรณีกลับมาจาก error)
        this.zones.forEach(z =>{
          const m = /^new_(\d+)$/.exec(z.key);
          if (m) this.nextNew = Math.max(this.nextNew, Number(m[1]) + 1);
        });
        // เตือนก่อนออกจากหน้าถ้ายังไม่ได้บันทึก
        window.addEventListener('beforeunload', (e) =>{
          if (this.dirty && !this.submitting) { e.preventDefault(); e.returnValue = ''; }
        });
      },

      // ---------- รูปภาพ ----------
      shown(type) {
        const img = this.images[type];
        return img.preview || (img.remove ? null : img.url);
      },
      pick(type) { this.$refs['file_' + type].click(); },
      onFile(event, type) {
        const file = event.target.files[0];
        if (file) this.setFile(file, type, event.target);
      },
      drop(event, type) {
        this.dragging = null;
        const file = event.dataTransfer.files[0];
        if (!file) return;
        const input = this.$refs['file_' + type];
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        this.setFile(file, type, input);
      },
      setFile(file, type, input) {
        this.imgError[type] = '';
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
          this.imgError[type] = 'รองรับเฉพาะไฟล์ JPEG, PNG, WEBP';
          input.value = '';
          return;
        }
        const maxKB = this.limits[type];
        if (maxKB && file.size >maxKB * 1024) {
          this.imgError[type] = 'ไฟล์ใหญ่เกินไป (สูงสุด ' + (maxKB / 1024).toFixed(1) + ' MB)';
          input.value = '';
          return;
        }
        if (this.images[type].preview) URL.revokeObjectURL(this.images[type].preview);
        this.images[type].preview = URL.createObjectURL(file);
        this.images[type].remove = false;
        this.dirty = true;
      },
      clearFile(type) {
        const input = this.$refs['file_' + type];
        if (input) input.value = '';
        if (this.images[type].preview) URL.revokeObjectURL(this.images[type].preview);
        this.images[type].preview = null;
        this.imgError[type] = '';
      },
      toggleRemove(type) {
        this.images[type].remove = !this.images[type].remove;
        this.dirty = true;
      },

      // ---------- โซน ----------
      addZone(type) {
        this.zones.push({
          key: 'new_' + (this.nextNew++),
          is_new: true,
          zone_type: type,
          name: '',
          price: '',
          color: type === 'STANDING' ? '#10B981' : '#3B82F6',
          capacity: '',
          total_rows: '',
          seats_per_row: '',
          seat_start_number: 1,
          locked: false,
          sold: 0,
          remove: false,
        });
        this.dirty = true;
      },
      removeZone(index) {
        const z = this.zones[index];
        if (z.is_new) this.zones.splice(index, 1);   // โซนใหม่: เอาออกได้เลย
        else z.remove = !z.remove;                   // โซนเดิม: ทำเครื่องหมายลบ (ยกเลิกได้จนกว่าจะบันทึก)
        this.dirty = true;
      },
      seatTotal(z) {
        return (Number(z.total_rows) || 0) * (Number(z.seats_per_row) || 0);
      },

      // ---------- ปุ่มล่าง ----------
      resetForm() {
        if (!this.dirty || confirm('ยกเลิกการแก้ไขทั้งหมดที่ยังไม่ได้บันทึก?')) {
          this.submitting = true;   // ไม่ให้ขึ้นคำเตือนตอนโหลดหน้าใหม่
          window.location.href = cfg.resetUrl;
        }
      },
    };
  }
</script>
@endpush
