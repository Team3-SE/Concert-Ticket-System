{{--
 * concert-management.blade.php
 * หน้าจัดการคอนเสิร์ตของ Organizer พร้อมค้นหา กรองสถานะ และจัดการรายการ
 * @Author 67160279 กนกพล แซ่ตั๊น
 * @Create Date 26/11/2569
 * @Update Date 26/11/2569 67160279 กนกพล แซ่ตั๊น
--}}
@extends('layouts.backoffice')

@section('title', 'จัดการคอนเสิร์ต')
@section('body_class', 'page-organizer-concert-management')

@section('content')
  <div class="cm-page">
    <div class="cm-heading">
      <h1 class="bo-title">คอนเสิร์ตของฉัน</h1>
      <button class="cm-button cm-button--primary" type="button">+ สร้างคอนเสิร์ตใหม่</button>
    </div>

    <form class="cm-filters" method="GET" action="{{ route('organizer.concerts.index') }}"
          data-concert-search data-suggest-mode="organizer" data-suggest-url="{{ route('organizer.concerts.suggest') }}">
      <div class="cm-search-wrap" data-suggest-wrap>
        <label class="cm-search">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 5 5"></path></svg>
          <input type="search" name="q" value="{{ $search }}" placeholder="ค้นหาชื่องานคอนเสิร์ต หรือศิลปิน" aria-label="ค้นหาชื่องานคอนเสิร์ตหรือศิลปิน" autocomplete="off" spellcheck="false" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="concertManagementSuggestions" data-search-input>
        </label>
        <div class="cm-suggestions" id="concertManagementSuggestions" role="listbox" aria-label="คอนเสิร์ตที่แนะนำ" hidden></div>
      </div>

      <input type="hidden" name="status" value="{{ $selectedStatus }}" data-status-input>
      <div class="cm-status-filter" data-dropdown>
        <button class="cm-status-toggle" type="button" data-dropdown-toggle aria-haspopup="true" aria-expanded="false">
          <span>{{ $selectedStatus === 'ALL' ? 'สถานะทั้งหมด' : ($statusFilters[$selectedStatus] ?? 'สถานะทั้งหมด') }}</span>
          <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 7.5 5 5 5-5"></path></svg>
        </button>
        <div class="cm-status-menu" data-dropdown-menu hidden>
          <button type="button" data-status-value="ALL" @class(['is-selected' => $selectedStatus === 'ALL'])>สถานะทั้งหมด</button>
          @foreach($statusFilters as $value => $label)
            <button type="button" data-status-value="{{ $value }}" @class(['is-selected' => $selectedStatus === $value])>{{ $label }}</button>
          @endforeach
        </div>
      </div>
    </form>

    <section class="cm-table-card" aria-label="รายการคอนเสิร์ต">
      @if($concerts->isEmpty())
        <div class="cm-empty">
          <strong>{{ $search || $selectedStatus !== 'ALL' ? 'ไม่พบคอนเสิร์ตที่ตรงกับเงื่อนไข' : 'ยังไม่มีคอนเสิร์ต' }}</strong>
          <span>{{ $search || $selectedStatus !== 'ALL' ? 'ลองเปลี่ยนคำค้นหาหรือตัวกรองสถานะ' : 'เริ่มต้นด้วยการสร้างคอนเสิร์ตใหม่' }}</span>
        </div>
      @else
        <div class="cm-table-scroll">
          <table class="cm-table">
            <thead>
              <tr>
                <th scope="col">ชื่อคอนเสิร์ต / ศิลปิน</th>
                <th scope="col">กำหนดเวลาเปิดจำหน่าย</th>
                <th scope="col">กำหนดเวลาปิดจำหน่าย</th>
                <th scope="col">วันแสดงจริง</th>
                <th scope="col">สถานะ</th>
                <th scope="col">การจัดการ</th>
              </tr>
            </thead>
            <tbody>
              @foreach($concerts as $concert)
                <tr>
                  <td>
                    <div class="cm-concert">
                      @if($concert['poster'])
                        <img class="cm-poster" src="{{ $concert['poster'] }}" alt="" loading="lazy">
                      @else
                        <span class="cm-poster cm-poster--empty" aria-hidden="true">♪</span>
                      @endif
                      <span class="cm-concert__text">
                        <strong>{{ $concert['name'] }}</strong>
                        <span>{{ $concert['artist'] ?: 'ไม่ระบุศิลปิน' }}</span>
                      </span>
                    </div>
                  </td>
                  <td>{{ $concert['sale_start'] }}</td>
                  <td>{{ $concert['sale_end'] }}</td>
                  <td>{{ $concert['event_date'] }}</td>
                  <td class="cm-table__status">
                    <span @class(['cm-status', 'cm-status--'.strtolower($concert['status'])])>{{ $concert['status_label'] }}</span>
                  </td>
                  <td class="cm-table__actions">
                    <div class="cm-actions">
                      @if($concert['editable'])
                        <a class="cm-button cm-button--edit" href="{{ route('organizer.concerts.edit', $concert['id']) }}">แก้ไขคอนเสิร์ต</a>
                        <form method="POST" action="{{ route('organizer.concerts.destroy', $concert['id']) }}" data-delete-form>
                          @csrf
                          @method('DELETE')
                          @if($search !== '')<input type="hidden" name="q" value="{{ $search }}">@endif
                          <input type="hidden" name="status" value="{{ $selectedStatus }}">
                          <button class="cm-button cm-button--delete" type="button" data-delete-trigger data-concert-name="{{ $concert['name'] }}">ลบ</button>
                        </form>
                      @else
                        <a class="cm-button cm-button--inspect" href="{{ url('/organizer/check-concert?concert_id='.$concert['id']) }}">ตรวจสอบ</a>
                      @endif
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
      <div class="cm-pagination">
        @include('partials.pagination', ['paginator' => $concerts, 'unit' => 'คอนเสิร์ต', 'emphasizeTotal' => true])
      </div>
    </section>
  </div>

  <dialog class="cm-delete-dialog" data-delete-dialog aria-labelledby="cm-delete-title" aria-describedby="cm-delete-description">
    <div class="cm-dialog-icon" aria-hidden="true">?</div>
    <h2 id="cm-delete-title">ยืนยันการลบคอนเสิร์ต</h2>
    <p id="cm-delete-description">คุณต้องการลบคอนเสิร์ต <strong data-delete-name></strong> ใช่หรือไม่?</p>
    <div class="cm-dialog-actions">
      <button class="cm-button cm-button--cancel" type="button" data-delete-cancel>ยกเลิก</button>
      <button class="cm-button cm-button--confirm-delete" type="button" data-delete-confirm>ยืนยัน</button>
    </div>
  </dialog>
@endsection
