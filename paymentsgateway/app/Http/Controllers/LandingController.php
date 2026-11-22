<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Setting;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total'     => Payment::count(),
            'completed' => Payment::where('status', 'COMPLETED')->count(),
            'failed'    => Payment::where('status', 'FAILED')->count(),
            'pending'   => Payment::where('status', 'PENDING')->count(),
            'expired'   => Payment::where('status', 'EXPIRED')->count(),
        ];

        $chaosMode = Setting::get('chaos_mode', 'normal');

        return view('landing', compact('stats', 'chaosMode'));
    }
}
