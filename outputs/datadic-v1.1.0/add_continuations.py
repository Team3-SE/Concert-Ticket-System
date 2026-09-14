from pathlib import Path
from zipfile import ZipFile
from copy import deepcopy
from lxml import etree as E
import json

ROOT=Path(__file__).resolve().parent
W='http://schemas.openxmlformats.org/wordprocessingml/2006/main'
NS={'w':W}
def q(n):return '{'+W+'}'+n
def ensure(parent,n):
    child=parent.find(q(n))
    if child is None:child=E.SubElement(parent,q(n))
    return child
def visible(el):return ''.join(el.xpath('.//w:t/text()',namespaces=NS))
def row_content(row):return [visible(c) for c in row.findall(q('tc'))]

pagination=json.loads((ROOT/'font-qa/pagination.json').read_text(encoding='utf-8-sig'))
with ZipFile(ROOT/'font-adjusted.docx') as zin:
    entries={i.filename:(i,zin.read(i.filename)) for i in zin.infolist()}
root=E.fromstring(entries['word/document.xml'][1])
body=root.find(q('body'))
tables=list(body.findall(q('tbl')))
original_rows=[[row_content(r) for r in t.findall(q('tr'))] for t in tables]
manifest=[]

for original_index,(table,layout) in enumerate(zip(tables,pagination['Tables']),1):
    rows=list(table.findall(q('tr')))
    page_groups={}
    for row in layout['Rows']:
        assert row['StartPage']==row['EndPage'],'A data row must not split across pages.'
        page_groups.setdefault(row['StartPage'],[]).append(row['Row']-1)
    caption=table.getprevious()
    caption_text=visible(caption)
    assert caption_text.startswith('ตารางที่ ')
    if len(page_groups)==1:
        manifest.append({'table':original_index,'caption':caption_text,'continuations':0,'data_rows_preserved':True})
        continue
    # Pin both fragments: Word otherwise recalculates the first fragment after
    # its longer rows are moved to the continuation.
    original_grid=[int(c.get(q('w'))) for c in table.find(q('tblGrid'))]
    original_props=ensure(table,'tblPr')
    ensure(original_props,'tblLayout').set(q('type'),'fixed')
    original_table_width=ensure(original_props,'tblW')
    original_table_width.set(q('type'),'dxa')
    original_table_width.set(q('w'),str(sum(original_grid)))
    for row in rows:
        column=0
        for cell in row.findall(q('tc')):
            cell_props=ensure(cell,'tcPr')
            span=cell_props.find(q('gridSpan'))
            count=int(span.get(q('val'))) if span is not None else 1
            width=ensure(cell_props,'tcW')
            width.set(q('type'),'dxa')
            width.set(q('w'),str(sum(original_grid[column:column+count])))
            column+=count
    segments=list(page_groups.values())
    assert all(segment[0]>=6 for segment in segments[1:]),'Continuation must start on a data row.'
    first_end=segments[0][-1]+1
    for row in rows[first_end:]:table.remove(row)
    previous=table
    reconstructed=[row_content(r) for r in table.findall(q('tr'))]
    for indices in segments[1:]:
        continued_caption=deepcopy(caption)
        texts=continued_caption.xpath('.//w:t',namespaces=NS)
        texts[-1].text=(texts[-1].text or '')+' (ต่อ)'
        ensure(ensure(continued_caption,'pPr'),'pageBreakBefore').set(q('val'),'1')
        ensure(ensure(continued_caption,'pPr'),'keepNext').set(q('val'),'1')
        previous.addnext(continued_caption)
        continuation=E.Element(q('tbl'))
        for child in table:
            if child.tag!=q('tr'):continuation.append(deepcopy(child))
        widths=[int(c.get(q('w'))) for c in continuation.find(q('tblGrid'))]
        props=ensure(continuation,'tblPr')
        ensure(props,'tblLayout').set(q('type'),'fixed')
        table_width=ensure(props,'tblW')
        table_width.set(q('type'),'dxa')
        table_width.set(q('w'),str(sum(widths)))
        header=deepcopy(rows[5])
        ensure(ensure(header,'trPr'),'tblHeader').set(q('val'),'1')
        for p in header.xpath('.//w:p',namespaces=NS):ensure(ensure(p,'pPr'),'keepNext').set(q('val'),'1')
        continuation.append(header)
        for index in indices:
            continuation.append(deepcopy(rows[index]))
            reconstructed.append(row_content(rows[index]))
        for row in continuation.findall(q('tr')):
            column=0
            for cell in row.findall(q('tc')):
                cell_props=ensure(cell,'tcPr')
                span=cell_props.find(q('gridSpan'))
                count=int(span.get(q('val'))) if span is not None else 1
                width=ensure(cell_props,'tcW')
                width.set(q('type'),'dxa')
                width.set(q('w'),str(sum(widths[column:column+count])))
                column+=count
        continued_caption.addnext(continuation)
        previous=continuation
    assert reconstructed==original_rows[original_index-1]
    manifest.append({'table':original_index,'caption':caption_text,'continuations':len(segments)-1,'data_rows_preserved':True})

output=ROOT/'v1.1.0-AngsanaNew-continued.docx'
info,_=entries['word/document.xml']
entries['word/document.xml']=(info,E.tostring(root,xml_declaration=True,encoding='UTF-8',standalone=True))
with ZipFile(output,'w') as zout:
    for info,data in entries.values():zout.writestr(info,data)
(ROOT/'continuations.json').write_text(json.dumps(manifest,ensure_ascii=False,indent=2),encoding='utf-8')
print(json.dumps({'output':str(output),'logical_tables':len(tables),'table_fragments':len(body.findall(q('tbl'))),'continuations':[m['caption']+' (ต่อ)' for m in manifest if m['continuations']],'data_rows_preserved':True},ensure_ascii=False))
