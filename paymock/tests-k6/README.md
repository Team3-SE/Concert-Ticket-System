# k6 Load Testing – Concurrent Booking under Slow Gateway

สคริปต์นี้ใช้ทดสอบการแย่งที่นั่ง (Race Condition) พร้อมกัน 10 คน ขณะที่ Payment Gateway กำลังจำลองความช้า (`slow:3`)

---

## 🏃 วิธีการรัน

### วิธีที่ 1: รันผ่าน Docker (ไม่ต้องติดตั้ง k6 ลงเครื่อง)
```bash
# บน Windows PowerShell
Get-Content paymock/tests-k6/seat-race-condition.js | docker run --rm -i --network=host grafana/k6 run -
```

หรือระบุ URL ข้าม Container:
```bash
docker run --rm -i -e PAYMOCK_URL=http://host.docker.internal:8081 grafana/k6 run - < paymock/tests-k6/seat-race-condition.js
```

### วิธีที่ 2: รันด้วยคำสั่ง k6 โดยตรง (ถ้าติดตั้ง k6 ไว้)
```bash
k6 run paymock/tests-k6/seat-race-condition.js
```

---

## 🎯 สิ่งที่สคริปต์นี้ตรวจสอบ
1. **Concurrency Handling:** ยิงพร้อมกัน 10 VUs ไปยัง Order / Idempotency-Key เดียวกัน
2. **Slow Scenario (`slow:3`):** วัด response time ว่า Gateway หน่วงเวลาจริงไม่ต่ำกว่า 3 วินาที
3. **Idempotency Guarantee:** ตรวจสอบว่าทุกคำขอที่สำเร็จจะได้รับ `payment_id` เดียวกันเท่านั้น ไม่เกิดรายการจ่ายเงินซ้ำซ้อนในระบบ
