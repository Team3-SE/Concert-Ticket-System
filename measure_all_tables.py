from zipfile import ZipFile
from lxml import etree as E
import re
import subprocess
import json

W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
NS = {"w": W}

def q(tag):
    return f"{{{W}}}{tag}"

def visible(el):
    return "".join(el.xpath(".//w:t/text()", namespaces=NS)).strip()

# 1. Read original docx and extract all 17 logical tables
with ZipFile("/mnt/c/Github/Concert-P-Tum/งานอื่นๆ/datadic/2569-11-16_DD_V2.3.0.docx") as z:
    entries = {info.filename: z.read(info.filename) for info in z.infolist()}

root = E.fromstring(entries["word/document.xml"])
body = root.find(q("body"))
tbls = body.findall(q("tbl"))

logical_tables = []
# Group by base table name
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

print(f"Extracted {len(logical_tables)} logical tables.")
for lt in logical_tables:
    print(f"  {lt['name']}: {len(lt['data_rows'])} data rows")
