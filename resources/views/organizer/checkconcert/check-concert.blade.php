{{--
 * check-concert.blade.php
 * หน้าจอตรวจสอบรายละเอียดคอนเสิร์ต ผังที่นั่งจำลองสถานะปัจจุบัน และรายงานจำนวนตั๋วแยกตามโซน
 * @Author 67160434 จิตฏิยากร โสภา
 * @Create Date 25/11/2569
 * @Update Date 26/11/2569 67160434 จิตฏิยากร โสภา
--}}

@extends('layouts.backoffice')

@section('title', 'ตรวจสอบคอนเสิร์ต - ' . ($concert->title ?? 'TEAM 3 Ticket'))
@section('body_class', 'bo page-organizer-check-concert')

@push('styles')
    @vite(['resources/css/checkconcert/check-concert.css'])
@endpush

@section('content')
<div
    class="check-concert-container"
    data-realtime-url="{{ route('organizer.concerts.check.realtime', ['concertId' => $concert->id]) }}"
>
    {{-- ส่วนหัวหน้าจอและปุ่มย้อนกลับ --}}
    <div class="check-concert-header">
        <a href="{{ route('organizer.concerts.index') }}" class="btn-back-circle" aria-label="ย้อนกลับ">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="page-main-title">ตรวจสอบคอนเสิร์ต</h1>
    </div>

    {{-- การ์ดรายละเอียดคอนเสิร์ต --}}
    <section class="concert-info-card">
        <div class="concert-info-card__poster">
            <img
                src="{{ $concert->poster_url ?? asset('images/concert-placeholder.jpg') }}"
                alt="{{ $concert->title }}"
                class="concert-poster-img"
            >
        </div>

        <div class="concert-info-card__details">
            <div class="concert-info-top">
                <h2 class="concert-title">{{ $concert->title }}</h2>
                <p class="concert-artist">
                    ศิลปิน : <span>{{ $concert->artist_name ?? '-' }}</span>
                </p>

                <ul class="concert-meta-list">
                    <li>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span>
                            {{ \Carbon\Carbon::parse($concert->event_date)->locale('th')->translatedFormat('lที่ j F Y') }}
                        </span>
                    </li>
                    <li>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>
                            เวลา {{ \Carbon\Carbon::parse($concert->start_time)->format('H:i') }}
                            @if(!empty($concert->end_time))
                                - {{ \Carbon\Carbon::parse($concert->end_time)->format('H:i') }}
                            @endif
                            น.
                        </span>
                    </li>
                    <li>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span>{{ $concert->venue_name ?? '-' }}</span>
                    </li>
                </ul>
            </div>

            <div class="concert-info-bottom">
                <a href="#" class="btn-edit-concert">แก้ไขคอนเสิร์ต</a>
            </div>
        </div>
    </section>

    {{-- การ์ดสรุปข้อมูลตัวเลขและสถานะ (อัปเดต Real-time) --}}
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

    {{-- ส่วนผังที่นั่งและรายงานรายโซน (สลับ Layout ตามจำนวนที่นั่งต่อแถว) --}}
    <div class="seat-and-report-layout {{ $isWideLayout ? 'layout-stacked' : 'layout-split' }}">
        {{-- กล่องแสดงผังที่นั่ง --}}
        <section class="seat-map-panel">
            <div class="panel-header-with-tabs">
                <h3 class="panel-title">ผังที่นั่ง</h3>

                <div class="zone-tabs" id="zoneFilterTabs">
                    @foreach($zoneReports as $index => $zone)
                        <button
                            type="button"
                            class="zone-tab {{ $index === 0 ? 'is-active' : '' }}"
                            data-zone-id="{{ $zone['id'] }}"
                        >
                            <span class="zone-color-dot" style="background-color: {{ $zone['colorHex'] }};"></span>
                            <span>{{ $zone['name'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="stage-banner">
                <span>STAGE</span>
            </div>

            <div class="seat-grid-scroll-wrapper">
                <div class="seat-grid-rows">
                    @forelse($seatRows as $rowLabel => $seatsInRow)
                        <div class="seat-row">
                            <span class="seat-row__label">{{ $rowLabel }}</span>

                            <div class="seat-row__dots">
                                @foreach($seatsInRow as $seat)
                                    @php
                                        $seatStateClass = 'seat-dot--available';
                                        $showPersonIcon = false;
                                        $showCrossIcon = false;

                                        if ($seat['status'] === 'DISABLED') {
                                            $seatStateClass = 'seat-dot--disabled';
                                            $showCrossIcon = true;
                                        } elseif ($statusConfig['isCheckInMode']) {
                                            if ($seat['status'] === 'SOLD' && $seat['isCheckedIn']) {
                                                $seatStateClass = 'seat-dot--checked-in';
                                                $showPersonIcon = true;
                                            } elseif ($seat['status'] === 'SOLD') {
                                                $seatStateClass = 'seat-dot--not-checked-in';
                                                $showPersonIcon = true;
                                            }
                                        } else {
                                            if ($seat['status'] === 'SOLD') {
                                                $seatStateClass = 'seat-dot--sold';
                                            } elseif ($seat['status'] === 'LOCKED') {
                                                $seatStateClass = 'seat-dot--locked';
                                            }
                                        }
                                    @endphp

                                    <span
                                        class="seat-dot {{ $seatStateClass }}"
                                        data-seat-id="{{ $seat['id'] }}"
                                        data-zone-id="{{ $seat['zoneId'] }}"
                                        title="แถว {{ $rowLabel }} ที่นั่ง {{ $seat['number'] }}"
                                    >
                                        @if($showPersonIcon)
                                            <svg viewBox="0 0 24 24" class="seat-dot__icon" aria-hidden="true">
                                                <path fill="currentColor" d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                                            </svg>
                                        @elseif($showCrossIcon)
                                            <svg viewBox="0 0 24 24" class="seat-dot__icon" aria-hidden="true">
                                                <path fill="currentColor" d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12 5.7 16.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"/>
                                            </svg>
                                        @endif
                                    </span>
                                @endforeach
                            </div>

                            <span class="seat-row__label">{{ $rowLabel }}</span>
                        </div>
                    @empty
                        <p class="empty-seat-message">ยังไม่มีข้อมูลผังที่นั่งในโซนนี้</p>
                    @endforelse
                </div>
            </div>

            {{-- คำอธิบายสถานะที่นั่ง (Legend) --}}
            <div class="seat-legend">
                @if($statusConfig['isCheckInMode'])
                    <div class="legend-item">
                        <span class="seat-dot seat-dot--checked-in legend-dot">
                            <svg viewBox="0 0 24 24" class="seat-dot__icon"><path fill="currentColor" d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                        </span>
                        <span>เช็กอินแล้ว</span>
                    </div>
                    <div class="legend-item">
                        <span class="seat-dot seat-dot--not-checked-in legend-dot">
                            <svg viewBox="0 0 24 24" class="seat-dot__icon"><path fill="currentColor" d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                        </span>
                        <span>ยังไม่เช็กอิน</span>
                    </div>
                    <div class="legend-item">
                        <span class="seat-dot seat-dot--available legend-dot"></span>
                        <span>ที่นั่งว่าง</span>
                    </div>
                    <div class="legend-item">
                        <span class="seat-dot seat-dot--disabled legend-dot">
                            <svg viewBox="0 0 24 24" class="seat-dot__icon"><path fill="currentColor" d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12 5.7 16.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"/></svg>
                        </span>
                        <span>ปิดใช้งาน</span>
                    </div>
                @else
                    <div class="legend-item">
                        <span class="seat-dot seat-dot--available legend-dot"></span>
                        <span>ที่นั่งว่าง</span>
                    </div>
                    <div class="legend-item">
                        <span class="seat-dot seat-dot--sold legend-dot"></span>
                        <span>ขายแล้ว</span>
                    </div>
                    <div class="legend-item">
                        <span class="seat-dot seat-dot--locked legend-dot"></span>
                        <span>กำลังทำรายการ</span>
                    </div>
                    <div class="legend-item">
                        <span class="seat-dot seat-dot--disabled legend-dot">
                            <svg viewBox="0 0 24 24" class="seat-dot__icon"><path fill="currentColor" d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12 5.7 16.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"/></svg>
                        </span>
                        <span>ปิดใช้งาน</span>
                    </div>
                @endif
            </div>
        </section>

        {{-- ตารางรายงานจำนวนตั๋วและรายได้แยกตามโซน --}}
        <section class="zone-report-panel">
            <h3 class="panel-title">รายงานจำนวนตั๋วแยกตามโซน</h3>

            <div class="zone-table-wrapper">
                <table class="zone-report-table">
                    <thead>
                        <tr>
                            <th>โซน</th>
                            <th class="text-center">ราคาต่อใบ</th>
                            <th class="text-center">ตั๋วคงเหลือ</th>
                            <th class="text-right">รายได้</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($zoneReports as $zone)
                            <tr data-zone-row-id="{{ $zone['id'] }}">
                                <td>
                                    <div class="zone-name-cell">
                                        <span class="zone-color-dot" style="background-color: {{ $zone['colorHex'] }};"></span>
                                        <span>{{ $zone['name'] }}</span>
                                    </div>
                                </td>
                                <td class="text-center">{{ number_format($zone['price']) }} บาท</td>
                                <td class="text-center">
                                    <span
                                        class="ticket-pill {{ $zone['remainingSeats'] === 0 ? 'ticket-pill--sold-out' : 'ticket-pill--available' }}"
                                        data-zone-remaining
                                    >
                                        {{ number_format($zone['remainingSeats']) }} ที่นั่ง
                                    </span>
                                </td>
                                <td class="text-right" data-zone-revenue>{{ number_format($zone['zoneRevenue']) }} บาท</td>
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
    /* ช่วงเวลาในการดึงข้อมูลอัปเดตอัตโนมัติ (มิลลิวินาที) */
    const POLLING_INTERVAL_MS = 3000;

    /* ไอคอน SVG สำหรับสถานะที่นั่ง */
    const ICON_PERSON_SVG = '<svg viewBox="0 0 24 24" class="seat-dot__icon" aria-hidden="true"><path fill="currentColor" d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>';
    const ICON_CROSS_SVG = '<svg viewBox="0 0 24 24" class="seat-dot__icon" aria-hidden="true"><path fill="currentColor" d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12 5.7 16.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"/></svg>';

    document.addEventListener('DOMContentLoaded', () => {
        initZoneFilterTabs();
        initRealtimePolling();
    });

    /**
     * initZoneFilterTabs()
     * ตั้งค่าการคลิกปุ่มเลือกโซนเพื่อกรองการแสดงผลจุดที่นั่ง
     */
    function initZoneFilterTabs() {
        const tabButtons = document.querySelectorAll('#zoneFilterTabs .zone-tab');

        tabButtons.forEach((tabButton) => {
            tabButton.addEventListener('click', () => {
                tabButtons.forEach((btn) => btn.classList.remove('is-active'));
                tabButton.classList.add('is-active');

                const selectedZoneId = tabButton.getAttribute('data-zone-id');
                document.querySelectorAll('.seat-dot').forEach((seatDot) => {
                    const isMatch = seatDot.getAttribute('data-zone-id') === selectedZoneId;
                    seatDot.style.opacity = isMatch ? '1' : '0.25';
                });
            });
        });
    }

    /**
     * initRealtimePolling()
     * เริ่มต้นการดึงข้อมูลสถานะคอนเสิร์ตล่าสุดจากเซิร์ฟเวอร์เป็นระยะ
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
                    headers: { 'Accept': 'application/json' },
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                updateSummaryCards(data);
                updateSeatMap(data.seatRows, data.isCheckInMode);
                updateZoneReportTable(data.zoneReports);
            } catch (error) {
                console.error('ไม่สามารถอัปเดตข้อมูล Real-time ได้:', error);
            }
        }, POLLING_INTERVAL_MS);
    }

    /**
     * updateSummaryCards(data)
     * อัปเดตตัวเลขและสีสถานะในการ์ดสรุป 5 ใบด้านบน
     */
    function updateSummaryCards(data) {
        document.getElementById('realtimeTotalRevenue').textContent = data.totalRevenue;
        document.getElementById('realtimeSoldTickets').textContent = data.soldTickets;
        document.getElementById('realtimeRemainingSeats').textContent = data.remainingSeats;
        document.getElementById('realtimeTotalSeats').textContent = data.totalSeats;

        const statusElement = document.getElementById('realtimeConcertStatus');
        statusElement.textContent = data.statusLabel;
        statusElement.className = `summary-card__value ${data.statusTextClass}`;
    }

    /**
     * updateSeatMap(seatRows, isCheckInMode)
     * อัปเดตสีและไอคอนของจุดที่นั่งทุกตัวในผังตามสถานะล่าสุด
     */
    function updateSeatMap(seatRows, isCheckInMode) {
        Object.values(seatRows).forEach((seatsInRow) => {
            seatsInRow.forEach((seat) => {
                const seatDot = document.querySelector(`.seat-dot[data-seat-id="${seat.id}"]`);
                if (!seatDot) {
                    return;
                }

                let stateClass = 'seat-dot--available';
                let innerHtml = '';

                if (seat.status === 'DISABLED') {
                    stateClass = 'seat-dot--disabled';
                    innerHtml = ICON_CROSS_SVG;
                } else if (isCheckInMode) {
                    if (seat.status === 'SOLD' && seat.isCheckedIn) {
                        stateClass = 'seat-dot--checked-in';
                        innerHtml = ICON_PERSON_SVG;
                    } else if (seat.status === 'SOLD') {
                        stateClass = 'seat-dot--not-checked-in';
                        innerHtml = ICON_PERSON_SVG;
                    }
                } else {
                    if (seat.status === 'SOLD') {
                        stateClass = 'seat-dot--sold';
                    } else if (seat.status === 'LOCKED') {
                        stateClass = 'seat-dot--locked';
                    }
                }

                seatDot.className = `seat-dot ${stateClass}`;
                seatDot.innerHTML = innerHtml;
            });
        });
    }

    /**
     * updateZoneReportTable(zoneReports)
     * อัปเดตจำนวนตั๋วคงเหลือและรายได้ของแต่ละโซนในตารางด้านขวา/ด้านล่าง
     */
    function updateZoneReportTable(zoneReports) {
        zoneReports.forEach((zone) => {
            const row = document.querySelector(`tr[data-zone-row-id="${zone.id}"]`);
            if (!row) {
                return;
            }

            const remainingBadge = row.querySelector('[data-zone-remaining]');
            const revenueCell = row.querySelector('[data-zone-revenue]');

            if (remainingBadge) {
                remainingBadge.textContent = `${zone.remainingSeats.toLocaleString()} ที่นั่ง`;
                remainingBadge.className = `ticket-pill ${zone.remainingSeats === 0 ? 'ticket-pill--sold-out' : 'ticket-pill--available'}`;
            }

            if (revenueCell) {
                revenueCell.textContent = `${zone.zoneRevenue.toLocaleString()} บาท`;
            }
        });
    }
</script>
@endpush
