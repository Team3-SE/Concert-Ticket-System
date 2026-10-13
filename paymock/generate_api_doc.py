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
    '② ระบบคอนเสิร์ต POST /v1/payments → PayMock คืน payment_id + checkout_url (PENDING)',
    '③ ระบบคอนเสิร์ต Redirect ผู้ใช้ไปที่ checkout_url (หน้าจ่ายเงินของ PayMock)',
    '④ ผู้ใช้สแกน PromptPay QR หรือกรอกเลขบัตรที่หน้า Checkout',
    '⑤ PayMock เปลี่ยนสถานะ → ส่ง Webhook (POST {callback_url}) พร้อม HMAC-SHA256',
    '⑥ ระบบคอนเสิร์ตตรวจ signature → ตรวจ idempotency → อัปเดต booking + seat + ออกตั๋ว',
    '⑦ (สำรอง) Scheduler ของระบบหลักยิง GET /v1/payments/{id} เพื่อ reconcile',
]
for s in steps:
    p = doc.add_paragraph(style='List Bullet')
    p.paragraph_format.left_indent = Cm(0.5)
    p.paragraph_format.space_after = Pt(3)
    run = p.add_run(s)
    run.font.size = Pt(10)

doc.add_paragraph()
add_para(doc,
    '⚠️ สำคัญ: หน้า return_url ที่ผู้ใช้ถูก redirect กลับมา ห้ามเชื่อ query string '
    'ต้องอ่านสถานะจาก Database ของระบบหลักที่ Webhook/Polling เป็นผู้อัปเดตเท่านั้น',
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
        ['X-Mock-Scenario',  'ไม่จำเป็น', 'webhook_delay:30',   'จำลองสถานการณ์เฉพาะรายการ'],
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
    '  "status":     "online",',
    '  "chaos_mode": "normal",',
    '  "recent": [',
    '    {',
    '      "id":           "01JCXYZ...",',
    '      "order_ref":    "b_TK2024001",',
    '      "amount":       1200.00,',
    '      "status":       "COMPLETED",',
    '      "checkout_url": "http://localhost:8081/checkout/01JCXYZ...",',
    '      "paid_at":      "2026-10-13T10:05:32+07:00"',
    '    }',
    '  ]',
    '}',
])

add_heading(doc, 'Response Fields', 3, color='374151')
add_table(doc,
    ['Field', 'Type', 'ความหมาย'],
    [
        ['status',     'string', '"online" เสมอ (ถ้าตอบได้)'],
        ['chaos_mode', 'string', 'mode ปัจจุบัน: normal, down, slow:3 ฯลฯ'],
        ['recent',     'array',  '10 รายการล่าสุด (schema เดียวกับ POST response)'],
    ],
    col_widths=[3.5, 2.5, 10.5]
)

# 3.2 POST /v1/payments
add_heading(doc, '3.2  POST /v1/payments — สร้างรายการจ่ายเงิน', 2, color='065F46')
add_para(doc, 'สร้างรายการชำระเงินใหม่ รองรับ Idempotency เพื่อป้องกันการสร้างรายการซ้ำเมื่อ retry')

add_heading(doc, 'Request', 3, color='374151')
add_code(doc, [
    'POST /v1/payments HTTP/1.1',
    'Authorization: Bearer pm_test_key_concert_system_2024',
    'Content-Type: application/json',
    'Idempotency-Key: b_TK2024001',
    '',
    '{',
    '  "order_ref":    "b_TK2024001",',
    '  "amount":       1200.00,',
    '  "method":       "card",',
    '  "callback_url": "http://localhost:8080/webhooks/paymock",',
    '  "return_url":   "http://localhost:8080/payment/return?booking=b_TK2024001",',
    '  "expires_in":   600,',
    '  "currency":     "THB",',
    '  "metadata":     { "booking_id": 42 }',
    '}',
])

add_heading(doc, 'Request Body Fields', 3, color='374151')
add_table(doc,
    ['Field', 'Type', 'Required', 'ค่าที่ยอมรับ', 'คำอธิบาย'],
    [
        ['order_ref',    'string',  'จำเป็น',      'max 255 chars',          'รหัสอ้างอิง (booking code)'],
        ['amount',       'number',  'จำเป็น',      'min: 1',                 'ยอดเงิน (บาท, ทศนิยมได้)'],
        ['method',       'string',  'จำเป็น',      '"card" หรือ "promptpay"', 'วิธีชำระเงิน'],
        ['callback_url', 'string',  'จำเป็น',      'URL ที่ถูกต้อง',         'URL รับ Webhook จาก PayMock'],
        ['return_url',   'string',  'ไม่จำเป็น',   'URL ที่ถูกต้อง',         'URL redirect ผู้ใช้กลับ'],
        ['expires_in',   'integer', 'ไม่จำเป็น',   'min: 60 (วินาที)',        'อายุก่อนหมดเวลา (default: 600)'],
        ['currency',     'string',  'ไม่จำเป็น',   '3 ตัวอักษร',             'สกุลเงิน (default: "THB")'],
        ['metadata',     'object',  'ไม่จำเป็น',   'JSON object',            'ข้อมูลเพิ่มเติม (ไม่ส่งใน webhook)'],
    ],
    col_widths=[3, 2, 2.5, 3.5, 5.5]
)

add_heading(doc, 'Idempotency', 3, color='374151')
add_para(doc, (
    'ถ้าส่ง Idempotency-Key header → ใช้ค่านั้น  |  '
    'ถ้าไม่ส่ง → ใช้ order_ref เป็น key อัตโนมัติ  |  '
    'ถ้าส่ง key เดิมซ้ำ → คืน payment เดิม ไม่สร้างใหม่ (ใช้ตอน retry หลัง timeout)'
))

add_heading(doc, 'Response 201 Created', 3, color='374151')
add_code(doc, [
    '{',
    '  "id":           "01JCXYZ1234567890ABCDEFGH",',
    '  "order_ref":    "b_TK2024001",',
    '  "amount":       1200.00,',
    '  "currency":     "THB",',
    '  "method":       "card",',
    '  "status":       "PENDING",',
    '  "failure_code": null,',
    '  "checkout_url": "http://localhost:8081/checkout/01JCXYZ1234567890ABCDEFGH",',
    '  "return_url":   "http://localhost:8080/payment/return?booking=b_TK2024001",',
    '  "expires_at":   "2026-10-13T10:10:00+07:00",',
    '  "paid_at":      null,',
    '  "created_at":   "2026-10-13T10:00:00+07:00"',
    '}',
])

add_heading(doc, 'Response Fields', 3, color='374151')
add_table(doc,
    ['Field', 'Type', 'ความหมาย'],
    [
        ['id',           'string (ULID)',   'Payment ID — ใช้อ้างอิงใน API ทุก call ต่อจากนี้'],
        ['status',       'string',          'PENDING / COMPLETED / FAILED / EXPIRED'],
        ['failure_code', 'string | null',   'รหัสเหตุผลเมื่อล้มเหลว (ดูตาราง Failure Codes)'],
        ['checkout_url', 'string',          'URL ที่ต้อง redirect ผู้ใช้ไป (หน้าจ่ายเงิน)'],
        ['expires_at',   'ISO 8601 | null', 'เวลาหมดอายุ'],
        ['paid_at',      'ISO 8601 | null', 'เวลาที่จ่ายสำเร็จ'],
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
        ['3ds_required',      'รอยืนยัน OTP',         'รอ Webhook ห้ามปลดล็อกที่นั่ง'],
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
add_para(doc, 'ใช้สำหรับ Polling หรือ Reconcile เมื่อ Webhook ไม่มาหรือมาช้า')

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
    '  "id":           "01JCXYZ1234567890ABCDEFGH",',
    '  "event":        "payment.completed",',
    '  "order_ref":    "b_TK2024001",',
    '  "amount":       1200.00,',
    '  "currency":     "THB",',
    '  "status":       "COMPLETED",',
    '  "failure_code": null,',
    '  "paid_at":      "2026-10-13T10:05:32+07:00"',
    '}',
])

add_heading(doc, 'Webhook Body Fields', 3, color='374151')
add_table(doc,
    ['Field', 'Type', 'ความหมาย'],
    [
        ['id',           'string (ULID)',   'Payment ID'],
        ['event',        'string',          'ชื่อ event: payment.completed / payment.failed / payment.expired'],
        ['order_ref',    'string',          'รหัสอ้างอิงจากระบบคอนเสิร์ต'],
        ['amount',       'float',           'ยอดเงิน'],
        ['currency',     'string',          'สกุลเงิน'],
        ['status',       'string',          'สถานะใหม่: COMPLETED / FAILED / EXPIRED'],
        ['failure_code', 'string | null',   'รหัสเหตุผล (มีเฉพาะ FAILED)'],
        ['paid_at',      'ISO 8601 | null', 'เวลาจ่ายจริง (มีเฉพาะ COMPLETED)'],
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
add_para(doc, 'ต้องทำก่อน process webhook ทุกครั้ง เพื่อป้องกัน Replay Attack และ Forgery:')

steps_sig = [
    'อ่าน X-Signature header แล้วแยก t (timestamp) และ v1 (signature hex)',
    'ตรวจว่า timestamp ไม่เก่าเกิน 5 นาที (300 วินาที)',
    'คำนวณ HMAC-SHA256 ด้วย raw request body (ก่อน decode JSON)',
    'เปรียบเทียบด้วย hash_equals() (constant-time comparison)',
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
add_para(doc,
    'แนะนำ: endpoint ของคุณควรตอบ 200 OK เร็วที่สุด แล้วค่อย process ใน background queue',
    color='065F46'
)

doc.add_page_break()

# ═══════════════════════════════════════════════
# 5. X-MOCK-SCENARIO
# ═══════════════════════════════════════════════
add_heading(doc, '5. X-Mock-Scenario — ควบคุมพฤติกรรม', 1)
add_para(doc, 'ส่ง header นี้พร้อม POST /v1/payments เพื่อจำลองสถานการณ์เฉพาะรายการ')
add_code(doc, ['X-Mock-Scenario: webhook_delay:30'])

add_heading(doc, '5.1 Scenarios สำหรับ POST /v1/payments', 2)
add_table(doc,
    ['ค่า', 'พฤติกรรม', 'ใช้ทดสอบ'],
    [
        ['normal',          'ปกติ (override chaos ทั้งระบบ)',     'reset'],
        ['webhook_delay:N', 'ส่ง webhook หลัง N วินาที',           'ทดสอบ polling reconcile'],
        ['webhook_lost',    'ไม่ส่ง webhook เลย',                 'ทดสอบ scheduler poll เอง'],
        ['webhook_dup:N',   'ส่ง webhook N ชุด (duplicate)',       'ทดสอบ idempotency'],
        ['webhook_bad_sig', 'ส่ง webhook signature ผิด',           'ทดสอบ signature validation'],
        ['webhook_replay',  'ส่ง timestamp เก่า 1 ชั่วโมง',       'ทดสอบ replay protection'],
        ['auto_pay:N',      'PromptPay จ่ายอัตโนมัติใน N วินาที', 'เดโมโดยไม่กดปุ่ม'],
    ],
    col_widths=[3.5, 5.5, 7.5]
)

add_heading(doc, '5.2 Chaos Mode (ทั้งระบบ — ตั้งจาก Admin Panel)', 2)
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
add_para(doc, 'ลำดับความสำคัญ: X-Mock-Scenario Header > เลขบัตรวิเศษ > Chaos Mode ทั้งระบบ', color='B45309')

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
        ['4000 0000 0000 3220', '🔐 รอ OTP (PENDING)', '3ds_required',     'รอ Webhook ห้ามปลดล็อกที่นั่ง'],
        ['4100 0000 0000 0019', '🚨 FAILED',           'fraud_suspected',  'บันทึก Anomaly แจ้ง Admin'],
        ['4000 0000 0000 0341', '⏳ COMPLETED (ช้า)',  '—',                'Webhook ดีเลย์ 11 นาที ห้าม expire ก่อน'],
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
        ['GET',  '/checkout/{id}',            'หน้าจ่ายเงินจำลอง (QR + Card)'],
        ['GET',  '/admin',                    'Admin Dashboard'],
        ['GET',  '/',                         'Landing Page'],
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
    'ใช้ checkout_url ที่ได้ redirect ผู้ใช้ต่อไปได้เลย',
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

# ─── Footer ───
add_para(doc, '')
p_footer = doc.add_paragraph()
p_footer.alignment = WD_ALIGN_PARAGRAPH.CENTER
shade_paragraph(p_footer, 'F3F4F6')
run = p_footer.add_run('PayMock v1.0  ·  Mock Payment Gateway  ·  Concert Ticket System  ·  2026')
run.font.size = Pt(9)
run.font.color.rgb = RGBColor.from_string('6B7280')

# ─── Save ───
output_path = 'paymock/PayMock_API_Reference.docx'
doc.save(output_path)
print(f"✅ สร้างไฟล์เสร็จ: {output_path}")
