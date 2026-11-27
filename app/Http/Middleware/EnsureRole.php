<?php

/**
 * EnsureRole
 * จำกัดว่า route ใช้ได้กับ role ไหน ใช้ใน route เป็น ->middleware('role:ORGANIZER') หรือ 'role:GUEST,USER'
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 27/10/2569
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

/*
 * - GUEST = คนที่ยังไม่เข้าสู่ระบบ (หน้าฝั่ง User ที่เปิดดูได้โดยไม่ต้อง login)
 * - เข้าสู่ระบบแล้วแต่ role ไม่ตรง → พากลับหน้าแรกของ role ตัวเอง (AppUser::homeUrl)
 *   เช่น Organizer เปิดหน้า Home ของลูกค้า → ไป /organizer/dashboard
 * - ยังไม่เข้าสู่ระบบและ route ไม่รับ GUEST → ให้ระบบ auth จัดการ (กลับหน้าแรกพร้อมเปิดหน้าต่าง login)
 * ลงชื่อ alias 'role' ไว้ที่ bootstrap/app.php
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /* ใส่ใน role:... เพื่อให้คนที่ยังไม่ login ใช้ route นี้ได้ */
    public const GUEST = 'GUEST';

    /**
     * handle($request, $next, $roles)
     * ตรวจ role ของผู้ใช้กับรายการที่ route อนุญาต
     *
     * @param Request $request คำขอ HTTP ที่ส่งมา
     * @param Closure $next ค่าถัดไป
     * @param string[] $roles role ที่อนุญาต (USER, ORGANIZER, STAFF, ADMIN, GUEST
     * @return Response ไปต่อ หรือ redirect ไปหน้าแรกของ role ตัวเอง
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $roles = array_map('strtoupper', $roles);
        $user  = $request->user();

        if (!$user) {
            if (in_array(self::GUEST, $roles, true)) {
                return $next($request);
            }
            throw new AuthenticationException('Unauthenticated.');
        }

        if (in_array(strtoupper((string) $user->role), $roles, true)) {
            return $next($request);
        }

        $home = $user->homeUrl();

        // กันวนไม่รู้จบ (เช่น role ที่ไม่รู้จัก แล้วหน้าแรกของ role นั้นก็เข้าไม่ได้)
        if ($request->expectsJson() || rtrim($request->getPathInfo(), '/') === rtrim($home, '/')) {
            abort(403, 'บัญชีนี้ไม่มีสิทธิ์ใช้งานส่วนนี้');
        }

        return redirect($home);
    }
}
