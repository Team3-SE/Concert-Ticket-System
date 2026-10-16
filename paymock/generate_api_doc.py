"""
สร้างเอกสาร API Reference ของ PayMock ในรูปแบบ Word (.docx)
"""
from docx import Document
from docx.shared import Pt, RGBColor, Cm, Inches
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml.ns import qn
from docx.oxml import OxmlElement
import copy

doc = Document()

# ─── Page Margins ───
section = doc.sections[0]
section.page_width  = Cm(21)
section.page_height = Cm(29.7)
section.top_margin    = Cm(2.5)
section.bottom_margin = Cm(2.5)
section.left_margin   = Cm(2.5)
section.right_margin  = Cm(2.5)

# ─── Helper: set paragraph shading ───
def shade_paragraph(paragraph, hex_color):
    pPr = paragraph._p.get_or_add_pPr()
    shd = OxmlElement('w:shd')
    shd.set(qn('w:val'), 'clear')
    shd.set(qn('w:color'), 'auto')
    shd.set(qn('w:fill'), hex_color)
    pPr.append(shd)

# ─── Helper: set cell shading ───
def shade_cell(cell, hex_color):
    tc   = cell._tc
    tcPr = tc.get_or_add_tcPr()
    shd  = OxmlElement('w:shd')
    shd.set(qn('w:val'),   'clear')
    shd.set(qn('w:color'), 'auto')
    shd.set(qn('w:fill'),  hex_color)
    tcPr.append(shd)

# ─── Helper: set cell border ───
def set_table_borders(table):
    tbl  = table._tbl
    tblPr = tbl.find(qn('w:tblPr'))
    if tblPr is None:
        tblPr = OxmlElement('w:tblPr')
        tbl.insert(0, tblPr)
    tblBorders = OxmlElement('w:tblBorders')
    for side in ('top','left','bottom','right','insideH','insideV'):
        el = OxmlElement(f'w:{side}')
        el.set(qn('w:val'),  'single')
        el.set(qn('w:sz'),   '4')
        el.set(qn('w:space'),'0')
        el.set(qn('w:color'),'D1D5DB')
        tblBorders.append(el)
    tblPr.append(tblBorders)

# ─── Helper: add a heading ───
def add_heading(doc, text, level=1, color="1E3A8A"):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(14 if level==1 else 8)
    p.paragraph_format.space_after  = Pt(4)
    run = p.add_run(text)
    run.bold = True
    run.font.size = Pt({1:18, 2:14, 3:12, 4:11}.get(level, 11))
    run.font.color.rgb = RGBColor.from_string(color)
    if level == 1:
        p.paragraph_format.border_bottom = None
    return p

# ─── Helper: add body paragraph ───
def add_para(doc, text="", bold=False, size=10, color="111827", indent=0, space_after=4):
    p = doc.add_paragraph()
    p.paragraph_format.space_after  = Pt(space_after)
    p.paragraph_format.space_before = Pt(2)
    if indent:
        p.paragraph_format.left_indent = Cm(indent)
    if text:
        run = p.add_run(text)
        run.bold = bold
        run.font.size = Pt(size)
        run.font.color.rgb = RGBColor.from_string(color)
    return p

# ─── Helper: add code block ───
def add_code(doc, lines, indent=0):
    for line in lines:
        p = doc.add_paragraph()
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after  = Pt(0)
        if indent:
            p.paragraph_format.left_indent = Cm(indent)
        shade_paragraph(p, '1F2937')
        run = p.add_run(line if line else " ")
        run.font.name = 'Courier New'
        run.font.size = Pt(8.5)
        run.font.color.rgb = RGBColor.from_string('86EFAC')
    doc.add_paragraph().paragraph_format.space_after = Pt(2)

# ─── Helper: add table ───
def add_table(doc, headers, rows, col_widths=None):
    table = doc.add_table(rows=1+len(rows), cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.LEFT
    set_table_borders(table)

    # Header row
    hdr_row = table.rows[0]
    for i, h in enumerate(headers):
        cell = hdr_row.cells[i]
        shade_cell(cell, '1E3A8A')
        p = cell.paragraphs[0]
        p.paragraph_format.space_before = Pt(4)
        p.paragraph_format.space_after  = Pt(4)
        run = p.add_run(h)
        run.bold = True
        run.font.size = Pt(9)
        run.font.color.rgb = RGBColor.from_string('FFFFFF')

    # Data rows
    for ri, row_data in enumerate(rows):
        row = table.rows[ri+1]
        bg  = 'F9FAFB' if ri % 2 == 0 else 'FFFFFF'
        for ci, cell_text in enumerate(row_data):
            cell = row.cells[ci]
            shade_cell(cell, bg)
            p = cell.paragraphs[0]
            p.paragraph_format.space_before = Pt(3)
            p.paragraph_format.space_after  = Pt(3)
            # ตัวหนาถ้า column แรก
            run = p.add_run(cell_text)
            run.bold = (ci == 0)
            run.font.size = Pt(9)
            run.font.color.rgb = RGBColor.from_string('111827')

    # Column widths
    if col_widths:
        for i, w in enumerate(col_widths):
            for row in table.rows:
                row.cells[i].width = Cm(w)

    doc.add_paragraph().paragraph_format.space_after = Pt(2)
    return table

# ═══════════════════════════════════════════════
# TITLE PAGE
# ═══════════════════════════════════════════════
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
p.paragraph_format.space_before = Pt(60)
p.paragraph_format.space_after  = Pt(6)
shade_paragraph(p, '1E3A8A')
run = p.add_run('  PayMock  ')
run.bold = True
run.font.size = Pt(36)
run.font.color.rgb = RGBColor.from_string('FFFFFF')

p2 = doc.add_paragraph()
p2.alignment = WD_ALIGN_PARAGRAPH.CENTER
p2.paragraph_format.space_before = Pt(4)
p2.paragraph_format.space_after  = Pt(4)
shade_paragraph(p2, '1E3A8A')
run2 = p2.add_run('  Mock Payment Gateway — API Reference  ')
run2.font.size = Pt(16)
run2.font.color.rgb = RGBColor.from_string('BFDBFE')

p3 = doc.add_paragraph()
p3.alignment = WD_ALIGN_PARAGRAPH.CENTER
p3.paragraph_format.space_before = Pt(4)
shade_paragraph(p3, '1E3A8A')
run3 = p3.add_run('  เอกสารสำหรับนักพัฒนาระบบคอนเสิร์ต (ฝั่งต้นทาง)  ')
run3.font.size = Pt(11)
run3.font.color.rgb = RGBColor.from_string('93C5FD')

doc.add_paragraph()

info_table = doc.add_table(rows=4, cols=2)
info_table.alignment = WD_TABLE_ALIGNMENT.CENTER
def info_row(table, ri, label, val):
    shade_cell(table.rows[ri].cells[0], 'EFF6FF')
    shade_cell(table.rows[ri].cells[1], 'FFFFFF')
    r0 = table.rows[ri].cells[0].paragraphs[0]
    r0.add_run(label).bold = True
    r0.runs[0].font.size = Pt(10)
    r1 = table.rows[ri].cells[1].paragraphs[0]
    r1.add_run(val).font.size = Pt(10)
for i,(l,v) in enumerate([
    ('Base URL',     'http://localhost:8081'),
    ('API Version',  'v1'),
    ('API Key',      'pm_test_key_concert_system_2024'),
    ('Webhook Secret','whsec_concert_super_secret_2024'),
]):
    info_row(info_table, i, l, v)
set_table_borders(info_table)

doc.add_page_break()

# ═══════════════════════════════════════════════
# 1. OVERVIEW
# ═══════════════════════════════════════════════
add_heading(doc, '1. ภาพรวม (Overview)', 1)
add_para(doc, (
    'PayMock เป็น Mock Payment Gateway แบบ Standalone สำหรับใช้ทดสอบระบบจองตั๋วคอนเสิร์ต '
    'รัน แยกกันคนละโปรเจกต์ คนละ Port และคนละ Database จากระบบหลัก '
    'ระบบคอนเสิร์ตติดต่อกับ PayMock ผ่าน HTTP API และ Webhook เท่านั้น '
    'เหมือนการใช้ Payment Gateway จริง เช่น Stripe หรือ Omise'
))

add_heading(doc, '1.1 Tech Stack', 2)
add_table(doc,
    ['ส่วน', 'เทคโนโลยี', 'หมายเหตุ'],
    [
        ['Framework',   'Laravel 12',          'PHP 8.3, แยกโปรเจกต์ port 8081'],
        ['Database',    'SQLite',               'แยกจาก MySQL ของระบบหลักชัดเจน'],
        ['UI',          'Blade + Tailwind + Alpine.js', 'หน้า Checkout + Admin Panel'],
        ['Queue',       'Database Queue + Scheduler',  'ส่ง Webhook + expire payment'],
        ['Security',    'API Key (Bearer) + HMAC-SHA256', 'เหมือน Gateway จริง'],
        ['Container',   'Docker Compose',       'รัน 3 containers: app, queue, scheduler'],
    ],
    col_widths=[3.5, 5, 7]
)

add_heading(doc, '1.2 Flow การทำงาน', 2)
add_para(doc, 'ลำดับการทำงานระหว่างระบบคอนเสิร์ตและ PayMock:')
steps = [
    '① ผู้ใช้เลือกที่นั่ง → ระบบคอนเสิร์ตล็อกที่นั่ง 10 นาที + สร้าง booking (PENDING_PAYMENT)',
    '② ระบบคอนเสิร์ต POST /v1/payments → PayMock คืน payment_id + (qrcode_url สำหรับ PromptPay / ผลบัตรทันทีสำหรับ Card)',
    '③ PromptPay: เว็บคอนเสิร์ตแสดง QR Code จาก qrcode_url ใน Native Modal ของตัวเอง | Card: แจ้งผลลัพธ์ทันที (COMPLETED หรือ FAILED)',
    '④ ผู้ใช้สแกน QR ผ่านแอปธนาคาร หรือคลิก bank_simulator_url เพื่อจำลองการโอนเงิน',
    '⑤ PayMock เปลี่ยนสถานะ → ส่ง Webhook (POST {callback_url}) พร้อม HMAC-SHA256',
    '⑥ ระบบคอนเสิร์ตตรวจ signature → ตรวจ idempotency → อัปเดต booking + seat + ออกตั๋ว',
    '⑦ (สำรอง) ระบบคอนเสิร์ตยิง GET /v1/payments/{id} เพื่อ reconcile — ใช้ response.id (provider transaction ID)',
]
for s in steps:
    p = doc.add_paragraph(style='List Bullet')
    p.paragraph_format.left_indent = Cm(0.5)
    p.paragraph_format.space_after = Pt(3)
    run = p.add_run(s)
    run.font.size = Pt(10)

doc.add_paragraph()
add_para(doc,
    '⚠️ สำคัญ: ห้ามตัดสินว่าการชำระเงินสำเร็จหรือไม่จากการ Poll ฝั่ง Frontend เท่านั้น '
    'ต้องรอการยืนยันจาก Webhook หรือ GET /v1/payments/{id} เท่านั้น',
    color='B45309'
)

doc.add_page_break()

# ═══════════════════════════════════════════════
# 2. AUTHENTICATION
# ═══════════════════════════════════════════════
add_heading(doc, '2. Authentication', 1)
add_para(doc, 'ทุก request ไปยัง /v1/* ต้องส่ง Authorization header:')
add_code(doc, ['Authorization: Bearer pm_test_key_concert_system_2024'])

add_heading(doc, '2.1 Headers ที่ใช้บ่อย', 2)
add_table(doc,
    ['Header', 'Required', 'ค่าตัวอย่าง', 'หน้าที่'],
    [
        ['Authorization',    'จำเป็น',    'Bearer pm_test_key_concert_system_2024', 'API Key ยืนยันตัวตน'],
        ['Content-Type',     'จำเป็น (POST)', 'application/json', 'ชนิดข้อมูล'],
        ['Idempotency-Key',  'แนะนำ',     'b_TK2024001',        'ป้องกัน duplicate เมื่อ retry'],
    ],
    col_widths=[4, 3, 5.5, 4]
)

# ═══════════════════════════════════════════════
# 3. API ENDPOINTS
# ═══════════════════════════════════════════════
add_heading(doc, '3. API Endpoints', 1)

# 3.1 GET /v1/payments
add_heading(doc, '3.1  GET /v1/payments — ตรวจสถานะ API', 2, color='065F46')
add_para(doc, 'ใช้ตรวจสอบว่า PayMock ออนไลน์อยู่ก่อนสร้างรายการ และดูรายการล่าสุด 10 รายการ')

add_heading(doc, 'Request', 3, color='374151')
add_code(doc, [
    'GET /v1/payments HTTP/1.1',
    'Authorization: Bearer pm_test_key_concert_system_2024',
])

add_heading(doc, 'Response 200 OK', 3, color='374151')
add_code(doc, [
    '{',
    '  "service":    "PayMock Gateway API",',
    '  "status":       "online",',
    '  "chaos_mode":   "normal",',
    '  "webhook_mode": "normal",',
    '  "recent": [',
    '    {',
    '      "id":                 "txn_01jcxyz...",',
    '      "provider_transaction_id": "txn_01jcxyz...",',
    '      "payment_id":         "b_TK2024001",',
    '      "amount":             1200.00,',
    '      "method":             "promptpay",',
    '      "status":             "COMPLETED",',
    '      "qrcode_url":         "http://localhost:8081/v1/payments/01JCXYZ.../qr.svg",',
    '      "bank_simulator_url": "http://localhost:8081/bank-simulate/01JCXYZ...",',
    '      "paid_at":            "2026-10-13T10:05:32+07:00"',
    '    }',
    '  ]',
    '}',
])

add_heading(doc, 'Response Fields', 3, color='374151')
add_table(doc,
    ['Field', 'Type', 'ความหมาย'],
    [
        ['status',       'string', '"online" เสมอ (ถ้าตอบได้)'],
        ['chaos_mode',   'string', 'ชั้น 1 — ตั้งที่ Admin: normal, down, slow:3 ฯลฯ'],
        ['webhook_mode', 'string', 'ชั้น 2 — ตั้งที่ Admin: normal, webhook_lost ฯลฯ'],
        ['recent',       'array',  '10 รายการล่าสุด (schema เดียวกับ POST response)'],
    ],
    col_widths=[3.5, 2.5, 10.5]
)

# 3.2 POST /v1/payments
add_heading(doc, '3.2  POST /v1/payments — สร้างรายการจ่ายเงิน', 2, color='065F46')
add_para(doc, (
    'สร้างรายการชำระเงินใหม่ รองรับ Idempotency เพื่อป้องกันการสร้างรายการซ้ำเมื่อ retry '
    'ต้องส่ง payment_id ทุกครั้งเพื่อระบุรายการฝั่ง merchant และรองรับ Idempotency เพื่อป้องกันการสร้างรายการซ้ำเมื่อ retry. '
    'Response คืน id (provider transaction ID) สำหรับใช้กับ GET / cancel / QR / polling ทั้งบัตรและ PromptPay.'
))

add_heading(doc, 'Request', 3, color='374151')
add_code(doc, [
    'POST /v1/payments HTTP/1.1',
    'Authorization: Bearer pm_test_key_concert_system_2024',
    'Content-Type: application/json',
    'Idempotency-Key: b_TK2024001',
    '',
    '// PromptPay:',
    '{',
    '  "payment_id":   "b_TK2024001",',
    '  "amount":       1200.00,',
    '  "method":       "promptpay",',
    '  "callback_url": "http://localhost:8080/webhooks/paymock",',
    '  "expires_in":   600,',
    '  "currency":     "THB",',
    '  "metadata":     { "booking_id": 42 }',
    '}',
    '',
    '// Credit Card (Direct API):',
    '{',
    '  "payment_id":   "b_TK2024002",',
    '  "amount":       1200.00,',
    '  "method":       "card",',
    '  "callback_url": "http://localhost:8080/webhooks/paymock",',
    '  "card_number":  "4242424242424242",',
    '  "exp_month":    "12",',
    '  "exp_year":     "28",',
    '  "cvc":          "123"',
    '}',
])

add_heading(doc, 'Request Body Fields', 3, color='374151')
add_table(doc,
    ['Field', 'Type', 'Required', 'ค่าที่ยอมรับ', 'คำอธิบาย'],
    [
        ['payment_id',   'string',  'จำเป็น',      'max 255 chars',          'รหัสรายการฝั่งร้านค้า ต้องส่งทุกครั้ง; response จะ echo กลับ'],
        ['amount',       'number',  'จำเป็น',      'min: 1',                 'ยอดเงิน (บาท, ทศนิยมได้)'],
        ['method',       'string',  'จำเป็น',      '"card" หรือ "promptpay"', 'วิธีชำระเงิน'],
        ['callback_url', 'string',  'จำเป็น',      'URL ที่ถูกต้อง',         'URL รับ Webhook จาก PayMock'],
        ['expires_in',   'integer', 'ไม่จำเป็น',   'min: 60 (วินาที)',        'อายุก่อนหมดเวลา (default: 600)'],
        ['currency',     'string',  'ไม่จำเป็น',   '3 ตัวอักษร',             'สกุลเงิน (default: "THB")'],
        ['card_number',  'string',  'ไม่จำเป็น',   '16 หลัก',                'หมายเลขบัตรเครดิต (สำหรับ Direct Card Payment)'],
        ['exp_month',    'string',  'ไม่จำเป็น',   '2 หลัก (01-12)',          'เดือนหมดอายุบัตร'],
        ['exp_year',     'string',  'ไม่จำเป็น',   '2 หรือ 4 หลัก',           'ปีหมดอายุบัตร'],
        ['cvc',          'string',  'ไม่จำเป็น',   '3-4 หลัก',               'รหัสตรวจสอบบัตร (CVV/CVC)'],
        ['metadata',     'object',  'ไม่จำเป็น',   'JSON object',            'ข้อมูลเพิ่มเติม (ไม่ส่งใน webhook)'],
    ],
    col_widths=[3, 2, 2.5, 3.5, 5.5]
)

add_heading(doc, 'Idempotency', 3, color='374151')
add_para(doc, (
    'ถ้าส่ง Idempotency-Key header → ใช้ค่านั้น  |  '
    'ถ้าไม่ส่ง → ใช้ payment_id เป็น key อัตโนมัติ  |  '
    'ถ้าส่ง key เดิมซ้ำ → คืน payment เดิม ไม่สร้างใหม่ (ใช้ตอน retry หลัง timeout)'
))

add_heading(doc, 'Response 201 Created (PromptPay)', 3, color='374151')
add_code(doc, [
    '{',
    '  "id":                 "txn_01jcxyz1234567890",',
    '  "provider_transaction_id": "txn_01jcxyz1234567890",',
    '  "payment_id":         "b_TK2024001",',
    '  "amount":             1200.00,',
    '  "currency":           "THB",',
    '  "method":             "promptpay",',
    '  "status":             "PENDING",',
    '  "failure_code":       null,',
    '  "expires_at":         "2026-10-13T10:10:00+07:00",',
    '  "paid_at":            null,',
    '  "created_at":         "2026-10-13T10:00:00+07:00",',
    '  "qrcode_url":         "http://localhost:8081/v1/payments/01JCXYZ.../qr.svg",',
    '  "bank_simulator_url": "http://localhost:8081/bank-simulate/01JCXYZ..."',
    '}',
])

add_heading(doc, 'Response Fields', 3, color='374151')
add_table(doc,
    ['Field', 'Type', 'ความหมาย'],
    [
        ['id',                 'string',          'Provider transaction ID (txn_...) — ใช้กับ GET / cancel / QR / ระบบคอนเสิร์ต หลังสร้าง'],
        ['provider_transaction_id', 'string',      'ค่าเดียวกับ id'],
        ['payment_id',         'string',          'รหัสรายการที่ merchant ส่งมา (required ใน request)'],
        ['status',             'string',          'PENDING / COMPLETED / FAILED / EXPIRED'],
        ['failure_code',       'string | null',   'รหัสเหตุผลเมื่อล้มเหลว (ดูตาราง Failure Codes)'],
        ['qrcode_url',         'string | null',   'URL รูปภาพ SVG QR Code สำหรับ PromptPay (ใส่ใน <img src="...">)'],
        ['bank_simulator_url', 'string | null',   'URL หน้าจำลอง Mobile Banking สำหรับทดสอบ'],
        ['expires_at',         'ISO 8601 | null', 'เวลาหมดอายุ = created_at + expires_in (นาฬิกาเซิร์ฟเวอร์)'],
        ['paid_at',            'ISO 8601 | null', 'เวลาจ่ายสำเร็จจริง (เฉพาะ COMPLETED) — ไม่มีฟิลด์ completed_at'],
        ['created_at',         'ISO 8601',        'เวลาสร้างรายการจริง (timezone Asia/Bangkok)'],
    ],
    col_widths=[3.5, 3.5, 9.5]
)

add_heading(doc, 'สถานะ Payment', 3, color='374151')
add_table(doc,
    ['status', 'ความหมาย', 'ระบบคอนเสิร์ตควรทำ'],
    [
        ['PENDING',   'รอชำระเงิน',                   'Lock ที่นั่งไว้ รอ Webhook'],
        ['COMPLETED', 'จ่ายสำเร็จ',                   'ออกตั๋ว, seat → SOLD'],
        ['FAILED',    'ล้มเหลว / ถูกปฏิเสธ / ยกเลิก', 'แจ้งผู้ใช้ ปลดล็อกที่นั่ง'],
        ['EXPIRED',   'หมดเวลาก่อนจ่าย',               'ยกเลิก booking ปลดล็อกที่นั่ง'],
    ],
    col_widths=[3, 4.5, 9]
)

add_heading(doc, 'Failure Codes', 3, color='374151')
add_table(doc,
    ['failure_code', 'ความหมาย', 'ระบบควรแสดงอะไร'],
    [
        ['card_declined',     'บัตรถูกปฏิเสธ',       'บัตรถูกปฏิเสธ กรุณาลองบัตรอื่น'],
        ['insufficient_funds','วงเงินไม่พอ',          'วงเงินในบัตรไม่เพียงพอ'],
        ['expired_card',      'บัตรหมดอายุ',          'บัตรหมดอายุ'],
        ['incorrect_cvc',     'CVC ผิด',              'รหัส CVC ไม่ถูกต้อง'],
        ['processing_error',  'ข้อผิดพลาดจาก gateway','ระบบขัดข้องชั่วคราว ลองอีกครั้ง'],
        ['fraud_suspected',   'สงสัย fraud',           'บันทึก Anomaly แจ้ง Admin'],
        ['cancelled',         'ยกเลิกโดยผู้ใช้/ระบบ','ยกเลิก booking'],
        ['payment_expired',   'หมดเวลา',              'เหมือน EXPIRED'],
    ],
    col_widths=[4, 4, 8.5]
)

add_heading(doc, 'Error Responses', 3, color='374151')
add_table(doc,
    ['HTTP', 'error', 'สาเหตุ'],
    [
        ['401', 'unauthorized',       'API Key ผิดหรือไม่มี'],
        ['422', 'validation_error',   'ข้อมูลที่ส่งไม่ถูกต้อง (ดูฟิลด์ details)'],
        ['429', 'rate_limited',       'ถูก rate limit (ดู Retry-After header)'],
        ['500', 'internal_error',     'ข้อผิดพลาดภายใน gateway'],
        ['503', 'service_unavailable','gateway อยู่ในโหมด down'],
    ],
    col_widths=[1.5, 4, 11]
)

doc.add_page_break()

# 3.3 GET /v1/payments/{id}
add_heading(doc, '3.3  GET /v1/payments/{id} — ดูสถานะรายการ', 2, color='065F46')
add_para(doc, 'ใช้สำหรับ Polling หรือ Reconcile เมื่อ Webhook ไม่มาหรือมาช้า — {id} คือ response.id (provider transaction ID) จาก POST')

add_heading(doc, 'Request', 3, color='374151')
add_code(doc, [
    'GET /v1/payments/01JCXYZ1234567890ABCDEFGH HTTP/1.1',
    'Authorization: Bearer pm_test_key_concert_system_2024',
])

add_heading(doc, 'Response 200 OK', 3, color='374151')
add_para(doc, 'Response schema เหมือนกับ POST /v1/payments ทุกประการ (ดูหัวข้อ 3.2)')

add_heading(doc, 'Error Responses', 3, color='374151')
add_table(doc,
    ['HTTP', 'สาเหตุ'],
    [
        ['401', 'API Key ผิด'],
        ['404', 'ไม่พบ Payment ID นี้'],
        ['503', 'gateway อยู่ในโหมด down'],
    ],
    col_widths=[1.5, 15]
)

# 3.4 POST /v1/payments/{id}/cancel
add_heading(doc, '3.4  POST /v1/payments/{id}/cancel — ยกเลิกรายการ', 2, color='065F46')
add_para(doc, 'ยกเลิกได้เฉพาะรายการที่ยัง PENDING เท่านั้น')

add_heading(doc, 'Request', 3, color='374151')
add_code(doc, [
    'POST /v1/payments/01JCXYZ1234567890ABCDEFGH/cancel HTTP/1.1',
    'Authorization: Bearer pm_test_key_concert_system_2024',
    '',
    '(ไม่ต้องส่ง Body)',
])

add_heading(doc, 'Response 200 OK', 3, color='374151')
add_code(doc, [
    '{',
    '  "ok":           true,',
    '  "status":       "FAILED",',
    '  "failure_code": "cancelled"',
    '}',
])

add_heading(doc, 'Error Responses', 3, color='374151')
add_table(doc,
    ['HTTP', 'error', 'สาเหตุ'],
    [
        ['401', 'unauthorized', 'API Key ผิด'],
        ['404', '—',            'ไม่พบ Payment ID'],
        ['409', 'cannot_cancel','รายการ settle แล้ว ยกเลิกไม่ได้'],
    ],
    col_widths=[1.5, 4, 11]
)

# 3.5 Concert backend — ใช้ payment_id
add_heading(doc, '3.5  API ระบบคอนเสิร์ต — อ้างอิงด้วย payment_id', 2, color='065F46')
add_para(doc, (
    'หลัง POST /v1/payments สำเร็จ ให้เก็บ response.id เป็น provider transaction ID '
    'ทั้ง PromptPay และบัตรใช้คีย์นี้กับ polling / ยกเลิก / reconcile'
))
add_table(doc,
    ['Method', 'Path', 'Body / Query', 'หน้าที่'],
    [
        ['GET',  '/api/booking-status',    '?payment_id=txn_…',            'สถานะตั๋ว + webhook logs'],
        ['POST', '/api/reconcile-booking', '{ "payment_id": "txn_…" }',    'GET จาก PayMock แล้วอัปเดตตั๋ว'],
        ['POST', '/api/cancel-booking',    '{ "payment_id": "txn_…" }',    'เรียก POST /v1/payments/{id}/cancel'],
        ['POST', '/api/webhook',           'PayMock ส่ง payload.id',       'อัปเดตตั๋วตาม payment_id'],
    ],
    col_widths=[2, 5, 5.5, 4]
)
add_para(doc, (
    'payment_id เป็นฟิลด์บังคับตอนสร้างรายการ '
    'หลังจากสร้างแล้วระบบคอนเสิร์ตต้องทำงานด้วย response.id (provider transaction ID)'
), color='B45309')

doc.add_page_break()

# ═══════════════════════════════════════════════
# 4. WEBHOOK
# ═══════════════════════════════════════════════
add_heading(doc, '4. Webhook — สิ่งที่ PayMock ส่งมาหาคุณ', 1)
add_para(doc, (
    'เมื่อสถานะ payment เปลี่ยน PayMock จะ POST ไปที่ callback_url ที่คุณให้ไว้ '
    'endpoint ของระบบคอนเสิร์ตต้องตอบกลับ 2xx ภายใน 10 วินาที '
    'มิเช่นนั้น PayMock จะ retry ตาม backoff schedule'
))

add_heading(doc, '4.1 Headers ที่ PayMock ส่งมา', 2)
add_code(doc, [
    'POST {callback_url} HTTP/1.1',
    'Content-Type: application/json',
    'X-Signature: t=1728810332,v1=a3f9c2b1d8e4f7a0b5c6d9e2f1a4b7c0d3e6f9a2b5c8d1e4f7a0b3c6d9e2f5',
    'X-Event-Id: 01JCABC9876543210ZYXWVUTS',
])

add_table(doc,
    ['Header', 'ความหมาย'],
    [
        ['Content-Type','application/json เสมอ'],
        ['X-Signature', 'ลายเซ็น HMAC-SHA256 ใช้ยืนยันว่า webhook มาจาก PayMock จริง'],
        ['X-Event-Id',  'ULID ไม่ซ้ำของแต่ละ delivery ใช้ป้องกัน duplicate (idempotency)'],
    ],
    col_widths=[3.5, 13]
)

add_heading(doc, '4.2 Webhook Body', 2)
add_code(doc, [
    '{',
    '  "id":           "txn_01JCXYZ1234567890ABCDEFGH",',
    '  "event":        "payment.completed",',
    '  "payment_id":   "12",',
    '  "amount":       1200.00,',
    '  "currency":     "THB",',
    '  "method":       "promptpay",',
    '  "status":       "COMPLETED",',
    '  "failure_code": null,',
    '  "paid_at":      "2026-10-13T10:05:32+07:00",',
    '  "created_at":   "2026-10-13T10:00:00+07:00"',
    '}',
])

add_heading(doc, 'Webhook Body Fields', 3, color='374151')
add_table(doc,
    ['Field', 'Type', 'ความหมาย'],
    [
        ['id',           'string (ULID)',   'Payment ID (ขึ้นต้นด้วย txn_)'],
        ['event',        'string',          'ชื่อ event: payment.completed / payment.failed / payment.expired'],
        ['payment_id',   'string',          'รหัสรายการจาก merchant ที่ส่งในตอนสร้าง payment'],
        ['amount',       'float',           'ยอดเงินที่ชำระ'],
        ['currency',     'string',          'สกุลเงิน เช่น THB'],
        ['method',       'string',          'ช่องทางชำระเงิน: promptpay หรือ card'],
        ['status',       'string',          'สถานะรายการ: COMPLETED / FAILED / EXPIRED'],
        ['failure_code', 'string | null',   'รหัสเหตุผลความล้มเหลว (มีเฉพาะสถานะ FAILED เช่น card_declined, insufficient_funds)'],
        ['paid_at',      'ISO 8601 | null', 'เวลาที่ชำระเงินสำเร็จ (มีเฉพาะสถานะ COMPLETED)'],
        ['created_at',   'ISO 8601',        'เวลาที่สร้างรายการชำระเงิน'],
    ],
    col_widths=[3.5, 3.5, 9.5]
)

add_heading(doc, 'Webhook Events', 3, color='374151')
add_table(doc,
    ['event', 'เมื่อไหร่', 'status ใน body'],
    [
        ['payment.completed', 'จ่ายสำเร็จ',           'COMPLETED'],
        ['payment.failed',    'ถูกปฏิเสธ / ยกเลิก',  'FAILED'],
        ['payment.expired',   'หมดเวลาก่อนจ่าย',      'EXPIRED'],
    ],
    col_widths=[4.5, 5.5, 6.5]
)

add_heading(doc, '4.3 การตรวจ Signature (สำคัญมาก)', 2)
add_para(doc, (
    'ต้องทำก่อน process webhook ทุกครั้ง เพื่อป้องกัน Replay Attack และ Forgery '
    '(ใครเป็นคนตรวจสอบ และใครเป็นคนปฏิเสธกรณี Invalid Signature?):'
))

sig_explanations = [
    'Gateway ส่งข้อมูลและลายเซ็น: PayMock จะส่ง Header X-Signature: t={timestamp},v1={hmac} มาพร้อมกับ Webhook Payload เสมอ',
    'Backend เป็นผู้ตรวจสอบลายเซ็น: ระบบคอนเสิร์ต (Backend ของคุณ) มีหน้าที่คำนวณ HMAC-SHA256 ด้วยตัวเอง โดยใช้ Webhook Secret ที่เก็บไว้เป็นความลับ',
    'กรณี Invalid Signature (ลายเซ็นปลอม): Gateway ไม่ได้เป็นคนบอกว่า "ไม่ผ่าน" แต่ผู้ไม่ประสงค์ดีอาจส่ง Body ที่อ้างว่า status: COMPLETED เข้ามาหลอก! ฝั่ง Backend ของคุณเมื่อตรวจพบว่า HMAC ไม่ตรง จึงมีหน้าที่ "ปฏิเสธไม่ออกตั๋ว" และ "ตอบกลับ HTTP 401 Unauthorized" ด้วยตัวเอง!',
    'กรณี Replay Attack (ส่งซ้ำ/หมดอายุ): หาก timestamp ใน Header ห่างจากเวลาปัจจุบันเกิน 5 นาที (300 วินาที) Backend ของคุณต้องปฏิเสธด้วย HTTP 401 เช่นกัน',
]
for exp in sig_explanations:
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(2)
    p.paragraph_format.left_indent = Cm(0.5)
    run = p.add_run('• ' + exp)
    run.font.size = Pt(9.5)

add_para(doc, 'ขั้นตอนการตรวจสอบ Signature ในโค้ดของระบบคอนเสิร์ต:')
steps_sig = [
    'อ่าน X-Signature header แล้วแยก t (timestamp) และ v1 (signature hex)',
    'ตรวจว่า timestamp ไม่เก่าเกิน 5 นาที (300 วินาที)',
    'คำนวณ HMAC-SHA256 ด้วย raw request body (ก่อน decode JSON) ด้วย secret key ของคุณ',
    'เปรียบเทียบด้วย hash_equals() (constant-time comparison) เพื่อป้องกัน Timing Attack',
    'หากลายเซ็นถูกต้อง อัปเดตสถานะตั๋วและตอบ HTTP 200 หากไม่ถูกต้อง ปฏิเสธไม่ออกตั๋วและตอบ HTTP 401',
]
for i, s in enumerate(steps_sig, 1):
    p = doc.add_paragraph()
    p.paragraph_format.space_after  = Pt(3)
    p.paragraph_format.left_indent  = Cm(0.5)
    run = p.add_run(f'{i}. {s}')
    run.font.size = Pt(10)

add_heading(doc, 'ตัวอย่างโค้ด PHP (ฝั่งระบบคอนเสิร์ต)', 3, color='374151')
add_code(doc, [
    'public function handleWebhook(Request $request): Response',
    '{',
    '    // 1. แยก signature header',
    '    $sigHeader = $request->header(\'X-Signature\', \'\');',
    '    preg_match(\'/t=(\\d+),v1=([a-f0-9]+)/\', $sigHeader, $m);',
    '    [$timestamp, $signature] = [$m[1] ?? 0, $m[2] ?? \'\'];',
    '',
    '    // 2. ตรวจ timestamp (replay attack protection)',
    '    if (abs(time() - $timestamp) > 300) {',
    '        return response(\'Expired\', 400);',
    '    }',
    '',
    '    // 3. คำนวณและตรวจ HMAC',
    '    $secret   = config(\'services.paymock.webhook_secret\');',
    '    $rawBody  = $request->getContent();',
    '    $expected = hash_hmac(\'sha256\', "{$timestamp}.{$rawBody}", $secret);',
    '    if (! hash_equals($expected, $signature)) {',
    '        return response(\'Unauthorized\', 401);',
    '    }',
    '',
    '    // 4. ตรวจ idempotency ด้วย X-Event-Id',
    '    $eventId = $request->header(\'X-Event-Id\');',
    '    if (WebhookLog::where(\'event_id\', $eventId)->exists()) {',
    '        return response(\'OK\', 200); // รับแล้ว ไม่ต้อง process ซ้ำ',
    '    }',
    '    WebhookLog::create([\'event_id\' => $eventId]);',
    '',
    '    // 5. process ตาม event',
    '    $payload = $request->json()->all();',
    '    match($payload[\'event\']) {',
    '        \'payment.completed\' => $this->handleCompleted($payload),',
    '        \'payment.failed\'    => $this->handleFailed($payload),',
    '        \'payment.expired\'   => $this->handleExpired($payload),',
    '        default             => null,',
    '    };',
    '',
    '    return response(\'OK\', 200); // ต้องตอบ 2xx ไม่เช่นนั้น PayMock จะ retry',
    '}',
])

add_heading(doc, '4.4 Retry Policy', 2)
add_para(doc, 'PayMock จะ retry webhook ถ้าได้รับ response ที่ไม่ใช่ 2xx หรือ connection timeout')
add_table(doc,
    ['ครั้งที่', 'หน่วงก่อน retry (วินาที)'],
    [
        ['retry 1', '5'],
        ['retry 2', '30'],
        ['retry 3', '120'],
        ['retry 4', '300'],
        ['retry 5', '600'],
        ['สูงสุด',  '6 ครั้ง'],
    ],
    col_widths=[4, 12.5]
)
add_heading(doc, '4.5 การหมดอายุของ QR Code (payment.expired)', 2)
add_para(doc, (
    'เมื่อ QR Code หรือรายการที่รอชำระเงินหมดเวลา (เลย expires_at) ระบบ PayMock จะส่ง Webhook '
    'Event ชื่อ payment.expired ไปยัง callback_url ของคุณทันที เพื่อแจ้งเตือนให้ระบบคอนเสิร์ตทำการปลดล็อกที่นั่ง'
))
add_table(doc,
    ['สิ่งที่ระบบคอนเสิร์ตต้องทำเมื่อได้รับ payment.expired', 'ตารางในฐานข้อมูล', 'การเปลี่ยนแปลง'],
    [
        ['1. ยกเลิกการจองตั๋ว', 'booking', 'อัปเดต b_status = "EXPIRED"'],
        ['2. ปลดล็อกที่นั่ง', 'seat', 'อัปเดต s_seat_booking_status = "AVAILABLE"'],
        ['3. คืนสต็อกโซนยืน', 'zone_inventory', 'ลด zi_held ลงตามจำนวนตั๋ว'],
        ['4. บันทึกประวัติการปลดล็อก', 'seat_lock_log', 'บันทึก sll_released_at = NOW()'],
    ],
    col_widths=[6, 4, 6.5]
)

doc.add_page_break()

# ═══════════════════════════════════════════════
# 5. CHAOS + WEBHOOK MODE (Admin)
# ═══════════════════════════════════════════════
add_heading(doc, '5. Chaos และ Webhook Mode — ตั้งที่ PayMock Admin', 1)
add_para(doc, (
    'ระบบคอนเสิร์ตเรียกแค่ API จ่ายเงินจริง: สร้างรายการ, ใส่เลขบัตร, ยกเลิก, GET สถานะด้วย payment_id '
    'ความผิดปกติทั้งหมดตั้งที่ http://localhost:8081/admin ไม่ส่ง scenario / X-Mock-Scenario จากฝั่ง merchant'
))
add_table(doc,
    ['ชั้น', 'Setting', 'เมื่อไหร่มีผล'],
    [
        ['1 (ใหญ่สุด)', 'Chaos Mode', 'คำขอเข้า /v1/* และ /checkout/* (ล่ม, ช้า, 429, 500)'],
        ['2 (รอง)',     'Webhook Mode', 'ตอนยิง webhook หลัง settle (lost, delay, dup, bad_sig, replay)'],
    ],
    col_widths=[3, 4, 9.5]
)
add_para(doc, 'Webhook Mode เป็นค่า global เดียวจาก Admin และมีผลกับทุกรายการ ไม่มี override ต่อ payment', color='B45309')

add_heading(doc, '5.1 Webhook Mode (ชั้น 2 — ตั้งจาก Admin)', 2)
add_para(doc, 'ตั้งค่าที่ Admin ทั้งระบบเท่านั้น ระบบคอนเสิร์ตไม่ต้องส่ง header หรือ scenario ใด ๆ:')

add_table(doc,
    ['Scenario', 'สิ่งที่ Gateway ส่งมา', 'สิ่งที่ Backend ต้องตอบ', 'ผลต่อตั๋วคอนเสิร์ต', 'การรับมือ / วัตถุประสงค์'],
    [
        ['normal', 'ส่ง Webhook ทันทีพร้อม Signature ถูกต้อง', '200 OK', 'ออกตั๋วสำเร็จ (PAID)', 'กรณีการทำงานตามปกติ'],
        ['webhook_lost', 'ไม่ส่ง Webhook เลย (Packet Drop)', '— (ไม่ได้รับ)', 'ค้าง PENDING', 'ใช้ทดสอบ Fallback: ต้องมีปุ่มหรือ Cron ยิง GET /v1/payments/{id} เพื่อ Reconcile สถานะ'],
        ['webhook_delay:N', 'หน่วงเวลา N วินาทีก่อนส่ง Webhook', '200 OK (เมื่อมาถึง)', 'PENDING → PAID หลัง N วิ', 'ทดสอบว่าหน้าเว็บรองรับช่วงรอยต่อระหว่างรอ Webhook หรือไม่'],
        ['webhook_dup:N', 'ส่ง Webhook ซ้ำกัน N รอบ (Payload เดิม)', '200 OK ทุกรอบ', 'ออกตั๋ว 1 ใบเท่านั้น (PAID)', 'ทดสอบ Idempotency: รอบที่ 2-N ต้องตอบ 200 แต่ไม่ออกตั๋วซ้ำเด็ดขาด'],
        ['webhook_bad_sig', 'ส่ง Body อ้างว่า COMPLETED แต่ลายเซ็นปลอม (0000...)', '401 Unauthorized', 'ปฏิเสธไม่ออกตั๋ว (REJECTED)', 'ทดสอบ Security: ป้องกัน Hacker ส่ง Webhook ปลอมมาหลอกออกตั๋ว'],
        ['webhook_replay', 'ส่ง Body ถูกต้อง แต่ Timestamp เก่าเกิน 5 นาที', '401 Unauthorized', 'ปฏิเสธไม่ออกตั๋ว (REJECTED)', 'ทดสอบ Replay Attack: ป้องกันการดักจับ Webhook เก่ามายิงซ้ำ'],
    ],
    col_widths=[3, 4, 3, 3.5, 4.5]
)

add_heading(doc, 'รายละเอียดเชิงลึกของแต่ละ Webhook Scenario', 3, color='374151')

sc_details = [
    ('1. webhook_lost (Webhook สูญหายระหว่างทาง)', [
        'พฤติกรรม: Gateway ตัดเงินสำเร็จจริง (ในฐานข้อมูลของ PayMock เป็น COMPLETED) แต่เกิดปัญหาเน็ตหลุด ทำให้ Webhook ไปไม่ถึง Backend ของคุณ',
        'ผลกระทบ: ฝั่งคอนเสิร์ตไม่ได้รับ Webhook ทำให้ตั๋วยังคงค้างสถานะ PENDING',
        'วิธีรับมือที่ถูกต้อง: ระบบคอนเสิร์ตต้องมีปุ่ม "ตรวจสอบสถานะกับ Gateway" หรือตั้ง Scheduler คอยเรียก GET /v1/payments/{payment_id} เพื่อนำสถานะจริงมา Reconcile',
    ]),
    ('2. webhook_bad_sig (ลายเซ็นปลอม / Security Attack)', [
        'พฤติกรรม: Payload ใน Body อ้างว่า "status": "COMPLETED" แต่ Header X-Signature ถูกส่งเป็น v1=00000000000000000000... (จำลอง Hacker แกล้งส่งข้อมูลมาหลอก)',
        'การตรวจสอบ: Backend ต้องนำ Secret Key มาคำนวณ HMAC-SHA256 ด้วยตัวเอง เมื่อพบว่าไม่ตรงกัน ต้องสั่ง "ไม่ให้ผ่าน"',
        'สิ่งที่ Backend ต้องทำ: ตอบกลับ HTTP 401 Unauthorized ทันที และห้ามอัปเดตสถานะตั๋วเป็น PAID โดยเด็ดขาด!',
    ]),
    ('3. webhook_replay (Replay Attack / ข้อมูลเก่าเกินเวลา)', [
        'พฤติกรรม: ข้อมูลใน Body ถูกต้อง และลายเซ็นถูกต้อง แต่ timestamp (t) ใน Header เก่ากว่าเวลาปัจจุบันเกิน 5 นาที (300 วินาที)',
        'สิ่งที่ Backend ต้องทำ: ตรวจสอบ abs(time() - $timestamp) > 300 หากเกิน 5 นาที ต้องตอบกลับ HTTP 401 Unauthorized และปฏิเสธการออกตั๋ว',
    ]),
    ('4. webhook_dup:N (ส่ง Webhook ซ้ำหลายครั้ง)', [
        'พฤติกรรม: PayMock จะยิง Webhook ข้อมูลเดิมเข้ามาติดกัน N ครั้ง (เช่น 3 ครั้ง)',
        'สิ่งที่ Backend ต้องทำ: ตรวจสอบ X-Event-Id หรือ payment_id (payload.id) หากเคยประมวลผลไปแล้วในครั้งแรก ครั้งต่อไปต้องตอบ HTTP 200 OK ทันทีโดยไม่รันคำสั่งออกตั๋วซ้ำ',
    ]),
    ('5. webhook_delay:N (หน่วงเวลาส่ง Webhook)', [
        'พฤติกรรม: รายการถูกตัดเงินสำเร็จทันที แต่ Gateway จะหน่วงเวลาก่อนส่ง Webhook ออกไปเป็นเวลา N วินาที',
        'สิ่งที่ Backend ต้องทำ: ในระหว่างที่ยังไม่ได้รับ Webhook ตั๋วต้องค้าง PENDING และเมื่อ Webhook มาถึงจึงเปลี่ยนเป็น PAID อย่างราบรื่น',
    ]),
]

for title, points in sc_details:
    p_t = doc.add_paragraph()
    p_t.paragraph_format.space_before = Pt(4)
    p_t.paragraph_format.space_after = Pt(1)
    run_t = p_t.add_run(title)
    run_t.bold = True
    run_t.font.size = Pt(10)
    run_t.font.color.rgb = RGBColor.from_string('065F46')
    for pt in points:
        p = doc.add_paragraph()
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after = Pt(1)
        p.paragraph_format.left_indent = Cm(0.5)
        run = p.add_run('• ' + pt)
        run.font.size = Pt(9)


add_heading(doc, '5.2 Chaos Mode (ชั้น 1 — ตั้งจาก Admin Panel)', 2)
add_table(doc,
    ['mode', 'พฤติกรรม', 'ระบบคอนเสิร์ตควรทำ'],
    [
        ['normal',        'ทำงานปกติ',                   '—'],
        ['down',          'ตอบ 503 ทุก request',          'แจ้ง "ระบบชำระเงินขัดข้อง"'],
        ['slow:N',        'หน่วง N วินาที',               'แสดง loading ไม่กดซ้ำ'],
        ['timeout:N',     'หน่วง N วินาที (payment สร้างจริง)', 'Retry ด้วย Idempotency-Key เดิม'],
        ['error_rate:N',  'สุ่ม 500 ใส่ N%',             'Retry จำกัดครั้ง'],
        ['rate_limit',    'ตอบ 429 + Retry-After: 30',   'อ่าน Retry-After แล้ว backoff'],
        ['bad_auth',      'ตอบ 401',                      'แจ้ง Admin (ไม่แจ้งผู้ใช้ด้วยข้อความเทคนิค)'],
        ['bad_json',      'ตอบ HTML หรือ JSON พัง',       'ไม่ crash จับเป็น error'],
    ],
    col_widths=[3.5, 5, 8]
)
add_para(doc, 'ลำดับความสำคัญ: Chaos Mode (inbound) > เลขบัตรวิเศษ (ผลจ่าย) > Webhook Mode (outbound)', color='B45309')

doc.add_page_break()

# ═══════════════════════════════════════════════
# 6. MAGIC CARD NUMBERS
# ═══════════════════════════════════════════════
add_heading(doc, '6. Magic Card Numbers', 1)
add_para(doc, 'ใช้ที่หน้า Checkout เมื่อเลือก method = "card" — CVC และวันหมดอายุใส่อะไรก็ได้ (ยกเว้นบัตร ...0069)')
add_table(doc,
    ['หมายเลขบัตร', 'ผล', 'failure_code', 'ระบบควรทำ'],
    [
        ['4242 4242 4242 4242', '✅ COMPLETED',        '—',                'ออกตั๋ว seat → SOLD'],
        ['4000 0000 0000 0002', '❌ FAILED',           'card_declined',    'แจ้งผู้ใช้ ให้ลองบัตรอื่น'],
        ['4000 0000 0000 9995', '❌ FAILED',           'insufficient_funds','แจ้งวงเงินไม่พอ'],
        ['4000 0000 0000 0069', '❌ FAILED',           'expired_card',     'แจ้งบัตรหมดอายุ'],
        ['4000 0000 0000 0127', '❌ FAILED',           'incorrect_cvc',    'แจ้ง CVC ผิด'],
        ['4000 0000 0000 0119', '❌ FAILED',           'processing_error', 'แจ้งระบบขัดข้อง ลองใหม่'],
        ['4100 0000 0000 0019', '🚨 FAILED',           'fraud_suspected',  'บันทึก Anomaly แจ้ง Admin'],
        ['4000 0000 0000 0341', '✅ COMPLETED',        '—',                'ผลเหมือนบัตรสำเร็จ; การหน่วง webhook ตั้งจาก Admin'],
    ],
    col_widths=[4.5, 3, 3.5, 5.5]
)

# ═══════════════════════════════════════════════
# 7. ERROR RESPONSE FORMAT
# ═══════════════════════════════════════════════
add_heading(doc, '7. Error Response Format', 1)
add_para(doc, 'Error response ทุกชนิดมี format เดียวกัน:')

add_heading(doc, '401 Unauthorized', 2, color='991B1B')
add_code(doc, ['{ "error": "unauthorized", "message": "Invalid or missing API key." }'])

add_heading(doc, '422 Validation Error', 2, color='991B1B')
add_code(doc, [
    '{',
    '  "error":   "validation_error",',
    '  "details": {',
    '    "amount": ["The amount field is required."],',
    '    "method": ["The selected method is invalid."]',
    '  }',
    '}',
])

add_heading(doc, '409 Conflict', 2, color='991B1B')
add_code(doc, ['{ "error": "cannot_cancel", "message": "Payment is already settled." }'])

add_heading(doc, '429 Rate Limited', 2, color='991B1B')
add_code(doc, [
    'HTTP/1.1 429 Too Many Requests',
    'Retry-After: 30',
    '',
    '{ "error": "rate_limited", "message": "Retry after 30 seconds." }',
])

add_heading(doc, '503 Service Unavailable', 2, color='991B1B')
add_code(doc, ['{ "error": "service_unavailable", "message": "Gateway is currently down." }'])

add_heading(doc, '7.1 ปรัชญาการออกแบบ HTTP Status Code (ทำไมแต่ละ API ถึงต่างกัน)', 2)
add_para(doc, (
    'ตามมาตรฐาน RESTful API และ RFC 9110 HTTP Status Code ทำหน้าที่บอก Client (ระบบคอนเสิร์ต) '
    'อย่างแม่นยำว่า "ปัญหาเกิดขึ้นจากใคร และควรแก้ไขหรือจัดการต่อไปอย่างไร" โดยแบ่งเป็น 2 กลุ่มใหญ่:'
))
add_table(doc,
    ['กลุ่มรหัส', 'ความหมายหลัก', 'การจัดการของระบบคอนเสิร์ต'],
    [
        ['4xx (Client Error)', 'ปัญหาเกิดจากฝั่งผู้เรียก (ระบบคอนเสิร์ต)', 'ห้าม Retry ซ้ำด้วยข้อมูลเดิม ต้องแก้ไขโค้ดหรือพารามิเตอร์'],
        ['5xx (Server Error)', 'ปัญหาเกิดจากฝั่งผู้ให้บริการ (Gateway)', 'อนุญาตให้ Retry ได้อย่างจำกัดครั้ง (เช่น 1-3 ครั้ง)'],
    ],
    col_widths=[4, 6, 6.5]
)

add_heading(doc, '7.2 ตารางเปรียบเทียบ Error Code ราย Endpoint และวิธีรับมือ', 2)
add_table(doc,
    ['Endpoint', 'HTTP Code', 'Error String', 'สาเหตุแท้จริง', 'สิ่งที่ระบบคอนเสิร์ตต้องทำ'],
    [
        ['POST /v1/payments', '401', 'unauthorized', 'API Key ผิด/ไม่มี', 'เช็ค config .env ห้าม Retry'],
        ['POST /v1/payments', '422', 'validation_error', 'amount <= 0 หรือพารามิเตอร์ขาด', 'เช็ค request body ก่อนส่ง'],
        ['POST /v1/payments', '429', 'rate_limited', 'ยิงถี่เกินไป', 'อ่าน Retry-After แล้วหน่วงเวลาก่อนลองใหม่'],
        ['POST /v1/payments', '503', 'service_unavailable', 'Gateway อยู่ในโหมด down', 'แจ้งผู้ใช้ระบบขัดข้องชั่วคราว'],
        ['GET /v1/payments/{id}', '404', 'not_found', 'ไม่พบ Payment ID นี้ในระบบ', 'เช็ค ID ว่าคัดลอกมาถูกต้องหรือไม่'],
        ['POST /v1/payments/{id}/cancel', '409', 'cannot_cancel', 'รายการจ่ายสำเร็จแล้ว (Settled)', 'ห้ามยกเลิก ปล่อยผ่านเป็น COMPLETED'],
        ['Webhook (POST callback)', '400', 'expired', 'Timestamp ห่างเกิน 5 นาที', 'ระวัง Replay Attack ปฏิเสธทันที'],
        ['Webhook (POST callback)', '401', 'unauthorized', 'HMAC Signature ไม่ตรง', 'ข้อมูลถูกดัดแปลง ปฏิเสธทันที'],
    ],
    col_widths=[4, 2, 3, 4, 3.5]
)

add_heading(doc, '7.3 Hierarchy ลำดับชั้นการตรวจสอบในระบบ PayMock', 2)
add_para(doc, (
    'ระบบ PayMock มีลำดับชั้นการตัดสินใจ (Execution Hierarchy) แบ่งเป็น 4 ชั้นอย่างเด็ดขาด:'
))
steps_hierarchy = [
    'Layer 1 Chaos (ใหญ่สุด): Admin chaos_mode ครอบคำขอเข้า /v1/* และ checkout (503/429/500/ช้า)',
    'Layer 2 Request & Idempotency: ตรวจ body และ Idempotency-Key (ถ้าซ้ำ คืนรายการเดิม)',
    'Layer 3 การชำระเงิน: Magic Card / PromptPay confirm / cancel — ระบบคอนเสิร์ตอ้างอิงด้วย payment_id',
    'Layer 4 Webhook (รอง): Admin webhook_mode global — lost / delay / dup / bad_sig / replay',
]
for s in steps_hierarchy:
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(2)
    p.paragraph_format.left_indent = Cm(0.5)
    run = p.add_run('• ' + s)
    run.font.size = Pt(9.5)

doc.add_page_break()

# ═══════════════════════════════════════════════
# 8. QUICK REFERENCE
# ═══════════════════════════════════════════════
add_heading(doc, '8. Quick Reference', 1)

add_heading(doc, '8.1 Endpoints ทั้งหมด', 2)
add_table(doc,
    ['Method', 'Path', 'หน้าที่'],
    [
        ['GET',  '/v1/payments',              'ตรวจสถานะ API + รายการล่าสุด'],
        ['POST', '/v1/payments',              'สร้างรายการจ่ายเงิน (Idempotent)'],
        ['GET',  '/v1/payments/{id}',         'ดูสถานะรายการ (Polling)'],
        ['POST', '/v1/payments/{id}/cancel',  'ยกเลิกรายการที่ยัง PENDING'],
        ['GET',  '/v1/payments/{id}/qr.svg',  'ภาพ QR (PromptPay) — {id} = payment_id'],
        ['GET',  '/checkout/{id}',            'หน้าจ่ายเงินจำลอง (QR + Card)'],
        ['GET',  '/admin',                    'Admin Dashboard (Chaos + Webhook Mode)'],
        ['GET',  '/api/booking-status',       'Concert: ?payment_id=txn_…'],
        ['POST', '/api/cancel-booking',       'Concert: body { payment_id }'],
        ['POST', '/api/reconcile-booking',    'Concert: body { payment_id }'],
    ],
    col_widths=[2, 5.5, 9]
)

add_heading(doc, '8.2 ข้อมูลสำหรับทดสอบ', 2)
add_table(doc,
    ['รายการ', 'ค่า'],
    [
        ['Base URL',      'http://localhost:8081'],
        ['API Key',       'pm_test_key_concert_system_2024'],
        ['Webhook Secret','whsec_concert_super_secret_2024'],
        ['Admin URL',     'http://localhost:8081/admin'],
        ['Admin Password','admin1234'],
        ['Postman Collection', 'http://localhost:8081/paymock.postman_collection.json'],
    ],
    col_widths=[4, 12.5]
)

add_heading(doc, '8.3 Flow Retry หลัง Timeout (สำคัญ)', 2)
steps_retry = [
    'ส่ง POST /v1/payments พร้อม Idempotency-Key: b_TK2024001',
    'หมดเวลา (timeout) ไม่ได้ response',
    'ส่ง POST /v1/payments อีกครั้งพร้อม Idempotency-Key เดิม: b_TK2024001',
    'PayMock คืน payment เดิม (ไม่สร้างซ้ำ)',
    'นำ qrcode_url หรือผลลัพธ์เดิมไปแสดงผลต่อได้ทันที',
]
for i, s in enumerate(steps_retry, 1):
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(3)
    p.paragraph_format.left_indent = Cm(0.5)
    run = p.add_run(f'{i}. {s}')
    run.font.size = Pt(10)

add_heading(doc, '8.4 Flow Reconcile (Webhook หาย)', 2)
add_para(doc, (
    'Scheduler ของระบบคอนเสิร์ตควรรันทุก 5 นาที เพื่อตรวจ payment ที่ยัง PENDING นานกว่า expires_at: '
    'ถ้า status = COMPLETED → process เหมือนได้ webhook  |  '
    'ถ้า status = EXPIRED → ยกเลิก booking ปลดล็อกที่นั่ง'
))

doc.add_page_break()

# ═══════════════════════════════════════════════
# 9. NATIVE MODAL & DIRECT CARD INTEGRATION
# ═══════════════════════════════════════════════
add_heading(doc, '9. การเชื่อมต่อแบบ Native Modal & Direct Payment (Headless Flow)', 1)
add_para(doc, (
    'ระบบออกแบบเป็น Direct API / Headless Flow โดยสมบูรณ์ ไม่จำเป็นต้องมี checkout_url หรือ return_url:'
))

add_heading(doc, '9.1 ข้อดีของ Headless Direct Flow', 2)
add_table(doc,
    ['วิธีชำระ', 'การทำงาน', 'ผลลัพธ์ที่ได้'],
    [
        ['PromptPay', 'PayMock ส่งคืน qrcode_url (SVG) + bank_simulator_url',
         'เว็บคอนเสิร์ตนำ QR ใส่ใน Native Modal — ไม่ต้องใช้ iframe'],
        ['Credit Card', 'เว็บคอนเสิร์ตส่งข้อมูลบัตรผ่าน card_number/exp_month/exp_year/cvc',
         'ได้ผลทันที: COMPLETED หรือ FAILED'],
    ],
    col_widths=[2.5, 7, 7]
)

add_heading(doc, '9.2 ตัวอย่างโค้ด HTML + JavaScript ฝั่งเว็บคอนเสิร์ต (Native PromptPay Modal)', 2)
add_code(doc, [
    '<!-- 1. Native Modal Container — ไม่ใช้ iframe เลย -->',
    '<div id="paymentModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">',
    '    <div style="background:white; border-radius:24px; padding:24px; width:95%; max-width:440px; text-align:center;">',
    '        <h3>📱 สแกน QR เพื่อชำระเงิน</h3>',
    '',
    '        <!-- แสดงภาพ QR Code จาก qrcode_url (SVG) -->',
    '        <img id="qrImage" src="" alt="PromptPay QR" style="width:240px; height:240px; margin:0 auto; display:block;">',
    '',
    '        <!-- ปุ่มเปิดหน้าจำลอง Mobile Banking -->',
    '        <a id="bankSimulateBtn" href="#" target="_blank"',
    '           style="display:block; margin-top:12px; padding:12px; background:#059669; color:white; border-radius:12px; text-decoration:none; font-weight:bold;">',
    '            📲 เปิดหน้าจำลอง Mobile Banking ↗',
    '        </a>',
    '    </div>',
    '</div>',
    '',
    '<script>',
    'let pollInterval = null;',
    '',
    '// เปิด Modal พร้อม QR และเริ่ม Polling',
    'function openPromptPayModal(qrUrl, simulateUrl, paymentId) {',
    '    document.getElementById("qrImage").src = qrUrl;',
    '    document.getElementById("bankSimulateBtn").href = simulateUrl;',
    '    document.getElementById("paymentModal").style.display = "flex";',
    '',
    '    if (pollInterval) clearInterval(pollInterval);',
    '    pollInterval = setInterval(async () => {',
    '        const res = await fetch("/api/booking-status?payment_id=" + encodeURIComponent(paymentId));',
    '        const data = await res.json();',
    '        if (data.booking && data.booking.status === "PAID") {',
    '            clearInterval(pollInterval);',
    '            closeModal();',
    '            alert("ชำระเงินสำเร็จ! ออกตั๋วคอนเสิร์ตเรียบร้อย");',
    '            window.location.reload();',
    '        }',
    '    }, 1500);',
    '}',
    '',
    'function closeModal() {',
    '    document.getElementById("paymentModal").style.display = "none";',
    '    if (pollInterval) clearInterval(pollInterval);',
    '}',
    '</script>',
])

# ═══════════════════════════════════════════════
# 10. COMPLETE ROUTE CATALOG
# ═══════════════════════════════════════════════
doc.add_page_break()
add_heading(doc, '10. รายการ Route ทั้งหมดและตัวอย่างข้อมูล', 1)
add_para(doc, (
    'ตารางนี้เป็นรายการ route ที่เปิดใช้งานจริง ณ เวอร์ชันเอกสารนี้ ครอบคลุม Merchant API, Checkout, Admin, '
    'Demo, Bank Simulator และ Concert Backend. Route ที่คืนหน้าเว็บจะตอบ HTML หรือ redirect (302); route ที่เป็น API จะตอบ JSON ตามตัวอย่าง.'
))

add_heading(doc, '10.1 PayMock Merchant API และ Public Utility', 2)
add_table(doc,
    ['Method', 'Path', 'ต้องส่ง', 'ผลลัพธ์สำเร็จ', 'กรณีผิดพลาด'],
    [
        ['GET', '/v1/payments', 'Bearer token', '200 JSON: service, status, chaos_mode, webhook_mode, recent[]', '401 token ผิด; 429/500/502/503 เมื่อ Chaos Mode'],
        ['POST', '/v1/payments', 'Bearer, JSON, Idempotency-Key แนะนำ', '201 JSON payment; Card ที่ส่ง card_number อาจ COMPLETED/FAILED ทันที', '401, 422 validation, 429, 500, 502, 503'],
        ['GET', '/v1/payments/{publicId}', 'Bearer token; publicId = response.id', '200 JSON payment', '401, 404, 429, 500, 502, 503'],
        ['POST', '/v1/payments/{publicId}/cancel', 'Bearer token; ไม่มี body', '200 { ok:true, status:"FAILED", failure_code:"cancelled" }', '401, 404, 409, 429, 500, 502, 503'],
        ['GET', '/v1/payments/{publicId}/qr.svg', 'publicId; ไม่ต้อง Bearer', '200 image/svg+xml', '404 เมื่อไม่พบรายการ'],
        ['GET', '/up', 'ไม่มี', '200 { "status":"ok" }', 'ไม่มีกรณีพิเศษจาก route'],
        ['GET', '/', 'ไม่มี', '200 HTML landing page', 'ไม่มี JSON contract'],
    ],
    col_widths=[1.5, 4.8, 4, 5.5, 4.2]
)

add_heading(doc, 'ตัวอย่าง Merchant API ครบกรณีหลัก', 3, color='374151')
add_code(doc, [
    '// 1) PromptPay request',
    'POST /v1/payments',
    'Authorization: Bearer pm_test_key_concert_system_2024',
    'Idempotency-Key: BOOKING-1001',
    '{ "payment_id":"1001", "amount":1200.00, "method":"promptpay", "callback_url":"http://concert_backend:5000/api/webhook", "expires_in":600 }',
    '',
    '// 201 response',
    '{ "id":"txn_01…", "provider_transaction_id":"txn_01…", "payment_id":"1001", "amount":1200, "currency":"THB", "method":"promptpay", "status":"PENDING", "failure_code":null, "expires_at":"2026-10-16T10:10:00+07:00", "paid_at":null, "created_at":"2026-10-16T10:00:00+07:00", "qrcode_url":"http://localhost:8081/v1/payments/txn_01…/qr.svg", "bank_simulator_url":"http://localhost:8081/bank-simulate/txn_01…" }',
    '',
    '// 2) Direct card success request and response',
    '{ "payment_id":"1002", "amount":1200.00, "method":"card", "card_number":"4242424242424242", "exp_month":"12", "exp_year":"28", "cvc":"123", "callback_url":"http://concert_backend:5000/api/webhook" }',
    '{ "id":"txn_02…", "payment_id":"1002", "method":"card", "status":"COMPLETED", "failure_code":null, "paid_at":"2026-10-16T10:00:02+07:00" }',
    '',
    '// 3) Direct card declined response (4000 0000 0000 0002)',
    '{ "id":"txn_03…", "payment_id":"1003", "method":"card", "status":"FAILED", "failure_code":"card_declined", "paid_at":null }',
    '',
    '// 4) Validation and cancellation conflict',
    '{ "error":"validation_error", "details":{ "amount":["The amount field is required."] } }',
    '{ "error":"cannot_cancel", "message":"Payment is already settled and cannot be cancelled." }',
])

add_heading(doc, '10.2 Checkout และ Bank Simulator', 2)
add_table(doc,
    ['Method', 'Path', 'ข้อมูลที่ส่ง', 'Response ตัวอย่าง / ทุกกรณี'],
    [
        ['GET', '/checkout/{publicId}', 'path publicId', '200 HTML หน้าจ่าย; ถ้า settle หรือหมดอายุ แสดง receipt HTML; 404 ถ้าไม่พบ'],
        ['POST', '/checkout/{publicId}/card', 'form: card_number; optional redirect_to_return', '302 -> checkout receipt; PENDING + valid card จะ settle; non-PENDING กลับพร้อม flash error; 404 ถ้าไม่พบ'],
        ['POST', '/checkout/{publicId}/promptpay', 'form: action=pay (default) หรือ fail; optional redirect_to_return', '302 -> checkout receipt; pay => COMPLETED, fail => FAILED/payment_declined; non-PENDING กลับพร้อม flash error'],
        ['POST', '/checkout/{publicId}/simulate', 'JSON/form: status (default COMPLETED), failure_code optional', '200 { "ok":true, "status":"COMPLETED" }; ถ้า non-PENDING: { "ok":false, "message":"Not pending" }; 404 ถ้าไม่พบ'],
        ['GET', '/bank-simulate/{publicId}', 'path publicId', '200 HTML mobile banking simulator; 404 ถ้าไม่พบ'],
        ['POST', '/bank-simulate/{publicId}/pay', 'ไม่มี body', '302 -> bank simulator, flash status=success; ถ้าจ่ายแล้ว flash status=already_processed; 404 ถ้าไม่พบ'],
    ],
    col_widths=[1.5, 5.2, 4.5, 9]
)
add_code(doc, [
    'POST /checkout/txn_01…/simulate',
    'Content-Type: application/json',
    '{ "status":"FAILED", "failure_code":"payment_declined" }',
    '// 200: { "ok":true, "status":"FAILED" }',
])

add_heading(doc, '10.3 Admin Routes', 2)
add_para(doc, 'Admin routes ใช้ session login และ CSRF token ของ Laravel. Request สำเร็จส่วนใหญ่ตอบ 302 redirect กลับ dashboard พร้อม flash message; ไม่ใช่ Merchant API.')
add_table(doc,
    ['Method', 'Path', 'ข้อมูลที่ส่งตัวอย่าง', 'Response / เงื่อนไข'],
    [
        ['GET', '/admin/login', 'ไม่มี', '200 HTML login form'],
        ['POST', '/admin/login', 'form: password=admin1234', '302 -> /admin เมื่อถูกต้อง; 302 กลับ form + validation error เมื่อผิด'],
        ['POST', '/admin/logout', 'CSRF/session', '302 -> /admin/login'],
        ['GET', '/admin', 'authenticated session', '200 HTML dashboard; unauthenticated ถูก redirect โดย middleware'],
        ['POST', '/admin/chaos', 'form: chaos_mode=normal|down|slow:5|timeout:30|error_rate:30|rate_limit|bad_auth|bad_json', '302 กลับ dashboard; invalid value ถูกบังคับเป็น normal'],
        ['POST', '/admin/webhook-mode', 'form: webhook_mode=normal|webhook_lost|webhook_delay:10|webhook_dup:3|webhook_bad_sig|webhook_replay', '302 กลับ dashboard; นี่คือจุดเดียวที่ตั้ง webhook behavior สำหรับทุกรายการ'],
        ['POST', '/admin/scenarios/run', 'form: method=card|promptpay, amount optional, card_number optional', '302 -> /checkout/{publicId}; ไม่รับหรือบันทึก webhook override'],
        ['POST', '/admin/payments/{publicId}/transition', 'form: status=COMPLETED|FAILED|EXPIRED, failure_code optional', '302; invalid status กลับพร้อม error; 404 เมื่อไม่พบ'],
        ['POST', '/admin/payments/{publicId}/resend-webhook', 'ไม่มี body', '302; queue webhook ใหม่โดยใช้ Admin Webhook Mode ปัจจุบัน; 404 เมื่อไม่พบ'],
        ['POST', '/admin/clear-all', 'ไม่มี body', '302; ลบ payments, webhook deliveries และ jobs ทั้งหมด'],
        ['POST', '/admin/webhook-test', 'JSON/form: url, event, scenario=normal|webhook_bad_sig|webhook_replay', '200 JSON { ok,status,body,signature,raw_body } หรือ { ok:false,error }; 422 เมื่อ url/event หาย'],
    ],
    col_widths=[1.5, 5.2, 7, 7]
)
add_code(doc, [
    'POST /admin/webhook-test',
    '{ "url":"http://concert_backend:5000/api/webhook", "event":"payment.completed", "scenario":"normal" }',
    '// 200: { "ok":true, "status":200, "body":"{\\"ok\\":true,...}", "signature":"t=...,v1=...", "raw_body":"{...}" }',
])

add_heading(doc, '10.4 Demo Routes', 2)
add_table(doc,
    ['Method', 'Path', 'ข้อมูลที่ส่ง', 'Response ตัวอย่าง / ทุกกรณี'],
    [
        ['GET', '/demo/modal', 'ไม่มี', '200 HTML demo modal'],
        ['POST', '/demo/create-payment', 'JSON/form optional: amount, method', '200 { "ok":true, "payment_id":"<merchant id>", "provider_transaction_id":"txn_…", "checkout_url":"http://…/checkout/txn_…" }'],
        ['POST', '/api/demo-webhook-sink', 'Webhook JSON ใด ๆ', '200 { "status":"received" }'],
    ],
    col_widths=[1.5, 5.2, 5, 9]
)

add_heading(doc, '10.5 Concert Backend Routes', 2)
add_table(doc,
    ['Method', 'Path', 'ข้อมูลที่ส่ง', 'Response ตัวอย่าง / ทุกกรณี'],
    [
        ['POST', '/api/webhook หรือ /webhooks/payment', 'headers X-Signature, X-Event-Id; PayMock webhook body', '200 { "ok":true, "message":"Webhook received and processed" }; 401 { "error":"invalid_signature", "reason":"bad_signature"|"replay_attack" }'],
        ['POST', '/api/create-booking', 'JSON: seat, amount, method; card เพิ่ม card_number, exp_month, exp_year, cvc', '201 flow => 200 { ok:true,payment_id,status,failure_code,method,qrcode_url,bank_simulator_url }; gateway failure => status จาก gateway + { ok:false,error:"paymock_error",raw,httpCode }'],
        ['POST', '/api/reconcile-booking', 'JSON: { "payment_id":"txn_…" }', '200 { ok:true,reconciled:true,gateway_status,booking_status,payment_id }; 404 { ok:false,error:"booking_or_payment_not_found" }; gateway error => { ok:false,error:"reconcile_failed",httpCode,raw }'],
        ['POST', '/api/cancel-booking', 'JSON: { "payment_id":"txn_…" }', '200 { ok:true,payment_id,booking_status:"FAILED",failure_code:"cancelled" }; 404 not found; gateway conflict/error => { ok:false,error:"cannot_cancel",httpCode,raw }'],
        ['GET', '/api/booking-status?payment_id=txn_…', 'query payment_id', '200 { "booking":{...}|null, "logs":[...] }'],
    ],
    col_widths=[1.5, 5.2, 6.5, 7]
)

# ─── Footer ───
add_para(doc, '')
p_footer = doc.add_paragraph()
p_footer.alignment = WD_ALIGN_PARAGRAPH.CENTER
shade_paragraph(p_footer, 'F3F4F6')
run = p_footer.add_run('PayMock v1.0  ·  Mock Payment Gateway  ·  Concert Ticket System  ·  2026')
run.font.size = Pt(9)
run.font.color.rgb = RGBColor.from_string('6B7280')

# ─── Save ───
import os
here = os.path.dirname(os.path.abspath(__file__))
paths = [
    os.path.join(here, 'PayMock_API_Reference.docx'),
    os.path.join(here, 'public', 'PayMock_API_Reference.docx'),
    os.path.join(here, '..', 'PayMock_API_Reference.docx'),
]
for output_path in paths:
    os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)
    doc.save(output_path)
    print("SUCCESS: Generated " + os.path.abspath(output_path))

