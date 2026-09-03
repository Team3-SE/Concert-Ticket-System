import sys
from pathlib import Path

import openpyxl
from docx import Document
from docx.table import Table
from docx.text.paragraph import Paragraph

sys.stdout.reconfigure(encoding="utf-8")

DOCX = Path(r"C:\Users\ttpho\Downloads\TestReport.docx\TestReport.docx")
XLSX = Path(r"C:\Github\Concert-P-Tum\งานอื่นๆ\จารเต่าสุดหล่อ\R5_test_cases_revised_for_execution.xlsx")


def iter_block_items(parent):
    for child in parent.element.body.iterchildren():
        if child.tag.endswith("}p"):
            yield Paragraph(child, parent)
        elif child.tag.endswith("}tbl"):
            yield Table(child, parent)


print("=== TestReport.docx ===")
doc = Document(DOCX)
print("paragraphs", len(doc.paragraphs))
print("tables", len(doc.tables))
table_idx = 0
para_idx = 0
for block_idx, block in enumerate(iter_block_items(doc)):
    if isinstance(block, Paragraph):
        text = block.text.strip().replace("\n", " | ")
        if text:
            print(f"B{block_idx:03d} P{para_idx:03d}: {text[:220]}")
        para_idx += 1
    else:
        print(f"B{block_idx:03d} T{table_idx:03d}: rows={len(block.rows)} cols={len(block.columns)}")
        for r_idx, row in enumerate(block.rows[:8]):
            vals = [cell.text.strip().replace("\n", " | ")[:120] for cell in row.cells]
            print(f"  R{r_idx}: {vals}")
        table_idx += 1

print("=== Excel ===")
wb = openpyxl.load_workbook(XLSX, data_only=False)
print("sheets", wb.sheetnames)
for ws in wb.worksheets:
    print("SHEET", ws.title, ws.max_row, ws.max_column)
    for row in ws.iter_rows(min_row=1, max_row=min(ws.max_row, 8), values_only=True):
        print(row)
    print("---")
