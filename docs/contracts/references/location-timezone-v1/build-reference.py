"""Offline reference build. Usage: python build-reference.py INPUT_DIR OUTPUT_DIR.
INPUT_DIR holds pinned sources/ and explicit location-manifest/registry JSON.
No network; no runtime dependencies introduced. Output must be outside repository first.
"""
import pathlib,tarfile,zipfile,subprocess,json,hashlib,re,datetime,zoneinfo,sys
P=pathlib.Path(sys.argv[1]);D=pathlib.Path(sys.argv[2]);D.mkdir(parents=True,exist_ok=True)
UTC=datetime.timezone.utc;START=-2240524800;END=4133980800
canon=lambda x:(json.dumps(x,ensure_ascii=False,sort_keys=True,separators=(',',':'))+'\n').encode()
sha=lambda b:hashlib.sha256(b).hexdigest()
def read(n):return json.loads((P/n).read_text(encoding='utf-8-sig'))
def save(n,x): (D/n).write_bytes(canon(x))
def seal(x):x['artifactChecksum']=sha(canon(x));return x
manifest=read('location-manifest.json');registry=read('location-registry.json');sources=read('source-manifest.json')
for name,h in sources['files'].items():assert sha((P/'sources'/name).read_bytes())==h,name
assert sources['files']['tzdata2026b.tar.gz']=='114543d9f19a6bfeb5bca43686aea173d38755a3db1f2eec112647ae92c6f544'
rows={}
for archive,name in [('cities500.zip','cities500.txt'),('AU.zip','AU.txt')]:
 with zipfile.ZipFile(P/'sources'/archive) as z:
  for line in z.read(name).decode().splitlines():
   r=line.split('\t')
   if r[0] in rows:assert rows[r[0]]==r
   rows[r[0]]=r
src=D/'work-source';src.mkdir(exist_ok=True)
with tarfile.open(P/'sources/tzdata2026b.tar.gz') as t:t.extractall(src,filter='data')
assert (src/'version').read_text().strip()=='2026b'
files=['africa','antarctica','asia','australasia','europe','northamerica','southamerica','etcetera','backward']
out=D/'work-tzif';out.mkdir(exist_ok=True)
subprocess.run(['zic','-b','fat','-d',str(out)]+[str(src/f) for f in files],check=True)
aliases={}
for f in files:
 for line in (src/f).read_text().splitlines():
  x=line.split('#')[0].split()
  if x and x[0]=='Link':aliases[x[2]]=x[1]
def norm(z):
 seen=set()
 while z in aliases:
  assert z not in seen;seen.add(z);z=aliases[z]
 return z
def micro(s):
 assert re.fullmatch(r'-?\d+(?:\.\d{1,6})?',s),s
 a,_,b=s.lstrip('-').partition('.');v=(int(a)*1000000+int(b.ljust(6,'0')))*(-1 if s.startswith('-') else 1)
 assert abs(v)<=180000000
 return v
def correction(v):
 n=4*(v-127500000);return (-1 if n<0 else 1)*((abs(n)+500000)//1000000)
active={r['sourceGeoNamesId']:r['locationId'] for r in registry['entries']}
assert len(active)==len(registry['entries'])==129
locations=[];goldloc=[]
for m in manifest['records']:
 r=rows[m['sourceGeoNamesId']];assert sha(('\t'.join(r)+'\n').encode())==m['sourceRowChecksum'];assert active[r[0]]==m['locationId']
 for k,v in [('countryCode',r[8]),('admin1',r[10]),('cityName',r[1]),('featureCode',r[7]),('sourceTimezoneId',r[17]),('longitudeDecimal',r[5])]:assert m[k]==v
 record={'locationId':m['locationId'],'sourceGeoNamesId':r[0],'displayName':m['admin1Name']+' / '+r[1]+' ('+r[8]+')','countryCode':r[8],'admin1':r[10],'timezoneId':norm(r[17]),'longitudeDecimal':r[5],'longitudeMicrodegrees':micro(r[5]),'latitudeDecimal':r[4],'locationReferenceVersion':'SAJU_LOCATION_REFERENCE_V1','referenceSource':'GeoNames','referenceLicense':'CC BY 4.0','supportedFrom':'1900-01-01','supportedTo':'2099-12-31'}
 record['referenceChecksum']=sha(canon(record));locations.append(record)
for g in manifest['validationOnlySourceIds']:
 r=rows[g];goldloc.append({'fixtureId':'GEONAMES_'+g,'sourceGeoNamesId':g,'displayName':r[1],'timezoneId':norm(r[17]),'longitudeDecimal':r[5],'longitudeMicrodegrees':micro(r[5]),'publicSelectable':False})
assert len({r['locationId'] for r in locations})==129
assert len([r for r in locations if r['countryCode']=='KR'])==17
jp=[r for r in locations if r['countryCode']=='JP'];assert len(jp)==len({r['admin1'] for r in jp})==47
zones={};probes=0
for z in sorted({r['timezoneId'] for r in locations+goldloc}):
 with (out/z).open('rb') as f:zi=zoneinfo.ZoneInfo.from_file(f,key=z)
 dt=datetime.datetime.fromtimestamp(START,UTC).astimezone(zi)
 ts=[{'serviceStartUs':str(START*1000000),'offsetSeconds':int(dt.utcoffset().total_seconds()),'isDst':bool(dt.dst()),'abbreviation':dt.tzname()}]
 for line in subprocess.check_output(['zdump','-V','-c','1899,2101',str(out/z)],text=True).splitlines():
  m=re.search(r'  (.*?) UT = .* isdst=(\d) gmtoff=(-?\d+)$',line)
  if not m:continue
  t=int(datetime.datetime.strptime(m[1],'%a %b %d %H:%M:%S %Y').replace(tzinfo=UTC).timestamp())
  if not START<t<END:continue
  local=datetime.datetime.fromtimestamp(t,UTC).astimezone(zi)
  v={'serviceStartUs':str(t*1000000),'offsetSeconds':int(m[3]),'isDst':bool(int(m[2])),'abbreviation':local.tzname()}
  assert int(local.utcoffset().total_seconds())==v['offsetSeconds']
  if any(v[k]!=ts[-1][k] for k in ['offsetSeconds','isDst','abbreviation']):ts.append(v)
 idx=0
 for t in range(START,END,86400):
  while idx+1<len(ts) and int(ts[idx+1]['serviceStartUs'])//1000000<=t:idx+=1
  local=datetime.datetime.fromtimestamp(t,UTC).astimezone(zi)
  assert ts[idx]['offsetSeconds']==int(local.utcoffset().total_seconds()) and ts[idx]['isDst']==bool(local.dst());probes+=1
 for i,v in enumerate(ts[1:],1):
  point=int(v['serviceStartUs'])//1000000
  for t,expected in [(point-1,ts[i-1]),(point,v)]:
   local=datetime.datetime.fromtimestamp(t,UTC).astimezone(zi);assert expected['offsetSeconds']==int(local.utcoffset().total_seconds()) and expected['isDst']==bool(local.dst());probes+=1
 zones[z]={'timezoneId':z,'timezoneReferenceVersion':'SAJU_TIMEZONE_REFERENCE_V1','transitions':ts}
loc=seal({'version':'SAJU_LOCATION_REFERENCE_V1','source':'GeoNames','license':'CC BY 4.0','sourceArchiveIdentity':['cities500.zip'],'sourceArchiveChecksum':sources['files']['cities500.zip'],'normalizationVersion':'LOCATION_NORMALIZE_V1','recordCount':129,'supportedFrom':'1900-01-01','supportedTo':'2099-12-31','records':locations})
tz=seal({'version':'SAJU_TIMEZONE_REFERENCE_V1','ianaTzdbVersion':'2026b','sourceArchiveIdentity':'tzdata2026b.tar.gz','sourceArchiveChecksum':sources['files']['tzdata2026b.tar.gz'],'normalizationVersion':'TZ_NORMALIZE_V1','comparisonAxis':'SERVICE_PROLEPTIC_POSIX_V1','supportedFromUs':str(START*1000000),'supportedToExclusiveUs':str(END*1000000),'zoneCount':len(zones),'transitionCount':sum(len(z['transitions']) for z in zones.values()),'aliases':{a:norm(a) for a in sorted(aliases) if norm(a) in zones},'zones':list(zones.values())})
save('locations.json',loc);save('timezones.json',tz);save('validation-only-locations.json',goldloc)
save('longitude-goldens.json',[{'locationId':r['locationId'],'longitudeDecimal':r['longitudeDecimal'],'longitudeMicrodegrees':r['longitudeMicrodegrees'],'correctionMinutes':correction(r['longitudeMicrodegrees'])} for r in locations])
save('timezone-manifest.json',{'productionZones':sorted({r['timezoneId'] for r in locations}),'validationOnlyZones':sorted({r['timezoneId'] for r in goldloc}-{r['timezoneId'] for r in locations}),'buildInputs':files,'backzoneIncluded':False,'leapSecondsIncluded':False,'builderVersion':subprocess.check_output(['zic','--version'],text=True).strip(),'zicSha256':sha(pathlib.Path('/usr/sbin/zic').read_bytes()),'zdumpSha256':sha(pathlib.Path('/usr/bin/zdump').read_bytes())})
save('build-validation.json',{'result':'PASS','productionRecords':129,'zoneCount':len(zones),'transitionCount':tz['transitionCount'],'tzifCrossChecks':probes,'duplicateLocationId':0,'duplicateSourceId':0,'missingZone':0,'invalidLongitude':0,'jpUniqueAdmin1':47,'krRecords':17})
print(json.dumps({'result':'PASS','locations':129,'zones':len(zones),'transitionRows':tz['transitionCount'],'referenceProbes':probes}))
