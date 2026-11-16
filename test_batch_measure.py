from zipfile import ZipFile
from lxml import etree as E
from copy import deepcopy
import subprocess
import json

W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
NS = {"w": W}
def q(tag): return f"{{{W}}}{tag}"

with ZipFile("/mnt/c/Github/Concert-P-Tum/งานอื่นๆ/datadic/2569-11-16_DD_V2.3.0.docx") as z:
    entries = {info.filename: z.read(info.filename) for info in z.infolist()}

root = E.fromstring(entries["word/document.xml"])
body = root.find(q("body"))
tbls = body.findall(q("tbl"))

# Let's collect all 17 tables
# To measure every row:
# For each table, we can create small 2-row tables on separate pages.
# Wait, can we put them on the same page if they fit?
# If we put a 2-row table: [row, dummy]
# And check Y of row and Y of dummy:
# If both row and dummy are on the SAME page:
# height = Y(dummy) - Y(row)!
# If dummy wrapped to next page, then height = (page bottom - Y(row)) + ... or we can just ensure pageBreakBefore on each 2-row table!
print("Ready to write batch measurement script.")
