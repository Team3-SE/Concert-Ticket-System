# Full pipeline: inspect original doc → compute fragments → build final docx
# Run as: powershell.exe -NoProfile -ExecutionPolicy Bypass -File full_pipeline.ps1

[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8
$ErrorActionPreference = 'Stop'

$SRC = "C:\Github\Concert-P-Tum\งานอื่นๆ\datadic\2569-11-16_DD_V2.3.0.docx"
$TEMP = "C:\Github\Concert-P-Tum\temp_layout.docx"
$OUT  = "C:\Github\Concert-P-Tum\งานอื่นๆ\datadic\2569-11-16_DD_V2.3.0.docx"

function Get-WordInspection($path) {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    try {
        $doc = $word.Documents.Open($path, $false, $true)
        $doc.Repaginate()
        $results = @()
        for ($t = 1; $t -le $doc.Tables.Count; $t++) {
            $tbl = $doc.Tables.Item($t)
            $capPara = $doc.Range($tbl.Range.Start - 1, $tbl.Range.Start - 1).Paragraphs.Item(1)
            $capPage = $capPara.Range.Information(3)
            $capText = $capPara.Range.Text.Trim()

            $r6 = $tbl.Rows.Item(6)
            $rng6 = $r6.Range.Duplicate; $rng6.Collapse(1)
            $hEndPage = $rng6.Information(3)

            $rowPages = @()
            $rowYs    = @()
            for ($r = 7; $r -le $tbl.Rows.Count; $r++) {
                $row = $tbl.Rows.Item($r)
                $rng = $row.Range.Duplicate; $rng.Collapse(1)
                $rowPages += [int]$rng.Information(3)
                $rowYs   += [double][Math]::Round($rng.Information(6), 1)
            }

            $lastY = if ($rowYs.Count -gt 0) { $rowYs[-1] } else {
                $r6rng = $r6.Range.Duplicate; $r6rng.Collapse(1)
                [double][Math]::Round($r6rng.Information(6), 1)
            }

            $results += [PSCustomObject]@{
                CapPage    = $capPage
                CapText    = $capText
                HEndPage   = $hEndPage
                RowPages   = $rowPages
                RowYs      = $rowYs
                LastY      = $lastY
            }
        }
        return $results
    } finally {
        $doc.Close(0)
        $word.Quit()
        [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
        [GC]::Collect(); [GC]::WaitForPendingFinalizers()
    }
}

# ── Step 1: Use Python to build temp docx from fragment spec ──────────────────
function Build-Docx($fragmentsJson, $outputPath) {
    $fragFile = "C:\Github\Concert-P-Tum\frags_in.json"
    $fragmentsJson | Set-Content -LiteralPath $fragFile -Encoding UTF8
    $py = @"
import json, re
from zipfile import ZipFile
from lxml import etree as E
from copy import deepcopy

W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
NS = {'w': W}
def q(tag): return f'{{{W}}}{tag}'
def visible(el): return ''.join(el.xpath('.//w:t/text()', namespaces=NS)).strip()

SRC = '/mnt/c/Github/Concert-P-Tum/งานอื่นๆ/datadic/2569-11-16_DD_V2.3.0.docx'
OUT = r'$($outputPath.Replace("\\", "/"))'
FRAG_FILE = '/mnt/c/Github/Concert-P-Tum/frags_in.json'

with open(FRAG_FILE, encoding='utf-8') as f:
    fragments = json.load(f)

with ZipFile(SRC) as z:
    entries = {info.filename: z.read(info.filename) for info in z.infolist()}

orig_root = E.fromstring(entries['word/document.xml'])
orig_body = orig_root.find(q('body'))

cover_elements = []
for c in orig_body:
    cover_elements.append(c)
    if c.find(f'.//{{{W}}}sectPr') is not None:
        break

data_dict_p = None
for c in orig_body:
    if visible(c) == 'Data Dictionary':
        data_dict_p = c
        break

tbls = orig_body.findall(q('tbl'))
logical_tables = []
current_lt = None
for t in tbls:
    p = t.getprevious()
    cap = visible(p) if p is not None else ''
    m = re.match(r'(ตารางที่\s*1-\d+\s*[^(]+)(?:\(ต่อ\))?', cap)
    base_name = m.group(1).strip() if m else cap
    is_cont = '(ต่อ)' in cap
    rows = t.findall(q('tr'))
    if not is_cont:
        current_lt = {
            'name': base_name,
            'tblPr': t.find(q('tblPr')),
            'tblGrid': t.find(q('tblGrid')),
            'header_rows': rows[:6],
            'data_rows': rows[6:],
            'caption_p': p
        }
        logical_tables.append(current_lt)
    else:
        current_lt['data_rows'].extend(rows[6:])

spacer_p = E.Element(q('p'))
pPr = E.SubElement(spacer_p, q('pPr'))
sp = E.SubElement(pPr, q('spacing'))
sp.set(q('before'), '0'); sp.set(q('after'), '0')
sp.set(q('line'), '460'); sp.set(q('lineRule'), 'exact')
rPr = E.SubElement(pPr, q('rPr'))
rf = E.SubElement(rPr, q('rFonts'))
rf.set(q('ascii'), 'TH SarabunPSK'); rf.set(q('hAnsi'), 'TH SarabunPSK'); rf.set(q('cs'), 'TH SarabunPSK')
sz = E.SubElement(rPr, q('sz')); sz.set(q('val'), '32')

new_body = E.Element(q('body'))
for ce in cover_elements:
    new_body.append(deepcopy(ce))
new_body.append(deepcopy(data_dict_p))

for frag in fragments:
    lt = next(t for t in logical_tables if t['name'] == frag['table_name'])
    if frag.get('needs_spacer', False):
        new_body.append(deepcopy(spacer_p))
    cap_p = deepcopy(lt['caption_p'])
    for r in cap_p.findall(q('r')):
        cap_p.remove(r)
    c_pPr = cap_p.find(q('pPr'))
    if c_pPr is None:
        c_pPr = E.SubElement(cap_p, q('pPr'))
    pbb = c_pPr.find(q('pageBreakBefore'))
    if frag.get('page_break_before', False):
        if pbb is None: E.SubElement(c_pPr, q('pageBreakBefore'))
    else:
        if pbb is not None: c_pPr.remove(pbb)
    if c_pPr.find(q('keepNext')) is None:
        E.SubElement(c_pPr, q('keepNext'))
    run = E.SubElement(cap_p, q('r'))
    rPr2 = E.SubElement(run, q('rPr'))
    rf2 = E.SubElement(rPr2, q('rFonts'))
    rf2.set(q('ascii'), 'TH SarabunPSK'); rf2.set(q('hAnsi'), 'TH SarabunPSK'); rf2.set(q('cs'), 'TH SarabunPSK')
    E.SubElement(rPr2, q('b')); E.SubElement(rPr2, q('bCs'))
    sz2 = E.SubElement(rPr2, q('sz')); sz2.set(q('val'), '32')
    tn = E.SubElement(run, q('t')); tn.text = frag['caption']
    new_body.append(cap_p)
    tbl = E.Element(q('tbl'))
    tbl.append(deepcopy(lt['tblPr'])); tbl.append(deepcopy(lt['tblGrid']))
    for hr in lt['header_rows']: tbl.append(deepcopy(hr))
    for idx in frag['data_row_indices']: tbl.append(deepcopy(lt['data_rows'][idx]))
    new_body.append(tbl)

end_p = E.Element(q('p'))
new_body.append(end_p)
new_body.append(deepcopy(orig_body.find(q('sectPr'))))
new_root = deepcopy(orig_root)
new_root.remove(new_root.find(q('body')))
new_root.append(new_body)
entries['word/document.xml'] = E.tostring(new_root, xml_declaration=True, encoding='utf-8', standalone=True)
with ZipFile(OUT, 'w') as z:
    for k, v in entries.items(): z.writestr(k, v)
print('BUILD_OK')
"@
    $pyFile = "C:\Github\Concert-P-Tum\build_step.py"
    $py | Set-Content -LiteralPath $pyFile -Encoding UTF8
    $result = & python3 $pyFile
    if ($result -ne "BUILD_OK") { throw "Python build failed: $result" }
}

# ── Initial fragments: 1 per logical table, all on new pages ─────────────────
Write-Host "Loading original doc for table names..."
$word0 = New-Object -ComObject Word.Application
$word0.Visible = $false; $word0.DisplayAlerts = 0
$doc0 = $word0.Documents.Open($SRC, $false, $true)
$tableNames = @()
for ($t = 1; $t -le $doc0.Tables.Count; $t++) {
    $tbl = $doc0.Tables.Item($t)
    $cap = $doc0.Range($tbl.Range.Start - 1, $tbl.Range.Start - 1).Paragraphs.Item(1).Range.Text.Trim()
    if (-not $cap.Contains("(ต่อ)")) { $tableNames += $cap }
}
$doc0.Close(0); $word0.Quit()
[void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word0)
[GC]::Collect(); [GC]::WaitForPendingFinalizers()
Write-Host "Found $($tableNames.Count) logical tables: $($tableNames -join ', ')"

# Data row counts (from earlier analysis)
$dataRowCounts = @{
    "ตารางที่ 1-1 organization" = 4
    "ตารางที่ 1-2 app_user" = 13
    "ตารางที่ 1-3 concert" = 21
    "ตารางที่ 1-4 zone" = 16
    "ตารางที่ 1-5 zone_inventory" = 4
    "ตารางที่ 1-6 seat" = 8
    "ตารางที่ 1-7 booking" = 12
    "ตารางที่ 1-8 booking_seat" = 3
    "ตารางที่ 1-9 payment" = 9
    "ตารางที่ 1-10 user_order" = 18
    "ตารางที่ 1-11 ticket" = 16
    "ตารางที่ 1-12 seat_lock_log" = 8
    "ตารางที่ 1-13 staff_concert" = 5
    "ตารางที่ 1-14 ticket_validation_log" = 5
    "ตารางที่ 1-15 system_anomaly" = 12
    "ตารางที่ 1-16 anomaly_attachment" = 5
    "ตารางที่ 1-17 audit_log" = 6
}

$fragments = @()
$ti = 0
foreach ($name in $tableNames) {
    $cnt = $dataRowCounts[$name]
    $fragments += [PSCustomObject]@{
        table_name        = $name
        caption           = $name
        page_break_before = ($ti -gt 0)
        needs_spacer      = $false
        data_row_indices  = @(0..($cnt - 1))
    }
    $ti++
}

Write-Host "Starting optimization loop..."
$iteration = 0
while ($true) {
    $iteration++
    Write-Host "=== Iteration $iteration (fragments: $($fragments.Count)) ==="

    # Build docx
    $fragJson = $fragments | ConvertTo-Json -Depth 10 -Compress
    Build-Docx $fragJson $TEMP

    # Inspect
    $insp = Get-WordInspection $TEMP

    $hasChanges = $false
    $newFragments = @()

    for ($i = 0; $i -lt $fragments.Count; $i++) {
        $frag = $fragments[$i]
        $info = $insp[$i]

        $capPage = $info.CapPage
        $hEndPage = $info.HEndPage
        $rowPages = $info.RowPages

        # Header spilled?
        if ($hEndPage -gt $capPage -or ($rowPages.Count -gt 0 -and $rowPages[0] -gt $capPage)) {
            Write-Host "  [Header spill] $($frag.caption) -> force page break"
            $frag.page_break_before = $true
            $frag.needs_spacer = $false
            $newFragments += $frag
            for ($j = $i + 1; $j -lt $fragments.Count; $j++) { $newFragments += $fragments[$j] }
            $hasChanges = $true
            break
        }

        # Data rows spilled?
        $spillIdx = -1
        for ($ri = 0; $ri -lt $rowPages.Count; $ri++) {
            if ($rowPages[$ri] -gt $capPage) { $spillIdx = $ri; break }
        }

        if ($spillIdx -ge 0) {
            $kept    = $frag.data_row_indices[0..($spillIdx - 1)]
            $spilled = $frag.data_row_indices[$spillIdx..($frag.data_row_indices.Count - 1)]
            Write-Host "  [Split] $($frag.caption): kept=$($kept.Count), spilled=$($spilled.Count)"
            $frag.data_row_indices = $kept
            $newFragments += $frag
            $newFragments += [PSCustomObject]@{
                table_name        = $frag.table_name
                caption           = "$($frag.table_name)(ต่อ)"
                page_break_before = $true
                needs_spacer      = $false
                data_row_indices  = $spilled
            }
            for ($j = $i + 1; $j -lt $fragments.Count; $j++) { $newFragments += $fragments[$j] }
            $hasChanges = $true
            break
        }

        $newFragments += $frag
    }

    if (-not $hasChanges) {
        # Try compaction
        $canCompact = $false
        for ($i = 1; $i -lt $fragments.Count; $i++) {
            if ($fragments[$i].page_break_before -and -not $fragments[$i].caption.EndsWith("(ต่อ)")) {
                $prevLastY = $insp[$i - 1].LastY
                if ($prevLastY -lt 580) {
                    Write-Host "  [Compact] $($fragments[$i].caption) onto same page (prevLastY=$prevLastY)"
                    $fragments[$i].page_break_before = $false
                    $fragments[$i].needs_spacer = $true
                    $canCompact = $true
                    break
                }
            }
        }
        if ($canCompact) { continue }
        else { Write-Host "Optimization DONE: $($fragments.Count) fragments."; break }
    }
    else { $fragments = $newFragments }
}

# Save fragments JSON
$fragJson = $fragments | ConvertTo-Json -Depth 10
$fragJson | Set-Content -LiteralPath "C:\Github\Concert-P-Tum\final_fragments.json" -Encoding UTF8
Write-Host "Saved final_fragments.json"

# Build final output
Write-Host "Building final output: $OUT"
$fragJsonCompact = $fragments | ConvertTo-Json -Depth 10 -Compress
Build-Docx $fragJsonCompact $OUT
Write-Host "ALL DONE!"
