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
FINAL_OUTPUT = "/mnt/c/Github/Concert-P-Tum/งานอื่นๆ/datadic/2569-11-16_DD_V2.3.0.docx"
TEMP_FILE = "/mnt/c/Github/Concert-P-Tum/temp_layout.docx"
WIN_TEMP_FILE = "C:\\Github\\Concert-P-Tum\\temp_layout.docx"

with ZipFile(TARGET_FILE) as z:
    entries = {info.filename: z.read(info.filename) for info in z.infolist()}

orig_root = E.fromstring(entries["word/document.xml"])
orig_body = orig_root.find(q("body"))

# Cover elements (up to first sectPr)
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

# Extract 17 logical tables
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

# Spacer paragraph (~23pt line height: line=460 twips / 20 = 23pt)
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

def build_docx(fragments, output_path):
    new_body = E.Element(q("body"))
    for ce in cover_elements:
        new_body.append(deepcopy(ce))
    new_body.append(deepcopy(data_dict_p))
    
    for frag in fragments:
        lt = next(t for t in logical_tables if t["name"] == frag["table_name"])
        
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
    print(f"Written: {output_path}")

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

# ---- Main optimization loop ----
fragments = []
for ti, lt in enumerate(logical_tables):
    fragments.append({
        "table_name": lt["name"],
        "caption": lt["name"],
        "page_break_before": (ti > 0),
        "needs_spacer": False,
        "data_row_indices": list(range(len(lt["data_rows"])))
    })

print(f"Starting with {len(fragments)} fragments...")
iteration = 0
while True:
    iteration += 1
    build_docx(fragments, TEMP_FILE)
    insp = run_word_inspection(WIN_TEMP_FILE)
    
    has_changes = False
    new_fragments = []
    
    for i, (frag, info) in enumerate(zip(fragments, insp)):
        cap_page = info["CapPage"]
        h_end_page = info["HeaderEndPage"]
        row_pages = info["RowPages"]
        
        if h_end_page > cap_page or (len(row_pages) > 0 and row_pages[0] > cap_page):
            print(f"  Iter {iteration}: [{frag['caption']}] header spilled -> page break")
            frag["page_break_before"] = True
            frag["needs_spacer"] = False
            new_fragments.append(frag)
            new_fragments.extend(fragments[i+1:])
            has_changes = True
            break
            
        spill_idx = None
        for r_idx, rp in enumerate(row_pages):
            if rp > cap_page:
                spill_idx = r_idx
                break
                
        if spill_idx is not None:
            kept = frag["data_row_indices"][:spill_idx]
            spilled = frag["data_row_indices"][spill_idx:]
            print(f"  Iter {iteration}: [{frag['caption']}] rows spilled at {spill_idx} -> split ({len(kept)} kept, {len(spilled)} spilled)")
            frag["data_row_indices"] = kept
            new_fragments.append(frag)
            t_base = frag["table_name"]
            new_fragments.append({
                "table_name": t_base,
                "caption": f"{t_base}(ต่อ)",
                "page_break_before": True,
                "needs_spacer": False,
                "data_row_indices": spilled
            })
            new_fragments.extend(fragments[i+1:])
            has_changes = True
            break
            
        new_fragments.append(frag)
        
    if not has_changes:
        # Try compacting: place next non-cont table on same page as previous if space allows
        can_compact = False
        for i in range(1, len(fragments)):
            if fragments[i]["page_break_before"] and not fragments[i]["caption"].endswith("(ต่อ)"):
                prev_info = insp[i-1]
                prev_end_y = prev_info["RowYs"][-1] if len(prev_info["RowYs"]) > 0 else prev_info["HeaderStartY"]
                if prev_end_y < 580:
                    print(f"  Iter {iteration}: [{fragments[i]['caption']}] try same page as [{fragments[i-1]['caption']}] (prev_end_y={prev_end_y})")
                    fragments[i]["page_break_before"] = False
                    fragments[i]["needs_spacer"] = True
                    can_compact = True
                    break
        if can_compact:
            continue
        else:
            print(f"\nOptimization done in {iteration} iterations! {len(fragments)} fragments.")
            break
    else:
        fragments = new_fragments

# Save fragments to JSON
frag_save = [{k: v for k, v in f.items()} for f in fragments]
with open("final_fragments.json", "w", encoding="utf-8") as f:
    json.dump(frag_save, f, ensure_ascii=False, indent=2)
print(f"Saved {len(fragments)} fragments to final_fragments.json")

# Build final output file
print(f"Building final output: {FINAL_OUTPUT}")
build_docx(fragments, FINAL_OUTPUT)
print("DONE!")
