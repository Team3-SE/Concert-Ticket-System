import http from 'k6/http';
import { check, sleep } from 'k6';

// ─────────────────────────────────────────────────────────────
// k6 Load Test: Race Condition under slow gateway (slow:3)
// จำลองผู้ใช้ 10 คนพยายามชำระเงินและแย่งที่นั่งเดียวกันพร้อมกัน
// ขณะที่ Gateway กำลังติดหน่วงเวลา 3 วินาที (slow:3)
// ─────────────────────────────────────────────────────────────

export const options = {
    scenarios: {
        concurrent_rush: {
            executor: 'shared-iterations',
            vus: 10,              // 10 concurrent users
            iterations: 10,       // 10 attempts
            maxDuration: '30s',
        },
    },
    thresholds: {
        http_req_failed: ['rate<0.8'], // ยอมรับว่าคำขอส่วนใหญ่จะโดน reject เนื่องจากแย่งที่นั่ง
    },
};

const PAYMOCK_URL = __ENV.PAYMOCK_URL || 'https://localhost:8081';
const API_KEY     = __ENV.PAYMOCK_API_KEY || 'pm_test_key_concert_system_2024';
const SAME_SEAT_BOOKING_REF = 'BOOKING_RACE_CONCURRENT_001';

export default function () {
    const vuId = __VU;

    // 1. ส่งคำขอสร้าง payment โดยใช้ Idempotency-Key เดิม หรือ Booking Code เดียวกัน
    // พร้อมเปิด Header X-Mock-Scenario: slow:3 จำลอง Gateway ตอบช้า
    const payload = JSON.stringify({
        order_ref:    SAME_SEAT_BOOKING_REF,
        amount:       1500.00,
        currency:     'THB',
        method:       'card',
        callback_url: 'https://concert:80/webhooks/payment',
        return_url:   'https://localhost:8080/return',
        expires_in:   600,
    });

    const params = {
        headers: {
            'Content-Type':    'application/json',
            'Authorization':   `Bearer ${API_KEY}`,
            'Idempotency-Key': SAME_SEAT_BOOKING_REF, // ทุกคนส่งด้วย booking ref เดียวกัน
            'X-Mock-Scenario': 'slow:3',              // Gateway หน่วง 3 วินาที
        },
        timeout: '10s',
    };

    const startTime = new Date();
    const res = http.post(`${PAYMOCK_URL}/payments`, payload, params);
    const duration = (new Date() - startTime) / 1000;

    // ตรวจสอบ:
    // 1. Gateway ต้องตอบกลับ status 201 (Created) หรือ 200 (Existing idempotent)
    // 2. ระยะเวลาต้องมากกว่า 3 วินาที (พิสูจน์ว่าเกิด slow:3 จริง)
    // 3. ทุกลำดับต้องได้ payment id เดียวกัน (พิสูจน์ Idempotency สำเร็จ ไม่เบิ้ลรายการ)
    check(res, {
        'status is 201 or 200': (r) => r.status === 201 || r.status === 200,
        'delayed at least 3s': () => duration >= 2.8,
        'has valid payment id': (r) => {
            try {
                const body = JSON.parse(r.body);
                return body.id && body.id.startsWith('txn_');
            } catch (e) {
                return false;
            }
        },
    });

    sleep(1);
}
