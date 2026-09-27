<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

use RuntimeException;

final class LocationReferenceRepository implements LocationReferenceProvider
{
    public const VERSION = 'SAJU_LOCATION_REFERENCE_V1';
    public const FILE_SHA256 = '5261b7cd37776a6d6b25aea4d32e0dd8c5c073e9bcde29b6daec08800049b152';
    private ?array $records = null;

    public function __construct(private readonly string $path = __DIR__ . '/../../../config/references/locations-v1.json') {}

    public function get(string $locationId): array
    {
        if ($this->records === null) {
            $data = NatalReference::load($this->path, self::VERSION, self::FILE_SHA256, 'LOCATION_VERSION_MISMATCH');
            $this->records = array_column($data['records'], null, 'locationId');
        }
        return $this->records[$locationId] ?? throw new RuntimeException('LOCATION_NOT_FOUND');
    }
}
