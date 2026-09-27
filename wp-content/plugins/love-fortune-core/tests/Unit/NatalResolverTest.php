<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

use LoveFortune\Core\Engine\Saju\{NatalResolutionService, LocationReferenceRepository, LocationReferenceProvider,
    TimezoneReferenceRepository, NatalLocalTimeResolver, NatalCivilTime, NatalCandidatePartitioner,
    NatalCandidateContextFactory, SolarTermReference, YearPillarCalculator, MonthPillarCalculator,
    DayPillarCalculator, CalculationDateResolver, HourPillarCalculator};
use LoveFortune\Core\PluginFactory;
use PHPUnit\Framework\TestCase;

final class NatalResolverTest extends TestCase
{
    private function error(string $code, callable $operation): void
    {
        try { $operation(); } catch (Throwable $e) { self::assertSame($code, $e->getMessage()); return; }
        self::fail('Expected explicit internal error');
    }

    /** Fixture provider exists only in tests; production IDs005/006 remain unavailable. */
    private function goldenService(string $zone, int $longitude): NatalResolutionService
    {
        return new NatalResolutionService(new class($zone, $longitude) implements LocationReferenceProvider {
            public function __construct(private string $zone, private int $longitude) {}
            public function get(string $locationId): array {
                if ($locationId !== 'TEST_ONLY') { throw new RuntimeException('LOCATION_NOT_FOUND'); }
                return ['timezoneId' => $this->zone, 'longitudeMicrodegrees' => $this->longitude,
                    'referenceChecksum' => 'SYNTHETIC_TEST_ONLY_NO_PUBLIC_LOCATION_ID'];
            }
        });
    }

    public function testActualProductionLookupAndUnknownIdentifiers(): void
    {
        $repo = new LocationReferenceRepository();
        foreach (['LOC000001' => ['Asia/Tokyo',139691710], 'LOC000002' => ['Asia/Seoul',126978400],
            'LOC000003' => ['America/New_York',-74005970], 'LOC000004' => ['Europe/London',-125740]] as $id => [$zone,$longitude]) {
            $r = $repo->get($id);self::assertSame($zone,$r['timezoneId']);self::assertSame($longitude,$r['longitudeMicrodegrees']);
        }
        foreach (['LOC000005','LOC000006','Tokyo','UNKNOWN','LOC000132'] as $id) {
            $this->error('LOCATION_NOT_FOUND',fn()=> $repo->get($id));
        }
        $this->error('TIMEZONE_NOT_FOUND',fn()=> (new TimezoneReferenceRepository())->intervals('US/Eastern'));
    }

    public function testKnownUniqueAndExactMinutePoint(): void
    {
        $s = new NatalResolutionService();
        foreach (['LOC000001','LOC000002','LOC000003','LOC000004'] as $id) {
            $r = $s->resolve('2020-01-15',$id,'12:00');self::assertSame('UNIQUE',$r['status']);self::assertTrue($r['timeKnown']);
            self::assertCount(1,$r['candidates']);$c=$r['candidates'][0];self::assertSame('POINT',$c['kind']);
            self::assertNull($c['serviceRange']);self::assertNull($c['adjustedCivilRange']);self::assertSame('C0',$c['candidateId']);
            self::assertSame(0,(int)$c['serviceCoordinateUs']%60000000);self::assertSame(1,$c['provenance']['correctionApplications']);
            self::assertSame($c['dayPillar']['pillar']['stemIndex'],$c['hourPillar']['dayStemIndexUsed']);
            self::assertSame($c['yearPillar']['stemIndex'],$c['monthPillar']['yearStemIndex']);
        }
    }

    public function testNewYorkFoldPreservesIdenticalPillarsAndDistinctLineages(): void
    {
        $s=new NatalResolutionService();$r=$s->resolve('2020-11-01','LOC000003','01:30');
        self::assertSame('FOLD_AMBIGUOUS',$r['status']);self::assertCount(2,$r['candidates']);[$a,$b]=$r['candidates'];
        self::assertSame(['1604208600000000','1604212200000000'],array_column($r['candidates'],'serviceCoordinateUs'));
        self::assertSame([-14400,-18000],array_column($r['candidates'],'historicalOffsetSeconds'));
        self::assertSame(['C0','C1'],array_column($r['candidates'],'candidateId'));
        self::assertNotSame($a['provenance']['transitionIdentity'],$b['provenance']['transitionIdentity']);
        foreach (['yearPillar','monthPillar','dayPillar','hourPillar'] as $key) { self::assertSame($a[$key],$b[$key]); }
        self::assertSame($r,$s->resolve('2020-11-01','LOC000003','01:30'));
        self::assertSame('C0',$s->resolve('2020-01-15','LOC000001','12:00')['candidates'][0]['candidateId']);
    }

    public function testGapAndGoldenOnlyNonHourAndDateSkip(): void
    {
        $r=(new NatalResolutionService())->resolve('2020-03-08','LOC000003','02:30');
        self::assertSame('GAP_UNRESOLVED',$r['status']);self::assertSame([],$r['candidates']);self::assertTrue($r['timeKnown']);
        $lord=$this->goldenService('Australia/Lord_Howe',159085790);
        $fold=$lord->resolve('2020-04-05','TEST_ONLY','01:45');self::assertSame('FOLD_AMBIGUOUS',$fold['status']);
        self::assertSame(['1586011500000000','1586013300000000'],array_column($fold['candidates'],'serviceCoordinateUs'));
        self::assertSame([39600,37800],array_column($fold['candidates'],'historicalOffsetSeconds'));
        self::assertSame('GAP_UNRESOLVED',$lord->resolve('2020-10-04','TEST_ONLY','02:15')['status']);
        $apia=$this->goldenService('Pacific/Apia',-171766660)->resolve('2011-12-30','TEST_ONLY');
        self::assertSame('GAP_UNRESOLVED',$apia['status']);self::assertFalse($apia['timeKnown']);self::assertSame([],$apia['candidates']);
    }

    public function testMappingSearchHandlesMoreThanTwoAndOrderIndependent(): void
    {
        // Three disjoint synthetic source intervals all map the same civil point.
        $rows=[];foreach ([0,3600,7200] as $i=>$offset) {
            $s=-$offset*1000000;$rows[]=['startUs'=>$s-1000000,'endUs'=>$s+1000000,
                'historicalOffsetSeconds'=>$offset,'isDst'=>false,'transitionIdentity'=>'SYNTHETIC_'.$i];
        }
        $resolver=new NatalLocalTimeResolver();$a=$resolver->mappings($rows,0,true);
        self::assertCount(3,$a);self::assertSame([-7200000000,-3600000000,0],array_column($a,'startUs'));
        self::assertSame($a,$resolver->mappings(array_reverse($rows),0,true));
    }

    public function testUnknownOrdinaryDateIsIntervalsWithoutFakeBirthTime(): void
    {
        $s=new NatalResolutionService();$r=$s->resolve('2019-01-27','LOC000001');
        self::assertSame('UNKNOWN_TIME',$r['status']);self::assertFalse($r['timeKnown']);self::assertCount(2,$r['candidates']);
        self::assertSame(['2019-01-27','2019-01-28'],array_map(fn($c)=>$c['dayPillar']['calculationDate'],$r['candidates']));
        foreach($r['candidates'] as $c){self::assertSame('INTERVAL',$c['kind']);self::assertNull($c['serviceCoordinateUs']);self::assertNull($c['adjustedCivil']);self::assertFalse($c['hourPillar']['known']);}
        self::assertSame('2019-01-27T23:30:00.000000',$r['candidates'][0]['adjustedCivilRange']['end']);
        self::assertSame($r,$s->resolve('2019-01-27','LOC000001',null));
    }

    public function testUnknownDstDateDurationAndTransitionLineage(): void
    {
        $s=new NatalResolutionService();
        foreach([['2020-03-08',23],['2020-11-01',25]] as [$date,$hours]){
            $r=$s->resolve($date,'LOC000003');$total=0;$previous=null;$lineages=[];
            foreach($r['candidates'] as $i=>$c){$a=(int)$c['serviceRange']['startUs'];$b=(int)$c['serviceRange']['endUs'];self::assertLessThan($b,$a);$total+=$b-$a;
                if($previous!==null){self::assertGreaterThanOrEqual($previous,$a);} $previous=$b;
                self::assertSame('C'.$i,$c['candidateId']);$lineages[$c['provenance']['transitionIdentity']]=true;self::assertFalse($c['hourPillar']['known']);}
            self::assertSame($hours*3600000000,$total);self::assertCount(2,$lineages);
        }
    }

    public function testUnknownLichunJieAndNoZhongqiOrHourCuts(): void
    {
        $s=new NatalResolutionService();
        // Approved fixed FP02/03 fixture boundaries. Civil dates are derived in UTC+9 only for fixture selection.
        foreach([['949667995816000','己卯','庚辰','戊寅'],['952238575816000','庚辰','庚辰','己卯']] as [$boundary,$beforeYear,$afterYear,$afterMonth]){
            $date=NatalCivilTime::datetime((int)$boundary+32400000000)->format('Y-m-d');
            $r=$s->resolve($date,'LOC000001');$before=$after=null;
            foreach($r['candidates'] as $c){if($c['serviceRange']['endUs']===$boundary)$before=$c;if($c['serviceRange']['startUs']===$boundary)$after=$c;}
            self::assertNotNull($before);self::assertNotNull($after);self::assertSame($beforeYear,$before['yearPillar']['ganzhi']);self::assertSame($afterYear,$after['yearPillar']['ganzhi']);self::assertSame($afterMonth,$after['monthPillar']['ganzhi']);
            self::assertSame($after['yearPillar']['stemIndex'],$after['monthPillar']['yearStemIndex']);
        }
        $solar=json_decode(file_get_contents(dirname(__DIR__,2).'/config/references/solar-terms-v1.json'),true);
        $event=array_values(array_filter($solar['events'],fn($e)=>$e['year']===2000&&$e['termId']==='CHUNFEN'))[0];
        $date=NatalCivilTime::datetime((int)$event['serviceBoundaryUs']+32400000000)->format('Y-m-d');
        $r=$s->resolve($date,'LOC000001');self::assertCount(2,$r['candidates']);
        foreach($r['candidates'] as $c){self::assertNotSame($event['serviceBoundaryUs'],$c['serviceRange']['startUs']);self::assertNotSame($event['serviceBoundaryUs'],$c['serviceRange']['endUs']);}
    }

    public function testIntervalInvarianceAndAtomicPairingAtBothEndpoints(): void
    {
        $s=new NatalResolutionService();$factory=new NatalCandidateContextFactory();
        foreach(['LOC000001','LOC000002','LOC000003','LOC000004'] as $id){
            foreach(['1900-01-01','2000-02-04','2020-03-08','2020-11-01','2099-12-31'] as $date){
                $location=(new LocationReferenceRepository())->get($id);
                foreach($s->resolve($date,$id)['candidates'] as $c){
                    foreach([(int)$c['serviceRange']['startUs'],(int)$c['serviceRange']['endUs']-1] as $point){
                        $r=['startUs'=>$point,'endUs'=>$point,'historicalOffsetSeconds'=>$c['historicalOffsetSeconds']]+$c['provenance'];
                        $probe=$factory->create($r,$location['longitudeMicrodegrees'],true);
                        foreach(['yearPillar','monthPillar','dayPillar'] as $field){self::assertSame($c[$field],$probe[$field]);}
                        self::assertSame($probe['dayPillar']['pillar']['stemIndex'],$probe['hourPillar']['dayStemIndexUsed']);
                    }
                }
            }
        }
    }

    public function testNormalizationOnlyMergesEquivalentSameLineage(): void
    {
        $r=(new NatalResolutionService())->resolve('2019-01-27','LOC000001')['candidates'][0];
        $a=$b=$r;$mid=(string)((int)$r['serviceRange']['startUs']+1000000);$a['serviceRange']['endUs']=$mid;$b['serviceRange']['startUs']=$mid;
        $n=new NatalCandidatePartitioner();self::assertCount(1,$n->normalize([$a,$b]));
        $b['provenance']['transitionIdentity'].='OTHER';self::assertCount(2,$n->normalize([$a,$b]));
        $b=$r;$b['serviceRange']['startUs']=(string)((int)$mid+1);self::assertCount(2,$n->normalize([$a,$b]));
    }

    public function testLongitudeExactlyOnceAndMidnightHourPolicy(): void
    {
        $s=new NatalResolutionService();
        foreach([['LOC000001','22:11','23:00','2019-01-27','甲子'],['LOC000001','22:41','23:30','2019-01-28','丙子'],
            ['LOC000002','23:01','22:59','2019-01-27','乙亥'],['LOC000002','23:02','23:00','2019-01-27','甲子'],['LOC000002','23:32','23:30','2019-01-28','丙子']] as [$id,$time,$adjusted,$date,$hour]){
            $c=$s->resolve('2019-01-27',$id,$time)['candidates'][0];self::assertSame('2019-01-27T'.$adjusted.':00.000000',$c['adjustedCivil']);self::assertSame($date,$c['dayPillar']['calculationDate']);self::assertSame($hour,$c['hourPillar']['ganzhi']);
            self::assertSame((string)(NatalCivilTime::input('2019-01-27',$time)-32400000000),$c['serviceCoordinateUs']);
        }
    }

    public function testHistoricalSecondsAndPublicRangeEdgesAllLocations(): void
    {
        $s=new NatalResolutionService();$c=$s->resolve('1900-01-01','LOC000002','12:00')['candidates'][0];
        self::assertSame(30472,$c['historicalOffsetSeconds']);self::assertSame(1808,$c['adjustmentSeconds']);
        self::assertSame('-2208976072000000',$c['serviceCoordinateUs']);self::assertSame('1900-01-01T12:30:08.000000',$c['adjustedCivil']);
        $records=json_decode(file_get_contents(dirname(__DIR__,2).'/config/references/locations-v1.json'),true)['records'];
        foreach($records as $r){foreach(['1900-01-01','2099-12-31'] as $date){foreach([null,'00:00','23:59'] as $time){$v=$s->resolve($date,$r['locationId'],$time);self::assertNotEmpty($v['candidates']);self::assertSame($time===null?'UNKNOWN_TIME':'UNIQUE',$v['status']);}}}
    }

    public function testInputErrorsIdentityAndCorruptedReferencesFailClosed(): void
    {
        $s=new NatalResolutionService();
        foreach(['2020-02-30','2020-1-01','0000-01-01'] as $date){$this->error('INVALID_DATE',fn()=> $s->resolve($date,'LOC000001','12:00'));}
        foreach(['1899-12-31','2100-01-01'] as $date){$this->error('UNSUPPORTED_DATE',fn()=> $s->resolve($date,'LOC000001'));}
        foreach(['24:00','1:00','12:60','12:00:00',''] as $time){$this->error('INVALID_BIRTH_TIME',fn()=> $s->resolve('2020-01-01','LOC000001',$time));}
        $this->error('REFERENCE_INCOMPATIBLE',fn()=> (new LocationReferenceRepository('https://invalid.example'))->get('LOC000001'));
        $path=tempnam(sys_get_temp_dir(),'lf-natal-fixture-');
        try{
            file_put_contents($path,'{"version":"SAJU_LOCATION_REFERENCE_V1","records":[]}');
            $this->error('LOCATION_VERSION_MISMATCH',fn()=> (new LocationReferenceRepository($path))->get('LOC000001'));
            file_put_contents($path,'{"version":"SAJU_TIMEZONE_REFERENCE_V1","zones":[]}');
            $this->error('TIMEZONE_VERSION_MISMATCH',fn()=> (new TimezoneReferenceRepository($path))->intervals('Asia/Tokyo'));
            $this->error('CANDIDATE_RESOLUTION_FAILED',fn()=> (new NatalResolutionService(solar:new SolarTermReference($path)))->resolve('2020-01-01','LOC000001','12:00'));
        }finally{unlink($path);}
    }

    public function testNoOsTimezoneDependenceAndNoPersistence(): void
    {
        $s=new NatalResolutionService();$expected=$s->resolve('1900-01-01','LOC000002','12:00');$old=date_default_timezone_get();$writes=$GLOBALS['writes'];
        try{foreach(['UTC','Pacific/Apia','America/New_York'] as $zone){date_default_timezone_set($zone);self::assertSame($expected,$s->resolve('1900-01-01','LOC000002','12:00'));}}
        finally{date_default_timezone_set($old);}
        $plugin=PluginFactory::create(dirname(__DIR__,2));self::assertSame(NatalResolutionService::VERSION,$plugin->versions->get('saju.natal_resolver'));
        self::assertSame($expected,$plugin->engines->get('saju.natal_resolver')->resolve('1900-01-01','LOC000002','12:00'));self::assertSame($writes,$GLOBALS['writes']);
        $dir=dirname(__DIR__,2).'/src/Engine/Saju/';
        foreach(['NatalReference','LocationReferenceRepository','TimezoneReferenceRepository','NatalCivilTime','NatalLocalTimeResolver','NatalCandidateContextFactory','NatalCandidatePartitioner','NatalResolutionService'] as $name){
            $source=implode('',array_map(static fn($t)=>is_array($t)?(in_array($t[0],[T_COMMENT,T_DOC_COMMENT],true)?'':$t[1]):$t,token_get_all(file_get_contents($dir.$name.'.php'))));
            self::assertDoesNotMatchRegularExpression('/\b(?:error_log|file_put_contents|wp_remote_post|curl_exec|set_transient|update_option|wpdb|mysqli|PDO)\b/',$source);
            if($name!=='NatalCivilTime'){self::assertStringNotContainsString('DateTimeZone',$source);}
        }
    }
}
