<?php

namespace App\Http\Controllers;

use App\Models\Concert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * ConcertImageController
 * จัดการอัปโหลด/ลบรูปภาพของคอนเสิร์ต (โปสเตอร์, แบนเนอร์, ผังที่นั่ง)
 * Author : AI Agent
 * Created : 17/09/2026
 */
class ConcertImageController extends Controller
{
    /**
     * ขนาดรูปที่แนะนำ — แก้ค่าตรงนี้แล้ว View จะอัปเดตตาม
     */
    const IMAGE_SIZES = [
        'poster' => [
            'w' => 500,
            'h' => 700,
            'h' => 750,
            'maxKB' => 2048,   // 2 MB
        ],
        'banner' => [
            'w' => 1200,
            'h' => 300,
            'maxKB' => 3072,   // 3 MB
        ],
        'seat_map' => [
            'w' => 1200,
            'h' => 900,
            'maxKB' => 5120,   // 5 MB
        ],
    ];

    /**
     * index()
     * แสดงหน้าจัดการรูปภาพ พร้อมข้อมูล concerts ทั้งหมดและขนาดรูปที่แนะนำ
     * param : none
     * return : View
     */
    public function index()
    {
        $concerts = Concert::orderBy('event_datetime', 'desc')->get();

        return view('manage-images', [
            'concerts'   => $concerts,
            'imageSizes' => self::IMAGE_SIZES,
        ]);
    }

    /**
     * upload()
     * อัปโหลด/เปลี่ยนรูปภาพของคอนเสิร์ตตามประเภท (poster, banner, seat_map)
     * param : Request $request, string $concertId, string $type
     * return : Redirect
     */
    public function upload(Request $request, string $concertId, string $type)
    {
        $concert = Concert::findOrFail($concertId);

        $columnMap = [
            'poster'   => 'poster_path',
            'banner'   => 'banner_path',
            'seat_map' => 'seat_map_image_path',
        ];

        if (!isset($columnMap[$type])) {
            return back()->with('error', 'ประเภทรูปไม่ถูกต้อง');
        }

        $column = $columnMap[$type];
        $maxKB  = self::IMAGE_SIZES[$type]['maxKB'];

        $request->validate([
            'image' => "required|image|mimes:jpeg,png,webp|max:{$maxKB}",
        ], [
            'image.required' => 'กรุณาเลือกไฟล์รูปภาพ',
            'image.image'    => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น',
            'image.mimes'    => 'รองรับเฉพาะ JPEG, PNG, WEBP',
            'image.max'      => 'ขนาดไฟล์ต้องไม่เกิน ' . round($maxKB / 1024, 1) . ' MB',
        ]);

        // ลบรูปเก่า (ถ้ามี)
        if ($concert->{$column}) {
            Storage::disk('public')->delete($concert->{$column});
        }

        // บันทึกรูปใหม่
        $path = $request->file('image')->store("concerts/{$concertId}", 'public');
        $concert->{$column} = $path;
        $concert->save();

        return back()->with('success', 'อัปโหลดรูปสำเร็จ!');
    }

    /**
     * destroy()
     * ลบรูปภาพของคอนเสิร์ตตามประเภท
     * param : string $concertId, string $type
     * return : Redirect
     */
    public function destroy(string $concertId, string $type)
    {
        $concert = Concert::findOrFail($concertId);

        $columnMap = [
            'poster'   => 'poster_path',
            'banner'   => 'banner_path',
            'seat_map' => 'seat_map_image_path',
        ];

        if (!isset($columnMap[$type])) {
            return back()->with('error', 'ประเภทรูปไม่ถูกต้อง');
        }

        $column = $columnMap[$type];

        if ($concert->{$column}) {
            Storage::disk('public')->delete($concert->{$column});
            $concert->{$column} = null;
            $concert->save();
        }

        return back()->with('success', 'ลบรูปสำเร็จ!');
    }
}

