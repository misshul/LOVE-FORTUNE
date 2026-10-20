<?php
declare(strict_types=1);
namespace LoveFortune\Core\Support;

/** Process-local configuration only. Never exports secrets into the environment. */
final class RuntimeConfig
{
    public const KEYS = [
        'LOVE_FORTUNE_SIGNING_KID', 'LOVE_FORTUNE_SIGNING_KEYS', 'LOVE_FORTUNE_SIGNING_PREVIOUS_KID',
        'LOVE_FORTUNE_RATE_SECRET', 'LOVE_FORTUNE_RATE_ROOT', 'LOVE_FORTUNE_RATE_SITE',
        'LOVE_FORTUNE_AI_ENABLED', 'LOVE_FORTUNE_AI_ENDPOINT', 'LOVE_FORTUNE_AI_KEY',
        'LOVE_FORTUNE_AI_PROVIDER', 'LOVE_FORTUNE_AI_MODEL',
    ];
    private static array $snapshots = [];

    public static function reset(): void { self::$snapshots = []; }

    public static function get(string $name): string|false
    {
        if (!in_array($name, self::KEYS, true)) { throw new RuntimeConfigException(); }
        $envPath = getenv('LOVE_FORTUNE_CONFIG_FILE');
        $constantPath = defined('LOVE_FORTUNE_CONFIG_FILE') ? constant('LOVE_FORTUNE_CONFIG_FILE') : null;
        if (defined('LOVE_FORTUNE_CONFIG_FILE') && !is_string($constantPath)) { throw new RuntimeConfigException(); }
        if ($envPath !== false && $constantPath !== null && $envPath !== $constantPath) { throw new RuntimeConfigException(); }
        $path = $envPath !== false ? $envPath : $constantPath;
        $private = [];
        if ($path !== null) {
            // An explicitly selected broken source is an error even when ENV supplies this key.
            if (!array_key_exists($path, self::$snapshots)) { self::$snapshots[$path] = (new PrivateJsonConfig())->load($path); }
            $private = self::$snapshots[$path];
        }
        $value = getenv($name);
        return $value !== false ? $value : ($private[$name] ?? false);
    }
}
