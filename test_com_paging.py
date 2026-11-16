import subprocess

ps = """
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $filePath = "C:\\Github\\Concert-P-Tum\\test_output.docx"
    $doc = $word.Documents.Open($filePath, $false, $true)
    $doc.Repaginate()
    
    # Check Table 3 row 11 vs row 12
    $tbl = $doc.Tables.Item(3)
    $r11 = $tbl.Rows.Item(11)
    $r12 = $tbl.Rows.Item(12)
    Write-Output "Row 11 Page: $($r11.Range.Information(3))"
    Write-Output "Row 12 Page: $($r12.Range.Information(3))"
} finally {
    if ($doc) { $doc.Close(0) }
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
}
"""
with open("test_com_paging.ps1", "w", encoding="utf-8-sig") as f:
    f.write(ps)

res = subprocess.run(["powershell.exe", "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", "test_com_paging.ps1"], capture_output=True)
print(res.stdout.decode("utf-8", errors="replace"))
