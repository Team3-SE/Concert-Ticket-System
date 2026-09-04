<?php

namespace App\Http\Controllers;

use App\Models\Concert;
use Illuminate\Http\Request;

/**
 * BookingController
 * handle rendering of the booking page with real data from the database
 * Author : AI Agent
 * Created : 04/09/2026
 */
class BookingController extends Controller
{
    /**
     * index()
     * display the booking page for a specific concert.
     * accepts optional ?concert_id=xxx query param.
     * if not provided, falls back to the first ON_SALE concert.
     * param : Request $request
     * return : \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ดึงคอนเสิร์ตที่ต้องการ หรือ fallback concert แรกที่ ON_SALE
        $concertId = $request->query('concert_id');

        if ($concertId) {
            $concert = Concert::with(['contacts', 'zones.seatPattern'])
                ->where('concert_id', $concertId)
                ->firstOrFail();
        } else {
            $concert = Concert::with(['contacts', 'zones.seatPattern'])
                ->where('status', 'ON_SALE')
                ->orderBy('event_datetime', 'asc')
                ->first();
        }

        // ถ้าไม่มีคอนเสิร์ตเลยในระบบ
        if (!$concert) {
            return view('booking', [
                'concert'       => null,
                'zones'         => collect(),
                'occupiedSeats' => [],
                'contacts'      => collect(),
            ]);
        }

        // สร้างข้อมูล zones พร้อมจำนวนที่นั่งว่าง
        $zones = $concert->zones->map(function ($zone) {
            // นับที่นั่งว่าง (AVAILABLE) สำหรับ SEATED zones
            $availableCount = $zone->zone_type !== 'STANDING'
                ? $zone->seats()->where('status', 'AVAILABLE')->count()
                : $zone->getAvailableCapacity();

            // pattern สำหรับ seat map (rows/cols)
            $pattern = $zone->seatPattern;

            return [
                'zone_id'    => $zone->zone_id,
                'name'       => $zone->name,
                'zone_type'  => $zone->zone_type,
                'price'      => (int) $zone->price,
                'seats'      => $availableCount,
                'color'      => $zone->color,
                'totalCols'  => $pattern ? $pattern->seats_per_row : 20,
                'rows'       => $pattern ? $this->generateRowLabels($pattern->start_row, $pattern->row_count) : ['A', 'B', 'C', 'D', 'E'],
            ];
        });

        // ดึงที่นั่งทั้งหมดที่ไม่ว่าง (LOCKED หรือ BOOKED) ของคอนเสิร์ตนี้
        // รูปแบบ: "A1", "B3", ... (row + number)
        $occupiedSeats = [];
        foreach ($concert->zones as $zone) {
            if ($zone->zone_type !== 'STANDING') {
                $bookedSeats = $zone->seats()
                    ->whereIn('status', ['LOCKED', 'BOOKED'])
                    ->get(['row', 'number']);

                foreach ($bookedSeats as $seat) {
                    $occupiedSeats[] = $seat->row . $seat->number;
                }
            }
        }

        return view('booking', [
            'concert'       => $concert,
            'zones'         => $zones,
            'occupiedSeats' => $occupiedSeats,
            'contacts'      => $concert->contacts->keyBy('contact_type'),
        ]);
    }

    /**
     * generateRowLabels()
     * สร้าง array ของ row labels เช่น ['A','B','C'] จาก start_row และ row_count
     * param : string $startRow, int $count
     * return : array
     */
    private function generateRowLabels(string $startRow, int $count): array
    {
        $labels = [];
        $startVal = 0;
        $row = strtoupper($startRow);
        for ($i = 0; $i < strlen($row); $i++) {
            $startVal = ($startVal * 26) + ord($row[$i]) - 64;
        }

        for ($offset = 0; $offset < $count; $offset++) {
            $val = $startVal + $offset;
            $label = '';
            while ($val > 0) {
                $val--;
                $label = chr(65 + ($val % 26)) . $label;
                $val = intdiv($val, 26);
            }
            $labels[] = $label;
        }

        return $labels;
    }
}

