<?php

namespace App\Providers;

use Carbon\Carbon as BaseCarbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
