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
8. [Chaos และ Webhook Mode (PayMock Admin)](#8-chaos-และ-webhook-mode-paymock-admin)
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

---

## 2. Headers ที่ใช้บ่อย

| Header | Required | ค่า | หมายเหตุ |
|--------|----------|-----|----------|
| `Authorization` | ✅ ทุก request | `Bearer pm_test_key_concert_system_2024` | API Key |
| `Content-Type` | ✅ POST | `application/json` | |
| `Idempotency-Key` | ไม่บังคับ | เช่น `attempt-12` | หากส่ง จะใช้ค่า header เป็น idempotency key; หากไม่ส่ง ระบบใช้ `body.payment_id` แทน |

สำหรับ `POST /v1/payments` ต้องส่ง `payment_id` ใน body เสมอ แต่ไม่จำเป็นต้องส่ง `Idempotency-Key` ซ้ำอีกครั้ง หากมี header ระบบจะตรวจ header; หากไม่มี header ระบบ fallback ไปตรวจ `payment_id`. ค่าที่เลือกใช้เป็น key ต้องคงเดิมเมื่อ retry attempt เดิม.

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
  "webhook_mode": "normal",
  "recent": [
    {
      "id": "01JCXYZ...",
      "payment_id": "12",
      "booking_ref": "TK-260101-ABCDE",
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
| `chaos_mode` | string | ชั้น 1: `normal`, `down`, `slow:3`, ฯลฯ (ตั้งที่ Admin) |
| `webhook_mode` | string | ชั้น 2: `normal`, `webhook_lost`, ฯลฯ (ตั้งที่ Admin) |
| `recent` | array | 10 รายการล่าสุด (ดู schema ใน [หัวข้อ 4](#response-201-created)) |

---

## 4. POST /v1/payments — สร้างรายการจ่ายเงิน

### Request (PromptPay)

ส่ง `booking_ref` จาก booking จริงใน request body เพื่อให้ response ใช้ booking code เดียวกัน; หากละ field นี้ ระบบจะใช้ `payment_id` แทน.

```http
POST /v1/payments HTTP/1.1
Authorization: Bearer pm_test_key_concert_system_2024
Content-Type: application/json
```

```json
{
  "payment_id":   "12",
  "booking_ref":  "TK-260101-ABCDE",
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

### Request (Credit Card - Hosted Checkout)

```json
{
  "payment_id":   "13",
  "booking_ref":    "TK-260101-ABCDE",
  "amount":       1200.00,
  "method":       "card",
  "callback_url": "http://localhost:8080/webhooks/paymock",
  "return_url":   "http://localhost:5000/booking/TK-260101-ABCDE"
}
```

### Request Body Fields

| Field | Type | Required | ค่าที่ยอมรับ | Default | คำอธิบาย |
|-------|------|----------|-------------|---------|----------|
| `payment_id` | string | ✅ | max 255 chars | — | รหัสรายการจากระบบคอนเสิร์ต ต้องส่งทุกครั้ง |
| `booking_ref` | string | ❌ | max 255 chars | ค่าเดียวกับ `payment_id` | Booking code ที่ใช้แสดงแก่ผู้ใช้และใช้เป็น path สำหรับกลับมาหน้า booking; หากต้องการให้ response คืน booking code จริง ต้องส่ง field นี้มา |
| `amount` | number | ✅ | min 1 | — | ยอดเงิน (บาท, ทศนิยมได้) |
| `method` | string | ✅ | `"card"` หรือ `"promptpay"` | — | วิธีชำระ |
| `callback_url` | string | ✅ | URL ที่ถูกต้อง | — | URL ที่ PayMock จะ POST Webhook มาหา |
| `return_url` | string | ❌ | URL ที่ถูกต้อง | `null` | หน้าที่ Gateway redirect กลับหลัง submit บัตร |
| `expires_in` | integer | ❌ | min 60 (วินาที) | 600 | อายุรายการก่อนหมดเวลา |
| `currency` | string | ❌ | 3 ตัวอักษร | `"THB"` | สกุลเงิน (default: `"THB"`) |
| `metadata` | object | ❌ | JSON object ใดก็ได้ | `null` | ข้อมูลเพิ่มเติม (ไม่ส่งใน webhook) |

เพื่อ backward compatibility ยังรับ `order_ref` ใน request เก่าได้ แต่ response ใช้ชื่อ `booking_ref` เสมอ; integration ใหม่ควรส่ง `booking_ref`. ชื่อคอลัมน์ persistence ภายในยังคงเดิม จึงไม่ต้อง migrate ข้อมูล.

### Idempotency

- ระบบเลือก idempotency key จาก `Idempotency-Key` header ก่อน; ถ้าไม่ส่ง header จะใช้ `body.payment_id` เป็น key อัตโนมัติ
- ดังนั้น request ปกติส่ง `payment_id` ใน body อย่างเดียวได้ ไม่ต้องส่ง field `idempotency_key` หรือ header ซ้ำ
- หากเลือกส่ง `Idempotency-Key` ให้ส่ง header เดิมทุกครั้งที่ retry attempt นั้น; ค่า header ไม่จำเป็นต้องเท่ากับ `payment_id` แต่ต้องไม่เปลี่ยนระหว่าง retry
- retry เพราะ timeout ของ attempt เดิม ต้องส่ง `payment_id` เดิม และถ้าใช้ header ต้องส่ง key เดิมด้วย → **คืน payment เดิม** (ไม่สร้างใหม่)
- ไม่มีการอ่าน `idempotency_key` จาก JSON body; body field ที่เป็น fallback คือ `payment_id` เท่านั้น
- บัตรถูกปฏิเสธ/PromptPay หมดอายุแล้วต้องการจ่ายใหม่ ให้สร้าง Concert payment ใหม่ใต้ booking เดิม

#### Concert-PayMock flow

Concert ส่ง `body.payment_id` และ `body.booking_ref` ไปยัง `POST /v1/payments`. `Idempotency-Key` header ไม่บังคับ; หากไม่ส่ง ระบบใช้ `body.payment_id`. สำหรับ Concert demo ให้ใช้ `payment_id` ใน query หรือ body ตาม endpoint.

**ข้อจำกัดที่มีอยู่จริง:** Concert ตั้ง cURL timeout 10 วินาที. หาก PayMock สร้างรายการแล้วแต่ Concert ไม่ได้รับ response, PayMock รองรับการ resend ด้วย `payment_id`/key เดิมอยู่แล้ว แต่ Concert ยังไม่มี route หรือ worker ที่ resend แถวเดิมอัตโนมัติ และบันทึก attempt นั้นเป็น `GATEWAY_ERROR`. จึงห้ามสร้าง Concert payment ใหม่เพียงเพราะ timeout; ต้อง retry/reconcile attempt เดิมด้วย key เดิมก่อน และสร้าง payment ใหม่เมื่อยืนยันว่า `FAILED` หรือ `EXPIRED` เท่านั้น.

### ต้องส่ง ID ตัวไหนในแต่ละ API?

| API / จุดที่ส่งค่า | ชื่อ field / path | ค่าที่ต้องใช้ |
|---|---|---|
| `GET /v1/payments` | ไม่มี | ไม่ต้องส่ง ID |
| `POST /v1/payments` | `body.payment_id` | รหัส payment attempt ฝั่ง merchant; Concert ใช้เลข `id` ของแถว `concert_payments` |
| `POST /v1/payments` | `body.booking_ref` | Booking code เช่น `TK-260101-ABCDE`; คงเดิมตลอด booking |
| `POST /v1/payments` | `Idempotency-Key` | ถ้าส่ง ใช้ header เป็น key; ถ้าไม่ส่ง ระบบใช้ `body.payment_id` |
| `GET /v1/payments/{id}` | path `{id}` | ส่ง `id` ของรายการที่ต้องการดู |
| `POST /v1/payments/{id}/cancel` | path `{id}` | ส่ง `id` ของรายการที่ต้องการยกเลิก |
| `GET /v1/payments/{id}/qr.svg` | path `{id}` | ส่ง `id` ของรายการที่ต้องการแสดง QR |
| `GET /checkout/{id}`, `POST /payments/{id}` | path `{id}` | ส่ง `id` ของรายการที่จะเปิด checkout หรือชำระด้วยบัตร |
| `/checkout/{id}/...`, `/bank-simulate/{id}/...` | path `{id}` | ส่ง `id` ของรายการที่ต้องการดำเนินการ |
| Webhook จาก PayMock | `body.id`, `body.payment_id`, `body.booking_ref` | ใช้ชื่อ field ตามที่ส่งมา: `id`, `payment_id` และ `booking_ref` |
| ตัวอย่าง Concert: `/api/booking-status`, `/api/cancel-booking`, `/api/reconcile-booking` | query/body `payment_id` | ใช้ชื่อ field `payment_id` |

### Response 201 Created (PromptPay)

Response จะมี URL เฉพาะช่องทางที่เลือก: PromptPay คืน `qrcode_url` และ `bank_simulator_url` โดยไม่คืน `checkout_url`; บัตรคืน `checkout_url` โดยไม่คืน `qrcode_url` หรือ `bank_simulator_url`.

```json
{
  "id":                 "01JCXYZ1234567890ABCDEFGH",
  "payment_id":         "12",
  "booking_ref":          "TK-260101-ABCDE",
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
| `id` | string (ULID) | ใช้ใน path ของ GET, cancel, QR และ checkout |
| `payment_id` | string | รหัสรายการที่ merchant ส่งมาใน request |
| `booking_ref` | string | Booking code ที่ merchant ส่งมา (ใช้แสดงให้ผู้ใช้และจับคู่หน้า return) |
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

```json
{
  "id": "txn_01JCXYZ1234567890ABCDEFGH",
  "payment_id": "13",
  "booking_ref": "TK-260101-ABCDE",
  "amount": 1200.00,
  "currency": "THB",
  "method": "card",
  "status": "PENDING",
  "failure_code": null,
  "expires_at": "2026-10-13T10:10:00+07:00",
  "paid_at": null,
  "created_at": "2026-10-13T10:00:00+07:00",
  "checkout_url": "http://localhost:8081/checkout/txn_01JCXYZ1234567890ABCDEFGH",
  "return_url": "http://localhost:5000/booking/TK-260101-ABCDE"
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
  "payment_id":         "12",
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
  "id":           "txn_01JCXYZ1234567890ABCDEFGH",
  "event":        "payment.completed",
  "payment_id":   "12",
  "booking_ref":  "TK-260101-ABCDE",
  "amount":       1200.00,
  "currency":     "THB",
  "method":       "promptpay",
  "status":       "COMPLETED",
  "failure_code": null,
  "paid_at":      "2026-10-13T10:05:32+07:00",
  "created_at":   "2026-10-13T10:00:00+07:00"
}
```

### Webhook Body Fields

| Field | Type | ความหมาย |
|-------|------|----------|
| `id` | string (ULID) | Payment ID (ขึ้นต้นด้วย `txn_` ใช้ call `GET /v1/payments/{id}` เพื่อยืนยันได้) |
| `event` | string | ชื่อ event ดู [ตาราง events](#webhook-events) |
| `payment_id` | string | รหัสรายการจาก merchant ที่ส่งในตอนสร้าง payment |
| `booking_ref` | string | Booking code จาก merchant ใช้จับคู่กับ booking |
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

**ต้องทำก่อน process webhook ทุกครั้ง** เพื่อป้องกัน Replay Attack และ Forgery

> **ใครเป็นคนตรวจสอบ และใครเป็นคนปฏิเสธกรณี Invalid Signature?**
> - **Gateway ส่งมา:** PayMock จะส่ง Payload พร้อมแนบ Header `X-Signature: t={timestamp},v1={hmac}` มาเสมอ
> - **Backend ของคุณเป็นผู้ตรวจ:** Backend ของระบบคอนเสิร์ตมีหน้าที่คำนวณ HMAC-SHA256 ด้วยตัวเองโดยใช้ Webhook Secret ที่เก็บไว้เป็นความลับ
> - **หาก Signature ปลอม (Invalid Signature):** Gateway หรือ Hacker ไม่ได้ส่งข้อความบอกว่า "ไม่ผ่านนะ" แต่ส่ง Payload แกล้งบอกว่า `status: COMPLETED`! **Backend ของคุณเองมีหน้าที่สั่ง "ไม่ให้ผ่าน"** โดยการ **(1) ตอบกลับ HTTP 401 Unauthorized** และ **(2) ไม่ออกตั๋วเด็ดขาด (ตั๋วต้องไม่เป็น PAID)**
> - **หาก Replay Attack:** หาก Timestamp ห่างเกิน 5 นาที (300 วินาที) Backend ของคุณต้องปฏิเสธด้วย HTTP 401 เช่นกัน

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

## 8. Chaos และ Webhook Mode (PayMock Admin)

ระบบคอนเสิร์ตเรียกแค่ API จ่ายเงินจริง: **สร้างรายการ**, **ใส่เลขบัตร**, **ยกเลิก**, **GET สถานะ / QR**  
ความผิดปกติทั้งหมดตั้งที่ `http://localhost:8081/admin` ไม่ส่ง `scenario` / `X-Mock-Scenario` จากฝั่ง merchant

| ชั้น | Setting | เมื่อไหร่มีผล |
|------|---------|----------------|
| 1 (ใหญ่สุด) | Chaos Mode | คำขอเข้า `/v1/*` และ `/checkout/*` (ล่ม, ช้า, 429, 500, …) |
| 2 (รอง) | Webhook Mode | ตอนยิง webhook หลัง settle (lost, delay, dup, bad_sig, replay) |

`webhook_mode` เป็นค่า global เดียวจาก Admin และมีผลกับทุกรายการ ไม่มี override ต่อ payment

### 8.1 Webhook Mode

ตั้งค่าที่ Admin ทั้งระบบเท่านั้น:

| Scenario | สิ่งที่ Gateway ส่งมา | สิ่งที่ Backend ต้องตอบ | ผลต่อตั๋วคอนเสิร์ต | การรับมือ / วัตถุประสงค์ |
|----------|----------------------|-----------------------|-------------------|--------------------------|
| `normal` | ส่ง Webhook ทันทีพร้อม Signature ถูกต้อง | `200 OK` | ออกตั๋วสำเร็จ (`PAID`) | กรณีการทำงานตามปกติ |
| `webhook_lost` | **ไม่ส่ง Webhook เลย** (Packet Drop) | — (ไม่ได้รับ) | ค้าง `PENDING` | ใช้ทดสอบ Fallback: ต้องมีปุ่มหรือ Cron ยิง `GET /v1/payments/{id}` เพื่อ Reconcile สถานะ |
| `webhook_delay:N` | หน่วงเวลา N วินาทีก่อนส่ง Webhook | `200 OK` (เมื่อมาถึง) | `PENDING` → `PAID` หลัง N วิ | ทดสอบว่าหน้าเว็บรองรับช่วงรอยต่อระหว่างรอ Webhook หรือไม่ |
| `webhook_dup:N` | ส่ง Webhook ซ้ำกัน N รอบ (Payload เดิม) | `200 OK` ทุกรอบ | ออกตั๋ว 1 ใบเท่านั้น (`PAID`) | ทดสอบ Idempotency: รอบที่ 2-N ต้องตอบ 200 แต่ไม่ออกตั๋วซ้ำเด็ดขาด |
| `webhook_bad_sig` | ส่ง Body อ้างว่า `COMPLETED` แต่ลายเซ็นปลอม (`0000...`) | **`401 Unauthorized`** | **ปฏิเสธไม่ออกตั๋ว (`REJECTED`)** | ทดสอบ Security: ป้องกัน Hacker ส่ง Webhook ปลอมมาหลอกออกตั๋ว |
| `webhook_replay` | ส่ง Body ถูกต้อง แต่ Timestamp เก่าเกิน 5 นาที | **`401 Unauthorized`** | **ปฏิเสธไม่ออกตั๋ว (`REJECTED`)** | ทดสอบ Replay Attack: ป้องกันการดักจับ Webhook เก่ามายิงซ้ำ |

#### รายละเอียดเชิงลึกของแต่ละ Webhook Scenario:

1. **`webhook_lost` (Webhook สูญหายระหว่างทาง)**
   - **พฤติกรรม:** Gateway ตัดเงินสำเร็จจริง (ในฐานข้อมูลของ PayMock เป็น `COMPLETED`) แต่เกิดปัญหาเน็ตหลุด ทำให้ Webhook ไปไม่ถึง Backend ของคุณ
   - **ผลกระทบ:** ฝั่งคอนเสิร์ตไม่ได้รับ Webhook ทำให้ตั๋วยังคงค้างสถานะ `PENDING`
   - **วิธีรับมือที่ถูกต้อง:** ระบบคอนเสิร์ตต้องมีปุ่ม *"ตรวจสอบสถานะกับ Gateway"* หรือตั้ง Scheduler คอยเรียก `GET /v1/payments/{payment_id}` เพื่อนำสถานะจริงมา Reconcile

2. **`webhook_bad_sig` (ลายเซ็นปลอม / Security Attack)**
   - **พฤติกรรม:** Payload ใน Body อ้างว่า `"status": "COMPLETED"` แต่ Header `X-Signature` ถูกส่งเป็น `v1=00000000000000000000...` (จำลอง Hacker แกล้งส่งข้อมูลมาหลอก)
   - **การตรวจสอบ:** Backend ต้องนำ Secret Key มาคำนวณ HMAC-SHA256 ด้วยตัวเอง เมื่อพบว่าไม่ตรงกัน ต้องสั่ง *"ไม่ให้ผ่าน"*
   - **สิ่งที่ Backend ต้องทำ:** ตอบกลับ `HTTP 401 Unauthorized` ทันที และห้ามอัปเดตสถานะตั๋วเป็น `PAID` โดยเด็ดขาด!

3. **`webhook_replay` (Replay Attack / ข้อมูลเก่าเกินเวลา)**
   - **พฤติกรรม:** ข้อมูลใน Body ถูกต้อง และลายเซ็นถูกต้อง แต่ timestamp (`t`) ใน Header เก่ากว่าเวลาปัจจุบันเกิน 5 นาที (300 วินาที)
   - **สิ่งที่ Backend ต้องทำ:** ตรวจสอบ `abs(time() - $timestamp) > 300` หากเกิน 5 นาที ต้องตอบกลับ `HTTP 401 Unauthorized` และปฏิเสธการออกตั๋ว

4. **`webhook_dup:N` (ส่ง Webhook ซ้ำหลายครั้ง)**
   - **พฤติกรรม:** PayMock จะยิง Webhook ข้อมูลเดิมเข้ามาติดกัน N ครั้ง (เช่น 3 ครั้ง)
   - **สิ่งที่ Backend ต้องทำ:** ตรวจสอบ `X-Event-Id` หรือ `payment_id` หากเคยประมวลผลไปแล้วในครั้งแรก ครั้งต่อไปต้องตอบ `HTTP 200 OK` ทันทีโดยไม่รันคำสั่งออกตั๋วซ้ำ

5. **`webhook_delay:N` (หน่วงเวลาส่ง Webhook)**
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

ตัวอย่างด้านล่างเป็น request แบบ API ที่ตอบ JSON (`/v1/*` หรือ request ที่ส่ง `Accept: application/json`). โหมด `normal` และ `slow:N` ใช้ response body ของ endpoint ตามปกติ; body จึงขึ้นกับ endpoint และ `method`.

#### `normal`

ไม่มี error จาก Chaos Mode; ได้ response ตาม endpoint. ตัวอย่าง `POST /v1/payments` เมื่อสร้าง PromptPay สำเร็จ:

```http
HTTP/1.1 201 Created
Content-Type: application/json
```

```json
{
  "id": "txn_example",
  "payment_id": "12",
  "booking_ref": "TK-260101-ABCDE",
  "amount": 1200,
  "currency": "THB",
  "method": "promptpay",
  "status": "PENDING",
  "failure_code": null,
  "qrcode_url": "http://localhost:8081/v1/payments/txn_example/qr.svg",
  "bank_simulator_url": "http://localhost:8081/bank-simulate/txn_example"
}
```

#### `slow:N`

หน่วงประมาณ `N` วินาที แล้วคืน status และ body เดียวกับ endpoint ปกติ เช่น `POST /v1/payments` จะคืน `201` และ body ตามตัวอย่าง `normal`. หาก client timeout ก่อนครบเวลา อาจไม่ได้รับ response body.

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
| `POST /v1/payments` | 401 | `unauthorized` | API Key ผิดหรือไม่มี | เช็ค config `.env` ห้าม Retry ซ้ำ |
| `POST /v1/payments` | 422 | `validation_error` | ข้อมูลไม่ครบ เช่น amount <= 0 | แก้ไข request body ก่อนส่งใหม่ |
| `POST /v1/payments` | 429 | `too_many_requests` | Chaos rate limit | รอตาม `Retry-After` แล้วค่อยลองใหม่ |
| `POST /v1/payments` | 503 | `service_unavailable` | Gateway unavailable | แจ้ง `Gateway มีปัญหา โปรดลองอีกครั้ง` |
| `GET /v1/payments/{id}` | 404 | `not_found` | ไม่พบ Payment ID นี้ | ตรวจสอบ ID ว่าส่งมาถูกต้องหรือไม่ |
| `POST /v1/payments/{id}/cancel` | 409 | `cannot_cancel` | รายการจ่ายสำเร็จไปแล้ว | ห้ามยกเลิก ปล่อยผ่านเป็น COMPLETED |
| `Webhook (POST callback)` | 400 | `expired` | Timestamp เก่าเกิน 5 นาที | ระวัง Replay Attack ปฏิเสธทันที |
| `Webhook (POST callback)` | 401 | `unauthorized` | HMAC Signature ไม่ตรง | ข้อมูลถูกดัดแปลง ปฏิเสธทันที |

### 9.3 Hierarchy ลำดับชั้นการตรวจสอบในระบบ PayMock

1. **Layer 1 Chaos (ใหญ่สุด):** Admin `chaos_mode` ครอบคำขอเข้า `/v1/*` และ checkout ด้วย 503, slow หรือ rate limit
2. **Layer 2 Request & Idempotency:** ใช้ `Idempotency-Key` header ถ้ามี; หากไม่มีใช้ `body.payment_id` (ถ้าซ้ำ คืนรายการเดิม)
3. **Layer 3 การชำระเงิน:** Magic Card / PromptPay confirm / cancel
4. **Layer 4 Webhook (รอง):** Admin `webhook_mode` แบบ global — lost / delay / dup / bad_sig / replay

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
     │  1. POST /v1/payments               │
     │     payment_id, amount, method,     │
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

### PayMock: Worker ตรวจรายการหมดอายุ

PayMock แยก scheduler สำหรับหมดอายุออกจาก queue worker ที่ส่ง webhook:

1. เมื่อสร้าง payment จะตั้ง `expires_at` และสถานะเริ่มต้นเป็น `PENDING`.
2. `paymock_scheduler` รัน Laravel `schedule:work`; schedule เรียกคำสั่ง `payments:expire` ทุก 10 วินาที.
3. คำสั่งค้นหาเฉพาะรายการ `PENDING` ที่ `expires_at < เวลาปัจจุบัน` แล้วเปลี่ยนสถานะเป็น `EXPIRED` ผ่าน `PaymentService::transition`.
4. การเปลี่ยนสถานะบันทึก `status`, `failure_code` และ `paid_at` ลงฐานข้อมูล PayMock; สำหรับ `EXPIRED` ค่า `failure_code` และ `paid_at` เป็น `null`. รายการที่ไม่ใช่ `PENDING` จะไม่ถูกเปลี่ยนซ้ำ.
5. หลังอัปเดต PayMock จะสร้าง webhook delivery ของ `payment.expired` และ dispatch ไปยัง queue. `paymock_queue` ส่ง callback ไปยัง `callback_url`; หากปลายทางตอบ non-2xx หรือการเชื่อมต่อล้มเหลว จะ retry ตาม backoff ที่กำหนด (สูงสุด 6 attempts).

ดังนั้น queue worker มีหน้าที่ส่ง webhook ไม่ได้เป็นตัวค้นหารายการหมดอายุ. การตั้ง `expires_at` ผ่านไปอย่างเดียวไม่ได้เปลี่ยน status ทันที; โดยปกติ scheduler จะเปลี่ยนสถานะในรอบถัดไป. อีกกรณีหนึ่ง `GET /checkout/{id}` จะตรวจเวลาและเปลี่ยนรายการที่ยัง `PENDING` เป็น `EXPIRED` เมื่อมีการเปิดหน้า Checkout. ส่วน `GET /v1/payments/{id}` อ่าน status ที่บันทึกอยู่เท่านั้น ไม่ได้สั่ง expire รายการเอง.

### Flow Reconcile (Webhook หายหรือมาช้า)

```
ระบบคอนเสิร์ต (ปุ่มตรวจสอบใน demo; production ควรมี scheduled job)
     │
     │  POST /api/reconcile-booking { "payment_id": "<PayMock id>" }  [Concert demo]
     │  Concert เรียก GET /v1/payments/{id} พร้อม Bearer key ไป PayMock
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
1. ส่ง POST /v1/payments พร้อม body.payment_id = `b_TK2024001` (ไม่ต้องส่ง header)
2. หมดเวลา (timeout) ไม่ได้ response
3. ส่ง POST /v1/payments อีกครั้งด้วย body.payment_id เดิม: `b_TK2024001`
4. PayMock คืน payment เดิม (ไม่สร้างซ้ำ)
5. นำ qrcode_url หรือผลลัพธ์เดิมไปแสดงผลต่อได้ทันที
```

หาก request เดิมใช้ `Idempotency-Key` header ให้ส่ง header ค่าเดิมซ้ำตอน retry; header จะมี precedence เหนือ `payment_id`.

---

## 12. การเชื่อมต่อแบบ Native PromptPay และ Hosted Card Checkout

PromptPay ใช้ Native Modal ได้ แต่บัตรใช้ Hosted Checkout เพื่อไม่รับข้อมูลบัตรบนหน้า booking:
- **PromptPay**: PayMock ส่งคืน `qrcode_url` (ภาพ SVG) และ `bank_simulator_url` (หน้าจำลองแอปมือถือ) เพื่อให้เว็บคอนเสิร์ตนำรูป QR Code ไปใส่ใน Native Modal ของตัวเองได้ทันที
- **Credit Card**: `POST /v1/payments` สร้างรายการ `PENDING` และคืน `checkout_url`; browser redirect ไปที่ `/checkout/{id}`. เมื่อกดจ่าย ฟอร์มจะ `POST /payments/{id}` เพื่อ settle payment เดิม แล้ว redirect ไป `return_url` โดยไม่เติม payment ID ลงใน URL. ระบบร้านค้าควรใช้ webhook เพื่อตรวจสถานะ

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

Concert ติดต่อ PayMock ผ่าน Docker network ด้วย `PAYMOCK_URL=http://paymock:80/v1/payments`; URL นี้ใช้ service name และ internal port จึงไม่เปลี่ยนตาม host port. หากเปลี่ยน port ฝั่ง browser ของ Concert เป็น `5001`, เปลี่ยน mapping เป็น `"5001:5000"`, ตั้ง `CONCERT_BROWSER_URL=http://localhost:5001` และปรับ Postman `concertUrl` เป็น `http://localhost:5001`. ในการเรียก API โดยตรง ให้ส่ง `return_url` เป็น public URL ที่ browser เข้าถึงได้; อย่าใช้ Docker service name ใน `return_url`.

---

*PayMock v1.0 — Mock Payment Gateway สำหรับระบบคอนเสิร์ต*
