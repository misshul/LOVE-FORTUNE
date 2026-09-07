<?php
declare(strict_types=1);

namespace LoveFortune\Core\Support;

use InvalidArgumentException;
use OutOfBoundsException;

/** Immutable code/config metadata. Database activation policy belongs to T02/T03. */
final class VersionRegistry implements VersionRegistryInterface
{
    public function __construct(private readonly array $versions)
    {
        foreach ($versions as $type => $version) {
            if (!is_string($type) || $type === '' || !is_string($version) || trim($version) === '') {
                throw new InvalidArgumentException('Invalid version registration.');
            }
        }
    }

    public function get(string $type): string
    {
        return $this->versions[$type] ?? throw new OutOfBoundsException('Version is not registered.');
    }

    public function all(): array
    {
        return $this->versions;
    }
}
