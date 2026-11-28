{{--
 * check-concert.blade.php
 * หน้าจอตรวจสอบรายละเอียดคอนเสิร์ต ผังที่นั่งจำลองสถานะปัจจุบัน และรายงานจำนวนตั๋วแยกตามโซน
 * @Author 67160434 จิตฏิยากร โสภา
 * @Create Date 25/11/2569
 * @Update Date 28/11/2569 67160434 จิตฏิยากร โสภา
--}}

@extends('layouts.backoffice')

@section('title', 'ตรวจสอบคอนเสิร์ต - ' . ($concert->name ?? 'TEAM 3 Ticket'))
@section('body_class', 'bo page-organizer-check-concert')

@push('head')
    @vite(['resources/css/organizer/checkconcert/check-concert.css'])
@endpush

@section('content')
<div
    class="check-concert-container"
    data-realtime-url="{{ route('organizer.concerts.check.realtime', ['concertId' => $concert->c_id]) }}"
>
    {{-- ส่วนหัวหน้าจอ: ชื่อคอนเสิร์ต วันที่/สถานที่ และปุ่มจัดการมุมขวาบน --}}
    <header class="concert-top-header">
        <div class="concert-top-header__info">
            <h1 class="concert-main-title">{{ $concert->name }}</h1>
            <p class="concert-sub-meta">
                วันที่แสดง: {{ $concert->event_datetime?->thDateLong() ?? '-' }}
                | สถานที่: {{ $concert->venue ?? '-' }}
            </p>
        </div>

        <div class="concert-top-header__actions">
            <form
                method="POST"
                action="{{ route('organizer.concerts.update-status', ['concertId' => $concert->c_id]) }}"
                class="status-action-form"
                id="concertStatusActionForm"
                @if(empty($statusConfig['targetStatus'])) hidden @endif
            >
                @csrf
                <input type="hidden" name="target_status" value="{{ $statusConfig['targetStatus'] ?? '' }}">
                <button
                    type="submit"
                    id="concertStatusActionButton"
                    class="btn-top-action {{ $statusConfig['actionButtonClass'] ?? '' }}"
                >
                    {{ $statusConfig['actionButtonLabel'] }}
                </button>
            </form>

            <a
                href="{{ route('manage.images', ['concert' => $concert->c_id]) }}"
                class="btn-top-action btn-top-action--edit"
            >
                แก้ไขคอนเสิร์ต
            </a>
        </div>
    </header>

    {{-- การ์ดสรุปข้อมูลตัวเลขและสถานะ 5 ใบ --}}
    <section class="summary-cards-grid" aria-label="สรุปยอดขายและสถานะคอนเสิร์ต">
        <div class="summary-card">
            <span class="summary-card__label">รายได้รวมของคอนเสิร์ต</span>
            <strong class="summary-card__value" id="realtimeTotalRevenue">{{ number_format($totalRevenue) }} บาท</strong>
        </div>

        <div class="summary-card">
            <span class="summary-card__label">ตั๋วขายแล้ว</span>
            <strong class="summary-card__value" id="realtimeSoldTickets">{{ number_format($soldTickets) }} ใบ</strong>
        </div>

        <div class="summary-card">
            <span class="summary-card__label">ที่นั่งคงเหลือ</span>
            <strong class="summary-card__value" id="realtimeRemainingSeats">{{ number_format($remainingSeats) }} ที่นั่ง</strong>
        </div>

        <div class="summary-card">
            <span class="summary-card__label">ที่นั่งทั้งหมด</span>
            <strong class="summary-card__value" id="realtimeTotalSeats">{{ number_format($totalSeats) }} ที่นั่ง</strong>
        </div>

        <div class="summary-card">
            <span class="summary-card__label">สถานะ</span>
            <strong class="summary-card__value {{ $statusConfig['textClass'] }}" id="realtimeConcertStatus">
                {{ $statusConfig['label'] }}
            </strong>
        </div>
    </section>

    {{-- ส่วนผังที่นั่งจำลองสถานะปัจจุบัน และรายงานจำนวนตั๋วแยกตามโซน --}}
    <div class="seat-and-report-layout {{ $isWideLayout ? 'layout-stacked' : 'layout-split' }}">
        {{-- ฝั่งผังที่นั่ง --}}
        <section class="seat-map-section">
            <div class="section-heading-with-bar">
                <span class="heading-orange-bar"></span>
                <h2 class="section-heading-title">ผังที่นั่งจำลองสถานะปัจจุบัน (Real-time Seat Map)</h2>
            </div>

            {{-- แท็บปุ่มเลือกโซนสีทึบ พร้อมขอบสีส้มเมื่อถูกเลือก --}}
            @if($zoneReports->isNotEmpty())
            <div class="zone-pill-tabs" id="zoneFilterTabs">
                @foreach($zoneReports as $zoneIndex => $zone)
                    <button
                        type="button"
                        class="zone-pill-btn {{ $zoneIndex === 0 ? 'is-active' : '' }}"
                        data-zone-id="{{ $zone['id'] }}"
                        aria-pressed="{{ $zoneIndex === 0 ? 'true' : 'false' }}"
                        style="background-color: {{ $zone['colorHex'] }}; color: {{ $zone['textColor'] }};"
                    >
                        {{ $zone['name'] }}
                    </button>
                @endforeach
            </div>

            {{-- ผังที่นั่งแยกตามโซน --}}
            @foreach($zoneReports as $zoneIndex => $zone)
                @php
                    $zoneRows = $seatMapsByZone[(string) $zone['id']] ?? [];
                    $firstRowSeats = collect($zoneRows)
                        ->sortByDesc(fn ($seatRow) => count($seatRow))
                        ->first() ?? [];
                @endphp

                <div
                    class="zone-seat-board {{ $zoneIndex === 0 ? 'is-visible' : '' }}"
                    data-zone-board="{{ $zone['id'] }}"
                >
                    @if($zone['zoneType'] === 'STANDING')
                        <div class="standing-zone-card">
                            <span class="standing-zone-card__tag" style="background-color: {{ $zone['colorHex'] }};">
                                โซนยืน (STANDING)
                            </span>
                            <strong class="standing-zone-card__name">{{ $zone['name'] }}</strong>
                            <p class="standing-zone-card__detail">
                                ขายแล้ว <strong>{{ number_format($zone['soldSeats']) }}</strong> / {{ number_format($zone['totalSeats']) }} ใบ
                                (คงเหลือ <strong>{{ number_format($zone['remainingSeats']) }}</strong> ใบ)
                            </p>
                        </div>
                    @else
                        <div class="seat-matrix-scroll">
                            <div class="seat-matrix-container">
                                {{-- แถวเลขคอลัมน์ด้านบน --}}
                                @if(!empty($firstRowSeats))
                                    <div class="seat-col-numbers-row">
                                        <span class="seat-row-spacer"></span>
                                        <div class="seat-col-numbers">
                                            @foreach($firstRowSeats as $columnSeat)
                                                <span class="seat-col-num">{{ $columnSeat['number'] }}</span>
                                            @endforeach
                                        </div>
                                        <span class="seat-row-spacer"></span>
                                    </div>
                                @endif

                                {{-- แถวที่นั่ง --}}
                                @forelse($zoneRows as $rowLabel => $seatRow)
                                    <div class="seat-matrix-row">
                                        <span class="seat-row-label">{{ $rowLabel }}</span>

                                        <div class="seat-cells-group">
                                            @foreach($seatRow as $seat)
                                                @php
                                                    $seatStateClass = 'seat-box--available';
                                                    $showPersonIcon = false;
                                                    $showCrossIcon = false;

                                                    if ($seat['status'] === 'DISABLED') {
                                                        $seatStateClass = 'seat-box--disabled';
                                                        $showCrossIcon = true;
                                                    } elseif ($statusConfig['isCheckInMode']) {
                                                        if ($seat['status'] === 'SOLD' && $seat['isCheckedIn']) {
                                                            $seatStateClass = 'seat-box--checked-in';
                                                            $showPersonIcon = true;
                                                        } elseif ($seat['status'] === 'SOLD') {
                                                            $seatStateClass = 'seat-box--not-checked-in';
                                                            $showPersonIcon = true;
                                                        }
                                                    } else {
                                                        if ($seat['status'] === 'SOLD') {
                                                            $seatStateClass = 'seat-box--sold';
                                                        } elseif ($seat['status'] === 'LOCKED') {
                                                            $seatStateClass = 'seat-box--locked';
                                                        }
                                                    }
                                                @endphp

                                                <span
                                                    class="seat-box {{ $seatStateClass }}"
                                                    data-seat-id="{{ $seat['id'] }}"
                                                    title="โซน {{ $zone['name'] }} แถว {{ $rowLabel }} ที่นั่ง {{ $seat['number'] }}"
                                                >
                                                    @if($showPersonIcon)
                                                        <svg viewBox="0 0 24 24" class="seat-box__icon" aria-hidden="true">
                                                            <path fill="currentColor" d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                                                        </svg>
                                                    @elseif($showCrossIcon)
                                                        <svg viewBox="0 0 24 24" class="seat-box__icon" aria-hidden="true">
                                                            <path fill="currentColor" d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12 5.7 16.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"/>
                                                        </svg>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>

                                        <span class="seat-row-label">{{ $rowLabel }}</span>
                                    </div>
                                @empty
                                    <p class="empty-seat-text">ยังไม่มีข้อมูลผังที่นั่งในโซนนี้</p>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
            @else
                <p class="empty-seat-text">ยังไม่มีข้อมูลโซนสำหรับคอนเสิร์ตนี้</p>
            @endif

            {{-- Legend อธิบายสัญลักษณ์ด้านล่างผังที่นั่ง --}}
            <div class="seat-legend-bar">
                <div id="checkInLegend" class="seat-legend-content" @if(!$statusConfig['isCheckInMode']) hidden @endif>
                    <div class="legend-entry">
                        <span class="seat-box seat-box--available legend-box"></span>
                        <span>ว่าง</span>
                    </div>
                    <div class="legend-entry">
                        <span class="seat-box seat-box--not-checked-in legend-box">
                            <svg viewBox="0 0 24 24" class="seat-box__icon"><path fill="currentColor" d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                        </span>
                        <span>ยังไม่เข้างาน</span>
                    </div>
                    <div class="legend-entry">
                        <span class="seat-box seat-box--checked-in legend-box">
                            <svg viewBox="0 0 24 24" class="seat-box__icon"><path fill="currentColor" d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                        </span>
                        <span>เข้างานแล้ว</span>
                    </div>
                    <div class="legend-entry">
                        <span class="seat-box seat-box--disabled legend-box">
                            <svg viewBox="0 0 24 24" class="seat-box__icon"><path fill="currentColor" d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12 5.7 16.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"/></svg>
                        </span>
                        <span>ไม่เปิดให้บริการ</span>
                    </div>
                </div>
                <div id="salesLegend" class="seat-legend-content" @if($statusConfig['isCheckInMode']) hidden @endif>
                    <div class="legend-entry">
                        <span class="seat-box seat-box--available legend-box"></span>
                        <span>ว่าง</span>
                    </div>
                    <div class="legend-entry">
                        <span class="seat-box seat-box--sold legend-box"></span>
                        <span>ขายแล้ว</span>
                    </div>
                    <div class="legend-entry">
                        <span class="seat-box seat-box--locked legend-box"></span>
                        <span>กำลังทำรายการ</span>
                    </div>
                    <div class="legend-entry">
                        <span class="seat-box seat-box--disabled legend-box">
                            <svg viewBox="0 0 24 24" class="seat-box__icon"><path fill="currentColor" d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12 5.7 16.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"/></svg>
                        </span>
                        <span>ไม่เปิดให้บริการ</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ฝั่งตารางรายงานจำนวนตั๋วแยกตามโซน --}}
        <section class="zone-report-section">
            <div class="section-heading-with-bar">
                <span class="heading-orange-bar"></span>
                <h2 class="section-heading-title">รายงานจำนวนตั๋วแยกตามโซน</h2>
            </div>

            <div class="zone-report-card">
                <table class="zone-report-table">
                    <thead>
                        <tr>
                            <th>ชื่อโซน</th>
                            <th>ราคา</th>
                            <th>ตั๋วคงเหลือ</th>
                            <th>รายได้โซน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($zoneReports as $zone)
                            <tr data-zone-row-id="{{ $zone['id'] }}">
                                <td>
                                    <div class="zone-title-cell">
                                        <span class="zone-square-dot" style="background-color: {{ $zone['colorHex'] }};"></span>
                                        <span>{{ $zone['name'] }}</span>
                                    </div>
                                </td>
                                <td>{{ number_format($zone['price']) }} บาท</td>
                                <td>
                                    <span
                                        class="remaining-pill {{ $zone['remainingSeats'] === 0 ? 'remaining-pill--zero' : 'remaining-pill--available' }}"
                                        data-zone-remaining
                                    >
                                        {{ number_format($zone['remainingSeats']) }} ที่นั่ง
                                    </span>
                                </td>
                                <td data-zone-revenue>{{ number_format($zone['zoneRevenue']) }} บาท</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // กำหนดค่าคงที่สำหรับช่วงเวลาการอัปเดตสถานะที่นั่งและไอคอน SVG
    const POLLING_INTERVAL_MS = 5000;
    const ICON_PERSON_SVG = '<svg viewBox="0 0 24 24" class="seat-box__icon" aria-hidden="true"><path fill="currentColor" d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>';
    const ICON_CROSS_SVG = '<svg viewBox="0 0 24 24" class="seat-box__icon" aria-hidden="true"><path fill="currentColor" d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12 5.7 16.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"/></svg>';

    document.addEventListener('DOMContentLoaded', () => {
        initZonePillTabs();
        initRealtimePolling();
    });

    /**
     * initZonePillTabs()
     * สลับปุ่มโซนที่ถูกเลือกพร้อมกรอบสีส้มและแสดงผังที่นั่งเฉพาะโซนนั้น
     *
     * @return void
     */
    function initZonePillTabs() {
        const tabButtons = document.querySelectorAll('#zoneFilterTabs .zone-pill-btn');
        const zoneBoards = document.querySelectorAll('.zone-seat-board');

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                tabButtons.forEach((itemButton) => itemButton.classList.remove('is-active'));
                tabButtons.forEach((itemButton) => itemButton.setAttribute('aria-pressed', 'false'));
                button.classList.add('is-active');
                button.setAttribute('aria-pressed', 'true');

                const selectedZoneId = button.getAttribute('data-zone-id');
                zoneBoards.forEach((board) => {
                    if (board.getAttribute('data-zone-board') === selectedZoneId) {
                        board.classList.add('is-visible');
                    } else {
                        board.classList.remove('is-visible');
                    }
                });
            });
        });
    }

    /**
     * initRealtimePolling()
     * ดึงข้อมูลสถานะคอนเสิร์ต ผังที่นั่ง และรายงานยอดขายแบบ Real-time เมื่อหน้าจอเปิดใช้งานอยู่
     *
     * @return void
     */
    function initRealtimePolling() {
        const container = document.querySelector('.check-concert-container');
        const realtimeUrl = container ? container.getAttribute('data-realtime-url') : null;

        if (!realtimeUrl) {
            return;
        }

        setInterval(async () => {
            if (document.hidden) {
                return;
            }

            try {
                const response = await fetch(realtimeUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    console.error(`Concert status polling failed: ${response.status}`);
                    return;
                }

                const payload = await response.json();
                updateSummaryCards(payload);
                updateStatusAction(payload);
                updateSeatMatrix(payload.seatMapsByZone, payload.isCheckInMode);
                updateSeatLegend(payload.isCheckInMode);
                updateZoneReportTable(payload.zoneReports);
            } catch (error) {
                console.error('Concert status polling failed.', error);
            }
        }, POLLING_INTERVAL_MS);
    }

    /**
     * updateSummaryCards(payload)
     * อัปเดตตัวเลขและข้อความสถานะในการ์ดสรุป 5 ใบด้านบน
     *
     * @param Object payload ข้อมูลสรุปยอดขายและสถานะคอนเสิร์ตล่าสุด
     * @return void
     */
    function updateSummaryCards(payload) {
        document.getElementById('realtimeTotalRevenue').textContent = payload.totalRevenue;
        document.getElementById('realtimeSoldTickets').textContent = payload.soldTickets;
        document.getElementById('realtimeRemainingSeats').textContent = payload.remainingSeats;
        document.getElementById('realtimeTotalSeats').textContent = payload.totalSeats;

        const statusElement = document.getElementById('realtimeConcertStatus');
        statusElement.textContent = payload.statusLabel;
        statusElement.className = `summary-card__value ${payload.statusTextClass}`;
    }

    /**
     * updateStatusAction(payload)
     * อัปเดตปุ่มจัดการสถานะให้ตรงกับสถานะล่าสุดของคอนเสิร์ต
     *
     * @param Object payload ข้อมูลปุ่มสถานะล่าสุด
     * @return void
     */
    function updateStatusAction(payload) {
        const form = document.getElementById('concertStatusActionForm');
        const button = document.getElementById('concertStatusActionButton');
        const targetStatusInput = form.querySelector('input[name="target_status"]');

        form.hidden = !payload.actionTargetStatus;
        if (!payload.actionTargetStatus) {
            return;
        }

        targetStatusInput.value = payload.actionTargetStatus;
        button.textContent = payload.actionButtonLabel;
        button.className = `btn-top-action ${payload.actionButtonClass}`;
    }

    /**
     * updateSeatMatrix(seatMapsByZone, isCheckInMode)
     * อัปเดตสีและสัญลักษณ์ของกล่องที่นั่งทั้งหมดตามสถานะล่าสุด
     *
     * @param Object seatMapsByZone ข้อมูลที่นั่งแยกตามโซนและแถว
     * @param Boolean isCheckInMode สถานะโหมดการแสดงผลเช็กอินเข้างาน
     * @return void
     */
    function updateSeatMatrix(seatMapsByZone, isCheckInMode) {
        Object.values(seatMapsByZone).forEach((rowsInZone) => {
            Object.values(rowsInZone).forEach((seatsInRow) => {
                seatsInRow.forEach((seat) => {
                    const seatBox = document.querySelector(`.seat-box[data-seat-id="${seat.id}"]`);
                    if (!seatBox) {
                        return;
                    }

                    let stateClass = 'seat-box--available';
                    let innerHtml = '';

                    if (seat.status === 'DISABLED') {
                        stateClass = 'seat-box--disabled';
                        innerHtml = ICON_CROSS_SVG;
                    } else if (isCheckInMode) {
                        if (seat.status === 'SOLD' && seat.isCheckedIn) {
                            stateClass = 'seat-box--checked-in';
                            innerHtml = ICON_PERSON_SVG;
                        } else if (seat.status === 'SOLD') {
                            stateClass = 'seat-box--not-checked-in';
                            innerHtml = ICON_PERSON_SVG;
                        }
                    } else {
                        if (seat.status === 'SOLD') {
                            stateClass = 'seat-box--sold';
                        } else if (seat.status === 'LOCKED') {
                            stateClass = 'seat-box--locked';
                        }
                    }

                    seatBox.className = `seat-box ${stateClass}`;
                    seatBox.innerHTML = innerHtml;
                });
            });
        });
    }

    /**
     * updateSeatLegend(isCheckInMode)
     * สลับ Legend ให้ตรงกับโหมดจำหน่ายหรือโหมดตรวจเข้างาน
     *
     * @param Boolean isCheckInMode สถานะโหมดการแสดงผลเช็กอินเข้างาน
     * @return void
     */
    function updateSeatLegend(isCheckInMode) {
        document.getElementById('checkInLegend').hidden = !isCheckInMode;
        document.getElementById('salesLegend').hidden = isCheckInMode;
    }

    /**
     * updateZoneReportTable(zoneReports)
     * อัปเดตป้ายจำนวนตั๋วคงเหลือและรายได้รวมของแต่ละโซนในตารางรายงาน
     *
     * @param Array zoneReports รายการข้อมูลรายงานยอดขายรายโซน
     * @return void
     */
    function updateZoneReportTable(zoneReports) {
        zoneReports.forEach((zone) => {
            const row = document.querySelector(`tr[data-zone-row-id="${zone.id}"]`);
            if (!row) {
                return;
            }

            const remainingPill = row.querySelector('[data-zone-remaining]');
            const revenueCell = row.querySelector('[data-zone-revenue]');

            if (remainingPill) {
                remainingPill.textContent = `${zone.remainingSeats.toLocaleString()} ที่นั่ง`;
                remainingPill.className = `remaining-pill ${zone.remainingSeats === 0 ? 'remaining-pill--zero' : 'remaining-pill--available'}`;
            }

            if (revenueCell) {
                revenueCell.textContent = `${zone.zoneRevenue.toLocaleString()} บาท`;
            }
        });
    }
</script>
@endpush
