<?php
declare(strict_types=1);

namespace LoveFortune\Core\Support;

interface VersionRegistryInterface
{
    public function get(string $type): string;

    /** @return array<string, string> */
    public function all(): array;
}
