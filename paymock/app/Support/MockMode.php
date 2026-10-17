<?php

namespace App\Support;

use App\Models\Setting;

/**
 * แยกชั้นจำลองของ PayMock:
 *   1) Chaos (ใหญ่สุด) — มีผลตอนรับ API / checkout
 *   2) Webhook (รอง) — มีผลตอน emit หลัง settle
 *
 * Merchant (ระบบคอนเสิร์ต) ไม่ได้ส่ง scenario มา — กำหนดที่ Admin เท่านั้น
 */
class MockMode
{
    public const CHAOS_NAMES = [
        'normal',
        'down',
        'rate_limit',
        'slow',
    ];

    /** Chaos modes retired in favour of one business-facing 503 response. */
    private const RETIRED_CHAOS_NAMES = [
        'bad_auth', 'bad_json', 'error_rate', 'timeout',
    ];

    public const WEBHOOK_NAMES = [
        'webhook_lost',
        'webhook_delay',
        'webhook_dup',
        'webhook_bad_sig',
        'webhook_replay',
    ];

    public static function name(?string $mode): string
    {
        if ($mode === null || $mode === '') {
            return 'normal';
        }

        return explode(':', $mode, 2)[0];
    }

    public static function isChaos(?string $mode): bool
    {
        return in_array(self::name($mode), self::CHAOS_NAMES, true);
    }

    public static function isWebhook(?string $mode): bool
    {
        return in_array(self::name($mode), self::WEBHOOK_NAMES, true);
    }

    public static function chaosMode(): string
    {
        $mode = Setting::get('chaos_mode', 'normal') ?: 'normal';

        if (self::isChaos($mode)) {
            return $mode;
        }

        // Existing installations may still have an older simulation saved.
        // Treat it as the single outage scenario, rather than exposing a
        // technical 401/429/500/502 response to a customer.
        return in_array(self::name($mode), self::RETIRED_CHAOS_NAMES, true)
            ? 'down'
            : 'normal';
    }

    public static function webhookMode(): string
    {
        $mode = Setting::get('webhook_mode', 'normal') ?: 'normal';

        return self::isWebhook($mode) ? $mode : 'normal';
    }

}
