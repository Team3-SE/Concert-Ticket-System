# PayMock
### Mock Payment Gateway — API Reference
> เอกสารสำหรับนักพัฒนาระบบคอนเสิร์ต (ฝั่งต้นทาง)

---

## เนื้อหาในเอกสาร
1. [ภาพรวมและการเชื่อมต่อ](#1-ภาพรวมและการเชื่อมต่อ) — ข้อมูลเชื่อมต่อ สรุป Endpoint ทั้งหมด และ Public ID
2. [สร้างรายการชำระเงิน](#2-สร้างรายการชำระเงิน) — POST /payments — ฟิลด์ ตัวอย่าง PromptPay / บัตรเครดิต และ Idempotency
3. [Endpoints อื่น ๆ](#3-endpoints-อื่น-ๆ) — ตรวจสถานะ ยกเลิก และหน้า UI จำลอง
4. [สถานะ รหัสผิดพลาด และ Error Format](#4-สถานะ-รหัสผิดพลาด-และ-error-format) — Status, Failure Codes, HTTP Error และตัวอย่าง response
5. [Webhook — สิ่งที่ PayMock ส่งมาหาคุณ](#5-webhook-สิ่งที่-paymock-ส่งมาหาคุณ) — สิ่งที่ PayMock ส่งมา การตรวจ Signature โค้ดตัวอย่าง Retry และ payment.expired
6. [Flow ฝั่งระบบคอนเสิร์ต](#6-flow-ฝั่งระบบคอนเสิร์ต) — Retry หลัง Timeout, Reconcile และ Scheduler
7. [การทดสอบด้วย PayMock Admin](#7-การทดสอบด้วย-paymock-admin) — ลำดับชั้น Chaos Mode, Webhook Mode และ Magic Card Numbers

---

# 1. ภาพรวมและการเชื่อมต่อ

## 1.1 ข้อมูลเชื่อมต่อ

| รายการ | ค่า |
|---|---|
| **Base URL** | `http://localhost:8081` |
| **API Key** | `pm_test_key_concert_system_2024` |
| **Webhook Secret** | `whsec_concert_super_secret_2024` |
| **Admin Panel** | `http://localhost:8081/admin` (ตั้งค่า Chaos Mode และ Webhook Mode — ดูหัวข้อ 7) |

## 1.2 Flow การทำงาน

ลำดับการทำงานระหว่างระบบคอนเสิร์ตและ PayMock:
* ① ผู้ใช้เลือกที่นั่ง → Concert สร้าง booking และ booking code (booking_ref) เช่น `TK-260101-ABCDE` ก่อนสร้างรายการจ่ายเงิน
* ② Concert ส่ง `amount`, `method` และ `callback_url` ให้ PayMock; ข้อมูลประกอบอื่นส่งผ่าน `metadata` ได้โดย Gateway ไม่ตีความ
* ③ PromptPay: เว็บคอนเสิร์ตแสดง QR Code จาก `qrcode_url` ใน Native Modal ของตัวเอง | Card: สร้างสถานะ PENDING แล้ว redirect ไป Hosted Checkout
* ④ ผู้ใช้สแกน QR ผ่านแอปธนาคาร หรือคลิก `bank_simulator_url` เพื่อจำลองการโอนเงิน
* ⑤ PayMock เปลี่ยนสถานะ → ส่ง Webhook (`POST {callback_url}`) พร้อม HMAC-SHA256; หลังจ่ายบัตร redirect กลับ `/booking/{booking_ref}` โดยไม่ใส่ payment ID ใน URL
* ⑥ ระบบคอนเสิร์ตตรวจ signature → ตรวจ idempotency → อัปเดต booking + seat + ออกตั๋ว
* ⑦ (สำรอง) ระบบคอนเสิร์ตยิง `GET /payments/{id}` เพื่อ reconcile — ส่ง id ใน path

> ⚠️ **สำคัญ:** ห้ามตัดสินว่าการชำระเงินสำเร็จหรือไม่จากการ Poll ฝั่ง Frontend เท่านั้น ต้องรอการยืนยันจาก Webhook หรือ `GET /payments/{id}` เท่านั้น

## 1.3 สรุป Endpoint

| Method | Path | ใช้เมื่อ | ต้องส่ง | ผลลัพธ์สำเร็จ | กรณีผิดพลาด |
| --- | --- | --- | --- | --- | --- |
| `GET` | `/payments` | ตรวจ health และรายการล่าสุด | Bearer token | 200 JSON: service, status, chaos_mode, webhook_mode, recent[] | 401 token ผิด; 429/500/502/503 เมื่อ Chaos Mode |
| `POST` | `/payments` | เริ่มสร้าง payment / attempt ใหม่ หรือ retry request ที่ timeout | Bearer, JSON; Idempotency-Key optional; ใช้ header เดิมเมื่อ retry | 201 JSON PENDING; URL ตาม method | 401, 422 validation, 429, 500, 502, 503 |
| `GET` | `/payments/{publicId}` | poll / reconcile สถานะ transaction | Bearer token; path: id | 200 JSON payment | 401, 404, 429, 500, 502, 503 |
| `POST` | `/payments/{publicId}/cancel` | ยกเลิกเฉพาะรายการที่ยัง PENDING | Bearer token; ไม่มี body | 200 { ok:true, status:"FAILED", failure_code:"cancelled" } | 401, 404, 409, 429, 500, 502, 503 |
| `GET` | `/checkout/{publicId}` | เปิด UI ชำระเงิน (ใช้เฉพาะบัตรเครดิต) | path publicId | 200 HTML หน้าจ่าย; ถ้า settle หรือหมดอายุ แสดง receipt HTML | 404 ถ้าไม่พบ |
| `GET` | `/bank-simulate/{publicId}` | เปิดแอปธนาคารจำลองจาก QR | path publicId | 200 HTML mobile banking simulator | 404 ถ้าไม่พบ |

## 1.4 Public ID

**Public ID** คือ payment gateway id ที่ขึ้นต้นด้วย `txn_...` เช่น `txn_01m5e8xnzjbjv15t6pdms7tbvd` อยู่ในฟิลด์ **id** ที่ได้จาก response ของ `POST /payments` ทั้งกรณี card และ promptpay ใช้เป็น `{publicId}` ใน path ของ GET / cancel / QR / checkout

## 1.5 เปลี่ยนportและurl

ค่าตัวอย่างอ้างอิง Docker Compose ปัจจุบัน: PayMock เปิดให้ browser/Postman ผ่าน host port 8081 และ Concert demo ผ่าน host port 5000.

| ต้องการเปลี่ยน | จุดตั้งค่า | ตัวอย่าง |
| --- | --- | --- |
| Port ที่เครื่อง host ใช้เปิด PayMock | ports ของ service paymock ใน paymock/docker-compose.yml (หรือ Compose หลักที่ root) | "8082:80" |
| URL ที่ PayMock สร้างใน response เช่น checkout_url/qrcode_url | APP_URL ของ service paymock ใน Compose | http://localhost:8082 |
| Port สำหรับเปิด Concert demo ใน browser | ports ของ service concert_backend ใน Compose หลักที่ root | "5001:5000" |
| URL ที่ browser กลับหลังจ่ายบัตร | ใส่ในrequest body ในrouteที่ต้องการreturn url โดยควรกำหนดตัวกลางใน.env หรือดึงค่าจาก.env จะตั้งตัวแปรแบบใดตามศรัทธา | http://localhost:5001/booking/TK-260101-ABCDE |
| URL ที่ Postman เรียก | collection variables baseUrl (PayMock), concertUrl (Concert) | http://localhost:8082; http://localhost:5001 |

# 2. สร้างรายการชำระเงิน

`POST /payments` — สร้าง payment (PromptPay / บัตรเครดิต)

Endpoint เดียวใช้สร้างทั้งสองช่องทาง เลือกช่องทางด้วยฟิลด์ `method` ส่วนอื่น ๆ ของ request/response ใช้ร่วมกัน

## 2.1 Request Body Fields

| Field | Type | Required | ค่าที่ยอมรับ | คำอธิบาย |
| --- | --- | --- | --- | --- |
| `amount` | number | จำเป็น | min: 1 | ยอดเงิน (บาท, ทศนิยมได้) |
| `method` | string | จำเป็น | "card" หรือ "promptpay" | วิธีชำระเงิน |
| `callback_url` | string | จำเป็น | URL ที่ถูกต้อง | URL รับ Webhook จาก PayMock |
| `return_url` | string | ไม่จำเป็น | URL ที่ถูกต้อง | Gateway redirect กลับหลัง submit บัตร |
| `expires_in` | integer | ไม่จำเป็น | min: 60 (วินาที) | อายุก่อนหมดเวลา ใช้ได้ทั้งบัตรและ PromptPay (default: 600) |
| `currency` | string | ไม่จำเป็น | 3 ตัวอักษร | สกุลเงิน (default: "THB") |
| `metadata` | JSON value | ไม่จำเป็น | JSON value ใดก็ได้ | ข้อมูลเพิ่มเติม; Gateway ไม่ตรวจและส่งกลับตามที่รับมา |

## 2.2 Response Fields

| Field | Type | ความหมาย |
| --- | --- | --- |
| `id` | string (txn_...) | Provider transaction ID ใช้ใน path ของ GET / cancel / QR / checkout และ deduplicate webhook |
| `metadata` | JSON value | Optional; ข้อมูลจาก request โดยไม่ตรวจหรือแก้ไข |
| `status` | string | PENDING / COMPLETED / FAILED / EXPIRED |
| `failure_code` | string \| null | รหัสเหตุผลเมื่อล้มเหลว (ดูตาราง Failure Codes) |
| `checkout_url` | string (Card) | URL Hosted Checkout; ส่งคืนเฉพาะเมื่อ method=card |
| `qrcode_url` | string (PromptPay) | URL รูปภาพ SVG QR Code; ส่งคืนเฉพาะเมื่อ method=promptpay |
| `bank_simulator_url` | string (PromptPay) | URL หน้าจำลอง Mobile Banking; ส่งคืนเฉพาะเมื่อ method=promptpay |
| `expires_at` | ISO 8601 \| null | เวลาหมดอายุ = created_at + expires_in (นาฬิกาเซิร์ฟเวอร์) |
| `paid_at` | ISO 8601 \| null | เวลาจ่ายสำเร็จจริง (เฉพาะ COMPLETED) — ไม่มีฟิลด์ completed_at |
| `created_at` | ISO 8601 | เวลาสร้างรายการจริง (timezone Asia/Bangkok) |

## 2.3 ตัวอย่าง: PromptPay

* **Auth:** Bearer Token = `pm_test_key_concert_system_2024`
* **Headers:** (ไม่ต้องส่ง header เพิ่มเติม นอกจาก `Authorization` และ `Content-Type: application/json`)

**Request Body:**
```json
{
  "amount":       1200.00,
  "method":       "promptpay",
  "callback_url": "http://localhost:8080/webhooks/paymock",
  "expires_in":   600,
  "currency":     "THB",
  "metadata":     { "source": "concert-app", "context": { "campaign": "summer" } }
}
```

**Response Body:**
```json
{
  "id":                 "txn_01jcxyz1234567890",
  "amount":             1200.00,
  "currency":           "THB",
  "method":             "promptpay",
  "status":             "PENDING",
  "failure_code":       null,
  "expires_at":         "2026-10-13T10:10:00+07:00",
  "paid_at":            null,
  "created_at":         "2026-10-13T10:00:00+07:00",
  "qrcode_url":         "http://localhost:8081/payments/01JCXYZ.../qr.svg",
  "bank_simulator_url": "http://localhost:8081/bank-simulate/txn_01jcxyz1234567890",
  "metadata": { "source": "concert-app", "context": { "campaign": "summer" } }
}
```

## 2.4 ตัวอย่าง: บัตรเครดิต

* **Auth:** Bearer Token = `pm_test_key_concert_system_2024`
* **Headers:**
  * `Content-Type: application/json`
  * `Idempotency-Key: 405`

**Request Body:**
```json
{
  "amount":       1200.00,
  "method":       "card",
  "callback_url": "http://localhost:8080/webhooks/paymock",
  "return_url":   "http://localhost:5000/booking/TK-260101-ABCDE",
  "expires_in":   600,
  "metadata":     { "source": "concert-app", "context": { "campaign": "summer" } }
}
```

**Response Body:**
```json
{
  "id":          "txn_01jcxyz1234567890",
  "amount":      1200.00,
  "currency":    "THB",
  "method":      "card",
  "status":      "PENDING",
  "failure_code": null,
  "expires_at":  "2026-10-13T10:10:00+07:00",
  "paid_at":     null,
  "created_at":  "2026-10-13T10:00:00+07:00",
  "checkout_url":"http://localhost:8081/checkout/txn_01jcxyz1234567890",
  "return_url":  "http://localhost:5000/booking/TK-260101-ABCDE",
  "metadata": { "source": "concert-app", "context": { "campaign": "summer" } }
}
```

## 2.5 Idempotency

`Idempotency-Key` header เป็นวิธีเดียวที่ใช้ deduplicate POST; ไม่มีการอ่าน idempotency_key จาก JSON body หรือ metadata. payment_id และ booking_ref เป็น merchant references เท่านั้น. Idempotency-Key ใช้ภายในและไม่ส่งกลับใน response หรือ webhook. หากไม่ส่ง header ทุก POST จะสร้างรายการใหม่; หากส่ง header ให้ใช้ค่าเดิมเมื่อ retry attempt เดิม. Concert สร้าง booking_ref ก่อนเริ่มชำระและคงเดิมแม้จ่ายซ้ำ; เมื่อ FAILED/EXPIRED ให้สร้าง payment attempt ใหม่พร้อม payment_id/key ใหม่.

# 3. Endpoints อื่น ๆ

## 3.1 API สำหรับ Backend ระบบคอนเสิร์ต

### `GET /payments` — Health check

| หัวข้อ | รายละเอียด |
|---|---|
| **Auth** | Bearer Token = pm_test_key_concert_system_2024 |
| **ใช้เมื่อ** | ตรวจ health และรายการล่าสุด |
| **Response** | 200 JSON: service, status, chaos_mode, webhook_mode, recent[] |
| **Error** | 401 token ผิด; 429 / 500 / 502 / 503 เมื่อ Chaos Mode |

### `GET /payments/{publicId}` — ตรวจสถานะ (poll / reconcile)

* **Auth:** Bearer Token = `pm_test_key_concert_system_2024`
* **Headers:** — (ไม่มี)
* **Request Body:** — (ไม่มี)

**Response Body:**
```json
{
    "id": "txn_01m5pa0jrebhspq6gfn55ac0rg",
    "amount": 2200,
    "currency": "THB",
    "method": "promptpay",
    "status": "FAILED",
    "failure_code": "cancelled",
    "expires_at": "2026-10-24T06:46:46+00:00",
    "paid_at": null,
    "created_at": "2026-10-24T06:36:46+00:00",
    "return_url": null,
    "metadata": {
        "source": "postman",
        "context": {
            "attempt": "202"
        }
    },
    "qrcode_url": "http://localhost:8081/payments/txn_01m5pa0jrebhspq6gfn55ac0rg/qr.svg",
    "bank_simulator_url": "http://localhost:8081/bank-simulate/txn_01m5pa0jrebhspq6gfn55ac0rg"
}
```

| HTTP | สาเหตุ |
|---|---|
| `401` | API Key ผิด |
| `404` | ไม่พบ Payment ID นี้ |
| `503` | gateway อยู่ในโหมด down |

### `POST /payments/{publicId}/cancel` — ยกเลิกรายการที่ยัง PENDING

* **Auth:** Bearer Token = `pm_test_key_concert_system_2024`
* **Headers:** ไม่ต้องส่ง header เพิ่มเติม (นอกจาก Authorization)
* **Request Body:** — (ไม่มี)

**Response Body:**
```json
{
  "ok": true,
  "status": "FAILED",
  "failure_code": "cancelled"
}
```

* **Error:** 401(API Key ผิด), 404(ไม่พบ Payment ID), 409 (รายการ settle แล้ว),code error จาก chaos

---

## 3.2 หน้า UI จำลอง

Endpoint กลุ่มนี้ตอบกลับเป็นหน้าเว็บ (HTML) สำหรับใช้ทดสอบ flow การจ่ายเงินในเบราว์เซอร์

### `GET /checkout/{publicId}` — หน้าชำระเงินด้วยบัตรเครดิต

| หัวข้อ | รายละเอียด |
|---|---|
| **Headers** | ไม่ต้องส่ง header เพิ่มเติม (นอกจาก Authorization) |
| **Response** | ตอบกลับเป็นหน้าชำระเงินโดยบัตรเครดิต (ใช้เฉพาะ method = card); ถ้า settle หรือหมดอายุ แสดง receipt HTML; 404 ถ้าไม่พบ |

### `GET /bank-simulate/{publicId}` — แอปธนาคารจำลอง (PromptPay)

| หัวข้อ | รายละเอียด |
|---|---|
| **Headers** | ไม่ต้องส่ง header เพิ่มเติม (นอกจาก Authorization) |
| **Response** | แสดงหน้าจำลองธนาคาร (mobile banking) สำหรับจ่ายเงิน payment นั้น ๆ; 404 ถ้าไม่พบ |

# 4. สถานะ รหัสผิดพลาด และ Error Format

## 4.1 สถานะ Payment

| status | ความหมาย |
| --- | --- |
| `PENDING` | รอชำระเงิน |
| `COMPLETED` | จ่ายสำเร็จ |
| `FAILED` | ล้มเหลว / ถูกปฏิเสธ / ยกเลิก |
| `EXPIRED` | หมดเวลาก่อนจ่าย |

## 4.2 Failure Codes

| failure_code | ความหมาย | ระบบควรแสดงอะไร |
| --- | --- | --- |
| `card_declined` | บัตรถูกปฏิเสธ | บัตรถูกปฏิเสธ กรุณาลองบัตรอื่น |
| `insufficient_funds` | วงเงินไม่พอ | วงเงินในบัตรไม่เพียงพอ |
| `expired_card` | บัตรหมดอายุ | บัตรหมดอายุ |
| `incorrect_cvc` | CVC ผิด | รหัส CVC ไม่ถูกต้อง |
| `processing_error` | ข้อผิดพลาดจาก gateway | ระบบขัดข้องชั่วคราว ลองอีกครั้ง |
| `fraud_suspected` | สงสัย fraud | บันทึก Anomaly แจ้ง Admin |
| `cancelled` | ยกเลิกโดยผู้ใช้ / ระบบ | ยกเลิก booking |
| `payment_expired` | หมดเวลา | เหมือน EXPIRED |

## 4.3 HTTP Error Responses

| HTTP | error | สาเหตุ |
| --- | --- | --- |
| `401` | `unauthorized` | API Key ผิดหรือไม่มี |
| `409` | `cannot_cancel` | ยกเลิกไม่ได้ เพราะรายการ settle แล้ว (cancel เฉพาะ PENDING) |
| `422` | `validation_error` | ข้อมูลที่ส่งไม่ถูกต้อง (ดูฟิลด์ details) |
| `429` | `too_many_requests` | Chaos mode = rate_limit (ดู Retry-After header) |
| `500` | `internal_error` | ข้อผิดพลาดภายใน gateway |
| `503` | `service_unavailable` | gateway อยู่ในโหมด down |

## 4.4 ตัวอย่าง Error Response

Error response ทุกชนิดมี format เดียวกัน:

**401 Unauthorized**
```json
{ "error": "unauthorized", "message": "Invalid or missing API key." }
```

**409 Conflict**
```json
{ "error": "cannot_cancel", "message": "Payment is already settled." }
```

**422 Validation Error**
```json
{
  "error": "validation_error",
  "details": {
    "amount": ["The amount field is required."],
    "method": ["The selected method is invalid."]
  }
}
```

**429 Rate Limited**
```http
HTTP/1.1 429 Too Many Requests
Retry-After: 30
 
{ "error": "too_many_requests", "message": "Too many requests. Please retry after 30 seconds." }
```

**503 Service Unavailable**
```json
{ "error": "service_unavailable", "message": "Gateway มีปัญหา โปรดลองอีกครั้ง" }
```

# 5. Webhook — สิ่งที่ PayMock ส่งมาหาคุณ <a id="5-webhook-สิ่งที่-paymock-ส่งมาหาคุณ"></a><a id="5-webhook--สิ่งที่-paymock-ส่งมาหาคุณ"></a>

เมื่อสถานะ payment เปลี่ยน PayMock จะ POST ไปที่ `callback_url` ที่คุณให้ไว้ ไม่มี fixed 10-second HTTP timeout; หาก delivery ล้มเหลว queue จะ retry ตาม backoff schedule (ดูหัวข้อ 5.6)

## 5.1 Headers ที่ PayMock ส่งมา

```http
POST {callback_url} HTTP/1.1
Content-Type: application/json
X-Signature: t=1728810332,v1=a3f9c2b1d8e4f7a0b5c6d9e2f1a4b7c0d3e6f9a2b5c8d1e4f7a0b3c6d9e2f5
X-Event-Id: 01JCABC9876543210ZYXWVUTS
```

| Header | ความหมาย |
| --- | --- |
| `Content-Type` | application/json เสมอ |
| `X-Signature` | ลายเซ็น HMAC-SHA256 ใช้ยืนยันว่า webhook มาจาก PayMock จริง |
| `X-Event-Id` | ULID ไม่ซ้ำของแต่ละ delivery ใช้ป้องกัน duplicate (idempotency) |

## 5.2 Webhook Body

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
  "metadata": { "source": "concert-app", "context": { "campaign": "summer" } }
}
```

| Field | Type | ความหมาย |
| --- | --- | --- |
| `id` | string (txn_...) | Provider transaction ID ใช้เรียก GET และ deduplicate สถานะ |
| `event` | string | ชื่อ event: payment.completed / payment.failed / payment.expired |
| `metadata` | JSON value | Optional; metadata จาก request โดยไม่ตรวจหรือแก้ไข |
| `amount` | float | ยอดเงินที่ชำระ |
| `currency` | string | สกุลเงิน เช่น THB |
| `method` | string | ช่องทางชำระเงิน: promptpay หรือ card |
| `status` | string | สถานะรายการ: COMPLETED / FAILED / EXPIRED |
| `failure_code` | string \| null | รหัสเหตุผลความล้มเหลว (มีเฉพาะสถานะ FAILED เช่น card_declined, insufficient_funds) |
| `paid_at` | ISO 8601 \| null | เวลาที่ชำระเงินสำเร็จ (มีเฉพาะสถานะ COMPLETED) |
| `created_at` | ISO 8601 | เวลาที่สร้างรายการชำระเงิน |

## 5.3 Webhook Events

| event | เมื่อไหร่ | status ใน body |
| --- | --- | --- |
| `payment.completed` | จ่ายสำเร็จ | `COMPLETED` |
| `payment.failed` | ถูกปฏิเสธ / ยกเลิก | `FAILED` |
| `payment.expired` | หมดเวลาก่อนจ่าย | `EXPIRED` |

## 5.4 การตรวจ Signature (สำคัญมาก)

ต้องตรวจก่อน process webhook ทุกครั้ง เพื่อป้องกัน Replay Attack และ Forgery

### ใครตรวจ และใครปฏิเสธ
* **Gateway ส่งข้อมูลและลายเซ็น:** PayMock ส่ง Header `X-Signature: t={timestamp},v1={hmac}` มาพร้อมกับ Webhook Payload เสมอ
* **Backend เป็นผู้ตรวจสอบลายเซ็น:** ระบบคอนเสิร์ต (Backend ของคุณ) มีหน้าที่คำนวณ HMAC-SHA256 ด้วยตัวเอง โดยใช้ Webhook Secret ที่เก็บไว้เป็นความลับ
* **กรณี Invalid Signature (ลายเซ็นปลอม):** Gateway ไม่ได้เป็นคนบอกว่า "ไม่ผ่าน" แต่ผู้ไม่ประสงค์ดีอาจส่ง Body ที่อ้างว่า status: COMPLETED เข้ามาหลอก! ฝั่ง Backend เมื่อตรวจพบว่า HMAC ไม่ตรง ต้อง "ปฏิเสธไม่ออกตั๋ว" และ "ตอบกลับ HTTP 401 Unauthorized" ด้วยตัวเอง

### ขั้นตอนการตรวจสอบในโค้ดของระบบคอนเสิร์ต
1. อ่าน `X-Signature` header แล้วแยก `t` (timestamp) และ `v1` (signature hex)
2. คำนวณ HMAC-SHA256 ด้วย raw request body (ก่อน decode JSON) ด้วย secret key ของคุณ
3. เปรียบเทียบด้วย `hash_equals()` (constant-time comparison) เพื่อป้องกัน Timing Attack
4. หากลายเซ็นถูกต้อง อัปเดตสถานะตั๋วและตอบ HTTP 200 — หากไม่ถูกต้อง ปฏิเสธไม่ออกตั๋วและตอบ HTTP 401

## 5.5 ตัวอย่างโค้ด PHP (ฝั่งระบบคอนเสิร์ต)

```php
public function handleWebhook(Request $request): Response
{
    // 1. แยก signature header
    $sigHeader = $request->header('X-Signature', '');
    preg_match('/t=(\d+),v1=([a-f0-9]+)/', $sigHeader, $m);
    [$timestamp, $signature] = [$m[1] ?? 0, $m[2] ?? ''];
 
    // 3. คำนวณและตรวจ HMAC
    $secret   = config('services.paymock.webhook_secret');
    $rawBody  = $request->getContent();
    $expected = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);
    if (! hash_equals($expected, $signature)) {
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
 
    return response('OK', 200); // ต้องตอบ 2xx ไม่เช่นนั้น PayMock จะ retry
}
```

## 5.6 Retry Policy ของ Payment Gateway

PayMock จะ retry webhook ถ้าได้รับ response ที่ไม่ใช่ 2xx หรือ connection timeout

| ครั้งที่ | หน่วงก่อน retry (วินาที) |
| --- | --- |
| retry 1 | 5 |
| retry 2 | 30 |
| retry 3 | 120 |
| retry 4 | 300 |
| retry 5 | 600 |
| สูงสุด | 6 ครั้ง |

## 5.7 การหมดอายุของ QR Code (payment.expired)

เมื่อสร้าง payment จะตั้ง status=PENDING และ expires_at ตาม expires_in (หน่วยวินาที; default 600 วินาที/10 นาที). paymock_scheduler รัน Laravel schedule:work และเรียก payments:expire ทุก 10 วินาที (ประมาณ 0.17 นาที). แต่ละรอบเลือกเฉพาะ PENDING ที่ expires_at < เวลาปัจจุบัน แล้วใช้ PaymentService เปลี่ยนเป็น EXPIRED; failure_code และ paid_at เป็น null. รายการที่จ่ายหรือยกเลิกแล้วจะไม่ถูกเปลี่ยนซ้ำ. จากนั้น PayMock สร้าง webhook payment.expired และส่งงานให้ paymock_queue เพื่อ POST ไป callback_url; หาก callback ตอบ non-2xx หรือเชื่อมต่อไม่ได้ queue จะ retry ตาม backoff 5, 30, 120, 300 และ 600 วินาที (สูงสุด 6 attempts).

ระบบคอนเสิร์ตตรวจสถานะล่าสุดได้ด้วย `GET /payments/{id}`; endpoint นี้อ่านสถานะที่บันทึกไว้และไม่สั่ง expire เอง. หาก expires_at ผ่านแล้วแต่ scheduler ยังไม่ถึงรอบ รายการอาจยังเป็น PENDING ชั่วคราว โดยปกติไม่เกินประมาณ 10 วินาทีเมื่อ scheduler ทำงาน.

เมื่อ QR Code หรือรายการที่รอชำระเงินหมดเวลา (เลย expires_at) PayMock จะส่ง Webhook event `payment.expired` ไปยัง `callback_url` ของคุณทันที เพื่อแจ้งให้ระบบคอนเสิร์ตปลดล็อกที่นั่ง

**สรุปหน้าที่:** `paymock_scheduler` ตรวจและเปลี่ยนสถานะหมดอายุทุก 10 วินาที; `paymock_queue` ส่ง webhook และ retry.

### สิ่งที่ระบบคอนเสิร์ตต้องทำเมื่อได้รับ payment.expired

| ขั้นตอน | ตารางในฐานข้อมูล | การเปลี่ยนแปลง |
| --- | --- | --- |
| 1. ยกเลิกการจองตั๋ว | `booking` | อัปเดต booking_status = "EXPIRED" |
| 2. เปลี่ยนสถานะรายการชำระเงิน | `payment` | อัปเดต payment_status เป็น "EXPIRED" |
| 3. ปลดล็อกที่นั่ง | `seat` | อัปเดต s_seat_booking_status = "AVAILABLE" |
| 4. คืนสต็อกโซนยืน | `zone_inventory` | ลด zi_held ลงตามจำนวนตั๋ว |
| 5. บันทึกประวัติการปลดล็อก | `seat_lock_log` | บันทึก sll_released_at = NOW() |

# 6. Flow ฝั่งระบบคอนเสิร์ต

## 6.1 Retry หลัง Timeout

1. ส่ง `POST /payments` พร้อม `Idempotency-Key` header เช่น `attempt-b_TK2024001`; `metadata` เป็น optional และใช้ส่งข้อมูลเพิ่มเติมได้
2. หมดเวลา (timeout) ไม่ได้ response
3. ส่ง `POST /payments` อีกครั้งด้วย `Idempotency-Key` header เดิม
4. PayMock คืน payment เดิม (ไม่สร้างซ้ำ)
5. นำ `qrcode_url` หรือผลลัพธ์เดิมไปแสดงผลต่อได้ทันที

หากไม่ส่ง `Idempotency-Key` จะไม่มี key ที่คงเดิมสำหรับ retry; gateway จะสร้าง payment ใหม่.

## 6.2 Reconcile (Webhook หาย)

Reconcile ทำฝั่งระบบคอนเสิร์ตเพื่ออ่านสถานะจริงจาก PayMock (`GET /payments/{id}`) และอัปเดตฐานข้อมูลของคอนเสิร์ตเมื่อ webhook หายหรือมาช้า

## 6.3 Scheduler

Scheduler ของระบบคอนเสิร์ตควรรัน **ทุก 5 นาที** เพื่อตรวจ payment ที่ยัง PENDING นานกว่า `expires_at`:

| สถานะที่ได้จาก PayMock | สิ่งที่ต้องทำ |
| --- | --- |
| `COMPLETED` | process เหมือนได้รับ webhook |
| `EXPIRED` | ยกเลิก booking และปลดล็อกที่นั่ง |

# 7. การทดสอบด้วย PayMock Admin

ระบบคอนเสิร์ตเรียกแค่ API จ่ายเงินจริง: สร้างรายการ, ใส่เลขบัตร, ยกเลิก, GET สถานะด้วย payment_id ความผิดปกติทั้งหมดตั้งที่ `http://localhost:8081/admin` — ไม่ส่ง scenario / `X-Mock-Scenario` จากฝั่ง merchant

## 7.1 ลำดับชั้นการตัดสินใจของ PayMock

| ชั้น | Setting | เมื่อไหร่มีผล |
| --- | --- | --- |
| 1 (ใหญ่สุด) | Chaos Mode | คำขอเข้า /payments/* และ /checkout/* (ล่ม, ช้า, 429, 500) |
| 2 (รอง) | Webhook Mode | ตอนยิง webhook หลัง settle (lost, delay, dup, bad_sig, replay) |

รายละเอียด Execution Hierarchy แบ่งเป็น 4 ชั้นอย่างเด็ดขาด:
* **Layer 1 — Chaos (ใหญ่สุด):** Admin `chaos_mode` ครอบคำขอเข้า `/payments/*` และ checkout ด้วย 503, slow หรือ rate limit
* **Layer 2 — Request & Idempotency:** ใช้ `Idempotency-Key` header ถ้ามี
* **Layer 3 — การชำระเงิน:** Magic Card / PromptPay confirm / cancel — ระบบคอนเสิร์ตอ้างอิงด้วย `payment_id`
* **Layer 4 — Webhook (รอง):** Admin `webhook_mode` แบบ global — lost / delay / dup / bad_sig / replay

**ลำดับความสำคัญ:** Chaos Mode (inbound) > เลขบัตรวิเศษ (ผลจ่าย) > Webhook Mode (outbound)

## 7.2 Chaos Mode (ชั้น 1 — ตั้งจาก Admin Panel)

| mode | พฤติกรรม | ระบบคอนเสิร์ตควรทำ |
| --- | --- | --- |
| `normal` | ทำงานปกติ | — |
| `down` | ตอบ 503 ทุก request | แจ้ง "Gateway มีปัญหา โปรดลองอีกครั้ง" |
| `slow:N` | หน่วง N วินาที | แสดง loading ไม่กดซ้ำ |
| `rate_limit` | ตอบ 429 + Retry-After: 30 | รอแล้วค่อยลองใหม่ |

## 7.3 Webhook Mode (ชั้น 2 — ตั้งจาก Admin)

Webhook Mode เป็นค่า **global** เดียวจาก Admin และมีผลกับทุกรายการ ไม่มี override ต่อ payment ระบบคอนเสิร์ตไม่ต้องส่ง header หรือ scenario ใด ๆ

| Scenario | สิ่งที่ Gateway ส่งมา | สิ่งที่ Backend ต้องตอบ | ผลต่อตั๋วคอนเสิร์ต | การรับมือ / วัตถุประสงค์ |
| --- | --- | --- | --- | --- |
| `normal` | ส่ง Webhook ทันทีพร้อม Signature ถูกต้อง | 200 OK | ออกตั๋วสำเร็จ (PAID) | กรณีการทำงานตามปกติ |
| `webhook_lost` | ไม่ส่ง Webhook เลย (Packet Drop) | — (ไม่ได้รับ) | ค้าง PENDING | ทดสอบ Fallback: ต้องมีปุ่มหรือ Cron ยิง GET /payments/{id} เพื่อ Reconcile สถานะ |
| `webhook_delay:N` | หน่วงเวลา N วินาทีก่อนส่ง Webhook | 200 OK | PENDING → PAID หลัง N วิ (เมื่อมาถึง) | ทดสอบว่าหน้าเว็บรองรับช่วงรอยต่อระหว่างรอ Webhook หรือไม่ |
| `webhook_dup:N` | ส่ง Webhook ซ้ำกัน N รอบ (Payload เดิม) | 200 OK ทุกรอบ | ออกตั๋ว 1 ใบเท่านั้น (PAID) | ทดสอบ Idempotency: รอบที่ 2–N ต้องตอบ 200 แต่ไม่ออกตั๋วซ้ำเด็ดขาด |
| `webhook_bad_sig` | ส่ง Body อ้างว่า COMPLETED แต่ลายเซ็นปลอม (0000...) | 401 Unauthorized | ปฏิเสธ ไม่ออกตั๋ว (REJECTED) | ทดสอบ Security: ป้องกัน Hacker ส่ง Webhook ปลอมมาหลอกออกตั๋ว |

### รายละเอียดเชิงลึกของแต่ละ Scenario

#### 1) `webhook_lost` — Webhook สูญหายระหว่างทาง
* **พฤติกรรม:** Gateway ตัดเงินสำเร็จจริง (ในฐานข้อมูลของ PayMock เป็น COMPLETED) แต่เกิดปัญหาเน็ตหลุด ทำให้ Webhook ไปไม่ถึง Backend ของคุณ
* **ผลกระทบ:** ฝั่งคอนเสิร์ตไม่ได้รับ Webhook ทำให้ตั๋วยังคงค้างสถานะ PENDING
* **วิธีรับมือที่ถูกต้อง:** ระบบคอนเสิร์ตต้องมีปุ่ม "ตรวจสอบสถานะกับ Gateway" หรือตั้ง Scheduler คอยเรียก `GET /payments/{payment_id}` เพื่อนำสถานะจริงมา Reconcile

#### 2) `webhook_delay:N` — หน่วงเวลาส่ง Webhook
* **พฤติกรรม:** รายการถูกตัดเงินสำเร็จทันที แต่ Gateway จะหน่วงเวลาก่อนส่ง Webhook ออกไปเป็นเวลา N วินาที
* **สิ่งที่ Backend ต้องทำ:** ในระหว่างที่ยังไม่ได้รับ Webhook ตั๋วต้องค้าง PENDING และเมื่อ Webhook มาถึงจึงเปลี่ยนเป็น PAID อย่างราบรื่น

#### 3) `webhook_dup:N` — ส่ง Webhook ซ้ำหลายครั้ง
* **พฤติกรรม:** PayMock จะยิง Webhook ข้อมูลเดิมเข้ามาติดกัน N ครั้ง (เช่น 3 ครั้ง)
* **สิ่งที่ Backend ต้องทำ:** ตรวจสอบ `X-Event-Id` หรือ payment_id (`payload.id`) หากเคยประมวลผลไปแล้วในครั้งแรก ครั้งต่อไปต้องตอบ HTTP 200 OK ทันทีโดยไม่รันคำสั่งออกตั๋วซ้ำ

#### 4) `webhook_bad_sig` — ลายเซ็นปลอม (Security Attack)
* **พฤติกรรม:** Payload ใน Body อ้างว่า `"status": "COMPLETED"` แต่ Header `X-Signature` ถูกส่งเป็น `v1=00000000000000000000...` (จำลอง Hacker แกล้งส่งข้อมูลมาหลอก)
* **การตรวจสอบ:** Backend ต้องนำ Secret Key มาคำนวณ HMAC-SHA256 ด้วยตัวเอง เมื่อพบว่าไม่ตรงกัน ต้องสั่ง "ไม่ให้ผ่าน"
* **สิ่งที่ Backend ต้องทำ:** ตอบกลับ HTTP 401 Unauthorized ทันที และ **ห้ามอัปเดตสถานะตั๋วเป็น PAID โดยเด็ดขาด**

## 7.4 Magic Card Numbers

ใช้ที่หน้า Checkout เมื่อเลือก method = "card" — CVC และวันหมดอายุใส่อะไรก็ได้ **(ยกเว้นบัตร ...0069)**

| หมายเลขบัตร | ผล | failure_code | ระบบควรทำ |
| --- | --- | --- | --- |
| `4242 4242 4242 4242` | COMPLETED | — | ออกตั๋ว seat → SOLD |
| `4000 0000 0000 0002` | FAILED | `card_declined` | แจ้งผู้ใช้ ให้ลองบัตรอื่น |
| `4000 0000 0000 9995` | FAILED | `insufficient_funds` | แจ้งวงเงินไม่พอ |
| `4000 0000 0000 0069` | FAILED | `expired_card` | แจ้งบัตรหมดอายุ |
| `4000 0000 0000 0127` | FAILED | `incorrect_cvc` | แจ้ง CVC ผิด |
| `4000 0000 0000 0119` | FAILED | `processing_error` | แจ้งระบบขัดข้อง ลองใหม่ |
| `4100 0000 0000 0019` | FAILED | `fraud_suspected` | บันทึก Anomaly แจ้ง Admin |
| `4000 0000 0000 0341` | COMPLETED | — | ผลเหมือนบัตรสำเร็จ; การหน่วง webhook ตั้งจาก Admin |
