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
    '① ผู้ใช้เลือกที่นั่ง → Concert สร้าง booking และ booking code (booking_ref) เช่น TK-260101-ABCDE ก่อนสร้างรายการจ่ายเงิน',
    '② Concert ส่ง amount, method และ callback_url ให้ PayMock; ข้อมูลประกอบอื่นส่งผ่าน metadata ได้โดย Gateway ไม่ตีความ',
    '③ PromptPay: เว็บคอนเสิร์ตแสดง QR Code จาก qrcode_url ใน Native Modal ของตัวเอง | Card: สร้างสถานะ PENDING แล้ว redirect ไป Hosted Checkout',
    '④ ผู้ใช้สแกน QR ผ่านแอปธนาคาร หรือคลิก bank_simulator_url เพื่อจำลองการโอนเงิน',
    '⑤ PayMock เปลี่ยนสถานะ → ส่ง Webhook (POST {callback_url}) พร้อม HMAC-SHA256; หลังจ่ายบัตร redirect กลับ /booking/{booking_ref} โดยไม่ใส่ payment ID ใน URL',
    '⑥ ระบบคอนเสิร์ตตรวจ signature → ตรวจ idempotency → อัปเดต booking + seat + ออกตั๋ว',
    '⑦ (สำรอง) ระบบคอนเสิร์ตยิง GET /v1/payments/{id} เพื่อ reconcile — ส่ง id ใน path',
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
add_para(doc, 'ทุก protected API request ต้องส่ง Authorization header; QR endpoint เป็น public:')
add_code(doc, ['Authorization: Bearer pm_test_key_concert_system_2024'])

add_heading(doc, '2.1 Headers ที่ใช้บ่อย', 2)
add_table(doc,
    ['Header', 'Required', 'ค่าตัวอย่าง', 'หน้าที่'],
    [
        ['Authorization', 'Required for protected routes', '******', 'Not used by public QR endpoint'],
        ['Accept',           'แนะนำ',     'application/json', 'ใช้ image/svg+xml กับ QR endpoint'],
        ['Content-Type',     'จำเป็น (POST มี JSON body)', 'application/json', 'cancel ไม่มี request body'],
        ['Idempotency-Key',  'ไม่บังคับ', 'attempt-12',         'Optional; เป็นวิธีเดียวที่ใช้ deduplicate POST'],
    ],
    col_widths=[4, 3, 5.5, 4]
)

# ═══════════════════════════════════════════════
# 3. API ENDPOINTS
# ═══════════════════════════════════════════════
add_heading(doc, '3. API Endpoints', 1)

add_heading(doc, '3.0  ต้องส่ง ID ตัวไหนในแต่ละ API?', 2, color='7C2D12')
add_para(doc, (
    'POST /v1/payments ไม่ใช้หรือจัดเก็บ merchant references; metadata เป็น optional และ Gateway ไม่ตีความ fields ภายใน; '
    'เส้นทาง GET, cancel, QR และ checkout รับ provider transaction id ใน path.'
))
add_table(doc,
    ['API / จุดที่ส่งค่า', 'ชื่อ field / path', 'ค่าที่ต้องใช้'],
    [
        ['GET /v1/payments', 'ไม่มี', 'ไม่ต้องส่ง ID'],
        ['POST /v1/payments', 'body.metadata', 'Optional JSON value; Gateway ไม่ตรวจ metadata'],
        ['POST /v1/payments', 'Idempotency-Key', 'Optional; ส่งค่าเดิมเมื่อ retry เพื่อ deduplicate'],
        ['GET /v1/payments/{id}', 'path {id}', 'ส่ง id ของรายการที่ต้องการดู'],
        ['POST /v1/payments/{id}/cancel', 'path {id}', 'ส่ง id ของรายการที่ต้องการยกเลิก'],
        ['GET /v1/payments/{id}/qr.svg', 'path {id}', 'ส่ง id ของรายการที่ต้องการแสดง QR'],
        ['GET /checkout/{id}, POST /payments/{id}', 'path {id}', 'ส่ง id ของรายการที่จะเปิด checkout หรือชำระด้วยบัตร'],
        ['/checkout/{id}/..., /bank-simulate/{id}/...', 'path {id}', 'ส่ง id ของรายการที่ต้องการดำเนินการ'],
        ['Webhook จาก PayMock', 'body.id, body.metadata (ถ้ามี)', 'ใช้ id (txn_...) เป็น provider transaction ID; metadata เป็น optional'],
        ['/api/booking-status, /api/cancel-booking, /api/reconcile-booking (ตัวอย่าง Concert)', 'query/body.payment_id', 'ใช้ชื่อ field payment_id'],
    ],
    col_widths=[5.5, 4, 7.5]
)

# 3.1 GET /v1/payments
add_heading(doc, '3.1  GET /v1/payments — ตรวจสถานะ API', 2, color='065F46')
add_para(doc, 'ใช้ตรวจสอบว่า PayMock ออนไลน์อยู่ก่อนสร้างรายการ และดูรายการล่าสุด 10 รายการ')

add_heading(doc, 'Request', 3, color='374151')
add_code(doc, [
    'GET /v1/payments HTTP/1.1',
    'Accept: application/json',
    'Authorization: Bearer pm_test_key_concert_system_2024',
])

add_heading(doc, 'Response 200 OK', 3, color='374151')
add_code(doc, [
    'HTTP/1.1 200 OK',
    'Content-Type: application/json',
])
add_code(doc, [
    '{',
    '  "service":    "PayMock Gateway API",',
    '  "status":       "online",',
    '  "chaos_mode":   "normal",',
    '  "webhook_mode": "normal",',
    '  "recent": [',
    '    {',
    '      "id":                 "txn_01jcxyz...",',
    '      "metadata": { "source": "concert-app", "context": { "campaign": "summer" } },',
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
        ['chaos_mode',   'string', 'ชั้น 1 — ตั้งที่ Admin: normal, down, slow:N หรือ rate_limit'],
        ['webhook_mode', 'string', 'ชั้น 2 — ตั้งที่ Admin: normal, webhook_lost ฯลฯ'],
        ['recent',       'array',  '10 รายการล่าสุด (schema เดียวกับ POST response)'],
    ],
    col_widths=[3.5, 2.5, 10.5]
)

# 3.2 POST /v1/payments
add_heading(doc, '3.2  POST /v1/payments — สร้างรายการจ่ายเงิน', 2, color='065F46')
add_para(doc, (
    'สร้างรายการชำระเงินใหม่ รองรับ Idempotency เพื่อป้องกันการสร้างรายการซ้ำเมื่อ retry '
    'Idempotency-Key header เป็น optional และเป็นวิธีเดียวที่ใช้ deduplicate POST. หากไม่มี header ทุก request จะสร้างรายการใหม่. '
    'ถ้าส่ง header ระบบใช้ header เป็น key แทน และไม่อ่าน idempotency_key จาก JSON body. '
    'หลังสร้างสำเร็จ response id ที่ขึ้นต้น txn_ เป็น provider transaction ID ใช้ใน GET / cancel / QR / polling และ deduplicate webhook.'
))

add_heading(doc, 'Request', 3, color='374151')
add_para(doc, 'หากไม่ส่ง Idempotency-Key ระบบยังสร้างรายการได้ แต่ทุก POST จะสร้างรายการใหม่; retry attempt เดิมต้องส่ง header key เดิม. txn_... เกิดหลังสร้างสำเร็จ จึงใช้เป็น provider transaction ID สำหรับ GET/reconcile ไม่ใช่ key ของ POST ครั้งแรก.')
add_code(doc, [
    'POST /v1/payments HTTP/1.1',
    'Authorization: Bearer pm_test_key_concert_system_2024',
    'Content-Type: application/json',
    'Accept: application/json',
    'Idempotency-Key: attempt-12',
    '',
    '// PromptPay:',
    '{',
    '  "amount":       1200.00,',
    '  "method":       "promptpay",',
    '  "callback_url": "http://localhost:8080/webhooks/paymock",',
    '  "expires_in":   600,',
    '  "currency":     "THB",',
    '  "metadata":     { "source": "concert-app", "context": { "campaign": "summer" } }',
    '}',
    '',
    '// Credit Card (Hosted Checkout):',
    '{',
    '  "amount":       1200.00,',
    '  "method":       "card",',
    '  "callback_url": "http://localhost:8080/webhooks/paymock",',
    '  "return_url":   "http://localhost:5000/booking/TK-260101-ABCDE",',
    '  "expires_in":   600,',
    '  "metadata":     { "source": "concert-app", "context": { "campaign": "summer" } }',
    '}',
])

add_heading(doc, 'Request Body Fields', 3, color='374151')
add_para(doc, 'metadata เป็น optional JSON value ใดก็ได้; Gateway ไม่ตรวจ metadata และส่งค่ากลับใน response/webhook. เมื่อไม่ได้ส่ง metadata response และ webhook จะไม่ส่ง field นี้.')
add_table(doc,
    ['Field', 'Type', 'Required', 'ค่าที่ยอมรับ', 'คำอธิบาย'],
    [
        ['amount',       'number',  'จำเป็น',      'min: 1',                 'ยอดเงิน (บาท, ทศนิยมได้)'],
        ['method',       'string',  'จำเป็น',      '"card" หรือ "promptpay"', 'วิธีชำระเงิน'],
        ['callback_url', 'string',  'จำเป็น',      'URL ที่ถูกต้อง',         'URL รับ Webhook จาก PayMock'],
        ['return_url',   'string',  'ไม่จำเป็น',   'URL ที่ถูกต้อง',         'Gateway redirect กลับหลัง submit บัตร'],
        ['expires_in',   'integer', 'ไม่จำเป็น',   'min: 60 (วินาที)',        'อายุก่อนหมดเวลา ใช้ได้ทั้งบัตรและ PromptPay (default: 600)'],
        ['currency',     'string',  'ไม่จำเป็น',   '3 ตัวอักษร',             'สกุลเงิน (default: "THB")'],
        ['metadata',     'JSON value', 'ไม่จำเป็น', 'JSON value ใดก็ได้', 'ข้อมูลเพิ่มเติม; Gateway ไม่ตรวจและส่งกลับตามที่รับมา'],
    ],
    col_widths=[3, 2, 2.5, 3.5, 5.5]
)
add_para(doc, 'merchant reference fields เช่น payment_id, booking_ref และ order_ref ไม่ใช่ส่วนหนึ่งของ API contract. Gateway ไม่อ่านหรือแก้ไขข้อมูลภายใน metadata.')

add_heading(doc, 'Idempotency', 3, color='374151')
add_para(doc, (
    'Idempotency-Key header เป็นวิธีเดียวที่ใช้ deduplicate POST; ไม่มีการอ่าน idempotency_key จาก JSON body หรือ metadata. '
    'Idempotency-Key ใช้ภายในและไม่ส่งกลับใน response หรือ webhook. '
    'หากไม่ส่ง header ทุก POST จะสร้างรายการใหม่; '
    'หากส่ง header ให้ใช้ค่าเดิมเมื่อ retry attempt เดิม. '
    'เมื่อ FAILED/EXPIRED ให้สร้าง payment attempt ใหม่พร้อม Idempotency-Key ใหม่.'
))

add_heading(doc, 'Response 201 Created (PromptPay)', 3, color='374151')
add_para(doc, 'URL ใน response ขึ้นกับ method: PromptPay คืน qrcode_url และ bank_simulator_url แต่ไม่คืน checkout_url; Card คืน checkout_url แต่ไม่คืน qrcode_url และ bank_simulator_url.')
add_code(doc, [
    'HTTP/1.1 201 Created',
    'Content-Type: application/json',
])
add_code(doc, [
    '{',
    '  "id":                 "txn_01jcxyz1234567890",',
    '  "amount":             1200.00,',
    '  "currency":           "THB",',
    '  "method":             "promptpay",',
    '  "status":             "PENDING",',
    '  "failure_code":       null,',
    '  "expires_at":         "2026-10-13T10:10:00+07:00",',
    '  "paid_at":            null,',
    '  "created_at":         "2026-10-13T10:00:00+07:00",',
    '  "qrcode_url":         "http://localhost:8081/v1/payments/01JCXYZ.../qr.svg",',
    '  "bank_simulator_url": "http://localhost:8081/bank-simulate/txn_01jcxyz1234567890",',
    '  "metadata": { "source": "concert-app", "context": { "campaign": "summer" } }',
    '}',
])

add_heading(doc, 'Response Fields', 3, color='374151')
add_table(doc,
    ['Field', 'Type', 'ความหมาย'],
    [
        ['id',                  'string (txn_...)', 'Provider transaction ID ใช้ใน path ของ GET / cancel / QR / checkout และ deduplicate webhook'],
        ['metadata',            'JSON value',       'Optional; ข้อมูลจาก request โดยไม่ตรวจหรือแก้ไข'],
        ['status',             'string',          'PENDING / COMPLETED / FAILED / EXPIRED'],
        ['failure_code',       'string | null',   'รหัสเหตุผลเมื่อล้มเหลว (ดูตาราง Failure Codes)'],
        ['checkout_url',       'string (Card)',   'URL Hosted Checkout; ส่งคืนเฉพาะเมื่อ method=card'],
        ['qrcode_url',         'string (PromptPay)', 'URL รูปภาพ SVG QR Code; ส่งคืนเฉพาะเมื่อ method=promptpay'],
        ['bank_simulator_url', 'string (PromptPay)', 'URL หน้าจำลอง Mobile Banking; ส่งคืนเฉพาะเมื่อ method=promptpay'],
        ['expires_at',         'ISO 8601 | null', 'เวลาหมดอายุ = created_at + expires_in (นาฬิกาเซิร์ฟเวอร์)'],
        ['paid_at',            'ISO 8601 | null', 'เวลาจ่ายสำเร็จจริง (เฉพาะ COMPLETED) — ไม่มีฟิลด์ completed_at'],
        ['created_at',         'ISO 8601',        'เวลาสร้างรายการจริง (timezone Asia/Bangkok)'],
    ],
    col_widths=[3.5, 3.5, 9.5]
)
add_para(doc, 'หาก request ไม่มี metadata response จะไม่ใส่ field metadata. การเรียก GET /v1/payments/{id} จะใช้รูปแบบเดียวกัน.')

add_heading(doc, 'Response 201 Created (Credit Card)', 3, color='374151')
add_para(doc, 'ตัวอย่างนี้คืน checkout_url สำหรับ redirect ไป Hosted Checkout และไม่มี qrcode_url หรือ bank_simulator_url.')
add_code(doc, [
    'HTTP/1.1 201 Created',
    'Content-Type: application/json',
])
add_code(doc, [
    '{',
    '  "id":          "txn_01jcxyz1234567890",',
    '  "amount":      1200.00,',
    '  "currency":    "THB",',
    '  "method":      "card",',
    '  "status":      "PENDING",',
    '  "failure_code": null,',
    '  "expires_at":  "2026-10-13T10:10:00+07:00",',
    '  "paid_at":     null,',
    '  "created_at":  "2026-10-13T10:00:00+07:00",',
    '  "checkout_url":"http://localhost:8081/checkout/txn_01jcxyz1234567890",',
    '  "return_url":  "http://localhost:5000/booking/TK-260101-ABCDE",',
    '  "metadata": { "source": "concert-app", "context": { "campaign": "summer" } }',
    '}',
])

add_heading(doc, 'Concert-PayMock: Idempotency ที่ใช้งานจริง', 3, color='374151')
add_para(doc, 'Concert ไม่จำเป็นต้องส่ง merchant references; metadata เป็น optional และ Gateway ไม่อ่านหรือ validate fields ภายใน. Idempotency-Key header เป็น optional และเป็นวิธีเดียวที่ใช้ deduplicate POST. หากไม่มี header gateway จะสร้างรายการใหม่ในทุก request; retry attempt เดิมต้องใช้ header key เดิม. Response id (txn_...) ใช้เป็น provider transaction ID หลังสร้างสำเร็จ ไม่ใช่ key สำหรับ POST ครั้งแรก.')

add_para(doc, (
    'ข้อจำกัดปัจจุบัน: Concert ตั้ง cURL timeout 10 วินาที. ถ้า PayMock สร้าง payment แล้วแต่ Concert ไม่ได้รับ response, '
    'PayMock รองรับการส่งซ้ำด้วย Concert payment id และ key เดิมอยู่แล้ว แต่ Concert ยังไม่มีงาน/route ที่ resend แถวเดิมอัตโนมัติ; '
    'โค้ดปัจจุบันบันทึกแถวนั้นเป็น GATEWAY_ERROR. ดังนั้นห้ามตีความ timeout ว่า FAILED แล้วสร้าง Concert payment ใหม่ทันที. '
    'การแก้ที่ถูกต้องคือตรวจ/retry payment attempt เดิมด้วย key เดิมก่อน; สร้าง attempt ใหม่ได้เมื่อยืนยันว่า FAILED หรือ EXPIRED เท่านั้น.'
), color='B45309')

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
        ['429', 'too_many_requests',  'Chaos mode = rate_limit (ดู Retry-After header)'],
        ['500', 'internal_error',     'ข้อผิดพลาดภายใน gateway'],
        ['503', 'service_unavailable','gateway อยู่ในโหมด down'],
    ],
    col_widths=[1.5, 4, 11]
)

doc.add_page_break()

# 3.3 GET /v1/payments/{id}
add_heading(doc, '3.3  GET /v1/payments/{id} — ดูสถานะรายการ', 2, color='065F46')
add_para(doc, 'ใช้สำหรับ Polling หรือ Reconcile เมื่อ Webhook ไม่มาหรือมาช้า — ส่ง id ใน path')

add_heading(doc, 'Request', 3, color='374151')
add_code(doc, [
    'GET /v1/payments/01JCXYZ1234567890ABCDEFGH HTTP/1.1',
    'Accept: application/json',
    'Authorization: Bearer pm_test_key_concert_system_2024',
])

add_heading(doc, 'Response 200 OK', 3, color='374151')
add_code(doc, [
    'HTTP/1.1 200 OK',
    'Content-Type: application/json',
])
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
add_para(doc, 'ยกเลิกได้เฉพาะรายการที่ยัง PENDING เท่านั้น; ส่ง id ใน path')

add_heading(doc, 'Request', 3, color='374151')
add_code(doc, [
    'POST /v1/payments/01JCXYZ1234567890ABCDEFGH/cancel HTTP/1.1',
    'Accept: application/json',
    'Authorization: Bearer pm_test_key_concert_system_2024',
    '',
    '(ไม่ต้องส่ง Body)',
])

add_heading(doc, 'Response 200 OK', 3, color='374151')
add_code(doc, [
    'HTTP/1.1 200 OK',
    'Content-Type: application/json',
])
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

# 3.5 GET /v1/payments/{id}/qr.svg
add_heading(doc, '3.5  GET /v1/payments/{id}/qr.svg — ดู QR Code', 2, color='065F46')
add_para(doc, 'QR endpoint เป็น public เพื่อให้ browser โหลดภาพผ่าน <img> ได้โดยตรง ไม่ต้องส่ง API key.')
add_heading(doc, 'Request headers', 3, color='374151')
add_code(doc, [
    'GET /v1/payments/txn_01JCXYZ1234567890ABCDEFGH/qr.svg HTTP/1.1',
    'Accept: image/svg+xml',
])
add_heading(doc, 'Response headers', 3, color='374151')
add_code(doc, [
    'HTTP/1.1 200 OK',
    'Content-Type: image/svg+xml',
    'Cache-Control: must-revalidate, no-cache, no-store, private',
])
add_para(doc, 'Response body เป็น SVG; หากไม่พบ payment จะตอบ 404 Not Found.')

# 3.6 Concert backend — ใช้ payment_id
add_heading(doc, '3.6  API ระบบคอนเสิร์ต — booking และ Concert payment', 2, color='065F46')
add_para(doc, (
    'หลัง POST /v1/payments สำเร็จ ให้ใช้ id ใน path '
    'ทั้ง PromptPay และบัตรใช้คีย์นี้กับ polling / ยกเลิก / reconcile'
))
add_table(doc,
    ['Method', 'Path', 'Body / Query', 'หน้าที่'],
    [
        ['POST', '/api/create-booking',     'seat, amount, method, card fields', 'สร้าง booking code (booking_ref) + Concert payment แรก'],
        ['POST', '/api/bookings/{id}/payments', 'method, card fields optional', 'สร้าง Concert payment ใหม่ใน booking เดิมหลัง FAILED/EXPIRED'],
        ['GET',  '/api/booking-status',    '?payment_id=txn_…',            'สถานะตั๋ว + webhook logs'],
        ['POST', '/api/reconcile-booking', '{ "payment_id": "txn_…" }',    'GET จาก PayMock แล้วอัปเดตตั๋ว'],
        ['POST', '/api/cancel-booking',    '{ "payment_id": "txn_…" }',    'เรียก POST /v1/payments/{id}/cancel'],
        ['POST', '/api/webhook',           'PayMock ส่ง payload.id',       'อัปเดตตั๋วตาม payment_id'],
    ],
    col_widths=[2, 5, 5.5, 4]
)
add_para(doc, (
    'เมื่อผู้ใช้เริ่มจอง Concert สร้าง booking code (booking_ref เช่น TK-260101-ABCDE) พร้อม booking ก่อนเรียก PayMock; '
    'รหัสนี้เกิดก่อน payment attempt และคงเดิมตลอด booking. create-booking จะสร้าง Concert payment attempt แรกด้วย. หากบัตรล้มเหลว ให้เรียก '
    '/api/bookings/{bookingId}/payments เพื่อสร้าง Concert payment ใหม่ใต้ booking เดิม. '
    'ระบบอาจส่งข้อมูลเพิ่มเติมใด ๆ ใน metadata ได้; Gateway จะส่งกลับโดยไม่อ่านหรือแก้ไข fields ภายใน. หากต้องการ deduplicate POST ต้องส่ง Idempotency-Key header แยกต่างหาก. '
    'หลังจากสร้างแล้ว endpoint สถานะรับ id ใน path'
), color='B45309')

doc.add_page_break()

# ═══════════════════════════════════════════════
# 4. WEBHOOK
# ═══════════════════════════════════════════════
add_heading(doc, '4. Webhook — สิ่งที่ PayMock ส่งมาหาคุณ', 1)
add_para(doc, (
    'เมื่อสถานะ payment เปลี่ยน PayMock จะ POST ไปที่ callback_url ที่คุณให้ไว้. '
    'ไม่มี fixed 10-second HTTP timeout; หาก delivery ล้มเหลว queue จะ retry ตาม backoff schedule.'
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

add_heading(doc, '4.2 Response จาก Webhook Receiver', 2)
add_para(doc, 'Receiver ควรตอบ 2xx เมื่อรับและจัดการ webhook แล้ว; response body ไม่ถูกใช้เป็น payment result.')
add_code(doc, [
    'HTTP/1.1 200 OK',
    'Content-Type: application/json',
    '',
    '{ "ok": true }',
])

add_heading(doc, '4.3 Webhook Body', 2)
add_code(doc, [
    '{',
    '  "id":           "txn_01JCXYZ1234567890ABCDEFGH",',
    '  "event":        "payment.completed",',
    '  "amount":       1200.00,',
    '  "currency":     "THB",',
    '  "method":       "promptpay",',
    '  "status":       "COMPLETED",',
    '  "failure_code": null,',
    '  "paid_at":      "2026-10-13T10:05:32+07:00",',
    '  "created_at":   "2026-10-13T10:00:00+07:00",',
    '  "metadata": { "source": "concert-app", "context": { "campaign": "summer" } }',
    '}',
])

add_heading(doc, 'Webhook Body Fields', 3, color='374151')
add_para(doc, 'ตัวอย่าง webhook ที่มี metadata แสดงข้อมูลเพิ่มเติมจาก request; metadata เป็น optional และจะไม่ปรากฏใน body หากไม่ได้ส่งมา.')
add_table(doc,
    ['Field', 'Type', 'ความหมาย'],
    [
        ['id',           'string (txn_...)', 'Provider transaction ID ใช้เรียก GET และ deduplicate สถานะ'],
        ['event',        'string',          'ชื่อ event: payment.completed / payment.failed / payment.expired'],
        ['metadata',       'JSON value',   'Optional; metadata จาก request โดยไม่ตรวจหรือแก้ไข'],
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
    'ระบบคอนเสิร์ตตรวจ HMAC signature ก่อน process webhook เพื่อยืนยันว่า payload มาจาก Gateway '
    'และไม่ถูกแก้ไข; การจัดการ timestamp เก่าหรือ event ซ้ำเป็นนโยบายของระบบคอนเสิร์ตเอง:'
))

sig_explanations = [
    'Gateway ส่งข้อมูลและลายเซ็น: PayMock จะส่ง Header X-Signature: t={timestamp},v1={hmac} มาพร้อมกับ Webhook Payload เสมอ',
    'Backend เป็นผู้ตรวจสอบลายเซ็น: ระบบคอนเสิร์ต (Backend ของคุณ) มีหน้าที่คำนวณ HMAC-SHA256 ด้วยตัวเอง โดยใช้ Webhook Secret ที่เก็บไว้เป็นความลับ',
    'กรณี Invalid Signature (ลายเซ็นปลอม): Gateway ไม่ได้เป็นคนบอกว่า "ไม่ผ่าน" แต่ผู้ไม่ประสงค์ดีอาจส่ง Body ที่อ้างว่า status: COMPLETED เข้ามาหลอก! ฝั่ง Backend ของคุณเมื่อตรวจพบว่า HMAC ไม่ตรง จึงมีหน้าที่ "ปฏิเสธไม่ออกตั๋ว" และ "ตอบกลับ HTTP 401 Unauthorized" ด้วยตัวเอง!',
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
    'คำนวณ HMAC-SHA256 จาก timestamp และ raw request body (ก่อน decode JSON) ด้วย secret key ของคุณ',
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
    '    // 2. คำนวณและตรวจ HMAC',
    '    $secret   = config(\'services.paymock.webhook_secret\');',
    '    $rawBody  = $request->getContent();',
    '    $expected = hash_hmac(\'sha256\', "{$timestamp}.{$rawBody}", $secret);',
    '    if (! hash_equals($expected, $signature)) {',
    '        return response(\'Unauthorized\', 401);',
    '    }',
    '',
    '    // 3. Concert จัดการ event ซ้ำตามนโยบายของระบบ',
    '    $eventId = $request->header(\'X-Event-Id\');',
    '    if (WebhookLog::where(\'event_id\', $eventId)->exists()) {',
    '        return response(\'OK\', 200); // รับแล้ว ไม่ต้อง process ซ้ำ',
    '    }',
    '    WebhookLog::create([\'event_id\' => $eventId]);',
    '',
    '    // 4. process ตาม event',
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
add_para(doc, 'PayMock จะ retry webhook ถ้าได้รับ response ที่ไม่ใช่ 2xx หรือเกิด connection error ตาม backoff ด้านล่าง รวมสูงสุด 6 attempts. แต่ละครั้งสร้าง X-Signature timestamp ใหม่และใช้ X-Event-Id เดิม.')
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
add_para(doc, 'Gateway บันทึกผลล่าสุดใน webhook_deliveries: attempts คือจำนวนครั้งที่พยายามส่ง, last_status_code คือ HTTP status ล่าสุด (null หากเชื่อมต่อไม่สำเร็จก่อนมี response), last_error เก็บข้อความเมื่อส่งไม่สำเร็จ และ delivered_at จะถูกตั้งเมื่อได้รับ 2xx เท่านั้น. เมื่อ retry สำเร็จ last_status_code เป็น status 2xx ล่าสุด, last_error ถูกล้าง และ delivered_at ถูกตั้ง. Response body และประวัติผลครั้งก่อนหน้าไม่ได้เก็บใน record นี้.')
add_heading(doc, '4.5 การหมดอายุของ Payment (payment.expired)', 2)
add_para(doc, (
    'เมื่อสร้าง payment จะตั้ง status=PENDING และ expires_at ตาม expires_in (หน่วยวินาที; default 600 วินาที/10 นาที). '
    'paymock_scheduler รัน Laravel schedule:work และเรียก payments:expire ทุก 10 วินาที (ประมาณ 0.17 นาที). '
    'แต่ละรอบเลือกเฉพาะ PENDING ที่ expires_at < เวลาปัจจุบัน แล้วใช้ PaymentService เปลี่ยนเป็น EXPIRED; failure_code และ paid_at เป็น null. '
    'รายการที่จ่ายหรือยกเลิกแล้วจะไม่ถูกเปลี่ยนซ้ำ. จากนั้น PayMock สร้าง webhook payment.expired และส่งงานให้ paymock_queue เพื่อ POST ไป callback_url; '
    'หาก callback ตอบ non-2xx หรือเชื่อมต่อไม่ได้ queue จะ retry ตาม backoff 5, 30, 120, 300 และ 600 วินาที (สูงสุด 6 attempts).'
))
add_para(doc, 'ระบบคอนเสิร์ตตรวจสถานะล่าสุดได้ด้วย GET /v1/payments/{id}; endpoint นี้อ่านสถานะที่บันทึกไว้และไม่สั่ง expire เอง. หาก expires_at ผ่านแล้วแต่ scheduler ยังไม่ถึงรอบ รายการอาจยังเป็น PENDING ชั่วคราว โดยปกติไม่เกินประมาณ 10 วินาทีเมื่อ scheduler ทำงาน. GET /checkout/{id} เป็นอีกกรณีที่เปลี่ยนรายการ PENDING ซึ่งเลยเวลาเป็น EXPIRED ทันที.')
add_para(doc, 'สรุปหน้าที่: paymock_scheduler ตรวจและเปลี่ยนสถานะหมดอายุทุก 10 วินาที; paymock_queue ส่ง webhook และ retry. หาก scheduler หยุด รายการที่หมดเวลาอาจยังค้าง PENDING จนกว่า scheduler จะกลับมาทำงานหรือมีผู้เปิดหน้า Checkout.')
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
    'ระบบคอนเสิร์ตเรียกแค่ API จ่ายเงินจริง: สร้างรายการ, ใส่เลขบัตร, ยกเลิก, GET สถานะด้วย provider transaction id '
    'ความผิดปกติทั้งหมดตั้งที่ http://localhost:8081/admin ไม่ส่ง scenario / X-Mock-Scenario จากฝั่ง merchant'
))
add_table(doc,
    ['ชั้น', 'Setting', 'เมื่อไหร่มีผล'],
    [
        ['1 (ใหญ่สุด)', 'Chaos Mode', 'คำขอเข้า /v1/* และ /checkout/* (ล่ม, ช้า, 429, 500)'],
        ['2 (รอง)',     'Webhook Mode', 'ตอนยิง webhook หลัง settle (lost, delay, dup, bad_sig)'],
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
    ],
    col_widths=[3, 4, 3, 3.5, 4.5]
)

add_heading(doc, 'รายละเอียดเชิงลึกของแต่ละ Webhook Scenario', 3, color='374151')

sc_details = [
    ('1. webhook_lost (Webhook สูญหายระหว่างทาง)', [
        'พฤติกรรม: Gateway ตัดเงินสำเร็จจริง (ในฐานข้อมูลของ PayMock เป็น COMPLETED) แต่เกิดปัญหาเน็ตหลุด ทำให้ Webhook ไปไม่ถึง Backend ของคุณ',
        'ผลกระทบ: ฝั่งคอนเสิร์ตไม่ได้รับ Webhook ทำให้ตั๋วยังคงค้างสถานะ PENDING',
        'วิธีรับมือที่ถูกต้อง: ระบบคอนเสิร์ตต้องมีปุ่ม "ตรวจสอบสถานะกับ Gateway" หรือตั้ง Scheduler คอยเรียก GET /v1/payments/{id} เพื่อนำสถานะจริงมา Reconcile',
    ]),
    ('2. webhook_bad_sig (ลายเซ็นปลอม / Security Attack)', [
        'พฤติกรรม: Payload ใน Body อ้างว่า "status": "COMPLETED" แต่ Header X-Signature ถูกส่งเป็น v1=00000000000000000000... (จำลอง Hacker แกล้งส่งข้อมูลมาหลอก)',
        'การตรวจสอบ: Backend ต้องนำ Secret Key มาคำนวณ HMAC-SHA256 ด้วยตัวเอง เมื่อพบว่าไม่ตรงกัน ต้องสั่ง "ไม่ให้ผ่าน"',
        'สิ่งที่ Backend ต้องทำ: ตอบกลับ HTTP 401 Unauthorized ทันที และห้ามอัปเดตสถานะตั๋วเป็น PAID โดยเด็ดขาด!',
    ]),
    ('3. webhook_dup:N (ส่ง Webhook ซ้ำหลายครั้ง)', [
        'พฤติกรรม: PayMock จะยิง Webhook ข้อมูลเดิมเข้ามาติดกัน N ครั้ง (เช่น 3 ครั้ง)',
        'สิ่งที่ Backend ต้องทำ: ตรวจสอบ X-Event-Id หรือ provider transaction ID จาก payload.id หากเคยประมวลผลแล้ว ครั้งต่อไปต้องตอบ HTTP 200 OK โดยไม่รันคำสั่งออกตั๋วซ้ำ',
    ]),
    ('4. webhook_delay:N (หน่วงเวลาส่ง Webhook)', [
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
        ['down',          'ตอบ 503 ทุก request',          'แจ้ง "Gateway มีปัญหา โปรดลองอีกครั้ง"'],
        ['slow:N',        'หน่วง N วินาที',               'แสดง loading ไม่กดซ้ำ'],
        ['rate_limit',    'ตอบ 429 + Retry-After: 30',    'รอแล้วค่อยลองใหม่'],
    ],
    col_widths=[3.5, 5, 8]
)
add_heading(doc, 'Response Body ของแต่ละ Chaos Mode', 3, color='374151')
add_para(doc, 'ตัวอย่างนี้ใช้กับ API ที่ตอบ JSON (/v1/* หรือ request ที่ส่ง Accept: application/json). normal และ slow:N ใช้ body ตาม endpoint; slow:N เพียงหน่วง N วินาทีก่อนส่ง response.')
add_para(doc, 'normal — ตัวอย่าง POST /v1/payments สำเร็จ (PromptPay), HTTP 201:')
add_code(doc, [
    '{',
    '  "id": "txn_example",',
    '  "metadata": { "source": "concert-app" },',
    '  "amount": 1200,',
    '  "currency": "THB",',
    '  "method": "promptpay",',
    '  "status": "PENDING",',
    '  "failure_code": null,',
    '  "qrcode_url": "http://localhost:8081/v1/payments/txn_example/qr.svg",',
    '  "bank_simulator_url": "http://localhost:8081/bank-simulate/txn_example"',
    '}',
])
add_para(doc, 'slow:N — รอ N วินาที แล้วได้ status และ body ตาม endpoint ปกติ; หาก client timeout ก่อน อาจไม่ได้รับ body.')
add_para(doc, 'down — HTTP 503 Service Unavailable:')
add_code(doc, [
    '{',
    '  "error": "service_unavailable",',
    '  "message": "Gateway มีปัญหา โปรดลองอีกครั้ง"',
    '}',
])
add_para(doc, 'rate_limit — HTTP 429 Too Many Requests; response header Retry-After: 30:')
add_code(doc, [
    '{',
    '  "error": "too_many_requests",',
    '  "message": "Too many requests. Please retry after 30 seconds."',
    '}',
])
add_para(doc, 'Checkout ที่เปิดจาก browser โดยไม่ส่ง Accept: application/json จะแสดงหน้า HTML แทน JSON สำหรับ down และ rate_limit.')
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
    '{ "error": "too_many_requests", "message": "Too many requests. Please retry after 30 seconds." }',
])

add_heading(doc, '503 Service Unavailable', 2, color='991B1B')
add_code(doc, ['{ "error": "service_unavailable", "message": "Gateway มีปัญหา โปรดลองอีกครั้ง" }'])

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
        ['POST /v1/payments', '503', 'service_unavailable', 'Gateway unavailable', 'แจ้ง Gateway มีปัญหา โปรดลองอีกครั้ง'],
        ['GET /v1/payments/{id}', '404', 'not_found', 'ไม่พบ Payment ID นี้ในระบบ', 'เช็ค ID ว่าคัดลอกมาถูกต้องหรือไม่'],
        ['POST /v1/payments/{id}/cancel', '409', 'cannot_cancel', 'รายการจ่ายสำเร็จแล้ว (Settled)', 'ห้ามยกเลิก ปล่อยผ่านเป็น COMPLETED'],
        ['Webhook (POST callback)', '401', 'unauthorized', 'HMAC Signature ไม่ตรง', 'ข้อมูลถูกดัดแปลง ปฏิเสธทันที'],
    ],
    col_widths=[4, 2, 3, 4, 3.5]
)

add_heading(doc, '7.3 Hierarchy ลำดับชั้นการตรวจสอบในระบบ PayMock', 2)
add_para(doc, (
    'ระบบ PayMock มีลำดับชั้นการตัดสินใจ (Execution Hierarchy) แบ่งเป็น 4 ชั้นอย่างเด็ดขาด:'
))
steps_hierarchy = [
    'Layer 1 Chaos (ใหญ่สุด): Admin chaos_mode ครอบคำขอเข้า /v1/* และ checkout ด้วย 503, slow หรือ rate limit',
    'Layer 2 Request & Idempotency: deduplicate เฉพาะด้วย Idempotency-Key header; หากไม่มี header ทุก POST สร้าง payment ใหม่',
    'Layer 3 การชำระเงิน: Magic Card / PromptPay confirm / cancel — ระบบคอนเสิร์ตอ้างอิงด้วย provider transaction id',
    'Layer 4 Webhook (รอง): Admin webhook_mode global — lost / delay / dup / bad_sig',
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
        ['GET',  '/v1/payments/{id}',               'ดูสถานะ — ใช้ id ใน path'],
        ['POST', '/v1/payments/{id}/cancel',        'ยกเลิก — ใช้ id ใน path'],
        ['GET',  '/v1/payments/{id}/qr.svg',        'ภาพ QR — ใช้ id ใน path'],
        ['GET',  '/checkout/{id}',                  'Hosted checkout — ใช้ id ใน path'],
        ['POST', '/payments/{id}',                  'submit บัตร — ใช้ id ใน path'],
        ['GET',  '/admin',                    'Admin Dashboard (Chaos + Webhook Mode)'],
        ['GET',  '/api/booking-status',       'Concert demo: query.payment_id'],
        ['POST', '/api/cancel-booking',       'Concert demo: body.payment_id'],
        ['POST', '/api/reconcile-booking',    'Concert demo: body.payment_id'],
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

add_heading(doc, '8.3 เปลี่ยน URL และ Port', 2)
add_para(doc, 'ค่าตัวอย่างอ้างอิง Docker Compose ปัจจุบัน: PayMock เปิดให้ browser/Postman ผ่าน host port 8081 และ Concert demo ผ่าน host port 5000.')
add_table(doc,
    ['ต้องการเปลี่ยน', 'จุดตั้งค่า', 'ตัวอย่าง'],
    [
        ['Port ที่เครื่อง host ใช้เปิด PayMock', 'ports ของ service paymock ใน paymock/docker-compose.yml (หรือ Compose หลักที่ root)', '"8082:80"'],
        ['URL ที่ PayMock สร้างใน response เช่น checkout_url/qrcode_url', 'APP_URL ของ service paymock ใน Compose', 'http://localhost:8082'],
        ['Port สำหรับเปิด Concert demo ใน browser', 'ports ของ service concert_backend ใน Compose หลักที่ root', '"5001:5000"'],
        ['URL ที่ browser กลับหลังจ่ายบัตร', 'request body return_url; Concert demo ใช้ CONCERT_BROWSER_URL', 'http://localhost:5001/booking/TK-260101-ABCDE'],
        ['URL ที่ Postman เรียก', 'collection variables baseUrl (PayMock), concertUrl (Concert)', 'http://localhost:8082; http://localhost:5001'],
    ],
    col_widths=[4.5, 7.5, 5]
)
add_para(doc, 'เปลี่ยน PayMock host port 8081 เป็น 8082: ตั้ง ports เป็น "8082:80", APP_URL เป็น http://localhost:8082 และ Postman baseUrl ให้ตรงกัน. เลข 80 ด้านขวาคือ port ภายใน container และคงเดิมเมื่อเปลี่ยนเฉพาะ host port.')
add_para(doc, 'Concert ติดต่อ PayMock ผ่าน Docker network ด้วย PAYMOCK_URL=http://paymock:80/v1/payments; ใช้ service name และ internal port จึงไม่เปลี่ยนตาม host port. หากเปลี่ยน Concert browser port เป็น 5001 ให้ตั้ง ports เป็น "5001:5000", CONCERT_BROWSER_URL=http://localhost:5001 และ Postman concertUrl=http://localhost:5001.')
add_para(doc, 'เมื่อเรียก PayMock โดยตรง ให้ส่ง return_url เป็น public URL ที่ browser เข้าถึงได้ เช่น http://localhost:5001/booking/{booking_ref}; อย่าใช้ Docker service name ใน return_url.')

add_heading(doc, '8.4 Flow Retry หลัง Timeout (สำคัญ)', 2)
steps_retry = [
    'ส่ง POST /v1/payments พร้อม Idempotency-Key header เช่น attempt-b_TK2024001; metadata เป็น optional และใช้ส่งข้อมูลเพิ่มเติมได้',
    'หมดเวลา (timeout) ไม่ได้ response',
    'ส่ง POST /v1/payments อีกครั้งด้วย Idempotency-Key header เดิม',
    'PayMock คืน payment เดิม (ไม่สร้างซ้ำ)',
    'นำ qrcode_url หรือผลลัพธ์เดิมไปแสดงผลต่อได้ทันที',
]
for i, s in enumerate(steps_retry, 1):
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(3)
    p.paragraph_format.left_indent = Cm(0.5)
    run = p.add_run(f'{i}. {s}')
    run.font.size = Pt(10)
add_para(doc, 'หากไม่ส่ง Idempotency-Key จะไม่มี key ที่คงเดิมสำหรับ retry; gateway จะสร้าง payment ใหม่.')

add_heading(doc, '8.5 Flow Reconcile (Webhook หาย)', 2)
add_para(doc, 'Reconcile ทำฝั่งระบบ Concert เพื่ออ่านสถานะจริงจาก PayMock และอัปเดตฐานข้อมูล Concert เมื่อ webhook หายหรือมาช้า; ไม่ใช่คำสั่งที่เปลี่ยนสถานะใน PayMock.')
add_code(doc, [
    'Concert user กดปุ่ม Reconcile (ใน demo) หรือ production scheduled job เลือก payment ที่ต้องตรวจ',
    'POST /api/reconcile-booking { "payment_id":"<PayMock id>" }  // endpoint ของ Concert demo',
    'Concert เรียก GET /v1/payments/{id} พร้อม Authorization ไปยัง PayMock',
    'PayMock คืน status ปัจจุบันที่บันทึกอยู่; GET ไม่ได้ expire payment เอง',
    'เมื่อ HTTP 200: Concert อัปเดต concert_payments.status/failure_code และ bookings.status/paid_at',
    'COMPLETED -> booking PAID; FAILED -> booking FAILED; EXPIRED -> booking EXPIRED',
    'PENDING หรือเรียก Gateway ไม่สำเร็จ -> คงข้อมูลเดิม/รายงาน reconcile_failed และตรวจซ้ำภายหลัง',
])
add_para(doc, 'PayMock demo มีปุ่มเรียก reconcile; ไม่พบ scheduler ฝั่ง Concert ที่ตั้งทุก 5 นาทีใน implementation นี้. หากนำไปใช้จริง ให้ทำ scheduled job ฝั่ง Concert เอง และทำการอัปเดตซ้ำได้อย่างปลอดภัย (idempotent).')

doc.add_page_break()

# ═══════════════════════════════════════════════
# 9. NATIVE PROMPTPAY & HOSTED CARD CHECKOUT
# ═══════════════════════════════════════════════
add_heading(doc, '9. การเชื่อมต่อแบบ Native PromptPay และ Hosted Card Checkout', 1)
add_para(doc, (
    'booking_ref คือ booking code ที่ระบบ Concert สร้างพร้อม booking ก่อนสร้าง payment เช่น TK-260101-ABCDE; '
    'ค่านี้คงเดิมตลอด booking แม้ผู้ใช้เริ่มชำระใหม่. metadata เป็นข้อมูลเพิ่มเติมและไม่ถูกใช้เป็น idempotency key; POST Idempotency-Key เป็น optional. เส้นทางดูหรือดำเนินการกับรายการใช้ id ใน path. '
    'PromptPay ใช้ Native Modal ได้ ส่วนบัตรใช้ Hosted Checkout: สร้าง payment เป็น PENDING, redirect browser ไปที่ checkout_url, '
    'ส่งฟอร์มบัตรไปยัง POST /payments/{id}, แล้ว Gateway redirect กลับ return_url ที่เป็น /booking/{booking_ref} โดยไม่แนบ payment ID. '
    'หน้าจองใช้ booking code หา payment ล่าสุดในฐานข้อมูลและแสดงเฉพาะ booking code ให้ผู้ใช้'
))

add_heading(doc, '9.1 Flow การชำระเงิน', 2)
add_table(doc,
    ['วิธีชำระ', 'การทำงาน', 'ผลลัพธ์ที่ได้'],
    [
        ['PromptPay', 'PayMock ส่งคืน qrcode_url (SVG) + bank_simulator_url',
         'เว็บคอนเสิร์ตนำ QR ใส่ใน Native Modal — ไม่ต้องใช้ iframe'],
        ['Credit Card', 'redirect ไปที่ checkout_url ของ PayMock เพื่อกรอกบัตร แล้ว form POST /payments/{id}',
         'Gateway กลับ /booking/{booking_ref} โดยไม่มี payment ID ใน URL; หน้า booking โหลด payment จาก DB'],
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
    ['Method', 'Path', 'ใช้เมื่อ', 'ต้องส่ง', 'ผลลัพธ์สำเร็จ', 'กรณีผิดพลาด'],
    [
        ['GET', '/v1/payments', 'ตรวจ health และรายการล่าสุด', 'Bearer token', '200 JSON: service, status, chaos_mode, webhook_mode, recent[]', '401 token ผิด; 429/500/502/503 เมื่อ Chaos Mode'],
        ['POST', '/v1/payments', 'เริ่ม payment ใหม่ หรือ retry request ที่ timeout', 'Bearer, JSON; Idempotency-Key optional; ใช้ header เดิมเมื่อ retry', '201 JSON PENDING; URL ตาม method', '401, 422 validation, 429, 500, 502, 503'],
        ['GET', '/v1/payments/{id}', 'poll/reconcile สถานะ transaction', 'Bearer token; path: id', '200 JSON payment', '401, 404, 429, 500, 502, 503'],
        ['POST', '/v1/payments/{id}/cancel', 'ยกเลิกเฉพาะรายการที่ยัง PENDING', 'Bearer token; ไม่มี body', '200 { ok:true, status:"FAILED", failure_code:"cancelled" }', '401, 404, 409, 429, 500, 502, 503'],
        ['GET', '/v1/payments/{id}/qr.svg', 'นำ QR PromptPay ไปแสดงในหน้า merchant', 'id; ไม่ต้อง Bearer', '200 image/svg+xml', '404 เมื่อไม่พบรายการ'],
        ['GET', '/up', 'healthcheck ของ Docker/load balancer', 'ไม่มี', '200 { "status":"ok" }', 'ไม่มีกรณีพิเศษจาก route'],
        ['GET', '/', 'เปิดหน้าข้อมูลและลิงก์ demo/admin', 'ไม่มี', '200 HTML landing page', 'ไม่มี JSON contract'],
    ],
    col_widths=[1.3, 4, 3.8, 3.5, 4.5, 3.3]
)

add_heading(doc, 'ตัวอย่าง Merchant API ครบกรณีหลัก', 3, color='374151')
add_code(doc, [
    '// 1) PromptPay request',
    'POST /v1/payments',
    'Authorization: Bearer pm_test_key_concert_system_2024',
    '{ "amount":1200.00, "method":"promptpay", "callback_url":"http://concert_backend:5000/api/webhook", "expires_in":600 }',
    '',
    '// 201 response',
    '{ "id":"txn_01…", "amount":1200, "currency":"THB", "method":"promptpay", "status":"PENDING", "failure_code":null, "expires_at":"2026-10-16T10:10:00+07:00", "paid_at":null, "created_at":"2026-10-16T10:00:00+07:00", "qrcode_url":"http://localhost:8081/v1/payments/txn_01…/qr.svg", "bank_simulator_url":"http://localhost:8081/bank-simulate/txn_01…" }',
    '',
    '// 2) Hosted card: create PENDING then redirect browser to checkout_url',
    '{ "amount":1200.00, "method":"card", "return_url":"http://localhost:5000/payment/return", "callback_url":"http://concert_backend:5000/api/webhook" }',
    '{ "id":"txn_02…", "method":"card", "status":"PENDING", "checkout_url":"http://localhost:8081/checkout/txn_02…", "return_url":"http://localhost:5000/payment/return" }',
    '// Browser: POST /payments/txn_02… with card form → 302 http://localhost:5000/booking/TK-260101-ABCDE',
    '',
    '// 3) Invalid/declined card is shown on the hosted page; booking checks DB status after the redirect',
    '',
    '// 4) Validation and cancellation conflict',
    '{ "error":"validation_error", "details":{ "amount":["The amount field is required."] } }',
    '{ "error":"cannot_cancel", "message":"Payment is already settled and cannot be cancelled." }',
])

add_heading(doc, '10.2 Checkout และ Bank Simulator', 2)
add_table(doc,
    ['Method', 'Path', 'ใช้เมื่อ', 'ข้อมูลที่ส่ง', 'Response ตัวอย่าง / ทุกกรณี'],
    [
        ['GET', '/checkout/{id}', 'เปิด UI ชำระเงิน', 'path: id', '200 HTML หน้าจ่าย; ถ้า settle หรือหมดอายุ แสดง receipt HTML; 404 ถ้าไม่พบ'],
        ['POST', '/payments/{id}', 'ส่งบัตรจาก hosted checkout', 'path: id; form: card_number, expiry, cvc, cardholder, redirect_to_return=1', '302 -> return_url (เช่น /booking/{booking_ref}, ไม่มี payment ID); PENDING + valid card จะ settle; 404 ถ้าไม่พบ'],
        ['POST', '/checkout/{id}/promptpay', 'ยืนยัน/ปฏิเสธ PromptPay ใน UI', 'path: id; form: action=pay (default) หรือ fail; optional redirect_to_return', '302 -> checkout receipt; pay => COMPLETED, fail => FAILED/payment_declined; non-PENDING กลับพร้อม flash error'],
        ['POST', '/checkout/{id}/simulate', 'ทดสอบเปลี่ยนผล payment ผ่าน API', 'path: id; JSON/form: status (default COMPLETED), failure_code optional', '200 { "ok":true, "status":"COMPLETED" }; ถ้า non-PENDING: { "ok":false, "message":"Not pending" }; 404 ถ้าไม่พบ'],
        ['GET', '/bank-simulate/{id}', 'เปิดแอปธนาคารจำลองจาก QR', 'path: id', '200 HTML mobile banking simulator; 404 ถ้าไม่พบ'],
        ['POST', '/bank-simulate/{id}/pay', 'กดจ่ายจากธนาคารจำลอง', 'path: id; ไม่มี body', '302 -> bank simulator, flash status=success; ถ้าจ่ายแล้ว flash status=already_processed; 404 ถ้าไม่พบ'],
    ],
    col_widths=[1.3, 4.4, 3.5, 4, 6.5]
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
    ['Method', 'Path', 'ใช้เมื่อ', 'ข้อมูลที่ส่งตัวอย่าง', 'Response / เงื่อนไข'],
    [
        ['GET', '/admin/login', 'เปิดหน้า sign in ของผู้ดูแล', 'ไม่มี', '200 HTML login form'],
        ['POST', '/admin/login', 'เริ่ม session Admin', 'form: password=admin1234', '302 -> /admin เมื่อถูกต้อง; 302 กลับ form + validation error เมื่อผิด'],
        ['POST', '/admin/logout', 'จบ session Admin', 'CSRF/session', '302 -> /admin/login'],
        ['GET', '/admin', 'ดู payments, deliveries และ config', 'authenticated session', '200 HTML dashboard; unauthenticated ถูก redirect โดย middleware'],
        ['POST', '/admin/chaos', 'เลือกสถานะ Gateway', 'form: chaos_mode=normal|down|slow|rate_limit; delay_seconds=0..3600 สำหรับ slow', '302 กลับ dashboard; down ตอบ 503 พร้อมข้อความสำหรับลูกค้า; invalid value ถูกบังคับเป็น normal'],
        ['POST', '/admin/webhook-mode', 'เลือก anomaly ของ webhook ทั้งระบบ', 'form: webhook_mode=normal|webhook_lost|webhook_dup:3|webhook_bad_sig', '302 กลับ dashboard; ไม่มี webhook delay 10 วินาทีตายตัว และนี่คือจุดเดียวที่ตั้ง webhook behavior สำหรับทุกรายการ'],
        ['POST', '/admin/scenarios/run', 'สร้างรายการ demo และเปิด checkout', 'form: method=card|promptpay, amount optional, card_number optional', '302 -> /checkout/{id}; ไม่รับหรือบันทึก webhook override'],
        ['POST', '/admin/payments/{id}/transition', 'บังคับสถานะเพื่อทดสอบ', 'form: status=COMPLETED|FAILED|EXPIRED, failure_code optional', '302; invalid status กลับพร้อม error; 404 เมื่อไม่พบ'],
        ['POST', '/admin/payments/{id}/resend-webhook', 'ทดสอบ receiver idempotency', 'ไม่มี body', '302; queue webhook ใหม่โดยใช้ Admin Webhook Mode ปัจจุบัน; 404 เมื่อไม่พบ'],
        ['POST', '/admin/clear-all', 'reset data สำหรับ demo', 'ไม่มี body', '302; ลบ payments, webhook deliveries และ jobs ทั้งหมด'],
        ['POST', '/admin/webhook-test', 'ยิง signed/invalid webhook ด้วยตนเอง', 'JSON/form: url, event, scenario=normal|webhook_bad_sig', '200 JSON { ok,status,body,signature,raw_body } หรือ { ok:false,error }; 422 เมื่อ url/event หาย'],
    ],
    col_widths=[1.3, 4.1, 3.7, 5.8, 5.5]
)
add_code(doc, [
    'POST /admin/webhook-test',
    '{ "url":"http://concert_backend:5000/api/webhook", "event":"payment.completed", "scenario":"normal" }',
    '// 200: { "ok":true, "status":200, "body":"{\\"ok\\":true,...}", "signature":"t=...,v1=...", "raw_body":"{...}" }',
])

add_heading(doc, '10.4 Demo Routes', 2)
add_table(doc,
    ['Method', 'Path', 'ใช้เมื่อ', 'ข้อมูลที่ส่ง', 'Response ตัวอย่าง / ทุกกรณี'],
    [
        ['GET', '/demo/modal', 'เปิดตัวอย่าง booking demo', 'ไม่มี', '200 HTML demo; PromptPay ใช้ modal, Card redirect'],
        ['GET', '/demo/bookings/{id}', 'หน้า booking หลังกลับจาก Card Checkout', 'path: id; ไม่มี payment_id query', '200 HTML โดยอ่าน payment ล่าสุดจาก DB'],
        ['POST', '/demo/create-payment', 'สร้าง payment สำหรับ demo', 'JSON/form optional: amount, method', '200 { "ok":true, "id":"txn_…", "redirect_url":"…/checkout/txn_…", "return_path":"…/demo/bookings/txn_…" }'],
        ['POST', '/api/demo-webhook-sink', 'endpoint รับ webhook สำหรับทดลอง', 'Webhook JSON ใด ๆ', '200 { "status":"received" }'],
    ],
    col_widths=[1.3, 4.6, 4.3, 4.2, 6.1]
)

add_heading(doc, '10.5 Concert Backend Routes', 2)
add_table(doc,
    ['Method', 'Path', 'ใช้เมื่อ', 'ข้อมูลที่ส่ง', 'Response ตัวอย่าง / ทุกกรณี'],
    [
        ['POST', '/api/webhook หรือ /webhooks/payment', 'PayMock แจ้งผลเข้า backend', 'headers X-Signature, X-Event-Id; PayMock webhook body', '200 { "ok":true, "message":"Webhook received and processed" }; 401 { "error":"invalid_signature", "reason":"bad_signature" }'],
        ['POST', '/api/create-booking', 'สร้าง booking และ Concert payment แรก', 'JSON: seat, amount, method; card ไม่ส่งข้อมูลบัตร', '200 { ok:true,booking_id,concert_payment_id,payment_id:"txn_…",checkout_url,status,... }; card redirect ไป Hosted Checkout'],
        ['POST', '/api/bookings/{bookingId}/payments', 'สร้าง Concert payment ใหม่ใน booking เดิมหลัง FAILED/EXPIRED', 'JSON: method; card ไม่ส่งข้อมูลบัตร', '200 { ok:true,booking_id,concert_payment_id,payment_id:"txn_…",checkout_url,status,... }; 404 booking_not_found; 409 booking_already_paid'],
        ['POST', '/api/reconcile-booking', 'กู้สถานะเมื่อ webhook หายหรือมาช้า', 'JSON: { "payment_id":"txn_…" }', '200 { ok:true,reconciled:true,gateway_status,booking_status,payment_id }; 404 { ok:false,error:"booking_or_payment_not_found" }; gateway error => { ok:false,error:"reconcile_failed",httpCode,raw }'],
        ['POST', '/api/cancel-booking', 'ผู้ใช้ยกเลิกรายการ PENDING', 'JSON: { "payment_id":"txn_…" } (id)', '200 { ok:true,payment_id,booking_status:"FAILED",failure_code:"cancelled" }; 404 not found; gateway conflict/error => { ok:false,error:"cannot_cancel",httpCode,raw }'],
        ['GET', '/api/booking-status?payment_id=txn_…', 'หน้าเว็บ polling booking และดู Concert payments/logs', 'query.payment_id', '200 { "booking":{...}|null, "payments":[...], "logs":[...] }'],
    ],
    col_widths=[1.3, 4.5, 3.8, 5.4, 5.5]
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
