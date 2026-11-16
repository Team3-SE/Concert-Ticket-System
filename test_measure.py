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
    
    $sec = $doc.Sections.Item(2)
    Write-Output "PageHeight: $($sec.PageSetup.PageHeight)"
    Write-Output "TopMargin: $($sec.PageSetup.TopMargin)"
    Write-Output "BottomMargin: $($sec.PageSetup.BottomMargin)"
    
    # Inspect first 2 tables and their rows: Y position and height
    for ($t = 1; $t -le 2; $t++) {
        $tbl = $doc.Tables.Item($t)
        Write-Output "=== Table $t (Rows: $($tbl.Rows.Count)) ==="
        for ($r = 1; $r -le $tbl.Rows.Count; $r++) {
            $row = $tbl.Rows.Item($r)
            $rng = $row.Range.Duplicate
            $rng.Collapse(1)
            $p = $rng.Information(3)
            $y = [Math]::Round($rng.Information(6), 2)
            $txt = $row.Range.Text.Substring(0, [Math]::Min(25, $row.Range.Text.Length)).Replace("`r", " ").Replace("`a", " ")
            Write-Output "  Row $r: Page $p, Y=$y, Txt=$txt"
        }
    }
} finally {
    if ($doc) { $doc.Close(0) }
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
}
"""

with open("run_measure.ps1", "w", encoding="utf-8-sig") as f:
    f.write(ps_script)

res = subprocess.run(["powershell.exe", "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", "run_measure.ps1"], capture_output=True)
print(res.stdout.decode("utf-8", errors="replace"))
if res.stderr:
    print("ERR:", res.stderr.decode("utf-8", errors="replace"))
