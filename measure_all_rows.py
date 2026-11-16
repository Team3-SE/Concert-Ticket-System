import subprocess

ps = """
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $filePath = "C:\\Github\\Concert-P-Tum\\งานอื่นๆ\\datadic\\2569-11-16_DD_V2.3.0.docx"
    $doc = $word.Documents.Open($filePath, $false, $true)
    $doc.Repaginate()
    
    $tbl = $doc.Tables.Item(1)
    for ($r = 1; $r -le $tbl.Rows.Count; $r++) {
        $row = $tbl.Rows.Item($r)
        # To get row height, check top of row and bottom of cell
        $c = $row.Cells.Item(1)
        $rStart = $c.Range.Duplicate
        $rStart.Collapse(1)
        $rEnd = $c.Range.Duplicate
        $rEnd.Collapse(0)
        $yStart = $rStart.Information(6)
        $yEnd = $rEnd.Information(6)
        Write-Output "Row $($r): yStart=$yStart, yEnd=$yEnd"
    }
} finally {
    if ($doc) { $doc.Close(0) }
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
}
"""
with open('measure_all_rows.ps1', 'w', encoding='utf-8-sig') as f:
    f.write(ps)

res = subprocess.run(['powershell.exe', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', 'measure_all_rows.ps1'], capture_output=True)
print(res.stdout.decode('utf-8', errors='replace'))
if res.stderr:
    print('ERR:', res.stderr.decode('utf-8', errors='replace'))
