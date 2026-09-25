"""Offline FP-01 documentation reference builder; Python stdlib only.
Usage: python build.py INPUT_DIRECTORY OUTPUT_FILE
No network, no production engine implementation, no fixture regeneration.
"""
import pathlib,sys,json,re,gzip,hashlib,datetime
from fractions import Fraction as F
EPOCH=datetime.datetime(1970,1,1)
REF='NAOJ_SOLAR_TERMS_1899_2100_V1'; BRIDGE='SAJU_TIME_SCALE_BRIDGE_V1'
TERMS='CHUNFEN QINGMING GUYU LIXIA XIAOMAN MANGZHONG XIAZHI XIAOSHU DASHU LIQIU CHUSHU BAILU QIUFEN HANLU SHUANGJIANG LIDONG XIAOXUE DAXUE DONGZHI XIAOHAN DAHAN LICHUN YUSHUI JINGZHE'.split()
def sec(s):return (datetime.datetime.fromisoformat(s)-EPOCH).days*86400+(datetime.datetime.fromisoformat(s)-EPOCH).seconds
def sha(b):return hashlib.sha256(b).hexdigest()
def canonical(x):return (json.dumps(x,sort_keys=True,ensure_ascii=True,separators=(',',':'))+'\n').encode()
def read(r,n):return json.loads((r/n).read_text(encoding='utf-8-sig'))
def build(r):
 manifest=read(r,'source-manifest.json')
 for n,h in manifest['files'].items():assert sha((r/'sources'/n).read_bytes())==h,n
 raw=json.loads(gzip.decompress((r/'sources/naoj-tt0.json.gz').read_bytes()))
 dt=read(r,'delta-t-s2020.json'); drift=read(r,'historical-drift.json'); leap=read(r,'leap-seconds.json'); tz=read(r,'timezone-identity.json')
 delta={x['year']:x for x in dt['values']};assert list(sorted(delta))==list(range(1899,2101))
 events=[]
 for y in range(1899,2101):
  text=raw[str(y)];assert sha(text.encode('latin1'))==manifest['entries'][y-1899]['ttRawSha256']
  rows=re.findall(r'(<tr><td>(\d{4}-\d\d-\d\d)</td>\s*<td>(\d\d:\d\d)</td>\s*<td>Sun</td>\s*<td>24 Solar Terms</td>\s*<td>.*?</td>\s*<td>(.*?) \(&lambda; = (\d+)&deg;\)</td></tr>)',text)
  assert len(rows)==24,(y,len(rows))
  for idx,(row,date,hm,name,angle) in enumerate(rows):
   angle=int(angle);assert angle==(285+idx*15)%360
   h,m=map(int,hm.split(':'));assert 0<=h<=24 and 0<=m<60 and (h!=24 or m==0)
   normalized=datetime.datetime.fromisoformat(date)+datetime.timedelta(hours=h,minutes=m);t=sec(normalized.isoformat());candidates=[]
   if normalized.year in delta:
    s=F(t)-F(delta[normalized.year]['deltaTSeconds'])
    if s<sec('1961-01-01'):candidates.append((s,'PRE_1961_S2020',str(normalized.year)))
   for interval in drift['intervals']:
    b=F(interval['baseOffset']); rate=F(interval['dailyDrift']);ref=F(interval['referenceMJD'])
    s=(F(t)-F('32.184')-b-(F(40587)-ref)*rate)/(1+rate/86400)
    if sec(interval['effectiveStart'])<=s<sec(interval['effectiveEnd']):candidates.append((s,'UTC_1961_1971_DRIFT',interval['effectiveStart']))
   for interval in leap['intervals']:
    s=F(t)-F('32.184')-F(interval['taiMinusUtcSeconds'])
    if sec(interval['effectiveDate'])<=s<sec(interval['effectiveEnd']):
     era='FROZEN_FUTURE_37' if s>=sec(leap['knownSnapshotBoundary']) else 'KNOWN_TAI_UTC'
     candidates.append((s,era,interval['effectiveDate']))
   assert len(candidates)==1,(date,hm,candidates)
   s,era,key=candidates[0];us=s*1000000;boundary=-((-us.numerator)//us.denominator)
   assert -(2**63)<=boundary<2**63
   e=dict(eventId=f'SOLAR_{y}_{TERMS[angle//15]}',year=y,termId=TERMS[angle//15],solarLongitude=angle,sourceDate=date,sourceHourMinute=hm,normalizedTT=normalized.isoformat(),sourcePrecision='MINUTE',sourceTimeScale='TT_EQUIVALENT_NAOJ_EXPLICIT_DT_ZERO',sourceBoundaryConvention='FROZEN_TT_DISPLAYED_MINUTE_START',serviceBoundaryUs=str(boundary),solarReferenceVersion=REF,timeScaleBridgeVersion=BRIDGE,eraConversionType=era,conversionIntervalKey=key,deltaTSourceVersion=dt['version'] if era=='PRE_1961_S2020' else None,historicalUtcDriftTableVersion=drift['version'] if era=='UTC_1961_1971_DRIFT' else None,leapSecondTableVersion=leap['version'] if era in ['KNOWN_TAI_UTC','FROZEN_FUTURE_37'] else None,futureConvention=leap['futureConvention'] if era=='FROZEN_FUTURE_37' else None,sourceRowHash=sha(row.encode('latin1')),exactServiceCoordinateSeconds=f'{s.numerator}/{s.denominator}')
   events.append(e)
 assert len(events)==4848 and len({e['eventId'] for e in events})==4848
 assert all(int(a['serviceBoundaryUs'])<int(b['serviceBoundaryUs']) for a,b in zip(events,events[1:]))
 metadata=dict(referenceVersion=REF,bridgeVersion=BRIDGE,primaryAuthority='NAOJ',sourceVersion=manifest['sourceVersion'],retrievalTimestamp=manifest['retrievalTimestamp'],rawManifestChecksum=manifest['rawManifestChecksum'],referenceRange=[1899,2100],eventCount=len(events),comparisonAxis='SERVICE_PROLEPTIC_POSIX_V1',coordinateUnit='SIGNED_INTEGER_MICROSECONDS_DECIMAL_STRING',roundingRule='MATHEMATICAL_CEIL_EXACT_SECONDS_TIMES_1000000',sourcePrecision='MINUTE',boundaryConvention='FROZEN_TT_DISPLAYED_MINUTE_START_THEN_BRIDGE_NO_REROUND',preUtcConvention='HISTORICAL_CIVIL_APPROXIMATION',futureConvention=leap['futureConvention'],deltaTSnapshotVersion=dt['version'],driftSnapshotVersion=drift['version'],leapSecondSnapshotVersion=leap['version'],timezoneDataVersion=tz['phpTimezoneDataVersion'],normalizationVersion='FP01_NORMALIZATION_V1',inputChecksums={n:sha((r/n).read_bytes()) for n in ['bridge.json','source-manifest.json','delta-t-s2020.json','historical-drift.json','leap-seconds.json','timezone-identity.json']},artifactChecksumAlgorithm='SHA256(canonical sorted-key ASCII JSON + LF, excluding only metadata.artifactChecksum)')
 result=dict(metadata=metadata,events=events);metadata['artifactChecksum']=sha(canonical(result));return result
if __name__=='__main__':
 result=build(pathlib.Path(sys.argv[1]));pathlib.Path(sys.argv[2]).write_bytes(canonical(result));print(json.dumps(dict(events=len(result['events']),checksum=result['metadata']['artifactChecksum'])))
