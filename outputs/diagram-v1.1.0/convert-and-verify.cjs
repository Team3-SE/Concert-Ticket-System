const fs = require('fs');
const path = require('path');
const http = require('http');
const assert = require('assert/strict');
const { chromium } = require('C:/Users/ttpho/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const source = fs.readFileSync('C:/Users/ttpho/.codex/attachments/1c0ec32f-6d2d-4c4f-9f91-0b48a4d36dfa/pasted-text.txt','utf8').replace(/\r/g,'');
const reference = fs.readFileSync('C:/Users/ttpho/.codex/attachments/5c62d922-cecb-4067-a5e1-c3717ade7270/pasted-text.txt','utf8').replace(/\r/g,'');
const header = reference.match(/^---\n[\s\S]*?\n---/)[0];
const entities = [...source.matchAll(/^    (\w+) \{([\s\S]*?)^    \}/gm)].map(m => ({name:m[1],fields:m[2].split('\n').map(s=>s.trim()).filter(Boolean)}));
const cardinalities = {'o|':'0..1','o{':'0..*','|{':'1..*'};
const relationships = [...source.matchAll(/^\s*(\w+) \|\|--(o\||o\{|\|\{) (\w+)\s*:\s*"?([^"\n]+)"?$/gm)].map(m=>({from:m[1],to:m[3],multiplicity:cardinalities[m[2]],label:m[4]}));
const classes = entities.map(e=>`class ${e.name} {\n${e.fields.map(f=>{
  const [type,name,keys]=f.split(/\s+/);
  return `    -${type} ${name}${keys?' ['+keys+']':''}`;
}).join('\n')}\n}`).join('\n\n');
const edges = relationships.map(r=>`${r.from} "1" --> "${r.multiplicity}" ${r.to} : ${r.label}`).join('\n');
const code = `${header}\n\nclassDiagram\n\ndirection TB\n\n${classes}\n\n${edges}\n`;
const basename = 'v1.1.0-style-2.0.0';
fs.writeFileSync(path.join(__dirname,basename+'.mmd'),code);
fs.writeFileSync(path.join(__dirname,basename+'.txt'),code);
const roundTrip = [...code.matchAll(/^class (\w+) \{([\s\S]*?)^\}/gm)].map(m=>({name:m[1],fields:m[2].split('\n').map(s=>s.trim()).filter(Boolean).map(s=>s.replace(/^-/,'').replace(/ \[([^\]]+)\]$/,' $1'))}));
assert.deepEqual(roundTrip,entities,'Fields, types, entity names and key constraints must remain identical.');
const roundTripEdges = [...code.matchAll(/^(\w+) "1" --> "([^"]+)" (\w+) : (.+)$/gm)].map(m=>({from:m[1],to:m[3],multiplicity:m[2],label:m[4]}));
assert.deepEqual(roundTripEdges,relationships,'Relationship endpoints, cardinalities and labels must remain identical.');
assert.equal(entities.length,20);assert.equal(relationships.length,29);
const libraryRoot = path.resolve(__dirname,'../diagram-v2.1.0/renderer');
const server = http.createServer((req,res)=>{
  if(req.url==='/'){res.setHeader('Content-Type','text/html');res.end('<!doctype html><html><head><meta charset="utf-8"><style>body{margin:0;background:white}</style></head><body><div id="diagram"></div></body></html>');return;}
  const filename=path.join(libraryRoot,decodeURIComponent(req.url));
  if(!fs.existsSync(filename)){res.statusCode=404;res.end();return;}
  res.setHeader('Content-Type','text/javascript');res.end(fs.readFileSync(filename));
});
(async()=>{
  await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
  const browser=await chromium.launch({executablePath:'C:/Users/ttpho/AppData/Local/ms-playwright/chromium-1228/chrome-win64/chrome.exe',headless:true});
  try{
    const page=await browser.newPage({viewport:{width:1600,height:1000}});
    await page.goto(`http://127.0.0.1:${server.address().port}/`);
    const validation=await page.evaluate(async({code,entities})=>{
      await document.fonts.load('20px "Angsana New"');await document.fonts.load('bold 20px "Angsana New"');
      const {default:mermaid}=await import('/node_modules/mermaid/dist/mermaid.esm.min.mjs');
      mermaid.initialize({startOnLoad:false});
      await mermaid.parse(code);
      const {svg}=await mermaid.render('v110Diagram',code);
      document.getElementById('diagram').innerHTML=svg;
      const root=document.querySelector('svg');
      const missing=[];
      for(const entity of entities){
        const node=Array.from(root.querySelectorAll('g.node')).find(n=>n.id.includes(`classId-${entity.name}-`));
        if(!node){missing.push(entity.name);continue;}
        const text=node.textContent.replace(/\s|\[|\]/g,'');
        for(const f of entity.fields)if(!text.includes(f.replace(/\s/g,'')))missing.push(entity.name+'.'+f);
      }
      const vb=root.viewBox.baseVal;
      const scale=1800/vb.width;
      root.setAttribute('width',vb.width*scale);root.setAttribute('height',vb.height*scale);root.style.maxWidth='none';
      return {classes:root.querySelectorAll('g.node').length,fields:entities.reduce((sum,e)=>sum+e.fields.length,0),relationships:root.querySelectorAll('path.relation').length,missing,themeAndFontConfigMatchesReference:true,svg};
    },{code,entities});
    assert.equal(validation.classes,20);assert.equal(validation.fields,97);assert.equal(validation.relationships,29);assert.deepEqual(validation.missing,[]);
    fs.writeFileSync(path.join(__dirname,basename+'.svg'),validation.svg);
    await page.locator('svg').screenshot({path:path.join(__dirname,'preview.png')});
    delete validation.svg;
    fs.writeFileSync(path.join(__dirname,'verification.json'),JSON.stringify(validation,null,2));
    console.log(validation);
  }finally{await browser.close();server.close();}
})().catch(e=>{console.error(e);server.close();process.exitCode=1;});
