<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

use RuntimeException;

/** Static reference bytes only. No user data, network wrappers or dynamic identity override. */
final class NatalReference
{
    public static function load(string $path, string $version, string $hash, string $error): array
    {
        if (PHP_INT_SIZE !== 8 || str_contains($path, '://') || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException('REFERENCE_INCOMPATIBLE');
        }
        $bytes = @file_get_contents($path);
        if ($bytes === false || !hash_equals($hash, hash('sha256', $bytes))) {
            throw new RuntimeException($error);
        }
        $data = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
        if (($data['version'] ?? null) !== $version) {
            throw new RuntimeException($error);
        }
        return $data;
    }
}
