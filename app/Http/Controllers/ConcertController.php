<?php

namespace App\Http\Controllers;

use App\Models\Concert;
use Illuminate\Http\Request;

/**
 * ConcertController  (Customer / Public — read-only)
 * Methods: getConcertList(), searchConcert(), getConcertDetail()
 *
 * TODO – Organizer methods จะเพิ่มทีหลัง:
 *   createConcert(), updateConcert()
 *
 * Author : AI Agent | Updated : 27/09/2026
 */
class ConcertController extends Controller
{
    /**
     * getConcertList(): List<Concert>
     * ดึงรายการ Concert สำหรับหน้า Home (ไม่รวม Concert ที่ isDeleted)
     * ใช้: Customer (ConcertListInterface)
     */
    public function getConcertList()
    {
        return Concert::with('organization')
            ->where('is_deleted', 0)
            ->orderBy('sale_start_datetime', 'asc')
            ->get();
    }

    /**
     * searchConcert(keyword): List<Concert>
     * ค้นหา Concert ตามเงื่อนไข (ไม่รวม Concert ที่ isDeleted)
     * ใช้: Customer (ConcertSearchInterface)
     */
    public function searchConcert(string $keyword)
    {
        return Concert::with('organization')
            ->where('is_deleted', 0)
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('artist', 'like', "%{$keyword}%")
                  ->orWhereHas('organization', fn($q2) => $q2->where('name', 'like', "%{$keyword}%"));
            })
            ->get();
    }

    /**
     * getConcertDetail(concertID): Concert
     * ดึงรายละเอียด Concert รวม Organization (phone, email, facebook, line) และ Zone
     * ใช้: Customer (ConcertDetailInterface), Staff (ดูข้อมูล Concert ที่รับผิดชอบ)
     */
    public function getConcertDetail(int $concertId)
    {
        return Concert::with(['organization', 'zones'])
            ->where('concert_id', $concertId)
            ->where('is_deleted', 0)
            ->firstOrFail();
    }
}
