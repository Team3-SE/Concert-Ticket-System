const fs = require('fs');
const path = require('path');
const src = fs.readFileSync('C:/Windows/Fonts/angsana.ttc');
const names = [];
let css = '';
for(let i=0;i<src.readUInt32BE(8);i++) {
  const start = src.readUInt32BE(12+i*4);
  const count = src.readUInt16BE(start+4);
  const tables=[];
  for(let j=0;j<count;j++) {
    const p=start+12+j*16;
    tables.push({tag:src.toString('ascii',p,p+4),checksum:src.readUInt32BE(p+4),offset:src.readUInt32BE(p+8),length:src.readUInt32BE(p+12)});
  }
  const table=tables.find(t=>t.tag==='name');
  const strings=table.offset+src.readUInt16BE(table.offset+4);
  const namerecords={};
  for(let j=0;j<src.readUInt16BE(table.offset+2);j++) {
    const p=table.offset+6+j*12;
    if(src.readUInt16BE(p)!==3 || src.readUInt16BE(p+4)!==0x0409)continue;
    const id=src.readUInt16BE(p+6), len=src.readUInt16BE(p+8), offset=strings+src.readUInt16BE(p+10);
    namerecords[id]=Buffer.from(src.subarray(offset,offset+len)).swap16().toString('utf16le');
  }
  names.push(namerecords[1]+' '+namerecords[2]);
  if(namerecords[1]!=='Angsana New' || !['Regular','Bold'].includes(namerecords[2]))continue;
  let length=12+count*16;
  for(const t of tables)length+=Math.ceil(t.length/4)*4;
  const ttf=Buffer.alloc(length);
  src.copy(ttf,0,start,start+12);
  let next=12+count*16,head;
  tables.forEach((t,j)=>{
    const p=12+j*16;
    ttf.write(t.tag,p,4,'ascii');ttf.writeUInt32BE(t.checksum,p+4);ttf.writeUInt32BE(next,p+8);ttf.writeUInt32BE(t.length,p+12);
    src.copy(ttf,next,t.offset,t.offset+t.length);
    if(t.tag==='head')head=next;
    next+=Math.ceil(t.length/4)*4;
  });
  ttf.writeUInt32BE(0,head+8);
  let sum=0;for(let j=0;j<ttf.length;j+=4)sum=(sum+ttf.readUInt32BE(j))>>>0;
  ttf.writeUInt32BE((0xB1B0AFBA-sum)>>>0,head+8);
  css+=`@font-face{font-family:'Angsana New';font-style:normal;font-weight:${namerecords[2]==='Bold'?700:400};src:url(data:font/ttf;base64,${ttf.toString('base64')}) format('truetype');}`;
}
fs.writeFileSync(path.join(__dirname,'font.css'),css);
console.log({fonts:names,embeddedCSSBytes:css.length});
