# PayMock API Reference
> เอกสารสำหรับนักพัฒนาระบบคอนเสิร์ต (ฝั่งต้นทาง) ที่ต้องการเชื่อมต่อกับ PayMock Payment Gateway

**Base URL:** `http://localhost:8081`  
**API Prefix:** ไม่มี (เรียกผ่าน /payments ได้โดยตรง)
**Content-Type:** `application/json` สำหรับ JSON requests/responses; QR response ใช้ `image/svg+xml`

---

## สารบัญ

1. [Authentication](#1-authentication)
2. [Headers ที่ใช้บ่อย](#2-headers-ที่ใช้บ่อย)
3. [GET /payments — ตรวจสถานะ API](#3-get-payments--ตรวจสถานะ-api)
4. [POST /payments — สร้างรายการจ่ายเงิน](#4-post-payments--สร้างรายการจ่ายเงิน)
5. [GET /payments/{id} — ดูสถานะรายการ](#5-get-paymentsid--ดูสถานะรายการ)
6. [POST /payments/{id}/cancel — ยกเลิกรายการ](#6-post-paymentsidcancel--ยกเลิกรายการ)
6.1 [GET /payments/{id}/qr.svg — ดู QR Code](#qr-code-route)
7. [Webhook — สิ่งที่ PayMock ส่งมาหาคุณ](#7-webhook--สิ่งที่-paymock-ส่งมาหาคุณ)
8. [Chaos และ Webhook Mode (PayMock Admin)](#8-chaos-และ-webhook-mode-paymock-admin)
9. [Error Response Format](#9-error-response-format)
10. [Magic Card Numbers](#10-magic-card-numbers)
11. [Flow ทั้งหมด (Step-by-Step)](#11-flow-ทั้งหมด-step-by-step)

---

## 1. Authentication

ทุก protected API request ต้องส่ง `Authorization` header ยกเว้น public QR endpoint `GET /payments/{id}/qr.svg`:

```
Authorization: Bearer pm_test_key_concert_system_2024
```

| สถานการณ์ | Response |
|-----------|----------|
| ไม่มี header / key ผิด | `401 Unauthorized` |

---

## 2. Headers ที่ใช้บ่อย

| Header | Required | ค่า | หมายเหตุ |
|--------|----------|-----|----------|
| `Authorization` | Required for protected routes | `******` | Excluded for public QR endpoint |
| `Accept` | แนะนำ | `application/json` | QR endpoint ใช้ `image/svg+xml` |
| `Content-Type` | ✅ POST ที่มี JSON body | `application/json` | cancel ไม่มี request body |
| `Idempotency-Key` | ไม่บังคับ | เช่น `attempt-12` | ใช้ deduplicate เมื่อส่ง key เดิมซ้ำ; หากไม่ส่งแต่ละครั้งจะเป็น request ใหม่ |

`POST /payments` ไม่ใช้หรือจัดเก็บ merchant reference fields เช่น `payment_id`, `booking_ref` หรือ `order_ref`; fields เหล่านี้ไม่อยู่ใน API contract. `metadata` เป็น optional JSON value สำหรับข้อมูลเพิ่มเติม; Gateway ไม่ตรวจหรืออ่านค่าใดภายใน. `Idempotency-Key` เป็น optional และเป็นวิธีเดียวสำหรับ idempotency; หากไม่ส่ง แต่ละ request จะสร้างรายการใหม่.

---

## 3. GET /payments — ตรวจสถานะ API

ใช้เช็คว่า PayMock ออนไลน์อยู่ก่อนสร้างรายการ

### Request

```http
GET /payments HTTP/1.1
Accept: application/json
Authorization: Bearer pm_test_key_concert_system_2024
```

### Response 200 OK

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

```json
{
  "service": "PayMock Gateway API",
  "status": "online",
  "chaos_mode": "normal",
  "webhook_mode": "normal",
  "recent": [
    {
      "id": "txn_01JCXYZ...",
      "metadata": {
        "source": "concert-app",
        "context": {"campaign": "summer"}
      },
      "amount": 1200.00,
      "currency": "THB",
      "method": "promptpay",
      "status": "COMPLETED",
      "failure_code": null,
      "qrcode_url": "http://localhost:8081/payments/01JCXYZ.../qr.svg",
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
| `chaos_mode` | string | ชั้น 1: `normal`, `down`, `slow:3`, ฯลฯ (ตั้งที่ Admin) |
| `webhook_mode` | string | ชั้น 2: `normal`, `webhook_lost`, ฯลฯ (ตั้งที่ Admin) |
| `recent` | array | 10 รายการล่าสุด (ดู schema ใน [หัวข้อ 4](#response-201-created)) |

---

## 4. POST /payments — สร้างรายการจ่ายเงิน

### Request (PromptPay)

`metadata` เป็น optional JSON value สำหรับข้อมูลเพิ่มเติม; Gateway จะไม่ตรวจชนิดหรือค่าใดภายใน และจะส่งค่ากลับใน response/webhook.

```http
POST /payments HTTP/1.1
Authorization: Bearer pm_test_key_concert_system_2024
Content-Type: application/json
Accept: application/json
Idempotency-Key: attempt-12
```

```json
{
  "amount":       1200.00,
  "method":       "promptpay",
  "callback_url": "http://localhost:8080/webhooks/paymock",
  "expires_in":   600,
  "currency":     "THB",
  "metadata": {
    "source": "concert-app",
    "context": {
      "campaign": "summer"
    }
  }
}
```

### Request (Credit Card - Hosted Checkout)

```json
{
  "amount":       1200.00,
  "method":       "card",
  "callback_url": "http://localhost:8080/webhooks/paymock",
  "return_url":   "http://localhost:5000/booking/TK-260101-ABCDE",
  "expires_in":   600,
  "metadata": {
    "source": "concert-app",
    "context": {
      "campaign": "summer"
    }
  }
}
```

### Request Body Fields

| Field | Type | Required | ค่าที่ยอมรับ | Default | คำอธิบาย |
|-------|------|----------|-------------|---------|----------|
| `amount` | number | ✅ | min 1 | — | ยอดเงิน (บาท, ทศนิยมได้) |
| `method` | string | ✅ | `"card"` หรือ `"promptpay"` | — | วิธีชำระ |
| `callback_url` | string | ✅ | URL ที่ถูกต้อง | — | URL ที่ PayMock จะ POST Webhook มาหา |
| `return_url` | string | ❌ | URL ที่ถูกต้อง | `null` | หน้าที่ Gateway redirect กลับหลัง submit บัตร |
| `expires_in` | integer | ❌ | min 60 (วินาที) | 600 | อายุรายการก่อนหมดเวลา ใช้ได้ทั้งบัตรและ PromptPay |
| `currency` | string | ❌ | 3 ตัวอักษร | `"THB"` | สกุลเงิน (default: `"THB"`) |
| `metadata` | JSON value | ❌ | JSON value ใดก็ได้ | ไม่ส่ง | ข้อมูลเพิ่มเติม; Gateway ไม่ตรวจ metadata และ response/webhook ส่งค่ากลับตามที่รับมา |

Gateway ตรวจรูปแบบของ fields ปกติใน request แต่ไม่ตรวจหรืออ่านค่าใดภายใน `metadata`; nested values และ custom keys จะถูกส่งกลับตามเดิมใน response/webhook.

### Idempotency

- ระบบใช้ `Idempotency-Key` header เท่านั้น; หากไม่ส่ง ระบบสร้าง key ภายในใหม่ทุก request
- `Idempotency-Key` ใช้ภายใน gateway เท่านั้น; จะไม่ถูกส่งกลับใน API response หรือ webhook body
- หากไม่ส่ง header การ retry POST จะสร้าง payment ใหม่
- ส่ง `Idempotency-Key` เดิมทุกครั้งที่ retry attempt เดิม; ใช้ key ใหม่เมื่อต้องการสร้าง payment attempt ใหม่
- หลังสร้างสำเร็จ ให้ใช้ response `id` ที่ขึ้นต้นด้วย `txn_` เป็น provider transaction ID สำหรับ GET, cancel, polling/reconciliation และจับคู่/deduplicate webhook
- ไม่มีการอ่าน `idempotency_key` จาก JSON body และไม่มี fallback จาก metadata
- บัตรถูกปฏิเสธ/PromptPay หมดอายุแล้วต้องการจ่ายใหม่ ให้สร้าง Concert payment ใหม่ใต้ booking เดิม

#### Concert-PayMock flow

Concert ส่ง `metadata` เป็นข้อมูลเพิ่มเติมใด ๆ ได้โดย Gateway ไม่อ่านหรือ validate keys/values ภายใน; `Idempotency-Key` เป็น optional. หากไม่ส่ง header แต่ละ POST จะสร้าง payment ใหม่ ดังนั้นการ retry attempt เดิมต้องส่ง header เดิมทุกครั้ง.

**ข้อจำกัดที่มีอยู่จริง:** Concert ตั้ง cURL timeout 10 วินาที. หาก PayMock สร้างรายการแล้วแต่ Concert ไม่ได้รับ response, ให้ retry attempt เดิมด้วย `Idempotency-Key` เดิม; Concert ยังไม่มี route หรือ worker ที่ resend แถวเดิมอัตโนมัติ และบันทึก attempt นั้นเป็น `GATEWAY_ERROR`. จึงห้ามสร้าง Concert payment ใหม่เพียงเพราะ timeout; ต้อง retry/reconcile attempt เดิมด้วย key เดิมก่อน และสร้าง payment ใหม่เมื่อยืนยันว่า `FAILED` หรือ `EXPIRED` เท่านั้น.

### ต้องส่ง ID ตัวไหนในแต่ละ API?

| API / จุดที่ส่งค่า | ชื่อ field / path | ค่าที่ต้องใช้ |
|---|---|---|
| `GET /payments` | ไม่มี | ไม่ต้องส่ง ID |
| `POST /payments` | `body.metadata` | Optional JSON value; Gateway ไม่ตรวจ metadata |
| `POST /payments` | `Idempotency-Key` | Optional; ใช้ deduplicate เมื่อส่ง key เดิมซ้ำ |
| `GET /payments/{id}` | path `{id}` | ส่ง `id` ของรายการที่ต้องการดู |
| `POST /payments/{id}/cancel` | path `{id}` | ส่ง `id` ของรายการที่ต้องการยกเลิก |
| `GET /payments/{id}/qr.svg` | path `{id}` | ส่ง `id` ของรายการที่ต้องการแสดง QR |
| `GET /checkout/{id}`, `POST /payments/{id}` | path `{id}` | ส่ง `id` ของรายการที่จะเปิด checkout หรือชำระด้วยบัตร |
| `/checkout/{id}/...`, `/bank-simulate/{id}/...` | path `{id}` | ส่ง `id` ของรายการที่ต้องการดำเนินการ |
| Webhook จาก PayMock | `body.id`, `body.metadata` (ถ้ามี) | ใช้ `body.id` (`txn_...`) เป็น provider transaction ID; metadata เป็นข้อมูลเพิ่มเติมจาก request |
| ตัวอย่าง Concert: `/api/booking-status`, `/api/cancel-booking`, `/api/reconcile-booking` | query/body `payment_id` | ใช้ชื่อ field `payment_id` |

### Response 201 Created (PromptPay)

Response จะมี URL เฉพาะช่องทางที่เลือก: PromptPay คืน `qrcode_url` และ `bank_simulator_url` โดยไม่คืน `checkout_url`; บัตรคืน `checkout_url` โดยไม่คืน `qrcode_url` หรือ `bank_simulator_url`. `metadata` เป็น optional และจะไม่ปรากฏใน response หากไม่ได้ส่ง metadata.

```http
HTTP/1.1 201 Created
Content-Type: application/json
```

```json
{
  "id":                 "txn_01JCXYZ1234567890ABCDEFGH",
  "amount":             1200.00,
  "currency":           "THB",
  "method":             "promptpay",
  "status":             "PENDING",
  "failure_code":       null,
  "expires_at":         "2026-10-13T10:10:00+07:00",
  "paid_at":            null,
  "created_at":         "2026-10-13T10:00:00+07:00",
  "qrcode_url":         "http://localhost:8081/payments/01JCXYZ1234567890ABCDEFGH/qr.svg",
  "bank_simulator_url": "http://localhost:8081/bank-simulate/txn_01JCXYZ1234567890ABCDEFGH",
  "metadata": {
    "source": "concert-app",
    "context": {
      "campaign": "summer"
    }
  }
}
```

### Response Fields

| Field | Type | ความหมาย |
|-------|------|----------|
| `id` | string (`txn_...`) | Provider transaction ID; ใช้ใน path ของ GET, cancel, QR และ checkout รวมถึงจับคู่/deduplicate webhook |
| `metadata` | JSON value | Optional; metadata จาก request โดย Gateway ไม่ตรวจหรือแก้ไข |
| `amount` | float | ยอดเงิน |
| `currency` | string | สกุลเงิน |
| `method` | string | `card` หรือ `promptpay` |
| `status` | string | สถานะปัจจุบัน ดู [ตารางสถานะ](#สถานะ-payment) |
| `failure_code` | string\|null | รหัสเหตุผลเมื่อล้มเหลว ดู [ตารางด้านล่าง](#failure-codes) |
| `checkout_url` | string | URL Hosted Checkout; ส่งคืนเฉพาะเมื่อ `method` เป็น `card` |
| `qrcode_url` | string | **URL รูปภาพ SVG QR Code** สำหรับ PromptPay (นำไปใส่แท็ก `<img src="...">` บนเว็บหรือ Native Modal); ส่งคืนเฉพาะเมื่อ `method` เป็น `promptpay` |
| `bank_simulator_url` | string | **URL หน้าจำลอง Mobile Banking** สำหรับคลิกทดสอบยืนยันชำระเงิน; ส่งคืนเฉพาะเมื่อ `method` เป็น `promptpay` |
| `expires_at` | ISO 8601\|null | เวลาหมดอายุ = `created_at` + `expires_in` (นาฬิกาเซิร์ฟเวอร์) |
| `paid_at` | ISO 8601\|null | เวลาจ่ายสำเร็จจริง (เฉพาะ `COMPLETED`) — **ไม่มีฟิลด์ `completed_at`** |
| `created_at` | ISO 8601 | เวลาสร้างรายการจริง (`Asia/Bangkok`) |

ตัวอย่าง Response 201 สำหรับบัตร (ไม่มี `qrcode_url` และ `bank_simulator_url`):

```http
HTTP/1.1 201 Created
Content-Type: application/json
```

```json
{
  "id": "txn_01JCXYZ1234567890ABCDEFGH",
  "amount": 1200.00,
  "currency": "THB",
  "method": "card",
  "status": "PENDING",
  "failure_code": null,
  "expires_at": "2026-10-13T10:10:00+07:00",
  "paid_at": null,
  "created_at": "2026-10-13T10:00:00+07:00",
  "checkout_url": "http://localhost:8081/checkout/txn_01JCXYZ1234567890ABCDEFGH",
  "return_url": "http://localhost:5000/booking/TK-260101-ABCDE",
  "metadata": {
    "source": "concert-app",
    "context": {"campaign": "summer"}
  }
}
```

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
| `429` | `too_many_requests` | Chaos mode = `rate_limit` (ดู `Retry-After` header) |
| `500` | `internal_error` | ข้อผิดพลาดภายใน gateway |
| `503` | `service_unavailable` | gateway อยู่ในโหมด `down` |

---

## 5. GET /payments/{id} — ดูสถานะรายการ

ใช้สำหรับ **polling** หรือ **reconcile** เมื่อ webhook ไม่มา

### Request

```http
GET /payments/01JCXYZ1234567890ABCDEFGH HTTP/1.1
Accept: application/json
Authorization: Bearer pm_test_key_concert_system_2024
```

### Response 200 OK

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

```json
{
  "id":                 "txn_01JCXYZ1234567890ABCDEFGH",
  "amount":             1200.00,
  "currency":           "THB",
  "method":             "promptpay",
  "status":             "COMPLETED",
  "failure_code":       null,
  "qrcode_url":         "http://localhost:8081/payments/01JCXYZ1234567890ABCDEFGH/qr.svg",
  "bank_simulator_url": "http://localhost:8081/bank-simulate/01JCXYZ1234567890ABCDEFGH",
  "expires_at":         "2026-10-13T10:10:00+07:00",
  "paid_at":            "2026-10-13T10:05:32+07:00",
  "created_at":         "2026-10-13T10:00:00+07:00",
  "metadata": {
    "source": "concert-app",
    "context": {
      "campaign": "summer"
    }
  }
}
```

> **หมายเหตุ:** Schema เหมือนกับ POST response ทุกประการ โดย `metadata` เป็น optional และอาจไม่มีใน response

### Error Responses

| HTTP | สาเหตุ |
|------|--------|
| `401` | API Key ผิด |
| `404` | ไม่พบ payment ID นี้ |
| `503` | gateway อยู่ในโหมด down |

---

## 6. POST /payments/{id}/cancel — ยกเลิกรายการ

ยกเลิกได้เฉพาะรายการที่ยัง `PENDING` เท่านั้น

### Request

```http
POST /payments/01JCXYZ1234567890ABCDEFGH/cancel HTTP/1.1
Accept: application/json
Authorization: Bearer pm_test_key_concert_system_2024
```

> ไม่ต้องส่ง Body

### Response 200 OK

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

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

<a id="qr-code-route"></a>
### GET /payments/{id}/qr.svg — ดู QR Code

Route นี้เป็น public เพื่อให้หน้าเว็บโหลด URL ใน `<img>` ได้โดยตรง ไม่ต้องส่ง `Authorization`.

#### Request headers

```http
GET /payments/txn_01JCXYZ1234567890ABCDEFGH/qr.svg HTTP/1.1
Accept: image/svg+xml
```

#### Response headers

```http
HTTP/1.1 200 OK
Content-Type: image/svg+xml
Cache-Control: must-revalidate, no-cache, no-store, private
```

Response body เป็น SVG; หากไม่พบรายการจะได้ `404 Not Found`.

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

### 7.2 Response จาก Webhook Receiver

Receiver ควรตอบ `2xx` เมื่อรับ webhook แล้ว; PayMock ไม่ใช้ response body เพื่อตีความสถานะ payment.

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

```json
{ "ok": true }
```

### 7.3 Webhook Body

```json
{
  "id":           "txn_01JCXYZ1234567890ABCDEFGH",
  "event":        "payment.completed",
  "amount":       1200.00,
  "currency":     "THB",
  "method":       "promptpay",
  "status":       "COMPLETED",
  "failure_code": null,
  "paid_at":      "2026-10-13T10:05:32+07:00",
  "created_at":   "2026-10-13T10:00:00+07:00",
  "metadata": {
    "source": "concert-app",
    "context": {
      "campaign": "summer"
    }
  }
}
```

### Webhook Body Fields

| Field | Type | ความหมาย |
|-------|------|----------|
| `id` | string (`txn_...`) | Provider transaction ID; ใช้เรียก `GET /payments/{id}` และเป็น key สำหรับจับคู่/deduplicate webhook |
| `event` | string | ชื่อ event ดู [ตาราง events](#webhook-events) |
| `metadata` | JSON value | Optional; metadata จาก request โดย Gateway ไม่ตรวจหรือแก้ไข |
| `amount` | float | ยอดเงินที่ชำระ |
| `currency` | string | สกุลเงิน (เช่น `THB`) |
| `method` | string | ช่องทางชำระเงิน: `promptpay` หรือ `card` |
| `status` | string | สถานะใหม่: `COMPLETED`, `FAILED`, `EXPIRED` |
| `failure_code` | string\|null | รหัสเหตุผล (มีเฉพาะกรณี `FAILED`) |
| `paid_at` | ISO 8601\|null | เวลาจ่ายจริง (มีเฉพาะ `COMPLETED`) |
| `created_at` | ISO 8601 | เวลาที่สร้างรายการชำระเงิน |

### Webhook Events

| `event` | เมื่อไหร่ | `status` ใน body |
|---------|---------|-----------------|
| `payment.completed` | จ่ายสำเร็จ | `COMPLETED` |
| `payment.failed` | ถูกปฏิเสธ / ยกเลิก | `FAILED` |
| `payment.expired` | หมดเวลาก่อนจ่าย | `EXPIRED` |

### 7.3 การตรวจ Signature (สำคัญมาก!)

ตรวจ HMAC signature ก่อน process webhook เพื่อยืนยันว่า payload มาจาก Gateway และไม่ถูกแก้ไข

> **ใครเป็นคนตรวจสอบ และใครเป็นคนปฏิเสธกรณี Invalid Signature?**
> - **Gateway ส่งมา:** PayMock จะส่ง Payload พร้อมแนบ Header `X-Signature: t={timestamp},v1={hmac}` มาเสมอ
> - **Backend ของคุณเป็นผู้ตรวจ:** Backend ของระบบคอนเสิร์ตมีหน้าที่คำนวณ HMAC-SHA256 ด้วยตัวเองโดยใช้ Webhook Secret ที่เก็บไว้เป็นความลับ
> - **หาก Signature ปลอม (Invalid Signature):** Gateway หรือ Hacker ไม่ได้ส่งข้อความบอกว่า "ไม่ผ่านนะ" แต่ส่ง Payload แกล้งบอกว่า `status: COMPLETED`! **Backend ของคุณเองมีหน้าที่สั่ง "ไม่ให้ผ่าน"** โดยการ **(1) ตอบกลับ HTTP 401 Unauthorized** และ **(2) ไม่ออกตั๋วเด็ดขาด (ตั๋วต้องไม่เป็น PAID)**
> - นโยบายตรวจจับการส่งซ้ำหรือ timestamp เก่าเป็นความรับผิดชอบของระบบคอนเสิร์ต ไม่ใช่การตรวจสถานะ payment ของ Gateway

#### ขั้นตอน:

1. อ่าน `X-Signature` header แล้วแยก `t` และ `v1`:
   ```
   X-Signature: t=1728810332,v1=a3f9c2...
   → timestamp = 1728810332
   → signature = a3f9c2...
   ```

2. คำนวณ HMAC ด้วย **raw request body** (ก่อน decode JSON) โดยใช้ timestamp จาก header:
   ```php
   $secret    = 'whsec_concert_super_secret_2024';
   $rawBody   = $request->getContent();   // raw bytes
   $expected  = hash_hmac('sha256', "$timestamp.$rawBody", $secret);
   ```

3. เปรียบเทียบแบบ constant-time:
   ```php
   if (! hash_equals($expected, $signature)) {
       return response('Invalid signature', 401);
   }
   ```

#### ตัวอย่าง PHP (ตรวจลายเซ็น):

```php
public function handleWebhook(Request $request): Response
{
    // 1. แยก signature header
    $sigHeader = $request->header('X-Signature', '');
    preg_match('/t=(\d+),v1=([a-f0-9]+)/', $sigHeader, $m);
    [$timestamp, $signature] = [$m[1] ?? 0, $m[2] ?? ''];

    // 2. คำนวณและตรวจ HMAC
    $secret   = config('services.paymock.webhook_secret');
    $rawBody  = $request->getContent();
    $expected = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);
    if (! hash_equals($expected, $signature)) {
        Log::warning('Webhook: invalid signature');
        return response('Unauthorized', 401);
    }

    // 3. ระบบคอนเสิร์ตจัดการ event ซ้ำตามนโยบายของตน
    $eventId = $request->header('X-Event-Id');
    if (WebhookLog::where('event_id', $eventId)->exists()) {
        return response('OK', 200); // รับแล้ว ไม่ต้อง process ซ้ำ
    }
    WebhookLog::create(['event_id' => $eventId]);

    // 4. process ตาม event
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

PayMock จะ retry webhook ถ้าได้รับ response ที่ **ไม่ใช่ 2xx** หรือเกิด connection error ตาม backoff ด้านล่าง รวมสูงสุด 6 attempts. เมื่อ retry แต่ละครั้ง Gateway สร้าง `X-Signature` timestamp ใหม่และใช้ `X-Event-Id` เดิม.

| ครั้งที่ | หน่วง (วินาที) |
|---------|--------------|
| retry 1 | 5 |
| retry 2 | 30 |
| retry 3 | 120 |
| retry 4 | 300 |
| retry 5 | 600 |
| **รวมสูงสุด** | **6 ครั้ง** |

> **แนะนำ:** endpoint ของคุณควรตอบ `200 OK` เร็วที่สุด แล้วค่อย process ใน background

Gateway บันทึกผลล่าสุดใน `webhook_deliveries`: `attempts` คือจำนวนครั้งที่พยายามส่ง, `last_status_code` คือ HTTP status ล่าสุด (เป็น `null` เมื่อการเชื่อมต่อล้มเหลวก่อนมี response), `last_error` เก็บข้อความ error เมื่อส่งไม่สำเร็จ และ `delivered_at` จะถูกตั้งเมื่อได้รับ 2xx เท่านั้น. เมื่อ retry สำเร็จ `last_status_code` จะเป็น status 2xx ล่าสุด, `last_error` ถูกล้าง และ `delivered_at` ถูกตั้ง; response body ของ Concert และผลครั้งก่อนหน้าไม่ได้เก็บเป็นประวัติใน record นี้.

---

## 8. Chaos และ Webhook Mode (PayMock Admin)

ระบบคอนเสิร์ตเรียกแค่ API จ่ายเงินจริง: **สร้างรายการ**, **ใส่เลขบัตร**, **ยกเลิก**, **GET สถานะ / QR**  
ความผิดปกติทั้งหมดตั้งที่ `http://localhost:8081/admin` ไม่ส่ง `scenario` / `X-Mock-Scenario` จากฝั่ง merchant

| ชั้น | Setting | เมื่อไหร่มีผล |
|------|---------|----------------|
| 1 (ใหญ่สุด) | Chaos Mode | คำขอเข้า `/payments/*` และ `/checkout/*` (ล่ม, ช้า, 429, 500, …) |
| 2 (รอง) | Webhook Mode | ตอนยิง webhook หลัง settle (lost, delay, dup, bad_sig) |

`webhook_mode` เป็นค่า global เดียวจาก Admin และมีผลกับทุกรายการ ไม่มี override ต่อ payment

### 8.1 Webhook Mode

ตั้งค่าที่ Admin ทั้งระบบเท่านั้น:

| Scenario | สิ่งที่ Gateway ส่งมา | สิ่งที่ Backend ต้องตอบ | ผลต่อตั๋วคอนเสิร์ต | การรับมือ / วัตถุประสงค์ |
|----------|----------------------|-----------------------|-------------------|--------------------------|
| `normal` | ส่ง Webhook ทันทีพร้อม Signature ถูกต้อง | `200 OK` | ออกตั๋วสำเร็จ (`PAID`) | กรณีการทำงานตามปกติ |
| `webhook_lost` | **ไม่ส่ง Webhook เลย** (Packet Drop) | — (ไม่ได้รับ) | ค้าง `PENDING` | ใช้ทดสอบ Fallback: ต้องมีปุ่มหรือ Cron ยิง `GET /payments/{id}` เพื่อ Reconcile สถานะ |
| `webhook_delay:N` | หน่วงเวลา N วินาทีก่อนส่ง Webhook | `200 OK` (เมื่อมาถึง) | `PENDING` → `PAID` หลัง N วิ | ทดสอบว่าหน้าเว็บรองรับช่วงรอยต่อระหว่างรอ Webhook หรือไม่ |
| `webhook_dup:N` | ส่ง Webhook ซ้ำกัน N รอบ (Payload เดิม) | `200 OK` ทุกรอบ | ออกตั๋ว 1 ใบเท่านั้น (`PAID`) | ทดสอบ Idempotency: รอบที่ 2-N ต้องตอบ 200 แต่ไม่ออกตั๋วซ้ำเด็ดขาด |
| `webhook_bad_sig` | ส่ง Body อ้างว่า `COMPLETED` แต่ลายเซ็นปลอม (`0000...`) | **`401 Unauthorized`** | **ปฏิเสธไม่ออกตั๋ว (`REJECTED`)** | ทดสอบ Security: ป้องกัน Hacker ส่ง Webhook ปลอมมาหลอกออกตั๋ว |

#### รายละเอียดเชิงลึกของแต่ละ Webhook Scenario:

1. **`webhook_lost` (Webhook สูญหายระหว่างทาง)**
   - **พฤติกรรม:** Gateway ตัดเงินสำเร็จจริง (ในฐานข้อมูลของ PayMock เป็น `COMPLETED`) แต่เกิดปัญหาเน็ตหลุด ทำให้ Webhook ไปไม่ถึง Backend ของคุณ
   - **ผลกระทบ:** ฝั่งคอนเสิร์ตไม่ได้รับ Webhook ทำให้ตั๋วยังคงค้างสถานะ `PENDING`
   - **วิธีรับมือที่ถูกต้อง:** ระบบคอนเสิร์ตต้องมีปุ่ม *"ตรวจสอบสถานะกับ Gateway"* หรือตั้ง Scheduler คอยเรียก `GET /payments/{id}` เพื่อนำสถานะจริงมา Reconcile

2. **`webhook_bad_sig` (ลายเซ็นปลอม / Security Attack)**
   - **พฤติกรรม:** Payload ใน Body อ้างว่า `"status": "COMPLETED"` แต่ Header `X-Signature` ถูกส่งเป็น `v1=00000000000000000000...` (จำลอง Hacker แกล้งส่งข้อมูลมาหลอก)
   - **การตรวจสอบ:** Backend ต้องนำ Secret Key มาคำนวณ HMAC-SHA256 ด้วยตัวเอง เมื่อพบว่าไม่ตรงกัน ต้องสั่ง *"ไม่ให้ผ่าน"*
   - **สิ่งที่ Backend ต้องทำ:** ตอบกลับ `HTTP 401 Unauthorized` ทันที และห้ามอัปเดตสถานะตั๋วเป็น `PAID` โดยเด็ดขาด!

3. **`webhook_dup:N` (ส่ง Webhook ซ้ำหลายครั้ง)**
   - **พฤติกรรม:** PayMock จะยิง Webhook ข้อมูลเดิมเข้ามาติดกัน N ครั้ง (เช่น 3 ครั้ง)
   - **สิ่งที่ Backend ต้องทำ:** ตรวจสอบ `X-Event-Id` หรือ provider transaction ID จาก `body.id` หากเคยประมวลผลแล้ว ครั้งต่อไปต้องตอบ `HTTP 200 OK` โดยไม่รันคำสั่งออกตั๋วซ้ำ

4. **`webhook_delay:N` (หน่วงเวลาส่ง Webhook)**
   - **พฤติกรรม:** รายการถูกตัดเงินสำเร็จทันที แต่ Gateway จะหน่วงเวลาก่อนส่ง Webhook ออกไปเป็นเวลา N วินาที
   - **สิ่งที่ Backend ต้องทำ:** ในระหว่างที่ยังไม่ได้รับ Webhook ตั๋วต้องค้าง `PENDING` และเมื่อ Webhook มาถึงจึงเปลี่ยนเป็น `PAID` อย่างราบรื่น

### 8.2 Chaos Mode (ทั้งระบบ — ตั้งจาก Admin Panel)

ตั้งค่าได้ที่ `http://localhost:8081/admin`

| mode | พฤติกรรม | ระบบควรทำ |
|------|---------|----------|
| `normal` | ปกติ | — |
| `down` | ตอบ 503 ทุก request ด้วยข้อความเดียว | แจ้ง `Gateway มีปัญหา โปรดลองอีกครั้ง` |
| `slow:N` | หน่วง N วินาที | แสดง loading และไม่กดซ้ำ |
| `rate_limit` | ตอบ 429 พร้อม `Retry-After: 30` | รอแล้วค่อยลองใหม่ |

### Response Body ของแต่ละ Chaos Mode

ตัวอย่างด้านล่างเป็น request แบบ API ที่ตอบ JSON (`/payments/*` หรือ request ที่ส่ง `Accept: application/json`). โหมด `normal` และ `slow:N` ใช้ response body ของ endpoint ตามปกติ; body จึงขึ้นกับ endpoint และ `method`.

#### `normal`

ไม่มี error จาก Chaos Mode; ได้ response ตาม endpoint. ตัวอย่าง `POST /payments` เมื่อสร้าง PromptPay สำเร็จ:

```http
HTTP/1.1 201 Created
Content-Type: application/json
```

```json
{
  "id": "txn_example",
  "source": "concert-app",
  "context": {"campaign": "summer"},
  "amount": 1200,
  "currency": "THB",
  "method": "promptpay",
  "status": "PENDING",
  "failure_code": null,
  "qrcode_url": "http://localhost:8081/payments/txn_example/qr.svg",
  "bank_simulator_url": "http://localhost:8081/bank-simulate/txn_example"
}
```

#### `slow:N`

หน่วงประมาณ `N` วินาที แล้วคืน status และ body เดียวกับ endpoint ปกติ เช่น `POST /payments` จะคืน `201` และ body ตามตัวอย่าง `normal`. หาก client timeout ก่อนครบเวลา อาจไม่ได้รับ response body.

#### `down`

```http
HTTP/1.1 503 Service Unavailable
Content-Type: application/json
```

```json
{
  "error": "service_unavailable",
  "message": "Gateway มีปัญหา โปรดลองอีกครั้ง"
}
```

#### `rate_limit`

```http
HTTP/1.1 429 Too Many Requests
Retry-After: 30
Content-Type: application/json
```

```json
{
  "error": "too_many_requests",
  "message": "Too many requests. Please retry after 30 seconds."
}
```

สำหรับหน้า Checkout ที่เปิดด้วย browser โดยไม่ส่ง `Accept: application/json`, โหมด `down` และ `rate_limit` แสดงหน้า HTML แทน JSON.

ไม่มี timeout simulation, bad JSON, bad auth หรือสุ่ม 5xx ใน Chaos Mode

> **ลำดับความสำคัญ:** Chaos Mode ทั้งระบบ (inbound) > เลขบัตรวิเศษ (ผลจ่าย) > Webhook Mode (outbound)

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

### 503 Service Unavailable

```json
{
  "error": "service_unavailable",
  "message": "Gateway มีปัญหา โปรดลองอีกครั้ง"
}
```

### 429 Rate Limited

```json
{
  "error": "too_many_requests",
  "message": "Too many requests. Please retry after 30 seconds."
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
| `POST /payments` | 401 | `unauthorized` | API Key ผิดหรือไม่มี | เช็ค config `.env` ห้าม Retry ซ้ำ |
| `POST /payments` | 422 | `validation_error` | ข้อมูลไม่ครบ เช่น amount <= 0 | แก้ไข request body ก่อนส่งใหม่ |
| `POST /payments` | 429 | `too_many_requests` | Chaos rate limit | รอตาม `Retry-After` แล้วค่อยลองใหม่ |
| `POST /payments` | 503 | `service_unavailable` | Gateway unavailable | แจ้ง `Gateway มีปัญหา โปรดลองอีกครั้ง` |
| `GET /payments/{id}` | 404 | `not_found` | ไม่พบ Payment ID นี้ | ตรวจสอบ ID ว่าส่งมาถูกต้องหรือไม่ |
| `POST /payments/{id}/cancel` | 409 | `cannot_cancel` | รายการจ่ายสำเร็จไปแล้ว | ห้ามยกเลิก ปล่อยผ่านเป็น COMPLETED |
| `Webhook (POST callback)` | 401 | `unauthorized` | HMAC Signature ไม่ตรง | ข้อมูลถูกดัดแปลง ปฏิเสธทันที |

### 9.3 Hierarchy ลำดับชั้นการตรวจสอบในระบบ PayMock

1. **Layer 1 Chaos (ใหญ่สุด):** Admin `chaos_mode` ครอบคำขอเข้า `/payments/*` และ checkout ด้วย 503, slow หรือ rate limit
2. **Layer 2 Request & Idempotency:** deduplicate เฉพาะเมื่อส่ง `Idempotency-Key` header; หากไม่มี header แต่ละ request จะสร้างรายการใหม่
3. **Layer 3 การชำระเงิน:** Magic Card / PromptPay confirm / cancel
4. **Layer 4 Webhook (รอง):** Admin `webhook_mode` แบบ global — lost / delay / dup / bad_sig

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
| `4000 0000 0000 0341` | ✅ COMPLETED | `null` | ผลเหมือนบัตรสำเร็จ; การหน่วง webhook ตั้งจาก Admin |

> CVC และวันหมดอายุ: ใส่อะไรก็ได้ (ยกเว้นบัตร `...0069` ที่ตรวจจาก expiry จริง) — การตัดสินขึ้นอยู่กับเลขบัตรเท่านั้น

---

## 11. Flow ทั้งหมด (Step-by-Step)

### Flow ปกติ (Happy Path)

```
ระบบคอนเสิร์ต                          PayMock
     │                                     │
     │  1. POST /payments               │
     │     amount, method, callback_url,    │
     │     Idempotency-Key                  │
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

### PayMock: ตรวจสถานะและทำให้รายการหมดอายุ

1. เมื่อสร้าง payment สถานะเริ่มต้นเป็น `PENDING`; `expires_in` กำหนดอายุเป็นวินาที (ค่าเริ่มต้น 600 วินาที/10 นาที) และ PayMock บันทึกเวลา `expires_at`.
2. container `paymock_scheduler` รัน Laravel `schedule:work`; schedule เรียก `payments:expire` ทุก 10 วินาที (ประมาณ 0.17 นาที).
3. แต่ละรอบ คำสั่งค้นหาเฉพาะรายการที่ยัง `PENDING` และ `expires_at < เวลาปัจจุบัน` แล้วเปลี่ยนสถานะผ่าน `PaymentService::transition` เป็น `EXPIRED`. รายการที่ชำระหรือยกเลิกแล้วจะไม่ถูกเปลี่ยนซ้ำ.
4. เมื่อหมดอายุ ค่า `failure_code` และ `paid_at` เป็น `null`; PayMock สร้าง webhook `payment.expired` แล้วส่งผ่าน `paymock_queue` ไปยัง `callback_url`. หาก callback ล้มเหลว ระบบ queue retry ตาม backoff 5, 30, 120, 300 และ 600 วินาที (สูงสุด 6 attempts).

`GET /payments/{id}` ใช้ตรวจสถานะที่บันทึกไว้และ **ไม่** เป็นตัวสั่งหมดอายุเอง; ถ้า `expires_at` ผ่านแล้วแต่ scheduler ยังไม่ถึงรอบ รายการอาจแสดง `PENDING` ชั่วคราวได้ (ปกติไม่เกินประมาณ 10 วินาทีเมื่อ scheduler ทำงาน). การเปิด `GET /checkout/{id}` เป็นอีกกรณีที่ตรวจเวลาและเปลี่ยนรายการ `PENDING` ที่เลยกำหนดเป็น `EXPIRED` ทันที. Scheduler เป็นตัวค้นหารายการตามรอบ ส่วน queue worker มีหน้าที่ส่ง webhook และ retry เท่านั้น.

### Flow Reconcile (Webhook หายหรือมาช้า)

```
ระบบคอนเสิร์ต (ปุ่มตรวจสอบใน demo; production ควรมี scheduled job)
     │
     │  POST /api/reconcile-booking { "payment_id": "<PayMock id>" }  [Concert demo]
     │  Concert เรียก GET /payments/{id} พร้อม Bearer key ไป PayMock
     │
     │  PayMock ตอบ status ปัจจุบันจากฐานข้อมูล
     │  Concert อัปเดต concert_payments.status/failure_code
     │  และ bookings.status/paid_at ในฐานข้อมูลของ Concert
     │
     ├─ COMPLETED → booking เป็น PAID; ทำขั้นตอนยืนยัน/ออกตั๋วตามระบบ Concert
     ├─ FAILED    → booking เป็น FAILED
     ├─ EXPIRED   → booking เป็น EXPIRED; ระบบ Concert จัดการยกเลิก/คืนทรัพยากรตามกติกาของตน
     └─ PENDING   → คงสถานะ booking เดิม; ตรวจสอบใหม่ภายหลัง
```

`POST /api/reconcile-booking` เป็น endpoint ของ Concert demo ไม่ใช่ PayMock API. Demo เรียก reconcile เมื่อกดปุ่มตรวจสอบ; การทำงานแบบ production ควรมี job ฝั่ง Concert ตรวจรายการที่ยังรอผลเป็นระยะ และใช้ `payment_id` ที่บันทึกไว้เพื่อเรียก PayMock. Reconcile เป็น fallback สำหรับ webhook ที่หาย/มาช้า; เมื่อได้สถานะแล้ว Concert ต้องอัปเดตฐานข้อมูลตัวเองแบบ idempotent.

### Flow Retry หลัง Timeout

```
1. ส่ง POST /payments พร้อม Idempotency-Key header เช่น `attempt-b_TK2024001`; ส่ง `metadata` ได้หากมีข้อมูลเพิ่มเติม
2. หมดเวลา (timeout) ไม่ได้ response
3. ส่ง POST /payments อีกครั้งด้วย `Idempotency-Key` header เดิม
4. PayMock คืน payment เดิม (ไม่สร้างซ้ำ)
5. นำ qrcode_url หรือผลลัพธ์เดิมไปแสดงผลต่อได้ทันที
```

หากไม่ส่ง `Idempotency-Key` จะไม่มี key ที่คงเดิมสำหรับ retry; gateway จะสร้าง payment ใหม่.

---

## 12. การเชื่อมต่อแบบ Native PromptPay และ Hosted Card Checkout

PromptPay ใช้ Native Modal ได้ แต่บัตรใช้ Hosted Checkout เพื่อไม่รับข้อมูลบัตรบนหน้า booking:
- **PromptPay**: PayMock ส่งคืน `qrcode_url` (ภาพ SVG) และ `bank_simulator_url` (หน้าจำลองแอปมือถือ) เพื่อให้เว็บคอนเสิร์ตนำรูป QR Code ไปใส่ใน Native Modal ของตัวเองได้ทันที
- **Credit Card**: `POST /payments` สร้างรายการ `PENDING` และคืน `checkout_url`; browser redirect ไปที่ `/checkout/{id}`. เมื่อกดจ่าย ฟอร์มจะ `POST /payments/{id}` เพื่อ settle payment เดิม แล้ว redirect ไป `return_url` โดยไม่เติม payment ID ลงใน URL. ระบบร้านค้าควรใช้ webhook เพื่อตรวจสถานะ

### 12.1 Hosted Card Flow
1. **แยกข้อมูลบัตรจาก booking page**: ผู้ใช้กรอกบัตรบนหน้า PayMock เท่านั้น
2. **Redirect กลับร้านค้า**: เส้นทาง submit คือ `POST /payments/{id}` และเส้นทางกลับคือ `return_url` ตามที่ร้านค้าระบุ โดยไม่มี payment ID ใน URL
3. **สถานะล่าสุด**: booking page ต้อง query ฐานข้อมูลอีกครั้งหลัง redirect; webhook ยังเป็นแหล่งอัปเดตสถานะหลัก

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

function openPromptPayModal(qrUrl, simulateUrl, paymentId) {
    document.getElementById('qrImage').src = qrUrl;
    document.getElementById('bankSimulateBtn').href = simulateUrl;
    document.getElementById('paymentModal').style.display = 'flex';

    // เริ่ม Polling เช็คสถานะจาก Backend ของเว็บคอนเสิร์ต (ใช้ payment_id)
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(async () => {
        const res = await fetch('/api/booking-status?payment_id=' + encodeURIComponent(paymentId));
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

## เปลี่ยน URL และ Port

ค่าด้านล่างอ้างอิง Docker Compose ปัจจุบัน โดยมี PayMock เปิดให้ browser/Postman ผ่าน port `8081` และ Concert demo ผ่าน port `5000`.

| ต้องการเปลี่ยน | จุดตั้งค่า | ตัวอย่าง |
|---|---|---|
| Port ที่เครื่อง host ใช้เปิด PayMock | `ports` ของ service `paymock` ใน `paymock/docker-compose.yml` (หรือ Compose หลักที่ root หากใช้ full stack) | `"8082:80"` |
| URL ที่ PayMock สร้างใน response เช่น `checkout_url`, `qrcode_url` | `APP_URL` ของ service `paymock` ใน Compose | `http://localhost:8082` |
| Port สำหรับเปิด Concert demo ใน browser | `ports` ของ service `concert_backend` ใน Compose หลักที่ root | `"5001:5000"` |
| URL ที่ browser กลับหลังจ่ายบัตร | request body `return_url`; Concert demo สร้างค่านี้จาก `CONCERT_BROWSER_URL` | `http://localhost:5001/booking/TK-260101-ABCDE` |
| URL ที่ Postman เรียก | collection variables `baseUrl` (PayMock) และ `concertUrl` (Concert) | `http://localhost:8082`, `http://localhost:5001` |

ถ้าเปลี่ยน PayMock host port จาก `8081` เป็น `8082`, ปรับ mapping เป็น `"8082:80"`, `APP_URL` เป็น `http://localhost:8082` และ Postman `baseUrl` ให้ตรงกัน. ส่วน `80` ด้านขวาของ port mapping คือ port ภายใน container และไม่ต้องเปลี่ยนเมื่อเปลี่ยนเฉพาะ host port.

Concert ติดต่อ PayMock ผ่าน Docker network ด้วย `PAYMOCK_URL=http://paymock:80/payments`; URL นี้ใช้ service name และ internal port จึงไม่เปลี่ยนตาม host port. หากเปลี่ยน port ฝั่ง browser ของ Concert เป็น `5001`, เปลี่ยน mapping เป็น `"5001:5000"`, ตั้ง `CONCERT_BROWSER_URL=http://localhost:5001` และปรับ Postman `concertUrl` เป็น `http://localhost:5001`. ในการเรียก API โดยตรง ให้ส่ง `return_url` เป็น public URL ที่ browser เข้าถึงได้; อย่าใช้ Docker service name ใน `return_url`.

---

*PayMock v1.0 — Mock Payment Gateway สำหรับระบบคอนเสิร์ต*
