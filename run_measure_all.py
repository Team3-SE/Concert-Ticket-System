import subprocess
import json

ps = """
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $filePath = "C:\\Github\\Concert-P-Tum\\measure_all.docx"
    $doc = $word.Documents.Open($filePath, $false, $true)
    $doc.Repaginate()
    
    $heights = @()
    for ($t = 1; $t -le $doc.Tables.Count; $t++) {
        $tbl = $doc.Tables.Item($t)
        $r1 = $tbl.Rows.Item(1)
        $r2 = $tbl.Rows.Item(2)
        $rng1 = $r1.Range.Duplicate
        $rng1.Collapse(1)
        $rng2 = $r2.Range.Duplicate
        $rng2.Collapse(1)
        $y1 = $rng1.Information(6)
        $y2 = $rng2.Information(6)
        $h = [Math]::Round($y2 - $y1, 2)
        $heights += $h
    }
    $heights | ConvertTo-Json -Compress
} finally {
    if ($doc) { $doc.Close(0) }
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
}
"""
with open("run_measure_all.ps1", "w", encoding="utf-8-sig") as f:
    f.write(ps)

res = subprocess.run(["powershell.exe", "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", "run_measure_all.ps1"], capture_output=True)
out = res.stdout.decode("utf-8", errors="replace").strip()
if res.stderr:
    print("ERR:", res.stderr.decode("utf-8", errors="replace"))

heights = json.loads(out)
with open("measured_heights.json", "w", encoding="utf-8") as f:
    json.dump(heights, f, indent=2)

print(f"Successfully measured {len(heights)} rows!")
print("First 15 heights:", heights[:15])
