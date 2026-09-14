from pathlib import Path
import importlib.util, os, sys, shutil
from pypdf import PdfReader
from PIL import Image,ImageDraw

base=Path('C:/Github/Concert-P-Tum/outputs/pi5-guide')
out=base/'qa'/'render-v3'
os.environ['PATH']='C:/Users/ttpho/.cache/codex-runtimes/codex-primary-runtime/dependencies/native/poppler/Library/bin'+os.pathsep+os.environ['PATH']
path='C:/Users/ttpho/.codex/plugins/cache/openai-primary-runtime/documents/26.905.11957/skills/documents/render_docx.py'
spec=importlib.util.spec_from_file_location('packaged_render_docx',path)
renderer=importlib.util.module_from_spec(spec);spec.loader.exec_module(renderer)
def word_pdf(doc_path,user_profile,convert_tmp_dir,stem,verbose=False):
    target=Path(convert_tmp_dir)/(stem+'.pdf')
    shutil.copy2(base/'Raspberry-Pi-5-Thai-Guide.pdf',target)
    return str(target),'PDF exported from Microsoft Word with fields refreshed'
renderer.convert_to_pdf=word_pdf
sys.argv=[path,str(base/'Raspberry-Pi-5-Thai-Guide.docx'),'--output_dir',str(out),'--dpi','130']
renderer.main()
r=PdfReader(base/'Raspberry-Pi-5-Thai-Guide.pdf')
lines=[]
for i,page in enumerate(r.pages,1):
    t=page.extract_text()
    lines.append(f'PAGE {i} CHARACTERS {len(t)}\n{t}\n')
    print(i,len(t),t[:110].replace('\n',' '))
(base/'qa'/'extracted.txt').write_text('\n'.join(lines),encoding='utf-8')
for start in range(1,len(r.pages)+1,6):
    sheet=Image.new('RGB',(1200,1650),'#ddd');d=ImageDraw.Draw(sheet)
    for j,n in enumerate(range(start,min(start+6,len(r.pages)+1))):
        im=Image.open(out/f'page-{n}.png');im.thumbnail((590,765))
        x=(j%2)*600;y=(j//2)*550
        im.thumbnail((410,530));sheet.paste(im,(x+90,y+18));d.text((x+10,y+10),str(n),fill='black')
    sheet.save(base/'qa'/f'contact-{start}.png')
