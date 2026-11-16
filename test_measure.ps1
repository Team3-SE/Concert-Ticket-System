
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $doc = $word.Documents.Open("C:\Github\Concert-P-Tum\test_measure_doc.docx", $false, $true)
    $doc.Repaginate()
    $t = $doc.Tables.Item(1)
    for ($r = 1; $r -le $t.Rows.Count; $r++) {
        $row = $t.Rows.Item($r)
        $rng = $row.Range.Duplicate
        $rng.Collapse(1)
        Write-Output "Row $($r): Y=$([Math]::Round($rng.Information(6), 2))"
    }
} finally {
    if ($doc) { $doc.Close(0) }
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
}
