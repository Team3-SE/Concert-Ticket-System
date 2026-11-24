<?php

use Illuminate\Support\Facades\Schedule;

// Expire PENDING payments ทุก 10 วินาที (ใช้เวลา dev ตั้ง expires_in สั้นๆ เดโมได้เร็ว)
Schedule::command('payments:expire')->everyTenSeconds();
