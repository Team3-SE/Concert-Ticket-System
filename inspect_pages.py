import subprocess

ps_script = """
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $filePath = "C:\\Github\\Concert-P-Tum\\งานอื่นๆ\\datadic\\2569-11-16_DD_V2.3.0.docx"
    $doc = $word.Documents.Open($filePath, $false, $true)
    $doc.Repaginate()
    $pageCount = $doc.ComputeStatistics(2)
    Write-Output "PAGE_COUNT:$pageCount"
    
    # Check tables
    for ($t = 1; $t -le $doc.Tables.Count; $t++) {
        $tbl = $doc.Tables.Item($t)
        $r1 = $tbl.Rows.Item(1)
        $rN = $tbl.Rows.Item($tbl.Rows.Count)
        $p1 = $r1.Range.Information(3)
        $y1 = [Math]::Round($r1.Range.Information(6), 1)
        $pN = $rN.Range.Information(3)
        $yN = [Math]::Round($rN.Range.Information(6), 1)
        
        $caption = ""
        try {
            $capPara = $doc.Range($tbl.Range.Start - 1, $tbl.Range.Start - 1).Paragraphs.Item(1)
            $caption = $capPara.Range.Text.Trim()
            $capPage = $capPara.Range.Information(3)
            $capY = [Math]::Round($capPara.Range.Information(6), 1)
        } catch {
            $caption = "ERR"
            $capPage = 0
            $capY = 0
        }
        
        Write-Output "TABLE|$t|$caption|CapPage=$capPage|CapY=$capY|StartPage=$p1|StartY=$y1|EndPage=$pN|EndY=$yN|Rows=$($tbl.Rows.Count)"
    }
} finally {
    if ($doc) { $doc.Close(0) }
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
}
"""

with open("run_inspect.ps1", "w", encoding="utf-8-sig") as f:
    f.write(ps_script)

res = subprocess.run(["powershell.exe", "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", "run_inspect.ps1"], capture_output=True)
out = res.stdout.decode("utf-8", errors="replace")
print(out)
if res.stderr:
    print("ERR:", res.stderr.decode("utf-8", errors="replace"))
