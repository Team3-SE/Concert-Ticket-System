# PayMock API Reference
> เอกสารสำหรับนักพัฒนาระบบคอนเสิร์ต (ฝั่งต้นทาง) ที่ต้องการเชื่อมต่อกับ PayMock Payment Gateway

**Base URL:** `http://localhost:8081`  
**API Prefix:** `/v1`  
**Content-Type:** `application/json` (ทั้ง request และ response)

---

## สารบัญ

1. [Authentication](#1-authentication)
2. [Headers ที่ใช้บ่อย](#2-headers-ที่ใช้บ่อย)
3. [GET /v1/payments — ตรวจสถานะ API](#3-get-v1payments--ตรวจสถานะ-api)
4. [POST /v1/payments — สร้างรายการจ่ายเงิน](#4-post-v1payments--สร้างรายการจ่ายเงิน)
5. [GET /v1/payments/{id} — ดูสถานะรายการ](#5-get-v1paymentsid--ดูสถานะรายการ)
6. [POST /v1/payments/{id}/cancel — ยกเลิกรายการ](#6-post-v1paymentsidcancel--ยกเลิกรายการ)
7. [Webhook — สิ่งที่ PayMock ส่งมาหาคุณ](#7-webhook--สิ่งที่-paymock-ส่งมาหาคุณ)
8. [X-Mock-Scenario — ควบคุมพฤติกรรม](#8-x-mock-scenario--ควบคุมพฤติกรรม)
9. [Error Response Format](#9-error-response-format)
10. [Magic Card Numbers](#10-magic-card-numbers)
11. [Flow ทั้งหมด (Step-by-Step)](#11-flow-ทั้งหมด-step-by-step)

---

## 1. Authentication

ทุก request ต้องส่ง `Authorization` header:

```
Authorization: Bearer pm_test_key_concert_system_2024
```

| สถานการณ์ | Response |
|-----------|----------|
| ไม่มี header / key ผิด | `401 Unauthorized` |
| Chaos mode = `bad_auth` | `401 Unauthorized` (จำลอง key หมดอายุ) |

---

## 2. Headers ที่ใช้บ่อย

| Header | Required | ค่า | หมายเหตุ |
|--------|----------|-----|----------|
| `Authorization` | ✅ ทุก request | `Bearer pm_test_key_concert_system_2024` | API Key |
| `Content-Type` | ✅ POST | `application/json` | |
| `Idempotency-Key` | ✅ แนะนำ | string ไม่ซ้ำ เช่น booking code | ป้องกัน duplicate เมื่อ retry |
| `X-Mock-Scenario` | ❌ optional | ดู [หัวข้อ 8](#8-x-mock-scenario--ควบคุมพฤติกรรม) | จำลองสถานการณ์เฉพาะรายการ |

---

## 3. GET /v1/payments — ตรวจสถานะ API

ใช้เช็คว่า PayMock ออนไลน์อยู่ก่อนสร้างรายการ

### Request

```http
GET /v1/payments HTTP/1.1
Authorization: Bearer pm_test_key_concert_system_2024
```

### Response 200 OK

```json
{
  "service": "PayMock Gateway API",
  "status": "online",
  "chaos_mode": "normal",
  "recent": [
    {
      "id": "01JCXYZ...",
      "order_ref": "b_TK2024001",
      "amount": 1200.00,
      "currency": "THB",
      "method": "card",
      "status": "COMPLETED",
      "failure_code": null,
      "checkout_url": "http://localhost:8081/checkout/01JCXYZ...",
      "return_url": "http://localhost:8080/payment/return",
      "expires_at": "2026-10-13T10:10:00+07:00",
      "paid_at": "2026-10-13T10:05:32+07:00",
      "created_at": "2026-10-13T10:00:00+07:00"
    }
  ]
}
```

| Field | Type | ความหมาย |
|-------|------|----------|
| `status` | string | `"online"` เสมอ (ถ้าตอบได้) |
| `chaos_mode` | string | mode ปัจจุบัน: `normal`, `down`, `slow:3`, ฯลฯ |
| `recent` | array | 10 รายการล่าสุด (ดู schema ใน [หัวข้อ 4](#response-201-created)) |

---

## 4. POST /v1/payments — สร้างรายการจ่ายเงิน

### Request

```http
POST /v1/payments HTTP/1.1
Authorization: Bearer pm_test_key_concert_system_2024
Content-Type: application/json
Idempotency-Key: b_TK2024001
X-Mock-Scenario: webhook_delay:30
```

```json
{
  "order_ref":    "b_TK2024001",
  "amount":       1200.00,
  "method":       "card",
  "callback_url": "http://localhost:8080/webhooks/paymock",
  "return_url":   "http://localhost:8080/payment/return?booking=b_TK2024001",
  "expires_in":   600,
  "currency":     "THB",
  "metadata": {
    "booking_id": 42,
    "user_id": 7
  }
}
```

### Request Body Fields

| Field | Type | Required | ค่าที่ยอมรับ | Default | คำอธิบาย |
|-------|------|----------|-------------|---------|----------|
| `order_ref` | string | ✅ | max 255 chars | — | รหัสอ้างอิงจากระบบคอนเสิร์ต (booking code) |
| `amount` | number | ✅ | min 1 | — | ยอดเงิน (บาท, ทศนิยมได้) |
| `method` | string | ✅ | `"card"` หรือ `"promptpay"` | — | วิธีชำระ |
| `callback_url` | string | ✅ | URL ที่ถูกต้อง | — | URL ที่ PayMock จะ POST Webhook มาหา |
| `return_url` | string | ❌ | URL ที่ถูกต้อง | `null` | URL ที่ redirect ผู้ใช้กลับหลังจ่าย |
| `expires_in` | integer | ❌ | min 60 (วินาที) | 600 | อายุรายการก่อนหมดเวลา |
| `currency` | string | ❌ | 3 ตัวอักษร | `"THB"` | สกุลเงิน |
| `metadata` | object | ❌ | JSON object ใดก็ได้ | `null` | ข้อมูลเพิ่มเติม (ไม่ส่งใน webhook) |

### Idempotency

- ถ้าส่ง `Idempotency-Key` header → ใช้ค่านั้น
- ถ้าไม่ส่ง → ใช้ `order_ref` เป็น key อัตโนมัติ
- ถ้าส่ง key เดิมซ้ำ → **คืน payment เดิม** (ไม่สร้างใหม่) ใช้เมื่อ retry หลัง timeout

### Response 201 Created

```json
{
  "id":           "01JCXYZ1234567890ABCDEFGH",
  "order_ref":    "b_TK2024001",
  "amount":       1200.00,
  "currency":     "THB",
  "method":       "card",
  "status":       "PENDING",
  "failure_code": null,
  "checkout_url": "http://localhost:8081/checkout/01JCXYZ1234567890ABCDEFGH",
  "return_url":   "http://localhost:8080/payment/return?booking=b_TK2024001",
  "expires_at":   "2026-10-13T10:10:00+07:00",
  "paid_at":      null,
  "created_at":   "2026-10-13T10:00:00+07:00"
}
```

### Response Fields

| Field | Type | ความหมาย |
|-------|------|----------|
| `id` | string (ULID) | **Payment ID** — ใช้อ้างอิงใน API ทุก call ต่อจากนี้ |
| `order_ref` | string | รหัสที่ส่งมา |
| `amount` | float | ยอดเงิน |
| `currency` | string | สกุลเงิน |
| `method` | string | `card` หรือ `promptpay` |
| `status` | string | สถานะปัจจุบัน ดู [ตารางสถานะ](#สถานะ-payment) |
| `failure_code` | string\|null | รหัสเหตุผลเมื่อล้มเหลว ดู [ตารางด้านล่าง](#failure-codes) |
| `checkout_url` | string | **URL ที่ต้อง redirect ผู้ใช้ไป** (หน้าจ่ายเงินของ PayMock) |
| `return_url` | string\|null | URL ที่ PayMock จะ redirect ผู้ใช้กลับ |
| `expires_at` | ISO 8601\|null | เวลาหมดอายุ |
| `paid_at` | ISO 8601\|null | เวลาที่จ่ายสำเร็จ |
| `created_at` | ISO 8601 | เวลาสร้างรายการ |

### สถานะ Payment

| `status` | ความหมาย | ระบบคอนเสิร์ตควรทำ |
|----------|----------|-------------------|
| `PENDING` | รอชำระ | lock ที่นั่งไว้ รอ webhook |
| `COMPLETED` | จ่ายสำเร็จ | ออกตั๋ว, seat → SOLD |
| `FAILED` | ล้มเหลว / ถูกปฏิเสธ | แจ้งผู้ใช้ ปลดล็อกที่นั่ง (ถ้า expires) |
| `EXPIRED` | หมดเวลาก่อนจ่าย | ยกเลิกการจอง ปลดล็อกที่นั่ง |

### Failure Codes

| `failure_code` | ความหมาย | ระบบควรแสดงอะไร |
|----------------|----------|----------------|
| `card_declined` | บัตรถูกปฏิเสธ | "บัตรถูกปฏิเสธ กรุณาลองบัตรอื่น" |
| `insufficient_funds` | วงเงินไม่พอ | "วงเงินในบัตรไม่เพียงพอ" |
| `expired_card` | บัตรหมดอายุ | "บัตรหมดอายุ" |
| `incorrect_cvc` | CVC ผิด | "รหัส CVC ไม่ถูกต้อง" |
| `processing_error` | ข้อผิดพลาดจาก gateway | "ระบบขัดข้องชั่วคราว ลองอีกครั้ง" (ไม่ใช่ความผิดผู้ใช้) |
| `3ds_required` | ต้องยืนยัน OTP (ยังรอ) | รอ webhook อย่าปลดล็อกที่นั่ง |
| `fraud_suspected` | สงสัย fraud | บันทึก anomaly แจ้ง Admin |
| `cancelled` | ผู้ใช้/ระบบยกเลิก | ยกเลิกการจอง |
| `payment_expired` | หมดเวลา | เหมือน EXPIRED |

### Error Responses

| HTTP | `error` | สาเหตุ |
|------|---------|--------|
| `401` | `unauthorized` | API Key ผิดหรือไม่มี |
| `422` | `validation_error` | ข้อมูลที่ส่งไม่ถูกต้อง (ดู `details`) |
| `429` | `rate_limited` | ถูก rate limit (ดู `Retry-After` header) |
| `500` | `internal_error` | ข้อผิดพลาดภายใน gateway |
| `503` | `service_unavailable` | gateway อยู่ในโหมด `down` |

---

## 5. GET /v1/payments/{id} — ดูสถานะรายการ

ใช้สำหรับ **polling** หรือ **reconcile** เมื่อ webhook ไม่มา

### Request

```http
GET /v1/payments/01JCXYZ1234567890ABCDEFGH HTTP/1.1
Authorization: Bearer pm_test_key_concert_system_2024
```

### Response 200 OK

```json
{
  "id":           "01JCXYZ1234567890ABCDEFGH",
  "order_ref":    "b_TK2024001",
  "amount":       1200.00,
  "currency":     "THB",
  "method":       "card",
  "status":       "COMPLETED",
  "failure_code": null,
  "checkout_url": "http://localhost:8081/checkout/01JCXYZ1234567890ABCDEFGH",
  "return_url":   "http://localhost:8080/payment/return?booking=b_TK2024001",
  "expires_at":   "2026-10-13T10:10:00+07:00",
  "paid_at":      "2026-10-13T10:05:32+07:00",
  "created_at":   "2026-10-13T10:00:00+07:00"
}
```

> **หมายเหตุ:** Schema เหมือนกับ POST response ทุกประการ

### Error Responses

| HTTP | สาเหตุ |
|------|--------|
| `401` | API Key ผิด |
| `404` | ไม่พบ payment ID นี้ |
| `503` | gateway อยู่ในโหมด down |

---

## 6. POST /v1/payments/{id}/cancel — ยกเลิกรายการ

ยกเลิกได้เฉพาะรายการที่ยัง `PENDING` เท่านั้น

### Request

```http
POST /v1/payments/01JCXYZ1234567890ABCDEFGH/cancel HTTP/1.1
Authorization: Bearer pm_test_key_concert_system_2024
```

> ไม่ต้องส่ง Body

### Response 200 OK

```json
{
  "ok":           true,
  "status":       "FAILED",
  "failure_code": "cancelled"
}
```

### Error Responses

| HTTP | `error` | สาเหตุ |
|------|---------|--------|
| `401` | `unauthorized` | API Key ผิด |
| `404` | — | ไม่พบ payment ID |
| `409` | `cannot_cancel` | รายการ settle แล้ว (COMPLETED/FAILED/EXPIRED) ยกเลิกไม่ได้ |

---

## 7. Webhook — สิ่งที่ PayMock ส่งมาหาคุณ

เมื่อสถานะ payment เปลี่ยน PayMock จะ **POST** ไปที่ `callback_url` ที่คุณให้ไว้

### 7.1 Headers ที่ PayMock ส่งมา

```http
POST {callback_url} HTTP/1.1
Content-Type: application/json
X-Signature: t=1728810332,v1=a3f9c2b1d8e4f7a0b5c6d9e2f1a4b7c0d3e6f9a2b5c8d1e4f7a0b3c6d9e2f5
X-Event-Id: 01JCABC9876543210ZYXWVUTS
```

| Header | ความหมาย |
|--------|----------|
| `Content-Type` | `application/json` เสมอ |
| `X-Signature` | ลายเซ็น HMAC-SHA256 ใช้ยืนยันว่า webhook มาจาก PayMock จริง |
| `X-Event-Id` | ULID ไม่ซ้ำกันของแต่ละ delivery ใช้ป้องกัน duplicate |

### 7.2 Webhook Body

```json
{
  "id":           "01JCXYZ1234567890ABCDEFGH",
  "event":        "payment.completed",
  "order_ref":    "b_TK2024001",
  "amount":       1200.00,
  "currency":     "THB",
  "status":       "COMPLETED",
  "failure_code": null,
  "paid_at":      "2026-10-13T10:05:32+07:00"
}
```

### Webhook Body Fields

| Field | Type | ความหมาย |
|-------|------|----------|
| `id` | string (ULID) | Payment ID (ใช้ call `GET /v1/payments/{id}` เพื่อยืนยันได้) |
| `event` | string | ชื่อ event ดู [ตาราง events](#webhook-events) |
| `order_ref` | string | รหัสอ้างอิงจากระบบคอนเสิร์ต |
| `amount` | float | ยอดเงิน |
| `currency` | string | สกุลเงิน |
| `status` | string | สถานะใหม่: `COMPLETED`, `FAILED`, `EXPIRED` |
| `failure_code` | string\|null | รหัสเหตุผล (ถ้า FAILED) |
| `paid_at` | ISO 8601\|null | เวลาจ่ายจริง (มีเฉพาะ `COMPLETED`) |

### Webhook Events

| `event` | เมื่อไหร่ | `status` ใน body |
|---------|---------|-----------------|
| `payment.completed` | จ่ายสำเร็จ | `COMPLETED` |
| `payment.failed` | ถูกปฏิเสธ / ยกเลิก | `FAILED` |
| `payment.expired` | หมดเวลาก่อนจ่าย | `EXPIRED` |

### 7.3 การตรวจ Signature (สำคัญมาก!)

**ต้องทำก่อน process webhook ทุกครั้ง**

#### ขั้นตอน:

1. อ่าน `X-Signature` header แล้วแยก `t` และ `v1`:
   ```
   X-Signature: t=1728810332,v1=a3f9c2...
   → timestamp = 1728810332
   → signature = a3f9c2...
   ```

2. ตรวจว่า timestamp ไม่เก่าเกิน 5 นาที (300 วินาที):
   ```php
   if (abs(time() - $timestamp) > 300) {
       return response('Timestamp expired', 400);
   }
   ```

3. คำนวณ HMAC ด้วย **raw request body** (ก่อน decode JSON):
   ```php
   $secret    = 'whsec_concert_super_secret_2024';
   $rawBody   = $request->getContent();   // raw bytes
   $expected  = hash_hmac('sha256', "$timestamp.$rawBody", $secret);
   ```

4. เปรียบเทียบแบบ constant-time:
   ```php
   if (! hash_equals($expected, $signature)) {
       return response('Invalid signature', 401);
   }
   ```

#### ตัวอย่าง PHP ครบถ้วน:

```php
public function handleWebhook(Request $request): Response
{
    // 1. แยก signature header
    $sigHeader = $request->header('X-Signature', '');
    preg_match('/t=(\d+),v1=([a-f0-9]+)/', $sigHeader, $m);
    [$timestamp, $signature] = [$m[1] ?? 0, $m[2] ?? ''];

    // 2. ตรวจ timestamp (replay attack protection)
    if (abs(time() - $timestamp) > 300) {
        Log::warning('Webhook: timestamp expired');
        return response('Expired', 400);
    }

    // 3. คำนวณและตรวจ HMAC
    $secret   = config('services.paymock.webhook_secret');
    $rawBody  = $request->getContent();
    $expected = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);
    if (! hash_equals($expected, $signature)) {
        Log::warning('Webhook: invalid signature');
        return response('Unauthorized', 401);
    }

    // 4. ตรวจ idempotency ด้วย X-Event-Id
    $eventId = $request->header('X-Event-Id');
    if (WebhookLog::where('event_id', $eventId)->exists()) {
        return response('OK', 200); // รับแล้ว ไม่ต้อง process ซ้ำ
    }
    WebhookLog::create(['event_id' => $eventId]);

    // 5. process ตาม event
    $payload = $request->json()->all();
    match($payload['event']) {
        'payment.completed' => $this->handleCompleted($payload),
        'payment.failed'    => $this->handleFailed($payload),
        'payment.expired'   => $this->handleExpired($payload),
        default             => null,
    };

    // ต้องตอบ 2xx ไม่เช่นนั้น PayMock จะ retry
    return response('OK', 200);
}
```

### 7.4 Retry Policy ของ PayMock

PayMock จะ retry webhook ถ้าได้รับ response ที่ **ไม่ใช่ 2xx** หรือ timeout

| ครั้งที่ | หน่วง (วินาที) |
|---------|--------------|
| retry 1 | 5 |
| retry 2 | 30 |
| retry 3 | 120 |
| retry 4 | 300 |
| retry 5 | 600 |
| **รวมสูงสุด** | **6 ครั้ง** |

> **แนะนำ:** endpoint ของคุณควรตอบ `200 OK` เร็วที่สุด แล้วค่อย process ใน background

---

## 8. X-Mock-Scenario — ควบคุมพฤติกรรม

ส่ง header นี้พร้อม `POST /v1/payments` เพื่อจำลองสถานการณ์เฉพาะรายการนั้น

```
X-Mock-Scenario: webhook_delay:30
```

### 8.1 Scenarios ที่ใช้กับ POST /v1/payments

| ค่า | พฤติกรรม | ใช้ทดสอบ |
|-----|---------|---------|
| `normal` | ปกติ (override chaos ทั้งระบบ) | reset ค่า |
| `webhook_delay:N` | ส่ง webhook หลัง N วินาที | ทดสอบ polling reconcile |
| `webhook_lost` | ไม่ส่ง webhook เลย | ทดสอบว่าระบบ poll เอง |
| `webhook_dup:N` | ส่ง webhook N ชุด (duplicate) | ทดสอบ idempotency |
| `webhook_bad_sig` | ส่ง webhook ที่ signature ผิด | ทดสอบ signature validation |
| `webhook_replay` | ส่ง timestamp เก่า 1 ชั่วโมง | ทดสอบ replay protection |
| `auto_pay:N` | QR จ่ายอัตโนมัติใน N วินาที | เดโมโดยไม่กดปุ่ม |

### 8.2 Chaos Mode (ทั้งระบบ — ตั้งจาก Admin Panel)

ตั้งค่าได้ที่ `http://localhost:8081/admin`

| mode | พฤติกรรม | ระบบควรทำ |
|------|---------|----------|
| `normal` | ปกติ | — |
| `down` | ตอบ 503 ทุก request | แจ้ง "ระบบชำระเงินขัดข้อง" |
| `slow:N` | หน่วง N วินาที | แสดง loading ไม่กดซ้ำ |
| `timeout:N` | หน่วง N วินาที (เกิน timeout ของ client) | retry ด้วย Idempotency-Key เดิม |
| `error_rate:N` | สุ่ม 500 ใส่ N% | retry จำกัดครั้ง |
| `rate_limit` | ตอบ 429 + Retry-After: 30 | อ่าน Retry-After แล้ว backoff |
| `bad_auth` | ตอบ 401 | แจ้ง Admin (ไม่แจ้งผู้ใช้ด้วยข้อความเทคนิค) |
| `bad_json` | ตอบ HTML หรือ JSON พัง | ไม่ crash จับเป็น error |

> **ลำดับความสำคัญ:** `X-Mock-Scenario` header > เลขบัตรวิเศษ > Chaos Mode ทั้งระบบ

---

## 9. Error Response Format

Error response ทุกชนิดมี format เดียวกัน:

### 401 Unauthorized

```json
{
  "error": "unauthorized",
  "message": "Invalid or missing API key."
}
```

### 422 Validation Error

```json
{
  "error": "validation_error",
  "details": {
    "amount": ["The amount field is required."],
    "method": ["The selected method is invalid."]
  }
}
```

### 409 Conflict

```json
{
  "error": "cannot_cancel",
  "message": "Payment is already settled and cannot be cancelled."
}
```

### 429 Rate Limited

```http
HTTP/1.1 429 Too Many Requests
Retry-After: 30
Content-Type: application/json

{
  "error": "rate_limited",
  "message": "Too many requests. Retry after 30 seconds."
}
```

### 503 Service Unavailable

```json
{
  "error": "service_unavailable",
  "message": "Payment gateway is currently down (chaos mode: down)."
}
```

---

## 10. Magic Card Numbers

ใช้ที่หน้า checkout เมื่อเลือก method = `card`

| เลขบัตร | ผล | `failure_code` | ระบบควรทำ |
|---------|-----|---------------|----------|
| `4242 4242 4242 4242` | ✅ COMPLETED | `null` | ออกตั๋ว seat → SOLD |
| `4000 0000 0000 0002` | ❌ FAILED | `card_declined` | แจ้งผู้ใช้ ให้ลองบัตรอื่น |
| `4000 0000 0000 9995` | ❌ FAILED | `insufficient_funds` | แจ้งวงเงินไม่พอ |
| `4000 0000 0000 0069` | ❌ FAILED | `expired_card` | แจ้งบัตรหมดอายุ |
| `4000 0000 0000 0127` | ❌ FAILED | `incorrect_cvc` | แจ้ง CVC ผิด |
| `4000 0000 0000 0119` | ❌ FAILED | `processing_error` | แจ้งระบบขัดข้อง ลองอีกครั้ง |
| `4000 0000 0000 3220` | ⏳ รอ OTP | `3ds_required` | รอ webhook ห้ามปลดล็อกที่นั่ง |
| `4100 0000 0000 0019` | ⚠️ FAILED | `fraud_suspected` | บันทึก anomaly แจ้ง Admin |
| `4000 0000 0000 0341` | ✅ COMPLETED (webhook ช้า 11 นาที) | `null` | ห้าม expire booking ก่อน poll ยืนยัน |

> CVC และวันหมดอายุ: ใส่อะไรก็ได้ (ยกเว้นบัตร `...0069` ที่ตรวจจาก expiry จริง) — การตัดสินขึ้นอยู่กับเลขบัตรเท่านั้น

---

## 11. Flow ทั้งหมด (Step-by-Step)

### Flow ปกติ (Happy Path)

```
ระบบคอนเสิร์ต                          PayMock
     │                                     │
     │  1. POST /v1/payments               │
     │     order_ref, amount, method       │
     │     callback_url, Idempotency-Key   │
     │ ─────────────────────────────────► │
     │                                     │  สร้าง payment PENDING
     │  ◄───────────────────────────────── │
     │  201: {id, checkout_url, ...}       │
     │                                     │
     │  2. redirect ผู้ใช้ไป checkout_url  │
     │ ─────────────────────────────────► │
     │                                     │  ผู้ใช้จ่ายเงิน
     │                                     │  status → COMPLETED
     │  3. POST {callback_url} (Webhook)   │
     │  ◄───────────────────────────────── │
     │  {event: payment.completed, ...}    │
     │                                     │
     │  4. ตรวจ signature + idempotency   │
     │     อัปเดต booking → CONFIRMED      │
     │     seat → SOLD, ออกตั๋ว            │
     │     ตอบ 200 OK                      │
     │ ─────────────────────────────────► │
```

### Flow Reconcile (Webhook หาย)

```
ระบบคอนเสิร์ต (Scheduler ทุก 5 นาที)
     │
     │  GET /v1/payments/{id}
     │  ตรวจสอบสถานะ payment ที่ยัง PENDING มากกว่า expires_at
     │
     │  ถ้า status = COMPLETED → process เหมือนได้ webhook
     │  ถ้า status = EXPIRED   → ยกเลิก booking ปลดล็อกที่นั่ง
```

### Flow Retry หลัง Timeout

```
1. ส่ง POST /v1/payments พร้อม Idempotency-Key: b_TK2024001
2. หมดเวลา (timeout) ไม่ได้ response
3. ส่ง POST /v1/payments อีกครั้ง พร้อม Idempotency-Key เดิม: b_TK2024001
4. PayMock คืน payment เดิม (ไม่สร้างซ้ำ)
5. ใช้ checkout_url ที่ได้ redirect ผู้ใช้ต่อไป
```

---

## ข้อมูลสำหรับทดสอบ

| รายการ | ค่า |
|--------|-----|
| Base URL | `http://localhost:8081` |
| API Key | `pm_test_key_concert_system_2024` |
| Webhook Secret | `whsec_concert_super_secret_2024` |
| Admin Panel | `http://localhost:8081/admin` |
| Admin Password | `admin1234` |
| Postman Collection | `http://localhost:8081/paymock.postman_collection.json` |

---

*PayMock v1.0 — Mock Payment Gateway สำหรับระบบคอนเสิร์ต*
