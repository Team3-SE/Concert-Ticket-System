from zipfile import ZipFile
from lxml import etree as E
from copy import deepcopy
import subprocess

W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
NS = {"w": W}
def q(tag): return f"{{{W}}}{tag}"

with ZipFile("/mnt/c/Github/Concert-P-Tum/งานอื่นๆ/datadic/2569-11-16_DD_V2.3.0.docx") as z:
    entries = {info.filename: z.read(info.filename) for info in z.infolist()}

root = E.fromstring(entries["word/document.xml"])
body = root.find(q("body"))
tbl = body.find(q("tbl")) # Table 1
rows = tbl.findall(q("tr"))

# Create a test document with Table 1 and an extra dummy row
test_tbl = deepcopy(tbl)
dummy_row = deepcopy(rows[0])
for t in dummy_row.xpath(".//w:t", namespaces=NS):
    t.text = ""
test_tbl.append(dummy_row)

test_body = E.Element(q("body"))
test_body.append(test_tbl)
# copy sectPr
sectPr = body.find(q("sectPr"))
test_body.append(deepcopy(sectPr))

test_root = deepcopy(root)
test_root.remove(test_root.find(q("body")))
test_root.append(test_body)

entries["word/document.xml"] = E.tostring(test_root, xml_declaration=True, encoding="utf-8", standalone=True)
with ZipFile("test_measure_doc.docx", "w") as z:
    for k, v in entries.items():
        z.writestr(k, v)

# Now inspect with Word COM
ps = """
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $doc = $word.Documents.Open("C:\\Github\\Concert-P-Tum\\test_measure_doc.docx", $false, $true)
    $doc.Repaginate()
    $t = $doc.Tables.Item(1)
    for ($r = 1; $r -le $t.Rows.Count; $r++) {
        $row = $t.Rows.Item($r)
        $rng = $row.Range.Duplicate
        $rng.Collapse(1)
        Write-Output "Row $($r): Y=$([Math]::Round($rng.Information(6), 2))"
    }
} finally {
    if ($doc) { $doc.Close(0) }
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
}
"""
with open("test_measure.ps1", "w", encoding="utf-8-sig") as f:
    f.write(ps)

res = subprocess.run(["powershell.exe", "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", "test_measure.ps1"], capture_output=True)
print(res.stdout.decode("utf-8", errors="replace"))
if res.stderr:
    print("ERR:", res.stderr.decode("utf-8", errors="replace"))
