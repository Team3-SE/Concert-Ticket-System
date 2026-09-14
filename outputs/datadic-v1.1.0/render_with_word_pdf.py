import argparse
import importlib.util
from pathlib import Path

parser=argparse.ArgumentParser()
parser.add_argument('docx')
parser.add_argument('pdf')
parser.add_argument('output')
parser.add_argument('--dpi',type=int,default=110)
args=parser.parse_args()
skill=Path('C:/Users/ttpho/.codex/plugins/cache/openai-primary-runtime/documents/26.905.11957/skills/documents/render_docx.py')
spec=importlib.util.spec_from_file_location('packaged_docx_renderer',skill)
renderer=importlib.util.module_from_spec(spec)
spec.loader.exec_module(renderer)
pdf=Path(args.pdf).resolve()
assert pdf.exists() and pdf.stat().st_size>0
def word_pdf_backend(*unused,**kwargs):return str(pdf),'Microsoft Word PDF export used because the bundled LibreOffice backend is unavailable on Windows.'
renderer.convert_to_pdf=word_pdf_backend
renderer.rasterize(str(Path(args.docx).resolve()),str(Path(args.output).resolve()),args.dpi,verbose=False,emit_pdf=False)
