from zipfile import ZipFile
from lxml import etree as E
from copy import deepcopy
import subprocess
import json
import re

W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
NS = {"w": W}
def q(tag): return f"{{{W}}}{tag}"
def visible(el): return "".join(el.xpath(".//w:t/text()", namespaces=NS)).strip()

def build_docx(plan, output_path):
    with ZipFile("/mnt/c/Github/Concert-P-Tum/งานอื่นๆ/datadic/2569-11-16_DD_V2.3.0.docx") as z:
        entries = {info.filename: z.read(info.filename) for info in z.infolist()}

    orig_root = E.fromstring(entries["word/document.xml"])
    orig_body = orig_root.find(q("body"))
    
    # 1. Extract cover page elements (all elements up to and including the first sectPr paragraph)
    cover_elements = []
    data_dict_p = None
    for c in orig_body:
        cover_elements.append(c)
        if c.find(f".//{{{W}}}sectPr") is not None:
            break

    # Find the "Data Dictionary" paragraph
    for c in orig_body:
        if visible(c) == "Data Dictionary":
            data_dict_p = c
            break

    # Extract all 17 logical tables
    tbls = orig_body.findall(q("tbl"))
    logical_tables = {}
    for t in tbls:
        p = t.getprevious()
        cap = visible(p) if p is not None else ""
        m = re.match(r"(ตารางที่\s*1-\d+\s*[^(]+)(?:\(ต่อ\))?", cap)
        base_name = m.group(1).strip() if m else cap
        is_cont = "(ต่อ)" in cap
        rows = t.findall(q("tr"))
        if base_name not in logical_tables:
            logical_tables[base_name] = {
                "tblPr": t.find(q("tblPr")),
                "tblGrid": t.find(q("tblGrid")),
                "header_rows": rows[:6],
                "data_rows": rows[6:],
                "caption_p": p
            }
        else:
            logical_tables[base_name]["data_rows"].extend(rows[6:])

    # Create new body
    new_body = E.Element(q("body"))
    for ce in cover_elements:
        new_body.append(deepcopy(ce))

    # Append Data Dictionary title
    new_body.append(deepcopy(data_dict_p))

    # Spacer template:
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

    last_page = 2
    for frag_idx, frag in enumerate(plan):
        t_name = frag["table"]
        lt = logical_tables[t_name]
        is_cont = "(ต่อ)" in frag["caption"]
        page = frag["page"]
        
        # Determine if we need a spacer or page break
        # If on the same page as previous fragment, add spacer
        if frag_idx > 0 and page == last_page:
            new_body.append(deepcopy(spacer_p))
            need_pbb = False
        elif frag_idx > 0:
            need_pbb = True
        else:
            need_pbb = False # first table after Data Dictionary

        # Create caption
        cap_p = deepcopy(lt["caption_p"])
        # Clear existing text
        for r in cap_p.findall(q("r")):
            cap_p.remove(r)
        
        c_pPr = cap_p.find(q("pPr"))
        if c_pPr is None:
            c_pPr = E.SubElement(cap_p, q("pPr"))
        
        # Set pbb
        pbb_elem = c_pPr.find(q("pageBreakBefore"))
        if need_pbb:
            if pbb_elem is None:
                E.SubElement(c_pPr, q("pageBreakBefore"))
            else:
                pbb_elem.set(q("val"), "1")
        else:
            if pbb_elem is not None:
                c_pPr.remove(pbb_elem)

        # Set keepNext
        if c_pPr.find(q("keepNext")) is None:
            E.SubElement(c_pPr, q("keepNext"))

        # Add text run
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

        # Create table fragment
        tbl_frag = E.Element(q("tbl"))
        tbl_frag.append(deepcopy(lt["tblPr"]))
        tbl_frag.append(deepcopy(lt["tblGrid"]))
        
        # Append header rows (0..5)
        for hr in lt["header_rows"]:
            tbl_frag.append(deepcopy(hr))
            
        # Append data rows
        for row_num in frag["rows"]:
            tbl_frag.append(deepcopy(lt["data_rows"][row_num - 1]))

        new_body.append(tbl_frag)
        last_page = page

    # Append final empty paragraph and sectPr
    end_p = E.Element(q("p"))
    new_body.append(end_p)
    new_body.append(deepcopy(orig_body.find(q("sectPr"))))

    orig_root.remove(orig_root.find(q("body")))
    orig_root.append(new_body)

    entries["word/document.xml"] = E.tostring(orig_root, xml_declaration=True, encoding="utf-8", standalone=True)
    with ZipFile(output_path, "w") as z:
        for k, v in entries.items():
            z.writestr(k, v)
    print(f"Document successfully written to {output_path}")

from simulate_layout import plan
build_docx(plan, "test_output.docx")
