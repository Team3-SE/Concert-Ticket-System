<?php

/**
 * AppServiceProvider
 * ตั้งค่าบริการเริ่มต้นของแอปพลิเคชัน เช่น View Composer และรูปแบบวันเวลา (พ.ศ.)
 * Author :
 * Created :
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Providers;

use App\View\Composers\AuthModalComposer;
use Carbon\Carbon as BaseCarbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * register()
     * Register any application services.
     *
     * @return void ไม่มีค่าส่งกลับ
     */
    public function register(): void
    {
        //
    }

    /**
     * boot()
     * Bootstrap any application services.
     *
     * @return void ไม่มีค่าส่งกลับ
     */
    public function boot(): void
    {
        // ข้อมูลหน้าต่างเข้าสู่ระบบ/สมัครสมาชิก/ลืมรหัสผ่าน/ตรวจอีเมล (user/partials/auth-modals)
        View::composer('user.partials.auth-modals', AuthModalComposer::class);

        // รูปแบบวันเวลามาตรฐานของระบบ: ว/ด/ป(พ.ศ.) และเวลา 24 ชม.
        //   $dt->thDate()     -> 16/10/2569
        //   $dt->thDateLong() -> 16 ตุลาคม 2569
        //   $dt->thDateTime() -> 16/10/2569 20:00
        foreach ([BaseCarbon::class, Carbon::class, CarbonImmutable::class] as $class) {
            $class::macro('thDate', function () {
                /** @var \Carbon\CarbonInterface $this */
                return $this->format('d/m/') . ($this->year + 543);
            });
            // 30 ตุลาคม 2569
            $class::macro('thDateLong', function () {
                /** @var \Carbon\CarbonInterface $this */
                $months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                           'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                return $this->day . ' ' . $months[$this->month - 1] . ' ' . ($this->year + 543);
            });
            $class::macro('thDateTime', function () {
                /** @var \Carbon\CarbonInterface $this */
                return $this->format('d/m/') . ($this->year + 543) . ' ' . $this->format('H:i');
            });
        }
    }
}
