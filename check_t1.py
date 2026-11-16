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
    $lastCell = $tbl.Cell(10, 1)
    $rng = $lastCell.Range.Duplicate
    $rng.Collapse(0)
    $endY = $rng.Information(6)
    Write-Output "Table 1 bottom Y approximately: $endY"
} finally {
    if ($doc) { $doc.Close(0) }
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
}
"""
with open('check_t1.ps1', 'w', encoding='utf-8-sig') as f:
    f.write(ps)

res = subprocess.run(['powershell.exe', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', 'check_t1.ps1'], capture_output=True)
print('STDOUT:', res.stdout.decode('utf-8', errors='replace'))
print('STDERR:', res.stderr.decode('utf-8', errors='replace'))
