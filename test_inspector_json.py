import subprocess
import json

ps = """
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $filePath = "C:\\Github\\Concert-P-Tum\\test_output.docx"
    $doc = $word.Documents.Open($filePath, $false, $true)
    $doc.Repaginate()
    
    $results = @()
    for ($t = 1; $t -le $doc.Tables.Count; $t++) {
        $tbl = $doc.Tables.Item($t)
        
        # Caption
        $capPara = $doc.Range($tbl.Range.Start - 1, $tbl.Range.Start - 1).Paragraphs.Item(1)
        $capText = $capPara.Range.Text.Trim()
        $capPage = $capPara.Range.Information(3)
        $capY = [Math]::Round($capPara.Range.Information(6), 1)
        
        # Header (Rows 1..6)
        $r1 = $tbl.Rows.Item(1)
        $r6 = $tbl.Rows.Item(6)
        $rng1 = $r1.Range.Duplicate; $rng1.Collapse(1)
        $rng6 = $r6.Range.Duplicate; $rng6.Collapse(1)
        $hStartPage = $rng1.Information(3)
        $hEndPage = $rng6.Information(3)
        $hStartY = [Math]::Round($rng1.Information(6), 1)
        
        # Data rows (Rows 7..Rows.Count)
        $rowPages = @()
        $rowYs = @()
        for ($r = 7; $r -le $tbl.Rows.Count; $r++) {
            $row = $tbl.Rows.Item($r)
            $rng = $row.Range.Duplicate; $rng.Collapse(1)
            $rowPages += $rng.Information(3)
            $rowYs += [Math]::Round($rng.Information(6), 1)
        }
        
        $results += [PSCustomObject]@{
            TableIndex = $t
            Caption = $capText
            CapPage = $capPage
            CapY = $capY
            HeaderStartPage = $hStartPage
            HeaderEndPage = $hEndPage
            HeaderStartY = $hStartY
            DataRowCount = $rowPages.Count
            RowPages = $rowPages
            RowYs = $rowYs
        }
    }
    
    $results | ConvertTo-Json -Depth 5 -Compress
} finally {
    if ($doc) { $doc.Close(0) }
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
}
"""
with open("inspect_json.ps1", "w", encoding="utf-8-sig") as f:
    f.write(ps)

res = subprocess.run(["powershell.exe", "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", "inspect_json.ps1"], capture_output=True)
out = res.stdout.decode("utf-8", errors="replace").strip()
if res.stderr:
    print("ERR:", res.stderr.decode("utf-8", errors="replace"))

data = json.loads(out)
print(f"Inspected {len(data)} tables.")
for d in data[:5]:
    print(f"Table {d['TableIndex']}: {d['Caption']} | CapPage={d['CapPage']}, HStart={d['HeaderStartPage']}, HEnd={d['HeaderEndPage']}, RowPages={d['RowPages']}")
