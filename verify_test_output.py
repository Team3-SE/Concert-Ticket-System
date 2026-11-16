import subprocess
import json

ps = """
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $filePath = "C:\\Github\\Concert-P-Tum\\test_output.docx"
    $doc = $word.Documents.Open($filePath, $false, $true)
    $doc.Repaginate()
    $pages = $doc.ComputeStatistics(2)
    Write-Output "PAGE_COUNT:$pages"
    
    for ($t = 1; $t -le $doc.Tables.Count; $t++) {
        $tbl = $doc.Tables.Item($t)
        $r1 = $tbl.Rows.Item(1)
        $rN = $tbl.Rows.Item($tbl.Rows.Count)
        $rng1 = $r1.Range.Duplicate
        $rng1.Collapse(1)
        $rngN = $rN.Range.Duplicate
        $rngN.Collapse(1)
        $p1 = $rng1.Information(3)
        $y1 = [Math]::Round($rng1.Information(6), 1)
        $pN = $rngN.Information(3)
        $yN = [Math]::Round($rngN.Information(6), 1)
        
        $capPara = $doc.Range($tbl.Range.Start - 1, $tbl.Range.Start - 1).Paragraphs.Item(1)
        $capText = $capPara.Range.Text.Trim()
        $capPage = $capPara.Range.Information(3)
        $capY = [Math]::Round($capPara.Range.Information(6), 1)
        
        Write-Output "TABLE|$t|$capText|CapPage=$capPage|CapY=$capY|StartPage=$p1|StartY=$y1|EndPage=$pN|EndY=$yN|Rows=$($tbl.Rows.Count)"
    }
} finally {
    if ($doc) { $doc.Close(0) }
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
}
"""
with open("verify_test_output.ps1", "w", encoding="utf-8-sig") as f:
    f.write(ps)

res = subprocess.run(["powershell.exe", "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", "verify_test_output.ps1"], capture_output=True)
out = res.stdout.decode("utf-8", errors="replace")
print(out)
if res.stderr:
    print("ERR:", res.stderr.decode("utf-8", errors="replace"))
