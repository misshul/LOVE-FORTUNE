const fs=require('fs'),a=require('assert/strict');
const read=f=>JSON.parse(fs.readFileSync('docs/contracts/examples/'+f,'utf8')),eq=(x,y)=>a.deepEqual(x,y);
const sum=x=>x.reduce((s,n)=>s+n,0),mean=x=>x.length?sum(x)/x.length:0;let old=0,v3=0;
const v=read('arithmetic-period-dst-vectors.json');
for(const t of v.confidence){let e=sum(t.eligible),w=sum(t.available.map(x=>x.w)),c=e?Math.min(1,w/e):0,r=w?sum(t.available.map(x=>x.w*x.c))/w:0;eq({coverage:c,categoryResultConfidence:r,resultConfidence:c*r},t.expected);old++;}
for(const t of v.candidates){let c=1-(Math.max(...t.values)-Math.min(...t.values))/2;eq(c,t.agreement);eq(c*t.present/t.total,t.confidence);eq(mean(t.values),t.mean);old++;}
for(const t of v.sampleSignals){let s=t.samples.filter(x=>x.signal!==null),peak=[...s].sort((x,y)=>Math.abs(y.signal)-Math.abs(x.signal)||x.time.localeCompare(y.time))[0];eq(s.length?.75*mean(s.map(x=>x.signal))+.25*peak.signal:null,t.expected);old++;}
const raw=f=>{let w=sum(f.map(x=>x.w));return w?50+50*sum(f.map(x=>x.w*x.v))/w:50;};
function guard(input){let f=structuredClone(input).sort((x,y)=>x.id.localeCompare(y.id));const diff=i=>Math.abs(raw(f)-raw(f.filter((_,j)=>j!==i)));for(let p=0;p<32;p++){for(let i=0;i<f.length;i++){if(diff(i)<=20)continue;let lo=0,hi=f[i].w;for(let k=0;k<32;k++){f[i].w=(lo+hi)/2;if(diff(i)<=20)lo=f[i].w;else hi=f[i].w;}f[i].w=lo;}if(f.every((_,i)=>diff(i)<=20))return{status:'PASS',f};}return{status:'SCORE_GUARDRAIL_UNSATISFIED',f};}
for(const t of v.guardrail){let r=guard(t.features);eq(r.status,t.expected);if(t.raw!==undefined)eq(raw(r.f),t.raw);eq(r,guard([...t.features].reverse()));old++;}
for(const t of v.outerAfterGuardrail){let r=guard(t.features);eq(r.status,'PASS');eq(sum(r.f.filter(x=>x.outer).map(x=>x.w))/sum(r.f.map(x=>x.w))>.15?'SCORE_CAP_INVARIANT_FAILED':'PASS',t.expected);old++;}
for(const t of v.periods){let n=(Date.parse(t.end)-Date.parse(t.start))/86400000+1;eq(t.start<'1900-01-01'||t.end>'2099-12-31'?'UNSUPPORTED_DATE':n<=0?'INVALID_DATE_RANGE':n>31?'DATE_RANGE_TOO_LARGE':n,t.expected);old++;}
for(const t of v.slope){let mx=mean(t.x),my=mean(t.y);eq(t.x.length<2?null:sum(t.x.map((x,i)=>(x-mx)*(t.y[i]-my)))/sum(t.x.map(x=>(x-mx)**2)),t.expected);old++;}
function round(s){s=String(s);let neg=s[0]==='-';if(neg)s=s.slice(1);let [i,d='']=s.split('.');d=d.padEnd(5,'0');let n=BigInt(i)*10000n+BigInt(d.slice(0,4));if(d[4]>='5')n++;return Number(n)*(neg?-1:1)/10000;}
for(const t of v.rounding){eq(round(t.input),t.expected);old++;}
for(const t of v.aggregates){eq(round(mean(t.values)),t.canonicalMean);if(t.populationStdDev!==undefined)eq(Math.sqrt(mean(t.values.map(x=>(x-mean(t.values))**2))),t.populationStdDev);old++;}
for(const t of v.ties){eq([...t.items].sort((x,y)=>(t.order==='best'?y.score-x.score:x.score-y.score)||y.resultConfidence-x.resultConfidence||x.date.localeCompare(y.date)).map(x=>x.date),t.expectedDates);old++;}
for(const t of v.dst){let fmt=new Intl.DateTimeFormat('en-CA',{timeZone:t.timezone,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}),base=Date.parse(t.local+':00Z'),best=null;for(let ms=base-26*3600000;ms<=base+26*3600000;ms+=60000){let p=Object.fromEntries(fmt.formatToParts(ms).map(p=>[p.type,p.value])),local=p.year+'-'+p.month+'-'+p.day+'T'+p.hour+':'+p.minute;if(local>=t.local&&(!best||local<best.local))best={local,ms};}eq(new Date(best.ms).toISOString(),t.expected);old++;}
// Exact decimal rational operations avoid binary reduction noise; no tolerance.
const rat=n=>{let [i,d='']=String(n).split('.');return[BigInt(i+d),10n**BigInt(d.length)];},add=(x,y)=>[x[0]*y[1]+y[0]*x[1],x[1]*y[1]],mul=(x,y)=>[x[0]*y[0],x[1]*y[1]],div=(x,y)=>[x[0]*y[1],x[1]*y[0]],num=x=>Number(x[0])/Number(x[1]),rs=arr=>arr.map(rat).reduce(add,[0n,1n]);
const t3=read('arithmetic-v3-vectors.json');
for(const t of t3.coverage){let e=rs(t.eligible),w=rs(t.available.map(x=>x.w)),u=t.available.filter(x=>x.w>0&&x.c>0).length,c=e[0]?div(w,e):rat(0),r=w[0]?div(t.available.map(x=>mul(rat(x.w),rat(x.c))).reduce(add,rat(0)),w):rat(0);eq({coverage:num(c),categoryResultConfidence:num(r),resultConfidence:num(mul(c,r)),usable:u},t.expected);v3++;}
for(const t of t3.sourceConfidence){eq(num(div(rs(t.samples.map(x=>x??0)),rat(4))),t.expected);v3++;}
for(const t of t3.dailyConfidence){eq(mean(t.sources.filter(x=>x.available).map(x=>x.c)),t.expected);v3++;}
for(const t of t3.validDays){eq(t.score!==null&&t.lifetime!==null&&!t.error&&t.status!=='INSUFFICIENT_PERIOD_DATA'&&t.supported,t.expected);v3++;}
for(const t of t3.weekly){eq(t.scores.length?mean(t.scores):null,t.expectedScore);eq(mean(t.confidence),t.expectedConfidence);v3++;}
for(const t of t3.action){let f=t.refs.map(id=>t.features.find(x=>x.id===id));let result;if(f.some(x=>!x))result='INVALID_EVIDENCE_REF';else{f=f.filter(x=>x.w>0);result=f.length?num(div(f.map(x=>mul(rat(x.w),rat(x.c))).reduce(add,rat(0)),rs(f.map(x=>x.w)))):null;}eq(result,t.expected);v3++;}
for(const t of t3.trend){let delta=t.period===null||t.lifetime===null?null:t.period-t.lifetime;eq(delta,t.delta);eq(delta===null?'INSUFFICIENT_DATA':Math.abs(delta)<3?'STABLE':Math.abs(delta)<8?'NOTICEABLE':'SIGNIFICANT',t.status);eq(delta===null?'UNKNOWN':Math.abs(delta)<3?'STABLE':delta>0?'UP':'DOWN',t.direction);v3++;}
for(const t of t3.bands){eq(t.score===null?'INSUFFICIENT_DATA':t.score<45?'CAUTION':t.score<60?'BALANCED':t.score<75?'GOOD':t.score<85?'VERY_GOOD':'EXCELLENT',t.status);v3++;}
console.log(JSON.stringify({existingSemanticVectors:old,v3SemanticVectors:v3,totalSemanticVectors:old+v3,dstVectors:v.dst.length,tzdb:process.versions.tz,icu:process.versions.icu,result:'PASS',comparison:'exact; decimal rational confidence and HALF_UP rounding'}));
