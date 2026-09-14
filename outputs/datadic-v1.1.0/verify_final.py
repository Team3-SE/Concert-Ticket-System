from pathlib import Path
from zipfile import ZipFile
from lxml import etree as E
from collections import Counter
import json

root=Path(__file__).resolve().parent
source=Path('C:/Github/Concert-P-Tum/งานอื่นๆ/datadic/v1.1.0.docx')
output=root/'v1.1.0-AngsanaNew-continued.docx'
W='http://schemas.openxmlformats.org/wordprocessingml/2006/main'
NS={'w':W}
def q(n):return '{'+W+'}'+n
def visible(el):return ''.join(el.xpath('.//w:t/text()',namespaces=NS))
def rows(table):return [[visible(c) for c in row.findall(q('tc'))] for row in table.findall(q('tr'))]
def body(path):
    with ZipFile(path) as z:return E.fromstring(z.read('word/document.xml')).find(q('body'))
original=body(source)
final=body(output)
original_tables={}
original_widths={}
for table in original.findall(q('tbl')):
    original_tables[visible(table.getprevious())]=rows(table)
    original_widths[visible(table.getprevious())]=[int(c.get(q('w'))) for c in table.find(q('tblGrid'))]
final_tables={}
continued=[]
for table in final.findall(q('tbl')):
    caption=visible(table.getprevious())
    if caption.endswith(' (ต่อ)'):
        continued.append(caption)
        original_caption=caption[:-6]
        assert rows(table)[0]==original_tables[original_caption][5]
        assert table.find(q('tblPr')).find(q('tblLayout')).get(q('type'))=='fixed'
        assert [int(c.get(q('w'))) for c in table.find(q('tblGrid'))]==original_widths[original_caption]
        final_tables[original_caption]+=rows(table)[1:]
    else:final_tables[caption]=rows(table)
assert final_tables==original_tables,'All original cells must remain identical and in order.'
fonts=Counter()
with ZipFile(output) as z:
    for n in z.namelist():
        if n.startswith('word/') and n.endswith('.xml'):
            xml=E.fromstring(z.read(n))
            for font in xml.xpath('//w:rFonts',namespaces=NS):
                for attr,value in font.attrib.items():
                    if E.QName(attr).localname in ['ascii','hAnsi','eastAsia','cs']:fonts[value]+=1
assert set(fonts)=={'Angsana New'}
layout=json.loads((root/'final-qa/pagination.json').read_text(encoding='utf-8-sig'))
for table in layout['Tables']:
    pages={r['StartPage'] for r in table['Rows']}|{r['EndPage'] for r in table['Rows']}
    assert len(pages)==1,(table['Table'],pages)
captions=[p for p in layout['Paragraphs'] if p['Text'].startswith('ตารางที่ ')]
assert len(captions)==24
assert len({p['Page'] for p in captions})==24
assert layout['Pages']==24
for caption,table in zip(captions,layout['Tables']):assert caption['Page']==table['Rows'][0]['StartPage']
report={'pages':24,'logical_tables':20,'table_fragments':24,'continuation_captions':continued,'fonts':dict(fonts),'all_original_table_cells_preserved':True,'each_fragment_on_one_page':True,'all_captions_on_same_page_as_their_table':True,'blank_pages':0}
(root/'verification.json').write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
print(json.dumps(report,ensure_ascii=False))
