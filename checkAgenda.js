const fs = require('fs');
const path = 'c:/xampp/htdocs/T4L/public/gestionale_utenti.php';
const text = fs.readFileSync(path,'utf8');
let scripts=[];
const re = /<script>([\s\S]*?)<\/script>/g;
let m;
while((m=re.exec(text))!==null){scripts.push(m[1]);}
console.log('Extracted',scripts.length,'<script> blocks');
scripts.forEach((s,i)=>{
  console.log('--- script',i,'len',s.length);
  try{
    new Function(s);
    console.log('script',i,'ok');
  }catch(e){
    console.error('script',i,'syntax error',e);
  }
});
