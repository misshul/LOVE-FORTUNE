<?php
declare(strict_types=1);

namespace LoveFortune\Core\Application\Score;

use LoveFortune\Core\Domain\Score\CategoryResult;
use LoveFortune\Core\Engine\Saju\SajuLifetimeScoringService;
use LoveFortune\Core\Support\Rational;

/** Trusted in-process source result. No public parser or numeric recalculation. */
final readonly class LifetimeSourceEnvelope
{
    public const CATEGORIES = ['ATTRACTION','EMOTION','COMMUNICATION','PASSION','STABILITY','HARMONY','SUPPORT','LONG_TERM'];
    public const ELIGIBLE = [
        'SAJU' => ['ATTRACTION','EMOTION','PASSION','STABILITY','HARMONY','SUPPORT','LONG_TERM'],
        'ZODIAC' => ['ATTRACTION','EMOTION','PASSION','HARMONY'],
    ];
    public const IDENTITIES = [
        'SAJU' => ['sourceVersion'=>'SAJU_LIFETIME_SCORING_V1','catalogVersion'=>'1.0.0'],
        'ZODIAC' => ['sourceVersion'=>'FIXED_DATE_SINGLE_SIGN_V1','catalogVersion'=>'ZODIAC_CATALOG_V1','dateRangeVersion'=>'ZODIAC_DATE_RANGE_V1'],
    ];

    public string $sourceId;
    public array $versions;
    public array $categories;
    public ?array $sourceContext;

    public function __construct(string $sourceId, array $versions, array $categories, ?array $sourceContext = null)
    {
        if (!isset(self::ELIGIBLE[$sourceId])) { throw new \InvalidArgumentException('INVALID_COMBINED_SOURCE'); }
        $versions = self::canonical($versions);
        if ($versions !== self::canonical(self::IDENTITIES[$sourceId])) { throw new \InvalidArgumentException('SOURCE_VERSION_MISMATCH'); }
        self::checkKeys($categories, self::CATEGORIES);
        $ordered = [];
        foreach (self::CATEGORIES as $category) {
            $row = $categories[$category];
            if (!$row instanceof CategoryResult) { throw new \InvalidArgumentException('INVALID_COMBINED_CATEGORY'); }
            // CategoryResult is final/readonly and validates ranges and null-confidence at construction.
            if (!in_array($category, self::ELIGIBLE[$sourceId], true)
                && ($row->score !== null || !$row->coverage->isZero() || !$row->confidence->isZero())) {
                throw new \InvalidArgumentException('INELIGIBLE_COMBINED_CATEGORY');
            }
            $ordered[$category] = $row;
        }
        if ($sourceContext !== null && $sourceId === 'ZODIAC') {
            if (($sourceContext['source'] ?? null) !== 'ZODIAC'
                || ($sourceContext['contextRole'] ?? null) !== 'STATIC'
                || ($sourceContext['modelVersion'] ?? null) !== $versions['sourceVersion']
                || ($sourceContext['dateRangeVersion'] ?? null) !== $versions['dateRangeVersion']) {
                throw new \InvalidArgumentException('SOURCE_CONTEXT_MISMATCH');
            }
        }
        $this->sourceId = $sourceId;
        $this->versions = $versions;
        $this->categories = $ordered;
        $this->sourceContext = $sourceContext === null ? null : self::canonical($sourceContext);
    }

    /** Normalize only structural absence; a missing eligible output is always an error. */
    public static function fromEligible(string $sourceId, array $versions, array $categories, ?array $context = null): self
    {
        if (!isset(self::ELIGIBLE[$sourceId])) { throw new \InvalidArgumentException('INVALID_COMBINED_SOURCE'); }
        self::checkKeys($categories, self::ELIGIBLE[$sourceId]);
        $complete = [];
        foreach (self::CATEGORIES as $category) {
            $complete[$category] = in_array($category, self::ELIGIBLE[$sourceId], true)
                ? $categories[$category] : CategoryResult::unavailable();
        }
        return new self($sourceId, $versions, $complete, $context);
    }

    /** Preserve the production Saju result's identity and parallel Ten Gods context. */
    public static function fromSaju(array $result): self
    {
        if (($result['scoringVersion'] ?? null) !== SajuLifetimeScoringService::VERSION
            || !is_array($result['dependencies'] ?? null)
            || self::canonical($result['dependencies']) !== self::canonical(SajuLifetimeScoringService::DEPENDENCIES)) {
            throw new \InvalidArgumentException('SOURCE_VERSION_MISMATCH');
        }
        if (!is_array($result['categories'] ?? null) || !is_array($result['tenGods'] ?? null)) {
            throw new \InvalidArgumentException('INVALID_COMBINED_SOURCE');
        }
        return self::fromEligible('SAJU', [
            'sourceVersion'=>$result['scoringVersion'], 'catalogVersion'=>$result['dependencies']['catalogVersion'],
        ], $result['categories'], $result['tenGods']);
    }

    public function blenderCategories(): array
    {
        return array_intersect_key($this->categories, array_flip(self::ELIGIBLE[$this->sourceId]));
    }

    private static function checkKeys(array $map, array $expected): void
    {
        // Lists (including duplicate-category row lists) are never converted to maps.
        foreach (array_keys($map) as $key) {
            if (!is_string($key) || !in_array($key, $expected, true)) { throw new \InvalidArgumentException('INVALID_COMBINED_CATEGORY'); }
        }
        foreach ($expected as $key) {
            if (!array_key_exists($key, $map)) { throw new \InvalidArgumentException('MISSING_REQUIRED_CATEGORY'); }
        }
    }

    /** Sort object keys, preserve semantic list order and immutable exact values. */
    public static function canonical(array $value): array
    {
        foreach ($value as &$item) {
            if (is_array($item)) { $item = self::canonical($item); }
            elseif (!is_null($item) && !is_scalar($item) && !$item instanceof Rational) {
                throw new \InvalidArgumentException('INVALID_COMBINED_CONTEXT');
            }
        }
        unset($item);
        if (!array_is_list($value)) { ksort($value, SORT_STRING); }
        return $value;
    }
}
