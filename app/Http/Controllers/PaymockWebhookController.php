<?php

/**
 * PaymockWebhookController
 * รับ webhook จาก PayMock (POST /webhooks/paymock): ตรวจลายเซ็น แล้วปรับสถานะการชำระเงินของระบบหลัก
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 20/11/2569
 * Updated : 20/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Http\Controllers;

use App\Services\PaymockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymockWebhookController extends Controller
{
    /**
     * handle(request, paymock)
     * ลายเซ็นไม่ถูกต้อง → 401 (gateway ไม่ retry ด้วยข้อมูลเดิมให้ผ่านอยู่แล้ว), ไม่พบรายการ → 200 (กัน retry ไม่รู้จบ),
     * ประมวลผลผิดพลาด → 500 เพื่อให้ gateway ส่งซ้ำ (การประมวลผล idempotent)
     * param : request, paymock
     * return : JsonResponse
     */
    public function handle(Request $request, PaymockService $paymock): JsonResponse
    {
        $raw = $request->getContent();

        if (! $paymock->verifySignature($request->header('X-Signature'), $raw)) {
            Log::warning('PayMock webhook: invalid signature', ['ip' => $request->ip()]);

            return response()->json(['message' => 'invalid signature'], 401);
        }

        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return response()->json(['message' => 'invalid payload'], 400);
        }

        $payment = $paymock->findPayment($data);
        if ($payment === null) {
            Log::info('PayMock webhook: payment not found', ['id' => $data['id'] ?? null]);

            return response()->json(['ok' => true, 'result' => 'unknown_payment']);
        }

        try {
            $result = $paymock->applyResult($payment, $data, 'webhook');
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'processing error'], 500);
        }

        return response()->json(['ok' => true, 'result' => $result]);
    }
}
