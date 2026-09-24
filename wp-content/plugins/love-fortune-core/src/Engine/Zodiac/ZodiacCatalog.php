<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Zodiac;

/** Loads the generated, code-owned projection; never request-selected configuration. */
final class ZodiacCatalog
{
    public static function configuration(): array
    {
        static $configuration = null;
        return $configuration ??= require dirname(__DIR__, 3) . '/config/zodiac.php';
    }

    public static function allows(string $source, string $period): bool
    {
        $scope = self::configuration()['scope'];
        $key = match ($period) {
            'LIFETIME' => 'lifetimeSources',
            'DAILY' => 'dailySignalSources',
            default => null,
        };
        return $key !== null && in_array($source, $scope[$key], true);
    }
}
