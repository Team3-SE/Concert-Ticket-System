import os
import sys
from copy import deepcopy
from datetime import date
from pathlib import Path

import openpyxl
from docx import Document

sys.stdout.reconfigure(encoding="utf-8")

BASE_DIR = Path(r"C:\Github\Concert-P-Tum")
THREAD_ID = "01a065ac-e3d1-7981-89f4-76ee196dd18b"
OUTPUT_DIR = BASE_DIR / "outputs" / THREAD_ID
DOCX_INPUT = Path(r"C:\Users\ttpho\Downloads\TestReport.docx\TestReport.docx")
XLSX_INPUT = Path(r"C:\Github\Concert-P-Tum\งานอื่นๆ\จารเต่าสุดหล่อ\R5_test_cases_revised_for_execution.xlsx")
DOCX_OUTPUT = OUTPUT_DIR / "TestReport_skeleton_from_excel.docx"


def clear_paragraph(paragraph):
    for run in list(paragraph.runs):
        run._element.getparent().remove(run._element)


def set_paragraph_text_keep_style(paragraph, text):
    if paragraph.runs:
        sample = paragraph.runs[0]
        clear_paragraph(paragraph)
        run = paragraph.add_run(text)
        run.style = sample.style
        run.bold = sample.bold
        run.italic = sample.italic
        run.underline = sample.underline
        run.font.name = sample.font.name
        run.font.size = sample.font.size
        if sample.font.color and sample.font.color.rgb:
            run.font.color.rgb = sample.font.color.rgb
    else:
        paragraph.add_run(text)


def set_cell_text_keep_style(cell, text):
    set_paragraph_text_keep_style(cell.paragraphs[0], str(text) if text is not None else "")
    for extra in list(cell.paragraphs[1:]):
        extra._element.getparent().remove(extra._element)


def clone_row(table, row_idx):
    new_tr = deepcopy(table.rows[row_idx]._tr)
    table._tbl.append(new_tr)
    return table.rows[-1]


def remove_row(table, row_idx):
    tr = table.rows[row_idx]._tr
    tr.getparent().remove(tr)


def ensure_rows(table, desired_rows, template_row_idx=1):
    while len(table.rows) < desired_rows:
        clone_row(table, template_row_idx)
    while len(table.rows) > desired_rows:
        remove_row(table, len(table.rows) - 1)


def add_lines(paragraph, lines):
    clear_paragraph(paragraph)
    for idx, line in enumerate(lines):
        run = paragraph.add_run(line)
        if idx < len(lines) - 1:
            run.add_break()


def status_counts(rows):
    counts = {"Pass": 0, "Fail": 0, "Blocked": 0, "Not Run": 0, "N/A": 0}
    for row in rows:
        status = str(row.get("Status") or "Not Run").strip()
        counts[status] = counts.get(status, 0) + 1
    return counts


def load_report_data():
    wb = openpyxl.load_workbook(XLSX_INPUT, data_only=False)
    ws = wb["Test_Execution"]
    headers = [cell.value for cell in ws[1]]
    rows = []
    for values in ws.iter_rows(min_row=2, values_only=True):
        if not values[0]:
            continue
        row = dict(zip(headers, values))
        rows.append(row)

    by_source = {}
    for row in rows:
        source = row.get("Source Sheet") or ""
        by_source.setdefault(source, []).append(row)

    result_rows = []
    total_counts = status_counts(rows)
    for source, items in by_source.items():
        counts = status_counts(items)
        planned = len(items)
        executed = planned - counts.get("Not Run", 0)
        pass_count = counts.get("Pass", 0)
        fail_count = counts.get("Fail", 0)
        blocked = counts.get("Blocked", 0)
        not_run = counts.get("Not Run", 0)
        na = counts.get("N/A", 0)
        execution_pct = f"{executed / planned:.0%}" if planned else ""
        pass_pct = f"{pass_count / executed:.0%}" if executed else "0%"
        fail_pct = f"{fail_count / executed:.0%}" if executed else "0%"
        result_rows.append([source, planned, executed, pass_count, fail_count, blocked, not_run, na, execution_pct, pass_pct, fail_pct])

    defects = []
    if "Defect_Log" in wb.sheetnames:
        defect_ws = wb["Defect_Log"]
        defect_headers = [cell.value for cell in defect_ws[1]]
        for values in defect_ws.iter_rows(min_row=2, values_only=True):
            item = dict(zip(defect_headers, values))
            if item.get("Summary") or item.get("Exec ID") or item.get("Scenario/Test Case ID"):
                defects.append(item)

    return {
        "rows": rows,
        "result_rows": result_rows,
        "total_counts": total_counts,
        "total_planned": len(rows),
        "total_executed": len(rows) - total_counts.get("Not Run", 0),
        "defects": defects,
    }


def fill_docx(data):
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    doc = Document(DOCX_INPUT)

    for paragraph in doc.paragraphs:
        text = paragraph.text.strip()
        if text == "<Test Phase>":
            set_paragraph_text_keep_style(paragraph, "")
        elif "แสดงสถานะต่อเป้าหมายการทดสอบ" in paragraph.text:
            set_paragraph_text_keep_style(
                paragraph,
                "แสดงสถานะต่อเป้าหมายการทดสอบตามข้อมูลใน Excel โดยขณะนี้ยังเป็นโครงรายงาน เนื่องจากรายการทดสอบยังไม่มีผลการ execute จริง",
            )
        elif text == "สรุปวิธีการทดสอบ":
            set_paragraph_text_keep_style(paragraph, "สรุปวิธีการทดสอบจากชีต Test_Execution ใน Excel")
        elif text == "สรุปสิ่งที่ได้ทดสอบและสิ่งที่ยังไม่ได้ทดสอบตามแผน":
            set_paragraph_text_keep_style(paragraph, "สรุปสิ่งที่วางแผนทดสอบและสถานะ Not Run ตามข้อมูลใน Excel")
        elif text == "ระบุข้อบกพร่องที่ยังค้างอยู่และสถานะที่สิ้นสุดการทดสอบ":
            set_paragraph_text_keep_style(paragraph, "ระบุข้อบกพร่องที่ยังค้างอยู่เมื่อมีข้อมูลจริงใน Defect_Log")

    # Document identification table.
    id_table = doc.tables[0]
    set_cell_text_keep_style(id_table.cell(0, 1), "Test Summary Report")
    set_cell_text_keep_style(id_table.cell(1, 1), "")
    set_cell_text_keep_style(id_table.cell(3, 1), "QA")

    # Revision history: author not available from Excel, leave blank.
    rev = doc.tables[1]
    set_cell_text_keep_style(rev.cell(1, 0), date.today().isoformat())
    set_cell_text_keep_style(rev.cell(1, 1), "0.1")
    set_cell_text_keep_style(rev.cell(1, 2), "จัดทำโครง Test Summary Report จากข้อมูลใน Excel")
    set_cell_text_keep_style(rev.cell(1, 3), "")

    # Reference documents.
    ref = doc.tables[2]
    set_cell_text_keep_style(ref.cell(1, 0), "R5_test_cases_revised_for_execution.xlsx")
    set_cell_text_keep_style(ref.cell(1, 1), str(XLSX_INPUT))

    # Review/distribution and glossary have no backing data in Excel; leave template blanks.
    for table_index in [3, 4]:
        table = doc.tables[table_index]
        for row in table.rows[1:]:
            for cell in row.cells:
                set_cell_text_keep_style(cell, "")

    # Test method.
    method = doc.tables[5]
    set_cell_text_keep_style(method.cell(1, 1), "มีไฟล์ Excel พร้อมรายการ Test_Execution, Test Data และ Expected Result")
    set_cell_text_keep_style(method.cell(2, 1), "ใช้ Test Data และ Expected Result จากชีต Test_Execution เพื่อ execute และบันทึกผลจริง")
    set_cell_text_keep_style(method.cell(3, 1), "รายงานนี้จะสมบูรณ์เมื่อมีการกรอก Actual Result, Status และ Defect จริงใน Excel")

    # Tested scope.
    set_cell_text_keep_style(
        doc.tables[6].cell(0, 0),
        "ทดสอบตามข้อมูลใน Excel: R5, All_20_Scenarios, Success Scenarios, Success_SilverToGold และ Success_GoldToPlatinum",
    )
    set_cell_text_keep_style(doc.tables[7].cell(0, 0), "")
    set_cell_text_keep_style(doc.tables[8].cell(0, 0), "")

    # Results table.
    result_table = doc.tables[9]
    ensure_rows(result_table, len(data["result_rows"]) + 2, 1)
    for row_idx, values in enumerate(data["result_rows"], start=1):
        for col_idx, value in enumerate(values):
            set_cell_text_keep_style(result_table.cell(row_idx, col_idx), value)
    total = data["total_counts"]
    total_executed = data["total_executed"]
    total_planned = data["total_planned"]
    total_values = [
        "รวม",
        total_planned,
        total_executed,
        total.get("Pass", 0),
        total.get("Fail", 0),
        total.get("Blocked", 0),
        total.get("Not Run", 0),
        total.get("N/A", 0),
        f"{total_executed / total_planned:.0%}" if total_planned else "",
        f"{total.get('Pass', 0) / total_executed:.0%}" if total_executed else "0%",
        f"{total.get('Fail', 0) / total_executed:.0%}" if total_executed else "0%",
    ]
    for col_idx, value in enumerate(total_values):
        set_cell_text_keep_style(result_table.cell(len(data["result_rows"]) + 1, col_idx), value)

    # Defects summary intro box.
    set_cell_text_keep_style(
        doc.tables[10].cell(0, 0),
        "ยังไม่มี defect จริงใน Excel เนื่องจาก Defect_Log เป็นโครงว่าง และ Test_Execution ยังไม่มีผล Fail/Blocked จากการ execute จริง",
    )

    defect_summary = doc.tables[11]
    ensure_rows(defect_summary, len(data["result_rows"]) + 2, 1)
    for row_idx, result in enumerate(data["result_rows"], start=1):
        source = result[0]
        executed = result[2]
        values = [source, executed, 0, 0, 0]
        for col_idx, value in enumerate(values):
            set_cell_text_keep_style(defect_summary.cell(row_idx, col_idx), value)
    total_defect_values = ["รวม", data["total_executed"], 0, 0, 0]
    for col_idx, value in enumerate(total_defect_values):
        set_cell_text_keep_style(defect_summary.cell(len(data["result_rows"]) + 1, col_idx), value)

    open_defects = doc.tables[12]
    ensure_rows(open_defects, 2, 1)
    for col_idx in range(len(open_defects.columns)):
        set_cell_text_keep_style(open_defects.cell(1, col_idx), "")

    doc.save(DOCX_OUTPUT)
    return DOCX_OUTPUT


if __name__ == "__main__":
    report_data = load_report_data()
    output = fill_docx(report_data)
    print("planned", report_data["total_planned"])
    print("executed", report_data["total_executed"])
    print("not_run", report_data["total_counts"].get("Not Run", 0))
    print("defects", len(report_data["defects"]))
    print(output)
