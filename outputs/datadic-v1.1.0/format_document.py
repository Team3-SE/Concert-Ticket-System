from pathlib import Path
from zipfile import ZipFile
from lxml import etree as E
import json
import sys

ROOT = Path(__file__).resolve().parent
SOURCE = Path('C:/Github/Concert-P-Tum/งานอื่นๆ/datadic/v1.1.0.docx')
W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
NS = {'w': W}
def q(local): return '{'+W+'}'+local
def ensure(parent, name):
    child=parent.find(q(name))
    if child is None: child=E.SubElement(parent,q(name))
    return child
def text(el): return ''.join(el.itertext())
def visible(el): return ''.join(el.xpath('.//w:t/text()',namespaces=NS))

with ZipFile(SOURCE) as zin:
    entries={i.filename:(i,zin.read(i.filename)) for i in zin.infolist()}
original=E.fromstring(entries['word/document.xml'][1])
original_text=original.xpath('//w:t/text()',namespaces=NS)

for name,(info,data) in list(entries.items()):
    if not name.startswith('word/') or not name.endswith('.xml'): continue
    root=E.fromstring(data)
    for fonts in root.xpath('//w:rFonts',namespaces=NS):
        for attr in list(fonts.attrib):
            if 'theme' in E.QName(attr).localname.lower(): del fonts.attrib[attr]
        for attr in ['ascii','hAnsi','eastAsia','cs']: fonts.set(q(attr),'Angsana New')
    if name=='word/styles.xml':
        default=ensure(ensure(ensure(root,'docDefaults'),'rPrDefault'),'rPr')
        fonts=ensure(default,'rFonts')
        for attr in ['ascii','hAnsi','eastAsia','cs']: fonts.set(q(attr),'Angsana New')
    if name=='word/document.xml':
        body=root.find(q('body'))
        for p in list(body.findall(q('p'))):
            if not visible(p).strip() and not p.xpath('.//w:drawing|.//w:object|.//w:pict|.//w:sectPr',namespaces=NS): body.remove(p)
        caption_count=0
        for p in body.findall(q('p')):
            if visible(p).startswith('ตารางที่ '):
                ppr=ensure(p,'pPr')
                ensure(ppr,'keepNext').set(q('val'),'1')
                if caption_count: ensure(ppr,'pageBreakBefore').set(q('val'),'1')
                caption_count+=1
        for table in body.findall(q('tbl')):
            for ri,row in enumerate(table.findall(q('tr'))):
                trpr=ensure(row,'trPr')
                ensure(trpr,'cantSplit')
                for height in trpr.findall(q('trHeight')):
                    if height.get(q('hRule'))=='exact': height.set(q('hRule'),'atLeast')
                for p in row.xpath('.//w:p',namespaces=NS):
                    ensure(ensure(p,'pPr'),'keepNext').set(q('val'),'1' if ri<6 else '0')
        assert root.xpath('//w:t/text()',namespaces=NS)==original_text
    entries[name]=(info,E.tostring(root,xml_declaration=True,encoding='UTF-8',standalone=True))

output=ROOT/'font-adjusted.docx'
with ZipFile(output,'w') as zout:
    for name,(info,data) in entries.items():zout.writestr(info,data)
print(json.dumps({'output':str(output),'source_text_preserved':True,'font':'Angsana New','logical_tables':20},ensure_ascii=False))
