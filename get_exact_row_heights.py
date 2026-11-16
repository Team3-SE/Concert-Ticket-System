from zipfile import ZipFile
from lxml import etree as E
from copy import deepcopy
import subprocess
import json
import re

W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
NS = {"w": W}
def q(tag): return f"{{{W}}}{tag}"
def visible(el): return "".join(el.xpath(".//w:t/text()", namespaces=NS)).strip()

# 1. Load original docx
with ZipFile("/mnt/c/Github/Concert-P-Tum/งานอื่นๆ/datadic/2569-11-16_DD_V2.3.0.docx") as z:
    entries = {info.filename: z.read(info.filename) for info in z.infolist()}

root = E.fromstring(entries["word/document.xml"])
body = root.find(q("body"))
tbls = body.findall(q("tbl"))

# Reconstruct 17 logical tables
logical_tables = []
current_table = None
for t in tbls:
    p = t.getprevious()
    cap = visible(p) if p is not None else ""
    m = re.match(r"(ตารางที่\s*1-\d+\s*[^(]+)(?:\(ต่อ\))?", cap)
    base_name = m.group(1).strip() if m else cap
    is_cont = "(ต่อ)" in cap
    rows = t.findall(q("tr"))
    
    if not is_cont:
        current_table = {
            "name": base_name,
            "header_rows": rows[:6],
            "data_rows": rows[6:],
            "tbl_elem": t
        }
        logical_tables.append(current_table)
    else:
        current_table["data_rows"].extend(rows[6:])

print(f"Reconstructed {len(logical_tables)} logical tables.")

# Build measurement document:
# For each table, we will measure:
# - Header rows 0..5 (6 rows)
# - Each data row
# Dummy row template:
dummy_row = deepcopy(logical_tables[0]["header_rows"][0])
for t in dummy_row.xpath(".//w:t", namespaces=NS):
    t.text = ""

test_body = E.Element(q("body"))
tbl_template = deepcopy(logical_tables[0]["tbl_elem"])
tbl_grid = deepcopy(tbl_template.find(q("tblGrid")))
tbl_pr = deepcopy(tbl_template.find(q("tblPr")))

row_manifest = [] # (table_idx, row_type, row_idx, description)

for ti, lt in enumerate(logical_tables):
    # Header rows
    for hi, hr in enumerate(lt["header_rows"]):
        p_break = E.Element(q("p"))
        pPr = E.SubElement(p_break, q("pPr"))
        E.SubElement(pPr, q("pageBreakBefore"))
        test_body.append(p_break)
        
        t_meas = E.Element(q("tbl"))
        t_meas.append(deepcopy(tbl_pr))
        t_meas.append(deepcopy(tbl_grid))
        t_meas.append(deepcopy(hr))
        t_meas.append(deepcopy(dummy_row))
        test_body.append(t_meas)
        row_manifest.append((ti, "header", hi, f"{lt['name']} header {hi}"))
        
    # Data rows
    for di, dr in enumerate(lt["data_rows"]):
        p_break = E.Element(q("p"))
        pPr = E.SubElement(p_break, q("pPr"))
        E.SubElement(pPr, q("pageBreakBefore"))
        test_body.append(p_break)
        
        t_meas = E.Element(q("tbl"))
        t_meas.append(deepcopy(tbl_pr))
        t_meas.append(deepcopy(tbl_grid))
        t_meas.append(deepcopy(dr))
        t_meas.append(deepcopy(dummy_row))
        test_body.append(t_meas)
        row_manifest.append((ti, "data", di, f"{lt['name']} data {di+1}"))

sectPr = deepcopy(body.find(q("sectPr")))
test_body.append(sectPr)

test_root = deepcopy(root)
test_root.remove(test_root.find(q("body")))
test_root.append(test_body)

entries["word/document.xml"] = E.tostring(test_root, xml_declaration=True, encoding="utf-8", standalone=True)
with ZipFile("measure_all.docx", "w") as z:
    for k, v in entries.items():
        z.writestr(k, v)

print(f"Generated measure_all.docx with {len(row_manifest)} rows to measure.")
