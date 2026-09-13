const fs = require('fs');
const path = require('path');
const http = require('http');
const { chromium } = require('C:/Users/ttpho/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const sharp = require('C:/Users/ttpho/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/sharp');
const out = path.resolve(__dirname, '..');
const fontCSS = fs.readFileSync(path.join(__dirname,'font.css'),'utf8');
const source = fs.readFileSync('C:/Users/ttpho/.codex/attachments/153fbd4d-c74e-4796-89af-8a50fb9ae159/pasted-text.txt', 'utf8');
const body = source.replace(/^---[\s\S]*?---\s*/, '').replace(/\u00a0/g, ' ').replace(/\r/g, '').replace(/\n\s*\n/g, '\n').replace('direction TB', 'direction LR');
const config = `---
config:
  theme: base
  look: classic
  layout: elk
  htmlLabels: false
  themeCSS: ".edgeLabel .background { fill: #d3d3d3 !important; opacity: 1 !important; } .relation { stroke-width: 1.35px; }"
  themeVariables:
    fontFamily: "Angsana New"
    fontSize: "20px"
    primaryColor: "#ffffff"
    primaryTextColor: "#28253d"
    primaryBorderColor: "#26263d"
    lineColor: "#333333"
    secondaryColor: "#ffffff"
    tertiaryColor: "#ffffff"
    edgeLabelBackground: "#d3d3d3"
  class:
    useMaxWidth: false
    padding: 16
    nodeSpacing: 100
    rankSpacing: 100
  flowchart:
    wrappingWidth: 1000
  elk:
    mergeEdges: false
    nodePlacementStrategy: NETWORK_SIMPLEX
---
`;
fs.writeFileSync(path.join(out, 'V2.1.0-fixed.mmd'), config + body);
const types = {'.mjs':'text/javascript','.js':'text/javascript','.json':'application/json','.ttc':'font/collection'};
const server = http.createServer((req, res) => {
  if (req.url === '/') {res.setHeader('Content-Type', 'text/html'); res.end('<!doctype html><html><head><meta charset="utf-8"><style>body{margin:0;background:white}svg{overflow:visible}text{font-family:"Angsana New"}</style></head><body><div id="diagram"></div></body></html>');return;}
  let target = path.join(__dirname, decodeURIComponent(req.url.split('?')[0]));
  if(req.url === '/angsana.ttc') target = 'C:/Windows/Fonts/angsana.ttc';
  if (!fs.existsSync(target)) {res.statusCode=404;res.end();return;}
  res.setHeader('Content-Type', types[path.extname(target)] || 'application/octet-stream');
  res.end(fs.readFileSync(target));
});
(async () => {
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const browser = await chromium.launch({executablePath:'C:/Users/ttpho/AppData/Local/ms-playwright/chromium-1228/chrome-win64/chrome.exe', headless:true});
  const page = await browser.newPage({viewport:{width:1600,height:1200},deviceScaleFactor:1});
  page.on('pageerror', e => console.error(e.message));
  try {
    await page.goto(`http://127.0.0.1:${server.address().port}/`);
    const report = await page.evaluate(async ({source,fontCSS}) => {
      await document.fonts.load('20px "Angsana New"');
      await document.fonts.load('bold 20px "Angsana New"');
      await document.fonts.ready;
      const {default:mermaid} = await import('/node_modules/mermaid/dist/mermaid.esm.min.mjs');
      const {default:elk} = await import('/node_modules/@mermaid-js/layout-elk/dist/mermaid-layout-elk.esm.min.mjs');
      mermaid.registerLayoutLoaders(elk);
      mermaid.initialize({startOnLoad:false,securityLevel:'loose',maxTextSize:200000});
      const rendered = await mermaid.render('concertDiagram', source);
      document.getElementById('diagram').innerHTML = rendered.svg;
      const svg = document.querySelector('svg');
      const style = document.createElementNS('http://www.w3.org/2000/svg','style');
      style.textContent = fontCSS + '#concertDiagram .edgeLabel .background{fill:#d3d3d3!important;opacity:1!important} #concertDiagram .relation{stroke-width:1.35px}';
      svg.prepend(style);
      await document.fonts.ready;
      const vb = svg.viewBox.baseVal;
      svg.setAttribute('viewBox',`${vb.x-18} ${vb.y-18} ${vb.width+36} ${vb.height+36}`);
      svg.setAttribute('width',vb.width);svg.setAttribute('height',vb.height);
      svg.style.maxWidth = 'none';
      const expected = Array.from(source.matchAll(/^class (\w+)\s*\{([^]*?)\}/gm), m=>({id:m[1],members:m[2].split('\n').map(s=>s.trim()).filter(Boolean)}));
      const text = svg.textContent;
      const canonical = s=>s.replace(/~([^~]+)~/g,'<$1>').replace(/\s/g,'').replace(/<<|>>/g,'');
      const nodes=Array.from(svg.querySelectorAll('g.node'));
      const missingMembers=[],overflow=[];
      for(const cls of expected){
        const node=nodes.find(n=>n.id.includes(`classId-${cls.id}-`));
        if(!node)continue;
        const actual=canonical(node.textContent);
        for(const member of cls.members)if(!actual.includes(canonical(member)))missingMembers.push({class:cls.id,member});
        const rect=node.querySelector('.outer-path');
        const box=rect.getBoundingClientRect();
        for(const t of node.querySelectorAll('text')){
          const b=t.getBoundingClientRect();
          if(b.left<box.left-1 || b.right>box.right+1 || b.top<box.top-1 || b.bottom>box.bottom+1)overflow.push({class:cls.id,text:t.textContent});
        }
      }
      const expectedRelationships = source.split('\n').filter(l=>/-->|<\|--/.test(l)).length;
      const obstacles=nodes.map(n=>({id:n.id,box:n.querySelector('.outer-path').getBoundingClientRect()}));
      const edgeBoxOverlaps=[];
      for(const edge of svg.querySelectorAll('path.relation')) {
        const hits=new Set();
        const matrix=edge.getScreenCTM();
        for(let distance=3;distance<edge.getTotalLength()-3;distance+=5){
          const p=edge.getPointAtLength(distance);
          const global=new DOMPoint(p.x,p.y).matrixTransform(matrix);
          for(const obstacle of obstacles){const b=obstacle.box;
            if(global.x>b.left+3 && global.x<b.right-3 && global.y>b.top+3 && global.y<b.bottom-3)hits.add(obstacle.id);
          }
        }
        if(hits.size)edgeBoxOverlaps.push({edge:edge.dataset.id,nodes:[...hits]});
      }
      return {width:vb.width,height:vb.height,classes:expected.length,members:expected.reduce((n,c)=>n+c.members.length,0),relationships:svg.querySelectorAll('path.relation').length,expectedRelationships,missingClasses:expected.filter(x=>!text.includes(x.id)).map(x=>x.id),missingMembers,overflow,edgeBoxOverlaps,svg:svg.outerHTML};
    }, {source:config+body,fontCSS});
    fs.writeFileSync(path.join(out, 'V2.1.0-fixed.svg'), report.svg);
    const scale = Math.min(3, 12000/report.width, 16000/report.height);
    await page.evaluate(scale=>{const svg=document.querySelector('svg');svg.setAttribute('width',svg.viewBox.baseVal.width*scale);svg.setAttribute('height',svg.viewBox.baseVal.height*scale);},scale);
    await page.locator('svg').screenshot({path:path.join(out,'V2.1.0-fixed.png'),timeout:60000});
    for(const id of ['payment','ticketValidationLog','customerOrder']) {
      await page.locator(`g.node[id*="classId-${id}-"]`).screenshot({path:path.join(__dirname,`check-${id}.png`)});
    }
    await sharp(path.join(out,'V2.1.0-fixed.png')).resize({width:1800}).png().toFile(path.join(out,'preview.png'));
    delete report.svg;
    fs.writeFileSync(path.join(out,'verification.json'),JSON.stringify(report,null,2));
    console.log(report);
  } finally { await browser.close(); server.close(); }
})().catch(e=>{console.error(e);server.close();process.exitCode=1;});
