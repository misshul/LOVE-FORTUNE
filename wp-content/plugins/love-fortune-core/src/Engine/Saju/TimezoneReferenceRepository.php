<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

use RuntimeException;

final class TimezoneReferenceRepository
{
    public const VERSION = 'SAJU_TIMEZONE_REFERENCE_V1';
    public const FILE_SHA256 = 'ee74cc16f926c0076ff0de5acf3df72070681e0422cdcf25076e141c5fc8a79f';
    private ?array $zones = null;
    private array $aliases = [];
    private int $endUs;

    public function __construct(private readonly string $path = __DIR__ . '/../../../config/references/timezones-v1.json') {}

    /** Canonical zones only. A lineage is the immutable source interval, not a birth hash. */
    public function intervals(string $timezoneId): array
    {
        if ($this->zones === null) {
            $data = NatalReference::load($this->path, self::VERSION, self::FILE_SHA256, 'TIMEZONE_VERSION_MISMATCH');
            $this->zones = array_column($data['zones'], null, 'timezoneId');
            $this->aliases = $data['aliases'];
            $this->endUs = SolarTermReference::coordinate($data['supportedToExclusiveUs']);
        }
        $zone = $this->zones[$timezoneId] ?? throw new RuntimeException('TIMEZONE_NOT_FOUND');
        $rows = $zone['transitions'];
        $result = [];
        foreach ($rows as $i => $r) {
            $result[] = [
                'startUs' => (int) $r['serviceStartUs'],
                'endUs' => isset($rows[$i + 1]) ? (int) $rows[$i + 1]['serviceStartUs'] : $this->endUs,
                'historicalOffsetSeconds' => $r['offsetSeconds'], 'isDst' => $r['isDst'],
                'transitionIdentity' => self::VERSION . ':' . $timezoneId . ':' . $r['serviceStartUs'],
            ];
        }
        return $result;
    }

    /** Only aliases from the byte-pinned artifact are accepted. */
    public function canonicalize(string $zone): string
    {
        if ($this->zones === null) { $this->intervals('Asia/Seoul'); }
        $canonical = $this->aliases[$zone] ?? $zone;
        if (!isset($this->zones[$canonical])) { throw new \InvalidArgumentException('UNSUPPORTED_TIMEZONE'); }
        return $canonical;
    }
}
