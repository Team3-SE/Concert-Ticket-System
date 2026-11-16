import json
import re
import subprocess
from zipfile import ZipFile
from lxml import etree as E
from copy import deepcopy

W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
NS = {"w": W}
def q(tag): return f"{{{W}}}{tag}"
def visible(el): return "".join(el.xpath(".//w:t/text()", namespaces=NS)).strip()

TARGET_FILE = "/mnt/c/Github/Concert-P-Tum/งานอื่นๆ/datadic/2569-11-16_DD_V2.3.0.docx"
WIN_TARGET_FILE = "C:\\Github\\Concert-P-Tum\\งานอื่นๆ\\datadic\\2569-11-16_DD_V2.3.0.docx"
TEMP_FILE = "/mnt/c/Github/Concert-P-Tum/temp_layout.docx"
WIN_TEMP_FILE = "C:\\Github\\Concert-P-Tum\\temp_layout.docx"

with ZipFile(TARGET_FILE) as z:
    entries = {info.filename: z.read(info.filename) for info in z.infolist()}

orig_root = E.fromstring(entries["word/document.xml"])
orig_body = orig_root.find(q("body"))

cover_elements = []
for c in orig_body:
    cover_elements.append(c)
    if c.find(f".//{{{W}}}sectPr") is not None:
        break

data_dict_p = None
for c in orig_body:
    if visible(c) == "Data Dictionary":
        data_dict_p = c
        break

tbls = orig_body.findall(q("tbl"))
logical_tables = []
current_lt = None
for t in tbls:
    p = t.getprevious()
    cap = visible(p) if p is not None else ""
    m = re.match(r"(ตารางที่\s*1-\d+\s*[^(]+)(?:\(ต่อ\))?", cap)
    base_name = m.group(1).strip() if m else cap
    is_cont = "(ต่อ)" in cap
    rows = t.findall(q("tr"))
    if not is_cont:
        current_lt = {
            "name": base_name,
            "tblPr": t.find(q("tblPr")),
            "tblGrid": t.find(q("tblGrid")),
            "header_rows": rows[:6],
            "data_rows": rows[6:],
            "caption_p": p
        }
        logical_tables.append(current_lt)
    else:
        current_lt["data_rows"].extend(rows[6:])

print(f"Loaded {len(logical_tables)} logical tables.")

spacer_p = E.Element(q("p"))
pPr = E.SubElement(spacer_p, q("pPr"))
sp = E.SubElement(pPr, q("spacing"))
sp.set(q("before"), "0")
sp.set(q("after"), "0")
sp.set(q("line"), "460")
sp.set(q("lineRule"), "exact")
rPr = E.SubElement(pPr, q("rPr"))
rf = E.SubElement(rPr, q("rFonts"))
rf.set(q("ascii"), "TH SarabunPSK")
rf.set(q("hAnsi"), "TH SarabunPSK")
rf.set(q("cs"), "TH SarabunPSK")
sz = E.SubElement(rPr, q("sz"))
sz.set(q("val"), "32")

def build_docx_from_fragments(fragments, output_path):
    new_body = E.Element(q("body"))
    for ce in cover_elements:
        new_body.append(deepcopy(ce))
    new_body.append(deepcopy(data_dict_p))
    
    for frag in fragments:
        lt = [t for t in logical_tables if t["name"] == frag["table_name"]][0]
        
        if frag.get("needs_spacer", False):
            new_body.append(deepcopy(spacer_p))
            
        cap_p = deepcopy(lt["caption_p"])
        for r in cap_p.findall(q("r")):
            cap_p.remove(r)
        c_pPr = cap_p.find(q("pPr"))
        if c_pPr is None:
            c_pPr = E.SubElement(cap_p, q("pPr"))
            
        pbb_elem = c_pPr.find(q("pageBreakBefore"))
        if frag.get("page_break_before", False):
            if pbb_elem is None:
                E.SubElement(c_pPr, q("pageBreakBefore"))
            else:
                pbb_elem.set(q("val"), "1")
        else:
            if pbb_elem is not None:
                c_pPr.remove(pbb_elem)
                
        if c_pPr.find(q("keepNext")) is None:
            E.SubElement(c_pPr, q("keepNext"))
            
        run = E.SubElement(cap_p, q("r"))
        rPr = E.SubElement(run, q("rPr"))
        rf = E.SubElement(rPr, q("rFonts"))
        rf.set(q("ascii"), "TH SarabunPSK")
        rf.set(q("hAnsi"), "TH SarabunPSK")
        rf.set(q("cs"), "TH SarabunPSK")
        E.SubElement(rPr, q("b"))
        E.SubElement(rPr, q("bCs"))
        sz = E.SubElement(rPr, q("sz"))
        sz.set(q("val"), "32")
        t_node = E.SubElement(run, q("t"))
        t_node.text = frag["caption"]
        new_body.append(cap_p)
        
        tbl = E.Element(q("tbl"))
        tbl.append(deepcopy(lt["tblPr"]))
        tbl.append(deepcopy(lt["tblGrid"]))
        for hr in lt["header_rows"]:
            tbl.append(deepcopy(hr))
        for row_idx in frag["data_row_indices"]:
            tbl.append(deepcopy(lt["data_rows"][row_idx]))
        new_body.append(tbl)
        
    end_p = E.Element(q("p"))
    new_body.append(end_p)
    new_body.append(deepcopy(orig_body.find(q("sectPr"))))
    
    new_root = deepcopy(orig_root)
    new_root.remove(new_root.find(q("body")))
    new_root.append(new_body)
    
    entries["word/document.xml"] = E.tostring(new_root, xml_declaration=True, encoding="utf-8", standalone=True)
    with ZipFile(output_path, "w") as z:
        for k, v in entries.items():
            z.writestr(k, v)

def run_word_inspection(doc_win_path):
    ps = f"""
    [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
    $OutputEncoding = [System.Text.Encoding]::UTF8
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    try {{
        $doc = $word.Documents.Open("{doc_win_path}", $false, $true)
        $doc.Repaginate()
        $results = @()
        for ($t = 1; $t -le $doc.Tables.Count; $t++) {{
            $tbl = $doc.Tables.Item($t)
            $capPara = $doc.Range($tbl.Range.Start - 1, $tbl.Range.Start - 1).Paragraphs.Item(1)
            $capText = $capPara.Range.Text.Trim()
            $capPage = $capPara.Range.Information(3)
            $capY = [Math]::Round($capPara.Range.Information(6), 1)
            
            $r1 = $tbl.Rows.Item(1)
            $r6 = $tbl.Rows.Item(6)
            $rng1 = $r1.Range.Duplicate; $rng1.Collapse(1)
            $rng6 = $r6.Range.Duplicate; $rng6.Collapse(1)
            $hStartPage = $rng1.Information(3)
            $hEndPage = $rng6.Information(3)
            $hStartY = [Math]::Round($rng1.Information(6), 1)
            
            $rowPages = @()
            $rowYs = @()
            for ($r = 7; $r -le $tbl.Rows.Count; $r++) {{
                $row = $tbl.Rows.Item($r)
                $rng = $row.Range.Duplicate; $rng.Collapse(1)
                $rowPages += $rng.Information(3)
                $rowYs += [Math]::Round($rng.Information(6), 1)
            }}
            
            $results += [PSCustomObject]@{{
                TableIndex = $t
                Caption = $capText
                CapPage = $capPage
                CapY = $capY
                HeaderStartPage = $hStartPage
                HeaderEndPage = $hEndPage
                HeaderStartY = $hStartY
                DataRowCount = $rowPages.Count
                RowPages = $rowPages
                RowYs = $rowYs
            }}
        }}
        $results | ConvertTo-Json -Depth 5 -Compress
    }} finally {{
        if ($doc) {{ $doc.Close(0) }}
        $word.Quit()
        [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($word)
    }}
    """
    with open("do_inspect.ps1", "w", encoding="utf-8-sig") as f:
        f.write(ps)
    res = subprocess.run(["powershell.exe", "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", "do_inspect.ps1"], capture_output=True)
    out = res.stdout.decode("utf-8", errors="replace").strip()
    return json.loads(out)

# Initial plan: start with 1 fragment per logical table
# For table 1-1: page_break_before = False
# For tables 1-2..1-17: try page_break_before = False, needs_spacer = True
# Let Word tell us what fits and what spills!
fragments = []
for ti, lt in enumerate(logical_tables):
    fragments.append({
        "table_name": lt["name"],
        "caption": lt["name"],
        "page_break_before": (ti > 0), # start with new page for each table initially
        "needs_spacer": False,
        "data_row_indices": list(range(len(lt["data_rows"])))
    })

print(f"Starting layout optimization with {len(fragments)} fragments...")

iteration = 0
while True:
    iteration += 1
    print(f"\n=== Iteration {iteration} (fragments: {len(fragments)}) ===")
    build_docx_from_fragments(fragments, TEMP_FILE)
    insp = run_word_inspection(WIN_TEMP_FILE)
    
    # Check each fragment
    has_changes = False
    new_fragments = []
    
    for i, (frag, info) in enumerate(zip(fragments, insp)):
        cap_page = info["CapPage"]
        h_end_page = info["HeaderEndPage"]
        row_pages = info["RowPages"]
        
        # Check Case A: header or first data row didn't fit on CapPage
        if h_end_page > cap_page or (len(row_pages) > 0 and row_pages[0] > cap_page):
            print(f"  [Case A] Table {i+1} ({frag['caption']}): header/first row spilled (CapPage={cap_page}, HEnd={h_end_page}, Row0={row_pages[0] if row_pages else None}). Moving to new page!")
            frag["page_break_before"] = True
            frag["needs_spacer"] = False
            new_fragments.append(frag)
            has_changes = True
            # Keep remaining fragments as they are for this iteration
            new_fragments.extend(fragments[i+1:])
            break
            
        # Check Case B: some data rows spilled to next page
        spill_idx = None
        for r_idx, rp in enumerate(row_pages):
            if rp > cap_page:
                spill_idx = r_idx
                break
                
        if spill_idx is not None:
            # We keep rows 0..spill_idx-1 in this fragment
            kept_rows = frag["data_row_indices"][:spill_idx]
            spilled_rows = frag["data_row_indices"][spill_idx:]
            print(f"  [Case B] Table {i+1} ({frag['caption']}): rows spilled at index {spill_idx} (kept {len(kept_rows)}, spilled {len(spilled_rows)}). Creating continuation fragment!")
            
            frag["data_row_indices"] = kept_rows
            new_fragments.append(frag)
            
            # Create continuation fragment
            t_base_name = frag["table_name"]
            cont_frag = {
                "table_name": t_base_name,
                "caption": f"{t_base_name}(ต่อ)",
                "page_break_before": True,
                "needs_spacer": False,
                "data_row_indices": spilled_rows
            }
            new_fragments.append(cont_frag)
            new_fragments.extend(fragments[i+1:])
            has_changes = True
            break
            
        # All rows fit on CapPage!
        new_fragments.append(frag)
        
    if not has_changes:
        # All fragments fit completely on their respective pages!
        # Now check if any table starting on a NEW page can be merged onto the PREVIOUS page!
        print("\nAll fragments currently fit! Checking if any table can start on previous page...")
        can_compact = False
        for i in range(1, len(fragments)):
            # If fragment i has page_break_before = True, AND it is the FIRST fragment of a table (not a continuation)
            # Or even if previous fragment ended on page P, could fragment i start on page P?
            prev_info = insp[i-1]
            curr_info = insp[i]
            
            # Only try moving if it is a new logical table (not a continuation of the same table)
            if fragments[i]["page_break_before"] and not fragments[i]["caption"].endswith("(ต่อ)"):
                # Check if previous table's last row Y leaves enough room
                prev_end_y = prev_info["RowYs"][-1] if len(prev_info["RowYs"]) > 0 else prev_info["HeaderStartY"]
                # If prev_end_y is small (e.g. < 550 pt), try moving this table to the same page!
                if prev_end_y < 580:
                    print(f"  Attempting to place Table {i+1} ({fragments[i]['caption']}) on same page as Table {i} (prev_end_y={prev_end_y})...")
                    fragments[i]["page_break_before"] = False
                    fragments[i]["needs_spacer"] = True
                    can_compact = True
                    break
                    
        if can_compact:
            continue
        else:
            print("\nOptimization complete! No more splits or moves possible.")
            break
    else:
        fragments = new_fragments

print(f"\nFinal layout has {len(fragments)} fragments across document.")
