<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

use LoveFortune\Core\Support\Rational as R;
use RuntimeException;

/** Byte-pinned deployment copy of the approved catalog; no runtime downloads. */
final class SajuFeatureCatalog
{
    public const VERSION = '1.0.0';
    public const SHA256 = '3d580c733e91c9aff32373c684deda4063895d7671cc54b1f6cb7ed0b6bd2c3f';
    private array $data;

    public function __construct(string $path = __DIR__ . '/../../../config/references/saju-rules-v1.json')
    {
        if (str_contains($path, '://') || !is_file($path)) { throw new RuntimeException('FEATURE_CATALOG_MISMATCH'); }
        $bytes = file_get_contents($path);
        if ($bytes === false || hash('sha256', $bytes) !== self::SHA256) { throw new RuntimeException('FEATURE_CATALOG_MISMATCH'); }
        $this->data = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
    }

    public function tables(): array { return $this->data['referenceTables']; }
    public function rules(): array { return $this->data['rules']; }
    public function rule(string $id): array
    {
        foreach ($this->rules() as $rule) { if ($rule['ruleId'] === $id) { return $rule; } }
        throw new RuntimeException('FEATURE_CATALOG_MISMATCH');
    }
    public function variant(string $id, string $variant): array
    {
        foreach ($this->rule($id)['variants'] as $v) { if ($v['variant'] === $variant) { return $v; } }
        throw new RuntimeException('FEATURE_CATALOG_MISMATCH');
    }
    public function weight(string $id): R
    {
        $r = $this->rule($id);
        return R::of((string)$r['baseWeight'])->multiply(R::of((string)$r['ruleWeight']))->multiply(R::of((string)$r['pairWeight']));
    }
}
