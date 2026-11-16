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
    $afterRng = $doc.Range($tbl.Range.End, $tbl.Range.End)
    $p = $afterRng.Paragraphs.Item(1)
    Write-Output "Para after Table 1: text='$($p.Range.Text.Trim())', page=$($p.Range.Information(3)), y=$($p.Range.Information(6))"
} finally {
    if ($doc) { $doc.Close(0) }
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
}
"""
with open('measure_after_t1.ps1', 'w', encoding='utf-8-sig') as f:
    f.write(ps)

res = subprocess.run(['powershell.exe', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', 'measure_after_t1.ps1'], capture_output=True)
print(res.stdout.decode('utf-8', errors='replace'))
if res.stderr:
    print('ERR:', res.stderr.decode('utf-8', errors='replace'))
