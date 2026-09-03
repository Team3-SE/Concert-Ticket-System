import sys
from pathlib import Path

from docx import Document

sys.stdout.reconfigure(encoding="utf-8")

path = Path(r"C:\Github\Concert-P-Tum\outputs\01a065ac-e3d1-7981-89f4-76ee196dd18b\TestReport_skeleton_from_excel.docx")
doc = Document(path)
text = "\n".join(p.text for p in doc.paragraphs)
text += "\n".join(cell.text for table in doc.tables for row in table.rows for cell in row.cells)

print("paragraphs", len(doc.paragraphs))
print("tables", len(doc.tables))
print("output_size", path.stat().st_size)
for needle in [
    "R5_test_cases_revised_for_execution.xlsx",
    "planned",
    "99",
    "Not Run",
    "ยังไม่มี defect จริง",
    "Test_Execution",
]:
    print(needle, needle in text)

result_table = doc.tables[9]
print("result_rows", len(result_table.rows), "cols", len(result_table.columns))
for row in result_table.rows[:4]:
    print([cell.text for cell in row.cells[:4]])
