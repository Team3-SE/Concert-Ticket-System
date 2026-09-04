<?php

namespace App\Http\Controllers;

use App\Models\Concert;
use Illuminate\Http\Request;

/**
 * PaymentController
 * handle rendering of the payment page with real concert data from the database
 * Author : AI Agent
 * Created : 04/09/2026
 */
class PaymentController extends Controller
{
    /**
     * index()
     * display the payment page.
     * zone, seat, price, quantity come from query params (passed by booking page).
     * concert_id is also passed via query to fetch concert info from DB.
     * param : Request $request
     * return : \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ดึง concert_id จาก query params (ส่งมาจากหน้า booking)
        $concertId = $request->query('concert_id');

        if ($concertId) {
            $concert = Concert::with('contacts')
                ->where('concert_id', $concertId)
                ->firstOrFail();
        } else {
            // fallback: concert แรกที่ ON_SALE (กรณีเข้าหน้าตรง)
            $concert = Concert::with('contacts')
                ->where('status', 'ON_SALE')
                ->orderBy('event_datetime', 'asc')
                ->first();
        }

        return view('payment', [
            'concert'  => $concert,
            'contacts' => $concert ? $concert->contacts->keyBy('contact_type') : collect(),
        ]);
    }
}

