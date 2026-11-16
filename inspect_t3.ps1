
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $filePath = "C:\Github\Concert-P-Tum\test_output.docx"
    $doc = $word.Documents.Open($filePath, $false, $true)
    $doc.Repaginate()
    
    $tbl = $doc.Tables.Item(3)
    Write-Output "Table 3 (Rows: $($tbl.Rows.Count))"
    for ($r = 1; $r -le $tbl.Rows.Count; $r++) {
        $row = $tbl.Rows.Item($r)
        $rng = $row.Range.Duplicate
        $rng.Collapse(1)
        $p = $rng.Information(3)
        $y = [Math]::Round($rng.Information(6), 1)
        Write-Output "  Row $($r): Page $p, Y=$y, Txt=$($row.Range.Text.Substring(0, [Math]::Min(25, $row.Range.Text.Length)).Replace("`r", " ").Replace("`a", " "))"
    }
} finally {
    if ($doc) { $doc.Close(0) }
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
}
