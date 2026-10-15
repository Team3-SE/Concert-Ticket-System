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
      "method": "promptpay",
      "status": "COMPLETED",
      "failure_code": null,
      "qrcode_url": "http://localhost:8081/v1/payments/01JCXYZ.../qr.svg",
      "bank_simulator_url": "http://localhost:8081/bank-simulate/01JCXYZ...",
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

### Request (PromptPay)

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
  "method":       "promptpay",
  "callback_url": "http://localhost:8080/webhooks/paymock",
  "expires_in":   600,
  "currency":     "THB",
  "metadata": {
    "booking_id": 42,
    "user_id": 7
  }
}
```

### Request (Credit Card - Direct API)

```json
{
  "order_ref":    "b_TK2024002",
  "amount":       1200.00,
  "method":       "card",
  "callback_url": "http://localhost:8080/webhooks/paymock",
  "card_number":  "4242424242424242",
  "exp_month":    "12",
  "exp_year":     "28",
  "cvc":          "123"
}
```

### Request Body Fields

| Field | Type | Required | ค่าที่ยอมรับ | Default | คำอธิบาย |
|-------|------|----------|-------------|---------|----------|
| `order_ref` | string | ✅ | max 255 chars | — | รหัสอ้างอิงจากระบบคอนเสิร์ต (booking code) |
| `amount` | number | ✅ | min 1 | — | ยอดเงิน (บาท, ทศนิยมได้) |
| `method` | string | ✅ | `"card"` หรือ `"promptpay"` | — | วิธีชำระ |
| `callback_url` | string | ✅ | URL ที่ถูกต้อง | — | URL ที่ PayMock จะ POST Webhook มาหา |
| `expires_in` | integer | ❌ | min 60 (วินาที) | 600 | อายุรายการก่อนหมดเวลา |
| `currency` | string | ❌ | 3 ตัวอักษร | `"THB"` | สกุลเงิน (default: `"THB"`) |
| `card_number` | string | ❌ | 16 หลัก | `null` | หมายเลขบัตรเครดิต (สำหรับ Direct Card Payment) |
| `exp_month` | string | ❌ | 2 หลัก (01-12) | `null` | เดือนหมดอายุบัตร |
| `exp_year` | string | ❌ | 2 หรือ 4 หลัก | `null` | ปีหมดอายุบัตร |
| `cvc` | string | ❌ | 3-4 หลัก | `null` | รหัสตรวจสอบบัตร |
| `metadata` | object | ❌ | JSON object ใดก็ได้ | `null` | ข้อมูลเพิ่มเติม (ไม่ส่งใน webhook) |

### Idempotency

- ถ้าส่ง `Idempotency-Key` header → ใช้ค่านั้น
- ถ้าไม่ส่ง → ใช้ `order_ref` เป็น key อัตโนมัติ
- ถ้าส่ง key เดิมซ้ำ → **คืน payment เดิม** (ไม่สร้างใหม่) ใช้เมื่อ retry หลัง timeout

### Response 201 Created (PromptPay)

```json
{
  "id":                 "01JCXYZ1234567890ABCDEFGH",
  "order_ref":          "b_TK2024001",
  "amount":             1200.00,
  "currency":           "THB",
  "method":             "promptpay",
  "status":             "PENDING",
  "failure_code":       null,
  "expires_at":         "2026-10-13T10:10:00+07:00",
  "paid_at":            null,
  "created_at":         "2026-10-13T10:00:00+07:00",
  "qrcode_url":         "http://localhost:8081/v1/payments/01JCXYZ1234567890ABCDEFGH/qr.svg",
  "bank_simulator_url": "http://localhost:8081/bank-simulate/01JCXYZ1234567890ABCDEFGH"
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
| `qrcode_url` | string\|null | **URL รูปภาพ SVG QR Code** สำหรับ PromptPay (นำไปใส่แท็ก `<img src="...">` บนเว็บหรือ Native Modal) |
| `bank_simulator_url` | string\|null | **URL หน้าจำลอง Mobile Banking** สำหรับคลิกทดสอบยืนยันชำระเงิน |
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
  "id":                 "01JCXYZ1234567890ABCDEFGH",
  "order_ref":          "b_TK2024001",
  "amount":             1200.00,
  "currency":           "THB",
  "method":             "promptpay",
  "status":             "COMPLETED",
  "failure_code":       null,
  "qrcode_url":         "http://localhost:8081/v1/payments/01JCXYZ1234567890ABCDEFGH/qr.svg",
  "bank_simulator_url": "http://localhost:8081/bank-simulate/01JCXYZ1234567890ABCDEFGH",
  "expires_at":         "2026-10-13T10:10:00+07:00",
  "paid_at":            "2026-10-13T10:05:32+07:00",
  "created_at":         "2026-10-13T10:00:00+07:00"
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

### 9.1 ปรัชญาการออกแบบ HTTP Status Code (ทำไมแต่ละ API ถึงต่างกัน)

ตามมาตรฐาน RESTful API และ RFC 9110 HTTP Status Code ทำหน้าที่บอก Client (ระบบคอนเสิร์ต) อย่างแม่นยำว่า **"ปัญหาเกิดขึ้นจากใคร และควรแก้ไขหรือจัดการต่อไปอย่างไร"** โดยแบ่งเป็น 2 กลุ่มใหญ่:

| กลุ่มรหัส | ความหมายหลัก | การจัดการของระบบคอนเสิร์ต |
|---|---|---|
| **4xx (Client Error)** | ปัญหาเกิดจากฝั่งผู้เรียก (ระบบคอนเสิร์ต) | ห้าม Retry ซ้ำด้วยข้อมูลเดิม ต้องแก้ไขโค้ดหรือพารามิเตอร์ |
| **5xx (Server Error)** | ปัญหาเกิดจากฝั่งผู้ให้บริการ (Gateway) | อนุญาตให้ Retry ได้อย่างจำกัดครั้ง (เช่น 1-3 ครั้ง) |

### 9.2 ตาราง Error Code ราย Endpoint และวิธีรับมือ

| Endpoint | HTTP Code | Error String | สาเหตุแท้จริง | สิ่งที่ระบบคอนเสิร์ตต้องทำ |
|---|---|---|---|---|
| `POST /v1/payments` | 401 | `unauthorized` | API Key ผิดหรือไม่มี | เช็ค config `.env` ห้าม Retry ซ้ำ |
| `POST /v1/payments` | 422 | `validation_error` | ข้อมูลไม่ครบ เช่น amount <= 0 | แก้ไข request body ก่อนส่งใหม่ |
| `POST /v1/payments` | 429 | `rate_limited` | ยิง API ถี่เกินกำหนด | อ่าน Header `Retry-After` แล้วหน่วงเวลา |
| `POST /v1/payments` | 503 | `service_unavailable` | Gateway อยู่ในโหมด down | แจ้งผู้ใช้ระบบขัดข้องชั่วคราว |
| `GET /v1/payments/{id}` | 404 | `not_found` | ไม่พบ Payment ID นี้ | ตรวจสอบ ID ว่าส่งมาถูกต้องหรือไม่ |
| `POST /v1/payments/{id}/cancel` | 409 | `cannot_cancel` | รายการจ่ายสำเร็จไปแล้ว | ห้ามยกเลิก ปล่อยผ่านเป็น COMPLETED |
| `Webhook (POST callback)` | 400 | `expired` | Timestamp เก่าเกิน 5 นาที | ระวัง Replay Attack ปฏิเสธทันที |
| `Webhook (POST callback)` | 401 | `unauthorized` | HMAC Signature ไม่ตรง | ข้อมูลถูกดัดแปลง ปฏิเสธทันที |

### 9.3 Hierarchy ลำดับชั้นการตรวจสอบในระบบ PayMock

1. **Layer 1 (ด่าน Network & Chaos):** ตรวจสอบ Header `X-Mock-Scenario` และ Admin Setting ก่อนเพื่อน (ถ้าเจอ `down` หรือ `rate_limit` จะตัดจบ 503/429 ทันที)
2. **Layer 2 (ด่าน Request & Idempotency):** ตรวจสอบ Body และตรวจ `Idempotency-Key` (ถ้าซ้ำ คืนรายการเดิมทันที)
3. **Layer 3 (ด่านการชำระเงิน):** ในหน้า Checkout ตรวจสอบตามตาราง Magic Cards (เช่น บัตรผ่าน, บัตรวงเงินไม่พอ, บัตรหมดอายุ)
4. **Layer 4 (ด่านการส่ง Webhook):** เมื่อสถานะเปลี่ยน ส่ง Webhook ตาม Scenario (เช่น ดีเลย์ หรือส่งซ้ำ) พร้อม HMAC-SHA256

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
     │     order_ref, amount, method,      │
     │     callback_url, Idempotency-Key   │
     │ ─────────────────────────────────► │
     │                                     │  สร้าง payment (PENDING หรือประมวลผลบัตร)
     │  ◄───────────────────────────────── │
     │  201: {id, qrcode_url, status...}   │
     │                                     │
     │  2. แสดงผลบนเว็บ/Modal คอนเสิร์ต    │
     │     - PromptPay: แสดง qrcode_url    │
     │     - Card: แจ้งผลบัตรทันที         │
     │                                     │
     │  3. ผู้ใช้สแกน QR หรือกดจ่ายในแอป    │
     │     สถานะ → COMPLETED               │
     │                                     │
     │  4. POST {callback_url} (Webhook)   │
     │  ◄───────────────────────────────── │
     │  {event: payment.completed, ...}    │
     │                                     │
     │  5. ตรวจ signature + idempotency   │
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
5. นำ qrcode_url หรือผลลัพธ์เดิมไปแสดงผลต่อได้ทันที
```

---

## 12. การเชื่อมต่อแบบ Native Modal & Direct Payment (Headless Flow)

ระบบออกแบบเป็น **Direct API / Headless Flow** โดยสมบูรณ์ ไม่จำเป็นต้องมี `checkout_url` หรือ `return_url`:
- **PromptPay**: PayMock ส่งคืน `qrcode_url` (ภาพ SVG) และ `bank_simulator_url` (หน้าจำลองแอปมือถือ) เพื่อให้เว็บคอนเสิร์ตนำรูป QR Code ไปใส่ใน Native Modal ของตัวเองได้ทันที
- **Credit Card**: เว็บคอนเสิร์ตรับข้อมูลบัตรจากผู้ใช้ แล้วส่งไปยัง `POST /v1/payments` เพื่อประมวลผลและทราบผลทันที (`COMPLETED` หรือ `FAILED`)

### 12.1 ข้อดีของ Headless Direct Flow
1. **Seamless User Experience**: ผู้ใช้อยู่บนเว็บไซต์คอนเสิร์ตตลอดเวลา ไม่ถูก Redirect ข้ามโดเมน
2. **ไม่ใช้ iframe**: ปลอดภัยกว่า ไร้ปัญหา iframe sandbox / cross-origin cookie / CSP restrictions
3. **การอัปเดตสถานะ**: เว็บคอนเสิร์ตใช้ Polling ตรวจสอบสถานะตั๋วจาก Database ตัวเอง ควบคู่ไปกับการรอรับ Webhook จาก PayMock

### 12.2 ตัวอย่างโค้ด HTML + JavaScript ฝั่งเว็บคอนเสิร์ต (PromptPay Modal)

```html
<!-- 1. Native Modal Container บนหน้าเว็บคอนเสิร์ต -->
<div id="paymentModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:white; border-radius:24px; overflow:hidden; width:95%; max-width:440px; text-align:center; padding:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="margin:0; font-weight:bold; color:#1e293b;">📱 สแกน QR เพื่อชำระเงิน</h3>
            <button onclick="closeModal()" style="border:none; background:none; font-size:18px; cursor:pointer;">✕</button>
        </div>

        <!-- แสดงภาพ QR Code จาก qrcode_url -->
        <div style="padding:16px; border:1px dashed #cbd5e1; border-radius:16px; margin-bottom:16px;">
            <img id="qrImage" src="" alt="PromptPay QR Code" style="width:240px; height:240px; margin:0 auto; display:block;">
        </div>

        <!-- ปุ่มจำลองเปิด Mobile Banking -->
        <a id="bankSimulateBtn" href="#" target="_blank" style="display:block; padding:12px; background:#059669; color:white; border-radius:12px; text-decoration:none; font-weight:bold; margin-bottom:8px;">
            📲 เปิดหน้าจำลอง Mobile Banking (โอนเงิน) ↗
        </a>
        <p style="font-size:12px; color:#64748b; margin:0;">
            เมื่อโอนเงินแล้ว PayMock จะยิง Webhook แจ้งระบบคอนเสิร์ต และ Modal จะปิดอัตโนมัติ
        </p>
    </div>
</div>

<!-- 2. JavaScript จัดการเปิด-ปิด และ Polling -->
<script>
let pollInterval = null;

function openPromptPayModal(qrUrl, simulateUrl, orderRef) {
    document.getElementById('qrImage').src = qrUrl;
    document.getElementById('bankSimulateBtn').href = simulateUrl;
    document.getElementById('paymentModal').style.display = 'flex';

    // เริ่ม Polling เช็คสถานะจาก Backend ของเว็บคอนเสิร์ต
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(async () => {
        const res = await fetch('/api/booking-status?order_ref=' + orderRef);
        const data = await res.json();
        if (data.booking && data.booking.status === 'PAID') {
            clearInterval(pollInterval);
            closeModal();
            alert('🎉 ชำระเงินสำเร็จ! ออกตั๋วคอนเสิร์ตเรียบร้อย');
            window.location.reload();
        }
    }, 1500);
}

function closeModal() {
    document.getElementById('paymentModal').style.display = 'none';
    if (pollInterval) clearInterval(pollInterval);
}
</script>
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
