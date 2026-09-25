<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

use InvalidArgumentException;
use OutOfRangeException;
use UnexpectedValueException;

/** Local, immutable FP-01 reference. No time conversion or astronomy at runtime. */
final class SolarTermReference
{
    public const VERSION = 'NAOJ_SOLAR_TERMS_1899_2100_V1';
    public const BRIDGE = 'SAJU_TIME_SCALE_BRIDGE_V1';
    public const CHECKSUM = '6fc6165b76951a0267d46fe2a7e7d4da190c7e2119dc8d1ffdef12fed399848c';
    public const FILE_SHA256 = '948f243c049ede8519b8e998d191d6e2a9a49c837120836d101e38b67f332943';
    public const JIE = ['LICHUN', 'JINGZHE', 'QINGMING', 'LIXIA', 'MANGZHONG', 'XIAOSHU', 'LIQIU', 'BAILU', 'HANLU', 'LIDONG', 'DAXUE', 'XIAOHAN'];
    private const TERMS = ['XIAOHAN', 'DAHAN', 'LICHUN', 'YUSHUI', 'JINGZHE', 'CHUNFEN', 'QINGMING', 'GUYU', 'LIXIA', 'XIAOMAN', 'MANGZHONG', 'XIAZHI', 'XIAOSHU', 'DASHU', 'LIQIU', 'CHUSHU', 'BAILU', 'QIUFEN', 'HANLU', 'SHUANGJIANG', 'LIDONG', 'XIAOXUE', 'DAXUE', 'DONGZHI'];
    private ?array $lichun = null;
    private array $jie = [];

    public function __construct(private readonly string $path = __DIR__ . '/../../../config/references/solar-terms-v1.json') {}

    /** Reject noncanonical strings and overflow before casting; never pass through a float. */
    public static function coordinate(string $value): int
    {
        if (PHP_INT_SIZE !== 8) {
            throw new UnexpectedValueException('SOLAR_REFERENCE_REQUIRES_64_BIT');
        }
        if (!preg_match('/^(?:0|-?[1-9][0-9]*)$/D', $value)
            || filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('INVALID_SERVICE_COORDINATE');
        }
        return (int) $value;
    }

    private function load(): void
    {
        if ($this->lichun !== null) {
            return;
        }
        // Only filesystem paths, including Windows drive paths; reject PHP/HTTP stream wrappers.
        if (str_contains($this->path, '://') || !is_file($this->path) || !is_readable($this->path)) {
            throw new UnexpectedValueException('SOLAR_REFERENCE_UNAVAILABLE');
        }
        $bytes = @file_get_contents($this->path);
        if ($bytes === false) {
            throw new UnexpectedValueException('SOLAR_REFERENCE_UNAVAILABLE');
        }
        // Pin the approved bytes as well as the embedded payload identity. A self-rehashed
        // replacement is not an approved artifact. No runtime canonicalization/rebuild.
        if (!hash_equals(self::FILE_SHA256, hash('sha256', $bytes))) {
            throw new UnexpectedValueException('SOLAR_REFERENCE_IDENTITY_MISMATCH');
        }
        $data = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
        $meta = $data['metadata'];
        if ($meta['referenceVersion'] !== self::VERSION || $meta['bridgeVersion'] !== self::BRIDGE
            || $meta['artifactChecksum'] !== self::CHECKSUM
            || $meta['comparisonAxis'] !== 'SERVICE_PROLEPTIC_POSIX_V1'
            || $meta['sourcePrecision'] !== 'MINUTE' || $meta['eventCount'] !== 4848
            || count($data['events']) !== 4848) {
            throw new UnexpectedValueException('SOLAR_REFERENCE_METADATA_MISMATCH');
        }
        $lichun = $jie = [];
        $previous = null;
        foreach ($data['events'] as $i => $event) {
            $year = 1899 + intdiv($i, 24);
            $term = self::TERMS[$i % 24];
            $boundary = self::coordinate($event['serviceBoundaryUs']);
            if ($event['year'] !== $year || $event['termId'] !== $term
                || $event['eventId'] !== "SOLAR_{$year}_{$term}"
                || $event['solarReferenceVersion'] !== self::VERSION
                || $event['timeScaleBridgeVersion'] !== self::BRIDGE
                || ($previous !== null && $boundary <= $previous)) {
                throw new UnexpectedValueException('SOLAR_REFERENCE_EVENT_SEQUENCE_INVALID');
            }
            $previous = $boundary;
            $row = ['eventId' => $event['eventId'], 'year' => $year, 'termId' => $term, 'boundaryUs' => $boundary];
            if ($term === 'LICHUN') {
                $lichun[] = $row;
            }
            if (in_array($term, self::JIE, true)) {
                $jie[] = $row;
            }
        }
        $this->jie = $jie;
        $this->lichun = $lichun;
    }

    /** Shared supported envelope: [Lichun 1899, Lichun 2100). Public date validation is upstream. */
    public function interval(string $resolvedServiceCoordinateUs, bool $month = false): array
    {
        $coordinate = self::coordinate($resolvedServiceCoordinateUs);
        $this->load();
        if ($coordinate < $this->lichun[0]['boundaryUs']
            || $coordinate >= $this->lichun[count($this->lichun) - 1]['boundaryUs']) {
            throw new OutOfRangeException('SOLAR_REFERENCE_COORDINATE_OUT_OF_RANGE');
        }
        $rows = $month ? $this->jie : $this->lichun;
        $lo = 0;
        $hi = count($rows) - 1;
        while ($lo + 1 < $hi) {
            $mid = intdiv($lo + $hi, 2);
            if ($rows[$mid]['boundaryUs'] <= $coordinate) {
                $lo = $mid;
            } else {
                $hi = $mid;
            }
        }
        return [$rows[$lo], $rows[$hi]];
    }

    public function provenance(): array
    {
        $this->load();
        return ['solarTermReferenceVersion' => self::VERSION, 'timeScaleBridgeVersion' => self::BRIDGE,
            'artifactChecksum' => self::CHECKSUM];
    }
}
