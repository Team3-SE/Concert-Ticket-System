param([string]$InputPath,[string]$OutputDir)
$ErrorActionPreference = 'Stop'
$wordApp = $null
$wordDoc = $null
try {
    $wordApp = New-Object -ComObject Word.Application
    $wordApp.Visible = $false
    $wordApp.DisplayAlerts = 0
    $wordDoc = $wordApp.Documents.Open($InputPath, $false, $true)
    $wordDoc.Repaginate()
    Write-Output 'Document opened and paginated.'
    $tables = @()
    $paragraphs = @()
    for ($ti = 1; $ti -le $wordDoc.Tables.Count; $ti++) {
        $table = $wordDoc.Tables.Item($ti)
        $captionRange = $wordDoc.Range($table.Range.Start - 1, $table.Range.Start - 1).Paragraphs.Item(1).Range
        $paragraphs += [PSCustomObject]@{Text=$captionRange.Text.Trim();Page=$captionRange.Information(3);Start=$captionRange.Start}
        $rows = @()
        for ($ri = 1; $ri -le $table.Rows.Count; $ri++) {
            $row = $table.Rows.Item($ri)
            $startRange = $row.Range.Duplicate
            $startRange.Collapse(1)
            $endRange = $row.Range.Duplicate
            $endRange.MoveEnd(1,-1) | Out-Null
            $endRange.Collapse(0)
            $rows += [PSCustomObject]@{Row=$ri;StartPage=$startRange.Information(3);EndPage=$endRange.Information(3);Y=$startRange.Information(6);Text=$row.Range.Text.Trim()}
        }
        $tables += [PSCustomObject]@{Table=$ti;Rows=$rows}
    }
    $report = [PSCustomObject]@{Pages=$wordDoc.ComputeStatistics(2);Tables=$tables;Paragraphs=$paragraphs}
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
    $report | ConvertTo-Json -Depth 8 | Set-Content -LiteralPath (Join-Path $OutputDir 'pagination.json') -Encoding utf8
    Write-Output 'Table pagination inspected.'
    $pdfPath = Join-Path $OutputDir 'document.pdf'
    $wordDoc.ExportAsFixedFormat($pdfPath,17)
    [PSCustomObject]@{Pages=$report.Pages;Tables=$tables.Count;Output=$OutputDir} | ConvertTo-Json
} finally {
    if ($wordDoc) { $wordDoc.Close(0) }
    if ($wordApp) { $wordApp.Quit(); [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($wordApp) }
}
